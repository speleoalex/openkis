<?php
/**
 * Offline use (extra/openkis/openkis_offline.inc.php): settings and script in the head of
 * every page of the theme. Queued with FN_IncludeScript() from extra/openkis.inc.php,
 * so it runs after the login and after finis has set section_header_footer.
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
// Every answer of the site says whether it is the same for every anonymous visitor
// ("public": the worker may keep it on the device) or not ("private": a logged user, who
// may see restricted caves and their coordinates, or a POST). finis says it again for the
// pages (methods/website.php); this covers the map exports, the files, the thumbnails.
if (empty($_FN['consolemode']) && !headers_sent())
{
    header("X-Finis-Visitor: " . (($_FN['user'] == "" && ($_SERVER['REQUEST_METHOD'] ?? "GET") === "GET") ? "public" : "private"));
}
// only the pages of the site (index.php), not the exports and the other endpoints
if (function_exists("openkis_OfflineHead") && empty($_FN['consolemode']) && basename($_SERVER['SCRIPT_FILENAME'] ?? "") === "index.php")
{
    $_FN['section_header_footer'] = (isset($_FN['section_header_footer']) ? $_FN['section_header_footer'] : "") . openkis_OfflineHead();
}
