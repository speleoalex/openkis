<?php
/**
 * Cave sheet header: renders the record as thematic cards.
 *
 * Included by FNDBVIEW::ViewRecordPage() for the main record only, with
 * $row (raw record), $Table (XMETADBForm), $id_record and $config in scope.
 * The section template sections/caves/detail.tp.html suppresses the default
 * field rendering, so this file is responsible for showing every field.
 *
 * Output layout (openkis_SheetRender(), extra/openkis/openkis_sheet.inc.php):
 *   - title block (icon, code, name, place, status badges)
 *   - <div class="row"><div class="col-lg-8"> thematic cards </div>
 *     <div class="col-lg-4"> photo card + side blocks pushed by viewfooter.php
 *     into $GLOBALS['openkis_view_side'] ... (left open: detail.tp.html prints
 *     {innertables} here and closes both divs)
 *
 * The card list can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_{mod}.php which must define
 * the array $openkis_cards (title => list of field names).
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
    "identification" => array("synonyms", "caveslinks", "alias", "firstreference"),
    "location" => array("regione", "provincia", "comune", "localita", "mount", "velley", "areas",
        "latitude_txt", "longitude_txt", "coordinates_type", "elevation", "elevation_map", "elevation_gps",
        "map_denomination", "map_edition", "location_evaluation"),
    "dimensions" => array("lenght_total", "lenght_planimetric", "lenght_extension", "lenght_scope",
        "depth_positive", "depth_negative", "depth_total"),
    "characteristics" => array("geologicalformation", "lithology", "hydrology", "trend", "practicability",
        "meteorology", "archeological", "marine", "lake", "environmentalrisk", "tourist", "closed",
        "closed_notes", "destroyed"),
    "description" => array("description", "authordescription", "itinerary", "authoritinerary", "wells"),
    "fauna" => array("fauna", "authorfauna"),
    "geology" => array("geology"),
    "history" => array("history", "authorhistory"),
    "groups and chronology" => array("associations", "chronology", "notes", "changelog"),
    "sheet data" => array("check", "check_date", "status", "recordinsert", "username", "userupdate",
        "recordupdate", "import_rawdata"),
);
// fields that are never shown inside a card (shown elsewhere or internal)
$openkis_cards_exclude = array("id", "code", "name", "photo1", "authorphoto1", "latitude", "longitude",
    "coordnatesupdated", "recorddeleted", "groupinsert", "groupview", "xxx", "country", "original_coordinates_type");
// fields rendered as a block with the title above the text
$openkis_cards_longtext = array("description", "itinerary", "history", "geology", "fauna", "notes", "wells",
    "chronology", "closed_notes", "changelog", "firstreference");
// fields rendered as a small right-aligned credit line
$openkis_cards_author = array("authordescription", "authoritinerary", "authorfauna", "authorhistory");
// other codes of the cave shown as badges next to the cadastre code instead of
// inside a card, set by the regional file: field => label, e.g. "code_fslo" => "FSLo"
$openkis_sheet_codes = array();

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}

//---- card definition ---------------------------------------------------------<

//---- title block ------------------------------------------------------------->
$icon = "{$_FN['siteurl']}" . openkis_GetIcon($row, $_FN['mod']);
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
if (!empty($values['areas']))
{
    $placetxt .= ($placetxt != "" ? " &middot; " : "") . FN_Translate("area") . " " . strip_tags($values['areas']);
}
$badges = "";
if (isset($row['closed']) && ($row['closed'] == "Y" || $row['closed'] == "S"))
    $badges .= "<span class=\"badge badge-danger openkis-badge-closed\">" . FN_Translate("closed cave") . "</span> ";
elseif (isset($row['closed']))
    $badges .= "<span class=\"badge badge-success openkis-badge-open\">" . FN_Translate("open cave") . "</span> ";
if (!empty($row['destroyed']) && $row['destroyed'] == "Y")
    $badges .= "<span class=\"badge badge-dark\">" . FN_Translate("destroyed") . "</span> ";
elseif (!empty($row['destroyed']) && $row['destroyed'] == "P")
    $badges .= "<span class=\"badge badge-secondary\">" . FN_Translate("partially destroyed") . "</span> ";
if (!empty($row['tourist']) && $row['tourist'] == "Y")
    $badges .= "<span class=\"badge badge-info\">" . FN_Translate("tourist cave") . "</span> ";
if (!empty($row['archeological']) && $row['archeological'] == "Y")
    $badges .= "<span class=\"badge badge-warning\">" . FN_Translate("archeological") . "</span> ";
if (!empty($row['marine']) && $row['marine'] == "Y")
    $badges .= "<span class=\"badge badge-primary\">" . FN_Translate("marine") . "</span> ";
if (!empty($row['status']))
    $badges .= "<span class=\"badge badge-light border\">" . FN_Translate($row['status']) . "</span> ";
$codes = "<span class=\"badge badge-dark openkis-sheet-code\">" . htmlspecialchars((string) $row['code']) . "</span> ";
foreach ($openkis_sheet_codes as $codefield => $codelabel)
{
    if (!empty($row[$codefield]))
    {
        $codetitle = isset($frm->formvals[$codefield]['title']) ? $frm->formvals[$codefield]['title'] : $codelabel;
        $codes .= "<span class=\"badge badge-primary openkis-sheet-code openkis-sheet-code-$codefield\" title=\"" . htmlspecialchars(strip_tags($codetitle)) . "\">"
            . htmlspecialchars($codelabel) . " " . htmlspecialchars((string) $row[$codefield]) . "</span> ";
    }
}
//---- title block -------------------------------------------------------------<

openkis_SheetRender($frm, $values, array(
    "title" => htmlspecialchars((string) $row['name']),
    "place" => $placetxt,
    "badges" => $codes . $badges,
    "icon" => $icon,
    "cards" => $openkis_cards,
    "exclude" => array_merge($openkis_cards_exclude, array_keys($openkis_sheet_codes)),
    "longtext" => $openkis_cards_longtext,
    "author" => $openkis_cards_author,
    "photos" => array("photo1" => "authorphoto1"),
    "photos_title" => "entrance photo",
));
// the side column and the row are closed by detail.tp.html after {innertables}
