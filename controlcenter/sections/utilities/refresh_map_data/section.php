<?php
/**
 * Control center -> Utilities -> Refresh map data
 *
 * Drops the cached map exports (KML, GPX, JSON, markers) and recalculates the
 * coordinates. Needed after the caves data were changed directly in the
 * database: edits made through OpenKIS refresh the maps by themselves.
 * Same as "php refresh-cache.php" from the command line.
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
if (!UserCanAdmin("utilities/refresh_map_data"))
{
    die(FN_Translate("you may not do that"));
}
$secret = FN_GetUserSessionValue('fn_csrf_secret');
if (empty($secret))
{
    $secret = bin2hex(random_bytes(16));
    FN_SetUserSessionValue('fn_csrf_secret', $secret);
}
$token = hash_hmac('sha256', "refresh_map_data", $secret);
$isPost = isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
if ($isPost && isset($_POST['fn_csrf']) && hash_equals($token, (string) $_POST['fn_csrf']))
{
    @set_time_limit(0);
    $done = openkis_RefreshMapData();
    echo "<div class=\"alert alert-success\"><p><strong>" . FN_Translate("map data refreshed") . "</strong></p><ul>";
    foreach ($done as $line)
    {
        echo "<li>" . htmlspecialchars($line) . "</li>";
    }
    echo "</ul></div>";
}
echo "<p>" . FN_Translate("The maps are refreshed automatically when the data are changed through OpenKIS. After changing the data directly in the database, use this button to refresh them now.") . "</p>";
echo "<form method=\"post\" action=\"?fnapp=controlcenter&amp;opt=utilities/refresh_map_data\">";
echo "<input type=\"hidden\" name=\"fn_csrf\" value=\"" . htmlspecialchars($token) . "\" />";
echo "<button class=\"btn btn-primary\" type=\"submit\">" . FN_Translate("refresh map data") . "</button>";
echo "</form>";
