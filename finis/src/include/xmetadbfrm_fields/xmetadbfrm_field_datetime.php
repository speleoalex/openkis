<?php
//---------datetime-------------------------------------------->
class xmetadbfrm_field_datetime
{

    function show($params)
    {
        global $_FN;
        static $idcalendar=0;
        $idcalendar++;
        $html="";
        $attributes=isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";

        if ($idcalendar == 1)
        {
            $html.="
<script type=\"text/javascript\">                
function initCalendarLang()
{
    WeekDayName1 = [\"{$_FN['days'][0]}\", \"{$_FN['days'][1]}\", \"{$_FN['days'][2]}\", \"{$_FN['days'][3]}\", \"{$_FN['days'][4]}\", \"{$_FN['days'][5]}\", \"{$_FN['days'][6]}\"];
    WeekDayName2 = [\"{$_FN['days'][1]}\", \"{$_FN['days'][2]}\", \"{$_FN['days'][3]}\", \"{$_FN['days'][4]}\", \"{$_FN['days'][5]}\", \"{$_FN['days'][6]}\", \"{$_FN['days'][0]}\"];
    MonthName = new Array
    (
";
            $v="";
            foreach($_FN['months'] as $g)
            {
                $html.="\n$v\"$g\"";
                $v=",";
            }
            $html.="
    );
};
if (window.addEventListener) {
    window.addEventListener('load', function () {
        initCalendarLang();
    })
    } else {
        window.attachEvent('onload', function () {
            initCalendarLang();
        })    
}
</script>";
        }
        $toltips=($params['frm_help']!= "") ? "title=\"".$params['frm_help']."\"" : "";
        $dateformat="y-mm-dd 00:00:00";

        if (isset($params['frm_dateformat']) && $params['frm_dateformat']!= "")
            $dateformat=$params['frm_dateformat'];
        //if (isset($_POST[$params['name']]))
        //    $params['value']=$this->formtovalue($params['value'],$params);
        $val=$this->valuetoform($params['value'],$dateformat);
        $dateformat_js=$dateformat;
        $dateformat_js=str_replace("y","YYYY",$dateformat_js);

        $DateSeparator="";
        if (strpos($dateformat_js,"/")!== false)
        {
            $DateSeparator="/";
        }
        elseif (strpos($dateformat_js,"-")!== false)
        {
            $DateSeparator="-";
        }
        $dateformat_js=str_replace("-","",$dateformat_js);
        $dateformat_js=str_replace("/","",$dateformat_js);
        $Navigation_pattern="arrow";
        if (!empty($params['calendar_dropdown']))
        {
            $Navigation_pattern="dropdown";
        }
        $Display_time_in_calendar="false";
        $Time_mode=24;
        $Show_Seconds="false";
        //hh:mm:ss
        if (strpos($dateformat," 00:00:00")!== false)
        {
            $Display_time_in_calendar="true";
            $Show_Seconds="true";
            $dateformat_js=str_replace(" 00:00:00","",$dateformat_js);
        }
        //hh:mm
        elseif (strpos($dateformat," 00:00")!== false)
        {
            $Display_time_in_calendar="true";
            $Show_Seconds="false";
            $dateformat_js=str_replace(" 00:00","",$dateformat_js);
        }
        $idInput="xmetadb_bcalendar_".$params['name']."$idcalendar";


        $jscal="DateSeparator='$DateSeparator';return NewCssCal('$idInput', '$dateformat_js','$Navigation_pattern',$Display_time_in_calendar,$Time_mode,$Show_Seconds)";
        //closewin("$idInput"); stopSpin();
        $html.="<input onblur=\"checkclosewin('$idInput');\" onchange=\"dropwin('$idInput');\" $attributes autocomplete=\"off\" onclick=\"$jscal\" $toltips name=\"".$params['name']."\" id=\"xmetadb_bcalendar_".$params['name']."$idcalendar\" value=\"".$val."\" />";
//        $html.="<button id=\"xmetadb_bcalendar_btn_".$params['name']."$idcalendar\" onclick=\"$jscal\" type=\"button\" ><img style=\"border:0px;vertical-align:middle\" alt = \"\" src=\"".FN_FromTheme("images/calendar.png")."\" /></button>";
//        $html.="<button id=\"xmetadb_bcalendar_btn_".$params['name']."$idcalendar\" onclick=\"document.getElementById('$idInput').click();\" type=\"button\" ><img style=\"border:0px;vertical-align:middle\" alt = \"\" src=\"".FN_FromTheme("images/calendar.png")."\" /></button>";
//        $html.="<img style=\"border:0px;vertical-align:middle\" alt = \"\" src=\"".FN_FromTheme("images/calendar.png")."\" />";
        return $html;
    }

    function view($params)
    {
        $dateformat="y-mm-dd 00:00:00";
        if (isset($params['frm_dateformat']) && $params['frm_dateformat']!= "")
            $dateformat=$params['frm_dateformat'];
        if (isset($params['view_dateformat']) && $params['view_dateformat']!= "")
            $dateformat=$params['view_dateformat'];


        $val=$this->valuetoform($params['value'],$dateformat);
        return $val;
    }

    /**
     *
     * @param string $str
     * @param array $params
     * @return string 
     */
    function formtovalue($str,$params)
    {
        if ($str == "")
            return "";
        $dateformat="y-mm-dd 00:00:00";
        if (isset($params['frm_dateformat']) && $params['frm_dateformat']!= "")
            $dateformat=$params['frm_dateformat'];
        $dateformatFORM=strtolower($dateformat);
        $LocalMonthDay=0;
        $LocalMonth=0;
        $LocalYear=0;
        $LocalHour24=0;
        $LocalMinute=0;
        $LocalSecond=0;
        $dateformatFORM=str_replace("y","yyyy",$dateformatFORM);
        //day
        $posd=strpos($dateformatFORM,"dd");
        if ($posd!== false)
            $LocalMonthDay=substr($str,$posd,2);
        //month
        $posm=strpos($dateformatFORM,"mm");
        if ($posm!== false)
            $LocalMonth=substr($str,$posm,2);
        //year
        $posy=strpos($dateformatFORM,"yyyy");
        if ($posy!== false)
            $LocalYear=substr($str,$posy,4);

        $poshms=strpos($dateformatFORM,"00:00:00");
        $poshm=strpos($dateformatFORM,"00:00");
        $posh=strpos($dateformatFORM,"00");
        if ($poshms!== false)
        {
            $LocalHour24=substr($str,$poshms,2);
            $LocalMinute=substr($str,$poshms + 3,2);
            $LocalSecond=substr($str,$poshms + 6,2);
        }
        elseif ($poshm!== false)
        {
            $LocalHour24=substr($str,$poshm,2);
            $LocalMinute=substr($str,$poshm + 3,2);
        }
        elseif ($posh!== false)
        {
            $LocalHour24=substr($str,$poshms,2);
        }
        $timestamp=mktime(intval($LocalHour24),intval($LocalMinute),intval($LocalSecond),intval($LocalMonth),intval($LocalMonthDay),intval($LocalYear));
        $strdate=date('Y-m-d H:i:s',$timestamp);
        return $strdate;
    }

    /**
     *
     * @param string $strdate
     * @param string $dateformat
     * @return string 
     */
    function valuetoform($strdate,$dateformat)
    {
        if ($strdate == "" || $strdate == "0000-00-00 00:00:00")
            return "";
        $dateformat=str_replace("y","Y",$dateformat);
        $dateformat=str_replace("00:00:00","H:i:s",$dateformat);
        $dateformat=str_replace("00:00","H:i",$dateformat);
        $dateformat=str_replace("00","H",$dateformat);
        $dateformat=str_replace("mm","m",$dateformat);
        $dateformat=str_replace("dd","d",$dateformat);
        $dateObj=$date=DateTime::createFromFormat($dateformat,$strdate);
        if ($dateObj)
        {
            $time=$dateObj->getTimestamp();
            $strformdate=date($dateformat,$time);
        }
        else
        {
            $time=strtotime($strdate);
            $strformdate=date($dateformat,$time);
        }
        return $strformdate;
    }

}
