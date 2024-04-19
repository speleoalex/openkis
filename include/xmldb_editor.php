<?php

/**
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2009
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * @package xmldb
 * 
 */
function XMLDB_editor_mergelink($link1, $link2)
{
    $vars = array();
    //dprint_r("1)$link1\n2)$link2","","red");

    if ($link1 && false == strstr($link1, "?") && false !== strstr($link1, "&"))
    {
        $link1 = "?$link1";
    }
    if ($link2 && false == strstr($link2, "?") && false !== strstr($link2, "&"))
    {
        $link2 = "?$link2";
    }
    if ($link1 == "?" || $link1 == "&")
        $link1 = "";
    if ($link2 == "?" || $link2 == "&")
        $link2 = "";

    if (preg_match('/^http(s?)\:\/\//i', $link1) && preg_match('/^http(s?)\:\/\//i', $link2))
    {
        $link2 = explode("?", $link2);
        $link2 = isset($link2[1]) ? $link2[1] : "";
    }
    if ($link1 == "")
    {
        return XMLDB_editor_cleanLink($link2);
    }
    if ($link2 == "")
    {
        return XMLDB_editor_cleanLink($link1);
    }
    if (preg_match('/^http(s?)\:\/\//i', $link1) && !preg_match('/^http(s?)\:\/\//i', $link2))
    {
        $tmp = $link1;
        $link1 = $link2;
        $link2 = $tmp;
    }
    if (!preg_match('/^http(s?)\:\/\//i', $link1) && preg_match('/^http(s?)\:\/\//i', $link2))
    {

        $tmplink1 = explode("?", $link1);
        if (isset($tmplink1[1]))
        {
            $base1 = $tmplink1[0];
            $tmplink1 = $tmplink1[1];
        }
        else
        {
            $base1 = "";
            $tmplink1 = $tmplink1[0];
        }

        $_sep = "&amp;";
        if (false === strstr($link2, "?"))
        {
            $_sep = "?";
        }

        $_final_link = $link2 . $_sep . $tmplink1;
        //dprint_r($_final_link);
        return XMLDB_editor_cleanLink($_final_link);
    }
    if (!preg_match('/^http(s?)\:\/\//i', $link1) && !preg_match('/^http(s?)\:\/\//i', $link2))
    {
        $tmplink1 = explode("?", $link1);
        if (isset($tmplink1[1]))
        {
            $base1 = $tmplink1[0];
            $tmplink1 = $tmplink1[1];
        }
        else
        {
            $base1 = "";
            $tmplink1 = $tmplink1[0];
        }

        $tmplink2 = explode("?", $link2);
        if (isset($tmplink2[1]))
        {
            $base2 = $tmplink2[0];
            $tmplink2 = $tmplink2[1];
        }
        else
        {
            $base2 = "";
            $tmplink2 = $tmplink2[0];
        }
        $base = !empty($base1) ? $base1 : $base2;
        $link = $base . "?$tmplink1&$tmplink2";
        return XMLDB_editor_cleanLink($link);
    }
}

/**
 * 
 * @param type $link
 * @return string
 */
function XMLDB_editor_cleanLink($link)
{

    $link1 = explode("?", $link);
    if (!isset($link1[1]))
    {
        return $link;
    }
    else
    {
        $querystring = $link1[1];
        $base = $link1[0];
    }
    $sep = "&";
    if (false !== stristr($querystring, "&amp;"))
    {
        $sep = "&amp;";
        $querystring = str_replace("&amp;", "&", $querystring);
    }

    $url_params = "";
    parse_str($querystring, $url_params);
    $args = http_build_query($url_params);
    $ret = $base . "?" . $args;
    return $ret;
}

/**
 * edid xml tables
 * 
 * @param $tablename
 * @param $dbname
 * @param $params
 * @return unknown_type
 */
function XMLDB_editor($tablename, $params = array())
{
    $dbname = isset($params['xmldatabase']) ? $params['xmldatabase'] : "xmldatabase";
    //--parametri ---->
    $bgcolorover = isset($params['bgcolorover']) ? $params['bgcolorover'] : "#ffff00";
    $defaultsParams = array(
        "charset_page" => "UTF-8" //charset page
        , "echo" => true           //print output
        , "table" => $tablename
        , "path" => ""
        , "lang" => "en"
        , "languages" => "en"
        , "restr" => false
        , "filters" => null
        , "fields" => false
        , "functionsview" => false
        , "show_translations" => false
        , "bkrow" => "#ffffff"
        , "bkrow2" => "#dfdfdf"
        , "bgcolorover" => "#ffff00"
        , "bkheader" => "#dfdfdf"
        , "bordercolor" => "#d8d8d8"
        , "textcolor" => "#000000"
        , "linkcolor" => "#000000"
        , "link" => ""
        , "link_listmode" => null
        , "enableview" => false
        , "enabledelete" => true
        , "enableedit" => true
        , "enablenew" => true
        , "enableexport" => false
        , "defaultorder" => false
        , "defaultorderdesc" => false
        , "isadmin" => null
        , "functioninsert" => ""
        , "oninsert" => false  //html on insert
        , "onupdate" => false  //html on update 
        , "function_on_insert" => ""
        , "function_on_update" => ""
        , "function_on_delete" => ""
        , "htmlgrid" => ""
        , "recordset" => false // force records
        , "function_grid" => "" // function grid
        , "forcevalues" => false       //force alwais
        , "forcefieldvalues" => ""     //force alias forcevalues
        , "forcevaluesinsert" => ""
        , "forcevaluesupdate" => ""
        , "forcenewvalues" => ""       //force if not set
        , "forceupdatevalues" => ""    //force if not set
        , "recordsperpage" =>  isset($params['recordsperpage']) ? $params['recordsperpage'] : ""
        , "requiredfieldsymbol" => "*"
        , "textrequiredfields" => XMLDB_i18n("required fields")
        , "textsave" => XMLDB_i18n("save")
        , "textnorecord" => XMLDB_i18n("no records found")
        , "textcancel" => XMLDB_i18n("cancel")
        , "textnew" => "[ new ]"
        , "textinsertok" => XMLDB_i18n("the data were successfully inserted")
        , "textupdateok" => XMLDB_i18n("the data were successfully updated")
        , "textinsertfail" => XMLDB_i18n("error insert record")
        , "textupdatefail" => XMLDB_i18n("error update record")
        , "textviewlist" => "&lt;&lt;&nbsp;" . "back" . ""
        , "textpages" => "Pages :"
        , "textview" => "view"
        , "textmodify" => XMLDB_i18n("modify")
        , "textdelete" => XMLDB_i18n("delete")
        , "textexitwithoutsaving" => XMLDB_i18n("want to exit without saving?")
        , "list_onsave" => 1
        , "list_oninsert" => ""
        , "list_onupdate" => ""
        , "html_template_grid" => "
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
        , "maxrows" => false
        , 'max_cell_text_lenght' => 40
        , "textexport" => XMLDB_i18n("export")
        , "urlexport" => ""
    );

    //dprint_r($params);
    $function_update = isset($params['function_update']) ? $params['function_update'] : false;
    $function_insert = isset($params['function_insert']) ? $params['function_insert'] : false;
    $function_delete = isset($params['function_delete']) ? $params['function_delete'] : false;
    $function_export = isset($params['function_export']) ? $params['function_export'] : false;
    $export_filename = !empty($params["export_filename"]) ? $params["export_filename"] : "export.csv";

    $textcancel = isset($params['textcancel']) ? $params['textcancel'] : "Cancel";
    $textnew = isset($params['textnew']) ? $params['textnew'] : "[ new ]";
    $textinsertok = isset($params['textinsertok']) ? $params['textinsertok'] : "the data were successfully inserted";
    $textupdateok = isset($params['textupdateok']) ? $params['textupdateok'] : "the data were successfully updated";
    $textinsertfail = isset($params['textinsertfail']) ? $params['textinsertfail'] : "error insert record";
    $textupdatefail = isset($params['textupdatefail']) ? $params['textupdatefail'] : "error update record";
    $textviewlist = isset($params['textviewlist']) ? $params['textviewlist'] : "&lt;&lt;&nbsp;" . "back" . "";
    $textpages = isset($params['textpages']) ? $params['textpages'] : "Pages :";
    $textview = isset($params['textview']) ? $params['textview'] : "view";
    $textmodify = isset($params['textmodify']) ? $params['textmodify'] : "modify";
    $textdelete = isset($params['textdelete']) ? $params['textdelete'] : "delete";
    $textexitwithoutsaving = isset($params['textexitwithoutsaving']) ? $params['textexitwithoutsaving'] : "want to exit without saving?";
    $list_oninsert = $list_onupdate = $list_onsave = isset($params['list_onsave']) ? $params['list_onsave'] : 1;
    $list_oninsert = isset($params['list_oninsert']) ? $params['list_oninsert'] : $list_onsave;
    $list_onupdate = isset($params['list_onupdate']) ? $params['list_onupdate'] : $list_onsave;

    $urlexport = isset($params['urlexport']) ? $params['urlexport'] : "";

    $html = "";
    $tname = $tablename;
    if (is_array($tablename))
    {
        if (isset($tablename['tablename']))
        {
            $tname = $tablename['tablename'];
        }
        else
        {
            die("tablename is not set");
        }
    }
    elseif (is_object($tablename))
    {
        $params['table'] = $tablename;
        $tablename = $tname = $tablename->tablename;
    }
    foreach ($defaultsParams as $key => $param)
    {
        $params[$key] = isset($params[$key]) ? $params[$key] : $defaultsParams[$key];
    }
    //dprint_r($params);
    $errors = array();
    $postgetkey = "__xdb_{$tname}"; //identificatico della tabella

    $path = isset($params['path']) ? $params['path'] : "";
    $lang = isset($params['lang']) ? $params['lang'] : "en";
    $languages = isset($params['languages']) ? $params['languages'] : "en";
    $restr = isset($params['restr']) ? $params['restr'] : false;
    $restr = isset($params['filters']) ? $params['filters'] : $restr;
    $table = isset($params['table']) ? $params['table'] : false;
    $fields = isset($params['fields']) ? $params['fields'] : false;
    $functionsview = isset($params['functionsview']) ? $params['functionsview'] : false;
    $show_translations = isset($params['show_translations']) ? $params['show_translations'] : false;
    $bgcolor = isset($params['bkrow']) ? $params['bkrow'] : "#ffffff";
    $bgcolor2 = isset($params['bkrow2']) ? $params['bkrow2'] : "#ffffff";
    $bgcolorover = isset($params['bgcolorover']) ? $params['bgcolorover'] : "#ffff00";
    $backgroundcolorheader = isset($params['bkheader']) ? $params['bkheader'] : "#dfdfdf";
    $bordercolor = isset($params['bordercolor']) ? $params['bordercolor'] : "#d8d8d8";
    $textcolor = isset($params['textcolor']) ? $params['textcolor'] : "#000000";
    $linkcolor = isset($params['linkcolor']) ? $params['linkcolor'] : "#000000";
    $flink = isset($params['link']) ? $params['link'] : "";
    $flink_listmode = isset($params['link_listmode']) ? $params['link_listmode'] : $params['link'];
    $flink_linkcancel = isset($params['link_cancel']) ? $params['link_cancel'] : "";
    $flink_linkanchor = isset($params['linkanchor']) ? $params['linkanchor'] : "";

    $enableview = isset($params['enableview']) ? $params['enableview'] : false;
    $enabledelete = isset($params['enabledelete']) ? $params['enabledelete'] : true;
    $enableedit = isset($params['enableedit']) ? $params['enableedit'] : true;
    $enablenew = isset($params['enablenew']) ? $params['enablenew'] : true;
    $enableexport = isset($params['enableexport']) ? $params['enableexport'] : false;
    $textexport = isset($params['textexport']) ? $params['textexport'] : "";
    $reverse = isset($params['defaultorderdesc']) ? $params['defaultorderdesc'] : false;
    $maxpages = isset($params['maxpages']) ? $params['maxpages'] : 100;
    //dprint_r($params);
    if (!isset($params['isadmin']))
        $params['isadmin'] = XMLDBEDITOR_IsAdmin();
    //function in insert form
    $functioninsert = isset($params['functioninsert']) ? $params['functioninsert'] : "";
    //html in insert form
    $oninsert = isset($params['oninsert']) ? $params['oninsert'] : "";
    $onupdate = isset($params['onupdate']) ? $params['onupdate'] : "";
    //function on insert form
    $function_on_insert = isset($params['function_on_insert']) ? $params['function_on_insert'] : "";

    $function_on_update = isset($params['function_on_update']) ? $params['function_on_update'] : "";
    $function_on_delete = isset($params['function_on_delete']) ? $params['function_on_delete'] : "";

    $htmlgrid = isset($params['htmlgrid']) ? $params['htmlgrid'] : "";

    //force values
    $forcevalues = isset($params['forcevalues']) ? $params['forcevalues'] : false;
    $forcevalues = !empty($params['forcefieldvalues']) ? $params['forcefieldvalues'] : $forcevalues;
    //force in insert
    $forcevaluesinsert = isset($params['forcevaluesinsert']) ? $params['forcevaluesinsert'] : false;
    //force in update
    $forcevaluesupdate = isset($params['forcevaluesupdate']) ? $params['forcevaluesupdate'] : false;
    //force in form insert
    $forcenewvalues = isset($params['forcenewvalues']) ? $params['forcenewvalues'] : true;
    //force in form update
    $forceupdatevalues = isset($params['forceupdatevalues']) ? $params['forceupdatevalues'] : true;
    $recordsperpage = isset($params['recordsperpage']) ? $params['recordsperpage'] : false;

    $requiredfieldsymbol = $params['requiredfieldsymbol'];
    $textrequiredfields = $params['textrequiredfields'];

    $textsave = isset($params['textsave']) ? $params['textsave'] : "save";
    $textnorecord = isset($params['textnorecord']) ? $params['textnorecord'] : "no records found";
    $optionsedit = array();
    if ($enableview)
        $optionsedit[] = "view";
    if ($enableedit)
        $optionsedit[] = "edit";
    if ($enabledelete)
        $optionsedit[] = "delete";
    $textcancel = isset($params['textcancel']) ? $params['textcancel'] : "Cancel";
    $textnew = isset($params['textnew']) ? $params['textnew'] : "[ new ]";
    $textinsertok = isset($params['textinsertok']) ? $params['textinsertok'] : "the data were successfully inserted";
    $textupdateok = isset($params['textupdateok']) ? $params['textupdateok'] : "the data were successfully updated";
    $textinsertfail = isset($params['textinsertfail']) ? $params['textinsertfail'] : "error insert record";
    $textupdatefail = isset($params['textupdatefail']) ? $params['textupdatefail'] : "error update record";
    $textviewlist = isset($params['textviewlist']) ? $params['textviewlist'] : "&lt;&lt;&nbsp;" . "back" . "";
    $textpages = isset($params['textpages']) ? $params['textpages'] : "Pages :";
    $textview = isset($params['textview']) ? $params['textview'] : "view";
    $textmodify = isset($params['textmodify']) ? $params['textmodify'] : "modify";
    $textdelete = isset($params['textdelete']) ? $params['textdelete'] : "delete";
    $textexitwithoutsaving = isset($params['textexitwithoutsaving']) ? $params['textexitwithoutsaving'] : "want to exit without saving?";

    $list_oninsert = $list_onupdate = $list_onsave = isset($params['list_onsave']) ? $params['list_onsave'] : 1;
    $list_oninsert = isset($params['list_oninsert']) ? $params['list_oninsert'] : $list_onsave;
    $list_onupdate = isset($params['list_onupdate']) ? $params['list_onupdate'] : $list_onsave;

    $params['maxrows'] = isset($params['maxrows']) ? $params['maxrows'] : false;

    //--parametri ----<
    //-----variabili da get --------->
    $opmod = isset($_GET["op_$postgetkey"]) ? htmlspecialchars($_GET["op_$postgetkey"]) : "";
    $pk = isset($_GET["pk_$postgetkey"]) ? ($_GET["pk_$postgetkey"]) : "";
    $page = isset($_GET["page_$postgetkey"]) ? htmlspecialchars($_GET["page_$postgetkey"]) : "";
    if ($page == "")
        $page = 1;

    $order = isset($_GET["order_$postgetkey"]) ? htmlspecialchars($_GET["order_$postgetkey"]) : "";

    if ($order != "")
    {
        $reverse = isset($_GET["desc_$postgetkey"]) ? htmlspecialchars($_GET["desc_$postgetkey"]) : "";
    }
    //-----variabili da get ---------<
    $tlink = "";
    $mlink = "?";
    if ($page > 1)
    {
        $mlink = "?page_$postgetkey=$page";
    }
    if (is_array($forcevalues))
        foreach ($forcevalues as $key => $value)
        {
            $tlink .= "&amp;" . $key . "=" . $value;
            $mlink .= "&amp;" . $key . "=" . $value;
        }
    if ($flink != "")
    {
        $tlink = XMLDB_editor_mergelink($tlink, $flink);
        $mlink = XMLDB_editor_mergelink($mlink, $flink);
    }


    //-----tabella --------->
    if (!is_object($table))
    {
        $paramsFRM = array();
        $paramsFRM['siteurl'] = isset($params['siteurl']) ? $params['siteurl'] : "";
        if (isset($params['charset_storage']))
            $paramsFRM['charset_storage'] = $params['charset_storage'];
        else
            $paramsFRM['charset_storage'] = "UTF-8";
        $table = new FieldFrm("$dbname", $tablename, $path, $lang, $languages, $paramsFRM);
    }
    $siteurl = "";
    if (isset($params['siteurl']))
    {
        $siteurl = $params['siteurl'];
    }
    $table->charset_page = isset($param['charset_page']) ? $param['charset_page'] : "";
    if (isset($params['layout']))
    {
        $table->SetLayout($params['layout']);
    }
    $layout_template = "";
    if (!empty($params['layout_template']))
    {

        $layout_template = $params['layout_template'];
        if (false !== strstr($layout_template, "<form") && false !== strstr($layout_template, "<!-- contents -->"))
        {
            $layout_template = $params['layout_template'];
            $params['layout_template'] = TPL_GetHtmlPart("contents", $layout_template);
        }

        $table->SetlayoutTemplate($params['layout_template']);
    }

    //filtro sulle foreignkey---->
    if (!empty($params['restrfk']))
    {
        if (is_array($params['restrfk']))
        {
            foreach ($params['restrfk'] as $k => $v)
            {
                if (isset($table->formvals[$k]))
                {
                    $table->formvals[$k]['fk_filter_field'] = $v;
                }
            }
        }
        else
        {
            foreach ($table->formvals as $k => $v)
            {
                if (!empty($table->formvals[$k]['foreignkey']))
                {
                    $table->formvals[$k]['fk_filter_field'] = $params['restrfk'];
                }
            }
        }
    }
    //filtro sulle foreignkey----<



    $scriptOnExit = "
<script type=\"text/javascript\">
function set_changed()
{
try{
    document.getElementById('exit_$postgetkey').setAttribute('onclick',
    'if(confirm (\"" . addslashes($textexitwithoutsaving) . "\")){window.location=\"" . str_replace("&amp;", "&", $tlink) . "\";}');
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
    if(confirm ('" . addslashes($textexitwithoutsaving) . "'))
    {
        return true;
    }
    return false;
}
</script>
	";
    if (isset($_POST['__NOSAVE']))
    {
        $scriptOnExit .= "
<script type=\"text/javascript\">
set_changed();
</script>";
    }


    //-----tabella --------->
    $toupdate = false;
    //----eliminazione del record
    if ($enabledelete && $opmod == "del")
    {
        $oldvalues = $table->xmltable->GetRecordByPk($pk);
        //insert record
        if ($function_delete)
        {
            ob_start();
            $function_delete($pk);
            $html .= ob_get_clean();
        }
        else
        {

            if ($function_on_delete && function_exists($function_on_delete))
            {
                ob_start();
                $function_on_delete($oldvalues);
                $html .= ob_get_clean();
            }
            $table->xmltable->DelRecord($pk);
        }
    }
    $endloop = false;
    $num_records = $table->xmltable->GetNumRecords($restr);
    if ($params['maxrows'] && $params['maxrows'] <= $num_records)
    {
        $enablenew = false;
    }
    //------filters------------------------------------------------------------>
    $filters_by_post = array();
    $fields_filters = isset($params['fields_filters']) ? $params['fields_filters'] : array();
    $fields_filters_names = array();
    foreach ($fields_filters as $k => $fields_filter)
    {
        $fields_filters_names[$k] = str_replace("%", "", $fields_filter);
    }
    $filters_post_exists = false;
    foreach ($table->formvals as $k => $v)
    {
        if (in_array($k, $fields_filters_names))
        {
            if (isset($_POST["search{$postgetkey}_" . $k]))
            {
                $filters_post_exists = true;
            }
            if (!empty($_POST["search{$postgetkey}_" . $k]))
            {
                $filters_by_post[$k] = FN_GetParam("search{$postgetkey}_" . $k, $_POST, "html");
            }
        }
    }
    $filters_by_get = json_decode(FN_GetParam("filter{$postgetkey}", $_GET, "flat"), JSON_OBJECT_AS_ARRAY);

    if (is_array($filters_by_get) && $filters_post_exists == false)
    {
        $array_filters = array_merge($filters_by_get, $filters_by_post);
    }
    else
    {
        $array_filters = $filters_by_post;
    }
    //------filters------------------------------------------------------------<


    $link_FiltersEncoded = json_encode($array_filters);
    $tplvars['textviewlist'] = $textviewlist;
    while ($endloop == false)
    {
        switch ($opmod)
        {
            //-------inserimento/aggiornamento record  --------->
            case "insnew" :

                $tplvars = array();
                $tplvars['text_on_update_fail'] = "";
                $tplvars['text_on_update_ok'] = "";
                $tplvars['text_on_insert_fail'] = "";
                $tplvars['text_on_insert_ok'] = "";

                $layout_template = $scriptOnExit . $layout_template;

                $endloop = true;

                $html .= "" . $scriptOnExit;
                $newvalues = $table->getbypost();

                if (is_array($forcevalues))
                {
                    foreach ($forcevalues as $k => $v)
                    {
                        $newvalues[$k] = $v;
                    }
                }

                if (is_array($pk) && count($pk) > 0 || $pk != "")
                {
                    $oldvalues = $table->xmltable->GetRecordByPk($pk);
                    $toupdate = true; //se esiste la chiave primaria il record ï¿½ da aggiornare
                }

                if ($toupdate && is_array($forcevaluesupdate))
                {
                    foreach ($forcevaluesupdate as $k => $v)
                    {
                        $newvalues[$k] = $v;
                    }
                }
                if (!$toupdate && is_array($forcevaluesinsert))
                {
                    foreach ($forcevaluesinsert as $k => $v)
                    {
                        $newvalues[$k] = $v;
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
                            $pknewvalues = array();
                            foreach ($table->xmltable->primarykey as $pkk)
                            {
                                $pknewvalues[$pkk] = $_POST[$pkk];
                            }
                            if ($table->xmltable->GetRecordByPk($pknewvalues))
                            {
                                $toupdate = true;
                            }
                        }
                    }
                    elseif (isset($_POST[$table->xmltable->primarykey]) && $pk)
                    {
                        if ($oldvalues_verify = $table->xmltable->GetRecordByPk($_POST[$table->xmltable->primarykey]))
                        {
                            $toupdate = true;
                        }
                    }
                    if ($toupdate && isset($oldvalues_verify) && is_array($oldvalues_verify))
                    {
                        $newvalues_verify = array_merge($oldvalues_verify, $newvalues);
                        $errors = $table->Verify($newvalues_verify, true);
                    }
                    else
                    {
                        $errors = $table->Verify($newvalues, $toupdate);
                    }
                    if (count($errors) == 0)
                    {
                        if ($toupdate)
                        {
                            if ($function_update)
                            {
                                $newvalues = $function_update($newvalues, $pk);
                            }
                            else
                                $newvalues = $table->UpdateRecord($newvalues, $pk);


                            if (is_array($table->xmltable->primarykey))
                            {
                                $pk = array();
                                foreach ($table->xmltable->primarykey as $pkv)
                                {
                                    $pk[$pkv] = $newvalues[$pkv];
                                }
                            }
                            else
                            {
                                $pk = $newvalues[$table->xmltable->primarykey];
                            }
                            if ($onupdate != "")
                            {
                                break;
                            }
                            if (is_array($newvalues))
                            {
                                if ($function_on_update && function_exists($function_on_update))
                                {
                                    ob_start();
                                    $function_on_update($newvalues, $oldvalues);
                                    $html .= ob_get_clean();
                                }
                                $tplvars['text_on_update_ok'] = $textupdateok;
                                $html = XMLDBEDITOR_HtmlAlert($textupdateok) . $html;
                                if ($list_onupdate)
                                {
                                    $endloop = false;
                                    $opmod = "";
                                    break;
                                }
                            }
                            else
                            {
                                $tplvars['text_on_update_fail'] = $textupdateok;
                                $html = XMLDBEDITOR_HtmlAlert($textupdatefail) . $html;
                            }
                        }
                        else
                        {
                            //insert record
                            if ($function_insert)
                            {
                                $newvalues = $function_insert($newvalues);
                            }
                            else
                            {
                                $newvalues = $table->InsertRecord($newvalues);
                            }

                            if (is_array($newvalues) && count($newvalues) > 0)
                            {
                                $oldvalues = $newvalues;
                                $pk = $newvalues[$table->xmltable->primarykey];
                                if ($function_on_insert && function_exists($function_on_insert))
                                {

                                    ob_start();
                                    $function_on_insert($newvalues);
                                    $tmp_html = ob_get_clean();
                                    $html .= $tmp_html;
                                    $tplvars['html_on_insert'] = $tmp_html;
                                }
                                if ($oninsert != "")
                                {
                                    $html .= $oninsert;
                                    $tplvars['oninsert'] = $oninsert;
                                    break;
                                }
                                $html = XMLDBEDITOR_HtmlAlert($textinsertok) . $html;
                                $tplvars['text_on_insert_ok'] = $textinsertok;
                                $toupdate = true;
                                if (!$enableedit)
                                {
                                    $list_oninsert = true;
                                }
                            }
                            else
                            {
                                $html = XMLDBEDITOR_HtmlAlert($textinsertfail) . $html;
                                $tplvars['text_on_insert_fail'] = $textinsertfail;
                                $toupdate = false;
                                // break;
                            }
                            if ($list_oninsert)
                            {
                                $endloop = false;
                                $opmod = "";
                                break;
                            }
                        }
                    }
                }

                $tplvars['symbol_requiredfield'] = "$requiredfieldsymbol";
                $tplvars['text_requiredfields'] = "";

                $html .= "$requiredfieldsymbol $textrequiredfields";
                $html .= "

";
                //---------- $httpqueryparams ins new-->
                $httpqueryparams = array();
                if (is_array($table->xmltable->primarykey))
                {
                    $httpqueryparams["pk_{$postgetkey}"] = array();
                    foreach ($table->xmltable->primarykey as $pkfield)
                    {
                        if (isset($oldvalues[$pkfield]))
                            $httpqueryparams["pk_{$postgetkey}"] [$pkfield] = $oldvalues[$pkfield];
                    }
                }
                else
                {
                    if (isset($oldvalues[$table->xmltable->primarykey]))
                        $httpqueryparams["pk_{$postgetkey}"] = $oldvalues[$table->xmltable->primarykey];
                }
                $httpqueryparams["page_$postgetkey"] = $page;
                $httpqueryparams["op_$postgetkey"] = $page;
                $httpqueryparams["desc_"] = $reverse;
                $httpqueryparams["order_$postgetkey"] = $order;
                /*
                  dprint_r($toupdate);
                  dprint_r($newvalues);
                  dprint_r($oldvalues);
                  dprint_r($pk);
                  dprint_r($httpqueryparams);
                 */
                $urlquery = (http_build_query($httpqueryparams));

                //---------- $httpqueryparams ins new--<             
                $link_ = XMLDB_editor_mergelink($tlink, "$urlquery&amp;op_$postgetkey=insnew");
                $tplvars['action'] = $link_;
                $tplvars['form_onchange'] = "set_changed()";
//
                $html .= "<form onchange=\"set_changed()\" enctype=\"multipart/form-data\" action=\"$link_\" method=\"post\"><div>\n";
                if ($toupdate)
                {
                    $html .= "";

                    if (is_array($forceupdatevalues))
                        foreach ($forceupdatevalues as $fok => $fov)
                        {
                            if (!isset($_POST[$fok]))
                            {
                                $newvalues[$fok] = $fov;
                            }
                        }

                    $htmlform = $table->HtmlShowUpdateForm($pk, $params['isadmin'], $newvalues, $errors);
                    $tplvars['htmlform'] = $htmlform;
                    $html .= $htmlform;
                }
                else
                {
                    if (is_array($forcenewvalues))
                        foreach ($forcenewvalues as $fok => $fov)
                        {
                            if (!isset($_POST[$fok]))
                            {
                                $newvalues[$fok] = $fov;
                            }
                        }
                    $htmlform = $table->HtmlShowInsertForm($params['isadmin'], $newvalues, $errors);
                    $html .= $htmlform;
                    $tplvars['htmlform'] = $htmlform;
                }
                $tplvars['html_on_insert'] = "";
                if ($functioninsert != "" && function_exists($functioninsert))
                {
                    ob_start();
                    $functioninsert($newvalues);
                    $tmp_html = ob_get_clean();
                    $html .= $tmp_html;
                    $tplvars['html_on_insert'] = $tmp_html;
                }
                $hiddenfield = "<input style=\"display:none\" id=\"frm$postgetkey\" type=\"hidden\" name=\"save_$postgetkey\" value=\"$postgetkey\"/>";
                $html .= "<br />$hiddenfield<button  type=\"submit\"   >" . $textsave . "</button>";
                //dprint_r($tplvars);
                $tplvars['text_cancel'] = $textcancel;
                $link_modify = "";
                $link_view = "";

                if ($enableedit)
                {
                    $link_modify = XMLDB_editor_mergelink($mlink, "$urlquery&amp;op_$postgetkey=insnew&amp;filter{$postgetkey}=$link_FiltersEncoded");
                }
                if ($enableview)
                {
                    $link_view = XMLDB_editor_mergelink($mlink, "$urlquery&amp;op_$postgetkey=view&amp;filter{$postgetkey}=$link_FiltersEncoded");
                }

                $tplvars['link_modify'] = $link_modify;
                $tplvars['link_view'] = $link_view;

                if ($textcancel != "")
                {
                    $link_ = XMLDB_editor_mergelink("", $mlink);
                    if ($flink_linkcancel)
                    {
                        $link_ = XMLDB_editor_mergelink("", $flink_linkcancel);
                    }
                    $link_ .= $flink_linkanchor;
                    $tplvars['url_cancel'] = $link_;

                    $html .= "&nbsp;<button id='exit_$postgetkey' type=\"button\" onclick=\"window.location='" . str_replace("&amp;", "&", $link_) . "'\" >" . $textcancel . "</button>";
                }
                $html .= "\n</div></form>";

                if ($textviewlist)
                {
                    $link_textviewlist = XMLDB_editor_mergelink($flink_listmode, "?page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse&amp;filter{$postgetkey}=$link_FiltersEncoded");
                    $html .= "<br /><a  href=\"$link_textviewlist$flink_linkanchor\">$textviewlist</a> ";
                }

                if (false !== strstr($layout_template, "<form"))
                {
                    $layout_template = TPL_ReplaceHtmlPart("contents", "{htmlform}$hiddenfield", $layout_template);
                    $html = TPL_ApplyTplString($layout_template, $tplvars, "", $params);
                }
                $return = $params;
                break;
            //-------inserimento/aggiornamento record  ---------<
            case "view" :
                $endloop = true;
                if (isset($params['layout_view']))
                {
                    $table->SetLayoutView($params['layout_view']);
                }
                $basepath_template_dir = "";
                if (isset($params['html_template_view']))
                {
                    if (strlen($params['html_template_view']) < 255 && file_exists($params['html_template_view']))
                    {
                        $basepath_template_dir = dirname($params['html_template_view']);
                        $params['html_template_view'] = file_get_contents($params['html_template_view']);
                    }
                    $table->SetlayoutTemplateView($params['html_template_view']);
                }
                $html .= $table->HtmlShowView($table->GetRecordTranslatedByPrimarykey($pk));
                $linkviewlist = XMLDB_editor_mergelink($flink_listmode, "?page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse&amp;filter{$postgetkey}=$link_FiltersEncoded");
                $tplvars["link_view_list"] = $linkviewlist . $flink_linkanchor;
                $html .= "<br /><br /><a style=\"color:$linkcolor\" href=\"$linkviewlist$flink_linkanchor\">$textviewlist</a><br /><br />";

                $link_modify = "";
                $link_view = "";
                $httpqueryparams = array();
                $httpqueryparams["pk_{$postgetkey}"] = $pk;
                $httpqueryparams["page_$postgetkey"] = $page;
                $httpqueryparams["op_$postgetkey"] = $page;
                $httpqueryparams["desc_"] = $reverse;
                $httpqueryparams["order_$postgetkey"] = $order;
                $urlquery = (http_build_query($httpqueryparams));
                if ($enableedit)
                {
                    $link_modify = XMLDB_editor_mergelink($mlink, "$urlquery&amp;op_$postgetkey=insnew&amp;filter{$postgetkey}=$link_FiltersEncoded");
                }
                if ($enableview)
                {
                    $link_view = XMLDB_editor_mergelink($mlink, "$urlquery&amp;op_$postgetkey=view&amp;filter{$postgetkey}=$link_FiltersEncoded");
                }
                $tplvars['link_modify'] = $link_modify;
                $tplvars['link_view'] = $link_view;

                if (isset($params['html_template_view']) && false !== strstr($params['html_template_view'], "<!-- if "))
                {
                    $layout_template_view = $params['html_template_view'];
                    if (false !== strstr($params['html_template_view'], "<!-- contents -->"))
                    {
                        $table->SetlayoutTemplateView(TPL_GetHtmlPart("contents", $params['html_template_view']));
                        $layout_template_view = TPL_ReplaceHtmlPart("contents", $table->HtmlShowView($table->GetRecordTranslatedByPrimarykey($pk)), $layout_template_view);
                    }
                    $html = TPL_ApplyTplString($layout_template_view, $tplvars, "$basepath_template_dir", $params);
                }
                $return = $params;

                break;
            default :
                $tplvars = array();
                //---------------------------GRID------------------------------>
                $endloop = true;
                if (!empty($params['function_grid']) && function_exists($params['function_grid']))
                {
                    $params['function_grid']();
                    break;
                }
                //---------------------template grid--------------------------------------->
                //----filters by get and post---------------------------------------------->
                if (isset($params['html_template_grid']))
                {
                    if (strlen($params['html_template_grid']) < 255 && file_exists($params['html_template_grid']))
                    {
                        if (empty($params['template_path']))
                            $params['template_path'] = dirname($params['html_template_grid']);
                        $params['html_template_grid'] = file_get_contents($params['html_template_grid']);
                    }
                }

                $tplvars['filters'] = array();
                //dprint_r($array_filters);
                //dprint_r($fields_filters);
                //dprint_r($fields_filters_names);

                foreach ($table->formvals as $k => $v)
                {
                    $itemfilter = array();
                    if (in_array($k, $fields_filters_names))
                    {
                        $itemfilter['title'] = $table->formvals[$k]['title'];
                        $itemfilter['name'] = "search{$postgetkey}_$k";
                        $itemfilter['value'] = isset($array_filters[$k]) ? $array_filters[$k] : "";
                        $itemfilter['type_text'] = true;
                        $itemfilter['type_select'] = false;
                        if (is_array($v['options']))
                        {

                            foreach ($v['options'] as $ko => $vo)
                            {
                                $v['options'][$ko]['selected'] = false;
                                if ($vo['value'] == $itemfilter['value'])
                                {
                                    $v['options'][$ko]['selected'] = true;
                                }
                            }
                            $itemfilter['options'] = $v['options'];
                        }
                        $tplvars['filters'][] = $itemfilter;
                    }
                }
                if (is_array($array_filters))
                {
                    foreach ($array_filters as $filter_name => $v)
                    {
                        foreach ($fields_filters_names as $keyid => $val)
                        {

                            if ($val == $filter_name)
                            {
                                $v = $fields_filters[$keyid];
                                $tosearch = $array_filters[$filter_name];
                                if ($v[0] == "%" && $v[strlen($v) - 1] == "%")
                                {
                                    $array_filters[$filter_name] = "%{$tosearch}%";
                                }
                                elseif ($v[0] != "%" && $v[strlen($v) - 1] == "%")
                                {
                                    $array_filters[$filter_name] = "{$tosearch}%";
                                }
                                elseif ($v[0] == "%" && $v[strlen($v) - 1] != "%")
                                {
                                    $array_filters[$filter_name] = "%{$tosearch}";
                                }
                                else
                                {
                                    $array_filters[$filter_name] = $tosearch;
                                }
                            }
                        }
                    }
                    if (is_array($restr))
                    {
                        $restr = array_merge($restr, $array_filters);
                    }
                    else
                    {
                        $restr = $array_filters;
                    }
                }
                $tplvars['urlreset'] = XMLDB_editor_mergelink($mlink, "?page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse&amp;filter{$postgetkey}=");
                $tplvars['actionfilters'] = XMLDB_editor_mergelink($mlink, "?page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse&amp;filter{$postgetkey}=");

//----filters by get and post----------------------------------------------<



                preg_match('/(<!-- start insertnew -->)(.*)(<!-- end insertnew -->)/is', $params['html_template_grid'], $out);
                $template_insertnew = empty($out[2]) ? "" : $out[2];
                $htmlnewrecord = "";
                if ($enablenew)
                {
                    $link_newrecord = XMLDB_editor_mergelink($mlink, "?page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse&amp;op_{$postgetkey}=insnew&amp;filter{$postgetkey}=$link_FiltersEncoded");
                    $tplvars['urlnewrecord'] = $link_newrecord;
                    $htmlnewrecord = str_replace("{urlnewrecord}", "$link_newrecord", $template_insertnew);
                    $htmlnewrecord = str_replace("{textnew}", $textnew, $htmlnewrecord);
                }
                $params['html_template_grid'] = str_replace($template_insertnew, $htmlnewrecord, $params['html_template_grid']);
                $params['html_template_grid'] = str_replace("{htmlnewrecord}", $htmlnewrecord, $params['html_template_grid']);
                preg_match('/(<!-- start export -->)(.*)(<!-- end export -->)/is', $params['html_template_grid'], $out);
                $template_export = empty($out[2]) ? "" : $out[2];
                $htmlexport = "";
                if ($enableexport)
                {
                    $urlexport = !empty($urlexport) ? $urlexport : XMLDB_editor_mergelink($mlink, "?page_$postgetkey=$page&amp;order_$postgetkey=$order&amp;desc_$postgetkey=$reverse&amp;op_{$postgetkey}=export&amp;filter{$postgetkey}=$link_FiltersEncoded");
                    $htmlexport = str_replace("{urlexport}", "$urlexport", $template_export);
                    $htmlexport = str_replace("{textexport}", "$textexport", $htmlexport);
                    $params['urlexport'] = $urlexport;
                }
                $params['html_template_grid'] = str_replace($template_export, $htmlexport, $params['html_template_grid']);
                $params['html_template_grid'] = str_replace("{htmlexport}", $htmlexport, $params['html_template_grid']);
                preg_match('/(<!-- start grid -->)(.*)(<!-- end grid -->)/is', $params['html_template_grid'], $out);
                $template_grid = empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start pages -->)(.*)(<!-- end pages -->)/is', $params['html_template_grid'], $out);
                $template_pages = empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start page -->)(.*)(<!-- end page -->)/is', $template_pages, $out);
                $template_page = empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start currentpage -->)(.*)(<!-- end currentpage -->)/is', $template_pages, $out);
                $template_currentpage = empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start gridheader -->)(.*)(<!-- end gridheader -->)/is', $params['html_template_grid'], $out);
                $template_gridheader = empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start table -->)(.*)(<!-- end table -->)/is', $params['html_template_grid'], $out);
                $template_table = empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start gridrow -->)(.*)(<!-- end gridrow -->)/is', $template_gridheader, $out);
                $template_gridheader_gridrow = empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start gridfield -->)(.*)(<!-- end gridfield -->)/is', $template_gridheader, $out);
                $template_gridheader_gridrow_gridfield = empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start gridbody -->)(.*)(<!-- end gridbody -->)/is', $params['html_template_grid'], $out);
                $template_gridbody = empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start gridrow -->)(.*)(<!-- end gridrow -->)/is', $template_gridbody, $out);
                $template_gridbody_gridrow = empty($out[2]) ? "" : $out[2];
                preg_match('/(<!-- start gridfield -->)(.*)(<!-- end gridfield -->)/is', $template_gridbody, $out);
                $template_gridbody_gridrow_gridfield = empty($out[2]) ? "" : $out[2];
                //---------------------template grid---------------------------------------<

                $tplvars['nav_pages'] = array();
                $tplvars['nav_page_next'] = false;
                $tplvars['nav_page_prev'] = false;
                $tplvars['nav_page_first'] = false;
                $tplvars['nav_page_last'] = false;
                $tplvars['nav_page_current'] = false;
                $tmp_nav_pages = array();

                if ($htmlgrid)
                    $html .= $htmlgrid;
                else
                {
                    $fieldstoread = $__fieldstoread = array();
                    if ($fields != false && !is_array($fields))
                    {
                        $fields = explode("|", $fields);
                        $__fieldstoread = $fields;
                        if (!isset($__fieldstoread[$table->xmltable->primarykey]))
                        {
                            $__fieldstoread[] = $table->xmltable->primarykey;
                        }
                    }
                    if ($__fieldstoread)
                    {
                        foreach ($__fieldstoread as $fieldname)
                        {
                            $fieldname = preg_replace("/\[.*\]/s", "", $fieldname);
                            if (isset($table->xmltable->fields[$fieldname]))
                            {
                                $fieldstoread[] = $fieldname;
                            }
                        }
                    }
                    //dprint_r($fields);
                    if ($params['defaultorder'] == false)
                        $params['defaultorder'] = is_array($table->xmltable->primarykey) ? $table->xmltable->primarykey[0] : $table->xmltable->primarykey;
                    if ($order == false)
                    {
                        $order = $params['defaultorder'];
                        //$reverse = isset($params['defaultorderdesc']) ? $params['defaultorderdesc'] : false;
                    }
                    $order = preg_replace("/\[.*\]/s", "", $order);
                    $numPages = 1;
                    $all = false;
                    if (isset($params['recordset']) && is_array($params['recordset']))
                    {
                        $all_nofilters = $params['recordset'];
                        if ($restr)
                        {
                            $all = array();
                            foreach ($all_nofilters as $item_nofilters)
                            {
                                foreach ($restr as $fileldname => $fieldrestr)
                                {
                                    if (xmldb_strlike($item_nofilters[$fileldname], $fieldrestr))
                                    {
                                        $all[] = $item_nofilters;
                                        break;
                                    }
                                }
                            }
                        }
                        else
                        {
                            $all = $params['recordset'];
                        }
                        $num_records = count($all);
                    }
                    else
                    {
                        $num_records = $table->xmltable->GetNumRecords($restr);
                    }
                    if (intval($recordsperpage) != false)
                    {
                        $numPages = ceil($num_records / $recordsperpage);
                        if ($page > $numPages)
                            $page = $numPages;
                        $start = ($page * $recordsperpage - $recordsperpage) + 1;
                        $end = $start + $recordsperpage - 1;
                        $end = min($end, $num_records);
                    }
                    else
                    {
                        $start = 1;
                        $end = $num_records;
                    }
                    if ($all === false)
                    {
                        $all = $table->xmltable->GetRecords($restr, false, false, false, false, $fieldstoread);
                    }
                    if ($opmod == "export")
                    {
                        if (isset($function_export) && function_exists($function_export))
                        {
                            $function_export($all);
                        }
                        else
                        {
                            xmldb_export_csv($all, $export_filename);
                        }
                    }
                    if ($num_records > 0)
                        $txt_num_records = "$start-" . ($end) . " " . XMLDB_i18n("of", "aa") . " " . $num_records;
                    else
                        $txt_num_records = "0-0" . " " . XMLDB_i18n("of", "aa") . " 0";
                    $htmlpages = "";
                    $link_prevpage = "";
                    $link_nextpage = "";
                    $cp = false;
                    if ($recordsperpage && $numPages > 1)
                    {
                        $page_display_first = 1;
                        if ($numPages > $maxpages && $page > 1)
                        {
                            $page_display_first = $page;
                            if (($numPages - $maxpages) > 0 && ($numPages - $maxpages) < $page_display_first)
                            {
                                $page_display_first = $numPages - $maxpages + 1;
                            }
                        }
                        $cp = 1;
                        if ($page_display_first > 1)
                        {
                            //first page --->
                            $s = array("{pagelink}", "{pagetitle}");
                            $r = array();
                            $link_ = XMLDB_editor_mergelink($flink_listmode, "?page_{$postgetkey}=1&amp;order_{$postgetkey}=$order&amp;desc_{$postgetkey}=$reverse&amp;filter{$postgetkey}=$link_FiltersEncoded");
                            $link_ .= $flink_linkanchor;
                            $r['pagelink'] = "$link_";
                            $r['pagetitle'] = "&lt;&lt;";
                            $htmlpages .= str_replace($s, $r, $template_page);
                            $tplvars['nav_page_first'] = array("title" => $r['pagetitle'], "link" => $r['pagelink']);
                            //end first page ---<
                            //prev page --->
                            $s = array("{pagelink}", "{pagetitle}");
                            $r = array();
                            $link_ = XMLDB_editor_mergelink($flink_listmode, "?page_{$postgetkey}=" . ($page - 1) . "&amp;order_{$postgetkey}=$order&amp;desc_{$postgetkey}=$reverse&amp;filter{$postgetkey}=$link_FiltersEncoded");
                            $link_ .= $flink_linkanchor;
                            $r['pagelink'] = "$link_";
                            $r['pagetitle'] = "&lt;";
                            $htmlpages .= str_replace($s, $r, $template_page);
                            $tplvars['nav_page_prev'] = array("title" => $r['pagetitle'], "link" => $r['pagelink']);
                            //end prev page ---<
                        }
                        for ($i = $page_display_first; $i <= $numPages; $i++)
                        {
                            $s = array("{pagelink}", "{pagetitle}");
                            $r = array();
                            $link_ = XMLDB_editor_mergelink($flink_listmode, "?page_{$postgetkey}=$i&amp;order_{$postgetkey}=$order&amp;desc_{$postgetkey}=$reverse&amp;filter{$postgetkey}=$link_FiltersEncoded");
                            $link_ .= $flink_linkanchor;
                            $r['pagelink'] = "$link_";
                            $r['pagetitle'] = $i;
                            $tmp_nav_page['link'] = $link_;
                            $tmp_nav_page['title'] = $i;
                            $tmp_nav_page['current'] = false;
                            if ($page == $i)
                            {
                                $tmp_nav_page['current'] = true;
                                $htmlpages .= str_replace($s, $r, $template_currentpage);
                                $tplvars['nav_page_current'] = array("title" => $r['pagetitle'], "link" => $r['pagelink']);
                            }
                            else
                                $htmlpages .= str_replace($s, $r, $template_page);
                            if ($cp > $maxpages)
                            {
                                break;
                            }
                            $cp++;
                            $tmp_nav_pages[] = $tmp_nav_page;
                        }
                    }
                    $tplvars['nav_pages'] = $tmp_nav_pages;

                    //dprint_r("page=$page cp=$cp numpages=$numPages num_records=$num_records");
                    if ($numPages > $maxpages && $page < $numPages)
                    {
                        //next page --->
                        $s = array("{pagelink}", "{pagetitle}");
                        $r = array();
                        $r['pagelink'] = XMLDB_editor_mergelink($flink_listmode, "?page_{$postgetkey}=" . ($page + 1) . "&amp;order_{$postgetkey}=$order&amp;desc_{$postgetkey}=$reverse&amp;filter{$postgetkey}=$link_FiltersEncoded");
                        $r['pagetitle'] = "&gt;";
                        $htmlpages .= str_replace($s, $r, $template_page);
                        $tplvars['nav_page_next'] = array("title" => $r['pagetitle'], "link" => $r['pagelink']);
                        //end next page ---<
                        //last page --->
                        $s = array("{pagelink}", "{pagetitle}");
                        $r = array();
                        $link_ = XMLDB_editor_mergelink("$flink_listmode", "?page_{$postgetkey}=$numPages&amp;order_{$postgetkey}=$order&amp;desc_{$postgetkey}=$reverse&amp;filter{$postgetkey}=$link_FiltersEncoded");
                        $link_ .= $flink_linkanchor;
                        $r['pagelink'] = "$link_";
                        $r['pagetitle'] = "&gt;&gt;";
                        $htmlpages .= str_replace($s, $r, $template_page);
                        $tplvars['nav_page_last'] = array("title" => $r['pagetitle'], "link" => $r['pagelink']);
                        //end last page ---<
                    }
                    $htmlpages_full = $htmlpages;

                    //----------------------header----------------------------->
                    $html_GridHeader = "";
                    $orderfield = array();

                    if ($fields)
                    {
                        foreach ($fields as $ofield)
                        {
                            //$ofield=preg_replace("/\[.*\]/s","",$ofield);
                            if (isset($table->formvals[$ofield]))
                            {
                                $orderfield[$ofield] = $table->formvals[$ofield];
                            }
                            else
                            {
                                $tf = "";
                                if (strstr($ofield, "]"))
                                {
                                    $tf = explode("]", $ofield);
                                    $__ofield = $tf[1];
                                    $orderfield[$__ofield] = array();
                                    $orderfield[$__ofield] = isset($table->formvals[$__ofield]) ? $table->formvals[$__ofield] : array();
                                    $tf = $tf[0];
                                    $tf = str_replace("[", "", $tf);
                                    $orderfield[$__ofield]['title'] = $tf;
                                }
                                else
                                    $orderfield[$ofield] = "";
                            }
                        }
                    }
                    else
                        $orderfield = $table->formvals;
                    $numcols = 0;

                    $tplvars['headers'] = array();
                    $numcol = 0;
                    foreach ($orderfield as $key => $value)
                    {
                        $numcol++;
                        $tmp_header = is_array($value) ? $value : array("title" => "", "value" => $value);
                        $tmp_header['id'] = $key;
                        $tmp_header['arrow'] = "";
                        if ($fields && !isset($orderfield[$key]))
                        {
                            continue;
                        }
                        if ($show_translations == true || !isset($table->formvals[$key]['frm_multilanguage']) || $table->formvals[$key]['frm_multilanguage'] != "1")
                        {
                            $numcols++;
                            $key = preg_replace("/\[.*\]/s", "", $key);
                            if (isset($value['title']))
                            {
                                $rev = "";
                                $t = "";
                                $desclink = "";
                                if ($order == $key)
                                {
                                    if (false === strstr("<", $value['title']))
                                    {
                                        if ($reverse)
                                        {
                                            $t = "<img style=\"vertical-align:middle;float:right\" src=\"{$siteurl}images/fn_up.png\" alt=\"\" />";
                                            $desclink = "";
                                        }
                                        else
                                        {
                                            $t = "<img style=\"vertical-align:middle;float:right\" src=\"{$siteurl}images/fn_down.png\" alt=\"\" />";
                                            $desclink = "&amp;desc_{$postgetkey}=$key";
                                        }
                                    }
                                }
                                $tmp_html = str_replace("{fieldvalue}", "" . $value['title'], $template_gridheader_gridrow_gridfield);
                                $link_ = XMLDB_editor_mergelink("$tlink", "?order_{$postgetkey}=$key{$desclink}&amp;filter{$postgetkey}=$link_FiltersEncoded");
                                $link_ .= $flink_linkanchor;
                                if (false !== strstr("<", $value['title']))
                                {
                                    $link_ = "#";
                                }

                                $tmp_html = str_replace("{link}", "$link_", $tmp_html);
                                $tmp_html = str_replace("{numcols}", $numcols, $tmp_html);
                                $tmp_html = str_replace("{numcol}", $numcol, $tmp_html);
                                $tmp_html = str_replace("{arrow}", $t, $tmp_html);
                                $html_GridHeader .= $tmp_html;
                                $tmp_header['link'] = $link_;
                                $tmp_header['numcols'] = $numcols;
                                $tmp_header['numcol'] = $numcol;
                                $tmp_header['arrow'] = $t;
                            }
                            else
                            {
                                $tmp_html = str_replace("{fieldvalue}", "", $template_gridheader_gridrow_gridfield);
                                $tmp_html = str_replace("{arrow}", "", $tmp_html);

                                $html_GridHeader .= $tmp_html;
                            }
                        }
                        $tplvars['headers'][] = $tmp_header;
                    }


                    $tplvars['numactions'] = count($optionsedit);
                    $template_gridheader_gridrow = str_replace("{numactions}", count($optionsedit), $template_gridheader_gridrow);
                    $template_gridheader_gridrow = str_replace("{bkheader}", $backgroundcolorheader, $template_gridheader_gridrow);

                    $html_GridRow = preg_replace('/(<!-- start gridfields -->)(.*)(<!-- end gridfields -->)/is', xmldb_encode_preg_replace2nd($html_GridHeader), $template_gridheader_gridrow);
                    $html_GridHeader = preg_replace('/(<!-- start gridrow -->)(.*)(<!-- end gridrow -->)/is', xmldb_encode_preg_replace2nd($template_gridheader_gridrow), $html_GridRow);

                    //----------------------header-----------------------------<
                    $i = 1;
                    static $tablefk = array();
                    $html_gridbody = "";
                    $tplvars['rows'] = array();

                    //----order------------------------------------------------>
                    if (is_array($all) && count($all) > 0)
                    {
                        foreach ($all as $idk => $row)
                        {
                            foreach ($orderfield as $key => $field)
                            {

                                $kfunction = (false !== strpos($key, "()")) ? str_replace("()", "", $key) : false;
                                if (false !== strpos($kfunction, "]"))
                                {
                                    $kfunction = explode("]", $kfunction);
                                    $kfunction = $kfunction[1];
                                }
                                if (function_exists($kfunction))
                                {
                                    $value = $kfunction($row[$table->xmltable->primarykey], $table, $row);
                                    $all[$idk][$kfunction] = ($value);
                                }
                            }
                        }
                    }
                    if ($order != false)
                    {
                        if (is_array($all))
                        {
                            $order = str_replace("()", "", $order);
                            $all = xmldb_array_natsort_by_key($all, str_replace("{$postgetkey}", "", $order));
                        }
                    }
                    if ($reverse == true && is_array($all))
                    {
                        $all = array_reverse($all);
                    }
                    //----order------------------------------------------------<

                    if (is_array($all) && count($all) > 0)
                    {

                        foreach ($all as $idk => $row)
                        {

                            $tmp_row = array();

                            $html_gridrow = "";
                            $backgroundcolor = ($i % 2 == 0) ? $bgcolor : $bgcolor2;
                            //start - end --->
                            if ($i >= $start && $i <= $end)
                            {
                                //dprint_r("($i >= $start && $i <= $end)");
                                //start -end ---<
                                $html_gridfields = "";
                                $httpqueryparams = array();
                                if (is_array($table->xmltable->primarykey))
                                {
                                    $httpqueryparams["pk_{$postgetkey}"] = array();
                                    foreach ($table->xmltable->primarykey as $pkfield)
                                    {
                                        $httpqueryparams["pk_{$postgetkey}"] [$pkfield] = $row[$pkfield];
                                    }
                                }
                                else
                                {
                                    $httpqueryparams["pk_{$postgetkey}"] = $row[$table->xmltable->primarykey];
                                }
                                $httpqueryparams["page_$postgetkey"] = $page;
                                $httpqueryparams["desc_"] = $reverse;
                                $httpqueryparams["order_$postgetkey"] = $order;
                                $urlquery = (http_build_query($httpqueryparams));
                                //---------- $httpqueryparams actions--<



                                $tmp_row['action_view'] = array();
                                if ($enableview)
                                {
                                    $link_ = XMLDB_editor_mergelink($mlink, "$urlquery&amp;op_$postgetkey=view&amp;filter{$postgetkey}=$link_FiltersEncoded");
                                    $link_ .= $flink_linkanchor;
                                    $tmp_row['action_view']['link'] = $link_;
                                    $tmp_row['action_view']['html'] = $textview;
                                    $tmp_row['action_view']['text'] = strip_tags($textview);
                                }
                                if ($enableedit)
                                {
                                    $link_ = XMLDB_editor_mergelink($mlink, "$urlquery&amp;op_$postgetkey=insnew&amp;filter{$postgetkey}=$link_FiltersEncoded");
                                    $link_ .= $flink_linkanchor;
                                    $tmp_row['action_edit']['link'] = $link_;
                                    $tmp_row['action_edit']['html'] = $textmodify;
                                    $tmp_row['action_edit']['text'] = strip_tags($textmodify);
                                }
                                if ($enabledelete)
                                {
                                    $link_ = XMLDB_editor_mergelink($mlink, "$urlquery&op_$postgetkey=del&amp;filter{$postgetkey}=$link_FiltersEncoded");
                                    $link_ .= $flink_linkanchor;
                                    $tmp_row['action_delete']['link'] = $link_;
                                    $tmp_row['action_delete']['html'] = $textdelete;
                                    $tmp_row['action_delete']['text'] = strip_tags($textdelete);
                                }


                                if (!empty($params['actions_before_fields']))
                                {
                                    if ($enableview && $numcols++)
                                    {
                                        $link_ = XMLDB_editor_mergelink($mlink, "$urlquery&amp;op_$postgetkey=view&amp;filter{$postgetkey}=$link_FiltersEncoded");
                                        $html_gridfields .= str_replace("{fieldvalue}", "<a href=\"$link_\">" . $textview . "</a>", $template_gridbody_gridrow_gridfield);
                                    }
                                    if ($enableedit && $numcols++)
                                    {
                                        $link_ = XMLDB_editor_mergelink($mlink, "$urlquery&amp;op_$postgetkey=insnew&amp;filter{$postgetkey}=$link_FiltersEncoded");
                                        $html_gridfields .= str_replace("{fieldvalue}", "<a href=\"$link_\">" . $textmodify . "</a>", $template_gridbody_gridrow_gridfield);
                                    }
                                    if ($enabledelete && $numcols++)
                                    {
                                        $link_ = XMLDB_editor_mergelink($mlink, "$urlquery&op_$postgetkey=del&amp;filter{$postgetkey}=$link_FiltersEncoded");
                                        $html_gridfields .= str_replace("{fieldvalue}", "<a href=\"javascript:check('$link_');\">" . $textdelete . "</a>", $template_gridbody_gridrow_gridfield);
                                    }
                                }
                                $tmp_row['cols'] = array();
                                $numcol = 0;
                                foreach ($orderfield as $key => $field)
                                {

                                    $tmp_col = array();
                                    $tmp_col['fieldvalue'] = "";
                                    $tmp_col['fieldname'] = $key;
                                    $tmp_col['fieldtitle'] = $key;

                                    $tmp_col['action_view'] = $tmp_row['action_view'];

                                    $kfunction = (false !== strpos($key, "()")) ? str_replace("()", "", $key) : false;
                                    if (false !== strpos($kfunction, "]"))
                                    {
                                        $kfunction = explode("]", $kfunction);
                                        $kfunction = $kfunction[1];
                                    }

                                    if ($fields && !isset($orderfield[$key]))
                                        continue;

                                    $vimage = "";
                                    $numcol++;

                                    if ($show_translations == true || !isset($table->formvals[$key]['frm_multilanguage']) || $table->formvals[$key]['frm_multilanguage'] != "1")
                                    {
                                        if (isset($functionsview[$key]))
                                        {
                                            $value = $functionsview[$key]($row[$field['name']]);
                                        }
                                        elseif (function_exists($kfunction))
                                        {
                                            $value = $all[$idk][$kfunction];
                                        }
                                        elseif (!isset($field['name']))
                                        {
                                            $value = "";
                                        }
                                        elseif (($field['frm_type'] == "datetime") && method_exists($table->formclass[$field['name']], "view"))
                                        {
                                            $tparams = $field;
                                            $tparams['name'] = $field['name'];
                                            $tparams['value'] = $row[$field['name']];
                                            $tparams['values'] = $row;
                                            $tparams['fieldform'] = $table;
                                            $value = $table->formclass[$field['name']]->view($tparams);
                                        }
                                        else
                                        {
                                            $value = $row[$field['name']];

                                            if ($field['frm_type'] == "image")
                                            {
                                                $image = $table->xmltable->get_file($row, $field['name']);
                                                if ($image != "")
                                                {
                                                    $himage = 16;
                                                    if (isset($params['imagesize']))
                                                    {
                                                        $himage = $params['imagesize'];
                                                    }
                                                    $image = dirname($image) . "/thumbs/" . basename($image) . ".jpg";
                                                    $vimage = "<img src=\"" . $image . "\" height=\"$himage\" />";
                                                    $value = "";
                                                }
                                            }
                                            if (!empty($field['foreignkey']) && !empty($field['fk_show_field']))
                                            {
                                                $r = array();
                                                if (function_exists("FN_XmlTable"))
                                                    $tfk[$field['fk_link_field']] = FN_XmlTable($field['foreignkey']);
                                                else
                                                    $tfk[$field['fk_link_field']] = xmldb_table($dbname, $field['foreignkey'], $path);


                                                $tablefk = $tfk[$field['fk_link_field']];
                                                $f = $field['fk_link_field'];
                                                if ($field['fk_link_field'] != $field['fk_show_field'])
                                                {
                                                    $f .= "|" . $field['fk_show_field'];
                                                }
                                                if (isset($tfk[$field['fk_link_field']]->fields[$field['fk_link_field'] . "_{$lang}"]))
                                                    $f .= "|" . $field['fk_show_field'] . "_{$_FN['lang']}";

                                                $r[$field['fk_link_field']] = $row[$field['name']];
                                                $showfields = explode(",", $field['fk_show_field']);
                                                $value = "";
                                                $sep = "";

                                                $fname_tmp_array = $row[$field['name']];
                                                $fname_tmp_array = explode(",", $fname_tmp_array);
                                                foreach ($fname_tmp_array as $fname_tmp)
                                                {
                                                    foreach ($showfields as $showfield)
                                                    {
                                                        $tvalue = $tablefk->GetRecord(array($field['fk_link_field'] => $fname_tmp));
                                                        if (isset($tvalue["{$showfield}_{$lang}"]) && $tvalue["{$showfield}_{$lang}"] != "")
                                                            $value .= $sep . $tvalue["{$showfield}_{$lang}"];
                                                        elseif (isset($tvalue["{$showfield}_en"]) && $tvalue["{$showfield}_en"] != "")
                                                            $value .= $sep . $tvalue[$showfield . "_en"];
                                                        elseif (!empty($tvalue[$showfield]))
                                                            $value .= $sep . $tvalue[$showfield];
                                                        $sep = "\n";
                                                    }
                                                    $sep = "-";
                                                }
                                            }
                                            else
                                            {
                                                if (isset($field['options']) && is_array($field['options']))
                                                {
                                                    foreach ($field['options'] as $opt)
                                                    {
                                                        if ($row[$field['name']] == $opt['value'])
                                                            $value = $opt['title'];
                                                    }
                                                }
                                            }
                                            $value = isset($value) ? $value : "";
                                            $value = XMLDB_FixEncoding(substr(strip_tags($value), 0, $params['max_cell_text_lenght']), $params['charset_page']);
                                        }

                                        $fieldtitle = (!empty($field['title'])) ? $field['title'] : "";
                                        $html_gridfields .= str_replace("{fieldtitle}", $fieldtitle, str_replace("{fieldvalue}", "$vimage$value", $template_gridbody_gridrow_gridfield));
                                        $tmp_col['fieldname'] = $key;
                                        $tmp_col['fieldtitle'] = "$fieldtitle";
                                        $tmp_col['fieldvalue'] = "$vimage$value";
                                    }

                                    $tmp_col['fieldnumber'] = $numcol;
                                    $tmp_col['field_' . $tmp_col['fieldname']]['action_view'] = isset($tmp_row['action_view']) ? $tmp_row['action_view'] : false;
                                    $tmp_col['field_' . $tmp_col['fieldname']]['action_edit'] = isset($tmp_row['action_edit']) ? $tmp_row['action_edit'] : false;
                                    $tmp_col['field_' . $tmp_col['fieldname']]['action_delete'] = isset($tmp_row['action_delete']) ? $tmp_row['action_delete'] : false;
                                    $tmp_row['cols'][] = $tmp_col;
                                }
                                //---------- $httpqueryparams actions-->

                                if (empty($params['actions_before_fields']))
                                {
                                    if ($enableview && $numcols++)
                                    {
                                        $link_ = XMLDB_editor_mergelink($mlink, "$urlquery&amp;op_$postgetkey=view&amp;filter{$postgetkey}=$link_FiltersEncoded");
                                        $link_ = $link_ . $flink_linkanchor;
                                        $html_gridfields .= str_replace("{fieldtitle}", "", str_replace("{fieldvalue}", "<a href=\"$link_\">" . $textview . "</a>", $template_gridbody_gridrow_gridfield));
                                    }
                                    if ($enableedit && $numcols++)
                                    {
                                        $link_ = XMLDB_editor_mergelink($mlink, "$urlquery&amp;op_$postgetkey=insnew&amp;filter{$postgetkey}=$link_FiltersEncoded");
                                        $link_ = $link_ . $flink_linkanchor;
                                        $html_gridfields .= str_replace("{fieldtitle}", "", str_replace("{fieldvalue}", "<a href=\"$link_\">" . $textmodify . "</a>", $template_gridbody_gridrow_gridfield));
                                    }
                                    if ($enabledelete && $numcols++)
                                    {

                                        $link_ = XMLDB_editor_mergelink($mlink, "$urlquery&op_$postgetkey=del&amp;filter{$postgetkey}=$link_FiltersEncoded");
                                        $link_ = $link_ . $flink_linkanchor;
                                        $html_gridfields .= str_replace("{fieldtitle}", "", str_replace("{fieldvalue}", "<a href=\"javascript:check('$link_');\">" . $textdelete . "</a>", $template_gridbody_gridrow_gridfield));
                                    }
                                }
                                $tmp_html = str_replace("{bkrow}", $backgroundcolor, $template_gridbody_gridrow);

                                $html_gridrow = preg_replace('/(<!-- start gridfields -->)(.*)(<!-- end gridfields -->)/is', xmldb_encode_preg_replace2nd($html_gridfields), $tmp_html);
                                $html_gridbody .= $html_gridrow;
                                $tplvars['rows'][] = $tmp_row;
                            }
                            $i++;
                        }
                    }
                    else
                    {
                        //$html = "<table border=\"\" style=\"background-color:$bordercolor\" cellpadding=\"2\" cellspacing=\"1\" >";
//                        $html_gridbody .= "$textnorecord";
                    }



                    $tplvars['txt_num_records'] = $txt_num_records;
                    $tplvars['start'] = $start;
                    $tplvars['end'] = $end;
                    $tplvars['num_records'] = $num_records;
                    if (false !== strstr($params['html_template_grid'], "<!-- start table -->"))
                    {
                        $params['html_template_grid'] = str_replace("{txt_num_records}", "$txt_num_records", $params['html_template_grid']);
                        $params['html_template_grid'] = str_replace("{start}", "$start", $params['html_template_grid']);
                        $params['html_template_grid'] = str_replace("{end}", "$end", $params['html_template_grid']);
                        $params['html_template_grid'] = str_replace("{num_records}", "$num_records", $params['html_template_grid']);
                        $html_table = preg_replace('/(<!-- start table -->)(.*)(<!-- end table -->)/is', xmldb_encode_preg_replace2nd($html_gridbody), $template_grid);
                        $html = preg_replace('/(<!-- start gridheader -->)(.*)(<!-- end gridheader -->)/is', xmldb_encode_preg_replace2nd($html_GridHeader), $params['html_template_grid']);
                        $html = preg_replace('/(<!-- start gridbody -->)(.*)(<!-- end gridbody -->)/is', xmldb_encode_preg_replace2nd($html_gridbody), $html);
                        $html = preg_replace('/(<!-- start pages -->)(.*)(<!-- end pages -->)/is', xmldb_encode_preg_replace2nd($htmlpages_full), $html);
                    }
                    else
                    {
                        unset($params['filters']);
                        $tplvars['method'] = "post";
                        $params['template_path'] = isset($params['template_path']) ? $params['template_path'] : "";
                        $html = TPL_ApplyTplString($params['html_template_grid'], $tplvars, $params['template_path'], $params);
                    }
                }
                $endloop = true;
                //---------------------------GRID------------------------------<
                $return = $params;
                break;
        }
    }
    if ($params['echo'] == true)
        echo $html;
    if (!empty("return_params"))
    {
        return $return;
    }
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
function xmldb_view($unirecid, $tablename, $databasename, $path, $lang, $languages, $params = false)
{
    global $_FN;
    $params['link'] = isset($params['link']) ? $params['link'] : "";
    $htmlout = "";
    if (!isset($params['isadmin']))
        $params['isadmin'] = XMLDBEDITOR_IsAdmin();
    $Table = new FieldFrm("$databasename", $tablename, $path, $lang, $languages, $params);
    $row = $Table->xmltable->GetRecordByPrimaryKey($unirecid);
    $unirecid = isset($row['unirecid']) ? $row['unirecid'] : false;
    $htmlout = $Table->ShowView($row);
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
function xmldb_go_download($file, $databasename, $tablename, $pathdatabase, $tablepath)
{
    // evita di accedere a directory esterne
    //  if (stristr($file,".."))
    //      die(fn_i18n("operation is not permitted"));
    // se il file non esiste lo crea
    if (!file_exists("$pathdatabase/$databasename/$tablename" . "_download_stat.php"))
    {
        //echo "<br>creazione statistiche $tablename";
        $sfields = array();
        $sfields[1]['name'] = "filename";
        $sfields[1]['primarykey'] = "1";
        $sfields[2]['name'] = "numdownload";
        $sfields[2]['defaultvalue'] = "0";
        echo createxmltable($databasename, $tablename . "_download_stat", $sfields, $pathdatabase);
    }
    $stat = new XMLTable($databasename, $tablename . "_download_stat", $pathdatabase);
    $oldval = $stat->GetRecordByPrimaryKey($file);
    $r['filename'] = $file;
    if ($oldval == null)
    {
        $r['numdownload'] = 1;
        $stat->InsertRecord($r);
    }
    else
    {
        //incrementa download
        $r['numdownload'] = $oldval['numdownload'] + 1;
        $stat->UpdateRecord($r);
    }

    $file = "$pathdatabase/$databasename/$tablepath/$file";

    //First, see if the file exists
    if (!is_file($file))
    {
        die("<b>$file 404 File not found!</b>");
    }

    //Gather relevent info about file
    $len = filesize($file);
    $filename = basename($file);
    $file_extension = strtolower(substr(strrchr($filename, "."), 1));
    //This will set the Content-Type to the appropriate setting for the file
    switch ($file_extension)
    {
        case "pdf" :
            $ctype = "application/pdf";
            break;
        case "exe" :
            $ctype = "application/octet-stream";
            break;
        case "zip" :
            $ctype = "application/zip";
            break;
        case "doc" :
            $ctype = "application/msword";
            break;
        case "xls" :
            $ctype = "application/vnd.ms-excel";
            break;
        case "ppt" :
            $ctype = "application/vnd.ms-powerpoint";
            break;
        case "gif" :
            $ctype = "image/gif";
            break;
        case "png" :
            $ctype = "image/png";
            break;
        case "jpeg" :
        case "jpg" :
            $ctype = "image/jpg";
            break;
        case "mp3" :
            $ctype = "audio/mpeg";
            break;
        case "wav" :
            $ctype = "audio/x-wav";
            break;
        case "mpeg" :
        case "mpg" :
        case "mpe" :
            $ctype = "video/mpeg";
            break;
        case "mov" :
            $ctype = "video/quicktime";
            break;
        case "avi" :
            $ctype = "video/x-msvideo";
            break;

        //The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files)
        case "php" :
        case "htm" :
        case "html" :
        case "txt" :
            $ctype = "application/force-download";
            break;

        default :
            $ctype = "application/force-download";
    }
    while (@ob_end_clean())
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
    $header = "Content-Disposition: attachment; filename=" . $filename . ";";
    header($header);
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . $len);
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
function xmldb_viewlink($row, $path, $databasename, $tablename, $field, $title, $value, $st, $et)
{
    $htmlout = "$st";
    if ($value != "")
    {
        if (!preg_match("/^http:\\/\\//si", $value))
        {
            $value = htmlspecialchars($value);
            $value = "http://$value";
        }
        $htmlout .= "<a onclick=\"window.open(this.href);return false;\" href=\"$value\">$value</a>";
    }
    $htmlout .= "$et";
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
function xmldb_viewswf($row, $path, $databasename, $tablename, $field, $title, $value, $st, $et)
{
    $htmlout = "$st";
    if (preg_match('/\.swf/is', $row[$field['name']]))
    {
        $fileflash = isset($row['unirecid']) ? "$path/$databasename/$tablename/" . $row['unirecid'] . "/" . $field['name'] . "/" . $row[$field['name']] : "";
        $htmlout .= "\n$st$title ";
        $htmlout .= "\n<object width=\"550\" height=\"400\">
<param name=\"movie\" value=\"$fileflash\">
<embed src=\"$fileflash\" width=\"550\" height=\"400\">
</embed>
</object>$et";
    }
    else
    {
        $htmlout = "$st$et";
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
function xmldb_viewyoutubelink($row, $path, $databasename, $tablename, $field, $title, $value, $st, $et)
{
    $htmlout = "$st";
    $html = "";
    $codes = explode(",", $value);
    foreach ($codes as $value)
    {
        $c = "v";
        $code = explode("/v/", $value);
        if (!isset($code[1]))
            $code = explode("?v=", $value);
        if (!isset($code[1]))
        {
            $code = explode("/p/", $value);
            $c = "p";
        }
        if (!isset($code[1]))
        {
            $code = explode("?p=", $value);
            $c = "p";
        }
        if (isset($code[1]))
        {
            $h = 344;
            $w = 425;
            $code = $code[1];
            if (false !== strstr($code, "&"))
                $code = substr($code, 0, strpos($code, "&"));
            if (false !== strstr($code, "?"))
                $code = substr($code, 0, strpos($code, "?"));
            if ($code != "")
            {
                $code = "http://www.youtube.com/$c/$code&amp;autoplay=0&amp;rel=0";
                $html .= "<div style=\"height:$h;width:$w;overflow:auto\">";
                //$html .= "<center>";
                $html .= "<object width=\"$w\" height=\"$h\">
					<param name=\"movie\" value=\"$code\"></param>
					<param name=\"enablejsapi\" value=\"1\"></param>
					<param name=\"allowFullScreen\" value=\"true\"></param>
					<embed src=\"$code\" type=\"application/x-shockwave-flash\" enablejsapi=\"1\" allowfullscreen=\"true\" width=\"$w\" height=\"$h\"></embed></object>";
                //$html .= "</center>";
                $html .= "</div>";
            }
            else
            {
                $html .= htmlentities($value);
            }
        }
    }
    $htmlout .= " \n$st$title " . $html . "$et";
    return $htmlout;
}

if (!function_exists("XMLDBEDITOR_HtmlAlert"))
{

    function XMLDBEDITOR_HtmlAlert($textupdateok)
    {
        $textupdateok = addslashes($textupdateok);
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

/**
 * 
 * @param type $string
 * @param type $like
 */
function xmldb_strlike($string, $like)
{
    $compare = str_replace("%", "", $like);
    $likelen = strlen($like);
    if ($like == "" && $string == "")
    {
        return true;
    }
    if ($like == "%")
    {
        return true;
    }
    if ($likelen > 0 && $like[0] != "%" && $like[$likelen - 1] != "%")
    {
        return $string == $compare;
    }
    if ($likelen > 0 && $like[0] == "%" && $like[$likelen - 1] == "%")
    {
        return (false !== strpos($string, $compare));
    }
    if ($likelen > 0 && $like[0] == "%" && $like[$likelen - 1] != "%")
    {
        return (strpos($string, $compare) === 0);
    }
    if ($likelen > 0 && $like[0] != "%" && $like[$likelen - 1] == "%")
    {
        return substr($string, -strlen($compare)) == $compare;
    }
    return false;
}

/**
 * 
 * @param type $data
 * @param string $filename
 */
function xmldb_export_csv($data, $filename = "")
{
    if (!$filename)
    {
        $filename = "export.csv";
    }
    $headers = true;
    $sep = ",";
    $str = "";
    foreach ($data as $row)
    {
        if ($headers)
        {
            $arraycols = array();
            foreach ($row as $k => $v)
            {
                $arraycols[] = "\"" . str_replace("\"", "\"\"", $k) . "\"";
            }
            $str .= implode($sep, $arraycols) . "\n";
            $headers = false;
        }
        $arraycols = array();
        foreach ($row as $cell)
        {
            $arraycols[] = "\"" . str_replace("\"", "\"\"", $cell) . "\"";
        }
        $str .= implode($sep, $arraycols) . "\n";
    }
    while (false !== @ob_end_clean()
    );
    if (!$filename)
    {
        $filename = "esportazione.xls";
    }
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: inline; filename=$filename");
    echo "$str";
    die();
}

?>