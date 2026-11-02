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

$frm = FN_XMDBForm($Table->tablename);
$values = $frm->GetRecordTranslatedByPrimarykey($id_record);
if (!is_array($values))
{
    $values = $row;
}

//---- card definition --------------------------------------------------------->
$openkis_cards = array(
    "taxonomy" => array("phylum", "class", "order", "family", "genus", "sinon"),
    "description" => array("description"),
    "sheet data" => array("username", "recordinsert", "userupdate", "recordupdate"),
);
// "author" (author of the species name, where the table has it) goes in the title
$openkis_cards_exclude = array("id", "scientific_name", "author", "name", "type", "groupview", "groupinsert");
$openkis_cards_longtext = array("description");
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
