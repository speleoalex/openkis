<?php
/**
 * Paleontological and archaeological findings (section "findings", table
 * ctl_findings): the cards that list them on the sheets of the cavities and
 * of the publications, the places of the map of the findings
 * (openkis_findings.php).
 *
 * A finding restricted to some groups (groupview) is listed for everybody
 * with its category and period only: title and link are for the users of
 * those groups and for the editors of the section.
 */
defined('_FNEXEC') or die('Restricted access');

/**
 * Whether the user sees a finding in full (title, finds, link to its sheet):
 * a finding with groupview is restricted to the administrators, to the users
 * of those groups and to the editors of the section; the others see only its
 * category and period.
 *
 * @param string $groupview field groupview of the finding
 * @return bool
 */
function openkis_FindingVisible($groupview)
{
    global $_FN;
    static $editors = null;
    if ($groupview == "" || FN_IsAdmin())
        return true;
    if ($_FN['user'] == "")
        return false;
    if ($editors === null)
    {
        $config = FN_LoadConfig("modules/dbview/config.php", "findings");
        $editors = $config['groupinsert'] . "," . $config['groupadmin'];
    }
    return FN_UserInGroup($_FN['user'], "$groupview,$editors");
}

/**
 * Findings that match a condition, from the oldest period (the options of the
 * field period are listed from the oldest), then by year; findings without a
 * period last. Empty when the user may not view the section findings.
 *
 * @param string $where SQL condition on ctl_findings
 * @return array records (id, name, category, finding_type, period,
 *               period_detail, dating_from, codecave, codeartificial,
 *               groupview) with "visible" (openkis_FindingVisible())
 */
function openkis_FindingsRecords($where)
{
    $table = FN_XMDBTable("ctl_findings");
    if (!file_exists("sections/findings") || !FN_UserCanViewSection("findings") || !is_object($table))
        return array();
    $records = FN_XMETADBQuery("SELECT id,name,category,finding_type,period,period_detail,dating_from,codecave,codeartificial,groupview FROM ctl_findings WHERE ($where) AND recorddeleted <> '1'");
    if (!is_array($records))
        return array();
    $periods = array_flip(explode(",", $table->fields['period']->frm_options));
    foreach ($records as $k => $record)
    {
        $records[$k]['visible'] = openkis_FindingVisible($record['groupview']);
        $records[$k]['order'] = (isset($periods[$record['period']]) ? $periods[$record['period']] : count($periods)) * 1e8 + floatval($record['dating_from']);
    }
    usort($records, function ($a, $b)
    {
        return $a['order'] <=> $b['order'];
    });
    return $records;
}

/**
 * Options of a field of ctl_findings (category, finding_type, period) in the
 * order of the definition: key => translated label.
 *
 * @param string $field
 * @return array
 */
function openkis_FindingsOptions($field)
{
    $table = FN_XMDBTable("ctl_findings");
    if (!is_object($table) || !isset($table->fields[$field]))
        return array();
    $keys = explode(",", $table->fields[$field]->frm_options);
    $labels = isset($table->fields[$field]->frm_options_i18n) ? explode(",", $table->fields[$field]->frm_options_i18n) : array();
    $options = array();
    foreach ($keys as $i => $key)
        $options[$key] = FN_Translate(isset($labels[$i]) && $labels[$i] !== "" ? $labels[$i] : $key);
    return $options;
}

/**
 * Types of finds (options of finding_type) with the icon shown on the map of
 * the findings, in legend order: key => icon (emoji), fallback (character
 * drawn when the browser has no glyph for the emoji), label (translated).
 *
 * @return array
 */
function openkis_FindingsTypes()
{
    $icons = array(
        "faunal remains" => array("🦴", ""),
        "human remains" => array("💀", ""),
        "lithic industry" => array("🪨", "◆"),
        "pottery" => array("🏺", ""),
        "bone artefacts" => array("🪡", "✎"),
        "metal objects" => array("⚔️", ""),
        "coins" => array("🪙", "💰"),
        "burial" => array("⚱️", ""),
        "structures" => array("🔥", ""),
        "rock art" => array("🎨", ""),
        "plant remains" => array("🌿", ""),
        "other" => array("🔸", "•"));
    $types = array();
    foreach (openkis_FindingsOptions("finding_type") as $key => $label)
    {
        $icon = isset($icons[$key]) ? $icons[$key] : $icons['other'];
        $types[$key] = array("icon" => $icon[0], "fallback" => $icon[1], "label" => $label);
    }
    return $types;
}

/**
 * Caves and artificial cavities with findings, for the map of the findings
 * (openkis_findings.php): one entry per place with coordinates that the user
 * may view. The restricted findings count with their category and period
 * only: their finds are left out of the types.
 *
 * @return array places: mod, record (id, code, name, comune, provincia,
 *               latitude, longitude), findings (records of
 *               openkis_FindingsRecords(), oldest first), categories (key =>
 *               number of findings), types (key => number of findings, in
 *               legend order), periods (keys, oldest first)
 */
function openkis_FindingsMapPlaces()
{
    $records = openkis_FindingsRecords("codecave <> '' OR codeartificial <> ''");
    if (!count($records))
        return array();
    require_once "extra/openkis/openkis_sheet.inc.php";
    $fields = "id,code,name,comune,provincia,latitude,longitude";
    $cavities = array();
    foreach (array("codecave" => "caves", "codeartificial" => "artificials") as $field => $mod)
        $cavities[$field] = array_column(openkis_SheetRecordsByCode($mod, array_column($records, $field), $fields), null, "code");
    $order = array_keys(openkis_FindingsTypes());
    $places = array();
    foreach ($records as $record)
    {
        $field = $record['codecave'] != "" ? "codecave" : "codeartificial";
        $code = $record[$field];
        if (!isset($cavities[$field][$code]) || empty($cavities[$field][$code]['latitude']) || empty($cavities[$field][$code]['longitude']))
            continue;
        $mod = $field == "codecave" ? "caves" : "artificials";
        $key = "$mod:$code";
        if (!isset($places[$key]))
            $places[$key] = array("mod" => $mod, "record" => $cavities[$field][$code], "findings" => array(), "categories" => array(), "types" => array(), "periods" => array());
        $places[$key]['findings'][] = $record;
        $category = $record['category'];
        $places[$key]['categories'][$category] = isset($places[$key]['categories'][$category]) ? $places[$key]['categories'][$category] + 1 : 1;
        if ($record['period'] != "" && !in_array($record['period'], $places[$key]['periods']))
            $places[$key]['periods'][] = $record['period'];
        if (!$record['visible'])
            continue;
        foreach (array_filter(explode(",", $record['finding_type']), "strlen") as $type)
            $places[$key]['types'][$type] = isset($places[$key]['types'][$type]) ? $places[$key]['types'][$type] + 1 : 1;
    }
    foreach ($places as $key => $place)
    {
        $types = array();
        foreach ($order as $type)
            if (isset($place['types'][$type]))
                $types[$type] = $place['types'][$type];
        $places[$key]['types'] = $types;
    }
    return $places;
}

/**
 * Card listing the findings that match a condition, from the oldest period.
 *
 * @param string $title
 * @param string $where SQL condition on ctl_findings
 * @param bool $with_cavity adds the column of the cavity and leaves out the
 *                          restricted findings and the ones in a cavity hidden
 *                          to the user (sheet of a publication)
 * @return string html, empty without findings to list
 */
function openkis_FindingsCard($title, $where, $with_cavity = false)
{
    $records = openkis_FindingsRecords($where);
    if (count($records) == 0)
        return "";
    require_once "extra/openkis/openkis_sheet.inc.php";
    $cavities = array();
    if ($with_cavity)
    {
        foreach (array("codecave" => "caves", "codeartificial" => "artificials") as $field => $mod)
        {
            $cavities[$field] = array();
            foreach (openkis_SheetRecordsByCode($mod, array_column($records, $field), "id,code,name") as $cavity)
            {
                $url = FN_RewriteLink("index.php?mod=$mod&op=view&id={$cavity['id']}");
                $cavities[$field][$cavity['code']] = "<a href=\"$url\">" . htmlspecialchars(trim($cavity['code'] . " " . $cavity['name'])) . "</a>";
            }
        }
    }
    $rows = array();
    foreach ($records as $record)
    {
        $visible = $record['visible'];
        $period = $record['period'] != "" ? openkis_SheetUcfirst(FN_Translate($record['period'])) : "";
        $row = array();
        if ($with_cavity)
        {
            $field = $record['codecave'] != "" ? "codecave" : "codeartificial";
            if (!$visible || !isset($cavities[$field][$record[$field]]))
                continue;
            $row[] = $cavities[$field][$record[$field]];
        }
        $row[] = htmlspecialchars(openkis_SheetUcfirst(FN_Translate($record['category'])));
        $row[] = htmlspecialchars(trim($period . ($record['period_detail'] != "" ? " ({$record['period_detail']})" : "")));
        $row[] = $visible ? htmlspecialchars($record['name']) : "<span class=\"text-muted\">" . FN_Translate("restricted finding") . "</span>";
        $row[] = $visible ? openkis_SheetViewButton(FN_RewriteLink("index.php?mod=findings&op=view&id={$record['id']}")) : "";
        $rows[] = $row;
    }
    if (count($rows) == 0)
        return "";
    $head = array(FN_Translate("category"), FN_Translate("period"), FN_Translate("finding"), array("", "text-right text-end"));
    if ($with_cavity)
        array_unshift($head, FN_Translate("cavity"));
    return openkis_SheetListCard($title, $head, $rows, array("id" => "card_findings"));
}

/**
 * Findings of a cavity, for its sheet.
 *
 * @param string $code code of the cavity
 * @param string $field codecave or codeartificial
 * @return string html
 */
function openkis_FindingsCavityCard($code, $field = "codecave")
{
    if ($code === "")
        return "";
    return openkis_FindingsCard(FN_Translate("paleontological and archaeological findings"), "$field LIKE '" . addslashes($code) . "'");
}

/**
 * Findings taken from a publication (field bibliography_ids), for the
 * bibliography sheet.
 *
 * @param string $id id of the bibliography record
 * @return string html
 */
function openkis_FindingsBibliographyCard($id)
{
    $q = addslashes(trim(strval($id)));
    if ($q === "")
        return "";
    return openkis_FindingsCard(FN_Translate("findings taken from the publication"),
        "bibliography_ids LIKE '$q' OR bibliography_ids LIKE '$q,%' OR bibliography_ids LIKE '%,$q,%' OR bibliography_ids LIKE '%,$q'", true);
}
