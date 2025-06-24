<?php

/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');


//echo FN_HtmlSectionEditor();

global $_FN;
global $currentSectionType;
$currentSectionType=null;
$opt=FN_GetParam("opt",$_GET);
$modcont=FN_GetParam("edit",$_GET);
FN_InitTables();

$is_in_details=false;
if (!empty($_GET['pk___xdb_fn_sections']))
    $is_in_details=true;

echo "<br />";
FN_FixSections();

if ($modcont)
{
    $filename=basename($modcont);
    $filedir=dirname($modcont);
    $lang=str_replace(".html","",str_replace("section.","",$filename));

    $editor_params=false;
    if (file_exists(dirname($modcont)."/style.css"))
    {
        $editor_params['css_file']=dirname($modcont)."/style.css";
    }

    if (!file_exists($modcont))
    {
        if (isset($_GET['create']))
        {
            //---from default language ---------------------------------------->
            if (!file_exists($modcont))
            {
                if (isset($_GET['copy']))
                {
                    $text=file_get_contents("$filedir/section.{$_FN['lang_default']}.html");
                    FN_Write($text,$modcont);
                }
            }
            //---from default language ----------------------------------------<
            FN_EditContent($modcont,"{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&edit=$modcont","?fnapp=controlcenter&opt=$opt",$editor_params);
        }
        else
        {
            echo "$modcont ".FN_i18n("does not exist")."<br /><br />";
            echo FN_i18n("you want to create the file?");
            echo "<br /><br />";
            echo "<button onclick=\"window.location='{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt'\" >".FN_Translate("no")."</button> ";
            echo "<button onclick=\"window.location='{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&amp;edit=$modcont&amp;create'\" >".FN_Translate("yes")."</button> ";

            if ($lang!= $_FN['lang_default'] && file_exists("$filedir/section.{$_FN['lang_default']}.html"))
            {
                echo " <a href=\"{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&amp;edit=$modcont&amp;create&amp;copy\" >[".FN_Translate("copy contents from the default translation and edit it")."]</a> ";
            }
        }
    }
    else
    {
        $file_restore=FN_GetParam("restore",$_GET);
        if (!empty($file_restore) && file_exists($file_restore) && FN_GetFileExtension($file_restore)== "bak~")
        {
            $editor_params['force_value']=file_get_contents($file_restore);
            $editor_params['text_save']=FN_Translate("restore");
        }
        $_FN['editor_folder']=dirname($modcont);
        FN_EditContent($modcont,"{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&edit=$modcont","?fnapp=controlcenter&opt=$opt",$editor_params);
        $html="";
        //-----old versions---------------------------------------------------->
        {
            $html.="<h3>".FN_Translate("old versions").":</h3>";
            $html.="<table><tr><td><b>".FN_Translate("creation date")."</b></td><td><b>".FN_Translate("created by")."</b></td><td><b>".FN_Translate("delete date")."</b></td><td><b>".FN_Translate("overwritten by")."</b></td><td></td></tr>";
            $files=glob("$modcont.*");
            usort($files,"FN_UsortFilemtime");

            $bk_user="";
            foreach($files as $file)
            {
                $html.="<tr>";
                $attr=explode(".",basename($file));
                $date=DateTime::createFromFormat('YmdHis',$attr[count($attr) - 3]);
                $dateFile=$attr[count($attr) - 4];
                if (is_numeric($dateFile))
                {
                    $dateFile=FN_FormatDate($dateFile);
                }
                else
                {
                    $dateFile="unknown";
                }
                if (is_object($date))
                {
                    $bk_date=$date->getTimestamp();
                    $bk_date=FN_FormatDate($bk_date);
                }
                else
                {
                    $bk_date="";
                }
                $html.="<td>$dateFile</td><td>$bk_user</td><td>".$bk_date."</td>";
                $bk_user=$attr[count($attr) - 2];
                $html.="<td>$bk_user</td>";
                $html.="<td><button onclick=\"window.location='index.php?fnapp=controlcenter&mod={$_FN['mod']}&opt=$opt&edit=$modcont&restore=$file'\">".FN_Translate("restore")."</button></td>";
                $html.="</tr>";
            }

            $html.="<tr><td>".FN_FormatDate(filemtime($modcont))."</td><td>$bk_user</td><td>-</td><td>-</td><td><button onclick=\"window.location='index.php?fnapp=controlcenter&mod={$_FN['mod']}&opt=$opt&edit=$modcont'\">".FN_Translate("edit")."</button>"."</td></tr>";
            $html.="</table>";
            echo $html;
        }
        //-----old versions----------------------------------------------------<
    }
}
else
{

    $newsection=FN_GetParam("newsection",$_GET);

//-------sitemap--------------------------------------------------------------->
    if (!$newsection)
    {
        $img="<img title=\"\" alt=\"\" src=\"".FN_FromTheme("images/add.png")."\" />";
        if (empty($_GET["pk___xdb_fn_sections"]))
            echo "$img <a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;newsection=1\">".FN_i18n("create a new page")."</a>";
        $htmlgrid="";
//          dprint_r("qui 0a:" . FN_GetExecuteTimer());  //1 sec
        FNCC_UpdateSections();
//          dprint_r("qui 0b:" . FN_GetExecuteTimer()); //8sec
        $table=FNCC_XmlForm("fn_sections");
//($section="", $recursive=false, $onlyreadable = true, $hidden = false, $onlyenabled=true)
        $sections=FN_GetSections("",true,false,true,false,true);
        $sections=FN_ArraySortByKey($sections,"position");
        $sections=FN_ArraySortByKey($sections,"parent");
//---script---->
        $htmlgrid.="<br /><br />
<script language=\"javascript\">
var moveup = function (node)
{
        node=node.parentNode;
	var nodetomove = node;
	var list_child = new Array();
	nodetomove_level = getlevel (node);
	var i = 0;
	var tmp_nearnode = node.nextSibling;
	//lista nodi da spostare ----->
	while (tmp_nearnode != null && getlevel (node) < getlevel (tmp_nearnode) )
	{
		list_child[i] = tmp_nearnode;
		i++;
		tmp_nearnode = tmp_nearnode.nextSibling;
	}
	//lista nodi da spostare -----<
	var newnode;
	var parent;
	var nextnode;
	//dove spostare ----->
	var target  = findprev(nodetomove);
	if (target == null)
	{
		return;
	}
	//dove spostare -----<
	var id_node_main = nodetomove.id;
	movenode (nodetomove,target);
	target = document.getElementById(id_node_main);


	for (var j = 0 ;j <= i; j++)
	{
	try{
		movenode (list_child[j],target.nextSibling);
		target = document.getElementById(list_child[j].id);
	}catch (e){}
	}
	syncdiv('sectionsdiv');
}


var movedown = function (node)
{
        node=node.parentNode;

	var nodetomove = node;
	var list_child = new Array();
	nodetomove_level = getlevel (node);
	var i = 0;
	var tmp_nearnode = node.nextSibling;
	//lista nodi da spostare ----->
	while (tmp_nearnode != null && getlevel (node) < getlevel (tmp_nearnode) )
	{
		list_child[i] = tmp_nearnode;
		i++;
		tmp_nearnode = tmp_nearnode.nextSibling;
	}
	//lista nodi da spostare -----<
	var newnode;
	var parent;
	var nextnode;
	//dove spostare -----<
	var target  = findnext(nodetomove);
	if (target == null)
	{
	
		return;
	}
	//dove spostare ----->
	var id_node_main = nodetomove.id;
	movenode (nodetomove,target.nextSibling);
	target = document.getElementById(id_node_main);
	for (var j = 0 ;j <= i; j++)
	{
	try{
		movenode (list_child[j],target.nextSibling);
		target = document.getElementById(list_child[j].id);
		}catch(e){}
	}
	syncdiv('sectionsdiv');
}
var movenode = function(node,before)
{
	var tmpnode = node;
	var parent = before.parentNode;
	parent.insertBefore(tmpnode,before);
}

var getlevel = function (node)
{
	try{
	var span = document.getElementById('span_'+node.id);
	var px = parseInt(span.style.paddingLeft.replace('px',''));
	}catch (e){
	return 0;
	}
        
	return px;
}
//ritorna lultimo
var findnext = function (node)
{

	var start = getlevel(node)
	var nextnode = node.nextSibling;
	var retnode = nextnode;
	if (getlevel(nextnode) < start)
	{
		return null;
	}
	have_child = false;
	while (getlevel(nextnode) > start )
	{
		nextnode = nextnode.nextSibling;
		retnode =  nextnode;
		have_child = true;
	}
	if (have_child)
	{
		nextnode = retnode;
		
	}
	while (getlevel(nextnode.nextSibling) != start )
	{
		if (getlevel(nextnode.nextSibling) < start)
			break;
		nextnode = nextnode.nextSibling;
		retnode =  nextnode;
	}
	//alert (nextnode.id);
	return retnode;
}
//torna qulello successivo
var findprev = function (node)
{
	var start = getlevel(node)
	var prevnode = node.previousSibling;
	var retnode = prevnode;
	if (getlevel(prevnode) < start)
	{
		return null;
	}
	have_child = false;
	while (getlevel(prevnode) > start )
	{
		retnode =  prevnode;
		prevnode = prevnode.previousSibling;
		have_child = true;
	}
	
	if (have_child)
	{
		prevnode = retnode;
		//alert (nextnode.id);
	}
	
	while (getlevel(prevnode) != start )
	{
		prevnode = prevnode.previousSibling;
		retnode =  prevnode;
	}
	//alert ('cur = '+node.id+ ' prev = '+ prevnode.id);
	
	return prevnode;
}
var moveleft = function (node)
{
        node=node.parentNode;
	var span = document.getElementById('span_'+node.id);
	var m = parseInt(span.style.paddingLeft.replace('px',''));
	m-=30;
	if (m<0)
		m=0;
	span.style.paddingLeft = m+'px';
	syncdiv('sectionsdiv');
}
var moveright = function (node)
{
        node=node.parentNode;
	var span = document.getElementById('span_'+node.id);
	var m = parseInt(span.style.paddingLeft.replace('px',''));
	m+=30;
	span.style.paddingLeft = m+'px';
	syncdiv('sectionsdiv');
}
var syncdiv = function (id)
{
	var divitems = document.getElementById( id ).childNodes[0].childNodes[1].childNodes;
	var sep='';
	var str='';
	var lastdiv = null;
        //console.log (divitems)
	for (var i in divitems)
	{
		if (divitems[i].id && document.getElementById('span_'+divitems[i].id ))
		{
			var span = document.getElementById('span_'+divitems[i].id );
			var left = parseInt(  span.style.paddingLeft.replace('px',''))/30;
			str = str + sep + divitems[i].id+':'+left;
			var ck = '0';
			if ( document.getElementById('hide_'+divitems[i].id ).checked)
			{
				ck = '1';
				divitems[i].style.color='#bbbbbb';
				divitems[i].style.opacity='.5';
				divitems[i].style.fontStyle='italic';
			}
			else
			{
				divitems[i].style.color='black';
				divitems[i].style.opacity='';
				divitems[i].style.fontStyle='normal';
			}
			str +=':' + ck;
			sep=',';
			//------------------images----------------------------------------->
			try{
			var image = document.getElementById('imageup_'+divitems[i].id );
			if (i == 0)
			{
				image.style.opacity='.2';
			}
			else
			{
				image.style.opacity='1';
			}
			image = document.getElementById('imagedown_'+divitems[i].id );
			image.style.opacity='1';
			image = document.getElementById('imageleft_'+divitems[i].id );
			if (left==0)
			{
				image.style.opacity='.2';
			}
			else
			{
				image.style.opacity='1';
			}
			}
			catch(e){
			}
			//------------------images-----------------------------------------<
			lastdiv = divitems[i];
		}
	}
	
	document.getElementById('sectionstring').value=str;
	if (lastdiv)
	{
		var image = document.getElementById('imagedown_'+lastdiv.id );
		try{image.style.opacity='.2';}catch(e){}
	}
	
    //alert (str);
}
</script>
";
//---script----<



        $htmlgrid.="<form stile=\"clear:both;\" method=\"post\" op=\"?fnapp=controlcenter&opt={$opt}\">";
        $htmlgrid.="<div  id=\"sectionsdiv\" >";
        $tmp=false;
        $htmlgrid.="<table border=\"0\"><thead style=\"text-align:center;padding:5px;font-size:12px;line-height:20px;background-color:#f5f5f5;color:#000000;border:1px inset;width:auto;white-space: wrap\"><tr>";
        $htmlgrid.="<td style=\"border:1px solid #eaeaea;padding:3px\">".FN_Translate("hidden")."</td>";
        $htmlgrid.="<td style=\"border:1px solid #eaeaea;padding:3px\">".FN_Translate("page type")."</td>";
        $htmlgrid.="<td style=\"border:1px solid #eaeaea;padding:3px\">".FN_Translate("contents")."</td>";
        $htmlgrid.="<td style=\"border:1px solid #eaeaea;padding:3px\">".FN_Translate("move")."</td>";
        $htmlgrid.="<td style=\"border:1px solid #eaeaea;padding:3px\">".FN_Translate("site map")."</td>";
        $htmlgrid.="<td style=\"border:1px solid #eaeaea;padding:3px\">".FN_Translate("level")."</td>";
        $htmlgrid.="<td style=\"border:1px solid #eaeaea;padding:3px\">".FN_Translate("groups for viewing")."</td>";
        $htmlgrid.="<td style=\"border:1px solid #eaeaea;padding:3px\">".FN_Translate("groups for editing")."</td>";
        $htmlgrid.="<td style=\"border:1px solid #eaeaea;padding:3px\">".FN_Translate("publication start date")."</td>";
        $htmlgrid.="<td style=\"border:1px solid #eaeaea;padding:3px\">".FN_Translate("publication end date")."</td>";

        $htmlgrid.="</tr></thead><tbody style=\"padding:5px;font-size:12px;line-height:20px;background-color:#ffffff;color:#000000;border:1px inset;width:auto;white-space: nowrap\">";
        $htmlgrid.=FNCC_print_node("",$sections,$tmp);
        $htmlgrid.="<tr id=\"__last\" ><td colspan=\"8\"></td></tr>";
        $htmlgrid.="</tbody></table>";
        $htmlgrid.="</div>";
        $htmlgrid.="<input type=\"hidden\" id=\"sectionstring\" name=\"sectionstring\" size=\"200\"  value=\"\"/>";
        $htmlgrid.="<br /><p><button type=\"submit\">".FN_i18n("save")."</button></p>";
        $htmlgrid.="</form>";
        $htmlgrid.="
<script language=\"javascript\">
	syncdiv('sectionsdiv');
</script>

";

        $htmlgrid.="<br /><br />";
    }
//-------sitemap---------------------------------------------------------------<
//-------section grid---------------------------------------------------------->
    if (!$newsection)
    {
        // dprint_r("qui 1:" . FN_GetExecuteTimer());
        $table=FNCC_XmlForm("fn_sections");
        $table->formvals['id']['frm_show']=1;
        $table->formvals['position']['frm_show']=0;

        //------------rename section id---------------------------------------->
        $pk___xdb_fn_sections=FN_GetParam("pk___xdb_fn_sections",$_GET,"html");
        $id=FN_GetParam("id",$_POST,"html");
        $error=false;
        if ($id!= "" && $pk___xdb_fn_sections!= "" && $id!= $pk___xdb_fn_sections)
        {
            if (file_exists($_FN['src_application'] ."/sections/$id"))
            {
                echo "<div>";
                echo FN_Translate("a file already exists with this name");
                echo "</div>";
                $error=true;
            }
            else
            {
                if (false=== FN_Rename($_FN['src_application'] ."/sections/$pk___xdb_fn_sections",$_FN['src_application'] ."/sections/$id"))
                {
                    echo FN_Translate("error");
                    $error=true;
                }
                else
                {
                    //rename parents ----->
                    $sectionstorenameparent=FN_XMETADBQuery("SELECT * FROM fn_sections WHERE parent LIKE '$pk___xdb_fn_sections'");
                    if (is_array($sectionstorenameparent))
                    {
                        $tb=FN_XMDBTable("fn_sections");
                        foreach($sectionstorenameparent as $sectiontorenameparent)
                        {
                            $sectiontorenameparent['parent']=$id;
                            $nv=$table->UpdateRecord($sectiontorenameparent);
                            FNCC_UpdateDefaultXML($nv);
                        }
                    }
                    //rename parents -----<
                }
            }
        }
        //------------rename section id----------------------------------------<

        if (!$error)
        {
            //-------table editor---------------------------------------------->
            $params=array();
            $params['enablenew']=false;
            $params['table']=$table;
            $params['htmlgrid']=$htmlgrid."";

            $params['list_onupdate']=false;
            $params['textviewlist']="";
            $params['textcancel']="";
            $params['function_on_update']="FNCC_OnUpdateSection";
            $mod=FN_GetParam("pk___xdb_fn_sections",$_GET,"html");
            if (!empty($mod))
            {
                $sectionvalues=FN_GetSectionValues($mod);
                echo "<h2>".FN_Translate("page")."</h2><div style=\"padding:5px;border:1px solid #dadada\">";
            }
           
            FNCC_XmltableEditor("fn_sections",$params); //editor
            $sectionvalues=FN_GetSectionValues($mod,false);

            if (!empty($mod))
            {
                echo "</div>";
            }
            echo "<hr />";
            //-------table editor----------------------------------------------<
            //-------config editor--------------------------------------------->
            $block="";
            if (!empty($mod))
            {
                $sectiontype=$sectionvalues['type'];
                if ($currentSectionType!== null)
                    $sectiontype=$currentSectionType;
                if ($sectiontype!= "" && file_exists($_FN['src_finis'] ."/modules/$sectiontype/config.php") && file_exists($_FN['src_application']."/sections/{$mod}"))
                {
                    $t=FNCC_XmlForm("fn_sectionstypes");
                    $values=$t->GetRecordTranslatedByPrimarykey("$sectiontype");
                    $title=isset($values['title']) ? $values['title'] : $sectiontype;
                    echo "<h2>$title</h2>";
                    echo "<div style=\"padding:5px;border:1px solid #dadada\">";
                    $formaction="{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&amp;op___xdb_fn_sections=insnew&amp;pk___xdb_fn_sections=$mod";
                    $formexit="{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&amp;op___xdb_fn_sections=insnew&amp;pk___xdb_fn_sections=$mod";
                    //($file,$formaction = "",$exit = "",$allow = false,$write_to_file = false,$mod = "",$block = "")
                    echo FNCC_HtmlEditConfFile($_FN['src_finis']."/modules/$sectiontype/config.php",$formaction,"",false,false,$mod,$block);
                    echo "</div>";
                }

                echo "<br /><br /><button onclick=\"window.location='?fnapp=controlcenter&opt=$opt'\">".FN_Translate("site map")." &gt;&gt;</button>";
            }
            //-------config editor---------------------------------------------<
        }
        else
        {
            echo "<div><a href=\"?fnapp=controlcenter&opt=$opt\">".FN_Translate("site map")." &gt;&gt;</a></div>";
        }

        //------del section----------------------------------------------------->
        if (!empty($_GET['op___xdb_fn_sections']) && $_GET['op___xdb_fn_sections']== "del")
        {
            $sid=FN_GetParam("pk___xdb_fn_sections",$_GET);
            if ($sid!= "" && file_exists($_FN['src_application']."/sections/$sid"))
            {
                FN_RemoveDir($_FN['src_application']."/sections/$sid");
            }
            FN_JsRedirect("?fnapp=controlcenter&mod{$_FN['mod']}&amp;opt=$opt");
            FN_Log("section deleted:$sid");
            FN_OnSitemapChange();
        }
        //------del section-----------------------------------------------------<
    }
    else
    {
        if (!FN_IsWritable("sections"))
        {
            echo "sections ".FN_i18n("is read-only");
        }
        else
        {
            $forminsert=FNCC_XmlForm("fn_sections");
            $newvalues = isset($_POST['newsection']) ? $forminsert->GetByPost() : array();
            $newvalues =  (!is_array($newvalues)) ? array():$newvalues;
            $errors=array();
            $forminsert->formvals['id']['frm_show']="0";
            $forminsert->formvals['title']['frm_required']="1";
            $forminsert->formvals['parent']['frm_show']="0";
            $forminsert->formvals['position']['frm_show']="0";
            $sections=FN_GetSections("sections",true);
            if (!isset($newvalues['status']))
            {
                
                $newvalues['status']="1";

            }
            if (isset($_POST['newsection']))
            {
                if (isset($newvalues['title']))
                    $newvalues['id']=FN_MakeSectionId($newvalues['title']);
                $before_after=FN_GetParam("before_after",$_POST);
                $before_after_section=FN_GetParam("before_after_section",$_POST);
                $newvalues['parent']=isset($sections[$before_after_section]['parent']) ? $sections[$before_after_section]['parent'] : "";
                $newvalues['sectionpath']="sections";

                $errors=$forminsert->VerifyInsert($newvalues);
                if (count($errors)== 0)
                {
                    if ($newvalues['type']!= "" && file_exists("{$_FN['src_finis']}/modules/{$newvalues['type']}/section_template"))
                    {
                        $r=FN_CopyDir($_FN['src_finis']."/modules/{$newvalues['type']}/section_template",$_FN['src_application']."{$_FN['src_application']}/sections/{$newvalues['id']}",false);
                    }
                    else
                    {
                        $r=FN_MkDir($_FN['src_application']."/sections/{$newvalues['id']}");
                    }
                    if ($r)
                    {
                        //fix position --------->
                        $i=1;
                        $newsections=array();
                        foreach($sections as $k=> $section)
                        {
                            $newsections[$k]=$section;
                            $newsections[$k]['position']=$i;
                            if ($k== $before_after_section)
                            {
                                if ($before_after== "before")
                                {
                                    $newvalues['position']=$i;
                                    $i++;
                                    $newsections[$k]['position']=$i;
                                }
                                else
                                {
                                    $i++;
                                    $newvalues['position']=$i;
                                }
                            }
                            $i++;
                        }
                        if ($before_after== "inside")
                            $newvalues['parent']=$before_after_section;
                        foreach($newsections as $k=> $newsection)
                        {
                            if ($newsections[$k]['position']!= $sections[$k]['position'])
                            {
                                // dprint_r("update {$newsections[$k]['position']} != {$sections[$k]['position']}");
                                $nv=$forminsert->UpdateRecord(array("id"=>$newsections[$k]['id'],"position"=>$newsections[$k]['position']));
                                FNCC_UpdateDefaultXML($nv);
                            }
                        }
                        //fix position ---------<
                        $nv=$forminsert->InsertRecord($newvalues);
                        FNCC_UpdateDefaultXML($nv);
                        echo FN_i18n("the page has been created");
                        echo "<br />";
                        echo "<br /><a href=\"{$_FN['controlcenter']}?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt\">".FN_Translate("next")."</a> <img style=\"vertical-align:middle\" src=\"images/right.png\" alt=\"\"/>";
                        if ($newvalues['type']!= "" && file_exists("{$_FN['src_finis']}/modules/{$newvalues['type']}/config.php"))
                        {
                            echo "<br /><a href=\"{$_FN['controlcenter']}?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;op___xdb_fn_sections=insnew&amp;pk___xdb_fn_sections={$newvalues['id']}\">".FN_Translate("module options that is loaded in this page")."</a> <img style=\"vertical-align:middle\" src=\"images/right.png\" alt=\"\"/>";
                        }
                        FN_Log("created new section:{$newvalues['id']}");
                        FN_OnSitemapChange();
                        return;
                    }
                    else
                    {
                        echo FN_i18n("error");
                    }
                }
            }
            $html="";
            $html.="<form method=\"post\" action=\""."{$_FN['controlcenter']}?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;newsection=1"."\">ssss";
            $html.="<input type=\"hidden\" name=\"newsection\" value=\"1\"/>";
            $html.=FNADMIN_HtmlSectionsTree();
            $html.=$forminsert->HtmlShowInsertForm(false,$newvalues,$errors);
            $html.="<button type=\"submit\">".FN_i18n("save")."</button>";
            $html.="<button onclick=\"window.location='{$_FN['controlcenter']}?fnapp=controlcenter&mod={$_FN['mod']}&opt=$opt';return false;\" >".FN_i18n("cancel")."</button>";
            $html.="</form>";
            echo $html."";
        }
    }
//-------section grid----------------------------------------------------------<
}

/**
 *
 * @staticvar array $list
 * @staticvar int $level
 * @param string $parent
 * @param string $sections
 * @return array 
 */
function FNCC_print_node($parent,$sections,&$list)
{
    static $list=array();
    static $level=0;
    $html="";
    $level++;
    foreach($sections as $section)
    {
        if ($section['parent']== $parent)
        {
            if (in_array($section['id'],$list))
                return;
            $list[]=$section['id'];
            $html.=FNCC_print_section($section,$level);
            $tmp=false;
            $html.=FNCC_print_node($section['id'],$sections,$list);
        }
    }
    $level--;

    return $html;
}

/**
 *
 * @staticvar int $id
 * @param <type> $section
 * @param <type> $level
 */
function FNCC_print_section($section,$level)
{
    static $id=0;
    global $_FN;
    $opt=FN_GetParam("opt",$_GET);
    $html="";
    $id++;
    $left=($level - 1) * 30;
    $linkdelete="{$_FN['controlcenter']}?fnapp=controlcenter&page___xdb_fn_sections=1&order___xdb_fn_sections=id&op___xdb_fn_sections=del&pk___xdb_fn_sections=".$section['id'].'&opt='.$opt;
    $linkedit="{$_FN['controlcenter']}?fnapp=controlcenter&page___xdb_fn_sections=1&order___xdb_fn_sections=id&op___xdb_fn_sections=insnew&pk___xdb_fn_sections=".$section['id'].'&opt='.$opt;

    $bkcolor="#ffffff";
    $textdecoration="";
    $disabled="";
    if ($section['status']!= "1")
    {
        $disabled=" (".FN_Translate("disabled").")";
        $textdecoration="text-decoration: line-through";
        $bkcolor="#ffdddd";
    }
    $html.="<tr onclick=\"this.style.backgroundColor='#ffff00'\" onmouseover=\"this.style.backgroundColor='#ffffaa'\" onmouseout=\"if(true){this.style.backgroundColor='$bkcolor'}\" style=\"background-color:$bkcolor;border:0px solid #000000;line-height:18px;padding:0px;margin:0px;font-size:12px;height:18px\" id=\"{$section['id']}\">";

    $t="";
    if ($section['level']!= "" || $section['group_view']!= "")
    {
        if ($section['level']!== "")
            $t="&#128274;";
        if ($section['group_view']!== "")
            $t="&#128272;";
    }
    //-----hidden------>
    $ck=empty($section['hidden']) ? "" : "checked=checked";
    $html.="<td style=\"text-align:center\">
    <a href=\"#\" onclick=\"this.nextSibling.click();syncdiv('sectionsdiv');\"  />&#128161;</a><input style=\"display:none;vertical-align:middle\" title=\"".FN_i18n("hidden")."\" onchange=\"syncdiv('sectionsdiv');\" $ck type=\"checkbox\" id=\"hide_{$section['id']}\" />";
    $html.="</td>";
    //-----hidden------<

    $html.="<td>{$section['type']}</td>";

    //pagine --->
    $html.="<td>";
    $link="{$_FN['controlcenter']}?fnapp=controlcenter&mod={$section['id']}&edit=sections/{$section['id']}/section.php&opt=$opt";
    //   window.open("mioFile.htm","","width=" + w + ",height=" + h + ",top=" + t + ",left=" + l);
    // $html .= "<img style=\"cursor:pointer;vertical-align:middle\" alt=\"".FN_i18n("preview")."\" onclick=\"preview=window.open('".FN_RewriteLink("index.php?mod={$section['id']}")."','preview','top=10,left=10,scrollbars=yes');preview.focus();\" src=\"images/mime/web.png\" title=\"".FN_i18n("preview")."\" />";
    $border="border:1px solid #ffffff";
    if (file_exists($_FN['src_application']."/sections/{$section['id']}/section.php"))
        $border="border:1px solid #00ff00";
    FN_PathSite("images/delete.png");
    $html.="<img style=\"vertical-align:middle;cursor:pointer\" onclick=\"if(confirm ('".FN_i18n("you want to permanently delete this page?")."')){window.location='$linkdelete'} else {return false;}\" src=\"images/delete.png\" /></span>";
    $html.="&nbsp;&nbsp;<a style=\"height:22px;cursor:pointer;vertical-align:middle;$border\" alt=\"\" onclick=\"window.location='$link'\">PHP</a>";
    foreach($_FN['listlanguages'] as $l)
    {
        $border="border:1px solid #ffffff";
        if (file_exists($_FN['src_application']."/sections/{$section['id']}/section.$l.html"))
            $border="border:1px solid #00ff00";
        $link="{$_FN['controlcenter']}?fnapp=controlcenter&mod={$section['id']}&edit=sections/{$section['id']}/section.$l.html&opt=$opt";
        $html.=" <img style=\"cursor:pointer;vertical-align:middle;$border\" onclick=\"window.location='$link'\" src=\"images/flags/$l.png\" />";
    }
    //pagine ---<
    $html.="<img style=\"vertical-align:middle;cursor:pointer\" onclick=\"window.location='$linkedit';return false;\" src=\"images/configure.png\" /></td>";
//    $html .= "</span>";
    //up --->
    $html.="<td><a href=\"javascript:;\" onclick=\"moveup(this.parentNode);\">";
    $html.="<img id=\"imageup_{$section['id']}\" style=\"height:9px;width:13px;vertical-align:middle;border:0px;\" src=\"".FN_FromTheme("images/fn_up.png")."\" title=\"".FN_i18n("move up")."\" /></a>";
    //up ---<
    //down --->
    $html.="<a href=\"javascript:;\" onclick=\"movedown(this.parentNode);\">";
    $html.="<img id=\"imagedown_{$section['id']}\" style=\"height:9px;width:13px;vertical-align:middle;border:0px;\" src=\"".FN_FromTheme("images/fn_down.png")."\" title=\"".FN_i18n("move down")."\" /></a>";
    //down --->
    //left --->
    $html.="<a href=\"javascript:;\" onclick=\"moveleft(this.parentNode);\">";
    $html.="<img id=\"imageleft_{$section['id']}\" style=\"height:9px;width:13px;vertical-align:middle;border:0px;\" src=\"".FN_FromTheme("images/fn_left.png")."\" title=\"".FN_i18n("move left")."\" /></a>";
    //left ---<
    //right --->
    $html.="<a href=\"javascript:;\" onclick=\"moveright(this.parentNode);\">";
    $html.="<img id=\"imageright_{$section['id']}\"  style=\"height:9px;width:13px;vertical-align:middle;border:0px;\" src=\"".FN_FromTheme("images/fn_right.png")."\" title=\"".FN_i18n("move right")."\" /></a>";
    //right ---<
    $html.="</td><td>";

//icon --->
    $html.="<span id=\"span_{$section['id']}\"  style=\"background-position: bottom right;background-image:url(".FN_FinisPathToApplicationPath("controlcenter/sections/contents/sitemap/node.png").");background-repeat:no-repeat;padding-left:{$left}px\"></span>";
    $html.="<span>&#128193;$t&nbsp;<a style=\"$textdecoration\" title=\"".FN_Translate("preview")."\" href=\"#\" onclick=\"preview=window.open('".FN_RewriteLink("index.php?mod={$section['id']}")."','preview','top=10,left=10,scrollbars=yes');preview.focus();\" >{$section['title']}</a>$disabled";
    //icon ---<

    $html.="</td>";


    $html.="<td style=\"text-align:left;border-left:1px solid #dadada\">{$section['level']}</td>";
    $html.="<td style=\"text-align:left;border-left:1px solid #dadada\">{$section['group_view']}</td>";
    $html.="<td style=\"text-align:left;border-left:1px solid #dadada\">{$section['group_edit']}</td>";

    $end=$start="";
    if ($section['startdate'])
    {
        $start=FN_FormatDate($section['startdate']);
    }
    if ($section['enddate'])
    {
        $end=FN_FormatDate($section['enddate']);
    }
    $html.="<td style=\"text-align:left;border-left:1px solid #dadada\">{$start}</td>";
    $html.="<td style=\"text-align:left;border-left:1px solid #dadada\">{$end}</td>";
    $html.="</td>";
    $html.="</tr>";

    return $html;
}

/**
 *
 * @global array $_FN
 */
function FNCC_UpdateSections()
{
    global $_FN;
    $table=FNCC_XmlForm("fn_sections");
    $sectionstring=FN_GetParam("sectionstring",$_POST);
    $i=1;
    $sects=array();
    if ($sectionstring!= "")
    {
        $str_sectionsvalues=explode(",",$sectionstring);
        {
            foreach($str_sectionsvalues as $str_section)
            {
                $tmp=explode(":",$str_section);
                if (isset($tmp[2]))
                {
                    $sec['id']=$tmp[0];
                    $sec['position']=$i;
                    $sec['leveltree']=$tmp[1];
                    $sec['hidden']=$tmp[2];
                    $sects[]=$sec;
                    $i++;
                }
            }
        }
        $errors=false;
        $idtree=0;
        $curparent="";
        $tree[$idtree]="";
        $newvalues=array();
        for($i=0; isset($sects[$i]); $i++)
        {
            $oldvalues=FN_GetSectionValues($sects[$i]['id']);
            $newvalues['id']=$sects[$i]['id'];
            $newvalues['hidden']=$sects[$i]['hidden'];
            $newvalues['position']=$sects[$i]['position'] * 10;

            if ($i== 0)
            {
                $newvalues['parent']="";
            }
            else
            {
                if ($sects[$i]['leveltree'] > $sects[$i - 1]['leveltree'])
                {
                    $curparent=$newvalues['parent']=$sects[$i - 1]['id'];
                    $idtree=$idtree + ($sects[$i]['leveltree'] - $sects[$i - 1]['leveltree'] );
                    $tree[$idtree]=$curparent;
                }
                elseif ($sects[$i]['leveltree'] < $sects[$i - 1]['leveltree'])
                {
                    $idtree=$idtree - ($sects[$i - 1]['leveltree'] - $sects[$i]['leveltree']);
                    if (isset($tree[$idtree]))
                        $curparent=$newvalues['parent']=$tree[$idtree];
                }
                else
                {
                    $newvalues['parent']=$curparent;
                }
            }
            if ($newvalues['hidden']!= $oldvalues['hidden'] || $newvalues['position']!= $oldvalues['position'] || $newvalues['parent']!= $oldvalues['parent'])
            {
                //dprint_r($newvalues);
                if (!($nv=$table->xmltable->UpdateRecord($newvalues)))
                {
                    $errors.=1;
                }
                else
                {
                    FNCC_UpdateDefaultXML($nv);
                }
            }
        }


        if (!$errors)
        {

            FN_Alert(FN_Translate("the data were successfully updated"));
            FN_Log("sitemap changed");
            FN_OnSitemapChange();
        }
    }
}
/**
 * 
 * @param type $newvalues
 */
function FNCC_UpdateDefaultXML($newvalues)
{
    FN_UpdateDefaultXML($newvalues);
}

/**
 * 
 * @global type $currentSectionType
 * @param type $newvalues
 * @param type $oldvalues
 */
function FNCC_OnUpdateSection($newvalues,$oldvalues)
{
    global $currentSectionType;
    if (isset($oldvalues["type"]) && isset($newvalues["type"]))
    {
        $currentSectionType=$newvalues["type"];
        FN_OnSitemapChange();
    }
}
