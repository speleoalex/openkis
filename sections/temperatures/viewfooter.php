<?php

global $_FN;
/**
 * Temperature sheet footer. Runs before viewheader.php: blocks pushed into
 * $GLOBALS['openkis_view_side'] are printed by viewheader.php in the side
 * column, while echoed html goes below the sheet at full width.
 */
require_once "extra/openkis/openkis_sheet.inc.php";
$GLOBALS['openkis_view_side'] = array();
// map of the measurement point, in the side column
if (!function_exists("openkis_TemperaturePoint") || empty($row['id']))
{
    return;
}
$point = openkis_TemperaturePoint($row);
if ($point !== false)
{
    $body = "<div class=\"card-body p-0\">";
    $body .= "<iframe style=\"border:0px;width:100%;height:250px;display:block\" src=\"{$_FN['siteurl']}bs_map.htm?temperature=" . intval($row['id']) . "&point=circle&lat={$point['lat']}&lon={$point['lon']}&zoom=15\"></iframe>";
    $body .= "</div>";
    $footer = "";
    $label = htmlspecialchars(trim($point['code'] . " " . $point['name']));
    if ($point['mod'] != "" && $point['id'] != "")
    {
        $label = "<a href=\"" . FN_RewriteLink("index.php?mod={$point['mod']}&op=view&id={$point['id']}") . "\">$label</a>";
    }
    if ($label === "")
    {
        $label = FN_Translate("free coordinates");
    }
    $footer .= $label . "<br />" . FN_Translate("coordinates WGS84") . ": " . round($point['lat'], 6) . ", " . round($point['lon'], 6);
    if ($point['elevation'] !== "" && $point['elevation'] !== null)
    {
        $footer .= " - " . FN_Translate("entrance elevation") . ": " . htmlspecialchars($point['elevation']) . " m";
    }
    $GLOBALS['openkis_view_side'][] = openkis_SheetCard(FN_Translate("map"), $body, array("side" => true, "footer" => $footer));
}
// the other measurements of the cave, or of the spring, below the sheet
if (!empty($row['cave']) && function_exists("openkis_TemperaturesCaveCard"))
{
    echo openkis_TemperaturesCaveCard($row['cave'], intval($row['id']));
}
elseif (!empty($row['spring']) && function_exists("openkis_TemperaturesCaveCard"))
{
    echo openkis_TemperaturesCaveCard($row['spring'], intval($row['id']), "spring");
}
