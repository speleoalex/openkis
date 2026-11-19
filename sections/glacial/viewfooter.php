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
        echo openkis_TplApply("map_point", array(
            "latlon" => "{$row['latitude']},{$row['longitude_txt']}",
            "src" => htmlspecialchars("{$_FN['siteurl']}bs_map.htm?mod={$_FN['mod']}&baselayer={$baselayer}&point=circle&lat={$row['latitude']}&lon={$row['longitude']}&zoom=$zoom&history={$row['id']}"),
        ));
        $forcename=openkis_TextToAscii($row['name']);
        echo openkis_TplApply("route_links", array(
            "title" => FN_Translate("calculate the route"),
            "gmap" => htmlspecialchars("//www.google.it/maps/dir//{$row['latitude']},{$row['longitude']}/@{$row['latitude']},{$row['longitude']},18z"),
            "ors" => htmlspecialchars("https://maps.openrouteservice.org/directions?n1=44.075342&n2=9.807849&n3=17&a=null,null,{$row['latitude']},{$row['longitude']}&b=0&c=0&k1=it&k2=km"),
            "osm" => htmlspecialchars("https://www.openstreetmap.org/directions?engine=graphhopper_foot&route={$row['latitude']}%2C{$row['longitude']}%3B{$row['latitude']}%2C{$row['longitude']}"),
            "kml" => htmlspecialchars("{$_FN['siteurl']}openkis_kml.php?mod={$_FN['mod']}&big_icons=1&filter_code=" . urlencode($row['code']) . "&filename=$forcename"),
        ));
    }

$table=FN_XMDBTable("ctl_fauna");
$fauna=explode("|",$row['fauna']);

$fauna=array_unique($fauna);

$fhtml="";
foreach($fauna as $key=> $val)
{
    $val=trim(ltrim(str_replace("\n","",str_replace("\r","",$val))));
    // the value is interpolated in the query: keep only the characters of a scientific name
    $val=preg_replace('/[^\p{L}\p{N} .\-]/u',"",$val);
    if ($val!= "")
    {
        $query="SELECT * FROM ctl_fauna WHERE scientific_name LIKE \"%".$val."%\" OR scientific_name LIKE \"%".strtolower($val)."%\" OR scientific_name LIKE \"%".ucfirst(strtolower($val))."%\" OR scientific_name LIKE \"%".strtoupper($val)."%\" ";
        $r=FN_XMETADBQuery($query);
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
    // the bibliography of a site may have no link to the glacial cavities
    $biblio_table=FN_XMDBTable("ctl_bibliography");
    if (!isset($biblio_table->fields['codeglacial']))
        $config=false;
    $params['appendquery']="codeglacial LIKE '{$row['code']}' OR codeglacial LIKE '{$row['code']},%' OR codeglacial LIKE '%,{$row['code']},%' OR codeglacial LIKE '%,{$row['code']}' ";
    $params['fields']="id,title,authors,year";
    $biblio_items=$config ? $biblio->GetResults(false,$params) : array();
    $biblio_items=FN_ArraySortByKey($biblio_items,"year");
    if (is_array($biblio_items) && count($biblio_items))
    {
        echo openkis_BibliographyAlertCard($biblio_items);
    }
}

?>