<?php

/**
 * @package Finis_module_search
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * 
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
//-----------------------------get request vars-------------------------------->
$q=trim(ltrim(FN_GetParam("q",$_REQUEST,"html")));
$method=FN_GetParam("method",$_REQUEST,"html");
$where=FN_GetParam("where",$_REQUEST,"html");
$op=FN_GetParam("op",$_REQUEST,"html");
$maxres=100;
$contares=0;
$array_result_tp=array();
$text_result="";
//-----------------------------get request vars--------------------------------<
//---------------------query--------------------------------------------------->
$tosearch_array=array();
if ($q!= "")
{
    $text_result=FN_Translate("no result");
    if ($method== "OR" || $method== "AND")
    {
        $tosearch_tmp=explode(" ",$q);
        foreach($tosearch_tmp as $searchitem)
        {
            if (strlen($searchitem) > 0)
            {
                if (!in_array($searchitem,$tosearch_array))
                    $tosearch_array=$tosearch_tmp;
            }
        }
    }
    else
    {
        $tosearch_array[]="$q";
    }
}
//---------------------query---------------------------------------------------<
//---------------------perform search------------------------------------------>
$htmlresults="";
$__results=array();
if (count($tosearch_array) > 0)
{

    $sections=FN_GetSections("",true);
    foreach($sections as $sectionvalues)
    {
        if ($where!= "" && $sectionvalues['id']!= $where)
        {
            continue;
        }
        $search_module_file="{$_FN['src_application']}/sections/{$sectionvalues['id']}/";
        $search_function_name="FNSEARCH_section_{$sectionvalues['id']}";
        if ($sectionvalues['type']!= "")
        {
            $search_module_file="{$_FN['src_finis']}/modules/{$sectionvalues['type']}/";
            $search_function_name="FNSEARCH_module_{$sectionvalues['type']}";
        }
        //--------------search in section title-------------------------------->
        $found=false;
        $lang_to_search=$_FN['lang'];
        foreach($tosearch_array as $texttofind)
        {
            if (false!== strpos(strtolower($sectionvalues['title']),strtolower($texttofind)) ||
                    false!== strpos(strtolower($sectionvalues['description']),strtolower($texttofind))
            )
            {
                $found=true;
            }
            else
                $found=false;
            if ($found== true && $method== "OR")
            {
                break;
            }
        }
        if ($found)
        {
            $text_result="";
            if (!isset($array_result_tp[$sectionvalues['id']]))
            {
                $array_result_tp[$sectionvalues['id']]=$sectionvalues;
                $array_result_tp[$sectionvalues['id']]['results']=array();
            }
            $array_result_tp[$sectionvalues['id']]['results'][]=array("title"=>$sectionvalues['title'],"link"=>FN_RewriteLink("index.php?mod={$sectionvalues['id']}&lang=$lang_to_search"),"text"=>$sectionvalues['description']);
            $array_result_tp[$sectionvalues['id']][]=array("title"=>$sectionvalues['title'],"link"=>FN_RewriteLink("index.php?mod={$sectionvalues['id']}&lang=$lang_to_search"),"text"=>$sectionvalues['description']);
            $contares++;
            $__results[]=array("title"=>$sectionvalues['title'],"link"=>FN_RewriteLink("index.php?mod={$sectionvalues['id']}&lang=$lang_to_search"),"text"=>$sectionvalues['description']);
        }


        //--------------search in section title--------------------------------<
        if (file_exists($search_module_file."/search.php"))
        {
            //------------------search.php------------------------------------->
            $results=array();
            $method=$method;
            include_once ($search_module_file."/search.php");
            $results=array();
            if (function_exists("$search_function_name"))
            {
                $results=$search_function_name($tosearch_array,$method,$sectionvalues,$maxres - $contares);
            }
            if (is_array($results))
            {
                foreach($results as $result)
                {
                    if (!isset($array_result_tp[$sectionvalues['id']]))
                    {
                        $array_result_tp[$sectionvalues['id']]=$sectionvalues;
                        $array_result_tp[$sectionvalues['id']]['results']=array();
                    }
                    $array_result_tp[$sectionvalues['id']]['results'][]=$result;
                    $__results[]=$result;
                    $contares++;
                }
            }
            //------------------search.php-------------------------------------<			
        }
        else
        {
            foreach($_FN['listlanguages'] as $lang_to_search)
            {
                if ($contares > $maxres)
                    break;
                if (file_exists("$search_module_file/section.$lang_to_search.html"))
                {
                    foreach($tosearch_array as $texttofind)
                    {
                        $text_section=strip_tags(file_get_contents("$search_module_file/section.$lang_to_search.html"));
                        $text_section=str_replace("&nbsp;"," ",$text_section);
                        $pos=strpos(strtolower($text_section),strtolower($texttofind));
                        $textres="";
                        if ($pos=== false)
                        {
                            $found=false;
                        }
                        else
                        {
                            //start and end text position --------------------->
                            $startpos=$pos - 8;
                            if ($startpos < 0)
                                $startpos=0;
                            for($i=1; $startpos>= 0; $i++)
                            {
                                if ($text_section[$startpos]== " " || $text_section[$startpos]== "." || $i > 10)
                                {
                                    break;
                                }
                                $startpos--;
                            }
                            $endpos=200;
                            for($i=1; isset($text_section[$pos + $endpos]); $i++)
                            {
                                if ($text_section[$pos + $endpos]== " " || $text_section[$pos + $endpos]== "." || $i > 20)
                                {

                                    break;
                                }
                                $endpos++;
                            }
                            $textresTMP=substr($text_section,$startpos,($pos - $startpos) + $endpos);
                            //start and end text position ---------------------<
                            $textres=$textresTMP;
                            $found=true;
                        }
                        if ($found== true && $method== "OR")
                        {
                            break;
                        }
                    }
                    if ($found)
                    {
                        $contares++;
                        if (!isset($array_result_tp[$sectionvalues['id']]))
                        {
                            $array_result_tp[$sectionvalues['id']]=$sectionvalues;
                            $array_result_tp[$sectionvalues['id']]['results']=array();
                        }
                        $array_result_tp[$sectionvalues['id']]['results'][]=array("title"=>$sectionvalues['title'],"link"=>FN_RewriteLink("index.php?mod={$sectionvalues['id']}&lang=$lang_to_search"),"text"=>$textres);
                        $__results[]=array("title"=>$sectionvalues['title'],"link"=>FN_RewriteLink("index.php?mod={$sectionvalues['id']}&lang=$lang_to_search"),"text"=>$textres);
                    }
                }
            }
        }
    }
    if ($contares > 0 && $q!= "")
    {
        $text_result="$contares ".FN_Translate("results have been found","aa");
    }
    //print results ----------------------------------------------------------->
    $htmlresults="";
    if (count($__results) > 0)
    {
        $htmlresults.="<h3>".FN_i18n("search results",false,"Aa").":</h3>";
        foreach($__results as $result)
        {
            $htmlresults.="<div class=\"search_item_result\" ><a class=\"search_item_title\" href=\"{$result['link']}\">{$result['title']}</a>";
            foreach($tosearch_array as $texttofind)
            {
                $result['text']=FN_FixEncoding($result['text']);
                $result['text']=@html_entity_decode(strip_tags($result['text']),ENT_QUOTES,$_FN['charset_page']);
                $result['text']=preg_replace("/$texttofind/is","___SPANSTART___{$texttofind}___SPANEND___",$result['text']);
                $result['text']=preg_replace("/___SPANSTART___/is","<span style=\"background-color:#ffff00;color:#000000\">",$result['text']);
                $result['text']=preg_replace("/___SPANEND___/is","</span>",$result['text']);
            }
            $htmlresults.="<div class=\"search_item_text\" >{$result['text']}</div>";
            $htmlresults.="</div>";
        }
    }
    else
    {
        $htmlresults.=FN_i18n("no result");
    }
    //print results -----------------------------------------------------------<	

}
//---------------------perform search------------------------------------------>
//----------------------------print search form-------------------------------->
$params=array();
$params['form_action']=FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;op=$op");
$params['q']=$q;
$params['op']=$op;
$params['text_result']=$text_result;
$params['where']=$where;
$params['results']=$htmlresults;
$params['items']=$__results;
$params['sections_results']=$array_result_tp;
//dprint_r($params['sections_results']);


$_FN['return']['result']=$tosearch_array;
$tplfile=FN_FromTheme("{$_FN['src_finis']}/modules/search/searchform.tp.html",false);
$html=file_get_contents($tplfile);
$html=preg_replace("/<option([^>]*)value=\"$method\"/ms","<option\\1value=\"$method\" selected=\"selected\"",$html);
$html=preg_replace("/<option([^>]*)value='$method'/ms","<option\\1value='$method' selected=\"selected\"",$html);
$html=FN_TPL_ApplyTplString($html,$params);
echo $html;
//----------------------------print search form--------------------------------<

