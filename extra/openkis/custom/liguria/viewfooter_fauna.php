<?php

/*
 * Footer of a species record (section "fauna") of the Liguria register, included by
 * sections/fauna/viewfooter.php, with the list cards of the other sheets
 * (extra/openkis/openkis_sheet.inc.php):
 * - bibliography that lists the species in its "fauna" field;
 * - caves and artificial cavities where the species was recorded (ctl_faunacave, which
 *   here keeps the species in "scientific_name"), with a map, for the users who can view
 *   the section "fauna_surveys" and for the groups w_caves, r_caves.
 *   A survey restricted to some groups (groupview) counts only for those groups and for the
 *   editors of fauna_surveys.
 */
global $_FN;
$scientific_name = isset($row['scientific_name']) ? trim($row['scientific_name']) : "";
if ($scientific_name == "")
    return;
$name_lower = strtolower($scientific_name);
$name_sql = addslashes($scientific_name);
//-----BIBLIOGRAFIA-------------------------->
$query = "SELECT id,title,magazine,year,authors,fauna FROM ctl_bibliography WHERE fauna LIKE \"%$name_sql%\" OR fauna LIKE \"%" . addslashes($name_lower) . "%\" OR fauna LIKE \"%" . addslashes(ucfirst($name_lower)) . "%\" OR fauna LIKE \"%" . addslashes(strtoupper($scientific_name)) . "%\"";
$bibliografia = FN_XMETADBQuery($query);
$bib_items = array();
if (is_array($bibliografia))
{
    foreach ($bibliografia as $bib)
    {
        // "fauna" is a comma separated list: match a whole item, also when the item carries the author
        foreach (explode(",", $bib['fauna']) as $item)
        {
            $item = strtolower(trim($item));
            if ($item == $name_lower || strpos($item, "$name_lower ") === 0)
            {
                $bib_items[$bib['id']] = $bib;
                break;
            }
        }
    }
}
if (count($bib_items) > 0)
{
    echo openkis_SheetBibliographyCard(FN_ArraySortByKey($bib_items, "year"));
}
//-----BIBLIOGRAFIA--------------------------<
//-----GROTTE ASSOCIATE----------------------->
if (FN_UserCanViewSection("fauna_surveys") || ($_FN['user'] != "" && FN_UserInGroup($_FN['user'], "w_caves,r_caves")))
{
    $surveys_config = FN_LoadConfig("modules/dbview/config.php", "fauna_surveys");
    $surveys_editors = $surveys_config['groupinsert'] . "," . $surveys_config['groupadmin'];
    $records = FN_XMETADBQuery("SELECT codecave,codeartificial,groupview FROM ctl_faunacave WHERE scientific_name LIKE \"$name_sql\"");
    $count_caves = array();
    $count_artificials = array();
    if (is_array($records))
    {
        foreach ($records as $r)
        {
            if ($r['groupview'] != "" && !FN_IsAdmin() && !FN_UserInGroup($_FN['user'], $r['groupview'] . "," . $surveys_editors))
                continue;
            if ($r['codecave'] != "")
                $count_caves[$r['codecave']] = isset($count_caves[$r['codecave']]) ? $count_caves[$r['codecave']] + 1 : 1;
            elseif ($r['codeartificial'] != "")
                $count_artificials[$r['codeartificial']] = isset($count_artificials[$r['codeartificial']]) ? $count_artificials[$r['codeartificial']] + 1 : 1;
        }
    }

    /**
     * Records of a dbview section with the given codes (section visibility rules apply)
     */
    $get_items = function ($section, $codes, $fields)
    {
        if (count($codes) == 0)
            return array();
        $config = FN_LoadConfig("modules/dbview/config.php", $section);
        $dbview = new FNDBVIEW($config);
        $in = array();
        foreach (array_keys($codes) as $code)
            $in[] = "'" . addslashes($code) . "'";
        $items = $dbview->GetResults(false, array("fields" => $fields, "appendquery" => "code IN (" . implode(",", $in) . ")"));
        return is_array($items) ? $items : array();
    };
    // number of surveys of each cave, column after the cave data
    $surveys_column = function ($counts)
    {
        return array("title" => FN_Translate("sightings"), "class" => "text-right text-end", "values" => $counts);
    };

    $caves = $get_items("caves", $count_caves, "id,code,name,comune,latitude,longitude,elevation,elevation_map,elevation_gps,depth_total,lenght_total");
    // the map shows the fauna points of the species (openkis_fauna.php) over the icons of its caves
    $map = "filter_code=" . implode(",", array_map("rawurlencode", array_column($caves, "code"))) . "&fauna_species=" . rawurlencode($scientific_name);
    echo openkis_SheetCavesCard(FN_Translate("caves where it was observed"), $caves, array("map" => $map, "extra" => array($surveys_column($count_caves))));
    $artificials = $get_items("artificials", $count_artificials, "id,code,name");
    echo openkis_SheetCavesCard(FN_Translate("artificial cavities where it was observed"), $artificials,
        array("mod" => "artificials", "columns" => array(), "map" => false, "extra" => array($surveys_column($count_artificials))));
}
//-----GROTTE ASSOCIATE-----------------------<
