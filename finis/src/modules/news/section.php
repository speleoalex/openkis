<?php

/**
 * @package Flatnux_module_news
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
//TODO: googlemap rss jet_lag
if (!defined("_PATH_NEWS_"))
{
    $path_news = "{$_FN['src_finis']}/modules/news/";
    define("_PATH_NEWS_", $path_news);
}

require_once (_PATH_NEWS_ . "functions.php");
require_once (_PATH_NEWS_ . "functions_theme.php");


global $_FN;
$op = FN_GetParam("op", $_GET, "html");
$mode = FN_GetParam("mode", $_GET, "html");

$opmod = FN_GetParam("opmod", $_GET, "html");
if ($op == "" && $opmod != "")
    $op = $opmod;
$config = FN_LoadConfig(_PATH_NEWS_ . "config.php", $_FN['mod']);
//dprint_r($config);
//dprint_r(_PATH_NEWS_."config.php");
$enablecomments = $config['enablecomments'];
$guestnews = $config['guestnews'];
$signews = $config['signews'];
$generate_googlesitemap = $config['generate_googlesitemap'];
$guestcomment = $config['guestcomment'];

$CLASS_NEWS = new FNNEWS($config);
$imgedit = "&#128221;";
$imgedel = "&#10060;";
$imgeview = "&#128250;";
$imgnew = "&plus;&nbsp;";
$imgback = "&#9664;&nbsp;";
if ($CLASS_NEWS->IsNewsAdministrator() && isset($_GET['rss']))
    $CLASS_NEWS->GenerateRSS();


if ($generate_googlesitemap && is_writable(".") && is_writable(".") && !file_exists("sitemap_news.xml"))
{
    $CLASS_NEWS->CreateGoogleSitemap();
}
if (!file_exists($_FN['datadir'] . "/rss/{$CLASS_NEWS->config['tablename']}/{$_FN['lang']}/backend.xml"))
{
    $CLASS_NEWS->GenerateRSS();
}

switch ($mode)
{
    case "archive" :
        include ("$path_news/modules/news/archive.php");
        break;

    case "edit" :
        
        if ($CLASS_NEWS->IsNewsAdministrator())
        {
            $CLASS_NEWS->NewsAdmin();
        }
        break;
    case "editconfig" :
        if (FN_IsAdmin())
        {
            $CLASS_NEWS->ConfigurationAdmin();
        }
        break;
    case "editarguments":
        if ($CLASS_NEWS->IsNewsAdministrator())
        {
            $CLASS_NEWS->ArgumentsAdmin();
        }
        break;
    case "submitnews";
        if ($signews)
        {
            if ($_FN['user'] != "" || $config['guestnews'])
            {
                $CLASS_NEWS->SubmitNews();
            }
            else
            {
                echo FN_Translate("you must be registered to") . " " . $_FN['sitename'] . " " . FN_i18n("to highlight news");
            }
        }
        break;
    default :
        //---if op is txtid --->
        if ($op != "")
        {
            
            echo FN_HtmlOpenTable();
            $news_contents = $CLASS_NEWS->GetNewsContents(false, $op);
            if (isset($news_contents['id']))
            {
                $CLASS_NEWS->GestDelNewsComment($news_contents['id']);
                if (isset($news_contents['status']) && $news_contents['status'] == 1)
                {

                    $CLASS_NEWS->UpdateNewsStat($news_contents);
                    $_FN['site_title'] .= $news_contents['title'];

                    FNNEWS_PrintNews($news_contents, $CLASS_NEWS);
                }
               
            }
            echo "<br /><br />$imgback<a href=\"" . FN_RewriteLink("?mod={$_FN['mod']}") . "\">" . FN_i18n("go to") . " " . $_FN['sectionvalues']['title'] . "</a>";
            echo FN_HtmlCloseTable();
        }
        //---if op is txtid ---<
        
        else
        {

            $tplfile = FN_FromTheme("modules/news/news.tp.html", false);
            if ($tplfile && file_exists(FN_PathSite("themes/{$_FN['theme']}/modules/news/news.tp.html")))
            {
                $CLASS_NEWS->PrintListNews($tplfile);
            }
            else
            {
                if ("" != ($topmessage = $CLASS_NEWS->HtmlTopMessage()))
                {
                    echo FN_HtmlOpenTable();
                    echo $topmessage;
                    echo FN_HtmlCloseTable();
                }
                $CLASS_NEWS->PrintListNews();
                
                $rsslist = $CLASS_NEWS->GetRssList();
                if (count($rsslist) > 0)
                {
                    echo FN_HtmlOpenTable();
                    if ($config['show_rss_icon'])
                    {
                        echo "<p>";
                        foreach ($rsslist as $rss)
                        {
                            echo FNNEWS_HtmlRss($rss);
                        }
                        echo "</p>";
                    }
                    echo FN_HtmlCloseTable();
                }
            }
            if ($CLASS_NEWS->IsNewsAdministrator())
            {
                echo FN_HtmlOpenTable();
                echo "$imgnew<a href=\"" . FN_RewriteLink("?mod={$_FN['mod']}&amp;mode=edit&amp;op___xdb_{$CLASS_NEWS->tablename}=insnew&amp;opt=rnt_ccnf_section_{$_FN['mod']}") . "\">" . FN_Translate("add news") . "</a><br />\n";
                echo "$imgedit&nbsp;<a href=\"" . FN_RewriteLink("?mod={$_FN['mod']}&amp;mode=edit&amp;opt=rnt_ccnf_section_{$_FN['mod']}") . "\">" . FN_Translate("edit news") . "</a><br />\n";
                echo FN_HtmlCloseTable();
            }
            elseif ($signews)
            {
                if ($config['guestnews'] || $_FN['user'] != "")
                {
                    echo FN_HtmlOpenTable();
                    echo "$imgnew<a href=\"" . FN_RewriteLink("?mod={$_FN['mod']}&amp;mode=submitnews") . "\">" . FN_Translate("submit news") . "</a>";
                    echo FN_HtmlCloseTable();
                }
            }
        }
        
        break;
}
