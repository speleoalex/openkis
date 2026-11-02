<?php

/**
 * Fauna sightings (section fauna_surveys) on the cave sheet and on the species sheet
 * (section fauna). Shown only to the users who can view the fauna_surveys section; the
 * sightings and the caves restricted with groupview are left out by FNDBVIEW.
 */

/**
 * Sightings visible to the user that match an SQL condition.
 *
 * @param string $where condition on the sightings table (codecave, name, ...)
 * @return array id, name, codecave, ammount, date, caver of each sighting
 */
function openkis_FaunaSightings($where)
{
    global $_FN;
    if (!file_exists("sections/fauna_surveys") || !FN_UserCanViewSection("fauna_surveys"))
        return array();
    $config = FN_LoadConfig("modules/dbview/config.php", "fauna_surveys");
    // the section must list the sightings table (it lists fn_files when never configured)
    $table = !empty($config['tables']) ? FN_XMDBTable($config['tables']) : false;
    if (!$table || !isset($table->fields['codecave']) || !isset($table->fields['name']))
        return array();
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    $dbview = new FNDBVIEW($config);
    $params = array();
    $params['fields'] = "id,name,codecave,ammount,date,caver";
    $params['appendquery'] = $where;
    $items = $dbview->GetResults($config, $params);
    return is_array($items) ? $items : array();
}

/**
 * Records of a dbview section whose field matches one of the values, visible to the user.
 *
 * @param string $mod section (fauna, caves)
 * @param string $field field compared with the values
 * @param array $values
 * @param string $fields fields to read
 * @return array records indexed by the field value
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
        $records[$item[$field]] = $item;
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
 * Card listing the species observed in a cave, for the cave sheet.
 *
 * @param string $code cave code
 * @return string html, empty when the cave has no sighting visible to the user
 */
function openkis_FaunaCaveCard($code)
{
    if ($code === "")
        return "";
    $sightings = openkis_FaunaSightings("codecave LIKE '" . addslashes($code) . "'");
    if (!count($sightings))
        return "";
    $sightings = FN_ArraySortByKey($sightings, "name");
    $species = openkis_FaunaRecordsByField("fauna", "scientific_name", array_column($sightings, "name"), "id,scientific_name,name,type");
    $html = "<div class=\"card openkis-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">" . FN_Translate("cave fauna") . "</h5></div>";
    $html .= "<div class=\"card-body p-0\"><div class=\"table-responsive\"><table class=\"table table-sm table-striped mb-0\">";
    $html .= "<tr><th>" . FN_Translate("species") . "</th><th>" . FN_Translate("category") . "</th><th>" . FN_Translate("specimens") . "</th><th>" . FN_Translate("date") . "</th><th>" . FN_Translate("observer") . "</th><th></th></tr>";
    foreach ($sightings as $sighting)
    {
        $url = FN_RewriteLink("index.php?mod=fauna_surveys&op=view&id={$sighting['id']}");
        $name = "<i>" . htmlspecialchars($sighting['name']) . "</i>";
        $type = "";
        if (isset($species[$sighting['name']]))
        {
            $sp = $species[$sighting['name']];
            $name = "<a href=\"" . FN_RewriteLink("index.php?mod=fauna&op=view&id={$sp['id']}") . "\">$name</a>";
            if ($sp['name'] != "")
                $name .= "<br /><small>" . htmlspecialchars($sp['name']) . "</small>";
            // the categories are stored in Italian (TROGLOBIO, STIGOFILO, ...)
            $type = htmlspecialchars(ucfirst(strtolower($sp['type'])));
        }
        $html .= "<tr><td>$name</td><td>$type</td><td>" . htmlspecialchars($sighting['ammount']) . "</td>";
        $html .= "<td class=\"text-nowrap\">" . openkis_FaunaDate($sighting['date']) . "</td><td>" . htmlspecialchars($sighting['caver']) . "</td>";
        $html .= "<td class=\"text-right\"><a class=\"btn btn-sm btn-outline-primary\" href=\"$url\">" . FN_Translate("view") . "</a></td></tr>";
    }
    $html .= "</table></div></div></div>";
    return $html;
}

/**
 * Map of the caves (only these caves, as in the cave systems), centred and zoomed on them.
 *
 * @param array $caves records with code, latitude, longitude
 * @return string html, empty when no cave has coordinates
 */
function openkis_FaunaCavesMap($caves)
{
    global $_FN;
    $codes = array();
    $lats = array();
    $lons = array();
    foreach ($caves as $cave)
    {
        $codes[] = rawurlencode($cave['code']);
        if (!empty($cave['latitude']) && !empty($cave['longitude']))
        {
            $lats[] = floatval(str_replace(",", ".", $cave['latitude']));
            $lons[] = floatval(str_replace(",", ".", $cave['longitude']));
        }
    }
    if (!count($lats))
        return "";
    $lat = (max($lats) + min($lats)) / 2;
    $lon = (max($lons) + min($lons)) / 2;
    // zoom that fits the extent in a map about 800x400 px (256 px tiles), between 8 and 14
    $span = max(max($lons) - min($lons), (max($lats) - min($lats)) * 3);
    $zoom = $span > 0 ? floor(log(3 * 360 / ($span * 1.3), 2)) : 14;
    $zoom = max(8, min(14, $zoom));
    $src = "{$_FN['siteurl']}bs_map.htm?mod=caves&zoom=$zoom&lat=" . sprintf("%.6F", $lat) . "&lon=" . sprintf("%.6F", $lon) . "&filter_code=" . implode(",", array_unique($codes));
    return "<iframe style=\"border:0px;width:100%;height:400px;display:block\" src=\"" . htmlspecialchars($src) . "\"></iframe>";
}

/**
 * Card listing the caves where a species was observed, for the species sheet.
 * Sightings in caves the user may not see are left out.
 *
 * @param string $scientific_name
 * @return string html, empty when there is no sighting visible to the user
 */
function openkis_FaunaSpeciesCard($scientific_name)
{
    if ($scientific_name === "")
        return "";
    $sightings = openkis_FaunaSightings("name LIKE '" . addslashes($scientific_name) . "'");
    if (!count($sightings))
        return "";
    $caves = openkis_FaunaRecordsByField("caves", "code", array_column($sightings, "codecave"), "id,code,name,comune,provincia,latitude,longitude");
    $rows = array();
    foreach ($sightings as $sighting)
    {
        if (!isset($caves[$sighting['codecave']]))
            continue;
        $rows[] = array("cave" => $caves[$sighting['codecave']], "sighting" => $sighting);
    }
    if (!count($rows))
        return "";
    usort($rows, function ($a, $b)
    {
        return strnatcmp($a['cave']['code'], $b['cave']['code']);
    });
    $html = "<div class=\"card openkis-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">" . FN_Translate("observed in") . " (" . count($rows) . ")</h5></div>";
    $html .= "<div class=\"card-body p-0\">" . openkis_FaunaCavesMap($caves);
    $html .= "<div class=\"table-responsive\"><table class=\"table table-sm table-striped mb-0\">";
    $html .= "<tr><th>" . FN_Translate("cave") . "</th><th></th><th>" . FN_Translate("specimens") . "</th><th>" . FN_Translate("date") . "</th><th>" . FN_Translate("observer") . "</th><th></th></tr>";
    foreach ($rows as $r)
    {
        $cave = $r['cave'];
        $sighting = $r['sighting'];
        $cave_url = FN_RewriteLink("index.php?mod=caves&op=view&id={$cave['id']}");
        $url = FN_RewriteLink("index.php?mod=fauna_surveys&op=view&id={$sighting['id']}");
        $place = trim($cave['comune'] . ($cave['provincia'] != "" ? " ({$cave['provincia']})" : ""));
        $html .= "<tr><td class=\"text-nowrap\"><a href=\"$cave_url\">" . htmlspecialchars($cave['code']) . "</a></td>";
        $html .= "<td><a href=\"$cave_url\">" . htmlspecialchars($cave['name']) . "</a><br /><small>" . htmlspecialchars($place) . "</small></td>";
        $html .= "<td>" . htmlspecialchars($sighting['ammount']) . "</td><td class=\"text-nowrap\">" . openkis_FaunaDate($sighting['date']) . "</td><td>" . htmlspecialchars($sighting['caver']) . "</td>";
        $html .= "<td class=\"text-right\"><a class=\"btn btn-sm btn-outline-primary\" href=\"$url\">" . FN_Translate("view") . "</a></td></tr>";
    }
    $html .= "</table></div></div></div>";
    return $html;
}
