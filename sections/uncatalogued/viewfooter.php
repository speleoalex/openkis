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
if (abs(floatval($position['lat'])) < 0.01)
{
    return;
}
$lat = round($position['lat'], 6);
$lon = round($position['lon'], 6);
echo "<div class=\"card openkis-card mb-3\"><div class=\"card-body p-0\">";
echo "<iframe style=\"border:0px;width:100%;height:350px;display:block\" src=\"{$_FN['siteurl']}bs_map.htm?point=circle&lat=$lat&lon=$lon&zoom=17\"></iframe>";
echo "</div><div class=\"card-footer small text-muted\">";
echo FN_Translate("coordinates WGS84") . ": <input class=\"form-control form-control-sm d-inline-block w-auto\" readonly value=\"$lat,$lon\" onclick=\"this.select();\">";
echo " " . FN_Translate("calculate the route") . ": <a target=\"_blank\" rel=\"noopener\" href=\"https://www.google.com/maps/dir//$lat,$lon/@$lat,$lon,18z\">GMaps</a>";
echo " | <a target=\"_blank\" rel=\"noopener\" href=\"https://www.openstreetmap.org/directions?engine=graphhopper_foot&amp;route=$lat%2C$lon%3B$lat%2C$lon\">OpenStreetMap</a>";
echo "</div></div>";
