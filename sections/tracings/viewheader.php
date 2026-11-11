<?php
/**
 * Tracer test sheet: renders the record as thematic cards with a side column
 * (openkis_SheetRender(), extra/openkis/openkis_sheet.inc.php), like the cave
 * sheet. Included by FNDBVIEW::ViewRecordPage() for the main record, after
 * viewfooter.php (the arrow map and the distance table are echoed there below
 * the sheet), with $row, $Table and $id_record in scope.
 * sections/tracings/detail.tp.html suppresses the default field rendering and
 * closes the side column.
 *
 * The cards can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_tracings.php
 * (see sections/caves/viewheader.php).
 */
global $_FN;
require_once "extra/openkis/openkis_sheet.inc.php";

$frm = FN_XMDBForm($Table->tablename);
$values = $frm->GetRecordTranslatedByPrimarykey($id_record);
if (!is_array($values))
{
    $values = $row;
}

//---- card definition --------------------------------------------------------->
$openkis_cards = array(
    "tracer test" => array("code", "type", "air_regime", "tracer", "release_date", "sensor_type",
        "result", "path_length"),
    "results" => array("result_notes"),
    "injection" => array("cave_injection", "uncatalogued_injection", "latitude_txt", "longitude_txt",
        "coordinates_type", "elevation"),
    "detection" => array("cave_detection", "spring_detection", "uncatalogued_detection",
        "detection_latitude_txt", "detection_longitude_txt", "detection_coordinates_type", "detection_elevation"),
    "data" => array("file", "attachment1", "attachment2"),
    "notes" => array("notes"),
    "sheet data" => array("username", "recordinsert", "userupdate", "recordupdate"),
);
$openkis_cards_exclude = array("id", "name", "latitude", "longitude", "detection_latitude", "detection_longitude",
    "coordnatesupdated", "recorddeleted", "groupview");
// fields rendered as blocks with their title above the text
$openkis_cards_longtext = array("result_notes", "notes");

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}
//---- card definition ---------------------------------------------------------<

// under the title: the tracer thrown into the water or the air
$place = !empty($values['tracer']) ? strip_tags($values['tracer']) : "";
$badges = "";
if (!empty($row['code']))
{
    $badges .= "<span class=\"badge badge-dark openkis-sheet-code\">" . htmlspecialchars((string) $row['code']) . "</span> ";
}
foreach (array("type", "result") as $f)
{
    if (!empty($values[$f]))
        $badges .= "<span class=\"badge badge-info\">" . openkis_TracingViewValue($values, $f) . "</span> ";
}

openkis_SheetRender($frm, $values, array(
    "title" => htmlspecialchars((string) $row['name']),
    "place" => $place,
    "badges" => $badges,
    "cards" => $openkis_cards,
    "exclude" => $openkis_cards_exclude,
    "longtext" => $openkis_cards_longtext,
    "photos" => array("photo1" => "photo1_caption"),
    "photos_title" => "image",
));
// the side column and the row are closed by detail.tp.html after {innertables}
