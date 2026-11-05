<?php

/**
 * Tracer tests (section "tracings", table ctl_tracings).
 *
 * A record is one arrow: from the injection point (free coordinates, or the
 * entrance of the injection cave or uncatalogued cavity when they are empty)
 * to the detection point (a cave, a spring or an uncatalogued cavity).
 * Several detection points of the same campaign are separate records sharing
 * the same code.
 */
// list cards of the sheets
require_once __DIR__ . "/openkis_sheet.inc.php";
defined('_FNEXEC') or die('Restricted access');

/**
 * Whether the installed definition of ctl_tracings has the links to the
 * uncatalogued cavities (uncatalogued_injection, uncatalogued_detection):
 * misc/fndatabase/ctl_tracings.php is never overwritten by the installer.
 *
 * @return bool
 */
function openkis_TracingsHasUncatalogued()
{
    static $has = null;
    if ($has === null)
    {
        $table = FN_XMDBTable("ctl_tracings");
        $has = is_object($table) && isset($table->fields['uncatalogued_injection']) && isset($table->fields['uncatalogued_detection']);
    }
    return $has;
}

/**
 * The register record of one end of a tracing: the injection cave or
 * uncatalogued cavity, the detection cave, spring or uncatalogued cavity
 * (first one filled in, in this order).
 *
 * @param array $tracing record of ctl_tracings
 * @param string $end "injection" or "detection"
 * @return array array(code, mod), both "" when the point is only given by
 *               its free coordinates
 */
function openkis_TracingEnd($tracing, $end)
{
    $fields = ($end == "injection") ?
        array("cave_injection" => "caves", "uncatalogued_injection" => "uncatalogued") :
        array("cave_detection" => "caves", "spring_detection" => "springs", "uncatalogued_detection" => "uncatalogued");
    foreach ($fields as $field => $mod)
    {
        $code = isset($tracing[$field]) ? trim(strval($tracing[$field])) : "";
        if ($code !== "")
            return array($code, $mod);
    }
    return array("", "");
}

/**
 * Groups allowed to see each restricted uncatalogued cavity: the groups of
 * the section (in the regions where the whole section is reserved) and those
 * of the record, both when both are set.
 *
 * @return array code => array of group names; only the restricted cavities
 */
function openkis_TracingsRestrictedUncatalogued()
{
    $restricted = array();
    if (!openkis_TracingsHasUncatalogued() || !FN_XMDBTable("ctl_uncatalogued"))
        return $restricted;
    $section = FN_GetSectionValues("uncatalogued");
    $section_groups = (is_array($section) && !empty($section['group_view'])) ? array_values(array_filter(array_map('trim', explode(",", $section['group_view'])))) : array();
    $rows = FN_XMETADBQuery("SELECT code,groupview FROM ctl_uncatalogued");
    if (!is_array($rows))
        return $restricted;
    foreach ($rows as $row)
    {
        $groups = $section_groups;
        $record_groups = array_values(array_filter(array_map('trim', explode(",", strval($row['groupview'])))));
        if (count($record_groups) > 0)
        {
            $groups = (count($groups) > 0) ? array_values(array_intersect($groups, $record_groups)) : $record_groups;
        }
        // an empty list (no group in both) means curators only, as for the caves
        if (count($section_groups) > 0 || count($record_groups) > 0)
        {
            $restricted[trim(strval($row['code']))] = $groups;
        }
    }
    return $restricted;
}

/**
 * Fills the computed fields of ctl_tracings:
 *  - latitude/longitude: WGS84 of the free injection coordinates (empty when
 *    the injection point is taken from the cave);
 *  - air_regime: from the release date when empty (May-October summer,
 *    November-April winter), air tracings only;
 *  - name: "injection -> detection" when empty;
 *  - groupview: the groups allowed to see the restricted caves and
 *    uncatalogued cavities of the record, so a tracing never shows a point
 *    that the user cannot see (the section enables the per-record permissions
 *    on this field).
 *
 * Cheap to call on every request: a time stamp file makes it a no-op until
 * ctl_caves, ctl_uncatalogued or ctl_tracings changes (same pattern as
 * openkis_UpdateCaveSystems). A change of the groups of the section
 * "uncatalogued" is seen at the next change of one of those tables.
 *
 * @param bool $forceall recompute even when the tables did not change
 * @return void
 */
function openkis_UpdateTracings($forceall = false)
{
    global $_FN;
    $tracings_table = FN_XMDBTable("ctl_tracings");
    $caves_table = FN_XMDBTable("ctl_caves");
    if (!is_object($tracings_table) || !is_object($caves_table) || !isset($tracings_table->fields['groupview']))
        return;
    $file_lastUpdate = "{$_FN['datadir']}/_cache/openkis_UpdateTracings.time";
    if (!file_exists($file_lastUpdate))
    {
        $fp = @fopen($file_lastUpdate, "a");
        if (is_resource($fp))
            fclose($fp);
        $forceall = true;
    }
    // fresh stat values (a table may have been written earlier in this
    // request) and a strict comparison: a change made in the same second as
    // the last pass is not missed, at worst the cheap pass runs once more
    clearstatcache();
    // the links to the uncatalogued cavities and the detection coordinates
    // exist only when the installed definition (misc/fndatabase/ctl_tracings.php,
    // never overwritten) has them
    $uncatalogued = openkis_TracingsHasUncatalogued();
    $uncatalogued_table = $uncatalogued ? FN_XMDBTable("ctl_uncatalogued") : false;
    $changed = $caves_table->GetLastUpdateTime() >= filectime($file_lastUpdate) || $tracings_table->GetLastUpdateTime() >= filectime($file_lastUpdate);
    if (!$changed && is_object($uncatalogued_table))
    {
        $changed = $uncatalogued_table->GetLastUpdateTime() >= filectime($file_lastUpdate);
    }
    if (!$forceall && !$changed)
    {
        return;
    }
    $detection = isset($tracings_table->fields['detection_latitude']);
    $fields = "id,name,type,air_regime,release_date,cave_injection,cave_detection,spring_detection,latitude_txt,longitude_txt,coordinates_type,elevation,latitude,longitude,groupview";
    if ($detection)
    {
        $fields .= ",detection_latitude_txt,detection_longitude_txt,detection_coordinates_type,detection_latitude,detection_longitude";
    }
    if ($uncatalogued)
    {
        $fields .= ",uncatalogued_injection,uncatalogued_detection";
    }
    $tracings = FN_XMETADBQuery("SELECT $fields FROM ctl_tracings");
    if (!is_array($tracings) || count($tracings) == 0)
    {
        @touch($file_lastUpdate);
        return;
    }
    $restricted = array();
    // XMETADB SQL parser: no IS NULL / "=" here, "<> ''" also skips NULL
    $rows = FN_XMETADBQuery("SELECT code,groupview FROM ctl_caves WHERE groupview <> ''");
    if (is_array($rows))
    {
        foreach ($rows as $cave)
        {
            $restricted[trim(strval($cave['code']))] = array_filter(array_map('trim', explode(",", $cave['groupview'])));
        }
    }
    $restricted_uncatalogued = $uncatalogued ? openkis_TracingsRestrictedUncatalogued() : array();
    foreach ($tracings as $tracing)
    {
        $new = array();
        //---- free coordinates of the injection and detection points --------->
        $points = array("" => "tracing {$tracing['id']}");
        if ($detection)
        {
            $points["detection_"] = "tracing {$tracing['id']} detection";
        }
        $free = array();
        foreach ($points as $prefix => $label)
        {
            $free[$prefix] = openkis_TracingFreePosition($tracing, $prefix, $label);
            $lat = $free[$prefix][0];
            $lon = $free[$prefix][1];
            // FLOAT columns come back with 6 significant digits (45.7673, 8.55287):
            // compare with that resolution, or every pass would rewrite the
            // record, mark the table as changed and run again at the next view
            $stored = (floatval($tracing["{$prefix}latitude"]) != 0 && floatval($tracing["{$prefix}longitude"]) != 0);
            if ($lat === "" ? $stored : (!$stored || abs(floatval($tracing["{$prefix}latitude"]) - floatval($lat)) > 0.0001 || abs(floatval($tracing["{$prefix}longitude"]) - floatval($lon)) > 0.0001))
            {
                $new["{$prefix}latitude"] = $lat;
                $new["{$prefix}longitude"] = $lon;
                $new['coordnatesupdated'] = FN_Now();
            }
        }
        //---- air regime from the release date ------------------------------->
        if ($tracing['type'] == "air" && trim(strval($tracing['air_regime'])) === "" && !empty($tracing['release_date']))
        {
            $month = intval(substr($tracing['release_date'], 5, 2));
            if ($month >= 1 && $month <= 12)
            {
                $new['air_regime'] = ($month >= 5 && $month <= 10) ? "summer" : "winter";
            }
        }
        //---- default name --------------------------------------------------->
        if (trim(strval($tracing['name'])) === "")
        {
            list($from) = openkis_TracingEnd($tracing, "injection");
            list($to) = openkis_TracingEnd($tracing, "detection");
            if ($to === "" && isset($free["detection_"]) && $free["detection_"][0] !== "")
            {
                $to = round(floatval($free["detection_"][0]), 5) . "," . round(floatval($free["detection_"][1]), 5);
            }
            if ($from !== "" || $to !== "")
            {
                $new['name'] = ($from !== "" ? $from : "?") . " -> " . ($to !== "" ? $to : "?");
            }
        }
        //---- visibility from the restricted caves and uncatalogued cavities ->
        $groups = false;
        $linked = array(array($tracing['cave_injection'], $restricted), array($tracing['cave_detection'], $restricted));
        if ($uncatalogued)
        {
            $linked[] = array($tracing['uncatalogued_injection'], $restricted_uncatalogued);
            $linked[] = array($tracing['uncatalogued_detection'], $restricted_uncatalogued);
        }
        foreach ($linked as $link)
        {
            $code = trim(strval($link[0]));
            if ($code === "" || !isset($link[1][$code]))
                continue;
            $groups = ($groups === false) ? $link[1][$code] : array_values(array_intersect($groups, $link[1][$code]));
        }
        if ($groups === false)
        {
            $groupview = "";
        }
        else
        {
            // no group can see both restricted caves: curators only
            if (count($groups) == 0)
            {
                $config = FN_LoadConfig("modules/dbview/config.php", "tracings");
                $groups = array_filter(explode(",", empty($config['groupadmin']) ? "w_caves" : $config['groupadmin']));
            }
            $groupview = implode(",", $groups);
        }
        if (strval($tracing['groupview']) !== $groupview)
        {
            $new['groupview'] = $groupview;
        }
        if (count($new) > 0)
        {
            $tracings_table->UpdateRecordBypk($new, "id", $tracing['id']);
        }
    }
    @touch($file_lastUpdate);
}

/**
 * WGS84 position of the free coordinates of a tracing point.
 *
 * @param array $tracing record of ctl_tracings
 * @param string $prefix "" for the injection point, "detection_" for the
 *                       detection point
 * @param string $label name of the point in the coordinates log
 * @return array array(lat, lon), both "" when the point has no free
 *               coordinates (it is the entrance of the cave or spring)
 */
function openkis_TracingFreePosition($tracing, $prefix, $label)
{
    $lat_txt = isset($tracing["{$prefix}latitude_txt"]) ? trim(strval($tracing["{$prefix}latitude_txt"])) : "";
    $lon_txt = isset($tracing["{$prefix}longitude_txt"]) ? trim(strval($tracing["{$prefix}longitude_txt"])) : "";
    if ($lat_txt === "" || $lon_txt === "")
    {
        return array("", "");
    }
    $position = openkis_GetItemPosition(array(
        "code" => $label,
        "latitude_txt" => $lat_txt,
        "longitude_txt" => $lon_txt,
        "coordinates_type" => isset($tracing["{$prefix}coordinates_type"]) ? $tracing["{$prefix}coordinates_type"] : "",
        "latitude" => "",
        "longitude" => "",
        "elevation" => ""));
    if (floatval($position['lat']) == 0 || floatval($position['lon']) == 0)
    {
        return array("", "");
    }
    return array($position['lat'], $position['lon']);
}

/**
 * dbview callback (function_on_insert / function_on_update of the section
 * "tracings"): fills the computed fields right after the save, so the record
 * shown after saving already has them.
 *
 * @param array $record the saved record (not used)
 * @return void
 */
function openkis_OnSaveTracing($record)
{
    openkis_UpdateTracings(true);
}

$_FN['modparams']['tracings']['editorparams']['table']['function_on_update'] = "openkis_OnSaveTracing";
$_FN['modparams']['tracings']['editorparams']['table']['function_on_insert'] = "openkis_OnSaveTracing";

/**
 * frm_functionview of the option and foreign key fields of ctl_tracings: the
 * title of the stored value (translated option, "code name" of the cave or
 * spring). The list cards need it: the radio and selectfilter widgets have no
 * view of their own there and would show the raw value.
 *
 * @param array $values the record
 * @param string $fieldname
 * @return string html
 */
function openkis_TracingViewValue($values, $fieldname)
{
    static $titles = array();
    if (!isset($titles[$fieldname]))
    {
        $titles[$fieldname] = array();
        $form = FN_XMDBForm("ctl_tracings");
        if (isset($form->formvals[$fieldname]['options']) && is_array($form->formvals[$fieldname]['options']))
        {
            foreach ($form->formvals[$fieldname]['options'] as $option)
            {
                $titles[$fieldname][strval($option['value'])] = $option['title'];
            }
        }
    }
    $value = isset($values[$fieldname]) ? strval($values[$fieldname]) : "";
    // an empty link stays empty: records without a code (springs of
    // Liguria) would otherwise give their name to every empty field
    if (trim($value) === "")
        return "";
    $title = isset($titles[$fieldname][$value]) ? $titles[$fieldname][$value] : $value;
    return htmlspecialchars($title, ENT_QUOTES, "UTF-8", false);
}

/**
 * Colour class of an arrow: water, air_summer (cold air) or air_winter.
 * An air tracing without regime counts as summer.
 *
 * @param array $tracing
 * @return string
 */
function openkis_TracingKind($tracing)
{
    if (isset($tracing['type']) && $tracing['type'] == "air")
    {
        return (isset($tracing['air_regime']) && $tracing['air_regime'] == "winter") ? "air_winter" : "air_summer";
    }
    return "water";
}

/**
 * Start and end of the arrows of a list of tracings, using only the caves,
 * springs and uncatalogued cavities the current user can see: a tracing that
 * touches one hidden to the user is left out, even when its points have free
 * coordinates. Free coordinates replace the entrance of the cave, spring or
 * uncatalogued cavity (a point inside the cave, a resurgence in a stream bed)
 * and are the only position of a point that is not linked to a record.
 *
 * @param array $tracings records of ctl_tracings
 * @return array one item per drawable tracing: tracing, from, to; from/to are
 *               array(lat, lon, elevation, code, name, mod, id, free), free
 *               true when the position comes from the free coordinates
 */
function openkis_TracingsArrows($tracings)
{
    global $_FN;
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    $link_fields = array("cave_injection" => "caves", "cave_detection" => "caves", "spring_detection" => "springs",
        "uncatalogued_injection" => "uncatalogued", "uncatalogued_detection" => "uncatalogued");
    $codes = array("caves" => array(), "springs" => array(), "uncatalogued" => array());
    foreach ($tracings as $tracing)
    {
        foreach ($link_fields as $field => $mod)
        {
            if (!empty($tracing[$field]))
                $codes[$mod][trim($tracing[$field])] = trim($tracing[$field]);
        }
    }
    // the visible records only: FNDBVIEW applies the per-record permissions
    $points = array("caves" => array(), "springs" => array(), "uncatalogued" => array());
    foreach ($codes as $mod => $list)
    {
        if (count($list) == 0 || !file_exists("sections/$mod"))
            continue;
        if ($mod != "caves" && !FN_UserCanViewSection($mod))
            continue;
        $params = array();
        $params['fields'] = "id,code,name,latitude,longitude,elevation";
        if ($mod == "uncatalogued")
        {
            // no map endpoint keeps their WGS84 columns up to date (only their
            // sheet does): the position is computed here from the typed ones
            $params['fields'] .= ",latitude_txt,longitude_txt,coordinates_type";
        }
        else
        {
            openkis_UpdateCoords($mod);
        }
        $config = FN_LoadConfig("modules/dbview/config.php", $mod);
        $dbview = new FNDBVIEW($config);
        $quoted = array();
        foreach ($list as $code)
        {
            $quoted[] = "'" . addslashes($code) . "'";
        }
        $params['appendquery'] = "code IN (" . implode(",", $quoted) . ")";
        $rows = $dbview->GetResults($config, $params);
        if (!is_array($rows))
            continue;
        foreach ($rows as $row)
        {
            if ($mod == "uncatalogued")
            {
                $position = openkis_GetItemPosition($row);
                $row['latitude'] = $position['lat'];
                $row['longitude'] = $position['lon'];
            }
            $points[$mod][trim($row['code'])] = array(
                "lat" => floatval($row['latitude']),
                "lon" => floatval($row['longitude']),
                "elevation" => isset($row['elevation']) ? $row['elevation'] : "",
                "code" => $row['code'],
                "name" => $row['name'],
                "mod" => $mod,
                "id" => $row['id'],
                "free" => false);
        }
    }
    $arrows = array();
    foreach ($tracings as $tracing)
    {
        list($injection, $injection_mod) = openkis_TracingEnd($tracing, "injection");
        list($detection, $detection_mod) = openkis_TracingEnd($tracing, "detection");
        // a linked cave or uncatalogued cavity the user cannot see hides the
        // whole tracing, even when it is not the point used for the arrow
        $hidden = false;
        foreach ($link_fields as $field => $mod)
        {
            $code = !empty($tracing[$field]) ? trim($tracing[$field]) : "";
            if ($code !== "" && $mod != "springs" && !isset($points[$mod][$code]))
                $hidden = true;
        }
        // a spring only when it is the detection point (springs hidden to the user)
        if ($hidden || ($detection_mod == "springs" && !isset($points["springs"][$detection])))
            continue;
        // the linked record of each point, or an empty point
        $none = array("lat" => 0, "lon" => 0, "elevation" => "", "code" => "", "name" => "", "mod" => "", "id" => "", "free" => false);
        $from = ($injection !== "") ? $points[$injection_mod][$injection] : $none;
        $to = ($detection !== "") ? $points[$detection_mod][$detection] : $none;
        // free coordinates (computed WGS84) replace the entrance, with the
        // elevation given for the point
        if (floatval($tracing['latitude']) != 0 && floatval($tracing['longitude']) != 0)
        {
            $from['lat'] = floatval($tracing['latitude']);
            $from['lon'] = floatval($tracing['longitude']);
            $from['elevation'] = isset($tracing['elevation']) ? $tracing['elevation'] : "";
            $from['free'] = true;
        }
        if (isset($tracing['detection_latitude']) && floatval($tracing['detection_latitude']) != 0 && floatval($tracing['detection_longitude']) != 0)
        {
            $to['lat'] = floatval($tracing['detection_latitude']);
            $to['lon'] = floatval($tracing['detection_longitude']);
            $to['elevation'] = isset($tracing['detection_elevation']) ? $tracing['detection_elevation'] : "";
            $to['free'] = true;
        }
        if ($from['lat'] == 0 || $to['lat'] == 0)
            continue;
        // a tracing inside one cave without the coordinates of its points
        // (from a branch to another) has no arrow
        if ($from['lat'] == $to['lat'] && $from['lon'] == $to['lon'])
            continue;
        $arrows[] = array("tracing" => $tracing, "from" => $from, "to" => $to);
    }
    return $arrows;
}

/**
 * Card listing the tracings that start or end in a cave, for the cave sheet.
 *
 * @param string $code cave code
 * @return string html, empty when the cave has no tracing visible to the user
 */
function openkis_TracingsCaveCard($code)
{
    return openkis_TracingsPointCard($code, "cave");
}

/**
 * Card listing the tracings detected at a spring, for the spring sheet.
 *
 * @param string $code spring code
 * @return string html, empty when the spring has no tracing visible to the user
 */
function openkis_TracingsSpringCard($code)
{
    return openkis_TracingsPointCard($code, "spring");
}

/**
 * Card listing the tracings that start or end in an uncatalogued cavity, for
 * its sheet.
 *
 * @param string $code code of the uncatalogued cavity
 * @return string html, empty when the cavity has no tracing visible to the user
 */
function openkis_TracingsUncataloguedCard($code)
{
    return openkis_TracingsPointCard($code, "uncatalogued");
}

/**
 * Card listing the tracings of a cave or of an uncatalogued cavity (injection
 * or detection) or of a spring (detection), as the other list cards of the
 * sheets.
 *
 * @param string $code cave, spring or uncatalogued cavity code
 * @param string $point "cave", "spring" or "uncatalogued"
 * @return string html, empty when there is no tracing visible to the user
 */
function openkis_TracingsPointCard($code, $point)
{
    global $_FN;
    if ($code === "" || !file_exists("sections/tracings") || !FN_XMDBTable("ctl_tracings") || !FN_UserCanViewSection("tracings"))
        return "";
    $uncatalogued = openkis_TracingsHasUncatalogued();
    if ($point == "uncatalogued" && !$uncatalogued)
        return "";
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    openkis_UpdateTracings();
    $config = FN_LoadConfig("modules/dbview/config.php", "tracings");
    $dbview = new FNDBVIEW($config);
    $code_sql = addslashes($code);
    $params = array();
    $params['fields'] = "id,name,type,air_regime,release_date,cave_injection,cave_detection,spring_detection,result";
    if ($uncatalogued)
    {
        $params['fields'] .= ",uncatalogued_injection,uncatalogued_detection";
    }
    if ($point == "spring")
    {
        $params['appendquery'] = "spring_detection LIKE '$code_sql'";
    }
    elseif ($point == "uncatalogued")
    {
        $params['appendquery'] = "(uncatalogued_injection LIKE '$code_sql' OR uncatalogued_detection LIKE '$code_sql')";
    }
    else
    {
        $params['appendquery'] = "(cave_injection LIKE '$code_sql' OR cave_detection LIKE '$code_sql')";
    }
    $tracings = $dbview->GetResults($config, $params);
    if (!is_array($tracings) || count($tracings) == 0)
        return "";
    $tracings = FN_ArraySortByKey($tracings, "release_date");
    $kinds = array(
        "water" => array("#1e9be9", FN_Translate("water")),
        "air_summer" => array("#0b2a80", FN_Translate("summer air (cold air)")),
        "air_winter" => array("#f07d00", FN_Translate("winter air")));
    $rows = array();
    foreach ($tracings as $tracing)
    {
        $kind = $kinds[openkis_TracingKind($tracing)];
        $url = FN_RewriteLink("index.php?mod=tracings&op=view&id={$tracing['id']}");
        $date = !empty($tracing['release_date']) ? date("d/m/Y", strtotime($tracing['release_date'])) : "";
        // arriving here: where it comes from; leaving from here: where it goes
        if ($point == "spring")
            $arriving = true;
        elseif ($point == "uncatalogued")
            $arriving = (trim(strval($tracing['uncatalogued_detection'])) == $code);
        else
            $arriving = (trim(strval($tracing['cave_detection'])) == $code);
        list($other) = openkis_TracingEnd($tracing, $arriving ? "injection" : "detection");
        $direction = ($arriving ? "&larr; " : "&rarr; ") . htmlspecialchars($other !== "" ? $other : FN_Translate("free coordinates"));
        $result = !empty($tracing['result']) ? FN_Translate($tracing['result']) : "";
        $rows[] = array($date,
            "<span style=\"display:inline-block;width:12px;height:12px;border-radius:2px;background:{$kind[0]};vertical-align:middle\"></span> {$kind[1]}",
            "<a href=\"$url\">" . htmlspecialchars($tracing['name']) . "</a>", $direction, $result, openkis_SheetViewButton($url));
    }
    $head = array(array(FN_Translate("date"), "text-nowrap"), array(FN_Translate("type"), "text-nowrap"), FN_Translate("name"),
        array(FN_Translate($point == "spring" ? "from" : "from / to"), "text-nowrap"), FN_Translate("result"), array("", "text-right text-end"));
    // bs_map.htm: tracing_cave, tracing_spring, tracing_uncatalogued
    $map = "{$_FN['siteurl']}bs_map.htm?tracing_$point=" . urlencode($code);
    $footer = "<a class=\"btn btn-sm btn-outline-secondary\" target=\"_blank\" href=\"$map\">" . FN_Translate("show on the map") . "</a>";
    // on phones the card of a row starts with the date and the name of the test
    return openkis_SheetListCard(FN_Translate("tracer tests"), $head, $rows, array("footer" => $footer, "id" => "card_tracings", "stack_title" => array(0, 2)));
}
