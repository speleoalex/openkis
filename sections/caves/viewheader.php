<?php
/**
 * Cave sheet header: renders the record as thematic cards.
 *
 * Included by FNDBVIEW::ViewRecordPage() for the main record only, with
 * $row (raw record), $Table (XMETADBForm), $id_record and $config in scope.
 * The section template sections/caves/detail.tp.html suppresses the default
 * field rendering, so this file is responsible for showing every field.
 *
 * Output layout:
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

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}

// leftover fields go into a final "other data" card
$used = $openkis_cards_exclude;
foreach ($openkis_cards as $cardfields)
{
    $used = array_merge($used, $cardfields);
}
$other = array();
foreach ($frm->formvals as $fname => $fdef)
{
    if (!isset($fdef['name']) || in_array($fname, $used))
        continue;
    if (isset($fdef['frm_show']) && (string) $fdef['frm_show'] === "0" && empty($fdef['view_show']))
        continue;
    $other[] = $fname;
}
if (count($other))
{
    $openkis_cards["other data"] = $other;
}
//---- card definition ---------------------------------------------------------<

/**
 * Render a set of fields of the current record with the view engine.
 *
 * @param XMETADBForm $frm
 * @param array $values translated record
 * @param array $fields field names to render, in order
 * @param array $longtext fields rendered as blocks
 * @param array $author fields rendered as credit lines
 * @return string html, empty when no field has a value
 */
function openkis_ViewCardFields($frm, $values, $fields, $longtext = array(), $author = array())
{
    $tpl = "<!-- contents --><!-- group --> <!-- end_group --><!-- endgroup --> <!-- end_endgroup --><!-- item --> <!-- end_item -->";
    foreach ($fields as $f)
    {
        if (!isset($frm->formvals[$f]))
            continue;
        if (in_array($f, $author))
        {
            $rowtpl = "<div class=\"openkis-field-author text-right text-muted small\">{input}</div>";
        }
        elseif (in_array($f, $longtext))
        {
            $rowtpl = "<div class=\"openkis-field openkis-field-block\"><div class=\"openkis-field-title\">{title}</div><div class=\"openkis-field-value\">{input}</div></div>";
        }
        elseif ($f == "import_rawdata")
        {
            $rowtpl = "<details class=\"openkis-field small\"><summary class=\"text-muted\">{title}</summary><div class=\"openkis-field-value\">{input}</div></details>";
        }
        else
        {
            $rowtpl = "<div class=\"row openkis-field\"><div class=\"col-sm-4 col-lg-3 openkis-field-title\">{title}</div><div class=\"col-sm-8 col-lg-9 openkis-field-value\">{input}</div></div>";
        }
        $tpl .= "<!-- item_$f -->$rowtpl<!-- end_item_$f -->";
    }
    $tpl .= "<!-- end_contents -->";
    $frm->SetlayoutTemplateView($tpl);
    $html = $frm->HtmlShowView($values);
    if (trim(strip_tags((string) $html, "<img><a><iframe>")) === "")
        return "";
    return $html;
}

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

// name and place on the left, badges and cave icon on the right
echo "<div class=\"openkis-sheet-title d-flex align-items-start mb-3\">";
echo "<div class=\"openkis-sheet-heading\">";
echo "<h2 class=\"openkis-sheet-name mb-1\">" . htmlspecialchars((string) $row['name']) . "</h2>";
if ($placetxt != "")
    echo "<div class=\"text-muted\">$placetxt</div>";
echo "</div>";
echo "<div class=\"openkis-sheet-tags d-flex align-items-start\">";
echo "<div class=\"openkis-sheet-badges\"><span class=\"badge badge-dark openkis-sheet-code\">" . htmlspecialchars((string) $row['code']) . "</span> $badges</div>";
echo "<img class=\"openkis-sheet-icon\" src=\"" . htmlspecialchars($icon) . "\" alt=\"\" />";
echo "</div></div>";
//---- title block -------------------------------------------------------------<

echo "<div class=\"row openkis-sheet\">";
echo "<div class=\"col-lg-8 openkis-sheet-main\">";
foreach ($openkis_cards as $cardtitle => $cardfields)
{
    $html = openkis_ViewCardFields($frm, $values, $cardfields, $openkis_cards_longtext, $openkis_cards_author);
    if ($html === "")
        continue;
    $cardid = "card_" . preg_replace('/[^a-z0-9]+/', '_', strtolower($cardtitle));
    echo "<div class=\"card openkis-card mb-3\" id=\"$cardid\">";
    echo "<div class=\"card-header\"><h5 class=\"mb-0\">" . FN_Translate($cardtitle) . "</h5></div>";
    echo "<div class=\"card-body\">$html</div>";
    echo "</div>";
}
echo "</div>";
echo "<div class=\"col-lg-4 openkis-sheet-side\">";
//---- entrance photo ----------------------------------------------------------->
$tpl = "<!-- contents --><!-- group --> <!-- end_group --><!-- endgroup --> <!-- end_endgroup --><!-- item --> <!-- end_item -->"
    . "<!-- item_photo1 --><div class=\"text-center openkis-photo\">{input}</div><!-- end_item_photo1 -->"
    . "<!-- item_authorphoto1 --><div class=\"text-center text-muted small mt-1\">{input}</div><!-- end_item_authorphoto1 -->"
    . "<!-- end_contents -->";
$frm->SetlayoutTemplateView($tpl);
$html = $frm->HtmlShowView($values);
if (trim(strip_tags((string) $html, "<img>")) !== "")
{
    echo "<div class=\"card openkis-side-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">" . FN_Translate("entrance photo") . "</h5></div>";
    echo "<div class=\"card-body p-2\">$html</div></div>";
}
//---- entrance photo -----------------------------------------------------------<
//---- side blocks prepared by viewfooter.php ---------------------------------->
if (!empty($GLOBALS['openkis_view_side']) && is_array($GLOBALS['openkis_view_side']))
{
    foreach ($GLOBALS['openkis_view_side'] as $block)
    {
        echo $block;
    }
}
//---- side blocks prepared by viewfooter.php ----------------------------------<
// the side column and the row are closed by detail.tp.html after {innertables}
