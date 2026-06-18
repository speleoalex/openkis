<?php

header ("Access-Control-Allow-Origin: *");
ini_set("display_errors","on");
global $_FN;
$f = isset($_GET['f'])?$_GET['f']:"";

if (GetFileExtension($f)=="las")
{
    die (file_get_contents($f));
    
}
function GetFileExtension($filename)
{
    if (!strstr($filename, "."))
        return "";
    $tmp = explode(".", $filename);
    $extension = $tmp[count($tmp) - 1];
    return $extension;
}

?>