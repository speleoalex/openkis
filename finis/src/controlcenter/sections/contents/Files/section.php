<?php

/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
global $_FN;
defined('_FNEXEC') or die('Restricted access');
$params = array();
$params['enablenew'] = false;
echo "<iframe src=\"{$_FN['siteurl']}/index.php?fnapp=filemanager\" style=\"border:0px;height:500px;;width:600px\"></iframe>";
    