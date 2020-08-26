<?php

/**
 * @package Flatnux_module_object
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
global $_FN;
require_once 'modules/block_mediafiles/functions.php';

//$config=FN_LoadConfig("modules/block_slideshow/config.php");
$config=FN_LoadConfig();
$tablename=$config['tablename'];



MD_init();


$sigobjectmode=FN_GetParam("sigobject",$_GET,"html");
$mode=FN_GetParam("mode",$_GET,"html");
$opt=FN_GetParam("opt",$_GET,"html"); //if is controlcenter
$gopt=$sig="";
if ($opt!= "")
{
    $gopt="&amp;opt=$opt";
}
if ($sigobjectmode!= "")
{
    $sig="&amp;sigobject=1";
}
//---pub object---->
$pubobjectid=FN_GetParam("pubobjectid",$_POST,"html");
if ($pubobjectid!= "")
{
    $Tableobject=xmldb_frm("fndatabase",$tablename,$_FN['datadir'],$_FN['lang'],$_FN['languages']);
    $itemobject=$Tableobject->xmltable->GetRecordByPrimarykey($pubobjectid);
    if (!empty($itemobject[$Tableobject->xmltable->primarykey]))
    {
        $itemobject['status']=1;
        $itemobject=$Tableobject->xmltable->UpdateRecord($itemobject);
        if (empty($itemobject['status']))
        {
            $html.=FN_HtmlAlert(FN_Translate("error"));
        }
    }
}
//---pub object----<
//hide object---->
$pubobjectid=FN_GetParam("hideobjectid",$_POST,"html");
if ($pubobjectid!= "")
{
    $Tableobject=xmldb_frm("fndatabase",$tablename,$_FN['datadir'],$_FN['lang'],$_FN['languages']);
    $itemobject=$Tableobject->xmltable->GetRecordByPrimarykey($pubobjectid);
    if (!empty($itemobject[$Tableobject->xmltable->primarykey]))
    {
        $itemobject['status']="0";
        $itemobject=$Tableobject->xmltable->UpdateRecord($itemobject);
        if (!isset($itemobject['status']))
        {
            $html.=FN_HtmlAlert(FN_Translate("error"));
        }
    }
}
//hide object----<




$params=array();
$params['fields']="image|title|status|[".FN_Translate("enable")."/".FN_Translate("disable")."]PubPhoto()";
$params['enablenew']=true;

FNCC_XmltableEditor("$tablename",$params);

/**
 * 
 * @global type $_FN
 * @param type $idobject
 * @param type $Table
 * @return type
 */
function PubPhoto($idobject,$Table)
{
    global $_FN;
    $sigobjectmode=FN_GetParam("sigobject",$_GET,"html");
    $opt=FN_GetParam("opt",$_GET,"html");
    $itemobject=$Table->xmltable->GetRecordByPrimarykey($idobject);
    $sig="";
    $gopt="";
    if ($opt!= "")
    {
        $gopt="&amp;opt=$opt";
    }
    if ($sigobjectmode!= "")
    {
        $sig="&amp;sigobject=1";
    }
    $page=FN_GetParam("page___xdb_{$Table->tablename}",$_GET,"int");
    if ($page!= "")
    {
        $page="&amp;page___xdb_{$Table->tablename}=$page";
    }
    if ($itemobject['status']!= 1)
        return "<form action=\"".("?mode=edit$page$sig$gopt")."\" method=\"post\"><input name=\"pubobjectid\" value=\"$idobject\" type=\"hidden\"/><button type=\"submit\">".FN_Translate("enable")."</button></form>";
    else
        return "<form action=\"".("?mode=edit$page$sig$gopt")."\" method=\"post\"><input name=\"hideobjectid\" value=\"$idobject\" type=\"hidden\"/><button type=\"submit\">".FN_Translate("disable")."</button></form>";
}

?>