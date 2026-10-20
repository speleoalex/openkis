<?php

/**
 * OpenKIS MCP server
 *
 * Model Context Protocol server (transport "Streamable HTTP", JSON responses) that exposes
 * read/write access to the OpenKIS database. Output is CSV to keep token usage low.
 * Records of dbview sections are written through the same logic used by the web UI
 * (history in <table>_versions, recordupdate/userupdate, fieldusers, function_on_insert/update/delete,
 * list cache invalidation).
 *
 * Authentication: every request carries an API key (header "X-API-Key: <key>",
 * "Authorization: Bearer <key>" or ?apikey=<key>).
 *
 *  - User keys (recommended): an administrator enables the user (group $_FN['apikey_groups'],
 *    default "mcp_users", see extra/openkis_config.inc.php), then the user generates a personal
 *    key in the section openkis_mcp (Configuration > MCP server) or in the profile page; the key
 *    is stored in fn_users.apikey. The keys of the users not enabled are refused.
 *    The write tools (insert, update, upload, delete) are available only to the administrators
 *    and to the users an administrator allowed to write (group $_FN['openkis_mcp_write_group'],
 *    default "mcp_write"); for the other users every section is read only.
 *    The server acts as the key owner with the same permissions of the web UI:
 *    administrators (level >= 10) have full access; the other users see only the dbview
 *    sections they can view, write only where the section allows them (groupinsert,
 *    groupadmin, record owner, fieldusers), can read the lookup tables referenced by those
 *    sections and cannot use the sql tool. Changes are recorded with their username.
 *  - Global key (legacy): $_FN['openkis_mcp_apikey'] gives full access and records the
 *    changes as $_FN['openkis_mcp_user'].
 *
 * Configuration (openkis_config.local.php):
 *   $_FN['openkis_mcp_apikey']    = "long-random-key"; // optional legacy key(s), string or array
 *   $_FN['openkis_mcp_user']      = "admin";           // author of the changes made with the legacy key
 *   $_FN['openkis_mcp_readonly']  = 0;                 // 1 = disable insert/update/upload/delete for everybody
 *   $_FN['openkis_mcp_userkeys']  = 1;                 // 0 = refuse the personal keys of the users
 *   $_FN['openkis_mcp_write_group'] = "mcp_write";     // users allowed to use the write tools
 *
 * Client:
 *   claude mcp add --transport http openkis https://<site>/openkis_mcp.php --header "X-API-Key: <apikey>"
 *   ("Authorization: Bearer <apikey>" works too where the web server passes it to PHP)
 *
 * @package OpenKIS
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
global $_FN;
ob_start();
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
ob_end_clean();
ini_set("display_errors", "off");

define("MCP_PROTOCOL_VERSION", "2025-06-18");
define("MCP_QUERY_DEFAULT_LIMIT", 50);
define("MCP_QUERY_MAX_LIMIT", 1000);

/**
 * Exposes the protected dbview helpers (history, field transformations)
 */
class OpenkisMcpDbview extends FNDBVIEW
{
    function SaveHistory($tablename, $Form, $oldvalues)
    {
        $this->saveRecordHistory($tablename, $Form, $oldvalues);
    }

    function Transform($values, $Form)
    {
        $post_bk = $_POST;
        $values = $this->applyFieldTransformations($values, $Form);
        $_POST = $post_bk;
        return $values;
    }
}

class McpToolError extends Exception
{

}

//--------------------------------- HTTP -------------------------------------->
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-cache");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, Mcp-Session-Id, Mcp-Protocol-Version");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : "";
if ($method == "OPTIONS")
{
    exit();
}
if (!mcp_Authenticate())
{
    http_response_code(401);
    header('WWW-Authenticate: Bearer realm="openkis_mcp"');
    echo json_encode(array("jsonrpc" => "2.0", "id" => null, "error" => array("code" => -32001, "message" => "Unauthorized")));
    exit();
}
if ($method == "GET")
{
    // no server-initiated SSE stream
    http_response_code(405);
    header("Allow: POST, OPTIONS");
    exit();
}
if ($method != "POST")
{
    http_response_code(405);
    exit();
}
$request = json_decode(file_get_contents("php://input"), true);
if (!is_array($request))
{
    http_response_code(400);
    echo json_encode(array("jsonrpc" => "2.0", "id" => null, "error" => array("code" => -32700, "message" => "Parse error")));
    exit();
}
$isBatch = isset($request[0]);
$responses = array();
foreach (($isBatch ? $request : array($request)) as $message)
{
    $response = mcp_HandleMessage($message);
    if ($response !== null)
    {
        $responses[] = $response;
    }
}
if (!$responses)
{
    http_response_code(202); // only notifications
    exit();
}
echo json_encode($isBatch ? $responses : $responses[0], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
exit();
//--------------------------------- HTTP --------------------------------------<

/**
 * Identifies the caller. A legacy global key gives full access as $_FN['openkis_mcp_user'],
 * a personal key logs in its owner (cookie sessions are ignored: only the key counts).
 * Sets $_FN['user'], $_FN['openkis_mcp_access'] ("full" or "user") and
 * $_FN['openkis_mcp_canwrite'] (write tools allowed).
 * @return bool
 */
function mcp_Authenticate()
{
    global $_FN;
    $given = mcp_RequestApiKey();
    $_FN['openkis_mcp_access'] = "";
    $_FN['openkis_mcp_canwrite'] = false;
    if ($given == "")
        return false;
    foreach ((array) (isset($_FN['openkis_mcp_apikey']) ? $_FN['openkis_mcp_apikey'] : array()) as $key)
    {
        if ($key != "" && hash_equals((string) $key, $given))
        {
            $_FN['user'] = !empty($_FN['openkis_mcp_user']) ? $_FN['openkis_mcp_user'] : "mcp";
            $_FN['uservalues'] = FN_GetUser($_FN['user'], false);
            $_FN['openkis_mcp_access'] = "full";
            $_FN['openkis_mcp_canwrite'] = true;
            return true;
        }
    }
    $_FN['user'] = "";
    $_FN['uservalues'] = array();
    if (isset($_FN['openkis_mcp_userkeys']) && !$_FN['openkis_mcp_userkeys'])
        return false;
    if (!function_exists("FN_LoginByApiKey") || !FN_LoginByApiKey($given))
        return false;
    $_FN['openkis_mcp_access'] = FN_IsAdmin($_FN['user']) ? "full" : "user";
    $_FN['openkis_mcp_canwrite'] = mcp_FullAccess() || mcp_UserInWriteGroup($_FN['uservalues']);
    return true;
}

/**
 * @param array $uservalues
 * @return bool true if the user belongs to the group allowed to use the write tools
 */
function mcp_UserInWriteGroup($uservalues)
{
    global $_FN;
    $group = isset($_FN['openkis_mcp_write_group']) ? trim($_FN['openkis_mcp_write_group']) : "mcp_write";
    if ($group == "" || empty($uservalues['group']))
        return false;
    return in_array($group, array_map("trim", explode(",", $uservalues['group'])));
}

/**
 * @return bool true if the current key can use the write tools
 */
function mcp_CanWrite()
{
    global $_FN;
    return !empty($_FN['openkis_mcp_canwrite']);
}

/**
 * @return string API key sent by the client
 */
function mcp_RequestApiKey()
{
    $auth = "";
    if (!empty($_SERVER['HTTP_AUTHORIZATION']))
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']))
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    elseif (function_exists("getallheaders"))
    {
        foreach (getallheaders() as $k => $v)
        {
            if (strtolower($k) == "authorization")
                $auth = $v;
        }
    }
    if (preg_match('/^Bearer\s+(.+)$/i', trim($auth), $m))
        return trim($m[1]);
    if (!empty($_SERVER['HTTP_X_API_KEY']))
        return trim($_SERVER['HTTP_X_API_KEY']);
    if (!empty($_GET['apikey']) && is_string($_GET['apikey']))
        return trim($_GET['apikey']);
    return "";
}

/**
 * @param array $message
 * @return array|null
 */
function mcp_HandleMessage($message)
{
    if (!is_array($message) || !isset($message['method']))
    {
        return array("jsonrpc" => "2.0", "id" => null, "error" => array("code" => -32600, "message" => "Invalid Request"));
    }
    $id = isset($message['id']) ? $message['id'] : null;
    $params = isset($message['params']) && is_array($message['params']) ? $message['params'] : array();
    if (!array_key_exists('id', $message))
    {
        return null; // notification (notifications/initialized, notifications/cancelled...)
    }
    switch ($message['method'])
    {
        case "initialize":
            $version = !empty($params['protocolVersion']) ? $params['protocolVersion'] : MCP_PROTOCOL_VERSION;
            if (!in_array($version, array("2024-11-05", "2025-03-26", "2025-06-18")))
                $version = MCP_PROTOCOL_VERSION;
            $result = array(
                "protocolVersion" => $version,
                "capabilities" => array("tools" => new stdClass()),
                "serverInfo" => array("name" => "openkis", "version" => "2.0.0"),
                "instructions" => "OpenKIS speleological cadastre DB. Data is CSV. Start with `sections` (dbview sections and their tables) then `schema`. Prefer `section` over `table` for catalogue data: writes then keep history and run the section hooks. Values are read and written in storage format. " . mcp_AccessDescription()
            );
            break;
        case "ping":
            $result = new stdClass();
            break;
        case "tools/list":
            $result = array("tools" => mcp_Tools());
            break;
        case "tools/call":
            $result = mcp_CallTool(isset($params['name']) ? $params['name'] : "", isset($params['arguments']) && is_array($params['arguments']) ? $params['arguments'] : array());
            break;
        default:
            return array("jsonrpc" => "2.0", "id" => $id, "error" => array("code" => -32601, "message" => "Method not found"));
    }
    return array("jsonrpc" => "2.0", "id" => $id, "result" => $result);
}

/**
 * @return array
 */
function mcp_Tools()
{
    $src = array(
        "section" => array("type" => "string", "description" => "dbview section id (e.g. caves)"),
        "table" => array("type" => "string", "description" => "table name (e.g. ctl_caves); alternative to section")
    );
    $values = array(
        "data" => array("description" => "record object {field:value} or array of record objects"),
        "csv" => array("type" => "string", "description" => "alternative to data: CSV with header row, one record per row")
    );
    $tools = array();
    $tools[] = array(
        "name" => "whoami",
        "description" => "User of the API key, its groups and access level",
        "inputSchema" => array("type" => "object", "properties" => new stdClass())
    );
    $tools[] = array(
        "name" => "sections",
        "description" => "List the dbview sections readable with this key: id,table,title,parent,history,delete,access (admin = edit all records, write = insert records and edit the records allowed by the section rules, read = read only)",
        "inputSchema" => array("type" => "object", "properties" => new stdClass())
    );
    $tools[] = array(
        "name" => "tables",
        "description" => "List database tables readable with this key: table,driver,section",
        "inputSchema" => array("type" => "object", "properties" => new stdClass())
    );
    $tools[] = array(
        "name" => "schema",
        "description" => "Fields of a table/section as CSV: name,type,flags(pk/ai/req/uniq/ro/hidden),fk(table.link>show),options,label",
        "inputSchema" => array("type" => "object", "properties" => $src)
    );
    $tools[] = array(
        "name" => "query",
        "description" => "Read records as CSV. Last line: #offset,rows,total. Long values are cut to maxlen (ending with …); use get for full values. Records hidden by delete (recorddeleted=1) are excluded unless deleted=true.",
        "inputSchema" => array("type" => "object", "properties" => $src + array(
        "fields" => array("type" => "string", "description" => "comma list, * = all. Default: pk+title fields for sections, all for tables"),
        "filter" => array("type" => "object", "description" => "{field:value} AND-ed; value with % = LIKE pattern"),
        "where" => array("type" => "string", "description" => "SQL WHERE condition (mysql tables only, no subqueries), e.g. depth_total>100 AND comune='FINALE LIGURE'"),
        "order" => array("type" => "string", "description" => "e.g. name or depth_total:DESC,name"),
        "limit" => array("type" => "integer", "description" => "default " . MCP_QUERY_DEFAULT_LIMIT . ", max " . MCP_QUERY_MAX_LIMIT),
        "offset" => array("type" => "integer"),
        "maxlen" => array("type" => "integer", "description" => "max chars per value, default 100, 0 = no limit"),
        "count" => array("type" => "boolean", "description" => "return only total=N"),
        "deleted" => array("type" => "boolean")
        ))
    );
    $tools[] = array(
        "name" => "get",
        "description" => "Full record by primary key as CSV field,value (empty fields omitted unless empty=true)",
        "inputSchema" => array("type" => "object", "properties" => $src + array(
        "id" => array("type" => "string", "description" => "primary key value"),
        "fields" => array("type" => "string", "description" => "comma list, default all"),
        "empty" => array("type" => "boolean")
        ), "required" => array("id"))
    );
    if (mcp_FullAccess())
        $tools[] = array(
        "name" => "sql",
        "description" => "Read-only SQL on MySQL (SELECT/SHOW/DESCRIBE/EXPLAIN, single statement). Result as CSV. Administrators only",
        "inputSchema" => array("type" => "object", "properties" => array(
            "query" => array("type" => "string"),
            "limit" => array("type" => "integer", "description" => "max rows returned, default 200"),
            "maxlen" => array("type" => "integer", "description" => "max chars per value, default 0 = no limit")
            ), "required" => array("query"))
    );
    if (!mcp_CanWrite())
        return $tools;
    $tools[] = array(
        "name" => "insert",
        "description" => "Insert one or more records. Returns CSV row,id,result. Sections: sets recordinsert/username, runs function_on_insert (e.g. cadastre number assignment)",
        "inputSchema" => array("type" => "object", "properties" => $src + $values)
    );
    $tools[] = array(
        "name" => "update",
        "description" => "Update records; only given fields change. Each record needs the pk (or pass id with a single data object). Sections: previous version saved to history, sets recordupdate/userupdate, runs function_on_update. Returns CSV id,result,changed_fields",
        "inputSchema" => array("type" => "object", "properties" => $src + array("id" => array("type" => "string", "description" => "pk when data is a single object")) + $values)
    );
    $tools[] = array(
        "name" => "upload",
        "description" => "Set a file/image field of a record (thumbnail, history and hooks as in update). The file comes from exactly one of: content_base64 (small files), url (downloaded by the server), from_id+from_field (file of another record, from_table defaults to the same table). clear=true empties the field and deletes its file. Returns CSV id,result,field,file",
        "inputSchema" => array("type" => "object", "properties" => $src + array(
        "id" => array("type" => "string", "description" => "primary key of the record"),
        "field" => array("type" => "string", "description" => "file/image field"),
        "filename" => array("type" => "string", "description" => "stored file name; default: name of the source file"),
        "content_base64" => array("type" => "string"),
        "url" => array("type" => "string", "description" => "http(s) url"),
        "from_table" => array("type" => "string"),
        "from_id" => array("type" => "string"),
        "from_field" => array("type" => "string"),
        "clear" => array("type" => "boolean")
        ), "required" => array("id", "field"))
    );
    $tools[] = array(
        "name" => "delete",
        "description" => "Delete records by pk. Sections: allowed only if the section enables delete; hide_on_delete sections set recorddeleted=1 instead",
        "inputSchema" => array("type" => "object", "properties" => $src + array("ids" => array("type" => "string", "description" => "comma separated primary keys")), "required" => array("ids"))
    );
    return $tools;
}

/**
 * @param string $name
 * @param array $args
 * @return array
 */
function mcp_CallTool($name, $args)
{
    global $_FN;
    $writeTools = array("insert", "update", "upload", "delete");
    try
    {
        $function = "mcp_Tool_" . $name;
        if (!preg_match('/^[a-z]+$/', $name) || !function_exists($function))
            throw new McpToolError("unknown tool $name");
        if (in_array($name, $writeTools) && !empty($_FN['openkis_mcp_readonly']))
            throw new McpToolError("server is read-only");
        if (in_array($name, $writeTools) && !mcp_CanWrite())
            throw new McpToolError("permission denied: the write tools are not enabled for this user, ask an administrator");
        if ($name == "sql" && !mcp_FullAccess())
            throw new McpToolError("permission denied: sql requires an administrator key");
        ob_start();
        $text = $function($args);
        ob_end_clean();
        if (in_array($name, $writeTools))
            FN_Log("openkis_mcp||" . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "") . "||{$_FN['user']}||$name " . (isset($args['section']) ? $args['section'] : (isset($args['table']) ? $args['table'] : "")) . "||" . str_replace("\n", " ", $text));
        return array("content" => array(array("type" => "text", "text" => $text)));
    }
    catch (Throwable $e)
    {
        while (ob_get_level() > 0)
            ob_end_clean();
        return array("content" => array(array("type" => "text", "text" => "ERROR: " . $e->getMessage())), "isError" => true);
    }
}

//---------------------------------- tools ------------------------------------>

/**
 * @return string
 */
function mcp_Tool_sections($args)
{
    $rows = array();
    foreach (mcp_DbviewSections() as $id => $section)
    {
        $access = mcp_SectionAccess($id);
        if ($access == "")
            continue;
        $config = mcp_SectionConfig($id);
        $rows[] = array($id, $config['tables'], html_entity_decode($section['title'], ENT_QUOTES, "UTF-8"), $section['parent'], empty($config['enable_history']) ? 0 : 1, empty($config['enable_delete']) ? 0 : (empty($config['hide_on_delete']) ? 1 : "hide"), $access);
    }
    return mcp_Csv(array_merge(array(array("id", "table", "title", "parent", "history", "delete", "access")), $rows));
}

/**
 * @return string
 */
function mcp_Tool_whoami($args)
{
    global $_FN;
    $groups = isset($_FN['uservalues']['group']) ? str_replace(",", "|", trim($_FN['uservalues']['group'], ",")) : "";
    $rows = array(array("user", "groups", "access", "readonly"));
    $rows[] = array($_FN['user'], $groups, mcp_FullAccess() ? "admin" : "user", (empty($_FN['openkis_mcp_readonly']) && mcp_CanWrite()) ? 0 : 1);
    return mcp_Csv($rows);
}

/**
 * @return string
 */
function mcp_Tool_tables($args)
{
    global $_FN;
    $bytable = array();
    foreach (mcp_DbviewSections() as $id => $section)
    {
        $config = mcp_SectionConfig($id);
        if (mcp_SectionAccess($id) != "")
            $bytable[$config['tables']][] = $id;
    }
    $rows = array(array("table", "driver", "section"));
    foreach (glob("{$_FN['datadir']}/{$_FN['database']}/*.php") as $file)
    {
        $tablename = basename($file, ".php");
        if (!preg_match('/^[A-Za-z0-9_]+$/', $tablename) || !mcp_CanReadTable($tablename))
            continue;
        $Table = FN_XMDBTable($tablename);
        if (!is_object($Table) || empty($Table->fields))
            continue;
        $driver = isset($Table->driver) ? $Table->driver : "";
        $rows[] = array($tablename, $driver, isset($bytable[$tablename]) ? implode("|", $bytable[$tablename]) : "");
    }
    return mcp_Csv($rows);
}

/**
 * @return string
 */
function mcp_Tool_schema($args)
{
    global $_FN;
    $src = mcp_Source($args);
    $Form = FN_XMDBForm($src['table']);
    $rows = array(array("name", "type", "flags", "fk", "options", "label"));
    foreach ($src['Table']->fields as $name => $field)
    {
        $fv = isset($Form->formvals[$name]) ? $Form->formvals[$name] : array();
        $type = !empty($fv['frm_type']) ? $fv['frm_type'] : $field->type;
        if (!empty($field->type) && $field->type != $type)
            $type = "{$field->type}/$type";
        $flags = array();
        if (!empty($field->primarykey))
            $flags[] = "pk";
        if ($field->extra == "autoincrement")
            $flags[] = "ai";
        if (!empty($fv['frm_required']) || !empty($field->frm_required))
            $flags[] = "req";
        if (!empty($field->unique))
            $flags[] = "uniq";
        if (!empty($fv['frm_readonly']))
            $flags[] = "ro";
        if (isset($field->frm_show) && (string) $field->frm_show === "0")
            $flags[] = "hidden";
        $fk = "";
        if (!empty($field->foreignkey))
        {
            $fk = $field->foreignkey;
            if (!empty($field->fk_link_field))
                $fk .= "." . $field->fk_link_field;
            if (!empty($field->fk_show_field))
                $fk .= ">" . $field->fk_show_field;
        }
        $options = "";
        if (!empty($fv['frm_options']))
            $options = is_array($fv['frm_options']) ? implode("|", $fv['frm_options']) : $fv['frm_options'];
        elseif (!empty($field->frm_options))
            $options = $field->frm_options;
        $label = "";
        foreach (array("frm_" . $_FN['lang'], "frm_it", "frm_i18n") as $k)
        {
            if (!empty($field->$k))
            {
                $label = $k == "frm_i18n" ? FN_Translate($field->$k) : $field->$k;
                break;
            }
        }
        if ($label == $name)
            $label = "";
        $rows[] = array($name, $type, implode(" ", $flags), $fk, $options, html_entity_decode(strip_tags($label), ENT_QUOTES, "UTF-8"));
    }
    $header = "#pk=" . $src['pk'] . ($src['section'] ? " section=" . $src['section'] : "") . "\n";
    return $header . mcp_Csv($rows);
}

/**
 * @return string
 */
function mcp_Tool_query($args)
{
    $src = mcp_Source($args);
    $Table = $src['Table'];
    $native = mcp_IsNative($Table);
    //fields
    $fields = isset($args['fields']) ? trim($args['fields']) : "";
    if ($fields == "" && $src['section'])
    {
        $fields = $src['pk'] . "," . $src['config']['titlefield'];
    }
    $fields = mcp_FieldList($Table, $fields);
    //conditions
    $filter = isset($args['filter']) && is_array($args['filter']) ? $args['filter'] : array();
    foreach ($filter as $k => $v)
        mcp_CheckField($Table, $k);
    $where = isset($args['where']) ? trim($args['where']) : "";
    if ($where != "" && !mcp_FullAccess() && preg_match('/\b(SELECT|UNION|SLEEP|BENCHMARK|LOAD_FILE|INFORMATION_SCHEMA|PERFORMANCE_SCHEMA|MYSQL|SYS|OUTFILE|DUMPFILE)\b|@|`\s*\.|\.\s*`|#|--|\/\*/i', $where))
        throw new McpToolError("where: subqueries, comments, variables and schema references are not allowed");
    if (!empty($args['deleted']) && !mcp_CanSeeDeleted($src))
        throw new McpToolError("permission denied: deleted records are visible to the section administrators only");
    $hideDeleted = empty($args['deleted']) && isset($Table->fields['recorddeleted']);
    $rowFilter = mcp_NeedsRowFilter($src);
    if ($native)
    {
        $conditions = array();
        foreach ($filter as $k => $v)
        {
            $v = (string) $v;
            $op = (strpos($v, "%") !== false) ? "LIKE" : "=";
            $conditions[] = "`$k` $op '" . $Table->driverclass->conn->real_escape_string($v) . "'";
        }
        if ($where != "")
        {
            if (strpos($where, ";") !== false)
                throw new McpToolError("';' not allowed in where");
            $conditions[] = "($where)";
        }
        if ($hideDeleted)
            $conditions[] = "(`recorddeleted` IS NULL OR `recorddeleted`<>'1')";
        $restr = implode(" AND ", $conditions);
    }
    else
    {
        if ($where != "")
            throw new McpToolError("where is supported only on mysql tables, use filter");
        $restr = $filter ? $filter : false;
    }
    $total = $rowFilter ? null : $Table->GetNumRecords($restr);
    if (!$native && $hideDeleted)
        $total = null; // approximated below
    if (!empty($args['count']))
    {
        if ($total === null)
        {
            $records = $Table->GetRecords($restr, false, false, false, false, array_keys(array_intersect_key($Table->fields, array_flip(array($src['pk'], "recorddeleted", "groupview", "username")))));
            if (!$native && $hideDeleted)
                $records = mcp_FilterDeleted($records);
            $total = count(mcp_FilterViewable($src, $records));
        }
        return "total=$total";
    }
    $limit = isset($args['limit']) ? intval($args['limit']) : MCP_QUERY_DEFAULT_LIMIT;
    $limit = max(1, min(MCP_QUERY_MAX_LIMIT, $limit));
    $offset = isset($args['offset']) ? max(0, intval($args['offset'])) : 0;
    $order = isset($args['order']) ? trim($args['order']) : "";
    if ($order != "")
    {
        foreach (explode(",", $order) as $o)
        {
            $o = explode(":", trim($o));
            mcp_CheckField($Table, $o[0]);
            if (isset($o[1]) && !in_array(strtoupper($o[1]), array("ASC", "DESC")))
                throw new McpToolError("bad order direction {$o[1]}");
        }
    }
    else
        $order = false;
    $getfields = $fields;
    if (!$native && $hideDeleted && !in_array("recorddeleted", $getfields))
        $getfields[] = "recorddeleted";
    if ($rowFilter)
    {
        foreach (array($src['pk'], "groupview", "username") as $f)
        {
            if (isset($Table->fields[$f]) && !in_array($f, $getfields))
                $getfields[] = $f;
        }
    }
    if ($rowFilter)
    {
        // per-record permissions: filter the whole result, then paginate
        $records = $Table->GetRecords($restr, false, false, $order, false, $getfields);
        if (!$native && $hideDeleted)
            $records = mcp_FilterDeleted($records);
        $records = mcp_FilterViewable($src, $records);
        $total = count($records);
        $records = array_slice($records, $offset, $limit);
    }
    elseif ($native)
    {
        $records = $Table->GetRecords($restr, $offset, $limit, $order, false, $getfields);
    }
    else
    {
        $records = $Table->GetRecords($restr, false, false, $order, false, $getfields);
        if ($hideDeleted)
            $records = mcp_FilterDeleted($records);
        $total = count($records);
        $records = array_slice(is_array($records) ? $records : array(), $offset, $limit);
    }
    if (!is_array($records))
        throw new McpToolError("query failed" . (is_string($records) ? ": $records" : ""));
    $maxlen = isset($args['maxlen']) ? intval($args['maxlen']) : 100;
    $rows = array($fields);
    foreach ($records as $record)
    {
        $row = array();
        foreach ($fields as $f)
            $row[] = isset($record[$f]) ? $record[$f] : "";
        $rows[] = $row;
    }
    return mcp_Csv($rows, $maxlen) . "#offset=$offset rows=" . count($records) . " total=$total";
}

/**
 * @return string
 */
function mcp_Tool_get($args)
{
    $src = mcp_Source($args);
    $id = mcp_CheckPk(isset($args['id']) ? $args['id'] : "");
    $record = $src['Table']->GetRecordByPrimaryKey($id);
    if (!is_array($record) || !mcp_CanViewRow($src, $record))
        throw new McpToolError("record $id not found");
    if (!empty($record['recorddeleted']) && !mcp_CanSeeDeleted($src))
        throw new McpToolError("record $id not found");
    $fields = isset($args['fields']) && trim($args['fields']) != "" ? mcp_FieldList($src['Table'], $args['fields']) : array_keys($record);
    $rows = array(array("field", "value"));
    foreach ($fields as $f)
    {
        $v = isset($record[$f]) ? $record[$f] : "";
        if ((string) $v === "" && empty($args['empty']))
            continue;
        $rows[] = array($f, $v);
    }
    return mcp_Csv($rows);
}

/**
 * @return string
 */
function mcp_Tool_sql($args)
{
    global $_FN;
    $query = trim(isset($args['query']) ? $args['query'] : "");
    $query = rtrim($query, "; \t\r\n");
    if (!preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN|WITH)\b/i', $query))
        throw new McpToolError("only SELECT/SHOW/DESCRIBE/EXPLAIN allowed");
    if (strpos($query, ";") !== false)
        throw new McpToolError("single statement only");
    if (preg_match('/\b(INTO\s+(OUT|DUMP)FILE|LOAD_FILE|FOR\s+UPDATE|LOCK\s+IN|SLEEP|BENCHMARK)\b/i', $query))
        throw new McpToolError("statement not allowed");
    if (empty($_FN['xmetadb_mysqlhost']))
        throw new McpToolError("mysql not configured");
    mysqli_report(MYSQLI_REPORT_OFF);
    $port = !empty($_FN['xmetadb_mysqlport']) ? intval($_FN['xmetadb_mysqlport']) : null;
    $conn = @new mysqli($_FN['xmetadb_mysqlhost'], $_FN['xmetadb_mysqlusername'], $_FN['xmetadb_mysqlpassword'], $_FN['xmetadb_mysqldatabase'], $port);
    if ($conn->connect_error)
        throw new McpToolError("mysql connection failed");
    $conn->set_charset("utf8mb4");
    @$conn->query("SET SESSION TRANSACTION READ ONLY");
    $conn->query("START TRANSACTION");
    $res = $conn->query($query);
    if ($res === false)
    {
        $err = $conn->error;
        $conn->query("ROLLBACK");
        throw new McpToolError($err);
    }
    $limit = isset($args['limit']) ? max(1, intval($args['limit'])) : 200;
    $maxlen = isset($args['maxlen']) ? intval($args['maxlen']) : 0;
    $rows = array();
    if ($res instanceof mysqli_result)
    {
        $header = array();
        foreach ($res->fetch_fields() as $f)
            $header[] = $f->name;
        $rows[] = $header;
        $n = 0;
        while ($n < $limit && ($row = $res->fetch_row()))
        {
            $rows[] = $row;
            $n++;
        }
        $total = $res->num_rows;
        $res->free();
    }
    $conn->query("ROLLBACK");
    $conn->close();
    $out = mcp_Csv($rows, $maxlen);
    if (isset($total) && $total > $limit)
        $out .= "#rows=$limit of $total";
    return $out;
}

/**
 * @return string
 */
function mcp_Tool_insert($args)
{
    $src = mcp_Source($args, true);
    if (!mcp_FullAccess() && !mcp_Dbview($src)->CanAddRecord())
        throw new McpToolError("permission denied: you cannot add records to section {$src['section']}");
    $records = mcp_Records($args);
    $out = array(array("row", "id", "result"));
    foreach ($records as $i => $values)
    {
        try
        {
            $record = mcp_InsertRecord($src, $values);
            $out[] = array($i + 1, $record[$src['pk']], "ok");
        }
        catch (McpToolError $e)
        {
            $out[] = array($i + 1, "", "ERR " . $e->getMessage());
        }
    }
    return mcp_Csv($out);
}

/**
 * @return string
 */
function mcp_Tool_update($args)
{
    $src = mcp_Source($args, true);
    $records = mcp_Records($args);
    if (isset($args['id']) && $args['id'] !== "")
    {
        if (count($records) != 1)
            throw new McpToolError("id can be used only with a single record");
        $records[0][$src['pk']] = $args['id'];
    }
    $out = array(array("id", "result", "changed"));
    foreach ($records as $values)
    {
        $id = isset($values[$src['pk']]) ? $values[$src['pk']] : "";
        try
        {
            $changed = mcp_UpdateRecord($src, $id, $values);
            $out[] = array($id, $changed ? "ok" : "unchanged", implode("|", $changed));
        }
        catch (McpToolError $e)
        {
            $out[] = array($id, "ERR " . $e->getMessage(), "");
        }
    }
    return mcp_Csv($out);
}

/**
 * @return string
 */
function mcp_Tool_upload($args)
{
    $src = mcp_Source($args, true);
    $Table = $src['Table'];
    $id = isset($args['id']) ? (string) $args['id'] : "";
    $field = isset($args['field']) ? (string) $args['field'] : "";
    mcp_CheckField($Table, $field);
    if (!xmetadb_is_file_field($Table->fields[$field]))
        throw new McpToolError("field $field is not a file/image field");
    $sources = 0;
    foreach (array("content_base64", "url", "from_id", "clear") as $k)
    {
        if (!empty($args[$k]))
            $sources++;
    }
    if ($sources != 1)
        throw new McpToolError("pass exactly one of content_base64, url, from_id, clear");
    if (!empty($args['clear']))
    {
        $post_bk = $_POST;
        $_POST["__isnull__$field"] = "null";
        try
        {
            $changed = mcp_UpdateRecord($src, $id, array($field => ""), array($field => true));
        }
        finally
        {
            $_POST = $post_bk;
        }
        return mcp_Csv(array(array("id", "result", "field", "file"), array($id, $changed ? "cleared" : "unchanged", $field, "")));
    }
    $filename = isset($args['filename']) ? $args['filename'] : "";
    $tmp = tempnam(sys_get_temp_dir(), "mcp");
    try
    {
        if (!empty($args['content_base64']))
        {
            $data = base64_decode($args['content_base64'], true);
            if ($data === false)
                throw new McpToolError("content_base64 is not valid base64");
            file_put_contents($tmp, $data);
        }
        elseif (!empty($args['url']))
        {
            if (!preg_match('#^https?://#i', $args['url']))
                throw new McpToolError("only http(s) urls are allowed");
            $fp = fopen($tmp, "w");
            $ch = curl_init($args['url']);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_USERAGENT, "OpenKIS MCP");
            $ok = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            curl_close($ch);
            fclose($fp);
            if (!$ok || $status >= 400)
                throw new McpToolError("download failed (HTTP $status)");
            if ($filename == "")
                $filename = rawurldecode(basename(parse_url($args['url'], PHP_URL_PATH)));
        }
        else
        {
            $from = mcp_Source(array("table" => !empty($args['from_table']) ? $args['from_table'] : $src['table']));
            $fromField = !empty($args['from_field']) ? $args['from_field'] : $field;
            mcp_CheckField($from['Table'], $fromField);
            mcp_CheckPk($args['from_id']);
            $row = $from['Table']->GetRecordByPrimaryKey($args['from_id']);
            if (!is_array($row) || !mcp_CanViewRow($from, $row))
                throw new McpToolError("source record not found");
            if (empty($row[$fromField]))
                throw new McpToolError("source field $fromField is empty");
            $path = $from['Table']->getFilePath($row, $fromField);
            if (!$path || !file_exists($path))
                throw new McpToolError("source file not found");
            copy($path, $tmp);
            if ($filename == "")
                $filename = $row[$fromField];
        }
        $filename = str_replace(array("/", "\\"), "", $filename);
        if ($filename == "")
            throw new McpToolError("filename required");
        if (preg_match('/\.(php[0-9]?|phtml|phar|shtml|pl|cgi|sh|py)(\.|$)/i', $filename))
            throw new McpToolError("file type not allowed");
        if (filesize($tmp) == 0)
            throw new McpToolError("empty file");
        mcp_UpdateRecord($src, $id, array($field => $filename), array($field => $tmp));
        $record = $Table->GetRecordByPrimaryKey($id);
        $path = $Table->getFilePath($record, $field);
        if (!$path || !file_exists($path))
            throw new McpToolError("file not saved");
        return mcp_Csv(array(array("id", "result", "field", "file"), array($id, "ok", $field, $record[$field] . " (" . filesize($path) . " bytes)")));
    }
    finally
    {
        if (file_exists($tmp))
            unlink($tmp);
    }
}

/**
 * @return string
 */
function mcp_Tool_delete($args)
{
    global $_FN;
    $src = mcp_Source($args, true);
    $Table = $src['Table'];
    $config = $src['config'];
    if ($src['section'] && empty($config['enable_delete']))
        throw new McpToolError("delete is disabled for section {$src['section']}");
    $out = array(array("id", "result"));
    foreach (explode(",", isset($args['ids']) ? $args['ids'] : "") as $id)
    {
        $id = trim($id);
        if ($id == "")
            continue;
        try
        {
            mcp_CheckPk($id);
            $row = $Table->GetRecordByPrimaryKey($id);
            if (!is_array($row))
                throw new McpToolError("not found");
            mcp_RequireEditRow($src, $row);
            if ($src['section'])
            {
                $dbview = mcp_Dbview($src);
                if (!empty($config['enable_history']))
                {
                    $dbview->SaveHistory($src['table'], FN_XMDBForm($src['table']), $row);
                }
                if (!empty($config['hide_on_delete']))
                {
                    if (!isset($Table->fields['recorddeleted']))
                    {
                        addxmltablefield($Table->databasename, $Table->tablename, array("name" => "recorddeleted", "type" => "bool", "frm_show" => "0"), $Table->path);
                        $Table = $src['Table'] = FN_XMDBTable($src['table']);
                    }
                    $Table->UpdateRecord(array($src['pk'] => $id, "recorddeleted" => 1));
                    $out[] = array($id, "hidden");
                }
                else
                {
                    $listusers = FN_XMDBTable("fieldusers");
                    $list = $listusers->GetRecords(array("table_unirecid" => $id, "tablename" => $src['table']));
                    if (is_array($list))
                    {
                        foreach ($list as $item)
                            $listusers->DelRecord($item['id']);
                    }
                    $Table->DelRecord($id);
                    mcp_Hook($src, "function_on_delete", $row);
                    $out[] = array($id, "deleted");
                }
                $dbview->InvalidateCache($src['table']);
            }
            else
            {
                $Table->DelRecord($id);
                $out[] = array($id, "deleted");
            }
        }
        catch (McpToolError $e)
        {
            $out[] = array($id, "ERR " . $e->getMessage());
        }
    }
    return mcp_Csv($out);
}

//---------------------------------- tools ------------------------------------<
//--------------------------------- writes ------------------------------------>

/**
 * @param array $src
 * @param array $values
 * @return array inserted record
 */
function mcp_InsertRecord($src, $values)
{
    global $_FN;
    $Form = FN_XMDBForm($src['table']);
    mcp_CheckWritableFields($src['Table'], $values);
    mcp_CheckProtectedFields($src, $values);
    if ($src['section'])
    {
        $values = mcp_Dbview($src)->Transform($values, $Form);
        $now = XMETATable::now();
        $values['recordinsert'] = $values['recordupdate'] = $now;
        $values['insert'] = $values['update'] = time();
        $values['username'] = $values['userupdate'] = $_FN['user'];
    }
    $errors = $Form->VerifyInsert($values);
    if ($errors)
        throw new McpToolError(mcp_VerifyErrors($errors));
    $record = $Form->xmltable->InsertRecord($values);
    if (!is_array($record))
        throw new McpToolError(is_string($record) ? $record : "insert failed");
    if ($src['section'])
    {
        $pk = $record[$src['pk']];
        if (isset($record['view']))
        {
            $record = $Form->xmltable->UpdateRecord(array($src['pk'] => $pk, "view" => $pk));
        }
        $users = FN_XMDBTable("fieldusers");
        $users->InsertRecord(array("tablename" => $src['table'], "username" => $_FN['user'], "table_unirecid" => $pk));
        mcp_Hook($src, "function_on_insert", $record);
        mcp_Dbview($src)->InvalidateCache($src['table']);
        $record = $Form->xmltable->GetRecordByPrimaryKey($pk);
    }
    return $record;
}

/**
 * @param array $src
 * @param string $id
 * @param array $values
 * @param array $files file/image fields written by upload: field => temp file (true = clear)
 * @return array changed fields
 */
function mcp_UpdateRecord($src, $id, $values, $files = array())
{
    global $_FN;
    $pk = $src['pk'];
    mcp_CheckPk($id);
    $Form = FN_XMDBForm($src['table']);
    $old = $Form->xmltable->GetRecordByPrimaryKey($id);
    if (!is_array($old))
        throw new McpToolError("not found");
    mcp_RequireEditRow($src, $old);
    unset($values[$pk]);
    mcp_CheckWritableFields($src['Table'], array_diff_key($values, $files));
    mcp_CheckProtectedFields($src, $values);
    if ($src['section'])
        $values = mcp_Dbview($src)->Transform($values, $Form);
    $changed = array();
    foreach ($values as $k => $v)
    {
        if ($v === null)
            $v = "";
        if (is_bool($v))
            $v = $v ? "1" : "0";
        // an uploaded file replaces the old one even with the same name
        if (!array_key_exists($k, $old) || (string) $old[$k] !== (string) $v || (isset($files[$k]) && $files[$k] !== true))
            $changed[$k] = (string) $v;
    }
    if (!$changed)
        return array();
    $merged = array_merge($old, $changed);
    $errors = $Form->VerifyUpdate($merged, $id);
    if ($errors)
        throw new McpToolError(mcp_VerifyErrors($errors));
    $names = array_keys($changed);
    $changed[$pk] = $id;
    if ($src['section'])
    {
        $changed['recordupdate'] = XMETATable::now();
        $changed['update'] = time();
        $changed['userupdate'] = $_FN['user'];
        if (!empty($src['config']['enable_history']))
            mcp_Dbview($src)->SaveHistory($src['table'], $Form, $old);
    }
    foreach ($files as $k => $tmp)
    {
        if ($tmp !== true)
            $Form->xmltable->SetFile($k, $tmp, $values[$k]);
    }
    $record = $Form->xmltable->UpdateRecord($changed);
    if (!is_array($record))
        throw new McpToolError(is_string($record) ? $record : "update failed");
    if ($src['section'])
    {
        mcp_Hook($src, "function_on_update", $record);
        mcp_Dbview($src)->InvalidateCache($src['table']);
    }
    return $names;
}

/**
 * Runs a section callback ($_FN['modparams'][section]['editorparams']['table'][$hook])
 */
function mcp_Hook($src, $hook, $record)
{
    global $_FN;
    if (empty($_FN['modparams'][$src['section']]['editorparams']['table'][$hook]))
        return;
    $function = $_FN['modparams'][$src['section']]['editorparams']['table'][$hook];
    if (!function_exists($function))
        return;
    $mod_bk = $_FN['mod'];
    $_FN['mod'] = $src['section'];
    $function($record);
    $_FN['mod'] = $mod_bk;
}

/**
 * @param array $errors xmetadb_frm::Verify output
 * @return string
 */
function mcp_VerifyErrors($errors)
{
    $ret = array();
    foreach ($errors as $k => $e)
    {
        $field = is_array($e) && !empty($e['field']) ? $e['field'] : $k;
        $msg = is_array($e) && isset($e['error']) ? $e['error'] : (is_string($e) ? $e : "");
        $ret[] = "$field: " . html_entity_decode(strip_tags($msg), ENT_QUOTES, "UTF-8");
    }
    return implode("; ", $ret);
}

/**
 * @param object $Table
 * @param array $values
 */
function mcp_CheckWritableFields($Table, $values)
{
    foreach ($values as $k => $v)
    {
        mcp_CheckField($Table, $k);
        if (xmetadb_is_file_field($Table->fields[$k]))
            throw new McpToolError("field $k is a file/image field, upload not supported");
        if (is_array($v) || is_object($v))
            throw new McpToolError("field $k: scalar value expected");
    }
}

//--------------------------------- writes ------------------------------------<
//------------------------------- permissions --------------------------------->

/**
 * Administrators and the legacy global key bypass every permission check
 * @return bool
 */
function mcp_FullAccess()
{
    global $_FN;
    return isset($_FN['openkis_mcp_access']) && $_FN['openkis_mcp_access'] == "full";
}

/**
 * @return string sentence for the initialize instructions
 */
function mcp_AccessDescription()
{
    global $_FN;
    if (mcp_FullAccess())
        return "Connected as {$_FN['user']} with administrator access.";
    return "Connected as {$_FN['user']} with the permissions of this user: only the sections listed by `sections` are available (column access), lookup tables only through `tables`, no `sql`." . (mcp_CanWrite() ? "" : " Read only: the write tools are not enabled for this user.");
}

/**
 * Access level of the current user on a dbview section, same rules of the web UI
 * @param string $section
 * @return string admin|add|read or "" when the section is not readable
 */
function mcp_SectionAccess($section)
{
    static $cache = array();
    if (mcp_FullAccess())
        return "admin";
    if (!isset($cache[$section]))
    {
        $cache[$section] = "";
        $sections = mcp_DbviewSections();
        if (isset($sections[$section]) && FN_UserCanViewSection($section))
        {
            $config = mcp_SectionConfig($section);
            $dbview = mcp_Dbview(array("section" => $section, "config" => $config));
            if ($dbview->CanViewRecords($config))
            {
                if (!mcp_CanWrite())
                    $cache[$section] = "read";
                elseif ($dbview->IsAdmin())
                    $cache[$section] = "admin";
                elseif ($dbview->CanAddRecord())
                    $cache[$section] = "write";
                else
                    $cache[$section] = "read";
            }
        }
    }
    return $cache[$section];
}

/**
 * Tables readable without a section: tables of the readable sections and the
 * lookup tables they reference, except the tables of sections the user cannot read
 * or that have per-record permissions.
 * @param string $tablename
 * @return bool
 */
function mcp_CanReadTable($tablename)
{
    static $readable = null;
    if (mcp_FullAccess())
        return true;
    if ($readable === null)
    {
        $readable = array();
        $denied = array();
        foreach (mcp_DbviewSections() as $id => $section)
        {
            $config = mcp_SectionConfig($id);
            $src = array("section" => $id, "config" => $config);
            if (mcp_SectionAccess($id) == "")
            {
                $denied[$config['tables']] = true;
                continue;
            }
            if (mcp_NeedsRowFilter($src))
                $denied[$config['tables']] = true;
            else
                $readable[$config['tables']] = true;
            $Table = FN_XMDBTable($config['tables']);
            if (!is_object($Table) || empty($Table->fields))
                continue;
            foreach ($Table->fields as $field)
            {
                if (!empty($field->foreignkey) && strpos($field->foreignkey, "fn_") !== 0)
                    $readable[$field->foreignkey] = true;
            }
        }
        $readable = array_diff_key($readable, $denied);
    }
    return isset($readable[$tablename]);
}

/**
 * @param array $src
 * @return bool true when the section restricts the visibility of single records
 */
function mcp_NeedsRowFilter($src)
{
    if (mcp_FullAccess() || empty($src['section']))
        return false;
    $config = $src['config'];
    return !empty($config['viewonlycreator']) || (!empty($config['enable_permissions_each_records']) && $config['enable_permissions_each_records'] == 1);
}

/**
 * @param array $src
 * @param array $row
 * @return bool
 */
function mcp_CanViewRow($src, $row)
{
    global $_FN;
    if (!mcp_NeedsRowFilter($src))
        return true;
    $config = $src['config'];
    if (!empty($config['viewonlycreator']))
        return mcp_Dbview($src)->CanViewRecord($row[$src['Table']->primarykey], $src['table'], $config);
    // enable_permissions_each_records: same rule of FNDBVIEW::CanViewRecord, evaluated on the loaded row
    if (empty($row['groupview']))
        return true;
    if ($_FN['user'] == "")
        return false;
    $usergroups = explode(",", isset($_FN['uservalues']['group']) ? $_FN['uservalues']['group'] : "");
    $allowed = explode(",", $row['groupview'] . "," . $config['groupinsert'] . "," . $config['groupadmin']);
    foreach ($usergroups as $group)
    {
        if ($group != "" && in_array($group, $allowed))
            return true;
    }
    return false;
}

/**
 * @param array $src
 * @param array $records
 * @return array
 */
function mcp_FilterViewable($src, $records)
{
    $records = is_array($records) ? array_values($records) : array();
    if (!mcp_NeedsRowFilter($src))
        return $records;
    $ret = array();
    foreach ($records as $r)
    {
        if (mcp_CanViewRow($src, $r))
            $ret[] = $r;
    }
    return $ret;
}

/**
 * Records hidden by delete (recorddeleted=1) are visible only to the section administrators
 * @param array $src
 * @return bool
 */
function mcp_CanSeeDeleted($src)
{
    if (mcp_FullAccess())
        return true;
    return !empty($src['section']) && mcp_Dbview($src)->IsAdmin();
}

/**
 * Edit/delete right on a record: section administrators, owner, groupinsert
 * of the record, users added to the record (fieldusers)
 * @param array $src
 * @param array $row
 */
function mcp_RequireEditRow($src, $row)
{
    if (mcp_FullAccess())
        return;
    $id = $row[$src['Table']->primarykey];
    // Same rule as dbview: section write permission first, then the record level check
    if (empty($src['section']) || !mcp_CanViewRow($src, $row) || !mcp_Dbview($src)->CanEditRecords() || !mcp_Dbview($src)->IsAdminRecord($row))
        throw new McpToolError("permission denied: you cannot modify record $id");
}

/**
 * Fields managed by the system and hidden fields can be written only by section administrators
 * @param array $src
 * @param array $values
 */
function mcp_CheckProtectedFields($src, $values)
{
    if (mcp_FullAccess() || (!empty($src['section']) && mcp_Dbview($src)->IsAdmin()))
        return;
    $system = array("username", "userupdate", "recordinsert", "recordupdate", "recorddeleted", "insert", "update");
    foreach ($values as $k => $v)
    {
        $field = $src['Table']->fields[$k];
        if (in_array($k, $system) || (isset($field->frm_show) && (string) $field->frm_show === "0"))
            throw new McpToolError("permission denied: field $k is reserved to the administrators");
    }
}

//------------------------------- permissions ---------------------------------<
//--------------------------------- helpers ----------------------------------->

/**
 * Resolves section/table arguments. A table used by exactly one dbview section is handled as that section.
 * Checks that the user can read it (and, with $write, that the table belongs to a section).
 * @param array $args
 * @param bool $write
 * @return array table, section, config, Table, pk
 */
function mcp_Source($args, $write = false)
{
    global $_FN;
    $section = isset($args['section']) ? trim($args['section']) : "";
    $tablename = isset($args['table']) ? trim($args['table']) : "";
    $sections = mcp_DbviewSections();
    $config = null;
    if ($section != "")
    {
        if (!isset($sections[$section]))
            throw new McpToolError("unknown dbview section $section, see sections tool");
        $config = mcp_SectionConfig($section);
        $tablename = $config['tables'];
    }
    elseif ($tablename != "")
    {
        $found = array();
        foreach ($sections as $id => $s)
        {
            $c = mcp_SectionConfig($id);
            if ($c['tables'] == $tablename)
                $found[$id] = $c;
        }
        if (count($found) == 1)
        {
            $section = key($found);
            $config = current($found);
        }
    }
    else
        throw new McpToolError("section or table required");
    if (!preg_match('/^[A-Za-z0-9_]+$/', $tablename) || !file_exists("{$_FN['datadir']}/{$_FN['database']}/$tablename.php"))
        throw new McpToolError("unknown table $tablename");
    $Table = FN_XMDBTable($tablename);
    if (!is_object($Table) || empty($Table->fields))
        throw new McpToolError("cannot open table $tablename");
    if (is_array($Table->primarykey))
        throw new McpToolError("tables with composite primary key are not supported");
    if (!mcp_FullAccess())
    {
        if ($section != "")
        {
            if (mcp_SectionAccess($section) == "")
                throw new McpToolError("permission denied: you cannot read section $section");
        }
        elseif ($write)
            throw new McpToolError("permission denied: only administrators can write tables directly, use a section");
        elseif (!mcp_CanReadTable($tablename))
            throw new McpToolError("permission denied: table $tablename is not readable with this key, use a section");
    }
    return array("table" => $tablename, "section" => $section, "config" => $config, "Table" => $Table, "pk" => $Table->primarykey);
}

/**
 * @return array dbview sections by id
 */
function mcp_DbviewSections()
{
    static $ret = null;
    if ($ret === null)
    {
        $ret = array();
        foreach (FN_GetAllSections() as $id => $section)
        {
            if (isset($section['type']) && $section['type'] == "dbview")
                $ret[$id] = $section;
        }
    }
    return $ret;
}

/**
 * @param string $section
 * @return array
 */
function mcp_SectionConfig($section)
{
    $config = FN_LoadConfig("modules/dbview/config.php", $section);
    $tables = explode(",", $config['tables']);
    $config['tables'] = trim($tables[0]);
    return $config;
}

/**
 * @return OpenkisMcpDbview
 */
function mcp_Dbview($src)
{
    static $cache = array();
    if (!isset($cache[$src['section']]))
    {
        $cache[$src['section']] = new OpenkisMcpDbview($src['config']);
    }
    return $cache[$src['section']];
}

/**
 * @param object $Table
 * @return bool
 */
function mcp_IsNative($Table)
{
    return isset($Table->driverclass) && is_object($Table->driverclass) && !empty($Table->driverclass->conn) && !empty($Table->driverclass->sqltable);
}

/**
 * @param object $Table
 * @param string $field
 */
function mcp_CheckField($Table, $field)
{
    if (!is_string($field) || !isset($Table->fields[$field]))
        throw new McpToolError("unknown field $field");
}

/**
 * @param object $Table
 * @param string $fields comma list or *
 * @return array
 */
function mcp_FieldList($Table, $fields)
{
    $fields = trim($fields);
    if ($fields == "" || $fields == "*")
        return array_keys($Table->fields);
    $ret = array();
    foreach (explode(",", $fields) as $f)
    {
        $f = trim($f);
        if ($f == "" || in_array($f, $ret))
            continue;
        mcp_CheckField($Table, $f);
        $ret[] = $f;
    }
    return $ret;
}

/**
 * Primary key values are concatenated in LIKE clauses by the mysql driver
 * @param string $id
 * @return string
 */
function mcp_CheckPk($id)
{
    $id = (string) $id;
    if ($id === "" || preg_match('/[\'"\\\\%]/', $id))
        throw new McpToolError("invalid primary key value");
    return $id;
}

/**
 * @param array $records
 * @return array
 */
function mcp_FilterDeleted($records)
{
    $ret = array();
    foreach ((array) $records as $r)
    {
        if (empty($r['recorddeleted']))
            $ret[] = $r;
    }
    return $ret;
}

/**
 * Records from arguments "data" (object or list of objects) or "csv" (with header)
 * @param array $args
 * @return array
 */
function mcp_Records($args)
{
    $records = array();
    if (isset($args['csv']) && trim($args['csv']) != "")
    {
        $lines = mcp_ParseCsv($args['csv']);
        $header = array_map("trim", array_shift($lines));
        foreach ($lines as $n => $line)
        {
            if (count($line) == 1 && trim($line[0]) == "")
                continue;
            if (count($line) != count($header))
                throw new McpToolError("csv row " . ($n + 2) . ": " . count($line) . " values, " . count($header) . " expected");
            $records[] = array_combine($header, $line);
        }
    }
    elseif (isset($args['data']))
    {
        $data = $args['data'];
        if (is_string($data))
            $data = json_decode($data, true);
        if (!is_array($data))
            throw new McpToolError("data must be an object or an array of objects");
        $records = isset($data[0]) ? $data : array($data);
        foreach ($records as $r)
        {
            if (!is_array($r))
                throw new McpToolError("data must be an object or an array of objects");
        }
    }
    if (!$records)
        throw new McpToolError("no records: pass data or csv");
    return $records;
}

/**
 * @param string $csv
 * @return array
 */
function mcp_ParseCsv($csv)
{
    // RFC 4180 parser, independent from the fgetcsv escape handling of the PHP version
    $ret = array();
    $row = array();
    $field = "";
    $quoted = false;
    $len = strlen($csv);
    for ($i = 0; $i < $len; $i++)
    {
        $c = $csv[$i];
        if ($quoted)
        {
            if ($c == '"')
            {
                if ($i + 1 < $len && $csv[$i + 1] == '"')
                {
                    $field .= '"';
                    $i++;
                }
                else
                    $quoted = false;
            }
            else
                $field .= $c;
        }
        elseif ($c == '"')
            $quoted = true;
        elseif ($c == ",")
        {
            $row[] = $field;
            $field = "";
        }
        elseif ($c == "\n" || $c == "\r")
        {
            if ($c == "\r" && $i + 1 < $len && $csv[$i + 1] == "\n")
                $i++;
            $row[] = $field;
            $ret[] = $row;
            $row = array();
            $field = "";
        }
        else
            $field .= $c;
    }
    if ($field !== "" || $row)
    {
        $row[] = $field;
        $ret[] = $row;
    }
    return $ret;
}

/**
 * @param array $rows first row is the header
 * @param int $maxlen 0 = no limit
 * @return string
 */
function mcp_Csv($rows, $maxlen = 0)
{
    $ret = "";
    foreach ($rows as $row)
    {
        $line = array();
        foreach ($row as $v)
        {
            $v = (string) $v;
            if ($maxlen > 0 && mb_strlen($v, "UTF-8") > $maxlen)
                $v = mb_substr($v, 0, $maxlen, "UTF-8") . "…";
            if (strpbrk($v, ",\"\r\n") !== false)
                $v = '"' . str_replace('"', '""', $v) . '"';
            $line[] = $v;
        }
        $ret .= implode(",", $line) . "\n";
    }
    return $ret;
}

//--------------------------------- helpers -----------------------------------<
