<?php
/**
 * @package Flatnux_module_html
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * 
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$folder = "sections/{$_FN['mod']}";
global $_FN;
$str = FN_HtmlContent($folder,true);
echo $str;
?>
