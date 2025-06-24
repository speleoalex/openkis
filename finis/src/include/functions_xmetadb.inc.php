<?php

/**
 * Finis xmetadb functions
 *
 * 
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2012
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');

global $_FN;
require_once $_FN['src_finis']."/include/xmetadb_editor.php";

/**
 * htmleditor
 *
 * @param string $name
 * @param string $value
 * @param string $rows
 * @param string $cols
 * @param string $tooltip
 */
function xmetadb_frm_field_html_overwrite($name,$value,$rows,$cols,$tooltip)
{
    global $_FN;
    $html="";
    $editor=$_FN['htmleditor'];
    if (isset($_FN['force_htmleditor']) && $_FN['force_htmleditor']!= "")
    {
        $editor=$_FN['force_htmleditor'];
    }
    $params=false;
    if (isset($_FN['force_htmleditorparams']) && $_FN['force_htmleditorparams']!= "")
    {
        $params=$_FN['force_htmleditorparams'];
    }
    if ($editor!= "0" && file_exists("{$_FN['src_finis']}/include/htmleditors/".$editor."/htmlarea.php"))
    {
        require_once ("{$_FN['src_finis']}/include/htmleditors/".$editor."/htmlarea.php");
        $defaultdir=false;
        if (isset($_FN['editor_folder']))
        {
            $defaultdir=$_FN['editor_folder'];
        }
        $html.=FN_HtmlHtmlArea($name,$cols,$rows,$value,$defaultdir,$params);
    }
    else
    {
        $html.="<textarea title=\"$tooltip\" cols=\"".$cols."\"  rows=\"".$rows."\"  name=\"$name\"  >";
        $html.=htmlspecialchars($value);
        $html.="</textarea>";
    }
    return $html;
}

/**
 *
 * @param string $lang
 * @return string
 */
function xmetadb_get_lang_img($lang)
{
    global $_FN;
    $img=FN_FromTheme("images/flags/$lang.png",false);
    if (file_exists($img))
        return "<img src=\"{$_FN['siteurl']}$img\" style=\"vertical-align:middle\" alt=\"$lang\" />";
    return $lang;
}

/**
 *
 * @global array $_FN
 * @param array $params
 * @return string
 */
function xmetadb_frm_view_file($params)
{

    global $_FN;
    $databasename=$params['fieldform']->databasename;
    $tablename=$params['fieldform']->tablename;
    $path=$params['fieldform']->path;
    $value=$params['value'];
    $values=$params['values'];
    $attributes=isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
    $tablepath=$params['fieldform']->xmltable->FindFolderTable($values);
    $table=FN_XMDBTable($tablename);
    $htmlout="";
    $fileimage=isset($values[$table->primarykey]) ? "$path/$databasename/$tablepath/".$values[$table->primarykey]."/".$params['name']."/".$values[$params['name']] : "";
    $fileimage2=isset($values[$table->primarykey]) ? "".$values[$table->primarykey]."/".$params['name']."/".$values[$params['name']] : "";
    $link=FN_GetParam("QUERY_STRING",$_SERVER);
    $htmlout.="\n<a $attributes title=\"Download $value\" href=\"?$link&xmetadb_ddfile_{$params['name']}={$values[$params['name']]}\"  >$value</a>";
    $downloadfile=FN_GetParam("xmetadb_ddfile_{$params['name']}",$_GET);

    if ($downloadfile!= "" && $downloadfile == $values[$params['name']])
    {
        $downloadfile=$values[$table->primarykey]."/{$params['name']}/$downloadfile";
        xmetadb_go_download($downloadfile,$databasename,$tablename,$path,$tablepath);
    }
    $fsize=0;
    if (file_exists($fileimage))
        $fsize=filesize($fileimage);
    $suff="bytes";
    if ($fsize > 1024)
    {
        $fsize=round($fsize / 1024,2);
        $suff="Kb";
    }
    if ($fsize > 1024)
    {
        $fsize=round($fsize / 1024,2);
        $suff="Mb";
    }
    $stat= FN_XMDBTable($tablename."_download_stat");
    $val=$stat->GetRecordByPrimaryKey($fileimage2);
    $count=isset($val['numdownload']) ? $val['numdownload'] : 0;
    $st=" | $count Download";
    $htmlout.="&nbsp;($fsize $suff$st)";
    return $htmlout;
}


/**
 * @param string $tablename
 * @param array $params
 * @return object
 */
function FN_XMDBTable($tablename, $params = array())
{
    global $_FN;
    $params = FN_setCommonParams($params);

    if (isset($_FN['tables'][$tablename]) && is_array($_FN['tables'][$tablename]))
    {
        $params = array_merge($params, $_FN['tables'][$tablename]);
    }

    return XMETATable::xmetadbTable("{$_FN['database']}", $tablename, $_FN['datadir'], $params);
}

/**
 * @param string $tablename
 * @param array $params
 * @return object
 */
function FN_XMDBForm($tablename, $params = array())
{
    global $_FN;
    $params = FN_setCommonParams($params);

    if (isset($_FN['tables'][$tablename]) && is_array($_FN['tables'][$tablename]))
    {
        $params = array_merge($params, $_FN['tables'][$tablename]);
    }

    return xmetadb_frm($_FN['database'], $tablename, $_FN['datadir'], $_FN['lang'], $_FN['languages'], $params);
}

/**
 * @global array $_FN
 * @param string $query
 * @return array 
 */
function FN_XMETADBQuery($query, $params = array())
{
    global $_FN;
    $params = FN_setCommonParams($params);

    if (isset($_FN['tables']) && is_array($_FN['tables']))
    {
        $params['tables'] = $_FN['tables'];
    }

    $DB = new XMETADatabase($_FN['database'], $_FN['datadir'], $params);
    return $DB->Query($query);
}


/**
 * Set common parameters from global $_FN
 * @param array $params
 * @return array
 */
function FN_setCommonParams($params = array())
{
    global $_FN;
    foreach ($_FN as $k => $v)
    {
        if (is_string($v) && !isset($params[$k]) && false !== strstr($k, "xmetadb"))
        {
            $params[$k] = $v;
        }
    }
    // Set common parameters
    $params['siteurl'] = $_FN['siteurl'];
    $params['charset_page'] = $_FN['charset_page'];
    $params['default_database_driver'] = $_FN['default_database_driver'];
    $params['requiredtext'] = isset($_FN['requiredfieldsymbol']) ? $_FN['requiredfieldsymbol'] : "*";

    return $params;
}

/**
 *
 * @param $tablename
 * @param $xmldatabase
 * @param $params
 */
function FN_XMETATableEditor($tablename,$params=array())
{
    
    
    global $_FN;
    require_once ("{$_FN['src_finis']}/include/xmetadb_editor.php");
    if (empty($params['xmldatabase']))
    {
        $params['xmldatabase']=$_FN['database'];
    }
    $op=FN_GetParam("opt",$_GET,"html");
    $params = FN_setCommonParams($params);

    if (isset($_FN['tables'][$tablename]) && is_array($_FN['tables'][$tablename]))
    {
        $params = array_merge($params, $_FN['tables'][$tablename]);
    }
    
    $link="mod={$_FN['mod']}&amp;opt=$op";
    $params['path']=$_FN['datadir'];
    $params['lang']=$_FN['lang'];
    $params['charset_page']=$_FN['charset_page'];
    $params['languages']=$_FN['languages'];
    $params['siteurl']=$_FN['siteurl'];
    $params['enable_mod_rewrite']=$_FN['enable_mod_rewrite'];
    $params['links_mode']=$_FN['links_mode'];
    if (!isset($params['link']))
    {
        $params['link']=$link;
    }
    //messages--->
    $params['path']=isset($params['path']) ? $params['path'] : $_FN['datadir'];
    $params['recordsperpage']=isset($params['recordsperpage']) ? $params['recordsperpage'] : 20;
    $params['textview']=isset($params['textview']) ? $params['textview'] : FN_Translate("view");
    $params['textsave']=isset($params['textsave']) ? $params['textsave'] : FN_Translate("save");
    $params['textmodify']=isset($params['textmodify']) ? $params['textmodify'] : FN_Translate("modify");
    $params['textdelete']=isset($params['textdelete']) ? $params['textdelete'] : FN_Translate("delete");

    $params['textviewlist']=isset($params['textviewlist']) ? $params['textviewlist'] : "<img style=\"vertical-align:middle;border:0px;\" alt=\"\"  src=\"".FN_FromTheme("images/left.png")."\" />&nbsp;".fn_i18n("back");
    $params['textinsertok']=isset($params['textinsertok']) ? $params['textinsertok'] : FN_Translate("the data were successfully inserted");
    $params['textupdateok']=isset($params['textupdateok']) ? $params['textupdateok'] : FN_Translate("the data were successfully updated");
    $params['textpages']=isset($params['textpages']) ? $params['textpages'] : FN_Translate("page").":";
    $params['textrequired']=isset($params['textrequired']) ? $params['textrequired'] : "*";
    $params['textfields']=isset($params['textfields']) ? $params['textfields'] : FN_Translate("required fields");
    $params['textcancel']=isset($params['textcancel']) ? $params['textcancel'] : FN_Translate("view list");
    $params['textnew']=isset($params['textnew']) ? $params['textnew'] : "".FN_Translate("new")."";
    $params['textexitwithoutsaving']=isset($params['textexitwithoutsaving']) ? $params['textexitwithoutsaving'] : FN_Translate("want to exit without saving?");
    //messages---<
    FN_PathSite("themes/{$_FN['theme']}/form.tp.html");
    FN_PathSite("themes/{$_FN['theme']}/grid.tp.html");
    FN_PathSite("themes/{$_FN['theme']}/view.tp.html");
    FN_PathSite("themes/{$_FN['theme']}/img/");
    
    if (empty($params['layout_template']) && file_exists("themes/{$_FN['theme']}/form.tp.html"))
    {
        $params['layout_template']=file_get_contents("themes/{$_FN['theme']}/form.tp.html");
        $params['template_path'] = "themes/{$_FN['theme']}/";
    }
    if (empty($params['html_template_grid']) && file_exists("themes/{$_FN['theme']}/grid.tp.html"))
    {
        $params['html_template_grid']=file_get_contents("themes/{$_FN['theme']}/grid.tp.html");
        $params['template_path'] = "themes/{$_FN['theme']}/";
    }
    if (empty($params['html_template_view']) && file_exists("themes/{$_FN['theme']}/view.tp.html"))
    {
        $params['html_template_view']=file_get_contents("themes/{$_FN['theme']}/view.tp.html");
        $params['template_path'] = "themes/{$_FN['theme']}/";
    }
    $params['lang_default'] = isset($params['lang_default']) ? $params['lang_default'] : $_FN['lang_default'];
    $params['siteurl'] = isset($params['siteurl']) ? $params['siteurl'] : $_FN['siteurl'];
    $params['lang'] = isset($params['lang']) ? $params['lang'] : $_FN['lang'];
    $params['enable_mod_rewrite'] = isset($params['enable_mod_rewrite']) ? $params['enable_mod_rewrite'] : $_FN['enable_mod_rewrite'];
    $params['use_urlserverpath'] = isset($params['use_urlserverpath']) ? $params['use_urlserverpath'] : $_FN['use_urlserverpath'];
    $params['sitepath'] = isset($params['sitepath']) ? $params['sitepath'] : $_FN['sitepath'];    
    return XMETADB_editor($tablename,$params);
}

function XMLDBEDITOR_IsAdmin($user=false)
{
    return FN_IsAdmin($user);
}

