<?php

ob_start();
global $_FN;
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
FN_LoadMessagesFolder("extra/openkis");

$history=FN_GetParam("history",$_GET,"flat");
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
$table=FN_XMDBTable($tablename);
$fields_to_read=explode(",","code,latitude,longitude,elevation,name,synonyms,depth_total,lenght_total,latitude_txt,longitude_txt,coordinates_type,userupdate");
$filename="history_{$mod}_{$tablename}_{$history}.kml";
foreach($fields_to_read as $field)
{
    if (isset($table->fields[$field]))
    {
        $fields[]=$field;
    }
}
$params['fields']=implode(",",$fields);
$params['appendquery']=" id LIKE '$history'";
$results=$dbview->GetResults(false,$params,$idresult);

if (!$results)
{
    die("not authorized");
}

$table_versions=FN_XMDBTable($tablename."_versions");
$items=$table_versions->GetRecords(array("id"=>$results[0]['id']));


$tplstring=file_get_contents("openkis_kml.tp.xml");
$tplvars['sourceurl']=$_FN['siteurl'];
$tplvars['name']=$tablename;
$tplvars['items']=array();

$kmlitems=array();


$coordinates=array();
//dprint_r($items);
$cx=1;

$item=$results[0];
$kmlitem=array();
$position=openkis_GetItemPosition($item);
dprint_r($position);
if (!empty($position['lat']))
{

    $kmlitem['title']="CURRENT by {$item['userupdate']}";
    $kmlitem['icon']="{$_FN['siteurl']}extra/openkis/icons/circle.png";
    $kmlitem['lat']=$position['lat'];
    $kmlitem['lon']=$position['lon'];
    $kmlitem['abstract']="{$item['latitude_txt']},{$item['longitude_txt']} {$item['elevation']} - {$item['coordinates_type']}";
    $kmlitem['elevation']="{$item['elevation']}";
    $tplvars['items'][]=$kmlitem;
}

foreach($items as $item)
{
    $kmlitem=array();
    $position=openkis_GetItemPosition($item);
    if (!empty($position['lat']))
    {

        $kmlitem['title']="$cx by {$item['userupdate']}";
        $kmlitem['icon']="{$_FN['siteurl']}extra/openkis/icons/circle.png";
        $kmlitem['lat']=$position['lat'];
        $kmlitem['lon']=$position['lon'];
        $kmlitem['abstract']="{$item['latitude_txt']},{$item['longitude_txt']} {$item['elevation']} - {$item['coordinates_type']}";
        $kmlitem['elevation']="{$item['elevation']}";
        $tplvars['items'][$position['lat'].$position['lon']]=$kmlitem;
        $cx++;
    }
}


$str=FN_TPL_ApplyTplString($tplstring,$tplvars);
$str=FN_FixNewline($str);



if ($_FN['enable_compress_gzip'])
{
    $str=gzencode($str);
}
PrintKml($str,$filename);

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