<?php
/**
 * @package Flatnux
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
ob_start();
global $_FN;
require_once "include/flatnux.php";
if ($_FN['enable_mod_rewrite']>0)
{
    header("Cache-Control: no-cache");
    header("Pragma: no-cache");
}

//accesskey  ----->
FN_GetSections("",true);
//accesskey  -----<
//FN_Debug_timer(__FILE__.":".__LINE__);
//--------------------------  auto scripts  ----------------------------------->
include ("include/autoexec.php");
//--------------------------  auto scripts  -----------------------------------<
if (file_exists("themes/{$_FN['theme']}/structure.php"))
{
    include "./themes/{$_FN['theme']}/structure.php";
    $str=ob_get_contents();
}
elseif (file_exists("themes/{$_FN['theme']}/template.{$_FN['mod']}.tp.html"))
{
    $str=FN_TPL_html_MakeThemeFromTemplate("themes/{$_FN['theme']}/template.{$_FN['mod']}.tp.html");
}
elseif (!empty($_FN['sectionvalues']['type'])&&file_exists("themes/{$_FN['theme']}/template.type.{$_FN['sectionvalues']['type']}.tp.html"))
{
    $str=FN_TPL_html_MakeThemeFromTemplate("themes/{$_FN['theme']}/template.type.{$_FN['sectionvalues']['type']}.tp.html");
}
elseif (file_exists("themes/{$_FN['theme']}/template.tp.html"))
{
    $str=FN_TPL_html_MakeThemeFromTemplate("themes/{$_FN['theme']}/template.tp.html");
}
if (file_exists("sections/{$_FN['mod']}/footer.php"))
{
    ob_start();
    include ("sections/{$_FN['mod']}/footer.php");
    $strfooter=ob_get_clean();
    $str=str_replace("</body>",$strfooter."</body>",$str);
}
$str.="<!-- Page generated in ".FN_GetExecuteTimer()." seconds. -->";
//FN_Debug_timer(__FILE__.":".__LINE__);
//die("");
if (function_exists("FN_BeforePrint"))
{
    $str=FN_BeforePrint($str);
}
if (@ob_end_clean())
{
    header("Content-Type: text/html; charset={$_FN['charset_page']}");
    if ($_FN['enable_compress_gzip'])
    {
        header("Content-Encoding: gzip");
        print gzencode($str);
    }
    else
    {
        print ($str);
    }
}
else
    print ($str);
?>