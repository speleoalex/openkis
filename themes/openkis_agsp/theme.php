<?php
global $_FN;
$config = FN_LoadConfig("themes/{$_FN['theme']}/config.php");
$_FN['show_search_form']=$config['show_search_form'];

/**
 * 
 * @param type $title
 * @return type
 */
function FN_HtmlOpenTableTitle($title="")
{
    $htmlout="<article class=\"post article\">
                                <div class=\"postmetadataheader\"><h2 class=\"postheader\">$title</h2></div>
		<div class=\"postcontent clearfix\">
		";
    return $htmlout;
}

/**
 * 
 * @param type $title
 * @return string
 */
function FN_HtmlCloseTableTitle($title="")
{
    return "</div></article>";
}

/**
 * 
 * @return string
 */
function FN_HtmlOpenTable()
{
    $htmlout="
		";

    return $htmlout;
}

/**
 * 
 * @param type $title
 * @return string
 */
function FN_HtmlCloseTable($title="")
{
    return "";
}

/**
 * 
 * @global type $_FN
 * @param type $title
 * @return string
 */
function FN_HtmlOpenSection($title=false)
{
    global $_FN;
    $htmlout="\n<!-- open section -->";
    return $htmlout;
}

/**
 * 
 * @global type $_FN
 * @param type $title
 * @return string
 */
function FN_HtmlCloseSection($title=false)
{
    global $_FN;
    $htmlout="\n<!-- close section -->";
    return $htmlout;
}

/**
 * 
 * @global type $_FN
 * @return type
 */
function FN_HtmlLoginLogout($tp_str)
{
    global $_FN;
    if ($_FN['user'] == "")
    {
        return FN_HtmlLoginForm(FN_TPL_GetHtmlPart("include FN_HtmlLoginForm",$tp_str));
    }
    else
    {
        return FN_HtmlLogoutForm(FN_TPL_GetHtmlPart("include FN_HtmlLogoutForm",$tp_str));
    }
}
function FNNEWS_PrintNews($news_values,$newsobject)
{
    global $_FN;
    echo "
<div class=\"jumbotron text-center\">
  <h1>{$news_values['news_TITLE']}</h1>
  
</div>        
<div class=\"container\">
  <div class=\"row\">
                ";
    // print summary
    echo $news_values['news_SUMMARY'];
    echo "<br />\n";
    // print body
    echo $news_values['news_BODY'];
    // print footer
    echo "\n<div class=\"news_footer\">";
    echo $news_values['txt_POSTED']." ".$news_values['txt_DATE']." ";
    echo FN_i18n("from")." ".$news_values['news_USER'];
    echo "</div>";
    //dprint_r($news_values);
    echo "<br />".$news_values['socialnetwork_buttons'];
    if ($newsobject->IsNewsAdministrator())
    {
        echo "<br /><a href=\"{$news_values['link_MODIFY']}\">[{$news_values['txt_MODIFY']}]</a>";
    }
    echo "</div></div>";
    if ($newsobject->config['enablecomments'])
    {
        $inline_form=true;
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
                    echo "<b>".FN_i18n("comments")."</b> | ";
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

function FNNEWS_PrintNews_summary($item,$newsobject)
{
    global $_FN;
    //static $i = 0;
    //$i++;
    //dprint_r($item);
    echo "<div class=\"panel panel-default\">
                <div class=\"panel-heading\">{$item['title']}</div>
                <div class=\"panel-body\">
                ";
    echo "<p>";
    echo $item['txt_DATE'];
    echo " |&nbsp;<a accesskey=\"{$item['accesskey_PRINT']}\" href=\"{$item['link_PRINT']}"."\" title=\"".$item['txt_PRINT']." ".$item['news_TITLE']."\" >".$item['txt_PRINT']."</a>";
    if ($item['link_MODIFY'] != "")
    {
        echo " |&nbsp;<a href=\"{$item['link_MODIFY']}\" title=\"".$item['txt_MODIFY']."\" >".$item['txt_MODIFY']."</a>";
    }
    echo "</p>";

    if ($item['img_argument'] != "")
    {
        echo "<p class=\"img_argument\"><img  alt=\"\"  title = \"{$item['title_argument']}\"  src=\"{$item['img_argument']}\" /></p>";
    }
    if ($item['img_news'] != "")
    {
        echo "<p class=\"img_news\"><img alt=\"\" src=\"{$item['img_news']}\" /></p>";
    }
    echo "<p class=\"news_summary\">{$item['news_SUMMARY']}";
    echo "</p>";
    //footer ------>
    echo "<p class=\"news_footer\" >";
    echo "<p>".$item['txt_VIEWS']."</p>";
    if ($newsobject->config['enablecomments'])
    {
        echo "<a class=\"btn btn-primary btn-lg\" href=\"{$item['link_COMMENTS']}#newscomments\" >".$item['txt_NUMCOMMENTS']."</a> ";
    }
    echo "<a class=\"btn btn-primary btn-lg\"  role=\"button\" accesskey=\"{$item['accesskey_READ']}\" href=\"".$item['link_READ']."\" title=\"".$item['txt_READ']." ".$item['news_TITLE']."\" >".$item['txt_READ']."&nbsp;&gt;&gt;</a>";
    echo "<br />".$item['socialnetwork_buttons'];
    echo "</p>";
    //footer ------<
    echo "</div></div>";
}

function FNNEWS_PrintComments($news_values)
{
    global $_FN;
    $comments=$news_values['COMMENTS'];
    //write all comments
    if (count($comments) > 0)
    {/* <div class="media">
      <div class="media-left">
      <img src="img_avatar1.png" class="media-object" style="width:60px">
      </div>
      <div class="media-body">
      <h4 class="media-heading">John Doe</h4>
      <p>Lorem ipsum...</p>
      </div>
      </div> */
        echo "<div id=\"newscomments\" class=\"newscomments\">";
        echo "<h3>".FN_i18n("comments").":</h3>";
        foreach($comments as $comment)
        {
            FNNEWS_PrintComment($comment);
        }
        echo "</div>";
    }
}

function FNNEWS_PrintComment($commentvalues)
{

    echo "<div class=\"media\">
      <div class=\"media-left\">";
    echo "<img class=\"media-object\" style=\"width:60px\" src=\"{$commentvalues['img_USER']}\" alt=\"{$commentvalues['username']}\" /><br />{$commentvalues['html_USER']}</div>";
    echo "<div class=\"media-body\">".$commentvalues['html_COMMENT']."</div>";
    if (FN_IsAdmin())
        echo "<p style=\"text-align:right\">[".$commentvalues['html_DELCOMMENT']."]</p>";
    echo "</div>";
}

function FN_HtmlOpenNews($title)
{
    return "<div class=\"panel panel-default\">
                <div class=\"panel-heading\">$title</div>
                <div class=\"panel-body\">";
}

function FN_HtmlCloseNews()
{
    return "</div></div>";
}



function FN_TPL_tp_create_menu_footer($str)
{
    return FN_TPL_html_menu($str,"footer","menu_footer");
}



?>
