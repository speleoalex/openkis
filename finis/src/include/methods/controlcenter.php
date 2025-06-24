<?php

/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
ini_set("display_errors", "on");
error_reporting(E_ALL);
ob_start();
global $_FN;
$_FN['controlcenter'] = basename($_FN['self'])."?fnapp=controlcenter&";

if (empty($_FN['controlcenter_theme']) || !file_exists("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}"))
    $_FN['controlcenter_theme'] = "classic";
if (file_exists("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/theme.php"))
    require_once("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/theme.php");

header("Content-Type: text/html; charset={$_FN['charset_page']}");
header("Cache-Control: no-cache");
header("Pragma: no-cache");
$_FN['theme'] = "base";
$_FN['fneditmode'] = "0";
$opt = FN_GetParam("opt", $_GET, "html");
$op = FN_GetParam("op", $_GET, "html");
$modcont = FN_GetParam("modcont", $_GET, "flat");
if ($opt == "")
{
    $section_enabled = FN_XMETADBQuery("SELECT * FROM fn_cc_users WHERE username LIKE '{$_FN['user']}'");
    if (!empty($section_enabled[0]['default']))
    {
        $opt = $section_enabled[0]['default'];
        $_GET['opt'] = $opt;
    }
}

//-------------------------init table cc_users--------------------------------->
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<?php exit(0);?>
<tables>
    <field>
        <name>username</name>
        <primarykey>1</primarykey>
    </field>
    <field>
        <name>ccsections</name>
        <frm_type>multiselect</frm_type>
        <frm_options>a,b</frm_options>
    </field>
    <field>
        <name>default</name>
        <frm_type>select</frm_type>
        <frm_options>a,b</frm_options>
    </field>
</tables>
';
if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_cc_users.php"))
{
    FN_Write($xml, "{$_FN['datadir']}/{$_FN['database']}/fn_cc_users.php");
}


//-------------------------init table cc_users---------------------------------<

$configsection = $sect = "";
$plugin = "";
$editconf = "";
$fileconfig_to_edit = "";
$_FN['mod'] = "";

$opt = FN_GetParam("opt", $_GET, "html");

$_FN['configsection'] = $configsection;
$vars = array();
$vars['sitelanguages'] = $_FN['sitelanguages'];
$vars['current_language'] = $vars['sitelanguages'][$_FN['lang']];
$vars['is_multilanguage'] = count($vars['sitelanguages']) > 1 ? true : "";

$vars['urlcontrolcenter'] = $_FN['siteurl'] . "/" . $_FN['controlcenter'] . "?fnapp=controlcenter";
$vars['version'] = htmlspecialchars(file_get_contents("{$_FN['src_finis']}/VERSION"));
$vars['cc_sectiongroup_title'] = FN_GetFolderTitle("{$_FN['src_finis']}/controlcenter/sections/" . dirname($opt) . "/"); //todo
$vars['cc_section_title'] = FN_GetFolderTitle("{$_FN['src_finis']}/controlcenter/sections/$opt/"); //todo
$vars['url_logo'] = "";
if (file_exists("{$_FN['src_application']}/themes/{$_FN['theme_default']}/images/logo.png"))
    $vars['url_logo'] = $_FN['siteurl'] . "{$_FN['src_application']}/themes/{$_FN['theme_default']}/images/logo.png";
if (file_exists("{$_FN['src_application']}/themes/{$_FN['theme_default']}/images/logo.svg"))
    $vars['url_logo'] = $_FN['siteurl'] . "themes/{$_FN['theme_default']}/images/logo.svg";
//if (file_exists("controlcenter/themes/{$_FN['controlcenter_theme']}/images/logo.png"))
//    $vars['url_logo'] = $_FN['siteurl'] . "controlcenter/themes/{$_FN['controlcenter_theme']}/images/logo.png";
//if (file_exists("controlcenter/themes/{$_FN['controlcenter_theme']}/images/logo.svg"))
//    $vars['url_logo'] = $_FN['siteurl'] . "controlcenter/themes/{$_FN['controlcenter_theme']}/images/logo.svg";

$items = FNCC_GetMenuItems();
$vars['menuitems'] = $items;
$notifications = FN_GetNotificationsUndisplayed($_FN['user']);
$vars['notifications'] = $notifications;
$vars['notifications_count'] = count($notifications);

$vars['view_users_and_groups_profile'] = UserCanAdmin("users_and_groups/profile");
$allsections = FN_ListDir("{$_FN['src_finis']}/controlcenter/sections/");
foreach ($allsections as $section)
{
    $allsubsections = FN_ListDir("{$_FN['src_finis']}/controlcenter/sections/" . basename($section));

    foreach ($allsubsections as $subsection)
    {
        $vars["{$section}__{$subsection}"] = UserCanAdmin("{$section}/{$subsection}");
    }
}

$can_view = false;
foreach ($items as $sections)
{
    foreach ($sections['sections'] as $section)
    {
        if ($section['opt'] == $opt)
        {
            $vars['section_title'] = $section['title'];
            $can_view = true;
        }
    }
}

if ($opt != "" && $can_view == false && !FN_IsAdmin())
{
    //------------------------------------------------------login form----->
    $vars = array();
    $vars['formaction'] = $_FN['controlcenter'] . "?fnapp=controlcenter&fnlogin=login";
    $_FN['login_error'] = isset($_FN['login_error']) ? $_FN['login_error'] : "";
    $html = FN_TPL_include_tpl(FN_TPL_ApplyTplFile("{$_FN['src_applications']}/controlcenter/themes/{$_FN['controlcenter_theme']}/controlcenter.login.tp.html", $vars), $vars);

    echo $html;
    die();
    //------------------------------------------------------login form-----<    
}


$vars['languages'] = FNCC_HtmlLanguages();
if (file_exists("{$_FN['src_finis']}/controlcenter/sections/$opt/help/"))
{
    $vars['htmlhelp'] = FN_HtmlContent("{$_FN['src_finis']}/controlcenter/sections/$opt/help/");
}

if (($modcont != "" && !FN_CanModifyFile($_FN['user'], $modcont)) || !FN_IsAdmin() && FNCC_GetCCSections() == false)
{

    //------------------------------------------------------login form----->
    $vars = array();
    $vars['formaction'] = $_FN['controlcenter'] . "?fnapp=controlcenter&fnlogin=login";
    $_FN['login_error'] = isset($_FN['login_error']) ? $_FN['login_error'] : "";
    $html = FN_TPL_include_tpl(FN_TPL_ApplyTplFile(FN_pathsite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/controlcenter.login.tp.html"), $vars), $vars);
    //FN_TPL_CopyFilesFromSrcToApplication($html, "controlcenter/themes/{$_FN['controlcenter_theme']}/");
    echo $html;
    die();
    //------------------------------------------------------login form-----<
}
else
{
//-----------------------------MAIN PAGE--------------------------------------->
    $sectionvalues = FN_GetSectionValues($sect);

    $tplcontents = file_get_contents(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/controlcenter.tp.html"));
    FN_TPL_CopyFilesFromSrcToApplication($tplcontents, "./controlcenter/themes/{$_FN['controlcenter_theme']}/");

    if (!empty($opt) && UserCanAdmin($opt))
    {

        $html = FN_TPL_include_tpl(FN_TPL_ApplyTplFile(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/controlcenter.tp.html"), $vars), $vars);
    }
    else
    {

        FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/controlcenter.dashboard.tp.html");
        if (file_exists("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/controlcenter.dashboard.tp.html"))
        {
            $html = FN_TPL_include_tpl(FN_TPL_ApplyTplFile(FN_PathSite("{$_FN['src_application']}/controlcenter/themes/{$_FN['controlcenter_theme']}/controlcenter.dashboard.tp.html"), $vars), $vars);
        }
        else
        {
            $str = file_get_contents(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/controlcenter.tp.html"));
            FN_TPL_CopyFilesFromSrcToApplication($str, "controlcenter/themes/{$_FN['controlcenter_theme']}/");

            $str = str_replace("include ccsection", "include dashboard", $str);
            $html = FN_TPL_include_tpl(FN_TPL_ApplyTplString($str, $vars, "{$_FN['src_application']}/controlcenter/themes/{$_FN['controlcenter_theme']}/"), $vars);
        }
    }
    $html = str_replace("</head>", "{$_FN['section_header_footer']}</head>", $html);
}
//-----------------------------MAIN PAGE---------------------------------------<

die($html);

function FN_TPL_tp_create_loginform()
{
    return FN_HtmlLoginForm();
}

/**
 * 
 * @global type $_FN
 * @return string
 */
function FN_TPL_tp_create_ccsection()
{
    global $_FN;
    $opt = FN_GetParam("opt", $_GET);
    $modcont = FN_GetParam("modcont", $_GET, "flat");
    //----edit file on filesystem----->
    if ($modcont != "" && FN_CanModifyFile($_FN['user'], $modcont) && file_exists($modcont))
    {
        $linkcancel = FN_RewriteLink("index.php?mod={$_FN['mod']}");
        FN_EditContent($modcont, $linkcancel, $linkcancel);
        $html = ob_get_clean();
        return $html;
    }
    //----edit file on filesystem-----<
    //----edit config.php-----<
    $fileconfig_to_edit = false;
    if (FN_erg("^fnc_ccnf_config_section_", $opt))
    {
        $section_to_edit_config = FN_erg_replace("^fnc_ccnf_config_section_", "", $opt);
        $sectionvalues = FN_GetSectionValues($section_to_edit_config);
        $configsection = $_FN['mod'] = $sectionvalues['id'];
        $_FN['sectionvalues'] = FN_GetSectionValues($_FN['mod']);
        if (file_exists("{$_FN['src_application']}/sections/{$sectionvalues['id']}/config.php"))
            $fileconfig_to_edit = "{$_FN['src_application']}/sections/{$sectionvalues['id']}/config.php";
        else
            $fileconfig_to_edit = "{$_FN['src_finis']}/modules/{$sectionvalues['type']}/config.php";
    }
    if (FN_erg("^fnc_ccnf_config_block_", $opt))
    {
        $section_to_edit_config = FN_erg_replace("^fnc_ccnf_config_block_", "", $opt);
        $sectionvalues = FN_GetBlockValues($section_to_edit_config);
        $configsection = $_FN['block'] = $sectionvalues['id'];
        $_FN['sectionvalues'] = FN_GetBlockValues($sectionvalues['id']);
        if (file_exists("{$_FN['src_application']}/blocks/{$sectionvalues['id']}/config.php"))
            $fileconfig_to_edit = "{$_FN['src_application']}/blocks/{$sectionvalues['id']}/config.php";
        else
            $fileconfig_to_edit = "{$_FN['src_finis']}/modules/{$sectionvalues['type']}/config.php";
    }


    if (FN_erg("^fnc_ccnf_config_plugin_", $opt))
    {
        $plugin_to_edit_config = FN_erg_replace("^fnc_ccnf_config_plugin_", "", $opt);
        $fileconfig_to_edit = "plugins/$plugin_to_edit_config/config.php";
    }
    //editor
    if ($fileconfig_to_edit != "" && file_exists($fileconfig_to_edit))
    {
        ob_start();
        echo FNCC_HtmlEditConfFile("$fileconfig_to_edit", "?fnapp=controlcenter&opt=$opt");
        return ob_get_clean();
    }
    //----edit config.php-----<
    //----settings.php-----<
    $filetoinclude = false;
    if (FN_erg("^fnc_ccnf_section_", $opt))
    {
        $sect = FN_erg_replace("^fnc_ccnf_section_", "", $opt);
        $sectionvalues = FN_GetSectionValues($sect);
        $_FN['mod'] = $sect;
        $_FN['sectionvalues'] = $sectionvalues;
        if (file_exists("{$_FN['src_application']}/sections/{$sect}/controlcenter/settings.php"))
        {
            $filetoinclude = "{$_FN['src_application']}/sections/$sect/controlcenter/settings.php";
        }
        elseif (!empty($sectionvalues['type']) && file_exists("{$_FN['src_finis']}/modules/{$sectionvalues['type']}/controlcenter/settings.php"))
        {
            $filetoinclude = "{$_FN['src_finis']}/modules/{$sectionvalues['type']}/controlcenter/settings.php";
        }
    }
    if (FN_erg("^fnc_ccnf_block_", $opt))
    {
        $sect = FN_erg_replace("^fnc_ccnf_block_", "", $opt);
        $_FN['block'] = $sect;
        $sectionvalues = FN_GetBlockValues($sect);

        $_FN['sectionvalues'] = $sectionvalues;
        if (file_exists("{$_FN['src_application']}/blocks/{$sect}/controlcenter/settings.php"))
        {
            $filetoinclude = "{$_FN['src_finis']}/modules/$sect/controlcenter/settings.php";
        }
        elseif (!empty($sectionvalues['type']) && file_exists("{$_FN['src_finis']}/modules/{$sectionvalues['type']}/controlcenter/settings.php"))
        {
            $filetoinclude = "{$_FN['src_finis']}/modules/{$sectionvalues['type']}/controlcenter/settings.php";
        }
    }
    if (FN_erg("^fnc_ccnf_plugin_", $opt))
    {
        $filetoinclude = false;
        $sect = FN_erg_replace("^fnc_ccnf_plugin_", "", $opt);
        $filetoinclude = "plugins/{$sect}/controlcenter/settings.php";
    }
    if ($filetoinclude)
    {
        if (FN_erg("^fnc_ccnf_section_", $opt))
        {
            $t = str_replace("fnc_ccnf_section_", "", $opt);
            $s = FN_GetSectionValues($t);
            $configsection = $sect = FN_erg_replace("^fnc_ccnf_section_", "", $opt);
            $_FN['mod'] = $sect;
        }
        if (FN_erg("^fnc_ccnf_block_", $opt))
        {
            $t = str_replace("fnc_ccnf_block_", "", $opt);
            $s = FN_GetBlockValues($t);
            $configsection = $sect = FN_erg_replace("^fnc_ccnf_block_", "", $opt);
        }
        if (FN_erg("^fnc_ccnf_plugin_", $opt))
        {
            $t = str_replace("fnc_ccnf_plugin_", "", $opt);
            $s['title'] = $t;
        }
        $title = $s['title'];
        $_FN['sectionvalues'] = FN_GetSectionValues($_FN['mod']);
        include "$filetoinclude";
        return ob_get_clean();
    }

    $title = FN_GetFolderTitle("{$_FN['src_finis']}/controlcenter/sections/$opt/");
    ob_start();
    if (!empty($opt) && file_exists("{$_FN['src_application']}/controlcenter/sections/$opt/section.php"))
    {
        include "{$_FN['src_application']}/controlcenter/sections/$opt/section.php";
    }
    elseif (!empty($opt) && file_exists("{$_FN['src_finis']}/controlcenter/sections/$opt/section.php"))
    {
        include "{$_FN['src_finis']}/controlcenter/sections/$opt/section.php";
    }
    $html = ob_get_clean();
    return $html;
}

/**
 *
 * @return array
 */
function FNCC_GetSectionsConfigs()
{
    global $_FN;
    $sections = FN_GetSections("", true, true, true);
    $blocks = $_FN['blocks'];
    $configs = array();
    foreach ($sections as $section)
    {
        $section['opt'] = "fnc_ccnf_config_section_" . $section['id'];
        $section['description'] = " ";
        if (!empty($section['type']) && (file_exists("{$_FN['src_finis']}/modules/{$section['type']}/config.php") /* || file_exists("{$_FN['src_finis']}/modules/{$section['type']}/controlcenter/settings.php") */))
        {
            $section['cc_icon'] = "controlcenter/sections/settings/cms/icon.png";
            $section['title'] = FN_Translate("page") . " " . FN_GetFolderTitle("{$_FN['src_finis']}/modules/{$section['type']}/") . ": " . $section['title'] . "";
            $section['description'] = ucfirst(FN_Translate("settings") . " " . FN_GetFolderTitle("{$_FN['src_finis']}/modules/{$section['type']}/") . ": " . $section['title'] . "");

            if (file_exists("{$_FN['src_finis']}/modules/{$section['type']}/cc_icon.png"))
                $section['cc_icon'] = FN_FromTheme("{$_FN['src_finis']}/modules/{$section['type']}/cc_icon.png");

            $configs[] = $section;
        }
        elseif (file_exists("{$_FN['src_application']}/sections/{$section['id']}/config.php") /* || file_exists("{$_FN['src_application']}/sections/{$section['id']}/controlcenter/settings.php") */)
        {
            $section['cc_icon'] = "controlcenter/sections/settings/cms/icon.png";
            $section['title'] = FN_Translate("settings in") . $section['title'];
            $configs[] = $section;
        }
    }
    foreach ($blocks as $section)
    {
        $section['opt'] = "fnc_ccnf_config_block_" . $section['id'];
        $section['description'] = " ";
        if (!empty($section['type']) && (file_exists("{$_FN['src_finis']}/modules/{$section['type']}/config.php") /* || file_exists("{$_FN['src_finis']}/modules/{$section['type']}/controlcenter/settings.php") */))
        {
            $section['cc_icon'] = "controlcenter/sections/settings/cms/icon.png";
            $section['title'] = FN_Translate("block") . " " . (FN_GetFolderTitle("{$_FN['src_finis']}/modules/{$section['type']}/") . " in \"" . $section['title'] . "\"");

            if (file_exists("{$_FN['src_finis']}/modules/{$section['type']}/cc_icon.png"))
                $section['cc_icon'] = FN_FromTheme("{$_FN['src_finis']}/modules/{$section['type']}/cc_icon.png");

            $configs[] = $section;
        }
        elseif (file_exists("{$_FN['src_application']}/blocks/{$section['id']}/config.php") /* || file_exists("{$_FN['src_application']}/sections/{$section['id']}/controlcenter/settings.php") */)
        {
            $section['cc_icon'] = "controlcenter/sections/settings/cms/icon.png";
            $section['title'] = FN_Translate("settings in") . $section['title'];
            $configs[] = $section;
        }
    }


    return $configs;
}

/**
 *
 * @return array
 */
function FNCC_GetSectionsSettings()
{
    global $_FN;
    $sections = FN_GetSections(false, true, true, true);
    $blocks = $_FN['blocks'];
    $configs = array();
    foreach ($sections as $section)
    {
        $section['opt'] = "fnc_ccnf_section_{$section['id']}";
        $ttype = ucfirst(FN_GetFolderTitle($_FN['src_finis'] . "/modules/{$section['type']}"));
        if ($section['type'] == "")
            $section['title'] = $section['title'];
        else
            $section['title'] = $ttype . " in \"" . $section['title'] . "\"";
        $section['description'] = " ";
        if (!empty($section['type']) && file_exists("{$_FN['src_finis']}/modules/{$section['type']}/controlcenter/settings.php"))
        {

            $section['cc_icon'] = FN_FromTheme("controlcenter/images/configure.png");
            if (file_exists("{$_FN['src_finis']}/modules/{$section['type']}/controlcenter/icon.png"))
                $section['cc_icon'] = FN_FromTheme("{$_FN['src_finis']}/modules/{$section['type']}/controlcenter/icon.png");
            $configs[] = $section;
        }
        elseif (file_exists("{$_FN['src_application']}/sections/{$section['id']}/controlcenter/settings.php"))
        {
            $section['cc_icon'] = FN_FromTheme("controlcenter/images/configure.png");
            if (file_exists("{$_FN['src_application']}/sections/{$section['id']}/controlcenter/icon.png"))
                $section['cc_icon'] = FN_FromTheme("{$_FN['src_application']}/sections/{$section['id']}/controlcenter/icon.png");
            $configs[] = $section;
        }
    }
    foreach ($blocks as $section)
    {
        $section['opt'] = "fnc_ccnf_block_{$section['id']}";
        $section['description'] = " ";

        if (!empty($section['type']) && file_exists("{$_FN['src_finis']}/modules/{$section['type']}/controlcenter/settings.php"))
        {

            $section['cc_icon'] = FN_FromTheme("controlcenter/images/configure.png");
            if (file_exists("{$_FN['src_finis']}/modules/{$section['type']}/controlcenter/icon.png"))
                $section['cc_icon'] = FN_FromTheme("{$_FN['src_finis']}/modules/{$section['type']}/controlcenter/icon.png");
            $ttype = ucfirst(FN_GetFolderTitle($_FN['src_finis'] . "/modules/{$section['type']}"));
            $section['title'] = $ttype . " in \"" . $section['title'] . "\"";
            $configs[] = $section;
        }
        elseif (file_exists("{$_FN['src_application']}/sections/{$section['id']}/controlcenter/settings.php"))
        {
            $section['cc_icon'] = FN_FromTheme("controlcenter/images/configure.png");
            $section['title'] = FN_Translate("block") . " " . $section['title'];
            if (file_exists("{$_FN['src_application']}/sections/{$section['id']}/controlcenter/icon.png"))
                $section['cc_icon'] = FN_FromTheme("{$_FN['src_application']}/blocks/{$section['id']}/controlcenter/icon.png");
            $configs[] = $section;
        }
    }
    return $configs;
}

/**
 *
 * @return array
 */
function FNCC_GetPluginsConfigs()
{
    $sections = glob("plugins/*");

    $configs = array();
    foreach ($sections as $section)
    {
        if (file_exists("$section/config.php"))
        {
            if (file_exists("$section/icon.png"))
                $_section['cc_icon'] = "$section/icon.png";
            else
                $_section['cc_icon'] = "controlcenter/sections/settings/cms/icon.png";
            $_section['title'] = FN_GetFolderTitle($section);
            $_section['id'] = basename($section);
            $configs[] = $_section;
        }
    }
    return $configs;
}

/**
 *
 * @return array
 */
function FNCC_GetPluginsSettings()
{
    $sections = glob("plugins/*");

    $configs = array();
    foreach ($sections as $section)
    {
        if (file_exists("$section/controlcenter/settings.php"))
        {
            if (file_exists("$section/controlcenter/icon.png"))
                $_section['cc_icon'] = "$section/controlcenter/icon.png";
            else
                $_section['cc_icon'] = FN_FromTheme("controlcenter/images/configure.png");
            $_section['title'] = FN_GetFolderTitle($section);
            $_section['id'] = basename($section);
            $configs[] = $_section;
        }
    }
    return $configs;
}

/**
 *
 * @global array $_FN
 * @return string
 */
function FNCC_HtmlLanguages()
{
    global $_FN;
    $opt = FN_GetParam("opt", $_GET, "flat");
    $html = "";
    foreach ($_FN['listlanguages'] as $l)
    {
        $image = FN_FromTheme("images/flags/$l.png");
        $html .= "<a  href=\"?fnapp=controlcenter&amp;lang=$l&amp;mod={$_FN['mod']}&amp;opt=$opt\"><img src=\"$image\" alt=\"$l\" title=\"$l\" /></a>&nbsp;";
    }
    return $html;
}

/**
 * 
 */
function FNCC_HtmlDashBoard($htmltemplate)
{
    global $_FN;
    if (!FN_IsAdmin())
    {
        return;
    }
    $html = "";
    if (strstr($htmltemplate, "dashboard_contents") === false)
    {
        $htmltemplate = "<h2>{dashboard_title}</h2>
                    <div>{dashboard_contents}</div>";
    }

    $sectiondirs = glob("{$_FN['src_application']}/sections/*");
    foreach ($sectiondirs as $sectiondir)
    {
        $section = $_FN['mod'] = basename($sectiondir);
        $section = FN_GetSectionValues($section);
        if (!empty($section['type']))
        {
            $sectiondir = "{$_FN['src_finis']}/modules/{$section['type']}";
            if (is_dir($sectiondir) && file_exists("$sectiondir/controlcenter/fncc_dashboard.php"))
            {
                $vars['dashboard_title'] = $section['title'];
                ob_start();
                include "$sectiondir/controlcenter/fncc_dashboard.php";
                $vars['dashboard_contents'] = ob_get_clean();
                $html .= FN_TPL_ApplyTplString($htmltemplate, $vars);
            }
        }
        $_FN['mod'] = "";
    }
    $sectiondirs = glob("plugins/*");
    foreach ($sectiondirs as $sectiondir)
    {
        $section = basename($sectiondir);
        $title = FN_GetFolderTitle($sectiondir);
        if (is_dir($sectiondir) && file_exists("$sectiondir/controlcenter/fncc_dashboard.php"))
        {

            $vars['dashboard_title'] = $title;
            ob_start();
            include "$sectiondir/controlcenter/fncc_dashboard.php";
            $vars['dashboard_contents'] = ob_get_clean();
            $html .= FN_TPL_ApplyTplString($htmltemplate, $vars);
        }
    }

    $cc_sectiondirs = glob("controlcenter/sections/*");
    {
        foreach ($cc_sectiondirs as $cc_sectiondir)
        {
            $sectiondirs = glob("$cc_sectiondir/*");
            if (is_array($sectiondirs))
                foreach ($sectiondirs as $sectiondir)
                {
                    if (is_dir($sectiondir) && file_exists("$sectiondir/fncc_dashboard.php"))
                    {

                        $vars['dashboard_title'] = FN_GetFolderTitle($sectiondir);
                        ;
                        ob_start();
                        include "$sectiondir/fncc_dashboard.php";
                        $vars['dashboard_contents'] = ob_get_clean();
                        $html .= FN_TPL_ApplyTplString($htmltemplate, $vars);
                    }
                }
        }
    }
    return $html;
}

/**
 * 
 * @global type $_FN
 * @return boolean
 */
function FNCC_GetCCSections()
{
    global $_FN;
    $sections = FN_XMETADBQuery("SELECT * FROM fn_cc_users WHERE username LIKE '{$_FN['user']}'");
    if (isset($sections[0]['ccsections']))
    {
        return $sections[0]['ccsections'];
    }
    return false;
}

/**
 * 
 * @global type $_FN
 * @param type $section
 * @return boolean
 */
function UserCanAdmin($section = "")
{
    global $_FN;
    if (FN_IsAdmin())
    {
        return true;
    }
    $opt = FN_GetParam("opt", $_GET);
    if ($section)
    {
        $opt = $section;
    }
    $sectionsEnabled = FNCC_GetCCSections();
    $sectionsEnabled = explode(",", $sectionsEnabled);
    if (in_array($opt, $sectionsEnabled))
    {
        return true;
    }
    return false;
}

/**
 *
 * @global global $_FN
 * @return type 
 */
function FNCC_GetMenuItems()
{
    global $_FN;
    $opt = FN_GetParam("opt", $_GET);
    $menu = array();
    $toShow = false;
    $section_enabled = FN_XMETADBQuery("SELECT * FROM fn_cc_users WHERE username LIKE '{$_FN['user']}'");
    $default = "";
    if (!empty($section_enabled[0]['ccsections']))
    {
        $toShow = explode(",", $section_enabled[0]['ccsections']);
        $default = isset($section_enabled[0]['default']) ? $section_enabled[0]['default'] : "";
        if ($default && !in_array($default, $toShow))
            $toShow[] = $default;
    }

    $dirs = FN_ListDir("{$_FN['src_finis']}/controlcenter/sections/", false);
    $dirs2 = FN_ListDir("{$_FN['src_application']}/controlcenter/sections/", false);
    $dirs = array_merge($dirs,$dirs2);
    FN_NatSort($dirs);
    $sectionsIngroup = array();
    foreach ($dirs as $sectiongroup)
    {
        $menu[$sectiongroup] = array();
        $sections = FN_ListDir("{$_FN['src_finis']}/controlcenter/sections/$sectiongroup");
        $sections2 = FN_ListDir("{$_FN['src_application']}/controlcenter/sections/$sectiongroup");
        $sections = array_unique(array_merge($sections,$sections2));
        FN_NatSort($sections);
        $sectionsIngroup = array();
        foreach ($sections as $i => $section)
        {
            $item['active'] = false;
            $item['opt'] = "$sectiongroup/$section";
            $item['id'] = "$sectiongroup/$section";
            $item['description'] = "";
            if (is_array($toShow) && !in_array($item['opt'], $toShow))
            {
                
            }
            else
            {
                $item['link'] = "?fnapp=controlcenter&opt={$item['opt']}";
                $item['title'] = "" . FN_GetFolderTitle("{$_FN['src_finis']}/controlcenter/sections/$sectiongroup/$section");
                $item['description'] = $item['title'];
                $icon = FN_FromTheme("{$_FN['src_application']}/controlcenter/images/configure.png");
                if (file_exists("{$_FN['src_finis']}/controlcenter/sections/$sectiongroup/$section/icon.png"))
                    $icon = FN_PathSite("{$_FN['src_finis']}/controlcenter/sections/$sectiongroup/$section/icon.png");
                $item['image'] = $icon;
                if ($opt == $item['opt'])
                {
                    $item['active'] = true;
                }
                $sectionsIngroup[] = $item;
            }
        }
        if ($sectiongroup == "settings")
        {
//---------------get list of config.php in plugins and sections---------------->
            $_sectionsIngroup = array();
            $dirsconf = FNCC_GetSectionsConfigs();
            foreach ($dirsconf as $_section)
            {
                $item = array();
                $item['active'] = false;
                $item['opt'] = $_section['opt'];
                $item['id'] = "$sectiongroup/{$_section['opt']}";
                $item['description'] = "";
                if (is_array($toShow) && !in_array($item['opt'], $toShow))
                {
                    continue;
                }
                $item['link'] = "?fnapp=controlcenter&opt={$item['opt']}";
                $item['title'] = $_section['title'];
                $item['image'] = $_section['cc_icon'];
                $item['description'] = $item['title'];
                if ($opt == $item['opt'])
                {
                    $item['active'] = true;
                    $menu[$sectiongroup]['active'] = true;
                }
                $sectionsIngroup[] = $item;
            }

            //customs configs----<
            //plugins configs---->
            $dirsconf = FNCC_GetPluginsConfigs();
            foreach ($dirsconf as $_section)
            {
                $item['active'] = false;
                $item['opt'] = "fnc_ccnf_config_plugin_{$_section['id']}";
                $item['id'] = "$sectiongroup/fnc_ccnf_config_plugin_{$_section['id']}";
                $item['description'] = "";

                if (is_array($toShow) && !in_array($item['opt'], $toShow))
                {
                    continue;
                }
                $item['link'] = "?fnapp=controlcenter&opt={$item['opt']}";
                $item['title'] = $_section['title'];
                $item['image'] = $_section['cc_icon'];
                $item['description'] = $item['title'];
                if ($opt == $item['opt'])
                {
                    $menu[$sectiongroup]['active'] = true;
                    $item['active'] = true;
                }
                $sectionsIngroup[] = $item;
            }
            //plugins configs----<
//---------------get list of config.php in plugins and sections----------------<				
        }
        if ($sectiongroup == "contents")
        {
            $dirs = FNCC_GetSectionsSettings();
            //dprint_r($dirs);
            foreach ($dirs as $section)
            {
                $item['opt'] = $section['opt'];
                $item['active'] = ( $opt == $item['opt']) ? true : false;

                $item['id'] = $section['opt'];
                $item['description'] = "";

                if (is_array($toShow) && !in_array($item['opt'], $toShow))
                {
                    continue;
                }
                $item['link'] = "?fnapp=controlcenter&opt={$item['opt']}";
                $ttype = "";
                if (!empty($section['type']))
                {

                    $ttype = ucfirst(FN_GetFolderTitle($_FN['src_finis'] . "/modules/{$section['type']}") . " ");
                }
                $item['title'] = $section['title'];
                $item['image'] = $section['cc_icon'];
                $item['description'] = $section['title'];
                $sectionsIngroup[] = $item;
            }
            //customs configs----<            
        }
        if (count($sectionsIngroup) == 0)
        {
            unset($menu[$sectiongroup]);
        }
        else
        {
            if (dirname($opt) == $sectiongroup)
            {
                $menu[$sectiongroup]['active'] = true;
            }
            $menu[$sectiongroup]['description'] = "";
            $menu[$sectiongroup]['opt'] = $sectiongroup;
            $menu[$sectiongroup]['id'] = $sectiongroup;
            $menu[$sectiongroup]['link'] = "{$_FN['controlcenter']}?fnapp=controlcenter&opt=$sectiongroup";
            $menu[$sectiongroup]['title'] = FN_GetFolderTitle($_FN['src_finis'] . "/controlcenter/sections/$sectiongroup");
            $menu[$sectiongroup]['sections'] = $sectionsIngroup;
        }
    }


//---------------get settings.php in plugins and sections --------------------->
    $menu['fnc_ccnf_plugin']['description'] = "";
    $menu['fnc_ccnf_plugin']['link'] = "";
    $menu['fnc_ccnf_plugin']['opt'] = "fnc_ccnf_plugin_";
    $menu['fnc_ccnf_plugin']['id'] = "fnc_ccnf_plugin_";
    $menu['fnc_ccnf_plugin']['title'] = FN_Translate("plugins");
    $menu['fnc_ccnf_plugin']['sections'] = $sectionsIngroup;
    //customs configs config---->

    $sectionsIngroup = array();

    //customs configs----<
    //plugins configs---->
    $dirs = FNCC_GetPluginsSettings();
    foreach ($dirs as $section)
    {
        $item['opt'] = "fnc_ccnf_plugin_{$section['id']}";
        $item['active'] = ( $opt == $item['opt']) ? true : false;
        if ($item['active'])
        {
            $menu['fnc_ccnf_plugin']['active'] = true;
        }
        $item['id'] = "fnc_ccnf_plugin_{$section['id']}";
        $item['description'] = "";

        if (is_array($toShow) && !in_array($item['opt'], $toShow))
        {
            continue;
        }
        $item['link'] = "?fnapp=controlcenter&opt={$item['opt']}";
        $item['title'] = $section['title'];
        $item['image'] = $section['cc_icon'];
        $sectionsIngroup[] = $item;
    }
//---------------get settings.php in plugins and sections ---------------------<		
    $menu['fnc_ccnf_plugin']['sections'] = $sectionsIngroup;
    if (count($sectionsIngroup) == 0)
        unset($menu['fnc_ccnf_plugin']);

    return $menu;
}

/**

  function FNCC_HtmlFilters($tablefrm, $fields_filters, &$link_filters, $templatefile = "")
  {
  global $_FN;
  if (!$templatefile)
  {
  FNCC_FromTheme("form_filters.tp.html");
  }
  if (!file_exists($templatefile))
  {
  $templatefile = "controlcenter/themes/classic/form_filters.tp.html";
  }

  $opt = FN_GetParam("opt", $_GET, "html");
  $link_filters = FN_GetParam("filter", $_GET, "flat");
  $postfilter = array();
  $array_filters = json_decode($link_filters, JSON_OBJECT_AS_ARRAY);
  //-----form----->
  $fv = array();
  $formfilter = array();
  $formfilter['action'] = "?mod={$_FN['mod']}&amp;opt=$opt";
  $formfilter['method'] = "post";
  $formfilter['filters'] = array();
  $filters_by_post = array();
  //----filters by get and post---------------------------------------------->
  foreach ($tablefrm->formvals as $k => $v)
  {
  if (in_array($k, $fields_filters))
  {
  if (!empty($_POST["search_" . $k]))
  {
  $filters_by_post[$k] = FN_GetParam("search_" . $k, $_POST, "html");
  }
  }
  }
  $filters_by_get = json_decode(FN_GetParam("filter", $_GET, "flat"), JSON_OBJECT_AS_ARRAY);
  if (is_array($filters_by_get))
  {
  $array_filters = array_merge($filters_by_get, $filters_by_post);
  }
  else
  {
  $array_filters = $filters_by_post;
  }
  $link_filters = json_encode($array_filters);
  //----filters by get and post----------------------------------------------<

  foreach ($tablefrm->formvals as $k => $v)
  {
  $itemfilter = array();


  if (in_array($k, $fields_filters))
  {
  $itemfilter['title'] = $tablefrm->formvals[$k]['title'];
  $itemfilter['name'] = "search_$k";
  $itemfilter['value'] = isset($array_filters[$k]) ? $array_filters[$k] : "";
  $itemfilter['type_text'] = true;
  $itemfilter['type_select'] = false;
  if (is_array($v['options']))
  {
  foreach ($v['options'] as $kk => $vo)
  {
  if ($itemfilter['value'] == $vo['value'])
  {
  $v['options'][$kk]['selected'] = true;
  }
  else
  {
  $v['options'][$kk]['selected'] = false;
  }
  }

  $itemfilter['options'] = $v['options'];
  }
  $formfilter['filters'][] = $itemfilter;
  }
  }
  $formfilter['urlreset'] = "?mod={$_FN['mod']}&amp;opt=$opt";
  $html = FN_TPL_ApplyTplFile($templatefile, $formfilter);
  return $html;
  }
 */

/**
 * 
 * @return string
 */
function FN_TPL_tp_create_dashboard($vars)
{
    global $_FN;
    $html = FNCC_HtmlDashBoard($vars);
    return $html;
}

/**
 * 
 * @global global $_FN
 * @param type $str
 * @return type
 */
function FN_TPL_tp_create_ccmenu($str)
{
    global $_FN;
    $opt = FN_GetParam("opt", $_GET);
    $sections = FNCC_GetMenuItems();
    $htmlout = "";
    //dprint_r($sections);
    $tp_menuitem = FN_TPL_GetHtmlPart("menuitem", $str, "<a href=\"link\">title</a><br />");
    $tp_menuitemactive = FN_TPL_GetHtmlPart("menuitemactive", $str, $tp_menuitem);
    $tp_menuitemdropdown = FN_TPL_GetHtmlPart("menuitemdropdown", $str);
    $tp_menuitemdropdownactive = FN_TPL_GetHtmlPart("menuitemdropdownactive", $str);
    if ($tp_menuitemdropdownactive == "")
        $tp_menuitemdropdownactive = $tp_menuitemdropdown;
    $tp_menuitem = preg_replace("/<a([^>]+)(href)=(\")([^\"]*)(\")/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitem);
    $tp_menuitem = preg_replace("/<a([^>]+)(href)=(\')([^\']*)(\')/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitem);
    $tp_menuitemactive = preg_replace("/<a([^>]+)(href)=(\")([^\"]*)(\")/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitemactive);
    $tp_menuitemactive = preg_replace("/<a([^>]+)(href)=(\')([^\']*)(\')/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitemactive);
    $tp_menuitemdropdown = preg_replace("/<a([^>]+)(href)=(\")([^\"]*)(\")/im", "<a\\1\\2=\\3#\\3", $tp_menuitemdropdown);
    $tp_menuitemdropdown = preg_replace("/<a([^>]+)(href)=(\')([^\']*)(\')/im", "<a\\1\\2=\\3#\\3", $tp_menuitemdropdown);
    $tp_menuitemdropdownactive = preg_replace("/<a([^>]+)(href)=(\")([^\"]*)(\")/im", "<a\\1\\2=\\3#\\3", $tp_menuitemdropdownactive);
    $tp_menuitemdropdownactive = preg_replace("/<a([^>]+)(href)=(\')([^\']*)(\')/im", "<a\\1\\2=\\3#\\3", $tp_menuitemdropdownactive);
    if (strpos($tp_menuitem, '{title}') === false)
    {
        $tp_menuitem = preg_replace("/(<a.*>)(.*)(<\/a)/im", "\\1{title}\\3", $tp_menuitem);
    }
    if (strpos($tp_menuitemactive, '{title}') === false)
    {
        $tp_menuitemactive = preg_replace("/(<a.*>)(.*)(<\/a)/im", "\\1{title}\\3", $tp_menuitemactive);
    }
    if (strpos($tp_menuitemdropdown, '{title}') === false)
    {
        $tp_menuitemdropdown = preg_replace("/(<a.*>)(.*)(<\/a)/im", "\\1{title}\\3", $tp_menuitemdropdown);
    }
    if (strpos($tp_menuitemdropdownactive, '{title}') === false)
    {
        $tp_menuitemdropdownactive = preg_replace("/(<a.*>)(.*)(<\/a)/im", "\\1{title}\\3", $tp_menuitemdropdownactive);
    }
    //add title
    if (false == strpos($tp_menuitem, "title="))
    {
        $tp_menuitem = str_replace("<a", "<a title=\"{description}\" ", $tp_menuitem);
    }
    //add title
    if (false == strpos($tp_menuitemactive, "title="))
    {
        $tp_menuitemactive = str_replace("<a", "<a title=\"{description}\" ", $tp_menuitemactive);
    }
    //add title
    if (false == strpos($tp_menuitemactive, "title="))
    {
        $tp_menuitemdropdown = str_replace("<a", "<a title=\"{description}\" ", $tp_menuitemdropdown);
    }
    //add title
    if (false == strpos($tp_menuitemactive, "title="))
    {
        $tp_menuitemdropdownactive = str_replace("<a", "<a title=\"{description}\" ", $tp_menuitemdropdownactive);
    }
    //add accesskey
    if (false == strpos($tp_menuitem, "{accesskey"))
    {
        $tp_menuitem = str_replace("<a", "<a accesskey=\"{accesskey}\" ", $tp_menuitem);
    }
    //add accesskey
    if (false == strpos($tp_menuitemactive, "{accesskey"))
    {
        $tp_menuitemactive = str_replace("<a", "<a accesskey=\"{accesskey}\" ", $tp_menuitemactive);
    }
    //add accesskey
    if (false == strpos($tp_menuitemdropdown, "{accesskey"))
    {
        $tp_menuitemdropdown = str_replace("<a", "<a accesskey=\"{accesskey}\" ", $tp_menuitemdropdown);
    }
    //add accesskey
    if (false == strpos($tp_menuitemdropdownactive, "{accesskey"))
    {
        $tp_menuitemdropdownactive = str_replace("<a", "<a accesskey=\"{accesskey}\" ", $tp_menuitemdropdownactive);
    }

    foreach ($sections as $sectionvalues)
    {
        //dprint_r($sectionvalues);
        $sectionvalues['accesskey'] = "";
        $htmlmenuitem = "";
        if (FN_erg("^fnc_ccnf_config_section", $opt) || FN_erg("^fnc_ccnf_config_block", $opt))
        {
            $opt = "settings/$opt";
        }
        elseif (FN_erg("^fnc_ccnf_config", $opt))
        {
            $opt = "settings/$opt";
        }
        elseif (FN_erg("^fnc_ccnf_section", $opt) || FN_erg("^fnc_ccnf_block", $opt)) //nc_ccnf_block
        {
            $opt = "contents/$opt";
        }
        $sectionvalues['title'] = htmlspecialchars($sectionvalues['title'], ENT_QUOTES);
        $sectionvalues['description'] = htmlspecialchars($sectionvalues['description'], ENT_QUOTES);

        //dprint_r(" $opt,{$sectionvalues['id']}  $tp_menuitemdropdownactive");
        if ($tp_menuitemdropdownactive != "" && false !== strpos($opt, $sectionvalues['id']))
        {
            $htmlmenuitem = FN_TPL_ApplyTplString($tp_menuitemdropdownactive, $sectionvalues, false);
            $tp_submenuitem_ori_template = FN_TPL_GetHtmlPart("submenu", $tp_menuitemdropdownactive);
        }
        elseif ($tp_menuitemdropdown != "")
        {
            $htmlmenuitem = FN_TPL_ApplyTplString($tp_menuitemdropdown, $sectionvalues, false);
            $tp_submenuitem_ori_template = FN_TPL_GetHtmlPart("submenu", $tp_menuitemdropdown);
        }
        $tp_submenuitem_ori = FN_TPL_GetHtmlPart("submenu", $htmlmenuitem);
        $tp_submenuitem_new = $tp_submenuitem_ori;
        $submenu_str = FN_TPL_tp_create_ccsubmenu_($tp_submenuitem_ori_template, $sectionvalues['sections']);
        $tp_submenuitem_new = str_replace($tp_submenuitem_ori, $submenu_str, $tp_submenuitem_ori);
        $htmlmenuitem = str_replace($tp_submenuitem_ori, $tp_submenuitem_new, $htmlmenuitem);
        $htmlout .= $htmlmenuitem;
    }
    return $htmlout;
}

/**
 *
 * @global array $_FN
 * @return string 
 */
function FN_TPL_tp_create_ccsubmenu_($str, $sections)
{
    global $_FN;
    $opt = FN_GetParam("opt", $_GET);
    if ($str == "")
        return "";
    if (!$sections)
        return "";
    if (!is_array($sections))
    {
        // dprint_r($sections);
        return "";
    }
    preg_match('/<!-- submenuitems -->(.*)<!-- endsubmenuitems -->/is', $str, $out);
    $tp_menuitem_old = FN_TPL_GetHtmlPart("submenuitems", $str, "<li><a href=\"link\">title</a></li>");

    $tp_menuitem = FN_TPL_GetHtmlPart("submenuitem", $str);
    $tp_menuitemactive = FN_TPL_GetHtmlPart("submenuitemactive", $str, $tp_menuitem);
    $tp_menuitem = preg_replace("/<a([^>]+)(href)=(\")([^\"]*)(\")/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitem);
    $tp_menuitem = preg_replace("/<a([^>]+)(href)=(\')([^\']*)(\')/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitem);
    $tp_menuitemactive = preg_replace("/<a([^>]+)(href)=(\")([^\"]*)(\")/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitemactive);
    $tp_menuitemactive = preg_replace("/<a([^>]+)(href)=(\')([^\']*)(\')/im", "<a\\1\\2=\\3{link}\\3", $tp_menuitemactive);
    if (strpos($tp_menuitem, '{title}') === false)
    {
        $tp_menuitem = preg_replace("/(<a.*>)(.*)(<\/a)/im", "\\1{title}\\3", $tp_menuitem);
        $tp_menuitemactive = preg_replace("/(<a.*>)(.*)(<\/a)/im", "\\1{title}\\3", $tp_menuitemactive);
    }
    //add title
    if (false == strpos($tp_menuitem, "title="))
    {
        $tp_menuitem = str_replace("<a", "<a title=\"{description}\" ", $tp_menuitem);
    }
    //add title
    if (false == strpos($tp_menuitemactive, "title="))
    {
        $tp_menuitemactive = str_replace("<a", "<a title=\"{description}\" ", $tp_menuitemactive);
    }
    //add accesskey
    if (false == strpos($tp_menuitem, "{accesskey"))
    {
        $tp_menuitem = str_replace("<a", "<a accesskey=\"{accesskey}\" ", $tp_menuitem);
    }
    //add accesskey
    if (false == strpos($tp_menuitemactive, "{accesskey"))
    {
        $tp_menuitemactive = str_replace("<a", "<a accesskey=\"{accesskey}\" ", $tp_menuitemactive);
    }


    $htmlout = "";
    foreach ($sections as $sectionvalues)
    {
        $sectionvalues['title'] = htmlspecialchars($sectionvalues['title'], ENT_QUOTES);
        $sectionvalues['description'] = htmlspecialchars($sectionvalues['description'], ENT_QUOTES);

        //dprint_r("$opt {$sectionvalues['id']} ");
        $sectionvalues['accesskey'] = "";
        if ($opt == $sectionvalues['opt'])
            $htmlout .= FN_TPL_ApplyTplString($tp_menuitemactive, $sectionvalues, false);
        else
            $htmlout .= FN_TPL_ApplyTplString($tp_menuitem, $sectionvalues, false);
        if (strpos($htmlout, '{submenu}') !== false)
        {
            $htmlout = str_replace("{submenu}", FN_TPL_tp_create_ccsubmenu_($str, $sectionvalues['id']), $htmlout);
        }
    }
    if ($htmlout != "")
        $htmlout = str_replace($tp_menuitem_old, $htmlout, $str);
    return $htmlout;
}

/**
 * 
 * @global type $_FN
 * @param type $tablename
 * @param type $vars
 */
function FNCC_XmltableEditor($tablename, $params = array())
{
    global $_FN;
    $params = array_merge($_FN,$params);
    if (empty($params['layout_template']) && file_exists(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/form.tp.html")))
    {
        $params['layout_template'] = file_get_contents(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/form.tp.html"));
        $params['template_path'] = dirname(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/form.tp.html"))."/" ;
    }
    if (empty($params['html_template_grid']) && file_exists(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/grid.tp.html")))
    {
        $params['html_template_grid'] = file_get_contents(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/grid.tp.html"));
        $params['template_path'] =  dirname(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/grid.tp.html"))."/" ;
    }
    if (empty($params['html_template_view']) && file_exists(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/view.tp.html")))
    {
        $params['html_template_view'] = file_get_contents(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/view.tp.html"));
        $params['template_path'] =  dirname(FN_PathSite("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/grid.tp.html"))."/" ;
    }
    FN_PathSite("controlcenter/themes/{$_FN['controlcenter_theme']}/img/");

    if (empty($params['xmldatabase']))
    {
        $params['xmldatabase'] = $_FN['database'];
    }
    $op = FN_GetParam("opt", $_GET, "html");
    $link = "fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$op";
    $params['path'] = $_FN['datadir'];
    $params['lang'] = $_FN['lang'];
    $params['charset_page'] = $_FN['charset_page'];
    $params['languages'] = $_FN['languages'];
    $params['siteurl'] = $_FN['siteurl'];
    $params['enable_mod_rewrite'] = $_FN['enable_mod_rewrite'];
    $params['links_mode'] = $_FN['links_mode'];
    if (!isset($params['link']))
    {
        $params['link'] = $link;
    }
    //messages--->
    $params['path'] = isset($params['path']) ? $params['path'] : $_FN['datadir'];
    $params['recordsperpage'] = isset($params['recordsperpage']) ? $params['recordsperpage'] : 20;
    $params['textview'] = isset($params['textview']) ? $params['textview'] : FN_Translate("view");
    $params['textsave'] = isset($params['textsave']) ? $params['textsave'] : FN_Translate("save");
    $params['textmodify'] = isset($params['textmodify']) ? $params['textmodify'] : FN_Translate("modify");
    $params['textdelete'] = isset($params['textdelete']) ? $params['textdelete'] : FN_Translate("delete");

    $params['textviewlist'] = isset($params['textviewlist']) ? $params['textviewlist'] : "<img style=\"vertical-align:middle;border:0px;\" alt=\"\"  src=\"" . FN_FromTheme("images/left.png") . "\" />&nbsp;" . fn_i18n("back");
    $params['textinsertok'] = isset($params['textinsertok']) ? $params['textinsertok'] : FN_Translate("the data were successfully inserted");
    $params['textupdateok'] = isset($params['textupdateok']) ? $params['textupdateok'] : FN_Translate("the data were successfully updated");
    $params['textpages'] = isset($params['textpages']) ? $params['textpages'] : FN_Translate("page") . ":";
    $params['textrequired'] = isset($params['textrequired']) ? $params['textrequired'] : "*";
    $params['textfields'] = isset($params['textfields']) ? $params['textfields'] : FN_Translate("required fields");
    $params['textcancel'] = isset($params['textcancel']) ? $params['textcancel'] : FN_Translate("cancel");
    $params['textnew'] = isset($params['textnew']) ? $params['textnew'] : "" . FN_Translate("new") . "";
    $params['textexitwithoutsaving'] = isset($params['textexitwithoutsaving']) ? $params['textexitwithoutsaving'] : FN_Translate("want to exit without saving?");
    //messages---<
    $params['lang_default'] = isset($params['lang_default']) ? $params['lang_default'] : $_FN['lang_default'];
    $params['siteurl'] = isset($params['siteurl']) ? $params['siteurl'] : $_FN['siteurl'];
    $params['lang'] = isset($params['lang']) ? $params['lang'] : $_FN['lang'];
    $params['enable_mod_rewrite'] = isset($params['enable_mod_rewrite']) ? $params['enable_mod_rewrite'] : $_FN['enable_mod_rewrite'];
    $params['use_urlserverpath'] = isset($params['use_urlserverpath']) ? $params['use_urlserverpath'] : $_FN['use_urlserverpath'];
    $params['sitepath'] = isset($params['sitepath']) ? $params['sitepath'] : $_FN['sitepath'];
//    $params['default_database_driver'] =  isset($params['default_database_driver']) ? $params['default_database_driver'] : $_FN['default_database_driver']; 
    FN_XMETATableEditor($tablename, $params);
}

/**
 *
 * @param string $tablename
 * @param array $params
 * @return object
 */
function FNCC_XmlForm($tablename, $params = array())
{
    global $_FN;
    foreach ($_FN as $k => $v)
    {
        if (is_string($v) && !isset($params[$k]))
        {
            $params[$k] = $v;
        }
    }
    $params['siteurl'] = $_FN['siteurl'];
    $params['charset_page'] = $_FN['charset_page'];
    $params['requiredtext'] = isset($_FN['requiredfieldsymbol']) ? $_FN['requiredfieldsymbol'] : "*";
    $t = xmetadb_frm($_FN['database'], $tablename, $_FN['datadir'], $_FN['lang'], $_FN['languages'], $params);
    if (file_exists("controlcenter/themes/{$_FN['controlcenter_theme']}/xmetadb_form.tp.html"))
    {
        //die(FN_TPL_ApplyTplFile("controlcenter/themes/{$_FN['controlcenter_theme']}/xmetadb_form.tp.html",$params));
        $t->SetlayoutTemplate(file_get_contents("controlcenter/themes/{$_FN['controlcenter_theme']}/xmetadb_form.tp.html"));
    }
    elseif (file_exists("themes/{$_FN['theme']}/xmetadb_form.tp.html"))
    {
        $t->SetlayoutTemplate(file_get_contents("themes/{$_FN['theme']}/xmetadb_form.tp.html"));
    }
    return $t;
}

/**
 * 
 * @global array $_FN
 * @staticvar string $html
 * @param type $body
 * @param type $title
 * @return string
 */
function FNCC_HtmlModalWindow($body, $title = "", $textbutton = "ok")
{
    global $_FN;
    static $html = "";
    if ($title == "")
    {
        $title = "{$_FN['sitename']}";
    }

    if ($html == "" && file_exists("controlcenter/themes/{$_FN['controlcenter_theme']}/modal.tp.html"))
    {
        $html = file_get_contents("controlcenter/themes/{$_FN['controlcenter_theme']}/modal.tp.html");
    }

    if ($html == "")
    {
        $html = "\n<script language=\"javascript\">";
        $html .= "\n setTimeout(function(){alert(\"" . str_replace("\n", "\\n", addslashes($body)) . "\",0)});";
        $html .= "\n</script>\n";
        return $html;
    }
    $html = FN_TPL_ApplyTplString($html, array("title" => $title, "body" => $body, "textbutton" => $textbutton, "idmodal" => uniqid("modal_")));
    return $html;
    //dprint_xml($html);
    //die();
}

/**
 * 
 * @param type $file
 * @param type $formaction
 * @param type $exit
 * @param type $allow
 * @param type $write_to_file
 * @param type $mod
 * @param type $block
 * @param type $htmltemplate
 */
function FNCC_HtmlEditConfFile($file, $formaction = "", $exit = "", $allow = false, $write_to_file = false, $mod = "", $block = "", $htmltemplate = "")
{
    global $_FN;
    if ($htmltemplate == "" && file_exists("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/editconf.tp.html"))
    {

        $htmltemplate = file_get_contents("{$_FN['src_finis']}/controlcenter/themes/{$_FN['controlcenter_theme']}/editconf.tp.html");
        if (false == strpos($htmltemplate, "<form"))
        {
            preg_match("/<!-- editconf table attributes -->(.*)<!-- end editconf table attributes -->/is", $htmltemplate, $out);
            $htmltemplate = empty($out[1]) ? "" : $out[1];
        }
    }
    if ($formaction == "")
    {
        $opt = FN_GetParam("opt", $_GET);
        $formaction = "?mod={$_FN['mod']}&amp;opt=$opt&amp;fnapp=controlcenter";
    }
    return FN_HtmlEditConfFile($file, $formaction, $exit, $allow, $write_to_file, $mod, $block, $htmltemplate);
}

/**
 * return file path from theme
 * 
 * @param string file
 * @param bool absolute path
 * @return string path file
 */
function FNCC_FromTheme($file, $absolute = true)
{
    global $_FN;
    // Check if the file path is already absolute
    $isAbsolute = $file[0] === $_FN['slash'] || $file[0] === '/';
    $applicationPath = $isAbsolute ? $file : "{$_FN['src_finis']}/$file";

    // Construct theme path
    $themePath = "{$_FN['src_application']}/controlcenter/themes/{$_FN['theme']}/" .
            ltrim(str_replace("{$_FN['src_finis']}/", "", $applicationPath), '/');

    // Check if the file exists in the theme directory
    if (file_exists($themePath))
    {
        return $absolute ? $_FN['siteurl'] . $themePath : $themePath;
    }

    // If not found in theme, use FN_PathSite
    return FN_PathSite($file, $absolute);
}
