<?php

/**
 * @package Flatnux
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
ob_start();
global $_FN;
//--------------------------  auto scripts  ----------------------------------->
include ("{$_FN['src_finis']}/include/autoexec.php");
//--------------------------  auto scripts  -----------------------------------<
if (FN_UserCanViewSection($_FN['mod']))
{
    if ($_FN['enable_mod_rewrite'] > 0)
    {
        header("Cache-Control: no-cache");
        header("Pragma: no-cache");
    }

    header("Content-Type: text/html; charset={$_FN['charset_page']}");
    echo "<html>
    <header>
    <style type=\"text/css\">
    *{
        color:#000000;
     }
        body,p,td,div{
        font-size: 10pt;
        line-height: 14pt;
        font:\"serif\";
        text-align: justify
    }
        h1{
        font-size: 20pt;
        line-height: 20pt;
    }
        h2{
        font-size: 18pt;
        line-height: 19pt;
    }
        h3{
        font-size: 16pt;
        line-height: 16pt;
    }
        h4{
        font-size: 14pt;
        line-height: 14pt;
    }
    a{
        text-decoration:none
    }
    </style>
        <title>{$_FN['site_title']}</title>
    </header>
<body>";
    echo FN_RunSection($_FN['mod'], true);

    $str = ob_get_contents();
    $str .= "<!-- Page generated in " . FN_GetExecuteTimer() . " seconds. -->";
    ob_end_clean();
    $str .= "\n</body>\n</html>";
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