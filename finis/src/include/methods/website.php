<?php
$str = "";
FN_InitSections();
ob_start();
global $_FN;
if ($_FN['enable_mod_rewrite'] > 0)
{
    header("Cache-Control: no-cache");
    header("Pragma: no-cache");
}
//accesskey  ----->
FN_GetSections("", true);
//accesskey  -----<
//--------------------------  auto scripts  ----------------------------------->
include ("{$_FN['src_finis']}/include/autoexec.php");
//--------------------------  auto scripts  -----------------------------------<
if (file_exists("{$_FN['src_application']}/themes/{$_FN['theme']}/structure.php"))
{
    
    include "{$_FN['src_application']}/themes/{$_FN['theme']}/structure.php";
    $str = ob_get_clean();
}

elseif (file_exists("{$_FN['src_application']}/themes/{$_FN['theme']}/template.{$_FN['mod']}.tp.html"))
{
    $str = FN_TPL_html_MakeThemeFromTemplate("{$_FN['src_application']}/themes/{$_FN['theme']}/template.{$_FN['mod']}.tp.html");
}
elseif (!empty($_FN['sectionvalues']['type']) && file_exists("{$_FN['src_application']}/themes/{$_FN['theme']}/template.type.{$_FN['sectionvalues']['type']}.tp.html"))
{
    $str = FN_TPL_html_MakeThemeFromTemplate("{$_FN['src_application']}/themes/{$_FN['theme']}/template.type.{$_FN['sectionvalues']['type']}.tp.html");
}
elseif (file_exists(FN_FinisPathToApplicationPath("{$_FN['src_application']}/themes/{$_FN['theme']}/template.tp.html")))
{
    $str = FN_TPL_html_MakeThemeFromTemplate("{$_FN['src_application']}/themes/{$_FN['theme']}/template.tp.html");

}



if (file_exists(FN_FinisPathToApplicationPath("{$_FN['src_application']}/sections/{$_FN['mod']}/footer.php")))
{
    ob_start();
    include ("{$_FN['src_application']}/sections/{$_FN['mod']}/footer.php");
    $strfooter = ob_get_clean();
    $str = str_replace("</body>", $strfooter . "</body>", $str);
}
$str .= "<!-- Page generated in " . FN_GetExecuteTimer() . " seconds. -->";
if ($tmp = @ob_get_clean())
{
    if ($_FN['display_errors'] !== "on")
    {
        $tmp = "";
    }
    header("Content-Type: text/html; charset={$_FN['charset_page']}");
    if ($_FN['enable_compress_gzip'])
    {
        header("Content-Encoding: gzip");
        print gzencode($tmp . $str);
    }
    else
    {
        print ($tmp . $str);
    }
}
else
{
    print ($str);
}