<?php
/**
 * Spring sheet header: renders the record as thematic cards with a side column
 * (openkis_SheetRender(), extra/openkis/openkis_sheet.inc.php), like the cave
 * sheet. Included by FNDBVIEW::ViewRecordPage() for the main record, after
 * viewfooter.php, with $row (raw record), $Table (XMETADBForm) and $id_record
 * in scope. sections/springs/detail.tp.html suppresses the default field
 * rendering and closes the side column.
 *
 * The cards can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_springs.php (see sections/caves/viewheader.php).
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
    "identification" => array("synonyms", "codecave"),
    "location" => array("regione", "provincia", "comune", "localita", "areas",
        "latitude_txt", "longitude_txt", "coordinates_type", "elevation", "elevation_map", "elevation_gps",
        "map_denomination", "map_edition"),
    "flow and use" => array("flow_average", "flow_min", "flow_max", "use", "utilization"),
    "characteristics" => array("hydrology", "hydrogeology", "morphology"),
    "description" => array("description", "authordescription", "itinerary", "authoritinerary"),
    "sheet data" => array("username", "recordinsert", "userupdate", "recordupdate", "import_rawdata"),
);
$openkis_cards_exclude = array("id", "code", "name", "latitude", "longitude", "coordnatesupdated", "country",
    "original_coordinates_type", "groupview", "groupinsert");
$openkis_cards_longtext = array("description", "itinerary");
$openkis_cards_author = array("authordescription", "authoritinerary");
// the table titles both fields "Idrogeologia"
$openkis_cards_titles = array("morphology" => FN_Translate("morphology"));

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}
//---- card definition ---------------------------------------------------------<

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
{
    $badges .= "<span class=\"badge badge-dark openkis-sheet-code\">" . htmlspecialchars((string) $row['code']) . "</span> ";
}
if (!empty($row['utilization']))
{
    $badges .= "<span class=\"badge badge-info\">" . strip_tags($values['utilization']) . "</span> ";
}

openkis_SheetRender($frm, $values, array(
    "title" => htmlspecialchars((string) $row['name']),
    "place" => $placetxt,
    "badges" => $badges,
    "icon" => "{$_FN['siteurl']}" . openkis_GetIcon($row, $_FN['mod']),
    "cards" => $openkis_cards,
    "exclude" => $openkis_cards_exclude,
    "longtext" => $openkis_cards_longtext,
    "author" => $openkis_cards_author,
    "titles" => $openkis_cards_titles,
    "photos" => array("photo1" => ""),
    "photos_title" => "photo",
));
// the side column and the row are closed by detail.tp.html after {innertables}
