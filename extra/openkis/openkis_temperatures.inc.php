<?php

/**
 * Underground temperatures (section "temperatures", table ctl_temperatures).
 *
 * One record is a measurement point in a cave with either one reading (type
 * "spot") or the summary of a data logger series (type "monitoring"), after
 * the two data sheets of the UCC project (Underground Climate Change,
 * https://www.progettoucc.it): "misure spot" and "monitoraggi"; or the
 * chemical and physical parameters of a water sample (type "analysis"), which
 * goes to the UCC export and to the map only when it has a temperature. The
 * record takes the cave or the spring from the register (coordinates,
 * municipality, karst area, entrance elevation) or free coordinates for a site
 * that is not in it.
 * See docs/TEMPERATURES.md.
 */
defined('_FNEXEC') or die('Restricted access');
// list cards of the sheets
require_once __DIR__ . "/openkis_sheet.inc.php";

/**
 * The fields of a list that the installed definition of ctl_temperatures has:
 * misc/fndatabase/ctl_temperatures.php is never overwritten by the installer,
 * so an older install lacks the fields added later (spring, water analysis).
 *
 * @param string $fields comma separated
 * @return string comma separated, in the same order
 */
function openkis_TemperaturesFields($fields)
{
    $table = FN_XMDBTable("ctl_temperatures");
    $installed = array();
    foreach (explode(",", $fields) as $field)
    {
        if (is_object($table) && isset($table->fields[$field]))
            $installed[] = $field;
    }
    return implode(",", $installed);
}

/**
 * Whether a record is a water analysis without a temperature: it stays out of
 * the UCC export and of the map, whose colour is the temperature.
 *
 * @param array $record
 * @return bool
 */
function openkis_TemperatureIsAnalysisOnly($record)
{
    return isset($record['type']) && $record['type'] == "analysis" && openkis_TemperatureValue($record) === "";
}

/**
 * Fills the computed fields of ctl_temperatures:
 *  - latitude/longitude: WGS84 of the free coordinates (empty when the point
 *    is taken from the cave);
 *  - sort_date: "yyyy-mm-dd" of the spot measurement (unknown month or day
 *    become 01) or the start of the monitoring, for the list order;
 *  - name: "cave - position - date" when empty;
 *  - groupview: the groups allowed to see the cave when it is restricted, so
 *    a measurement never shows a cave that the user cannot see (the section
 *    enables the per-record permissions on this field).
 *
 * Cheap to call on every request: a time stamp file makes it a no-op until
 * ctl_caves or ctl_temperatures changes (same pattern as openkis_UpdateTracings).
 *
 * @param bool $forceall recompute even when the tables did not change
 * @return void
 */
function openkis_UpdateTemperatures($forceall = false)
{
    global $_FN;
    $table = FN_XMDBTable("ctl_temperatures");
    $caves_table = FN_XMDBTable("ctl_caves");
    if (!is_object($table) || !is_object($caves_table) || !isset($table->fields['groupview']))
        return;
    $file_lastUpdate = "{$_FN['datadir']}/_cache/openkis_UpdateTemperatures.time";
    if (!file_exists($file_lastUpdate))
    {
        $fp = @fopen($file_lastUpdate, "a");
        if (is_resource($fp))
            fclose($fp);
        $forceall = true;
    }
    clearstatcache();
    if (!$forceall && $caves_table->GetLastUpdateTime() < filectime($file_lastUpdate) && $table->GetLastUpdateTime() < filectime($file_lastUpdate))
    {
        return;
    }
    $records = FN_XMETADBQuery("SELECT " . openkis_TemperaturesFields("id,name,type,cave,spring,position,year,month,day,hour,start_date,end_date,latitude_txt,longitude_txt,coordinates_type,latitude,longitude,sort_date,groupview") . " FROM ctl_temperatures");
    if (!is_array($records) || count($records) == 0)
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
    foreach ($records as $record)
    {
        $new = array();
        //---- free coordinates ----------------------------------------------->
        $lat = "";
        $lon = "";
        if (trim(strval($record['latitude_txt'])) !== "" && trim(strval($record['longitude_txt'])) !== "")
        {
            $position = openkis_GetItemPosition(array(
                "code" => "temperature {$record['id']}",
                "latitude_txt" => $record['latitude_txt'],
                "longitude_txt" => $record['longitude_txt'],
                "coordinates_type" => $record['coordinates_type'],
                "latitude" => "",
                "longitude" => "",
                "elevation" => ""));
            if (floatval($position['lat']) != 0 && floatval($position['lon']) != 0)
            {
                $lat = $position['lat'];
                $lon = $position['lon'];
            }
        }
        // FLOAT columns: compare with a tolerance (about 1 m), or every pass
        // would rewrite the record and mark the table as changed again
        $stored = (floatval($record['latitude']) != 0 && floatval($record['longitude']) != 0);
        if ($lat === "" ? $stored : (!$stored || abs(floatval($record['latitude']) - floatval($lat)) > 0.00001 || abs(floatval($record['longitude']) - floatval($lon)) > 0.00001))
        {
            $new['latitude'] = $lat;
            $new['longitude'] = $lon;
            $new['coordnatesupdated'] = FN_Now();
        }
        //---- date for the list order ---------------------------------------->
        $sort_date = openkis_TemperatureSortDate($record);
        if (strval($record['sort_date']) !== $sort_date)
        {
            $new['sort_date'] = $sort_date;
        }
        //---- default name --------------------------------------------------->
        if (trim(strval($record['name'])) === "")
        {
            $parts = array();
            if (trim(strval($record['cave'])) !== "")
                $parts[] = trim($record['cave']);
            elseif (!empty($record['spring']))
                $parts[] = trim($record['spring']);
            if (trim(strval($record['position'])) !== "")
                $parts[] = trim($record['position']);
            $date = openkis_TemperatureDateLabel($record);
            if ($date !== "")
                $parts[] = $date;
            if (count($parts) > 0)
            {
                $new['name'] = implode(" - ", $parts);
            }
        }
        //---- visibility from the cave --------------------------------------->
        $code = trim(strval($record['cave']));
        $groupview = ($code !== "" && isset($restricted[$code])) ? implode(",", $restricted[$code]) : "";
        if ($code !== "" && isset($restricted[$code]) && $groupview === "")
        {
            $config = FN_LoadConfig("modules/dbview/config.php", "temperatures");
            $groupview = empty($config['groupadmin']) ? "w_caves" : $config['groupadmin'];
        }
        if (strval($record['groupview']) !== $groupview)
        {
            $new['groupview'] = $groupview;
        }
        if (count($new) > 0)
        {
            $table->UpdateRecordBypk($new, "id", $record['id']);
        }
    }
    @touch($file_lastUpdate);
}

/**
 * dbview callback (function_on_insert / function_on_update of the section
 * "temperatures"): fills the computed fields right after the save.
 *
 * @param array $record the saved record (not used)
 * @return void
 */
function openkis_OnSaveTemperature($record)
{
    openkis_UpdateTemperatures(true);
}

$_FN['modparams']['temperatures']['editorparams']['table']['function_on_update'] = "openkis_OnSaveTemperature";
$_FN['modparams']['temperatures']['editorparams']['table']['function_on_insert'] = "openkis_OnSaveTemperature";

/**
 * "yyyy-mm-dd" of a record: the spot date (unknown month or day become 01,
 * an unknown year gives "") or the start of the monitoring.
 *
 * @param array $record
 * @return string
 */
function openkis_TemperatureSortDate($record)
{
    if (isset($record['type']) && $record['type'] == "monitoring")
    {
        $start = trim(strval(isset($record['start_date']) ? $record['start_date'] : ""));
        return ($start !== "" && $start !== "0000-00-00" && $start !== "0000-00-00 00:00:00") ? substr($start, 0, 10) : "";
    }
    $year = intval(isset($record['year']) ? $record['year'] : 0);
    if ($year <= 0)
        return "";
    $month = intval(isset($record['month']) ? $record['month'] : 0);
    $day = intval(isset($record['day']) ? $record['day'] : 0);
    return sprintf("%04d-%02d-%02d", $year, ($month >= 1 && $month <= 12) ? $month : 1, ($day >= 1 && $day <= 31) ? $day : 1);
}

/**
 * Readable date of a record: "dd/mm/yyyy hh:mm" with the known parts only for
 * a spot measurement ("7/1929", "1962"), "start - end" for a monitoring (the
 * end is "running" when empty).
 *
 * @param array $record
 * @return string
 */
function openkis_TemperatureDateLabel($record)
{
    $empty = function ($date) {
        $date = trim(strval($date));
        return ($date === "" || substr($date, 0, 10) === "0000-00-00");
    };
    if (isset($record['type']) && $record['type'] == "monitoring")
    {
        $start = $empty(isset($record['start_date']) ? $record['start_date'] : "") ? "" : date("d/m/Y", strtotime($record['start_date']));
        $end = $empty(isset($record['end_date']) ? $record['end_date'] : "") ? "" : date("d/m/Y", strtotime($record['end_date']));
        if ($start === "" && $end === "")
            return "";
        return $start . " - " . ($end !== "" ? $end : FN_Translate("running"));
    }
    $year = intval(isset($record['year']) ? $record['year'] : 0);
    if ($year <= 0)
        return "";
    $month = intval(isset($record['month']) ? $record['month'] : 0);
    $day = intval(isset($record['day']) ? $record['day'] : 0);
    $label = ($month >= 1 && $month <= 12) ? (($day >= 1 && $day <= 31 ? "$day/" : "") . "$month/$year") : "$year";
    if (!empty($record['hour']))
    {
        $label .= " " . trim($record['hour']);
    }
    return $label;
}

/**
 * The reading of a record: the temperature of a spot measurement, the mean
 * of a monitoring; "" when unknown.
 *
 * @param array $record
 * @return string number as stored ("7.2")
 */
function openkis_TemperatureValue($record)
{
    $field = (isset($record['type']) && $record['type'] == "monitoring") ? "t_mean" : "temperature";
    $value = isset($record[$field]) ? trim(str_replace(",", ".", strval($record[$field]))) : "";
    return is_numeric($value) ? $value : "";
}

/**
 * frm_functionview of the option and foreign key fields of ctl_temperatures:
 * the title of the stored value (translated option, "code name" of the cave).
 * The list cards need it: the radio, select and selectfilter widgets have no
 * view of their own there and would show the raw value.
 *
 * @param array $values the record
 * @param string $fieldname
 * @return string html
 */
function openkis_TemperatureViewValue($values, $fieldname)
{
    static $titles = array();
    if (!isset($titles[$fieldname]))
    {
        $titles[$fieldname] = array();
        $form = FN_XMDBForm("ctl_temperatures");
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
 * frm_functionview of sort_date: the readable date of the record in the list
 * cards ("7/1929", "1/1/1962 - 31/12/1986") instead of the computed
 * "yyyy-mm-dd", whose month and day are 01 when unknown.
 *
 * @param array $values the record
 * @param string $fieldname
 * @return string html
 */
function openkis_TemperatureViewDate($values, $fieldname)
{
    return htmlspecialchars(openkis_TemperatureDateLabel($values), ENT_QUOTES, "UTF-8", false);
}

/**
 * Names of the karst areas by code (ctl_areas), read once per request.
 *
 * @return array code => name
 */
function openkis_TemperatureAreaNames()
{
    static $names = null;
    if ($names === null)
    {
        $names = array();
        if (FN_XMDBTable("ctl_areas"))
        {
            $rows = FN_XMETADBQuery("SELECT code,name FROM ctl_areas");
            if (is_array($rows))
            {
                foreach ($rows as $row)
                {
                    $names[trim(strval($row['code']))] = $row['name'];
                }
            }
        }
    }
    return $names;
}

/**
 * Measurement points of a list of records, using only the caves the current
 * user can see: a record of a cave hidden to the user is left out, even when
 * it has free coordinates. A record without a cave takes the point of its
 * spring. The free coordinates, when present, replace the cave entrance or
 * the spring (a point deep in the cave, or a site not in the register).
 *
 * @param array $records records of ctl_temperatures
 * @return array one item per drawable record: record, point; point is
 *               array(lat, lon, elevation, code, name, mod, id, comune, areas, key)
 */
function openkis_TemperaturesPoints($records)
{
    global $_FN;
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    $codes = array();
    foreach ($records as $record)
    {
        if (!empty($record['cave']))
            $codes[trim($record['cave'])] = trim($record['cave']);
    }
    $caves = array();
    if (count($codes) > 0 && file_exists("sections/caves"))
    {
        // the visible records only: FNDBVIEW applies the per-record permissions
        openkis_UpdateCoords("caves");
        $config = FN_LoadConfig("modules/dbview/config.php", "caves");
        $dbview = new FNDBVIEW($config);
        $quoted = array();
        foreach ($codes as $code)
        {
            $quoted[] = "'" . addslashes($code) . "'";
        }
        $params = array();
        $params['fields'] = "id,code,name,latitude,longitude,elevation,comune,areas";
        $params['appendquery'] = "code IN (" . implode(",", $quoted) . ")";
        $rows = $dbview->GetResults($config, $params);
        if (is_array($rows))
        {
            $area_names = openkis_TemperatureAreaNames();
            foreach ($rows as $row)
            {
                $areas = array();
                foreach (array_filter(array_map('trim', explode(",", strval($row['areas'])))) as $area)
                {
                    $areas[] = isset($area_names[$area]) ? $area_names[$area] : $area;
                }
                $caves[trim($row['code'])] = array(
                    "lat" => floatval($row['latitude']),
                    "lon" => floatval($row['longitude']),
                    "elevation" => isset($row['elevation']) ? $row['elevation'] : "",
                    "code" => $row['code'],
                    "name" => $row['name'],
                    "mod" => "caves",
                    "id" => $row['id'],
                    "comune" => isset($row['comune']) ? $row['comune'] : "",
                    "areas" => implode(", ", $areas));
            }
        }
    }
    $springs = array();
    $spring_codes = array();
    foreach ($records as $record)
    {
        if (trim(strval($record['cave'])) === "" && !empty($record['spring']))
            $spring_codes[] = trim($record['spring']);
    }
    if (count($spring_codes) > 0 && file_exists("sections/springs"))
    {
        foreach (openkis_SheetRecordsByCode("springs", $spring_codes, "id,code,name,latitude,longitude,elevation,comune") as $row)
        {
            $springs[trim($row['code'])] = array(
                "lat" => floatval($row['latitude']),
                "lon" => floatval($row['longitude']),
                "elevation" => isset($row['elevation']) ? $row['elevation'] : "",
                "code" => $row['code'],
                "name" => $row['name'],
                "mod" => "springs",
                "id" => $row['id'],
                "comune" => isset($row['comune']) ? $row['comune'] : "",
                "areas" => "");
        }
    }
    $items = array();
    foreach ($records as $record)
    {
        $code = trim(strval($record['cave']));
        // a cave the user cannot see hides the measurement
        if ($code !== "" && !isset($caves[$code]))
            continue;
        $spring = ($code === "" && !empty($record['spring'])) ? trim($record['spring']) : "";
        $point = array("lat" => 0, "lon" => 0, "elevation" => "", "code" => "", "name" => "", "mod" => "", "id" => "", "comune" => "", "areas" => "");
        if ($code !== "")
            $point = $caves[$code];
        elseif ($spring !== "" && isset($springs[$spring]))
            $point = $springs[$spring];
        if (floatval($record['latitude']) != 0 && floatval($record['longitude']) != 0)
        {
            $point['lat'] = floatval($record['latitude']);
            $point['lon'] = floatval($record['longitude']);
        }
        if ($point['lat'] == 0 || $point['lon'] == 0)
            continue;
        // the points of one cave entrance share a marker on the map
        $point['key'] = (floatval($record['latitude']) == 0 && $point['code'] !== "") ? "{$point['mod']}:{$point['code']}" : "pos:" . round($point['lat'], 5) . "," . round($point['lon'], 5);
        $items[] = array("record" => $record, "point" => $point);
    }
    return $items;
}

/**
 * Measurement point of one record (see openkis_TemperaturesPoints).
 *
 * @param array $record
 * @return array|false
 */
function openkis_TemperaturePoint($record)
{
    $items = openkis_TemperaturesPoints(array($record));
    return count($items) > 0 ? $items[0]['point'] : false;
}

/**
 * Colour of a temperature on the map (bs_map/js/OPS_Map.js temperatureColors
 * has the same classes).
 *
 * @param string $value
 * @return string class: cold, cool, mild, warm, hot or unknown
 */
function openkis_TemperatureClass($value)
{
    if ($value === "" || !is_numeric($value))
        return "unknown";
    $value = floatval($value);
    if ($value < 4)
        return "cold";
    if ($value < 8)
        return "cool";
    if ($value < 12)
        return "mild";
    if ($value < 16)
        return "warm";
    return "hot";
}

/**
 * Small table of a list of records (date, type, medium, position, reading)
 * for the cave sheet card and the map popup.
 *
 * @param array $records records of ctl_temperatures, visible to the user
 * @param bool $links with a link to the record (false in the popup, which
 *                    opens the records in the "r" window)
 * @param bool $head with the column titles (the list card of the cave sheet)
 * @param array|null $caves with a column of the cave (the list card of the
 *                          bibliography sheet): the caves by code (id, code, name)
 * @return string html
 */
function openkis_TemperaturesTable($records, $links = true, $head = false, $caves = null)
{
    // with the column titles (sheet cards) the themes show the rows as cards
    // on phones: date, cave and reading on the first line, see openkis_SheetStackAttrs()
    $label = array("date" => openkis_SheetUcfirst(FN_Translate("date")), "cave" => openkis_SheetUcfirst(FN_Translate("cave")),
        "type" => openkis_SheetUcfirst(FN_Translate("type")), "medium" => openkis_SheetUcfirst(FN_Translate("measured medium")),
        "position" => openkis_SheetUcfirst(FN_Translate("measurement point")), "reading" => openkis_SheetUcfirst(FN_Translate("temperature")));
    // the column also holds pH and conductivity of the water analyses
    if (in_array("analysis", array_column($records, "type")))
    {
        $label['reading'] = openkis_SheetUcfirst(FN_Translate("values"));
    }
    $td = function ($column, $class = "") use ($head, $label)
    {
        if (!$head)
            return $class != "" ? " class=\"$class\"" : "";
        return openkis_SheetStackAttrs($label[$column], in_array($column, array("date", "cave", "reading")), $class);
    };
    $html = "<div class=\"table-responsive no-more-tables\"><table class=\"table table-sm table-striped mb-0" . ($head ? " openkis-table-stack" : "") . "\">";
    if ($head)
    {
        $html .= "<thead><tr><th class=\"text-nowrap\">{$label['date']}</th>";
        if (is_array($caves))
        {
            $html .= "<th>{$label['cave']}</th>";
        }
        $html .= "<th class=\"text-nowrap\">{$label['type']}</th>";
        $html .= "<th>{$label['medium']}</th><th>{$label['position']}</th>";
        $html .= "<th class=\"text-nowrap\">{$label['reading']}</th>" . ($links ? "<th></th>" : "") . "</tr></thead>";
    }
    foreach ($records as $record)
    {
        $url = FN_RewriteLink("index.php?mod=temperatures&op=view&id={$record['id']}");
        $date = htmlspecialchars(openkis_TemperatureDateLabel($record));
        $types = array("monitoring" => "monitoring", "analysis" => "water analysis");
        $type = FN_Translate(isset($types[$record['type']]) ? $types[$record['type']] : "spot measurement");
        $medium = FN_Translate(in_array($record['medium'], array("water", "rock")) ? $record['medium'] : "air");
        $value = openkis_TemperatureValue($record);
        $reading = ($value !== "") ? str_replace(".", ",", $value) . " °C" : "";
        if ($record['type'] == "monitoring" && $value !== "")
        {
            $reading = FN_Translate("mean") . " " . $reading;
        }
        if ($record['type'] == "analysis")
        {
            // the main parameters of the analysis after the temperature
            $parameters = array();
            if ($reading !== "")
                $parameters[] = $reading;
            if (isset($record['ph']) && trim(strval($record['ph'])) !== "")
                $parameters[] = "pH " . htmlspecialchars(trim($record['ph']));
            if (isset($record['conductivity']) && trim(strval($record['conductivity'])) !== "")
                $parameters[] = htmlspecialchars(trim($record['conductivity'])) . " µS/cm";
            $reading = implode(", ", $parameters);
        }
        $html .= "<tr><td" . $td("date", "text-nowrap") . ">$date</td>";
        if (is_array($caves))
        {
            $code = trim(strval($record['cave']));
            $cave = "";
            if ($code !== "" && isset($caves[$code]))
            {
                $cave_url = FN_RewriteLink("index.php?mod=caves&op=view&id={$caves[$code]['id']}");
                $cave = "<span class=\"text-nowrap\">" . htmlspecialchars($caves[$code]['code']) . "</span> <a href=\"$cave_url\">" . htmlspecialchars($caves[$code]['name']) . "</a>";
            }
            elseif ($code === "" && !empty($record['spring']))
            {
                $cave = FN_Translate("spring") . " " . htmlspecialchars(trim($record['spring']));
            }
            $html .= "<td" . $td("cave") . ">$cave</td>";
        }
        $html .= "<td" . $td("type", "text-nowrap") . ">$type</td><td" . $td("medium") . ">$medium</td>";
        $html .= "<td" . $td("position") . ">" . htmlspecialchars(strval($record['position'])) . "</td><td" . $td("reading", "text-nowrap") . "><b>$reading</b></td>";
        if ($links)
        {
            $view = openkis_SheetViewButton($url);
            $html .= "<td class=\"{$view[1]}\">{$view[0]}</td>";
        }
        $html .= "</tr>";
    }
    $html .= "</table></div>";
    return $html;
}

/**
 * Card listing the temperature measurements and water analyses of a cave or
 * of a spring, for their sheets and the sheet of a measurement (where the
 * record itself is left out).
 *
 * @param string $code cave or spring code
 * @param int $exclude_id record not to list
 * @param string $field "cave" or "spring"
 * @return string html, empty when there is no measurement visible to the user
 */
function openkis_TemperaturesCaveCard($code, $exclude_id = 0, $field = "cave")
{
    global $_FN;
    if ($code === "" || !file_exists("sections/temperatures") || !FN_XMDBTable("ctl_temperatures") || !FN_UserCanViewSection("temperatures")
        || openkis_TemperaturesFields($field) === "")
        return "";
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    openkis_UpdateTemperatures();
    $config = FN_LoadConfig("modules/dbview/config.php", "temperatures");
    $dbview = new FNDBVIEW($config);
    $params = array();
    $params['fields'] = openkis_TemperaturesFields("id,name,type,medium,position,temperature,t_mean,ph,conductivity,year,month,day,hour,start_date,end_date,sort_date");
    $params['appendquery'] = "$field LIKE '" . addslashes($code) . "'";
    $records = $dbview->GetResults($config, $params);
    if (!is_array($records))
        return "";
    if ($exclude_id > 0)
    {
        $records = array_values(array_filter($records, function ($record) use ($exclude_id) {
            return intval($record['id']) != $exclude_id;
        }));
    }
    if (count($records) == 0)
        return "";
    $records = FN_ArraySortByKey($records, "sort_date");
    $title = ($exclude_id > 0) ? FN_Translate($field == "spring" ? "other measurements of the spring" : "other measurements of the cave") : FN_Translate("temperatures and waters");
    // the map selects the measurements by cave
    $footer = "";
    if ($field == "cave")
    {
        $map = "{$_FN['siteurl']}bs_map.htm?temperature_cave=" . urlencode($code);
        $footer = "<a class=\"btn btn-sm btn-outline-secondary\" target=\"_blank\" href=\"$map\">" . FN_Translate("show on the map") . "</a>";
    }
    return openkis_SheetCard($title, "<div class=\"card-body p-0\">" . openkis_TemperaturesTable($records, true, true) . "</div>",
        array("count" => count($records), "footer" => $footer, "id" => "card_temperatures"));
}

/**
 * Card listing the temperature measurements taken from a publication (field
 * bibliography_ids), for the bibliography sheet: one row per measurement with
 * its cave, sorted by cave and date. A measurement of a cave hidden to the
 * user is left out, as on the map.
 *
 * @param string $id id of the bibliography record
 * @return string html, empty when no measurement visible to the user cites it
 */
function openkis_TemperaturesBibliographyCard($id)
{
    global $_FN;
    $id = trim(strval($id));
    if ($id === "" || !file_exists("sections/temperatures") || !FN_UserCanViewSection("temperatures"))
        return "";
    // installs whose table definition predates the field
    $table = FN_XMDBTable("ctl_temperatures");
    if (!is_object($table) || !isset($table->fields['bibliography_ids']))
        return "";
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    openkis_UpdateTemperatures();
    $config = FN_LoadConfig("modules/dbview/config.php", "temperatures");
    $dbview = new FNDBVIEW($config);
    $q = addslashes($id);
    $params = array();
    $params['fields'] = openkis_TemperaturesFields("id,name,type,medium,cave,spring,position,temperature,t_mean,ph,conductivity,year,month,day,hour,start_date,end_date,sort_date");
    $params['appendquery'] = "(bibliography_ids LIKE '$q' OR bibliography_ids LIKE '$q,%' OR bibliography_ids LIKE '%,$q,%' OR bibliography_ids LIKE '%,$q')";
    $records = $dbview->GetResults($config, $params);
    if (!is_array($records) || count($records) == 0)
        return "";
    $codes = array();
    foreach ($records as $record)
    {
        $codes[] = trim(strval($record['cave']));
    }
    $caves = array();
    foreach (openkis_SheetRecordsByCode("caves", $codes, "id,code,name") as $cave)
    {
        $caves[trim($cave['code'])] = $cave;
    }
    $records = array_values(array_filter($records, function ($record) use ($caves) {
        $code = trim(strval($record['cave']));
        return $code === "" || isset($caves[$code]);
    }));
    if (count($records) == 0)
        return "";
    usort($records, function ($a, $b) {
        $by_cave = strnatcmp(trim(strval($a['cave'])), trim(strval($b['cave'])));
        return $by_cave != 0 ? $by_cave : strcmp(strval($a['sort_date']), strval($b['sort_date']));
    });
    return openkis_SheetCard(FN_Translate("temperatures and waters"), "<div class=\"card-body p-0\">" . openkis_TemperaturesTable($records, true, true, $caves) . "</div>",
        array("count" => count($records), "id" => "card_temperatures"));
}

/**
 * Citations of bibliography records ("AUTHORS, year. Title. Magazine, issue:
 * pages"), read once, for the UCC export of the records whose field
 * bibliography is empty but that link the sheets of their sources.
 * Only the records visible to the user are read.
 *
 * @param array $ids ids of ctl_bibliography
 * @return array id => citation
 */
function openkis_TemperaturesBibliographyCitations($ids)
{
    global $_FN;
    $ids = array_unique(array_filter(array_map("trim", $ids), "strlen"));
    if (!count($ids) || !file_exists("sections/bibliography") || !FN_UserCanViewSection("bibliography"))
        return array();
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    $config = FN_LoadConfig("modules/dbview/config.php", "bibliography");
    $dbview = new FNDBVIEW($config);
    $in = array();
    foreach ($ids as $id)
    {
        $in[] = "'" . addslashes($id) . "'";
    }
    $rows = $dbview->GetResults($config, array("fields" => "id,authors,year,title,magazine,number,pages", "appendquery" => "id IN (" . implode(",", $in) . ")"));
    $citations = array();
    foreach (is_array($rows) ? $rows : array() as $row)
    {
        $clean = function ($field) use ($row) {
            return trim(preg_replace('/\s+/', " ", strip_tags(isset($row[$field]) ? strval($row[$field]) : "")), " ,;");
        };
        $citation = implode(", ", array_filter(array($clean("authors"), $clean("year")), "strlen"));
        foreach (array($clean("title"), implode(", ", array_filter(array($clean("magazine"), $clean("number")), "strlen"))) as $part)
        {
            // the dot of an abbreviation ("ROSSI M.") ends the part too
            if ($part !== "")
                $citation = ($citation !== "" ? rtrim($citation, ".") . ". " : "") . $part;
        }
        $citation = rtrim($citation, ".");
        if ($clean("pages") !== "")
            $citation .= ": " . $clean("pages");
        $citations[strval($row['id'])] = $citation;
    }
    return $citations;
}

/**
 * Links to the exports in the layout of the UCC project sheets (one CSV per
 * sheet: spot measurements, monitorings; both in one spreadsheet).
 *
 * @return string html
 */
function openkis_TemperaturesUccLinks()
{
    global $_FN;
    $html = FN_Translate("export for the UCC project") . ": ";
    $html .= "<a href=\"{$_FN['siteurl']}openkis_temperatures.php?format=ucc&type=spot\">" . FN_Translate("spot measurements") . " (CSV)</a>, ";
    $html .= "<a href=\"{$_FN['siteurl']}openkis_temperatures.php?format=ucc&type=monitoring\">" . FN_Translate("monitorings") . " (CSV)</a>, ";
    $html .= "<a href=\"{$_FN['siteurl']}openkis_temperatures.php?format=ods\">" . FN_Translate("both sheets in a spreadsheet") . " (ODS)</a>";
    return $html;
}

/**
 * Rows of the UCC project sheet of a type, from the visible records: the
 * columns of the sheets "misure spot" (UCC_misure spot T aria v3.3) and
 * "monitoraggi" (UCC_monitoraggi T aria v1.2), in their order and with their
 * Italian headers, so the export can be pasted into the national sheet.
 * Numbers keep the point as decimal separator (the sheets ask for the comma
 * in the cells, which the spreadsheet applies on paste in an Italian locale).
 *
 * @param string $type "spot" or "monitoring"
 * @param array $items output of openkis_TemperaturesPoints()
 * @return array first row the headers, then one row per record
 */
function openkis_TemperaturesUccRows($type, $items)
{
    $rows = array();
    $options = array(
        "ventilation" => array("none" => "non percepibile", "weak" => "debole", "medium" => "media", "strong" => "intensa"),
        "snow_ice_spot" => array("absent" => "no", "present" => "si", "seasonal" => "si", "persistent" => "si"),
        "snow_ice" => array("absent" => "assente", "present" => "presente", "seasonal" => "stagionale", "persistent" => "persistente"),
        "water" => array("absent" => "assente", "films" => "veli parietali", "dripping" => "stillicidio", "stream" => "torrente", "pool" => "lago"),
        "medium" => array("air" => "aria", "water" => "acqua", "rock" => "roccia"));
    $option = function ($list, $value) use ($options) {
        $value = strval($value);
        return isset($options[$list][$value]) ? $options[$list][$value] : $value;
    };
    $number = function ($value) {
        $value = trim(str_replace(",", ".", strval($value)));
        return is_numeric($value) ? $value : "";
    };
    $date = function ($value) {
        $value = trim(strval($value));
        return ($value === "" || substr($value, 0, 10) === "0000-00-00") ? "" : date("d/m/Y", strtotime($value));
    };
    // citation from the bibliography sheets when the field bibliography is empty
    $sheet_ids = array();
    foreach ($items as $item)
    {
        if (trim(strval($item['record']['bibliography'])) === "" && !empty($item['record']['bibliography_ids']))
            $sheet_ids = array_merge($sheet_ids, explode(",", $item['record']['bibliography_ids']));
    }
    $citations = openkis_TemperaturesBibliographyCitations($sheet_ids);
    $bibliography = function ($r) use ($citations) {
        if (trim(strval($r['bibliography'])) !== "" || empty($r['bibliography_ids']))
            return $r['bibliography'];
        $list = array();
        foreach (explode(",", $r['bibliography_ids']) as $id)
        {
            if (isset($citations[trim($id)]))
                $list[] = $citations[trim($id)];
        }
        return implode("; ", $list);
    };
    if ($type == "monitoring")
    {
        $rows[] = array("nome grotta", "num. Catasto", "Long. E (gg.ddddd)", "Lat. N (gg.ddddd)", "area carsica", "comune", "quota ingresso [m s.l.m.]", "posizione", "quota misura [m s.l.m]", "distanza spaziale ingresso [m]", "numero ingressi", "ventilazione", "neve/ghiaccio", "idrologia", "strumentazione", "accuratezza [°C]", "risoluzione [°C]", "inizio misurazione", "fine misurazione", "intervallo acquisizione [min]", "T media [°C]", "T massima [°C]", "T minima [°C]", "T moda [°C]", "T mediana [°C]", "deviazione standard", "detentore dati", "bibliografia", "note", "compilatore");
    }
    else
    {
        $rows[] = array("nome grotta", "num. Catasto", "Long. E (gg,ddddd)", "Lat. E (gg,ddddd)", "area carsica", "comune", "quota ingresso [m s.l.m]", "temperatura °C", "posizione", "quota misura [m s.l.m]", "distanza ingresso", "numero ingressi", "ventilazione", "neve/ghiaccio", "acqua", "strumentazione", "giorno", "mese", "anno", "ora", "bibliografia", "note", "compilatore");
    }
    foreach ($items as $item)
    {
        $r = $item['record'];
        $p = $item['point'];
        if (($type == "monitoring") != ($r['type'] == "monitoring") || openkis_TemperatureIsAnalysisOnly($r))
            continue;
        // the medium goes in the notes: the UCC sheets are for the air
        $notes = trim(strval($r['notes']));
        if ($r['medium'] == "water" || $r['medium'] == "rock")
        {
            $notes = trim("T " . $option("medium", $r['medium']) . ($notes !== "" ? ". " . $notes : ""));
        }
        if ($type != "monitoring" && $number($r['temperature_external']) !== "")
        {
            $notes = trim("T esterna: " . $number($r['temperature_external']) . " °C" . ($notes !== "" ? ". " . $notes : ""));
        }
        $common = array($p['name'], $p['code'], $p['lon'] != 0 ? round($p['lon'], 6) : "", $p['lat'] != 0 ? round($p['lat'], 6) : "", $p['areas'], $p['comune'], $number($p['elevation']));
        if ($type == "monitoring")
        {
            $rows[] = array_merge($common, array(
                $r['position'], $number($r['measure_elevation']), $number($r['entrance_distance']), "",
                $option("ventilation", $r['ventilation']), $option("snow_ice", $r['snow_ice']), $option("water", $r['water']),
                $r['instrument'], $number($r['accuracy']), $number($r['resolution']),
                $date($r['start_date']), ($date($r['end_date']) !== "" ? $date($r['end_date']) : ($date($r['start_date']) !== "" ? "in atto" : "")), $number($r['acq_interval']),
                $number($r['t_mean']), $number($r['t_max']), $number($r['t_min']), $number($r['t_mode']), $number($r['t_median']), $number($r['t_stddev']),
                $r['data_holder'], $bibliography($r), $notes, $r['username']));
        }
        else
        {
            $rows[] = array_merge($common, array(
                $number($r['temperature']), $r['position'], $number($r['measure_elevation']), $number($r['entrance_distance']), "",
                $option("ventilation", $r['ventilation']), $option("snow_ice_spot", $r['snow_ice']), $option("water", $r['water']),
                $r['instrument'], intval($r['day']) > 0 ? intval($r['day']) : "", intval($r['month']) > 0 ? intval($r['month']) : "", intval($r['year']) > 0 ? intval($r['year']) : "", $r['hour'],
                $bibliography($r), $notes, $r['username']));
        }
    }
    return $rows;
}

/**
 * The two UCC sheets in one OpenDocument spreadsheet (the "Format_UCC.ods"
 * file the Liguria referent of the project circulates: sheet MISURE_SPOT and
 * sheet MONITORAGGI with the same columns as the national sheets). Numbers
 * are numeric cells, the monitoring dates are date cells and the hour of a
 * spot reading a time cell, so the spreadsheet shows them in its locale.
 *
 * @param array $items output of openkis_TemperaturesPoints()
 * @return string the .ods file
 */
function openkis_TemperaturesUccOds($items)
{
    $sheets = array(
        "MISURE_SPOT" => array("rows" => openkis_TemperaturesUccRows("spot", $items), "string" => array(0, 1, 8, 12, 13, 14, 15, 20, 21, 22), "date" => array(), "time" => array(19)),
        "MONITORAGGI" => array("rows" => openkis_TemperaturesUccRows("monitoring", $items), "string" => array(0, 1, 7, 11, 12, 13, 14, 26, 27, 28, 29), "date" => array(17, 18), "time" => array()));
    $esc = function ($s) {
        return htmlspecialchars(FN_FixEncoding(strval($s)), ENT_QUOTES | ENT_XML1, "UTF-8");
    };
    $cell = function ($value, $col, $sheet) use ($esc) {
        $value = strval($value);
        if ($value === "")
        {
            return "<table:table-cell/>";
        }
        if (in_array($col, $sheet['date']) && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $m))
        {
            $iso = sprintf("%04d-%02d-%02d", $m[3], $m[2], $m[1]);
            return "<table:table-cell table:style-name=\"ce2\" office:value-type=\"date\" office:date-value=\"$iso\"><text:p>" . $esc($value) . "</text:p></table:table-cell>";
        }
        if (in_array($col, $sheet['time']) && preg_match('/^(\d{1,2})[:.](\d{2})$/', $value, $m))
        {
            return "<table:table-cell table:style-name=\"ce3\" office:value-type=\"time\" office:time-value=\"PT" . intval($m[1]) . "H" . intval($m[2]) . "M0S\"><text:p>" . $esc($value) . "</text:p></table:table-cell>";
        }
        if (!in_array($col, $sheet['string']) && is_numeric($value))
        {
            return "<table:table-cell office:value-type=\"float\" office:value=\"" . $esc($value) . "\"><text:p>" . $esc(str_replace(".", ",", $value)) . "</text:p></table:table-cell>";
        }
        $lines = preg_split('/\r\n|\r|\n/', $value);
        $html = "<table:table-cell office:value-type=\"string\">";
        foreach ($lines as $line)
        {
            $html .= "<text:p>" . $esc($line) . "</text:p>";
        }
        return $html . "</table:table-cell>";
    };
    $body = "";
    foreach ($sheets as $name => $sheet)
    {
        $columns = count($sheet['rows'][0]);
        $body .= "<table:table table:name=\"$name\" table:style-name=\"ta1\">";
        $body .= "<table:table-column table:style-name=\"co1\" table:number-columns-repeated=\"$columns\" table:default-cell-style-name=\"ce1\"/>";
        foreach ($sheet['rows'] as $i => $row)
        {
            $body .= "<table:table-row table:style-name=\"ro1\">";
            foreach ($row as $col => $value)
            {
                if ($i == 0)
                {
                    $body .= "<table:table-cell table:style-name=\"ce4\" office:value-type=\"string\"><text:p>" . $esc($value) . "</text:p></table:table-cell>";
                }
                else
                {
                    $body .= $cell($value, $col, $sheet);
                }
            }
            $body .= "</table:table-row>";
        }
        $body .= "</table:table>";
    }
    $ns = "xmlns:office=\"urn:oasis:names:tc:opendocument:xmlns:office:1.0\" xmlns:style=\"urn:oasis:names:tc:opendocument:xmlns:style:1.0\" xmlns:text=\"urn:oasis:names:tc:opendocument:xmlns:text:1.0\" xmlns:table=\"urn:oasis:names:tc:opendocument:xmlns:table:1.0\" xmlns:fo=\"urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0\" xmlns:number=\"urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0\" xmlns:meta=\"urn:oasis:names:tc:opendocument:xmlns:meta:1.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" office:version=\"1.2\"";
    $content = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<office:document-content $ns>"
            . "<office:automatic-styles>"
            . "<style:style style:name=\"co1\" style:family=\"table-column\"><style:table-column-properties style:column-width=\"2.258cm\"/></style:style>"
            . "<style:style style:name=\"ro1\" style:family=\"table-row\"><style:table-row-properties style:use-optimal-row-height=\"true\"/></style:style>"
            . "<style:style style:name=\"ta1\" style:family=\"table\"><style:table-properties table:display=\"true\"/></style:style>"
            . "<number:date-style style:name=\"N1\"><number:day number:style=\"long\"/><number:text>/</number:text><number:month number:style=\"long\"/><number:text>/</number:text><number:year number:style=\"long\"/></number:date-style>"
            . "<number:time-style style:name=\"N2\"><number:hours number:style=\"long\"/><number:text>:</number:text><number:minutes number:style=\"long\"/></number:time-style>"
            . "<style:style style:name=\"ce1\" style:family=\"table-cell\"><style:table-cell-properties fo:wrap-option=\"wrap\" style:vertical-align=\"top\"/><style:text-properties fo:font-size=\"10pt\"/></style:style>"
            . "<style:style style:name=\"ce2\" style:family=\"table-cell\" style:parent-style-name=\"Default\" style:data-style-name=\"N1\"><style:table-cell-properties fo:wrap-option=\"wrap\" style:vertical-align=\"top\"/><style:text-properties fo:font-size=\"10pt\"/></style:style>"
            . "<style:style style:name=\"ce3\" style:family=\"table-cell\" style:parent-style-name=\"Default\" style:data-style-name=\"N2\"><style:table-cell-properties fo:wrap-option=\"wrap\" style:vertical-align=\"top\"/><style:text-properties fo:font-size=\"10pt\"/></style:style>"
            . "<style:style style:name=\"ce4\" style:family=\"table-cell\"><style:table-cell-properties fo:wrap-option=\"wrap\" fo:background-color=\"#eeeeee\" style:vertical-align=\"top\"/><style:text-properties fo:font-size=\"10pt\" fo:font-weight=\"bold\"/></style:style>"
            . "</office:automatic-styles>"
            . "<office:body><office:spreadsheet>$body</office:spreadsheet></office:body></office:document-content>";
    $meta = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<office:document-meta $ns><office:meta>"
            . "<meta:generator>OpenKIS</meta:generator><dc:title>Format UCC</dc:title>"
            . "<meta:creation-date>" . date("Y-m-d\TH:i:s") . "</meta:creation-date></office:meta></office:document-meta>";
    $manifest = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<manifest:manifest xmlns:manifest=\"urn:oasis:names:tc:opendocument:xmlns:manifest:1.0\" manifest:version=\"1.2\">"
            . "<manifest:file-entry manifest:full-path=\"/\" manifest:version=\"1.2\" manifest:media-type=\"application/vnd.oasis.opendocument.spreadsheet\"/>"
            . "<manifest:file-entry manifest:full-path=\"content.xml\" manifest:media-type=\"text/xml\"/>"
            . "<manifest:file-entry manifest:full-path=\"meta.xml\" manifest:media-type=\"text/xml\"/>"
            . "</manifest:manifest>";
    return openkis_ZipString(array(
        "mimetype" => "application/vnd.oasis.opendocument.spreadsheet",
        "META-INF/manifest.xml" => $manifest,
        "meta.xml" => $meta,
        "content.xml" => $content));
}

/**
 * A zip archive in memory, without the zip extension: the first entry is
 * stored (an OpenDocument package wants its mimetype first and uncompressed),
 * the others are deflated.
 *
 * @param array $files name => content, in the order of the archive
 * @return string the zip file
 */
function openkis_ZipString($files)
{
    $local = "";
    $central = "";
    $count = 0;
    $t = getdate();
    $dostime = ($t['hours'] << 11) | ($t['minutes'] << 5) | intval($t['seconds'] / 2);
    $dosdate = (($t['year'] - 1980) << 9) | ($t['mon'] << 5) | $t['mday'];
    foreach ($files as $name => $data)
    {
        $method = $count == 0 ? 0 : 8;
        $packed = $method == 8 ? gzdeflate($data, 6) : $data;
        $crc = crc32($data);
        $header = pack("vvvvvVVVvv", 20, 0, $method, $dostime, $dosdate, $crc, strlen($packed), strlen($data), strlen($name), 0) . $name;
        $central .= pack("VvvvvvvVVVvvvvvVV", 0x02014b50, 20, 20, 0, $method, $dostime, $dosdate, $crc, strlen($packed), strlen($data), strlen($name), 0, 0, 0, 0, 0, strlen($local)) . $name;
        $local .= pack("V", 0x04034b50) . $header . $packed;
        $count++;
    }
    return $local . $central . pack("VvvvvVVv", 0x06054b50, 0, 0, $count, $count, strlen($central), strlen($local), 0);
}
