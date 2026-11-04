<?php
/**
 * Essential data of a record as an HTML fragment, shown by the map in a
 * modal window (bs_map/js/openkis_map_popup.js) instead of the small popover
 * of the marker: photo, place, dimensions, coordinates, the links to the
 * record and to the route. A region enables it in bs_map/conf/layers_<region>.js
 * with OPENKIS_MapPopup.enable("caves").
 *
 * Same visibility rules as the map data endpoints (openkis_SectionAccess():
 * a visitor who may not view the section gets only the records marked
 * public_map); the link to the record is printed only when the user may
 * view the section.
 *
 * GET: mod = section (default caves), id = record
 * The fields are the ones of $_FN['openkis_map_popup_fields'][<mod>] when
 * set (e.g. in extra/openkis_config.local.php), else the groups below;
 * labels and formats are the ones of the section's form (as the record sheet).
 */
ob_start();
global $_FN;
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
require_once "extra/openkis/openkis_sheet.inc.php";
FN_LoadMessagesFolder("extra/openkis");

$mod = FN_GetParam("mod", $_GET, "flat");
if ($mod == "")
    $mod = "caves";
$id = FN_GetParam("id", $_GET, "int");

function openkis_PopupOutput($html, $status = 200)
{
    while (false !== @ob_end_clean());
    http_response_code($status);
    header("Content-Type: text/html; charset=utf-8");
    header("Cache-Control: no-store");
    echo $html;
    exit();
}

if (!preg_match('/^[a-z0-9_]+$/i', $mod) || !file_exists("sections/$mod"))
    openkis_PopupOutput("", 404);
$_REQUEST['mod'] = $_FN['mod'] = $mod;
// ends with 403 when the user may see nothing of the section
$access = openkis_SectionAccess($mod);
$config = FN_LoadConfig("modules/dbview/config.php", $mod);
$tablename = $config['tables'];
$table = FN_XMDBTable($tablename);
$row = $id > 0 ? $table->GetRecord(array($table->primarykey => $id)) : false;
if (is_array($row))
{
    $rows = openkis_FilterPublicRecords($access, $config, array($row));
    if (empty($rows))
        $row = false;
}
if (!is_array($row))
    openkis_PopupOutput('<div class="alert alert-warning">' . htmlspecialchars(FN_Translate("record not found")) . '</div>', 404);

$frm = FN_XMDBForm($tablename);
$values = $frm->GetRecordTranslatedByPrimarykey($id);
if (!is_array($values))
    $values = $row;

// groups of fields, printed in this order (the form prints the fields of a
// group in its own order); missing fields are skipped
$groups = array(
    array("provincia", "comune", "localita", "mount", "elevation"),
    array("lenght_total", "depth_total", "depth_negative", "depth_positive"),
    array("geologicalformation", "lithology", "hydrology"),
);
if (!empty($_FN['openkis_map_popup_fields'][$mod]) && is_array($_FN['openkis_map_popup_fields'][$mod]))
    $groups = array($_FN['openkis_map_popup_fields'][$mod]);
// unit of the metric fields in the label
$titles = array();
foreach (array("elevation", "lenght_total", "depth_total", "depth_negative", "depth_positive") as $f)
{
    if (isset($frm->formvals[$f]) && !empty($frm->formvals[$f]['title']))
        $titles[$f] = $frm->formvals[$f]['title'] . " (m)";
}
$fieldshtml = "";
foreach ($groups as $fields)
    $fieldshtml .= openkis_ViewCardFields($frm, $values, $fields, array(), array(), $titles);

$code = isset($row['code']) ? trim((string) $row['code']) : "";
$name = isset($row['name']) ? trim((string) $row['name']) : "";
$title = trim($code . ($code != "" && $name != "" ? " - " : "") . $name);
if ($title == "")
    $title = "#$id";

$photo = "";
$photofull = "";
if (!empty($row['photo1']) && isset($table->fields['photo1']))
{
    $photo = $table->getFileUrl($row, "photo1", true);
    $photofull = $table->getFileUrl($row, "photo1", false);
}
$lat = isset($row['latitude']) && $row['latitude'] !== "" && $row['latitude'] !== null ? openkis_floatfrmt($row['latitude']) : "";
$lon = isset($row['longitude']) && $row['longitude'] !== "" && $row['longitude'] !== null ? openkis_floatfrmt($row['longitude']) : "";
$viewurl = FN_UserCanViewSection($mod) ? FN_RewriteLink("index.php?mod=$mod&op=view&id=$id", "&", true) : "";

$h = function ($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
};
$html = '<div class="openkis-popup" data-title="' . $h($title) . '">';
$html .= '<div class="row">';
if ($photo)
{
    $html .= '<div class="col-sm-5 openkis-popup-photo">';
    $html .= '<a href="' . $h($photofull ? $photofull : $photo) . '" target="_blank" rel="noopener">';
    $html .= '<img src="' . $h($photo) . '" alt="' . $h($title) . '" /></a></div>';
}
$html .= '<div class="' . ($photo ? 'col-sm-7' : 'col-sm-12') . ' openkis-popup-data">';
if (!empty($row['synonyms']))
    $html .= '<div class="openkis-popup-synonyms"><em>' . $h($row['synonyms']) . '</em></div>';
$html .= $fieldshtml;
if ($lat !== "" && $lon !== "")
{
    $html .= '<div class="row openkis-field"><div class="col-sm-4 col-lg-3 openkis-field-title">' . $h(FN_Translate("coordinates")) . ' WGS84</div>';
    $html .= '<div class="col-sm-8 col-lg-9 openkis-field-value">' . $h($lat) . ', ' . $h($lon) . '</div></div>';
}
$html .= '</div></div>';
$html .= '<div class="openkis-popup-actions">';
if ($viewurl != "")
    $html .= '<a class="btn btn-primary btn-sm" href="' . $h($viewurl) . '" target="_blank" rel="noopener"><span class="glyphicon glyphicon-eye-open"></span> ' . $h(FN_Translate("open record")) . '</a> ';
if ($lat !== "" && $lon !== "")
    $html .= '<a class="btn btn-default btn-sm" href="https://www.google.it/maps/dir//' . $h($lat) . ',' . $h($lon) . '/@' . $h($lat) . ',' . $h($lon) . ',18z" target="_blank" rel="noopener"><span class="glyphicon glyphicon-road"></span> ' . $h(FN_Translate("go to")) . '</a>';
$html .= '</div></div>';
openkis_PopupOutput($html);
