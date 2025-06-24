<?php
/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-1015
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
ob_start();
phpinfo();
preg_match('%<style type="text/css">(.*?)</style>.*?(<body>.*</body>)%s',ob_get_clean(),$matches);

# $matches [1]; # Style information
# $matches [2]; # Body information
$contents = $matches[2];
$contents = str_replace("<body>","<div>",$contents);
$contents = str_replace("</body>","</div>",$contents);
echo $contents;
?>

