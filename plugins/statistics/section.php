<?php

/**
 * @package Flatnux_statistics
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
##<fnmodule>statistics</fnmodule>
/*
  FILES:
  include/statistics.inc.php
  include/autoexec.d/20_stat.php
  include/plugins/statistics/
  include/plugins/statistics/contolcenter/
  include/plugins/statistics/contolcenter/settings.php
  include/plugins/statistics/config.php
  include/plugins/statistics/section.php
 */

defined('_FNEXEC') or die('Restricted access');
echo "<div style=\"margin:2px;border:2px solid #00da00\">".FN_Translate("unique access")."</div>";
echo "<div style=\"margin:2px;border:2px solid #0000da\">".FN_Translate("total access")."</div>";
FNSTAT_PageStatistic();

/**
 * 
 * @param string $tablename
 * @param string $fieldname
 * @param string $sql
 */
function FNSTAT_PrintStatistic($tablename,$fieldname,$sql="")
{
    global $_FN;
    $max_rows=20;
    $color_unique="#00da00";
    $color_counter="#0000da";
    $all=FN_XMLQuery("SELECT * FROM $tablename $sql");
    if (!is_array($all) || count($all) == 0)
    {
        echo "<table style=\"\" cellpadding=\"1\" cellspacing=\"1\" border=\"0\">";
        echo "<tr><td style=\"padding:1px\">".FN_Translate("no results")."</td></tr></table>";
        return;
    }
    echo "<table style=\"background-color:#8a8a8a\" cellpadding=\"1\" cellspacing=\"1\" border=\"0\">";
    $total=$total_unique=0;
    foreach($all as $key=> $rec)
    {
        if (isset($rec['counter_unique']))
            $total_unique += $rec['counter_unique'];
        if (isset($rec['counter']))
            $total += $rec['counter'];
    }
    $max=max(array($total,$total_unique));
    $i=1;
    foreach($all as $key=> $rec)
    {
        if ($max_rows == 0 || $i <= $max_rows)
        {
            echo "<tr style=\"background-color:#ffffff;color:#000000\">";
            if (isset($rec[$fieldname]))
                echo "<td style=\"padding:1px;\" >{$rec[$fieldname]}</td>";
            echo "<td style=\"padding:1px;text-align:left;\">";
            echo "<table style=\"margin:0px;\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
            if (isset($rec['counter_unique']) && $rec['counter_unique'] > 0)
            {
                $w=intval(($rec['counter_unique'] / $max) * 100);
                echo "<td style=\"padding:1px;width:100px;\"><div style=\"border:1px outset $color_unique;background-color:$color_unique;width:{$w}%;height:10px;\"> </div></td><td style=\"color:$color_unique\">&nbsp;{$rec['counter_unique']}</td></tr>";
            }
            if (isset($rec['counter']) && $rec['counter'] > 0)
            {
                $w=intval(($rec['counter'] / $max) * 100);
                echo "<tr><td  style=\"padding:1px;width:100px;\"><div style=\"border:1px outset $color_counter;background-color:$color_counter;width:{$w}%;height:10px;\"> </div></td><td style=\"color:$color_counter\">&nbsp;{$rec['counter']}</td></tr>";
            }
            echo "</table>";
            echo "</td></tr>";
        }
        $i++;
    }
    if (count($all) > 1)
    {
        echo "<tr style=\"background-color:#ffffda;color:#000000\"><td>";
        if (isset($rec[$fieldname]))
            echo "</td><td  style=\"padding:0px;\" >";
        echo "<table style=\"margin:0px;\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
        if ($total_unique > 0)
        {
            echo "<tr><td style=\"width:100px;padding:1px\"></td><td style=\"color:$color_unique\">&nbsp;{$total_unique}</td></tr>";
        }
        if ($total > 0)
        {
            echo "<tr><td  style=\"width:100px;padding:1px\"></td><td style=\"color:$color_counter\" >&nbsp;{$total}</td></tr>";
        }
        echo "</table>";
        echo "</td></tr>";
    }
    echo "</table>";
}

/**
 * 
 */
function FNSTAT_PageStatistic()
{
    global $_FN;
//---------------config-------------------------------------------------------->	
    $config=FN_LoadConfig("plugins/statistics/config.php");
    $track_ip=$config['track_ip'];
    $track_refer=$config['track_refer'];
    $track_total=$config['track_total'];
    $track_pages=$config['track_pages'];
    $track_data=$config['track_data'];
    $track_agents=$config['track_agents'];
    $antispam=$config['antispam'];
//---------------config--------------------------------------------------------<

    $max_rows=20;
    $limit="";
    $limit="LIMIT 1,$max_rows";
    if ($track_total)
    {
        echo "<h3>".FN_Translate("total visit").":</h3>";
        FNSTAT_PrintStatistic("stats_total","","WHERE 1 ORDER BY counter DESC $limit");
    }
    if ($track_pages)
    {
        echo "<h3>".FN_Translate("pages").":</h3>";
        FNSTAT_PrintStatistic("stats_pages","url","$limit ORDER BY counter DESC");
    }
    if ($track_ip)
    {
        echo "<h3>IP:</h3>";
        FNSTAT_PrintStatistic("stats_ip","ip","$limit ORDER BY counter_unique DESC");
    }
    if ($track_refer)
    {
        echo "<h3>REFER:</h3>";
        FNSTAT_PrintStatistic("stats_ref","url","$limit ORDER BY counter_unique DESC");
    }
    if ($track_agents)
    {
        echo "<h3>Browsers;SO:</h3>";
        FNSTAT_PrintStatistic("stats_browsers","browser","$limit ORDER BY counter_unique DESC");
    }
    if ($track_data)
    {
        echo "<h3>".FN_Translate("data").":</h3>";
        $m=FN_GetParam("m",$_GET,"html");
        $y=FN_GetParam("y",$_GET,"html");
        $opt=FN_GetParam("opt",$_GET,"html");
        if ($m == "" || $y == "")
        {
            $m=date("m");
            $y=date("Y");
        }
        $m=intval($m);
        $y=intval($y);
        $next_m=$m + 1;
        $next_y=$y;
        $prev_y=$y;
        $prev_m=$m - 1;
        if ($next_m > 12)
        {
            $next_m=1;
            $next_y++;
            if ($next_m < 10)
            {
                $next_m="0$next_m";
            }
        }
        if ($prev_m < 1)
        {
            $prev_y--;
            $prev_m=12;
            if ($prev_m < 10)
            {
                $prev_m="0$prev_m";
            }
        }
        if ($m < 10)
        {
            $m="0$m";
        }
        echo "<div id=\"statisticsday\">";
        echo "<a alt=\"\" onclick=\"return fn_to_ajax(this,'statisticsday');\" href=\"?mod={$_FN['mod']}&amp;opt=$opt&amp;m=$prev_m&amp;y=$prev_y\"><img style=\"border:0px;vertical-align:middle\" src=\"images/fn_left.png\" />";
        echo "&nbsp;".FN_Translate("back")."</a> ";
        //--->
        echo " | ".FN_Translate("data").":"." {$y}-{$m} | ";
        //---<
        echo "<a onclick=\"return fn_to_ajax(this,'statisticsday');\" href=\"?mod={$_FN['mod']}&amp;opt=$opt&amp;m=$next_m&amp;y=$next_y\">".FN_Translate("next")." ";
        echo "&nbsp;<img style=\"border:0px;vertical-align:middle\" alt=\"\" src=\"images/fn_right.png\" /></a>";
        FNSTAT_PrintStatistic("stats_date","day","WHERE day LIKE '{$y}-{$m}-%'");
        echo "</div>";
    }
}

?>