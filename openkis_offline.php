<?php
/**
 * Data of the offline use of the site installed as an application
 * (extra/openkis/openkis_offline.inc.php, docs/piano_offline_webapp_2026-11-03.md).
 *
 * op=info               versions, addresses of the shells, of the map, sections that can
 *                       be downloaded (JSON)
 * op=index&mod=<mod>    records of a section the visitor may read: id, code, name,
 *                       synonyms, municipality... and the address of their sheet (JSON)
 *
 * The worker and the offline page ask without cookies, so they get what an anonymous
 * visitor gets: only public content is kept on the devices.
 */
global $_FN;
require_once "loadfinis.php";

$op = FN_GetParam("op", $_GET, "flat");
$mod = FN_GetParam("mod", $_GET, "flat");

/**
 * @param mixed $data
 * @param int $status
 */
function openkis_OfflineJson($data, $status = 200)
{
    while (false !== @ob_end_clean());
    http_response_code($status);
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-cache");
    header("X-Content-Type-Options: nosniff");
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    $accept = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : "";
    if (strlen($json) > 2048 && strpos($accept, "gzip") !== false && function_exists("gzencode") && !ini_get("zlib.output_compression"))
    {
        header("Content-Encoding: gzip");
        header("Vary: Accept-Encoding");
        $json = gzencode($json, 6);
    }
    header("Content-Length: " . strlen($json));
    echo $json;
    exit();
}

if (!openkis_OfflineEnabled())
{
    openkis_OfflineJson(array("error" => "disabled"), 404);
}

switch ($op)
{
    case "info":
        openkis_OfflineJson(openkis_OfflineInfo());
        break;

    case "index":
        $sections = openkis_OfflineSections();
        if (!isset($sections[$mod]))
        {
            openkis_OfflineJson(array("error" => "not found"), 404);
        }
        $config = FN_LoadConfig("modules/dbview/config.php", $mod);
        $tablename = $sections[$mod]['table'];
        // same cache as the map exports: valid until the table changes
        $idcache = openkis_MapCacheKey(__FILE__, $config) . "_index_" . md5($mod);
        $cache = openkis_MapCacheGet($idcache, $tablename);
        if ($cache !== null && !FN_GetParam("nocache", $_GET, "flat"))
        {
            $index = json_decode($cache, true);
            if (is_array($index))
            {
                openkis_OfflineJson($index);
            }
        }
        $stamp = openkis_MapCacheStamp($tablename);
        $index = openkis_OfflineIndex($mod);
        $index['stamp'] = $stamp;
        openkis_MapCacheSet($idcache, $stamp, json_encode($index, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR));
        openkis_OfflineJson($index);
        break;

    default:
        openkis_OfflineJson(array("error" => "unknown op"), 400);
}
