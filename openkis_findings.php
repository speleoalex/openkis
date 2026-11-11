<?php
/**
 * Caves and artificial cavities with paleontological and archaeological
 * findings as GeoJSON for the map (OPS_Map.addFindingsLayer): one Point per
 * place with the number of its findings by category, the types of finds and
 * the periods. Only the caves and cavities the user can see are returned,
 * nothing at all when the user may not view the section findings. A
 * restricted finding (openkis_FindingVisible()) counts with its category and
 * period only: its finds, title and link are left out (see
 * openkis_FindingsMapPlaces()).
 *
 * Besides the features the collection carries a "legend" member with the
 * translated labels of the categories and the icons of the types of finds.
 */
ob_start();
global $_FN;
require_once "loadfinis.php";
FN_LoadMessagesFolder("extra/openkis");
require_once "extra/openkis/openkis_sheet.inc.php";

$categories = openkis_FindingsOptions("category");
$types = openkis_FindingsTypes();
$places = openkis_FindingsMapPlaces();

$open = "onclick=\"try{window.open(this.href,'r','scrollbars=yes').focus();return false;}catch(e){return true;}\" target=\"_blank\"";
$features = array();
foreach ($places as $key => $place)
{
    $record = $place['record'];
    $url = FN_RewriteLink("index.php?mod={$place['mod']}&op=view&id={$record['id']}", "&", true);
    $placename = trim($record['comune'] . ($record['provincia'] != "" ? " ({$record['provincia']})" : ""));
    $description = "";
    if ($placename !== "")
    {
        $description .= "<small>" . htmlspecialchars($placename) . "</small><br />";
    }
    $description .= "<b>" . count($place['findings']) . " " . FN_Translate(count($place['findings']) == 1 ? "finding" : "findings", "aa") . "</b><br />";
    $description .= "<table class=\"ops-popup-table\">";
    $list = array();
    foreach ($place['findings'] as $finding)
    {
        $dot = "<span class=\"openkis-findings-dot\" data-category=\"" . htmlspecialchars($finding['category']) . "\"></span>";
        $meta = htmlspecialchars(openkis_SheetUcfirst(isset($categories[$finding['category']]) ? $categories[$finding['category']] : $finding['category']));
        if ($finding['period'] != "")
        {
            $meta .= " &middot; " . htmlspecialchars(FN_Translate($finding['period']));
        }
        if ($finding['visible'])
        {
            $icons = "";
            $finds = array_filter(explode(",", $finding['finding_type']), "strlen");
            foreach ($finds as $type)
            {
                if (isset($types[$type]))
                {
                    $icons .= "<span class=\"openkis-findings-icon\" title=\"" . htmlspecialchars($types[$type]['label']) . "\" aria-label=\"" . htmlspecialchars($types[$type]['label']) . "\">{$types[$type]['icon']}</span>";
                }
            }
            $name = "<a $open href=\"" . FN_RewriteLink("index.php?mod=findings&op=view&id={$finding['id']}", "&", true) . "\">" . htmlspecialchars($finding['name']) . "</a>";
            $name = trim("$icons $name");
            $list[] = array("category" => $finding['category'], "period" => $finding['period'], "types" => array_values($finds));
        }
        else
        {
            $name = "<span class=\"text-muted\">" . FN_Translate("restricted finding") . "</span>";
            $list[] = array("category" => $finding['category'], "period" => $finding['period'], "types" => array());
        }
        $description .= "<tr><td>$dot</td><td>$name<div class=\"ops-popup-meta\">$meta</div></td></tr>";
    }
    $description .= "</table>";
    $description .= "<div class=\"text-right\"><a $open href=\"$url\"><span class=\"glyphicon glyphicon-eye-open\"></span> <b>" . FN_Translate("open") . "</b></a></div>";
    $features[] = array(
        "type" => "Feature",
        "id" => $key,
        "geometry" => array("type" => "Point", "coordinates" => array(
                floatval(str_replace(",", ".", $record['longitude'])), floatval(str_replace(",", ".", $record['latitude'])))),
        "properties" => array(
            "name" => FN_FixEncoding(trim($record['code'] . " " . $record['name'])),
            "description" => FN_FixEncoding($description),
            "code" => FN_FixEncoding($record['code']),
            "kind" => $place['mod'],
            "categories" => $place['categories'],
            "types" => $place['types'],
            "periods" => $place['periods'],
            "findings" => $list,
            "nfindings" => count($place['findings']),
            "url" => $url));
}

$legend = array(
    "title" => FN_Translate("paleontological and archaeological findings"),
    "size" => FN_Translate("the size of a point is the number of findings, «mixed» also marks the caves with findings of different categories"),
    "categories" => $categories,
    "types_title" => FN_Translate("type of finds"),
    "types" => $types);
@ini_set("serialize_precision", -1);
$json = json_encode(array("type" => "FeatureCollection", "legend" => $legend, "features" => $features));
while (false !== @ob_end_clean());
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
echo $json;
