<?php
/**
 * @package Finis
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
global $_FN;
$title = "";
FN_GetAccessKey($title,"index.php?mod=sitemap","m");
if ($_FN['showaccesskey'])
	FN_GetAccessKey($title,"showaccesskey=0","a");
else
	FN_GetAccessKey($title,"showaccesskey=1","a");

?>