<?php

/**
 * @package Flatnux_module_contacts
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
$csv=FN_GetParam("csv",$_GET,"html");
$opt=FN_GetParam("opt",$_GET,"html");
$type=FN_GetParam("type",$_GET,"html");
$archive=FN_GetParam("archive",$_GET,"html");
$unarchive=FN_GetParam("unarchive",$_GET,"html");
//--------------------init----------------------------------------------------->
$config=FN_LoadConfig();
$tablename=empty($config['tablename']) ? "contact_message" : $config['tablename'];
$t=FN_XmlTable("{$tablename}");
if (empty($t->fields['status']))
{
    $field['name']="status";
    $field['frm_i18n']="status";
    $field['frm_show']=0;
    addxmltablefield($t->databasename,$t->tablename,$field,$_FN['datadir']);
    $t=FN_XmlTable("{$tablename}");
}
//--------------------init-----------------------------------------------------<

$order=FN_GetParam("order___xdb_{$tablename}",$_GET,"html");
if ($order== "")
{
    $order="id";
}
if ($archive)
{
    $res=$t->UpdateRecord(array("id"=>$archive,"status"=>"archived"));
    //dprint_r($res);
}
if ($unarchive)
{
    $res=$t->UpdateRecord(array("id"=>$unarchive,"status"=>""));
    //
}
//dprint_r($res);

if ($csv)
{
    $records=$t->GetRecords(false,false,false,"id",false,/* $fields = */false);
    SaveToCSV($records,"contacts.csv");
    exit;
}

$fields=array();

foreach($t->fields as $field)
{
    if (isset($field->frm_show) && $field->frm_show== 0)
    {
        continue;
    }
    $fields[]=$field->name;
}
$params=array();
$fields=implode("|",$fields);
$fields.="|INS_archive()";
if ($type== "archived")
{
    $params['restr']=array("status"=>"archived");
}
if ($type== "")
{
    $params['restr']=array("status"=>"");
}
if ($type== "all")
{
    unset($params['restr']);
}
$params['enableview']=true;
$params['layout_view']="table";
$params['link']="type=$type&opt=$opt";
$params['fields']=$fields;

$params['textviewlist']=FN_Translate("list of messages");


echo "<div>";
echo "<a href=\"".("?mod={$_FN['mod']}&amp;opt=$opt&amp;type=archived")."\">".FN_Translate("archived messages")."</a>";
echo " | <a href=\"".("?mod={$_FN['mod']}&amp;opt=$opt&amp;type=all")."\">".FN_Translate("all messages")."</a>";
echo " | <a href=\"".("?mod={$_FN['mod']}&amp;opt=$opt")."\">".FN_Translate("new messages")."</a>";
echo "</div>";

FNCC_XmltableEditor("{$tablename}",$params);
$config=FN_LoadConfig();
$tablename=empty($config['tablename']) ? "contact_message" : $config['tablename'];

$link="controlcenter.php?opt=$opt&amp;csv=1";
echo "<hr /><p><button type=\"button\" onclick=\"window.location='$link'\"><img alt=\"download csv\" src=\"".FN_FromTheme("images/download.png")."\" /> ".FN_Translate("download list of messages")."</button></p>";
echo "<p><button type=\"button\" onclick=\"window.location='controlcenter.php?mod=&opt=utilities/xmldb_admin&op=edit&t=$tablename'\"><img alt=\"edit\" src=\"".FN_FromTheme("images/modify.png")."\" /> ".FN_Translate("edit message fields")."</button></p>";

/**
 *
 * @param type $data 
 */
function SaveToCSV($data,$filename)
{
    $sep=",";
    $str="";
    foreach($data as $row)
    {
        $arraycols=array();
        foreach($row as $cell)
        {
            $arraycols[]="\"".str_replace("\"","\"\"",$cell)."\"";
        }
        $str.=implode($sep,$arraycols)."\n";
    }
    FN_SaveFile($str,$filename,"application/vnd.ms-excel");
}

/**
 * 
 * @param type $id
 * @param type $table
 * @return string
 */
function INS_archive($id,$table)
{

    $config=FN_LoadConfig();
    $tablename=empty($config['tablename']) ? "contact_message" : $config['tablename'];

    $values=$table->xmltable->GetRecordByPrimarykey($id);
    $opt=FN_GetParam("opt",$_GET);
    $type=FN_GetParam("type",$_GET);
    if ($values['status']== "archived")
        $html="<a href=\"controlcenter.php?type=$type&opt=$opt&unarchive=$id&t=$tablename\">".FN_Translate("unarchive")."</a>";
    else
        $html="<a href=\"controlcenter.php?type=$type&opt=$opt&archive=$id&t=$tablename\">".FN_Translate("archive")."</a>";
    return $html;
}

?>