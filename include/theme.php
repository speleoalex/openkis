<?php

/**
 * @package Flatnux_functions
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
if (!function_exists("FN_HtmlNext"))
{

    /**
     * Open Table
     * @return string
     */
    function FN_HtmlNext($title="")
    {
        return "<img title=\"$title\" style=\"vertical-align:middle\" src=\"".FN_FromTheme("images/right.png")."\" alt=\"$title\" />";
    }

}
if (!function_exists("FN_HtmlOpenTable"))
{

    /**
     * Open Table
     * @return string
     */
    function FN_HtmlOpenTable()
    {
        return "<table  width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" ><tr><td valign=\"top\">";
    }

}
if (!function_exists("FN_HtmlCloseTable"))
{

    /**
     * Close Table
     * @return string
     */
    function FN_HtmlCloseTable()
    {
        return "</td></tr></table>";
    }

}
if (!function_exists("FN_HtmlOpenTableTitle"))
{

    /**
     * Open Table
     * @return string
     */
    function FN_HtmlOpenTableTitle($title)
    {
        return "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"><tr>\n<td valign=\"top\">$title</td></tr>\n<tr><td>";
    }

}
if (!function_exists("FN_HtmlCloseTableTitle"))
{

    /**
     * Close Table
     * @return string
     */
    function FN_HtmlCloseTableTitle($title="")
    {
        return "</td></tr></table>\n";
    }

}
if (!function_exists("FN_HtmlHeader"))
{

    /**
     *
     * @global array $_FN
     * @param bool $tags
     * @return string 
     */
    function FN_HtmlHeader($include_theme_css=true,$include_section_css=true)
    {
        global $_FN;
        $html="";
        $sectionvalues=FN_GetSectionValues($_FN['mod']);
        ob_start();
        if (!empty($sectionvalues['type']) && file_exists("modules/{$sectionvalues['type']}/header.php"))
        {
            require_once "modules/{$sectionvalues['type']}/header.php";
        }
        if (file_exists("sections/{$_FN['mod']}/header.php"))
        {
            require_once "sections/{$_FN['mod']}/header.php";
        }
        if (!empty($_FN['section_header']))
        {
            $html.=$_FN['section_header'];
        }
        $html.=trim(ltrim(ob_get_clean()));

        //$html.="\n\t<title>{$_FN['site_title']}</title>";
        $html.=FN_IncludeCSS($include_theme_css,$include_section_css);
        $html.=FN_IncludeJS();
        $html.="\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".$_FN['charset_page']."\" />";
        $html.="\n\t<meta name=\"KEYWORDS\" content=\"{$_FN['keywords']}\"  />";
        $html.="\n\t<meta http-equiv=\"EXPIRES\" content=\"0\" />";
        $html.="\t<meta name=\"REVISIT-AFTER\" content=\"1 DAYS\" />\n";
        $html.="\t<script type=\"text/javascript\">
\t//<!--
\tcheck = function (url)
\t{
\t\tif(confirm (\"".FN_i18n("are you sure you want to do it?")."\"))
\t\t\twindow.location=url;
\t}
\t// -->
\t</script>";
        if (!empty($_FN['section_header_footer']))
        {
            $html.=$_FN['section_header_footer'];
        }

        return $html;
    }

}
if (!function_exists("FN_HtmlBlocks"))
{

    /**
     *
     * @global array $_FN
     * @param string $where
     * @return string
     */
    function FN_HtmlBlocks($where)
    {
        global $_FN;
        $ret="";
        $blocks=FN_GetBlocks($where);
        foreach($blocks as $block)
        {
            $html=FN_HtmlBlock($block['id']);
            if ($html!= "")
            {
                $ret.=FN_HtmlOpenBlock($block['title']);
                $ret.=$html;
                $ret.=FN_HtmlCloseBlock();
            }
        }
        return $ret;
    }

}

if (!function_exists("FN_HtmlOpenBlock"))
{

    /**
     * Open block
     * @return string
     */
    function FN_HtmlOpenBlock($title)
    {
        $html="<div class=\"block\">";
        if ($title!== "")
            $html.="<div class=\"blocktitle\">$title</div>";
        $html.="<div class=\"blockcontents\">";
        return $html;
    }

}
if (!function_exists("FN_HtmlCloseBlock"))
{

    /**
     * default close block
     * @param string $title
     * @return string
     */
    function FN_HtmlCloseBlock($title="")
    {
        return "</div></div>\n";
    }

}

if (!function_exists("FN_HtmlMenu"))
{

    /**
     *
     * @param string $separator
     * @param string $sectionroot
     * @return string
     */
    function FN_HtmlMenu($separator="&nbsp;|&nbsp;",$sectionroot=false)
    {
        $menu=array();
        $sections=FN_GetSections($sectionroot);
        foreach($sections as $section)
        {
            $accesskey=FN_GetAccessKey($section['title'],"index.php?mod={$section['id']}",$section['accesskey']);
            if ($accesskey!= "")
                $accesskey=" accesskey=\"$accesskey\"";
            $menu[]="<a $accesskey href=\"{$section['link']}\">{$section['title']}</a>";
        }
        $ret=implode($separator,$menu);
        return $ret;
    }

}

if (!function_exists("FN_HtmlSectionMenu"))
{

    /**
     *
     * @param string $separator
     * @param string $sectionroot
     * @return string
     */
    function FN_HtmlSectionMenu($sectionroot=false)
    {
        $menu=array();
        $menu=FN_GetSections($sectionroot);
        if (count($menu)== 0)
            return "";
        $ret="<ul>\n";
        foreach($menu as $item)
        {
            $ret.=FN_HtmlSectionMenuItem($item);
        }
        $ret.="</ul>";
        return $ret;
    }

}
if (!function_exists("FN_HtmlSectionMenuItem"))
{

    /**
     *
     * @param string $separator
     * @param string $sectionroot
     * @return string
     */
    function FN_HtmlSectionMenuItem($menuitem)
    {
        $ret="";
        $title=$menuitem['title'];
        $accesskey=FN_GetAccessKey($title,"index.php?mod={$menuitem['id']}",$menuitem['accesskey']);
        if ($accesskey!= "")
            $accesskey=" accesskey=\"$accesskey\"";
        $ret.="<li><a $accesskey href=\"{$menuitem['link']}\">{$menuitem['title']}</a></li>";
        return $ret;
    }

}

/**
 *
 * @global array $_FN
 * @staticvar int $lev
 * @staticvar string $html
 * @param string $parent
 * @return string
 */
function FN_HtmlMenuTree($parent="",$recursive=true)
{
    global $_FN;
    static $lev=0;
    $html="";
    $ret=array();
    $current=$_FN['mod'];
    $sections=FN_GetSections($parent);
    if (empty($sections) || count($sections)== 0)
        return "";
    foreach($sections as $section)
    {
        $html.="<span style=\"white-space: nowrap\">";
        for($i=0; $i < $lev; $i++)
        {
            $html.="&nbsp;&nbsp;&nbsp;&nbsp;";
        }

        $accesskey=FN_GetAccessKey($section['title'],"index.php?mod={$section['id']}");
        if ($accesskey!= "")
            $accesskey=" accesskey=\"$accesskey\"";
        $title=(empty($section['description'])) ? "" : "title=\"{$section['description']}\"";
        if ($current== $section['id'])
        {
            $html.="<a $title $accesskey href=\"".fn_rewritelink("index.php?mod={$section['id']}")."\">".$section['title']."</a>";
        }
        else
        {
            $html.="<a $title $accesskey href=\"".fn_rewritelink("index.php?mod={$section['id']}")."\">".$section['title']."</a>";
        }
        $html.="</span><br />";
        $lev++;
        if ($recursive)
            $html.=FN_HtmlMenuTree($section['id']);
        $lev--;
    }
    return $html;
}

if (!function_exists("FN_HtmlMenuTreeUl"))
{

    /**
     *
     * @global array $_FN
     * @staticvar int $lev
     * @staticvar string $html
     * @param string $parent
     * @return string
     */
    function FN_HtmlMenuTreeUl($parent="",$recursive=true)
    {
        global $_FN;
        static $lev=0;
        $html="";
        $ret=array();
        $current=$_FN['mod'];
        $sections=FN_GetSections($parent);
        if (empty($sections) || count($sections)== 0)
            return "";
        $html.="<ul>\n";
        foreach($sections as $section)
        {
            $html.="<li>";
            $accesskey=FN_GetAccessKey($section['title'],"index.php?mod={$section['id']}");
            if ($accesskey!= "")
                $accesskey=" accesskey=\"$accesskey\"";
            $title=(empty($section['description'])) ? "" : "title=\"{$section['description']}\"";
            if ($current== $section['id'])
            {
                $html.="<a $title $accesskey href=\"".fn_rewritelink("index.php?mod={$section['id']}")."\">".$section['title']."</a>";
            }
            else
            {
                $html.="<a $title $accesskey href=\"".fn_rewritelink("index.php?mod={$section['id']}")."\">".$section['title']."</a>";
            }
            $lev++;
            if ($recursive)
                $html.=FN_HtmlMenuTreeUl($section['id']);
            $html.="</li>";
            $lev--;
        }
        $html.="\n</ul>";
        return $html;
    }

}


if (!function_exists("FN_HtmlCredits"))
{

    /**
     *
     * @return string 
     */
    function FN_HtmlCredits()
    {
        global $_FN;
        if (!isset($_FN['credits']))
        {
            $html="Powered by <a href=\"http://www.flatnux.org\">Flatnux</a>";
        }
        else
        {
            $html=$_FN['credits'];
        }
        return $html;
    }

}
/* * *********************************TEMPLATE********************************** */
/* * *********************************TEMPLATE********************************** */
/* * *********************************TEMPLATE********************************** */
/* * *********************************TEMPLATE********************************** */

/**
 *
 * @global array $_FN
 * @param string $templatefile
 * @return type 
 */
function FN_TPL_html_MakeThemeFromTemplate($templatefile)
{
    global $_FN;
    $conf=FN_LoadConfig("themes/{$_FN['theme']}/config.php");
    $header=FN_HtmlHeader(false);
    $vars=array();

    //replace all {var key}
    $vars['lang']=$_FN['lang'];
    $vars['fnblocksright']=FN_HtmlBlocks("right");
    $vars['fnblocksleft']=FN_HtmlBlocks("left");
    $vars['site_title']=$_FN['site_title'];
    $vars['sitename']=$_FN['sitename'];
    $vars['keywords']=$_FN['keywords'];
    $vars['site_subtitle']=$_FN['site_subtitle'];
    $vars['siteurl']=$_FN['siteurl'];
    $vars['sitepath']=$_FN['sitepath'];

    $vars['credits']=FN_HtmlCredits();
    $vars['navbar']=FN_HtmlNavbar();
    $vars['section_title']=$_FN['sectionvalues']['title'];
    $vars['section_description']=$_FN['sectionvalues']['description'];
    $vars['rss_link']=isset($_FN['rss_link']) ? $_FN['rss_link'] : "#";
    $vars['hmenu']=FN_TPL_tp_create_hmenu();
    $vars['languages']=FN_HtmlLanguages();


    //---------import generic html file---------------------------------------->
    $tplstring=file_get_contents($templatefile);
    $tplstring=preg_replace('/<title>[^<]*<\/title>/is',"<title>{site_title}</title>",$tplstring);
    $tplstring=preg_replace('/href="index.html"/is','href="{siteurl}"',$tplstring);
    $tplstring=preg_replace('/href=\'index.html\'/is','href=\'{siteurl}\'',$tplstring);
    $tplstring=preg_replace('/href="([A-Z0-9_]+).html"/is','href="{siteurl}index.php?mod=$1"',$tplstring);
    $tplstring=preg_replace('/ charset="UTF-8"/is',' charset="{charset_page}"',$tplstring);
    //---------import generic html file----------------------------------------<




    $html=FN_TPL_include_tpl(FN_TPL_ApplyTplString($tplstring,$vars,dirname($templatefile)."/"),$vars);

    foreach($vars as $key=> $value)
    {
        if (!is_array($value) || is_numeric($value))
        {
            $html=str_replace("{".$key."}",htmlspecialchars("{".$key."}"),$html);
            $html=str_replace("{".$key."}",FN_TPL_encode($value),$html);
        }
    }
    $html=preg_replace('/<title>[^<]*<\/title>/is',"<title>{$_FN['site_title']}</title>",$html);
//    $html=str_replace("</head>",$header."</head>",$html);
    $html=implode($header."</head>",explode("</head>",$html,2));

    return FN_TPL_decode($html);
}

global $tpl_skeep;
/**
 * 
 * @global string $tpl_skeep
 * @param type $str
 * @return type
 */
function FN_TPL_encode($str)
{
    global $tpl_skeep;
    if (!$tpl_skeep)
        $tpl_skeep="__skeep___graph_";
    $str=str_replace("{",$tpl_skeep,$str);
    return $str;
}
/**
 * 
 * @global string $tpl_skeep
 * @param type $str
 * @return type
 */
function FN_TPL_decode($str)
{
    global $tpl_skeep;
    $str=str_replace($tpl_skeep,"{",$str);
    return $str;
}

/**
 *
 * @return string
 */
function FN_TPL_tp_create_section()
{
    global $_FN;
    $config=FN_LoadConfig("themes/{$_FN['theme']}/config.php");
    $page_title=$_FN['sectionvalues']['title'];
    $htmlsection=FN_TPL_encode(FN_HtmlSection());
    if (isset($config['show_page_title']))
    {
        if ($config['show_page_title']== 0)
        {
            $page_title=false;
        }
        if (!empty($config['hide_title_in_main_page']) && $_FN['mod']== $_FN['home_section'])
        {
            $page_title=false;
        }
    }
    if (function_exists("FN_HtmlOpenSection") && function_exists("FN_HtmlCloseSection"))
    {
        $htmlsection=FN_HtmlOpenSection($page_title).$htmlsection.FN_HtmlCloseSection($page_title);
    }

    return $htmlsection;
}

/**
 *
 * @param string $str
 * @param array $vars
 * @return string
 */
function FN_TPL_include_tpl($str,$vars)
{
    $strout=$str;
    $array=preg_match_all('/<!-- include ([\w]+) -->(.*?)(<!-- end include (\\1) -->)/s',$str,$out);
    if (is_array($out[0]))
        foreach($out as $k=> $v)
        {
            if (is_array($v))
            {
                foreach($v as $toreplace)
                {
                    //dprint_r($toreplace);
                    $tpname=explode("-->",$toreplace);
                    $tpname=str_replace("<!-- include ","",$tpname[0]);
                    $tpname=trim(ltrim($tpname));
                    if (function_exists("FN_TPL_tp_create_".$tpname))
                    {
                        $fname="FN_TPL_tp_create_".$tpname;
                        $replace=$fname($toreplace);
                        $strout=str_replace($toreplace,$replace,$strout);
                    }
                    if (function_exists($tpname) && preg_match("/^FN_Html/is",$tpname))
                    {
                        $fname=$tpname;
                        $replace=$fname($toreplace);
                        $strout=str_replace($toreplace,$replace,$strout);
                    }
                }
            }
            break;
        }
    //   die();
    return $strout;
}

/**
 *
 * @param string $tplname
 * @param array $vars
 * @return string
 */
function FN_TPL_ApplyTplFile($tplname,$vars)
{
    global $_FN;
    $str="";
    if (file_exists($tplname))
        $str=file_get_contents($tplname);
    $basepath=dirname($tplname)."/";
    return FN_TPL_ApplyTplString($str,$vars,$basepath);
}

/**
 *
 * @global array $_FN
 * @param string $str
 * @param array $vars
 * @param string $basepath
 * @return string 
 */
function FN_TPL_ApplyTplString($str,$vars,$basepath=false)
{

    global $_FN;
    static $recursion=0;
    $use_cache = $_FN['use_cache'];
    //$use_cache=false;
    $recursion++;
    if ($recursion > 5)
    {
        $recursion--;
        return $str;
    }
    $arrayvars=array();
    $match="";
    if (preg_match_all('/\{([a-zA-Z0-9_&]+)\}/m',$str,$match))
    {
        foreach($match[1] as $tplvar)
        {
//            $arrayvars[$tplvar]=null;
            $tplvar=str_replace("_&","",$tplvar);
            $arrayvars[$tplvar]=null;
        }
    }
    $section=FN_GetSectionValues($_FN['mod']);
    $uservalues=FN_GetUser($_FN['user']);
    if ($recursion== 1)
    {
        foreach($arrayvars as $k=> $v)
        {
            if (isset($_FN[$k]))
                $arrayvars[$k]=$_FN[$k];
            if (false!== strstr($k,"section_"))
            {
                $key_section=str_replace("section_","",$k);
                if (isset($section[$key_section]))
                {
                    $arrayvars["section_".$k]=$section[$key_section];
                }
            }
        }
    }
    foreach($arrayvars as $k=> $v)
    {
        if (false!== strstr($k,"section_"))
        {
            $key_section=str_replace("section_","",$k);
            if (isset($section[$key_section]))
            {
                $arrayvars["section_".$k]=$section[$key_section];
            }
        }
        if (isset($vars[$k]))
        {
            $arrayvars[$k]=$vars[$k];
        }
        if (false!== strstr($k,"user_"))
        {
            $key_user=str_replace("user_","",$k);
            if (isset($uservalues[$key_user]))
            {
                if ($k!= "passwd" && $k!= "password")
                {
                    $arrayvars["user_".$k]=$uservalues[$key_user];
                }
            }
        }
    }
    if (isset($arrayvars['url_avatar']))
    {
        $arrayvars['url_avatar']=FN_GetUserImage($_FN['user']);
    }
    else{
        $arrayvars['url_avatar']="{$_FN['siteurl']}/images/user.png";
    }
    if ($use_cache)
    {
        $idcache=md5(serialize($vars)).md5(serialize($arrayvars)).md5(serialize($str)).$recursion.$_FN['use_urlserverpath']."_".$_FN['lang'];
        $cache=FN_GetGlobalVarValue($idcache);
        if ($cache!== null)
        {
            /*
              dprint_r($idcache,"","blue");
              dprint_r($arrayvars,"","blue");
              dprint_xml($cache,"","blue");
              @ob_end_flush(); */
            $recursion--;
            return $cache;
        }
    }
    $old="";
    {
        $str=str_replace("href='#","ferh='#",$str);
        $str=str_replace("href=\"#","ferh=\"#",$str);
        $str=str_replace("href=\"//","ferh=\"//",$str);
        $str=str_replace("href='//","ferh='//",$str);
        $str=str_replace("src='#","rcs='#",$str);
        $str=str_replace("src=\"#","rcs=\"#",$str);
        $str=str_replace("src=\"//","rcs=\"//",$str);
        $str=str_replace("src='//","rcs='//",$str);
        $siteurl=$_FN['siteurl'];
        if (!empty($_FN['use_urlserverpath']))
            $siteurl="http://____replace____/";
        if ($basepath)
        {
            if ($_FN['enable_mod_rewrite'] > 0 && $_FN['links_mode']== "html")
            {
                if ($_FN['lang']== $_FN['lang_default'])
                {
                    $str=preg_replace("/(href=\"index.php\?mod=)([A-Z0-9_]+)\"/is","href=\"{$siteurl}\$2.html\"",$str);
                    $str=preg_replace("/(href='index.php\?mod=)([A-Z0-9_]+)'/is","href=\"{$siteurl}\$2.html\"",$str);
                }
                else
                {
                    $str=preg_replace("/(href=\"index.php\?mod=)([A-Z0-9_]+)\"/is","href=\"{$siteurl}\$2.{$_FN['lang']}.html\"",$str);
                    $str=preg_replace("/(href='index.php\?mod=)([A-Z0-9_]+)'/is","href=\"{$siteurl}\$2.{$_FN['lang']}.html\"",$str);
                }
            }
            while($old!= $str)
            {
                $old=$str;
                $str=preg_replace("/<([^>]+)( background| href| src)=(\")([^:^{]*)(\")/im","<\\1\\2=\\3{$siteurl}$basepath\\4\\3",$str);
                $str=preg_replace("/<([^>]+)( background| href| src)=(\')([^:^{]*)(\')/im","<\\1\\2=\\3{$siteurl}$basepath\\4\\3",$str);
                $str=preg_replace('#<([^>]+)(url\(\'(?!http))#','<$1$2$3'.$siteurl.$basepath.'',$str);
            }
        }
        $str=str_replace("ferh=\"","href=\"",$str);
        $str=str_replace("ferh='","href='",$str);
        $str=str_replace("rcs=\"","src=\"",$str);
        $str=str_replace("rcs='","src='",$str);
    }
    $strout=$str;
    $listparams="<pre>";
    foreach($arrayvars as $key=> $value)
    {
        $strout=str_replace("<!-- if {".$key."}","<!-- if {_&".$key."}",$strout);
        $strout=str_replace("<!-- end if {".$key."}","<!-- end if {_&".$key."}",$strout);
        $strout=str_replace("<!-- if not {".$key."}","<!-- if not {_&".$key."}",$strout);
        $strout=str_replace("<!-- end if not {".$key."}","<!-- end if not {_&".$key."}",$strout);
        $strout=str_replace("<!-- foreach {".$key."}","<!-- foreach {_&".$key."}",$strout);
        $strout=str_replace("<!-- end foreach {".$key."}","<!-- end foreach {_&".$key."}",$strout);
    }
    foreach($arrayvars as $key=> $value)
    {
        if (is_array($value))
        {
            //array   --->
            $html_template_array_items=FN_TPL_GetHtmlParts("foreach {_&".$key."}",$strout);
            foreach($html_template_array_items as $html_template_array)
            {
                if ($html_template_array)
                {
                    $html_array="";
                    if (is_array($value))
                    {
                        foreach($value as $item)
                        {
                            $html_array.=FN_TPL_ApplyTplString($html_template_array,$item,$basepath);
                        }
                    }
                    $strout=str_replace($html_template_array,$html_array,$strout);
                }
            }
            //array   ---<
        }
    }
    foreach($arrayvars as $key=> $value)
    {
        //if----
        $html_template_if_items=FN_TPL_GetHtmlParts("if {_&".$key."}",$strout);
        if ($html_template_if_items)
        {
            foreach($html_template_if_items as $html_template_if)
            {
                $html_array="";
                if ($value)
                {
                    if (is_array($value))
                    {
                        //$value[$key]=$value;
                        $html_template_if=preg_replace("/^<!-- if {_&".$key."} -->/","",$html_template_if);
                        $html_template_if=preg_replace("/<!-- end if {_&".$key."} -->\$/","",$html_template_if);
                        /* dprint_xml($html_template_if,"","orange");
                          dprint_r($value,"","orange"); */
                        $html_array=FN_TPL_ApplyTplString($html_template_if,$value,$basepath);
                    }
                    else
                    {
                        $html_array=FN_TPL_ApplyTplString($html_template_if,array("$key"=>$value),$basepath);
                    }
                }
                $strout=FN_TPL_ReplaceHtmlPart("if {_&".$key."}",$html_array,$strout);
            }
        }
        //end if---
        //if not----
        $html_template_if=FN_TPL_GetHtmlPart("if not {_&".$key."}",$strout);
        if ($html_template_if)
        {
            $html_array="";
            if ($value)
            {
                $strout=FN_TPL_ReplaceHtmlPart("if not {_&".$key."}",$html_array,$strout);
            }
        }
        //end if not---        
    }
    foreach($arrayvars as $key=> $value)
    {
        if ($value!== null && (is_string($value) || is_numeric($value) ))
        {

            $listparams.="$key = ".htmlentities($value)."\n";
            $strout=str_replace("_startvar_".$key."_endvar_","{".$key."}",$strout);
            $strout=str_replace("{".$key."}",FN_TPL_encode($value),$strout);
        }
    }
    $listparams.="</pre>";
    $strout=str_replace("{listvars}",$listparams,$strout);
    $i18n=array();
    preg_match_all("/{i18n:([^\}]+)}/",$strout,$i18n);
    if (isset($i18n[1]))
    {
        foreach($i18n[1] as $i18n_item)
        {
            $mode="";
            $i18n_item_tmp=str_replace("?","",$i18n_item);
            if (preg_match("/^[A-Z]/s",$i18n_item) && preg_match("/[a-z]$/s",$i18n_item_tmp))
            {
                $mode="Aa";
            }
            elseif (preg_match("/^[a-z]/s",$i18n_item) && preg_match("/[a-z]$/s",$i18n_item_tmp))
            {
                $mode="aa";
            }
            elseif (preg_match("/^[A-A]/s",$i18n_item) && preg_match("/[A-Z]$/s",$i18n_item_tmp))
            {
                $mode="AA";
            }
            $strout=str_replace("{i18n:$i18n_item}",FN_Translate(strtolower("$i18n_item"),$mode),$strout);
        }
    }

    if ($recursion== 1)
    {
        if (!empty($_FN['use_urlserverpath']))
            $strout=str_replace($siteurl,$_FN['sitepath'],$strout);
    }

    foreach($arrayvars as $ks=> $kv)
    {
        if ($recursion== 1)
        {

            $strout=str_replace("<!-- if {_&".$ks."} -->","",$strout);
            $strout=str_replace("<!-- end if {_&".$ks."} -->","",$strout);
            $strout=str_replace("<!-- if not {_&".$ks."} -->","",$strout);
            $strout=str_replace("<!-- end if not {_&".$ks."} -->","",$strout);
            $strout=str_replace("<!-- foreach {_&".$ks."} -->","",$strout);
            $strout=str_replace("<!-- end foreach {_&".$ks."} -->","",$strout);
        }
        else
        {
            $strout=str_replace("<!-- if {_&".$ks."} -->","<!-- if {".$ks."} -->",$strout);
            $strout=str_replace("<!-- end if {_&".$ks."} -->","<!-- end if {".$ks."} -->",$strout);
            $strout=str_replace("<!-- if not {_&".$ks."} -->","<!-- if not {".$ks."} -->",$strout);
            $strout=str_replace("<!-- end if not {_&".$ks."} -->","<!-- end if not {".$ks."} -->",$strout);
            $strout=str_replace("<!-- foreach {_&".$ks."} -->","<!-- foreach {".$ks."} -->",$strout);
            $strout=str_replace("<!-- end foreach {_&".$ks."} -->","<!-- end foreach {".$ks."} -->",$strout);
        }
    }

    $ret=FN_TPL_decode($strout);

    if ($use_cache)
    {
        FN_SetGlobalVarValue($idcache,$ret);
        /*
          dprint_r($idcache,"","red");
          dprint_r($arrayvars,"","red");
          dprint_xml($ret,"","red");
          @ob_end_flush(); */
    }
    $recursion--;
    return $ret;
}

/**
 *
 * @return string
 */
function FN_TPL_tp_create_hmenu($str="&nbsp;|&nbsp;")
{
    return FN_HtmlMenu($str);
}

/**
 * find <!-- $partname -->(.*)<!-- end$partname -->
 * 
 * @param type $partname
 * @param type $tp_str
 * @param type $default
 * @return type
 */
function FN_TPL_GetHtmlPart($partname,$tp_str,$default="")
{
    $out=array();
    if (preg_match("/<!-- $partname -->.*<!-- $partname -->/s",$tp_str))//se il nome del nodo contiene un elemento con lo stesso nome
    {
        $tmp=explode("<!-- $partname -->",$tp_str);
        //dprint_xml($tmp);
        $tmp=$tmp[1];
        if (false!== strpos($tmp,"<!-- end $partname -->"))
            $tmp=explode("<!-- end $partname -->",$tmp);
        elseif (false!== strpos($tmp,"<!-- end$partname -->"))
            $tmp=explode("<!-- end$partname -->",$tmp);
        if (is_array($tmp))
        {
            $tmp=$tmp[0];
            $tp_str="<!-- $partname -->".$tmp."<!-- end $partname -->";
            return $tp_str;
        }
    }
    preg_match("/<!-- $partname -->(.*)<!-- end$partname -->/is",$tp_str,$out) || preg_match("/<!-- $partname -->(.*)<!-- end $partname -->/is",$tp_str,$out);
    $tp_str=empty($out[0]) ? $default : $out[0];
    return $tp_str;
}

/**
 * 
 * @staticvar array $cache
 * @param type $partname
 * @param type $tp_str
 * @param type $default
 * @return string
 */
function FN_TPL_GetHtmlParts($partname,$tp_str,$default="")
{
    global $_FN;
    static $cache=array();
    $md5=md5($partname.$tp_str.$default);
    if (isset($cache[$md5]))
    {
        //dprint_r("cache $partname");
        return $cache[$md5];
    }
    if ($_FN['use_cache'])
    {
        if (($cache[$md5]=FN_GetGlobalVarValue($md5))!== null)
        {
            return $cache[$md5];
        }
        else
        {
            unset($cache[$md5]);
        }
    }

    $out=array();
    $ret=false;
    if (preg_match("/<!-- $partname -->.*<!-- $partname -->/s",$tp_str))//se il nome del nodo contiene un elemento con lo stesso nome
    {
        $tmp=explode("<!-- $partname -->",$tp_str);
        //dprint_xml($tmp);
        $i=1;
        while(isset($tmp[$i]))
        {
            $tmp2=$tmp[$i];
            if (false!== strpos($tmp2,"<!-- end $partname -->"))
                $tmp2=explode("<!-- end $partname -->",$tmp2);
            elseif (false!== strpos($tmp2,"<!-- end$partname -->"))
                $tmp2=explode("<!-- end$partname -->",$tmp2);
            if (is_array($tmp2))
            {
                $tmp2=$tmp2[0];
                $tp_str="<!-- $partname -->".$tmp2."<!-- end $partname -->";
                $ret[]=$tp_str;
            }
            $i++;
        }
        $cache[$md5]=$ret;
        if ($_FN['use_cache'])
        {
            FN_SetGlobalVarValue($md5,$cache[$md5]);
        }
        return $ret;
    }
    preg_match("/<!-- $partname -->(.*)<!-- end$partname -->/is",$tp_str,$out) || preg_match("/<!-- $partname -->(.*)<!-- end $partname -->/is",$tp_str,$out);
    $tp_str=empty($out[0]) ? $default : $out[0];
    if ($tp_str)
    {

        $cache[$md5]=array(0=>$tp_str);
        return array(0=>$tp_str);
    }
    $cache[$md5]=array();
    return array();
}

/**
 * 
 * @param type $partname
 * @param type $replace
 * @param type $tp_str
 * @param type $default
 * @return type
 */
function FN_TPL_ReplaceHtmlPart($partname,$replace,$tp_str,$default="")
{
    $tp_str_tmp=FN_TPL_GetHtmlPart($partname,$tp_str,$default);
    $str_out=str_replace($tp_str_tmp,$replace,$tp_str);
    return $str_out;
}

/**
 * 
 * @param string $str
 * @return string
 */
function FN_TPL_tp_create_topmenu($str="")
{
    return FN_TPL_html_menu($str,"top");
}

/**
 * 
 * @param string $str
 * @return string
 */
function FN_TPL_tp_create_menu($str="")
{
    return FN_TPL_html_menu($str,"vertical");
}

/**
 * 
 * @global array $_FN
 * @staticvar boolean $sections
 * @param type $str
 * @param type $part
 * @param type $parent
 * @return string
 */
function FN_TPL_html_menu($str="",$part,$parent=false)
{
    global $_FN;
    static $sections=false;
    $config=FN_LoadConfig("themes/{$_FN['theme']}/config.php");
    if (isset($config['show_'.$part.'_menu']) && $config['show_'.$part.'_menu']== 0)
        return "";
    if ($str== "")
        return "";
    $tp_menuitem['default']=FN_TPL_GetHtmlPart("menuitem",$str,"<a href=\"link\">title</a><br />");
    $tp_menuitem['active']=FN_TPL_GetHtmlPart("menuitemactive",$str,$tp_menuitem['default']);
    $tp_menuitem['dropdown']=FN_TPL_GetHtmlPart("menuitemdropdown",$str);
    $tp_menuitem['dropdownactive']=FN_TPL_GetHtmlPart("menuitemdropdownactive",$str);
    foreach($tp_menuitem as $k=> $v)
    {
        $tp_menuitem[$k]=preg_replace("/href=\"javascript:/im","ferh=\"javascript:",$tp_menuitem[$k]);
        $tp_menuitem[$k]=preg_replace("/href='javascript:/im","ferh='javascript:",$tp_menuitem[$k]);

        $tp_menuitem[$k]=preg_replace("/<a([^>]+)(href)=(\")([^\"]*)(\")/im","<a\\1\\2=\\3{link}\\3",$tp_menuitem[$k]);
        $tp_menuitem[$k]=preg_replace("/<a([^>]+)(href)=(\')([^\']*)(\')/im","<a\\1\\2=\\3{link}\\3",$tp_menuitem[$k]);

        $tp_menuitem[$k]=preg_replace("/ferh=\"javascript:/im","href=\"javascript:",$tp_menuitem[$k]);
        $tp_menuitem[$k]=preg_replace("/ferh='javascript:/im","href='javascript:",$tp_menuitem[$k]);

        if (strpos($tp_menuitem[$k],'{title}')=== false)
        {
            $tp_menuitem[$k]=preg_replace("/(<a.*>)(.*)(<\/a)/im","\\1{title}\\3",$tp_menuitem[$k]);
        }
        if (false== strpos($tp_menuitem[$k],"title="))
        {
            $tp_menuitem[$k]=str_replace("<a","<a title=\"{description}\" ",$tp_menuitem[$k]);
        }
        //add accesskey
        if (false== strpos($tp_menuitem[$k],"{accesskey"))
        {
            $tp_menuitem[$k]=str_replace("<a","<a accesskey=\"{accesskey}\" ",$tp_menuitem[$k]);
        }
    }

    $htmlout="";
    $sectionradix="";
    if (!empty($config[$part.'_menu_parent']))
    {
        if ($config[$part.'_menu_parent']== "__submenu__")
            $sectionradix=$_FN['mod'];
        else
            $sectionradix=$config[$part.'_menu_parent'];
    }
    if ($parent)
    {
        $sectionradix=$parent;
    }
//dprint_r($sectionradix);
    if (empty($sections[$sectionradix]))
        $sections[$sectionradix]=FN_GetSections($sectionradix);


    foreach($sections[$sectionradix] as $sectionvalues)
    {
        $sectionvalues['accesskey']=FN_GetAccessKey($sectionvalues['title'],"index.php?mod={$sectionvalues['id']}");
        if ($tp_menuitem['dropdownactive']!= "" && FN_GetSections($sectionvalues['id']) && (FN_SectionIsInsideThis($sectionvalues['id']) || $_FN['mod']== $sectionvalues['id'] )) //if have childs and active
        {
            $htmlmenuitem=FN_TPL_ApplyTplString($tp_menuitem['dropdownactive'],$sectionvalues,false);
            $tp_submenuitem_ori_template=FN_TPL_GetHtmlPart("submenu",$tp_menuitem['dropdownactive']);
        }
        elseif ($tp_menuitem['dropdown']!= "" && FN_GetSections($sectionvalues['id'])) //if have childs
        {
            $htmlmenuitem=FN_TPL_ApplyTplString($tp_menuitem['dropdown'],$sectionvalues,false);
            $tp_submenuitem_ori_template=FN_TPL_GetHtmlPart("submenu",$tp_menuitem['dropdown']);
        }
        elseif ($_FN['mod']== $sectionvalues['id'] || FN_SectionIsInsideThis($sectionvalues['id']))
        {
            $htmlmenuitem=FN_TPL_ApplyTplString($tp_menuitem['active'],$sectionvalues,false);
            $tp_submenuitem_ori_template=FN_TPL_GetHtmlPart("submenu",$tp_menuitem['active']);
        }
        else
        {
            $htmlmenuitem=FN_TPL_ApplyTplString($tp_menuitem['default'],$sectionvalues,false);
            $tp_submenuitem_ori_template=FN_TPL_GetHtmlPart("submenu",$tp_menuitem['default']);
        }
        $tp_submenuitem_ori=FN_TPL_GetHtmlPart("submenu",$htmlmenuitem);
        $tp_submenuitem_new=$tp_submenuitem_ori;
        $print_submenu=false;
        if (isset($config['make_'.$part.'_menu_recursive']))
        {
            if ($config['make_'.$part.'_menu_recursive']== 1)
            {
                $print_submenu=true;
            }
            else
            if ($config['make_'.$part.'_menu_recursive']== 2)
            {
                if ($_FN['mod']== $sectionvalues['id'] || FN_SectionIsInsideThis($sectionvalues['id'],$_FN['mod']))
                    $print_submenu=true;
            }
        }
        else
        {
            $print_submenu=true;
        }
        if ($print_submenu)
        {
            $submenu_str=FN_TPL_tp_create_submenu_($tp_submenuitem_ori_template,$sectionvalues['id']);
        }
        else
        {
            $submenu_str="";
        }
        $tp_submenuitem_new=str_replace($tp_submenuitem_ori,$submenu_str,$tp_submenuitem_ori);
        $htmlmenuitem=str_replace($tp_submenuitem_ori,$tp_submenuitem_new,$htmlmenuitem);
        $htmlout.=$htmlmenuitem;
    }
    $htmlout=FN_TPL_ReplaceHtmlPart("menuitems",$htmlout,$str);
    //$htmlout=str_replace("{submenu}","",$htmlout);


    return $htmlout;
}

/**
 *
 * @global array $_FN
 * @return string 
 */
function FN_TPL_tp_create_submenu_($str,$idsection)
{
    global $_FN;
    static $cache_tp_menuitem=array();
    static $cache_tp_menuitem_old=array();
    //$cache_tp_menuitem=array();
    //$cache_tp_menuitem_old=array();
    $idcache=md5($str);

    if ($str== "" || $idsection== "")
        return "";
    $sections=FN_GetSections($idsection);
    if (!$sections)
        return "";

    if (empty($cache_tp_menuitem["$idcache"]))
    {
        preg_match('/<!-- submenuitems -->(.*)<!-- endsubmenuitems -->/is',$str,$out);
        $tp_menuitem_old=FN_TPL_GetHtmlPart("submenuitems",$str,"<li><a href=\"link\">title</a></li>");
        $tp_menuitem['default']=FN_TPL_GetHtmlPart("submenuitem",$str);
        $tp_menuitem['active']=FN_TPL_GetHtmlPart("submenuitemactive",$str,$tp_menuitem['default']);
        $tp_menuitem['dropdown']=FN_TPL_GetHtmlPart("submenuitemdropdown",$str,$tp_menuitem['default']);
        $tp_menuitem['dropdownactive']=FN_TPL_GetHtmlPart("submenuitemdropdownactive",$str,$tp_menuitem['dropdown']);
        foreach($tp_menuitem as $k=> $tp_menu)
        {
            $tp_menuitem[$k]=preg_replace("/<a([^>]+)(href)=(\")([^\"]*)(\")/im","<a\\1\\2=\\3{link}\\3",$tp_menuitem[$k]);
            $tp_menuitem[$k]=preg_replace("/<a([^>]+)(href)=(\')([^\']*)(\')/im","<a\\1\\2=\\3{link}\\3",$tp_menuitem[$k]);
            if (strpos($tp_menuitem[$k],'{title}')=== false)
            {
                $tp_menuitem[$k]=preg_replace("/(<a.*>)(.*)(<\/a)/im","\\1{title}\\3",$tp_menuitem[$k]);
            }
        }
        $cache_tp_menuitem["$idcache"]=$tp_menuitem;
        $cache_tp_menuitem_old["$idcache"]=$tp_menuitem_old;
        foreach($tp_menuitem as $k=> $tp_menu)
        {
            if (false== strpos($tp_menuitem[$k],"title="))
            {
                $tp_menuitem[$k]=str_replace("<a","a<a title=\"{section_description}\" ",$tp_menuitem[$k]);
            }
            if (false== strpos($tp_menuitem[$k],"{accesskey"))
            {
                $tp_menuitem[$k]=str_replace("<a","<a accesskey=\"{accesskey}\" ",$tp_menuitem[$k]);
            }
        }
    }
    else
    {
        $tp_menuitem_old=$cache_tp_menuitem_old["$idcache"];
        $tp_menuitem=$cache_tp_menuitem["$idcache"];
    }
    $htmlout="";
    foreach($sections as $sectionvalues)
    {
        $sectionvalues['accesskey']=FN_GetAccessKey($sectionvalues['title'],"index.php?mod={$sectionvalues['id']}");
        if ($tp_menuitem['dropdownactive']!= "" && FN_GetSections($sectionvalues['id']) && (FN_SectionIsInsideThis($sectionvalues['id']) || $_FN['mod']== $sectionvalues['id'] ))
        {
            $htmlout.=FN_TPL_ApplyTplString($tp_menuitem['dropdownactive'],$sectionvalues,false);
        }
        elseif ($tp_menuitem['dropdown']!= "" && FN_GetSections($sectionvalues['id'])) //if have childs
        {
            $htmlout.=FN_TPL_ApplyTplString($tp_menuitem['dropdown'],$sectionvalues,false);
        }
        elseif ($_FN['mod']== $sectionvalues['id'])
            $htmlout.=FN_TPL_ApplyTplString($tp_menuitem['active'],$sectionvalues,false);
        else
            $htmlout.=FN_TPL_ApplyTplString($tp_menuitem['default'],$sectionvalues,false);
        if (strpos($htmlout,'{submenu}')!== false)
        {
            $htmlout=str_replace("{submenu}",FN_TPL_tp_create_submenu_($str,$sectionvalues['id']),$htmlout);
        }
    }
    if ($htmlout!= "")
        $htmlout=str_replace($tp_menuitem_old,$htmlout,$str);
    return $htmlout;
}

/**
 * 
 * @param string $str
 * @return string
 */
function FN_TPL_tp_create_blocks_right($str)
{
    return FN_TPL_tp_create_blocks($str,"right");
}

/**
 * 
 * @param string $str
 * @return string
 */
function FN_TPL_tp_create_blocks_left($str)
{
    return FN_TPL_tp_create_blocks($str,"left");
}

/**
 *
 * @return string
 */
function FN_TPL_tp_create_blocks_top($str)
{
    return FN_TPL_tp_create_blocks($str,"top");
}

/**
 *
 * @return string
 */
function FN_TPL_tp_create_blocks_bottom($str)
{
    return FN_TPL_tp_create_blocks($str,"bottom");
}

/**
 *
 * @global array $_FN
 * @param string $where
 * @return string
 */
function FN_TPL_tp_create_blocks($str,$where)
{
    global $_FN;
    //$conf = FN_LoadConfig("themes/{$_FN['theme']}/config.php");
    $tp_block=FN_TPL_GetHtmlPart("blockitem",$str);
    $tp_block=FN_TPL_ReplaceHtmlPart("blocktitle","{title}",$tp_block);
    $tp_block=FN_TPL_ReplaceHtmlPart("blockcontents","{contents}",$tp_block);
    $tp_block_noheader=FN_TPL_ReplaceHtmlPart("blockheader","",$tp_block);
    $blocks=FN_GetBlocks("$where");
    $htmlout="";
    foreach($blocks as $block)
    {
        $block['contents']=FN_HtmlBlock($block['id']);
        if ($block['contents']!= "")
        {
            if (!empty($block['hidetitle']))
            {
                $htmlout.=FN_TPL_ApplyTplString($tp_block_noheader,$block,false);
            }
            else
                $htmlout.=FN_TPL_ApplyTplString($tp_block,$block,false);
        }
    }

    return $htmlout;
}

if (!function_exists("FN_HtmlNavbar"))
{

    /**
     *
     * @param string $sections
     * @return string 
     */
    function FN_HtmlNavbar($sections="")
    {
        if ($sections== "")
            $sections=FN_GetSectionsTree();
        if (!is_array($sections))
            return "";
        $htmls=array();
        foreach($sections as $section)
        {
            $htmls[]="<a title=\"{$section['description']}\" accesskey=\"".FN_GetAccessKey($section['title'],$section['link'])."\" href=\"{$section['link']}\">{$section['title']}</a>";
        }
        $html=implode("&nbsp;&#187;&nbsp;",$htmls);
        return $html;
    }

}
if (!function_exists("FN_HtmlMainteanceMode"))
{

    /**
     *
     * @global array $_FN
     * @return string 
     */
    function FN_HtmlMainteanceMode()
    {
        global $_FN;
        if (file_exists("themes/{$_FN['theme']}/mainteancemode.tp.html"))
        {

            return FN_TPL_ApplyTplFile("themes/{$_FN['theme']}/mainteancemode.tp.html",$_FN);
        }

        $html="<html><head><title>{$_FN['site_title']}</title></head><body>";
        $html.="<h2>".FN_Translate("site in maintenance")."</h2>";
        $html.=FN_HtmlLoginForm();
        $html.="</body>";
        return $html;
    }

}
if (!function_exists("FN_HtmlRight"))
{

    /**
     *
     * @param string $alt
     * @param string $title
     * @return string 
     */
    function FN_HtmlRight($alt="",$title="")
    {

        $html="<img style=\"vertical-align:middle\" alt=\"$alt\" title=\"$title\" src=\"".FN_FromTheme("images/fn_right.png")."\" />";
        return $html;
    }

}
if (!function_exists("FN_HtmlLeft"))
{

    /**
     *
     * @param string $alt
     * @param string $title
     * @return string 
     */
    function FN_HtmlLeft($alt="",$title="")
    {
        $html="<img style=\"vertical-align:middle\" alt=\"$alt\" title=\"$title\" src=\"".FN_FromTheme("images/fn_left.png")."\" />";
        return $html;
    }

}
if (!function_exists("FN_HtmlUp"))
{

    /**
     *
     * @param string $alt
     * @param string $title
     * @return string 
     */
    function FN_HtmlUp($alt="",$title="")
    {

        $html="<img style=\"vertical-align:middle\" alt=\"$alt\" title=\"$title\" src=\"".FN_FromTheme("images/fn_up.png")."\" />";
        return $html;
    }

}
if (!function_exists("FN_HtmlDown"))
{

    /**
     *
     * @param string $alt
     * @param string $title
     * @return string 
     */
    function FN_HtmlDown($alt="",$title="")
    {
        $html="<img style=\"vertical-align:middle\" alt=\"$alt\" title=\"$title\" src=\"".FN_FromTheme("images/fn_down.png")."\" />";
        return $html;
    }

}
if (!function_exists("FN_HtmlArrowRight"))
{

    /**
     *
     * @param string $alt
     * @param string $title
     * @return string 
     */
    function FN_HtmlArrowRight($alt="",$title="")
    {
        $html="<img style=\"vertical-align:middle\" alt=\"$alt\" title=\"$title\" src=\"".FN_FromTheme("images/right.png")."\" />";
        return $html;
    }

}
if (!function_exists("FN_HtmlArrowLeft"))
{

    /**
     *
     * @param string $alt
     * @param string $title
     * @return string 
     */
    function FN_HtmlArrowLeft($alt="",$title="")
    {
        $html="<img style=\"vertical-align:middle\" alt=\"$alt\" title=\"$title\" src=\"".FN_FromTheme("images/left.png")."\" />";
        return $html;
    }

}
if (!function_exists("FN_HtmlArrowUp"))
{

    /**
     *

     * @param string $alt
     * @param string $title
     * @return string 
     */
    function FN_HtmlArrowUp($alt="",$title="")
    {

        $html="<img style=\"vertical-align:middle\" alt=\"$alt\" title=\"$title\"src=\"".FN_FromTheme("images/up.png")."\" />";
        return $html;
    }

}
if (!function_exists("FN_HtmlArrowDown"))
{

    /**
     *

     * @param string $alt
     * @param string $title
     * @return string 
     */
    function FN_HtmlArrowDown($alt="",$title="")
    {
        $html="<img alt=\"$alt\" title=\"$title\" style=\"vertical-align:middle\" src=\"".FN_FromTheme("images/down.png")."\" />";
        return $html;
    }

}

if (!function_exists("FN_HtmlLanguages"))
{

    /**
     *
     * @global array $_FN
     * @param string $sep
     * @return string 
     */
    function FN_HtmlLanguages($sep="&nbsp;")
    {
        global $_FN;
        $langs=array();
        foreach($_FN['listlanguages'] as $lang)
        {
            $link=FN_RewriteLink("index.php?lang=$lang&amp;mod={$_FN['mod']}");
            $icon=FN_FromTheme("images/flags/$lang.png");
            $langtitle=FN_GetFolderTitle("languages/$lang",$lang);
            $langs[]="<a title=\"$langtitle\" href=\"$link\"><img alt=\"$lang\" style=\"border:0px;\" src=\"$icon\"/></a>";
        }
        if (count($langs) > 1)
            return implode($sep,$langs);
    }

}

/**
 * 
 * @global global $_FN
 * @param type $str
 * @return type
 */
function FN_TPL_tp_create_languages($str)
{
    global $_FN;
    $htmlItem=FN_TPL_GetHtmlPart("langitem",$str);
    $html="";
    foreach($_FN['listlanguages'] as $l)
    {
        $params['langname']=$l;
        $params['langtitle']=FN_Translate("_LANGUAGE","",$l);
        $params['langimg']=$_FN['siteurl']."/images/flags/$l.png";
        $html.=FN_TPL_ApplyTplString($htmlItem,$params);
    }
    return $html;
}

if (!function_exists("FN_HtmlModalWindow"))
{

    /**
     * 
     * @global array $_FN
     * @staticvar string $html
     * @param type $body
     * @param type $title
     * @return string
     */
    function FN_HtmlModalWindow($body,$title="",$textbutton="ok")
    {
        global $_FN;
        static $html="";
        static $id=0;
        if ($html== "" && file_exists("themes/{$_FN['theme']}/modal.tp.html"))
        {
            $html=file_get_contents("themes/{$_FN['theme']}/modal.tp.html");
        }
        if ($html== "")
        {
            $html="\n<script language=\"javascript\">";
            $html.="\n setTimeout(function(){alert(\"".str_replace("\n","\\n",addslashes($body))."\",0)});";
            $html.="\n</script>\n";
            return $html;
        }
        $html=FN_TPL_ApplyTplString($html,array("title"=>$title,"body"=>$body,"textbutton"=>$textbutton,"idmodal"=>"modal_fn".$id));
        $id++;
        return $html;
    }

}


if (!function_exists("FN_HtmlButton"))
{

    /**
     * 
     * @param type $attributes
     */
    function FN_HtmlButton($value,$attributes)
    {
        global $_FN;
        $html="<button";
        foreach($attributes as $k=> $v)
        {
            $html.=" $k=\"".str_replace('"','\\\"',$v)."\"";
        }
        $html.=" >$value</button>";
        return $html;
    }

}
?>
