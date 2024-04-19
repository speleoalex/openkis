<?php
/**
 * @package Flatnux_module_navigator
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * 
 * set sectiontype navigator in YourPage and
 * copy this file in sections/YourPage to execute this script
 * 
 */
#<fnmodule>navigator</fnmodule>
defined('_FNEXEC') or die('Restricted access');
//dprint_r($row);

$zoom=8;
$baselayer="";
$lat=45;
$lon=9;


$config=FN_LoadConfig();
$search_fields=$config['search_fields']!= "" ? explode(",",$config['search_fields']) : array();
$search_partfields=$config['search_fields']!= "" ? explode(",",$config['search_partfields']) : array();
$search_orders=$config['search_orders']!= "" ? explode(",",$config['search_orders']) : array();
$navigate_groups=$config['navigate_groups']!= "" ? explode(",",$config['navigate_groups']) : array();
$search_options=$config['search_options']!= "" ? explode(",",$config['search_options']) : array();
$search_min=$config['search_min']!= "" ? explode(",",$config['search_min']) : array();
$databasename="fndatabase";
$pathdatabase=$_FN['datadir'];
$tables=explode(",",$config['tables']);
$tablename=$tables[0];
$id=FN_GetParam("id",$_GET,"html");
//--config--<
$config=FN_LoadConfig("modules/dbview/config.php","glacial");
$params['fields']="code,latitude,longitude,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total";


$params['navigate_groups']=array("nv_glaciers"=>$row['code']);
$dbview=new FNDBVIEW($config);
$items=$dbview->GetResults($config,$params);


$num=count($items);
$html="";
if ($num > 0)
{
    echo "<h3>Cavità conosciute in questo ghiacciaio: $num</h3>";
    $lat=0;
    $lon=0;
    $lat_min=100;
    $lon_min=100;
    $lat_max=0;
    $lon_max=0;
    $i=0;
    $num=array();
    foreach($items as $item)
    {
        $num[]=$item['code'];
        if (!empty($item['latitude']))
        {
            $_lat=floatval($item['latitude']);
            $_lon=floatval($item['longitude']);
            $lat_max=max($lat_max,$_lat);
            $lat_min=min($lat_min,$_lat);
            $lon_max=max($lon_max,$_lon);
            $lon_min=min($lon_min,$_lon);
            $latlon=" <span style=\"color:grey\">wgs84: ".$_lat."N ".$_lon."E</span>";
            $i++;
        }
        else
        {
            $latlon="";
        }
        // dprint_r($item);

        $html.="<br /><b>{$item['code']}</b> <a target = \"_blank\" href=\"".FN_Rewritelink("index.php?mod=glacial&op=view&id={$item['id']}")."\">{$item['name']}</a> $latlon Q.{$item['elevation']}";
    }
    if ($i > 0)
    {
        $lon=(($lon_max - $lon_min) / 2) + $lon_min;
        $lat=(($lat_max - $lat_min) / 2) + $lat_min;

        $lat=str_replace(",",".",$lat);
        $lon=str_replace(",",".",$lon);
        $zoom=13;
        echo "<iframe id=\"mapframe\" name=\"mapframe\" 
    frameborder=\"0\" src=\"{$_FN['siteurl']}bs_map.htm?nv_glaciers={$row['code']}&zoom=$zoom&mod=glacial&lat=$lat&lon=$lon\" width=\"100%\" height=\"500\" ></iframe>";
    }
}
echo $html;


?>