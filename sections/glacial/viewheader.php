<?php
/**
 * Glacial cave sheet header: renders the record as thematic cards with a
 * side column (openkis_SheetRender(), extra/openkis/openkis_sheet.inc.php), like
 * the cave sheet. Included by FNDBVIEW::ViewRecordPage() for the main record,
 * after viewfooter.php, with $row (raw record), $Table (XMETADBForm) and
 * $id_record in scope. sections/glacial/detail.tp.html suppresses the
 * default field rendering and closes the side column.
 *
 * The cards can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_glacial.php (see sections/caves/viewheader.php).
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
    "identification" => array("PSG", "synonyms", "firstreference"),
    "location" => array("regione", "provincia", "comune", "localita", "mount", "velley", "glaciers",
        "latitude_txt", "longitude_txt", "coordinates_type", "elevation", "elevation_map", "elevation_gps",
        "map_denomination", "map_edition", "location_evaluation"),
    "dimensions" => array("lenght_total", "lenght_planimetric", "lenght_extension",
        "depth_positive", "depth_negative", "depth_total"),
    "characteristics" => array("typology", "hydrology", "meteorology", "trend", "practicability"),
    "description" => array("description", "authordescription", "itinerary", "authoritinerary", "wells"),
    "fauna" => array("fauna", "authorfauna"),
    "history" => array("history", "authorhistory"),
    "groups and chronology" => array("associations", "chronology", "notes", "changelog"),
    "sheet data" => array("check", "check_date", "status", "license", "recordinsert", "username", "userupdate",
        "recordupdate", "import_rawdata"),
);
$openkis_cards_exclude = array("id", "code", "name", "photo1", "authorphoto1", "latitude", "longitude",
    "coordnatesupdated", "recorddeleted", "groupinsert", "groupview", "country", "original_coordinates_type");
$openkis_cards_longtext = array("description", "itinerary", "history", "notes", "wells", "chronology",
    "changelog", "firstreference");
$openkis_cards_author = array("authordescription", "authoritinerary", "authorfauna", "authorhistory");

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}
//---- card definition ---------------------------------------------------------<

//---- title block ------------------------------------------------------------->
$place = array();
foreach (array("comune", "provincia") as $pf)
{
    if (!empty($values[$pf]))
        $place[] = strip_tags($values[$pf]);
}
$placetxt = "";
if (count($place))
{
    $placetxt = $place[0];
    if (count($place) > 1)
        $placetxt .= " (" . $place[1] . ")";
}
if (!empty($values['localita']))
{
    $placetxt .= ($placetxt != "" ? " &middot; " : "") . strip_tags($values['localita']);
}
$badges = "";
if (!empty($row['code']))
    $badges .= "<span class=\"badge badge-dark openkis-sheet-code\">" . htmlspecialchars((string) $row['code']) . "</span> ";
if (!empty($row['category']))
    $badges .= "<span class=\"badge badge-info\">" . strip_tags($values['category']) . "</span> ";
if (isset($row['closed']) && ($row['closed'] == "Y" || $row['closed'] == "S"))
    $badges .= "<span class=\"badge badge-danger openkis-badge-closed\">" . FN_Translate("closed cave") . "</span> ";
if (!empty($row['destroyed']) && $row['destroyed'] == "Y")
    $badges .= "<span class=\"badge badge-dark\">" . FN_Translate("destroyed") . "</span> ";
elseif (!empty($row['destroyed']) && $row['destroyed'] == "P")
    $badges .= "<span class=\"badge badge-secondary\">" . FN_Translate("partially destroyed") . "</span> ";
if (!empty($row['archeological']) && $row['archeological'] == "Y")
    $badges .= "<span class=\"badge badge-warning\">" . FN_Translate("archeological") . "</span> ";
if (!empty($row['status']))
    $badges .= "<span class=\"badge badge-light border\">" . FN_Translate($row['status']) . "</span> ";
//---- title block -------------------------------------------------------------<

openkis_SheetRender($frm, $values, array(
    "title" => htmlspecialchars((string) $row['name']),
    "place" => $placetxt,
    "badges" => $badges,
    "icon" => "{$_FN['siteurl']}" . openkis_GetIcon($row, $_FN['mod']),
    "cards" => $openkis_cards,
    "exclude" => $openkis_cards_exclude,
    "longtext" => $openkis_cards_longtext,
    "author" => $openkis_cards_author,
    "photos" => array("photo1" => "authorphoto1"),
    "photos_title" => "entrance photo",
));
// the side column and the row are closed by detail.tp.html after {innertables}
