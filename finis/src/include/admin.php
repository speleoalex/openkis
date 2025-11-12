<?php

/**
 * @package Finis
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2024
 */
defined('_FNEXEC') or die('Restricted access');

/**
 * print editmode button
 * @global array $_FN
 * @return string
 */
function FN_HtmlAdminOnOff()
{
    global $_FN;
    $html = "";
    $stout = "";
    //tasto editmode ON/OFF---------------------------------------------------->
    if (FN_IsAdmin() || FN_CanModify($_FN['user'], "{$_FN['src_application']}/sections/{$_FN['mod']}")) {
        $img = FN_GetUserImage($_FN['user']);
        $stout .= "<div id=\"fn_adminonoff\" style=\"\" class=\"fn_admin\"><span class=\"fn_admin_username\"><img src=\"$img\"  /> " . " {$_FN['user']}";
        $stout .= " <a href=\"{$_FN['siteurl']}?fnlogin=logout\">" . FN_Translate("logout") . "</a></span>";
        if (!empty($_FN['maintenance'])) {
            $stout .= "<span style=\"\">" . FN_Translate("site in maintenance") . "</span> ";
        } {
            $stout .= "<a class=\"fn_admin_ccicon\" style=\"cursor:pointer\" title=\"" . FN_Translate("control center") . "\"  href=\"{$_FN['siteurl']}index.php?fnapp=controlcenter\" >&#x1F6E0;</a>";
        }
        $stout .= "<select  title=\"" . FN_Translate("edit mode") . "\" style=\"vertical-align:middle\" onchange=\"window.location=this.options[this.selectedIndex].value\" >";
        //$stout .=FN_i18n("edit mode")."<select >";
        $s = $_FN['fneditmode'] == 0 ? "selected=\"selected\"" : "";
        $stout .= "<option title=\"" . FN_Translate("edit mode off") . "\" $s value=\"index.php?mod={$_FN['mod']}&amp;fneditmode=0\" >" . FN_Translate("edit mode") . ":" . FN_Translate("off") . "</option>";
        $s = $_FN['fneditmode'] != 0 ? "selected=\"selected\"" : "";
        $stout .= "<option title=\"" . FN_Translate("edit mode on") . "\"  $s value=\"index.php?mod={$_FN['mod']}&amp;fneditmode=1\" >" . FN_Translate("edit mode") . ":" . FN_Translate("on") . "</option>";
        $stout .= "</select>";
        $stout .= "<a title=\"" . FN_Translate("close") . "\" onclick=\"document.getElementById('fn_adminonoff').style.display='none';return false;\" alt=\"close\" style=\"border:0px;vertical-align:middle;cursor:pointer\" >&#10060;</a>";
        $stout .= "</div>";

        //tasto editmode ON/OFF----------------------------------------------------<
        $html = "";
        $stout = addslashes($stout);
        $html .= "
<script type=\"text/javascript\" >
    function fn_addOnOff()
    {
        var node =  document.createElement('div');
        node.innerHTML='$stout';
            
        document.getElementsByTagName(\"body\")[0].appendChild(node) ;
    }
    window.setTimeout('fn_addOnOff()',100);
</script>
";
    }
    return $html;
}

/**
 *
 * @global array $_FN
 * @return string
 */
function FN_HtmlAdminOptions()
{
    global $_FN;
    if (!$_FN['enable_online_administration'])
        return "";
    $html = FN_FtmlAdminCSS();
    $html .= FN_HtmlAdminOnOff();
    if (FN_IsAdmin()) {
        $filetodel = FN_GetParam("filetodel", $_POST, "html");
        if (file_exists($filetodel)) {
            FN_BackupFile($filetodel);
            if (unlink($filetodel)) {
                $html .= FN_HtmlAlert(FN_Translate("the content has been deleted"));
            } else {
                $html .= FN_Translate("permissions error");
            }
        }
    }
    if ($_FN['fneditmode'] && FN_CanModify($_FN['user'], "{$_FN['src_application']}/sections/{$_FN['mod']}")) {
        //check if section is empty --->
        $SectionIsEmpty = true;
        if (file_exists("{$_FN['src_application']}/sections/{$_FN['mod']}/section.php"))
            $SectionIsEmpty = false;
        foreach ($_FN['listlanguages'] as $l) {
            if (file_exists("{$_FN['src_application']}/sections/{$_FN['mod']}/section.{$_FN['lang']}.html")) {
                $SectionIsEmpty = false;
            }
        }
        //check if section is empty ---<
        $html .= "<div id=\"fn_adminoptions\" class=\"fn_admin\">";
        //------SECTION.PHP, SECTION.XX.HTML ECC. ------------------------->
        if (FN_UserCanEditFolder("{$_FN['src_application']}/sections/{$_FN['mod']}")) {
            $html .= FN_OpenAdminSection(FN_Translate("page contents"), true);
            if (file_exists("{$_FN['src_application']}/sections/{$_FN['mod']}/section.php")) {
                $html .= "<button onclick=\"window.location='{$_FN['siteurl']}index.php?mod={$_FN['mod']}&opt=sections/{$_FN['mod']}/section.php'\">" . FN_Translate("modify") . "</button><br />";
            }
            //----------------section.xx.html------------------------------>
            //----------------settings button---------------------------------->
            $html .= "<table>";
            $title_admin = $_FN['sectionvalues']['title'] . " (" . FN_Translate("page like", "Aa") . " " . FN_GetFolderTitle("{$_FN['src_finis']}/modules/{$_FN['sectionvalues']['type']}") . ")";
            if (!empty($_FN['sectionvalues']['type']) && file_exists("{$_FN['src_finis']}/modules/{$_FN['sectionvalues']['type']}/controlcenter/settings.php")) {
                $html .= "<tr><td>&#x1F527;</td><td><button onclick=\"window.location='{$_FN['siteurl']}index.php?mod={$_FN['mod']}&opt=rnt_ccnf_section_{$_FN['mod']}'\">" .
                    FN_Translate("administration tools") . " $title_admin" .
                    "</button></td></tr>";
            }
            if (empty($_FN['sectionvalues']['type']) && file_exists("{$_FN['src_application']}/sections/{$_FN['mod']}/controlcenter/settings.php")) {
                $html .= "<tr><td>&#x1F527;</td><td><button onclick=\"window.location='{$_FN['siteurl']}index.php?mod={$_FN['mod']}&opt=rnt_ccnf_section_{$_FN['mod']}'\">" .
                    FN_Translate("administration tools") . $title_admin .
                    "</button></td></tr>";
            }
            //----------------settings button----------------------------------<
            //------------------config button---------------------------------->
            if (FN_IsAdmin()) {
                if (!empty($_FN['sectionvalues']['type']) && file_exists("{$_FN['src_finis']}/modules/{$_FN['sectionvalues']['type']}/config.php")) {
                    $html .= "<tr><td>&#x1F6E0;</td><td><button onclick=\"window.location='{$_FN['siteurl']}index.php?mod={$_FN['mod']}&opt=modules/{$_FN['sectionvalues']['type']}/config.php'\">" .
                        FN_Translate("advanced settings") . " $title_admin" .
                        "</button></td></tr>";
                }
                if (empty($_FN['sectionvalues']['type']) && file_exists("{$_FN['src_application']}/sections/{$_FN['mod']}/config.php")) {
                    $html .= "<tr><td><button onclick=\"window.location='{$_FN['siteurl']}index.php?mod={$_FN['mod']}&opt=sections/{$_FN['mod']}/config.php'\">" .
                        FN_Translate("advanced settings") . " " . $_FN['sectionvalues']['title'] .
                        "</button></td></tr>";
                }
            }
            //------------------config button----------------------------------<
            //----------------section.xx.html----------------------------------<
            foreach ($_FN['listlanguages'] as $l) {
                if (FN_UserCanEditFile("{$_FN['src_application']}/sections/{$_FN['mod']}/section.{$l}.html")) {
                    if (!file_exists("{$_FN['src_application']}/sections/{$_FN['mod']}/section.{$l}.html")) {
                        $html .= "\n<tr><td><span style=\"border:1px solid #ff0000;\" >" .
                            FN_getCountryFlag($l) . "</span> " .
                            "</td><td><button onclick=\"window.location='" .
                            FN_RewriteLink("index.php?mod={$_FN['mod']}&opt=sections/{$_FN['mod']}/section.{$l}.html", "&") . "'\" >" .
                            FN_Translate("create a text content in") . " " . FN_GetFolderTitle(FN_FinisPathToApplicationPath("{$_FN['src_finis']}/languages/$l")) . "" .
                            "</button>";
                    } else {
                        $html .= "\n<tr><td><span style=\"border:1px solid #00ff00;\">" .
                            FN_getCountryFlag($l) . "</span> " .
                            "</td><td><button onclick=\"window.location='" .
                            FN_RewriteLink("index.php?mod={$_FN['mod']}&opt=sections/{$_FN['mod']}/section.{$l}.html", "&") . "'\" >" .
                            FN_Translate("modify") .
                            "</button>
<form style=\"display:inline\" method=\"post\" action=\"" . FN_RewriteLink("index.php?mod={$_FN['mod']}") . "\">
<input type=\"hidden\" name=\"filetodel\" value=\"{$_FN['src_application']}/sections/{$_FN['mod']}/section.{$l}.html\" />    
<button type=\"submit\" onclick=\"if(!confirm ('" . FN_Translate('are you sure you want to delete this content?') . "')){return false;}\" >" . FN_Translate("delete") . "</button></form>";
                    }
                    $html .= "    <button onclick=\"window.location='" .
                        FN_RewriteLink("index.php?mod={$_FN['mod']}&opt=sections/{$_FN['mod']}/section.{$l}.html&mode=versions", "&") . "'\" >" .
                        FN_Translate("old versions") .
                        "</button>";
                    $html .= "</td></tr>";
                }
            }
            //----------------section.xx.html----------------------------------<
            $html .= "</table>";
            $html .= FN_CloseAdminSection();
            //------SECTION.PHP, SECTION.XX.HTML ECC. -------------------------<
        }
        if (FN_IsAdmin()) {
            //update section--------------------------------------------------->
            $html .= FN_OpenAdminSection(FN_Translate("page properties"), isset($_REQUEST['updatesection']));
            $html .= "<form class=\"fn_adminform\" method=\"post\" action=\"" . FN_RewriteLink("index.php?mod={$_FN['mod']}") . "\"><div>";
            $form = FN_XMDBForm("fn_sections");
            $form->formvals['id']['frm_show'] = "0";
            $form->formvals['title']['frm_required'] = "0";
            $form->formvals['parent']['frm_show'] = "0";
            $form->formvals['position']['frm_show'] = "0";
            $newvalues = isset($_POST['updatesection']) ? $form->GetByPost() : array();
            $newvalues['id'] = $_FN['mod'];
            $errors = array();
            if (isset($_POST['updatesection'])) {
                $errors = $form->VerifyUpdate($newvalues);
                if (count($errors) == 0) {
                    $form->UpdateRecord($newvalues);
                    $html .= FN_HtmlAlert("the data were successfully updated");
                    $html .= "<script language=\"javascript\">\nwindow.location='{$_FN['siteurl']}index.php?mod={$_FN['mod']}&updatesection=1'\n</script>";
                    FN_UpdateDefaultXML($newvalues);
                    FN_Log("updated section:{$newvalues['id']}");
                    FN_OnSitemapChange();
                }
            }
            $template = "
<table>
<!-- contents -->
<!-- group -->
	<tr><td colspan=\"2\" style=\"text-align:center\"><b>{groupname}</b></td></tr>
<!-- end_group -->
<!-- item -->
	<tr>
		<td class=\"fn_fnadminformtitle\">{title}<!-- error --><span style=\"color:red\"><br />{error}</span><!-- end_error --></td>
		<td class=\"fn_fnadminformvalue\" >{input}</td>
	</tr>
<!-- end_item -->
<!-- endgroup -->
<!-- end_endgroup -->
<!-- end_contents -->
<tr><td colspan=\"2\"><div class=\"fn_fnadminsave\" ><button type=\"submit\">" . FN_Translate("save") . "</button></div></td></tr>
</table>
";
            $form->SetlayoutTemplate($template);
            $html .= $form->HtmlShowUpdateForm($_FN['mod'], false, $newvalues, $errors);
            $html .= "<input type=\"hidden\" name=\"updatesection\" value=\"1\"/>";
            //$html .= "<div class=\"fn_fnadminsave\" ><button type=\"submit\">" . FN_Translate("save") . "</button></div>";
            $html .= "</div></form>";
            $html .= FN_CloseAdminSection();
        }
        //update section---------------------------------------------------<
        if (FN_IsAdmin()) {
            //new section------------------------------------------------------>
            if (FN_IsWritable("{$_FN['src_application']}/sections")) {
                $forminsert = FN_XMDBForm("fn_sections");
                $newvalues = isset($_POST['newsection']) ? $forminsert->GetByPost() : array();
                $errors = array();
                $html .= FN_OpenAdminSection(FN_Translate("create new page"), isset($_POST['newsection']));
                $html .= "<form class=\"fn_adminform\" method=\"post\" action=\"" . FN_RewriteLink("index.php?mod={$_FN['mod']}") . "\"><div>";
                $html .= "<input type=\"hidden\" name=\"newsection\" value=\"1\"/>";
                $forminsert->formvals['id']['frm_show'] = "0";
                $forminsert->formvals['title']['frm_required'] = "1";
                $forminsert->formvals['parent']['frm_show'] = "0";
                $forminsert->formvals['position']['frm_show'] = "0";
                $sections = FN_GetSections("", true);
                if (!isset($newvalues['status'])) {
                    $newvalues['status'] = "1";
                }
                $html .= FNADMIN_HtmlSectionsTree();
                if (isset($_POST['newsection'])) {
                    if (isset($newvalues['title']))
                        $newvalues['id'] = FN_MakeSectionId($newvalues['title']);
                    $before_after = FN_GetParam("before_after", $_POST);
                    $before_after_section = FN_GetParam("before_after_section", $_POST);
                    $newvalues['parent'] = isset($sections[$before_after_section]['parent']) ? $sections[$before_after_section]['parent'] : "";
                    $newvalues['sectionpath'] = "sections";
                    //dprint_r($_POST);
                    //dprint_r($newvalues);
                    $errors = $forminsert->VerifyInsert($newvalues);
                    if (count($errors) == 0) {
                        if ($newvalues['type'] != "" && file_exists("{$_FN['src_finis']}/modules/{$newvalues['type']}/section_template")) {
                            $r = FN_CopyDir("{$_FN['src_finis']}/modules/{$newvalues['type']}/section_template", "{$_FN['src_application']}/sections/{$newvalues['id']}", false);
                        } else {
                            $r = FN_MkDir("{$_FN['src_application']}/sections/{$newvalues['id']}");
                        }
                        if ($r) {
                            //fix position --------->
                            $i = 1;
                            $newsections = array();
                            foreach ($sections as $k => $section) {
                                $newsections[$k] = $section;
                                $newsections[$k]['position'] = $i;
                                if ($k == $before_after_section) {
                                    if ($before_after == "before") {
                                        $newvalues['position'] = $i;
                                        $i++;
                                        $newsections[$k]['position'] = $i;
                                    } else {
                                        $i++;
                                        $newvalues['position'] = $i;
                                    }
                                }
                                $i++;
                            }
                            if ($before_after == "inside")
                                $newvalues['parent'] = $before_after_section;
                            foreach ($newsections as $k => $newsection) {
                                if ($newsections[$k]['position'] != $sections[$k]['position']) {
                                    $forminsert->UpdateRecord(array("id" => $newsections[$k]['id'], "position" => $newsections[$k]['position']));
                                }
                            }
                            //fix position ---------<
                            $forminsert->InsertRecord($newvalues);
                            FN_Log("created new section:{$newvalues['id']}");
                            FN_Alert("the page has been created");
                            FN_JsRedirect(FN_RewriteLink("index.php?mod={$newvalues['id']}&amp;updatesection=1"));
                            FN_OnSitemapChange();
                        }
                    }
                }
                $template = "
<table>
<!-- contents -->
<!-- group -->
	<tr><td colspan=\"2\" style=\"text-align:center\"><b>{groupname}</b></td></tr>
<!-- end_group -->
<!-- item -->
	<tr>
		<td class=\"fn_fnadminformtitle\">{title}<!-- error --><span style=\"color:red\"><br />{error}</span><!-- end_error --></td>
		<td class=\"fn_fnadminformvalue\" >{input}</td>
	</tr>
<!-- end_item -->
<!-- endgroup -->
<!-- end_endgroup -->
<!-- end_contents -->
<tr><td colspan=\"2\"><div class=\"fn_fnadminsave\" ><button type=\"submit\">" . FN_Translate("save") . "</button></div></td></tr>
</table>
";
                $forminsert->SetlayoutTemplate($template);
                $html .= $forminsert->HtmlShowInsertForm(false, $newvalues, $errors);
                //$html .= "<div class=\"fn_fnadminsave\" ><button type=\"submit\">" . FN_i18n("save") . "</button></div>";
                $html .= "</div></form>";
                $html .= FN_CloseAdminSection();
            }
            //new section------------------------------------------------------<
            $html .= "<button onclick=\"filemanagerpopup=window.open('{$_FN['siteurl']}index.php?fnapp=filemanager&mime=all&amp;dir=sections/{$_FN['mod']}','filemanager_','top=10,left=10,height=450,width=480,scrollbars=no');filemanagerpopup.focus()\" >" . FN_Translate("filemanager") . "</button>";
        }
        $html .= "</div>";
    }

    return $html;
}

/**
 *
 * @staticvar string $list
 * @staticvar array $retval
 * @param string $parent
 * @param array $sections
 * @return array 
 */
function FNCC_SortSectionsByTree($parent, $sections)
{
    static $list = array();
    static $retval = array();
    if ($parent == "") {
        $list = array();
        $retval = array();
    }
    foreach ($sections as $section) {
        if ($section['parent'] == $parent) {
            if (in_array($section['id'], $list))
                return;
            $list[] = $section['id'];
            $retval[] = $section;
            FNCC_SortSectionsByTree($section['id'], $sections);
        }
    }
    return $retval;
}

/**
 *
 * @return string
 */
function FNADMIN_HtmlSectionsTree()
{
    $html = "<div>" . FN_Translate("create new page") . ": <select name=\"before_after\" >";
    $s = isset($_POST['before_after']) && $_POST['before_after'] == "after" ? "selected=\"selected\"" : "";
    $html .= "<option $s value=\"after\">" . FN_Translate("after") . "</option>";
    $s = isset($_POST['before_after']) && $_POST['before_after'] == "before" ? "selected=\"selected\"" : "";
    $html .= "<option $s value=\"before\">" . FN_Translate("before") . "</option>";
    $s = isset($_POST['before_after']) && $_POST['before_after'] == "inside" ? "selected=\"selected\"" : "";
    $html .= "<option $s value=\"inside\">" . FN_Translate("inside") . "</option>";

    $html .= "</select>";

    $html .= "<select name = \"before_after_section\" >";
    $sections = FN_GetSections(false, true, true, true);
    //sort sections --------------------------------------------------------------->
    $sections = FNCC_SortSectionsByTree("", $sections);
    //sort sections ---------------------------------------------------------------<
    foreach ($sections as $section) {
        $s = isset($_POST['before_after_section']) && $_POST['before_after_section'] == $section['id'] ? "selected=\"selected\"" : "";
        $style = "";
        if ($section['hidden']) {
            $style = "style=\"text-decoration: line-through\"";
        }
        $padding = "";
        $margin = count($section['path']);
        for ($i = 0; $i < $margin; $i++) {
            $padding .= "&nbsp;&nbsp;";
        }
        $html .= "<option $style $s  value=\"{$section['id']}\" >$padding" . htmlspecialchars($section['title']) . "</option>";
    }
    $html .= "</select></div><hr />";
    return $html;
}

/**
 *
 * @staticvar string $style
 * @param string $title
 * @return string
 */
function FN_OpenAdminSection($title, $visible = false)
{
    global $_FN;
    $style = $visible ? "display:block" : "display:none";
    $html = "";
    $html .= "<div class=\"fn_admin_toggle\"><a href=\"#\" onclick=\"this.parentNode.childNodes.item(1).style.display=this.parentNode.childNodes.item(1).style.display=='block'?'none':'block';return false;\">$title &#x1F527;</a>";
    $html .= "<div  style=\"$style\" >";
    return $html;
}

/**
 *
 * @return string 
 */
function FN_CloseAdminSection()
{
    $html = "";
    $html .= "</div>";
    $html .= "</div>";
    return $html;
}

/**
 *
 * @param type $section
 * @return boolean 
 */
function FN_HtmlEditSection($section)
{
    ob_start();
    $sectionvalues = FN_GetSectionValues($section); {
        if (!empty($sectionvalues['type'])) {
            if (file_exists("{$_FN['src_finis']}/modules/{$sectionvalues['type']}/controlcenter/settings.php")) {
                include "{$_FN['src_finis']}/modules/{$sectionvalues['type']}/controlcenter/settings.php";
            } else {
                return false;
            }
        } elseif (file_exists("{$_FN['src_application']}/sections/$section/controlcenter/settings.php")) {
            include "{$_FN['src_application']}/sections/$section/controlcenter/settings.php";
        } else {
            return false;
        }
        $html = ob_get_clean();
        $html .= FN_FtmlAdminCSS();

        return $html;
    }
}

function FN_FtmlAdminCSS()
{
    $sitepath = FN_SitePath();
    $csspath = FN_FinisPathToApplicationPath("include/css/admin.css", true);
    $htmlcss = "\n\t<link rel='StyleSheet' type='text/css' href=\"{$sitepath}include/css/admin.css\" />";
    $html = "<script>window.setTimeout(function(){document.getElementsByTagName('head')[0].innerHTML+='" . addslashes(str_replace("\n", "", $htmlcss)) . "';},10);</script>";
    return $html;
}

function FN_HtmlOnlineAdmin($modcont)
{
    global $_FN;
    $mode = FN_GetParam("mode", $_GET, "flat");
    $t_exit = "<button onclick=\"window.location='" . FN_RewriteLink("index.php?mod={$_FN['mod']}", "&") . "';return false;\">&larr; " . FN_Translate("back to") . " \"{$_FN['sectionvalues']['title']}\"</button>";
    $html = FN_FtmlAdminCSS();
    if ($mode == "versions") {

        $html .= "FILE: <b>$modcont</b><br />";
        $html .= "" . FN_Translate("versions") . ":<br />";
        $html .= "<table><tr><td>" . FN_Translate("creation date") . "</td><td>" . FN_Translate("created by") . "</td><td>" . FN_Translate("delete date") . "</td><td>" . FN_Translate("overwritten by") . "</td><td></td></tr>";
        $files = glob("$modcont.*");
        usort($files, "FN_UsortFilemtime");
        $bk_user = "";
        foreach ($files as $file) {
            $html .= "<tr>";
            $attr = explode(".", basename($file));
            $date = DateTime::createFromFormat('YmdHis', $attr[count($attr) - 3]);
            $dateFile = $attr[count($attr) - 4];

            if (is_numeric($dateFile)) {
                $dateFile = FN_FormatDate($dateFile);
            } else {
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
        if (file_exists($modcont)) {
            $html .= "<tr><td>" . FN_FormatDate(filemtime($modcont)) . "</td><td>$bk_user</td><td>-</td><td>-</td><td><button onclick=\"window.location='{$_FN['siteurl']}index.php?mod={$_FN['mod']}&opt=$modcont'\">" . FN_Translate("edit") . "</button>" . "</td></tr>";
        }
        $html .= "</table>";
        $linkcancel = FN_RewriteLink("index.php?mod={$_FN['mod']}");
        $html .= "<br /><button onclick=\"window.location='$linkcancel';\">" . FN_Translate("cancel") . "</button>";
        return "<div class=\"fn_admin\"><h4>" . FN_Translate("administration tools") . " $title $t_exit</h4>" . $html . "</div>";
    } else {

        $title = $_FN['sectionvalues']['title'];
        if (FN_erg('config.php$', $modcont)) {

            $title = FN_Translate("advanced settings", "Aa") . " $title";
            if (!file_exists($modcont) && file_exists($_FN['src_finis'] . "/" . $modcont)) {
                $modcont = $_FN['src_finis'] . "/" . $modcont;
            }
        }
        if ($_FN['sectionvalues']['type'] != "" && is_dir("{$_FN['src_finis']}/modules/{$_FN['sectionvalues']['type']}"))
            $title .= " (" . FN_Translate("page like", "Aa") . " " . FN_GetFolderTitle("{$_FN['src_finis']}/modules/{$_FN['sectionvalues']['type']}") . ")";

        //try edit module------------------------------------------------------>
        if ($modcont == "rnt_ccnf_section_{$_FN['mod']}") {
            if (FN_UserCanEditSection() && false !== ($html = FN_HtmlEditSection($_FN['mod']))) {
                return "<div class=\"fn_admin\"><h4>" . FN_Translate("administration tools") . " $title $t_exit</h4>" . $html . "</div>";
            }
        }
        //try edit module------------------------------------------------------<
        //try edit file-------------------------------------------------------->
        elseif (is_dir(dirname($modcont)) && !is_dir($modcont) && FN_CanModifyFile($_FN['user'], $modcont)) {
            $editor_params = array();
            $linkcancel = FN_RewriteLink("index.php?mod={$_FN['mod']}");
            $linkform = FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;opt=$modcont");
            if (file_exists("{$_FN['src_application']}/sections/{$_FN['mod']}/style.css")) {
                $editor_params['css_file'] = "{$_FN['src_application']}/sections/{$_FN['mod']}/style.css";
            }
            $_FN['editor_folder'] = "{$_FN['src_application']}/sections/{$_FN['mod']}";
            $file_restore = FN_GetParam("restore", $_GET);
            if (!empty($file_restore) && file_exists($file_restore) && FN_GetFileExtension($file_restore) == "bak~") {
                $editor_params['force_value'] = file_get_contents($file_restore);
                $linkcancel = $linkcancel = FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;opt=$modcont&amp;mode=versions");
                $editor_params['text_save'] = FN_Translate("restore");
            }
            $html = FN_HtmlEditContent($modcont, $linkform, $linkcancel, $editor_params);
            if (!empty($_POST['savefileconfig'])) {
                FN_UpdateDefaultXML(FN_GetSectionValues($_FN['mod'], false));
            }
            $html = FN_FtmlAdminCSS() . $html;
            if ($html !== false) {
                return "<div class=\"fn_admin\"><h4>$title $t_exit</h4>" . $html . "</div>";
            }
        }
        //try edit file--------------------------------------------------------<
    }
    return $html;
}
