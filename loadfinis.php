<?php
// Sites that keep OpenKIS in a subfolder (index.php and the openkis_*.php wrappers in the
// site root, this repository in openkis/) have their own loadfinis.php in the root: the
// scripts of this folder must use it, whatever the include_path of the server is.
$openkis_site = isset($_SERVER['SCRIPT_FILENAME']) ? dirname(realpath($_SERVER['SCRIPT_FILENAME'])) : "";
if ($openkis_site != "" && $openkis_site != __DIR__ && file_exists("$openkis_site/loadfinis.php"))
{
    require_once "$openkis_site/loadfinis.php";
    return;
}
header('X-Accel-Buffering: no');
ini_set("display_errors","on");
error_reporting(E_ALL);
global $_FN;
// Map defaults (centro Italia)

require_once __DIR__ . "/finis/src/include/finis.php";
require_once __DIR__ . "/finis/src/FINIS.php";
