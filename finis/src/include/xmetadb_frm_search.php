<?php

/**
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2012
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * @package xmetadb
 * 
 */
//TODO: to complete
class xmetadb_searchform
{
    var $params;
    var $xmetadb_frm;

    function __construct($databasename,$tablename,$path="misc",$lang="en",$languages="en,it",$params=array())
    {
        if (!is_array($params))
        {
            $params=array();
        }
        
        if (empty($params['tpl_filters']))
        {
            
            $params['tpl_filters']="
<div>
<!-- contents -->
<!-- item -->
<span>{title}:{filtermode}{input}</span>
<!-- end_item -->
<!-- end contents -->
</div>
";
        }
        $this->params=$params;
        $this->xmetadb_frm=xmetadb_frm($databasename,$tablename,$path,$lang,$languages,$params);
    }

    function HtmlSearchForm($oldvalues=false,$isadmin=false)
    {
        
        $html="";
        $lang=$this->xmetadb_frm->lang;
        foreach($this->xmetadb_frm->formvals as $k=> $fv)
        {
            if (isset($fv['primarykey'])&&$fv['primarykey']=="1")
                $primarykey=$k;
        }
        $extravalues=null;
        $strTemplateItem=$this->TPL_GetHtmlPart("item",$this->params['tpl_filters']);
        foreach($this->xmetadb_frm->formvals as $extravaluesk=> $extravalues)
        {
            if (isset($extravalues['fk_filter_field'])&&$extravalues['fk_filter_field']!="")
                $extravalues['options']=$this->xmetadb_frm->LoadOptions($extravalues,$oldvalues);
            // --------multilanguage -------------->
            if (isset($extravalues['frm_multilanguage'])&&$extravalues['frm_multilanguage']==1)
            {
                continue;
            }
            $extravalues['realname']=$extravalues['name'];
            // --------multilanguage --------------<
            //------------gestione visualizzazione----------->
            $showfield=true;
            if (empty($extravalues['frm_search']))
                $showfield=false;
            //------------gestione visualizzazione-----------<
            $oldval=isset($oldvalues[$extravaluesk]) ? $oldvalues[$extravaluesk] : "";
            $extravalues['messages']=$this->xmetadb_frm->messages;
            $extravalues['value']=$oldval;
            $extravalues['fieldform']=$this->xmetadb_frm;
            $extravalues['oldvalues']=$oldvalues;
            $extravalues['oldvalues_primarikey']=$primarykey;
            $extravalues['multilanguage']=false;
            $extravalues['lang']=$lang;
            $extravalues['lang_noprefix']=$lang;

            $extravalues['languagesfield']=$lang;
            $extravalues['frm_help']=isset($extravalues['frm_help']) ? $extravalues['frm_help'] : "";
            if ($showfield)
            {
                $params=$extravalues;
                //----------    select ><= ------------------------------------>
                if (empty($oldvalues["_search_{$extravalues['name']}"]))
                {
                    if (empty($extravalues['default_filter_mode']))
                    {
                        $extravalues['default_filter_mode']="_x_";
                    }
                    $oldvalues["_search_{$extravalues['name']}"]=$extravalues['default_filter_mode'];
                }
                $str="";
                $str.="<select  name=\"_search_{$extravalues['name']}\" >";
                $str.="<option  value=\"\" >nessun filtro</option>";
                $s=(isset($oldvalues["_search_{$extravalues['name']}"])&&$oldvalues["_search_{$extravalues['name']}"]=="_x_") ? "selected=\"selected\"" : "";
                $str.="<option $s value=\"_x_\" >contiene</option>";
                $s=(isset($oldvalues["_search_{$extravalues['name']}"])&&$oldvalues["_search_{$extravalues['name']}"]=="x") ? "selected=\"selected\"" : "";
                $str.="<option $s value=\"x\">uguale</option>";
                $s=(isset($oldvalues["_search_{$extravalues['name']}"])&&$oldvalues["_search_{$extravalues['name']}"]=="x_") ? "selected=\"selected\"" : "";
                $str.="<option  $s value=\"x_\" >inizia per</option>";
                $s=(isset($oldvalues["_search_{$extravalues['name']}"])&&$oldvalues["_search_{$extravalues['name']}"]=="_x") ? "selected=\"selected\"" : "";
                $str.="<option  $s value=\"_x\">finisce per</option>";
                $s=(isset($oldvalues["_search_{$extravalues['name']}"])&&$oldvalues["_search_{$extravalues['name']}"]=="<") ? "selected=\"selected\"" : "";
                $str.="<option  $s value=\"<\">&egrave minore di</option>";
                $s=(isset($oldvalues["_search_{$extravalues['name']}"])&&$oldvalues["_search_{$extravalues['name']}"]==">") ? "selected=\"selected\"" : "";
                $str.="<option  $s value=\">\">&egrave maggiore di</option>";
                if (!empty($extravalues['frm_search_ranges']))
                {
                    $s=(isset($oldvalues["_search_{$extravalues['name']}"])&&$oldvalues["_search_{$extravalues['name']}"]=="range") ? "selected=\"selected\"" : "";
                    $str.="<option $s value=\"range\">&egrave compreso tra</option>";
                }$str.="</select>";
                $params['filtermode']=$str;
                //----------    select ><= ------------------------------------<



                $htmlInput="";
                if (!empty($extravalues['frm_search_ranges']))
                {
                    $extravalues2=$extravalues;
                    $extravalues2['strhiddenfield']="";
                    $extravalues2['frm_starttagvalue']="";
                    $extravalues2['frm_endtagvalue']="";
                    $extravalues2['frm_starttagtitle']="";
                    $extravalues2['frm_endtagtitle']="";
                    $extravalues2['title']="";
                    $extravalues2['value']=(!empty($oldvalues["_search_range_end_".$extravalues2['name']])) ? $oldvalues["_search_range_end_".$extravalues2['name']] : "";
                    $extravalues2['name']="_search_range_end_".$extravalues2['name'];
                    $htmlInput.=$this->xmetadb_frm->formclass[$extravaluesk]->show($extravalues2);
                }
                $params['input']=$this->xmetadb_frm->formclass[$extravaluesk]->show($extravalues);

                $html.=FN_TPL_ApplyTplString($strTemplateItem,$params);
            }
        }

        $html=FN_TPL_ReplaceHtmlPart("contents",$html,$this->params['tpl_filters']);
        // die();
        return $html;
    }

    /**
     * find <!-- $partname -->(.*)<!-- end$partname -->
     * 
     * @param type $partname
     * @param type $tp_str
     * @param type $default
     * @return type
     */
    function TPL_GetHtmlPart($partname,$tp_str,$default="")
    {
        $out=array();
        preg_match("/<!-- $partname -->(.*)<!-- end_$partname -->/is",$tp_str,$out)||preg_match("/<!-- $partname -->(.*)<!-- end $partname -->/is",$tp_str,$out)||preg_match("/<!-- $partname -->(.*)<!-- end $partname -->/is",$tp_str,$out);
        $tp_str=empty($out[0]) ? $default : $out[0];
        return $tp_str;
    }

    function GetQueryByPost($postvalues=false)
    {
        $post=$this->GetSearchByPost($postvalues);
        //dprint_r($post);
        $query="SELECT * FROM {$this->xmetadb_frm->table}  ";
        $query="";

        $sep="";
        $query2="";
        foreach($this->xmetadb_frm->formvals as $key=> $value)
        {
            $mode=isset($post['_search_'.$key]) ? $post['_search_'.$key] : "";
            if (!empty($post[$key])&&!empty($value['frm_search'])&&$mode!="")
            {
                if ($mode=="x_")
                {
                    $query2.=" $key LIKE '{$post[$key]}%'";
                    $sep=" AND ";
                }
                if ($mode=="_x_")
                {
                    $query2.="$sep $key LIKE '%{$post[$key]}%'";
                    $sep=" AND ";
                }
                if ($mode=="_x")
                {
                    $query2.="$sep $key LIKE '%{$post[$key]}'";
                    $sep=" AND ";
                }
                if ($mode=="x")
                {
                    $query2.="$sep $key LIKE '{$post[$key]}'";
                    $sep=" AND ";
                }
                if ($mode==">")
                {
                    $query2.="$sep $key > '{$post[$key]}'";
                    $sep=" AND ";
                }
                if ($mode=="<")
                {
                    $query2.="$sep $key < '{$post[$key]}'";
                    $sep=" AND ";
                }

                if (!empty($post[$key])&&!empty($post['_search_range_end_'.$key])&&$mode=="range")
                {
                    $query2.="$sep ($key >= '{$post[$key]}' AND $key <= '".$post['_search_range_end_'.$key]."')";
                    $sep=" AND ";
                }
            }
        }
        if ($query2!="")
            $query="$query2";
        return $query;
    }

    /**
     * getbypost
     *
     * riempe un array con i valori ricevuti tramite post
     * @param array $oldvalues i valori che non sono in post vanno mantenuti
     */
    function GetSearchByPost($postvalues=false)
    {
        if (!$postvalues)
        {
            $postvalues=$_POST;
        }
        $newvalues=array();
        foreach($postvalues as $key=> $value)
        {
            $newvalues[$key]=$value;
        }
        foreach($this->xmetadb_frm->formvals as $key=> $value)
        {
            if (isset($value['type'])&&($value['type']=='check'))
            {
                if (isset($postvalues["__check__$key"])&&!isset($postvalues["$key"]))
                {
                    $newvalues[$key]="";
                }
                if (isset($postvalues["__check__$key"])&&isset($postvalues[$key]))
                {
                    $newvalues[$key]=$postvalues[$key];
                }
            }
            else
            {
                if (isset($postvalues[$key]))
                {
                    $newvalues[$key]=$postvalues[$key];
                    if (ini_get('magic_quotes_gpc')==1)
                    {
                        $newvalues[$key]=stripslashes($newvalues[$key]);
                    }
                    if (isset($value['frm_allowhtml'])&&$value['frm_allowhtml']!="true")
                    {
                        $newvalues[$key]=htmlentities($postvalues[$key]);
                    }
                }
            }
        }
        return $newvalues;
    }

}

?>
