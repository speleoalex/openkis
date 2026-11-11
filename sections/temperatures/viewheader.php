<?php
/**
 * Temperature or water analysis sheet: renders the record as thematic cards
 * with a side column (openkis_SheetRender(), extra/openkis/openkis_sheet.inc.php),
 * like the cave sheet. Included by FNDBVIEW::ViewRecordPage() for the main
 * record, after viewfooter.php, with $row (raw record), $Table (XMETADBForm) and
 * $id_record in scope. sections/temperatures/detail.tp.html suppresses the
 * default field rendering and closes the side column.
 *
 * The cards can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_temperatures.php
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
    "measurement" => array("type", "medium", "temperature", "temperature_external", "year", "month", "day", "hour"),
    "monitoring" => array("start_date", "end_date", "acq_interval", "samples"),
    "statistics" => array("t_mean", "t_min", "t_max", "t_median", "t_mode", "t_stddev"),
    "location" => array("cave", "spring", "position", "measure_elevation", "entrance_distance",
        "latitude_txt", "longitude_txt", "coordinates_type"),
    "characteristics" => array("ventilation", "snow_ice", "water"),
    "instrument" => array("instrument", "accuracy", "resolution"),
    "water analysis" => array("ph", "conductivity", "hardness", "parameters"),
    "bibliography" => array("bibliography", "bibliography_ids"),
    "data" => array("file", "attachment1", "attachment2", "data_holder", "ucc"),
    "notes" => array("notes"),
    "sheet data" => array("username", "recordinsert", "userupdate", "recordupdate"),
);
$openkis_cards_exclude = array("id", "name", "sort_date", "latitude", "longitude", "coordnatesupdated",
    "recorddeleted", "groupview");
// fields rendered as blocks with their title above the text
$openkis_cards_longtext = array("parameters", "bibliography", "notes");

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}
//---- card definition ---------------------------------------------------------<

// under the title: the cave or the spring the measurement belongs to
$place = "";
foreach (array("cave", "spring") as $f)
{
    if (!empty($values[$f]))
    {
        $place = strip_tags($values[$f]);
        break;
    }
}
$badges = "";
foreach (array("type", "medium") as $f)
{
    if (!empty($values[$f]))
        $badges .= "<span class=\"badge badge-info\">" . openkis_TemperatureViewValue($values, $f) . "</span> ";
}
if (!empty($row['ucc']) && $row['ucc'] == "1")
{
    $badges .= "<span class=\"badge badge-secondary\">UCC</span> ";
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
