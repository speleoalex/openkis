<?php

/**
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2009
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * @package xmldb
 * 
 */
function XMLDB_editor_mergelink($link1,$link2)
{
    $vars=array();
    if (!preg_match('/^http(s?)\:\/\//i',$link2) && preg_match('/http(s?)\:\/\//i',$link2))
    {
        dprint_r("$link1\n$link2","","red");
       // die();
    }

    if (preg_match('/^http(s?)\:\/\//i',$link1) && preg_match('/^http(s?)\:\/\//i',$link2))
    {
        $link2=explode("?",$link2);
        $link2=isset($link2[1]) ? $link2[1] : "";
    }

    if ($link1== "")
    {
        if (preg_match('/^http(s?)\:\/\//i',$link2))
            return "$link2";
        else
        {
            if ($link2[0]== "?")
                return $link2;
            else
                return "?$link2";
        }
    }
    if ($link2== "")
    {
        if (preg_match('/^http(s?)\:\/\//i',$link1))
            return "$link1";
        else
            return "?$link1";
    }

    if (preg_match('/^http(s?)\:\/\//i',$link1) && !preg_match('/^http(s?)\:\/\//i',$link2))
    {
        $_sep="&amp;";
        if (false!== strstr($link1,"?"))
        {
            $_sep="&";
        }
        $_final_link=$link1.$_sep.$link2;
        //dprint_r($_final_link,"","magenta");
        return $_final_link;
    }
    if (!preg_match('/^http(s?)\:\/\//i',$link1) && preg_match('/^http(s?)\:\/\//i',$link2))
    {
        $_sep="&amp;";
        if (false!== strstr($link1,"?"))
        {
            $_sep="?";
        }
        $_final_link=$link2.$_sep.$link1;
        return $_final_link;
    }
    if (!preg_match('/^http(s?)\:\/\//i',$link1) && !preg_match('/^http(s?)\:\/\//i',$link2))
    {
        if ($link2[0]== "?")
        {
            $link2[0]="&";
        }
        if ($link1[0]== "?")
        {
            return $link1."&".$link2;
        }
        return "?$link1"."&".$link2;
    }
}

/**
 * edid xml tables
 * 
 * @param $tablename
 * @param $dbname
 * @param $params
 * @return unknown_type
 */
function XMLDB_editor($tablename,$dbname,$params=false)
{
    //--parametri ---->
    $bgcolorover=isset($params['bgcolorover']) ? $params['bgcolorover'] : "#ffff00";
    $defaultsParams=array(
        "charset_page"=>"UTF-8" //charset page
        ,"echo"=>true           //print output
        ,"table"=>$tablename
        ,"path"=>""
        ,"lang"=>"en"
        ,"languages"=>"en"
        ,"restr"=>false
        ,"filters"=>null
        ,"fields"=>false
        ,"functionsview"=>false
        ,"show_translations"=>false
        ,"bkrow"=>"#ffffff"
        ,"bkrow2"=>"#dfdfdf"
        ,"bgcolorover"=>"#ffff00"
        ,"bkheader"=>"#dfdfdf"
        ,"bordercolor"=>"#d8d8d8"
        ,"textcolor"=>"#000000"
        ,"linkcolor"=>"#000000"
        ,"link"=>""
        ,"link_listmode"=>null
        ,"enableview"=>false
        ,"enabledelete"=>true
        ,"enableedit"=>true
        ,"enablenew"=>true
        ,"defaultorder"=>false
        ,"defaultorderdesc"=>false
        ,"isadmin"=>null
        ,"functioninsert"=>""
        ,"oninsert"=>false  //html on insert
        ,"onupdate"=>false  //html on update 
        ,"function_on_insert"=>""
        ,"function_on_update"=>""
        ,"function_on_delete"=>""
        ,"htmlgrid"=>""
        ,"function_grid"=>"" // function grid
        ,"forcevalues"=>false       //force alwais
        ,"forcefieldvalues"=>""     //force alias forcevalues
        ,"forcevaluesinsert"=>""
        ,"forcevaluesupdate"=>""
        ,"forcenewvalues"=>""       //force if not set
        ,"forceupdatevalues"=>""    //force if not set
        ,"recordsperpage"=>""
        ,"requiredfieldsymbol"=>"*"
        ,"textrequiredfields"=>XMLDB_i18n("required fields")
        ,"textsave"=>XMLDB_i18n("save")
        ,"textnorecord"=>XMLDB_i18n("no records found")
        ,"textcancel"=>XMLDB_i18n("cancel")
        ,"textnew"=>"[ new ]"
        ,"textinsertok"=>XMLDB_i18n("the data were successfully inserted")
        ,"textupdateok"=>XMLDB_i18n("the data were successfully updated")
        ,"textinsertfail"=>XMLDB_i18n("error insert record")
        ,"textupdatefail"=>XMLDB_i18n("error update record")
        ,"textviewlist"=>"&lt;&lt;&nbsp;"."back".""
        ,"textpages"=>"Pages :"
        ,"textview"=>"view"
        ,"textmodify"=>XMLDB_i18n("modify")
        ,"textdelete"=>XMLDB_i18n("delete")
        ,"textexitwithoutsaving"=>XMLDB_i18n("want to exit without saving?")
        ,"list_onsave"=>1
        ,"list_oninsert"=>""
        ,"list_onupdate"=>""
        ,"html_template_grid"=>"
<script type=\"text/javascript\">
trh = function (over,elem){
    var old;
    if (over){
        this.old = elem.style.backgroundColor ;
        elem.style.backgroundColor='$bgcolorover';
    }
    else{
        elem.style.backgroundColor=this.old;
    }
}
</script>          

<!-- start grid -->
Pages : <!-- start pages --><!-- start page --><a href=\"{pagelink}\">{pagetitle}</a> <!-- end page --> <!-- start currentpage --><b><a href=\"{pagelink}\">{pagetitle}</a></b> <!-- end currentpage --> <!-- end pages -->
<!-- start table -->
<table style=\"border-color:{bordercolor}\">
    <!-- start gridheader -->
    <!-- start gridrow --><tr style=\"background-color:{bkheader}\">
    <!-- start gridfields --><!-- start gridfield --><td><a href=\"{link}\">{fieldvalue}</a>{arrow}</td><!-- end gridfield -->
    <!-- end gridfields -->
    <td colspan=\"{numactions}\">---</td></tr><!-- end gridrow -->
    <!-- end gridheader -->

    <!-- start gridbody -->
    <!-- start gridrow -->
    <tr style=\"background-color:{bkrow}\" onmouseover=\"trh(1,this)\" onmouseout=\"trh(0,this)\"   >
    <!-- start gridfields --><!-- start gridfield --><td>{fieldvalue}</td><!-- end gridfield --><!-- end gridfields --></tr>
    <!-- end gridrow -->
    <!-- end gridbody -->
</table>
<!-- end table -->
<!-- end grid -->
<!-- start insertnew -->
 <a href=\"{urlnewrecord}\">{textnew}</a>
<!-- end insertnew -->
            
            "
        ,"maxrows"=>false
        ,'max_cell_text_lenght'=>40
    );
    $function_update=isset($params['function_update']) ? $params['function_update'] : false;
    $function_insert=isset($params['function_insert']) ? $params['function_insert'] : false;

    $textcancel=isset($params['textcancel']) ? $params['textcancel'] : "Cancel";
    $textnew=isset($params['textnew']) ? $params['textnew'] : "[ new ]";
    $textinsertok=isset($params['textinsertok']) ? $params['textinsertok'] : "the data were successfully inserted";
    $textupdateok=isset($params['textupdateok']) ? $params['textupdateok'] : "the data were successfully updated";
    $textinsertfail=isset($params['textinsertfail']) ? $params['textinsertfail'] : "error insert record";
    $textupdatefail=isset($params['textupdatefail']) ? $params['textupdatefail'] : "error update record";
    $textviewlist=isset($params['textviewlist']) ? $params['textviewlist'] : "&lt;&lt;&nbsp;"."back"."";
    $textpages=isset($params['textpages']) ? $params['textpages'] : "Pages :";
    $textview=isset($params['textview']) ? $params['textview'] : "view";
    $textmodify=isset($params['textmodify']) ? $params['textmodify'] : "modify";
    $textdelete=isset($params['textdelete']) ? $params['textdelete'] : "delete";
    $textexitwithoutsaving=isset($params['textexitwithoutsaving']) ? $params['textexitwithoutsaving'] : "want to exit without saving?";
    $list_oninsert=$list_onupdate=$list_onsave=isset($params['list_onsave']) ? $params['list_onsave'] : 1;
    $list_oninsert=isset($params['list_oninsert']) ? $params['list_oninsert'] : $list_onsave;
    $list_onupdate=isset($params['list_onupdate']) ? $params['list_onupdate'] : $list_onsave;



    $html="";
    $tname=$tablename;
    if (is_array($tablename))
    {
        if (isset($tablename['tablename']))
        {
            $tname=$tablename['tablename'];
        }
        else
        {
            die("tablename is not set");
        }
    }
    elseif (is_object($tablename))
    {
        $params['table']=$tablename;
        $tablename=$tname=$tablename->tablename;
    }
    foreach($defaultsParams as $key=> $param)
    {
        $params[$key]=isset($params[$key]) ? $params[$key] : $defaultsParams[$key];
    }


    $errors=array();
    $postgetkey="__xdb_{$tname}"; //identificatico della tabella

    $path=isset($params['path']) ? $params['path'] : "";
    $lang=isset($params['lang']) ? $params['lang'] : "en";
    $languages=isset($params['languages']) ? $params['languages'] : "en";
    $restr=isset($params['restr']) ? $params['restr'] : false;
    $restr=isset($params['filters']) ? $params['filters'] : $restr;
    $table=isset($params['table']) ? $params['table'] : false;
    $fields=isset($params['fields']) ? $params['fields'] : false;
    $functionsview=isset($params['functionsview']) ? $params['functionsview'] : false;
    $show_translations=isset($params['show_translations']) ? $params['show_translations'] : false;
    $bgcolor=isset($params['bkrow']) ? $params['bkrow'] : "#ffffff";
    $bgcolor2=isset($params['bkrow2']) ? $params['bkrow2'] : "#ffffff";
    $bgcolorover=isset($params['bgcolorover']) ? $params['bgcolorover'] : "#ffff00";
    $backgroundcolorheader=isset($params['bkheader']) ? $params['bkheader'] : "#dfdfdf";
    $bordercolor=isset($params['bordercolor']) ? $params['bordercolor'] : "#d8d8d8";
    $textcolor=isset($params['textcolor']) ? $params['textcolor'] : "#000000";
    $linkcolor=isset($params['linkcolor']) ? $params['linkcolor'] : "#000000";
    $flink=isset($params['link']) ? $params['link'] : "";
    $flink_listmode=isset($params['link_listmode']) ? $params['link_listmode'] : $params['link'];

    $enableview=isset($params['enableview']) ? $params['enableview'] : false;
    $enabledelete=isset($params['enabledelete']) ? $params['enabledelete'] : true;
    $enableedit=isset($params['enableedit']) ? $params['enableedit'] : true;
    $enablenew=isset($params['enablenew']) ? $params['enablenew'] : true;
    $reverse=isset($params['defaultorderdesc']) ? $params['defaultorderdesc'] : false;
    $maxpages=isset($params['maxpages']) ? $params['maxpages'] : 100;

    if (!isset($params['isadmin']))
        $params['isadmin']=XMLDBEDITOR_IsAdmin();
    //function in insert form
    $functioninsert=isset($params['functioninsert']) ? $params['functioninsert'] : "";
    //html in insert form
    $oninsert=isset($params['oninsert']) ? $params['oninsert'] : "";
    $onupdate=isset($params['onupdate']) ? $params['onupdate'] : "";
    //function on insert form
    $function_on_insert=isset($params['function_on_insert']) ? $params['function_on_insert'] : "";
    $function_on_update=isset($params['function_on_update']) ? $params['function_on_update'] : "";
    $function_on_delete=isset($params['function_on_delete']) ? $params['function_on_delete'] : "";

    $htmlgrid=isset($params['htmlgrid']) ? $params['htmlgrid'] : "";
    //force values
    $forcevalues=isset($params['forcevalues']) ? $params['forcevalues'] : false;
    $forcevalues=!empty($params['forcefieldvalues']) ? $params['forcefieldvalues'] : $forcevalues;
    //force in insert
    $forcevaluesinsert=isset($params['forcevaluesinsert']) ? $params['forcevaluesinsert'] : false;
    //force in update
    $forcevaluesupdate=isset($params['forcevaluesupdate']) ? $params['forcevaluesupdate'] : false;
    //force in form insert
    $forcenewvalues=isset($params['forcenewvalues']) ? $params['forcenewvalues'] : true;
    //force in form update
    $forceupdatevalues=isset($params['forceupdatevalues']) ? $params['forceupdatevalues'] : true;
    $recordsperpage=isset($params['recordsperpage']) ? $params['recordsperpage'] : false;
    $requiredfieldsymbol=$params['requiredfieldsymbol'];
    $textrequiredfields=$params['textrequiredfields'];

    $textsave=isset($params['textsave']) ? $params['textsave'] : "save";
    $textnorecord=isset($params['textnorecord']) ? $params['textnorecord'] : "no records found";
    $optionsedit=array();
    if ($enableview)
        $optionsedit[]="view";
    if ($enableedit)
        $optionsedit[]="edit";
    if ($enabledelete)
        $optionsedit[]="delete";
    $textcancel=isset($params['textcancel']) ? $params['textcancel'] : "Cancel";
    $textnew=isset($params['textnew']) ? $params['textnew'] : "[ new ]";
    $textinsertok=isset($params['textinsertok']) ? $params['textinsertok'] : "the data were successfully inserted";
    $textupdateok=isset($params['textupdateok']) ? $params['textupdateok'] : "the data were successfully updated";
    $textinsertfail=isset($params['textinsertfail']) ? $params['textinsertfail'] : "error insert record";
    $textupdatefail=isset($params['textupdatefail']) ? $params['textupdatefail'] : "error update record";
    $textviewlist=isset($params['textviewlist']) ? $params['textviewlist'] : "&lt;&lt;&nbsp;"."back"."";
    $textpages=isset($params['textpages']) ? $params['textpages'] : "Pages :";
    $textview=isset($params['textview']) ? $params['textview'] : "view";
    $textmodify=isset($params['textmodify']) ? $params['textmodify'] : "modify";
    $textdelete=isset($params['textdelete']) ? $params['textdelete'] : "delete";
    $textexitwithoutsaving=isset($params['textexitwithoutsaving']) ? $params['textexitwithoutsaving'] : "want to exit without saving?";

    $list_oninsert=$list_onupdate=$list_onsave=isset($params['list_onsave']) ? $params['list_onsave'] : 1;
    $list_oninsert=isset($params['list_oninsert']) ? $params['list_oninsert'] : $list_onsave;
    $list_onupdate=isset($params['list_onupdate']) ? $params['list_onupdate'] : $list_onsave;

    $params['maxrows']=isset($params['maxrows']) ? $params['maxrows'] : false;


    //--parametri ----<
    //-----variabili da get --------->
    $opmod=isset($_GET["op_$postgetkey"]) ? htmlspecialchars($_GET["op_$postgetkey"]) : "";
    $pk=isset($_GET["pk_$postgetkey"]) ? ($_GET["pk_$postgetkey"]) : "";
    $page=isset($_GET["page_$postgetkey"]) ? htmlspecialchars($_GET["page_$postgetkey"]) : "";
    $order=isset($_GET["order_$postgetkey"]) ? htmlspecialchars($_GET["order_$postgetkey"]) : "";
    if ($order!= "")
    {
        $reverse=isset($_GET["desc_$postgetkey"]) ? htmlspecialchars($_GET["desc_$postgetkey"]) : "";
    }
    //-----variabili da get ---------<
    $tlink="";
    $mlink="page_$postgetkey=$page";
    if (is_array($forcevalues))
        foreach($forcevalues as $key=> $value)
        {
            $tlink.="&amp;".$key."=".$value;
            $mlink.="&amp;".$key."=".$value;
        }



    //    dprint_r($forcenewvalues);
    if ($flink!= "")
    {
        $tlink=XMLDB_editor_mergelink($tlink,$flink);
        $mlink=XMLDB_editor_mergelink($mlink,$flink);
    }


    //-----tabella --------->
    if (!is_object($table))
    {
        $paramsFRM=array();
        $paramsFRM['siteurl']=isset($params['siteurl']) ? $params['siteurl'] : "";
        if (isset($params['charset_storage']))
            $paramsFRM['charset_storage']=$params['charset_storage'];
        else
            $paramsFRM['charset_storage']="UTF-8";
        $table=new FieldFrm("$dbname",$tablename,$path,$lang,$languages,$paramsFRM);
    }
    $siteurl="";
    if (isset($params['siteurl']))
    {
        $siteurl=$params['siteurl'];
    }
    $table->charset_page=$param['charset_page'];
    if (isset($params['layout']))
    {
        $table->SetLayout($params['layout']);
    }
    if (!empty($params['layout_template']))
    {
        $table->SetlayoutTemplate($params['layout_template']);
    }

    //filtro sulle foreignkey---->
    if (!empty($params['restrfk']))
    {
        if (is_array($params['restrfk']))
        {
            foreach($params['restrfk'] as $k=> $v)
            {
                if (isset($table->formvals[$k]))
                {
                    $table->formvals[$k]['fk_filter_field']=$v;
                }
            }
        }
        else
        {
            foreach($table->formvals as $k=> $v)
            {
                if (!empty($table->formvals[$k]['foreignkey']))
                {
                    $table->formvals[$k]['fk_filter_field']=$params['restrfk'];
                }
            }
        }
    }
    //filtro sulle foreignkey----<



    $scriptOnExit="
<script type=\"text/javascript\">
function set_changed()
{
try{
    document.getElementById('exit_$postgetkey').setAttribute('onclick',
    'if(confirm (\"".addslashes($textexitwithoutsaving)."\")){window.location=\"".str_replace("&amp;","&",$tlink)."\";}');
    var allLinks = document.getElementsByTagName('a');
    for (var i in allLinks)
    {
        if (!allLinks[i].onclick || allLinks[i].onclick=='' || allLinks[i].onclick==undefined && allLinks[i].href )
        {
            if (allLinks[i].setAttribute)
            {
                allLinks[i].setAttribute('onclick','return confirm_exitnosave()');
            }
        }
    }
 }catch(e){}
}
function confirm_exitnosave()
{
    if(confirm ('".addslashes($textexitwithoutsaving)."'))
    {
        return true;
    }
    return false;
}
</script>
	";
    if (isset($_POST['__NOSAVE']))
    {
        $scriptOnExit.="
<script type=\"text/javascript\">
set_changed();
</script>";
    }


    //-----tabella --------->
    $toupdate=false;
    //----eliminazione del record
    if ($enabledelete && $opmod== "del")
    {
        $oldvalues=$table->xmltable->GetRecordByPk($pk);
        if ($function_on_delete && function_exists($function_on_delete))
        {
            ob_start();
            $function_on_delete($oldvalues);
            $html.=ob_get_clean();
        }
        $table->xmltable->DelRecord($pk);
    }
    $endloop=false;
    $num_records=$table->xmltable->GetNumRecords($restr);
    if ($params['maxrows'] && $params['maxrows']<= $num_records)
    {
        $enablenew=false;
    }

    while($endloop== false)
    {
        switch($opmod)
        {
            //-------inserimento/aggiornamento record  --------->
            case "insnew" :
                $endloop=true;
                $html.=$scriptOnExit;
                $newvalues=$table->getbypost();
                if (is_array($forcevalues))
                {
                    foreach($forcevalues as $k=> $v)
                    {
                        $newvalues[$k]=$v;
                    }
                }

                if (is_array($pk) && count($pk) > 0 || $pk!= "")
                {
                    $oldvalues=$table->xmltable->GetRecordByPk($pk);
                    $toupdate=true; //se esiste la chiave primaria il record ï¿½ da aggiornare
                }

                if ($toupdate && is_array($forcevaluesupdate))
                {
                    foreach($forcevaluesupdate as $k=> $v)
                    {
                        $newvalues[$k]=$v;
                    }
                }
                if (!$toupdate && is_array($forcevaluesinsert))
                {
                    foreach($forcevaluesinsert as $k=> $v)
                    {
                        $newvalues[$k]=$v;
                    }
                }
                if ($toupdate && !$enableedit)
                    break;
                if (!$toupdate && !$enablenew)
                    break;
                if (isset($_POST["save_$postgetkey"]) && !isset($_POST["__NOSAVE"]))
                {
                    if (is_array($table->xmltable->primarykey))
                    {
                        if ($pk) // arriva da un'altra insert
                        {
                            $pknewvalues=array();
                            foreach($table->xmltable->primarykey as $pkk)
                            {
                                $pknewvalues[$pkk]=$_POST[$pkk];
                            }
                            if ($table->xmltable->GetRecordByPk($pknewvalues))
                            {
                                $toupdate=true;
                            }
                        }
                    }
                    elseif (isset($_POST[$table->xmltable->primarykey]) && $pk)
                    {
                        if ($table->xmltable->GetRecordByPk($_POST[$table->xmltable->primarykey]))
                        {
                            $toupdate=true;
                        }
                    }
                    $errors=$table->Verify($newvalues,$toupdate);
                    if (count($errors)== 0)
                    {
                        if ($toupdate)
                        {
                            if ($function_update)
                            {
                                $newvalues=$function_update($newvalues,$pk);
                            }
                            else
                                $newvalues=$table->UpdateRecord($newvalues,$pk);
                            if (is_array($table->xmltable->primarykey))
                            {
                                $pk=array();
                                foreach($table->xmltable->primarykey as $pkv)
                                {
                                    $pk[$pkv]=$newvalues[$pkv];
                                }
                            }
                            else
                            {
                                $pk=$newvalues[$table->xmltable->primarykey];
                            }
                            if ($onupdate!= "")
                            {
                                //$html.=$onupdate;
                                //die("UPDATE");
                                break;
                            }
                            if (is_array($newvalues))
                            {
                                if ($function_on_update && function_exists($function_on_update))
                                {
                                    ob_start();
                                    $function_on_update($newvalues,$oldvalues);
                                    $html.=ob_get_clean();
                                }
                                $html=XMLDBEDITOR_HtmlAlert($textupdateok).$html;
                                if ($list_onupdate)
                                {
                                    $endloop=false;
                                    $opmod="";
                                    break;
                                }
                            }
                            else
                            {
                                $html=XMLDBEDITOR_HtmlAlert($textupdatefail).$html;
                            }
                        }
                        else
                        {
                            //insert record
                            if ($function_insert)
                            {
                                $newvalues=$function_insert($newvalues);
                            }
                            else
                            {
                                $newvalues=$table->InsertRecord($newvalues);
                            }
                            if (is_array($newvalues) && count($newvalues) > 0)
                            {
                                $oldvalues=$newvalues;
                                $pk=$newvalues[$table->xmltable->primarykey];
                                if ($function_on_insert && function_exists($function_on_insert))
                                {
                                    ob_start();
                                    $function_on_insert($newvalues);
                                    $html.=ob_get_clean();
                                }
                                if ($oninsert!= "")
                                {
                                    $html.=$oninsert;
                                    break;
                                }
                                $html=XMLDBEDITOR_HtmlAlert($textinsertok).$html;
                                $toupdate=true;
                            }
                            else
                            {
                                $html=XMLDBEDITOR_HtmlAlert($textinsertfail).$html;
                                $toupdate=false;
                                // break;
                            }
                            if ($list_oninsert)
                            {
                                $endloop=false;
                                $opmod="";
                                break;
                            }
                        }
                    }
                }
                $html.="$requiredfieldsymbol $textrequiredfields";
                $html.="

";
                //---------- $httpqueryparams ins new-->
                $httpqueryparams=array();
                if (is_array($table->xmltable->primarykey))
                {
                    $httpqueryparams["pk_{$postgetkey}"]=array();
                    foreach($table->xmltable->primarykey as $pkfield)
                    {
                        if (isset($oldvalues[$pkfield]))
                            $httpqueryparams["pk_{$postgetkey}"] [$pkfield]=$oldvalues[$pkfield];
                    }
                }
                else
                {
                    if (isset($oldvalues[$table->xmltable->primarykey]))
                        $httpqueryparams["pk_{$postgetkey}"]=$oldvalues[$table->xmltable->primarykey];
                }
                $httpqueryparams["page_$postgetkey"]=$page;
                $httpqueryparams["op_$postgetkey"]=$page;
                $httpqueryparams["desc_"]=$reverse;
                $httpqueryparams["order_$postgetkey"]=$order;
                /*
                  dprint_r($toupdate);
                  dprint_r($newvalues);
                  dprint_r($oldvalues);
                  dprint_r($pk);
                  dprint_r($httpqueryparams);
                 */
                $urlquery=(http_build_query($httpqueryparams));



                //---------- $httpqueryparams ins new--<             
                $link_=XMLDB_editor_mergelink($tlink,"$urlquery&amp;op_$postgetkey=insnew");
                $html.="<form onchange=\"set_changed()\" enctype=\"multipart/form-data\" action=\"$link_\" method=\"post\"><div>\n";
                if ($toupdate)
                {
                    $html.="";

                    if (is_array($forceupdatevalues))
                        foreach($forceupdatevalues as $fok=> $fov)
                        {
                            if (!isset($_POST[$fok]))
                            {
                                $newvalues[$fok]=$fov;
                            }
                        }
                    $html.=$table->HtmlShowUpdateForm($pk,$params['isadmin'],$newvalues,$errors);
                }
                else
                {
                    if (is_array($forcenewvalues))
                        foreach($forcenewvalues as $fok=> $fov)
                        {
                            if (!isset($_POST[$fok]))
                            {
                                $newvalues[$fok]=$fov;
                            }
                        }
                    $html.=$table->HtmlShowInsertForm($params['isadmin'],$newvalues,$errors);
                }
                if ($functioninsert!= "" && function_exists($functioninsert))
                {
                    ob_start();
                    $functioninsert($newvalues);
                    $html.=ob_get_clean();
                }
                $html.="<br /><input style=\"display:none\" id=\"frm$postgetkey\" type=\"hidden\" name=\"save_$postgetkey\" value=\"$postgetkey\"/><button  type=\"submit\"   >".$textsave."</button>";


                if ($textcancel!= "")
                {
                    $link_=XMLDB_editor_mergelink("",$mlink);
                    $html.="&nbsp;<button id='exit_$postgetkey' type=\"button\" onclick=\"window.location='".str_replace("&amp;","&",$link_)."'\" >".$textcancel."</button>";
                }
                $html.="\n</div></form>";





                if ($textviewlist)
                {
                    $link_textviewlist=XMLDB_editor_mergelink($flink_listmode,"page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse");
                    $html.="<br /><a  href=\"$link_textviewlist\">$textviewlist</a> ";
                }
                // if ($enablenew)
                //     $html.="&nbsp;<a href=\"?page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse&amp;op_{$postgetkey}=insnew$mlink\">$textnew</a>";
//                if ($enablenew && $textnewinner)
//                    $html.="&nbsp;<button id='exit_$postgetkey' type=\"button\" onclick=\"window.location='?".str_replace("&amp;","&","?page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse&amp;op_{$postgetkey}=insnew$mlink")."'\" >".$textnewinner."</button>";


                break;
            //-------inserimento/aggiornamento record  ---------<
            case "view" :
                $endloop=true;
                if (isset($params['layout_view']))
                {
                    $table->SetLayoutView($params['layout_view']);
                }
                if (isset($params['html_template_view']))
                {

                    $table->SetlayoutTemplateView($params['html_template_view']);
                }
                $html.=$table->HtmlShowView($table->GetRecordTranslatedByPrimarykey($pk));
                //$html .= xmldb_view($pk,$tablename,$dbname,$path,$lang,$languages);
                $html.="<br /><br /><a style=\"color:$linkcolor\" href=\"?page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse&amp;$flink_listmode\">$textviewlist</a><br /><br />";

                break;
            default :
                //---------------------------GRID------------------------------>

                $endloop=true;
                if (!empty($params['function_grid']) && function_exists($params['function_grid']))
                {
                    $params['function_grid']();
                    break;
                }
                //---------------------template grid--------------------------------------->
                preg_match('/(<!-- start insertnew -->)(.*)(<!-- end insertnew -->)/is',$params['html_template_grid'],$out);
                $template_insertnew=empty($out[2]) ? "" : $out[2];
                $htmlnewrecord="";
                if ($enablenew)
                {
                    $link_newrecord=XMLDB_editor_mergelink($mlink,"page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse&amp;op_{$postgetkey}=insnew");
                    $htmlnewrecord=str_replace("{urlnewrecord}","$link_newrecord",$template_insertnew);
                    $htmlnewrecord=str_replace("{textnew}",$textnew,$htmlnewrecord);
                }
                $params['html_template_grid']=str_replace($template_insertnew,$htmlnewrecord,$params['html_template_grid']);
                $params['html_template_grid']=str_replace("{htmlnewrecord}",$htmlnewrecord,$params['html_template_grid']);
                preg_match('/(<!-- start grid -->)(.*)(<!-- end grid -->)/is',$params['html_template_grid'],$out);
                $template_grid=empty($out[2]) ? "" : $out[2];

                preg_match('/(<!-- start pages -->)(.*)(<!-- end pages -->)/is',$params['html_template_grid'],$out);
                $template_pages=empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start page -->)(.*)(<!-- end page -->)/is',$template_pages,$out);
                $template_page=empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start currentpage -->)(.*)(<!-- end currentpage -->)/is',$template_pages,$out);
                $template_currentpage=empty($out[2]) ? "" : $out[2];

                preg_match('/(<!-- start gridheader -->)(.*)(<!-- end gridheader -->)/is',$params['html_template_grid'],$out);
                $template_gridheader=empty($out[2]) ? "" : $out[2];

                preg_match('/(<!-- start table -->)(.*)(<!-- end table -->)/is',$params['html_template_grid'],$out);
                $template_table=empty($out[2]) ? "" : $out[2];

                preg_match('/(<!-- start gridrow -->)(.*)(<!-- end gridrow -->)/is',$template_gridheader,$out);
                $template_gridheader_gridrow=empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start gridfield -->)(.*)(<!-- end gridfield -->)/is',$template_gridheader,$out);
                $template_gridheader_gridrow_gridfield=empty($out[2]) ? "" : $out[2];

                preg_match('/(<!-- start gridbody -->)(.*)(<!-- end gridbody -->)/is',$params['html_template_grid'],$out);
                $template_gridbody=empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start gridrow -->)(.*)(<!-- end gridrow -->)/is',$template_gridbody,$out);
                $template_gridbody_gridrow=empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start gridfield -->)(.*)(<!-- end gridfield -->)/is',$template_gridbody,$out);
                $template_gridbody_gridrow_gridfield=empty($out[2]) ? "" : $out[2];

                //---------------------template grid---------------------------------------<

                if ($htmlgrid)
                    $html.=$htmlgrid;
                else
                {
                    $fieldstoread=$__fieldstoread=false;
                    if ($fields!= false && !is_array($fields))
                    {
                        $fields=explode("|",$fields);
                        $__fieldstoread=$fields;
                        if (!isset($__fieldstoread[$table->xmltable->primarykey]))
                        {
                            $__fieldstoread[]=$table->xmltable->primarykey;
                        }
                    }
                    if ($__fieldstoread)
                    {
                        foreach($__fieldstoread as $fieldname)
                        {
                            $fieldname=preg_replace("/\[.*\]/s","",$fieldname);
                            if (isset($table->xmltable->fields[$fieldname]))
                            {
                                $fieldstoread[]=$fieldname;
                            }
                        }
                    }
                    //dprint_r($fields);
                    if ($params['defaultorder']== false)
                        $params['defaultorder']=is_array($table->xmltable->primarykey) ? $table->xmltable->primarykey[0] : $table->xmltable->primarykey;
                    if ($order== false)
                        $order=$params['defaultorder'];
                    $order=preg_replace("/\[.*\]/s","",$order);
                    $numPages=1;
                    $all=false;
                    if (!empty($params['recordset']) && is_array($params['recordset']))
                    {
                        $all=$params['recordset'];
                        $num_records=count($all);
                    }
                    if (intval($recordsperpage)!= false)
                    {
                        if ($page== "")
                            $page=1;
                        if ($all=== false)
                            $num_records=$table->xmltable->GetNumRecords($restr);
                        $numPages=ceil($num_records / $recordsperpage);
                        if ($page > $numPages)
                            $page=$numPages;
                        $start=($page * $recordsperpage - $recordsperpage) + 1;
                        $end=$start + $recordsperpage - 1;
                    }
                    else
                    {
                        $start=false;
                    }
                    //die ("ds");
                    if ($all=== false)
                    {
                        if ($order== false)
                        {
                            $all=$table->xmltable->GetRecords($restr,$start,$recordsperpage,$order,$reverse,$fieldstoread);
                        }
                        else
                        {
                            $all=$table->xmltable->GetRecords($restr,false,false,false,false,$fieldstoread);
                        }
                    }
                    $end=$start + $recordsperpage;
                    if ($end > $num_records)
                    {
                        $end=$num_records;
                    }
                    if ($num_records > 0)
                        $txt_num_records="$start-".($end)." ".XMLDB_i18n("of","aa")." ".$num_records;
                    else
                        $txt_num_records="0-0"." ".XMLDB_i18n("of","aa")." 0";
                    $htmlpages="";
                    $link_prevpage="";
                    $link_nextpage="";
                    $cp=false;
                    if ($recordsperpage && $numPages > 1)
                    {
                        //$maxpages=100;
                        $id_page=1;

                        if ($numPages > $maxpages && $page > 1)
                        {
                            $id_page=$page;
                            if ($numPages - $id_page < $maxpages)
                            {
                                $id_page=$id_page - $maxpages + 1;
                            }
                        }
                        $cp=1;
                        if ($id_page > 1)
                        {
                            
                            $s=array("{pagelink}","{pagetitle}");
                            $r=array();
                            $link_=XMLDB_editor_mergelink("","$flink_listmode&amp;page_{$postgetkey}=1&amp;order_{$postgetkey}=$order");
                            $r['pagelink']="$link_";
                            $r['pagetitle']="&lt;&lt;";
                            $htmlpages.=str_replace($s,$r,$template_page);
                        }
                        if ($id_page > 1)
                        {
                            $s=array("{pagelink}","{pagetitle}");
                            $r=array();
                            $link_=XMLDB_editor_mergelink("","$flink_listmode&amp;page_{$postgetkey}=".($page - 1)."&amp;order_{$postgetkey}=$order");
                            $r['pagelink']="$link_";
                            $r['pagetitle']="&lt;";
                            $htmlpages.=str_replace($s,$r,$template_page);
                        }
                        for($i=$id_page; $i<= $numPages; $i++)
                        {
                            $s=array("{pagelink}","{pagetitle}");
                            $r=array();
                            $link_=XMLDB_editor_mergelink("","$flink_listmode&amp;page_{$postgetkey}=$i&amp;order_{$postgetkey}=$order");
                            $r['pagelink']="$link_";
                            $r['pagetitle']=$i;
                            if ($page== $i)
                                $htmlpages.=str_replace($s,$r,$template_currentpage);
                            else
                                $htmlpages.=str_replace($s,$r,$template_page);
                            if ($cp>= $maxpages)
                            {
                                break;
                            }
                            $cp++;
                        }
                    }
                    if ($cp && $cp < $numPages)
                    {
                        $s=array("{pagelink}","{pagetitle}");
                        $r=array();

                        $r['pagelink']="?$flink_listmode&amp;page_{$postgetkey}=".($page + 1)."&amp;order_{$postgetkey}=$order";
                        $r['pagetitle']="&gt;";
                        $htmlpages.=str_replace($s,$r,$template_page);
                    }
                    if ($cp && $cp < $numPages)
                    {
                        $s=array("{pagelink}","{pagetitle}");
                        $r=array();
                        $link_=XMLDB_editor_mergelink("","$flink_listmode&amp;page_{$postgetkey}=$numPages&amp;order_{$postgetkey}=$order");
                        $r['pagelink']="$link_";
                        $r['pagetitle']="&gt;&gt;";
                        $htmlpages.=str_replace($s,$r,$template_page);
                    }

                    $htmlpages_full=$htmlpages;
                    //  $htmlpages_full=preg_replace('/(<!-- start pages -->)(.*)(<!-- end pages -->)/is',$htmlpages,$htmlpages_full);


                    if ($order!= false)
                    {
                        if (is_array($all))
                            $all=xmldb_array_natsort_by_key($all,str_replace("{$postgetkey}","",$order));
                    }
                    if ($reverse== true && is_array($all))
                    {
                        $all=array_reverse($all);
                    }




                    //----------------------header----------------------------->
                    $html_GridHeader="";
                    $orderfield=array();
                    if ($fields)
                    {
                        foreach($fields as $ofield)
                        {
                            //$ofield=preg_replace("/\[.*\]/s","",$ofield);
                            if (isset($table->formvals[$ofield]))
                            {
                                $orderfield[$ofield]=$table->formvals[$ofield];
                            }
                            else
                            {
                                $tf="";
                                if (strstr($ofield,"]"))
                                {
                                    $tf=explode("]",$ofield);
                                    $__ofield=$tf[1];
                                    $orderfield[$ofield]=isset($table->formvals[$__ofield]) ? $table->formvals[$__ofield] : "";
                                    $tf=$tf[0];
                                    $tf=str_replace("[","",$tf);
                                    $orderfield[$ofield]=array();
                                    $orderfield[$ofield]['title']=$tf;
                                }
                                else
                                    $orderfield[$ofield]="";
                            }
                        }
                    }
                    else
                        $orderfield=$table->formvals;
                    // dprint_r($orderfield);
                    $numcols=0;
                    foreach($orderfield as $key=> $value)
                    {
                        if ($fields && !in_array($key,$fields))
                        {
                            continue;
                        }
                        if ($show_translations== true || !isset($table->formvals[$key]['frm_multilanguage']) || $table->formvals[$key]['frm_multilanguage']!= "1")
                        {
                            $numcols++;
                            $key=preg_replace("/\[.*\]/s","",$key);
                            if (isset($value['title']))
                            {
                                $rev="";
                                $t="";
                                $desclink="";
                                if ($order== $key)
                                {
                                    if ($reverse)
                                    {
                                        $t="<img style=\"vertical-align:middle;float:right\" src=\"{$siteurl}images/fn_up.png\" alt=\"\" />";
                                        $desclink="";
                                    }
                                    else
                                    {
                                        $t="<img style=\"vertical-align:middle;float:right\" src=\"{$siteurl}images/fn_down.png\" alt=\"\" />";
                                        $desclink="&amp;desc_{$postgetkey}=$key";
                                    }
                                }
                                $tmp_html=str_replace("{fieldvalue}","".$value['title'],$template_gridheader_gridrow_gridfield);
                                $link_=XMLDB_editor_mergelink("$tlink","order_{$postgetkey}=$key{$desclink}");
                                $tmp_html=str_replace("{link}","$link_",$tmp_html);
                                $tmp_html=str_replace("{numcols}",$numcols,$tmp_html);
                                $tmp_html=str_replace("{arrow}",$t,$tmp_html);


                                $html_GridHeader.=$tmp_html;

                                //$html_Grid .= "<td style=\"background-color:$backgroundcolorheader;font-weight:bold;color:$textcolor\" ><a style=\"color:$linkcolor\" href=\"?order_{$postgetkey}=$key{$desclink}&amp;$tlink\">".$value['title']."</a>$t</td>";
                            }
                            else
                            {
                                $tmp_html=str_replace("{fieldvalue}","",$template_gridheader_gridrow_gridfield);
                                $tmp_html=str_replace("{arrow}","",$tmp_html);

                                $html_GridHeader.=$tmp_html;
                            }
                        }
                    }


                    $template_gridheader_gridrow=str_replace("{numactions}",count($optionsedit),$template_gridheader_gridrow);
                    $template_gridheader_gridrow=str_replace("{bkheader}",$backgroundcolorheader,$template_gridheader_gridrow);
                    $html_GridRow=preg_replace('/(<!-- start gridfields -->)(.*)(<!-- end gridfields -->)/is',xmldb_encode_preg_replace2nd($html_GridHeader),$template_gridheader_gridrow);
                    $html_GridHeader=preg_replace('/(<!-- start gridrow -->)(.*)(<!-- end gridrow -->)/is',xmldb_encode_preg_replace2nd($template_gridheader_gridrow),$html_GridRow);


                    //----------------------header-----------------------------<
                    $i=0;
                    static $tablefk=array();
                    $html_gridbody="";
                    if (is_array($all) && count($all) > 0)
                    {
                        foreach($all as $row)
                        {
                            $html_gridrow="";
                            $backgroundcolor=($i % 2== 0) ? $bgcolor : $bgcolor2;
                            $i++;
                            //start - end --->
                            if ($recordsperpage!= false && ($i < $start || $i > $end))
                                continue;
                            //start -end ---<
                            $html_gridfields="";
                            $httpqueryparams=array();
                            if (is_array($table->xmltable->primarykey))
                            {
                                $httpqueryparams["pk_{$postgetkey}"]=array();
                                foreach($table->xmltable->primarykey as $pkfield)
                                {
                                    $httpqueryparams["pk_{$postgetkey}"] [$pkfield]=$row[$pkfield];
                                }
                            }
                            else
                            {
                                $httpqueryparams["pk_{$postgetkey}"]=$row[$table->xmltable->primarykey];
                            }
                            $httpqueryparams["page_$postgetkey"]=$page;
                            $httpqueryparams["desc_"]=$reverse;
                            $httpqueryparams["order_$postgetkey"]=$order;
                            $urlquery=(http_build_query($httpqueryparams));
                            //---------- $httpqueryparams actions--<

                            /* dprint_r($httpqueryparams);
                              dprint_r($urlquery);
                              dprint_r($flink_listmode); */
                            

                            if (!empty($params['actions_before_fields']))
                            {
                                if ($enableview && $numcols++)
                                {
                                    $link_=XMLDB_editor_mergelink($mlink,"$urlquery&amp;op_$postgetkey=view");
                                    $html_gridfields.=str_replace("{fieldvalue}","<a href=\"$link_\">".$textview."</a>",$template_gridbody_gridrow_gridfield);
                                }
                                if ($enableedit && $numcols++)
                                {
                                    $link_=XMLDB_editor_mergelink($mlink,"$urlquery&amp;op_$postgetkey=insnew");
                                    $html_gridfields.=str_replace("{fieldvalue}","<a href=\"$link_\">".$textmodify."</a>",$template_gridbody_gridrow_gridfield);
                                }
                                if ($enabledelete && $numcols++)
                                {
                                    $link_=XMLDB_editor_mergelink($mlink,"$urlquery&op_$postgetkey=del");
                                    $html_gridfields.=str_replace("{fieldvalue}","<a href=\"javascript:check('$link_');\">".$textdelete."</a>",$template_gridbody_gridrow_gridfield);
                                }
                            }

                            foreach($orderfield as $key=> $field)
                            {
                                $kfunction=(false!== strpos($key,"()")) ? str_replace("()","",$key) : false;
                                if (false!== strpos($kfunction,"]"))
                                {
                                    $kfunction=explode("]",$kfunction);
                                    $kfunction=$kfunction[1];
                                }

                                if ($fields && !in_array($key,$fields))
                                    continue;
                                $vimage="";
                                if ($show_translations== true || !isset($table->formvals[$key]['frm_multilanguage']) || $table->formvals[$key]['frm_multilanguage']!= "1")
                                {
                                    if (isset($functionsview[$key]))
                                    {
                                        $value=$functionsview[$key]($row[$field['name']]);
                                    }
                                    elseif (function_exists($kfunction))
                                    {
                                        $value=$kfunction($row[$table->xmltable->primarykey],$table);
                                    }
                                    elseif (!isset($field['name']))
                                    {
                                        $value="";
                                    }
                                    elseif (($field['frm_type']== "datetime") && method_exists($table->formclass[$field['name']],"view"))
                                    {
                                        $tparams=$field;
                                        $tparams['name']=$field['name'];
                                        $tparams['value']=$row[$field['name']];
                                        $tparams['values']=$row;
                                        $tparams['fieldform']=$table;
                                        $value=$table->formclass[$field['name']]->view($tparams);
                                    }
                                    else
                                    {
                                        $value=$row[$field['name']];

                                        if ($field['frm_type']== "image")
                                        {
                                            $image=$table->xmltable->get_file($row,$field['name']);
                                            if ($image!= "")
                                            {
                                                $himage=16;
                                                if (isset($params['imagesize']))
                                                {
                                                    $himage=$params['imagesize'];
                                                }
                                                $image=dirname($image)."/thumbs/".basename($image).".jpg";
                                                $vimage="<img src=\"".$image."\" height=\"$himage\" />";
                                                $value="";
                                            }
                                        }
                                        if (!empty($field['foreignkey']) && !empty($field['fk_show_field']))
                                        {
                                            $r=array();
                                            if (function_exists("FN_XmlTable"))
                                                $tfk[$field['fk_link_field']]=FN_XmlTable($field['foreignkey']);
                                            else
                                                $tfk[$field['fk_link_field']]=xmldb_table($dbname,$field['foreignkey'],$path);


                                            $tablefk=$tfk[$field['fk_link_field']];
                                            $f=$field['fk_link_field'];
                                            if ($field['fk_link_field']!= $field['fk_show_field'])
                                            {
                                                $f.="|".$field['fk_show_field'];
                                            }
                                            if (isset($tfk[$field['fk_link_field']]->fields[$field['fk_link_field']."_{$lang}"]))
                                                $f.="|".$field['fk_show_field']."_{$_FN['lang']}";
                                            //echo $f;

                                            $r[$field['fk_link_field']]=$row[$field['name']];
                                            //dprint_r($value);
                                            $showfields=explode(",",$field['fk_show_field']);
                                            $value="";
                                            $sep="";

                                            $fname_tmp_array=$row[$field['name']];
                                            $fname_tmp_array=explode(",",$fname_tmp_array);
                                            foreach($fname_tmp_array as $fname_tmp)
                                            {
                                                foreach($showfields as $showfield)
                                                {
                                                    $tvalue=$tablefk->GetRecord(array($field['fk_link_field']=>$fname_tmp));
                                                    if (isset($tvalue["{$showfield}_{$lang}"]) && $tvalue["{$showfield}_{$lang}"]!= "")
                                                        $value.=$sep.$tvalue["{$showfield}_{$lang}"];
                                                    elseif (isset($tvalue["{$showfield}_en"]) && $tvalue["{$showfield}_en"]!= "")
                                                        $value.=$sep.$tvalue[$showfield."_en"];
                                                    else
                                                        $value.=$sep.$tvalue[$showfield];
                                                    $sep="\n";
                                                }
                                                $sep="-";
                                            }
                                        }
                                        else
                                        {
                                            if (isset($field['options']) && is_array($field['options']))
                                            {
                                                foreach($field['options'] as $opt)
                                                {
                                                    if ($row[$field['name']]== $opt['value'])
                                                        $value=$opt['title'];
                                                }
                                            }
                                        }
                                        $value=XMLDB_FixEncoding(substr(strip_tags($value),0,$params['max_cell_text_lenght']),$params['charset_page']);
                                        //dprint_r($vs);
                                    }
                                    $html_gridfields.=str_replace("{fieldvalue}","$vimage$value",$template_gridbody_gridrow_gridfield);
                                    /* if (!empty($params['columns']))
                                      {
                                      $columns = explode(",",$params['columns']);
                                      foreach ($columns as $column )
                                      {
                                      if (  function_exists($column))
                                      {
                                      $column($row[$table->xmltable->primarykey]);
                                      }
                                      }
                                      }

                                     */
                                }
                            }

                            //---------- $httpqueryparams actions-->

                            if (empty($params['actions_before_fields']))
                            {
                                if ($enableview && $numcols++)
                                {
                                    $link_=XMLDB_editor_mergelink($mlink,"$urlquery&amp;op_$postgetkey=view");
                                    $html_gridfields.=str_replace("{fieldvalue}","<a href=\"$link_\">".$textview."</a>",$template_gridbody_gridrow_gridfield);
                                }
                                if ($enableedit && $numcols++)
                                {
                                    $link_=XMLDB_editor_mergelink($mlink,"$urlquery&amp;op_$postgetkey=insnew");

                                    $html_gridfields.=str_replace("{fieldvalue}","<a href=\"$link_\">".$textmodify."</a>",$template_gridbody_gridrow_gridfield);
                                }
                                if ($enabledelete && $numcols++)
                                {
                                   
                                    $link_=XMLDB_editor_mergelink($mlink,"$urlquery&op_$postgetkey=del");
                                    $html_gridfields.=str_replace("{fieldvalue}","<a href=\"javascript:check('$link_');\">".$textdelete."</a>",$template_gridbody_gridrow_gridfield);
                                }

                            }
                            $tmp_html=str_replace("{bkrow}",$backgroundcolor,$template_gridbody_gridrow);

                            $html_gridrow=preg_replace('/(<!-- start gridfields -->)(.*)(<!-- end gridfields -->)/is',xmldb_encode_preg_replace2nd($html_gridfields),$tmp_html);
                            $html_gridbody.=$html_gridrow;
                        }
                    }
                    else
                    {
                        //$html = "<table border=\"\" style=\"background-color:$bordercolor\" cellpadding=\"2\" cellspacing=\"1\" >";
//                        $html_gridbody .= "$textnorecord";
                    }
                    $params['html_template_grid']=str_replace("{txt_num_records}","$txt_num_records",$params['html_template_grid']);
                    $params['html_template_grid']=str_replace("{start}","$start",$params['html_template_grid']);
                    $params['html_template_grid']=str_replace("{end}","$end",$params['html_template_grid']);
                    $params['html_template_grid']=str_replace("{num_records}","$num_records",$params['html_template_grid']);



                    $html_table=preg_replace('/(<!-- start table -->)(.*)(<!-- end table -->)/is',xmldb_encode_preg_replace2nd($html_gridbody),$template_grid);
                    $html=preg_replace('/(<!-- start gridheader -->)(.*)(<!-- end gridheader -->)/is',xmldb_encode_preg_replace2nd($html_GridHeader),$params['html_template_grid']);
                    $html=preg_replace('/(<!-- start gridbody -->)(.*)(<!-- end gridbody -->)/is',xmldb_encode_preg_replace2nd($html_gridbody),$html);
                    $html=preg_replace('/(<!-- start pages -->)(.*)(<!-- end pages -->)/is',xmldb_encode_preg_replace2nd($htmlpages_full),$html);
                }
                $endloop=true;
                //---------------------------GRID------------------------------<

                break;
        }
    }
    if ($params['echo']== true)
        echo $html;
    //dprint_r($_POST);
    return $html;
}

/**
 * 
 * @param array $array
 * @param string $order
 * @param bool $desc
 */
/*
  function xmldb_array_natsort_by_key($array, $order, $desc = false)
  {
  $ret = $array;
  $newret = array();
  foreach ( $ret as $key => $value )
  {
  if ( isset($value[$order]) )
  {
  $i = $desc ? 99999999 : 0;
  $r = $value[$order];
  while ( isset($newret[$r . $i]) )
  {
  if ( $desc )
  $i--;
  else
  $i++;
  }
  $newret[$r . $i] = $ret[$key];
  }
  else
  {
  $i = $desc ? 99999999 : 0;
  $r = "";
  while ( isset($newret[$r . $i]) )
  {
  if ( $desc )
  $i--;
  else
  $i++;
  }
  $newret[$r . $i] = $ret[$key];
  }
  }
  //----era cc_natkcasesort ( $newret ); ---->
  $keys = array_keys($newret);
  $new_array = array();
  natcasesort($keys);
  foreach ( $keys as $k )
  {
  $new_array[$k] = $newret[$k];
  }
  $newret = $new_array;
  //----era cc_natkcasesort ( $newret ); ----<
  if ( $desc == true )
  {
  $newret = array_reverse($newret);
  }
  return $newret;
  }

 */

/**
 * 
 * @param string $unirecid
 * @param string $tablename
 * @param string $databasename
 * @param string $path
 * @param string $lang
 * @param string $languages
 * @param array $params
 * @return string
 */
function xmldb_view($unirecid,$tablename,$databasename,$path,$lang,$languages,$params=false)
{
    global $_FN;
    $params['link']=isset($params['link']) ? $params['link'] : "";
    $htmlout="";
    if (!isset($params['isadmin']))
        $params['isadmin']=XMLDBEDITOR_IsAdmin();
    $Table=new FieldFrm("$databasename",$tablename,$path,$lang,$languages,$params);
    $row=$Table->xmltable->GetRecordByPrimaryKey($unirecid);
    $unirecid=isset($row['unirecid']) ? $row['unirecid'] : false;
    $htmlout=$Table->ShowView($row);
    return $htmlout;
}

/**
 *
 * @global <type> $databasename
 * @global <type> $tablename
 * @global <type> $pathdatabase
 * @param <type> $file
 * @param <type> $databasename
 * @param <type> $tablename
 * @param <type> $pathdatabase 
 */
function xmldb_go_download($file,$databasename,$tablename,$pathdatabase,$tablepath)
{
    // evita di accedere a directory esterne
    //  if (stristr($file,".."))
    //      die(fn_i18n("operation is not permitted"));
    // se il file non esiste lo crea
    if (!file_exists("$pathdatabase/$databasename/$tablename"."_download_stat.php"))
    {
        //echo "<br>creazione statistiche $tablename";
        $sfields=array();
        $sfields[1]['name']="filename";
        $sfields[1]['primarykey']="1";
        $sfields[2]['name']="numdownload";
        $sfields[2]['defaultvalue']="0";
        echo createxmltable($databasename,$tablename."_download_stat",$sfields,$pathdatabase);
    }
    $stat=new XMLTable($databasename,$tablename."_download_stat",$pathdatabase);
    $oldval=$stat->GetRecordByPrimaryKey($file);
    $r['filename']=$file;
    if ($oldval== null)
    {
        $r['numdownload']=1;
        $stat->InsertRecord($r);
    }
    else
    {
        //incrementa download
        $r['numdownload']=$oldval['numdownload'] + 1;
        $stat->UpdateRecord($r);
    }

    $file="$pathdatabase/$databasename/$tablepath/$file";

    //First, see if the file exists
    if (!is_file($file))
    {
        die("<b>$file 404 File not found!</b>");
    }

    //Gather relevent info about file
    $len=filesize($file);
    $filename=basename($file);
    $file_extension=strtolower(substr(strrchr($filename,"."),1));
    //This will set the Content-Type to the appropriate setting for the file
    switch($file_extension)
    {
        case "pdf" :
            $ctype="application/pdf";
            break;
        case "exe" :
            $ctype="application/octet-stream";
            break;
        case "zip" :
            $ctype="application/zip";
            break;
        case "doc" :
            $ctype="application/msword";
            break;
        case "xls" :
            $ctype="application/vnd.ms-excel";
            break;
        case "ppt" :
            $ctype="application/vnd.ms-powerpoint";
            break;
        case "gif" :
            $ctype="image/gif";
            break;
        case "png" :
            $ctype="image/png";
            break;
        case "jpeg" :
        case "jpg" :
            $ctype="image/jpg";
            break;
        case "mp3" :
            $ctype="audio/mpeg";
            break;
        case "wav" :
            $ctype="audio/x-wav";
            break;
        case "mpeg" :
        case "mpg" :
        case "mpe" :
            $ctype="video/mpeg";
            break;
        case "mov" :
            $ctype="video/quicktime";
            break;
        case "avi" :
            $ctype="video/x-msvideo";
            break;

        //The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files)
        case "php" :
        case "htm" :
        case "html" :
        case "txt" :
            $ctype="application/force-download";
            break;

        default :
            $ctype="application/force-download";
    }
    while(@ob_end_clean())
    {
        
    };
    //die("x");
    //Begin writing headers
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Type: $ctype");
    $header="Content-Disposition: attachment; filename=".$filename.";";
    header($header);
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: ".$len);
    @readfile($file);
    die("");
}

/**
 * 
 * @param string $field
 * @param string $title
 * @param string $value
 * @param string $st
 * @param string $et
 */
function xmldb_viewlink($row,$path,$databasename,$tablename,$field,$title,$value,$st,$et)
{
    $htmlout="$st";
    if ($value!= "")
    {
        if (!preg_match("/^http:\\/\\//si",$value))
        {
            $value=htmlspecialchars($value);
            $value="http://$value";
        }
        $htmlout.="<a onclick=\"window.open(this.href);return false;\" href=\"$value\">$value</a>";
    }
    $htmlout.="$et";
    return $htmlout;
}

/**
 * 
 * @param string $field
 * @param string $title
 * @param string $value
 * @param string $st
 * @param string $et
 */
function xmldb_viewswf($row,$path,$databasename,$tablename,$field,$title,$value,$st,$et)
{
    $htmlout="$st";
    if (preg_match('/\.swf/is',$row[$field['name']]))
    {
        $fileflash=isset($row['unirecid']) ? "$path/$databasename/$tablename/".$row['unirecid']."/".$field['name']."/".$row[$field['name']] : "";
        $htmlout.="\n$st$title ";
        $htmlout.="\n<object width=\"550\" height=\"400\">
<param name=\"movie\" value=\"$fileflash\">
<embed src=\"$fileflash\" width=\"550\" height=\"400\">
</embed>
</object>$et";
    }
    else
    {
        $htmlout="$st$et";
    }
    return $htmlout;
}

/**
 * 
 * @param string $field
 * @param string $title
 * @param string $value
 * @param string $st
 * @param string $et
 */
function xmldb_viewyoutubelink($row,$path,$databasename,$tablename,$field,$title,$value,$st,$et)
{
    $htmlout="$st";
    $html="";
    $codes=explode(",",$value);
    foreach($codes as $value)
    {
        $c="v";
        $code=explode("/v/",$value);
        if (!isset($code[1]))
            $code=explode("?v=",$value);
        if (!isset($code[1]))
        {
            $code=explode("/p/",$value);
            $c="p";
        }
        if (!isset($code[1]))
        {
            $code=explode("?p=",$value);
            $c="p";
        }
        if (isset($code[1]))
        {
            $h=344;
            $w=425;
            $code=$code[1];
            if (false!== strstr($code,"&"))
                $code=substr($code,0,strpos($code,"&"));
            if (false!== strstr($code,"?"))
                $code=substr($code,0,strpos($code,"?"));
            if ($code!= "")
            {
                $code="http://www.youtube.com/$c/$code&amp;autoplay=0&amp;rel=0";
                $html.="<div style=\"height:$h;width:$w;overflow:auto\">";
                //$html .= "<center>";
                $html.="<object width=\"$w\" height=\"$h\">
					<param name=\"movie\" value=\"$code\"></param>
					<param name=\"enablejsapi\" value=\"1\"></param>
					<param name=\"allowFullScreen\" value=\"true\"></param>
					<embed src=\"$code\" type=\"application/x-shockwave-flash\" enablejsapi=\"1\" allowfullscreen=\"true\" width=\"$w\" height=\"$h\"></embed></object>";
                //$html .= "</center>";
                $html.="</div>";
            }
            else
            {
                $html.=htmlentities($value);
            }
        }
    }
    $htmlout.=" \n$st$title ".$html."$et";
    return $htmlout;
}

if (!function_exists("XMLDBEDITOR_HtmlAlert"))
{

    function XMLDBEDITOR_HtmlAlert($textupdateok)
    {
        $textupdateok=addslashes($textupdateok);
        return "<script>alert('$textupdateok');</script>";
    }

}
if (!function_exists("XMLDBEDITOR_IsAdmin"))
{

    function XMLDBEDITOR_IsAdmin()
    {
        return false;
    }

}
?>