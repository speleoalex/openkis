<?php

/**
 * Fauna sightings (section fauna_surveys) on the cave sheet and on the species sheet
 * (section fauna). Shown only to the users who can view the fauna_surveys section; the
 * sightings and the caves restricted with groupview are left out by FNDBVIEW.
 */
require_once __DIR__ . "/openkis_sheet.inc.php";

/**
 * Field of the sightings table with the scientific name of the species: "name" in
 * the table shipped with OpenKIS, "scientific_name" in older registers (Liguria).
 *
 * @return string field name, empty when the section has no sightings table
 */
function openkis_FaunaSpeciesField()
{
    static $field = null;
    if ($field !== null)
        return $field;
    $field = "";
    if (!file_exists("sections/fauna_surveys"))
        return $field;
    $config = FN_LoadConfig("modules/dbview/config.php", "fauna_surveys");
    // the section must list the sightings table (it lists fn_files when never configured)
    $table = !empty($config['tables']) ? FN_XMDBTable($config['tables']) : false;
    if (!$table || !isset($table->fields['codecave']))
        return $field;
    if (isset($table->fields['scientific_name']))
        $field = "scientific_name";
    elseif (isset($table->fields['name']))
        $field = "name";
    return $field;
}

/**
 * Sightings visible to the user that match an SQL condition.
 *
 * @param string $where condition on the sightings table (codecave, species field, ...)
 * @return array id, name (the species, whatever the field of the table), codecave,
 *               ammount, date, caver of each sighting
 */
function openkis_FaunaSightings($where)
{
    global $_FN;
    $species = openkis_FaunaSpeciesField();
    if ($species === "" || !FN_UserCanViewSection("fauna_surveys"))
        return array();
    $config = FN_LoadConfig("modules/dbview/config.php", "fauna_surveys");
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    $dbview = new FNDBVIEW($config);
    $params = array();
    $params['fields'] = "id,$species,codecave,ammount,date,caver";
    // the artificial cavity of the sighting, when the table has the column
    $table = !empty($config['tables']) ? FN_XMDBTable($config['tables']) : false;
    $has_artificial = $table && isset($table->fields['codeartificial']);
    if ($has_artificial)
        $params['fields'] .= ",codeartificial";
    $params['appendquery'] = $where;
    $items = $dbview->GetResults($config, $params);
    if (!is_array($items))
        return array();
    foreach ($items as $k => $item)
    {
        $items[$k]['name'] = trim((string) $item[$species]);
        $items[$k]['codecave'] = trim((string) $item['codecave']);
        $items[$k]['codeartificial'] = $has_artificial ? trim((string) $item['codeartificial']) : "";
    }
    return $items;
}

/**
 * Ecological categories of the species for the fauna map, from the most to
 * the least bound to the underground: key => label (translated).
 *
 * @return array
 */
function openkis_FaunaCategories()
{
    return array(
        "obligate" => FN_Translate("troglobites and stygobites"),
        "eutroglophile" => FN_Translate("eutroglophiles"),
        "troglophile" => FN_Translate("troglophiles and subtroglophiles"),
        "trogloxene" => FN_Translate("trogloxenes"),
        "unknown" => FN_Translate("not classified"));
}

/**
 * Category key of a species "type" (TROGLOBIO, SUBTROGLOFILO, STIGOBIO...
 * stored in Italian), see openkis_FaunaCategories().
 *
 * @param string $type
 * @return string
 */
function openkis_FaunaCategory($type)
{
    $type = strtoupper(trim((string) $type));
    if ($type === "")
        return "unknown";
    if (preg_match('/^(TROGLOB|STIGOB|STYGOB)/', $type))
        return "obligate";
    if (preg_match('/^(EUTROGLOF|EUSTIGOF|EUSTYGOF)/', $type))
        return "eutroglophile";
    if (preg_match('/^(SUBTROGLOF|TROGLOF|STIGOF|STYGOF)/', $type))
        return "troglophile";
    if (preg_match('/^(TROGLOSS|TROGLOX|STIGOSS|STYGOSS|STIGOX|STYGOX)/', $type))
        return "trogloxene";
    return "unknown";
}

/**
 * Taxonomic classes of the species for the fauna map: key => label (translated).
 *
 * @return array
 */
function openkis_FaunaClasses()
{
    return array(
        "mammalia" => FN_Translate("mammals"),
        "amphibia" => FN_Translate("amphibians"),
        "reptilia" => FN_Translate("reptiles"),
        "insecta" => FN_Translate("insects"),
        "arachnida" => FN_Translate("arachnids"),
        "crustacea" => FN_Translate("crustaceans"),
        "gastropoda" => FN_Translate("gastropods"),
        "other" => FN_Translate("other classes"),
        "unknown" => FN_Translate("not classified"));
}

/**
 * Class key of a species "class" (free text: MAMMALIA, INSECTA, MALACOSTRACA...),
 * see openkis_FaunaClasses().
 *
 * @param string $class
 * @return string
 */
function openkis_FaunaClassKey($class)
{
    $class = strtoupper(trim((string) $class));
    if ($class === "")
        return "unknown";
    $keys = array("MAMMAL" => "mammalia", "AMPHIB" => "amphibia", "ANFIB" => "amphibia", "REPTIL" => "reptilia", "RETTIL" => "reptilia",
        "INSECT" => "insecta", "INSETT" => "insecta", "ARACHN" => "arachnida", "ARACN" => "arachnida", "MALACOSTRAC" => "crustacea",
        "CRUSTAC" => "crustacea", "CROSTAC" => "crustacea", "COPEPOD" => "crustacea", "OSTRACOD" => "crustacea", "GASTROPOD" => "gastropoda", "GASTEROPOD" => "gastropoda");
    foreach ($keys as $prefix => $key)
        if (strpos($class, $prefix) === 0)
            return $key;
    return "other";
}

/**
 * Caves and artificial cavities with fauna sightings visible to the user, for
 * the fauna map (openkis_fauna.php): one entry per place with coordinates,
 * with its species grouped by category and by class.
 *
 * @param string $where condition on the sightings table ("" = all)
 * @param array $filter optional "category" and "class" keys: only the places
 *                      with at least one species of that category or class
 * @return array places: mod, record (id, code, name, comune, provincia,
 *               latitude, longitude), species (key => name, common, type,
 *               category, class, classkey, id of the species sheet, sightings,
 *               last date), categories and classes (key => number of species),
 *               category and classkey (the prevailing ones), nsightings
 */
function openkis_FaunaMapPlaces($where = "", $filter = array())
{
    $sightings = openkis_FaunaSightings($where !== "" ? $where : "1");
    if (!count($sightings))
        return array();
    $species = openkis_FaunaRecordsByField("fauna", "scientific_name", array_column($sightings, "name"), "id,scientific_name,name,type,class");
    $fields = "id,code,name,comune,provincia,latitude,longitude";
    $places = array(
        "caves" => openkis_FaunaRecordsByField("caves", "code", array_column($sightings, "codecave"), $fields),
        "artificials" => openkis_FaunaRecordsByField("artificials", "code", array_column($sightings, "codeartificial"), $fields));
    $order = array_keys(openkis_FaunaCategories());
    $result = array();
    foreach ($sightings as $sighting)
    {
        foreach (array("caves" => "codecave", "artificials" => "codeartificial") as $mod => $field)
        {
            $code = $sighting[$field];
            if ($code === "" || !isset($places[$mod][$code]))
                continue;
            $place = $places[$mod][$code];
            if (empty($place['latitude']) || empty($place['longitude']))
                continue;
            $key = "$mod:$code";
            if (!isset($result[$key]))
            {
                $result[$key] = array("mod" => $mod, "record" => $place, "species" => array(), "categories" => array(), "classes" => array(),
                    "category" => "unknown", "classkey" => "unknown", "nsightings" => 0);
            }
            $skey = openkis_FaunaKey($sighting['name']);
            if ($skey === "")
                continue;
            if (!isset($result[$key]['species'][$skey]))
            {
                $sheet = isset($species[$skey]) ? $species[$skey] : array("id" => 0, "scientific_name" => $sighting['name'], "name" => "", "type" => "", "class" => "");
                $result[$key]['species'][$skey] = array(
                    "name" => $sheet['scientific_name'] !== "" ? $sheet['scientific_name'] : $sighting['name'],
                    "common" => (string) $sheet['name'],
                    "type" => (string) $sheet['type'],
                    "category" => openkis_FaunaCategory($sheet['type']),
                    "class" => (string) $sheet['class'],
                    "classkey" => openkis_FaunaClassKey($sheet['class']),
                    "id" => intval($sheet['id']),
                    "sightings" => 0,
                    "last" => "");
            }
            $result[$key]['species'][$skey]['sightings']++;
            $result[$key]['nsightings']++;
            $date = !empty($sighting['date']) && strtotime($sighting['date']) ? date("Y-m-d", strtotime($sighting['date'])) : "";
            if ($date > $result[$key]['species'][$skey]['last'])
                $result[$key]['species'][$skey]['last'] = $date;
        }
    }
    foreach ($result as $key => $place)
    {
        if (!count($place['species']))
        {
            unset($result[$key]);
            continue;
        }
        uasort($place['species'], function ($a, $b) use ($order)
        {
            $d = array_search($a['category'], $order) - array_search($b['category'], $order);
            return $d != 0 ? $d : strcasecmp($a['name'], $b['name']);
        });
        $categories = array();
        $classes = array();
        foreach ($place['species'] as $sp)
        {
            $categories[$sp['category']] = isset($categories[$sp['category']]) ? $categories[$sp['category']] + 1 : 1;
            $classes[$sp['classkey']] = isset($classes[$sp['classkey']]) ? $classes[$sp['classkey']] + 1 : 1;
        }
        if (!empty($filter['category']) && !isset($categories[$filter['category']]))
        {
            unset($result[$key]);
            continue;
        }
        if (!empty($filter['class']) && !isset($classes[$filter['class']]))
        {
            unset($result[$key]);
            continue;
        }
        // the prevailing category is the one most bound to the underground
        $first = reset($place['species']);
        $place['category'] = $first['category'];
        // the prevailing class is the most frequent one, the known ones first
        arsort($classes);
        $place['classkey'] = key($classes);
        if ($place['classkey'] === "unknown" && count($classes) > 1)
        {
            $known = array_diff_key($classes, array("unknown" => 1));
            $place['classkey'] = key($known);
        }
        $place['categories'] = $categories;
        $place['classes'] = $classes;
        $result[$key] = $place;
    }
    return $result;
}

/**
 * Key of a species name for the lookups: the names in the sightings do not
 * always match the species sheet in case and spaces.
 *
 * @param string $name
 * @return string
 */
function openkis_FaunaKey($name)
{
    return strtolower(trim(preg_replace('/\s+/', " ", (string) $name)));
}

/**
 * Records of a dbview section whose field matches one of the values, visible to the user.
 *
 * @param string $mod section (fauna, caves)
 * @param string $field field compared with the values
 * @param array $values
 * @param string $fields fields to read
 * @return array records indexed by the field value (by openkis_FaunaKey() for scientific_name)
 */
function openkis_FaunaRecordsByField($mod, $field, $values, $fields)
{
    global $_FN;
    $values = array_unique(array_filter($values, "strlen"));
    if (!count($values) || !file_exists("sections/$mod") || !FN_UserCanViewSection($mod))
        return array();
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    $config = FN_LoadConfig("modules/dbview/config.php", $mod);
    $dbview = new FNDBVIEW($config);
    $or = array();
    foreach ($values as $value)
        $or[] = "$field LIKE '" . addslashes($value) . "'";
    $params = array();
    $params['fields'] = $fields;
    $params['appendquery'] = "(" . implode(" OR ", $or) . ")";
    $records = array();
    $items = $dbview->GetResults($config, $params);
    foreach (is_array($items) ? $items : array() as $item)
        $records[($field == "scientific_name") ? openkis_FaunaKey($item[$field]) : $item[$field]] = $item;
    return $records;
}

/**
 * Date of a sighting as dd/mm/yyyy, empty when missing.
 */
function openkis_FaunaDate($date)
{
    if (empty($date) || !strtotime($date))
        return "";
    return date("d/m/Y", strtotime($date));
}

/**
 * Card listing the species observed in a cave, for the cave sheet and the
 * sheet of a sighting (where the sighting itself is left out).
 *
 * @param string $code cave code
 * @param int $exclude_id sighting not to list
 * @param string $title title of the card, default "cave fauna"
 * @return string html, empty when the cave has no sighting visible to the user
 */
function openkis_FaunaCaveCard($code, $exclude_id = 0, $title = "")
{
    if ($code === "")
        return "";
    $sightings = openkis_FaunaSightings("codecave LIKE '" . addslashes($code) . "'");
    $sightings = array_values(array_filter($sightings, function ($sighting) use ($exclude_id)
    {
        return $exclude_id == 0 || $sighting['id'] != $exclude_id;
    }));
    if (!count($sightings))
        return "";
    $sightings = FN_ArraySortByKey($sightings, "name");
    $species = openkis_FaunaRecordsByField("fauna", "scientific_name", array_column($sightings, "name"), "id,scientific_name,name,type");
    $rows = array();
    foreach ($sightings as $sighting)
    {
        $url = FN_RewriteLink("index.php?mod=fauna_surveys&op=view&id={$sighting['id']}");
        $name = "<i>" . htmlspecialchars($sighting['name']) . "</i>";
        $type = "";
        $key = openkis_FaunaKey($sighting['name']);
        if (isset($species[$key]))
        {
            $sp = $species[$key];
            $name = "<a href=\"" . FN_RewriteLink("index.php?mod=fauna&op=view&id={$sp['id']}") . "\">$name</a>";
            if ($sp['name'] != "")
                $name .= "<br /><small>" . htmlspecialchars($sp['name']) . "</small>";
            // the categories are stored in Italian (TROGLOBIO, STIGOFILO, ...)
            $type = htmlspecialchars(ucfirst(strtolower($sp['type'])));
        }
        $rows[] = array($name, $type, htmlspecialchars($sighting['ammount']), openkis_FaunaDate($sighting['date']), htmlspecialchars($sighting['caver']), openkis_SheetViewButton($url));
    }
    $head = array(FN_Translate("species"), FN_Translate("category"), FN_Translate("specimens"), array(FN_Translate("date"), "text-nowrap"), FN_Translate("observer"), array("", "text-right text-end"));
    // on phones the card of a row starts with the species
    return openkis_SheetListCard($title != "" ? $title : FN_Translate("cave fauna"), $head, $rows, array("id" => "card_cave_fauna", "stack_title" => array(0)));
}

/**
 * Card listing the caves where a species was observed, for the species sheet
 * and the sheet of a sighting (where the sighting itself is left out).
 * Sightings in caves the user may not see are left out.
 *
 * @param string $scientific_name
 * @param int $exclude_id sighting not to list
 * @param string $title title of the card, default "observed in"
 * @return string html, empty when there is no sighting visible to the user
 */
function openkis_FaunaSpeciesCard($scientific_name, $exclude_id = 0, $title = "")
{
    $scientific_name = trim((string) $scientific_name);
    $field = openkis_FaunaSpeciesField();
    if ($scientific_name === "" || $field === "")
        return "";
    $sightings = openkis_FaunaSightings("$field LIKE '" . addslashes($scientific_name) . "'");
    $caves = openkis_FaunaRecordsByField("caves", "code", array_column($sightings, "codecave"), "id,code,name,comune,provincia,latitude,longitude");
    $rows = array();
    foreach ($sightings as $sighting)
    {
        if (!isset($caves[$sighting['codecave']]) || ($exclude_id != 0 && $sighting['id'] == $exclude_id))
            continue;
        $rows[] = array("cave" => $caves[$sighting['codecave']], "sighting" => $sighting);
    }
    if (!count($rows))
        return "";
    usort($rows, function ($a, $b)
    {
        return strnatcmp($a['cave']['code'], $b['cave']['code']);
    });
    $cells = array();
    $listed = array();
    foreach ($rows as $r)
    {
        $cave = $r['cave'];
        $sighting = $r['sighting'];
        $listed[$cave['code']] = $cave;
        $cave_url = FN_RewriteLink("index.php?mod=caves&op=view&id={$cave['id']}");
        $url = FN_RewriteLink("index.php?mod=fauna_surveys&op=view&id={$sighting['id']}");
        $place = trim($cave['comune'] . ($cave['provincia'] != "" ? " ({$cave['provincia']})" : ""));
        $cells[] = array("<a href=\"$cave_url\">" . htmlspecialchars($cave['code']) . "</a>",
            "<a href=\"$cave_url\">" . htmlspecialchars($cave['name']) . "</a><br /><small>" . htmlspecialchars($place) . "</small>",
            htmlspecialchars($sighting['ammount']), openkis_FaunaDate($sighting['date']), htmlspecialchars($sighting['caver']), openkis_SheetViewButton($url));
    }
    $head = array(array(FN_Translate("number"), "text-nowrap openkis-card-code"), FN_Translate("cave"), FN_Translate("specimens"), array(FN_Translate("date"), "text-nowrap"), FN_Translate("observer"), array("", "text-right text-end"));
    // the map shows the fauna points of the species (openkis_fauna.php) over the icons of its caves
    $filter = "filter_code=" . implode(",", array_map("rawurlencode", array_keys($listed))) . "&fauna_species=" . rawurlencode($scientific_name);
    $options = array_merge(array("id" => "card_observed_in"), openkis_SheetMapOptions(openkis_SheetCavesMapUrl($listed, $filter)));
    return openkis_SheetListCard($title != "" ? $title : FN_Translate("observed in"), $head, $cells, $options);
}
