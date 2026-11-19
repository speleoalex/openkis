<?php

global $_FN;
// WGS84 position of the typed coordinates and map: no map endpoint recalculates
// this table (openkis_UpdateCoords reads fields it does not have), so the sheet
// stores latitude/longitude when they are missing or out of date
if (empty($row['id']) || !function_exists("openkis_GetItemPosition"))
{
    return;
}
$position = openkis_GetItemPosition($row);
if (abs(floatval($position['lat']) - floatval($row['latitude'])) > 0.0000001 || abs(floatval($position['lon']) - floatval($row['longitude'])) > 0.0000001)
{
    $config = FN_LoadConfig("modules/dbview/config.php", $_FN['mod']);
    $table = FN_XMDBTable($config['tables']);
    if ($table)
    {
        $table->UpdateRecord(array("id" => $row['id'], "latitude" => $position['lat'], "longitude" => $position['lon'], "coordnatesupdated" => FN_Now()));
    }
}
// tracer tests starting or ending in this cavity
$tracings_card = function_exists("openkis_TracingsUncataloguedCard") ? openkis_TracingsUncataloguedCard($row['code']) : "";
if (abs(floatval($position['lat'])) < 0.01)
{
    echo $tracings_card;
    return;
}
$lat = round($position['lat'], 6);
$lon = round($position['lon'], 6);
$footer = openkis_TplApply("map_coordinates_footer", array(
    "coordinates" => FN_Translate("coordinates WGS84"),
    "route" => FN_Translate("calculate the route"),
    "latlon" => "$lat,$lon",
    "gmap" => "https://www.google.com/maps/dir//$lat,$lon/@$lat,$lon,18z",
    "osm" => "https://www.openstreetmap.org/directions?engine=graphhopper_foot&amp;route=$lat%2C$lon%3B$lat%2C$lon",
));
echo openkis_MapCard("{$_FN['siteurl']}bs_map.htm?point=circle&lat=$lat&lon=$lon&zoom=17", 350, "", $footer);
echo $tracings_card;
