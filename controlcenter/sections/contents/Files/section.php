<?php
/**
 * @package Flatnux_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
global $_FN;
defined('_FNEXEC') or die('Restricted access');
$params = false;
$params['enablenew'] = false;
$_FN['section_header_footer']="<link rel='StyleSheet' type='text/css' href=\"modules/filemanager/style.css\" />";
echo "<iframe src=\"{$_FN['siteurl']}/filemanager.php\" style=\"border:0px;height:500px;;width:600px\"></iframe>";
?>