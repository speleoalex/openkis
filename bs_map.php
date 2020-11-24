<?php
global $_FN;
require_once "include/flatnux.php";
require_once "extra/openkis.inc.php";

$str=file_get_contents(__DIR__."/bs_map.tp.html");
if (file_exists(__DIR__."/bs_map/conf/layers_{$_FN['openkis_custom']}.js"))
{
    $str=str_replace("bs_map/conf/layers_default.js","bs_map/conf/layers_{$_FN['openkis_custom']}.js",$str);
    file_put_contents("bs_map.htm",$str);
    echo "bs_map.htm updated ";
}
die(" to ".$_FN['openkis_custom']);
?>