<?php
/**
 * Bibliography sheet header: renders the publication as thematic cards with a
 * side column for the cover and the attachments (openkis_SheetRender(),
 * extra/openkis/openkis_sheet.inc.php), like the cave sheet. Included by
 * FNDBVIEW::ViewRecordPage() for the main record, after viewfooter.php, with
 * $row, $Table and $id_record in scope. The caves, cavities and species of the
 * publication are listed by viewfooter.php, not in the cards.
 * sections/bibliography/detail.tp.html suppresses the default field rendering
 * and closes the side column.
 *
 * The cards can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_bibliography.php (see sections/caves/viewheader.php).
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
    "publication" => array("authors", "editor", "magazine", "number", "pages", "year", "city", "province", "country", "doi", "license"),
    "abstract" => array("abstract"),
    "contents" => array("zone", "photos", "drawings", "fossils"),
    "sheet data" => array("username", "recordinsert", "userupdate", "recordupdate"),
);
// listed by viewfooter.php, in the side column, or a badge of the title (topic)
$openkis_cards_exclude = array("id", "title", "topic", "codecaves", "surveys", "modified_caves", "modified_surveys", "codeartificials", "fauna",
    "file1", "file2", "file3", "groupview", "groupinsert");
$openkis_cards_longtext = array("abstract");
$openkis_cards_author = array();
// fields without a title in the table
$openkis_cards_titles = array("magazine" => FN_Translate("magazine"), "editor" => FN_Translate("publisher"), "number" => FN_Translate("issue"));

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}
//---- card definition ---------------------------------------------------------<

//---- title: authors and magazine under it, the year as code ------------------>
$place = array();
foreach (array("authors", "magazine") as $f)
{
    if (!empty($values[$f]))
        $place[] = openkis_SheetEscape(strip_tags($values[$f]));
}
$badges = "";
if (!empty($row['year']))
{
    $badges .= "<span class=\"badge badge-dark openkis-sheet-code\">" . openkis_SheetEscape($row['year']) . "</span> ";
}
if (!empty($row['topic']))
{
    $badges .= "<span class=\"badge badge-light border\">" . openkis_SheetEscape(strip_tags($values['topic'])) . "</span> ";
}
//---- title -------------------------------------------------------------------<

//---- attachments in the side column ------------------------------------------>
$tpl = "<!-- contents --><!-- group --> <!-- end_group --><!-- endgroup --> <!-- end_endgroup --><!-- item --> <!-- end_item -->";
foreach (array("file1", "file2", "file3") as $f)
{
    $tpl .= "<!-- item_$f --><div class=\"mb-2\"><div class=\"small text-muted\">{title}</div><!-- inputattributes:class=\"btn btn-sm btn-outline-primary\"  -->{input}</div><!-- end_item_$f -->";
}
$tpl .= "<!-- end_contents -->";
$frm->SetlayoutTemplateView($tpl);
$files = (string) $frm->HtmlShowView($values);
if (trim(strip_tags($files, "<a>")) !== "" && strpos($files, "<a") !== false)
{
    $GLOBALS['openkis_view_side'][] = openkis_SheetCard(FN_Translate("attachments"), "<div class=\"card-body p-2\">$files</div>", array("side" => true));
}
//---- attachments -------------------------------------------------------------<

openkis_SheetRender($frm, $values, array(
    "title" => openkis_SheetEscape($row['title']),
    "place" => implode(" &middot; ", $place),
    "badges" => $badges,
    "cards" => $openkis_cards,
    "exclude" => $openkis_cards_exclude,
    "longtext" => $openkis_cards_longtext,
    "author" => $openkis_cards_author,
    "titles" => $openkis_cards_titles,
    "photos" => array("photo1" => ""),
    "photos_title" => "cover",
));
// the side column and the row are closed by detail.tp.html after {innertables}
