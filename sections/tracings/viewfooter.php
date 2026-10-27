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
    echo "<div class=\"card openkis-card mb-3\"><div class=\"card-body p-0\">";
    echo "<iframe style=\"border:0px;width:100%;height:450px;display:block\" src=\"{$_FN['siteurl']}bs_map.htm?mod=caves&tracing=" . intval($row['id']) . "\"></iframe>";
    echo "</div></div>";
}
$points = array(FN_Translate("injection") => $arrow['from'], FN_Translate("detection") => $arrow['to']);
echo "<div class=\"card openkis-card mb-3\"><div class=\"card-body p-0\"><div class=\"table-responsive\"><table class=\"table table-sm mb-0\">";
echo "<tr><th></th><th></th><th>" . FN_Translate("coordinates WGS84") . "</th><th>" . FN_Translate("elevation") . "</th></tr>";
foreach ($points as $title => $point)
{
    $label = htmlspecialchars(trim($point['code'] . " " . $point['name']));
    if ($point['mod'] != "" && $point['id'] != "")
    {
        $label = "<a href=\"" . FN_RewriteLink("index.php?mod={$point['mod']}&op=view&id={$point['id']}") . "\">$label</a>";
    }
    if ($point === $arrow['from'] && floatval($row['latitude']) != 0)
    {
        $label .= ($label != "" ? " - " : "") . FN_Translate("free coordinates");
    }
    $elevation = ($point['elevation'] !== "" && $point['elevation'] !== null) ? htmlspecialchars($point['elevation']) . " m" : "";
    echo "<tr><th>$title</th><td>$label</td><td>" . round($point['lat'], 6) . ", " . round($point['lon'], 6) . "</td><td>$elevation</td></tr>";
}
echo "</table></div></div>";
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
echo "<div class=\"card-footer small text-muted\">$footer</div></div>";
