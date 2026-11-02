<?php

global $_FN;
// map of the measurement point and the other measurements of the same cave
if (!function_exists("openkis_TemperaturePoint") || empty($row['id']))
{
    return;
}
$point = openkis_TemperaturePoint($row);
if ($point !== false)
{
    echo "<div class=\"card openkis-card mb-3\"><div class=\"card-body p-0\">";
    echo "<iframe style=\"border:0px;width:100%;height:350px;display:block\" src=\"{$_FN['siteurl']}bs_map.htm?temperature=" . intval($row['id']) . "&point=circle&lat={$point['lat']}&lon={$point['lon']}&zoom=15\"></iframe>";
    echo "</div><div class=\"card-footer small text-muted\">";
    $label = htmlspecialchars(trim($point['code'] . " " . $point['name']));
    if ($point['mod'] != "" && $point['id'] != "")
    {
        $label = "<a href=\"" . FN_RewriteLink("index.php?mod={$point['mod']}&op=view&id={$point['id']}") . "\">$label</a>";
    }
    if ($label === "")
    {
        $label = FN_Translate("free coordinates");
    }
    echo $label . " - " . FN_Translate("coordinates WGS84") . ": " . round($point['lat'], 6) . ", " . round($point['lon'], 6);
    if ($point['elevation'] !== "" && $point['elevation'] !== null)
    {
        echo " - " . FN_Translate("entrance elevation") . ": " . htmlspecialchars($point['elevation']) . " m";
    }
    echo "</div></div>";
}
// the other measurements of the cave
if (!empty($row['cave']) && function_exists("openkis_TemperaturesCaveCard"))
{
    echo openkis_TemperaturesCaveCard($row['cave'], intval($row['id']));
}
