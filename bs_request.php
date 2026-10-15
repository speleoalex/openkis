<?php

/**
 * Reverse geocoding of a point for the map "info" mode (bs_map/index.js)
 * and for openkis_insert.php.
 * Returns region, province, municipality, locality (from Nominatim)
 * and the number of caves in each of them.
 */
global $_FN;
ob_start();
require_once "loadfinis.php";
while (false !== @ob_end_clean());
header("Content-Type: application/json; charset=utf-8");

$lat = floatval(str_replace(",", ".", FN_GetParam("lat", $_GET)));
$lon = floatval(str_replace(",", ".", FN_GetParam("lon", $_GET)));

$ret = array();
$ret['lat'] = $lat;
$ret['lon'] = $lon;
$ret['title'] = "";
$ret['regione'] = "";
$ret['provincia'] = "";
$ret['comune'] = "";
$ret['localita'] = "";
$ret['via'] = "";
$ret['cap'] = "";
$ret['grotte_in_regione'] = 0;
$ret['grotte_in_provincia'] = 0;
$ret['grotte_in_comune'] = 0;
$ret['grotte_in_localita'] = 0;

if ($lat == 0 || $lon == 0 || abs($lat) > 90 || abs($lon) > 180)
{
    die(json_encode($ret));
}

$r = bs_request_get("https://nominatim.openstreetmap.org/reverse?format=json&addressdetails=1&accept-language=it&lat=$lat&lon=$lon");
$r = json_decode($r, true);
$address = isset($r['address']) ? $r['address'] : array();

if (!empty($r['display_name']))
{
    $ret['title'] = $r['display_name'];
}
if (!empty($address['state']))
{
    $ret['regione'] = $address['state'];
}
// ISO3166-2-lvl6 = "IT-SV" -> "SV"
if (!empty($address['ISO3166-2-lvl6']))
{
    $ret['provincia'] = preg_replace('/^[A-Z]+-/', "", $address['ISO3166-2-lvl6']);
}
elseif (!empty($address['county']))
{
    $ret['provincia'] = $address['county'];
}
// the municipality is the highest level, e.g. town "Finale Ligure" > village "Finalmarina"
$comune_key = "";
foreach (array("municipality", "city", "town", "village") as $k)
{
    if (!empty($address[$k]))
    {
        $ret['comune'] = $address[$k];
        $comune_key = $k;
        break;
    }
}
foreach (array("hamlet", "village", "locality", "neighbourhood", "suburb") as $k)
{
    if ($k != $comune_key && !empty($address[$k]))
    {
        $ret['localita'] = $address[$k];
        break;
    }
}
if (!empty($address['road']))
{
    $ret['via'] = $address['road'];
}
if (!empty($address['postcode']))
{
    $ret['cap'] = $address['postcode'];
}

$where = "";
foreach (array("regione", "provincia", "comune", "localita") as $field)
{
    if ($ret[$field] == "")
    {
        break;
    }
    $where .= ($where == "" ? "" : " AND ") . "$field LIKE '" . addslashes(strtoupper($ret[$field])) . "'";
    $num_grotte = FN_XMETADBQuery("SELECT count(*) AS c FROM ctl_caves WHERE $where");
    $ret["grotte_in_$field"] = isset($num_grotte[0]['c']) ? intval($num_grotte[0]['c']) : 0;
}

echo json_encode($ret);

/**
 * Nominatim requires an identifying User-Agent
 * @param string $url
 * @return string
 */
function bs_request_get($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "OpenKIS speleological cadastre");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $result = curl_exec($ch);
    curl_close($ch);
    return ($result === false) ? "" : $result;
}
