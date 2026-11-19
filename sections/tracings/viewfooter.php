<?php

global $_FN;
// map of the arrow (positive tracings only, as on the general map) and the
// two points with distance and elevation difference
if (!function_exists("openkis_TracingsArrows") || empty($row['id']))
{
    return;
}
$arrows = openkis_TracingsArrows(array($row));
if (count($arrows) == 0)
{
    return;
}
$arrow = $arrows[0];
if ($row['result'] == "positive")
{
    echo openkis_MapCard("{$_FN['siteurl']}bs_map.htm?tracing=" . intval($row['id']));
}
$points = array(FN_Translate("injection") => $arrow['from'], FN_Translate("detection") => $arrow['to']);
$rows = array();
foreach ($points as $title => $point)
{
    $label = htmlspecialchars(trim($point['code'] . " " . $point['name']));
    if ($point['mod'] != "" && $point['id'] != "")
    {
        $label = "<a href=\"" . FN_RewriteLink("index.php?mod={$point['mod']}&op=view&id={$point['id']}") . "\">$label</a>";
    }
    if (!empty($point['free']))
    {
        $label .= ($label != "" ? " - " : "") . FN_Translate("free coordinates");
    }
    $rows[] = array(
        "label" => $title,
        "point" => $label,
        "coords" => round($point['lat'], 6) . ", " . round($point['lon'], 6),
        "elevation_value" => ($point['elevation'] !== "" && $point['elevation'] !== null) ? htmlspecialchars($point['elevation']) . " m" : "",
    );
}
// horizontal distance (haversine) and elevation difference, as in the NASO register
$from = $arrow['from'];
$to = $arrow['to'];
$dlat = deg2rad($to['lat'] - $from['lat']);
$dlon = deg2rad($to['lon'] - $from['lon']);
$a = sin($dlat / 2) * sin($dlat / 2) + cos(deg2rad($from['lat'])) * cos(deg2rad($to['lat'])) * sin($dlon / 2) * sin($dlon / 2);
$distance = 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
$footer = FN_Translate("distance in plan") . ": " . round($distance) . " m";
if (is_numeric($from['elevation']) && is_numeric($to['elevation']) && floatval($from['elevation']) != 0 && floatval($to['elevation']) != 0)
{
    $difference = floatval($to['elevation']) - floatval($from['elevation']);
    $footer .= " - " . FN_Translate("elevation difference") . ": " . ($difference > 0 ? "+" : "") . round($difference) . " m";
}
if (isset($row['path_length']) && trim($row['path_length']) !== "")
{
    $footer .= " - " . FN_Translate("polygonal distance") . ": " . htmlspecialchars($row['path_length']) . " m";
}
echo openkis_TplApply("tracings_points_card", array(
    "coordinates" => FN_Translate("coordinates WGS84"),
    "elevation" => FN_Translate("elevation"),
    "rows" => $rows,
    "footer" => $footer,
));
