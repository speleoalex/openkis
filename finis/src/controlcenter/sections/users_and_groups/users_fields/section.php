<?php
/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$old_default_database_driver = $_FN['default_database_driver'];
$_FN['default_database_driver'] = "";
$xmetadb_default_driver = "";
//----- XML TABLE ------------------------------------------------------------->
$str = "<tables>
	<field>
		<name>name</name>
		<type>string</type>
		<primarykey>1</primarykey>
	</field>
	<field>
		<name>position</name>
		<frm_i18n>position</frm_i18n>
<frm_show>0</frm_show>
		<extra>autoincrement</extra>		
	</field>
";
$str .= "<field>
		<name>frm_i18n</name>
		<frm_i18n>i18n translation</frm_i18n>
		<frm_show>1</frm_show>
	</field>
";
foreach ($_FN['listlanguages'] as $l)
{
    $str .= "<field>
		<name>frm_{$l}</name>
		<frm_i18n>force translation</frm_i18n>
		<frm_suffix>($l)</frm_suffix>
		
	</field>
";
}
$str .= "<field>
		<name>primarykey</name>
		<frm_type>check</frm_type>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>type</name>
		<frm_i18n>type</frm_i18n>
		<type>select</type>
		<frm_options>varchar,text,image,file,datetime,check,select,radio,html,password</frm_options>
	</field>
	<field>
		<name>extra</name>
		<frm_i18n>autoincrement</frm_i18n>
		<type>radio</type>
		<frm_options>,autoincrement</frm_options>
		<frm_options_i18n>no,yes</frm_options_i18n>
	</field>
	<field>
		<name>size</name>
		<frm_i18n>size</frm_i18n>		
		<type>int</type>
	</field>
	<field>
		<name>frm_type</name>
		<frm_i18n>form type</frm_i18n>
		<type>varchar</type>
		<frm_type>select</frm_type>
		<frm_options>varchar,text,image,file,localfile,datetime,check,select,radio,html,password,multicheck</frm_options>
		<frm_options_i18n>string,text,image file,file,local file,date,check,select,radiobox,html,password<,multicheck/frm_options_i18n>
	</field>
	<field>
		<name>frm_options</name>
		<frm_i18n>options</frm_i18n>
		<type>text</type>
	</field>
	<field>
		<name>frm_retype</name>
		<frm_i18n>retype</frm_i18n>
		<type>check</type>
	</field>
	<field>
		<name>frm_show</name>
		<frm_i18n>visible</frm_i18n>		
		<type>select</type>
		<frm_options>0,1</frm_options>
		<frm_options_i18n>no,yes</frm_options_i18n>
	</field>
	<field>
		<name>frm_required</name>
		<frm_i18n>required field</frm_i18n>		
		<type>select</type>
		<frm_options>0,1</frm_options>
		<frm_options_i18n>no,yes</frm_options_i18n>
	</field>
	<field>
		<name>frm_allowupdate</name>
		<frm_i18n>allow users to update value</frm_i18n>		
		<type>select</type>
		<frm_options>0,1,onlyadmin</frm_options>
		<frm_options_i18n>no,yes,only administrator</frm_options_i18n>
	</field>
	<field>
		<name>showinprofile</name>
		<frm_i18n>show in user profile</frm_i18n>
		<frm_show>1</frm_show>
		<frm_type>select</frm_type>
		<frm_options>,1</frm_options>		
		<frm_options_i18n>no,yes</frm_options_i18n>
	</field>
	<field>
		<name>frm_default</name>
		<frm_i18n>form default value</frm_i18n>
		<frm_show>1</frm_show>
	</field>
	<field>
		<name>frm_validchars</name>
		<frm_i18n>list of characters allowed</frm_i18n>
		<frm_type>text</frm_type>
		<frm_show>1</frm_show>
	</field>
	
	<field>
		<name>frm_maximagesize</name>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>foreignkey</name>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>fk_link_field</name>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>fk_show_field</name>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>frm_show_image</name>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>frm_help_i18n</name>
		<frm_show>0</frm_show>
	</field>
</tables>
";
define("_XML_STRUCT_DESCRIPTOR_", $str);
//----- XML TABLE -------------------------------------------------------------<

$save = FN_GetParam("save", $_GET, "flat");
$edit = FN_GetParam("edit", $_GET, "flat");
$moveup = FN_GetParam("moveup", $_GET, "flat");
$movedown = FN_GetParam("movedown", $_GET, "flat");

//-------------------------init table with default values---------------------->
if (file_exists("{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users_tmp.php") && empty($_GET['t']) && empty($_GET['op']) && $save == "")
{
    unlink("{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users_tmp.php");
    
}
if (isset($_GET['loaddefault']))
{
    FN_Copy("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_users.php", "{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users_tmp.php");
}
//-------------------------init table with default values----------------------<



$tparams['xmltagroot'] = "tables";
$tparams['xmlfieldname'] = "field";
$tparams['datafile'] = "{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users_tmp.php";

//---------------init table data from fn_users--------------------------------->
$xmlori = $xml = file_get_contents("{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users.php");
if (isset($_GET['loaddefault']) || (empty($_POST['op___xdb_']) && empty($_POST) && $edit == "" && $save == "" && count($_GET) <= 2))
{
    if (isset($_GET['loaddefault']))
        $xml = file_get_contents("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_users.php");
    else
        $xml = file_get_contents("{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users.php");
    preg_match_all('/<(' . "name" . '[^\/]*?)>([^<]*)<\/\1>/s', $xml, $t1);
    $i = 1;
    foreach ($t1[2] as $fieldname)
    {
        $fieldname = trim(ltrim($fieldname));
        $fieldname = str_replace("\n", "", $fieldname);
        $fieldname = str_replace("\r", "", $fieldname);
        $xml = str_replace("<name>{$fieldname}</name>", "<name>{$fieldname}</name><position>$i</position>", $xml);
        $i++;
    }
    $xmlori = $xml;
    FN_Write($xml, "{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users_tmp.php");
}
//---------------init table data from fn_users---------------------------------<
//--------------------new table insance---------------------------------------->
$metaform = new FieldFrm("fn_users_tmp", array("xml" => $str), ".", $_FN['lang'], $_FN['languages'], $tparams);
$records = $metaform->xmltable->GetRecords(false, false, false, "position");
//--------------------new table insance----------------------------------------<
//-----------------change position--------------------------------------------->
$invert = false;
$prev = false;
if ($moveup)
{
    foreach ($records as $rec)
    {
        if ($rec['name'] == $moveup)
        {
            $invert_from = $prev;
            $invert_to = $rec;
            break;
        }
        $prev = $rec;
    }
}
$invert = false;
if ($movedown)
{
    foreach ($records as $rec)
    {
        if ($invert)
        {
            $invert_to = $rec;
            $invert = false;
        }
        if ($rec['name'] == $movedown)
        {
            $invert_from = $rec;
            $invert = true;
        }
    }
}

if (isset($invert_to['position']) && isset($invert_from['position']))
{
    $tmp = $invert_to['position'];
    $invert_to['position'] = $invert_from['position'];
    $invert_from['position'] = $tmp;

//dprint_r($invert_from);
//dprint_r($invert_to);
    $r = $metaform->xmltable->UpdateRecord($invert_from);
//dprint_r($r);
    $r = $metaform->xmltable->UpdateRecord($invert_to);
//dprint_r($r);
}
$records = $metaform->xmltable->GetRecords(false, false, false, "position");
//-----------------change position---------------------------------------------<

if (isset($_GET['op___xdb_']) && $_GET['op___xdb_'] == "del")
{
    if (isset($_GET['pk___xdb_']))
    {
        switch ($_GET['pk___xdb_'])
        {
            case "username":
            case "email":
            case "passwd":
            case "level":
            case "rnd":
            case "group":
            case "active":
                FN_Alert(FN_Translate("you can not delete this field"));
                unset($_GET['pk___xdb_']);
                unset($_GET['op___xdb_']);
                break;
        }
    }
}

//--------------------save data------------------------------------------------>
if ($save == 1)
{
    $xml = file_get_contents("{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users_tmp.php");
    $tableusers = FN_XMDBForm("fn_users_tmp");
    $formvals = $tableusers->formvals;
    $errors = false;
    if (!isset($formvals['username']))
    {
        $errors[] = "field username is required";
    }
    if (empty($tableusers->xmltable->primarykey))
    {
        $errors[] = "primarykey is required";
    }

    if (!isset($formvals['email']))
    {
        $errors[] = "field email is required";
    }
    if (!isset($formvals['passwd']))
    {
        $errors[] = "field passwd is required";
    }
    if (!isset($formvals['level']))
    {
        $errors[] = "field level is required";
    }

    if (!isset($formvals['group']))
    {
        $errors[] = "field level is required";
    }
    if (!isset($formvals['rnd']))
    {
        $errors[] = "field rnd is required";
    }
    if (!isset($formvals['active']))
    {
        $errors[] = "field active is required";
    }
    if (is_array($errors))
    {
        echo "<h3>" . FN_Translate("error") . "</h3>";
        foreach ($errors as $error)
        {
            echo "<div>" . FN_Translate($error) . "</div>";
        }
        echo "<br /><br /><br /><a href=\"?fnapp=controlcenter&opt=$opt&amp;edit=1\">" . FN_Translate("next") . " &gt;&gt;</a>";
    }
    else
    {
        $xml = file_get_contents("{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users_tmp.php");
        $xml = preg_replace('/<(' . "position" . '[^\/]*?)>([^<]*)<\/\1>/s', "", $xml);
        FN_Write($xml, "{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users.php");
        echo FN_Translate("the data were successfully updated");
        echo "<br /><br /><br /><a href=\"?fnapp=controlcenter&opt=$opt\">" . FN_Translate("next") . " &gt;&gt;</a>";
    }
}
//--------------------save data------------------------------------------------<
else
{
    edit_struct_table("fn_users_tmp", "{$_FN['src_application']}/{$_FN['datadir']}", $_FN['database']);
    $xml1 = $xmlori;
    $xml2 = file_get_contents("{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users_tmp.php");
    $xml3 = file_get_contents("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_users.php");
    $xml4 = file_get_contents("{$_FN['src_application']}/{$_FN['datadir']}/{$_FN['database']}/fn_users.php");
    $xml1 = str_replace("\t", "", str_replace("\n", "", str_replace("\r", "", $xml1)));
    $xml2 = str_replace("\t", "", str_replace("\n", "", str_replace("\r", "", $xml2)));
    $xml3 = str_replace("\t", "", str_replace("\n", "", str_replace("\r", "", $xml3)));
    $xml4 = str_replace("\t", "", str_replace("\n", "", str_replace("\r", "", $xml4)));

    if ($xml2 != $xml3)
    {
        echo "<div style=\"text-align:right\">";
        echo "<a  href=\"?fnapp=controlcenter&opt=$opt&amp;loaddefault=1\" >" . FN_Translate("load default") . "</a>";
        echo "</div>";
    }

    if ($xml1 != $xml2 || (isset($_GET['loaddefault']) && $xml3 != $xml4))
    {
        echo "<br /><br /><div style=\"background-color:yellow;color:red;\">" . FN_Translate("you have not yet applied changes");
        echo "</div>
		<a  href=\"?fnapp=controlcenter&opt=$opt&amp;save=1\">" . FN_Translate("apply changes") . "</a>
		&nbsp;&nbsp;&nbsp;<a  href=\"?fnapp=controlcenter&opt=$opt\">" . FN_Translate("cancel") . "</a>";
    }
}

/**
 * Edit xmltable structure
 * @param string $tablename
 * @param string $path
 * @param datanase name $databasename
 */
function edit_struct_table($tablename, $path, $databasename)
{
    global $_FN;
    $str = _XML_STRUCT_DESCRIPTOR_;
    $opt = FN_GetParam("opt", $_GET, "flat");
    $op = FN_GetParam("op", $_GET, "flat");
    $tparams['xmltagroot'] = "tables";
    $tparams['xmlfieldname'] = "field";
    $tparams['datafile'] = "$path/$databasename/$tablename.php";
    $table = new FieldFrm($path, array("xml" => $str), ".", $_FN['lang'], $_FN['languages'], $tparams);
    $params ['path'] = $path;
    $params ['table'] = $table;
    $params ['defaultorder'] = "position";
    $params ['bkheader'] = "#ffff00";
    $params ['bordercolor'] = "#ffaaaa";
    $params ['fields'] = "position|MoveUpdown()|name|frm_i18n|frm_type|frm_show|frm_required|showinprofile|frm_title_insert_i18n";
    $params ['link'] = "fnapp=controlcenter&op=edit&amp;opt=$opt&amp;t=$tablename";
    $params ['textnew'] = FN_Translate("new field");
    $params ['xmldatabase'] = "tables";
    FNCC_XMETATableEditor(false, $params);
    //fix position---->
    if (!empty($_GET['moveup']) || !empty($_GET['movedown']))
    {
        $recs = $table->xmltable->GetRecords(false, false, false, "position");
        if (is_array($recs))
        {
            $table->xmltable->Truncate();
            $recs2 = $table->xmltable->GetRecords(false, false, false, "position");
            //dprint_r($recs2);
            foreach ($recs as $record)
            {
                $table->xmltable->InsertRecord($record);
            }
        }
    }
    //fix position----<
    return;
}

/**
 * 
 * @param type $id
 * @param type $params
 * @return type
 */
function MoveUpdown($id, $params)
{
    $opt = FN_GetParam("opt", $_GET);
    $html = "";
    $html .= "<a href=\"?fnapp=controlcenter&opt=$opt&amp;moveup=$id&amp;op=edit\"><img alt=\"\" title=\"\" src=\"images/fn_up.png\" /></a>";
    $html .= "<a href=\"?fnapp=controlcenter&opt=$opt&amp;movedown=$id&amp;op=edit\"><img alt=\"\" title=\"\" src=\"images/fn_down.png\" /></a>";
    return $html;
}

$_FN['default_database_driver'] = $old_default_database_driver;

