<?php

/**
 * @package Finis_module_news
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$sections=FN_GetSections("",true,false,true,false,true);
$sections=FN_ArraySortByKey($sections,"position");
$sections=FN_ArraySortByKey($sections,"parent");
echo "<table>";
echo "<thead><tr>";
echo "<th>".FN_Translate("page type")."</th>";
echo "<th>".FN_Translate("contents")."</th>";
echo "<th>".FN_Translate("")."</th>";
echo "</tr></thead>";

$tmp=false;
echo FNCC_print_node("",$sections,$tmp);
echo "<tr><td colspan=\"9\">
        
</td></tr>";
echo "</table><br />";
echo "<p>    <button onclick=\"window.location='index.php?fnapp=controlcenter&opt=contents/sitemap'\">".FN_Translate("edit the pages and the structure of the website")."</button>
    <button onclick=\"window.location='index.php?fnapp=controlcenter&mod=&opt=contents/sitemap&newsection=1'\">".FN_Translate("create a new page")."</button></p>
";

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
        if ($section['parent']==$parent)
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
    $opt="contents/sitemap";
    $html="";
    $id++;
    $left=($level-1)*30;
    $linkdelete="{$_FN['controlcenter']}?fnapp=controlcenter&page___xdb_fn_sections=1&order___xdb_fn_sections=id&op___xdb_fn_sections=del&pk___xdb_fn_sections=".$section['id'].'&opt='.$opt;
    $linkedit="{$_FN['controlcenter']}?fnapp=controlcenter&page___xdb_fn_sections=1&order___xdb_fn_sections=id&op___xdb_fn_sections=insnew&pk___xdb_fn_sections=".$section['id'].'&opt='.$opt;
    $vis="";
    if ($section['hidden'])
    {
        $vis="style=\"opacity:'.5'\"";
    }
    $html.="<tr $vis>";

    $t="";
    $html.="<td>{$section['type']}</td>";
    //pagine --->
    $html.="<td>";
    $link="{$_FN['controlcenter']}?fnapp=controlcenter&mod={$section['id']}&edit=sections/{$section['id']}/section.php&opt=$opt";
    foreach($_FN['listlanguages'] as $l)
    {
        $border="border:1px solid #ffffff";
        if (file_exists($_FN['src_application'] ."/sections/{$section['id']}/section.$l.html"))
            $border="border:1px solid #00ff00";
        $link="{$_FN['controlcenter']}?mod={$section['id']}&edit=sections/{$section['id']}/section.$l.html&opt=$opt";
        $html.=" <img style=\"cursor:pointer;vertical-align:middle;$border\" onclick=\"window.location='$link'\" src=\"images/flags/$l.png\" />";
    }
    //pagine ---<
    $html.="</td><td>";

    //icon --->
    $link = FN_RewriteLink("index.php?mod={$section['id']}");
    $html.="<span id=\"span_{$section['id']}\"  style=\"background-position: bottom right;background-image:url(controlcenter/sections/contents/sitemap/node.png);background-repeat:no-repeat;padding-left:{$left}px\"></span>";
    $html.="<span><img style=\"vertical-align:middle;border:0px;height:22px;\" src=\"".FN_FromTheme("images/mime/dir.png")."\" />$t&nbsp;<a title=\"".FN_Translate("preview")."\" href=\"$link\" onclick=\"preview=window.open('$link','preview','top=10,left=10,scrollbars=yes');preview.focus();return false;\" >{$section['title']}</a>";
    //icon ---<

    $html.="</td>";
    $end=$start="";
    if ($section['startdate'])
    {
        $start =FN_FormatDate($section['startdate']);
    }
    if ($section['enddate'])
    {
        $end =FN_FormatDate($section['enddate']);
    }    
    $html.="</td>";
    $html.="</tr>";

    return $html;
}

?>