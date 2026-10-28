<?php

global $_FN;
require_once "loadfinis.php";

// bs_map.htm is bs_map.tp.html with the map layers of the region
// (bs_map/conf/layers_<openkis_custom>.js, else layers_default.js). It is
// written again whenever it differs from what the template and the current
// region give, so that a code update (new template, new script versions) or a
// change of region reaches it: the file times are not reliable after a copy
// or a checkout.
$layers = "bs_map/conf/layers_default.js";
$custom = isset($_FN['openkis_custom']) ? basename($_FN['openkis_custom']) : "";
if ($custom != "" && file_exists(__DIR__ . "/bs_map/conf/layers_{$custom}.js"))
{
    $layers = "bs_map/conf/layers_{$custom}.js";
}
$str = str_replace("bs_map/conf/layers_default.js", $layers, file_get_contents(__DIR__ . "/bs_map.tp.html"));
// written in the site folder (relative path): this script may be a symlink to the code
$htm = "bs_map.htm";
if (!file_exists($htm) || file_get_contents($htm) !== $str)
{
    file_put_contents($htm, $str);
    die("/*{$custom} updated*/");
}
else
{
    die("/*{$custom} already updated*/");
}
