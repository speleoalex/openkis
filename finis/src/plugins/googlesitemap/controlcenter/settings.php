<?php
/**
 * @package Finis_googlesitemap
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
##<fnmodule>googlesitemap</fnmodule>
defined('_FNEXEC') or die('Restricted access');
require_once ("plugins/googlesitemap/functions.php");
/**
 * 10_Google_sitemap.php created on 10/dic/2008
 *
 * 
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 */
global $_FN;
$op=FN_GetParam("opt",$_GET,"flat");
$opmod=FN_GetParam("opmod",$_GET,"flat");

if (!file_exists("sitemap.xml") && !is_writable("."))
{
	echo "<b>" . FN_Translate("permissions error") . "</b><br /><br />" . realpath(".") . "<br />" . fn_i18n("is read only");
}
else
{
	if (file_exists("sitemap.xml") && !is_writable("sitemap.xml"))
	{
		echo "sitemap.xml : " . fn_i18n("is read only") . "<br />";
	}
	else
	{
		if ($opmod == "update")
		{
			FNGOOGLESITEMAP_CreateGoogleSitemap("sitemap.xml");
			FN_Alert(FN_Translate("operation complete"));
		}
		echo "<a href=\"?opt=$op&amp;opmod=update\">Update sitemap.xml</a><br />";
	}
}
$imghelp="<img style=\"vertical-align:middle\" alt=\"\" src=\"" . FN_FromTheme("controlcenter/images/help.png") . "\"/>";
echo "<div style=\"text-align:right\">$imghelp&nbsp;<a onclick=\"window.open(this.href);return false;\" href=\"https://www.google.com/webmasters/tools/docs/{$_FN['lang']}/about.html\">" . FN_Translate("help") . "</a></div>";
if (file_exists("sitemap.xml"))
{
	echo "<pre style=\"border:1px inset;height:300px;overflow:auto;\">" . htmlspecialchars(file_get_contents("sitemap.xml")) . "</pre>";
}
echo "<br /><b>URL sitemep:</b>" . $_FN['siteurl'] . "sitemap.xml<br/>";
?>