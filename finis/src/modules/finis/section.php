<?php
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$folder = "{$_FN['src_application']}/sections/{$_FN['mod']}";
$html = "";
$SECTION = $_FN;
if (file_exists("$folder/section.php")) {
    include("$folder/section.php");
}
if (is_array($SECTION) && !empty($SECTION)) {
    if (file_exists("$folder/section.{$_FN['lang']}.html")) {
        $html = FN_NormalizeAllPaths(FN_TPL_ApplyTplFile("$folder/section.{$_FN['lang']}.html", $SECTION));
    } else {
        if (file_exists("$folder/section.html")) {
            $html = FN_NormalizeAllPaths(FN_TPL_ApplyTplFile("$folder/section.html", $SECTION));
        }
    }
    echo $html;
}
