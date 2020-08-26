<?php

ob_start();
global $_FN;
require_once "include/flatnux.php";
require_once "modules/dbview/FNDBVIEW.php";
FN_LoadMessagesFolder("extra/openkis");
$exclude=FN_GetParam("exclude",$_GET,"flat");
$minimal=FN_GetParam("minimal",$_GET,"flat");
$codes=FN_GetParam("filter_code",$_GET,"flat");
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
$fields_to_read=explode(",","code,latitude,longitude,latitude_txt,longitude_txt,coordinates_type,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total,meteorology,fauna,hydrology,closed");
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
openkis_UpdateCoords("caves");
openkis_UpdateCoords("artificials");
openkis_UpdateCoords("springs");

$data=$_FN['sitename']."-".FN_now();
$forcename=FN_GetParam("filename",$_GET,"html");
$data=str_replace(" ","_",str_replace(":","-",$data));
if ($forcename)
{
    $filename="$forcename.gpx";
}
else
{
    $filename="$data-$mod.gpx";
}

$results=$dbview->GetResults(false,$params,$idresult);
//evita di ricalcolare tutto se non è cambiato niente nel db o in questo file-->
$idcache="$idresult$minimal$big_icons".basename(__FILE__);
$maxtime=max(filectime(__FILE__),$table->GetLastUpdateTime());
$cache=FN_GetGlobalVarValue("$idcache",$maxtime); 
if (!empty($cache))
{
    PrintGpx($cache,$filename);
}
//evita di ricalcolare tutto se non è cambiato niente nel db o in questo file--<
$tplstring=file_get_contents("openkis_gpx.tp.xml");
$tplvars['sourceurl']=$_FN['siteurl'];
$tplvars['name']=$tablename;
$tplvars['items']=array();

$maxlat="";
$maxlon="";
$minlat="";
$minlon="";

$cx=0;
foreach($results as $item)
{
    
    $gpxitem=array();
    if (!empty($item['latitude']) && !empty($item['longitude']))
    {
        if ($minlon==="")
        {
            $maxlat=($item['latitude']);
            $maxlon=($item['longitude']);
            $minlat=($item['latitude']);
            $minlon=($item['longitude']);
        }
        $maxlat=max($maxlat,$item['latitude']);
        $maxlon=max($maxlon,$item['longitude']);
        $minlat=min($minlat,$item['latitude']);
        $minlon=min($minlon,$item['longitude']);
        
        $gpxitem=$item;
        $lat_=openkis_floatfrmt($item['latitude']);
        $lon_=openkis_floatfrmt($item['longitude']);
        $gpxitem['lat']=$lat_;
        $gpxitem['lon']=$lon_;
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
        $size=str_replace(",",".",$size);
        $url=FN_RewriteLink("index.php?mod=$mod&op=view&id={$item['id']}","&",true);
        
        $gpxitem['url']=$url;
        if ($minimal)
        {
            $description="{$item['name']}\n";
            $description.="$elevation_txt $lenght_total_txt $depth_total_txt\n";
        }
        else
        {
            $description="{$item['name']}\n";
            if (!empty($item['synonyms']))
            {
                $description.="{$item['synonyms']}\n";
            }
            $description.="$elevation_txt $lenght_total_txt $depth_total_txt\n";
            //latitude_txt,longitude_txt,coordinates_type
            $description.="{$item['latitude_txt']}\n";
            $description.="{$item['longitude_txt']}\n";
            $description.="{$item['coordinates_type']}\n";
            $description=str_replace("\t"," ",$description);
        }

        $description=FN_FixEncoding("$description");
        $gpxitem['abstract']=$description;
        if ($minimal)
        {
            $title=htmlspecialchars($item['code']);
        }
        else
        {
            $title=htmlspecialchars($item['code']."-".$item['name']);
        }
        $siteurl=str_replace("https://","//",$_FN['siteurl']);
        $siteurl=str_replace("http://","//",$siteurl);
        $icon=$siteurl.openkis_GetIcon($item,$mod);

        if ($elevation== "")
        {
            $elevation="0";
        }
        $gpxitem['title']=htmlentities($title,ENT_NOQUOTES,"UTF-8");
        $gpxitem['title']=htmlentities($gpxitem['title'],ENT_NOQUOTES,"UTF-8");
        $gpxitem['abstract']="$description";
        $gpxitem['size']="$size";
        $gpxitem['icon']="$icon";
        $gpxitem['elevation']=$elevation;
        $gpxitem['sourceurl']=$tplvars['sourceurl'];
        $gpxitem['abstract']=str_replace("]]","",$gpxitem['abstract']);
        $tplvars['items'][$cx]=$gpxitem;
        $cx++;
    }
}

$tplvars['maxlat']=$maxlat;
$tplvars['maxlon']=$maxlon;
$tplvars['minlat']=$minlat;
$tplvars['minlon']=$minlon;
$tplvars['timegpx']=date("Y-m-dTH:i:s.000Z");


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

if ("$idresult")
{
    FN_SetGlobalVarValue("$idcache",$str);
    if (isset($_GET['debug']))
    {
        dprint_r(__FILE__." ".__LINE__." : FN_SetGlobalVarValue  ".FN_GetExecuteTimer());
    }
}
PrintGpx($str,$filename);

/**
 * 
 * @global type $_FN
 * @param type $str
 * @param type $filename
 */
function PrintGpx($str,$filename="")
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
    header('Cache-Control: no-cache');
    header("Pragma: no-cache");
    header("Content-Length: ".$len);
    header("Content-Disposition: attachment; filename=".$filename.";");
    header("Content-Type: application/vnd.google-earth.gpx+xml");
    print($str);
    exit();
}






?>
