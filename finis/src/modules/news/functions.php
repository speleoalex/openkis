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
    $path_news = "modules/news/";
    define("_PATH_NEWS_", $path_news);
}
if (!defined("FNNEWS_FUNCTIONS"))
{
    define("FNNEWS_FUNCTIONS", 1);

    /**
     *
     * @global array $_FN
     * @param string $idnews
     * @return string
     */
    function PubNews($idnews, $Tablenews)
    {
        global $_FN;
        $signewsmode = FN_GetParam("signews", $_GET, "html");
        $opt = FN_GetParam("opt", $_GET, "html");
        $itemnews = $Tablenews->xmltable->GetRecordByPrimarykey($idnews);
        $sig = "";
        $gopt = "";
        if ($opt != "")
        {
            $gopt = "&amp;opt=$opt";
        }
        if ($signewsmode != "")
        {
            $sig = "&amp;signews=1";
        }
        $page = FN_GetParam("page___xdb_{$Tablenews->tablename}", $_GET, "int");
        if ($page != "")
        {
            $page = "&amp;page___xdb_{$Tablenews->tablename}=$page";
        }
        if ($itemnews['status'] != 1)
            return "<form action=\"" . ("?mod={$_FN['mod']}&amp;mode=edit$page$sig$gopt") . "\" method=\"post\"><input name=\"pubnewsid\" value=\"$idnews\" type=\"hidden\"/><button type=\"submit\">" . FN_Translate("publish") . "</button></form>";
        else
            return "<form action=\"" . ("?mod={$_FN['mod']}&amp;mode=edit$page$sig$gopt") . "\" method=\"post\"><input name=\"hidenewsid\" value=\"$idnews\" type=\"hidden\"/><button type=\"submit\">" . FN_Translate("hide news") . "</button></form>";
    }

    class FNNEWS
    {

        var $config;
        var $tablename;

        function __construct($config)
        {
            global $_FN;
            $this->config = $config;

            $_FN['force_htmleditor'] = $config['htmleditornews'];
            $this->tablename = $config['tablename'];
            if ($this->tablename == "")
            {
                $this->tablename = "news";
            }
            
            $this->InitTables();
            
            if (!file_exists("{$_FN['datadir']}/media/{$this->tablename}"))
            {
                FN_MkDir("{$_FN['datadir']}/media/{$this->tablename}");
            }
            $_FN['editor_folder'] = "{$_FN['datadir']}/media/{$this->tablename}";

            //dprint_r( $_FN['editor_folder']);
        }

        function IsSpam($text)
        {
            return false;
        }

        function InitTables()
        {
            global $_FN;
            $tablename = $this->tablename;
            $str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<?php exit(0);?>
<tables>
	<field>
		<name>unirecid</name>
		<primarykey>1</primarykey>
		<extra>autoincrement</extra>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>txtid</name>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>title</name>
		<frm_en>Title</frm_en>
		<frm_it>Titolo</frm_it>
		<frm_i18n>title</frm_i18n>
		<frm_multilanguages>auto</frm_multilanguages>
	</field>
	<field>
		<name>argument</name>
		<frm_en>Argument</frm_it>
		<frm_it>Argomento</frm_it>
		<frm_i18n>argument</frm_i18n>
		<foreignkey>{$tablename}_arguments</foreignkey>
		<fk_link_field>unirecid</fk_link_field>
		<fk_show_field>title</fk_show_field>
		<frm_show_image>icon</frm_show_image>
		<frm_required>1</frm_required>
	</field>
	<field>
		<name>status</name>
		<frm_en>Status</frm_en>
		<frm_it>Stato</frm_it>
		<frm_i18n>status</frm_i18n>
        <frm_type>radio</frm_type>
        <frm_options>1,0</frm_options>
		<frm_options_i18n>published,not published</frm_options_i18n>
	</field>
	<field>
		<name>summary</name>
		<frm_en>Summary</frm_en>
		<frm_it>Riassunto</frm_it>
		<frm_i18n>summary</frm_i18n>
		<frm_cols>auto</frm_cols>
		<frm_rows>6</frm_rows>
		<type>text</type>
		<frm_type>html</frm_type>
		<frm_multilanguages>auto</frm_multilanguages>
		<frm_required>1</frm_required>
	</field>
	<field>
		<name>body</name>
		<frm_en>Message body</frm_en>
		<frm_it>Corpo messaggio</frm_it>
		<frm_i18n>body</frm_i18n>
		<frm_cols>auto</frm_cols>
		<frm_rows>10</frm_rows>
		<type>text</type>
		<frm_type>html</frm_type>
		<frm_multilanguages>auto</frm_multilanguages>
	</field>
	<field>
		<name>photo1</name>
		<frm_en>News image</frm_en>
		<frm_it>Immagine notizia</frm_it>
		<frm_i18n>news image</frm_i18n>
		<frm_showinlist>1</frm_showinlist>
		<thumb_listheight>64</thumb_listheight>
		<type>image</type>
		<thumbsize>250</thumbsize>
		<view_tag>center</view_tag>
	</field>
	<field>
		<name>username</name>
		<frm_en>Author</frm_en>
		<frm_it>Autore messaggio</frm_it
		<frm_i18n>author</frm_i18n>>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>tags</name>
		<frm_en>Tags</frm_en>
		<frm_it>Tags</frm_it>
		<frm_i18n>tags</frm_i18n>
		<frm_type>string</frm_type>
	</field>
	<field>
		<name>date</name>
		<frm_en>Date</frm_en>
		<frm_it>Data</frm_it>
		<frm_i18n>date</frm_i18n>
		<frm_type>datetime</frm_type>
		<frm_dateformat>y-mm-dd 00:00:00</frm_dateformat>
	</field>
	<field>
		<name>startdate</name>
		<frm_en>Publication start date</frm_en>
		<frm_it>Data inizio pubblicazione</frm_it>
		<frm_i18n>publication start date</frm_i18n>
		<frm_type>datetime</frm_type>
		<frm_dateformat>y-mm-dd 00:00:00</frm_dateformat>
	</field>
	<field>
		<name>enddate</name>
		<frm_en>Publication end date</frm_en>
		<frm_it>Data fine pubblicazione</frm_it>
		<frm_i18n>publication end date</frm_i18n>
		<frm_type>datetime</frm_type>
		<frm_dateformat>y-mm-dd 00:00:00</frm_dateformat>
	</field>
	<field>
		<name>locktop</name>
		<frm_en>Lock top</frm_en>
		<frm_it>Blocca in alto</frm_it>
		<frm_i18n>lock top</frm_i18n>
		<frm_help_it>Blocca la notizia in alto alla pagina</frm_help_it>
		<frm_help_en>Lock the news in the top of page</frm_help_en>
		<frm_help_i18n>lock the news in the top of page</frm_help_i18n>
		<frm_type>check</frm_type>
	</field>
	<field>
		<name>guestnews</name>
		<frm_en>News proposal</frm_en>
		<frm_it>Segnalata</frm_it>
		<frm_fr>Signal&eacute;es</frm_fr>
		<frm_i18n>news proposal</frm_i18n>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>idimport</name>
		<frm_show>0</frm_show>
	</field>
</tables>
";
            if (!file_exists("{$_FN['datadir']}/fndatabase/$tablename.php"))
            {
                FN_Write($str, "{$_FN['datadir']}/fndatabase/$tablename.php", "w");
            }
            
            

                        
                        
            $Table = FN_XMDBForm($tablename);
            
            
            
            if (!isset($Table->formvals['guestnews']))
            {
                FN_Write($str, "{$_FN['datadir']}/fndatabase/$tablename.php", "w");
            }
            if (!file_exists("{$_FN['datadir']}/fndatabase/{$tablename}_arguments.php"))
            {
                $str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<?php exit(0);?>
<tables>
	<field>
		<name>unirecid</name>
		<primarykey>1</primarykey>
		<extra>autoincrement</extra>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>title</name>
		<frm_it>Titolo</frm_it>
		<frm_en>Title</frm_en>
		<frm_required>1</frm_required>
		<frm_multilanguages>auto</frm_multilanguages>
	</field>
	<field>
		<name>icon</name>
		<frm_it>Immagine argomento</frm_it>
		<frm_en>Argument image</frm_en>
		<frm_showinlist>1</frm_showinlist>
		<thumb_listheight>64</thumb_listheight>
		<type>image</type>
		<thumbsize>64</thumbsize>
		<view_tag>center</view_tag>
	</field>
	<field>
		<name>idimport</name>
		<frm_show>0</frm_show>
	</field>
</tables>
";
                FN_Write($str, "{$_FN['datadir']}/fndatabase/{$tablename}_arguments.php", "w");
            }
            if (!file_exists("{$_FN['datadir']}/fndatabase/{$tablename}_comments.php"))
            {
                $str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<?php exit(0);?>
<tables>
	<field>
		<name>unirecid</name>
		<primarykey>1</primarykey>
		<extra>autoincrement</extra>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>insert</name>
		<type>string</type>
		<defaultvalue></defaultvalue>
		<frm_type>datetime</frm_type>
		<frm_dateformat>y-mm-dd</frm_dateformat>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>username</name>
		<type>string</type>
		<frm_show>0</frm_show>
		<frm_required>1</frm_required>
	</field>
	<field>
		<name>comment</name>
		<frm_it>Commento</frm_it>
		<frm_en>Comment</frm_en>
		<type>text</type>
		<frm_type>bbcode</frm_type>
		<frm_rows>auto</frm_rows>
		<frm_required>1</frm_required>
		<frm_cols>80</frm_cols>
	</field>
	<field>
		<name>idimport</name>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>unirecidrecord</name>
		<frm_show>0</frm_show>
	</field>
	<indexfield>unirecidrecord</indexfield>
</tables>
";
                FN_Write($str, "{$_FN['datadir']}/fndatabase/{$tablename}_comments.php", "w");
            }
            $tablearguments = FN_XMDBForm($tablename . "_arguments");
            // la prima volta metto almeno un argomento--->
            //$tablearguments = xmetadb_table("fndatabase", "{$tablename}_arguments");
            $args = $tablearguments->xmltable->GetRecords();
            if (!is_array($args) || count($args) == 0)
            {
                $newvalues = array();
                $newvalues['title'] = "News";
                $newvalues['icon'] = "news.png";
                $_FILES['icon']['name'] = "news.png";
                $_FILES['icon']['tmp_name'] = "modules/news/news.png";
                $newvalues = $tablearguments->InsertRecord($newvalues);
            }
            // la prima volta metto almeno un argomento---<
        }

        function HtmlTopMessage()
        {
            global $_FN;
            return FN_HtmlStaticContent("sections/{$_FN['mod']}");
        }

        /**
         * get array with news values
         *
         * @param array $itemnews
         *
         */
        function GetNewsContents($id = false, $txtid = false)
        {
            global $_FN;
            static $id_accesskey = 1;
            static $fbexists = false;
            $tablename = $this->tablename;
            $Table = FN_XMDBForm($tablename);

            if ($id)
            {
                $item = $Table->GetRecordTranslatedByPrimarykey($id);
            }
            elseif ($txtid)
            {
                $item = $Table->xmltable->GetRecord(array("txtid" => $txtid));
                $item = $Table->GetRecordTranslated($item);
            }
            else
                return false;

            if (!isset($item['txtid']))
                return false;
            $item['op'] = FN_GetParam("op", $_GET, "html");
            //read news---->
            $_LEGGITUTTO = FN_Translate("read all");
            $item['accesskey_READ'] = FN_GetAccessKey($_LEGGITUTTO, $_FN['mod'] . "&amp;op={$item['txtid']}", "$id_accesskey");
            $id_accesskey++;
            $item['news_USER'] = $item['username'];
            if ($item['news_USER'] == "")
            {
                $item['news_USER'] = FN_Translate("unknown");
            }
            $item['txt_READ'] = $_LEGGITUTTO;
            $item['link_READ'] = FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;op={$item['txtid']}", "&", true);
            //read news----<
            //print news---->
            $_STAMPA = FN_Translate("print");
            $accstampa = FN_GetAccessKey($_STAMPA, "print.php?mod={$_FN['mod']}&amp;op={$item['txtid']}");
            $item['accesskey_PRINT'] = $accstampa;
            $item['txt_PRINT'] = $_STAMPA;
            $item['link_PRINT'] = "index.php?fnapp=print&mod={$_FN['mod']}&amp;op={$item['txtid']}";
            //print news----<
            //modify news---->
            $item['link_MODIFY'] = "";
            $item['txt_MODIFY'] = FN_Translate("modify");
            if ($this->IsNewsAdministrator())
            {
                $item['link_MODIFY'] = FN_RewriteLink("index.php?opt=fnc_ccnf_section_{$_FN['mod']}&amp;mod={$_FN['mod']}&amp;mode=edit&amp;pk___xdb_{$this->tablename}={$item['unirecid']}&amp;op___xdb_{$this->tablename}=insnew");
            }
            //modify news----<

            $item['txt_POSTED'] = FN_Translate("posted");
            //dprint_r($item);
            $item['txt_DATE'] = $this->HtmlDate($item['date']);

            $item['news_SUMMARY'] = FN_Tag2Html(str_replace("\r", "", str_replace("\n", "", ($item['summary']))));
            $item['news_BODY'] = FN_Tag2Html(str_replace("\r", "", str_replace("\n", "", ($item['body']))));

            $item['news_TITLE'] = htmlentities($item['title'], ENT_QUOTES, $_FN['charset_page']);
            $view = $this->GetNewsStat($tablename, $item, "unirecid");
            $item['news_VIEWS'] = $view;
            $item['txt_VIEWS'] = FN_Translate("read") . " " . $view . " " . FN_Translate("times");
            $item['img_argument'] = "";
            $item['argument_values'] = array();
            $item['img_argument_thumb'] = "";
            $item['title_argument'] = "";
            
            if ($item['argument'] != "")
            {
                $TableArguments = FN_XMDBForm($tablename . "_arguments");
                $argvalues = $TableArguments->xmltable->GetRecordByPrimaryKey($item['argument']);
                if (!empty($argvalues['title']))
                {
                    $argvalues = $TableArguments->GetRecordTranslated($argvalues);
                    $item['title_argument'] = htmlentities($argvalues['title'], ENT_QUOTES, $_FN['charset_page']);
                }
                if (!empty($argvalues['icon']) && $this->config['show_news_icon'])
                {
                    $item['argument_values'] = $argvalues;

                    $item['img_argument'] = "{$_FN['siteurl']}{$_FN['datadir']}/fndatabase/{$tablename}_arguments/{$argvalues['unirecid']}/icon/" . $argvalues['icon'];
                    $item['img_argument_thumb'] = "{$_FN['siteurl']}{$_FN['datadir']}/fndatabase/{$tablename}_arguments/{$argvalues['unirecid']}/icon/thumbs/" . $argvalues['icon'] . ".jpg";
                }
                else
                {
                    //$item['img_argument_thumb']=$item['img_argument']="images/px_transparent.png";
                    $item['img_argument_thumb'] = $item['img_argument'] = "";
                }
            }
            $item['img_news'] = "";
            $item['img_news_thumb'] = "";
            if ($item['photo1'] != "")
            {
                $item['img_news'] = "{$_FN['siteurl']}{$_FN['datadir']}/fndatabase/$tablename/{$item['unirecid']}/photo1/" . $item['photo1'];
                $item['img_news_thumb'] = "{$_FN['siteurl']}{$_FN['datadir']}/fndatabase/$tablename/{$item['unirecid']}/photo1/thumbs/" . $item['photo1'] . ".jpg";
            }
            
            if ($this->config['enablecomments'])
            {
                $comments = $this->GetComments($item);
                $item['COMMENTS'] = $comments;
                $item['txt_NUMCOMMENTS'] = count($comments) . " " . FN_Translate("comments");
                //-----------------------
                $item['link_COMMENTS'] = FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;mode=comment&amp;op={$item['txtid']}", "&amp;", true);
                $item['txt_COMMENTS'] = FN_Translate("comments");
                $item['accesskey_COMMENTS'] = FN_GetAccessKey($item['txt_COMMENTS'], $_FN['mod'] . "&amp;mode=comment&amp;op={$item['txtid']}");
                //-----------------------
                $item['txt_WRITECOMMENT'] = FN_Translate('add comment');
                $item['accesskey_WRITECOMMENT'] = FN_GetAccessKey($item['txt_WRITECOMMENT'], $_FN['mod'] . "&amp;mode=comment&amp;op={$item['txtid']}");
                $item['link_WRITECOMMENT'] = FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;mode=comment&amp;op={$item['txtid']}");
            }
            $item['txt_PDF'] = "";

            $item['link_PDF'] = "#";
            if (file_exists("pdf.php"))
                $item['link_PDF'] = "pdf.php?mod={$_FN['mod']}&amp;op={$item['txtid']}";
            $item['txt_SENDEMAIL'] = FN_Translate("send");
            $item['link_SENDEMAIL'] = "mailto:?body=" . urlencode($_FN['siteurl'] . $item['link_READ']);
            $item['news_CATEGORY'] = $item['title_argument'];
            $tags = explode(",", $item['tags']);
            $item['txt_TAGS'] = "";
            if ($this->config['show_tags'])
            {
                if (count($tags) > 0 && $tags[0] != "")
                {
                    $sep = "";
                    $item['txt_TAGS'] = FN_Translate("tags") . ": ";
                    foreach ($tags as $tag)
                    {
                        $item['txt_TAGS'] .= "$sep$tag";
                        $sep = ", ";
                    }
                }
            }
            //fb---->
            $item['socialnetwork_buttons'] = "";
            $item['facebook_button_i_like'] = "";
            if ($this->config['enable_socialnetworks'])
            {
                $urlpage = FN_RewriteLink("index.php?mod=" . $_FN['mod'] . "&op={$item['txtid']}&nc=" . time(), "&", true);
                $strSocialNetworks = "<div>";            
                //facebook----------------------------------------------------->
                if (!empty($this->config['enable_facebook']))
                {
                    if ($fbexists === false || !empty($_GET['op']))
                    {
                        $fbexists = true;
                        $ll = $_FN['lang'] . "_" . strtoupper($_FN['lang']);

                        $strSocialNetworks .= "\n
<script>
  window.fbAsyncInit = function() {
    FB.init({
      appId      : '{$this->config['fb_appid']}',
      xfbml      : true,
      version    : 'v2.7'
    });
  };

  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = \"//connect.facebook.net/$ll/sdk.js\";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script>  
  
  
  ";
                    }
                    $image = "";
                    if ($item['img_news'])
                        $image = "data-picture=\"{$item['img_news']}\"";
                    elseif ($item['img_argument'])
                        $image = "data-picture=\"{$item['img_argument']}\"";
                    /*
                      $image="";
                      $strSocialNetworks.="<div
                      class=\"fb-like\"
                      data-share=\"true\"
                      data-width=\"450\"
                      data-show-faces=\"true\">
                      </div>"; */

                    $strSocialNetworks .= "<br /><div class=\"fb-like\" data-href=\"$urlpage\" data-layout=\"button\" data-action=\"like\" data-show-faces=\"false\" data-share=\"true\" $image >";
                    $strSocialNetworks .= "</div><br /><br />";
                    //facebook-----------------------------------------------------<
                    $strSocialNetworks .= "</div>";
                    $item['socialnetwork_buttons'] = $strSocialNetworks;
                }
                $item['facebook_button_i_like'] = $item['socialnetwork_buttons'];
            }
            //fb----<			
            return $item;
        }

        /**
         * print date of news
         *
         * @param string $date
         * @return string
         */
        function HtmlDate($date)
        {
            global $_FN;
            if ($date == "")
                return "";
            $date = explode("-", $date);
            if (!isset($date[1]))
                return "";
            $h = explode(" ", $date[2]);
            $h = isset($h[1]) ? $h[1] : "";
            return intval($date[2]) . " " . $_FN['months'][intval($date[1]) - 1] . " " . $date[0] . " - " . $h;
        }

        /**
         * return true if the user is administrator
         *
         * @return bool
         */
        function IsNewsAdministrator()
        {
            global $_FN;
            $conf = FN_LoadConfig();
            FN_CreateGroupIfNotExists($conf['group_news']);

            if ($_FN['user'] != "" && $conf['group_news'] != "" && FN_UserInGroup($_FN['user'], $conf['group_news']))
                return true;
            return FN_UserCanEditSection();
        }

        /**
         * $this->WriteCommentForm
         * aggiunge un commento al record
         *
         * @param array record
         */
        function WriteCommentForm($newsvalues)
        {

            global $_FN;
            $unirecid = $newsvalues['unirecid'];
            $txtid = $newsvalues['txtid'];
            if ($_FN['user'] == "" && !$this->config['guestcomment'])
            {
                echo FN_Translate("you need to register on") . " " . $_FN['sitename'] . " " . FN_Translate("to add a comment");
                FN_LoginForm();
            }
            else
            {
                $tablecomments = new FieldFrm("fndatabase", $this->config['tablename'] . "_comments", $_FN['datadir'], $_FN['lang'], $_FN['languages']);
                $tablecomments->setlayout("table");
                $text = FN_GetParam("comment", $_POST, "html");
                if (isset($_POST['comment']))
                {
                    $commentvalues = $tablecomments->getbypost();
                    $commentvalues['comment'] = htmlspecialchars($commentvalues['comment']);
                    $commentvalues['comment'] = $this->FixNewLine($commentvalues['comment']);
                    $commentvalues['unirecidrecord'] = $unirecid;
                    $commentvalues['username'] = $_FN['user'];
                    $commentvalues['insert'] = FN_Now();
                    if ($text == "")
                    {
                        echo FN_Translate("you skipped some fields or you made some error") . ":<br />";
                        $tit = FN_Translate("back");
                        echo "<a accesskey=" . FN_GetAccessKey($tit, "javascript:history.back()") . " href=\"javascript:history.back()\">&lt;&lt;$tit</a><br /><br />";
                    }
                    else
                    {
                        if ($this->IsSpam($text))
                        {
                            echo FN_Translate("operation is not allowed because it was identified as spam");
                            $tit = FN_Translate("back");
                            echo "<br /><br /><a accesskey=" . FN_GetAccessKey($tit, "javascript:history.back()") . " href=\"javascript:history.back()\">&lt;&lt;$tit</a><br /><br />";
                            return;
                        }

                        $tablecomments->InsertRecord($commentvalues);
                        echo FN_Translate("message sent") . "<br />";
                        $this->CommentNotify($commentvalues);
                        FN_log("Comment added: table {$this->config['tablename']}, record $unirecid");
                        FN_JsRedirect(FN_RewriteLink("index.php?mod={$_FN['mod']}&op=$txtid", "&"));
                    }
                }
                else
                {
                    if ($unirecid != "")
                    {
                        echo "\n<form method=\"post\" enctype=\"multipart/form-data\" action=\"" . FN_RewriteLink("index.php?mod={$_FN['mod']}&op=$txtid&mode=comment") . "\" >";
                        $tablecomments->SetLayout("flat");
                        $tablecomments->formvals['comment']['title'] = FN_Translate("add comment");
                        $tablecomments->requiredtext = "";
                        $tablecomments->ShowInsertForm();
                        echo "<input class=\"submit\" type=\"submit\" value=\"" . FN_Translate("send") . "\"/>";
                        echo "<input type='button' class='button' onclick='window.location=(\"index.php?mod={$_FN['mod']}\")'  value='" . FN_Translate("cancel") . "' />";
                        echo "\n</form>";
                    }
                }
            }
        }

        /**
         *
         * @param array $commentvalues 
         */
        function CommentNotify($commentvalues)
        {
            global $_FN;
            if (empty($this->config['enable_comments_notify']))
                return;
            $newvalues = $this->GetNewsContents($commentvalues['unirecidrecord']);
            $comments = $this->GetComments($newvalues);

            //------------------------users comments ------------------------------>	
            $users = array();
            foreach ($comments as $item)
            {
                if (!isset($users[$item['username']]) && $commentvalues['username'] != $item['username'] && $newvalues['username'] != $item['username'])
                {
                    $users[$item['username']] = $item['username'];
                    $uservalues = FN_GetUser($item['username']);
                    if (!empty($uservalues['email']))
                    {
                        $email_subject = "[{$_FN['sitename']} - {$_FN['sectionvalues']['title']}] ";
                        $email_subject .= $commentvalues['username'] . " " . FN_Translate("has published something on the news") . " '{$newvalues['news_TITLE']}'";
                        $email_body = FN_Translate("to read the message follow this link") . ":<br />";
                        $email_body .= "<a href=\"{$_FN['siteurl']}{$newvalues['link_READ']}\">{$_FN['siteurl']}{$newvalues['link_READ']}</a>";
                        if (!empty($uservalues['email']))
                        {
                            FN_SendMail($uservalues['email'], $email_subject, $email_body, true);
                        }
                    }
                }
            }
            //------------------------users comments ------------------------------<
            //-------------------------news comments ------------------------------>
            $uservalues = FN_GetUser($newvalues['username']);
            $email_subject = "[{$_FN['sitename']} - {$_FN['sectionvalues']['title']}] ";
            $email_subject .= $commentvalues['username'] . " " . FN_Translate("has published something on the news") . " '{$newvalues['news_TITLE']}'";
            $email_body = FN_Translate("to read the message follow this link") . ":<br />";
            $email_body .= "<a href=\"{$_FN['siteurl']}{$newvalues['link_READ']}\">{$_FN['siteurl']}{$newvalues['link_READ']}</a>";
            FN_SendMail($uservalues['email'], $email_subject, $email_body, true);
            //-------------------------news comments ------------------------------<
            //dprint_r($users);
            //dprint_r($newvalues);
            //die("ssss");
        }

        /**
         *
         * @param string $unirecid
         * @param string $tablename
         */
        function GetComments($newsvalues)
        {
            global $_FN;
            $tablelinks = FN_XMDBForm( $this->config['tablename'] . "_comments");

            $idnews = $newsvalues['unirecid'];
            $r['unirecidrecord'] = $idnews;
            $comments = $tablelinks->xmltable->GetRecords($r, false, false, "unirecid", false);
            foreach ($comments as $k => $comment)
            {
                $comments[$k]['txt_FROM'] = FN_Translate("unknown");
                $comments[$k]['html_USER'] = $comment['username'];
                if ($comments[$k]['html_USER'] == "")
                    $comments[$k]['html_USER'] = FN_Translate("unknown");


                if ($comment['username'] != "" && $uservalues = FN_GetUser($comment['username']))
                {
                    //immagine utente ---->
                    $comments[$k]['img_USER'] = FN_GetUserImage($comment['username']);
                    $comments[$k]['html_img_USER'] = FN_HtmlUserImage($comment['username']);
                    //immagine utente ----<
                }
                else
                {
                    $imagesrc = FN_FromTheme("images/user.png");
                    $comments[$k]['img_USER'] = $imagesrc;
                    $comments[$k]['html_img_USER'] = "<img alt=\"\" src=\"$imagesrc\" />";
                }
                $comments[$k]['txt_DATE'] = FN_Translate("date");
                $comments[$k]['html_DATE'] = FN_GetDateTime($comment['insert']);
                $comments[$k]['html_COMMENT'] = FN_Tag2Html($comment['comment']);
                $unirecidrecord = $comment['unirecid'];
                $dellink = FN_RewriteLink("index.php?mod={$_FN['mod']}&mode=DelNewsComment&op={$newsvalues['txtid']}&unirecidrecord=$unirecidrecord", "&");
                $comments[$k]['html_DELCOMMENT'] = "<a href=\"javascript:check('$dellink')\" >" . FN_Translate("delete") . "</a>";
            }
            return $comments;
        }

        /**
         * GestDelNewsComment
         *
         * elimina un commento dal record
         * @param string unirecid record
         */
        function GestDelNewsComment($unirecid)
        {
            global $_FN;

            $tablename = $this->tablename;
            $databasename = $_FN['database'];
            $tablelinks = FN_XMDBForm($tablename . "_comments");
            $unirecidrecord = FN_GetParam('unirecidrecord', $_GET, "html");
            if (FN_IsAdmin() && isset($_GET['unirecidrecord']) && $unirecidrecord != "")
            {
                $unirecidrecord = intval($unirecidrecord);
                $tablelinks->xmltable->DelRecord($unirecidrecord);
                echo FN_Translate("the comment was deleted") . "<br />";
                FN_log("Comment deleted:table $tablename, record $unirecid");
            }
        }

        /**
         *
         * @global array $_FN
         * @param string $tablename
         * @return
         */
        function SubmitNews()
        {
            global $_FN;
            $tablename = $this->tablename;
            $table = new FieldFrm("fndatabase", "$tablename", $_FN['datadir'], $_FN['lang'], $_FN['languages']);
            $table->formvals['summary']['frm_type'] = "bbcode";
            //$table->formvals['summary']['frm_multilanguages'] = "";
            $table->formvals['body']['frm_type'] = "bbcode";
            //$table->formvals['body']['frm_multilanguages'] = "";
            //$table->formvals['title']['frm_multilanguages'] = "";
            $table->formvals['title']['frm_required'] = "1";
            $table->formvals['locktop']['frm_show'] = "0";
            $table->formvals['tags']['frm_show'] = "0";
            $table->formvals['status']['frm_show'] = "0";
            $table->formvals['locktop']['frm_show'] = "0";
            $table->formvals['date']['frm_show'] = "0";
            $table->formvals['startdate']['frm_show'] = "0";
            $table->formvals['enddate']['frm_show'] = "0";
            $table->formvals['photo1']['frm_show'] = "0";
            $save = FN_GetParam("save", $_GET, "html");
            $table->LoadFieldsClasses();
            $newvalues = array();
            //<script>alert('a')</script>ciao
            $newvalues = $table->getbypost();
            $newvalues['title'] = FN_GetParam("title", $_POST, "html");
            $newvalues['body'] = isset($newvalues['body']) ? $newvalues['body'] : "";
            $newvalues['summary'] = isset($newvalues['summary']) ? $newvalues['summary'] : "";
            $newvalues['status'] = 0;
            $newvalues['locktop'] = 0;
            $newvalues['date'] = FN_Now();
            $newvalues['startdate'] = "";
            $newvalues['enddate'] = "";
            $newvalues['tags'] = "";
            $newvalues['username'] = $_FN['user'];
            $newvalues['guestnews'] = "1";
            $errors = array();
            if (!empty($newvalues['title_' . $_FN['lang']]))
                $newvalues['title'] = $newvalues['title_' . $_FN['lang']];
            if (!empty($newvalues['title_' . $_FN['lang_default']]))
                $newvalues['title'] = $newvalues['title_' . $_FN['lang_default']];

            if ($newvalues['title'] != "")
            {
                $textid = $this->GenTxtId($newvalues['title']);
                $newvalues['txtid'] = $textid;
            }

            if ($save)
            {
                if ($this->IsSpam($newvalues['body'] . $newvalues['summary']))
                {
                    echo FN_Translate('spam');
                    return;
                }
                $errors = $table->Verify($newvalues);

                if (count($errors) == 0)
                {
                    $newvalues['title' . $_FN['lang']] = $newvalues['title'] = htmlspecialchars($newvalues['title']);
                    $newvalues['body_' . $_FN['lang']] = $newvalues['body'] = htmlspecialchars($newvalues['body']);
                    $newvalues['summary' . $_FN['lang']] = $newvalues['summary'] = htmlspecialchars($newvalues['summary']);
                    // trasforma "\n\r" , "\r" in "\n" e quindi in "<br />"
                    $newvalues['body_' . $_FN['lang']] = $newvalues['body'] = $this->FixNewLine($newvalues['body'], true);
                    $newvalues['summary' . $_FN['lang']] = $newvalues['summary'] = $this->FixNewLine($newvalues['summary'], true);
                    $r = $table->InsertRecord($newvalues);

                    if (is_array($r))
                    {
                        fn_log("Submit news " . $newvalues['title']);
                        echo "<div>";
                        echo FN_Translate("your highlight has been forwarded to the admin who will publish it");
                        echo "<br />";
                        echo "<br />";
                        echo "<a href=\"" . FN_RewriteLink("index.php?mod={$_FN['mod']}") . "\">" . FN_Translate("next") . " &gt;&gt;</a>";
                        echo "</div>";
                    }
                    else
                    {
                        $errors = array();
                        echo "<div>";
                        FN_Alert(FN_Translate($r));
                        fn_log("Error in submit news " . $newvalues['title']);
                        echo "<br />";
                        echo "</div>";
                        echo "<form method=\"post\" action=\"" . FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;mode=submitnews&amp;save=1") . "\">";
                        $table->ShowInsertForm(FN_IsAdmin(), $newvalues, $errors);
                        echo "<button type=\"submit\">" . FN_Translate("send") . "</button>";
                        echo "</form>";
                    }
                    return;
                }
            }
//$table->LoadFieldsForm();
            echo "<form method=\"post\" action=\"" . FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;mode=submitnews&amp;save=1") . "\">";
            $table->ShowInsertForm(FN_IsAdmin(), $newvalues, $errors);
            echo "<button type=\"submit\">" . FN_Translate("send") . "</button>";
            echo "</form>";
        }

//------themable

        function FixNewLine($text, $tobr = false)
        {
            $eol = "\n";
            $text = str_replace("\r\n", "\n", $text);
            $text = str_replace("\r", "", $text);
            $text = str_replace("\n", "$eol", $text);
            if ($tobr)
                $text = str_replace("\n", "<br />", $text);
            return $text;
        }

        function ReadVarFromCache($varname)
        {
            global $_FN, $_FNCACHE;
            if (isset($_FNCACHE[$varname]))
            {
                return $_FNCACHE[$varname];
            }
            if (!empty($this->config['use_cache']))
            {
                $filename = "{$_FN['datadir']}/_cache/news/$varname";
                if (file_exists($filename) && (false !== $v = file_get_contents($filename)))
                {
                    return @unserialize($v);
                }
            }
            return false;
        }

        function ClearCache()
        {
            global $_FN;
            FN_RemoveDir("{$_FN['datadir']}/_cache/news");
        }

        function SaveToCache($varname, $varvalue)
        {
            global $_FN, $_FNCACHE;
            //if (!empty($this->config['use_cache']))
            {
                if (!file_exists("{$_FN['datadir']}/_cache/news"))
                {
                    mkdir("{$_FN['datadir']}/_cache/news");
                }
            }
            FN_Write(serialize($varvalue), "{$_FN['datadir']}/_cache/news/$varname");
            $_FNCACHE[$varname] = $varvalue;
        }

        /**
         *
         * @return array
         */
        function GetListNews()
        {
            global $_FN;
            $tablename = $this->config['tablename'];
            $all = $this->ReadVarFromCache("allnews{$_FN['mod']}");
            if ($all == false)
            {
                $DB = new XMETADatabase("fndatabase", $_FN['datadir']);
                $all = $DB->query("SELECT * FROM $tablename WHERE status LIKE '1' ORDER BY date DESC");
                $this->SaveToCache("allnews{$_FN['mod']}", $all);
            }

            return $all;
        }

        /**
         * print news list
         *
         * @param string $tablename
         */
        function PrintListNews($tplfile = "")
        {
            global $_FN;
            $newspp = $this->config['newspp'];
            $all = $this->GetListNews();
            $idlang = "";
            if ($_FN['lang'] != $_FN['lang_default'])
            {
                $idlang = "_{$_FN['lang']}";
            }
            $locktop = false;
            $newstoprint = false;
            $locktop = $this->ReadVarFromCache("locktop{$_FN['mod']}");
            $newstoprint = $this->ReadVarFromCache("newstoprint{$_FN['mod']}");

            if (!is_array($newstoprint) || !is_array($locktop))
            {
                $locktop = array();
                $newstoprint = array();
                $curtime = FN_Time();
                
                if (is_array($all))
                {
                    $i = 1;
                    foreach ($all as $item)
                    {
                        if ($item['title' . $idlang] != "" || $item['body' . $idlang] != "" || $item['summary' . $idlang] != "")
                        {
                            if ($item['startdate'] != "" && $curtime < strtotime($item['startdate']))
                            {
                                continue;
                            }
                            if ($item['enddate'] != "" && $curtime > strtotime($item['enddate']))
                            {
                                continue;
                            }
                            if ($i > $newspp)
                                break;
                            if ($item['locktop'])
                            {
                                $locktop[] = $this->GetNewsContents($item['unirecid']);
                            }
                            else
                                $newstoprint[] = $this->GetNewsContents($item['unirecid']);

                            $i++;
                        }
                    }
                    
                }
                
                $this->SaveToCache("locktop{$_FN['mod']}", $locktop);
                
                $this->SaveToCache("newstoprint{$_FN['mod']}", $newstoprint);
            }


            if ($tplfile)
            {
                $vars = array();
                $vars['topnews'] = $locktop;
                $vars['news'] = $newstoprint;
                $vars['htmlcontents'] = $this->HtmlTopMessage();
                
                echo FN_TPL_ApplyTplFile($tplfile, $vars);
            }
            else
            {
                foreach ($locktop as $news_values)
                {
                    FNNEWS_PrintNews_summary($news_values, $this);
                }
                echo "<div class=\"news_list_news\">";
                foreach ($newstoprint as $news_values)
                {
                    FNNEWS_PrintNews_summary($news_values, $this);
                }
                echo "</div>";
            }
        }

        /**
         *
         * @global array $_FN
         * @param <type> $text
         * @param <type> $tablename
         * @return string
         */
        function GenTxtId($text)
        {
            global $_FN;
            $tablename = $this->config['tablename'];
            $table = FN_XMDBForm($tablename);
            $text = strtolower(str_replace(" ", "_", $text));
            $text = preg_replace("/à/s", "a", $text);
            $text = preg_replace("/á/s", "a", $text);
            $text = preg_replace("/è/s", "e", $text);
            $text = preg_replace("/é/s", "e", $text);
            $text = preg_replace("/ì/s", "i", $text);
            $text = preg_replace("/í/s", "i", $text);
            $text = preg_replace("/ò/s", "o", $text);
            $text = preg_replace("/ó/s", "o", $text);
            $text = preg_replace("/ù/s", "u", $text);
            $text = preg_replace("/ú/s", "u", $text);
            $text = preg_replace("/[^A-Z^a-z_0-9]/s", "_", $text);
            $text = str_replace("-", "_", $text);
            $text = str_replace(".", "_", $text);
            if ($text == "")
            {
                $text = $_FN['mod'] != "" ? $_FN['mod'] : "news";
            }
            $acc = "";
            do
            {
                $textid = $text . $acc;
                $rec = $table->xmltable->GetRecord(array("txtid" => $textid));
                $acc = intval($acc) + 1;
            } while (isset($rec['unirecid']));
            return $textid;
        }

        /**
         *
         * @return array
         */
        function GetRssList()
        {
            global $_FN;
            $rss[0]['title'] = "RSS";
            $rss[0]['path'] = "{$_FN['siteurl']}{$_FN['datadir']}/rss/{$this->config['tablename']}/{$_FN['lang']}/backend.xml";
            $rss[0]['image'] = "&#128257;";

            return $rss;
        }

        /**
         * generete rss
         *
         */
        function GenerateRSS()
        {

            global $_FN;
            $newspp = 10;
            $tablename = "";
            $conf = FN_LoadConfig();
            $tablename = $conf['tablename'];
            $DB = new XMETADatabase("fndatabase", $_FN['datadir']);
            $all = $this->GetListNews();
            foreach ($_FN['listlanguages'] as $llang)
            {
                $idlang = "";
                if ($llang != $_FN['lang_default'])
                {
                    $idlang = "_{$llang}";
                }
                $locktop = array();
                $newstoprint = array();
                $curtime = FN_Time();
                if (is_array($all))
                {
                    $i = 1;
                    foreach ($all as $item)
                    {
                        if ($item['title' . $idlang] != "" || $item['body' . $idlang] != "" || $item['summary' . $idlang] != "")
                        {



                            if ($item['startdate'] != "" && $curtime < strtotime($item['startdate']))
                            {
                                continue;
                            }
                            if ($item['enddate'] != "" && $curtime > strtotime($item['enddate']))
                            {
                                continue;
                            }
                            if ($i > $newspp)
                                break;
                            if ($item['locktop'])
                            {
                                $locktop[] = $this->GetNewsContents($item['unirecid']);
                            }
                            else
                            {
                                $newstoprint[] = $this->GetNewsContents($item['unirecid']);
                            }
                            $i++;
                        }
                    }
                }
                $body = "<?xml version='1.0' encoding='" . FN_i18n("_CHARSET", $llang) . "'?>\n<rss version='2.0'>\n\t<channel>\n";
                // informazioni generali sul feed
                $body .= "\t\t<title>{$_FN['sitename']}</title>\n\t\t<link>{$_FN['siteurl']}</link>\n\t\t<description><![CDATA['{$_FN['sitename']}' HEADLINES]]></description>\n";
                $body .= "\t\t<managingEditor>{$_FN['site_email_address']}</managingEditor>\n\t\t<generator>FlatNux RSS Generator - http://www.flatnux.sf.org</generator>\n";
                $body .= "\t\t<lastBuildDate>" . date("Y-m-d H:i:s") . " GMT</lastBuildDate>\n";

                foreach ($locktop as $news_values)
                {
                    $body .= "\t\t<item>\n";
                    $body .= "\t\t\t<title>{$news_values['news_TITLE']}</title>\n";
                    $body .= "\t\t\t<link>{$_FN['siteurl']}{$news_values['link_READ']}</link>\n\t\t\t<description><![CDATA[{$news_values['news_SUMMARY']}]]></description>\n";
                    $body .= "\t\t\t<pubDate>" . date("Y-m-d H:i:s", strtotime($news_values['date'])) . " GMT</pubDate>\n";
                    $body .= "\t\t</item>\n";
                }
                foreach ($newstoprint as $news_values)
                {
                    $body .= "\t\t<item>\n";
                    $body .= "\t\t\t<title>{$news_values['news_TITLE']}</title>\n";
                    $body .= "\t\t\t<link>{$_FN['siteurl']}{$news_values['link_READ']}</link>\n\t\t\t<description><![CDATA[{$news_values['news_SUMMARY']}]]></description>\n";
                    $body .= "\t\t\t<pubDate>" . date("Y-m-d H:i:s", strtotime($news_values['date'])) . " GMT</pubDate>\n";
                    $body .= "\t\t</item>\n";
                }
                $body .= "\t</channel>\n</rss>";
                // scrittura del feed
                if (!file_exists($_FN['datadir'] . "/rss"))
                    mkdir($_FN['datadir'] . "/rss");
                if (!file_exists($_FN['datadir'] . "/rss/{$this->config['tablename']}"))
                    mkdir($_FN['datadir'] . "/rss/{$this->config['tablename']}");

                if (!file_exists($_FN['datadir'] . "/rss/{$this->config['tablename']}/$llang"))
                    mkdir($_FN['datadir'] . "/rss/{$this->config['tablename']}/$llang");
                FN_Write($body, "{$_FN['datadir']}/rss/{$this->config['tablename']}/$llang/backend.xml");
            }
        }

        /**
         * generete google sitemap
         *
         */
        function CreateGoogleSitemap()
        {
            global $_FN;
            $DB = new XMETADatabase("fndatabase", $_FN['datadir']);
            $all = $DB->query("SELECT * FROM {$this->config['tablename']} WHERE status LIKE '1'");
            $newstoprint = array();
            foreach ($_FN['listlanguages'] as $llang)
            {
                $idlang = "";
                if ($llang != $_FN['lang_default'])
                {
                    $idlang = "_{$llang}";
                }
                $curtime = FN_Time();
                if (is_array($all))
                {
                    foreach ($all as $item)
                    {
                        if ($item['title' . $idlang] != "" || $item['body' . $idlang] != "" || $item['summary' . $idlang] != "")
                        {

                            if ($item['startdate'] != "" && $curtime < strtotime($item['startdate']))
                            {
                                continue;
                            }
                            if ($item['enddate'] != "" && $curtime > strtotime($item['enddate']))
                            {
                                continue;
                            }
                            $newstoprint[] = $item;
                        }
                    }
                }
            }

            $body = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.84\">";
            foreach ($newstoprint as $news_values)
            {
                $link = FN_RewriteLink("index.php?mod=news&amp;op={$news_values['txtid']}");
                $body .= "\n\t<url><loc>{$_FN['siteurl']}{$link}</loc></url>";
            }
            $body .= "\n</urlset><!-- end -->";
            FN_Write($body, "sitemap_{$_FN['mod']}.xml");
        }

        /**
         * count news has for the current language
         *
         * @author Simone Vellei <simone_vellei@users.sourceforge.net>
         * @author Alessandro Vernassa <speleoalex@gmail.com>
         *
         */
        function CountSigNews()
        {
            $tablename = $this->tablename;
            $news = FN_XMETADBQuery("SELECT unirecid FROM $tablename WHERE status LIKE '0' AND  guestnews <> '' ");
            if (!is_array($news))
                return 0;
            return (count($news));
        }

        /**
         *
         * @param string $tablename
         * @param array $row
         * @param string $pkf
         */
        function UpdateNewsStat($row)
        {
            global $_FN;
            $tablename = $this->tablename;
            $pkf = "unirecid";
//-------statistiche---------------------->>
            if (!file_exists("{$_FN['datadir']}/fndatabase/$tablename" . "_stat"))
            {
                $sfields = array();
                $sfields[0]['name'] = "unirecid";
                $sfields[0]['primarykey'] = "1";
                $sfields[1]['name'] = "view";
                echo createxmltable("fndatabase", $tablename . "_stat", $sfields, $_FN['datadir']);
            }
            $tbtmp = FN_XMDBTable($tablename . "_stat");
            $tmprow['unirecid'] = $row[$pkf];
            if (($oldview = $tbtmp->GetRecordByPrimaryKey($row[$pkf])) == false)
            {
                $tmprow['view'] = 1;
                $rowtmp = $tbtmp->InsertRecord($tmprow);
            }
            else
            {
                $oldview['view']++;
                $rowtmp = $tbtmp->UpdateRecord($oldview); //aggiunge vista
            }
//-------statistiche----------------------<<
        }

        /**
         *
         * @global array $_FN
         * @param <type> $tablename
         * @param <type> $row
         * @param <type> $pkf
         * @return <type>
         */
        function GetNewsStat($tablename, $row, $pkf = "unirecid")
        {
            global $_FN;
            if (!file_exists("{$_FN['datadir']}/fndatabase/$tablename" . "_stat"))
            {
                return 0;
            }
            $tbtmp = FN_XMDBTable($tablename . "_stat");
            $tmprow['unirecid'] = $row[$pkf];
            if (($oldview = $tbtmp->GetRecordByPrimaryKey($row[$pkf])) == false)
            {
                return 0;
            }
            else
            {
                return intval($oldview['view']);
            }
        }

        /**
         *
         * @global array $_FN
         */
        function ArgumentsAdmin()
        {
            global $_FN;
            $tablename = $this->config['tablename'];
            $signewsmode = FN_GetParam("signews", $_GET, "html");

            $op = FN_GetParam("op", $_GET, "html");
            $mode = FN_GetParam("mode", $_GET, "html");
            $opt = FN_GetParam("opt", $_GET, "html"); //if is controlcenter
            $gopt = "";
            if ($opt != "")
            {
                $gopt = "&amp;opt=$opt";
            }

            $imgedit = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/modify.png") . "\" alt=\"\" />";
            $imgedel = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/delete.png") . "\" alt=\"\" />";
            $imgeview = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/news.png") . "\" alt=\"\" />";
            $imgnew = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/add.png") . "\" alt=\"\" />&nbsp;";
            $imgback = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/left.png") . "\" alt=\"\" />&nbsp;";
            // if (!isset($_GET["op___xdb_$tablename" . "_arguments"]) || $_GET["op___xdb_$tablename" . "_arguments"] == "")
            {
                echo $this->HtmlNewsAdminToolbar();
            }
            echo "<h3>" . FN_Translate("edit arguments") . "</h3>";
            $params = array();
            $link = "mod={$_FN['mod']}&amp;mode=$mode$gopt";
            $params['link'] = $link;
            $params['textdelete'] = "<img title=\"" . FN_Translate("delete") . "\" style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/delete.png") . "\" alt=\"\" />";
            $params['fields'] = "title|icon";
            $params['textnew'] = $imgnew . FN_Translate("new argument");
            echo "<div class=\"news_editor\">";
            if (basename($_FN['self']) == "controlcenter.php")
            {
                FNCC_XMETATableEditor($tablename . "_arguments", $params);
            }
            else
            {
                FN_XMETATableEditor($tablename . "_arguments", $params);
            }
            echo "</div>";
        }

        /**
         *
         * @global array $_FN
         * @return string
         */
        function HtmlNewsAdminToolbar($from_news = false)
        {
            global $_FN;
            $tablename = $this->config['tablename'];
            $signewsmode = FN_GetParam("signews", $_GET, "html");
            $opt = FN_GetParam("opt", $_GET, "html"); //if is controlcenter
            $mode = FN_GetParam("mode", $_GET, "html");
            $signews = FN_GetParam("signews", $_GET, "html");

            $gopt = $sig = "";
            if ($opt != "")
            {
                $gopt = "&amp;opt=$opt";
            }
            if ($signewsmode != "")
            {
                $sig = "&amp;signews=1";
            }
            $imgedit = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/modify.png") . "\" alt=\"\" />";
            $imgeview = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/news.png") . "\" alt=\"\" />";
            $imgeright = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/right.png") . "\" alt=\"\" />";

            $imgesettings = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/configure.png") . "\" alt=\"\" />";

            $html = "<div class=\"news-admin-toolbar\">";
//toolbar ---->
            $numsig = $this->CountSigNews($tablename);
            $b1 = $b2 = "";
            if ($mode == "edit" && !$signews)
            {
                $b1 = "<b>";
                $b2 = "</b>";
            }
            $html .= "$imgeview&nbsp;<a href=\"" . ("?mod={$_FN['mod']}&amp;mode=edit$gopt") . "\">$b1" . FN_Translate("manage news") . "$b2</a>";
            $b1 = $b2 = "";
            if ($mode == "edit" && $signews)
            {
                $b1 = "<b>";
                $b2 = "</b>";
            }
            $html .= " ! $imgeview&nbsp;<a href=\"" . ("?mod={$_FN['mod']}&amp;mode=edit&amp;signews=1$gopt") . "\">$b1" . FN_Translate("signed news") . "$b2</a> ($numsig)";
            $b1 = $b2 = "";
            if ($mode == "editarguments")
            {
                $b1 = "<b>";
                $b2 = "</b>";
            }
            $html .= " | $imgedit&nbsp;<a href=\"" . ("?mod={$_FN['mod']}&amp;mode=editarguments$gopt") . "\">$b1" . FN_Translate("edit arguments") . "$b2</a>";
            $b1 = $b2 = "";
            if ($mode == "editconfig")
            {
                $b1 = "<b>";
                $b2 = "</b>";
            }
            if (FN_IsAdmin())
                $html .= " | $imgesettings&nbsp;<a href=\"" . ("?mod={$_FN['mod']}&amp;mode=editconfig$gopt") . "\">$b1" . FN_Translate("edit configuration") . "$b2</a>";



            if (!$from_news)
            {
                $html .= " | $imgeright&nbsp;<a href=\"" . FN_RewriteLink("?mod={$_FN['mod']}") . "\">" . FN_Translate("go to") . " \"{$_FN['sectionvalues']['title']}\"</a><br />";
            }
//toolbar ----<
            $html .= "</div>";
            return $html;
        }

        /**
         *
         * @global array $_FN
         */
        function ConfigurationAdmin()
        {
            global $_FN;
            $opt = FN_GetParam("opt", $_GET, "html"); //if is controlcenter
            $mode = FN_GetParam("mode", $_GET, "html"); //if is controlcenter
            echo $this->HtmlNewsAdminToolbar();
            FN_EditConfFile(_PATH_NEWS_ . "config.php", "?mod={$_FN['mod']}&amp;opt=$opt&amp;mode=$mode");
        }

        /**
         *
         * @global array $_FN
         */
        function NewsAdmin()
        {
            global $_FN;
            $html = "";
            $tablename = $this->config['tablename'];
            $signewsmode = FN_GetParam("signews", $_GET, "html");
            $mode = FN_GetParam("mode", $_GET, "html");
            $opt = FN_GetParam("opt", $_GET, "html"); //if is controlcenter
            $gopt = $sig = "";
            if ($opt != "")
            {
                $gopt = "&amp;opt=$opt";
            }
            if ($signewsmode != "")
            {
                $sig = "&amp;signews=1";
            }
            $imgedit = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/modify.png") . "\" alt=\"\" />";
            $imgedel = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/delete.png") . "\" alt=\"\" />";
            $imgeview = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/news.png") . "\" alt=\"\" />";
            $imgnew = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/add.png") . "\" alt=\"\" />&nbsp;";
            //if (empty($_GET["op___xdb_$tablename"]) || $_GET["op___xdb_$tablename"] == "del")
//---pub news---->
            $pubnewsid = FN_GetParam("pubnewsid", $_POST, "html");
            if ($pubnewsid != "")
            {
                $Tablenews = FN_XMDBForm($tablename);
                $itemnews = $Tablenews->xmltable->GetRecordByPrimarykey($pubnewsid);
                if (!empty($itemnews['unirecid']))
                {
                    $itemnews['status'] = 1;

                    $itemnews = $Tablenews->xmltable->UpdateRecord($itemnews);
                    $this->GenerateRSS();
                    if (!empty($itemnews['status']))
                    {
                        $html .= FN_HtmlAlert(FN_Translate("the data were successfully updated"));
                    }
                    else
                    {
                        $html .= FN_HtmlAlert(FN_Translate("error"));
                    }
                }
            }
//---pub news----<
//hide news---->
            $pubnewsid = FN_GetParam("hidenewsid", $_POST, "html");
            if ($pubnewsid != "")
            {
                $Tablenews = FN_XMDBForm( $tablename);
                $itemnews = $Tablenews->xmltable->GetRecordByPrimarykey($pubnewsid);
                if (!empty($itemnews['unirecid']))
                {
                    $itemnews['status'] = "0";
                    $itemnews = $Tablenews->xmltable->UpdateRecord($itemnews);
                    $this->GenerateRSS();
                    if (isset($itemnews['status']))
                    {
                        $html .= FN_HtmlAlert(FN_Translate("the data were successfully updated"));
                    }
                    else
                    {
                        $html .= FN_HtmlAlert(FN_Translate("error"));
                    }
                }
            }
//hide news----<
            $params = array();
            $link = "opt=opt=fnc_ccnf_section_{$_FN['mod']}&amp;mod={$_FN['mod']}&amp;mode=$mode&amp;$gopt$sig";
            $params['link'] = $link;
//$params['fields'] = "title|status|date|startdate|enddate|username|guestnews|$this->PubNews()";
            $params['fields'] = "title|argument|status|date|username|PubNews()";
            $params['textnew'] = $imgnew . FN_i18n("add news");
            $params['textdelete'] = "<img title=\"" . FN_Translate("delete") . "\" style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/delete.png") . "\" alt=\"\" />";
            $params['textview'] = FN_Translate("preview");
            $params['enableview'] = true;
            $params['textnorecord'] = FN_Translate("no signed news");
            $params['textviewlist'] = "<img style=\"border:0px;vertical-align:middle\" src=\"" . FN_FromTheme("images/left.png") . "\" alt=\"\" />&nbsp;" . FN_Translate("view all");
            $params['defaultorder'] = "date";
            $params['defaultorderdesc'] = true;
            $params['forcevaluesinsert'] = array("status" => "0");
//genera il txtid ---->
            $textid = "";
            if (isset($_POST['title_' . $_FN['lang_default']]))
            {
                $generate_id = true;
                $pkid = FN_GetParam("unirecid", $_POST, "html");
                if ($pkid != "")
                {
                    $Table = FN_XMDBForm($this->config['tablename']);
                    $itemnews = $Table->xmltable->GetRecordByPrimarykey($pkid);
                    if (isset($itemnews['txtid']) && $itemnews['txtid'] != "")
                    {
                        $generate_id = false;
                    }
                }
                if ($generate_id)
                {
                    if (!empty($_POST['title']))//se esiste per la lingua di default
                    {
                        $textid = $this->GenTxtId($_POST['title']);
                    }
                    elseif ($_POST['title_' . $_FN['lang_default']] != "")//se esiste per la lingua di default
                    {
                        $textid = $this->GenTxtId($_POST['title_' . $_FN['lang_default']]);
                    }
                    else
                    {
                        foreach ($_FN['listlanguages'] as $ll)
                        {
                            $idl = "";
                            if ($ll != $_FN['lang_default'])
                            {
                                $idl = "_$ll";
                            }
                            if (isset($_POST['title' . $idl]))
                            {
                                $textid = $this->GenTxtId($_POST['title' . $idl]);
                                break;
                            }
                        }
                    }
                }
                $params['forcevaluesinsert'] = array("username" => $_FN['user'], "txtid" => $textid);
                if (empty($_POST["date"]))
                {
                    $_POST["date"] = FN_Now();
                }
            }
//genera il txtid ----<
            if (isset($_POST['body']))
            {
                $_POST['body'] = $this->MakeNewsRelative($_POST['body']);
            }
            if (isset($_POST['summary']))
            {
                $_POST['summary'] = $this->MakeNewsRelative($_POST['summary']);
            }
            foreach ($_FN['listlanguages'] as $lang)
            {
                if (isset($_POST['body_' . $lang]))
                {
                    $_POST['body_' . $lang] = $this->MakeNewsRelative($_POST['body_' . $lang]);
                }
                if (isset($_POST['summary_' . $lang]))
                {
                    $_POST['summary_' . $lang] = $this->MakeNewsRelative($_POST['summary_' . $lang]);
                }
            }
            //if (empty($_GET["op___xdb_{$this->tablename}"]) || $_GET["op___xdb_{$this->tablename}"] == "del")
            {
                if ($signewsmode != "")
                {
                    $params['restr'] = array("status" => "0", "guestnews" => 1);
                    $html .= "<h3>" . FN_Translate("signed news") . ":</h3>";
                }
                else
                {
                    $html .= "<h3>" . FN_Translate("list of news") . ":</h3>";
                }
            }
            $params['layout'] = "flat";
            $html .= "<div class=\"news_editor\">";
            ob_start();
            if (basename($_FN['self']) == "controlcenter.php")
            {
                FNCC_XmlTableEditor($tablename, $params);
            }
            else
            {


                FN_XMETATableEditor($tablename, $params);
            }
            $html .= ob_get_clean();
            $html .= "</div>";
//generate RSS----->
            if (count($_POST) > 0)
            {
                $this->GenerateRSS();
                if ($this->config['generate_googlesitemap'])
                {
                    $this->CreateGoogleSitemap();
                }
                $this->ClearCache();
            }
//generate RSS-----<
            $htmltoolbar = $this->HtmlNewsAdminToolbar();
            echo $htmltoolbar . $html;
        }

        /**
         * make
         * @global array $_FN
         * @param string $str
         * @return string
         */
        function MakeNewsRelative($str)
        {
            global $_FN;
            $str = str_replace("=\"{$_FN['siteurl']}", "=\"", $str);
            $str = str_replace("='{$_FN['siteurl']}", "='", $str);
            $str = str_replace("url('{$_FN['siteurl']}", "url('", $str);
            $str = str_replace("url(\"{$_FN['siteurl']}", "url(\"", $str);
            $str = str_replace("url({$_FN['siteurl']}", "url(", $str);
            $str = str_replace("url(&quot;{$_FN['siteurl']}", "url(&quot;", $str);
//se il post aggiunge gli '\'
            $str = str_replace("=\\\"{$_FN['siteurl']}", "=\\\"", $str);
            $str = str_replace("=\\'{$_FN['siteurl']}", "=\\'", $str);
            $str = str_replace("url(\\'{$_FN['siteurl']}", "url(\\'", $str);
            $str = str_replace("url(\\\"{$_FN['siteurl']}", "url(\\\"", $str);
            $str = str_replace("<br>", "<br />", $str);
//dprint_xml($str);
            return $str;
        }
    }

}


