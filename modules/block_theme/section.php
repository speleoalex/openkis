<?php
/**
 * @package Flatnux_blocks
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$list_themes=FN_ListDir("themes/");
natsort($list_themes);
if ($_FN['block']=="")
{
	echo FN_HtmlContent("sections/{$_FN['mod']}");
}
$thid = uniqid("ffth");
echo "
<form method=\"get\" action=\"\" id=\"$thid\">
<div style=\"text-align: center\"><select id=\"theme\"
	onchange=\"window.location=document.getElementById('theme').options[document.getElementById('theme').selectedIndex].value\">";
foreach ($list_themes as $theme)
{
	echo "\n<option ";
	echo "value=\"" . FN_RewriteLink("index.php?mod={$_FN['mod']}&theme=$theme") . "\" ";
	if ($_FN['theme'] == $theme)
	{
		echo ' selected="selected" ';
	}
	echo ">$theme</option>";
}
echo "</select>
</div>
</form>";
