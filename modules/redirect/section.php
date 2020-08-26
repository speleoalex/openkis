<?php
/**
 * @package Flatnux_module_redirect
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2017
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$config = FN_LoadConfig();

if ($config['url'])
{
    header("location:{$config['url']}");
    die ("<script>window.location=\"".urlencode($config['url'])."\"</script>");
}


?>
