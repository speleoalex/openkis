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

global $_FN;
if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php");
}
else
{
    if (!empty($row['latitude']))
    {
        $zoom=17;
        $baselayer="";
        if (false!== strstr($row['coordinates_type'],"IGM") || false!== strstr($row['original_coordinates_type'],"IGM")) //
        {
            $baselayer="IGM 1:25000";
        }
        echo "<div>Coordinate wgs84:<input class=\"form-input\" value=\"{$row['latitude']},{$row['longitude']}\" onclick=\"this.select();\"></div>";
        echo "<iframe style=\"border:0px;width:100%;height:400px;\" src=\"{$_FN['siteurl']}bs_map.htm?mod={$_FN['mod']}&baselayer={$baselayer}&point=circle&lat={$row['latitude']}&lon={$row['longitude']}&zoom=$zoom&history={$row['id']}\"></iframe>";
        $gmap_link="//www.google.it/maps/dir//".$row['latitude'].",".$row['longitude']."/@".$row['latitude'].",".$row['longitude'].",18z";
        echo "<div class=\"alert alert-info\">";
        echo "<h5>".FN_Translate("calculate the route").":</h5>";

        echo " <a class=\"btn btn-secondary\" target=\"_blank\" href=\"$gmap_link\">GMaps</a>";

        $gmap_link="https://maps.openrouteservice.org/directions?n1=44.075342&n2=9.807849&n3=17&a=null,null,".$row['latitude'].",".$row['longitude']."&b=0&c=0&k1=it&k2=km";
        echo " <a class=\"btn btn-secondary\" target=\"_blank\" href=\"$gmap_link\">OpenRouteService</a>";


        $osmanLink="https://www.openstreetmap.org/directions?engine=graphhopper_foot&route={$row['latitude']}%2C{$row['longitude']}%3B{$row['latitude']}%2C{$row['longitude']}";
        echo " <a class=\"btn btn-secondary\" target=\"_blank\" href=\"$osmanLink\">OpenStreetmap</a>";
        $forcename=openkis_TextToAscii($row['name']);
        echo " <a class=\"btn btn-secondary\" target=\"_blank\" href=\"{$_FN['siteurl']}openkis_kml.php?mod={$_FN['mod']}&big_icons=1&filter_code=".urlencode($row['code'])."&filename=$forcename\">Scarica kml</a>";
        echo "</div>";
    }

$table=new XMLTable("fndatabase","ctl_fauna",$_FN['datadir']);
$fauna=explode("|",$row['fauna']);

$fauna=array_unique($fauna);
//dprint_r($row);
//dprint_r($fauna);

$fhtml="";
foreach($fauna as $key=> $val)
{
    $val=trim(ltrim(str_replace("\n","",str_replace("\r","",$val))));
    if ($val!= "")
    {
        $query="SELECT * FROM ctl_fauna WHERE scientific_name LIKE \"%".$val."%\" OR scientific_name LIKE \"%".strtolower($val)."%\" OR scientific_name LIKE \"%".ucfirst(strtolower($val))."%\" OR scientific_name LIKE \"%".strtoupper($val)."%\" ";
        $r=FN_XMLQuery($query);
        if (isset($r[0]['scientific_name']))
        {
            $l=fn_rewritelink("index.php?mod=fauna&op=view&id={$r[0]['id']}");
            $fhtml.="<br /><b>{$r[0]['scientific_name']}</b>&nbsp;<em>{$r[0]['name']}</em> <a href=\"$l\">".FN_Translate("view")."</a>";
        }
    }
}
if ($fhtml!= "")
{
    echo "<br /><br /><h4>Fauna presente in questa grotta:</h4>
		<div class=\"alert alert-primary\">
		$fhtml</div>";
}




    $config=FN_LoadConfig("","bibliography");
    $biblio=new FNDBVIEW($config);
    $params['appendquery']="codeglacial LIKE '{$row['code']}' OR codeglacial LIKE '{$row['code']},%' OR codeglacial LIKE '%,{$row['code']},%' OR codeglacial LIKE '%,{$row['code']}' ";
    $params['fields']="id,title,authors,year";
    $biblio_items=$biblio->GetResults(false,$params);
    $biblio_items=FN_ArraySortByKey($biblio_items,"year");
    if (is_array($biblio_items) && count($biblio_items))
    {
        echo "<div class=\"alert alert-warning\">";
        echo "<h3>".FN_Translate("bibliography")."</h3>";
        echo "<table class=\"table table-responsive\">";
        foreach($biblio_items as $biblio_item)
        {
            $url=FN_RewriteLink("index.php?mod=bibliography&op=view&id={$biblio_item['id']}");
            echo "<tr><td>{$biblio_item['year']}</td><td>{$biblio_item['title']}</td><td>{$biblio_item['authors']}</td><td><a class=\"btn btn-primary\" href=\"$url\">".FN_Translate("view")."</a></td></tr>";
        }
        echo "</table>";
        echo "</div>";
    }
}

?>