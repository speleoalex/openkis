<?php
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$folder="{$_FN['src_application']}/sections/{$_FN['mod']}";
if (file_exists("$folder/section.php"))
{
    include("$folder/section.php");
}

