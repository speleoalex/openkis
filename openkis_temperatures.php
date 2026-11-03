<?php

/**
 * Underground temperatures for the map (OPS_Map.addTemperaturesLayer) and
 * the exports in the layout of the UCC project sheets.
 *
 * GeoJSON (default): one Point per measurement point, carrying the list of
 * its measurements in the popup and the mean of its air temperatures (the
 * spot readings and the means of the monitorings) as "value", from which the
 * map picks the colour. Only the records and caves the current user can see
 * are returned (see openkis_TemperaturesPoints()).
 *
 * Parameters (all optional):
 *   id      only this record
 *   cave    only the measurements of this cave (code)
 *   format  "ucc": CSV in the layout of the UCC sheets, with
 *   type    "spot" (default) or "monitoring" choosing the sheet;
 *           "ods": both sheets in one OpenDocument spreadsheet (Format_UCC.ods)
 *
 * Besides the features the collection carries a "legend" member with the
 * translated labels of the colour classes.
 */
ob_start();
global $_FN;
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
FN_LoadMessagesFolder("extra/openkis");

$id = FN_GetParam("id", $_GET, "flat");
$cave = FN_GetParam("cave", $_GET, "flat");
$format = FN_GetParam("format", $_GET, "flat");
$type = FN_GetParam("type", $_GET, "flat") == "monitoring" ? "monitoring" : "spot";

$items = array();
if (file_exists("sections/temperatures") && FN_UserCanViewSection("temperatures"))
{
    openkis_UpdateTemperatures();
    $config = FN_LoadConfig("modules/dbview/config.php", "temperatures");
    $dbview = new FNDBVIEW($config);
    $params = array();
    $params['fields'] = "id,name,type,medium,cave,position,measure_elevation,entrance_distance,latitude,longitude,ventilation,snow_ice,water,instrument,accuracy,resolution,temperature,temperature_external,year,month,day,hour,start_date,end_date,acq_interval,samples,t_mean,t_min,t_max,t_median,t_mode,t_stddev,data_holder,bibliography,notes,sort_date,username";
    $where = array();
    if ($id !== "")
    {
        $where[] = "id = " . intval($id);
    }
    if ($cave !== "")
    {
        $where[] = "cave LIKE '" . addslashes($cave) . "'";
    }
    if (count($where) > 0)
    {
        $params['appendquery'] = implode(" AND ", $where);
    }
    $records = $dbview->GetResults($config, $params);
    if (is_array($records))
    {
        $records = FN_ArraySortByKey($records, "sort_date");
        $items = openkis_TemperaturesPoints($records);
    }
}

if ($format == "ods")
{
    $ods = openkis_TemperaturesUccOds($items);
    while (false !== @ob_end_clean());
    header('Content-Type: application/vnd.oasis.opendocument.spreadsheet');
    header('Content-Disposition: attachment; filename="Format_UCC_' . date("Ymd") . '.ods"');
    header('Content-Length: ' . strlen($ods));
    header('Cache-Control: no-cache');
    echo $ods;
    exit;
}

if ($format == "ucc")
{
    $rows = openkis_TemperaturesUccRows($type, $items);
    while (false !== @ob_end_clean());
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="UCC_' . ($type == "monitoring" ? "monitoraggi" : "misure_spot") . '_' . date("Ymd") . '.csv"');
    header('Cache-Control: no-cache');
    $out = fopen("php://output", "w");
    // BOM: the spreadsheets open the file as UTF-8
    fwrite($out, "\xEF\xBB\xBF");
    foreach ($rows as $row)
    {
        fputcsv($out, array_map(function ($v) {
                    return FN_FixEncoding(strval($v));
                }, $row), ";");
    }
    fclose($out);
    exit;
}

// one feature per measurement point
$groups = array();
foreach ($items as $item)
{
    $key = $item['point']['key'];
    if (!isset($groups[$key]))
    {
        $groups[$key] = array("point" => $item['point'], "records" => array());
    }
    $groups[$key]['records'][] = $item['record'];
}
$features = array();
foreach ($groups as $key => $group)
{
    $point = $group['point'];
    $records = $group['records'];
    // the colour: mean of the air readings, of all the readings when there is no air
    $sum = array("air" => array(), "all" => array());
    foreach ($records as $record)
    {
        $value = openkis_TemperatureValue($record);
        if ($value === "")
            continue;
        $sum['all'][] = floatval($value);
        if ($record['medium'] != "water" && $record['medium'] != "rock")
            $sum['air'][] = floatval($value);
    }
    $values = count($sum['air']) > 0 ? $sum['air'] : $sum['all'];
    $mean = count($values) > 0 ? round(array_sum($values) / count($values), 1) : "";
    $label = trim($point['code'] . " " . $point['name']);
    if ($label === "")
    {
        $label = $records[0]['name'] !== "" ? $records[0]['name'] : FN_Translate("free coordinates");
    }
    $description = "";
    if ($mean !== "")
    {
        $description .= "<b>" . FN_Translate("mean temperature") . ": " . str_replace(".", ",", strval($mean)) . " °C</b> (" . count($values) . " " . FN_Translate("readings") . ")<br />";
    }
    $description .= openkis_TemperaturesTable(array_slice($records, 0, 12), false);
    if (count($records) > 12)
    {
        $description .= "<small>... " . (count($records) - 12) . " " . FN_Translate("more") . "</small>";
    }
    if ($point['mod'] != "" && $point['id'] != "")
    {
        $url = FN_RewriteLink("index.php?mod={$point['mod']}&op=view&id={$point['id']}", "&", true);
        $list = FN_RewriteLink("index.php?mod=temperatures&spfield_cave=" . urlencode($point['code']), "&", true);
    }
    else
    {
        $url = FN_RewriteLink("index.php?mod=temperatures&op=view&id={$records[0]['id']}", "&", true);
        $list = "";
    }
    $open = "onclick=\"try{window.open(this.href,'r','scrollbars=yes').focus();return false;}catch(e){return true;}\" target=\"_blank\"";
    $description .= "<div class=\"text-right\">";
    if ($list !== "")
    {
        $description .= "<a $open href=\"$list\"><span class=\"glyphicon glyphicon-list\"></span> " . FN_Translate("temperatures") . "</a> &nbsp; ";
    }
    $description .= "<a $open href=\"$url\"><span class=\"glyphicon glyphicon-eye-open\"></span> <b>" . FN_Translate("open") . "</b></a></div>";
    $features[] = array(
        "type" => "Feature",
        "id" => $key,
        "geometry" => array("type" => "Point", "coordinates" => array($point['lon'], $point['lat'])),
        "properties" => array(
            "name" => FN_FixEncoding($label),
            "description" => FN_FixEncoding($description),
            "value" => $mean,
            "kind" => openkis_TemperatureClass($mean),
            "count" => count($records),
            "url" => $url));
}

$legend = array(
    "title" => FN_Translate("temperatures"),
    "cold" => "< 4 °C",
    "cool" => "4 - 8 °C",
    "mild" => "8 - 12 °C",
    "warm" => "12 - 16 °C",
    "hot" => "> 16 °C",
    "unknown" => FN_Translate("no reading"));
// shortest float representation (8.1, not 8.0999999999999996) whatever php.ini says
@ini_set("serialize_precision", -1);
$json = json_encode(array("type" => "FeatureCollection", "legend" => $legend, "features" => $features));
while (false !== @ob_end_clean());
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
echo $json;
