<?php
/**
 * @package Flatnux_blocks
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined( '_FNEXEC' ) or die( 'Restricted access' );
global $_FN;
$langs = array();
foreach ($_FN['listlanguages'] as $lang)
{
	$link = FN_RewriteLink("index.php?lang=$lang&amp;mod={$_FN['mod']}");
	$icon = FN_FromTheme("images/flags/$lang.png");
	$langs[] = "<a href=\"$link\"><img alt=\"$lang\" style=\"border:0px;\" src=\"$icon\"/></a>";
}
if (count($langs)>1)
	echo implode ("&nbsp;",$langs);

?>
