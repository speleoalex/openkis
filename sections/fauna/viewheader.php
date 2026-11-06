<?php
/**
 * Species sheet header: renders the record as thematic cards with a side
 * column for the photos (openkis_SheetRender(), extra/openkis/openkis_sheet.inc.php),
 * like the cave sheet. Included by FNDBVIEW::ViewRecordPage() for the main
 * record, after viewfooter.php, with $row (raw record), $Table (XMETADBForm)
 * and $id_record in scope. sections/fauna/detail.tp.html suppresses the
 * default field rendering and closes the side column.
 *
 * The cards can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_fauna.php (see sections/caves/viewheader.php).
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

//---- card definition --------------------------------------------------------->
$openkis_cards = array(
    "taxonomy" => array("phylum", "class", "order", "family", "genus", "sinon"),
    // validity of the name (taxon_status, accepted_name...): only where the table has these fields
    "name status" => array("taxon_status", "accepted_name", "taxon_source", "taxon_note"),
    "description" => array("description"),
    "sheet data" => array("username", "recordinsert", "userupdate", "recordupdate"),
);
// "author" (author of the species name, where the table has it) goes in the title
$openkis_cards_exclude = array("id", "scientific_name", "author", "name", "type", "groupview", "groupinsert");
$openkis_cards_longtext = array("description", "taxon_note");
$openkis_cards_author = array();
// the table titles the synonyms "name"
$openkis_cards_titles = array("sinon" => FN_Translate("synonyms"));

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}
//---- card definition ---------------------------------------------------------<

$title = isset($row['scientific_name']) && trim($row['scientific_name']) !== "" ? "<i>" . htmlspecialchars($row['scientific_name']) . "</i>" : htmlspecialchars((string) $row['name']);
if (!empty($row['author']))
{
    $title .= " <small class=\"text-muted\">" . htmlspecialchars($row['author']) . "</small>";
}
$place = array();
if (isset($row['scientific_name']) && trim($row['scientific_name']) !== "" && !empty($values['name']))
{
    $place[] = strip_tags($values['name']);
}
foreach (array("class", "order") as $f)
{
    if (!empty($values[$f]))
        $place[] = strip_tags($values[$f]);
}
$badges = "";
if (!empty($row['type']))
{
    // the ecological categories are stored in Italian upper case (TROGLOBIO, STIGOFILO, ...)
    $badges .= "<span class=\"badge badge-info\">" . htmlspecialchars(ucfirst(strtolower(strip_tags($values['type'])))) . "</span> ";
}

//---- name no longer valid: badge and notice under the title, names grouped here ->
$notice = "";
if (openkis_FaunaHasStatus() && isset($row['scientific_name']) && trim($row['scientific_name']) !== "")
{
    $statuses = openkis_FaunaStatuses();
    $resolved = openkis_FaunaResolve($row, $row['scientific_name']);
    if (isset($statuses[$resolved['status']]) && $resolved['status'] !== "valid")
    {
        $badges .= "<span class=\"badge badge-warning bg-warning text-dark\">" . htmlspecialchars(openkis_SheetUcfirst($statuses[$resolved['status']])) . "</span> ";
        if ($resolved['moved'])
        {
            $link = "<i>" . htmlspecialchars($resolved['name']) . "</i>";
            $target = openkis_FaunaRecordsByField("fauna", "scientific_name", array($resolved['name']), "id,scientific_name");
            if (count($target))
            {
                $t = reset($target);
                $link = "<a href=\"" . FN_RewriteLink("index.php?mod=fauna&op=view&id={$t['id']}") . "\">$link</a>";
            }
            if (trim((string) $row['accepted_name']) !== "")
                $text = FN_Translate("this name is no longer valid; valid name") . ": $link.";
            else
                $text = FN_Translate("this name is not valid and cannot be referred to a single species: the identification should be checked on the specimens") . ". "
                    . FN_Translate("its sightings are grouped as") . " $link.";
            $text .= " " . FN_Translate("the sightings keep the name cited by their source") . ".";
            $notice .= "<div class=\"alert alert-warning openkis-fauna-status\" role=\"note\">$text</div>";
        }
    }
    // names no longer valid whose sightings are shown on this sheet
    $grouped = openkis_FaunaNamesOf($row['scientific_name']);
    array_shift($grouped);
    if (count($grouped))
    {
        $sheets = openkis_FaunaNotValidSheets();
        $links = array();
        foreach ($grouped as $n)
        {
            $sheet = $sheets[openkis_FaunaKey($n)];
            $links[] = "<a href=\"" . FN_RewriteLink("index.php?mod=fauna&op=view&id={$sheet['id']}") . "\"><i>" . htmlspecialchars($n) . "</i></a>"
                . (isset($statuses[$sheet['taxon_status']]) ? " <small class=\"text-muted\">(" . htmlspecialchars($statuses[$sheet['taxon_status']]) . ")</small>" : "");
        }
        $notice .= "<div class=\"alert alert-info openkis-fauna-grouped\" role=\"note\">" . FN_Translate("this sheet also includes the sightings cited as") . ": " . implode(", ", $links) . ".</div>";
    }
}
//---- name status -------------------------------------------------------------<

openkis_SheetRender($frm, $values, array(
    "notice" => $notice,
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
