<?php
/**
 * Survey sheet of an artificial cavity: renders the record as thematic cards
 * with a side column (openkis_SheetRender(), extra/openkis/openkis_sheet.inc.php),
 * like the cave sheet. Included by FNDBVIEW::ViewRecordPage() for the main
 * record, after viewfooter.php (the 3D viewer of the LAS file is echoed there
 * below the sheet), with $row, $Table and $id_record in scope.
 * sections/survey_artificials/detail.tp.html suppresses the default field
 * rendering and closes the side column.
 *
 * The cards can be overridden per region by creating
 * extra/openkis/custom/{custom}/viewheader_cards_survey_artificials.php
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
// the code of the cavity is a link to its sheet
openkis_SheetLinkCodes($frm);

//---- card definition --------------------------------------------------------->
$openkis_cards = array(
    "survey" => array("codeartificial", "date", "author", "makers", "license", "accuracy"),
    "description" => array("description"),
    "files" => array("file", "filedwg", "filekml", "filelox", "filelas", "pdf"),
    "sheet data" => array("username", "recordinsert", "userupdate", "recordupdate"),
);
// priority only orders the surveys of the cavity in the inner table
$openkis_cards_exclude = array("id", "name", "priority", "groupview");
// fields rendered as blocks with their title above the text
$openkis_cards_longtext = array("description");

if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewheader_cards_{$_FN['mod']}.php");
}
//---- card definition ---------------------------------------------------------<

// under the title: the date of the survey and who made it
$place = array();
if (!empty($values['date']))
    $place[] = openkis_SheetDateText(strip_tags($values['date']));
foreach (array("author", "makers") as $f)
{
    if (!empty($values[$f]))
    {
        $place[] = strip_tags($values[$f]);
        break;
    }
}
openkis_SheetRender($frm, $values, array(
    "title" => htmlspecialchars((string) $row['name']),
    "place" => implode(" &middot; ", $place),
    "cards" => $openkis_cards,
    "exclude" => $openkis_cards_exclude,
    "longtext" => $openkis_cards_longtext,
    "photos" => array("photo1" => ""),
    "photos_title" => "image",
));
// the side column and the row are closed by detail.tp.html after {innertables}
