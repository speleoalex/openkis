<?php

/**
 * @package Finis_module_news
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
/**
 *
 * @global array $_FN
 * @param array $tosearch_array
 * @param string $method
 * @param array $sectionvalues
 * @param int $maxres
 * @return array 
 */
function FNSEARCH_module_dbview($tosearch_array,$method,$sectionvalues,$maxres)
{
    global $_FN;
    $results=array();
    $section_to_search=$sectionvalues['id']; // current section 
    $config=FN_LoadConfig("{$_FN['src_finis']}/modules/dbview/config.php",$sectionvalues['id']); //load config in section
   
    $dbview = new FNDBVIEW($config);
    $tablename=$config['tables'];
    $Table=FN_XMDBTable($tablename);
    $DB=new XMETADatabase($_FN['database'],$_FN['datadir']);
    //--search query ---------------------------------------------------------->
    $query="SELECT * FROM $tablename WHERE  ";
    $tmpmethod="";
    foreach($Table->fields as $fieldstoread=> $fieldvalues)
    {
        if ($fieldstoread!="insert"&&$fieldstoread!="update"&&$fieldstoread!="unirecid"&&$fieldvalues->type!="check")
        {
            //dprint_r($fieldvalues);
            if (isset($fieldvalues->frm_show)&&$fieldvalues->frm_show=="0")
            {
                continue;
            }
            if (isset($fieldvalues->frm_show)&&$fieldvalues->frm_show=="0")
            {
                continue;
            }

            foreach($tosearch_array as $f)
            {
                if ($f!="")
                {
                    $query.=" $tmpmethod ".$fieldstoread." LIKE '%".addslashes($f)."%' ";
                    $tmpmethod=$method;
                }
            }
            $tmpmethod=" OR ";
        }
    }
    if ((isset($viewonlycreator)&&$viewonlycreator==1)&&!FN_IsAdmin())
    {
        $query.=" AND username = '".$_FN['user']."'";
    }
    $query.=" LIMIT 1,$maxres";
    //--search query ----------------------------------------------------------<
    $records=$DB->Query("$query");
    $cont=0;
    $titles=explode(",",$config['titlefield']);
    if (is_array($records))
    {
        foreach($records as $data)
        {
            if (!$dbview->CanViewRecord($data[$Table->primarykey],$tablename))
            {
                continue;
            }            
            $link=FN_RewriteLink("index.php?mod=$section_to_search&amp;op=view&id={$data[$Table->primarykey]}");
            
            $results[$cont]['link']=$link;
            $results[$cont]['title']="";
            foreach($titles as $title)
            {
                $results[$cont]['title'].=isset($data[$title]) ? $data[$title]." " : "";
            }
            $results[$cont]['title']=trim(ltrim($results[$cont]['title']));
            if ($results[$cont]['title']== "")
            {
                $results[$cont]['title']=$sectionvalues['title'];
            }
            $results[$cont]['text']="";
            if (!empty($data[$config['descriptionfield']]))
            {
                $results[$cont]['text']=substr(strip_tags($data[$config['descriptionfield']]),0,100);
            }
            else{
                $results[$cont]['text']=$sectionvalues['title'];
            }
            $cont++;
        }
    }
    return $results;
}

?>
