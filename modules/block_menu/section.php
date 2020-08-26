<?php
/**
 * @package Flatnux_blocks
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$config = FN_LoadConfig();
$config['recursive'] = isset($config['recursive'])?$config['recursive']:"";
$config['parent'] = isset($config['parent'])?$config['parent']:"";

//dprint_r($config);
if ($config['parent'] == "__submenu__")
{
    $config['parent'] = $_FN['mod'];
}
if (empty($config['method']))
    echo FN_HtmlMenuTree($config['parent'], $config['recursive']);
else
    echo FN_HtmlMenuTreeUl($config['parent'], $config['recursive']);
?>