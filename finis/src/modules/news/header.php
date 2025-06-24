<?php
/**
 * @package Flatnux_module_news
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$config = FN_LoadConfig();
if (file_exists("".$_FN['datadir']."/rss/{$config['tablename']}/{$_FN['lang']}/backend.xml"))
{
	$_FN['section_header_footer'] .= "\n\t<link rel=\"alternate\" type=\"application/rss+xml\" title=\"{$_FN['sitename']}\" href=\"{$_FN['siteurl']}"."".$_FN['datadir']."/rss/{$config['tablename']}/{$_FN['lang']}/backend.xml\" />\n";
	$_FN['rss_link'] = $_FN['siteurl'].$_FN['datadir']."/rss/{$config['tablename']}/{$_FN['lang']}/backend.xml";
}

$op = FN_GetParam("op",$_GET,"html");
if ($op != "")
{
	if (!defined("_PATH_NEWS_"))
	{
		$path_news = "{$_FN['src_finis']}/modules/news/";
		define("_PATH_NEWS_",$path_news);
	}
	require_once (_PATH_NEWS_."functions.php");
	require_once (_PATH_NEWS_."functions_theme.php");

	$CLASS_NEWS = new FNNEWS($config);
	$news_contents = $CLASS_NEWS->GetNewsContents(false,$op);
	if (isset($news_contents['unirecid']))
	{
		if (isset($news_contents['status']) && $news_contents['status'] == 1)
		{
			$CLASS_NEWS->UpdateNewsStat($news_contents);
			$_FN['section_header_footer'].="\n\t<meta property=\"og:title\" content=\"".$news_contents['title']."\" />";
			//$_FN['site_title'] = $news_contents['title']." - ".$_FN['site_title'];

			$_FN['section_header_footer'].="\n\t<meta property=\"og:url\" content=\"{$news_contents['link_READ']}\" />";
			$desc = strip_tags($news_contents['news_SUMMARY']);
			$_FN['section_header_footer'].="\n\t<meta property=\"og:description\" content=\"{$desc}\" />";
			$_FN['section_header_footer'].="\n\t<meta property=\"og:type\" content=\"article\" />";
			
			if ($news_contents['img_news'])
				$_FN['section_header_footer'].="\n\t<meta property=\"og:image\" content=\"{$news_contents['img_news_thumb']}\" />";
			elseif ($news_contents['img_argument'])
				$_FN['section_header_footer'].="\n\t<meta property=\"og:image\" content=\"{$news_contents['img_argument_thumb']}\" />";

			if ($config['enable_socialnetworks'])
			{
				$_FN['section_header_footer'].="\n\t<meta property=\"fb:app_id\" content=\"{$config['fb_appid']}\" />\n";
			}
		}
	}
}
?>
