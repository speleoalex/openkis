<?php

ob_start();
global $_FN;
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
FN_LoadMessagesFolder("extra/openkis");
$exclude=FN_GetParam("exclude",$_GET,"flat");
$minimal=FN_GetParam("minimal",$_GET,"flat");
$codes=FN_GetParam("filter_code",$_GET,"flat");
$mod=$_FN['mod'];
if ($mod== "")
{
    $mod="caves";
}
foreach($_REQUEST as $k=> $v)
{
    $params[$k]=$v;
}
if (!file_exists("sections/$mod"))
{
    die();
}
$config=FN_LoadConfig("modules/dbview/config.php",$mod);
$dbview = new FNDBVIEW($config);
$tablename=$config['tables'];
$params['fields']="code,latitude,longitude,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total,meteorology,fauna,closed";
$params['filter_code']=$codes;
$idresult=false;
openkis_UpdateCoords("caves");
$results=$dbview->GetResults(false,$params,$idresult);
$cache =FN_GetGlobalVarValue("js$idresult");
if (!empty($cache))
{
   // PrintJs($cache);
}
$tplstring="OPS_Map.addMarkerPopup({lon}, {lat}, \"{icon}\", \"{title}\",\"{abstract}\",{size});\n";

$cx=0;
$str="";
foreach($results as $item)
{
    $kmlitem=array();
    if (!empty($item['latitude']) && !empty($item['longitude']))
    {
        $kmlitem=$item;
        $lat_=openkis_floatfrmt($item['latitude']);
        $lon_=openkis_floatfrmt($item['longitude']);
        $kmlitem['lat']=$lat_;
        $kmlitem['lon']=$lon_;
        $elevation=$item['elevation'];
        $lenght_total=$item['lenght_total'];
        $depth_total=$item['depth_total'];
        //-----dimensione icona------------------------------------------------>
        $size="0.3";
        $maxdis=$depth_total;
        if ($maxdis<= 2 && $lenght_total<= 8)
            $size="0.2";
        if ($maxdis>= 10 || $lenght_total>= 20)
            $size="0.25";
        if ($maxdis>= 20 || $lenght_total>= 30)
            $size="0.26";
        if ($maxdis>= 30 || $lenght_total>= 40)
            $size="0.27";
        if ($maxdis>= 40 || $lenght_total>= 60)
            $size="0.28";
        if ($maxdis>= 50 || $lenght_total>= 80)
            $size="0.29";
        if ($maxdis>= 100 || $lenght_total>= 500)
            $size="0.3";
        if ($maxdis>= 200 || $lenght_total>= 1000)
            $size="0.35";
        if ($maxdis>= 200 || $lenght_total>= 1500)
            $size="0.4";
        //-----dimensione icona------------------------------------------------<
        if ($minimal)
        {
            $description="{$item['name']}<br />";
            $description.="Q.$elevation SV.$lenght_total P.$depth_total<br />";
            $url=FN_RewriteLink("index.php?mod=$mod&op=view&id={$item['id']}","&",true);
            $description.="<br /><a onclick=\"try{miaPopUp=window.open(this.href,'result','width=1024,height=768,scrollbars=yes');miaPopUp.focus();return false;}catch(e){return true;}\" style=\"cursor:pointer\" target=\"_blank\" href=\"$url\" >".FN_Translate("open")."</a>";
        }
        else
        {
            $description="<div>";
            if (!empty($item['synonyms']))
            {
                $description.="<em>{$item['synonyms']}</em><br />";
            }
            $description.="Q.$elevation SV.$lenght_total P.$depth_total";
            $description.="<br /><a onclick=\"try{miaPopUp=window.open(this.href,'result','width=1024,height=768,scrollbars=yes');miaPopUp.focus();return false;}catch(e){return true;}\" style=\"cursor:pointer\" target=\"_blank\" href=\"{$_FN['siteurl']}index.php?mod=$mod&op=view&id={$item['id']}\" >".FN_Translate("view")."</a><br /><a target=\"_blank\" href='https://www.google.it/maps/dir//$lat_,$lon_/@$lat_,$lon_,18z' >".FN_Translate("open route")."</a>";
            $description=str_replace("\t"," ",$description);
        }
        $description=FN_FixEncoding("$description");
        $kmlitem['abstract']=$description;
        
        
        if ($minimal)
        {
            $title=htmlspecialchars($item['code']);
        }
        else
        {
            $title=htmlspecialchars($item['code']."-".$item['name']);
        }
        $siteurl = str_replace("https://","//",$_FN['siteurl']);
        $siteurl = str_replace("http://","//",$siteurl);
        $icon=$siteurl.openkis_GetIcon($item,$mod);
        /* 88x128 */
        if ($elevation== "")
        {
            $elevation="0";
        }
        $kmlitem['title']=htmlentities($title,ENT_QUOTES,"UTF-8");
        $kmlitem['title']=htmlentities($kmlitem['title'],ENT_QUOTES,"UTF-8");
        $kmlitem['abstract']="$description";
        $kmlitem['size']="$size";
        $kmlitem['icon']="$icon";
        $kmlitem['elevation']=$elevation;
        $kmlitem['abstract']=str_replace("\"","\\\"",$kmlitem['abstract']);
        //$kmlitem['abstract']="x";
        $cx++;
        $str.=FN_TPL_ApplyTplString($tplstring,$kmlitem);
        
    }
}

if (isset($_GET['debug']))
{
    dprint_r(__FILE__." ".__LINE__." : pre tpl ".FN_GetExecuteTimer());
}
//dprint_r($tplvars);
if (isset($_GET['debug']))
{
    dprint_r(__FILE__." ".__LINE__." : post tpl ".FN_GetExecuteTimer());
}
$data=$_FN['sitename']."-".FN_now();
$data=str_replace(" ","_",str_replace(":","-",$data));
$filename="$data-$mod.kml";
if ( "$idresult")
{
    FN_SetGlobalVarValue("js$idresult",$str);
}
PrintJs($str,$filename);

/**
 * 
 * @global type $_FN
 * @param type $str
 * @param type $filename
 */
function PrintJs($str)
{
    global $_FN;
    $str=FN_FixNewline($str);
    $data=$_FN['sitename']."-".FN_now();
    $data=str_replace(" ","_",str_replace(":","-",$data));
    if (isset($_GET['debug']))
    {
        dprint_r(htmlspecialchars($str));
        dprint_r(__FILE__." ".__LINE__." : ".FN_GetExecuteTimer());
        die();
    }
    while(false!== @ob_end_clean()
    );
    $len=strlen($str);
    print($str);
    exit();
}

?>
