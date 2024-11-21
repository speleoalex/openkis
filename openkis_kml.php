<?php
ob_start();
global $_FN;
require_once "include/flatnux.php";
require_once "modules/dbview/FNDBVIEW.php";
FN_LoadMessagesFolder("extra/openkis");
$_FN['enable_compress_gzip']=1;

$exclude=FN_GetParam("exclude",$_GET,"flat");
$minimal=FN_GetParam("minimal",$_GET,"flat");
$codes=FN_GetParam("filter_code",$_GET,"flat");
$zoom=FN_GetParam("zoom",$_GET,"html");
$nocache=FN_GetParam("nocache",$_GET,"flat");
$absolute=FN_GetParam("absolute",$_GET,"html");
$iconsize = FN_GetParam("iconsize",$_GET,"html");        
$iconsize = floatval($iconsize);
$big_icons=!empty($_GET['big_icons']);
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
$dbview=new FNDBVIEW($config);
$tablename=$config['tables'];
$table=FN_XmlTable($tablename);
$fields_to_read=explode(",","code,latitude,longitude,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total,meteorology,fauna,hydrology,closed,photo1,xxx");
foreach($fields_to_read as $field)
{
    if (isset($table->fields[$field]))
    {
        $fields[]=$field;
    }
}

$params['fields']=implode(",",$fields);
$params['filter_code']=$codes;
$idresult=false;
if (isset($_GET['debug']))
{
    dprint_r(__FILE__."  ".__LINE__." : ".FN_GetExecuteTimer());
}
openkis_UpdateCoords("caves");
openkis_UpdateCoords("artificials");
openkis_UpdateCoords("springs");
if (isset($_GET['debug']))
{
    dprint_r(__FILE__."  ".__LINE__." : ".FN_GetExecuteTimer());
}

$data=$_FN['sitename']."-".FN_now();
$forcename=FN_GetParam("filename",$_GET,"html");
$data=str_replace(" ","_",str_replace(":","-",$data));
if ($forcename)
{
    $filename="$forcename.kml";
}
else
{
    $filename="$data-$mod.kml";
}

$results=$dbview->GetResults(false,$params,$idresult);
//evita di ricalcolare tutto se non è cambiato niente nel db o in questo file-->
$idcache="$idresult$minimal$big_icons$absolute$iconsize";
if ($_FN['enable_compress_gzip'])
{
    $idcache.=".gz";
}
$maxtime=max(filectime(__FILE__),$table->GetLastUpdateTime());
$cache=FN_GetGlobalVarValue("$idcache",$maxtime);
if (empty($absolute) && !empty($cache) && empty($nocache))
{
    PrintKml($cache,$filename);
}
//evita di ricalcolare tutto se non è cambiato niente nel db o in questo file--<
$tplstring=file_get_contents("openkis_kml.tp.xml");
$tplvars['sourceurl']=$_FN['siteurl'];
$tplvars['name']=$tablename;
$tplvars['items']=array();

$cx=0;
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
        $elevation=isset($item['elevation']) ? $item['elevation'] : "";
        $lenght_total=isset($item['lenght_total']) ? $item['lenght_total'] : "";
        $depth_total=isset($item['depth_total']) ? $item['depth_total'] : "";
        $elevation_txt=isset($item['elevation']) ? "Q.".$item['elevation'] : "";
        $lenght_total_txt=isset($item['lenght_total']) ? "SV.".$item['lenght_total'] : "";
        $depth_total_txt=isset($item['depth_total']) ? "P.".$item['depth_total'] : "";
        //-----dimensione icona------------------------------------------------>

        $size=0.3;
        $maxdis=$depth_total;
        if ($maxdis<= 2 && $lenght_total<= 8)
            $size=0.2;
        if ($maxdis>= 10 || $lenght_total>= 20)
            $size=0.25;
        if ($maxdis>= 20 || $lenght_total>= 30)
            $size=0.26;
        if ($maxdis>= 30 || $lenght_total>= 40)
            $size=0.27;
        if ($maxdis>= 40 || $lenght_total>= 60)
            $size=0.28;
        if ($maxdis>= 50 || $lenght_total>= 80)
            $size=0.29;
        if ($maxdis>= 100 || $lenght_total>= 500)
            $size=0.3;
        if ($maxdis>= 200 || $lenght_total>= 1000)
            $size=0.35;
        if ($maxdis>= 200 || $lenght_total>= 1500)
            $size=0.4;
        if ($maxdis== 0 && $lenght_total== 0)
        {
            $size=0.2;
        }
        //-----dimensione icona------------------------------------------------<
        if ($big_icons)
        {
            $size=$size * 3;
        }
        if ($iconsize>0)
        {
            $size=$size * $iconsize;

        }
        $size=str_replace(",",".",$size);
        $url=FN_RewriteLink("index.php?mod=$mod&op=view&id={$item['id']}","&",true);
        //$description="{$item['hydrology']}";
        $description="";
        if ($minimal)
        {
            if ($item['photo1'])
            {
                $img=$table->getThumbPath($item,"photo1");
                if (file_exists($img))
                {
                    $img=$_FN['siteurl'].$img;
                }
                $description.="<img style=\"height:100px;\" src=\"$img\" /><br />";
            }
            $description.="$elevation_txt $lenght_total_txt $depth_total_txt<br />";
            $description.="<div class=\"text-right\"><a  onclick=\"try{window.open(this.href,'r','scrollbars=yes').focus();return false;}catch(e){return true;}\" target=\"_blank\"  href=\"$url\" ><span class=\"glyphicon glyphicon-eye-open\"></span> <b>".FN_Translate("open")."</b></a> <a target=\"_blank\" href='https://www.google.it/maps/dir//$lat_,$lon_/@$lat_,$lon_,18z' ><span class=\"glyphicon glyphicon-road\"></span> <b>".FN_Translate("go to")."</b></a></div>";
            $description=str_replace("\t"," ",$description);
        }
        else
        {
            if (!empty($item['synonyms']))
            {
                $description.="<em>{$item['synonyms']}</em><br />";
            }
            if ($item['photo1'])
            {
                $img=$_FN['siteurl'].$table->getThumbPath($item,"photo1");
                $description.="<img style=\"height:100px;\" src=\"$img\" /><br />";
            }
            $description.="$elevation_txt $lenght_total_txt $depth_total_txt<br />";
            $description.="<div class=\"text-right\"><a  onclick=\"try{window.open(this.href,'r','scrollbars=yes').focus();return false;}catch(e){return true;}\" target=\"_blank\"  href=\"$url\" ><span class=\"glyphicon glyphicon-eye-open\"></span> <b>".FN_Translate("open")."</b></a> <a target=\"_blank\" href='https://www.google.it/maps/dir//$lat_,$lon_/@$lat_,$lon_,18z' ><span class=\"glyphicon glyphicon-road\"></span> <b>".FN_Translate("go to")."</b></a></div>";
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
        if (empty($absolute)) 
        {
            $siteurl=str_replace("https://","//",$_FN['siteurl']);
            $siteurl=str_replace("http://","//",$siteurl);
        }
        //$siteurl=$_FN['siteurl'];
        
        $icon=$siteurl.openkis_GetIcon($item,$mod);
        
       // dprint_r($icon);
        
//        die();
//        $icon=$_FN['siteurl'].openkis_GetIcon($item,$mod);
        /* 88x128 */
        if ($elevation== "")
        {
            $elevation="0";
        }
        $kmlitem['title']=htmlentities($title,ENT_NOQUOTES,"UTF-8");
        $kmlitem['title']=htmlentities($kmlitem['title'],ENT_NOQUOTES,"UTF-8");
        $kmlitem['abstract']="$description";
        $kmlitem['size']="$size";
        $kmlitem['icon']="$icon";
        $kmlitem['elevation']=$elevation;
        $kmlitem['sourceurl']=$tplvars['sourceurl'];
        $kmlitem['abstract']=str_replace("]]","",$kmlitem['abstract']);
        $tplvars['items'][$cx]=$kmlitem;
        $cx++;
    }
}
if (isset($_GET['debug']))
{
    dprint_r(__FILE__." ".__LINE__." : pre tpl ".FN_GetExecuteTimer());
}
//dprint_r($tplvars);
$str=FN_TPL_ApplyTplString($tplstring,$tplvars);
if (isset($_GET['debug']))
{
    dprint_r(__FILE__." ".__LINE__." : post tpl ".FN_GetExecuteTimer());
}
$str=FN_FixNewline($str);
if ("$idresult")
{
    if ($_FN['enable_compress_gzip'])
    {
        $str=gzencode($str);
    }
    FN_SetGlobalVarValue("$idcache",$str);
    if (isset($_GET['debug']))
    {
        dprint_r(__FILE__." ".__LINE__." : FN_SetGlobalVarValue  ".FN_GetExecuteTimer());
    }
}
PrintKml($str,$filename);

/**
 * 
 * @global type $_FN
 * @param type $str
 * @param type $filename
 */
function PrintKml($str,$filename="")
{
    global $_FN;
    $data=$_FN['sitename']."-".FN_now();
    $data=str_replace(" ","_",str_replace(":","-",$data));
    if (isset($_GET['debug']))
    {
        if ($_FN['enable_compress_gzip'])
        {
            $str=gzdecode($str);
        }
        dprint_r(htmlspecialchars($str));
        dprint_r(__FILE__." ".__LINE__." : ".FN_GetExecuteTimer());
        die();
    }
    while(false!== @ob_end_clean()
    );
    header('Cache-Control: no-cache');
    header("Pragma: no-cache");
    header("Content-Disposition: attachment; filename=".$filename.";");
    header("Content-Type: application/vnd.google-earth.kml+xml\n");
    if ($_FN['enable_compress_gzip'])
    {
        header("Content-Encoding: gzip");
        print $str;
    }
    else
    {
        print $str;
    }
    exit();
}

?>
