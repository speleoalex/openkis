<?php

/**
 * Tracer tests (section "tracings", table ctl_tracings).
 *
 * A record is one arrow: from the injection point (free coordinates, or the
 * entrance of the injection cave when they are empty) to the detection point
 * (a cave or a spring). Several detection points of the same campaign are
 * separate records sharing the same code.
 */
// list cards of the sheets
require_once __DIR__ . "/openkis_sheet.inc.php";
defined('_FNEXEC') or die('Restricted access');

/**
 * Fills the computed fields of ctl_tracings:
 *  - latitude/longitude: WGS84 of the free injection coordinates (empty when
 *    the injection point is taken from the cave);
 *  - air_regime: from the release date when empty (May-October summer,
 *    November-April winter), air tracings only;
 *  - name: "injection -> detection" when empty;
 *  - groupview: the groups allowed to see the restricted caves of the record,
 *    so a tracing never shows a cave that the user cannot see (the section
 *    enables the per-record permissions on this field).
 *
 * Cheap to call on every request: a time stamp file makes it a no-op until
 * ctl_caves or ctl_tracings changes (same pattern as openkis_UpdateCaveSystems).
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
    if (!$forceall && $caves_table->GetLastUpdateTime() < filectime($file_lastUpdate) && $tracings_table->GetLastUpdateTime() < filectime($file_lastUpdate))
    {
        return;
    }
    // the detection coordinates exist only when the installed definition
    // (misc/fndatabase/ctl_tracings.php, never overwritten) has them
    $detection = isset($tracings_table->fields['detection_latitude']);
    $fields = "id,name,type,air_regime,release_date,cave_injection,cave_detection,spring_detection,latitude_txt,longitude_txt,coordinates_type,elevation,latitude,longitude,groupview";
    if ($detection)
    {
        $fields .= ",detection_latitude_txt,detection_longitude_txt,detection_coordinates_type,detection_latitude,detection_longitude";
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
            $from = trim(strval($tracing['cave_injection']));
            $to = trim(strval($tracing['cave_detection'])) !== "" ? trim(strval($tracing['cave_detection'])) : trim(strval($tracing['spring_detection']));
            if ($to === "" && isset($free["detection_"]) && $free["detection_"][0] !== "")
            {
                $to = round(floatval($free["detection_"][0]), 5) . "," . round(floatval($free["detection_"][1]), 5);
            }
            if ($from !== "" || $to !== "")
            {
                $new['name'] = ($from !== "" ? $from : "?") . " -> " . ($to !== "" ? $to : "?");
            }
        }
        //---- visibility from the restricted caves --------------------------->
        $groups = false;
        foreach (array($tracing['cave_injection'], $tracing['cave_detection']) as $code)
        {
            $code = trim(strval($code));
            if ($code === "" || !isset($restricted[$code]))
                continue;
            $groups = ($groups === false) ? $restricted[$code] : array_values(array_intersect($groups, $restricted[$code]));
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
 * Start and end of the arrows of a list of tracings, using only the caves
 * and springs the current user can see: a tracing that touches a cave or a
 * spring hidden to the user is left out, even when its points have free
 * coordinates. Free coordinates replace the entrance of the cave or spring
 * (a point inside the cave, a resurgence in a stream bed) and are the only
 * position of a point that is not in the register.
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
    $codes = array("caves" => array(), "springs" => array());
    foreach ($tracings as $tracing)
    {
        foreach (array("cave_injection", "cave_detection") as $field)
        {
            if (!empty($tracing[$field]))
                $codes["caves"][trim($tracing[$field])] = trim($tracing[$field]);
        }
        if (!empty($tracing['spring_detection']))
            $codes["springs"][trim($tracing['spring_detection'])] = trim($tracing['spring_detection']);
    }
    // the visible records only: FNDBVIEW applies the per-record permissions
    $points = array("caves" => array(), "springs" => array());
    foreach ($codes as $mod => $list)
    {
        if (count($list) == 0 || !file_exists("sections/$mod"))
            continue;
        if ($mod == "springs" && !FN_UserCanViewSection("springs"))
            continue;
        openkis_UpdateCoords($mod);
        $config = FN_LoadConfig("modules/dbview/config.php", $mod);
        $dbview = new FNDBVIEW($config);
        $quoted = array();
        foreach ($list as $code)
        {
            $quoted[] = "'" . addslashes($code) . "'";
        }
        $params = array();
        $params['fields'] = "id,code,name,latitude,longitude,elevation";
        $params['appendquery'] = "code IN (" . implode(",", $quoted) . ")";
        $rows = $dbview->GetResults($config, $params);
        if (!is_array($rows))
            continue;
        foreach ($rows as $row)
        {
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
        $injection = trim(strval($tracing['cave_injection']));
        $detection_cave = trim(strval($tracing['cave_detection']));
        $detection_spring = trim(strval($tracing['spring_detection']));
        // a linked cave or spring the user cannot see hides the whole tracing
        if (($injection !== "" && !isset($points["caves"][$injection])) || ($detection_cave !== "" && !isset($points["caves"][$detection_cave])))
            continue;
        if ($detection_cave === "" && $detection_spring !== "" && !isset($points["springs"][$detection_spring]))
            continue;
        // the linked cave or spring of each point, or an empty point
        $none = array("lat" => 0, "lon" => 0, "elevation" => "", "code" => "", "name" => "", "mod" => "", "id" => "", "free" => false);
        $from = ($injection !== "") ? $points["caves"][$injection] : $none;
        if ($detection_cave !== "")
        {
            $to = $points["caves"][$detection_cave];
        }
        elseif ($detection_spring !== "")
        {
            $to = $points["springs"][$detection_spring];
        }
        else
        {
            $to = $none;
        }
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
 * Card listing the tracings of a cave (injection or detection) or of a spring
 * (detection), as the other list cards of the sheets.
 *
 * @param string $code cave or spring code
 * @param string $point "cave" or "spring"
 * @return string html, empty when there is no tracing visible to the user
 */
function openkis_TracingsPointCard($code, $point)
{
    global $_FN;
    if ($code === "" || !file_exists("sections/tracings") || !FN_XMDBTable("ctl_tracings") || !FN_UserCanViewSection("tracings"))
        return "";
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    openkis_UpdateTracings();
    $config = FN_LoadConfig("modules/dbview/config.php", "tracings");
    $dbview = new FNDBVIEW($config);
    $code_sql = addslashes($code);
    $params = array();
    $params['fields'] = "id,name,type,air_regime,release_date,cave_injection,cave_detection,spring_detection,result";
    $params['appendquery'] = ($point == "spring") ? "spring_detection LIKE '$code_sql'" : "(cave_injection LIKE '$code_sql' OR cave_detection LIKE '$code_sql')";
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
        if ($point == "spring" || $tracing['cave_detection'] == $code)
        {
            $direction = "&larr; " . htmlspecialchars($tracing['cave_injection'] !== "" ? $tracing['cave_injection'] : FN_Translate("free coordinates"));
        }
        else
        {
            $target = $tracing['cave_detection'] !== "" ? $tracing['cave_detection'] : $tracing['spring_detection'];
            $direction = "&rarr; " . htmlspecialchars($target !== "" ? $target : FN_Translate("free coordinates"));
        }
        $result = !empty($tracing['result']) ? FN_Translate($tracing['result']) : "";
        $rows[] = array($date,
            "<span style=\"display:inline-block;width:12px;height:12px;border-radius:2px;background:{$kind[0]};vertical-align:middle\"></span> {$kind[1]}",
            "<a href=\"$url\">" . htmlspecialchars($tracing['name']) . "</a>", $direction, $result, openkis_SheetViewButton($url));
    }
    $head = array(array(FN_Translate("date"), "text-nowrap"), array(FN_Translate("type"), "text-nowrap"), FN_Translate("name"),
        array(FN_Translate($point == "spring" ? "from" : "from / to"), "text-nowrap"), FN_Translate("result"), array("", "text-right text-end"));
    $map = "{$_FN['siteurl']}bs_map.htm?tracing_$point=" . urlencode($code);
    $footer = "<a class=\"btn btn-sm btn-outline-secondary\" target=\"_blank\" href=\"$map\">" . FN_Translate("show on the map") . "</a>";
    return openkis_SheetListCard(FN_Translate("tracer tests"), $head, $rows, array("footer" => $footer, "id" => "card_tracings"));
}
