<?php

/**
 * Tracer tests as GeoJSON for the map (OPS_Map.addTracingsLayer).
 *
 * One LineString per positive tracing, from the injection point to the
 * detection point: negative and uncertain results are listed in the section
 * but not drawn. Only the tracings, caves and springs the current user can
 * see are returned (see openkis_TracingsArrows()).
 *
 * Parameters (all optional):
 *   id      only this tracing
 *   cave    only the tracings that start or end in this cave (code)
 *   spring  only the tracings that end in this spring (code)
 *
 * Besides the features the collection carries a "legend" member with the
 * translated labels of the colours and line styles.
 */
ob_start();
global $_FN;
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
FN_LoadMessagesFolder("extra/openkis");

$id = FN_GetParam("id", $_GET, "flat");
$cave = FN_GetParam("cave", $_GET, "flat");
$spring = FN_GetParam("spring", $_GET, "flat");

$features = array();
if (file_exists("sections/tracings") && FN_UserCanViewSection("tracings"))
{
    openkis_UpdateTracings();
    $config = FN_LoadConfig("modules/dbview/config.php", "tracings");
    $dbview = new FNDBVIEW($config);
    $params = array();
    $params['fields'] = "id,name,code,type,air_regime,tracer,release_date,cave_injection,cave_detection,spring_detection,latitude,longitude,elevation,result,sensor_type,path_length";
    $where = array("result LIKE 'positive'");
    if ($id !== "")
    {
        $where[] = "id = " . intval($id);
    }
    if ($cave !== "")
    {
        $where[] = "(cave_injection LIKE '" . addslashes($cave) . "' OR cave_detection LIKE '" . addslashes($cave) . "')";
    }
    if ($spring !== "")
    {
        $where[] = "spring_detection LIKE '" . addslashes($spring) . "'";
    }
    $params['appendquery'] = implode(" AND ", $where);
    $tracings = $dbview->GetResults($config, $params);
    $arrows = is_array($tracings) ? openkis_TracingsArrows($tracings) : array();

    $kinds = array(
        "water" => FN_Translate("water tracing"),
        "air_summer" => FN_Translate("summer air tracing (cold air)"),
        "air_winter" => FN_Translate("winter air tracing"));
    $point_label = function ($point) {
        $label = trim($point['code'] . " " . $point['name']);
        return htmlspecialchars($label !== "" ? $label : FN_Translate("free coordinates"));
    };
    foreach ($arrows as $arrow)
    {
        $tracing = $arrow['tracing'];
        $kind = openkis_TracingKind($tracing);
        $url = FN_RewriteLink("index.php?mod=tracings&op=view&id={$tracing['id']}", "&", true);
        $description = "<b>{$kinds[$kind]}</b><br />";
        $description .= FN_Translate("injection") . ": " . $point_label($arrow['from']) . "<br />";
        $description .= FN_Translate("detection") . ": " . $point_label($arrow['to']) . "<br />";
        if (!empty($tracing['tracer']))
        {
            $description .= FN_Translate("tracer") . ": " . htmlspecialchars($tracing['tracer']) . "<br />";
        }
        if (!empty($tracing['release_date']))
        {
            $description .= FN_Translate("release date") . ": " . date("d/m/Y", strtotime($tracing['release_date'])) . "<br />";
        }
        if (isset($tracing['path_length']) && trim($tracing['path_length']) !== "")
        {
            $description .= FN_Translate("polygonal distance") . ": " . htmlspecialchars($tracing['path_length']) . " m<br />";
        }
        $description .= "<div class=\"text-right\"><a onclick=\"try{window.open(this.href,'r','scrollbars=yes').focus();return false;}catch(e){return true;}\" target=\"_blank\" href=\"$url\"><span class=\"glyphicon glyphicon-eye-open\"></span> <b>" . FN_Translate("open") . "</b></a></div>";
        $name = trim(strval($tracing['name'])) !== "" ? $tracing['name'] : "{$arrow['from']['code']} -> {$arrow['to']['code']}";
        $features[] = array(
            "type" => "Feature",
            "id" => intval($tracing['id']),
            "geometry" => array(
                "type" => "LineString",
                "coordinates" => array(
                    array($arrow['from']['lon'], $arrow['from']['lat']),
                    array($arrow['to']['lon'], $arrow['to']['lat']))),
            "properties" => array(
                "name" => FN_FixEncoding($name),
                "description" => FN_FixEncoding($description),
                "kind" => $kind,
                "url" => $url));
    }
}

$legend = array(
    "title" => FN_Translate("tracer tests"),
    "water" => FN_Translate("water"),
    "air_summer" => FN_Translate("summer air (cold air)"),
    "air_winter" => FN_Translate("winter air"));
$json = json_encode(array("type" => "FeatureCollection", "legend" => $legend, "features" => $features));
while (false !== @ob_end_clean());
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
echo $json;
