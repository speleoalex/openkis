<?php
/**
 * Karst area sheet header: renders the record as thematic cards with a side
 * column (openkis_SheetRender(), extra/openkis/openkis_sheet.inc.php), like the
 * cave sheet. Included by FNDBVIEW::ViewRecordPage() for the main record, after
 * viewfooter.php, with $row (raw record), $Table (XMETADBForm) and $id_record
 * in scope. sections/areas/detail.tp.html suppresses the default field
 * rendering and closes the side column.
 *
 * The cards can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_areas.php (see sections/caves/viewheader.php).
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
    "location" => array("regione", "provincia", "comune", "cities", "surface", "mountaincommunity", "watershed"),
    "description" => array("description", "geographical_framework"),
    "geology" => array("geological_framework", "lithological", "geomorphology"),
    "hydrogeology" => array("hydrogeological", "aquifers"),
    "speleology" => array("caving"),
    "environment" => array("landcover", "othercharacteristics", "activequarries", "inactivequarries", "activelandfills", "inactivelandfills"),
    "groups and authors" => array("associations", "authors", "notes"),
    "bibliography" => array("bibliography"),
    "sheet data" => array("username", "recordinsert", "userupdate", "recordupdate"),
);
$openkis_cards_exclude = array("id", "code", "name", "filekml", "filelox", "groupview", "groupinsert");
$openkis_cards_longtext = array("description", "geographical_framework", "geological_framework", "lithological",
    "geomorphology", "hydrogeological", "aquifers", "caving", "landcover", "othercharacteristics", "notes", "bibliography");
$openkis_cards_author = array();
// the table titles the region and the municipality "Provincia"
$openkis_cards_titles = array("regione" => FN_Translate("region"), "comune" => FN_Translate("municipality"));

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}
//---- card definition ---------------------------------------------------------<

$place = array();
foreach (array("cities", "comune", "provincia") as $pf)
{
    if (!empty($values[$pf]) && !in_array(strip_tags($values[$pf]), $place))
        $place[] = strip_tags($values[$pf]);
}
$placetxt = FN_Translate("karst area");
if (count($place))
{
    $placetxt .= " &middot; " . implode(" &middot; ", $place);
}
$badges = "";
if (!empty($row['code']))
{
    $badges .= "<span class=\"badge badge-dark openkis-sheet-code\">" . htmlspecialchars((string) $row['code']) . "</span> ";
}

//---- files of the area (KML, 3D model) in the side column -------------------->
$tpl = "<!-- contents --><!-- group --> <!-- end_group --><!-- endgroup --> <!-- end_endgroup --><!-- item --> <!-- end_item -->";
foreach (array("filekml", "filelox") as $f)
{
    $tpl .= "<!-- item_$f --><div class=\"mb-1\"><!-- inputattributes:class=\"btn btn-sm btn-outline-primary\"  -->{input}</div><!-- end_item_$f -->";
}
$tpl .= "<!-- end_contents -->";
$frm->SetlayoutTemplateView($tpl);
$files = (string) $frm->HtmlShowView($values);
if (trim(strip_tags($files, "<a>")) === "")
{
    $files = "";
}
$loxfile = "{$_FN['datadir']}/{$_FN['database']}/{$Table->tablename}/{$row['id']}/filelox/" . (isset($row['filelox']) ? $row['filelox'] : "");
if (!empty($row['filelox']) && file_exists($loxfile) && openkis_FileAccess($Table->tablename, $row['id'], "filelox"))
{
    $file = urlencode(openkis_CaveViewerFile($Table->tablename, $row['id'], "filelox", $row['filelox']));
    $files .= "<a class=\"btn btn-sm btn-primary m-1\" href=\"{$_FN['siteurl']}openkis_cave_viewer.php?f=$file\" target=\"_blank\">&#10696; " . FN_Translate("3D model") . "</a>";
}
if ($files !== "")
{
    $GLOBALS['openkis_view_side'][] = openkis_SheetCard(FN_Translate("files"), "<div class=\"card-body p-2\">$files</div>", array("side" => true));
}
//---- files of the area --------------------------------------------------------<

openkis_SheetRender($frm, $values, array(
    "title" => htmlspecialchars((string) $row['name']),
    "place" => $placetxt,
    "badges" => $badges,
    "cards" => $openkis_cards,
    "exclude" => $openkis_cards_exclude,
    "longtext" => $openkis_cards_longtext,
    "author" => $openkis_cards_author,
    "titles" => $openkis_cards_titles,
    "photos" => array("photo1" => "authorphoto1"),
    "photos_title" => "image",
));
// the side column and the row are closed by detail.tp.html after {innertables}
