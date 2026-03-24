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
//accesskey  ----->
FN_GetSections("",true);
//accesskey  -----<
//--------------------------  auto scripts  ----------------------------------->
include ("include/autoexec.php");
//--------------------------  auto scripts  -----------------------------------<
echo "<?xml version=\"1.0\"?>
<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"{$_FN['lang']}\" xml:lang=\"{$_FN['lang']}\">
<head>
";
echo FN_HtmlHeader();
echo "</head>";
echo "<body>";
echo FN_HtmlSection();
echo "</body>";
$str=ob_get_contents();
$str .= "<!-- Page generated in " . FN_GetExecuteTimer() . " seconds. -->";
ob_end_clean();
if ($_FN['enable_compress_gzip'])
{
	header("Content-Encoding: gzip");
	print gzencode($str);
}
else
{
	print ($str);
}

?>