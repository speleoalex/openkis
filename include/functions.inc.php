<?php

/**
 * @package Flatnux_functions
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') || die('Restricted access');

/**
 *
 * @global array $_FN
 * @return float
 */
function FN_GetExecuteTimer()
{
    global $_FN;
    $mtime = microtime();
    $mtime = explode(" ", $mtime);
    $mtime = doubleval($mtime[1]) + doubleval($mtime[0]);
    return sprintf("%.4f", abs($mtime - $_FN['timestart']));
}

/**
 * 
 * @global type $_FN
 * @return type
 */
function FN_GetPartialTimer()
{
    global $_FN;
    if (empty($_FN['timepartial']))
    {
        $_FN['timepartial'] = $_FN['timestart'];
    }

    $mtime = microtime();
    $mtime = explode(" ", $mtime);
    $mtime = doubleval($mtime[1]) + doubleval($mtime[0]);
    if (empty($_FN['timepartial']))
    {
        $_FN['timepartial'] = $mtime;
    }
    $ret = sprintf("%.4f", abs($mtime - $_FN['timepartial']));
    $_FN['timepartial'] = $mtime;
    $total = FN_GetExecuteTimer();
    return "partial=$ret - total=$total";
}

/**
 * 
 * @param string $key
 * @param array $var
 * @param string $type
 * @return string
 */
function FN_GetParam($key, $var = false, $type = "")
{
    global $_FN;
    $ret = "";
    if ($var === false)
    {
        $var = $_REQUEST;
    }
    if ($key === false)
    {
        $ret = $var;
    }
    elseif (isset($var[$key]))
    {
        $ret = $var[$key];
    }
//array is not allowed
    if (is_array($ret))
        $ret = "";
    switch ($type)
    {
        case "html":
            $charset = (!empty($_FN['charset_page'])) ? $_FN['charset_page'] : "UTF-8";
            $ret = htmlentities($ret, ENT_QUOTES, $charset);
            break;
        case "int":
            if ($ret != "")
                $ret = intval($ret);
            break;
        case "float":
            if ($ret != "")
                $ret = floatval($ret);
            break;
        default:
            if (function_exists($type))
            {
                return $type($ret);
            }
            break;
    }
    return FN_StripPostSlashes($ret);
}

/**
 * 
 * @param type $a
 * @param type $b
 * @return type
 */
function FN_UsortFilemtime($a, $b)
{
    return filemtime($a) - filemtime($b);
}

/**
 * if user can view the page load html sections and the administrator options
 * 
 * @global array $_FN
 * @param string $section
 * @return string
 */
function FN_HtmlSection($section = "")
{
    global $_FN;
    if ($section == "")
        $section = $_FN['mod'];
    $sectionvalues = FN_GetSectionValues($section);
    //--language from module or section ----->
    FN_LoadMessagesFolder($_FN['filesystempath'] . "/sections/{$section}");
    if (!empty($sectionvalues['type']))
    {
        FN_LoadMessagesFolder($_FN['filesystempath'] . "/modules/{$sectionvalues['type']}");
    }
    //--language from module or section -----<

    $html = "";
    $htmlconfig = "";
    if (basename($_SERVER['SCRIPT_FILENAME']) == "index.php")
    {
        $htmlconfig .= FN_HtmlAdminOptions();
    }
    if (!FN_UserCanViewSection($section))
    {
        $html = FN_i18n("you don't have permission to view this page");
        return $html;
    }
    $modcont = FN_GetParam("opt", $_GET, "flat");
    if ($modcont != "")
    {

        $mode = FN_GetParam("mode", $_GET, "flat");
        if ($mode == "versions")
        {
            $html .= "FILE: <b>$modcont</b><br />";
            $html .= "" . FN_Translate("versions") . ":<br />";
            $html .= "<table><tr><td>" . FN_Translate("creation date") . "</td><td>" . FN_Translate("created by") . "</td><td>" . FN_Translate("delete date") . "</td><td>" . FN_Translate("overwritten by") . "</td><td></td></tr>";
            $files = glob("$modcont.*");
            usort($files, "FN_UsortFilemtime");
            $bk_user = "";
            foreach ($files as $file)
            {
                $html .= "<tr>";
                $attr = explode(".", basename($file));
                $date = DateTime::createFromFormat('YmdHis', $attr[count($attr) - 3]);
                $dateFile = $attr[count($attr) - 4];

                if (is_numeric($dateFile))
                {
                    $dateFile = FN_FormatDate($dateFile);
                }
                else
                {
                    $dateFile = "unknown";
                }
                $bk_date = $date->getTimestamp();
                $bk_date = FN_FormatDate($bk_date);
                $html .= "<td>$dateFile</td><td>$bk_user</td><td>" . $bk_date . "</td>";
                $bk_user = $attr[count($attr) - 2];
                $html .= "<td>$bk_user</td>";
                $html .= "<td><button onclick=\"window.location='{$_FN['siteurl']}index.php?mod={$_FN['mod']}&opt=$modcont&restore=$file'\">" . FN_Translate("restore") . "</button></td>";
                $html .= "</tr>";
            }

            $html .= "<tr><td>" . FN_FormatDate(filemtime($modcont)) . "</td><td>$bk_user</td><td>-</td><td>-</td><td><button onclick=\"window.location='{$_FN['siteurl']}index.php?mod={$_FN['mod']}&opt=$modcont'\">" . FN_Translate("edit") . "</button>" . "</td></tr>";
            $html .= "</table>";
            $linkcancel = FN_RewriteLink("index.php?mod={$_FN['mod']}");
            $html .= "<br /><button onclick=\"window.location='$linkcancel';\">" . FN_Translate("cancel") . "</button>";
            return "<div class=\"fn_admin\"><h4>" . FN_Translate("administration tools") . " $title $t_exit</h4>" . $html . "</div>";
        }
        else
        {

            $title = $_FN['sectionvalues']['title'];
            if (FN_erg('config.php$', $modcont))
                $title = FN_Translate("advanced settings", "Aa") . " $title";
            if ($_FN['sectionvalues']['type'] != "" && is_dir("modules/{$_FN['sectionvalues']['type']}"))
                $title .= " (" . FN_Translate("page like", "Aa") . " " . FN_GetFolderTitle("modules/{$_FN['sectionvalues']['type']}") . ")";

            $t_exit = "<button onclick=\"window.location='" . FN_RewriteLink("index.php?mod={$_FN['mod']}", "&") . "';return false;\">" . FN_HtmlArrowLeft() . " " . FN_Translate("back to") . " \"{$_FN['sectionvalues']['title']}\"</button>";
            //try edit module------------------------------------------------------>
            if ($modcont == "fnc_ccnf_section_{$_FN['mod']}")
            {
                if (FN_UserCanEditSection() && false !== ($html = FN_HtmlEditSection($_FN['mod'])))
                {
                    return "<div class=\"fn_admin\"><h4>" . FN_Translate("administration tools") . " $title $t_exit</h4>" . $html . "</div>";
                }
            }
            //try edit module------------------------------------------------------<
            //try edit file-------------------------------------------------------->
            elseif (is_dir(dirname($modcont)) && !is_dir($modcont) && FN_CanModifyFile($_FN['user'], $modcont))
            {
                $editor_params = array();
                $linkcancel = FN_RewriteLink("index.php?mod={$_FN['mod']}");
                $linkform = FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;opt=$modcont");
                if (file_exists("sections/{$_FN['mod']}/style.css"))
                {
                    $editor_params['css_file'] = "sections/{$_FN['mod']}/style.css";
                }
                $_FN['editor_folder'] = "sections/{$_FN['mod']}";
                $file_restore = FN_GetParam("restore", $_GET);
                if (!empty($file_restore) && file_exists($file_restore) && FN_GetFileExtension($file_restore) == "bak~")
                {
                    $editor_params['force_value'] = file_get_contents($file_restore);
                    $linkcancel = $linkcancel = FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;opt=$modcont&amp;mode=versions");
                    $editor_params['text_save'] = FN_Translate("restore");
                }
                $html = FN_HtmlEditContent($modcont, $linkform, $linkcancel, $editor_params);
                if (!empty($_POST['savefileconfig']))
                {
                    FN_UpdateDefaultXML(FN_GetSectionValues($_FN['mod'], false));
                }
                if ($html !== false)
                {
                    return "<div class=\"fn_admin\"><h4>$title $t_exit</h4>" . $html . "</div>";
                }
            }
            //try edit file--------------------------------------------------------<
        }
    }
    //-------------print section----------------------------------------------->
    if (!empty($sectionvalues['type']) && file_exists("modules/{$sectionvalues['type']}"))
    {
        $html = FN_HtmlContent("modules/{$sectionvalues['type']}");
    }
    else
    {
        $html = FN_HtmlContent("sections/$section");
    }
    //-------------print section-----------------------------------------------<
    $html .= $htmlconfig;

    return $html;
}

/**
 * get html block
 * 
 * @param string $block
 * @return string 
 */
function FN_HtmlBlock($block)
{
    global $_FN;
    static $htmls = array();
    if (isset($htmls[$block]))
    {
        return $htmls[$block];
    }
    $_FN['block'] = $block;
    $blockvalues = FN_GetBlockValues($block);
    if (!empty($blockvalues['type']) && file_exists("modules/{$blockvalues['type']}") && FN_erg("^block_", $blockvalues['type']))
    {
        $html = FN_HtmlContent("modules/{$blockvalues['type']}");
    }
    else
    {
        $html = FN_HtmlContent("blocks/$block");
    }
    $htmls[$block] = $html;
    $_FN['block'] = "";
    return $htmls[$block];
}

/**
 * load section.php or section.[lang].html and 
 * return html
 * 
 * @global array $_FN
 * @param string $folder
 * @param bool $usecache
 * @return string 
 */
function FN_HtmlContent($folder, $usecache = true)
{
    $str = "";
    if (file_exists("$folder/section.php"))
    {
        ob_start();
        include_once "$folder/section.php";
        $str = ob_get_clean();
        return $str;
    }
    else
        $str = FN_HtmlStaticContent($folder, $usecache);
    return $str;
}

/**
 * load section.[lang].html and 
 * return html
 * 
 * @global array $_FN
 * @param string $folder
 * @param bool $usecache
 * @return string 
 */
function FN_HtmlStaticContent($folder, $usecache = false)
{
    global $_FN;
    static $cache = array();

    if ($usecache)
    {
        if (!empty($cache[$folder]))
        {
            return $cache[$folder]; //cache in memory
        }
        if (!empty($_FN['use_cache']) && file_exists("{$_FN['datadir']}/_cache/{$_FN['lang']}" . urlencode($folder) . ".cache"))
        {
            return file_get_contents("{$_FN['datadir']}/_cache/{$_FN['lang']}" . urlencode($folder) . ".cache");
        }
    }
    $filetoread = "";
    $str = "";
    if (file_exists("$folder/section.{$_FN['lang']}.html"))
    {
        $filetoread = "$folder/section.{$_FN['lang']}.html";
    }
    elseif (file_exists("$folder/section.{$_FN['lang_default']}.html"))
    {
        $filetoread = "$folder/section.{$_FN['lang_default']}.html";
    }
    if ($filetoread)
    {
        $str = file_get_contents($filetoread);
        $str = FN_RewriteLinksLocalToAbsolute($str, $folder);
    }

    $cache[$folder] = $str;
    if (!empty($_FN['use_cache']))
        FN_Write($str, "{$_FN['datadir']}/_cache/{$_FN['lang']}" . urlencode($folder) . ".cache");
    return $str;
}

/**
 *
 * @param string $str
 * @param string $folder
 * @return string
 */
function FN_RewriteLinksLocalToAbsolute($str, $folder)
{
    global $_FN;

    $fullUrl = false;
    $sdir = "{$_FN['siteurl']}$folder/";

    if (!empty($_FN['use_urlserverpath']))
        $sdir = "http://____replace____/$folder/";

    $old = "";
    $str = str_replace("href=\"index.php", "ferh=\"index.php", $str);
    $str = str_replace("href='index.php", "ferh='index.php", $str);
    $str = str_replace("href='#", "ferh='#", $str);
    $str = str_replace("href=\"#", "ferh=\"#", $str);
    $str = str_replace("href=\"http:", "ferh=\"http:", $str);
    $str = str_replace("href='http:", "ferh='http:", $str);
    $str = str_replace("href=\"https:", "ferh=\"https:", $str);
    $str = str_replace("href='https:", "ferh='https:", $str);
    $str = str_replace("href=\"{", "ferh=\"{", $str);
    $str = str_replace("href='{", "ferh='{", $str);

    $str = str_replace("href='/", "ferh'/", $str);
    $str = str_replace("href=\"/", "ferh=\"/", $str);

    $str = str_replace("href=\"<", "ferh=\"<", $str);
    $str = str_replace("href='<", "ferh='<", $str);

    $str = str_replace("src='<", "s_r_c='<", $str);
    $str = str_replace("src=\"<", "s_r_c=\"<", $str);
    $str = str_replace("src='/", "s_r_c='/", $str);
    $str = str_replace("src=\"/", "s_r_c=\"/", $str);

    $sdir_ = $sdir;
    $i = 0;
    while ($str != $old)
    {
        $old = $str;
        $str = preg_replace("/<([^>]+)( background| href| src)=(\")([^#^:^{]*)(\")/im", "<\\1\\2=\\3$sdir_\\4\\3", $str);
        $str = preg_replace("/<([^>]+)( background| href| src)=(\')([^#^:^{]*)(\')/im", "<\\1\\2=\\3$sdir_\\4\\3", $str);
        $str = preg_replace('#<([^>]+)(url\(\'(?!http))#', '<$1$2$3' . $sdir_ . '', $str);
        $str = preg_replace('#<([^>]+)(url\((?!http))#', '<$1$2$3' . $sdir_ . '', $str);
    }
    $str = str_replace("ferh=\"", "href=\"", $str);
    $str = str_replace("ferh='", "href='", $str);
    $str = str_replace("s_r_c=\"", "src=\"", $str);
    $str = str_replace("s_r_c='", "src='", $str);

    if ($_FN['enable_mod_rewrite'] > 0 && $_FN['links_mode'] == "html")
    {
        if ($_FN['lang'] == $_FN['lang_default'])
        {
            $str = preg_replace("/(href=\"index.php\?mod=)([A-Z0-9_]+)\"/is", "href=\"\$2.html\"", $str);
            $str = preg_replace("/(href='index.php\?mod=)([A-Z0-9_]+)'/is", "href=\"\$2.html\"", $str);
        }
        else
        {
            $str = preg_replace("/(href=\"index.php\?mod=)([A-Z0-9_]+)\"/is", "href=\"\$2.{$_FN['lang']}.html\"", $str);
            $str = preg_replace("/(href='index.php\?mod=)([A-Z0-9_]+)'/is", "href=\"\$2.{$_FN['lang']}.html\"", $str);
        }
    }

    if (!empty($_FN['use_urlserverpath']))
        $str = str_replace("http://____replace____/", $_FN['sitepath'], $str);

    return $str;
}

/**
 * Init Sections
 *
 * @global array $_FN
 * @return array 
 */
function FN_InitSections()
{
    global $_FN;
//sections in database -------------------------------------------------------->
    $sections = $_FN['sections'];
    $flag_mod = false;
    $flag_mod_st = false;
    $sect_db = array();
    $posmax = 0;
    if (is_array($sections))
    {
        foreach ($sections as $section)
        {
            if (!file_exists("sections/{$section['id']}"))
            {
                $table = FN_XmlForm("fn_sections");
                $table->xmltable->DelRecord($section['id']);
                $flag_mod = true;
                continue;
            }
            $sect_db[$section['id']] = $section;
            if ($section['position'] >= $posmax)
                $posmax = $section['position'];
        }
    }
//sections in database --------------------------------------------------------<
//sections in filesystem ------------------------------------------------------>
    $sectionsdirs = glob("sections/*");
    $sections = array();
    foreach ($sectionsdirs as $section)
    {
        $tmp = array();
        if (is_dir($section))
        {
            $section = basename($section);
            if (isset($sect_db[$section]))
                continue;
            $defaultxmlfile = file_exists("sections/$section/default.xml.php") ? "sections/$section/default.xml.php" : "sections/$section/default.xml";
            if (file_exists($defaultxmlfile))
            {
                $default = xmldb_xml2array(file_get_contents($defaultxmlfile), "fn_sections");
                if (isset($default[0]) && is_array($default[0]))
                {
                    $tmp = $default[0];
                }
            }
            $tmp['id'] = $section;
            foreach ($_FN['listlanguages'] as $l)
            {
                if (file_exists("sections/$section/title.$l.fn"))
                {
                    $tmp['title_' . $l] = file_get_contents("sections/$section/title.$l.fn");
                }
                elseif (file_exists("sections/$section/title.i18n.fn"))
                {
                    $tmp['title_' . $l] = FN_Translate(file_get_contents("sections/$section/title.i18n.fn"), "Aa", $l);
                }
            }
            $tmp['title'] = isset($tmp['title_' . $_FN['lang_default']]) ? $tmp['title_' . $_FN['lang_default']] : $section;
            $tmp['link'] = FN_RewriteLink("index.php?mod=$section");
            foreach ($_FN['listlanguages'] as $lang)
            {
                if (file_exists("sections/$section/title.{$lang}.fn"))
                    $tmp["title" . FN_LangSuffix($lang)] = file_get_contents("sections/$section/title.{$lang}.fn");
            }
            $tmp['status'] = empty($tmp['status']) ? 1 : $tmp['status'];
            $tmp['sectionpath'] = "sections";
            if (!isset($sect_db[$tmp['id']]))
            {
                if (empty($tmp['position']))
                {
                    $tmp['position'] = $posmax + 1;
                    $posmax++;
                }
                $table = FN_XmlForm("fn_sections");
                $table->xmltable->InsertRecord($tmp);
                $flag_mod = true;
            }
        }
    }
//sections in filesystem ------------------------------------------------------>
//------------- modules  ------------------------------------------------------>
    $sectionstypes = glob("modules/*");
    foreach ($sectionstypes as $sectiontype)
    {
        if (is_dir($sectiontype))
        {
            $sectiontype = basename($sectiontype);
            if (!isset($_FN['sectionstypes'][$sectiontype]))
            {
                $tmp = array();
                $defaultxmlfile = file_exists("modules/$sectiontype/default.xml.php") ? "modules/$sectiontype/default.xml.php" : "modules/$sectiontype/default.xml";
                if (file_exists("$defaultxmlfile"))
                {
                    $default = xmldb_xml2array(file_get_contents("$defaultxmlfile"), "fncf_$sectiontype");
                    //$default=xmldb_xml2array(file_get_contents("$defaultxmlfile"),"fn_sectionstype");
                    if (isset($default[0]) && is_array($default[0]))
                    {
                        $tmp = $default[0];
                    }
                }
                $tmp['name'] = $sectiontype;
                if (empty($tmp['title']))
                    $tmp['title'] = str_replace("_", " ", $tmp['name']);
                $flag_mod_st = true;
                $table = FN_XmlTable("fn_sectionstypes");
                $table->InsertRecord($tmp);
            }
        }
    }
    $sectionstypes = $_FN['sectionstypes'];
    foreach ($sectionstypes as $sectiontype)
    {
        if (!is_dir("modules/" . $sectiontype['name']))
        {
            $flag_mod_st = true;
            $table = FN_XmlTable("fn_sectionstypes");
            $table->DelRecord($sectiontype['name']);
        }
    }
    if ($flag_mod)
    {
        $_FN['sections'] = FN_GetAllSections();
    }
    if ($flag_mod_st)
    {
        $_FN['sectionstypes'] = FN_GetAllSectionTypes();
    }
//------------- modules  ------------------------------------------------------<
    return $sections;
}

/**
 * Init Sections
 * 
 * @global array $_FN
 * @return array 
 */
function FN_InitBlocks()
{
    global $_FN;
//sections in database
    $sect_db = $_FN['blocks'];
    $blocksdirs = glob("blocks/*");
    $blocks = array();
    $flag_mod = false;
//sections in filesystem
    foreach ($blocksdirs as $block)
    {
        $tmp = array();
        if (is_dir($block))
        {
            $block = basename($block);
            $tmp['where'] = "left";
            $defaultxmlfile = file_exists("blocks/$block/default.xml.php") ? "blocks/$block/default.xml.php" : "blocks/$block/default.xml";
            if (file_exists("$defaultxmlfile"))
            {
                $default = xmldb_xml2array(file_get_contents("$defaultxmlfile"), "blocks");
                if (isset($default[0]) && is_array($default[0]))
                {
                    $tmp = $default[0];
                }
            }
            $tmp['id'] = $block;
            $tmp['title'] = $block;
            foreach ($_FN['listlanguages'] as $lang)
            {
                if (file_exists("blocks/$block/title.{$lang}.fn"))
                    $tmp["title" . FN_LangSuffix($lang)] = file_get_contents("blocks/$block/title.{$lang}.fn");
                elseif (file_exists("blocks/$block/title.i18n.fn"))
                {
                    $tmp['title_' . $lang] = FN_Translate(file_get_contents("blocks/$block/title.i18n.fn"), "Aa", $lang);
                }
            }
            $tmp['title'] = isset($tmp['title_' . $_FN['lang_default']]) ? $tmp['title_' . $_FN['lang_default']] : $tmp['title'];
            $tmp['status'] = empty($tmp['status']) ? 1 : $tmp['status'];
            if (!isset($sect_db[$tmp['id']]))
            {
                $table = FN_XmlForm("fn_blocks");
                $table->xmltable->InsertRecord($tmp);
                $flag_mod = true;
            }
        }
    }
    if ($flag_mod)
    {
        $_FN['blocks'] = FN_GetAllBlocks();
    }
    return $blocks;
}

/**
 * Include CSS from sections/SECTION/style.css include/css/ , include/themes/THEME/ 
 *
 * @global array $_FN
 * @param bool $include_theme_css
 * @param bool $include_section_css
 * @return string 
 */
function FN_IncludeCSS($include_theme_css = true, $include_section_css = true)
{
    global $_FN;
    $html = "";
    $css = "";
    $sectionvalues = FN_GetSectionValues($_FN['mod']);
    if (!empty($_FN['use_urlserverpath']))
        $sitepath = $_FN['sitepath'];
    else
        $sitepath = $_FN['siteurl'];

    $listcss = glob("include/css/*.css");
    foreach ($listcss as $cssfile)
    {
        $ftime = @filemtime($cssfile);
        $html .= "\n\t<link rel='StyleSheet' type='text/css' href=\"{$sitepath}$cssfile?$ftime\" />";
        $css .= file_get_contents($cssfile) . "\n";
    }
    if ($include_theme_css)
    {
        $listcss = glob("themes/{$_FN['theme']}/*.css");
        foreach ($listcss as $cssfile)
        {
            $html .= "\n\t<link rel='StyleSheet' type='text/css' href=\"{$sitepath}$cssfile\" />";
            $css .= file_get_contents($cssfile) . "\n";
        }
    }
    if ($include_section_css && !empty($sectionvalues['type']) && file_exists("modules/{$sectionvalues['type']}/style.css"))
    {
        $html .= "\n\t<link rel='StyleSheet' type='text/css' href=\"{$sitepath}modules/{$sectionvalues['type']}/style.css\" />";
        $css .= file_get_contents("modules/{$sectionvalues['type']}/style.css") . "\n";
    }
    if ($include_section_css && file_exists("sections/{$_FN['mod']}/style.css"))
    {
        $html .= "\n\t<link rel='StyleSheet' type='text/css' href=\"{$sitepath}sections/{$_FN['mod']}/style.css\" />";
        $css .= file_get_contents("sections/{$_FN['mod']}/style.css") . "\n";
    }
    if (!empty($_FN['inline_css']))
        $html = "<style>$css</style>";
    elseif (!empty($_FN['async_css']))
        $html = "<script>window.setTimeout(function(){document.getElementsByTagName('head')[0].innerHTML+='" . addslashes(str_replace("\n", "", $html)) . "';},10);</script>";
    return $html;
}

/**
 * Include JS from nclude/javascripts/
 * @global array $_FN
 */
function FN_IncludeJS()
{
    global $_FN;
    if (!empty($_FN['use_urlserverpath']))
        $sitepath = $_FN['sitepath'];
    else
        $sitepath = $_FN['siteurl'];
    $html = "";
    $listcss = glob("include/javascripts/*.js");
    foreach ($listcss as $file)
    {
        $html .= "\n\t<script defer=\"defer\" type=\"text/javascript\" src=\"{$sitepath}$file\"></script>";
    }
    return $html;
}

/**
 * return file path from theme
 * 
 * @param string file
 * @param bool absolute path
 * @return string path file
 */
function FN_FromTheme($file, $absolute = true)
{
    global $_FN;
    if ($absolute)
        return file_exists("themes/{$_FN['theme']}/" . $file) ? "{$_FN['siteurl']}themes/{$_FN['theme']}/" . $file : $_FN['siteurl'] . $file;
    else
        return file_exists("themes/{$_FN['theme']}/" . $file) ? "themes/{$_FN['theme']}/" . $file : $file;
}

function FN_FromThemeFS($file, $absolute = true)
{
    global $_FN;
    if ($absolute)
        return file_exists("themes/{$_FN['theme']}/" . $file) ? realpath("themes/{$_FN['theme']}/" . $file) : realpath($file);
    else
        return file_exists("themes/{$_FN['theme']}/" . $file) ? "themes/{$_FN['theme']}/" . $file : $file;
}

/**
 *
 * @global array $_FN
 * @return int
 */
function FN_Time()
{
    global $_FN;
    return time() + (3600 * intval($_FN['jet_lag']));
}

/**
 * translate the string
 *
 * @param string $constant
 * @param string $lang
 * @param string $mode
 * @return string
 */
function FN_i18n($constant, $language = "", $uppercasemode = "")
{
    global $_FN, $_FNMESSAGE;
    $ebabledb = false;
    $old = false;
    $constant_clean = strtolower($constant);
    $lang = $_FN['lang'];
    if ($language == "")
        $language = $lang;
    if (!isset($_FNMESSAGE[$language]))
    {
        $_FNMESSAGE[$language] = FN_GetMessagesFromCsv("languages/$language/lang.csv");
    }
    
    $text = "";
    if ($constant != "")
    {
        if (isset($_FNMESSAGE[$language][$constant]))
        {
            $text = $_FNMESSAGE[$language][$constant];
        }
        elseif (isset($_FNMESSAGE[$language][$constant]))
        {
            $text = $_FNMESSAGE[$language][$constant];
        }
        elseif (isset($_FNMESSAGE[$language][$constant_clean]))
        {
            $text = $_FNMESSAGE[$language][$constant_clean];
        }
        else
        {
            $text = "".str_replace("_", " ", $constant);
            $text = "$text";
        }
    }
    switch ($uppercasemode)
    {
        case "";
            break;
        case "Aa":
            $text = ucfirst($text);
            break;
        case "aa":
            $text = strtolower($text);
            break;
        case "AA":
            $text = strtoupper($text);
            break;
        case "Aa Aa":
            $text = ucwords($text);
            break;
    }
    $text = FN_ConvertEncoding($text, $_FN['charset_lang'], $_FN['charset_page']);
    return $text;
}

/**
 *
 * @global array $_FN
 * @param string $format
 * @return string
 */
function FN_Now($format = "Y-m-d H:i:s")
{
    global $_FN;
    return date("$format", time() + (3600 * intval($_FN['jet_lag'])));
}

/**
 * Gett accesskey from link
 *
 *
 * @global array $_FN
 * @param string $title
 * @param string $link
 * @param string $forcekey
 * @return string
 */
function FN_GetAccessKey(&$title, $link, $forcekey = "")
{
    global $_FN;
    $link = str_replace("&amp;", "&", $link);
    if (!isset($_FN['accesskey']) || !is_array($_FN['accesskey']))
        $_FN['accesskey'] = array();
    $showaccesskey = $_FN['showaccesskey'];
    $titlel = strtolower($title);
    if ($forcekey != "")
    {
        if ($showaccesskey == 1) // sottolinea gli accesskey
        {
            $title = "[" . $forcekey . "]$title";
        }
        $_FN['accesskey'][$forcekey] = $link;
        return $forcekey;
    }
//----------cerco un accesskey libero------------
    for ($i = 0; $i < strlen($titlel); $i++)
    {
        $a = $titlel[$i];
        if (!FN_erg("[a-z]", $a))
            continue;
//---------se esiste gia' per quel link esco --------------
        if (isset($_FN['accesskey'][$a]) && $_FN['accesskey'][$a] == $link)
        {
            if ($showaccesskey == 1) // sottolinea gli accesskey
                $title = "[" . $a . "]&nbsp;$title";
            $_FN['accesskey'][$a] = $link;
            return $a;
        }
//-----tento con le altre lettere ------
        if (!isset($_FN['accesskey'][$a]) && !is_numeric($a))
        {
            $_FN['accesskey'][$a] = $link;
            if ($showaccesskey == 1) // sottolinea gli accesskey
            {
                $title = "[" . $a . "]&nbsp;$title";
            }
            return $a;
        }
    }
    $chrs = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
        'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        ',', '.', '-', '+', '\\', '*', '@', '#', '?', '$', '!', '%', '/', '(',
        ')', '=', '^', ';', ':', '.', '_', '|', '*');
    foreach ($chrs as $a)
    {
        if (!isset($_FN['accesskey'][$a]))
        {
            if ($showaccesskey == 1) // sottolinea gli accesskey
            {
                $title = "[" . $a . "]$title";
            }
            $_FN['accesskey'][$a] = $link;
            return $a;
        }
    }
    return "";
}

/**
 * @global array $_FN
 * @param string $string
 */
function FN_Tag2Html($string)
{
    $string = str_replace("[:)]", "<img src=\"" . FN_FromTheme("images/emoticon/01.png") . "\" alt=\":)\" />", $string);
    $string = str_replace("[:(]", "<img src=\"" . FN_FromTheme("images/emoticon/02.png") . "\" alt=\":(\" />", $string);
    $string = str_replace("[:o]", "<img src=\"" . FN_FromTheme("images/emoticon/03.png") . "\" alt=\":o\" />", $string);
    $string = str_replace("[:p]", "<img src=\"" . FN_FromTheme("images/emoticon/04.png") . "\" alt=\":p\" />", $string);
    $string = str_replace("[:D]", "<img src=\"" . FN_FromTheme("images/emoticon/05.png") . "\" alt=\":D\" />", $string);
    $string = str_replace("[:!]", "<img src=\"" . FN_FromTheme("images/emoticon/06.png") . "\" alt=\":!\" />", $string);
    $string = str_replace("[:O]", "<img src=\"" . FN_FromTheme("images/emoticon/07.png") . "\" alt=\":O\" />", $string);
    $string = str_replace("[8)]", "<img src=\"" . FN_FromTheme("images/emoticon/08.png") . "\" alt=\"8)\" />", $string);
    $string = str_replace("[;)]", "<img src=\"" . FN_FromTheme("images/emoticon/09.png") . "\" alt=\";)\" />", $string);
    $string = str_replace("\n", "<br />", $string);
    $string = str_replace("\r", "", $string);
    $string = str_replace("[b]", "<b>", $string);
    $string = str_replace("[/b]", "</b>", $string);
    $string = str_replace("[i]", "<i>", $string);
    $string = str_replace("[/i]", "</i>", $string);
    $string = str_replace("[quote]", "<blockquote><hr noshade=\"noshade\" /><i>", $string);
    $string = str_replace("[/quote]", "</i><hr noshade=\"noshade\" /></blockquote>", $string);
    $string = str_replace("[code]", "<blockquote><pre>", $string);
    $string = str_replace("[/code]", "</pre></blockquote>", $string);

    $string = str_replace("[img]", "<br /><img src=\"", $string);
    $string = str_replace("[/img]", "\" alt=\"uploaded_image\" /><br />", $string);
//$string = preg_replace("/\[youtube\](.+?)\[\/youtube\]/s",'<object width="425" height="355"><param name="movie" value="http://www.youtube.com/v/$1&rel=1"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/$1&rel=1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="355"></embed></object>',$string);
// text color--->
    $string = str_replace("[red]", "<font color='ff0000'>", $string);
    $string = str_replace("[green]", "<font color='00ff00'>", $string);
    $string = str_replace("[blue]", "<font color='0000ff'>", $string);
    $string = str_replace("[pink]", "<font color='ff00ff'>", $string);
    $string = str_replace("[yellow]", "<font color='ffff00'>", $string);
    $string = str_replace("[cyan]", "<font color='00ffff'>", $string);
    $string = str_replace("[/red]", "</font>", $string);
    $string = str_replace("[/blue]", "</font>", $string);
    $string = str_replace("[/green]", "</font>", $string);
    $string = str_replace("[/pink]", "</font>", $string);
    $string = str_replace("[/yellow]", "</font>", $string);
    $string = str_replace("[/cyan]", "</font>", $string);
// text color---<
// WIKIPEDIA --->
    $items = explode("[/wp]", $string);
    for ($i = 0; $i < count($items); $i++)
    {
        $wp = "";
        if (stristr($items[$i], "[wp"))
        {
            $wp_lang = preg_replace("/.*\\[wp lang=/s", "", $items[$i]);
            $wp_lang = preg_replace("/\\].*/s", "", $wp_lang);
            $wp = preg_replace("/.*\\[wp.*\\]/s", "", $items[$i]);
            $wp = preg_replace("/\\[\\/wp\\].*/s", "", $wp);
            if ($wp != "")
            {
                $nuovowp = "<a style=\"text-decoration: none; border-bottom: 1px dashed; color: blue;\" target=\"new\" href=\"http://$wp_lang.wikipedia.org/wiki/$wp\">$wp</a>";
                $string = str_replace("[wp lang=$wp_lang]" . $wp . "[/wp]", $nuovowp, $string);
            }
        }
    }
// WIKIPEDIA ---<
    $items = "";
// URLs --->
    $items = explode("[/url]", $string);
    for ($i = 0; $i < count($items); $i++)
    {
        $url = "";
        if (stristr($items[$i], "[url]"))
        {
            $url = preg_replace("/.*\\[url\\]/s", "", $items[$i]);
            $url = preg_replace("/\\[\/url\\].*/s", "", $url);
            if ($url != "")
            {
                if (stristr($url, "http://") == FALSE && stristr($url, "https://") == FALSE)
                {
                    $nuovourl = "<a target=\"new\" href=\"http://$url\">$url</a>";
                }
                else
                {
                    $nuovourl = "<a target=\"new\" href=\"$url\">$url</a>";
                }
                $string = str_replace("[url]" . $url . "[/url]", $nuovourl, $string);
            }
        }
    }
// URLs ---<
    return ($string);
}

/**
 * 
 * @global array $_FN
 * @param type $event
 * @param type $context
 */
function FN_LogEvent($event, $context = "cms")
{
    global $_FN;
    if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_log.php"))
    {
        FN_Copy("include/install/fndatabase/fn_log.php", "{$_FN['datadir']}/{$_FN['database']}/fn_log.php");
    }
    $table = FN_XmlTable("fn_log");
    $newvalues = array();
    $newvalues['context'] = preg_replace('/[^a-z0-9]+/', '_', strtolower($context));
    $newvalues['event'] = $event;
    $newvalues['user'] = $_FN['user'];
    $newvalues['ip'] = FN_GetParam("REMOTE_ADDR", $_SERVER, "html");
    $newvalues['date'] = FN_Now();
    $f = $table->InsertRecord($newvalues);
    FN_Log($event);
}

/**
 * 
 * @global array $_FN
 * @param type $notificationvalues
 * @param type $users
 * @return type
 */
function FN_AddNotification($notificationvalues, $users)
{
    global $_FN;
    //if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_notifications.php"))
    {
        FN_Copy("include/install/fndatabase/fn_notifications.php", "{$_FN['datadir']}/{$_FN['database']}/fn_notifications.php");
    }
    if ($users && is_string($users))
    {
        if (!FN_GetUser($users))
        {
            return;
        }
        $users = array($users);
    }
    $users = array_unique($users);
    $table = FN_XmlTable("fn_notifications");
    if (is_string($notificationvalues))
    {
        $text = $notificationvalues;
        $notificationvalues = array();
        $notificationvalues['text'] = "$text";
    }

    $newvalues = array();
    $newvalues['context'] = preg_replace('/[^a-z0-9]+/', '_', strtolower(FN_GetParam("context", $notificationvalues, "html")));
    $newvalues['text'] = FN_GetParam("text", $notificationvalues, "html");
    $newvalues['link'] = FN_GetParam("link", $notificationvalues, "html");
    $newvalues['ip'] = FN_GetParam("REMOTE_ADDR", $_SERVER, "html");
    $newvalues['date'] = FN_Now();

    foreach ($users as $user)
    {
        if ($user)
        {
            $newvalues['username'] = $user;
            $f = $table->InsertRecord($newvalues);
        }
    }
}

/**
 * 
 * @global array $_FN
 * @param type $context
 */
function FN_GetNotificationsUndisplayed($user, $context = "")
{
    global $_FN;
    $table = FN_XmlTable("fn_notifications");
    //$user = str_replace("'", "\\'", $user);
    $query = "SELECT * FROM fn_notifications WHERE username LIKE '$user' AND displayed <> 1";

    $notifications = FN_XMLQuery($query);
    //$notifications = $table->GetRecords(array("username" => $user));
    $ret_notifications = array();
    if (is_array($notifications))
    {
        foreach ($notifications as $k => $notification)
        {
            if ($notification['displayed'] != 1)
            {
                $tmp = $notification;
                $tmp['human_date'] = FN_FormatDate($notification['date'], true);
                $tmp['link'] = isset($notification['link']) ? $notification['link'] : "";
                $ret_notifications[] = $tmp;
            }
        }
    }

    return $ret_notifications;
}

/**
 * 
 * @global array $_FN
 * @param type $id
 */
function FN_SetNotificationDisplayed($id)
{
    global $_FN;
    $table = FN_XmlTable("fn_notifications");
    $table->UpdateRecordBypk(array("displayed" => 1), "id", $id);
}

/**
 *
 * @global array $_FN
 * @param string $txt
 */
function FN_Log($txt)
{
    global $_FN;
    $flog = "{$_FN['datadir']}/log"; //splx zone da forum non c'e' piu'
    if (!is_dir($flog))
        mkdir($flog, 0777);
    $datelog = date("Y-m");
    if (!file_exists("$flog/$datelog-log.php"))
    {
        FN_Write("<?php exit(1);?>\n", "$flog/$datelog-log.php", "a");
    }
    if (is_writable("$flog/$datelog-log.php"))
    {
        $ip = FN_GetParam("REMOTE_ADDR", $_SERVER, "html");
        $user = isset($_FN['user']) ? $_FN['user'] : "";
        $txt = str_replace('"', '""', $txt);
        $ip = str_replace('"', '""', $ip);
        $user = str_replace('"', '""', $user);
        $string = xmldb_now() . ";\"$ip\";\"{$_FN['self']}\";\"{$_FN['mod']}\";\"$user\";\"$txt\";\n";
        FN_Write($string, "$flog/$datelog-log.php", "a");
    }
    else
    {
        if (FN_IsAdmin())
        {
            echo "<br />";
            echo "$flog/$datelog-log.php:" . FN_i18n("is read-only") . "<br />";
        }
    }
    if ($_FN['enable_log_email'] == 1)
    {
        $txtmail = "Log from: {$_FN['sitename']}";
        $txtmail .= "\n\nSite url:{$_FN['siteurl']}";
        $txtmail .= "\n\nLog: $string";
        FN_SendMail($_FN['log_email_address'], "[fnlog] {$_FN['sitename']}", $txtmail);
    }
}

/**
 *
 * @param string $user
 * @param string $group
 * @return bool
 */
function FN_UserInGroup($user, $group)
{
    $user = FN_GetUser($user);

    if (isset($user['group']))
    {
        $usergroups = explode(",", $user['group']);
        if (is_array($usergroups))
        {
            $groups = explode(",", $group);
            foreach ($groups as $group)
            {
                if (in_array($group, $usergroups))
                {
                    // dprint_r(" $user, $group true");
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $groupname
 */
function FN_CreateGroupIfNotExists($groupname)
{
    global $_FN;
    $table = FN_XmlTable("fn_groups");
    $old = $table->GetRecordByPrimaryKey($groupname);
    if (!isset($old['groupname']))
    {
        $table->InsertRecord(array("groupname" => $groupname));
    }
}

/**
 * Get file extension
 * @param string $filename
 * @return string
 */
function FN_GetFileExtension($filename)
{
    if (!strstr($filename, "."))
        return "";
    $tmp = explode(".", $filename);
    $extension = $tmp[count($tmp) - 1];
    return $extension;
}

/**
 *
 * @global array $_FN 
 */
function FN_InitTables($force = false)
{
    global $_FN;
    if (!is_writable($_FN['datadir']))
        return;
    if (!file_exists($_FN['datadir'] . "/_cache"))
        FN_MkDir($_FN['datadir'] . "/_cache");
    if (!empty($_FN['use_cache']) && !file_exists("{$_FN['datadir']}/_cache/html"))
    {
        FN_MkDir("{$_FN['datadir']}/_cache/html");
    }

    if (!file_exists("{$_FN['datadir']}/{$_FN['database']}"))
    {
        $ret = mkdir("{$_FN['datadir']}/{$_FN['database']}");
        if (!$ret)
            dprint_r("error create folder: {$_FN['datadir']}/{$_FN['database']}");
    }
    if ($force || !file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_i18n.php"))
    {
        FN_Copy("include/install/fndatabase/fn_i18n.php", "{$_FN['datadir']}/{$_FN['database']}/fn_i18n.php");
    }
    if ($force || !file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_sections.php"))
    {
        FN_Copy("include/install/fndatabase/fn_sections.php", "{$_FN['datadir']}/{$_FN['database']}/fn_sections.php");
    }
    if ($force || !file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_sectionstypes.php"))
    {
        FN_Copy("include/install/fndatabase/fn_sectionstypes.php", "{$_FN['datadir']}/{$_FN['database']}/fn_sectionstypes.php");
    }
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_blocks.php"))
    {
        FN_Copy("include/install/fndatabase/fn_blocks.php", "{$_FN['datadir']}/{$_FN['database']}/fn_blocks.php");
    }
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_settings.php"))
    {
        FN_Copy("include/install/fndatabase/fn_settings.php", "{$_FN['datadir']}/{$_FN['database']}/fn_settings.php");
    }
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_users.php"))
    {
        if (file_exists("include/install/fndatabase/fn_users.custom.php"))
            FN_Copy("include/install/fndatabase/fn_users.custom.php", "{$_FN['datadir']}/{$_FN['database']}/fn_users.php");
        else
            FN_Copy("include/install/fndatabase/fn_users.php", "{$_FN['datadir']}/{$_FN['database']}/fn_users.php");
    }
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_groups.php"))
    {
        FN_Copy("include/install/fndatabase/fn_groups.php", "{$_FN['datadir']}/{$_FN['database']}/fn_groups.php");
        $table = FN_XmlTable("fn_groups");
        $r['groupname'] = 'users';
        $table->InsertRecord($r);
    }
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_avatars") && file_exists("include/install/fndatabase/fn_avatars"))
    {
        FN_CopyDir("include/install/fndatabase/fn_avatars", "{$_FN['datadir']}/{$_FN['database']}/");
    }
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_avatars.php") && file_exists("include/install/{$_FN['database']}/fn_avatars.php"))
        FN_Copy("include/install/fndatabase/fn_avatars.php", "{$_FN['datadir']}/{$_FN['database']}/fn_avatars.php");
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_conditions.php"))
    {
        FN_Copy("include/install/fndatabase/fn_conditions.php", "{$_FN['datadir']}/{$_FN['database']}/fn_conditions.php");
        $tcond = FN_XmlForm("fn_conditions");
        $conditions = $tcond->xmltable->GetRecords();
        if (!is_array($conditions) || count($conditions) == 0)
        {

            $value['text'] = file_get_contents("modules/login/conditions/conditions.en.html");
            $value['text_it'] = file_get_contents("modules/login/conditions/conditions.it.html");
            $value['text_en'] = file_get_contents("modules/login/conditions/conditions.en.html");
            $value['text_de'] = file_get_contents("modules/login/conditions/conditions.de.html");
            $value['text_es'] = file_get_contents("modules/login/conditions/conditions.es.html");
            $value['text_fr'] = file_get_contents("modules/login/conditions/conditions.fr.html");
            $value['enabled'] = 1;
            $nv = $tcond->xmltable->InsertRecord($value);
        }
    }
    FN_InitSections();
    FN_InitBlocks();
}

/**
 *
 * @param string $tablename
 * @param array $params
 * @return object
 */
function FN_XmlTable($tablename, $params = array())
{
    global $_FN;
    if (!isset($params['defaultdriver']))
        $params['defaultdriver'] = $_FN['default_database_driver'];
    return xmldb_table("{$_FN['database']}", $tablename, $_FN['datadir'], $params);
}

/**
 *
 * @param string $tablename
 * @param array $params
 * @return object
 */
function FN_XmlForm($tablename, $params = array())
{
    global $_FN;
    $params['siteurl'] = $_FN['siteurl'];
    $params['charset_page'] = $_FN['charset_page'];
    $params['requiredtext'] = isset($_FN['requiredfieldsymbol']) ? $_FN['requiredfieldsymbol'] : "*";
    $t = xmldb_frm($_FN['database'], $tablename, $_FN['datadir'], $_FN['lang'], $_FN['languages'], $params);
    if (file_exists("themes/{$_FN['theme']}/form.tp.html"))
    {
        //$t->SetlayoutTemplate(file_get_contents("themes/{$_FN['theme']}/form.tp.html"));
    }
    return $t;
}

/**
 *
 * @global array $_FN
 * @param string $query
 * @return array 
 */
function FN_XMLQuery($query)
{
    global $_FN;
    $DB = new XMLDatabase($_FN['database']);
    return $DB->Query($query);
}

/**
 *
 * @param string $string
 * @return string
 */
function FN_StripPostSlashes($string)
{
    $magic_quotes = (bool) ini_get('magic_quotes_gpc');
    if ($magic_quotes)
        return stripslashes($string);
    else
        return ($string);
}

/**
 *
 * @global array $_FN
 * @param string $time
 * @return string
 */
function FN_GetDateTime($time)
{
    global $_FN;
    if (strlen("$time") == 19)
    {
        $time = strtotime($time);
    }
    if (!$time)
    {
        $time = time();
    }
    $ret = $_FN['days'][date("w", $time + (3600 * $_FN['jet_lag']))];
    $ret .= date(" d ", $time + (3600 * $_FN['jet_lag']));
    $tmp = date(" m", $time + (3600 * $_FN['jet_lag']));
    if ($tmp < 10)
        $tmp = str_replace("0", "", $tmp);
    $ret .= $_FN['months'][$tmp - 1];
    $ret .= date(" Y - ", $time + (3600 * $_FN['jet_lag']));
    $ret .= date("H:", $time + (3600 * $_FN['jet_lag']));
    $ret .= date("i", $time + (3600 * $_FN['jet_lag']));
    return $ret;
}

/**
 * 
 * @global array $_FN
 * @param type $time
 * @param type $showtime
 * @return type
 */
function FN_FormatDate($time, $showtime = true)
{
    global $_FN;
    if (strlen("$time") == 19 || !is_numeric($time))
    {
        $time = strtotime($time);
    }
    $_FN['jet_lag'] = intval($_FN['jet_lag']);
    $ret = $_FN['days'][date("w", $time + (3600 * $_FN['jet_lag']))];
    $ret .= date(" d ", $time + (3600 * $_FN['jet_lag']));
    $tmp = date(" m", $time + (3600 * $_FN['jet_lag']));
    if ($tmp < 10)
        $tmp = str_replace("0", "", $tmp);
    $ret .= $_FN['months'][$tmp - 1];
    $ret .= date(" Y ", $time + (3600 * $_FN['jet_lag']));
    if ($showtime)
    {
        $ret .= date("- H:", $time + (3600 * $_FN['jet_lag']));
        $ret .= date("i", $time + (3600 * $_FN['jet_lag']));
    }
    return $ret;
}

/**
 *
 * @global array $_FN
 * @return bool
 */
function FN_IsExternalReferer()
{
    global $_FN;
    if (empty($_SERVER['HTTP_REFERER']) || !FN_erg($_FN['siteurl'], $_SERVER['HTTP_REFERER']))
    {
        return true;
    }
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $to
 * @param string $subject
 * @param string $body
 * @param bool $ishtml
 * @param type $from
 * @return bool
 */
function FN_SendMail($to, $subject, $body, $ishtml = false, $from = "")
{
    global $_FN;
    $replyto = $from;
    if ($from == "")
    {
        $from = "\"{$_FN['sitename']}\" <{$_FN['site_email_address']}>";
        $replyto = $_FN['site_email_address'];
    }
    if (!empty($_FN['FN_SendMail']) && $_FN['FN_SendMail'] != "FN_SendMail")
    {
        return $_FN['FN_SendMail']($to, $subject, $body, $ishtml, $from);
    }
    if ($to != "")
    {
        if ($ishtml)
        {
            $headers = "MIME-Version: 1.0\n" .
                    "Content-type: text/html; charset=\"utf-8\"\n" .
                    "From: $from\n" .
                    "Reply-To: {$replyto}\n" .
                    "X-Mailer: PHP/" . phpversion();
        }
        else
        {
            $headers = "MIME-Version: 1.0\n" .
                    "Content-Type: text/plain; charset = \"utf-8\"\n";
            $headers .= "From: $from\n" .
                    "Reply-To: {$replyto}\n" .
                    "X-Mailer: PHP/" . phpversion();
        }
        $message = FN_FixNewline($body);
        $headers = FN_FixNewline($headers);
        if (@mail($to, $subject, $message, $headers))
        {
            return true;
        }
    }
    return false;
}

/**
 * convert newline in correct format
 *
 * @param string $text
 */
function FN_FixNewline($text)
{
    if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN'))
    {
        $eol = "\r\n";
    }
    elseif (strtoupper(substr(PHP_OS, 0, 3) == 'MAC'))
    {
        $eol = "\r";
    }
    else
    {
        $eol = "\n";
    }
//fix newline
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "", $text);
    $text = str_replace("\n", "$eol", $text);
    return $text;
}

/**
 *
 */
function FN_IsSpam()
{
    return false;
}

/**
 *
 * @global array $_FN 
 */
function FN_FixSections()
{
    global $_FN;
    $sections = $_FN['sections'];
    $flag_mod = false;
    foreach ($sections as $section)
    {
        if ($section['parent'] != "")
        {
            if (!isset($sections[$section['parent']]))
            {
                $section['parent'] = "";
                $table = FN_XmlTable("fn_sections");
                $table->UpdateRecord($section);
                $flag_mod = true;
            }
        }
    }
    if ($flag_mod)
    {
        $_FN['sections'] = FN_GetAllSections();
    }
}

/**
 * 
 * @global array $_FN
 * @return type
 */
function FN_GetAllBlocks()
{
    global $_FN;
    if (!empty($_FN['blocks']))
        return $_FN['blocks'];
    $table = FN_XmlForm("fn_blocks");
    $all = $table->xmltable->GetRecords();
    if (!is_array($all))
        return array();
    $all = xmldb_array_natsort_by_key($all, "position");
    $allByKey = array();
    foreach ($all as $item)
    {
        $allByKey[$item['id']] = $item;
    }
    $_FN['blocks'] = $allByKey;
    return $allByKey;
}

/**
 * 
 * @global array $_FN
 * @return type
 */
function FN_GetAllSections()
{
    global $_FN;
    if (!empty($_FN['sections']))
    {
        return $_FN['sections'];
    }
    $table = FN_XmlForm("fn_sections");
    $all = $table->xmltable->GetRecords();
    if (!is_array($all))
        return array();
    $all = xmldb_array_natsort_by_key($all, "position");
    $allByKey = array();
    $suffix = FN_LangSuffix();

    foreach ($all as $item)
    {
        $allByKey[$item['id']] = $item;
        if (!empty($allByKey[$item['id']]['title' . $suffix]))
            $allByKey[$item['id']]['title'] = $allByKey[$item['id']]['title' . $suffix];
        if (!empty($allByKey[$item['id']]['description' . $suffix]))
            $allByKey[$item['id']]['description'] = $allByKey[$item['id']]['description' . $suffix];
    }
    $_FN['sections'] = $allByKey;
    return $allByKey;
}

/**
 * 
 * @global array $_FN
 * @return type
 */
function FN_GetAllSectionTypes()
{
    global $_FN;
    if (!empty($_FN['sectionstypes']))
    {
        return $_FN['sectionstypes'];
    }
    $table = FN_XmlForm("fn_sectionstypes");
    $all = $table->xmltable->GetRecords();
    if (!is_array($all))
        return array();

    $allByKey = array();
    foreach ($all as $item)
    {
        $allByKey[$item['name']] = $item;
    }
    $_FN['sectionstypes'] = $allByKey;
    return $allByKey;
}

/**
 * Get Sections
 *
 * @global array $_FN
 * @param string $section
 * @param array $recursive
 * @param bool $onlyreadable
 * @param bool $hidden
 * @param bool $onlyenabled
 * @param type $nocache
 * @return array 
 */
function FN_GetSections($section = "", $recursive = false, $onlyreadable = true, $hidden = false, $onlyenabled = true, $nocache = false)
{
    global $_FN;
    static $cache = false;
    static $allsections = false;
    if ($nocache || !$allsections)
    {
        if (empty($_FN['sections']) || $nocache)
        {
            $_FN['sections'] = false;
            $_FN['sections'] = FN_GetAllSections();
        }
        $cache = array();
        $allsections = $_FN['sections'];
    }
    if ($section === false)
        $section = "";
    $idcache = $section . "|" . $recursive . "|" . $onlyreadable . "|" . $hidden . "|" . $onlyenabled;
    if (isset($cache[$idcache]))
    {
        return $cache[$idcache];
    }
    $sect_db = array();
//---------------------   get all sections from database   -------------------->


    if ($recursive)
    {
        $sections = $allsections;
    }
    else
    {
        foreach ($allsections as $sectionvalues)
        {
            $parents[$sectionvalues['parent']][] = $sectionvalues;
        }
        $sections = isset($parents[$section]) ? $parents[$section] : array();
    }


//---------------------   get all sections from database   --------------------<
//dprint_r($sections);

    foreach ($sections as $sectionvalues)
    {

        if (!file_exists("sections/{$sectionvalues['id']}"))
        {
            continue;
        }
        //only readable
        if ($onlyreadable)
        {
            if (!FN_UserCanViewSection($sectionvalues['id']))
                continue;
        }
//not hidden
        if (!$hidden)
        {
            if (!empty($sectionvalues['hidden']))
                continue;
        }
//sections enabled
        if ($onlyenabled)
        {
            if (!FN_SectionIsEnabled($sectionvalues['id']))
                continue;
        }

        $sectionvalues['link'] = FN_RewriteLink("index.php?mod={$sectionvalues['id']}");
        $suffix = FN_LangSuffix();

        if (empty($sectionvalues["title" . $suffix]))
        {
            if ($_FN['lang'] == $_FN['lang_default'] && empty($sectionvalues["title"]))
            {
                $sectionvalues['title'] = "_{$_FN['lang_default']}_ $suffix __" . FN_GetFolderTitle("sections/{$sectionvalues['id']}");
            }
        }
        else
            $sectionvalues['title'] = FN_ConvertEncoding($sectionvalues["title" . FN_LangSuffix()], "UTF-8", $_FN['charset_page']);
        $title = $sectionvalues['title'];
        if (empty($sectionvalues['image']))
            $sectionvalues['image'] = FN_FromTheme("sections/{$sectionvalues['id']}/icon.png", false);
        if (!file_exists($sectionvalues['image']))
            $sectionvalues['image'] = FN_FromTheme("images/section.png", false);
        $siteurl = empty($_FN['use_urlserverpath']) ? $_FN['siteurl'] : $_FN['sitepath'];
        $sectionvalues['image'] = $siteurl . $sectionvalues['image'];
        FN_GetAccessKey($title, "index.php?mod={$sectionvalues['id']}", $sectionvalues['accesskey']);
        $sect_db[$sectionvalues['id']] = $sectionvalues;
    }
    //dprint_r( $sect_db);
    //------------------make section tree-------------------------------------->
    foreach ($sect_db as $section)
    {
        $pathParents = array();
        $parentId = $section['parent'];
        while ($parentId != "" && !in_array($parentId, $pathParents))
        {
            $pathParents[] = $parentId;
            $parentId = isset($sect_db[$parentId]['parent']) ? $sect_db[$parentId]['parent'] : "";
        }
        $sect_db[$section['id']]['path'] = array_reverse($pathParents);
    }
    //------------------make section tree--------------------------------------<
    $cache[$idcache] = $sect_db;

    return $sect_db;
}

/**
 *
 * @global array $_FN
 * @param string $where
 * @return array
 */
function FN_GetBlocks($where, $onlyreadable = true, $onlyenabled = true)
{
    global $_FN;
    $blocks = $_FN['blocks'];
    $ret_blocks = array();
    foreach ($blocks as $blockvalues)
    {
        if ($where != $blockvalues['where'])
        {
            continue;
        }
        if (!file_exists("blocks/{$blockvalues['id']}"))
            continue;
        if ($onlyreadable && FN_BlockIsEnabled($blockvalues['id']) == false)
        {
            continue;
        }
        if ($onlyenabled && FN_UserCanViewBlock($blockvalues['id']) == false)
        {
            continue;
        }
        //--language from module or section ----->
        FN_LoadMessagesFolder($_FN['filesystempath'] . "/blocks/{$blockvalues['id']}");
        if (!empty($blockvalues['type']))
        {
            FN_LoadMessagesFolder($_FN['filesystempath'] . "/modules/{$blockvalues['type']}");
        }
        //--language from module or section -----<
        if (empty($blockvalues["title" . FN_LangSuffix()]))
        {
            $blockvalues['title'] = FN_GetFolderTitle("blocks/{$blockvalues['id']}");
        }
        else
            $blockvalues['title'] = $blockvalues["title" . FN_LangSuffix()];

        if ($blockvalues['hidetitle'])
            $blockvalues['title'] = "";
        $ret_blocks[$blockvalues['id']] = $blockvalues;
    }
    return $ret_blocks;
}

/**
 *
 * @param string $section
 * @return array
 */
function FN_GetBlockValues($section, $usecache = true)
{
    global $_FN;
    static $cache = array();
    static $cachesections = false;
    if (!$usecache)
    {
        $_FN['blocks'] = FN_GetAllBlocks();
        $cachesections = false;
        $cache = array();
    }
    if (isset($cache[$_FN['lang']][$section]))
    {
        return $cache[$_FN['lang']][$section];
    }
    if (!$cachesections)
    {
        $cachesections = $_FN['blocks'];
    }
    if (!isset($cachesections[$section]))
    {
        return false;
    }
    $values = $cachesections[$section];
    if (empty($values["title" . FN_LangSuffix()]))
    {
        $values['title'] = FN_GetFolderTitle("blocks/$section");
    }
    else
    {
        $values['title'] = $values["title" . FN_LangSuffix()];
    }
    $cache[$_FN['lang']][$section] = $values;
    return $values;
}

/**
 *
 * @param string $section
 * @return array
 */
function FN_GetSectionValues($section, $usecache = true)
{
    global $_FN;
    static $cache = array();
    static $cachesections = false;
    if (!$usecache)
    {
        $_FN['sections'] = FN_GetAllSections();
        $cachesections = false;
    }
    if ($usecache && isset($cache[$_FN['lang']][$section]))
    {
        return $cache[$_FN['lang']][$section];
    }
    if (!$cachesections)
    {
        $cachesections = array();
        $cachesections = $_FN['sections'];
    }
    if (!isset($cachesections[$section]))
    {
        return false;
    }
    $values = $cachesections[$section];
    if (empty($values["title" . FN_LangSuffix()]))
    {
        if ($_FN['lang'] != FN_LangSuffix() && $values["title"] == "")
        {
            $values['title'] = FN_GetFolderTitle("sections/$section");
        }
    }
    else
    {
        $values['title'] = $values["title" . FN_LangSuffix()];
    }
    $values['link'] = FN_RewriteLink("index.php?mod={$values['id']}", "", true);
    $cache[$_FN['lang']][$section] = $values;
    return $values;
}

/**
 *
 * @global array $_FN
 * @param string $lang
 * @return string 
 */
function FN_LangSuffix($lang = "")
{
    global $_FN;
    if ($lang == "")
        $lang = $_FN['lang'];
    /* 	if ( $lang == $_FN['lang_default'] )
      return "";
      else */
    return "_$lang";
}

/**
 *
 * @global array $_FN
 * @param string $section
 * @return bool
 */
function FN_SectionIsEnabled($section = "")
{
    global $_FN;
    if ($section == "")
        $section = $_FN['mod'];
    $section = FN_GetSectionValues($section);
    if (empty($section['status']))
        return false;
    $curtime = FN_Time();
    if ($section['startdate'] != "" && $curtime < strtotime($section['startdate']))
    {
        return false;
    }
    if ($section['enddate'] != "" && $curtime > strtotime($section['enddate']))
    {
        return false;
    }
    return true;
}

/**
 *
 * @param string $section
 */
function FN_SectionIsHidden($section = "")
{
    if ($section == "")
        $section = $_FN['mod'];
    $section = FN_GetSectionValues($section);
    if (!empty($section['hidden']))
        return true;
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $block
 * @return bool
 */
function FN_BlockIsEnabled($block)
{
    global $_FN;
    $block = FN_GetBlockValues($block);
    if (isset($_FN['sectionvalues']['blocks']) && !empty($_FN['sectionvalues']['blocksmode']))
    {
        $blocks = explode(",", $_FN['sectionvalues']['blocks']);
        if ($_FN['sectionvalues']['blocksmode'] == "hide")
        {
            if (in_array($block['id'], $blocks))
                return false;
        }
        elseif ($_FN['sectionvalues']['blocksmode'] == "show")
        {
            if (!in_array($block['id'], $blocks))
                return false;
        }
    }

    if (!empty($block['blocksmode']))
    {
        $sections = explode(",", $block['sections']);
        if ($block['blocksmode'] == "hide")
        {
            if (in_array($_FN['sectionvalues']['id'], $sections))
                return false;
        }
        elseif ($block['blocksmode'] == "show")
        {
            if (!in_array($_FN['sectionvalues']['id'], $sections))
                return false;
        }
    }

    if (empty($block['status']))
        return false;
    $curtime = FN_Time();
    if ($block['startdate'] != "" && $curtime < strtotime($block['startdate']))
    {
        return false;
    }
    if ($block['enddate'] != "" && $curtime > strtotime($block['enddate']))
    {
        return false;
    }
    return true;
}

/**
 *
 * @param string $param
 */
function FN_SaveGetPostParam($param,$ignore_post = false,$ignore_get = false,$ignore_cookie=false)
{
    global $_FN;
    $retparam = false;
    if (!$ignore_cookie && isset($_COOKIE [$param]))
    {
        $retparam = $_COOKIE [$param];
    }
    if (!$ignore_post && isset($_POST [$param]) && !is_array($_POST [$param]))
    {
        if (!$ignore_cookie)
        {        
            $_COOKIE [$param] = $_POST [$param];
            setcookie($param, $_POST [$param], time() + 999999999, $_FN ['urlcookie']);
        }
        $retparam = FN_StripPostSlashes($_POST [$param]);
    }
    elseif (!$ignore_get && isset($_GET [$param]) && !is_array($_GET [$param]))
    {
        if (!$ignore_cookie)
        {        
            $_COOKIE [$param] = $_GET [$param];
            setcookie($param, $_GET [$param], time() + 999999999, $_FN ['urlcookie']);
        }
        $retparam = FN_StripPostSlashes($_GET [$param]);
    }
    return $retparam;
}

/**
 *
 * @param string $filename
 * @return string
 */
function FN_GetIconByFilename($filename)
{
    $ext = FN_GetFileExtension($filename);
    $ext = strtolower($ext);
    $dimg = "unknown.png";
    switch ($ext)
    {
        case "sh" :
            $dimg = "binhex.png";
            break;
        case "xhtml" :
        case "html" :
        case "htm" :
            $dimg = "web.png";
            break;
        case "inc" :
        case "txt" :
        case "xml" :
        case "css" :
        case "" :
            $dimg = "text.png";
            break;
        case "png" :
        case "bmp" :
        case "jpg" :
        case "jpeg" :
        case "ico" :
        case "gif" :
            $dimg = "image.png";
            break;
        case "zip" :
        case "gz" :
            $dimg = "compressed.png";
            break;
        case "mp3" :
        case "wav" :
            $dimg = "sound.png";
            break;
        case "wma" :
        case "mpeg" :
        case "rm" :
            $dimg = "movie.png";
            break;
        default :
            if (file_exists("images/mime/$ext.png"))
                $dimg = "$ext.png";
            break;
    }
    return FN_FromTheme("images/mime/$dimg");
}

/**
 *
 * @param string $tablename
 */
function FN_GetVarsFromTable($tablename)
{
    $Table = FN_XmlTable($tablename);
    $items = $Table->GetRecords();
    $var = array();
    if (is_array($items))
        foreach ($items as $item)
        {
            if (isset($item['varname']) && isset($item['varvalue']))
                $var[$item['varname']] = $item['varvalue'];
        }
    return $var;
}

/**
 * 
 * @param type $var
 * @param type $tablename
 * @param type $configvars
 * @param array $ignore
 * @return type
 */
function FN_LoadVarsFromTable(&$var, $tablename, $configvars = array(), $ignore = array())
{
    $Table = FN_XmlTable($tablename);
    if (!is_array($ignore))
        $ignore = array();
    $vars_in_table_assoc = FN_GetVarsFromTable($tablename);
    if (is_array($var))
    {
        //---clear obsolete vars----------------------------------------------->
        foreach ($vars_in_table_assoc as $k => $v)
        {
            if (is_array($configvars) && count($configvars) > 0 && !in_array($k, $configvars))
            {
                $Table->DelRecord($k);
            }
        }
        //---clear obsolete vars-----------------------------------------------<
        $settingsByKey = array();
        $settings = $Table->GetRecords();
        if (is_array($settings))
            foreach ($settings as $v)
            {
                if (isset($v['varname']))
                {
                    $settingsByKey[$v['varname']] = $v;
                }
            }
        foreach ($var as $k => $v)
        {
            if (in_array($k, $ignore))
                continue;
            if (!in_array($k, $configvars))
                continue;

            //$old = $Table->GetRecordByPrimaryKey($k);
            $old = isset($settingsByKey[$k]) ? $settingsByKey[$k] : array();
            if (!@array_key_exists('defaultvalue', $old))
            {
                $Table->InsertRecord(array("varname" => $k, "varvalue" => $v, "defaultvalue" => $v));
            }
            else
            {
                if ($old['defaultvalue'] != $v)
                {
                    $Table->UpdateRecord(array("varname" => $k, "defaultvalue" => $v));
                }
            }
            if (isset($old['varvalue']))
                $var[$k] = $old['varvalue'];
        }
    }

    return $var;
}

/**
 *
 * @global array $_FN
 * @param string $folder 
 */
function FN_GetMessagesFromFolder($folder)
{
    global $_FN;
    $tmp = array();
    $tmp_theme = false;
    $rel_folder = str_replace($_FN['filesystempath'], "", $folder);
    // die ($rel_folder);
    if (file_exists("$folder/languages/{$_FN['lang']}/lang.csv"))
    {
        $foldertheme = FN_FromTheme("$folder/languages/{$_FN['lang']}/lang.csv");
        $tmp = FN_GetMessagesFromCsv("$folder/languages/{$_FN['lang']}/lang.csv");
    }
    elseif (file_exists("$folder/languages/en/lang.csv"))
    {
        $tmp = FN_GetMessagesFromCsv("$folder/languages/en/lang.csv");
    }
    if (file_exists("themes/{$_FN['theme']}/$rel_folder/languages/{$_FN['lang']}/lang.csv"))
    {
        $tmp_theme = FN_GetMessagesFromCsv("themes/{$_FN['theme']}/$rel_folder/languages/{$_FN['lang']}/lang.csv");
        $tmp = array_merge($tmp, $tmp_theme);
    }

    return $tmp;
}

/**
 *
 * @param string $filename
 */
function FN_GetMessagesFromCsv($filename)
{
    static $messages = array();
    if (!file_exists($filename))
        return $messages;
    if (isset($messages[$filename]))
        return $messages[$filename];
    $messages[$filename] = array();
    $first = true;
    $handle = fopen("$filename", "r");
    while (($data = fgetcsv($handle, 5000, ",")) !== false)
    {
        if ($first == true)
        {
            $first = false;
            continue;
        }
        if (isset($data[1]))
        {
            $messages[$filename][$data[0]] = $data[1];
        }
    }
    fclose($handle);
    return $messages[$filename];
}

/**
 *
 * @global array $_FN
 * @global array $_FNMESSAGES
 * @param string $filename
 */
function FN_LoadMessagesFolder($folder)
{
    global $_FNMESSAGE, $_FN;
    $tmp = FN_GetMessagesFromFolder($folder);
    if (is_array($tmp))
        foreach ($tmp as $k => $v)
        {
            $_FNMESSAGE[$_FN['lang']][$k] = $v;
        }
    if (!empty($_FNMESSAGE[$_FN['lang']]["_CHARSET"]))
    {
        $_FN['charset_lang'] = $_FNMESSAGE[$_FN['lang']]["_CHARSET"];
    }
}

/**
 *
 * @global array $_FN
 * @param string $path
 * @param string $title
 * @param string $lang
 */
function FN_SetFolderTitle($path, $title, $lang = "")
{
    global $_FN;
    if ($lang == "")
        $lang = $_FN['lang'];
    FN_Write($title, "$path/title.$lang.fn");
}

/**
 *
 * @global array $_FN
 * @param string $path
 * @return string
 */
function FN_GetFolderTitle($path, $lang = "")
{
    global $_FN;
    if ($lang == "")
        $lang = $_FN['lang'];
    $title = "";
    if (!is_dir($path))
    {
        if (file_exists("$path.$lang.fn"))
        {
            $title = file_get_contents("$path.$lang.fn");
        }
        elseif (file_exists("$path.i18n.fn"))
        {
            $title = FN_Translate(file_get_contents("$path.i18n.fn"), "Aa", $lang);
        }
        else
        {
            $title = basename($path);
        }
        return $title;
    }
    elseif (file_exists("$path/title.$lang.fn"))
        $title = file_get_contents("$path/title.$lang.fn");
    elseif (file_exists("$path/title.i18n.fn"))
        $title = FN_Translate(file_get_contents("$path/title.i18n.fn"), "Aa", $lang);
    elseif (file_exists("$path/title.{$_FN['lang_default']}.fn"))
        $title = file_get_contents("$path/title.{$_FN['lang_default']}.fn");
    elseif (file_exists("$path/title.en.fn"))
        $title = file_get_contents("$path/title.en.fn");
    if ($title === "")
        $title = basename($path);
    $title = str_replace("{siteurl}", $_FN['siteurl'], $title);
    $title = str_replace("\n", "", $title);
    $title = str_replace("\r", "", $title);

    return $title;
}

/**
 *
 * @global array $_FN
 * @param string file
 * @param string $sectionid
 * @return array 
 */
function FN_LoadConfig($fileconfig = "", $sectionid = "", $usecache = true)
{
    //dprint_r($fileconfig);
    global $_FN;
    static $cache = array();
    if (!$usecache)
        $cache = array();
    $tablename = "";

    //---------------------------- empty fileconfig --------------------------->

    if ($fileconfig == "")
    {
        if ($_FN['block'] != "")
        {
            $blockvalues = FN_GetBlockValues($_FN['block']);
            $module = $blockvalues['type'];
            if (file_exists("modules/$module/config.php"))
            {
                $fileconfig = "modules/{$module}/config.php";
            }
            else
            {
                $fileconfig = "blocks/{$_FN['block']}/config.php";
            }
        }
        else
        {
            if (!empty($_FN['sectionvalues']['type']))
            {
                $fileconfig = "modules/{$_FN['sectionvalues']['type']}/config.php";
            }
            else
            {
                if ($sectionid == "")
                {
                    $sectionid = $_FN['mod'];
                }
                $fileconfig = "sections/{$sectionid}/config.php";
            }
        }
    }
    //---------------------------- empty fileconfig ---------------------------<


    if (preg_match("/^blocks/is", $fileconfig) || preg_match("/^sections/is", $fileconfig) || preg_match("/^modules/is", $fileconfig))
    {
        if ($_FN['block'] != "")
        {
            $sectionid = $_FN['block'];
            $tablename = "fncf_block_{$sectionid}";
        }

        if ($sectionid == "")
        {
            $sectionid = $_FN['mod'];
        }

        if ($sectionid !== "" && $_FN['block'] == "")
        {

            $tablename = "fncf_{$sectionid}";
            if (file_exists("sections/$sectionid/default.xml.php"))
            {

                $sectionvalues = FN_GetSectionValues($sectionid);
                if (!empty($sectionvalues['type']))
                {
                    $default = xmldb_xml2array(file_get_contents("sections/$sectionid/default.xml.php"), "fncf_{$sectionvalues['type']}");
                    $default = (isset($default[0]) && is_array($default[0])) ? $default[0] : array();
                }
            }
        }
    }
    else
    {
        if ($fileconfig === "config.php" || $fileconfig === "./config.php")
        {
            $tablename = "fn_settings";
        }
        else
        {
            $tablename = str_replace("/", "_s_", dirname($fileconfig));
            $tablename = str_replace("\\", "_b_", $tablename);
            $tablename = str_replace(".", "_d_", $tablename);
        }
    }

    // dprint_r_arrayxml($tablename);
    // @ob_end_flush();

    if (!empty($cache["$tablename"]))
    {

        return $cache["$tablename"];
    }
    if ($tablename != "" && !file_exists("{$_FN['datadir']}/fndatabase/$tablename.php"))
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<?php exit(0);?>
<tables>
	<field>
		<name>varname</name>
		<type>string</type>
		<frm_help_it></frm_help_it>
		<frm_required>1</frm_required>
		<primarykey>1</primarykey>
	</field>
	<field>
		<name>varvalue</name>
		<type>string</type>
	</field>
	<field>
		<name>defaultvalue</name>
		<type>string</type>
		<frm_show>0</frm_show>
	</field>
	<filename>settings</filename>
</tables>";
        FN_Write($xml, "{$_FN['datadir']}/fndatabase/$tablename.php");
    }

    $config = array();
    $fields = false;
    if (file_exists($fileconfig))
    {
        include "$fileconfig";
        if (!empty($default) && is_array($default))
        {
            $config = array_merge($config, $default);
        }
        $fields = array_keys($config);
    }
    if ($tablename != "")
    {
        FN_LoadVarsFromTable($config, $tablename, $fields);

        $cache[$tablename] = $config;
    }
    if (isset($config['id']))
    {
        unset($config['id']);
    }

    return $config;
}

/**
 *
 * @param string $str
 * @param string $filefolder
 * @return string 
 */
function FN_RewriteLinksAbsoluteToLocal($str, $filefolder)
{
    global $_FN;
    $dirtarget = $_FN['siteurl'] . $filefolder . "/";
    $str = str_replace("'" . $dirtarget, "'", $str);
    $str = str_replace('"' . $dirtarget, '"', $str);
    $reldirarray = explode("/", $filefolder);
    $r = "";
    foreach ($reldirarray as $s)
    {
        if ($s !== "")
        {
            $r .= "../";
        }
    }
    $str = str_replace('"' . $_FN['siteurl'], '"' . $r, $str);
    return $str;
}

/**
 *
 * @param string $section
 * @return bool 
 */
function FN_SectionExists($section)
{
    $ret = FN_GetSectionValues($section);
    if (isset($ret['id']))
        return true;
    return false;
}

/**
 *
 * @param string $sectiontitle
 */
function FN_MakeSectionId($sectiontitle)
{
    global $_FN;
    $sectionname = strtolower(str_replace(" ", "_", $sectiontitle));
    $sectionname = preg_replace("/" . @html_entity_decode("&agrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "a", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&egrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "e", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&igrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "i", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&ograve;", ENT_QUOTES, $_FN['charset_page']) . "/s", "o", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&ugrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "u", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Agrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "a", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Egrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "e", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Igrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "i", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Ograve;", ENT_QUOTES, $_FN['charset_page']) . "/s", "o", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Ugrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "u", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&aacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "a", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&eacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "e", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&iacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "i", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&oacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "o", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&uacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "u", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Aacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "a", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Eacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "e", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Iacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "i", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Oacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "o", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Uacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "u", $sectionname);
    $sectionname = preg_replace("/[^A-Z^a-z_0123456789]/s", "", $sectionname);
    $t = "";
    while (1)
    {
        $sectionname = $sectionname . $t;
        if (!FN_SectionExists($sectionname))
        {
            break;
        }
        else
        {
            if ($t == "")
                $t = 0;
        }
        $t++;
    }
    return $sectionname;
}

/**
 *
 * @param string $email
 * @return bool 
 */
function FN_CheckMail($email)
{
    if (preg_match('/^([a-z0-9_\.-])+@(([a-z0-9_-])+\.)+[a-z]{2,128}$/si', trim($email)))
        return true;
    else
        return false;
}

/**
 *
 * @param string $filecontents
 * @param string $filename
 * @param string $HeaderContentType ï¿½
 */
function FN_SaveFile($filecontents, $filename, $HeaderContentType = "application/force-download")
{
    while (false !== @ob_end_clean()
    );
    if (!$filename)
    {
        $filename = "esportazione.xls";
    }
    header("Content-Type: $HeaderContentType");
    header("Content-Disposition: inline; filename=$filename");
    echo "$filecontents";
    die();
}

/**
 * 
 * @param type $filename
 * @param type $delimiter
 * @param type $enclosure
 * @return type
 */
function FN_ReadCsvDatabase($filename, $delimiter, $enclosure = '"')
{
    $row = 1;
    if (!file_exists($filename))
        return array();
    $handle = fopen("$filename", "r");
    $ret = array();
    while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false)
    {
        if ($row === 1)
        {
            foreach ($data as $k)
            {
                while (isset($keys[$k]))
                {
                    $k .= "_";
                }
                $keys[$k] = $k;
            }
            $row++;
        }
        else
        {
            $num = 0;
            $tmp = array();
            foreach ($keys as $k => $val)
            {
                $tmp[$k] = isset($data[$num]) ? $data[$num] : "";
                $num++;
            }
            if ($tmp)
                $ret[] = $tmp;
            $row++;
        }
    }
    fclose($handle);
    return $ret;
}

/**
 * @param string $str
 * @param string $charsetFrom
 * @param string $charsetTo
 */
function FN_ConvertEncoding($str, $charsetFrom, $charsetTo)
{
    $str_ret = @XMLDB_ConvertEncoding($str, $charsetFrom, $charsetTo);
    if ($str_ret != "")
        return $str_ret;
    return $str;
}

/**
 *
 * @global array $_FN
 * @param string $folder
 * @param string $lang
 * @return array 
 */
function FN_LoadMessagesFromFolder($folder, $lang)
{
    global $_FN;
    $messages = array();
    if (file_exists("$folder/languages/$lang/lang.csv"))
    {
        $messages = FN_GetMessagesFromCsv("$folder/languages/$lang/lang.csv");
    }
    else
    if (file_exists("$folder/languages/{$_FN['lang_default']}/lang.csv"))
    {
        $messages = FN_GetMessagesFromCsv("$folder/languages/{$_FN['lang_default']}/lang.csv");
    }
    else
    if (file_exists("$folder/languages/en/lang.csv"))
    {
        $messages = FN_GetMessagesFromCsv("$folder/languages/en/lang.csv");
    }
    return $messages;
}

/**
 *
 * @global array $_FN
 * @param string $section_to_check_id
 * @param string $section
 * @return bool
 */
function FN_SectionIsInsideThis($section_to_check_id, $section = "")
{
    global $_FN;
    if ($section == "")
        $section = $_FN['mod'];
    $tmpsection = FN_GetSectionValues($section);
    $section_to_check = FN_GetSectionValues($section_to_check_id);
    while (isset($tmpsection['parent']) && $tmpsection['parent'] != false)
    {
        if (isset($tmpsection['parent']) && $tmpsection['parent'] == $section_to_check['id'])
        {
            return true;
        }
        $tmpsection = FN_GetSectionValues($tmpsection['parent']);
    }
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $str
 * @return string 
 */
function FN_FixEncoding($str)
{
    global $_FN;
    $charsetpage = empty($_FN['charset_page']) ? "UTF-8" : $_FN['charset_page'];
    return XMLDB_FixEncoding($str, $charsetpage);
}

/**
 *
 * @param string $english_string
 * @param string $uppercasemode
 * @param string $language
 * @return string 
 */
function FN_Translate($english_string, $uppercasemode = "Aa", $language = "")
{
    return FN_i18n($english_string, $language, $uppercasemode);
}

/**
 *
 * @global array $_FN
 * @param string $section
 * @return array 
 */
function FN_GetSectionsTree($section = "")
{
    global $_FN;
    if ($section == "")
    {
        $section = $_FN['mod'];
    }
    if ($section == "")
    {
        return array();
    }
    $section = FN_GetSectionValues($section);
    $section['active'] = true;
    if (!$section)
        return array();
    $tree[] = $section;
    $parents = array();
    while ($section['parent'] != "")
    {
        $section = FN_GetSectionValues($section['parent']);
        $section['active'] = "";
        if (in_array($section['id'], $parents))
            break;
        $tree[] = $section;
        $parents[] = $section['id'];
    }
    $tree = array_reverse($tree);
    return $tree;
}

/**
 *
 * @global array $_FN
 * @param string $varname
 * @return variant 
 */
function FN_GetSessionValue($varname)
{
    global $_FN;
    //---------------get sid--------------------------------------------------->
    $_FN['fnsid'] = FN_GetParam("fnsid", $_REQUEST, "html");
    if (empty($_FN['fnsid']))
        $_FN['fnsid'] = FN_GetParam("fnsid", $_COOKIE, "html");
    if (empty($_FN['fnsid']))
    {
        $_FN['fnsid'] = uniqid("_") . uniqid("x");
        setcookie("fnsid", $_FN['fnsid'], time() + 999999999, $_FN ['urlcookie']);
        $_COOKIE["fnsid"] = $_FN['fnsid'];
    }
    $_FN['return']['fnsid'] = $_FN['fnsid'];

    //---------------get sid---------------------------------------------------<
    if (empty($_FN['fnsid']))
    {
        return null;
    }
    if (file_exists("{$_FN['datadir']}/_sessions/{$_FN['fnsid']}.session"))
    {
        $var = unserialize(file_get_contents("{$_FN['datadir']}/_sessions/{$_FN['fnsid']}.session"));
        if (isset($var[$varname]))
            return $var[$varname];
    }
    return null;
}

/**
 *
 * @global type $_FN
 * @param string $key
 * @param variant $value 
 */
function FN_SetSessionValue($key, $value)
{
    global $_FN;
    FN_ClearOldSessions();
    //---------------get sid--------------------------------------------------->
    $_FN['fnsid'] = FN_GetParam("fnsid", $_COOKIE, "html");
    if (empty($_FN['fnsid']))
    {
        $_FN['fnsid'] = uniqid("1") . uniqid("0");
        setcookie("fnsid", $_FN['fnsid'], time() + 999999999, $_FN ['urlcookie']);
        $_COOKIE["fnsid"] = $_FN['fnsid'];
    }
    $_FN['return']['fnsid'] = $_FN['fnsid'];

    //---------------get sid---------------------------------------------------<
    if (!file_exists("{$_FN['datadir']}/_sessions/"))
    {
        FN_MkDir("{$_FN['datadir']}/_sessions");
    }
    $session = array();
    if (file_exists("{$_FN['datadir']}/_sessions/{$_FN['fnsid']}.session"))
    {
        $session = unserialize(file_get_contents("{$_FN['datadir']}/_sessions/{$_FN['fnsid']}.session"));
        //dprint_r("old:");
        //dprint_r($session);
    }
    if (is_array($value))
    {
        $session[$key] = array();
        foreach ($value as $k => $v)
        {
            $session[$key][$k] = $v;
        }
    }
    else
    {
        $session[$key] = $value;
    }
    FN_Write(serialize($session), "{$_FN['datadir']}/_sessions/{$_FN['fnsid']}.session");
}

/**
 * clean old files
 */
function FN_ClearOldSessions()
{
    global $_FN;
    $sessions = glob("{$_FN['datadir']}/_sessions/*.session");
    if (is_array($sessions))
        foreach ($sessions as $sessionfile)
        {
            if (time() - filectime($sessionfile) > 3600)
            {
                FN_Unlink($sessionfile);
            }
        }
}

/**
 * 
 * @global array $_FN
 * @param type $varname
 * @param type $maxtime
 * @return type
 */
function FN_GetGlobalVarValue($varname, $maxtime = false)
{
    global $_FN;
    $filename = "{$_FN['datadir']}/_cache/" . md5($varname) . ".cache";
    //$filename= sys_get_temp_dir()."/".md5($varname).".cache";

    if (file_exists($filename) && !FN_FileIsLocked($filename))
    {
        if ($maxtime && $maxtime > filectime($filename))
        {
            unlink($filename);
            return null;
        }
        $var = unserialize(file_get_contents($filename));
        if (!$var)
        {
            @unlink($filename);
            return null;
        }
        return $var;
    }
    return null;
}

/**
 * 
 * @global array $_FN
 * @param type $varname
 * @param type $value
 */
function FN_SetGlobalVarValue($varname, $value)
{
    global $_FN;
    //---------------get sid--------------------------------------------------->
    $filename = "{$_FN['datadir']}/_cache/" . md5($varname) . ".cache";
    //$filename= sys_get_temp_dir()."/".md5($varname).".cache";
    //---------------get sid---------------------------------------------------<
    if (!file_exists("{$_FN['datadir']}/_cache/"))
    {
        FN_MkDir("{$_FN['datadir']}/_cache");
    }
    if (!FN_FileIsLocked($filename))
    {
        FN_LockFile($filename);
        FN_Write($res = serialize($value), $filename);
        FN_UNLockFile($filename);
        if (!$res)
        {
            unlink($filename);
        }
        return true;
    }
    return false;
}

/**
 *
 * @param type $url 
 */
function FN_Redirect($url)
{
    while (false !== ob_get_clean()
    );
    header("location:$url");
    die();
}

/**
 *
 * @global array $_FN
 * @param string $varname
 * @return variant 
 */
function FN_GetUserSessionValue($varname)
{
    global $_FN;

    if (empty($_FN['user']))
    {
        return null;
    }
    $t = FN_XmlTable("fn_userssessions");
    $values = $t->GetRecord(array("username" => $_FN['user'], "varname" => $varname));
    if (isset($values['varname']))
    {
        return $values['varvalue'];
    }
    return null;
}

/**
 *
 * @global type $_FN
 * @param string $key
 * @param variant $value 
 */
function FN_SetUserSessionValue($varname, $value)
{
    global $_FN;
    if ($_FN['user'] == "")
        return;
    if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_userssessions.php"))
    {
        FN_Write("<?php exit(0);?>
<tables>
	<field>
		<name>id</name>
		<frm_show>0</frm_show>
		<extra>autoincrement</extra>
		<primarykey>1</primarykey>
	</field>
	<field>
		<name>username</name>
		<frm_it>Nome utente</frm_it>		
		<frm_show>onlyadmin</frm_show>
		<frm_setonlyadmin>1</frm_setonlyadmin>
	</field>
	<field>
		<name>varname</name>
	</field>
	<field>
		<name>varvalue</name>
	</field>
        <indexfield>username</indexfield>
</tables>
", "{$_FN['datadir']}/{$_FN['database']}/fn_userssessions.php");
    }
    $t = FN_XmlTable("fn_userssessions");
    $values = $t->GetRecord(array("username" => $_FN['user'], "varname" => $varname));
    if (isset($values['varname']))
    {
        $values['varvalue'] = $value;
        $t->UpdateRecord($values);
    }
    else
    {
        $t->InsertRecord(array("username" => $_FN['user'], "varname" => $varname, "varvalue" => $value));
    }
}

/**
 * 
 */
function FN_ClearCache()
{
    FN_RemoveDir("misc/_cache");
    mkdir("misc/_cache");
}

/**
 * 
 * @staticvar int $level
 * @param type $var
 */
function dprint_r_arrayxml($var)
{
    static $level = 0;
    if ($level == 0)
        echo "<pre style='border:1px solid red'>";
    if (is_array($var))
    {
        echo "\narray{\n";
        foreach ($var as $k => $v)
        {
            echo "\t[$k]{\n";
            $level++;

            dprin_r_arrayxml($v);
            $level--;
            echo "\n\t}\n";
        }
        echo "\n}\n";
    }
    else
    {
        if (is_string($var))
            echo htmlspecialchars($var);
        else
            print_r($var);
    }
    if ($level == 0)
        echo "</pre>";
}

/**
 * 
 * @global array $_FN
 * @param type $newvalues
 */
function FN_UpdateDefaultXML($newvalues)
{
    global $_FN;

    if (is_writable("sections/{$newvalues['id']}"))
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?php exit(0);?>\n<fn_sections>\n";
        foreach ($newvalues as $k => $v)
        {
            if ($k !== "id" && !is_array($v))
                $xml .= "\t<$k>" . htmlentities($v) . "</$k>\n";
        }
        $xml .= "</fn_sections>";
        //die ("{$_FN['datadir']}/fndatabase/fncf_{$newvalues['id']}.php");
        if ($newvalues['type'] != "" && file_exists("{$_FN['datadir']}/fndatabase/fncf_{$newvalues['id']}.php"))
        {

            $table = FN_XmlTable("fncf_{$newvalues['id']}");
            $values = $table->GetRecords();
            $xml .= "\n";
            $xml .= "\n<fncf_{$newvalues['type']}>\n";
            foreach ($values as $k => $v)
            {
                $xml .= "\t<{$v['varname']}>" . htmlentities($v['varvalue']) . "</{$v['varname']}>\n";
            }
            $xml .= "</fncf_{$newvalues['type']}>";
        }

        file_put_contents("sections/{$newvalues['id']}/default.xml.php", $xml);
    }
}



function FN_GetOpenAuthProviders()
{
    global $_FN;
    if (!file_exists("{$_FN['datadir']}/fndatabase/fn_oauth_providers.php"))
    {
        return array();
    }
    $table = FN_XmlTable("fn_oauth_providers");
    $recs = $table->GetRecords(array("enabled"=>1));   
    foreach ($recs as $k=>$rec)
    {
        $recs[$k]['urlimage']= $table->getFilePath($rec, "avatar");
        $recs[$k]['url']= $_FN['siteurl']."?fnloginprovider=".$rec['id'];
        
    }
    return $recs;
}


?>