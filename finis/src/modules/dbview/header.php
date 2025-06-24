<?php

/**
 * @package Finis_module_navigator
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
#<fnmodule>navigator</fnmodule>

defined('_FNEXEC') or die('Restricted access');
$op=FN_GetParam("op",$_GET);
//--config-->
$config=FN_LoadConfig("{$_FN['src_finis']}/modules/dbview/config.php");
//--config--<
//dprint_r($config);
$tables=explode(",",$config['tables']);
$_tablename=$tables[0];
if ($_tablename== "")
{
    $tablename=$tables[0];
}
else
{
    $tablename=$_tablename;
}

$tplvars="";
$title=$_FN['site_title'];
if ($op== "view")
{
    $id=FN_GetParam("id",$_GET);
    $Table=FN_XMDBForm($tablename);
    $row=$Table->xmltable->GetRecordByPrimaryKey($id);
    if (is_array($row))
    {
        $titles=explode(",",$config['titlefield']);
        $t=array();

        foreach($titles as $tt)
        {
            $t[]=isset($row[$tt]) ? $row[$tt] : "";
        }
        $title=implode(" ",$t);
        $_FN['site_title'].=" - ".$title;
    }
    $vars=$row;
    $image="";
    if ($config['image_titlefield'] && !empty($row[$config['image_titlefield']]))
    {
        $image=$_FN['siteurl'].$Table->xmltable->getFilePath($row,$config['image_titlefield']);
    }
    $vars['image']=$image;
    $vars['url']=FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;op=view&amp;id=$id","",true);
    $vars['title']=$title;
    $_FN['section_header_footer'].=FN_TPL_ApplyTplFile(FN_FromTheme("{$_FN['src_finis']}/modules/dbview/header.tp.html",false),$vars);
}



if (file_exists("themes/{$_FN['theme']}/modules/dbview/style.css"))
{
    $_FN['section_header_footer'].="\n<style>\n".file_get_contents("themes/{$_FN['theme']}/modules/dbview/style.css")."\n</style>\n";
}
?>