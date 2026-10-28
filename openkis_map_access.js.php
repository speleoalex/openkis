<?php
ob_start();
global $_FN;
require_once "loadfinis.php";
// Loaded by the map page (bs_map.htm) before the layers. It tells which
// sections the current user may get from the map data endpoints, with the
// same rules as those endpoints (openkis_SectionMapAccess()), so that
// OPS_Map.addKmlLayer() skips a layer that would answer 403: the visitor
// gets a clean map, with no error in the browser console. No record data is
// sent, only the access level of each section.
$access = array();
// sections of the site (relative path: the script may be a symlink to the code)
$dirs = glob("sections/*", GLOB_ONLYDIR);
foreach (is_array($dirs) ? $dirs : array() as $dir)
{
    $mod = basename($dir);
    // only the record sections have map data; the map loads a layer of a
    // section that is not listed, as before
    $values = FN_GetSectionValues($mod);
    if (!empty($values['type']) && $values['type'] == "dbview")
        $access[$mod] = openkis_SectionMapAccess($mod);
}
while (false !== @ob_end_clean());
header("Cache-Control: no-store");
header('Content-type: application/javascript');
die("var OPENKIS_MAP_ACCESS = " . json_encode($access) . ";\n");
