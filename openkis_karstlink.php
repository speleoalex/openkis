<?php

/**
 * Karstlink export of the caves, the data exchange standard of the UIS
 * (https://ontology.uis-speleo.org/howto/) read by Grottocenter and by the
 * other cave databases.
 *
 *   openkis_karstlink.php?mod=caves                 CSV (the columns of the Grottocenter importer)
 *   openkis_karstlink.php?mod=caves&format=jsonld   JSON-LD
 *
 * Same records as the other map exports: the ones the visitor may see, with
 * coordinates; the dbview filters of the section work (filter_code, q, ...).
 * Source name, license and the export of the texts are set in the section
 * "grottocenter" (extra/openkis/openkis_grottocenter.inc.php).
 *
 * @package OpenKIS
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
ob_start();
global $_FN;
require_once "loadfinis.php";
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
$mod = $_FN['mod'] != "" ? $_FN['mod'] : "caves";
if (!openkis_GrottocenterIsCaveSection($mod))
{
    http_response_code(404);
    die();
}
$format = FN_GetParam("format", $_GET, "flat") == "jsonld" ? "jsonld" : "csv";
$openkis_access = openkis_SectionAccess($mod);
$config = FN_LoadConfig("modules/dbview/config.php", $mod);
$settings = openkis_GrottocenterConfig();
$tablename = $config['tables'];
// the settings and the links change the output too
$keyconfig = $config;
$keyconfig['karstlink'] = array($settings, openkis_MapCacheStamp("ctl_grottocenter"));
$idcache = openkis_MapCacheKey(__FILE__, $keyconfig);
if (!FN_GetParam("nocache", $_GET, "flat"))
{
    $cache = openkis_MapCacheGet($idcache, $tablename);
    if ($cache !== null)
        okkl_Print($cache, $format);
    unset($cache);
}
openkis_UpdateCoords($mod);
$stamp = openkis_MapCacheStamp($tablename);

$table = FN_XMDBTable($tablename);
$fields = array();
$read = array("id", "code", "alias", "name", "synonyms", "country", "latitude", "longitude", "elevation", "elevation_map", "elevation_gps",
    "location_evaluation", "lenght_total", "depth_total", "depth_positive", "depth_negative", "tourist", "closed", "recordinsert", "recordupdate");
if (!empty($settings['export_texts']))
    $read = array_merge($read, array("description", "authordescription", "itinerary", "authoritinerary"));
foreach ($read as $field)
{
    if (isset($table->fields[$field]))
        $fields[] = $field;
}
$params = array();
foreach ($_REQUEST as $k => $v)
{
    $params[$k] = $v;
}
$params['fields'] = implode(",", $fields);
$dbview = new FNDBVIEW($config);
$results = $dbview->GetResults(false, $params);
$results = openkis_FilterPublicRecords($openkis_access, $config, $results);
if (!is_array($results))
    $results = array();

// Grottocenter entrances of the confirmed links
$links = array();
foreach (openkis_GrottocenterLinks($mod, "confirmed") as $link)
{
    $links[$link['record_id']] = $link['gc_entrance'];
}
// written row by row, each record freed once written: a national register
// (WISH, 46000 caves) would not fit in memory as a whole graph
$fp = fopen("php://temp", "w+");
if ($format == "jsonld")
    fwrite($fp, "{\"@context\":" . json_encode(openkis_KarstlinkJsonLdContext(), JSON_UNESCAPED_SLASHES) . ",\"@graph\":[");
else
    fputcsv($fp, openkis_KarstlinkCsvColumns(), ",", "\"", "\\");
$n = 0;
foreach (array_keys($results) as $key)
{
    $row = $results[$key];
    unset($results[$key]);
    // Karstlink requires the coordinates of an underground cavity
    if (!openkis_GrottocenterValidCoords($row['latitude'], $row['longitude']))
        continue;
    $rec = openkis_KarstlinkRecord($mod, $row, $settings, isset($links[strval($row['id'])]) ? $links[strval($row['id'])] : "");
    if ($format == "jsonld")
        fwrite($fp, ($n > 0 ? ",\n" : "\n") . json_encode(openkis_KarstlinkJsonLdNode($rec), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    else
        fputcsv($fp, openkis_KarstlinkCsvRow($rec), ",", "\"", "\\");
    $n++;
}
if ($format == "jsonld")
    fwrite($fp, "\n]}\n");
rewind($fp);
$str = stream_get_contents($fp);
fclose($fp);
openkis_MapCacheSet($idcache, $stamp, $str);
okkl_Print($str, $format);

/**
 * Sends the export and ends the request
 * @global array $_FN
 * @param string $str
 * @param string $format csv or jsonld
 */
function okkl_Print($str, $format)
{
    global $_FN;
    $name = str_replace(array(" ", ":"), array("_", "-"), strip_tags($_FN['sitename']) . "-" . FN_Now("Y-m-d") . "-{$_FN['mod']}-karstlink");
    $name = preg_replace('/[^A-Za-z0-9_.-]/', "", $name);
    while (false !== @ob_end_clean())
        ;
    header("Cache-Control: no-cache");
    header("Pragma: no-cache");
    header("Content-Length: " . strlen($str));
    if ($format == "jsonld")
    {
        header("Content-Type: application/ld+json; charset=utf-8");
        header("Content-Disposition: inline; filename=$name.jsonld");
    }
    else
    {
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=$name.csv");
    }
    print($str);
    exit();
}
