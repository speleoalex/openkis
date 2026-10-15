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
 * Configuration (openkis_config.local.php):
 *   $_FN['openkis_mcp_apikey']   = "long-random-key";   // required, string or array of keys
 *   $_FN['openkis_mcp_user']     = "admin";             // user recorded as author of the changes
 *   $_FN['openkis_mcp_readonly'] = 0;                   // 1 = disable insert/update/delete
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
if (!mcp_CheckApiKey())
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
$_FN['user'] = !empty($_FN['openkis_mcp_user']) ? $_FN['openkis_mcp_user'] : "mcp";
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
 * @return bool
 */
function mcp_CheckApiKey()
{
    global $_FN;
    if (empty($_FN['openkis_mcp_apikey']))
    {
        return false; // server disabled until a key is configured
    }
    $given = "";
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
        $given = trim($m[1]);
    elseif (!empty($_SERVER['HTTP_X_API_KEY']))
        $given = trim($_SERVER['HTTP_X_API_KEY']);
    elseif (!empty($_GET['apikey']))
        $given = trim($_GET['apikey']);
    if ($given == "")
        return false;
    foreach ((array) $_FN['openkis_mcp_apikey'] as $key)
    {
        if ($key != "" && hash_equals((string) $key, $given))
            return true;
    }
    return false;
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
                "instructions" => "OpenKIS speleological cadastre DB. Data is CSV. Start with `sections` (dbview sections and their tables) then `schema`. Prefer `section` over `table` for catalogue data: writes then keep history and run the section hooks. Values are read and written in storage format."
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
        "name" => "sections",
        "description" => "List dbview sections: id,table,title,parent,history,delete",
        "inputSchema" => array("type" => "object", "properties" => new stdClass())
    );
    $tools[] = array(
        "name" => "tables",
        "description" => "List database tables: table,driver,section",
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
        "where" => array("type" => "string", "description" => "SQL WHERE condition (mysql tables only), e.g. depth_total>100 AND comune='FINALE LIGURE'"),
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
    $tools[] = array(
        "name" => "sql",
        "description" => "Read-only SQL on MySQL (SELECT/SHOW/DESCRIBE/EXPLAIN, single statement). Result as CSV",
        "inputSchema" => array("type" => "object", "properties" => array(
            "query" => array("type" => "string"),
            "limit" => array("type" => "integer", "description" => "max rows returned, default 200"),
            "maxlen" => array("type" => "integer", "description" => "max chars per value, default 0 = no limit")
            ), "required" => array("query"))
    );
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
    $writeTools = array("insert", "update", "delete");
    try
    {
        $function = "mcp_Tool_" . $name;
        if (!preg_match('/^[a-z]+$/', $name) || !function_exists($function))
            throw new McpToolError("unknown tool $name");
        if (in_array($name, $writeTools) && !empty($_FN['openkis_mcp_readonly']))
            throw new McpToolError("server is read-only");
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
        $config = mcp_SectionConfig($id);
        $rows[] = array($id, $config['tables'], html_entity_decode($section['title'], ENT_QUOTES, "UTF-8"), $section['parent'], empty($config['enable_history']) ? 0 : 1, empty($config['enable_delete']) ? 0 : (empty($config['hide_on_delete']) ? 1 : "hide"));
    }
    return mcp_Csv(array_merge(array(array("id", "table", "title", "parent", "history", "delete")), $rows));
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
        $bytable[$config['tables']][] = $id;
    }
    $rows = array(array("table", "driver", "section"));
    foreach (glob("{$_FN['datadir']}/{$_FN['database']}/*.php") as $file)
    {
        $tablename = basename($file, ".php");
        if (!preg_match('/^[A-Za-z0-9_]+$/', $tablename))
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
    $hideDeleted = empty($args['deleted']) && isset($Table->fields['recorddeleted']);
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
    $total = $Table->GetNumRecords($restr);
    if (!$native && $hideDeleted)
        $total = null; // approximated below
    if (!empty($args['count']))
    {
        if ($total === null)
            $total = count(mcp_FilterDeleted($Table->GetRecords($restr, false, false, false, false, array($src['pk'], "recorddeleted"))));
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
    if ($native)
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
    if (!is_array($record))
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
    $src = mcp_Source($args);
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
    $src = mcp_Source($args);
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
function mcp_Tool_delete($args)
{
    global $_FN;
    $src = mcp_Source($args);
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
 * @return array changed fields
 */
function mcp_UpdateRecord($src, $id, $values)
{
    global $_FN;
    $pk = $src['pk'];
    mcp_CheckPk($id);
    $Form = FN_XMDBForm($src['table']);
    $old = $Form->xmltable->GetRecordByPrimaryKey($id);
    if (!is_array($old))
        throw new McpToolError("not found");
    unset($values[$pk]);
    mcp_CheckWritableFields($src['Table'], $values);
    if ($src['section'])
        $values = mcp_Dbview($src)->Transform($values, $Form);
    $changed = array();
    foreach ($values as $k => $v)
    {
        if ($v === null)
            $v = "";
        if (is_bool($v))
            $v = $v ? "1" : "0";
        if (!array_key_exists($k, $old) || (string) $old[$k] !== (string) $v)
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
        if (in_array($Table->fields[$k]->type, array("file", "image")))
            throw new McpToolError("field $k is a file/image field, upload not supported");
        if (is_array($v) || is_object($v))
            throw new McpToolError("field $k: scalar value expected");
    }
}

//--------------------------------- writes ------------------------------------<
//--------------------------------- helpers ----------------------------------->

/**
 * Resolves section/table arguments. A table used by exactly one dbview section is handled as that section.
 * @param array $args
 * @return array table, section, config, Table, pk
 */
function mcp_Source($args)
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
