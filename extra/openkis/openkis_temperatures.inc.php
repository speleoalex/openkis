<?php

/**
 * Underground temperatures (section "temperatures", table ctl_temperatures).
 *
 * One record is a measurement point in a cave with either one reading (type
 * "spot") or the summary of a data logger series (type "monitoring"), after
 * the two data sheets of the UCC project (Underground Climate Change,
 * https://www.progettoucc.it): "misure spot" and "monitoraggi". The record
 * takes the cave from the register (coordinates, municipality, karst area,
 * entrance elevation) or free coordinates for a site that is not in it.
 * See docs/TEMPERATURES.md.
 */
defined('_FNEXEC') or die('Restricted access');

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
    $records = FN_XMETADBQuery("SELECT id,name,type,cave,position,year,month,day,hour,start_date,end_date,latitude_txt,longitude_txt,coordinates_type,latitude,longitude,sort_date,groupview FROM ctl_temperatures");
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
 * it has free coordinates. The free coordinates, when present, replace the
 * cave entrance (a point deep in the cave, or a site not in the register).
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
    $items = array();
    foreach ($records as $record)
    {
        $code = trim(strval($record['cave']));
        // a cave the user cannot see hides the measurement
        if ($code !== "" && !isset($caves[$code]))
            continue;
        $point = ($code !== "") ? $caves[$code] : array("lat" => 0, "lon" => 0, "elevation" => "", "code" => "", "name" => "", "mod" => "", "id" => "", "comune" => "", "areas" => "");
        if (floatval($record['latitude']) != 0 && floatval($record['longitude']) != 0)
        {
            $point['lat'] = floatval($record['latitude']);
            $point['lon'] = floatval($record['longitude']);
        }
        if ($point['lat'] == 0 || $point['lon'] == 0)
            continue;
        // the points of one cave entrance share a marker on the map
        $point['key'] = ($code !== "" && floatval($record['latitude']) == 0) ? "cave:$code" : "pos:" . round($point['lat'], 5) . "," . round($point['lon'], 5);
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
 * @return string html
 */
function openkis_TemperaturesTable($records, $links = true)
{
    $html = "<div class=\"table-responsive\"><table class=\"table table-sm table-striped mb-0\">";
    foreach ($records as $record)
    {
        $url = FN_RewriteLink("index.php?mod=temperatures&op=view&id={$record['id']}");
        $date = htmlspecialchars(openkis_TemperatureDateLabel($record));
        $type = FN_Translate($record['type'] == "monitoring" ? "monitoring" : "spot measurement");
        $medium = FN_Translate(in_array($record['medium'], array("water", "rock")) ? $record['medium'] : "air");
        $value = openkis_TemperatureValue($record);
        $reading = ($value !== "") ? str_replace(".", ",", $value) . " °C" : "";
        if ($record['type'] == "monitoring" && $value !== "")
        {
            $reading = FN_Translate("mean") . " " . $reading;
        }
        $html .= "<tr><td class=\"text-nowrap\">$date</td><td class=\"text-nowrap\">$type</td><td>$medium</td>";
        $html .= "<td>" . htmlspecialchars(strval($record['position'])) . "</td><td class=\"text-nowrap\"><b>$reading</b></td>";
        if ($links)
        {
            $html .= "<td class=\"text-right\"><a class=\"btn btn-sm btn-outline-primary\" href=\"$url\">" . FN_Translate("view") . "</a></td>";
        }
        $html .= "</tr>";
    }
    $html .= "</table></div>";
    return $html;
}

/**
 * Card listing the temperature measurements of a cave, for the cave sheet
 * and the sheet of a measurement (where the record itself is left out).
 *
 * @param string $code cave code
 * @param int $exclude_id record not to list
 * @return string html, empty when the cave has no measurement visible to the user
 */
function openkis_TemperaturesCaveCard($code, $exclude_id = 0)
{
    global $_FN;
    if ($code === "" || !file_exists("sections/temperatures") || !FN_XMDBTable("ctl_temperatures") || !FN_UserCanViewSection("temperatures"))
        return "";
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    openkis_UpdateTemperatures();
    $config = FN_LoadConfig("modules/dbview/config.php", "temperatures");
    $dbview = new FNDBVIEW($config);
    $params = array();
    $params['fields'] = "id,name,type,medium,position,temperature,t_mean,year,month,day,hour,start_date,end_date,sort_date";
    $params['appendquery'] = "cave LIKE '" . addslashes($code) . "'";
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
    $title = ($exclude_id > 0) ? FN_Translate("other measurements of the cave") : FN_Translate("temperatures");
    $html = "<div class=\"card openkis-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">$title</h5></div>";
    $html .= "<div class=\"card-body p-0\">" . openkis_TemperaturesTable($records) . "</div>";
    $map = "{$_FN['siteurl']}bs_map.htm?temperature_cave=" . urlencode($code);
    $html .= "<div class=\"card-footer bg-transparent\"><a class=\"btn btn-sm btn-outline-secondary\" target=\"_blank\" href=\"$map\">" . FN_Translate("show on the map") . "</a></div></div>";
    return $html;
}

/**
 * Links to the exports in the layout of the UCC project sheets (one per
 * sheet: spot measurements, monitorings).
 *
 * @return string html
 */
function openkis_TemperaturesUccLinks()
{
    global $_FN;
    $html = FN_Translate("export for the UCC project") . ": ";
    $html .= "<a href=\"{$_FN['siteurl']}openkis_temperatures.php?format=ucc&type=spot\">" . FN_Translate("spot measurements") . " (CSV)</a>, ";
    $html .= "<a href=\"{$_FN['siteurl']}openkis_temperatures.php?format=ucc&type=monitoring\">" . FN_Translate("monitorings") . " (CSV)</a>";
    return $html;
}

/**
 * Rows of the UCC project sheet of a type, from the visible records: the
 * columns of the sheets "misure spot" (UCC_misure spot T aria v3.2) and
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
        if (($type == "monitoring") != ($r['type'] == "monitoring"))
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
                $r['data_holder'], $r['bibliography'], $notes, $r['username']));
        }
        else
        {
            $rows[] = array_merge($common, array(
                $number($r['temperature']), $r['position'], $number($r['measure_elevation']), $number($r['entrance_distance']), "",
                $option("ventilation", $r['ventilation']), $option("snow_ice_spot", $r['snow_ice']), $option("water", $r['water']),
                $r['instrument'], intval($r['day']) > 0 ? intval($r['day']) : "", intval($r['month']) > 0 ? intval($r['month']) : "", intval($r['year']) > 0 ? intval($r['year']) : "", $r['hour'],
                $r['bibliography'], $notes, $r['username']));
        }
    }
    return $rows;
}
