<?php

/**
 * @package Flatnux_module_calendar
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * 
 */
if (!function_exists("FNCALENDAR_calendar"))
{

    function FNCALENDAR_calendar($month,$year)
    {
        global $_FN;
        static $id="";
        $fncalendar_id="fncalendar_id".$id;
        $id++;
        $config=FN_LoadConfig();
        if (empty($_GET['mm']) && empty($_GET['yy']))
        {
            $month=$month=intval($month);
            $year=$year=intval($year);
        }
        elseif (!empty($_GET['mm']) && !empty($_GET['yy']))
        {

            $month=$month=FN_GetParam("mm",$_GET,"int");
            $year=$year=FN_GetParam("yy",$_GET,"int");
        }
        $prev_month=$month - 1;
        $next_month=$month + 1;
        $next_year=$prev_year=$year;
        if ($prev_month < 1)
        {
            $prev_month=12;
            $prev_year=$year - 1;
        }
        if ($next_month > 12)
        {
            $next_month=1;
            $next_year=$year + 1;
        }
        $human_month=$_FN['months'];
        $settimana=$_FN['days'];
        $colonne=7;
        $giorni=date("t",mktime(0,0,0,$month,1,$year));  //giorni del mese in questione
        $primo_lunedi=date("w",mktime(0,0,0,$month,1,$year));
        if ($primo_lunedi== 0)
        {
            $primo_lunedi=7;  //siamo mica americani
        }
        echo("<div id=\"$fncalendar_id\"><table>"); //table
        //-------week days--------------------------------------------------------->
        echo "\n\t<tr class=\"txtredB\">\n\t\t<td><div style=\"text-align:center\" ><a onclick=\"return(fn_to_ajax(this,'$fncalendar_id'))\" href=\"{$_FN['siteurl']}?mod={$_FN['mod']}&amp;mm={$prev_month}&amp;yy={$prev_year}\">&lt;&lt;</a></div></td>";
        echo "<td colspan=\"".($colonne - 2)."\"><div style=\"text-align:center\" >".$human_month[(int)$month - 1]." ".$year."</div></td>";
        echo "<td><div style=\"text-align:center\" ><a onclick=\"return(fn_to_ajax(this,'$fncalendar_id'))\" href=\"{$_FN['siteurl']}index.php?mod={$_FN['mod']}&amp;mm={$next_month}&amp;yy={$next_year}\">&gt;&gt;</a></div></td>\n\t</tr>"; //mese/anno
        echo "<tr>";
        for($i=1; $i < 7; $i++)
        {
            $day=substr($settimana[$i],0,2);
            echo("\n\t\t<td height=\"20\" class=\"txtwhiteB\"><div>$day</div></td>");
        }
        $day=substr($settimana[0],0,2);
        echo "\n\t\t<td height=\"20\" class=\"txtwhiteB\"><div>$day</div></td>";
        echo "</tr>";
        //-------week days---------------------------------------------------------<

        for($i=1; $i < $giorni + $primo_lunedi; $i++)
        {
            if ($i % $colonne== 1)
            {
                echo("\n\t<tr>");
            }
            if ($i < $primo_lunedi)
            {
                echo("\n\t\t<td>&nbsp;</td>");
            }
            else
            {
                $day_=$i - ($primo_lunedi - 1);
                $a=strtotime(date($year."-".$month."-".$day_));
                $b=strtotime(date("Y-m-d"));
                $class="blockcalendar_day";
                if ($a== $b)
                {
                    $class="blockcalendar_currentday";
                }
                $link_day=FN_RewriteLink("?mode=archive&amp;mod={$_FN['mod']}&amp;mm=$month&amp;dd=$day_&amp;yy=$year","&amp;",true);
                if ($config['page_target']!= "" && file_exists("sections/{$config['page_target']}"))
                {
                    $link_day=FN_RewriteLink("index.php?mode=archive&amp;mod={$config['page_target']}&amp;mm=$month&amp;dd=$day_&amp;yy=$year","&amp;",true);
                }
                echo "\n\t\t<td class=\"$class\"><div  ><a href=\"$link_day\">".$day_."</a></div></td>";
            }
            if ($i % $colonne== 0)
            {
                echo "\n\t</tr>";
            }
        }
        if ($i % $colonne!= 1)
        {
            while($i % $colonne!= 1)
            {
                echo "<td><div>&nbsp;</div></td>";
                $i++;
            }
            echo "\n\t</tr>";
        }
        echo "\n</table>";
        echo "</div>";
    }

}
//-------------------- show calendar ------------------------------------------>
FNCALENDAR_calendar(date("m"),date("Y"));
//-------------------- show calendar ------------------------------------------<
?>