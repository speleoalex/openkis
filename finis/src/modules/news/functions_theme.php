<?php

/**
 * @package Flatnux_module_news
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
if (!function_exists("FN_HtmlOpenNews"))
{

    /**
     * Open a news
     *
     * @param string $title
     */
    function FN_HtmlOpenNews($title)
    {
        return "<h3>" . $title . "</h3><div>";
    }

}
if (!function_exists("FN_HtmlCloseNews"))
{

    /**
     * Close a news
     *
     * @param unknown_type $title
     */
    function FN_HtmlCloseNews($title = "")
    {
        return "</div>";
    }

}
if (!function_exists("FN_HtmlOpenTable"))
{

    function FN_HtmlOpenTable()
    {
        $html = "";
        return $html;
    }

    function FN_HtmlCloseTable()
    {
        $html = "";
        return $html;
    }

}

if (!function_exists("FNNEWS_PrintNews"))
{
    /*
     * $news_values:
     *
      [unirecid] => 1
      [txtid] => ciao_
      [title] => CIAO
      [argument] => 2
      [status] => 1
      [summary] =>
      [body] =>
      [photo1] =>
      [username] =>
      [tags] =>
      [date] => 2011-01-02 23:51:23
      [startdate] =>
      [enddate] =>
      [locktop] =>
      [idimport] =>
      [title_en] =>
      [title_es] =>
      [title_de] =>
      [title_fr] =>
      [summary_en] =>
      [summary_es] =>
      [summary_de] =>
      [summary_fr] =>
      [body_en] =>
      [body_es] =>
      [body_de] =>
      [body_fr] =>
      [accesskey_READ] => 1
      [txt_READ] => Leggi tutto
      [link_READ] => index.php?mod=news&op=ciao_
      [accesskey_PRINT] => m
      [txt_PRINT] => Stampa
      [link_PRINT] => print.php?mod=news&op=ciao_
      [link_MODIFY] => index.php?mod=news&op=edit&opmod=insnew_news&pk___xdb_news=1&op___xdb_news=insnew
      [txt_MODIFY] => Modifica
      [socialnetwork_buttons] =>
      [txt_POSTED] => Postato
      [txt_DATE] => 2 Gennaio 2011 - 23:51:23
      [news_SUMMARY] =>
      [news_BODY] =>
      [news_TITLE] => CIAO
      [news_VIEWS] => 40
      [txt_VIEWS] => letto  40 volte
      [img_argument] => misc/fndatabase/news_arguments/2/icon/Pampel2011.png
      [argument_values] => Array
      (
      [unirecid] => 2
      [title] => pampel
      [icon] => Pampel2011.png
      [idimport] =>
      [title_en] =>
      [title_es] =>
      [title_de] =>
      [title_fr] =>
      )
      [img_argument_thumb] => misc/fndatabase/news_arguments/2/icon/thumbs/Pampel2011.png.jpg
      [title_argument] => pampel
      [img_news] =>
      [img_news_thumb] =>
     */

    /**
     *
     * @global array $_FN
     * @param array $news_values
     * @param object $newsobject �
     */
    function FNNEWS_PrintNews($news_values, $newsobject)
    {
        global $_FN;
        echo "<div><h2>{$news_values['news_TITLE']}</h2>";
        // print summary
        echo FN_RewriteLinksLocalToAbsolute($news_values['news_SUMMARY'], "."); 
        echo "<br />\n";
        // print body
        echo FN_RewriteLinksLocalToAbsolute($news_values['news_BODY'], ".");
        // print footer
        echo "\n<div class=\"news_footer\">";
        echo $news_values['txt_POSTED'] . " " . $news_values['txt_DATE'] . " ";
        echo FN_i18n("from") . " " . $news_values['news_USER'];
        echo "</div>";
        //dprint_r($news_values);
        echo "<br />" . $news_values['socialnetwork_buttons'];
        if ($newsobject->IsNewsAdministrator())
        {
            echo "<br /><a href=\"{$news_values['link_MODIFY']}\">[{$news_values['txt_MODIFY']}]</a>";
        }
        echo "</div>";
        if ($newsobject->config['enablecomments'])
        {
            $inline_form = true;
            if (!$inline_form)
            {
                FNNEWS_PrintComments($news_values);
                if (isset($_GET['mode']) && $_GET['mode'] == "comment")
                {
                    echo "<div class=\"news_comment_login\" >";
                    $newsobject->WriteCommentForm($news_values);
                    echo "</div>";
                }
                else
                {
                    echo FN_HtmlOpenTable();
                    if (count($news_values['COMMENTS']))
                        echo "<b>" . FN_i18n("comments") . "</b> | ";
                    echo "<a href=\"{$news_values['link_WRITECOMMENT']}\">{$news_values['txt_WRITECOMMENT']}</a> | <a href=\"{$news_values['link_PRINT']}\">{$news_values['txt_PRINT']}</a>";
                    echo FN_HtmlCloseTable();
                }
            }
            else
            {
                FNNEWS_PrintComments($news_values);
                echo "<div class=\"news_comment_login\" >";
                $newsobject->WriteCommentForm($news_values);
                echo "</div>";
            }
        }
    }

}
/**
 *
 * @param array $newsvalues
 */
if (!function_exists("FNNEWS_PrintNews_summary"))
{
    //------------------ITEM------------------------------------------------------<

    /**
     *
     * @global array $_FN
     * @staticvar int $i
     * @param array $item
     * @param object $newsobject
     */
    function FNNEWS_PrintNews_summary($item, $newsobject)
    {
        global $_FN;
        //static $i = 0;
        //$i++;
        //dprint_r($item);
        $item['news_SUMMARY'] = FN_RewriteLinksLocalToAbsolute($item['news_SUMMARY'], ".");
        echo "<div class=\"news_summary_item\">";
        echo "<div class=\"news_title\" >{$item['title']}</div>";
        echo "<div>";
        echo $item['txt_DATE'];
        echo " |&nbsp;<a accesskey=\"{$item['accesskey_PRINT']}\" href=\"{$item['link_PRINT']}" . "\" title=\"" . $item['txt_PRINT'] . " " . $item['news_TITLE'] . "\" >" . $item['txt_PRINT'] . "</a>";
        if ($item['link_MODIFY'] != "")
        {
            echo " |&nbsp;<a href=\"{$item['link_MODIFY']}\" title=\"" . $item['txt_MODIFY'] . "\" >" . $item['txt_MODIFY'] . "</a>";
        }
        echo "</div>";

        if ($item['img_argument'] != "")
        {
            echo "<div class=\"img_argument\"><img  alt=\"\"  title = \"{$item['title_argument']}\"  src=\"{$item['img_argument']}\" /></div>";
        }
        if ($item['img_news'] != "")
        {
            echo "<div class=\"img_news\"><img alt=\"\" src=\"{$item['img_news']}\" /></div>";
        }
        echo "<div class=\"news_summary\">{$item['news_SUMMARY']}";
        echo "</div>";
        //footer ------>
        echo "<div class=\"news_footer\" >";
        echo $item['txt_VIEWS'];
        if ($newsobject->config['enablecomments'])
        {
            echo " |&nbsp;<a href=\"{$item['link_COMMENTS']}#newscomments\" >" . $item['txt_NUMCOMMENTS'] . "</a>";
        }
        echo " |&nbsp;<a accesskey=\"{$item['accesskey_READ']}\" href=\"" . $item['link_READ'] . "\" title=\"" . $item['txt_READ'] . " " . $item['news_TITLE'] . "\" >" . $item['txt_READ'] . "&nbsp;&gt;&gt;</a>";
        echo "<br />" . $item['socialnetwork_buttons'];
        echo "</div>";
        //footer ------<
        echo "</div>";
    }

}

if (!function_exists("FNNEWS_PrintComment"))
{

    /**
     *
     * @param array $commentvalues
     */
    function FNNEWS_PrintComment($commentvalues)
    {

        echo "<table border=\"0\" cellpadding=\"1\" cellspacing=\"1\" style=\"width:100%\"><tr>";
        echo "<td valign=\"top\" style=\"width:120px;text-align:center;\" ><img src=\"{$commentvalues['img_USER']}\" alt=\"{$commentvalues['username']}\" /><br />{$commentvalues['html_USER']}</td><td  valign=\"top\">";
        echo $commentvalues['html_COMMENT'];
        if (FN_IsAdmin())
            echo "<div style=\"text-align:right\">[" . $commentvalues['html_DELCOMMENT'] . "]</div>";
        echo "</td></tr></table>";
        echo "<br />";
    }

}
if (!function_exists("FNNEWS_HtmlRss"))
{

    /**
     * 
     * @param type $rss
     */
    function FNNEWS_HtmlRss($rss)
    {
        return "<a href=\"{$rss['path']}\">{$rss['image']}</a> ";
    }

}

if (!function_exists("FNNEWS_PrintComments"))
{

    /**
     * FNNEWS_PrintComments
     * visualizza i commenti associati al record
     *
     * @param string unirecid record
     */
    function FNNEWS_PrintComments($news_values)
    {
        global $_FN;
        $comments = $news_values['COMMENTS'];
        //write all comments
        if (count($comments) > 0)
        {
            echo "<div id=\"newscomments\" class=\"newscomments\">";
            echo "<h3>" . FN_i18n("comments") . ":</h3>";
            foreach ($comments as $comment)
            {
                FNNEWS_PrintComment($comment);
            }
            echo "</div>";
        }
    }

}
?>
