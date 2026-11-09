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
 * Taxonomic statuses of a species name (field taxon_status of ctl_fauna):
 * key => translated label. An empty status means "not checked" and counts as valid.
 *
 * @return array
 */
function openkis_FaunaStatuses()
{
    return array(
        "valid" => FN_Translate("valid name", "aa"),
        "synonym" => FN_Translate("synonym", "aa"),
        "combination" => FN_Translate("historical combination", "aa"),
        "ambiguous" => FN_Translate("ambiguous synonym", "aa"),
        "dubium" => "nomen dubium");
}

/**
 * Whether the species table has the name status fields (taxon_status, accepted_name):
 * installs whose definition was not updated work as before.
 *
 * @return bool
 */
function openkis_FaunaHasStatus()
{
    return openkis_FaunaHasField("taxon_status") && openkis_FaunaHasField("accepted_name");
}

/**
 * Fields to read from the species sheets: the given ones plus genus and the
 * name status fields when the table has them (see openkis_FaunaResolve()).
 *
 * @param string $fields comma separated
 * @return string
 */
function openkis_FaunaSpeciesFields($fields)
{
    $list = explode(",", $fields);
    $extra = openkis_FaunaHasStatus() ? array("genus", "taxon_status", "accepted_name") : array();
    // phylum and order choose the icon of the species (openkis_FaunaGroupKey())
    foreach (array("phylum", "order") as $f)
        if (openkis_FaunaHasField($f))
            $extra[] = $f;
    return implode(",", array_unique(array_merge($list, $extra)));
}

/**
 * Whether the installed definition of the species table has a field.
 *
 * @param string $field
 * @return bool
 */
function openkis_FaunaHasField($field)
{
    static $fields = null;
    if ($fields === null)
    {
        $fields = array();
        if (file_exists("sections/fauna"))
        {
            $config = FN_LoadConfig("modules/dbview/config.php", "fauna");
            $table = !empty($config['tables']) ? FN_XMDBTable($config['tables']) : false;
            if ($table)
                $fields = $table->fields;
        }
    }
    return isset($fields[$field]);
}

/**
 * Groups of organisms shown as icons on the fauna map and in its popup, in
 * legend order: key => icon (emoji), fallback (character drawn when the
 * browser has no glyph for the emoji), label (translated).
 *
 * @return array
 */
function openkis_FaunaGroups()
{
    $groups = array(
        "bats" => array("🦇", "", "bats"),
        "mammals" => array("🐭", "", "other mammals"),
        "birds" => array("🐦", "", "birds"),
        "reptiles" => array("🦎", "", "reptiles"),
        "amphibians" => array("🐸", "", "amphibians"),
        "fish" => array("🐟", "", "fish"),
        "beetles" => array("🐞", "", "beetles"),
        "flies" => array("🦟", "🐜", "flies and mosquitoes"),
        "moths" => array("🦋", "", "moths and caddisflies"),
        "crickets" => array("🦗", "🐜", "crickets and grasshoppers"),
        "insects" => array("🐜", "", "other insects and springtails"),
        "arachnids" => array("🕷️", "", "spiders, harvestmen, mites and other arachnids"),
        "scorpions" => array("🦂", "", "scorpions and pseudoscorpions"),
        "crustaceans" => array("🦐", "", "crustaceans"),
        "myriapods" => array("🐛", "", "millipedes and centipedes"),
        "worms" => array("🪱", "〰", "worms"),
        "snails" => array("🐌", "", "snails and molluscs"),
        "fungi" => array("🍄", "", "fungi"),
        "other" => array("🔬", "", "other organisms"),
        "unknown" => array("❓", "?", "not classified"));
    $result = array();
    foreach ($groups as $key => $g)
        $result[$key] = array("icon" => $g[0], "fallback" => $g[1], "label" => FN_Translate($g[2]));
    return $result;
}

/**
 * Species sheet completed with the phylum, class and order of its genus when
 * it has none of them (sheet left empty, or no sheet for the name of the
 * sighting): they come from another sheet of the same genus, e.g. "Roncus
 * antrorum" takes Arachnida, Pseudoscorpiones from "Roncus lubricus".
 *
 * @param array $sheet species sheet (phylum, class, order may be missing)
 * @param string $name scientific name, its first word is the genus
 * @return array
 */
function openkis_FaunaTaxonomyFromGenus($sheet, $name)
{
    foreach (array("phylum", "class", "order") as $field)
        if (isset($sheet[$field]) && trim((string) $sheet[$field]) !== "")
            return $sheet;
    $genera = openkis_FaunaGenusTaxonomy();
    $genus = openkis_FaunaKey(strtok(trim((string) $name), " "));
    return isset($genera[$genus]) ? array_merge($sheet, $genera[$genus]) : $sheet;
}

/**
 * Phylum, class and order of each genus, from the first visible species sheet
 * of the genus that has at least one of them; read once per request.
 *
 * @return array genus (openkis_FaunaKey()) => array(phylum, class, order)
 */
function openkis_FaunaGenusTaxonomy()
{
    global $_FN;
    static $genera = null;
    if ($genera !== null)
        return $genera;
    $genera = array();
    if (!file_exists("sections/fauna") || !FN_UserCanViewSection("fauna"))
        return $genera;
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    $config = FN_LoadConfig("modules/dbview/config.php", "fauna");
    $dbview = new FNDBVIEW($config);
    $items = $dbview->GetResults($config, array("fields" => openkis_FaunaSpeciesFields("id,scientific_name,class")));
    foreach (is_array($items) ? $items : array() as $item)
    {
        $taxonomy = array();
        foreach (array("phylum", "class", "order") as $field)
            if (isset($item[$field]) && trim((string) $item[$field]) !== "")
                $taxonomy[$field] = trim((string) $item[$field]);
        $genus = openkis_FaunaKey(strtok(trim((string) $item['scientific_name']), " "));
        if (count($taxonomy) && $genus !== "" && !isset($genera[$genus]))
            $genera[$genus] = $taxonomy;
    }
    return $genera;
}

/**
 * Icon group of a species sheet (openkis_FaunaGroups()) from its order, class,
 * phylum and type, the first that is known: the order tells bats from the other
 * mammals and beetles, flies, moths... from the other insects; the phylum covers
 * the sheets without a class (worms, molluscs, fungi...).
 *
 * @param array $sheet species sheet (type, phylum, class, order)
 * @return string
 */
function openkis_FaunaGroupKey($sheet)
{
    $value = function ($name) use ($sheet)
    {
        return isset($sheet[$name]) ? strtoupper(trim((string) $sheet[$name])) : "";
    };
    $type = $value("type");
    $phylum = $value("phylum");
    $class = $value("class");
    $order = $value("order");
    if (strpos($type, "FUNG") === 0 || strpos($phylum, "FUNG") === 0 || preg_match('/MYCETES$/', $class))
        return "fungi";
    $orders = array("CHIROPT" => "bats", "COLEOPT" => "beetles", "DIPTER" => "flies", "LEPIDOPT" => "moths", "TRICHOPT" => "moths",
        "ORTHOPT" => "crickets", "SCORPION" => "scorpions", "PSEUDOSCORP" => "scorpions",
        "CRUSTAC" => "crustaceans", "MYRIAPOD" => "myriapods", "GORDI" => "worms");
    $classes = array("MAMMAL" => "mammals", "AVES" => "birds", "UCCELL" => "birds", "REPTIL" => "reptiles", "RETTIL" => "reptiles",
        "AMPHIB" => "amphibians", "ANFIB" => "amphibians", "ACTINOPTER" => "fish", "OSTEICHT" => "fish", "CHONDRICH" => "fish", "PISC" => "fish",
        "INSECT" => "insects", "INSETT" => "insects", "COLLEMBOL" => "insects", "DIPLUR" => "insects", "PROTUR" => "insects",
        "ARACHN" => "arachnids", "ARACN" => "arachnids", "MALACOSTRAC" => "crustaceans", "CRUSTAC" => "crustaceans", "CROSTAC" => "crustaceans",
        "COPEPOD" => "crustaceans", "OSTRACOD" => "crustaceans", "MAXILLOPOD" => "crustaceans", "BRANCHIOPOD" => "crustaceans",
        "DIPLOPOD" => "myriapods", "CHILOPOD" => "myriapods", "SYMPHYL" => "myriapods", "PAUROPOD" => "myriapods",
        "CLITELL" => "worms", "OLIGOCHAET" => "worms", "HIRUDIN" => "worms", "POLYCHAET" => "worms", "TURBELL" => "worms", "NEMATOD" => "worms",
        "GASTROPOD" => "snails", "GASTEROPOD" => "snails", "BIVALV" => "snails");
    $phyla = array("ANNELID" => "worms", "PLATYHELM" => "worms", "NEMATOMORPH" => "worms", "NEMATOD" => "worms", "MOLLUSC" => "snails",
        "PORIFER" => "other", "CNIDAR" => "other", "PROTOZO" => "other", "BRYOZO" => "other");
    foreach (array(array($order, $orders), array($class, $classes), array($phylum, $phyla)) as $test)
    {
        if ($test[0] === "")
            continue;
        foreach ($test[1] as $prefix => $key)
            if (strpos($test[0], $prefix) === 0)
                return $key;
    }
    return ($class !== "" || $phylum !== "") ? "other" : "unknown";
}

/**
 * Name under which the sightings of a species are grouped (fauna map, cards of
 * the species sheets). The sightings keep the name of their source; the species
 * sheet says whether that name is still valid:
 * - valid or not checked: the name itself;
 * - synonym, historical combination, ambiguous synonym or nomen dubium with a
 *   valid name: that valid name;
 * - the same without a valid name (ambiguous names, referred to several
 *   species): the genus, as "Genus sp.".
 *
 * @param array|false $sheet species sheet (scientific_name, genus, taxon_status, accepted_name)
 * @param string $name name of the sighting
 * @return array "name" grouping name, "status" taxon_status of the sheet,
 *               "moved" true when the name differs from the cited one
 */
function openkis_FaunaResolve($sheet, $name)
{
    $name = trim(preg_replace('/\s+/', " ", (string) $name));
    $status = is_array($sheet) && isset($sheet['taxon_status']) ? trim((string) $sheet['taxon_status']) : "";
    $result = array("name" => $name, "status" => $status, "moved" => false);
    if ($status === "" || $status === "valid")
        return $result;
    $accepted = trim(preg_replace('/\s+/', " ", (string) (isset($sheet['accepted_name']) ? $sheet['accepted_name'] : "")));
    if ($accepted === "")
    {
        $genus = isset($sheet['genus']) ? trim((string) $sheet['genus']) : "";
        if ($genus === "")
        {
            $words = explode(" ", $name);
            $genus = $words[0];
        }
        if ($genus === "")
            return $result;
        $accepted = ucfirst(strtolower($genus)) . " sp.";
    }
    if (openkis_FaunaKey($accepted) !== openkis_FaunaKey($name))
    {
        $result['name'] = $accepted;
        $result['moved'] = true;
    }
    return $result;
}

/**
 * Species sheets whose name is not valid (see openkis_FaunaResolve()), visible
 * to the user, read once per request.
 *
 * @return array sheets indexed by openkis_FaunaKey() of the scientific name
 */
function openkis_FaunaNotValidSheets()
{
    global $_FN;
    static $sheets = null;
    if ($sheets !== null)
        return $sheets;
    $sheets = array();
    if (!openkis_FaunaHasStatus() || !FN_UserCanViewSection("fauna"))
        return $sheets;
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    $config = FN_LoadConfig("modules/dbview/config.php", "fauna");
    $dbview = new FNDBVIEW($config);
    $params = array();
    $params['fields'] = openkis_FaunaSpeciesFields("id,scientific_name,name,type,class");
    $params['appendquery'] = "(taxon_status <> '' AND taxon_status <> 'valid')";
    $items = $dbview->GetResults($config, $params);
    foreach (is_array($items) ? $items : array() as $item)
        $sheets[openkis_FaunaKey($item['scientific_name'])] = $item;
    return $sheets;
}

/**
 * Names whose sightings are grouped under a name: the name itself and the not
 * valid names that resolve to it (its synonyms; for "Genus sp." the ambiguous
 * names of the genus).
 *
 * @param string $name
 * @return array names, the given one first
 */
function openkis_FaunaNamesOf($name)
{
    $name = trim((string) $name);
    $names = array($name);
    $key = openkis_FaunaKey($name);
    foreach (openkis_FaunaNotValidSheets() as $skey => $sheet)
    {
        $resolved = openkis_FaunaResolve($sheet, $sheet['scientific_name']);
        if ($resolved['moved'] && openkis_FaunaKey($resolved['name']) === $key && $skey !== $key)
            $names[] = $sheet['scientific_name'];
    }
    return $names;
}

/**
 * SQL condition matching the sightings of a name and of the names grouped under it.
 *
 * @param string $field species field of the sightings table
 * @param array $names
 * @return string
 */
function openkis_FaunaNamesWhere($field, $names)
{
    $or = array();
    foreach ($names as $n)
        $or[] = "$field LIKE '" . addslashes($n) . "'";
    return "(" . implode(" OR ", $or) . ")";
}

/**
 * Short html note about a cited name grouped under another name, e.g.
 * "→ Trogulus sp. (ambiguous synonym)".
 *
 * @param array $resolved result of openkis_FaunaResolve()
 * @param int $id id of the sheet of the grouping name, 0 = no link
 * @return string html, empty when the name is not moved
 */
function openkis_FaunaResolvedNote($resolved, $id = 0)
{
    if (empty($resolved['moved']))
        return "";
    $statuses = openkis_FaunaStatuses();
    $name = "<i>" . htmlspecialchars($resolved['name']) . "</i>";
    if ($id > 0)
        $name = "<a href=\"" . FN_RewriteLink("index.php?mod=fauna&op=view&id=" . intval($id)) . "\">$name</a>";
    $status = isset($statuses[$resolved['status']]) ? " (" . htmlspecialchars($statuses[$resolved['status']]) . ")" : "";
    return "<small class=\"openkis-fauna-resolved text-muted\">&rarr; $name$status</small>";
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
 *               category, class, classkey, group (icon), id of the species sheet,
 *               sightings, last date), categories, classes and groups (key =>
 *               number of species),
 *               category and classkey (the prevailing ones), nsightings
 */
function openkis_FaunaMapPlaces($where = "", $filter = array())
{
    $sightings = openkis_FaunaSightings($where !== "" ? $where : "1");
    if (!count($sightings))
        return array();
    $species = openkis_FaunaRecordsByField("fauna", "scientific_name", array_column($sightings, "name"), openkis_FaunaSpeciesFields("id,scientific_name,name,type,class"));
    // names not valid: the sightings go under the valid name or the genus (openkis_FaunaResolve())
    $resolved = array();
    foreach (array_unique(array_column($sightings, "name")) as $n)
    {
        $k = openkis_FaunaKey($n);
        $resolved[$k] = openkis_FaunaResolve(isset($species[$k]) ? $species[$k] : false, $n);
    }
    $targets = array();
    foreach ($resolved as $r)
        if ($r['moved'] && !isset($species[openkis_FaunaKey($r['name'])]))
            $targets[] = $r['name'];
    if (count($targets))
        $species = array_merge($species, openkis_FaunaRecordsByField("fauna", "scientific_name", $targets, openkis_FaunaSpeciesFields("id,scientific_name,name,type,class")));
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
            $cited = openkis_FaunaKey($sighting['name']);
            if ($cited === "")
                continue;
            // the species is the valid name (or the genus) of the cited one
            $group = $resolved[$cited];
            $skey = openkis_FaunaKey($group['name']);
            if (!isset($result[$key]['species'][$skey]))
            {
                $empty = array("id" => 0, "scientific_name" => $group['name'], "name" => "", "type" => "", "class" => "");
                // without a sheet of the valid name, category and class come from the sheet of the cited name
                $sheet = isset($species[$skey]) ? $species[$skey] : (isset($species[$cited]) ? array_merge($species[$cited], array("id" => 0, "scientific_name" => $group['name'], "name" => "")) : $empty);
                $sheet = openkis_FaunaTaxonomyFromGenus($sheet, $group['name']);
                $result[$key]['species'][$skey] = array(
                    "name" => $sheet['scientific_name'] !== "" ? $sheet['scientific_name'] : $group['name'],
                    "common" => (string) $sheet['name'],
                    "type" => (string) $sheet['type'],
                    "category" => openkis_FaunaCategory($sheet['type']),
                    "class" => (string) $sheet['class'],
                    "classkey" => openkis_FaunaClassKey($sheet['class']),
                    "group" => openkis_FaunaGroupKey($sheet),
                    "id" => intval($sheet['id']),
                    "cited" => array(),
                    "sightings" => 0,
                    "last" => "");
            }
            if ($group['moved'])
            {
                $result[$key]['species'][$skey]['cited'][$cited] = isset($species[$cited]) ? $species[$cited]['scientific_name'] : $sighting['name'];
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
        $groups = array();
        foreach ($place['species'] as $sp)
        {
            $categories[$sp['category']] = isset($categories[$sp['category']]) ? $categories[$sp['category']] + 1 : 1;
            $classes[$sp['classkey']] = isset($classes[$sp['classkey']]) ? $classes[$sp['classkey']] + 1 : 1;
            $groups[$sp['group']] = isset($groups[$sp['group']]) ? $groups[$sp['group']] + 1 : 1;
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
        // icon groups in legend order (openkis_FaunaGroups())
        $place['groups'] = array();
        foreach (array_keys(openkis_FaunaGroups()) as $g)
            if (isset($groups[$g]))
                $place['groups'][$g] = $groups[$g];
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
    $species = openkis_FaunaRecordsByField("fauna", "scientific_name", array_column($sightings, "name"), openkis_FaunaSpeciesFields("id,scientific_name,name,type"));
    // valid names (or genera) of the names that are no longer valid, for the note under the cited name
    $resolved = array();
    foreach ($sightings as $sighting)
    {
        $key = openkis_FaunaKey($sighting['name']);
        $resolved[$key] = openkis_FaunaResolve(isset($species[$key]) ? $species[$key] : false, $sighting['name']);
    }
    $targets = array();
    foreach ($resolved as $r)
        if ($r['moved'])
            $targets[] = $r['name'];
    $valid = openkis_FaunaRecordsByField("fauna", "scientific_name", $targets, "id,scientific_name");
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
        if ($resolved[$key]['moved'])
        {
            $vkey = openkis_FaunaKey($resolved[$key]['name']);
            $name .= "<br />" . openkis_FaunaResolvedNote($resolved[$key], isset($valid[$vkey]) ? $valid[$vkey]['id'] : 0);
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
    // also the sightings recorded with the names grouped under this one (synonyms, ambiguous names of a genus)
    $names = openkis_FaunaNamesOf($scientific_name);
    $sightings = openkis_FaunaSightings(openkis_FaunaNamesWhere($field, $names));
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
        $cited = "";
        if (openkis_FaunaKey($sighting['name']) !== openkis_FaunaKey($scientific_name))
            $cited = "<br /><small class=\"text-muted\">" . FN_Translate("cited as", "aa") . " <i>" . htmlspecialchars($sighting['name']) . "</i></small>";
        $cells[] = array("<a href=\"$cave_url\">" . htmlspecialchars($cave['code']) . "</a>",
            "<a href=\"$cave_url\">" . htmlspecialchars($cave['name']) . "</a><br /><small>" . htmlspecialchars($place) . "</small>$cited",
            htmlspecialchars($sighting['ammount']), openkis_FaunaDate($sighting['date']), htmlspecialchars($sighting['caver']), openkis_SheetViewButton($url));
    }
    $head = array(array(FN_Translate("number"), "text-nowrap openkis-card-code"), FN_Translate("cave"), FN_Translate("specimens"), array(FN_Translate("date"), "text-nowrap"), FN_Translate("observer"), array("", "text-right text-end"));
    // the map shows the fauna points of the species (openkis_fauna.php) over the icons of its caves
    $filter = "filter_code=" . implode(",", array_map("rawurlencode", array_keys($listed))) . "&fauna_species=" . rawurlencode($scientific_name);
    $options = array_merge(array("id" => "card_observed_in"), openkis_SheetMapOptions(openkis_SheetCavesMapUrl($listed, $filter)));
    return openkis_SheetListCard($title != "" ? $title : FN_Translate("observed in"), $head, $cells, $options);
}
