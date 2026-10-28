<?php

/**
 * @package Flatnux
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
//ob_start();
global $_FN;
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
$op=FN_GetParam("op",$_REQUEST);
switch($op)
{
    case "near":
        GetNear();
        break;
    default:
        if ($_FN['mod'] == "" || !file_exists("sections/{$_FN['mod']}"))
            die();
        $openkis_access=openkis_SectionAccess($_FN['mod']);
        $config=FN_LoadConfig("modules/dbview/config.php",$_FN['mod']);
        $dbview=new FNDBVIEW($config);
        $params=$_REQUEST;
        // the exported columns are fixed: a "fields" parameter from the
        // request is ignored, so callers cannot pick other columns of the table
        $params['fields']="code,latitude,longitude,elevation";
        $results=$dbview->GetResults($config,$params);
        $results=openkis_FilterPublicRecords($openkis_access,$config,$results);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(array("count" => count($results), "items" => array_values($results)));
        break;
}

/**
 * 
 * @global type $_FN
 */
function GetNear()
{
    global $_FN;
    $mod=FN_GetParam("mod",$_REQUEST);
    if ($mod == "" || !file_exists("sections/$mod"))
        die();
    $openkis_access=openkis_SectionAccess($mod);
    $params['fields']="code,latitude,longitude,elevation,name";
    $config=FN_LoadConfig("modules/dbview/config.php",$mod);
    $lat=FN_GetParam("lat",$_REQUEST);
    $lon=FN_GetParam("lon",$_REQUEST);
    $op=FN_GetParam("op",$_REQUEST);
    $lat=floatval($lat);
    $lon=floatval($lon);
    $lat=str_replace(",",".",$lat);
    $lon=str_replace(",",".",$lon);
    $dbview=new FNDBVIEW($config);
    $allinrange=$dbview->GetResults(false,$params);
    $allinrange=openkis_FilterPublicRecords($openkis_access,$config,$allinrange);
    $near=0;
    $mindiff=-1;
    foreach($allinrange as $item)
    {
        if (!empty($item['latitude']))
        {
            $lat_=str_replace(",",".",$item['latitude']);
            $lon_=str_replace(",",".",$item['longitude']);
            $diff=calcola_distanza($lat,$lon,$lat_,$lon_);
            if ($mindiff < 0 || $mindiff > $diff)
            {
                $mindiff=$diff;
                $near=$item;
            }
        }
    }
    $ret ['methers']=round($mindiff,2);
    $ret ['cave']=$near;
    echo json_encode($ret);
    die();
}

/**
 * 
 * @param type $latitude1
 * @param type $longitude1
 * @param type $latitude2
 * @param type $longitude2
 * @return type
 */
function calcola_distanza($latitude1,$longitude1,$latitude2,$longitude2)
{
    $theta=$longitude1 - $longitude2;
    $miles=(sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
    $miles=acos($miles);
    $miles=rad2deg($miles);
    $miles=$miles * 60 * 1.1515;
    $feet=$miles * 5280;
    $yards=$feet / 3;
    $kilometers=$miles * 1.609344;
    $meters=$kilometers * 1000;
    return $meters;
}

?>