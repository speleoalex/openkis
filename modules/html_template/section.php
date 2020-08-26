<?php
/**
 * @package Flatnux_module_html_template
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * 
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$folder="sections/{$_FN['mod']}";
global $_FN;
$str="";
$V=$_FN;
if (file_exists("$folder/vars.php"))
{
    include("$folder/vars.php");
}

if (file_exists("$folder/section.{$_FN['lang']}.html"))
{
    $html=FN_TPL_ApplyTplFile("$folder/section.{$_FN['lang']}.html",$V);
}
echo $html;
?>
