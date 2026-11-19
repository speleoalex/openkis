<?php

$t_wish = FN_XMDBTable("ctl_wishregioni");
$regione = $t_wish->GetRecord(array("id" => $row['id_regione']));
echo openkis_TplApply("wish_source_alert", array(
    "title" => isset($regione['title']) ? $regione['title'] : '',
    "url" => isset($regione['portal']) ? $regione['portal'] : '',
    "img" => $regione ? $t_wish->getFilePath($regione, "photo1") : '',
    "link" => $row['link'],
));
if (!empty($row['latitude']))
{
    $zoom = 17;
    $baselayer = "";
    if (false !== strstr($row['coordinates_type'], "IGM") || false !== strstr($row['original_coordinates_type'], "IGM"))
    {
        $baselayer = "IGM 1:25000";
    }
    echo "<iframe style=\"border:0px;width:100%;height:400px;\" src=\"{$_FN['siteurl']}bs_map.htm?mod={$_FN['mod']}&baselayer={$baselayer}&point=circle&lat={$row['latitude']}&lon={$row['longitude']}&zoom=$zoom&history={$row['id']}\"></iframe>";
    $forcename = openkis_TextToAscii($row['name']);
    echo openkis_TplApply("route_links", array(
        "title" => FN_Translate("calculate the route"),
        "gmap" => htmlspecialchars("//www.google.it/maps/dir//" . $row['latitude'] . "," . $row['longitude'] . "/@" . $row['latitude'] . "," . $row['longitude'] . ",18z"),
        "ors" => htmlspecialchars("https://maps.openrouteservice.org/directions?n1=44.075342&n2=9.807849&n3=17&a=null,null," . $row['latitude'] . "," . $row['longitude'] . "&b=0&c=0&k1=it&k2=km"),
        "osm" => htmlspecialchars("https://www.openstreetmap.org/directions?engine=graphhopper_foot&route={$row['latitude']}%2C{$row['longitude']}%3B{$row['latitude']}%2C{$row['longitude']}"),
        "kml" => htmlspecialchars("{$_FN['siteurl']}openkis_kml.php?mod={$_FN['mod']}&big_icons=1&filter_code=" . urlencode($row['code']) . "&filename=$forcename&absolute=1"),
    )) . "<br />";
    $position = openkis_GetItemPosition($row);
    echo openkis_TplApply("wish_coordinates", array(
        "decimal" => "{$row['longitude']},{$row['latitude']}",
        "dms" => "{$position['lon_dms']},{$position['lat_dms']}",
        "utm" => "{$position['x']},{$position['y']},{$position['zone']}",
    ));
}
// items linked to this code, through the bibliography link table
$biblio_items = array();
$biblio_filter = openkis_BibliographyFilter($row['code'], "codecaves");
if ($biblio_filter !== false)
{
    $config = FN_LoadConfig("", "bibliography");
    $biblio = new FNDBVIEW($config);
    $params['appendquery'] = $biblio_filter;
    $params['fields'] = "id,title,authors,year";
    $biblio_items = $biblio->GetResults(false, $params);
    $biblio_items = FN_ArraySortByKey($biblio_items, "year");
}
if (is_array($biblio_items) && count($biblio_items))
{
    echo openkis_BibliographyAlertCard($biblio_items);
}
//----survey------------------------------------------------------------------->
$config = FN_LoadConfig("", "survey");
$survey = new FNDBVIEW($config);
$params['appendquery'] = "codecave LIKE '{$row['code']}'";
$params['fields'] = "id,name,filelox,priority";
$items = $survey->GetResults($config, $params);
$links = array();
if (is_array($items) && count($items))
{
    $items = FN_ArraySortByKey($items, "priority");
    foreach ($items as $survey_item)
    {
        if (!empty($survey_item['filelox']) && file_exists("misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelox/{$survey_item['filelox']}") && openkis_FileAccess("ctl_surveys", $survey_item['id'], "filelox"))
        {
            $file = urlencode(openkis_CaveViewerFile("ctl_surveys", $survey_item['id'], "filelox", $survey_item['filelox']));
            $links[] = array("name" => $survey_item['name'], "href" => "{$_FN['siteurl']}openkis_cave_viewer.php?f={$file}");
        }
    }
}
if (count($links))
{
    echo openkis_TplApply("survey_3d_links", array("viewer" => FN_Translate("3D Viewer"), "items" => $links));
}
//----survey-------------------------------------------------------------------<
//-----------SCANSIONI--------------------------------------------------------->
require_once "extra/openkis/openkis_historic_sheets.inc.php";
$id = str_replace("LI", "", $row['code']);
$archives = array(
    "scansioni_Issel" => array("title" => "Schede storiche Issel:", "wildcard" => "_*", "style" => "max-height:400px;overflow:auto;border:1px solid;padding:10px;"),
    "scansioni_Zoja" => array("title" => "Schede storiche Issel - Aggiornamento Zoja:", "wildcard" => "*", "style" => "max-height:400px;overflow:auto;border:1px solid;padding:10px;"),
    "schede_dsl" => array("title" => "Documentazione Delegazione Speleologica Ligure", "wildcard" => "_*", "class" => "alert alert-warning", "restricted" => true),
);
foreach ($archives as $folder => $archive)
{
    $files = openkis_HistoricSheetFiles("../nextcloud/data/catasto/files/{$folder}", $id, $archive['wildcard']);
    if (!count($files))
        continue;
    if (!empty($archive['restricted']) && !FN_IsAdmin() && !FN_UserInGroup($_FN['user'], "w_caves"))
        $content = "la visualizzazione della documentazione DSL è riservata ai curatori, eseguire il login per visualizzare le schede";
    else
        $content = implode("", array_map("openkis_HistoricSheetFileCard", $files));
    echo openkis_TplApply("historic_files_box", array(
        "title" => $archive['title'],
        "style" => isset($archive['style']) ? $archive['style'] : "",
        "class" => isset($archive['class']) ? $archive['class'] : "",
        "content" => $content,
    ));
}
//-----------SCANSIONI---------------------------------------------------------<
