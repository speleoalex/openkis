<?php

/**
 * Section: OpenKIS MCP server
 *
 * Shows the endpoint of the OpenKIS MCP server (openkis_mcp.php) and manages the personal
 * API keys of the users (fn_users.apikey, the same key used by the FINIS MCP server).
 * A client that uses a personal key acts with the permissions of the key owner.
 * Administrators (level >= 10) enable the users to use the MCP server (group
 * $_FN['apikey_groups'], default "mcp_users") and manage every key; an enabled user sees the
 * connection data and manages only their own key; the other users see nothing.
 * The write tools are allowed only to the administrators and to the users an administrator
 * allowed to write (group $_FN['openkis_mcp_write_group'], default "mcp_write").
 *
 * @package OpenKIS
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
FN_LoadMessagesFolder(__DIR__);
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";

if ($_FN['user'] == "")
{
    echo "<p>" . FN_Translate("log in to manage your MCP key") . "</p>";
    return;
}
$okmcp_link = "index.php?mod=" . urlencode($_FN['mod']);
$okmcp_isadmin = FN_IsAdmin();
$okmcp_message = "";
$okmcp_error = "";
$okmcp_group = okmcp_Group();
if (!$okmcp_isadmin && !okmcp_Enabled($_FN['user']))
{
    echo "<p>" . FN_Translate("your account is not enabled to use the MCP server, ask an administrator") . "</p>";
    return;
}
if ($okmcp_isadmin && $okmcp_group != "")
    FN_CreateGroupIfNotExists($okmcp_group);
if ($okmcp_isadmin && okmcp_WriteGroup() != "")
    FN_CreateGroupIfNotExists(okmcp_WriteGroup());

// non administrators can manage only their own key
$okmcp_user = $okmcp_isadmin ? FN_GetParam("user", $_GET, "html") : $_FN['user'];
if ($okmcp_user != "" && !FN_GetUser($okmcp_user, false))
{
    $okmcp_error = FN_Translate("user not found") . ": " . okmcp_h($okmcp_user);
    $okmcp_user = "";
}

//--------------------------- actions (POST only) ---------------------------->
$okmcp_action = FN_GetParam("okmcp_action", $_POST, "html");
if ($okmcp_action != "" && $okmcp_user != "")
{
    if (!hash_equals(okmcp_Token(), (string) FN_GetParam("okmcp_token", $_POST, "html")))
    {
        $okmcp_error = FN_Translate("invalid request, reload the page and try again");
    }
    elseif ($okmcp_action == "enable" && $okmcp_isadmin && $okmcp_group != "")
    {
        if (okmcp_SetGroup($okmcp_user, $okmcp_group, true))
        {
            $okmcp_message = FN_Translate("the user can now use the MCP server");
            FN_LogEvent("MCP enabled for user $okmcp_user by {$_FN['user']}", "apikey");
        }
        else
            $okmcp_error = FN_Translate("error");
    }
    elseif ($okmcp_action == "disable" && $okmcp_isadmin && $okmcp_group != "")
    {
        // the key and the write permission are revoked too
        if (okmcp_SetGroup($okmcp_user, $okmcp_group, false) && okmcp_SetGroup($okmcp_user, okmcp_WriteGroup(), false) && okmcp_RevokeKey($okmcp_user))
        {
            $okmcp_message = FN_Translate("the user can no longer use the MCP server, the key has been revoked");
            FN_LogEvent("MCP disabled and key revoked for user $okmcp_user by {$_FN['user']}", "apikey");
        }
        else
            $okmcp_error = FN_Translate("error");
    }
    elseif (($okmcp_action == "allow_write" || $okmcp_action == "deny_write") && $okmcp_isadmin && okmcp_WriteGroup() != "")
    {
        $allow = $okmcp_action == "allow_write";
        if ($allow && !okmcp_Enabled($okmcp_user))
            $okmcp_error = FN_Translate("enable the user before allowing the write tools");
        elseif (okmcp_SetGroup($okmcp_user, okmcp_WriteGroup(), $allow))
        {
            $okmcp_message = $allow ? FN_Translate("the user can now use the write tools") : FN_Translate("the user can no longer use the write tools: read only");
            FN_LogEvent("MCP write tools " . ($allow ? "allowed" : "denied") . " for user $okmcp_user by {$_FN['user']}", "apikey");
        }
        else
            $okmcp_error = FN_Translate("error");
    }
    elseif ($okmcp_action == "generate" && !okmcp_Enabled($okmcp_user))
    {
        $okmcp_error = FN_Translate("enable the user before generating the key");
    }
    elseif ($okmcp_action == "generate")
    {
        if (FN_GenerateApiKey($okmcp_user) != "")
        {
            $okmcp_message = FN_Translate("a new MCP key has been generated, the previous key no longer works");
            FN_LogEvent("MCP key generated for user $okmcp_user by {$_FN['user']}", "apikey");
        }
        else
            $okmcp_error = FN_Translate("error");
    }
    elseif ($okmcp_action == "revoke")
    {
        if (okmcp_RevokeKey($okmcp_user))
        {
            $okmcp_message = FN_Translate("the MCP key has been revoked");
            FN_LogEvent("MCP key revoked for user $okmcp_user by {$_FN['user']}", "apikey");
        }
        else
            $okmcp_error = FN_Translate("error");
    }
}
//--------------------------- actions (POST only) ----------------------------<

$okmcp_url = okmcp_ServerUrl();
echo "<div class=\"okmcp\">";
if ($okmcp_message != "")
    echo "<div class=\"alert alert-success\">" . okmcp_h($okmcp_message) . "</div>";
if ($okmcp_error != "")
    echo "<div class=\"alert alert-danger\">$okmcp_error</div>";

//--------------------------- server ----------------------------------------->
echo "<h3>" . FN_Translate("OpenKIS MCP server") . "</h3>";
echo "<p>" . FN_Translate("The MCP server lets AI assistants (Claude, ChatGPT, ...) read and edit the cadastre. Every enabled user has a personal key: the assistant works with the same permissions the user has on the website.") . "</p>";
echo "<div class=\"mb-3\"><label class=\"form-label\"><b>" . FN_Translate("server URL") . "</b></label>";
echo "<input type=\"text\" class=\"form-control\" readonly=\"readonly\" onclick=\"this.select()\" value=\"" . okmcp_h($okmcp_url) . "\" /></div>";
echo "<ul>";
echo "<li>" . FN_Translate("transport") . ": <code>Streamable HTTP</code></li>";
echo "<li>" . FN_Translate("authentication") . ": " . FN_Translate("header", "") . " <code>X-API-Key: &lt;" . FN_Translate("key", "") . "&gt;</code> " . FN_Translate("or", "") . " <code>Authorization: Bearer &lt;" . FN_Translate("key", "") . "&gt;</code></li>";
if (isset($_FN['openkis_mcp_userkeys']) && !$_FN['openkis_mcp_userkeys'])
    echo "<li><span class=\"text-danger\">" . FN_Translate("the personal keys are disabled in the configuration") . " (<code>\$_FN['openkis_mcp_userkeys'] = 0</code>)</span></li>";
if (!empty($_FN['openkis_mcp_readonly']))
    echo "<li><span class=\"text-warning\">" . FN_Translate("the server is read only for everybody") . " (<code>\$_FN['openkis_mcp_readonly']</code>)</span></li>";
if ($okmcp_isadmin)
{
    $legacy = isset($_FN['openkis_mcp_apikey']) && count(array_filter((array) $_FN['openkis_mcp_apikey'])) > 0;
    echo "<li>" . FN_Translate("global key with full access") . " (<code>\$_FN['openkis_mcp_apikey']</code>): " . ($legacy ? FN_Translate("configured") : FN_Translate("not configured")) . "</li>";
}
echo "<li>" . FN_Translate("the same key also works with the MCP server of the CMS") . ": <code>" . okmcp_h(FN_GetMcpUrl()) . "</code></li>";
echo "</ul>";
//--------------------------- server -----------------------------------------<

if ($okmcp_user != "")
{
    okmcp_HtmlUser($okmcp_user, $okmcp_link, $okmcp_isadmin);
}
if ($okmcp_isadmin)
{
    okmcp_HtmlUsers($okmcp_link);
}
echo "</div>";

/**
 * Absolute URL of the OpenKIS MCP endpoint
 * @return string
 */
function okmcp_ServerUrl()
{
    global $_FN;
    $siteurl = isset($_FN['siteurl']) ? $_FN['siteurl'] : "";
    if ($siteurl != "" && substr($siteurl, -1) != "/")
        $siteurl .= "/";
    return $siteurl . "openkis_mcp.php";
}

/**
 * Group of the users enabled to use the MCP server (first of $_FN['apikey_groups']),
 * empty when every user can use an API key
 * @return string
 */
function okmcp_Group()
{
    global $_FN;
    if (empty($_FN['apikey_groups']))
        return "";
    $groups = array_filter(array_map("trim", explode(",", $_FN['apikey_groups'])));
    return count($groups) ? reset($groups) : "";
}

/**
 * @param string $user
 * @return bool true if the user can use the MCP server (administrators always can)
 */
function okmcp_Enabled($user)
{
    if (function_exists("FN_UserCanUseApiKey"))
        return FN_UserCanUseApiKey($user);
    return true;
}

/**
 * Group of the users allowed to use the write tools
 * @return string
 */
function okmcp_WriteGroup()
{
    global $_FN;
    return isset($_FN['openkis_mcp_write_group']) ? trim($_FN['openkis_mcp_write_group']) : "mcp_write";
}

/**
 * @param string|array $user username or user values
 * @return bool true if the user can use the write tools (administrators always can)
 */
function okmcp_CanWrite($user)
{
    $uservalues = is_array($user) ? $user : FN_GetUser($user, false);
    if (!empty($uservalues['level']) && $uservalues['level'] >= 10)
        return true;
    $group = okmcp_WriteGroup();
    if ($group == "" || empty($uservalues['group']))
        return false;
    return in_array($group, array_map("trim", explode(",", $uservalues['group'])));
}

/**
 * Add or remove the user from a group
 * @param string $user
 * @param string $group
 * @param bool $enabled
 * @return bool
 */
function okmcp_SetGroup($user, $group, $enabled)
{
    $uservalues = FN_GetUser($user, false);
    if ($group == "" || empty($uservalues['username']))
        return false;
    $groups = array();
    foreach (explode(",", isset($uservalues['group']) ? $uservalues['group'] : "") as $g)
    {
        $g = trim($g);
        if ($g != "" && $g != $group && !in_array($g, $groups))
            $groups[] = $g;
    }
    if ($enabled)
        $groups[] = $group;
    $table = FN_GetUserForm();
    $result = $table->xmltable->UpdateRecordBypk(array("group" => implode(",", $groups)), $table->xmltable->primarykey, $user);
    FN_GetUser($user, false);
    return is_array($result);
}

/**
 * Anti CSRF token bound to the logged user, derived from server side secrets only
 * @return string
 */
function okmcp_Token()
{
    global $_FN;
    $uservalues = FN_GetUser($_FN['user'], false);
    $form = FN_GetUserForm();
    $pwfield = empty($form->fieldname_password) ? "passwd" : $form->fieldname_password;
    $secret = (isset($uservalues[$pwfield]) ? $uservalues[$pwfield] : "") . "|" . __FILE__ . "|" . (isset($_FN['datadir']) ? realpath($_FN['datadir']) : "");
    return hash_hmac("sha256", "openkis_mcp|" . $_FN['user'], $secret);
}

/**
 * Remove the API key of a user
 * @param string $user
 * @return bool
 */
function okmcp_RevokeKey($user)
{
    FN_EnsureUserApiKeyField();
    $table = FN_GetUserForm();
    $result = $table->xmltable->UpdateRecordBypk(array("apikey" => ""), $table->xmltable->primarykey, $user);
    FN_GetUser($user, false);
    return is_array($result);
}

/**
 * Show only the beginning and the end of a key
 * @param string $key
 * @return string
 */
function okmcp_MaskKey($key)
{
    if ($key == "")
        return "";
    return substr($key, 0, 4) . str_repeat("&bull;", 8) . substr($key, -4);
}

/**
 * Access of a user to the dbview sections, computed with the same rules used by
 * openkis_mcp.php (mcp_SectionAccess, mcp_NeedsRowFilter).
 * The current user is switched temporarily to evaluate the dbview permissions.
 *
 * @param string $user
 * @return array id => array(title, access, filtered)
 */
function okmcp_UserAccess($user)
{
    global $_FN;
    $bk_user = $_FN['user'];
    $bk_uservalues = isset($_FN['uservalues']) ? $_FN['uservalues'] : null;
    $_FN['user'] = $user;
    $_FN['uservalues'] = FN_GetUser($user, false);
    $ret = array();
    try
    {
        $isadmin = FN_IsAdmin();
        foreach (FN_GetAllSections() as $id => $section)
        {
            if (!isset($section['type']) || $section['type'] != "dbview")
                continue;
            $config = FN_LoadConfig("modules/dbview/config.php", $id);
            $tables = explode(",", $config['tables']);
            $config['tables'] = trim($tables[0]);
            $access = "";
            if ($isadmin)
            {
                $access = "admin";
            }
            elseif (FN_UserCanViewSection($id))
            {
                $dbview = new FNDBVIEW($config);
                if ($dbview->CanViewRecords($config))
                {
                    if ($dbview->IsAdmin())
                        $access = "admin";
                    elseif ($dbview->CanAddRecord())
                        $access = "write";
                    else
                        $access = "read";
                }
            }
            $site = $access;
            if (!$isadmin && $access != "" && !okmcp_CanWrite($user))
                $access = "read";
            $filtered = !$isadmin && $access != "" && (!empty($config['viewonlycreator']) || (isset($config['enable_permissions_each_records']) && $config['enable_permissions_each_records'] == 1));
            $ret[$id] = array("title" => isset($section['title']) ? html_entity_decode($section['title'], ENT_QUOTES, "UTF-8") : $id, "table" => $config['tables'], "access" => $access, "site" => $site, "filtered" => $filtered);
        }
    }
    catch (Exception $e)
    {
        $ret = array();
    }
    $_FN['user'] = $bk_user;
    $_FN['uservalues'] = $bk_uservalues;
    return $ret;
}

/**
 * Key, client configuration and permissions of a user
 * @param string $user
 * @param string $link
 * @param bool $isadmin
 */
function okmcp_HtmlUser($user, $link, $isadmin)
{
    global $_FN;
    $uservalues = FN_GetUser($user, false);
    $key = isset($uservalues['apikey']) ? $uservalues['apikey'] : "";
    $userlink = $isadmin ? "$link&amp;user=" . urlencode($user) : $link;
    $token = okmcp_Token();

    echo "<hr /><h3>" . FN_Translate("MCP key of") . " " . okmcp_h($user) . "</h3>";
    echo "<p>" . FN_Translate("level") . ": " . okmcp_h($uservalues['level']) . " &nbsp; " . FN_Translate("groups") . ": " . okmcp_h(str_replace(",", ", ", $uservalues['group'])) . "";
    if (empty($uservalues['active']) || $uservalues['active'] != 1)
        echo " &nbsp; <span class=\"text-danger\">" . FN_Translate("user not active: the key does not work") . "</span>";
    echo "</p>";

    $enabled = okmcp_Enabled($user);
    $group = okmcp_Group();
    if ($isadmin && $group != "")
    {
        echo "<form method=\"post\" action=\"$userlink\" class=\"mb-3\">";
        echo "<input type=\"hidden\" name=\"okmcp_token\" value=\"$token\" />";
        if (FN_IsAdmin($user))
        {
            echo "<p>" . FN_Translate("administrator: always enabled to use the MCP server") . "</p>";
        }
        elseif ($enabled)
        {
            echo "<p><span class=\"text-success\">" . FN_Translate("enabled to use the MCP server") . "</span> (" . FN_Translate("group") . " <code>" . okmcp_h($group) . "</code>) ";
            echo "<button class=\"btn btn-warning btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"disable\" onclick=\"return confirm('" . addslashes(FN_Translate("disable the user? The key will be revoked")) . "')\">" . FN_Translate("disable") . "</button></p>";
            if (okmcp_WriteGroup() != "")
            {
                // separate form: the hidden action must not override the disable button
                echo "</form><form method=\"post\" action=\"$userlink\" class=\"mb-3\">";
                echo "<input type=\"hidden\" name=\"okmcp_token\" value=\"$token\" />";
                if (okmcp_CanWrite($user))
                {
                    echo "<p>&#x270F;&#xFE0F; <span class=\"text-success\">" . FN_Translate("write tools allowed") . "</span> <small class=\"text-muted\">(insert, update, upload, delete; " . FN_Translate("group") . " <code>" . okmcp_h(okmcp_WriteGroup()) . "</code>)</small> ";
                    echo "<button class=\"btn btn-outline-warning btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"deny_write\">" . FN_Translate("set read only") . "</button></p>";
                }
                else
                {
                    echo "<p>&#x1F441;&#xFE0F; " . FN_Translate("read only: the write tools are not allowed") . " ";
                    echo "<button class=\"btn btn-outline-primary btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"allow_write\" onclick=\"return confirm('" . addslashes(FN_Translate("allow the write tools? The user will be able to insert, edit and delete records through the MCP server")) . "')\">&#x270F;&#xFE0F; " . FN_Translate("allow the write tools") . "</button></p>";
                }
            }
        }
        else
        {
            echo "<p><span class=\"text-danger\">" . FN_Translate("not enabled to use the MCP server") . "</span> ";
            echo "<button class=\"btn btn-primary btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"enable\">" . FN_Translate("enable") . "</button></p>";
        }
        echo "</form>";
    }
    if (!$enabled)
    {
        if ($key != "")
            echo "<p class=\"text-muted\">" . FN_Translate("the user has an old key that is refused by the server") . " <code>" . okmcp_MaskKey($key) . "</code></p>";
        return;
    }

    echo "<form method=\"post\" action=\"$userlink\" class=\"mb-3\">";
    echo "<input type=\"hidden\" name=\"okmcp_token\" value=\"$token\" />";
    if ($key != "")
    {
        echo "<div class=\"mb-2\"><input type=\"text\" class=\"form-control\" readonly=\"readonly\" onclick=\"this.select()\" value=\"" . okmcp_h($key) . "\" /></div>";
        echo "<button class=\"btn btn-secondary btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"generate\" onclick=\"return confirm('" . addslashes(FN_Translate("generate a new key? The clients that use the current key will stop working")) . "')\">" . FN_Translate("generate a new key") . "</button> ";
        echo "<button class=\"btn btn-danger btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"revoke\" onclick=\"return confirm('" . addslashes(FN_Translate("revoke the key? The clients that use it will stop working")) . "')\">" . FN_Translate("revoke the key") . "</button>";
    }
    else
    {
        echo "<p>" . FN_Translate("this user has no MCP key") . "</p>";
        echo "<button class=\"btn btn-primary btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"generate\">" . FN_Translate("generate the key") . "</button>";
    }
    echo "</form>";

    if ($key != "")
    {
        $url = okmcp_ServerUrl();
        $cmd = "claude mcp add --transport http openkis $url --header \"X-API-Key: $key\"";
        $json = "{\n  \"mcpServers\": {\n    \"openkis\": {\n      \"type\": \"http\",\n      \"url\": \"$url\",\n      \"headers\": { \"X-API-Key\": \"$key\" }\n    }\n  }\n}";
        echo "<p><b>" . FN_Translate("Claude Code") . "</b></p>";
        echo "<pre style=\"white-space:pre-wrap;word-break:break-all\">" . okmcp_h($cmd) . "</pre>";
        echo "<p><b>" . FN_Translate("other MCP clients (JSON configuration)") . "</b></p>";
        echo "<pre>" . okmcp_h($json) . "</pre>";
        echo "<p class=\"text-muted\">" . FN_Translate("the key is personal: whoever has it can act on the cadastre as this user") . "</p>";
    }

    //--------------------------- permissions -------------------------------->
    $icons = array(
        "admin" => "&#x1F6E1;&#xFE0F;",
        "write" => "&#x270F;&#xFE0F;",
        "read" => "&#x1F441;&#xFE0F;",
        "" => "&#x1F6AB;",
    );
    $labels = array(
        "admin" => FN_Translate("read and edit all records"),
        "write" => FN_Translate("read, insert and edit the records allowed"),
        "read" => FN_Translate("read only"),
        "" => FN_Translate("no access"),
    );
    $filteredicon = "&#x1F512;";
    echo "<p><b>" . FN_Translate("permissions of the key") . "</b></p>";
    if (FN_IsAdmin($user))
    {
        echo "<p>{$icons['admin']} " . FN_Translate("administrator: full access, including the sql tool") . "</p>";
    }
    else
    {
        $canwrite = okmcp_CanWrite($user);
        echo "<p>" . ($canwrite ? "{$icons['write']} " . FN_Translate("write tools allowed") : "{$icons['read']} " . FN_Translate("read only: the write tools are not allowed")) . "</p>";
        echo "<table class=\"table table-sm table-striped\"><thead><tr><th>" . FN_Translate("section") . "</th><th>" . FN_Translate("table") . "</th><th>" . FN_Translate("access") . "</th></tr></thead><tbody>";
        foreach (okmcp_UserAccess($user) as $id => $item)
        {
            $note = "";
            if ($item['filtered'])
                $note .= " <small class=\"text-muted\" title=\"" . okmcp_h(FN_Translate("only the records visible to the user")) . "\">$filteredicon " . FN_Translate("only the records visible to the user") . "</small>";
            if ($item['site'] != $item['access'])
                $note .= " <small class=\"text-muted\">(" . FN_Translate("on the site") . ": {$icons[$item['site']]} " . $labels[$item['site']] . ")</small>";
            $class = $item['access'] == "" ? " class=\"text-muted\"" : "";
            echo "<tr$class><td>" . okmcp_h($item['title']) . " <small>($id)</small></td><td>" . okmcp_h($item['table']) . "</td><td>{$icons[$item['access']]} " . $labels[$item['access']] . "$note</td></tr>";
        }
        echo "</tbody></table>";
        echo "<p class=\"text-muted small\">";
        foreach ($icons as $k => $icon)
            echo "$icon " . $labels[$k] . " &nbsp; ";
        echo "$filteredicon " . FN_Translate("only the records visible to the user") . "</p>";
        echo "<p class=\"text-muted\">" . FN_Translate("the lookup tables used by the readable sections are readable too; the sql tool is reserved to the administrators") . "</p>";
    }
    //--------------------------- permissions --------------------------------<
}

/**
 * Search the users and list the keys (administrators only)
 * @param string $link
 */
function okmcp_HtmlUsers($link)
{
    global $_FN;
    $q = trim(FN_GetParam("q", $_GET, "html"));
    $table = FN_GetUserForm();
    $users = $table->xmltable->GetRecords();
    if (!is_array($users))
        $users = array();
    $list = array();
    foreach ($users as $u)
    {
        $haskey = !empty($u['apikey']);
        if ($q == "")
        {
            if ($haskey || (okmcp_Group() != "" && okmcp_Enabled($u)))
                $list[] = $u;
        }
        else
        {
            $text = strtolower("{$u['username']} {$u['email']} {$u['name']} {$u['surname']}");
            if (strpos($text, strtolower($q)) !== false)
                $list[] = $u;
        }
    }
    usort($list, function ($a, $b) {
        return strcasecmp($a['username'], $b['username']);
    });

    echo "<hr /><h3>" . FN_Translate("users") . "</h3>";
    echo "<form method=\"get\" action=\"index.php\" class=\"mb-3\">";
    echo "<input type=\"hidden\" name=\"mod\" value=\"" . okmcp_h($_FN['mod']) . "\" />";
    echo "<div class=\"input-group\" style=\"max-width:30em\"><input type=\"text\" class=\"form-control\" name=\"q\" value=\"" . okmcp_h($q) . "\" placeholder=\"" . FN_Translate("username, email, name") . "\" />";
    echo "<button class=\"btn btn-primary\" type=\"submit\">" . FN_Translate("search") . "</button></div>";
    echo "</form>";
    if ($q == "")
        echo "<p>" . FN_Translate("users enabled or with an MCP key") . ": " . count($list) . ". " . FN_Translate("search a user to enable them or to generate a key") . "</p>";
    $max = 100;
    if (count($list) > $max)
        echo "<p>" . FN_Translate("too many results, only the first ones are shown") . " ($max / " . count($list) . ")</p>";
    if (count($list) == 0)
        return;
    echo "<table class=\"table table-sm table-striped\"><thead><tr><th>" . FN_Translate("user") . "</th><th>" . FN_Translate("name") . "</th><th>" . FN_Translate("level") . "</th><th>" . FN_Translate("groups") . "</th><th>" . FN_Translate("MCP") . "</th><th>" . FN_Translate("key") . "</th><th></th></tr></thead><tbody>";
    foreach (array_slice($list, 0, $max) as $u)
    {
        $class = (empty($u['active']) || $u['active'] != 1) ? " class=\"text-muted\"" : "";
        echo "<tr$class><td>" . okmcp_h($u['username']) . "</td>";
        echo "<td>" . okmcp_h(trim("{$u['name']} {$u['surname']}")) . "</td>";
        echo "<td>" . okmcp_h($u['level']) . "</td>";
        echo "<td>" . okmcp_h(str_replace(",", ", ", $u['group'])) . "</td>";
        if (!okmcp_Enabled($u))
            $mcp = "<span class=\"text-muted\">&#x1F6AB; " . FN_Translate("not enabled") . "</span>";
        elseif (okmcp_CanWrite($u))
            $mcp = "&#x2705; " . FN_Translate("enabled") . " &#x270F;&#xFE0F;";
        else
            $mcp = "&#x2705; " . FN_Translate("enabled") . " &#x1F441;&#xFE0F;";
        echo "<td>$mcp</td>";
        echo "<td><code>" . okmcp_MaskKey($u['apikey']) . "</code></td>";
        echo "<td><a class=\"btn btn-secondary btn-sm\" href=\"$link&amp;user=" . urlencode($u['username']) . ($q != "" ? "&amp;q=" . urlencode($q) : "") . "\">" . FN_Translate("manage") . "</a></td></tr>";
    }
    echo "</tbody></table>";
}

/**
 * HTML escape that accepts null values
 * @param mixed $s
 * @return string
 */
function okmcp_h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES);
}
