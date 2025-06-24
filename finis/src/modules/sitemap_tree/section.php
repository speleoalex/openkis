<?php
/**
 * @package Flatnux_module_sitemap_tree
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$config=FN_LoadConfig();
echo FN_HtmlContent("sections/{$_FN['mod']}");
echo FN_HtmlMenuTree($config['parent']);
$_FN['return']['sections']=$sections=FN_GetSections($config['parent'],true);
