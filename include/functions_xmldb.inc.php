<?php

/**
 * Flatnux xmldb functions
 *
 * @package Flatnux_functions
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2012
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');

global $_FN;
require_once $_FN['filesystempath']."/include/xmldb_editor.php";

/**
 * htmleditor
 *
 * @param string $name
 * @param string $value
 * @param string $rows
 * @param string $cols
 * @param string $tooltip
 */
function xmldb_frm_field_html_overwrite($name,$value,$rows,$cols,$tooltip)
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
    if ($editor!= "0" && file_exists("include/htmleditors/".$editor."/htmlarea.php"))
    {
        require_once ("include/htmleditors/".$editor."/htmlarea.php");
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
function xmldb_get_lang_img($lang)
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
function xmldb_frm_view_file($params)
{

    global $_FN;
    $databasename=$params['fieldform']->databasename;
    $tablename=$params['fieldform']->tablename;
    $path=$params['fieldform']->path;
    $value=$params['value'];
    $values=$params['values'];
    $attributes=isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
    $tablepath=$params['fieldform']->xmltable->FindFolderTable($values);
    $table=FN_XmlTable($tablename);
    $htmlout="";
    $fileimage=isset($values[$table->primarykey]) ? "$path/$databasename/$tablepath/".$values[$table->primarykey]."/".$params['name']."/".$values[$params['name']] : "";
    $fileimage2=isset($values[$table->primarykey]) ? "".$values[$table->primarykey]."/".$params['name']."/".$values[$params['name']] : "";
    $link=FN_GetParam("QUERY_STRING",$_SERVER);
    $htmlout.="\n<a $attributes title=\"Download $value\" href=\"?$link&xmldb_ddfile_{$params['name']}={$values[$params['name']]}\"  >$value</a>";
    $downloadfile=FN_GetParam("xmldb_ddfile_{$params['name']}",$_GET);

    if ($downloadfile!= "" && $downloadfile == $values[$params['name']])
    {
        $downloadfile=$values[$table->primarykey]."/{$params['name']}/$downloadfile";
        xmldb_go_download($downloadfile,$databasename,$tablename,$path,$tablepath);
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
    $stat=new XMLTable($databasename,$tablename."_download_stat",$_FN['datadir']);
    $val=$stat->GetRecordByPrimaryKey($fileimage2);
    $count=isset($val['numdownload']) ? $val['numdownload'] : 0;
    $st=" | $count Download";
    $htmlout.="&nbsp;($fsize $suff$st)";
    return $htmlout;
}

/**
 *
 * @param $tablename
 * @param $xmldatabase
 * @param $params
 */
function FN_XmltableEditor($tablename,$params=array())
{
    require_once ("include/xmldb_editor.php");
    global $_FN;
    if (empty($params['xmldatabase']))
    {
        $params['xmldatabase']=$_FN['database'];
    }
    $op=FN_GetParam("opt",$_GET,"html");
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
    return XMLDB_editor($tablename,$params);
}

//---------datetime-------------------------------------------->
class xmldbfrm_field_datetime
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
        $idInput="xmldb_bcalendar_".$params['name']."$idcalendar";


        $jscal="DateSeparator='$DateSeparator';return NewCssCal('$idInput', '$dateformat_js','$Navigation_pattern',$Display_time_in_calendar,$Time_mode,$Show_Seconds)";
        //closewin("$idInput"); stopSpin();
        $html.="<input onblur=\"checkclosewin('$idInput');\" onchange=\"dropwin('$idInput');\" $attributes autocomplete=\"off\" onclick=\"$jscal\" $toltips name=\"".$params['name']."\" id=\"xmldb_bcalendar_".$params['name']."$idcalendar\" value=\"".$val."\" />";
//        $html.="<button id=\"xmldb_bcalendar_btn_".$params['name']."$idcalendar\" onclick=\"$jscal\" type=\"button\" ><img style=\"border:0px;vertical-align:middle\" alt = \"\" src=\"".FN_FromTheme("images/calendar.png")."\" /></button>";
//        $html.="<button id=\"xmldb_bcalendar_btn_".$params['name']."$idcalendar\" onclick=\"document.getElementById('$idInput').click();\" type=\"button\" ><img style=\"border:0px;vertical-align:middle\" alt = \"\" src=\"".FN_FromTheme("images/calendar.png")."\" /></button>";
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

//---------datetime--------------------------------------------<
//---------localfile--------------------------------------->
/**
 * 
 * @author alessandro
 *
 */
class xmldbfrm_field_localfile
{

    function show($params)
    {
        global $_FN;
        $fmpath=isset($params['frm_path']) ? $params['frm_path'] : "{$_FN['datadir']}";
        $mime=isset($params['frm_mime']) ? "mime={$params['frm_mime']}&linklocalfs=1&" : "mime=all&linklocalfs=1&";
        $toltips=($params['frm_help']!= "") ? "title=\"".$params['frm_help']."\"" : "";
        $html="";
        $size=isset($params['frm_size']) ? $params['frm_size'] : 30;
        $idop="localfile{$params['name']}";
        $oldvalues=$params['oldvalues'];
        if ($params['value']!= "")
        {
            if (is_dir($params['value']))
                $fmpath=htmlspecialchars($params['value']);
            else
                $fmpath=htmlspecialchars(dirname($params['value']));
        }
        $html.="<input $toltips size=\"".$size."\" name=\"{$params['name']}\" id=\"$idop\" value=\"".str_replace('"','&quot;',$params['value'])."\" />";
        $onclick="tmp = window.open('{$_FN['siteurl']}filemanager.php?{$mime}dir={$fmpath}&local=1&mode=t&filemanager_editor=local&opener=$idop','filemanager','toolbar= 0,location= 0,directories= 0,status= 0,menubar= 0,scrollbars= 1,resizable= 1,width=640,height=480');tmp.focus();return false;";
        $html.="<button onclick=\"$onclick\">".fn_i18n("search")."</button>";
        return $html;
    }

    function view($params)
    {
        global $_FN;
        $html="";
        $val=htmlspecialchars(FN_RelativePath($params['value']));
        if (is_dir($params['value']))
            $html.="<iframe style=\"border:0px;height:400px;width:500px\" src=\"{$_FN['siteurl']}filemanager.php?dir=$val&mime=all\">$val</iframe>";
        else
            $html.="<a href=\"$val\">$val</a>";
        return $html;
    }

}

//---------bbcode--------------------------------------->
//---------bbcode--------------------------------------->
class xmldbfrm_field_bbcode
{

    function __construct()
    {
        
    }

    function show($params)
    {
        $html="";
        $rows=isset($params['frm_rows']) ? $params['frm_rows'] : 4;
        $cols=isset($params['frm_cols']) ? $params['frm_cols'] : 20;
        $tooltip=$params['frm_help'];
        $onkeyup="";
        $style="";
        if ($cols == "auto")
        {
            $cols="80";
            $style="width:90%;";
        }
        if ($rows == "auto")
        {
            $onkeyup="onkeyup=\"if (this.scrollHeight >= this.offsetHeight){ this.style.height = 10 + this.scrollHeight+'px';}\" ";
            $onkeyup.="onfocus=\"if (this.scrollHeight >= this.offsetHeight){ this.style.height = 10 + this.scrollHeight+'px';}\" ";
            $onkeyup.="style=\"overflow:auto;height:30px;\"";
            $rows=3;
        }
        $html="";
        $html.=FN_HtmlBbcodesJs();
        $html.=FN_HtmlBbcodesPanel($params['name'],"formatting");
        $html.=FN_HtmlBbcodesPanel($params['name'],"emoticons");
        $html.="<br /><textarea style=\"$style\" $onkeyup title=\"$tooltip\" cols=\"".$cols."\"  rows=\"".$rows."\"  name=\"{$params['name']}\"  >";
        $html.=htmlspecialchars($params['value']);
        $html.="</textarea>";
        return $html;
    }

    function view($params)
    {
        $html="";
        $html.=FN_Tag2Html(str_replace("\n","<br />",htmlspecialchars($params['value'])));
        return $html;
    }

}

//---------bbcode---------------------------------------<
//---------password--------------------------------------->
class xmldbfrm_field_md5password
{

    function show($params)
    {
        $toltips=($params['frm_help']!= "") ? "title=\"".$params['frm_help']."\"" : "";
        return "<input $toltips value=\"\" autocomplete=\"off\" name=\"".$params['name']."\" type=\"password\" />\n";
    }

    function formtovalue($str)
    {
        return md5($str);
    }

}

//---------password---------------------------------------<
/**
 * multi checkbox  field
 */
class xmldbfrm_field_multicheck
{

    function __construct()
    {
        
    }

    function show($params)
    {
        static $i =0;
        $i++;
//        $inputid_prefix=md5(serialize($params));
        $inputid_prefix = "$i";
        $html="
<script type=\"text/javascript\" >
var synccheck{$inputid_prefix}_{$params['name']} = function (id)
{
	var divitems = document.getElementById( id ).childNodes;
	var sep='';
	var str='';
	for (var i in divitems)
	{
		items=divitems[i].childNodes;
		for (var i in items)
		{
			if (items[i].checked == true )
			{
				str = str+ sep + items[i].value
				sep=',';
			}
		}
	}
	document.getElementById('xmldbvalue{$inputid_prefix}_{$params['name']}').value=str;
};
</script>
";

        $tooltip=$params['frm_help'];
        $name=$params['name'];
        $value=$params['value'];
        //dprint_r($params);
        $options=array();
        if (!isset($params['options']))
        {
            $options=explode(",",$params['frm_options']);
            $optionslang=array();
            if (isset($params['frm_options_'.$params['lang']]))
            {
                $optionslang=explode(",",$params['frm_options_'.$params['lang']]);
            }
        }
        else
        {
            $_options=$params['options'];
            if (is_array($_options))
                foreach($_options as $opt)
                {
                    $options[$opt['value']]=$opt['title'];
                }
        }
        $i=0;
        $toenable=$todisable="";
        $jexecute="synccheck{$inputid_prefix}_{$params['name']}('xmldbck{$inputid_prefix}_{$params['name']}');";
        $html.="<div id=\"xmldbck{$inputid_prefix}_{$params['name']}\" >";
        foreach($options as $k=> $option)
        {
            $jsonclick="onclick=\"$jexecute\" onchange=\"$jexecute\" ";
            $sel="";
            $toption=$option;
            if (isset($optionslang[$i]) && $optionslang[$i]!= "")
                $toption=$optionslang[$i];
            if (FN_erg("^$k\$",$value) || FN_erg(",$k,",$value) || FN_erg("^$k,",$value) || FN_erg(",$k\$",$value))
                $sel="checked=\"checked\"";
            $html.="<label style=\"white-space:nowrap\" ><input $sel $jsonclick type=\"checkbox\" value=\"$k\" title=\"$tooltip\" name=\"__xmldb_multicheck_".$params['name']."\"  />&nbsp;$toption</label>&nbsp;&nbsp; ";
            $i++;
        }
        $html.="<input type=\"hidden\" id=\"xmldbvalue{$inputid_prefix}_{$params['name']}\" name=\"$name\" value=\"$value\" />";
        $html.="</div>";
        $html.="<script type=\"text/javascript\"  >setTimeout(\"$jexecute\",0);</script>";
        return $html;
    }

}

//---------string--------------------------------------->
class xmldbfrm_field_stringselect
{

    function __construct()
    {
        
    }

    function show($params)
    {
        static $ik=0;
        $id_field=$ik."_".$params['name'];
        $options=false;
        $html="<div onmouseover=\"selectover{$id_field}=true\" onmouseout=\"selectover{$id_field}=false\" >";

        if ($options == false)
        {
            $options=array();
            $options_tmp=is_array($params['options']) ? $params['options'] : array();
            /*
              $all = $params['fieldform']->xmltable->GetRecords(false,false,false,false,false,"{$params['name']}");
              // GetRecords($restr = false,$min = false,$length = false,$order = false,$reverse = false,$fields = false)
              foreach ($all as $v)
              {
              $options[]=$v[$params['name']];
              }
              $options = array_unique($options);
             */
            foreach($options_tmp as $option_tmp)
            {
                $options[$option_tmp['value']]=$option_tmp;
            }
            $options=FN_ArraySortByKey($options,"title");

            //dprint_r($options);
            $html.="
<script type=\"text/javascript\" defer=\"defer\">
selectover{$id_field} = false;
xmldb_field_stringselect=function (idselect,value)
{
    var select = document.getElementById(idselect);
    var alloptions = select.options;
    var tosearch = false;
    var i = 0;
    var found = false;
    for (i in alloptions)
    {
        if (alloptions[i].value != undefined && alloptions[i].value != '')
        {
            tosearch = ''+alloptions[i].value.toLowerCase() + ' '+ alloptions[i].text.toLowerCase();
			try{
            if (tosearch != '' && tosearch.search(value.toLowerCase())<0)
            {
                alloptions[i].disabled = 'disabled';
                alloptions[i].style.display = 'none';
            }
            else
            {
                alloptions[i].style.display = 'block';
                found=true;
                alloptions[i].disabled = false;
                
            }
			}catch(e){alloptions[i].style.display = 'block';
                found=true;
                alloptions[i].disabled = false;}
       }
    }
    if (found)
    {
        select.style.display='block';
    }
    else
    {
        select.style.display='none';
    }
};

</script>

";
        }
        $ik++;
        $size=isset($params['frm_size']) ? $params['frm_size'] : 30;
        $l=(!empty($params['size'])) ? "maxlength=\"{$params['size']}\"" : "";
        $html.="<span style=\"position:relative;\"><input  
        id=\"xmldb_{$id_field}\" $l 
        title=\"{$params['frm_help']}\" 
        size=\"".$size."\" name=\"{$params['name']}\"  
        style=\"margin-right:0px;\" 
        autocomplete=\"off\" 
        onkeyup=\"xmldb_field_stringselect('xmldb_{$id_field}_s',this.value)\" 
        onfocus=\"xdb_show_menu('xmldb_{$id_field}_s');\"   
        onclick=\"xdb_show_menu('xmldb_{$id_field}_s');\" 
        id=\"xmldb_{$id_field}\" $l title=\"{$params['frm_help']}\" size=\"".$size."\" 
        name=\"{$params['name']}\"  ";
        if (!empty($params['frm_uppercase']) && $params['frm_uppercase'] == "uppercase")
        {
            $html.="onchange=\"this.value=this.value.toUpperCase();\"";
        }
        elseif (!empty($params['frm_uppercase']) && $params['frm_uppercase'] == "lowercase")
        {
            $html.="onchange=\"this.value=this.value.toLowerCase();\"";
        }
        $html.="value=\"".str_replace('"','&quot;',$params['value'])."\" />";
        $html.="<img style=\"margin-left:0px;vertical-align:middle;border-left:0px;cursor:pointer\" onclick=\"xdb_toggle_menu('xmldb_{$id_field}_s')\" alt=\"+\" src=\"".FN_FromTheme("images/fn_down.png")."\" /><br />";


        if (is_array($options))
        {
            $size=count($options);
            if ($size<= 1)
                $size=2;

            if ($size > 5)
                $size=5;
            $html.="<select  id=\"xmldb_{$id_field}_s\"  style=\"z-index:1;position:absolute;display:none\" size=\"$size\"  onchange=\"document.getElementById('xmldb_{$id_field}').value = this.options[this.selectedIndex].value;this.style.display='none'\" >";
            foreach($options as $option)
            {
                $value=htmlentities($option['value'],ENT_QUOTES,$params['fieldform']->charset_page);
                $html.="<option onclick=\"xmldb_{$id_field}_s.onchange();\" value=\"$value\">{$option['title']}</option>";
            }
            $html.="</select>";
        }
        $html.="&nbsp;</span>";
        $html.="
<script type=\"text/javascript\" defer=\"defer\">
xdb_toggle_menu=function(id){
	if(document.getElementById(id).style.display == 'none')
	{
		xdb_show_menu (id);
	} 
	else 
	{
		xdb_hide_menu(id);	
	}
};
xdb_show_menu=function(id)
{
	document.getElementById(id).style.display = 'block';
	
};
xdb_hide_menu=function(id)
{
	document.getElementById(id).style.display ='none';
	
};
xmldb_{$id_field}_s_hideshow = function()
{
	if (!selectover{$id_field})
		xdb_hide_menu('xmldb_{$id_field}_s');
};
oldonclick = document.getElementsByTagName('body')[0].getAttribute('onclick');
document.getElementsByTagName('body')[0].setAttribute('onclick',oldonclick+\";xmldb_{$id_field}_s_hideshow()\");
</script>

";
        $html.="</div>";
        return $html;
    }

    function view($params)
    {
        //$params['options']=  array_unique($params['options']);
        //dprint_r($params['value']);
        $html=htmlspecialchars($params['value']);
        return $html;
    }

}

//---------string---------------------------------------<
//---------uppercase--------------------------------------->
class xmldbfrm_field_varchar_uppercase
{

    function show($params)
    {
        $toltips=($params['frm_help']!= "") ? "title=\"".$params['frm_help']."\"" : "";
        $size=isset($params['frm_size']) ? $params['frm_size'] : 30;
        //-----------------------------------------
        $html="<script type=\"text/javascript\">
		var check_upper = function(input,change)
		{
			var str = input.value.toString();
			if (change)
			{
				str = str.replace(/\\s+$/,'');
				str = str.replace(/^\\s+/,'');
			}
			str = str.toUpperCase();
			input.value = str;
		};
		</script>";
        $oldvalues=$params['oldvalues'];
        $tooltip=$params['frm_help'];
        $suff="";
        if (isset($params['frm_suffix']))
            $suff=$params['frm_suffix'];
        $html.="<input onchange=\"check_upper(this,true)\"  title=\"$tooltip\" size=\"".$size."\" name=\"{$params['name']}\"  value=\"".str_replace('"','\\"',$params['value'])."\" />$suff";
        //-----------------------------------------
        return $html;
    }

}

//---------uppercase---------------------------------------<
//---------lowercase--------------------------------------->
class xmldbfrm_field_varchar_lowercase
{

    function show($params)
    {
        $toltips=($params['frm_help']!= "") ? "title=\"".$params['frm_help']."\"" : "";
        $size=isset($params['frm_size']) ? $params['frm_size'] : 30;
        //-----------------------------------------
        $html="<script type=\"text/javascript\">
		var check_lower = function(input,change)
		{
			var str = input.value.toString();
			if (change)
			{
				str = str.replace(/\\s+$/,'');
				str = str.replace(/^\\s+/,'');
			}
			str = str.toLowerCase();
			input.value = str;
		};
		</script>";
        $oldvalues=$params['oldvalues'];
        $tooltip=$params['frm_help'];
        $suff="";
        if (isset($params['frm_suffix']))
            $suff=$params['frm_suffix'];
        $html.="<input onchange=\"check_lower(this,true)\"  title=\"$tooltip\" size=\"".$size."\" name=\"{$params['name']}\"  value=\"".str_replace('"','\\"',$params['value'])."\" />$suff";
        //-----------------------------------------
        return $html;
    }

}

//---------lowercase---------------------------------------<
/**
 * multi checkbox  field
 */
class xmldbfrm_field_multiselect
{

    function __construct()
    {
        
    }

    function show($params)
    {
        global $_FN;
        static $i = 0;
        $i++;
        $inputid_prefix="_{$i}_";
        $html="
<script type=\"text/javascript\" >
var synccheck{$inputid_prefix}_{$params['name']} = function ()
{
    var Obj = document.getElementById('right_$inputid_prefix');
    var str = '';
    var sep = '';
    if (Obj.options){
        var listOptions=Obj.getElementsByTagName('option')
        for (var i in listOptions)
        {
            //    console.log('i='+i+'val='+listOptions[i].value);

            if (!isNaN(i) && listOptions[i] != undefined && listOptions[i].value != undefined)
            {
                str+=sep+listOptions[i].value;
                sep = ',';
            }
        }
    }
    document.getElementById('xmldbvalue{$inputid_prefix}_{$params['name']}').value=str;
};
var toright{$inputid_prefix}_{$params['name']} = function ()
{
    var Obj = document.getElementById('left_$inputid_prefix');
    var selIndex = Obj.selectedIndex;
    try{var selObj = Obj.options[selIndex];}catch (e){return;}
    var newObj = selObj.cloneNode(true);
    document.getElementById('right_$inputid_prefix').appendChild(newObj);
    Obj.removeChild(selObj);
    synccheck{$inputid_prefix}_{$params['name']}();
};
var toleft{$inputid_prefix}_{$params['name']} = function ()
{
    var Obj = document.getElementById('right_$inputid_prefix');
		
    var selIndex = Obj.selectedIndex;
    try{var selObj = Obj.options[selIndex];}catch (e){return;}
    var newObj = selObj.cloneNode(true);
    document.getElementById('left_$inputid_prefix').appendChild(newObj);
    Obj.removeChild(selObj);
    synccheck{$inputid_prefix}_{$params['name']}();

};
</script>
";

        $tooltip=$params['frm_help'];
        $name=$params['name'];
        $value=$params['value'];

        $options=array();
        if (!isset($params['options']))
        {
            $options=explode(",",$params['frm_options']);
            $optionslang=array();
            if (isset($params['frm_options_'.$params['lang']]))
            {
                $optionslang=explode(",",$params['frm_options_'.$params['lang']]);
            }
        }
        else
        {
            $_options=$params['options'];
            if (is_array($_options))
                foreach($_options as $opt)
                {
                    $options[$opt['value']]=$opt['title'];
                }
        }
        $i=0;
        $toenable=$todisable="";
        $html.="<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"padding:0px;\"><tr><td  style=\"padding:0px;\"><select style=\"width:200px;\"  id=\"left_$inputid_prefix\" size=\"5\">";
        foreach($options as $k=> $option)
        {

            $sel="";
            $toption=$option;
            if (isset($optionslang[$i]) && $optionslang[$i]!= "")
                $toption=$optionslang[$i];
            if (FN_erg("^$k\$",$value) || FN_erg(",$k,",$value) || FN_erg("^$k,",$value) || FN_erg(",$k\$",$value))
            {
                
            }
            else
            {
                $html.="<option  value=\"".htmlentities($k,ENT_QUOTES,$_FN['charset_page'])."\" title=\"".htmlentities($toption,ENT_QUOTES,$_FN['charset_page'])."\" name=\"__xmldb_multiselect_".$params['name']."\"  />".htmlentities($toption,ENT_QUOTES,$_FN['charset_page'])."</option> ";
            }
            $i++;
        }
        $html.="</select></td><td style=\"padding:0px;\" >";
        $html.="<img style=\"cursor:pointer\" onclick=\"toleft{$inputid_prefix}_{$params['name']}()\" alt=\"\" src=\"{$_FN['siteurl']}images/left.png\" /><br />";
        $html.="<img style=\"cursor:pointer\" onclick=\"toright{$inputid_prefix}_{$params['name']}()\" alt=\"\" src=\"{$_FN['siteurl']}images/right.png\" />";
        $html.="</td><td style=\"padding:0px;\" ><select  style=\"width:200px;\"  id=\"right_$inputid_prefix\" size=\"5\">";
        foreach($options as $k=> $option)
        {
            $sel="";
            $toption=$option;
            if (isset($optionslang[$i]) && $optionslang[$i]!= "")
                $toption=$optionslang[$i];
            if (FN_erg("^$k\$",$value) || FN_erg(",$k,",$value) || FN_erg("^$k,",$value) || FN_erg(",$k\$",$value))
                $html.="<option  value=\"".htmlentities($k,ENT_QUOTES,$_FN['charset_page'])."\" title=\"$tooltip\" name=\"__xmldb_multiselect_".$params['name']."\"  />$toption</option> ";
            $i++;
        }

        $html.="</select></td><td>";

        $html.="</td></tr></table>";
        $html.="<input  type=\"hidden\" id=\"xmldbvalue{$inputid_prefix}_{$params['name']}\" name=\"$name\" value=\"".htmlentities($value,ENT_QUOTES,$_FN['charset_page'])."\"  />";
        $html.="<script type=\"text/javascript\"  >setTimeout(\"synccheck{$inputid_prefix}_{$params['name']}()\",0);</script>";
        return $html;
    }

}

function XMLDBEDITOR_IsAdmin($user=false)
{
    return FN_IsAdmin($user);
}

?>