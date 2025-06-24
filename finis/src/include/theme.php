<?php

/**
 * 
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $tpl_skeep;

if (!function_exists("FN_HtmlMenu"))
{

    /**
     *
     * @param string $separator
     * @param string $sectionroot
     * @return string
     */
    function FN_HtmlMenu($separator = "&nbsp;|&nbsp;", $sectionroot = false)
    {
        $menu = array();
        $sections = FN_GetSections($sectionroot);
        foreach ($sections as $section)
        {
            $accesskey = FN_GetAccessKey($section['title'], "index.php?mod={$section['id']}", $section['accesskey']);
            if ($accesskey != "")
                $accesskey = " accesskey=\"$accesskey\"";
            $menu[] = "<a $accesskey href=\"{$section['link']}\">{$section['title']}</a>";
        }
        $ret = implode($separator, $menu);
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
function FN_HtmlMenuTree($parent = "", $recursive = true)
{
    global $_FN;
    static $lev = 0;
    $html = "";
    $ret = array();
    $current = $_FN['mod'];
    $sections = FN_GetSections($parent);
    if (empty($sections) || count($sections) == 0)
        return "";
    foreach ($sections as $section)
    {
        $html .= "<span style=\"white-space: nowrap\">";
        for ($i = 0; $i < $lev; $i++)
        {
            $html .= "&nbsp;&nbsp;&nbsp;&nbsp;";
        }

        $accesskey = FN_GetAccessKey($section['title'], "index.php?mod={$section['id']}");
        if ($accesskey != "")
            $accesskey = " accesskey=\"$accesskey\"";
        $title = (empty($section['description'])) ? "" : "title=\"{$section['description']}\"";
        if ($current == $section['id'])
        {
            $html .= "<a $title $accesskey href=\"" . fn_rewritelink("index.php?mod={$section['id']}") . "\">" . $section['title'] . "</a>";
        }
        else
        {
            $html .= "<a $title $accesskey href=\"" . fn_rewritelink("index.php?mod={$section['id']}") . "\">" . $section['title'] . "</a>";
        }
        $html .= "</span><br />";
        $lev++;
        if ($recursive)
            $html .= FN_HtmlMenuTree($section['id']);
        $lev--;
    }
    return $html;
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
            $html = "Powered by <a href=\"http://www.flatnux.org\">Finis</a>";
        }
        else
        {
            $html = $_FN['credits'];
        }
        return $html;
    }

}

function FN_TPL_CopyFilesFromSrcToApplication($str, $basepath)
{
    global $_FN;
    if ($basepath && preg_match_all("/<[^>]+(?:background|href|src)=[\"\']([^:#\{\"\'\?]+(?:\?[^\"\']*)?)[\"\']/im", $str, $match))
    {
        // Iterate over all matches
        foreach ($match[1] as $relativeFile)
        {
            // Print the relative file reference          
            FN_PathSite($basepath . preg_replace('/\?.*/', '', $relativeFile));
        }
    }
}

/**
 *
 * @global array $_FN
 * @param string $templatefile
 * @return type 
 */
function FN_TPL_html_MakeThemeFromTemplate($templatefile)
{
    global $_FN;
    $conf = FN_LoadConfig("themes/{$_FN['theme']}/config.php");
    $header = FN_HtmlHeader();
    $vars = $conf;
    //replace all {var key}
    $vars['lang'] = $_FN['lang'];
    $vars['site_title'] = $_FN['site_title'];
    $vars['sitename'] = $_FN['sitename'];
    $vars['keywords'] = $_FN['keywords'];
    $vars['site_subtitle'] = $_FN['site_subtitle'];
    $vars['siteurl'] = $_FN['siteurl'];
    $vars['sitepath'] = $_FN['sitepath'];
    $vars['listlanguages'] = $_FN['listlanguages'];

    $vars['credits'] = FN_HtmlCredits();
    $vars['navbar'] = FN_HtmlNavbar();

    if (!empty($_FN['sectionvalues']))
    {
        $vars['section_title'] = $_FN['sectionvalues']['title'];
        $vars['section_description'] = $_FN['sectionvalues']['description'];
    }
    $vars['rss_link'] = isset($_FN['rss_link']) ? $_FN['rss_link'] : "#";
    $vars['hmenu'] = FN_TPL_tp_create_hmenu();
    $vars['languages'] = FN_HtmlLanguages();
    $vars['sitelanguages'] = $_FN['sitelanguages'];
    $vars['current_language'] = $vars['sitelanguages'][$_FN['lang']];
    $vars['is_multilanguage'] = count($vars['sitelanguages']) > 1 ? true : "";
    //---------import generic html file---------------------------------------->
    $tplstring = file_get_contents($templatefile);
    $tplstring = preg_replace('/<title>[^<]*<\/title>/is', "<title>{site_title}</title>", $tplstring);
    $tplstring = preg_replace('/href="index.html"/is', 'href="{siteurl}"', $tplstring);
    $tplstring = preg_replace('/href=\'index.html\'/is', 'href=\'{siteurl}\'', $tplstring);
    $tplstring = preg_replace('/href="([A-Z0-9_]+).html"/is', 'href="{siteurl}index.php?mod=$1"', $tplstring);
    $tplstring = preg_replace('/ charset="UTF-8"/is', ' charset="{charset_page}"', $tplstring);
    //---------import generic html file----------------------------------------<
    $vars['blocks_right'] = "";
    if (false !== strpos($tplstring, "<!-- foreach {blocks_right} -->"))
    {
        $vars['blocks_right'] = FN_GetBlocksContentsArray("right");
    }
    if (false !== strpos($tplstring, "<!-- foreach {blocks_left} -->"))
    {
        $vars['blocks_left'] = FN_GetBlocksContentsArray("left");
    }
    if (false !== strpos($tplstring, "<!-- foreach {blocks_top} -->"))
    {
        $vars['blocks_top'] = FN_GetBlocksContentsArray("top");
    }
    if (false !== strpos($tplstring, "<!-- foreach {blocks_right} -->"))
    {
        $vars['blocks_top'] = FN_GetBlocksContentsArray("top");
    }


    $notifications = FN_GetNotificationsUndisplayed($_FN['user']);
    $vars['is_home'] = ($_FN['mod'] == $_FN['home_section']) ? true : "";
    $vars['notifications'] = $notifications;
    $vars['notifications_count'] = count($notifications);


    $vars['menuitems'] = FN_GetMenuTree();

    $vars['nav'] = FN_GetSectionsTree();

    $vars['url_logo'] = file_exists("themes/{$_FN['theme']}/images/logo.png") ? "themes/{$_FN['theme']}/images/logo.png" : "";
    if (!$vars['url_logo'])
        $vars['url_logo'] = file_exists("themes/{$_FN['theme']}/images/logo.svg") ? "themes/{$_FN['theme']}/images/logo.svg" : "";

    
    
    FN_TPL_CopyFilesFromSrcToApplication($tplstring, dirname($templatefile) . "/");
    $html = FN_TPL_include_tpl(FN_TPL_ApplyTplString($tplstring, $vars, dirname($templatefile) . "/"), $vars);

    foreach ($vars as $key => $value)
    {
        if (!is_array($value) || is_numeric($value))
        {
            $html = str_replace("{" . $key . "}", htmlspecialchars("{" . $key . "}"), $html);
            $html = str_replace("{" . $key . "}", FN_TPL_encode($value), $html);
        }
    }
    $html = preg_replace('/<title>[^<]*<\/title>/is', "<title>{$_FN['site_title']}</title>", $html);
    $html = implode($header . "</head>", explode("</head>", $html, 2));

    return FN_TPL_decode($html);
}

/**
 * 
 * @global string $tpl_skeep
 * @param type $str
 * @return type
 */
function FN_TPL_encode($str)
{
    return TPL_encode($str);
}

/**
 * 
 * @global string $tpl_skeep
 * @param type $str
 * @return type
 */
function FN_TPL_decode($str)
{
    return TPL_decode($str);
}

/**
 *
 * @return string
 */
function FN_TPL_tp_create_section()
{
    global $_FN;
    $config = FN_LoadConfig("themes/{$_FN['theme']}/config.php");
    $page_title = isset($_FN['sectionvalues']['title']) ? $_FN['sectionvalues']['title'] : "";
    $htmlsection = FN_TPL_encode(FN_HtmlSection());
    return $htmlsection;
}

/**
 *
 * @param string $str
 * @param array $vars
 * @return string
 */
function FN_TPL_include_tpl($str, $vars)
{
    $strout = $str;
    $array = preg_match_all('/<!-- include ([\w]+) -->(.*?)(<!-- end include (\\1) -->)/s', $str, $out);
    if (is_array($out[0]))
        foreach ($out as $k => $v)
        {
            if (is_array($v))
            {
                foreach ($v as $toreplace)
                {
                    //dprint_r($toreplace);
                    $tpname = explode("-->", $toreplace);
                    $tpname = str_replace("<!-- include ", "", $tpname[0]);
                    $tpname = trim(ltrim($tpname));
                    if (function_exists("FN_TPL_tp_create_" . $tpname))
                    {
                        $fname = "FN_TPL_tp_create_" . $tpname;
                        $replace = $fname($toreplace);
                        $strout = str_replace($toreplace, $replace, $strout);
                    }
                    if (function_exists($tpname) && preg_match("/^FN_Html/is", $tpname))
                    {
                        $fname = $tpname;
                        $replace = $fname($toreplace);
                        $strout = str_replace($toreplace, $replace, $strout);
                    }
                }
            }
            break;
        }
    return $strout;
}

/**
 *
 * @param string $tplname
 * @param array $vars
 * @return string
 */
function FN_TPL_ApplyTplFile($tplname, $vars)
{

    global $_FN;
    $str = "";
    if (file_exists($tplname))
        $str = file_get_contents($tplname);
    $basepath = dirname($tplname) . "/";
    return FN_TPL_ApplyTplString($str, $vars, $basepath);
}

/**
 *
 * @global array $_FN
 * @param string $str
 * @param array $vars
 * @param string $basepath
 * @return string 
 */
function FN_TPL_ApplyTplString($str, $vars, $basepath = false)
{
    global $_FN;
    $section = FN_GetSectionValues($_FN['mod']);
    $uservalues = FN_GetUser($_FN['user']);
    $config = array();

    if (is_array($uservalues))
    {
        foreach ($uservalues as $k => $value)
        {
            if (!isset($config["user_{$k}"]))
            {
                $config["user_{$k}"] = $value;
            }
        }
    }


    if (is_array($section))
    {
        foreach ($section as $k => $value)
        {
            if (!isset($_FN["section_{$k}"]))
            {
                $config["section_{$k}"] = $value;
            }
        }
    }
    if (!isset($vars['url_avatar']))
    {
        $config['url_avatar'] = FN_GetUserImage($_FN['user']);
    }
    else
    {
        $config['url_avatar'] = "{$_FN['siteurl']}/images/user.png";
    }

    foreach ($_FN as $k => $v)
    {
        if (is_string($v) || is_numeric($v))
        {
            $config[$k] = $v;
            $vars[$k] = $v;
        }
    }
    $config['lang_default'] = isset($config['lang_default']) ? $config['lang_default'] : $_FN['lang_default'];
    $config['siteurl'] = isset($config['siteurl']) ? $config['siteurl'] : $_FN['siteurl'];
    $config['lang'] = isset($config['lang']) ? $config['lang'] : $_FN['lang'];
    $config['enable_mod_rewrite'] = isset($config['enable_mod_rewrite']) ? $config['enable_mod_rewrite'] : $_FN['enable_mod_rewrite'];
    $config['use_urlserverpath'] = isset($config['use_urlserverpath']) ? $config['use_urlserverpath'] : $_FN['use_urlserverpath'];
    $config['sitepath'] = isset($config['sitepath']) ? $config['sitepath'] : $_FN['sitepath'];

    return TPL_ApplyTplString($str, $vars, $basepath, $config);
}

/**
 *
 * @return string
 */
function FN_TPL_tp_create_hmenu($str = "&nbsp;|&nbsp;")
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
function FN_TPL_GetHtmlPart($partname, $tp_str, $default = "")
{
    $out = array();
    if (preg_match("/<!-- $partname -->.*<!-- $partname -->/s", $tp_str))//se il nome del nodo contiene un elemento con lo stesso nome
    {
        $tmp = explode("<!-- $partname -->", $tp_str);
        //dprint_xml($tmp);
        $tmp = $tmp[1];
        if (false !== strpos($tmp, "<!-- end $partname -->"))
            $tmp = explode("<!-- end $partname -->", $tmp);
        elseif (false !== strpos($tmp, "<!-- end$partname -->"))
            $tmp = explode("<!-- end$partname -->", $tmp);
        if (is_array($tmp))
        {
            $tmp = $tmp[0];
            $tp_str = "<!-- $partname -->" . $tmp . "<!-- end $partname -->";
            return $tp_str;
        }
    }
    preg_match("/<!-- $partname -->(.*)<!-- end$partname -->/is", $tp_str, $out) || preg_match("/<!-- $partname -->(.*)<!-- end $partname -->/is", $tp_str, $out);
    $tp_str = empty($out[0]) ? $default : $out[0];
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
function FN_TPL_GetHtmlParts($partname, $tp_str, $default = "")
{
    global $_FN;
    static $cache = array();
    $md5 = md5($partname . $tp_str . $default);
    if (isset($cache[$md5]))
    {
        //dprint_r("cache $partname");
        return $cache[$md5];
    }
    if ($_FN['use_cache'])
    {
        if (($cache[$md5] = FN_GetGlobalVarValue($md5)) !== null)
        {
            return $cache[$md5];
        }
        else
        {
            unset($cache[$md5]);
        }
    }

    $out = array();
    $ret = false;
    if (preg_match("/<!-- $partname -->.*<!-- $partname -->/s", $tp_str))//se il nome del nodo contiene un elemento con lo stesso nome
    {
        $tmp = explode("<!-- $partname -->", $tp_str);
        //dprint_xml($tmp);
        $i = 1;
        while (isset($tmp[$i]))
        {
            $tmp2 = $tmp[$i];
            if (false !== strpos($tmp2, "<!-- end $partname -->"))
                $tmp2 = explode("<!-- end $partname -->", $tmp2);
            elseif (false !== strpos($tmp2, "<!-- end$partname -->"))
                $tmp2 = explode("<!-- end$partname -->", $tmp2);
            if (is_array($tmp2))
            {
                $tmp2 = $tmp2[0];
                $tp_str = "<!-- $partname -->" . $tmp2 . "<!-- end $partname -->";
                $ret[] = $tp_str;
            }
            $i++;
        }
        $cache[$md5] = $ret;
        if ($_FN['use_cache'])
        {
            FN_SetGlobalVarValue($md5, $cache[$md5]);
        }
        return $ret;
    }
    preg_match("/<!-- $partname -->(.*)<!-- end$partname -->/is", $tp_str, $out) || preg_match("/<!-- $partname -->(.*)<!-- end $partname -->/is", $tp_str, $out);
    $tp_str = empty($out[0]) ? $default : $out[0];
    if ($tp_str)
    {

        $cache[$md5] = array(0 => $tp_str);
        return array(0 => $tp_str);
    }
    $cache[$md5] = array();
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
function FN_TPL_ReplaceHtmlPart($partname, $replace, $tp_str, $default = "")
{
    $tp_str_tmp = FN_TPL_GetHtmlPart($partname, $tp_str, $default);
    $str_out = str_replace($tp_str_tmp, $replace, $tp_str);
    return $str_out;
}

/**
 * 
 * @param string $str
 * @return string
 */
function FN_TPL_tp_create_topmenu($str = "")
{
    return FN_TPL_html_menu($str, "top");
}

/**
 * 
 * @param string $str
 * @return string
 */
function FN_TPL_tp_create_menu($str = "")
{
    return FN_TPL_html_menu($str, "vertical");
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
function FN_TPL_html_menu($str = "", $part = "", $parent = false)
{
    global $_FN;
    static $sections = array();
    $config = FN_LoadConfig("themes/{$_FN['theme']}/config.php");
    if (isset($config['show_' . $part . '_menu']) && $config['show_' . $part . '_menu'] == 0)
        return "";
    if ($str == "")
        return "";
    $tp_menuitem['default'] = FN_TPL_GetHtmlPart("menuitem", $str, "<a href=\"link\">title</a><br />");
    $tp_menuitem['active'] = FN_TPL_GetHtmlPart("menuitemactive", $str, $tp_menuitem['default']);
    $tp_menuitem['dropdown'] = FN_TPL_GetHtmlPart("menuitemdropdown", $str);
    $tp_menuitem['dropdownactive'] = FN_TPL_GetHtmlPart("menuitemdropdownactive", $str);
    foreach ($tp_menuitem as $k => $v)
    {
        $tp_menuitem[$k] = preg_replace("/href=\"javascript:/im", "ferh=\"javascript:", $tp_menuitem[$k]);
        $tp_menuitem[$k] = preg_replace("/href='javascript:/im", "ferh='javascript:", $tp_menuitem[$k]);

        $tp_menuitem[$k] = preg_replace("/<a([^>]+)(href)=(\")([^\"]*)(\")/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitem[$k]);
        $tp_menuitem[$k] = preg_replace("/<a([^>]+)(href)=(\')([^\']*)(\')/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitem[$k]);

        $tp_menuitem[$k] = preg_replace("/ferh=\"javascript:/im", "href=\"javascript:", $tp_menuitem[$k]);
        $tp_menuitem[$k] = preg_replace("/ferh='javascript:/im", "href='javascript:", $tp_menuitem[$k]);

        if (strpos($tp_menuitem[$k], '{title}') === false)
        {
            $tp_menuitem[$k] = preg_replace("/(<a.*>)(.*)(<\/a)/im", "\\1{title}\\3", $tp_menuitem[$k]);
        }
        if (false == strpos($tp_menuitem[$k], "title="))
        {
            $tp_menuitem[$k] = str_replace("<a", "<a title=\"{description}\" ", $tp_menuitem[$k]);
        }
        //add accesskey
        if (false == strpos($tp_menuitem[$k], "{accesskey"))
        {
            $tp_menuitem[$k] = str_replace("<a", "<a accesskey=\"{accesskey}\" ", $tp_menuitem[$k]);
        }
    }

    $htmlout = "";
    $sectionradix = "";
    if (!empty($config[$part . '_menu_parent']))
    {
        if ($config[$part . '_menu_parent'] == "__submenu__")
            $sectionradix = $_FN['mod'];
        else
            $sectionradix = $config[$part . '_menu_parent'];
    }
    if ($parent)
    {
        $sectionradix = $parent;
    }
//dprint_r($sectionradix);
    if (empty($sections[$sectionradix]))
        $sections[$sectionradix] = FN_GetSections($sectionradix);


    foreach ($sections[$sectionradix] as $sectionvalues)
    {
        $sectionvalues['accesskey'] = FN_GetAccessKey($sectionvalues['title'], "index.php?mod={$sectionvalues['id']}");
        if ($tp_menuitem['dropdownactive'] != "" && FN_GetSections($sectionvalues['id']) && (FN_SectionIsInsideThis($sectionvalues['id']) || $_FN['mod'] == $sectionvalues['id'] )) //if have childs and active
        {
            $htmlmenuitem = FN_TPL_ApplyTplString($tp_menuitem['dropdownactive'], $sectionvalues, false);
            $tp_submenuitem_ori_template = FN_TPL_GetHtmlPart("submenu", $tp_menuitem['dropdownactive']);
        }
        elseif ($tp_menuitem['dropdown'] != "" && FN_GetSections($sectionvalues['id'])) //if have childs
        {
            $htmlmenuitem = FN_TPL_ApplyTplString($tp_menuitem['dropdown'], $sectionvalues, false);
            $tp_submenuitem_ori_template = FN_TPL_GetHtmlPart("submenu", $tp_menuitem['dropdown']);
        }
        elseif ($_FN['mod'] == $sectionvalues['id'] || FN_SectionIsInsideThis($sectionvalues['id']))
        {
            $htmlmenuitem = FN_TPL_ApplyTplString($tp_menuitem['active'], $sectionvalues, false);
            $tp_submenuitem_ori_template = FN_TPL_GetHtmlPart("submenu", $tp_menuitem['active']);
        }
        else
        {
            $htmlmenuitem = FN_TPL_ApplyTplString($tp_menuitem['default'], $sectionvalues, false);
            $tp_submenuitem_ori_template = FN_TPL_GetHtmlPart("submenu", $tp_menuitem['default']);
        }
        $tp_submenuitem_ori = FN_TPL_GetHtmlPart("submenu", $htmlmenuitem);
        $tp_submenuitem_new = $tp_submenuitem_ori;
        $print_submenu = false;
        if (isset($config['make_' . $part . '_menu_recursive']))
        {
            if ($config['make_' . $part . '_menu_recursive'] == 1)
            {
                $print_submenu = true;
            }
            else
            if ($config['make_' . $part . '_menu_recursive'] == 2)
            {
                if ($_FN['mod'] == $sectionvalues['id'] || FN_SectionIsInsideThis($sectionvalues['id'], $_FN['mod']))
                    $print_submenu = true;
            }
        }
        else
        {
            $print_submenu = true;
        }
        if ($print_submenu)
        {
            $submenu_str = FN_TPL_tp_create_submenu_($tp_submenuitem_ori_template, $sectionvalues['id']);
        }
        else
        {
            $submenu_str = "";
        }
        $tp_submenuitem_new = str_replace($tp_submenuitem_ori, $submenu_str, $tp_submenuitem_ori);
        $htmlmenuitem = str_replace($tp_submenuitem_ori, $tp_submenuitem_new, $htmlmenuitem);
        $htmlout .= $htmlmenuitem;
    }
    $htmlout = FN_TPL_ReplaceHtmlPart("menuitems", $htmlout, $str);
    //$htmlout=str_replace("{submenu}","",$htmlout);


    return $htmlout;
}

/**
 *
 * @global array $_FN
 * @return string 
 */
function FN_TPL_tp_create_submenu_($str, $idsection)
{
    global $_FN;
    static $cache_tp_menuitem = array();
    static $cache_tp_menuitem_old = array();
    //$cache_tp_menuitem=array();
    //$cache_tp_menuitem_old=array();
    $idcache = md5($str);

    if ($str == "" || $idsection == "")
        return "";
    $sections = FN_GetSections($idsection);
    if (!$sections)
        return "";

    if (empty($cache_tp_menuitem["$idcache"]))
    {
        preg_match('/<!-- submenuitems -->(.*)<!-- endsubmenuitems -->/is', $str, $out);
        $tp_menuitem_old = FN_TPL_GetHtmlPart("submenuitems", $str, "<li><a href=\"link\">title</a></li>");
        $tp_menuitem['default'] = FN_TPL_GetHtmlPart("submenuitem", $str);
        $tp_menuitem['active'] = FN_TPL_GetHtmlPart("submenuitemactive", $str, $tp_menuitem['default']);
        $tp_menuitem['dropdown'] = FN_TPL_GetHtmlPart("submenuitemdropdown", $str, $tp_menuitem['default']);
        $tp_menuitem['dropdownactive'] = FN_TPL_GetHtmlPart("submenuitemdropdownactive", $str, $tp_menuitem['dropdown']);
        foreach ($tp_menuitem as $k => $tp_menu)
        {
            $tp_menuitem[$k] = preg_replace("/<a([^>]+)(href)=(\")([^\"]*)(\")/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitem[$k]);
            $tp_menuitem[$k] = preg_replace("/<a([^>]+)(href)=(\')([^\']*)(\')/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitem[$k]);
            if (strpos($tp_menuitem[$k], '{title}') === false)
            {
                $tp_menuitem[$k] = preg_replace("/(<a.*>)(.*)(<\/a)/im", "\\1{title}\\3", $tp_menuitem[$k]);
            }
        }
        $cache_tp_menuitem["$idcache"] = $tp_menuitem;
        $cache_tp_menuitem_old["$idcache"] = $tp_menuitem_old;
        foreach ($tp_menuitem as $k => $tp_menu)
        {
            if (false == strpos($tp_menuitem[$k], "title="))
            {
                $tp_menuitem[$k] = str_replace("<a", "a<a title=\"{section_description}\" ", $tp_menuitem[$k]);
            }
            if (false == strpos($tp_menuitem[$k], "{accesskey"))
            {
                $tp_menuitem[$k] = str_replace("<a", "<a accesskey=\"{accesskey}\" ", $tp_menuitem[$k]);
            }
        }
    }
    else
    {
        $tp_menuitem_old = $cache_tp_menuitem_old["$idcache"];
        $tp_menuitem = $cache_tp_menuitem["$idcache"];
    }
    $htmlout = "";
    foreach ($sections as $sectionvalues)
    {
        $sectionvalues['accesskey'] = FN_GetAccessKey($sectionvalues['title'], "index.php?mod={$sectionvalues['id']}");
        if ($tp_menuitem['dropdownactive'] != "" && FN_GetSections($sectionvalues['id']) && (FN_SectionIsInsideThis($sectionvalues['id']) || $_FN['mod'] == $sectionvalues['id'] ))
        {
            $htmlout .= FN_TPL_ApplyTplString($tp_menuitem['dropdownactive'], $sectionvalues, false);
        }
        elseif ($tp_menuitem['dropdown'] != "" && FN_GetSections($sectionvalues['id'])) //if have childs
        {
            $htmlout .= FN_TPL_ApplyTplString($tp_menuitem['dropdown'], $sectionvalues, false);
        }
        elseif ($_FN['mod'] == $sectionvalues['id'])
            $htmlout .= FN_TPL_ApplyTplString($tp_menuitem['active'], $sectionvalues, false);
        else
            $htmlout .= FN_TPL_ApplyTplString($tp_menuitem['default'], $sectionvalues, false);
        if (strpos($htmlout, '{submenu}') !== false)
        {
            $htmlout = str_replace("{submenu}", FN_TPL_tp_create_submenu_($str, $sectionvalues['id']), $htmlout);
        }
    }
    if ($htmlout != "")
        $htmlout = str_replace($tp_menuitem_old, $htmlout, $str);
    return $htmlout;
}

/**
 * 
 * @param string $str
 * @return string
 */
function FN_TPL_tp_create_blocks_right($str)
{
    return FN_TPL_tp_create_blocks($str, "right");
}

/**
 * 
 * @param string $str
 * @return string
 */
function FN_TPL_tp_create_blocks_left($str)
{
    return FN_TPL_tp_create_blocks($str, "left");
}

/**
 *
 * @return string
 */
function FN_TPL_tp_create_blocks_top($str)
{
    return FN_TPL_tp_create_blocks($str, "top");
}

/**
 *
 * @return string
 */
function FN_TPL_tp_create_blocks_bottom($str)
{
    return FN_TPL_tp_create_blocks($str, "bottom");
}

/**
 *
 * @global array $_FN
 * @param string $where
 * @return string
 */
function FN_TPL_tp_create_blocks($str, $where)
{
    global $_FN;
    $tp_block = FN_TPL_GetHtmlPart("blockitem", $str);
    $tp_block = FN_TPL_ReplaceHtmlPart("blocktitle", "{title}", $tp_block);
    $tp_block = FN_TPL_ReplaceHtmlPart("blockcontents", "{contents}", $tp_block);
    $tp_block_noheader = FN_TPL_ReplaceHtmlPart("blockheader", "", $tp_block);
    $blocks = FN_GetBlocks("$where");
    $htmlout = "";
    foreach ($blocks as $block)
    {
        $block['contents'] = FN_HtmlBlock($block['id']);
        if ($block['contents'] != "")
        {
            if (!empty($block['hidetitle']))
            {
                $htmlout .= FN_TPL_ApplyTplString($tp_block_noheader, $block, false);
            }
            else
                $htmlout .= FN_TPL_ApplyTplString($tp_block, $block, false);
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
    function FN_HtmlNavbar($sections = "")
    {
        if ($sections == "")
            $sections = FN_GetSectionsTree();
        if (!is_array($sections))
            return "";
        $htmls = array();
        foreach ($sections as $section)
        {
            $htmls[] = "<a title=\"{$section['description']}\" accesskey=\"" . FN_GetAccessKey($section['title'], $section['link']) . "\" href=\"{$section['link']}\">{$section['title']}</a>";
        }
        $html = implode("&nbsp;&#187;&nbsp;", $htmls);
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

            return FN_TPL_ApplyTplFile("themes/{$_FN['theme']}/mainteancemode.tp.html", $_FN);
        }

        $html = "<html><head><title>{$_FN['site_title']}</title></head><body>";
        $html .= "<h2>" . FN_Translate("site in maintenance") . "</h2>";
        $html .= FN_HtmlLoginForm();
        $html .= "</body>";
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
    function FN_HtmlLanguages($sep = "&nbsp;")
    {
        global $_FN;
        $langs = array();
        foreach ($_FN['listlanguages'] as $lang)
        {
            $link = FN_RewriteLink("index.php?lang=$lang&amp;mod={$_FN['mod']}");
            $langtitle = FN_GetFolderTitle("languages/$lang", $lang);
            $langs[] = "<a title=\"$langtitle\" href=\"$link\">" . FN_getCountryFlag($lang) . "</a>";
        }
        if (count($langs) > 1)
            return implode($sep, $langs);
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
    $items = $_FN['sitelanguages'];
    $htmlItem = FN_TPL_GetHtmlPart("langitem", $str);
    $html = "";
    foreach ($items as $params)
    {
        $html .= FN_TPL_ApplyTplString($htmlItem, $params);
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
    function FN_HtmlModalWindow($body, $title = "", $textbutton = "ok")
    {
        global $_FN;
        static $html = "";
        static $id = 0;
        if ($html == "" && file_exists("themes/{$_FN['theme']}/modal.tp.html"))
        {
            $html = file_get_contents("themes/{$_FN['theme']}/modal.tp.html");
        }
        if ($html == "")
        {
            $html = "\n<script language=\"javascript\">";
            $html .= "\n setTimeout(function(){alert(\"" . str_replace("\n", "\\n", addslashes($body)) . "\",0)});";
            $html .= "\n</script>\n";
            return $html;
        }
        $html = FN_TPL_ApplyTplString($html, array("title" => $title, "body" => $body, "textbutton" => $textbutton, "idmodal" => "modal_fn" . $id));
        $id++;
        return $html;
    }

}

/**
 * 
 * @global array $_FN
 * @staticvar int $lev
 * @param type $parent
 * @param type $recursive
 * @return string
 */
function FN_GetMenuTree($parent = "")
{
    global $_FN;
    static $lev = 0;
    $menuarray = array();
    $current = $_FN['mod'];
    $sections = FN_GetSections($parent);
    if (empty($sections) || count($sections) == 0)
        return array();
    
    foreach ($sections as $section)
    {
        $menuitem = $section;
        if (function_exists("FN_GetSectionProprieties"))
        {
            $proprieties = FN_GetSectionProprieties($section['id']);
            $menuitem = array_merge($section, $proprieties);
        }

        $menuitem['menulevel'] = $lev;
        $accesskey = FN_GetAccessKey($menuitem['title'], "index.php?mod={$section['id']}");
        $menuitem['accesskey'] = $accesskey;

        $menuitem['active'] = ($current == $section['id']) ? "1" : "";
        $menuitem['opened'] = FN_SectionIsInsideThis($section['id']) ? true : "";
        $menuitem['link'] = FN_RewriteLink("index.php?mod={$section['id']}", "&amp;", true);
        $lev++;
        
        $menuitem['childs'] = FN_GetMenuTree($section['id']);
        $menuitem['class'] = "";
        $count = is_array($menuitem['childs']) ? count($menuitem['childs']) : "";
        $menuitem['havechilds'] = "$count";
        $menuarray[] = $menuitem;
        $lev--;
    }
    return $menuarray;
}

/**
 * 
 * @param type $where
 * @return type
 */
function FN_GetBlocksContentsArray($where)
{

    $blocks = FN_GetBlocks($where);
    $ret = array();
    foreach ($blocks as $block)
    {
        $block['html'] = FN_HtmlBlock($block['id']);
        $ret[] = $block;
    }
    return $ret;
}

if (!function_exists("FN_HtmlHeader"))
{

    /**
     *
     * @global array $_FN
     * @param bool $tags
     * @return string 
     */
    function FN_HtmlHeader()
    {
        global $_FN;
        $html = "";
        $sectionvalues = FN_GetSectionValues($_FN['mod']);
        ob_start();
        if (!empty($sectionvalues['type']) && file_exists("{$_FN['src_finis']}/modules/{$sectionvalues['type']}/header.php"))
        {
            require_once "{$_FN['src_finis']}/modules/{$sectionvalues['type']}/header.php";
        }
        if (file_exists("{$_FN['src_application']}/sections/{$_FN['mod']}/header.php"))
        {
            require_once "{$_FN['src_application']}/sections/{$_FN['mod']}/header.php";
        }
        if (!empty($_FN['section_header']))
        {
            $html .= $_FN['section_header'];
        }
        $html .= trim(ltrim(ob_get_clean()));

        //$html.="\n\t<title>{$_FN['site_title']}</title>";
        $html .= FN_IncludeCSS();
        $html .= FN_IncludeJS();
        $html .= "\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . $_FN['charset_page'] . "\" />";
        $html .= "\n\t<meta name=\"KEYWORDS\" content=\"{$_FN['keywords']}\"  />";
        $html .= "\n\t<meta http-equiv=\"EXPIRES\" content=\"0\" />";
        $html .= "\t<meta name=\"REVISIT-AFTER\" content=\"1 DAYS\" />\n";
        $html .= "\t<script type=\"text/javascript\">
\t//<!--
\tcheck = function (url)
\t{
\t\tif(confirm (\"" . FN_i18n("are you sure you want to do it?") . "\"))
\t\t\twindow.location=url;
\t}
\t// -->
\t</script>";
        if (!empty($_FN['section_header_footer']))
        {
            $html .= $_FN['section_header_footer'];
        }

        return $html;
    }

}