<?php
/**
 * Caves and artificial cavities with fauna sightings as GeoJSON for the map
 * (OPS_Map.addFaunaLayer): one Point per place, with its species grouped by
 * ecological category (troglobites..., field "type" of the species sheet) and
 * by taxonomic class (field "class") and by icon group (openkis_FaunaGroups(),
 * from order, class and phylum). Only the sightings, species, caves and
 * cavities the current user can see are returned, nothing at all when the
 * user may not view the section fauna_surveys (see openkis_FaunaMapPlaces()).
 *
 * Parameters (all optional):
 *   cave      only this cave (code)
 *   species   only the places where this species (scientific name) was observed,
 *             also under the names no longer valid grouped under it (synonyms;
 *             for "Genus sp." the ambiguous names of the genus)
 *   category  only the places with a species of this category (key of
 *             openkis_FaunaCategories())
 *   class     only the places with a species of this class (key of
 *             openkis_FaunaClasses())
 *
 * Besides the features the collection carries a "legend" member with the
 * translated labels of the categories, of the classes and the icons of the groups.
 */
ob_start();
global $_FN;
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
FN_LoadMessagesFolder("extra/openkis");
require_once "extra/openkis/openkis_fauna.inc.php";

$cave = FN_GetParam("cave", $_GET, "flat");
$species_name = FN_GetParam("species", $_GET, "flat");
$category = FN_GetParam("category", $_GET, "flat");
$class = FN_GetParam("class", $_GET, "flat");

$categories = openkis_FaunaCategories();
$classes = openkis_FaunaClasses();
$groups = openkis_FaunaGroups();
$places = array();
if (file_exists("sections/fauna_surveys") && FN_UserCanViewSection("fauna_surveys"))
{
    $field = openkis_FaunaSpeciesField();
    $where = array();
    if ($cave !== "")
    {
        $where[] = "codecave LIKE '" . addslashes($cave) . "'";
    }
    if ($species_name !== "" && $field !== "")
    {
        // also the names grouped under this one (synonyms, ambiguous names of a genus)
        $where[] = openkis_FaunaNamesWhere($field, openkis_FaunaNamesOf($species_name));
    }
    $filter = array(
        "category" => isset($categories[$category]) ? $category : "",
        "class" => isset($classes[$class]) ? $class : "");
    $places = openkis_FaunaMapPlaces(implode(" AND ", $where), $filter);
}

$open = "onclick=\"try{window.open(this.href,'r','scrollbars=yes').focus();return false;}catch(e){return true;}\" target=\"_blank\"";
$features = array();
foreach ($places as $key => $place)
{
    $record = $place['record'];
    $label = trim($record['code'] . " " . $record['name']);
    $url = FN_RewriteLink("index.php?mod={$place['mod']}&op=view&id={$record['id']}", "&", true);
    $list = FN_RewriteLink("index.php?mod=fauna_surveys&spfield_" . ($place['mod'] == "artificials" ? "codeartificial" : "codecave") . "=" . urlencode($record['code']), "&", true);
    $placename = trim($record['comune'] . ($record['provincia'] != "" ? " ({$record['provincia']})" : ""));
    $description = "";
    if ($placename !== "")
    {
        $description .= "<small>" . htmlspecialchars($placename) . "</small><br />";
    }
    $description .= "<b>" . count($place['species']) . " " . FN_Translate("species", "aa") . "</b>, " . $place['nsightings'] . " " . FN_Translate("sightings", "aa") . "<br />";
    $description .= "<table class=\"ops-popup-table\">";
    foreach ($place['species'] as $sp)
    {
        $name = "<i>" . htmlspecialchars($sp['name']) . "</i>";
        if ($sp['id'] > 0)
        {
            $name = "<a $open href=\"" . FN_RewriteLink("index.php?mod=fauna&op=view&id={$sp['id']}", "&", true) . "\">$name</a>";
        }
        if ($sp['common'] !== "")
        {
            $name .= " <small>" . htmlspecialchars($sp['common']) . "</small>";
        }
        // names no longer valid grouped under this one (openkis_FaunaResolve())
        if (!empty($sp['cited']))
        {
            $cited = array();
            foreach ($sp['cited'] as $c)
            {
                $cited[] = "<i>" . htmlspecialchars($c) . "</i>";
            }
            $name .= "<br /><small class=\"text-muted\">" . FN_Translate("cited as", "aa") . " " . implode(", ", $cited) . "</small>";
        }
        $group = $groups[$sp['group']];
        $name = "<span class=\"openkis-fauna-icon\" title=\"" . htmlspecialchars($group['label']) . "\" aria-label=\"" . htmlspecialchars($group['label']) . "\">{$group['icon']}</span> $name";
        $dot = "<span class=\"openkis-fauna-dot\" data-category=\"" . htmlspecialchars($sp['category']) . "\" data-class=\"" . htmlspecialchars($sp['classkey']) . "\"></span>";
        $meta = htmlspecialchars($categories[$sp['category']]);
        if ($sp['last'] !== "")
        {
            $meta .= " &middot; " . date("d/m/Y", strtotime($sp['last']));
        }
        // category and date under the name: four columns did not fit the popup
        $description .= "<tr><td>$dot</td><td>$name<div class=\"ops-popup-meta\">$meta</div></td></tr>";
    }
    $description .= "</table>";
    $description .= "<div class=\"text-right\"><a $open href=\"$list\"><span class=\"glyphicon glyphicon-list\"></span> " . FN_Translate("fauna surveys") . "</a> ";
    $description .= "<a $open href=\"$url\"><span class=\"glyphicon glyphicon-eye-open\"></span> <b>" . FN_Translate("open") . "</b></a></div>";
    $species_list = array();
    foreach ($place['species'] as $sp)
    {
        $species_list[] = array("name" => FN_FixEncoding($sp['name']), "category" => $sp['category'], "class" => $sp['classkey'], "group" => $sp['group'], "sightings" => $sp['sightings']);
    }
    $features[] = array(
        "type" => "Feature",
        "id" => $key,
        "geometry" => array("type" => "Point", "coordinates" => array(
                floatval(str_replace(",", ".", $record['longitude'])), floatval(str_replace(",", ".", $record['latitude'])))),
        "properties" => array(
            "name" => FN_FixEncoding($label),
            "description" => FN_FixEncoding($description),
            "code" => FN_FixEncoding($record['code']),
            "kind" => $place['mod'],
            "category" => $place['category'],
            "class" => $place['classkey'],
            "categories" => $place['categories'],
            "classes" => $place['classes'],
            "groups" => $place['groups'],
            "species" => $species_list,
            "nspecies" => count($place['species']),
            "nsightings" => $place['nsightings'],
            "url" => $url));
}

$legend = array(
    "title" => FN_Translate("biospeleology"),
    "mode_category" => FN_Translate("by ecological category"),
    "mode_class" => FN_Translate("by class"),
    "places" => FN_Translate("caves"),
    "size" => FN_Translate("the size of a point is the number of species"),
    "categories" => $categories,
    "classes" => $classes,
    "groups_title" => FN_Translate("organisms"),
    "groups" => $groups);
@ini_set("serialize_precision", -1);
$json = json_encode(array("type" => "FeatureCollection", "legend" => $legend, "features" => $features));
while (false !== @ob_end_clean());
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
echo $json;
