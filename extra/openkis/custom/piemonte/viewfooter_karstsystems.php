<?php

/**
 * Piemonte karst-system map. The shared cavesystem footer already lists the
 * linked caves and their details; this regional addition supplies the map.
 * Only caves visible to the current visitor are used for marker selection and
 * map centering (openkis_SheetRecordsByCode applies section/record permissions).
 */
require_once "extra/openkis/openkis_sheet.inc.php";

$codes = array_filter(array_map("trim", explode(",", isset($row['caves']) ? $row['caves'] : "")), "strlen");
$caves = openkis_SheetRecordsByCode("caves", $codes, "id,code,name,latitude,longitude,elevation,lenght_total,depth_total");
if (!count($caves))
{
    return;
}

$map_url = openkis_SheetCavesMapUrl($caves);
$map_options = openkis_SheetMapOptions($map_url);
if (!empty($map_options['map']))
{
    echo openkis_SheetCard(
        FN_Translate("map"),
        "<div class=\"card-body p-0\">{$map_options['map']}</div>",
        array("footer" => $map_options['footer'], "id" => "card_karstsystem_map"));
}
?>
