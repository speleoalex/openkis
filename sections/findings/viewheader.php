<?php
/**
 * Finding sheet: renders the record as thematic cards with a side column for
 * the photos (openkis_SheetRender(), extra/openkis/openkis_sheet.inc.php), like
 * the cave sheet. Included by FNDBVIEW::ViewRecordPage() for the main record,
 * with $row, $Table and $id_record in scope. sections/findings/detail.tp.html
 * suppresses the default field rendering and closes the side column.
 *
 * The cards can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_findings.php
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
// the codes of the cavity are links to its sheet
openkis_SheetLinkCodes($frm);

//---- card definition --------------------------------------------------------->
$openkis_cards = array(
    "context" => array("codecave", "codeartificial", "position", "discovery_date", "discoverer",
        "repository", "inventory"),
    "dating" => array("period", "period_detail", "dating_from", "dating_to", "dating_method"),
    "remains" => array("finding_type", "taxa", "description"),
    "bibliography" => array("bibliography_ids", "citations"),
    "notes" => array("notes"),
    "sheet data" => array("username", "recordinsert", "userupdate", "recordupdate"),
);
// category and reliability are shown as badges next to the title
$openkis_cards_exclude = array("id", "name", "category", "reliability", "recorddeleted", "groupview");
// fields rendered as blocks with their title above the text
$openkis_cards_longtext = array("description", "citations", "notes");

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}
//---- card definition ---------------------------------------------------------<

// under the title: where the remains were found in the cavity
$place = !empty($values['position']) ? strip_tags($values['position']) : "";
$badges = "";
foreach (array("category", "reliability") as $f)
{
    if (!empty($values[$f]))
        $badges .= "<span class=\"badge badge-info\">" . FN_Translate(strip_tags($values[$f])) . "</span> ";
}

openkis_SheetRender($frm, $values, array(
    "title" => htmlspecialchars((string) $row['name']),
    "place" => $place,
    "badges" => $badges,
    "cards" => $openkis_cards,
    "exclude" => $openkis_cards_exclude,
    "longtext" => $openkis_cards_longtext,
    "photos" => array("photo1" => "authorphoto1", "photo2" => "authorphoto2"),
    "photos_title" => "photos",
));
// the side column and the row are closed by detail.tp.html after {innertables}
