<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */




$zoom=8;
$baselayer="";
$lat=45;
$lon=9;

$config=FN_LoadConfig("modules/dbview/config.php","caves");
$params['fields']="code,latitude,longitude,elevation,name,synonyms,depth_total,depth_negative,depth_positive,filelox,lenght_total";


$params['navigate_groups']=array("nv_areas"=>$row['code']);
$dbview=new FNDBVIEW($config);
$items=$dbview->GetResults($config,$params);



$num=count($items);
$html="";
if ($num > 0)
{
    echo "<h3>Grotte conosciute in quest'area: $num</h3>";
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

        $html.="<br /><b>{$item['code']}</b> <a target = \"_blank\" href=\"".FN_Rewritelink("index.php?mod=caves&op=view&id={$item['id']}")."\">{$item['name']}</a> $latlon Q.{$item['elevation']}";
    }
    if ($i > 0)
    {
        $lon=(($lon_max - $lon_min) / 2) + $lon_min;
        $lat=(($lat_max - $lat_min) / 2) + $lat_min;

        $lat=str_replace(",",".",$lat);
        $lon=str_replace(",",".",$lon);
        $zoom=13;
        echo "<iframe id=\"mapframe\" name=\"mapframe\" 
    frameborder=\"0\" src=\"{$_FN['siteurl']}bs_map.htm?nv_areas={$row['code']}&zoom=$zoom&mod=caves&lat=$lat&lon=$lon\" width=\"100%\" height=\"500\" ></iframe>";
    }
}




$file = urlencode("misc/fndatabase/ctl_areas/{$row['id']}/filelox/{$row['filelox']}");
$iframe_href = "{$_FN['siteurl']}cave_viewer.php?f={$file}";
//$html.= "<iframe style=\"width:100%;height:800px;border:0px\" src=\"$iframe_href\"></iframe>";
$html.= "<br/><a href=\"$iframe_href\" target=\"_blank\">" . FN_Translate("open 3d") . "</a><br />";


echo $html;
?>