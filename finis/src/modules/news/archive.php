<?php

/**
 * @package Flatnux_module_newsarhive
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$argument=FN_GetParam("op",$_GET,"html");
$op=FN_GetParam("op",$_GET,"html");
$mm=FN_GetParam("mm",$_GET,"int");
$dd=FN_GetParam("dd",$_GET,"int");
$yy=FN_GetParam("yy",$_GET,"int");
$argumentfield='argument';
if ($mm!= "" && intval($mm) < 10)
{
    $mm="0".intval($mm);
}
if ($dd!= "" && intval($dd) < 10)
{
    $dd="0".intval($dd);
}
$tablename="news";
$config=FN_LoadConfig("modules/news/config.php");
$tablename=$config['tablename'];

$tableArguments=FN_XMDBForm("{$tablename}_arguments");
$DB=new XMETADatabase("fndatabase",$_FN['datadir']);

FN_NARC_PrintBar($tablename);

$query="SELECT * FROM $tablename WHERE status LIKE '1'";
if ($argument!= "")
{
    $query.=" AND argument LIKE '$argument%'";
}
if ($yy!= "" && $mm!= "" && $dd!= "")
{
    $query.=" AND date LIKE '$yy-$mm-$dd%'";
}
elseif ($yy!= "" && $mm!= "")
{
    $query.=" AND date LIKE '$yy-$mm%'";
}
elseif ($yy!= "")
{
    $query.=" AND date LIKE '$yy-%'";
}
$all=$DB->query($query);
$AllnewsByArguments=array();

if (is_array($all))
    foreach($all as $item)
    {
        if (isset($item[$argumentfield]))
        {
            $AllnewsByArguments[$item[$argumentfield]][]=$item;
        }
        else
        {
            $AllnewsByArguments['_'][]=$item;
        }
    }

if (count($AllnewsByArguments) > 0)
{
    echo "<div class=\"newsarchive_items\">";
    foreach($AllnewsByArguments as $argumentid=> $items)
    {
        $argumentvalues=$tableArguments->xmltable->GetRecordByPrimaryKey($argumentid);
        $argumentvalues=$tableArguments->GetRecordTranslated($argumentvalues);
        $title=$argumentvalues['title'];
        if (!empty($argumentvalues['icon']))
            $icon="<img style=\"border:0px;\" src=\"{$_FN['siteurl']}{$_FN['datadir']}/fndatabase/{$tablename}_arguments/{$argumentvalues['unirecid']}/icon/{$argumentvalues['icon']}\" alt=\"$title\" title=\"$title\"/>";
        else
            $icon="";
        $my="";
        if ($yy!= "")
        {
            $my.="&amp;yy=$yy";
        }
        if ($mm!= "")
        {
            $my.="&amp;mm=$mm";
        }
        $href=FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;op=$argumentid$my");
        echo "\n<div class=\"newsarchive_item\" ><a  href=\"$href\">$icon<br />$title</a></div>";
    }
    echo "</div>";
    if ($all)
        FN_NARC_PrintByDate($all);
    if ($argument!= "" || $yy!= "" || $dd!= "" || $mm!= "")
    {

        echo "<br /><br /><div class=\"newsarchive_list\">";
        FN_NARC_PrintList($all);
        echo "</div>";
    }
}

if ($argument!= "")
    echo "<div class=\"newsarchive_back\" ><a href=\"".FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}")."\">&lt;&lt;&nbsp;".FN_i18n("back")."</a></div>";

/**
 * Print arguments
 * @global array $_FN
 * @param string $argument 
 */
function FN_NARC_PrintAllByArg($argument)
{
    global $_FN;
    $config=FN_LoadConfig("modules/news_archive/config.php");
    $tablename=$config['tablename'];
    $Table=xmetadb_form("fndatabase",$tablename,$_FN['datadir'],$_FN['lang'],$_FN['languages']);
    $TableArgument=xmetadb_form("fndatabase",$tablename."_arguments",$_FN['datadir'],$_FN['lang'],$_FN['languages']);
    $all=$Table->xmltable->getRecords(array("status"=>1,"argument"=>$argument),false,false,"date",true);
    if ($argument!= "")
    {
        $argument_values=$TableArgument->xmltable->GetRecordByPrimaryKey($argument);
        $argument_values=$TableArgument->GetRecordTranslated($argument_values);
        echo "<img alt=\"\" border=\"0\" src=\"{$_FN['siteurl']}{$_FN['datadir']}/fndatabase/{$tablename}_arguments/{$argument_values['unirecid']}/icon/{$argument_values['icon']}\" />";
        $title=$argument_values['title'];
        echo "$title<br /><br />";
        FN_NARC_PrintList($all);
    }
}

/**
 *
 * @global array $_FN
 * @param array $items
 */
function FN_NARC_PrintList($items)
{
    global $_FN;
    $module_news=$_FN['mod'];
    if (is_array($items))
    {
        foreach($items as $item)
        {

            $titlenews=$item['title_'.$_FN['lang']];
            if (empty($titlenews))
            {
                $titlenews=$item['title'];
                foreach($_FN['listlanguages'] as $lang)
                {
                    if (!empty($item['title_'.$lang]))
                    {
                        $titlenews=$item['title_'.$lang];
                        break;
                    }
                }
            }
            $titlenews=FN_FixEncoding($titlenews);

            $linkread=FN_RewriteLink("index.php?mod=$module_news&amp;op={$item['txtid']}");
            echo "<h4><a accesskey=\"".FN_GetAccesskey($titlenews,"$linkread ")."\" href=\"$linkread \" alt=\"Read\">$titlenews</a></h4>";
        }
    }
}

/**
 *
 * @global array $_FN
 * @global <type> $mesi
 * @param <type> $all
 */
function FN_NARC_PrintByDate($all)
{
    global $_FN;
    $argument=FN_GetParam("argument",$_GET,"int");
    $yy=FN_GetParam("yy",$_GET,"int");
    $years=array();
    foreach($all as $item)
    {
        $y=substr($item['date'],0,4);
        $years[$y][]=$item;
    }
    ksort($years);
    foreach($years as $year=> $items)
    {
        $href=FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;yy=$year&amp;op=$argument");
        echo "<br /><a href=\"$href\">$year</a> (".count($items).")";
        if ($yy!= "")
        {
            $months=array();
            foreach($items as $item)
            {
                $m=substr($item['date'],5,2);
                $months[$m][]=$item;
            }
            foreach($months as $month=> $itemsmonth)
            {
                $href=FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;yy=$year&amp;mm=$month&amp;op=$argument");
                echo "<br />&nbsp;&nbsp;&nbsp;<a href=\"$href\">".$_FN['months'][intval($month) - 1]."</a> (".count($itemsmonth).")";
            }
        }
    }
}

/**
 *
 * @global array $_FN
 * @param string $tablename
 */
function FN_NARC_PrintBar($tablename)
{
    global $_FN;
    $config=FN_LoadConfig("modules/news/config.php");
    $tablename=$config['tablename'];
    $argument=FN_GetParam("op",$_GET,"int");
    $mm=FN_GetParam("mm",$_GET,"int");
    $dd=FN_GetParam("dd",$_GET,"int");
    $yy=FN_GetParam("yy",$_GET,"int");
    $tableArguments=FN_XMDBForm("{$tablename}_arguments");
   // echo "<a href=\"".FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}")."\">".$_FN['sectionvalues']["title"]."</a>";
    $sect=array();
    $sect[]=array(
        "title"=>$_FN['sectionvalues']["title"],
        "description"=>"",
        "link"=>FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}")
    );

    if ($yy!= "")
    {
        //echo "&gt;&gt;<a href=\"".FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;yy=$yy")."\">$yy</a>";
        $sect[]=array(
            "title"=>$yy,
            "description"=>"",
            "link"=>FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;yy=$yy")
        );

        if ($mm!= "")
        {
            $sect[]=array(
                "title"=>$_FN['months'][$mm - 1],
                "description"=>"",
                "link"=>FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;yy=$yy&amp;mm=$mm")
            );
         //   echo "&gt;&gt;<a href=\"".FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;yy=$yy&amp;mm=$mm")."\">".$_FN['months'][$mm - 1]."</a>";
        }
        if ($mm!= "" && $dd !="")
        {
            $sect[]=array(
                "title"=>FN_FormatDate("$yy-$mm-$dd",0),
                "description"=>"",
                "link"=>FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;yy=$yy&amp;mm=$mm&amp;dd=$dd")
            );
         //   echo "&gt;&gt;<a href=\"".FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;yy=$yy&amp;mm=$mm")."\">".$_FN['months'][$mm - 1]."</a>";
        }
    }
    if ($argument!= "")
    {
        $argumentvalues=$tableArguments->xmltable->GetRecordByPrimaryKey($argument);
        $argumentvalues=$tableArguments->GetRecordTranslated($argumentvalues);
        $title=$argumentvalues['title'];
            $sect[]=array(
                "title"=>$title,
                "description"=>"",
                "link"=>FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;op=$argument")
            );

       // echo "&gt;&gt;<a href=\"".FN_RewriteLink("index.php?mode=archive&amp;mod={$_FN['mod']}&amp;op=$argument")."\">$title</a>";
    }



    echo FN_HtmlNavbar($sect);
}

?>