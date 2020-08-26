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
if ( $_FN['enable_mod_rewrite'] > 0 )
{
	header("Cache-Control: no-cache");
	header("Pragma: no-cache");
}
header("Content-Type: text/html; charset={$_FN['charset_page']}");

//accesskey  ----->
$sections = FN_GetSections("sections", true);
//accesskey  -----<
//scripts  ----->
if ( file_exists("include/autoexec.d/") && false != ($handle = opendir('include/autoexec.d/')) )
{
	$filestorun = array();
	while ( false !== ($file = readdir($handle)) )
		if ( FN_GetFileExtension($file) == "php" && !preg_match("/^none_/si", $file) )
			$filestorun[] = $file;
	closedir($handle);
	FN_NatSort($filestorun);
	foreach ( $filestorun as $runfile )
	{
		include ("include/autoexec.d/$runfile");
	}
}
//scripts  -----<
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
echo FN_HtmlSection();
$str = ob_get_contents();
$str .= "<!-- Page generated in " . FN_GetExecuteTimer() . " seconds. -->";
ob_end_clean();
$str .="\n</body>\n</html>";
if ( $_FN['enable_compress_gzip'] )
{
	header("Content-Encoding: gzip");
	print gzencode($str);
}
else
{
	print ($str);
}
?>