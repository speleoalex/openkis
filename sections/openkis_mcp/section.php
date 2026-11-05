<?php

/**
 * Section: MCP servers
 *
 * Shows the endpoints of the two MCP servers of the site (openkis_mcp.php for the records,
 * index.php?fnapp=mcp of finis for the pages) and lets the logged user generate or revoke the
 * personal API key (fn_users.apikey hashed by finis, with a copy encrypted in fn_users.apikey_enc:
 * the owner shows the own key again with the client parameters, button "show", every key shown is
 * logged; the administrators show any key in the control center). A client
 * that uses the key acts with the permissions of the key owner; the page lists them section
 * by section. The write tools are allowed only to the administrators and to the users an
 * administrator allowed to write (group $_FN['mcp_write_group'], default "mcp_write"), from
 * Control center > Users and groups > MCP server, which also manages the keys of every user.
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
$okmcp_newkey = "";
$okmcp_shown = false;
if (!FN_UserCanUseApiKey($_FN['user']))
{
    echo "<p>" . FN_Translate("your account is not enabled to use the MCP server, ask an administrator") . "</p>";
    return;
}
if ($okmcp_isadmin && FN_McpWriteGroup() != "")
    FN_CreateGroupIfNotExists(FN_McpWriteGroup());

// the administrators can look at the permissions of another user (keys: control center)
$okmcp_user = $okmcp_isadmin ? FN_GetParam("user", $_GET, "html") : "";
if ($okmcp_user != "" && !FN_GetUser($okmcp_user, false))
{
    $okmcp_error = FN_Translate("user not found") . ": " . okmcp_h($okmcp_user);
    $okmcp_user = "";
}
if ($okmcp_user == "")
    $okmcp_user = $_FN['user'];

//--------------------------- actions (POST only, own key) ------------------->
$okmcp_action = FN_GetParam("okmcp_action", $_POST, "html");
if ($okmcp_action != "")
{
    if (!hash_equals(FN_ApiKeyFormToken(), (string) FN_GetParam("okmcp_token", $_POST, "html")))
        $okmcp_error = FN_Translate("invalid request, reload the page and try again");
    elseif ($okmcp_action == "generate")
    {
        //apikey_enc must be in the table definition before the key is generated, or it could not be shown again
        if (function_exists("FN_EnsureUserApiKeyField"))
            FN_EnsureUserApiKeyField();
        $okmcp_newkey = FN_GenerateApiKey($_FN['user']);
        if ($okmcp_newkey != "")
            $okmcp_message = FN_Translate("new key generated") . ". " . FN_Translate("The previous key, if any, no longer works.");
        else
            $okmcp_error = FN_Translate("error");
    }
    elseif ($okmcp_action == "show")
    {
        $okmcp_newkey = FN_ApiKeyReveal($_FN['user']);
        $okmcp_shown = $okmcp_newkey != "";
        if ($okmcp_shown)
            FN_LogEvent("API key of user {$_FN['user']} shown to its owner (MCP server page)", "apikey");
        elseif (FN_ApiKeyInfo($_FN['user'])['exists'])
            $okmcp_error = FN_Translate("this key cannot be shown: it was created when only its hash was stored. Generate a new key to see it");
        else
            $okmcp_error = FN_Translate("no API key");
    }
    elseif ($okmcp_action == "revoke")
    {
        if (FN_RevokeApiKey($_FN['user']))
            $okmcp_message = FN_Translate("the MCP key has been revoked");
        else
            $okmcp_error = FN_Translate("error");
    }
}
//--------------------------- actions (POST only, own key) -------------------<

$okmcp_url = okmcp_ServerUrl();
echo "<div class=\"okmcp\">";
if ($okmcp_message != "")
    echo "<div class=\"alert alert-success\">" . okmcp_h($okmcp_message) . "</div>";
if ($okmcp_error != "")
    echo "<div class=\"alert alert-danger\">$okmcp_error</div>";

//--------------------------- servers ---------------------------------------->
echo "<h3>" . FN_Translate("MCP servers") . "</h3>";
echo "<p>" . FN_Translate("The MCP servers let AI assistants (Claude, ChatGPT, ...) read and, when allowed, edit the cadastre and the pages of the site. Every user connects with the own username and password or with a personal key: the assistant works with the same permissions the user has on the website.") . "</p>";
echo "<div class=\"mb-2\"><label class=\"form-label mb-0\"><b>" . FN_Translate("cadastre (records): OpenKIS MCP server") . "</b></label>";
echo "<input type=\"text\" class=\"form-control\" readonly=\"readonly\" onclick=\"this.select()\" value=\"" . okmcp_h($okmcp_url) . "\" /></div>";
echo "<div class=\"mb-3\"><label class=\"form-label mb-0\"><b>" . FN_Translate("pages, menus and settings: CMS MCP server") . "</b></label>";
echo "<input type=\"text\" class=\"form-control\" readonly=\"readonly\" onclick=\"this.select()\" value=\"" . okmcp_h(FN_GetMcpUrl()) . "\" /></div>";
echo "<ul>";
echo "<li>" . FN_Translate("transport") . ": <code>Streamable HTTP</code></li>";
echo "<li>" . FN_Translate("authentication") . ": " . FN_Translate("header", "") . " <code>X-API-Key: &lt;" . FN_Translate("key", "") . "&gt;</code> " . FN_Translate("or", "") . " <code>Authorization: Bearer &lt;" . FN_Translate("key", "") . "&gt;</code>, <code>?apikey=&lt;" . FN_Translate("key", "") . "&gt;</code> " . FN_Translate("in the address (clients without custom headers)", "") . ", " . FN_Translate("or OAuth (login with username and password)", "") . "</li>";
echo "<li>" . FN_Translate("the same key works with both servers") . "</li>";
if (isset($_FN['openkis_mcp_userkeys']) && !$_FN['openkis_mcp_userkeys'])
    echo "<li><span class=\"text-danger\">" . FN_Translate("the personal keys are disabled in the configuration") . " (<code>\$_FN['openkis_mcp_userkeys'] = 0</code>)</span></li>";
if (!empty($_FN['openkis_mcp_readonly']))
    echo "<li><span class=\"text-warning\">" . FN_Translate("the server is read only for everybody") . " (<code>\$_FN['openkis_mcp_readonly']</code>)</span></li>";
if ($okmcp_isadmin)
{
    $legacy = isset($_FN['openkis_mcp_apikey']) && count(array_filter((array) $_FN['openkis_mcp_apikey'])) > 0;
    echo "<li>" . FN_Translate("global key with full access") . " (<code>\$_FN['openkis_mcp_apikey']</code>): " . ($legacy ? FN_Translate("configured") : FN_Translate("not configured")) . "</li>";
    echo "<li>" . FN_Translate("keys of all the users and write permissions") . ": <a href=\"index.php?fnapp=controlcenter&amp;opt=users_and_groups/mcp\">" . FN_Translate("Control center > Users and groups > MCP server") . "</a></li>";
}
echo "</ul>";
//--------------------------- servers ----------------------------------------<

okmcp_HtmlOwnKey($okmcp_link, $okmcp_newkey, $okmcp_shown);
okmcp_HtmlPermissions($okmcp_user, $okmcp_link, $okmcp_isadmin);
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
 * @param string|array $user username or user values
 * @return bool true if the user can use the write tools (administrators always)
 */
function okmcp_CanWrite($user)
{
    return FN_UserCanWriteByApiKey($user);
}

/**
 * The key of the logged user: state, show, generate and revoke buttons, the key
 * (just generated or shown on request) with the client configuration
 * @param string $link
 * @param string $newkey key just generated or shown, "" otherwise
 * @param bool $shown the key was shown with the "show" button (not generated)
 */
function okmcp_HtmlOwnKey($link, $newkey, $shown = false)
{
    global $_FN;
    $user = $_FN['user'];
    $info = FN_ApiKeyInfo($user);
    $token = FN_ApiKeyFormToken();
    echo "<hr /><h3>" . FN_Translate("your MCP key") . "</h3>";
    echo "<p><b>" . FN_Translate("Claude Desktop, claude.ai and the clients with OAuth") . "</b>: " . FN_Translate("add a custom connector with the server URL and log in with your username and password, no key needed. Generating a new key, revoking it or changing the password also disconnects these clients.") . "</p>";
    echo "<p><b>" . FN_Translate("personal key") . "</b>: ";
    if (!$info['exists'])
        echo FN_Translate("no API key");
    elseif ($info['created'] != "")
        echo FN_Translate("API key created on") . " " . okmcp_h($info['created']);
    else
        echo FN_Translate("API key present");
    echo ".";
    if ($info['exists'] && !$info['viewable'])
        echo " " . FN_Translate("this key cannot be shown: it was created when only its hash was stored. Generate a new key to see it") . ".";
    echo "</p>";
    if ($newkey != "")
        okmcp_HtmlKeyParameters($newkey, $shown);
    echo "<form method=\"post\" action=\"$link\" class=\"mb-3\">";
    echo "<input type=\"hidden\" name=\"okmcp_token\" value=\"$token\" />";
    if ($info['exists'])
    {
        if ($info['viewable'] && $newkey == "")
            echo "<button class=\"btn btn-primary btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"show\">&#x1F511; " . FN_Translate("show the key and the parameters") . "</button> ";
        elseif ($newkey != "")
            echo "<a class=\"btn btn-outline-secondary btn-sm\" href=\"$link\">" . FN_Translate("hide the key") . "</a> ";
        echo "<button class=\"btn btn-secondary btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"generate\" onclick=\"return confirm('" . addslashes(FN_Translate("generate a new key? The clients that use the current key will stop working")) . "')\">" . FN_Translate("generate a new key") . "</button> ";
        echo "<button class=\"btn btn-danger btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"revoke\" onclick=\"return confirm('" . addslashes(FN_Translate("revoke the key? The clients that use it will stop working")) . "')\">" . FN_Translate("revoke the key") . "</button>";
    }
    else
    {
        echo "<button class=\"btn btn-primary btn-sm\" type=\"submit\" name=\"okmcp_action\" value=\"generate\">" . FN_Translate("generate the key") . "</button>";
    }
    echo "</form>";
}

/**
 * The key of the logged user with the parameters of the clients: server URLs,
 * Claude Code commands, JSON configuration, address with the key for the clients
 * without custom headers
 * @param string $apikey
 * @param bool $shown the key was shown on request (not generated now)
 */
function okmcp_HtmlKeyParameters($apikey, $shown)
{
    $url = okmcp_ServerUrl();
    $cmsurl = FN_GetMcpUrl();
    $cmd = "claude mcp add --transport http openkis $url --header \"X-API-Key: $apikey\"\n"
        . "claude mcp add --transport http openkis_cms \"$cmsurl\" --header \"X-API-Key: $apikey\"";
    $json = "{\n  \"mcpServers\": {\n    \"openkis\": {\n      \"type\": \"http\",\n      \"url\": \"$url\",\n      \"headers\": { \"X-API-Key\": \"$apikey\" }\n    },\n    \"openkis_cms\": {\n      \"type\": \"http\",\n      \"url\": \"$cmsurl\",\n      \"headers\": { \"X-API-Key\": \"$apikey\" }\n    }\n  }\n}";
    $sep = strpos($url, "?") === false ? "?" : "&";
    $urlkey = "$url{$sep}apikey=" . urlencode($apikey);
    $cmsurlkey = $cmsurl . (strpos($cmsurl, "?") === false ? "?" : "&") . "apikey=" . urlencode($apikey);
    echo "<div class=\"card mb-3 border-success\"><div class=\"card-body\">";
    echo "<label class=\"form-label mb-0\"><b>" . FN_Translate("your MCP key") . "</b></label>";
    echo "<div class=\"input-group mb-1\"><input type=\"text\" class=\"form-control\" readonly=\"readonly\" onclick=\"this.select()\" value=\"" . okmcp_h($apikey) . "\" />";
    echo "<button class=\"btn btn-outline-secondary\" type=\"button\" onclick=\"var i=this.previousElementSibling;i.select();if(navigator.clipboard){navigator.clipboard.writeText(i.value);}else{document.execCommand('copy');}\">" . FN_Translate("copy") . "</button></div>";
    if (!$shown)
        echo "<div class=\"form-text mb-2\">" . FN_Translate("copy the key now; you can show it again later with the button of this page") . "</div>";
    echo "<p class=\"mt-3 mb-1\"><b>" . FN_Translate("parameters") . "</b></p>";
    echo "<table class=\"table table-sm mb-2\"><tbody>";
    echo "<tr><th style=\"width:14em\">" . FN_Translate("cadastre (records): OpenKIS MCP server") . "</th><td><code>" . okmcp_h($url) . "</code></td></tr>";
    echo "<tr><th>" . FN_Translate("pages, menus and settings: CMS MCP server") . "</th><td><code>" . okmcp_h($cmsurl) . "</code></td></tr>";
    echo "<tr><th>" . FN_Translate("transport") . "</th><td><code>Streamable HTTP</code> (<code>type: http</code>)</td></tr>";
    echo "<tr><th>" . FN_Translate("header") . "</th><td><code>X-API-Key: " . okmcp_h($apikey) . "</code><br /><small class=\"text-muted\">" . FN_Translate("or", "") . "</small> <code>Authorization: Bearer " . okmcp_h($apikey) . "</code></td></tr>";
    echo "<tr><th>" . FN_Translate("in the address (clients without custom headers)") . "</th><td><code style=\"word-break:break-all\">" . okmcp_h($urlkey) . "</code><br /><code style=\"word-break:break-all\">" . okmcp_h($cmsurlkey) . "</code></td></tr>";
    echo "</tbody></table>";
    echo "<p class=\"mb-1\"><b>" . FN_Translate("Claude Code") . "</b></p>";
    echo "<pre style=\"white-space:pre-wrap;word-break:break-all\">" . okmcp_h($cmd) . "</pre>";
    echo "<p class=\"mb-1\"><b>" . FN_Translate("other MCP clients (JSON configuration)") . "</b></p>";
    echo "<pre>" . okmcp_h($json) . "</pre>";
    echo "<p class=\"text-muted mb-0\">" . FN_Translate("the key is personal: whoever has it can act on the cadastre as this user") . "</p>";
    echo "</div></div>";
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
            if (!openkis_IsRecordSection($section))
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
 * Permissions of the key of a user on the cadastre, section by section
 * @param string $user
 * @param string $link
 * @param bool $isadmin the logged user is an administrator (can choose the user)
 */
function okmcp_HtmlPermissions($user, $link, $isadmin)
{
    global $_FN;
    $uservalues = FN_GetUser($user, false);
    echo "<hr /><h3>" . FN_Translate("permissions of the key") . ($user != $_FN['user'] ? ": " . okmcp_h($user) : "") . "</h3>";
    if ($isadmin)
    {
        echo "<form method=\"get\" action=\"index.php\" class=\"mb-3\"><input type=\"hidden\" name=\"mod\" value=\"" . okmcp_h($_FN['mod']) . "\" />";
        echo "<div class=\"input-group\" style=\"max-width:30em\"><input type=\"text\" class=\"form-control\" name=\"user\" value=\"" . okmcp_h($user) . "\" placeholder=\"" . FN_Translate("username") . "\" />";
        echo "<button class=\"btn btn-secondary\" type=\"submit\">" . FN_Translate("show the permissions of a user") . "</button></div></form>";
    }
    echo "<p>" . FN_Translate("level") . ": " . okmcp_h($uservalues['level']) . " &nbsp; " . FN_Translate("groups") . ": " . okmcp_h(str_replace(",", ", ", $uservalues['group']));
    if (empty($uservalues['active']) || $uservalues['active'] != 1)
        echo " &nbsp; <span class=\"text-danger\">" . FN_Translate("user not active: the key does not work") . "</span>";
    echo "</p>";
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
    if (FN_IsAdmin($user))
    {
        echo "<p>{$icons['admin']} " . FN_Translate("administrator: full access, including the sql tool") . "</p>";
        return;
    }
    $canwrite = okmcp_CanWrite($user);
    echo "<p>" . ($canwrite ? "{$icons['write']} " . FN_Translate("write tools allowed") : "{$icons['read']} " . FN_Translate("read only: the write tools are not allowed")) . " <small class=\"text-muted\">(" . FN_Translate("an administrator allows them in the control center") . ")</small></p>";
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

/**
 * HTML escape that accepts null values
 * @param mixed $s
 * @return string
 */
function okmcp_h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES);
}
