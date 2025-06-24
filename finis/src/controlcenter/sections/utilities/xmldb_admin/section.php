<?php

global $_FN, $_FNMESSAGE;

$_FN['table_border'] = "#000000;";
$_FN['table_background'] = "#ffffff;";

define("_XMLTABLESTRUCTURE", "<tables>
    <field>
            <name>name</name>
            <type>string</type>
            <primarykey>1</primarykey>
            <frm_group>DATA</frm_group>
    </field>
    <field>
            <name>primarykey</name>
            <frm_type>check</frm_type>
            <frm_show>1</frm_show>
            <frm_allowupdate>0</frm_allowupdate>
    </field>
    <field>
            <name>type</name>
            <frm_i18n>type (db)</frm_i18n>
            <type>select</type>
            <frm_options>varchar,text,image,file,datetime,check,select,radio,html,password</frm_options>
    </field>
    <field>
            <name>extra</name>
            <type>select</type>
            <frm_options>autoincrement</frm_options>
    </field>	
    <field>
            <name>size</name>
            <type>string</type>+
            <frm_endgroup></frm_endgroup>
    </field>
    <field>
            <name>frm_type</name>
            <type>varchar</type>
            <frm_i18n>type (form)</frm_i18n>
            <frm_type>stringselect</frm_type>
            <frm_options>varchar,text,image,file,localfile,datetime,check,select,radio,html,password</frm_options>
            <frm_group>FORM</frm_group>
    </field>
    <field>
            <name>frm_options</name>
            <frm_i18n>list options</frm_i18n>
            <type>text</type>
    </field>
    <field>
            <name>frm_retype</name>
            <type>check</type>
    </field>
    <field>
            <name>frm_show</name>
            <type>select</type>
            <frm_options>0,1</frm_options>
    </field>
    <field>
            <name>frm_default</name>
            <frm_i18n>default value</frm_i18n>
            <type>text</type>
    </field>
    <field>
            <name>frm_validator</name>
            <type>string</type>
    </field>
    <field>
            <name>frm_allowupdate</name>
            <type>select</type>
            <frm_options>0,1,onlyadmin</frm_options>
    </field>
    <field>
            <name>frm_multilanguage</name>
            <frm_type>check</frm_type>
            <frm_endgroup></frm_endgroup>
    </field>
</tables>
");
$opt = FN_GetParam("opt", $_GET, "flat");
$op = FN_GetParam("op", $_GET, "flat");
$tablename = FN_GetParam("t", $_GET, "flat");

$path = $_FN['datadir'];
$databasename = "fndatabase";
echo "<a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt\">Tables</a>&nbsp;";

switch ($op)
{
    case "edit":
        show_tools();
        //echo "-&gt;<a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op=$op&amp;t=$tablename\">$tablename(structure)</a><hr />";
        edit_table($tablename, $path, $databasename);
        break;
    case "new":
        new_table($tablename, $path, $databasename);
        break;
    case "editdata":
        show_tools();
        $params ['link'] = "fnapp=controlcenter&op=editdata&amp;opt=$opt&amp;t=$tablename";
        //$params ['xmldatabase'] = "fndatabase";
        FNCC_xmltableeditor($tablename, $params);
        break;

    case "editxml":
        show_tools();
        //echo "-&gt;<a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op=$op&amp;t=$tablename\">$tablename(xml)</a><hr />";
        FN_EditContent("{$_FN['datadir']}/fndatabase/$tablename.php", "{$_FN['self']}?fnapp=controlcenter&mod=" . $_FN['mod'] . "&opt=$opt&op=editxml&t=$tablename");
        break;
    case "tomysql":
        $host = FN_GetParam("host", $_POST, "flat");
        $user = FN_GetParam("user", $_POST, "flat");
        $dbname = FN_GetParam("dbname", $_POST, "flat");
        $password = FN_GetParam("password", $_POST, "flat");
        $port = FN_GetParam("port", $_POST, "flat");
        if ($port == "")
            $port = 3306;
        if ($host == "")
            $host = "localhost";
        if ($dbname == "")
            $dbname = "fndatabase";
        if ($user == "")
            $user = "root";


        if (isset($_POST['submit']))
        {
            $connection = array("host" => $host, "user" => $user, "password" => $password, "database" => $dbname, "port" => $port);
            //dprint_r($connection);
            if (xml_to_sql("fndatabase", $tablename, $_FN['datadir'], $connection, false))
            {
                echo "<br /><br /><br /><a href=\"?fnapp=controlcenter&opt=$opt\">" . FN_Translate("next") . " &gt;&gt;</a>";
            }
            else
            {
                echo "<br /><br />" . FN_Translate("error");
                echo "<br /><br /><a href=\"javascript:history.back()\">&lt;&lt;" . $_FNMESSAGE['error'] . "</a>";
            }
            break;
        }

        echo "<form method=\"post\" action=\"?fnapp=controlcenter&op=$op&amp;opt=$opt&amp;mod={$_FN['mod']}&amp;t=$tablename\">";
        echo "<br />Mysql host:<br /><input name=\"host\" value=\"$host\"/>";
        echo "<br />Mysql port:<br /><input type=\"text\" name=\"password\" value=\"$port\"/>";
        echo "<br />Mysql database:<br /><input name=\"dbname\" value=\"$dbname\"/>";
        echo "<br />Mysql user:<br /><input name=\"user\" value=\"$user\"/>";
        echo "<br />Mysql password:<br /><input type=\"password\" name=\"password\" value=\"$password\"/>";
        echo "<br /><br /><input type=\"submit\" name=\"submit\" value=\"" . FN_i18n("save") . "\"/>";
        echo "&nbsp;<input onclick=\"window.location='?mod={$_FN['mod']}&opt=$opt'\" type=\"button\" name=\"cancel\" value=\"" . FN_i18n("cancel") . "\"/>";
        echo "</form>";

        break;

    default :
        echo "<hr>";
        show_fntables($databasename, $path);
        break;
}

function show_tools()
{
    global $_FN;
    $opt = FN_GetParam("opt", $_GET, "flat");
    $op = FN_GetParam("op", $_GET, "flat");
    $tablename = FN_GetParam("t", $_GET, "flat");
    echo "-&gt;&nbsp;<b>$tablename</b>";
    echo "<div style=\"padding:3px;font-size:12px;width:600px;background-color:#000000;color:#ffffff;text-align:left;\">";
    $s = ($op == "edit") ? "style=\"color:#ffff00;font-weight:bold\"" : "style=\"color:#ffffff;\"";
    echo "<a $s href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op=edit&amp;t=$tablename\">" . FN_i18n("edit structure") . "</a>";

    $s = ($op == "editdata") ? "style=\"color:#ffff00;font-weight:bold\"" : "style=\"color:#ffffff;\"";
    echo " | <a $s href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op=editdata&amp;t=$tablename\">" . FN_i18n("contents") . "</a>";
    $s = ($op == "editxml") ? "style=\"color:#ffff00;font-weight:bold\"" : "style=\"color:#ffffff;\"";
    echo " | <a $s href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op=editxml&amp;t=$tablename\">" . FN_i18n("edit xml") . "</a>";
    echo "</div><br />";
}

/**
 * show tables
 * 
 */
function show_fntables($databasename, $path)
{
    global $_FN;
    $opt = FN_GetParam("opt", $_GET, "flat");
    $listatabelle = array();
    if (file_exists($path . "/" . $databasename))
    {
        if ($databasename !== "" && $path != "")
        {
            $handle = opendir($path . "/" . $databasename);
            while (false !== $file = readdir($handle))
                if (fn_erg('.php$', $file))
                    $listatabelle[] = fn_erg_replace('.php$', "", $file);
        }
    }
    fn_natsort($listatabelle);
    //-----lista delle tabelle ------
    echo "\n<table cellpadding=\"1\" cellspacing=\"1\" style=\"background-color:#{$_FN['table_border']}\" >\n<tbody>";
    echo "<th>";
    echo "<td  style=\"background-color:#{$_FN['table_background']}\"style=\"background-color:#{$_FN['table_background']}\" >" . FN_Translate("table name") . "</td>";
    echo "<td  style=\"background-color:#{$_FN['table_background']}\"style=\"background-color:#{$_FN['table_background']}\" ></td>";
    echo "<td  style=\"background-color:#{$_FN['table_background']}\"style=\"background-color:#{$_FN['table_background']}\" ></td>";
    echo "<td  style=\"background-color:#{$_FN['table_background']}\"style=\"background-color:#{$_FN['table_background']}\" ></td>";
    echo "<td  style=\"text-align:center;background-color:#{$_FN['table_background']}\"style=\"background-color:#{$_FN['table_background']}\" >" . FN_Translate("driver") . "</td>";
    echo "<td  style=\"text-align:center;background-color:#{$_FN['table_background']}\"style=\"background-color:#{$_FN['table_background']}\" >" . FN_Translate("action") . "</td>";

    echo "</th>";
    foreach ($listatabelle as $tabella)
    {
        echo "\n<tr>";
        echo "\n<td style=\"background-color:#{$_FN['table_background']}\" ><a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op=editdata&amp;t=$tabella\">" . $tabella . "</a></td>";
        echo "\n<td style=\"background-color:#{$_FN['table_background']}\" ><a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op=edit&amp;t=$tabella\">" . FN_i18n("structure") . "</a></td>";
        echo "\n<td style=\"background-color:#{$_FN['table_background']}\" ><a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op=editdata&amp;t=$tabella\">" . FN_i18n("contents") . "</a></td>";
        echo "\n<td style=\"background-color:#{$_FN['table_background']}\" ><a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op=editxml&amp;t=$tabella\">" . "XML structure" . "</a></td>";

        $driver = get_xml_single_element("driver", file_get_contents("{$_FN['datadir']}/fndatabase/$tabella.php"));
        if ($driver == "")
            $driver = $_FN['default_database_driver'];
        
        if (!empty($_FN['tables'][$tabella]['default_database_driver']))
        {
             $driver = $_FN['tables'][$tabella]['default_database_driver'];
        }
        if (!empty($_FN['tables'][$tabella]['driver']))
        {
             $driver = $_FN['tables'][$tabella]['driver'];
        }
        echo "\n<td style=\"text-align:center;background-color:#{$_FN['table_background']}\" >($driver)" . "</a></td>";
        if ($driver == "xml")
            echo "\n<td style=\"text-align:center;background-color:#{$_FN['table_background']}\" ><a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op=tomysql&amp;t=$tabella\">" . "$driver-&gt;mysql" . "</a></td>";
        else
            echo "<td style=\"text-align:center;background-color:#{$_FN['table_background']}\">-</td>";

        echo "\n</tr>";
    }
    echo "</tbody></table>";
    echo "<br /><img src=\"" . FN_FromTheme("images/add.png") . "\" alt=\"\"/>&nbsp;<a href=\"?opt=$opt&amp;op=new\">" . FN_i18n("new") . "</a>";
}

/**
 * Edit xmltable structure
 * @param string $tablename
 * @param string $path
 * @param datanase name $databasename
 */
function edit_table($tablename, $path, $databasename)
{
    global $_FN;
    $str = _XMLTABLESTRUCTURE;
    $opt = FN_GetParam("opt", $_GET, "flat");
    $op = FN_GetParam("op", $_GET, "flat");

    $tparams['xmltagroot'] = "tables";
    $tparams['xmlfieldname'] = "field";
    $tparams['datafile'] = "$path/$databasename/$tablename.php";
    //$table = new FieldFrm($path,array("xml"=>$str,"field"=>"field","tagroot"=>"tables","datafile"=>"$path/$databasename/$tablename.php"),".",$_FN['lang'],$_FN['languages']);
    $table = new FieldFrm($path, array("xml" => $str), ".", $_FN['lang'], $_FN['languages'], $tparams);
    //$table = new FieldFrm($path,array("xml"=>$str,"field"=>"field"),".",$_FN['lang'],$_FN['languages'],$tparams);
    $params ['path'] = $path;
    $params ['table'] = $table;
    $params ['bkheader'] = "#ffff00";
    $params ['bordercolor'] = "#ff0000";

    $params ['link'] = "op=edit&amp;opt=$opt&amp;t=$tablename";
    $params ['xmldatabase'] = "tables";

    FNCC_xmltablEeditor(false, $params);
    return;
}

/**
 * Edit xmltable structure
 * @param string $tablename
 * @param string $path
 * @param datanase name $databasename
 */
function new_table($tablename, $path, $databasename)
{
    global $_FN;
    $str = _XMLTABLESTRUCTURE;
    $opt = FN_GetParam("opt", $_GET, "flat");
    $op = FN_GetParam("op", $_GET, "flat");

    $table = new FieldFrm($path, array("xml" => $str, "field" => "field", "tagroot" => "tables", "datafile" => "$path/$databasename/$tablename.php"), ".", $_FN['lang'], $_FN['languages']);
    $table->setlayoutTags("<tr><td>", "</td>", "<td>", "</td></tr>", "<tr><td colspan=\"2\"><b>", "</b></td></tr>", "", "");
    $xmltablename = FN_GetParam('xmltablename', $_POST, "flat");
    $newvalues = array();
    $newvalues['primarykey'] = "1";
    if ($xmltablename != "")
    {
        $ok = true;
        if (file_exists("{$_FN['datadir']}/fndatabase/$xmltablename.php"))
        {
            echo "There is already a table with this name";
            $ok = false;
        }
        $newvalues = $table->getbypost();
        $newvalues['primarykey'] = "1";
        $errors = $table->Verify($newvalues, false);
        if (count($errors) > 0)
        {
            foreach ($errors as $field => $error)
            {
                $table->setlayoutTag($field, "<tr><td>", "</td>", "<td>", "<span style=\"background-color:#ffffff;color:red\">{$error['error']}</span></td></tr>", "<tr><td colspan=\"2\"><b>", "</b></td></tr>", "", "");
            }
            $ok = false;
        }
        if ($ok)
        {
            //dprint_r($newvalues);
            $singlefilename = false;
            if (isset($_POST['xmltablesinglefile']))
                $singlefilename = $xmltablename;
            echo XMETATable::createMetadbTable("fndatabase", $xmltablename, array(0 => $newvalues), $_FN['datadir'], $singlefilename);
            FN_JsRedirect("?opt=$opt&op=edit&t=$xmltablename");
            return;
        }
        //
    }


    echo "<form method=\"post\" action=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;op=$op&amp;opt=$opt\">";
    echo "<table>";
    echo "<tr><td colspan=\"2\" style=\"text-align:center;border:1px dotted\"><b>Table:</b></td></tr>";
    echo "<tr><td>Table name:</td><td><input type=\"text\" name=\"xmltablename\" value=\"$xmltablename\"></td></tr>";
    $ck = "";
    if (isset($_POST['xmltablesinglefile']))
        $ck = "checked=\"checked\"";
    echo "<tr><td>Store data in a single file:</td><td><input $ck type=\"checkbox\" name=\"xmltablesinglefile\" value=\"1\"></td></tr>";
    echo "<tr><td colspan=\"2\" style=\"text-align:center;border:1px dotted\"><b>Primary key :</b></td></tr>";
    $table->ShowInsertForm(true, $newvalues);
    echo "</table>";
    echo "<input type=\"submit\" value=\"" . FN_i18n("save") . "\" />";
    echo "&nbsp;<input type=\"button\" value=\"" . FN_i18n("cancel") . "\" onclick=\"window.location='?mod={$_FN['mod']}&opt=$opt'\" />";
    echo "</form>";
    return;
}

?>