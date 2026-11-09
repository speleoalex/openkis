<?php
/**
 * Sheet of a fauna sighting, header: renders the record as thematic cards with
 * a side column (openkis_SheetRender(), extra/openkis/openkis_sheet.inc.php),
 * like the cave sheet. Included by FNDBVIEW::ViewRecordPage() for the main
 * record, after viewfooter.php (which reads the species, the cave or cavity and
 * the map into $GLOBALS), with $row, $Table and $id_record in scope.
 * sections/fauna_surveys/detail.tp.html suppresses the default field rendering
 * and closes the side column.
 *
 * The cards can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_fauna_surveys.php (see sections/caves/viewheader.php).
 */
global $_FN;
require_once "extra/openkis/openkis_sheet.inc.php";
require_once "extra/openkis/openkis_fauna.inc.php";

$frm = FN_XMDBForm($Table->tablename);
$values = $frm->GetRecordTranslatedByPrimarykey($id_record);
if (!is_array($values))
{
    $values = $row;
}
// the species is in "name" (OpenKIS table) or "scientific_name" (older registers)
$species_field = isset($frm->formvals['scientific_name']) ? "scientific_name" : "name";

//---- card definition --------------------------------------------------------->
$openkis_cards = array(
    "sighting" => array($species_field, "codecave", "codeartificial", "ammount", "date", "caver"),
    "information" => array("description"),
    "sheet data" => array("username", "recordinsert", "userupdate", "recordupdate"),
);
$openkis_cards_exclude = array("id", "groupview", "groupinsert");
$openkis_cards_longtext = array("description");
$openkis_cards_author = array();
$openkis_cards_titles = array();

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}
//---- card definition ---------------------------------------------------------<

//---- title: species; under it its common name, the cave and the date, with links ->
$sighting = isset($GLOBALS['openkis_sighting']) ? $GLOBALS['openkis_sighting'] : array("species" => false, "place" => false, "mod" => "");
$species_name = isset($row[$species_field]) ? trim($row[$species_field]) : "";
$title = $species_name !== "" ? "<i>" . htmlspecialchars($species_name) . "</i>" : FN_Translate("fauna survey");
$place = array();
$badges = "";
if (!empty($sighting['species']))
{
    $sp = $sighting['species'];
    $url = FN_RewriteLink("index.php?mod=fauna&op=view&id={$sp['id']}");
    $place[] = "<a href=\"$url\">" . htmlspecialchars($sp['name'] != "" ? $sp['name'] : FN_Translate("species sheet")) . "</a>";
    if ($sp['type'] != "")
    {
        // the ecological categories are stored in Italian upper case (TROGLOBIO, STIGOFILO, ...)
        $badges .= "<span class=\"badge badge-info\">" . htmlspecialchars(ucfirst(strtolower($sp['type']))) . "</span> ";
    }
    // name no longer valid (taxon_status of the species sheet): badge and valid name or genus
    $statuses = openkis_FaunaStatuses();
    if (!empty($sp['taxon_status']) && $sp['taxon_status'] !== "valid" && isset($statuses[$sp['taxon_status']]))
    {
        $badges .= "<span class=\"badge badge-warning bg-warning text-dark\">" . htmlspecialchars(openkis_SheetUcfirst($statuses[$sp['taxon_status']])) . "</span> ";
    }
    if (!empty($sighting['resolved']['moved']))
    {
        $place[] = openkis_FaunaResolvedNote($sighting['resolved'], $sighting['resolved_id']);
    }
}
if (!empty($sighting['place']))
{
    $cave = $sighting['place'];
    $url = FN_RewriteLink("index.php?mod={$sighting['mod']}&op=view&id={$cave['id']}");
    $text = htmlspecialchars(trim($cave['code'] . " " . $cave['name']));
    if ($cave['comune'] != "")
        $text .= " (" . htmlspecialchars($cave['comune']) . ")";
    $place[] = "<a href=\"$url\">$text</a>";
}
elseif (!empty($row['codecave']) || !empty($row['codeartificial']))
{
    $place[] = htmlspecialchars(!empty($row['codecave']) ? $row['codecave'] : $row['codeartificial']);
}
if (openkis_FaunaDate($row['date']) !== "")
{
    $place[] = openkis_FaunaDate($row['date']);
}
//---- title -------------------------------------------------------------------<

openkis_SheetRender($frm, $values, array(
    "title" => $title,
    "place" => implode(" &middot; ", $place),
    "badges" => $badges,
    "cards" => $openkis_cards,
    "exclude" => $openkis_cards_exclude,
    "longtext" => $openkis_cards_longtext,
    "author" => $openkis_cards_author,
    "titles" => $openkis_cards_titles,
    "photos" => array("photo1" => "authorphoto1", "photo2" => "authorphoto2"),
    "photos_title" => "photos",
));
// the side column and the row are closed by detail.tp.html after {innertables}
