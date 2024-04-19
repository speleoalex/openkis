<?php
/**
 * @package Flatnux_module_navigator
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
#<fnmodule>navigator</fnmodule>
defined('_FNEXEC') or die('Restricted access');
global $_FN;
//extra editor params 
$_FN['modparams'][$_FN['mod']]['editorparams']=isset($_FN['modparams'][$_FN['mod']]['editorparams']) ? $_FN['modparams'][$_FN['mod']]['editorparams'] : array();
require_once "modules/navigator/section_functions.php";
if (file_exists("sections/{$_FN['mod']}/custom_functions.php"))
    require_once "sections/{$_FN['mod']}/custom_functions.php";

$file=FN_GetParam("file",$_GET,"flat");



if ((false=== strpos($file,"..")) && $file!= "" && file_exists("sections/{$_FN['mod']}/$file"))
{
    include "sections/{$_FN['mod']}/$file";
}
else
{
    FNNAV_Init();
    $unirecid=FN_GetParam("id",$_GET,"html");
    $op=FN_GetParam("op",$_GET,"html");
    $downloadfile=FN_GetParam("downloadfile",$_GET);
    $mode=FN_GetParam("mode",$_GET);
//-------------------------------config---------------------------------------->
    $config=FN_LoadConfig();
    $tablename=$config['tables'];
    $recordsperpage=FN_GetParam("rpp",$_GET);
    if ($recordsperpage== "")
        $recordsperpage=$config['recordsperpage'];
    $config['search_orders']=explode(",",$config['search_orders']);
    $config['search_options']=explode(",",$config['search_options']);
    $config['navigate_groups']=explode(",",$config['navigate_groups']);
    $config['search_fields']=explode(",",$config['search_fields']);
    $config['search_partfields']=explode(",",$config['search_partfields']);
//-------------------------------config----------------------------------------<

    if ($mode== "go")
    {
        FNNAV_GoDownload($downloadfile);
        exit();
    }
    $html="";
    require_once $_FN['filesystempath']."/include/xmldb_frm_search.php";
    $Table=FN_XmlForm($tablename);

    $Search=new xmldb_searchform($_FN['database'],$tablename,$_FN['datadir'],$_FN['lang'],$_FN['languages'],false);
    $html.=$Search->HtmlSearchForm();
    if ($config['enable_permissions_each_records'])
    {
        if (!isset($Table->formvals['groupview']))
        {
            $field=array();
            $field['name']='groupview';
            $field['frm_i18n']='limits the display of the content in these groups';
            $field['foreignkey']='fn_groups';
            $field['fk_link_field']='groupname';
            $field['fk_show_field']='groupname';
            $field['frm_type']='multicheck';
            $field['type']='string';
            addxmltablefield($_FN['database'],$tablename,$field,$_FN['datadir']);
        }
        $Table->formvals['groupview']['frm_show']=1;
        if ($config['permissions_records_groups']!= "")
        {
            $allAllowedGroups=explode(",",$config['permissions_records_groups']);
            if (isset($Table->formvals['groupview']['options']))
            {
                foreach($Table->formvals['groupview']['options'] as $k=> $v)
                {
                    if (!in_array($v['value'],$allAllowedGroups))
                    {
                        unset($Table->formvals['groupview']['options'][$k]);
                    }
                }
            }
        }
    }
    else
    {
        if (isset($Table->formvals['groupview']))
        {
            $Table->formvals['groupview']['frm_show']=0;
        }
    }


    if ($config['enable_permissions_edit_each_records'])
    {
        if (!isset($Table->formvals['groupinsert']))
        {
            $field=array();
            $field['name']='groupinsert';
            $field['frm_i18n']='limits the edit of the content to these groups';
            $field['foreignkey']='fn_groups';
            $field['fk_link_field']='groupname';
            $field['fk_show_field']='groupname';
            $field['frm_type']='multicheck';
            $field['type']='string';
            $field['frm_setonlyadmin']='1';
            $field['frm_allowupdate']='onlyadmin';
            $field['type']='string';
            addxmltablefield($_FN['database'],$tablename,$field,$_FN['datadir']);
        }
        if (!empty($config['groupadmin']) && FN_UserInGroup($_FN['user'],$config['groupadmin']))
        {
            $Table->formvals['groupinsert']['frm_show']=1;
            unset($Table->formvals['groupinsert']['frm_setonlyadmin']);
            $Table->formvals['groupinsert']['frm_allowupdate']="";
        }

        if ($config['permissions_records_edit_groups']!= "")
        {
            $allAllowedGroups=explode(",",$config['permissions_records_edit_groups']);
            if (isset($Table->formvals['groupinsert']['options']))
            {
                foreach($Table->formvals['groupinsert']['options'] as $k=> $v)
                {
                    if (!in_array($v['value'],$allAllowedGroups))
                    {
                        unset($Table->formvals['groupinsert']['options'][$k]);
                    }
                }
            }
        }
    }
    else
    {
        if (isset($Table->formvals['groupinsert']))
        {
            $Table->formvals['groupinsert']['frm_show']=0;
        }
    }
//-----------------------------principale ------------------------------------->
    if (FNNAV_CanViewRecords())
    {
        switch($op)
        {
            case "history" :
                $shownavigatebar=true;
                if ($config['enable_history'])
                    $html.=FNNAV_ViewRecordHistory($unirecid,false,$shownavigatebar); // visualizza la pagina col record
                break;
            case "view" :
                $shownavigatebar=true;
                if (isset($_GET['embed']))
                    $shownavigatebar=false;
                if (isset($_GET['inner']))
                    $shownavigatebar=false;

                $html.=FNNAV_ViewRecordPage($unirecid,false,$shownavigatebar); // visualizza la pagina col record
                $html.=FNNAV_ViewComments($unirecid);

                break;
            case "viewcomments" :
                $html.=FNNAV_ViewComments($unirecid);
                break;
            case "writecomment" :
                $html.=FNNAV_WriteComment($unirecid);
                break;
            case "request" :
                $html.=FNNAV_Request($unirecid);
                break;
            case "edit" :
                $html.=FNNAV_EditRecordForm($unirecid,$Table); // form edita record
                if (file_exists("sections/{$_FN['mod']}/bottom_edit.php"))
                {
                    include ("sections/{$_FN['mod']}/bottom_edit.php");
                }

                break;
            case "new" :
                if (isset($_POST['xmldbsave']))
                {
                    $html.=FNNAV_InsertRecord($Table);
                    $html.=FNNAV_WriteSitemap();
                }
                else
                {
                    $html.=FNNAV_NewRecordForm($Table); //  form nuovo record
                }
                break;
            case "users" :
                $html.=FNNAV_UsersForm($unirecid); //  form nuovo record
                break;
            case "admingroups" :
                $html.=FNNAV_AdminPerm($unirecid); //  permessi records
                break;
            case "delcomment" :
                $html.=FNNAV_DelComment($unirecid); //  form nuovo record
                break;
            case "del" :
                $html.=FNNAV_DelRecordForm($unirecid); //  form nuovo record
                break;
            case "updaterecord" :
                if (count($_POST)== 0 || isset($_POST['__NOSAVE']) || !isset($_POST['xmldbsave']))
                {
                    $html.=FNNAV_EditRecordForm($unirecid,$Table);
                }
                else
                {
                    $html.=FNNAV_UpdateRecord($Table); // esegue aggiornamento record
                    FNNAV_GenerateRSS();
                }
                break;
            case "insertrecord" : // esegue inserimento record
                break;
            case "updatesitemap" :
                FNNAV_WriteSitemap();
                dprint_xml(file_get_contents("sitemap-$tablename.xml"));
                dprint_xml(file_get_contents("index-$tablename.html"));
                break;
            default :
                if (FN_IsAdmin() && isset($_GET["refresh_rss"]))
                {
                    FNNAV_GenerateRSS();
                }
                $html.=FN_HtmlContent("sections/{$_FN['mod']}");
                $html.=FNNAV_ViewGridForm(); //griglia con tutti i records
                if (FN_IsAdmin() && $config['permissions_records_groups'] && $config['enable_permissions_each_records'])
                {
                    $l=FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;op=admingroups");
                    $html.="<div><br /><a href=\"$l\">".FN_Translate("access control")."</a></div>";
                }

                break;
        }
    }
    else
    {
        $html=FN_i18n("you are not authorized to view the data");
    }
    echo $html;
//-----------------------------principale -------------------------------------<
}

/**
 *
 */
function FNNAV_ViewGridForm()
{
    global $_FN;
    //--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $search_fields=$config['search_fields']!= "" ? explode(",",$config['search_fields']) : array();
    $search_partfields=$config['search_fields']!= "" ? explode(",",$config['search_partfields']) : array();
    $search_orders=$config['search_orders']!= "" ? explode(",",$config['search_orders']) : array();
    $navigate_groups=$config['navigate_groups']!= "" ? explode(",",$config['navigate_groups']) : array();
    $search_options=$config['search_options']!= "" ? explode(",",$config['search_options']) : array();
    $search_min=$config['search_min']!= "" ? explode(",",$config['search_min']) : array();

    //--config--<
    $recordsperpage=FN_GetParam("rpp",$_GET);
    if ($recordsperpage== "")
        $recordsperpage=$config['recordsperpage'];
    if (file_exists("sections/{$_FN['mod']}/top.php"))
    {
        include ("sections/{$_FN['mod']}/top.php");
    }
    $p=FN_GetParam("p",$_GET);
    $op=FN_GetParam("op",$_GET);
    $navigate=FNNAV_NavigationMode();
    $results=FNNAV_GetResults($config);
    $tplvars['html_searchform']=FNNAV_SearchForm($search_orders,$tables,$search_options,$search_min,$search_fields,$search_partfields);
    ob_start();
    if (file_exists("sections/{$_FN['mod']}/grid_header.php"))
    {
        include("sections/{$_FN['mod']}/grid_header.php");
    }
    $tplvars['html_header']=ob_get_clean();
    $tplvars['html_categories']="";

    if ($config['navigate_groups']!= "")
    {
        $tplvars['html_categories'].="<div class=\"FNNAV_CategoryShow\" style=\"\">";
        if ($navigate== 0)
        {
            $linkpage=FNNAV_MakeLink(array("nav"=>1),"&amp;");
            $tplvars['html_categories'].="<a href=\"$linkpage\">".FN_Translate("browse by categories")."</a> "." <img alt=\"\" src=\"{$_FN['siteurl']}images/right.png\" />";
        }
        else
        {
            $linkpage=FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;nav=0","&amp;");
            $tplvars['html_categories'].="<a href=\"$linkpage\">".FN_Translate("hide categories")."</a>"." <img alt=\"\" src=\"{$_FN['siteurl']}images/right.png\" />";
        }
        $tplvars['html_categories'].="</div>";
    }

    //----------------barra si navigazione categorie--------------------------->
    $tplvars['html_filters']="";
    if ($navigate== 1)
    {
        $tplvars['html_filters']=FNNAV_Navigate($results,$navigate_groups);
    }
    //----------------barra si navigazione categorie---------------------------<
    //-----------------------pagina con i risultati---------------------------->
    $tplvars['html_export']="";
    if (!empty($config['enable_export']))
    {
        $tplvars['html_export']="<a href=\"".FNNAV_MakeLink(array("export"=>1),"&amp;")."\">".FN_Translate("export to csv")."</a>";
    }

    $tplvars['html_addnew']="";
    if (FNNAV_CanAddRecord())
    {
        $link=FNNAV_MakeLink(array("op"=>"new"),"&");
        $tplvars['html_addnew']="<div class=\"searchnewrecord\"><button class=\"button\" onclick=\"window.location='$link'\">".FN_Translate("add new")."</button></div>";
    }
    $tplvars['html_footer']="";
    if (file_exists("sections/{$_FN['mod']}/grid_footer.php"))
    {
        include("sections/{$_FN['mod']}/grid_footer.php");
        $tplvars['html_footer'].=ob_get_clean();
    }
    if (isset($_GET['debug']))
    {
        dprint_r(__FILE__." ".__LINE__." : ".FN_GetExecuteTimer());
    }

    $res=FNNAV_PrintList($results,$tplvars);
    if (isset($_GET['debug']))
    {
        dprint_r(__FILE__." ".__LINE__." : ".FN_GetExecuteTimer());
    }

    return $res;
    //-----------------------pagina con i risultati----------------------------<
}

/**
 *
 * @global type $_FN
 * @param type $results
 * @param type $groups 
 */
function FNNAV_Navigate($results,$groups)
{
    global $_FN;
    //--config-->
    $config=FN_LoadConfig();
    //--config--<
    $gresults=array();
    $html="";
    foreach($results as $tablename=> $res)
    {
        break;
    }
    if ($tablename== "")
    {
        return "";
    }
    $Table=FN_XmlForm($tablename);
    //----foreign key ---->
    $i=0;
    if (is_array($res))
        foreach($res as $item)
        {
            $data=$item;
            //$data = $Table->xmltable->GetRecordByPrimaryKey($item[$Table->xmltable->primarykey]);
            foreach($groups as $group)
            {
                if (isset($Table->formvals[$group]['fk_show_field']))
                {
                    $fs=$Table->formvals[$group]['fk_show_field'];
                }
                //echo "$group ";
                if ($group!= "" && isset($data[$group]))
                    $gresults[$group][$data[$group]]=isset($gresults[$group][$data[$group]]) ? $gresults[$group][$data[$group]] + 1 : 1;
                $i++;
            }
        }
//----------------------------------Template----------------------------------->
    $tplfile=file_exists("sections/{$_FN['mod']}/filters.tp.html") ? "sections/{$_FN['mod']}/filters.tp.html" : FN_FromTheme("modules/navigator/filters.tp.html",false);
    $tplbasepath=dirname($tplfile)."/";
    $tplvars=array();
//----------------------------------Template-----------------------------------<
    //dprint_r($gresults);
    $templateString=file_get_contents($tplfile);
    $tplfiltercategory=FN_TPL_GetHtmlPart("filtercategory",$templateString);
    $tplfilterclear=FN_TPL_GetHtmlPart("filterclear",$templateString);
    $tplfilteritem=FN_TPL_GetHtmlPart("filteritem",$templateString);
    $htmlFilterCategories="";
    foreach($gresults as $groupname=> $group)
    {
        $htmlFilterItems="";
        $tplfiltercategoryTmp=$tplfiltercategory;

        $fk=$Table->xmltable->fields[$groupname]->foreignkey;
        if (isset($Table->formvals[$groupname]['fk_link_field']))
            $pklink=$Table->formvals[$groupname]['fk_link_field'];
        else
            $pklink="";
        if ($fk!= "" && file_exists("{$_FN['datadir']}/{$_FN['database']}/$fk.php"))
        {

            $tablegroup=xmldb_table($_FN['database'],$fk,$_FN['datadir']);
        }
        $tplvars['filtertitle']=$Table->formvals[$groupname]['title'];
        $tplvars['urlremovefilter']="";
        if (isset($_GET["nv_$groupname"]))
        {
            $link=FNNAV_MakeLink(array("nv_$groupname"=>null,"page"=>1));
            $tplvars['urlremovefilter']=$link;
        }
        else
        {
            $tplfiltercategoryTmp=FN_TPL_ReplaceHtmlPart("clearfilter","",$tplfiltercategory);
        }
        $group2=array();
        foreach($group as $groupcontentsname=> $groupcontentsnums)
        {
            $tmp['total']=$groupcontentsnums;
            $tmp['name']=$groupcontentsname;
            $group2[]=$tmp;
        }
        $group2=FN_ArraySortByKey($group2,"name");
        foreach($group2 as $group)
        {
            $groupcontentsnums=$group['total'];
            $groupcontentsname=$group['name'];
            if ($groupcontentsname== "")
                $groupcontentstitle=FN_Translate("---");
            else
            {
                if ($pklink!= "")
                {
                    $restr=array($pklink=>$group['name']);
                    $t=$tablegroup->GetRecord($restr);
                    //dprint_r($restr);
                    //dprint_r("t=".$groupcontentstitle=$t[$Table->xmltable->fields[$groupname]->fk_show_field]);
                    //dprint_r($Table->xmltable->fields[$groupname]->fk_show_field);
                    $ttitles=$groupname;
                    if (isset($Table->xmltable->fields[$groupname]->fk_show_field))
                        $ttitles=explode(",",$Table->xmltable->fields[$groupname]->fk_show_field);
                    $groupcontentstitle="";
                    $sep="";
                    foreach($ttitles as $tt)
                    {
                        if (isset($t[$tt]) && $t[$tt]!= "")
                        {
                            $groupcontentstitle.=$sep.$t[$tt];
                            $sep=" &bull; ";
                        }
                    }
                    if ($groupcontentstitle== "")
                        $groupcontentstitle=$group['name'];
                }
                else
                    $groupcontentstitle=$group['name'];
            }
            $link=FNNAV_MakeLink(array("nv_$groupname"=>"$groupcontentsname","page"=>1));

            $tplvars['urlfilteritem']=$link;
            $tplvars['titleitem']=$groupcontentstitle;
            $tplvars['counteritem']=$groupcontentsnums;
            $htmlFilterItems.=FN_TPL_ApplyTplString($tplfilteritem,$tplvars,$tplbasepath);
        }
        $tplfiltercategoryTmp=FN_TPL_ReplaceHtmlPart("filteritems",$htmlFilterItems,$tplfiltercategoryTmp);
        $tplfiltercategoryTmp=FN_TPL_ApplyTplString($tplfiltercategoryTmp,$tplvars,$tplbasepath);
        $htmlFilterCategories.=$tplfiltercategoryTmp;
    }

    $tmpStr=FN_TPL_ApplyTplString($htmlFilterCategories,$tplvars,$tplbasepath);

    $html=FN_TPL_ReplaceHtmlPart("filtercategory",$tmpStr,$templateString);
    //----foreign key ----<
    return $html;
}

/**
 * 
 * @param $orders
 * @param $tables
 * @param $config['search_options']
 */
function FNNAV_SearchForm($orders,$tables,$search_options,$search_min,$search_fields,$search_partfields="")
{
    global $_FN;
    $q=FN_GetParam("q",$_GET);
    $order=FN_GetParam("order",$_GET);
    $desc=FN_GetParam("desc",$_GET);
    //--config-->
    $config=FN_LoadConfig();
    $config['search_fields']=explode(",",$config['search_fields']);
    $config['search_orders']=explode(",",$config['search_orders']);
    $config['search_min']=explode(",",$config['search_min']);
    $config['search_partfields']=explode(",",$config['search_partfields']);
    $config['search_options']=explode(",",$config['search_options']);
    //--config--<
    if ($order== "")
    {
        $order=$config['defaultorder'];
        if ($desc== "")
            $desc=1;
    }
    foreach($tables as $tablename)
    {
        $_tables[$tablename]=FN_XmlForm($tablename);
    }
    $htmlform="";
    $htmlform.="<form name=\"filter\" method=\"get\">";
    $htmlform.="<input type=\"hidden\"name=\"mod\" value=\"{$_FN['mod']}\" /> ";
    if (isset($_GET['nav']))
        $htmlform.="<input type=\"hidden\"name=\"nav\" value=\"1\" /> ";
    //------------------------------table rules-------------------------------->
    if ($config['table_rules'])
    {
        if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/{$config['table_rules']}.php"))
        {
            $xml='<?php exit(0);?>
<tables>
	<field>
		<name>rule</name>
		<primarykey>1</primarykey>
		<frm_show>0</frm_show>
		<extra>autoincrement</extra>
	</field>
	<field>
		<name>title</name>
		<frm_i18n>rule title</frm_i18n>
		<frm_multilanguages>auto</frm_multilanguages>
		<frm_show>1</frm_show>
	</field>	
	<field>
		<name>query</name>
		<frm_i18n>query</frm_i18n>
		<frm_type>text</frm_type>
		<frm_cols>80</frm_cols>
		<frm_rows>10</frm_rows>
		<frm_show>1</frm_show>
	</field>
	<field>
		<name>function</name>
		<frm_i18n>function</frm_i18n>
		<frm_show>1</frm_show>
	</field>
</tables>';
            FN_Write($xml,"{$_FN['datadir']}/{$_FN['database']}/{$config['table_rules']}.php");
        }

        $tablerules=FN_XmlForm($config['table_rules']);
        $htmlform.="<div class=\"navigatorformtitle\" ><span>".FN_Translate("shows only")." :</span></div>";
        $htmlform.="<select name = \"rule\" >";
        $htmlform.="<option value=\"\">---</option>";
        $rules=$tablerules->xmltable->GetRecords();
        foreach($rules as $rule)
        {
            //$rule = $tablerules->GetRecordTranslated($rule);
            $s=(!empty($_GET['rule']) && $_GET['rule']== $rule['rule']) ? "selected=\"selected\"" : "";
            $htmlform.="<option $s value=\"{$rule['rule']}\" >{$rule['title']}</option>";
        }
        $htmlform.="</select>";
    }
    //------------------------------table rules--------------------------------<
    //--------------------------------text------------------------------------->
    $htmlform.="<div class=\"navigatorformtitle\" ><span>".FN_Translate("text").":</span><input size=\"15\" name=\"q\" value=\"$q\"/></div>";
    //--------------------------------text-------------------------------------<
    //----------------------search exact phrase-------------------------------->
    foreach($search_fields as $min)
    {
        if (isset($_tables[$tablename]->formvals[$min]))
        {
            $minval=FN_GetParam("sfield_$min",$_GET);
            $htmlform.="<div class=\"navigatorformtitle\" ><span>{$_tables[$tablename]->formvals[$min]['title']} =:</span><input size=\"15\" name=\"sfield_$min\" value=\"$minval\"/>";
            if (isset($_tables[$tablename]->formvals[$min]['frm_suffix']))
                $htmlform.=$_tables[$tablename]->formvals[$min]['frm_suffix'];
            $htmlform.="</div>";
        }
    }
    //----------------------search exact phrase--------------------------------<
    //------------- looking for a part of the text ---------------------------->
    foreach($config['search_partfields'] as $partf)
    {
        if (isset($_tables[$tablename]->formvals[$partf]))
        {
            //dprint_r($_tables[$tablename]->formvals[$partf]);
            $pfval=FN_GetParam("spfield_$partf",$_GET);
            $htmlform.="<div class=\"navigatorformtitle\" ><span>{$_tables[$tablename]->formvals[$partf]['title']} :</span><input size=\"15\" name=\"spfield_$partf\" value=\"$pfval\"/>";
            if (isset($_tables[$tablename]->formvals[$partf]['frm_suffix']))
                $htmlform.=$_tables[$tablename]->formvals[$partf]['frm_suffix'];

            $htmlform.="</div>";
        }
    }
    //------------------ looking for a part of the text -----------------------<
    //-------------------------- minimun value -------------------------------->
    foreach($config['search_min'] as $min)
    {
        //dprint_r($_tables[$tablename]->formvals);
        if (isset($_tables[$tablename]->formvals[$min]))
        {
            $minval=FN_GetParam("min_$min",$_GET);
            $htmlform.="<div class=\"navigatorformtitle\" ><span>{$_tables[$tablename]->formvals[$min]['title']} &gt;:</span><input size=\"15\" name=\"min_$min\" value=\"$minval\"/>";
            if (isset($_tables[$tablename]->formvals[$min]['frm_suffix']))
                $htmlform.=$_tables[$tablename]->formvals[$min]['frm_suffix'];
            $htmlform.="</div>";
        }
    }
    //-------------------------- minimun value --------------------------------<
    //------------------------- search filters --------------------------------<
    foreach($config['search_options'] as $option)
    {
        foreach($tables as $tablename)
        {
            if (isset($_tables[$tablename]->formvals[$option]['options']))
            {
                $optiontitle=$_tables[$tablename]->formvals[$option]['title'];
                $htmlform.="<div class=\"navigatorformtitleCK\" ><span>$optiontitle:</span></div>";
                if (is_array($_tables[$tablename]->formvals[$option]['options']))
                    foreach($_tables[$tablename]->formvals[$option]['options'] as $c)
                    {
                        $title=$c['title'];
                        $value=$c['value'];
                        $getid="s_opt_{$option}_{$tablename}_{$c['value']}";
                        $ck="";
                        if (isset($_GET[$getid]))
                            $ck="checked=\"checked\"";
                        $htmlform.="<input name = \"$getid\" id=\"i_$getid\" type=\"checkbox\" $ck />&nbsp;<label for=\"i_$getid\">".$title."</label>";
                    }
            }
        }
    }
    //------------------------- search filters --------------------------------<
    //----------------------------- order by ---------------------------------->
    if (count($orders) > 0)
    {
        $htmlform.="<div class=\"navigatorformtitleOrderBy\"><span>".FN_Translate("order by").": </span><select name=\"order\">";
        foreach($orders as $o)
        {
            if (!isset($_tables[$tablename]->xmltable->fields[$o]))
                continue;
            $tt="frm_{$_FN['lang']}";
            if (isset($_tables[$tablename]->xmltable->fields[$o]->$tt))
                $no=$_tables[$tablename]->xmltable->fields[$o]->$tt;
            elseif (isset($_tables[$tablename]->xmltable->fields[$o]->frm_i18n))
            {
                $no=FN_Translate($_tables[$tablename]->xmltable->fields[$o]->frm_i18n);
            }
            else
                $no=$_tables[$tablename]->xmltable->fields[$o]->title;
            if ($order== $o)
                $s="selected=\"selected\"";
            else
                $s="";
            $htmlform.="\n<option $s value=\"$o\">$no</option>";
        }
        $htmlform.="</select>";
        $htmlform.="</div>";
        $ck=($desc== "") ? "" : "checked=\"checked\"";
        $htmlform.="<br />".FN_Translate("reverse order (from largest to smallest)")." <input onclick=\"filter.submit()\" name=\"desc\" value=\"1\" type = \"checkbox\" $ck />";
    }
    //----------------------------- order by ----------------------------------<
    $htmlform.="<button class=\"searchbutton\" type=\"submit\" >".FN_Translate("search")."</button>";
    // if (!empty($_GET['q']))
    $htmlform.=" <button class=\"searchbutton\" onclick=\"window.location='".FN_RewriteLink("index.php?mod={$_FN['mod']}","&")."';return false;\" >".FN_Translate("new search")."</button>";

    $htmlform.="</form>";
    return $htmlform;
}

/**
 * 
 * @param string $tablename
 * @param string $res
 */
function FNNAV_HtmlItem($tablename,$pk,$templateStringAll)
{
    global $_FN;
    //--config-->
    $config=FN_LoadConfig();
    $titles=explode(",",$config['titlefield']);
    //--config--<
    $tplvars=$_FN;

    $templateString=$templateStringAll;
    $templateString=FN_TPL_GetHtmlPart("item",$templateString);
    $templateStrRow=FN_TPL_GetHtmlPart("field",$templateString);

    $viewmode=FN_GetParam("viewmode",$_GET);
    if ($viewmode== "")
        $viewmode=$config['default_view_mode'];
    if ($viewmode!= "list")
    {
        $tplfile=file_exists("sections/{$_FN['mod']}/viewicons.tp.html") ? "sections/{$_FN['mod']}/viewicons.tp.html" : FN_FromTheme("modules/navigator/viewicons.tp.html",false);
    }
    else
    {
        $tplfile=file_exists("sections/{$_FN['mod']}/viewtable.tp.html") ? "sections/{$_FN['mod']}/viewtable.tp.html" : FN_FromTheme("modules/navigator/viewtable.tp.html",false);
    }
    $tplbasepath=dirname($tplfile)."/";

    $tablename=$tablename;
    $Table=FN_XmlForm($tablename);
    //$data=$Table->xmltable->GetRecordByPrimaryKey($pk);
    $data=$Table->GetRecordTranslatedByPrimarykey($pk,false);
    //-----image----------------------->
    $photo=isset($data[$config['image_titlefield']]) ? $Table->xmltable->getThumbPath($data,$config['image_titlefield']) : "";
    $photo_fullsize=isset($data[$config['image_titlefield']]) ? $_FN['siteurl'].$Table->xmltable->getFilePath($data,$config['image_titlefield']) : "";

    if ($photo!= "")
    {
//        $photo="{$_FN['datadir']}/fndatabase/{$tablename}/{$data[$Table->xmltable->primarykey]}/{$config['image_titlefield']}/{$data[$config['image_titlefield']]}";
    }
    elseif (file_exists("sections/{$_FN['mod']}/default.png"))
    {
        $photo="sections/{$_FN['mod']}/default.png";
    }
    else
        $photo="modules/navigator/default.png";
    if (empty($config['image_size']))
        $config['image_size']=200;
    if (file_exists("thumb.php"))
        $img="{$_FN['siteurl']}thumb.php?h={$config['image_size']}&w={$config['image_size']}&f=".$photo;
    else
        $img="$photo";
    $counteritems=0;
    //-----image-----------------------<
    $tplvars['item_urlview']=FNNAV_MakeLink(array("op"=>"view","id"=>$pk),"&amp;");
    $tplvars['item_urledit']=FNNAV_MakeLink(array("op"=>"edit","id"=>$pk),"&amp;");
    $tplvars['item_urldelete']=FNNAV_MakeLink(array("op"=>"del","id"=>$pk),"&amp;");
    $tplvars['item_urlimage']=$img;
    $tplvars['item_urlimage_fullsize']=$photo_fullsize;

    $dettlink=FNNAV_MakeLink(array("op"=>"view","id"=>$pk),"&amp;");

    //----title-------------------------------->
    $titlename="";
    foreach($titles as $titleitem)
        if (isset($data[$titleitem]))
        {
            if (!empty($Table->formvals[$titleitem]['fk_link_field']))
            {
                $titlename.="{$data[$titleitem]}&nbsp;";
            }
            else
            {
                $titlename.="{$data[$titleitem]}&nbsp;";
            }
        }
        else
        {
            foreach($data as $tv)
            {
                $titlename=$tv;
                break;
            }
            $titlename=isset($titlename[1]) ? $titlename[1] : "";
        }
    $tplvars['item_title']=FN_FixEncoding($titlename);
    //----title--------------------------------<
    //-----icons--------------------------------->
    $html_icons="";
    foreach($Table->formvals as $field)
    {
        if (isset($field['gridicononexists']) && $field['gridicononexists']!= "" && isset($data[$field['name']]) && $data[$field['name']]!= "")
        {

            $html_icons="\n<img border=\"0\" alt=\"".$field['name']."\" src=\"{$_FN['siteurl']}".$field['gridicononexists']."\" /> ";
        }
        if ($field['type']== "image" || $field['type']== "file")
        {
            $tplvars['url_'.$field['name']]=$_FN['siteurl'].$Table->xmltable->getFilePath($data,$field['name']);
        }
        $tplvars['value_'.$field['name']]=isset($data[$field['name']]) ? $data[$field['name']] : "";
    }
    $tplvars['html_icons']=$html_icons;
    //-----icons---------------------------------<
    //-------------------------------valori----------------------------------->
    $row=$data;
    $t=FN_XmlForm($tablename);


    $tplvars['countercomments']="0";
    if ($config['enablecomments'])
    {
        $tablelinks=FN_XmlForm($tablename."_comments");
        $numcommenti=$tablelinks->xmltable->GetNumRecords(array("unirecidrecord"=>$row[$t->xmltable->primarykey]));
        $tplvars['countercomments']="$numcommenti";
        $tplvars['url_viewcomments']="{$tplvars['item_urlview']}#___comments";
    }
    else
    {
        $templateString=FN_TPL_ReplaceHtmlPart("comments","",$templateString);
    }
    if ($config['enableranks'])
    {
        $htmlrank=FNNAV_HtmlRank($row[$t->xmltable->primarykey],true,$tablename);
        $tplvars['html_rank']=$htmlrank;
    }
    else
    {
        $templateString=FN_TPL_ReplaceHtmlPart("rank","",$templateString);
    }

    $colsuffix="1";
    $html="";
    foreach($Table->formvals as $fieldform_valuesk=> $field) // $fieldform_valuesk=> $fieldform_values
    {

        if (isset($field['frm_showinlist']) && $field['frm_showinlist']!= 0)
            if (isset($row[$field['name']]) && $row[$field['name']]!= "")
            {
                $counteritems++;
                $fieldform_values=$field;
                $multilanguage=false;
                $view_value="";

                //--------------get value from frm----------------------------->
                $languagesfield="";
                if (isset($fieldform_values['frm_multilanguages']) && $fieldform_values['frm_multilanguages']!= "")
                {
                    $multilanguage=true;
                    $languagesfield=explode(",",$fieldform_values['frm_multilanguages']);
                }
                $fieldform_values['name']=$fieldform_valuesk;
                $fieldform_values['messages']=$Table->messages;
                $fieldform_values['value']=XMLDB_FixEncoding($row[$fieldform_valuesk],$_FN['charset_page']);
                $fieldform_values['values']=$row;
                $fieldform_values['fieldform']=$Table;
                $fieldform_values['oldvalues']=$row;
                $fieldform_values['oldvalues_primarikey']=$pk;
                $fieldform_values['multilanguage']=$multilanguage;
                $fieldform_values['lang_user']=$_FN['lang'];
                $fieldform_values['lang']=$Table->lang;
                $fieldform_values['languagesfield']=$languagesfield;
                $fieldform_values['frm_help']=isset($fieldform_values['frm_help']) ? $fieldform_values['frm_help'] : "";
                $row[$field['name']]=html_entity_decode($row[$field['name']]);

                if (isset($fieldform_values['frm_functionview']) && $field['frm_functionview']!= "" && function_exists($field['frm_functionview']))
                {
                    eval("\$view_value = ".$field['frm_functionview'].'($data,$fieldform_valuesk);');
                    $showfield=false;
                }
                else
                {
                    $fname="xmldb_frm_view_".$field['frm_type'];
                    if (function_exists($fname))
                    {
                        $view_value=$fname($fieldform_values);
                    }
                    elseif (method_exists($Table->formclass[$fieldform_valuesk],"view"))
                    {
                        $view_value=$Table->formclass[$fieldform_valuesk]->view($fieldform_values);
                    }
                    else
                    {
                        $view_value=$data[$field['name']];
                    }
                }
                //--------------get value from frm-----------------------------<
                $html.=FN_TPL_ApplyTplString($templateStrRow,array("title"=>$field['title'],"value"=>$view_value,"fieldtype"=>$field['frm_type'],"fieldname"=>$fieldform_valuesk));
                $tplvars['viewvalue_'.$field['name']]=$view_value;
                $tplvars['title_'.$field['name']]=$field['title'];
            }
    }
    $templateString=FN_TPL_ReplaceHtmlPart("itemvalues","$html",$templateString);
    $tplvars['html_itemvalues']=$html;

    //-------------------------------valori-----------------------------------<
    //-------------------------------footer----------------------------------->

    if (FNNAV_IsAdminRecord($row,$tablename,$_FN['database']))
    {
        if (empty($config['enable_delete']))
        {
            $templateString=FN_TPL_ReplaceHtmlPart("delete","",$templateString);
        }
    }
    else
    {
        $templateString=FN_TPL_ReplaceHtmlPart("modify","",$templateString);
        $templateString=FN_TPL_ReplaceHtmlPart("delete","",$templateString);
    }


    if (file_exists("sections/{$_FN['mod']}/pdf.php"))
    {
        $tplvars['url_pdf']="{$_FN['siteurl']}pdf.php?mod={$_FN['mod']}&amp;id=$pk";
    }
    else
    {
        $templateString=FN_TPL_ReplaceHtmlPart("pdf","",$templateString);
    }
    $tplvars['counteritems']="$counteritems";
    $counteritems++;
    $tplvars['counteritems_1']="$counteritems";
    $counteritems++;
    $tplvars['counteritems_2']="$counteritems";
    $counteritems++;
    $tplvars['counteritems_3']="$counteritems";

    //-------------------------------footer-----------------------------------<
    return FN_TPL_ApplyTplString($templateString,$tplvars,$tplbasepath);
}

/**
 * 
 * @param unknown_type $results
 */
function FNNAV_PrintList($results,$tplvars)
{
    global $_FN;
    //--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
    //--config--<
    $viewmode=FN_GetParam("viewmode",$_GET);
    if ($viewmode== "")
        $viewmode=$config['default_view_mode'];
    $page=FN_GetParam("page",$_GET);
    $recordsperpage=FN_GetParam("rpp",$_GET);
    if ($recordsperpage== "")
        $recordsperpage=$config['recordsperpage'];
    //---template------>
    $viewmode=FN_GetParam("viewmode",$_GET);
    if ($viewmode== "")
        $viewmode=$config['default_view_mode'];
    if ($viewmode!= "list")
    {
        $tplfile=file_exists("sections/{$_FN['mod']}/viewicons.tp.html") ? "sections/{$_FN['mod']}/viewicons.tp.html" : FN_FromTheme("modules/navigator/viewicons.tp.html",false);
    }
    else
    {
        $tplfile=file_exists("sections/{$_FN['mod']}/viewtable.tp.html") ? "sections/{$_FN['mod']}/viewtable.tp.html" : FN_FromTheme("modules/navigator/viewtable.tp.html",false);
    }
    $templateString=file_get_contents($tplfile);
    $tplbasepath=dirname($tplfile)."/";
    //---template------<
    $tplvars['html_pages']="";
    $tplvars['html_rss']="";
    if (is_array($results))
    {
        $htmlItems="";
        $t=FN_XmlForm($tablename);
        foreach($results as $tablename=> $res)
        {
            if (is_array($res) && ($c=count($res)) > 0)
            {
                //---------------------calcolo paginazione -------------------->
                $htmlpages="";
                if ($page== "")
                    $page=1;
                $num_records=count($res);
                //echo "num_records=$num_records ";
                $numPages=ceil($num_records / $recordsperpage);
                $start=($page * $recordsperpage - $recordsperpage) + 1;
                $end=$start + $recordsperpage - 1;

                if ($end > $num_records)
                    $end=$num_records;
                //---------------------calcolo paginazione --------------------<
                //---------------------tabella paginazione -------------------->
                $tpl_vars=array();

                $tp_str_navpages="
<div class=\"FNNAV_pages\"><span>{i18n:results per page} </span>
<!-- pagination -->
<!-- recordpage -->
<a onclick=\"call_ajax('{linkpage_rpp}','pageresults');return false\" href=\"{linkpage_rpp}\">{txt_rpp}</a>&nbsp;
<!-- end recordpage -->
<!-- recordpageactive -->
<a class=\"nv_selected\" onclick=\"call_ajax('{linkpage_rpp}','pageresults');return false\" href=\"{linkpage_rpp}\">{txt_rpp}</a>&nbsp;
<!-- end recordpageactive -->
<!-- end pagination -->

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a class=\"disabled\" href=\"{linkpreviouspage}\">{i18n:Previous}</a>&nbsp;
<!-- pages -->
<!-- page -->
<a onclick=\"call_ajax('{link}','pageresults');return false\" href=\"{link}\">{txt_page}</a>&nbsp;
<!-- endpage -->
<!-- pageactive -->
<a class=\"nv_selected\" onclick=\"call_ajax('{link}','pageresults');return false\" href=\"{link}\">{txt_page}</a>
<!-- endpageactive -->
<!-- endpages -->
<a class=\"disabled\" href=\"{linknextpage}\">{i18n:Next}</a>

&nbsp;<a title=\"{txtview}\" onclick=\"call_ajax('{linkviewmode}','pageresults');return false\" href=\"{linkviewmode}\">
<img style=\"vertical-align:middle;border:0px\" src=\"{imageviewmode}\"></a> - {txt_rsults}</div>
";
                $tp_str_navpages_theme=FN_TPL_GetHtmlPart("nav pagination",$templateString);
                if ($tp_str_navpages_theme!= "")
                {
                    $tp_str_navpages=$tp_str_navpages_theme;
                    $templateString=str_replace($tp_str_navpages_theme,"{html_pages}",$templateString);
                }
                /* dprint_xml($tp_str_navpages);
                  dprint_xml($templateString);
                  die(); */
                //----------------------------pagination----------------------->
                $tp_str_pagination=FN_TPL_GetHtmlPart("pagination",$tp_str_navpages);
                $tp_str_recordpage=FN_TPL_GetHtmlPart("recordpage",$tp_str_pagination);
                $tp_str_recordpageactive=FN_TPL_GetHtmlPart("recordpageactive",$tp_str_pagination);
                $html_rpp="";

                //$htmlpages.="<div class=\"FNNAV_pages\">";
                //risultati per pagina ---->
                //$htmlpages.="<span>".FN_Translate("results per page").":</span>";
                $ii=1;
                for($rpp=$config['recordsperpage']; $rpp<= $config['recordsperpage'] * 3; $rpp+=$config['recordsperpage'])
                {

                    $linkpage=FNNAV_MakeLink(array("rpp"=>$rpp),"&amp;");
                    $cl="";
                    if ($rpp== $recordsperpage)
                        $cl="class=\"nv_selected\"";
                    // $htmlpages.="<a $cl onclick=\"call_ajax('$linkpage','pageresults');return false\" href=\"$linkpage\">$rpp</a>&nbsp;";

                    $tplvars['linkpage_rpp']=$linkpage;
                    $tplvars['txt_rpp']=$rpp;
                    $ii++;

                    if ($rpp== $recordsperpage)
                        $html_rpp.=FN_TPL_ApplyTplString($tp_str_recordpageactive,$tplvars);
                    else
                        $html_rpp.=FN_TPL_ApplyTplString($tp_str_recordpage,$tplvars);
                }


                if ($viewmode== "icon")
                {
                    $linkpage=FNNAV_MakeLink(array("viewmode"=>"list"),"&");
                    $tplvars['linkviewmode']=$linkpage;
                    $tplvars['imageviewmode']="{$_FN['siteurl']}modules/{$_FN['sectionvalues']['type']}/icons.png";

                    //$htmlpages.="<a title=\"".FN_Translate("icon view")."\" onclick=\"call_ajax('$linkpage','pageresults');return false\" href=\"$linkpage\" ><img style=\"vertical-align:middle;border:0px\" src=\"{$_FN['siteurl']}modules/{$_FN['sectionvalues']['type']}/icons.png\" /></a>";
                }
                else
                {
                    $linkpage=FNNAV_MakeLink(array("viewmode"=>"icon"),"&");
                    $tplvars['linkviewmode']=$linkpage;
                    $tplvars['imageviewmode']="{$_FN['siteurl']}modules/{$_FN['sectionvalues']['type']}/list.png";
                    //$htmlpages.="<a title=\"".FN_Translate("list view")."\" onclick=\"call_ajax('$linkpage','pageresults');return false\" href=\"$linkpage\" ><img style=\"vertical-align:middle;border:0px\" src=\"{$_FN['siteurl']}modules/{$_FN['sectionvalues']['type']}/list.png\" /></a>";
                }

                $tp_str_navpages=FN_TPL_ReplaceHtmlPart("pagination",$html_rpp,$tp_str_navpages);
                $tp_str_navpages=FN_TPL_ApplyTplString($tp_str_navpages,$tplvars);

                //----------------------------pagination-----------------------<
                //----------------------------pages---------------------------->
                $tp_str_pages=FN_TPL_GetHtmlPart("pages",$tp_str_navpages);
                $tp_str_page=FN_TPL_GetHtmlPart("page",$tp_str_pages);
                $tp_str_pageactive=FN_TPL_GetHtmlPart("pageactive",$tp_str_pages);
                $html_pages="";
                //$htmlpages.="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                //risultati per pagina ----<
                if ($page > 1)
                {
                    $linkpage=FNNAV_MakeLink(array("page"=>$page - 1,"addtocart"=>null),"&amp;");
                    $tplvars['linkpreviouspage']=$linkpage;
                }
                else
                {
                    $tplvars['linkpreviouspage']="#";

                    // $htmlpages.="<a class=\"disabled\" href=\"#\">".FN_Translate("previous")."</a>&nbsp;";
                }
                $max_pages=8;
                $startpage=$page;
                $scarto=$startpage / $max_pages;
                if ($scarto!= 0)
                {
                    $scarto=$startpage % $max_pages;
                    $startpage-=($scarto);
                    if ($page < $startpage)
                        $startpage=$page;
                    if ($startpage < 1)
                        $startpage=1;
                }
                $ii=$startpage;
                for($i=$startpage; $i<= $numPages; $i++)
                {
                    if ($ii>= $startpage + $max_pages)
                        break;
                    $linkpage=FNNAV_MakeLink(array("page"=>$i,"addtocart"=>null),"&");
                    $hclass="";
                    if ($page== $i)
                        $hclass="class=\"nv_selected\"";
                    // $htmlpages.="<a $hclass onclick=\"call_ajax('$linkpage','pageresults');return false\" href=\"$linkpage\">$i</a>&nbsp;";
                    $tplvars['link']=$linkpage;
                    $tplvars['txt_page']=$i;
                    if ($page== $i)
                        $html_pages.=FN_TPL_ApplyTplString($tp_str_pageactive,$tplvars);
                    else
                        $html_pages.=FN_TPL_ApplyTplString($tp_str_page,$tplvars);
                    $ii++;
                }

                if ($page < $numPages)
                {
                    $linkpage=FNNAV_MakeLink(array("page"=>$page + 1,"addtocart"=>null),"&amp;");
                    $tplvars['linknextpage']=$linkpage;
                }
                else
                {
                    $tplvars['linknextpage']="#";
                }

                $tplvars['txt_rsults']=FN_Translate("search results","Aa")."  $start - $end  ".FN_i18n("of")." $num_records"."";
//                $htmlpages.=" - ".FN_Translate("search results","Aa")."  $start - $end  ".FN_i18n("of")." $num_records"."";
//                $htmlpages.="</div>";
                //---------------------tabella paginazione --------------------<

                for($c=$start - 1; $c<= $end - 1 && isset($res[$c]); $c++)
                {
                    $htmlItems.=FNNAV_HtmlItem($tablename,$res[$c][$t->xmltable->primarykey],$templateString);
                }
                $tplvars['html_rss']="";

                if ($config['enable_rss'] && !empty($_FN['rss_link']))
                    $tplvars['html_rss']="<div><a href=\"{$_FN['rss_link']}\"><img src=\"{$_FN['siteurl']}modules/navigator/rss.png\"  alt=\"rss\"/></a></div>";
                $tp_str_navpages=FN_TPL_ReplaceHtmlPart("pages",$html_pages,$tp_str_navpages);
                $tp_str_navpages=FN_TPL_ApplyTplString($tp_str_navpages,$tplvars);


                $tplvars['html_pages']=$tp_str_navpages;
            }
            else
            {
                if ($viewmode== "icon")
                {
                    $linkpage=FNNAV_MakeLink(array("viewmode"=>"list"),"&");
                    $tplvars['linkviewmode']=$linkpage;
                    $tplvars['imageviewmode']="{$_FN['siteurl']}modules/{$_FN['sectionvalues']['type']}/icons.png";

                    //$htmlpages.="<a title=\"".FN_Translate("icon view")."\" onclick=\"call_ajax('$linkpage','pageresults');return false\" href=\"$linkpage\" ><img style=\"vertical-align:middle;border:0px\" src=\"{$_FN['siteurl']}modules/{$_FN['sectionvalues']['type']}/icons.png\" /></a>";
                }
                else
                {
                    $linkpage=FNNAV_MakeLink(array("viewmode"=>"icon"),"&");
                    $tplvars['linkviewmode']=$linkpage;
                    $tplvars['imageviewmode']="{$_FN['siteurl']}modules/{$_FN['sectionvalues']['type']}/list.png";
                    //$htmlpages.="<a title=\"".FN_Translate("list view")."\" onclick=\"call_ajax('$linkpage','pageresults');return false\" href=\"$linkpage\" ><img style=\"vertical-align:middle;border:0px\" src=\"{$_FN['siteurl']}modules/{$_FN['sectionvalues']['type']}/list.png\" /></a>";
                }
                $tplvars['linknextpage']="#";
                $tplvars['linkpreviouspage']="#";
                $tplvars['txt_rsults']="{$_FN['sectionvalues']['title']} : ".FN_i18n("no result");
                $tplvars['txt_page']="-";
                $htmlItems="<br /><br />";
            }
            break;
        }




        $templateString=FN_TPL_ReplaceHtmlPart("items",$htmlItems,$templateString,$tplbasepath);
        $html=FN_TPL_ApplyTplString($templateString,$tplvars,$tplbasepath);
        //  dprint_r(__FILE__." ".__LINE__." : ".FN_GetExecuteTimer());


        if (isset($_GET['debug']))
        {
            dprint_r(__FILE__." ".__LINE__." : ".FN_GetExecuteTimer());
        }
        return $html;
    }
    //echo "</div>";
}
?>

<script type="text/javascript">
    function call_ajax(url, div)
    {
        var xsreq;
        loading(div);
        if (window.XMLHttpRequest) {
            xsreq = new XMLHttpRequest();
        } else if (window.ActiveXObject) {
            xsreq = new ActiveXObject("Microsoft.XMLHTTP");
        }
        if (xsreq) {
            xsreq.onreadystatechange = function () {
                try {
                    ajDone(url, div, xsreq);
                } catch (e) {
                    alert(e)
                }
            };
            xsreq.open("GET", url, true);
            xsreq.send("");
        }
    }
    function ajDone(url, div, xsreq)
    {
        if (xsreq.readyState == 4)
        {
            //alert (div);
            try {
                var d = document.getElementsByTagName('body')[0];
                var olddiv = document.getElementById("__fnloading");
                d.removeChild(olddiv);
            } catch (e) {
                alert(e);
            }

            var output = xsreq.responseText;
            var el = document.createElement("div");
            el.innerHTML = output;
            var nodes = el.getElementsByTagName('div');
            for (var i = 0; i < nodes.length; i++) {
                if (nodes[i].id == div)
                {
                    document.getElementById(div).innerHTML = nodes[i].innerHTML;
                }
            }
            nodes = el.getElementsByTagName('span');
            for (var i = 0; i < nodes.length; i++) {
                if (nodes[i].id == div)
                {
                    document.getElementById(div).innerHTML = nodes[i].innerHTML;
                }
            }
        }
    }
    function getScrollY() {
        var scrOfX = 0, scrOfY = 0;
        if (typeof (window.pageYOffset) == 'number') {
            //Netscape compliant
            scrOfY = window.pageYOffset;
            scrOfX = window.pageXOffset;
        } else if (document.body && (document.body.scrollLeft || document.body.scrollTop)) {
            //DOM compliant
            scrOfY = document.body.scrollTop;
            scrOfX = document.body.scrollLeft;
        } else if (document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)) {
            //IE6 standards compliant mode
            scrOfY = document.documentElement.scrollTop;
            scrOfX = document.documentElement.scrollLeft;
        }
        return scrOfY;
        //return [ scrOfX, scrOfY ];
    }
    function getScrollX() {
        var scrOfX = 0, scrOfY = 0;
        if (typeof (window.pageYOffset) == 'number') {
            //Netscape compliant
            scrOfY = window.pageYOffset;
            scrOfX = window.pageXOffset;
        } else if (document.body && (document.body.scrollLeft || document.body.scrollTop)) {
            //DOM compliant
            scrOfY = document.body.scrollTop;
            scrOfX = document.body.scrollLeft;
        } else if (document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)) {
            //IE6 standards compliant mode
            scrOfY = document.documentElement.scrollTop;
            scrOfX = document.documentElement.scrollLeft;
        }
        return scrOfX;
    }
    function loading(_div)
    {
        try {
            var d = document.getElementsByTagName('body')[0];
            var olddiv = document.getElementById("__fnloading");
            d.removeChild(olddiv);
        } catch (e) {
        }



        var div;
        div = document.createElement('div');
        div.innerHTML = 'loading...';
        div.setAttribute('id', "__fnloading");
        oHeight = document.getElementsByTagName('body')[0].clientHeight + getScrollY();
        oWidth = document.getElementsByTagName('body')[0].clientWidth + getScrollX();
        oHeight = oHeight + "px";
        oWidth = oWidth + "px";
        try {
            div.style.backgroundColor = '#000000';
            div.style.color = '#ffffff';
            div.style.zIndex = '8';
            div.style.display = 'block';
            div.style.position = 'absolute';
            div.style.width = oWidth;
            div.style.height = "auto";
            div.style.top = '0px';
            div.style.left = '0px';
            div.style.textAlign = 'center';
            div.style.opacity = '0.5';
            div.style.filter = 'alpha(opacity=50)';
        } catch (e)
        {
        }
        div.innerHTML = "<div style=\"color:#ffffff;margin-top:" + getScrollY() + "px\" ><br />loading...<br /><br /><img  src='<?php echo "{$_FN['siteurl']}modules/navigator/"?>loading.gif' /><br /><br /></div>";
        document.getElementsByTagName('body')[0].appendChild(div);
    }
</script>
