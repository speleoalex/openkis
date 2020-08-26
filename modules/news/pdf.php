<?php
/**
 * @package Flatnux_module_news
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
if (!defined("_PATH_NEWS_"))
{
	$path_news="modules/news/";
	define("_PATH_NEWS_",$path_news);
}
require_once (_PATH_NEWS_ . "functions.php");
require_once (_PATH_NEWS_ . "functions_theme.php");
global $_FN;
$op=FN_GetParam("op",$_GET,"html");
$config=FN_LoadConfig(_PATH_NEWS_ . "config.php",$_FN['mod']);
$CLASS_NEWS=new FNNEWS($config);
$html="";
$filename="";
if ($op != "")
{
	$item=$CLASS_NEWS->GetNewsContents(false,$op);
	if (isset($item['news_SUMMARY']))
	{
		$filename=$op . ".pdf";

		$html .= "<hr />{$item['news_USER']} - {$item['txt_DATE']}<hr />";
		$html .= $item['news_TITLE'] . "<br />";
		$html .= $item['news_SUMMARY'];
		$html .= "<br />\n";
		$html .= $item['news_BODY'];
		$html .= "<hr /><div style=\"text-align:right\">{$_FN['site_title']}<br /><em>{$_FN['siteurl']}</em></div>";
	}
	else
	{
		$html .= "news not found";
	}
}
FN_HtmlToPdf("<div style=\"margin:10px;\">".$html."</div>",$filename);
?>