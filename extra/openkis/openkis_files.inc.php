<?php
/**
 * Protected files of the register (survey drawings, photos, attachments,
 * PDFs, 3d models): they are served by openkis_file.php after the same
 * permission check as the record pages, behind an opaque address that does
 * not reveal where the file is stored, and their folders in the data folder
 * are closed to direct requests.
 *
 * finis calls openkis_FileUrl() to build the links of the files
 * ($_FN['xmetadb_file_url']) and openkis_FileAccess() before serving a
 * thumbnail ($_FN['xmetadb_file_access']); both are registered by
 * openkis_files_hooks.php, which runs after the local configuration, so
 * $_FN['openkis_protected_files'] = false in openkis_config.local.php keeps
 * the old direct paths.
 *
 * Based on the work of Roberto Avogadri (ravogdev/openkis 654b85b): same
 * endpoint and tokens, with the access rule of the record pages, so that the
 * files of a public register stay public.
 */
defined('_FNEXEC') or die('Restricted access');

/**
 * Tables whose files are protected:
 *   table => array(
 *     section that shows the table ("" when it has none, as the photos and
 *     attachments shown inside the cave sheet),
 *     items the record belongs to: code field => item table (a survey of a
 *     cave follows the cave as well),
 *     item table whose history the table keeps ("" for the others)
 *   )
 * Tables not listed here keep their direct links (users' avatars, news,
 * WISH regions, associations' logos...).
 *
 * @return array
 */
function openkis_FileTables()
{
    return array(
        "ctl_caves" => array("caves", array(), ""),
        "ctl_artificials" => array("artificials", array(), ""),
        "ctl_glacial" => array("glacial", array(), ""),
        "ctl_springs" => array("springs", array(), ""),
        "ctl_uncatalogued" => array("uncatalogued", array(), ""),
        "ctl_areas" => array("areas", array(), ""),
        "ctl_glaciers" => array("glaciers", array(), ""),
        "ctl_cavesystems" => array("cavesystem", array(), ""),
        "ctl_bibliography" => array("bibliography", array(), ""),
        "ctl_fauna" => array("fauna", array(), ""),
        "ctl_tracings" => array("tracings", array(), ""),
        "ctl_temperatures" => array("temperatures", array(), ""),
        "ctl_surveys" => array("survey", array("codecave" => "ctl_caves"), ""),
        "ctl_surveys_artificials" => array("survey_artificials", array("codeartificial" => "ctl_artificials"), ""),
        "ctl_surveys_glacial" => array("glacial_surveys", array("codeglacial" => "ctl_glacial"), ""),
        "ctl_faunacave" => array("fauna_surveys", array("codecave" => "ctl_caves", "codeartificial" => "ctl_artificials"), ""),
        "ctl_photos" => array("", array("codecave" => "ctl_caves"), ""),
        "ctl_attachments" => array("", array("codecave" => "ctl_caves"), ""),
        "ctl_photos_artificials" => array("", array("codeartificial" => "ctl_artificials"), ""),
        "ctl_attachments_artificials" => array("", array("codeartificial" => "ctl_artificials"), ""),
        "ctl_caves_versions" => array("", array(), "ctl_caves"),
        "ctl_artificials_versions" => array("", array(), "ctl_artificials"),
        "ctl_glacial_versions" => array("", array(), "ctl_glacial"),
    );
}

/**
 * @return bool false when the site keeps the old direct links
 *              ($_FN['openkis_protected_files'] = false)
 */
function openkis_ProtectedFiles()
{
    global $_FN;
    return !isset($_FN['openkis_protected_files']) || $_FN['openkis_protected_files'];
}

/**
 * Cipher of the file tokens: sodium (secretbox) when the extension is there,
 * otherwise OpenSSL AES-256-GCM; "" when neither is available, and then the
 * files keep their direct links.
 *
 * @return string "sodium", "openssl" or ""
 */
function openkis_FileCipher()
{
    static $cipher = null;
    if ($cipher === null)
    {
        $cipher = "";
        if (function_exists("sodium_crypto_secretbox"))
            $cipher = "sodium";
        elseif (function_exists("openssl_encrypt") && in_array("aes-256-gcm", openssl_get_cipher_methods()))
            $cipher = "openssl";
    }
    return $cipher;
}

/**
 * Secret key for the file tokens, derived from the site secret so it never
 * appears in the code or in the data folder; one key per purpose ("enc" to
 * encrypt, "nonce" to derive the nonce).
 *
 * @param string $purpose
 * @return string 32 raw bytes
 */
function openkis_FileKey($purpose)
{
    return hash_hmac("sha256", "openkis-file-$purpose", FN_GetSiteSecret(), true);
}

/**
 * Opaque token for one file: table, record id, field, stored name and the
 * thumbnail flag, encrypted and authenticated, so the address reveals none
 * of them and cannot be edited to reach another file. The nonce comes from
 * the content, so a file always gets the same token (browser caches and the
 * cache of the sheets keep working).
 *
 * @param string $table
 * @param string $id
 * @param string $field
 * @param string $filename stored value of the field
 * @param bool $thumb
 * @return string|false base64url of the encrypted data, false without a cipher
 */
function openkis_FileToken($table, $id, $field, $filename, $thumb = false)
{
    $plain = json_encode(array((string) $table, (string) $id, (string) $field, (string) $filename, $thumb ? 1 : 0));
    $mac = hash_hmac("sha256", $plain, openkis_FileKey("nonce"), true);
    switch (openkis_FileCipher())
    {
        case "sodium":
            $nonce = substr($mac, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $raw = $nonce . sodium_crypto_secretbox($plain, $nonce, openkis_FileKey("enc"));
            break;
        case "openssl":
            // the iv comes from the content as the nonce above: the same iv is
            // used again only for the same file, which gives the same token
            $iv = substr($mac, 0, 12);
            $tag = "";
            $box = openssl_encrypt($plain, "aes-256-gcm", openkis_FileKey("enc"), OPENSSL_RAW_DATA, $iv, $tag, "", 16);
            if ($box === false)
                return false;
            $raw = $iv . $tag . $box;
            break;
        default:
            return false;
    }
    return rtrim(strtr(base64_encode($raw), "+/", "-_"), "=");
}

/**
 * Reverses openkis_FileToken().
 *
 * @param string $token
 * @return array|false array(table, id, field, filename, thumb), false for a
 *                     missing, malformed or altered token
 */
function openkis_FileTokenDecode($token)
{
    if (!is_string($token) || $token == "")
        return false;
    $raw = base64_decode(strtr($token, "-_", "+/"), true);
    if ($raw === false)
        return false;
    $plain = false;
    switch (openkis_FileCipher())
    {
        case "sodium":
            if (strlen($raw) > SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)
                $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), openkis_FileKey("enc"));
            break;
        case "openssl":
            if (strlen($raw) > 28)
                $plain = openssl_decrypt(substr($raw, 28), "aes-256-gcm", openkis_FileKey("enc"), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
            break;
    }
    if ($plain === false)
        return false;
    $data = json_decode($plain, true);
    if (!is_array($data) || count($data) < 5)
        return false;
    return array(
        "table" => (string) $data[0],
        "id" => (string) $data[1],
        "field" => (string) $data[2],
        "filename" => (string) $data[3],
        "thumb" => !empty($data[4]),
    );
}

/**
 * Address of a file through openkis_file.php. The name after n= is only
 * there for the 3d viewers and the map, which pick the format from the
 * extension.
 *
 * @param string $token
 * @param string $filename stored value of the field
 * @return string relative to the site
 */
function openkis_FileEndpoint($token, $filename)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return "openkis_file.php?f=$token&n=file" . ($ext !== "" ? ".$ext" : "");
}

/**
 * Hook of XMETATable::getFileUrl() ($_FN['xmetadb_file_url']): the address of
 * a record's file, or of its thumbnail. For the tables of
 * openkis_FileTables() an opaque link to openkis_file.php, for the others the
 * file's own path as before. False when the field is empty or the file is
 * missing.
 *
 * @param XMETATable $xmltable
 * @param array $recordvalues the record, or enough of it to find it
 * @param string $recordkey the file field
 * @param bool $thumb
 * @return string|false
 */
function openkis_FileUrl($xmltable, $recordvalues, $recordkey, $thumb = false)
{
    global $_FN;
    $siteurl = !empty($_FN['siteurl']) ? $_FN['siteurl'] : "";
    if (!is_array($recordvalues) || !isset($recordvalues[$recordkey]) || !isset($recordvalues[$xmltable->primarykey]))
        $recordvalues = $xmltable->GetRecord($recordvalues);
    if (!is_array($recordvalues))
        return false;
    $path = $thumb ? $xmltable->getThumbPath($recordvalues, $recordkey) : $xmltable->getFilePath($recordvalues, $recordkey);
    if (!$path || ($path[0] != "?" && !file_exists($path)))
        return false;
    $tables = openkis_FileTables();
    if (!openkis_ProtectedFiles() || !isset($tables[$xmltable->tablename]) || $path[0] == "?")
        return $siteurl . $path;
    $value = isset($recordvalues[$recordkey]) ? $recordvalues[$recordkey] : "";
    $id = isset($recordvalues[$xmltable->primarykey]) ? $recordvalues[$xmltable->primarykey] : "";
    if ($value === "" || $value === null || $id === "")
        return false;
    $token = openkis_FileToken($xmltable->tablename, $id, $recordkey, $value, $thumb);
    // without a cipher the file stays reachable the old way rather than
    // breaking the page (the folder guard is not written either)
    if ($token === false)
        return $siteurl . $path;
    return $siteurl . openkis_FileEndpoint($token, $value);
}

/**
 * Address of a register file for the 3d viewers (cave_viewer.php,
 * openkis_cave_viewer.php), relative to the site: their loader prepends the
 * site url and reads the format from the extension.
 *
 * @param string $table
 * @param string $id
 * @param string $field
 * @param string $filename stored value of the field
 * @return string
 */
function openkis_CaveViewerFile($table, $id, $field, $filename)
{
    global $_FN;
    $database = empty($_FN['database']) ? "fndatabase" : $_FN['database'];
    $tables = openkis_FileTables();
    $token = (openkis_ProtectedFiles() && isset($tables[$table])) ? openkis_FileToken($table, $id, $field, $filename, false) : false;
    if ($token === false)
        return "{$_FN['datadir']}/$database/$table/$id/$field/$filename";
    return openkis_FileEndpoint($token, $filename);
}

/**
 * dbview object of a section, once per request.
 *
 * @param string $mod
 * @return FNDBVIEW
 */
function openkis_FileDbview($mod)
{
    global $_FN;
    static $views = array();
    if (!isset($views[$mod]))
    {
        require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
        $views[$mod] = new FNDBVIEW(FN_LoadConfig("modules/dbview/config.php", $mod));
    }
    return $views[$mod];
}

/**
 * @param string $groups comma separated groups of a record
 * @return bool true when they do not restrict the record for the current user
 */
function openkis_FileUserInGroups($groups)
{
    global $_FN;
    $groups = trim(strval($groups));
    if ($groups === "")
        return true;
    if ($_FN['user'] == "")
        return false;
    return FN_UserInGroup($_FN['user'], $groups);
}

/**
 * Whether the current user may view a record of a table of
 * openkis_FileTables(), as on its page: the section of the table (when the
 * site has it) and the record's permissions (FNDBVIEW::CanViewRecord(): its
 * groups, the editors of the section, the creator); without a section, the
 * record's own groups.
 *
 * @param string $table
 * @param array $record
 * @return bool
 */
function openkis_FileCanViewRecord($table, $record)
{
    $tables = openkis_FileTables();
    $mod = isset($tables[$table]) ? $tables[$table][0] : "";
    if ($mod != "" && file_exists("sections/$mod"))
    {
        if (!FN_UserCanViewSection($mod))
            return false;
        $dbview = openkis_FileDbview($mod);
        if ($dbview->config['tables'] == $table)
        {
            $t = FN_XMDBTable($table);
            return $dbview->CanViewRecords($dbview->config) && $dbview->CanViewRecord($record[$t->primarykey], $table, $dbview->config);
        }
    }
    return openkis_FileUserInGroups(isset($record['groupview']) ? $record['groupview'] : "");
}

/**
 * Items (caves, artificial cavities, glacial caves) a record belongs to
 * through its code fields.
 *
 * @param string $table
 * @param array $record
 * @return array item table => item record
 */
function openkis_FileOwners($table, $record)
{
    $tables = openkis_FileTables();
    $ret = array();
    if (!isset($tables[$table]))
        return $ret;
    foreach ($tables[$table][1] as $codefield => $itemtable)
    {
        if (empty($record[$codefield]))
            continue;
        $it = FN_XMDBTable($itemtable);
        $rows = $it ? $it->GetRecords(array("code" => $record[$codefield])) : false;
        if (is_array($rows) && count($rows))
            $ret[$itemtable] = reset($rows);
    }
    return $ret;
}

/**
 * Whether the current user may read a file of a record ($_FN['xmetadb_file_access'],
 * openkis_file.php, the 3d viewer links):
 *  - administrators read everything; tables not in openkis_FileTables()
 *    keep the old behaviour (readable);
 *  - the history of an item (_versions tables) only for the administrators
 *    of its section, as the history page;
 *  - otherwise who may view the record (openkis_FileCanViewRecord()) and
 *    every item it belongs to (a survey follows its cave);
 *  - the entrance photo (photo1) of an item marked for the public map, with
 *    no group restriction, also for who may see only the public map.
 *
 * @param string $table
 * @param string $id
 * @param string $field
 * @return bool
 */
function openkis_FileAccess($table, $id, $field)
{
    if (FN_IsAdmin())
        return true;
    $tables = openkis_FileTables();
    if (!isset($tables[$table]))
        return true;
    $t = FN_XMDBTable($table);
    $record = $t ? $t->GetRecordByPrimaryKey($id) : false;
    if (!is_array($record) || !isset($record[$t->primarykey]) || !isset($t->fields[$field]))
        return false;
    if ($tables[$table][2] != "")
    {
        $mod = $tables[$tables[$table][2]][0];
        return file_exists("sections/$mod") && FN_UserCanViewSection($mod) && openkis_FileDbview($mod)->IsAdmin();
    }
    if (!openkis_FileCanViewRecord($table, $record))
    {
        $mod = $tables[$table][0];
        return ($field == "photo1" && $mod != "" && count($tables[$table][1]) == 0 && file_exists("sections/$mod")
            && openkis_SectionMapAccess($mod) == "public" && isset($record['public_map']) && $record['public_map'] == "Y"
            && trim(strval(isset($record['groupview']) ? $record['groupview'] : "")) === "");
    }
    foreach (openkis_FileOwners($table, $record) as $itemtable => $item)
    {
        if (!openkis_FileCanViewRecord($itemtable, $item))
            return false;
    }
    return true;
}

/**
 * Closes to direct requests the folders of the protected tables in the data
 * folder (an .htaccess per table folder, for Apache): their files are served
 * only by openkis_file.php. Folders of other tables stay as they are. Other
 * web servers (nginx, Caddy) must deny these folders in their own
 * configuration.
 *
 * @return void
 */
function openkis_FileGuardFolders()
{
    global $_FN;
    if (!openkis_ProtectedFiles() || openkis_FileCipher() == "")
        return;
    $database = empty($_FN['database']) ? "fndatabase" : $_FN['database'];
    $rules = "# OpenKIS: the files of this table are served by openkis_file.php after a\n"
        . "# permission check; their storage path must not work as an address.\n"
        . "<IfModule mod_authz_core.c>\n"
        . "    Require all denied\n"
        . "</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n"
        . "    Order allow,deny\n"
        . "    Deny from all\n"
        . "</IfModule>\n";
    foreach (array_keys(openkis_FileTables()) as $table)
    {
        $dir = "{$_FN['datadir']}/$database/$table";
        if (is_dir($dir) && !file_exists("$dir/.htaccess") && is_writable($dir))
            @file_put_contents("$dir/.htaccess", $rules);
    }
}

/**
 * Hook $_FN['dbview_excluded_values'] of finis dbview: the records of a table
 * that belong to a restricted item the current user may not view (the
 * surveys, sightings, photos of a cave reserved to some groups) are left out
 * of the lists, the searches and the MCP server, and their page is refused,
 * whatever the groups of the record itself.
 *
 * @param string $tablename
 * @return array code field => codes of the hidden items
 */
function openkis_HiddenRecordValues($tablename)
{
    $tables = openkis_FileTables();
    $ret = array();
    if (!isset($tables[$tablename]))
        return $ret;
    foreach ($tables[$tablename][1] as $codefield => $itemtable)
    {
        $codes = openkis_HiddenItemCodes($itemtable);
        if (count($codes))
            $ret[$codefield] = $codes;
    }
    return $ret;
}

/**
 * Codes of the items of a table (caves, artificial cavities) restricted to
 * groups the current user is not in, with the rule of
 * FNDBVIEW::CanViewRecord(): the groups of the item, and the editors and
 * administrators of its section; nothing is hidden when the section does
 * not enable the groups of the records.
 *
 * @param string $itemtable
 * @return array once per table per request
 */
function openkis_HiddenItemCodes($itemtable)
{
    global $_FN;
    static $cache = array();
    if (isset($cache[$itemtable]))
        return $cache[$itemtable];
    $cache[$itemtable] = array();
    $tables = openkis_FileTables();
    $t = FN_XMDBTable($itemtable);
    if (FN_IsAdmin() || !isset($tables[$itemtable]) || !$t || !isset($t->fields['groupview']) || !isset($t->fields['code']))
        return $cache[$itemtable];
    $mod = $tables[$itemtable][0];
    $usergroups = array();
    if ($_FN['user'] != "")
    {
        $uservalues = FN_GetUser($_FN['user']);
        $usergroups = array_filter(explode(",", isset($uservalues['group']) ? $uservalues['group'] : ""));
    }
    if ($mod != "" && file_exists("sections/$mod"))
    {
        $config = openkis_FileDbview($mod)->config;
        if (empty($config['enable_permissions_each_records']) || $config['enable_permissions_each_records'] != 1)
            return $cache[$itemtable];
        if (array_intersect($usergroups, array_filter(explode(",", $config['groupinsert'] . "," . $config['groupadmin']))))
            return $cache[$itemtable];
    }
    foreach ((array) $t->GetRecords(false, false, false, false, false, array($t->primarykey, "code", "groupview")) as $item)
    {
        $groups = trim(strval($item['groupview']));
        if ($groups === "" || strval($item['code']) === "" || array_intersect($usergroups, explode(",", $groups)))
            continue;
        $cache[$itemtable][] = strval($item['code']);
    }
    return $cache[$itemtable];
}
