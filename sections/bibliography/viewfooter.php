<?php

/**
 * Bibliography sheet, footer. Runs before viewheader.php: echoed html goes
 * below the sheet at full width, as the list cards of the other sheets:
 * - the caves dealt with in the publication (fields codecaves, surveys,
 *   modified_caves, modified_surveys), with what the publication brings
 *   for each of them (survey, updated survey, updated data);
 * - the artificial cavities (codeartificials);
 * - the species (fauna), linked to their sheet when it exists;
 * - where the section speleopaleo exists, the caves with fossils;
 * - the temperature measurements taken from the publication (field
 *   bibliography_ids of the section temperatures).
 * Caves, cavities, species and measurements hidden to the user are left out.
 */
global $_FN;
require_once "extra/openkis/openkis_sheet.inc.php";
$GLOBALS['openkis_view_side'] = array();

/**
 * Codes listed in a field: separated by commas, typed with spaces inside ("LI 258"), "-" for none
 */
$openkis_codes = function ($value)
{
    $codes = array();
    foreach (preg_split('/[,|;\n]+/', (string) $value) as $code)
    {
        $code = strtoupper(preg_replace('/\s+/', "", $code));
        if ($code !== "" && $code !== "-")
            $codes[$code] = $code;
    }
    return array_values($codes);
};

//---- caves dealt with in the publication ------------------------------------->
$marks = array("surveys" => FN_Translate("survey"), "modified_surveys" => FN_Translate("updated survey"), "modified_caves" => FN_Translate("updated data"));
$codes = $openkis_codes(isset($row['codecaves']) ? $row['codecaves'] : "");
$notes = array();
foreach ($marks as $field => $mark)
{
    foreach ($openkis_codes(isset($row[$field]) ? $row[$field] : "") as $code)
    {
        $codes[] = $code;
        $notes[$code][] = $mark;
    }
}
$caves = openkis_SheetRecordsByCode("caves", $codes, "id,code,name,comune,latitude,longitude,elevation,elevation_map,elevation_gps,lenght_total,depth_total");
if (count($caves))
{
    $values = array();
    foreach ($caves as $cave)
    {
        $key = strtoupper($cave['code']);
        $values[$cave['code']] = isset($notes[$key]) ? htmlspecialchars(implode(", ", $notes[$key])) : "";
    }
    $extra = array();
    if (count($notes))
        $extra[] = array("title" => FN_Translate("in the publication"), "class" => "", "values" => $values);
    // the map selects the caves by their codes: no map when the url would be too long
    echo openkis_SheetCavesCard(FN_Translate("caves dealt with"), $caves, array("extra" => $extra, "map" => (count($caves) > 300) ? false : ""));
}
//---- caves dealt with in the publication -------------------------------------<
//---- artificial cavities ----------------------------------------------------->
$cavities = openkis_SheetRecordsByCode("artificials", $openkis_codes(isset($row['codeartificials']) ? $row['codeartificials'] : ""), "id,code,name,comune");
echo openkis_SheetCavesCard(FN_Translate("artificial cavities dealt with"), $cavities, array("mod" => "artificials", "columns" => array("comune"), "map" => false));
//---- artificial cavities -----------------------------------------------------<
//---- species: a list separated by commas (or "|"), an item may carry the author ->
$items = array();
foreach (preg_split('/[,|\n](?![^(]*\))/', isset($row['fauna']) ? (string) $row['fauna'] : "") as $item)
{
    $item = trim(preg_replace('/\s+/', " ", $item));
    if ($item !== "" && !isset($items[strtolower($item)]))
        $items[strtolower($item)] = $item;
}
if (count($items) && file_exists("sections/fauna"))
{
    $species = array();
    if (FN_UserCanViewSection("fauna"))
    {
        require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
        $config = FN_LoadConfig("modules/dbview/config.php", "fauna");
        $dbview = new FNDBVIEW($config);
        $species = $dbview->GetResults($config, array("fields" => "id,scientific_name,name,type"));
        $species = is_array($species) ? $species : array();
    }
    $rows = array();
    foreach ($items as $key => $item)
    {
        // the same name, else the longest name followed by the author, else a name containing the item
        $found = $prefix = $contains = false;
        foreach ($species as $sp)
        {
            $name = strtolower(trim($sp['scientific_name']));
            if ($name === "")
                continue;
            if ($name === $key)
            {
                $found = $sp;
                break;
            }
            if (strpos($key, "$name ") === 0 && ($prefix === false || strlen($name) > strlen($prefix['scientific_name'])))
                $prefix = $sp;
            elseif ($contains === false && strpos($name, $key) !== false)
                $contains = $sp;
        }
        if ($found === false)
            $found = ($prefix !== false) ? $prefix : $contains;
        if ($found !== false)
        {
            $url = FN_RewriteLink("index.php?mod=fauna&op=view&id={$found['id']}");
            $rows[] = array("<a href=\"$url\"><i>" . htmlspecialchars($found['scientific_name']) . "</i></a>", htmlspecialchars($found['name']),
                htmlspecialchars(ucfirst(strtolower($found['type']))), openkis_SheetViewButton($url));
        }
        else
        {
            $rows[] = array("<i>" . htmlspecialchars($item) . "</i>", "", "", "");
        }
    }
    $head = array(FN_Translate("species"), FN_Translate("common name"), FN_Translate("category"), array("", "text-right text-end"));
    echo openkis_SheetListCard(FN_Translate("species dealt with"), $head, $rows, array("id" => "card_species"));
}
//---- species -----------------------------------------------------------------<
//---- caves with fossils (section speleopaleo, only where it exists) ----------->
if (file_exists("sections/speleopaleo") && !empty($row['fossils']))
{
    $ids = array();
    foreach ($openkis_codes($row['fossils']) as $id)
        $ids[] = intval($id);
    $ids = array_filter(array_unique($ids));
    $rows = array();
    if (count($ids) && FN_UserCanViewSection("speleopaleo"))
    {
        $config = FN_LoadConfig("modules/dbview/config.php", "caves");
        $dbview = new FNDBVIEW($config);
        $fossil_caves = $dbview->GetResults($config, array("fields" => "id,code,name,synonyms", "appendquery" => "id IN (" . implode(",", $ids) . ")"));
        foreach (is_array($fossil_caves) ? $fossil_caves : array() as $cave)
        {
            $url = FN_RewriteLink("index.php?mod=speleopaleo&op=view&id={$cave['id']}");
            $rows[] = array(array(htmlspecialchars($cave['code']), "text-nowrap openkis-card-code"), "<a href=\"$url\">" . htmlspecialchars($cave['name']) . "</a>",
                htmlspecialchars($cave['synonyms']), openkis_SheetViewButton($url));
        }
    }
    if (count($rows))
    {
        $head = array(FN_Translate("number"), FN_Translate("name"), FN_Translate("synonyms"), array("", "text-right text-end"));
        echo openkis_SheetListCard(FN_Translate("caves with fossils"), $head, $rows, array("id" => "card_fossils"));
    }
}
//---- caves with fossils ------------------------------------------------------<
//---- temperature measurements taken from the publication -------------------->
if (function_exists("openkis_TemperaturesBibliographyCard") && !empty($row['id']))
{
    echo openkis_TemperaturesBibliographyCard($row['id']);
}
//---- temperature measurements ------------------------------------------------<
