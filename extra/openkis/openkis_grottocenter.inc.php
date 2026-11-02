<?php

/**
 * Grottocenter (https://grottocenter.org), the collaborative database of the
 * caves of the world:
 *
 *  1. Karstlink export of the caves (openkis_karstlink.php), CSV or JSON-LD.
 *     Karstlink is the data exchange standard of the UIS
 *     (https://ontology.uis-speleo.org/howto/); the CSV has the columns read
 *     by the Grottocenter importer (EntranceCSVImportService of
 *     grottocenter-api), which in a few names differ from the how-to page
 *     (licenseType, verticalExtend, extendBelowEntrance, extendAboveEntrance).
 *  2. Links between the caves and the Grottocenter entrances (table
 *     ctl_grottocenter, section "grottocenter"): openkis_GrottocenterMatch()
 *     proposes them from the distance and the names, an administrator of the
 *     caves section confirms them, the cave sheet then links to Grottocenter.
 *
 * Grottocenter is only read, through its public API: no account is needed and
 * nothing is written there.
 */
defined('_FNEXEC') or die('Restricted access');

/**
 * Settings of the integration: defaults in sections/grottocenter/config.php,
 * changed by the administrators in the section page (fncf_grottocenter).
 *
 * @return array
 */
function openkis_GrottocenterConfig()
{
    global $_FN;
    $defaults = array(
        "source_name" => "",
        "license" => "",
        "export_texts" => "0",
        "country_code" => "IT",
        "max_distance" => "500",
        "auto_confirm_score" => "90",
        "cache_hours" => "24",
        "api_url" => "https://api.grottocenter.org/api/v1",
    );
    $config = file_exists("sections/grottocenter/config.php") ? FN_LoadConfig("sections/grottocenter/config.php", "grottocenter") : array();
    foreach ($defaults as $k => $v)
    {
        if (!isset($config[$k]) || trim((string) $config[$k]) === "")
            $config[$k] = $v;
    }
    if (trim($config['source_name']) === "")
        $config['source_name'] = html_entity_decode(strip_tags($_FN['sitename']), ENT_QUOTES, "UTF-8");
    return $config;
}

/**
 * The sections whose table is ctl_caves: the only ones exported as Karstlink
 * underground cavities and linked to Grottocenter.
 *
 * @param string $mod
 * @return bool
 */
function openkis_GrottocenterIsCaveSection($mod)
{
    if ($mod == "" || !preg_match('/^[A-Za-z0-9_]+$/', $mod) || !file_exists("sections/$mod"))
        return false;
    $config = FN_LoadConfig("modules/dbview/config.php", $mod);
    return !empty($config['tables']) && trim($config['tables']) == "ctl_caves";
}

/**
 * Permanent URI of a record: the address of its sheet, used as Karstlink id
 * and attribution URL.
 *
 * @param string $mod
 * @param string $id
 * @return string
 */
function openkis_KarstlinkUri($mod, $id)
{
    global $_FN;
    return "{$_FN['siteurl']}index.php?mod=" . urlencode($mod) . "&op=view&id=" . urlencode($id);
}

/**
 * @param string|int $id Grottocenter entrance id
 * @return string the page of the entrance on Grottocenter
 */
function openkis_GrottocenterEntranceUrl($id)
{
    return "https://grottocenter.org/ui/entrances/" . intval($id);
}

/**
 * Karstlink license types (https://ontology.uis-speleo.org/ontology/#LicenseType)
 *
 * @param string $license setting "license"
 * @return string the URI, empty when not set
 */
function openkis_KarstlinkLicenseUri($license)
{
    $uris = array(
        "CC-BY" => "http://dbpedia.org/resource/CC_BY",
        "CC-BY-SA" => "http://dbpedia.org/resource/CC_BY_SA",
        "ODbL" => "http://dbpedia.org/resource/ODbL",
        "ODC-By" => "https://opendatacommons.org/licenses/by/",
    );
    return isset($uris[$license]) ? $uris[$license] : "";
}

/**
 * @param string $lang two letters language code
 * @return string ISO 639-2 code, the one used by Karstlink and Grottocenter
 */
function openkis_KarstlinkLanguage($lang)
{
    $codes = array("it" => "ita", "en" => "eng", "fr" => "fra", "de" => "deu", "es" => "spa", "sl" => "slv", "hr" => "hrv", "pt" => "por", "ca" => "cat");
    return isset($codes[$lang]) ? $codes[$lang] : $lang;
}

/**
 * @param string $country value of the field country (a name, or a code)
 * @param string $default code used when the name is not known
 * @return string ISO 3166-1 alpha-2 code
 */
function openkis_KarstlinkCountry($country, $default)
{
    $country = strtolower(trim($country));
    if (preg_match('/^[a-z]{2}$/', $country))
        return strtoupper($country);
    $codes = array("italy" => "IT", "italia" => "IT", "france" => "FR", "francia" => "FR", "switzerland" => "CH", "svizzera" => "CH",
        "austria" => "AT", "slovenia" => "SI", "croatia" => "HR", "croazia" => "HR", "san marino" => "SM", "vatican" => "VA",
        "spain" => "ES", "spagna" => "ES", "germany" => "DE", "germania" => "DE", "greece" => "GR", "grecia" => "GR",
        "albania" => "AL", "montenegro" => "ME", "bosnia and herzegovina" => "BA", "bosnia" => "BA", "malta" => "MT");
    return isset($codes[$country]) ? $codes[$country] : strtoupper($default);
}

/**
 * First number of a field like lenght_total or depth_negative ("120",
 * "12,5", "-7", "~300"), rounded to metres.
 *
 * @param string $value
 * @param bool $absolute drop the sign (depth_negative is stored either way)
 * @return string the integer, empty when there is no number
 */
function openkis_KarstlinkNumber($value, $absolute = false)
{
    if (!preg_match('/-?[0-9]+(?:[.,][0-9]+)?/', str_replace(" ", "", (string) $value), $m))
        return "";
    $n = round(floatval(str_replace(",", ".", $m[0])));
    return strval(intval($absolute ? abs($n) : $n));
}

/**
 * Y (and the old values S, X) of the radio fields of ctl_caves
 * @param string $value
 * @return bool
 */
function openkis_KarstlinkFlag($value)
{
    return in_array(strtoupper(trim((string) $value)), array("Y", "S", "X", "1"));
}

/**
 * Plain text from the html of a text field
 * @param string $html
 * @return string
 */
function openkis_KarstlinkText($html)
{
    $text = preg_replace('#<br\s*/?>|</p>|</div>|</li>#i', "\n", (string) $html);
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES, "UTF-8");
    $text = preg_replace('/[ \t\r]+/', " ", $text);
    $text = preg_replace('/ *\n[ \n]*/', "\n", $text);
    return trim($text);
}

/**
 * Coordinate precision in metres from the free text of location_evaluation,
 * e.g. "GPS, precisione 4m", "posizione ricavata con GPS, precisione: 3m".
 *
 * @param string $text
 * @return string the integer, empty when the text does not give it
 */
function openkis_KarstlinkPrecision($text)
{
    if (preg_match('/(precisione|precision|accuratezza|accuracy|errore|error|±|\+\/-)\D{0,15}?([0-9]+(?:[.,][0-9]+)?)\s*m\b/iu', (string) $text, $m))
        return strval(intval(round(floatval(str_replace(",", ".", $m[2])))));
    return "";
}

/**
 * Karstlink values of a cave, used by both the CSV and the JSON-LD export.
 *
 * @param string $mod section
 * @param array $row record of ctl_caves
 * @param array $settings openkis_GrottocenterConfig()
 * @param string $gc_entrance Grottocenter entrance confirmed for the record, or ""
 * @return array
 */
function openkis_KarstlinkRecord($mod, $row, $settings, $gc_entrance)
{
    global $_FN;
    $get = function ($field) use ($row) {
        return isset($row[$field]) ? trim((string) $row[$field]) : "";
    };
    $lang = isset($_FN['lang_default']) ? $_FN['lang_default'] : $_FN['lang'];
    // the cave code first: it is how the speleologists search a cave
    $alternate = array();
    foreach (array_merge(array($get("code")), explode(",", $get("alias")), preg_split('/[;|]/', $get("synonyms"))) as $name)
    {
        $name = trim($name);
        if ($name !== "" && $name !== $get("name") && !in_array($name, $alternate))
            $alternate[] = $name;
    }
    $elevation = $get("elevation");
    if ($elevation === "")
        $elevation = $get("elevation_map");
    if ($elevation === "")
        $elevation = $get("elevation_gps");
    $below = openkis_KarstlinkNumber($get("depth_negative"), true);
    $above = openkis_KarstlinkNumber($get("depth_positive"), true);
    $vertical = openkis_KarstlinkNumber($get("depth_total"), true);
    if ($vertical === "" && ($below !== "" || $above !== ""))
        $vertical = strval(intval($below) + intval($above));
    $date = function ($value) {
        return preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}/', $value) ? substr($value, 0, 10) : "";
    };
    $rec = array(
        "id" => $get("id"),
        "uri" => openkis_KarstlinkUri($mod, $get("id")),
        "label" => html_entity_decode($get("name"), ENT_QUOTES, "UTF-8"),
        "language" => openkis_KarstlinkLanguage($lang),
        "lang2" => $lang,
        "alternate" => $alternate,
        "latitude" => $get("latitude"),
        "longitude" => $get("longitude"),
        "altitude" => openkis_KarstlinkNumber($elevation),
        "precision" => openkis_KarstlinkPrecision($get("location_evaluation")),
        "length" => openkis_KarstlinkNumber($get("lenght_total"), true),
        "vertical" => $vertical,
        "below" => $below,
        "above" => $above,
        "country" => openkis_KarstlinkCountry($get("country"), $settings['country_code']),
        "created" => $date($get("recordinsert")),
        "modified" => $date($get("recordupdate")),
        "touristic" => openkis_KarstlinkFlag($get("tourist")),
        "rules" => openkis_KarstlinkFlag($get("closed")),
        "source" => $settings['source_name'],
        "license" => openkis_KarstlinkLicenseUri($settings['license']),
        "description" => null,
        "access" => null,
        "sameas" => $gc_entrance !== "" ? openkis_GrottocenterEntranceUrl($gc_entrance) : "",
    );
    if (!empty($settings['export_texts']))
    {
        $text = openkis_KarstlinkText($get("description"));
        if ($text !== "")
        {
            $creator = trim(openkis_KarstlinkText($get("authordescription")));
            $rec['description'] = array("creator" => $creator !== "" ? $creator : $settings['source_name'], "title" => $rec['label'], "text" => $text);
        }
        $text = openkis_KarstlinkText($get("itinerary"));
        if ($text !== "")
        {
            $creator = trim(openkis_KarstlinkText($get("authoritinerary")));
            $rec['access'] = array("creator" => $creator !== "" ? $creator : $settings['source_name'], "text" => $text);
        }
    }
    return $rec;
}

/**
 * Columns of the CSV export, the names read by the Grottocenter importer.
 * owl:sameAs (the Grottocenter entrance of a confirmed link) is not part of
 * the Karstlink CSV models: the importer ignores it, it lets a harvester
 * recognise the caves already on Grottocenter.
 *
 * @return array
 */
function openkis_KarstlinkCsvColumns()
{
    return array("id", "rdf:type", "dct:rights/cc:attributionName", "dct:rights/karstlink:licenseType", "dct:rights/dct:created",
        "dct:rights/dct:modified", "dct:rights/cc:attributionURL", "rdfs:label", "rdfs:label/dc:language", "gn:alternateName",
        "w3geo:latitude", "w3geo:longitude", "w3geo:altitude", "dwc:coordinatePrecision", "karstlink:length",
        "karstlink:verticalExtend", "karstlink:extendBelowEntrance", "karstlink:extendAboveEntrance", "gn:countryCode",
        "karstlink:isTouristic", "karstlink:hasRules",
        "karstlink:hasDescriptionDocument/dct:creator", "karstlink:hasDescriptionDocument/dc:language",
        "karstlink:hasDescriptionDocument/dct:title", "karstlink:hasDescriptionDocument/dct:description",
        "karstlink:hasAccessDocument/dct:creator", "karstlink:hasAccessDocument/dc:language",
        "karstlink:hasAccessDocument/dct:description", "owl:sameAs");
}

/**
 * @param array $rec openkis_KarstlinkRecord()
 * @return array values in the order of openkis_KarstlinkCsvColumns()
 */
function openkis_KarstlinkCsvRow($rec)
{
    $d = $rec['description'];
    $a = $rec['access'];
    return array($rec['id'], "karstlink:UndergroundCavity", $rec['source'], $rec['license'], $rec['created'],
        $rec['modified'], $rec['uri'], $rec['label'], $rec['language'], implode(" | ", $rec['alternate']),
        $rec['latitude'], $rec['longitude'], $rec['altitude'], $rec['precision'], $rec['length'],
        $rec['vertical'], $rec['below'], $rec['above'], $rec['country'],
        $rec['touristic'] ? "true" : "false", $rec['rules'] ? "true" : "false",
        $d ? $d['creator'] : "", $d ? $rec['language'] : "", $d ? $d['title'] : "", $d ? $d['text'] : "",
        $a ? $a['creator'] : "", $a ? $rec['language'] : "", $a ? $a['text'] : "", $rec['sameas']);
}

/**
 * @return array the @context of the JSON-LD export
 */
function openkis_KarstlinkJsonLdContext()
{
    return array(
        "karstlink" => "https://ontology.uis-speleo.org/ontology/#",
        "rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
        "owl" => "http://www.w3.org/2002/07/owl#",
        "dct" => "http://purl.org/dc/terms/",
        "dc" => "http://purl.org/dc/elements/1.1/",
        "cc" => "http://creativecommons.org/ns#",
        "gn" => "http://www.geonames.org/ontology#",
        "w3geo" => "http://www.w3.org/2003/01/geo/wgs84_pos#",
        "dwc" => "http://rs.tdwg.org/dwc/terms/",
    );
}

/**
 * @param array $rec openkis_KarstlinkRecord()
 * @return array a karstlink:UndergroundCavity node of the JSON-LD graph
 */
function openkis_KarstlinkJsonLdNode($rec)
{
    $int = function ($v) {
        return $v === "" ? null : intval($v);
    };
    $node = array(
        "@id" => $rec['uri'],
        "@type" => "karstlink:UndergroundCavity",
        "rdfs:label" => array("@value" => $rec['label'], "@language" => $rec['lang2']),
        "gn:alternateName" => $rec['alternate'],
        "w3geo:lat" => $rec['latitude'] === "" ? null : floatval($rec['latitude']),
        "w3geo:long" => $rec['longitude'] === "" ? null : floatval($rec['longitude']),
        "w3geo:alt" => $int($rec['altitude']),
        "dwc:coordinatePrecision" => $int($rec['precision']),
        "karstlink:length" => $int($rec['length']),
        "karstlink:verticalExtent" => $int($rec['vertical']),
        "karstlink:extentBelowEntrance" => $int($rec['below']),
        "karstlink:extentAboveEntrance" => $int($rec['above']),
        "gn:countryCode" => $rec['country'],
        "dct:rights" => array(
            "cc:attributionName" => $rec['source'],
            "cc:attributionURL" => array("@id" => $rec['uri']),
            "karstlink:licenseType" => $rec['license'] !== "" ? array("@id" => $rec['license']) : null,
            "dct:created" => $rec['created'] !== "" ? $rec['created'] : null,
            "dct:modified" => $rec['modified'] !== "" ? $rec['modified'] : null,
        ),
    );
    if ($rec['description'])
        $node["karstlink:hasDescriptionDocument"] = array("dct:creator" => $rec['description']['creator'], "dc:language" => $rec['language'], "dct:title" => $rec['description']['title'], "dct:description" => $rec['description']['text']);
    if ($rec['access'])
        $node["karstlink:hasAccessDocument"] = array("dct:creator" => $rec['access']['creator'], "dc:language" => $rec['language'], "dct:description" => $rec['access']['text']);
    if ($rec['sameas'] !== "")
        $node["owl:sameAs"] = array("@id" => $rec['sameas']);
    return openkis_KarstlinkDropEmpty($node);
}

/**
 * @param array $node
 * @return array the node without null values and empty lists
 */
function openkis_KarstlinkDropEmpty($node)
{
    foreach ($node as $k => $v)
    {
        if (is_array($v) && !isset($v['@id']) && !isset($v['@value']) && array_keys($v) !== range(0, count($v) - 1))
            $v = $node[$k] = openkis_KarstlinkDropEmpty($v);
        if ($v === null || (is_array($v) && count($v) == 0))
            unset($node[$k]);
    }
    return $node;
}

//------------------------------- Grottocenter API ---------------------------->

/**
 * GET request to the public Grottocenter API
 *
 * @param string $path e.g. "/geoloc/entrances"
 * @param array $query
 * @param string $error set to the reason of a failure
 * @return array|false the decoded JSON answer
 */
function openkis_GrottocenterGet($path, $query, &$error)
{
    global $_FN;
    $settings = openkis_GrottocenterConfig();
    $url = rtrim($settings['api_url'], "/") . $path . (count($query) ? "?" . http_build_query($query) : "");
    $agent = "OpenKIS (+{$_FN['siteurl']})";
    $error = "";
    $status = 0;
    if (function_exists("curl_init"))
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_USERAGENT => $agent,
            CURLOPT_HTTPHEADER => array("Accept: application/json"),
        ));
        $body = curl_exec($ch);
        $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        if ($body === false)
            $error = curl_error($ch);
        if (PHP_VERSION_ID < 80000)
            curl_close($ch); // a no-op since PHP 8, deprecated in 8.5
    }
    else
    {
        $context = stream_context_create(array("http" => array("timeout" => 90, "ignore_errors" => true, "follow_location" => 0,
                "header" => "Accept: application/json\r\nUser-Agent: $agent\r\n")));
        $body = @file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('/ ([0-9]{3})/', $http_response_header[0], $m))
            $status = intval($m[1]);
        if ($body === false)
            $error = "connection failed";
    }
    if ($body === false)
        return false;
    $data = json_decode($body, true);
    if ($status != 200 || !is_array($data))
    {
        $error = "HTTP $status" . ((is_array($data) && !empty($data['message'])) ? ": {$data['message']}" : "");
        return false;
    }
    return $data;
}

/**
 * @return string folder of the cached Grottocenter data
 */
function openkis_GrottocenterCacheDir()
{
    global $_FN;
    return "{$_FN['datadir']}/_cache/grottocenter";
}

/**
 * Grottocenter entrances of the 1x1 degree tile whose south-west corner is
 * ($lat, $lon). The API serves at most 35000 km2 per request (a tile is
 * smaller everywhere) and 200 requests every 10 minutes to anonymous clients:
 * the tiles are cached for cache_hours.
 *
 * @param int $lat
 * @param int $lon
 * @param string $error set to the reason of a failure
 * @return array|false id => array(id, name, cave, latitude, longitude)
 */
function openkis_GrottocenterTile($lat, $lon, &$error)
{
    $settings = openkis_GrottocenterConfig();
    $dir = openkis_GrottocenterCacheDir();
    $file = "$dir/entrances_{$lat}_{$lon}.json";
    if (file_exists($file) && filemtime($file) > time() - 3600 * floatval($settings['cache_hours']))
    {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data))
            return $data;
    }
    $items = openkis_GrottocenterGet("/geoloc/entrances", array("sw_lat" => $lat, "sw_lng" => $lon, "ne_lat" => $lat + 1, "ne_lng" => $lon + 1), $error);
    if (!is_array($items))
        return false;
    $data = array();
    foreach ($items as $item)
    {
        if (!isset($item['id'], $item['latitude'], $item['longitude']))
            continue;
        $data[strval($item['id'])] = array(
            "id" => strval($item['id']),
            "name" => isset($item['name']) ? (string) $item['name'] : "",
            "cave" => isset($item['caveId']) ? strval($item['caveId']) : "",
            "latitude" => floatval($item['latitude']),
            "longitude" => floatval($item['longitude']),
        );
    }
    if (!is_dir($dir))
        @mkdir($dir, 0777, true);
    @file_put_contents($file, json_encode($data));
    return $data;
}

/**
 * One entrance read from the API, for a link entered by hand
 *
 * @param string $id
 * @param string $error
 * @return array|false array(id, name, cave, latitude, longitude)
 */
function openkis_GrottocenterEntrance($id, &$error)
{
    $item = openkis_GrottocenterGet("/entrances/" . intval($id), array(), $error);
    if (!is_array($item) || empty($item['id']))
        return false;
    if (!empty($item['isDeleted']))
    {
        $error = FN_Translate("the entrance has been deleted on Grottocenter");
        return false;
    }
    return array(
        "id" => strval($item['id']),
        "name" => isset($item['name']) ? (string) $item['name'] : "",
        "cave" => isset($item['cave']['id']) ? strval($item['cave']['id']) : "",
        "latitude" => isset($item['latitude']) ? floatval($item['latitude']) : null,
        "longitude" => isset($item['longitude']) ? floatval($item['longitude']) : null,
    );
}

/**
 * Drops the cached Grottocenter tiles
 * @return int files removed
 */
function openkis_GrottocenterClearCache()
{
    $n = 0;
    $files = glob(openkis_GrottocenterCacheDir() . "/entrances_*.json");
    foreach (is_array($files) ? $files : array() as $file)
    {
        if (@unlink($file))
            $n++;
    }
    return $n;
}

//------------------------------- Grottocenter API ----------------------------<
//------------------------------- matching ------------------------------------>

/**
 * @param string|float $lat
 * @param string|float $lon
 * @return bool true for WGS84 coordinates in range. A register may store a
 * missing position as 0 (or 0 and a longitude), an import may have lost the
 * decimal point (longitude 17297): those caves are not exported nor searched.
 */
function openkis_GrottocenterValidCoords($lat, $lon)
{
    $lat = floatval(str_replace(",", ".", (string) $lat));
    $lon = floatval(str_replace(",", ".", (string) $lon));
    return $lat != 0 && $lon != 0 && abs($lat) <= 90 && abs($lon) <= 180;
}

/**
 * @param float $lat1
 * @param float $lon1
 * @param float $lat2
 * @param float $lon2
 * @return float distance in metres
 */
function openkis_GrottocenterDistance($lat1, $lon1, $lat2, $lon2)
{
    $p1 = deg2rad($lat1);
    $p2 = deg2rad($lat2);
    $a = pow(sin(($p2 - $p1) / 2), 2) + cos($p1) * cos($p2) * pow(sin(deg2rad($lon2 - $lon1) / 2), 2);
    return 2 * 6371000 * asin(min(1, sqrt($a)));
}

/**
 * Lower case ASCII text
 * @param string $text
 * @return string
 */
function openkis_GrottocenterAscii($text)
{
    $text = html_entity_decode((string) $text, ENT_QUOTES, "UTF-8");
    $text = function_exists("mb_strtolower") ? mb_strtolower($text, "UTF-8") : strtolower($text);
    return strtr($text, array(
        "à" => "a", "á" => "a", "â" => "a", "ã" => "a", "ä" => "a", "å" => "a", "æ" => "ae", "ç" => "c", "č" => "c", "ć" => "c",
        "è" => "e", "é" => "e", "ê" => "e", "ë" => "e", "ì" => "i", "í" => "i", "î" => "i", "ï" => "i", "ñ" => "n",
        "ò" => "o", "ó" => "o", "ô" => "o", "õ" => "o", "ö" => "o", "ø" => "o", "œ" => "oe", "ù" => "u", "ú" => "u",
        "û" => "u", "ü" => "u", "ý" => "y", "ÿ" => "y", "š" => "s", "ž" => "z", "đ" => "d", "ß" => "ss", "’" => "'"));
}

/**
 * Words of a cave name that tell it apart: no generic cave words (also the
 * dialect ones, e.g. arma, buranco, bus), no articles and prepositions.
 *
 * @param string $name
 * @return array sorted distinct words
 */
function openkis_GrottocenterNameWords($name)
{
    static $stop = null;
    if ($stop === null)
    {
        $stop = array_flip(explode(" ", "grotta grotte grottina grottino grotticella grottone grottoni buco buca buche buchi bus busa buso "
                . "pozzo pozzi pozzetto tana tane tanone arma armetta abisso caverna cavernetta cavernone cavita riparo antro inghiottitoio "
                . "risorgenza buranco buranchetto fessura voragine spluga covolo garbo senza nome "
                . "del della dello dei degli delle dell degl di da dal dalla dallo dai dagli dalle dall de du do "
                . "a al alla allo ai agli alle all nel nella nell sul sulla sull la il lo le i gli l d e ed n nr no"));
    }
    $words = array();
    foreach (preg_split('/[^a-z0-9]+/', openkis_GrottocenterAscii($name), -1, PREG_SPLIT_NO_EMPTY) as $w)
    {
        if (!isset($stop[$w]))
            $words[$w] = true;
    }
    ksort($words);
    return array_keys($words);
}

/**
 * Cave codes inside a text, e.g. "LI1878" in "Cava di Cravero (Grotta della)
 * [LI1878]" or "LI 1815"
 *
 * @param string $text
 * @return array normalised codes ("li1878")
 */
function openkis_GrottocenterCodes($text)
{
    $codes = array();
    if (preg_match_all('/\b([a-z]{2,3})\s*0*([0-9]+(?:\.[0-9]+)?)\b/', openkis_GrottocenterAscii($text), $m, PREG_SET_ORDER))
    {
        foreach ($m as $match)
            $codes[] = $match[1] . $match[2];
    }
    return $codes;
}

/**
 * Similarity of two lists of words, 0..1: the best of the share of common
 * words (Dice) and of the similarity of the texts, which tolerates the
 * truncated or misspelt names. Names made of numbers and initials only
 * ("GROTTA B. 2", "FESSURA N. 1") are common, they count half. So do names
 * with different numbered designations, the entrances of an area surveyed
 * together ("BUCA -B39- DI CARCARAIA" and "POZZO -B9- DI CARCARAIA").
 *
 * @param array $a openkis_GrottocenterNameWords()
 * @param array $b
 * @return float
 */
function openkis_GrottocenterWordsSimilarity($a, $b)
{
    if (count($a) == 0 || count($b) == 0)
        return 0.0;
    $a_text = implode(" ", $a);
    $b_text = implode(" ", $b);
    $dice = 2 * count(array_intersect($a, $b)) / (count($a) + count($b));
    similar_text($a_text, $b_text, $percent);
    $similarity = (float) max($dice, $percent / 100);
    if (!preg_match('/[a-z]{3}/', $a_text) || !preg_match('/[a-z]{3}/', $b_text))
        $similarity = min($similarity, 0.5);
    $a_numbered = preg_grep('/[0-9]/', $a);
    $b_numbered = preg_grep('/[0-9]/', $b);
    if (count($a_numbered) && count($b_numbered) && !count(array_intersect($a_numbered, $b_numbered)))
        $similarity *= 0.5;
    return $similarity;
}

/**
 * The names of a Grottocenter entrance: the name without the bracketed
 * parts, then each bracketed part ("Ballo de' Strie (?) [Tana della Rocca
 * della Ghirighina] [LI180]")
 *
 * @param string $name
 * @return array lists of words
 */
function openkis_GrottocenterEntranceWords($name)
{
    $parts = array(preg_replace('/\[[^\]]*\]/', " ", $name));
    if (preg_match_all('/\[([^\]]*)\]/', $name, $m))
        $parts = array_merge($parts, $m[1]);
    $ret = array();
    foreach ($parts as $part)
    {
        $words = openkis_GrottocenterNameWords($part);
        if (count($words))
            $ret[] = $words;
    }
    return $ret;
}

/**
 * Score of a candidate, 0..1: noisy-OR of the evidence from the names and
 * from the distance, so either one alone can make a strong match. Many
 * entrances of Grottocenter were imported years ago from the regional
 * registers: they lie within a few metres of the cave but often have
 * truncated names ("URANCO DI " for "BURANCO DI BEDO'"); the same cave
 * positioned again can lie 100-300 m away with the same name.
 * Distance evidence exp(-d / 60 m): 0.97 at 2 m, 0.61 at 30 m, 0.19 at 100 m.
 *
 * @param float $similarity of the names, 0..1 (1 also when the Grottocenter
 * name contains the cave code)
 * @param float $distance metres
 * @return float
 */
function openkis_GrottocenterScore($similarity, $distance)
{
    return 1 - (1 - 0.95 * $similarity) * (1 - 0.9 * exp(-$distance / 60));
}

/**
 * Proposes a Grottocenter entrance for each cave of a section with
 * coordinates. The entrances within max_distance are scored by
 * openkis_GrottocenterScore(); then the pairs are taken best first, so each
 * cave gets the best entrance not taken by a better pair, and an entrance is
 * proposed for one cave only (the entrances of the confirmed links are not
 * proposed again). A cave whose best pair scores less than 40% gets the
 * status "nomatch", its candidates are kept for the review.
 *
 * The links confirmed or set as missing by an administrator are kept, the
 * proposals of the previous run are replaced. Grottocenter data are read by
 * tiles (openkis_GrottocenterTile()); the caves of a tile that could not be
 * read are left as they were.
 *
 * @param string $mod section of the caves
 * @param array $errors set to the errors of the API
 * @return array counts: checked, proposed, nomatch, kept, skipped, tiles, entrances
 */
function openkis_GrottocenterMatch($mod, &$errors)
{
    global $_FN;
    $errors = array();
    $stats = array("checked" => 0, "proposed" => 0, "nomatch" => 0, "kept" => 0, "skipped" => 0, "tiles" => 0, "entrances" => 0);
    $settings = openkis_GrottocenterConfig();
    $maxd = max(10, floatval($settings['max_distance']));
    $minscore = 0.4;
    $links_table = FN_XMDBTable("ctl_grottocenter");
    $config = FN_LoadConfig("modules/dbview/config.php", $mod);
    $tablename = $config['tables'];
    openkis_UpdateCoords($mod);
    $caves_table = FN_XMDBTable($tablename);
    if (!is_object($caves_table) || !is_object($links_table))
        return $stats;
    $fields = array("id", "code", "name", "latitude", "longitude");
    foreach (array("alias", "synonyms", "recorddeleted") as $field)
    {
        if (isset($caves_table->fields[$field]))
            $fields[] = $field;
    }
    $caves = FN_XMETADBQuery("SELECT " . implode(",", $fields) . " FROM $tablename");
    if (!is_array($caves))
        return $stats;
    $links = array();
    $taken = array();
    foreach (openkis_GrottocenterLinks($mod) as $link)
    {
        $links[strval($link['record_id'])] = $link;
        if ($link['status'] == "confirmed" && $link['gc_entrance'] !== "")
            $taken[strval($link['gc_entrance'])] = true;
    }

    // the caves to check and the tiles they need (a cave near the edge of a
    // tile needs the next one too); only the values used below are kept: a
    // national register has more than 46000 caves
    $todo = array();
    $tiles = array();
    $present = array();
    $dlat = $maxd / 111000;
    foreach (array_keys($caves) as $k)
    {
        $cave = $caves[$k];
        unset($caves[$k]);
        if (!empty($cave['recorddeleted']) || !openkis_GrottocenterValidCoords($cave['latitude'], $cave['longitude']))
            continue;
        $id = strval($cave['id']);
        $present[$id] = true;
        if (isset($links[$id]) && in_array($links[$id]['status'], array("confirmed", "missing")))
        {
            $stats['kept']++;
            continue;
        }
        $lat = floatval(str_replace(",", ".", $cave['latitude']));
        $lon = floatval(str_replace(",", ".", $cave['longitude']));
        $dlon = $dlat / cos(deg2rad($lat));
        $item = array("lat" => $lat, "lon" => $lon, "name" => (string) $cave['name'], "code" => (string) $cave['code'],
            "synonyms" => isset($cave['synonyms']) ? (string) $cave['synonyms'] : "", "alias" => isset($cave['alias']) ? (string) $cave['alias'] : "",
            "tiles" => array());
        for ($y = floor($lat - $dlat); $y <= floor($lat + $dlat); $y++)
        {
            for ($x = floor($lon - $dlon); $x <= floor($lon + $dlon); $x++)
            {
                $item['tiles'][] = "$y,$x";
                $tiles["$y,$x"] = false;
            }
        }
        $todo[$id] = $item;
    }
    unset($caves);

    // Grottocenter entrances by cell of 0.01 degrees, to compare each cave
    // with the near entrances only
    $cell = 0.01;
    $grid = array();
    $entrances = array();
    @set_time_limit(0);
    foreach (array_keys($tiles) as $key)
    {
        list($y, $x) = explode(",", $key);
        $error = "";
        $data = openkis_GrottocenterTile(intval($y), intval($x), $error);
        if (!is_array($data))
        {
            $errors[] = "Grottocenter $y,$x: $error";
            continue;
        }
        $tiles[$key] = true;
        $stats['tiles']++;
        foreach ($data as $gid => $entrance)
        {
            if (isset($entrances[$gid]))
                continue;
            $entrances[$gid] = $entrance;
            $grid[floor($entrance['latitude'] / $cell) . "," . floor($entrance['longitude'] / $cell)][] = $gid;
        }
    }
    $stats['entrances'] = count($entrances);

    // candidates of each cave, and all the pairs
    $pairs = array();
    $candidates = array();
    $words_cache = array();
    foreach (array_keys($todo) as $id)
    {
        $cave = $todo[$id];
        foreach ($cave['tiles'] as $key)
        {
            if (!$tiles[$key])
            {
                unset($todo[$id]);
                $stats['skipped']++;
                continue 2;
            }
        }
        $names = array();
        foreach (array_merge(array($cave['name']), preg_split('/[;|]/', $cave['synonyms'])) as $name)
        {
            $words = openkis_GrottocenterNameWords($name);
            if (count($words))
                $names[] = $words;
        }
        $codes = array();
        foreach (array_merge(array($cave['code']), explode(",", $cave['alias'])) as $code)
            $codes = array_merge($codes, openkis_GrottocenterCodes($code));
        $list = array();
        $dlon = $dlat / cos(deg2rad($cave['lat']));
        for ($y = floor(($cave['lat'] - $dlat) / $cell); $y <= floor(($cave['lat'] + $dlat) / $cell); $y++)
        {
            for ($x = floor(($cave['lon'] - $dlon) / $cell); $x <= floor(($cave['lon'] + $dlon) / $cell); $x++)
            {
                if (!isset($grid["$y,$x"]))
                    continue;
                foreach ($grid["$y,$x"] as $gid)
                {
                    $e = $entrances[$gid];
                    $distance = openkis_GrottocenterDistance($cave['lat'], $cave['lon'], $e['latitude'], $e['longitude']);
                    if ($distance > $maxd)
                        continue;
                    if (!isset($words_cache[$gid]))
                        $words_cache[$gid] = array(openkis_GrottocenterEntranceWords($e['name']), openkis_GrottocenterCodes($e['name']));
                    $similarity = 0.0;
                    if (count($codes) && count(array_intersect($codes, $words_cache[$gid][1])))
                    {
                        $similarity = 1.0;
                    }
                    else
                    {
                        foreach ($names as $a)
                        {
                            foreach ($words_cache[$gid][0] as $b)
                                $similarity = max($similarity, openkis_GrottocenterWordsSimilarity($a, $b));
                        }
                    }
                    $score = openkis_GrottocenterScore($similarity, $distance);
                    // the entrances marked as duplicates on Grottocenter come after the original
                    if (stripos($e['name'], "doublon") !== false)
                        $score *= 0.9;
                    $list[] = array("id" => $gid, "cave" => $e['cave'], "name" => $e['name'], "distance" => intval(round($distance)),
                        "name_score" => intval(round($similarity * 100)), "score" => intval(round($score * 100)));
                    if (!isset($taken[$gid]) && $score >= $minscore)
                        $pairs[] = array($score, $id, $gid, count($pairs));
                }
            }
        }
        usort($list, function ($a, $b) {
            return $b['score'] - $a['score'];
        });
        // as JSON: thousands of small arrays would take hundreds of MB
        $candidates[$id] = json_encode(array_slice($list, 0, 5), JSON_UNESCAPED_UNICODE);
        $todo[$id] = true;
    }

    unset($grid, $entrances, $words_cache);
    // best pairs first; the original order breaks the ties
    usort($pairs, function ($a, $b) {
        if ($a[0] == $b[0])
            return $a[3] - $b[3];
        return $a[0] < $b[0] ? 1 : -1;
    });
    $proposed = array();
    $used = array();
    foreach ($pairs as $pair)
    {
        list($score, $id, $gid) = $pair;
        if (isset($proposed[$id]) || isset($used[$gid]))
            continue;
        $proposed[$id] = $gid;
        $used[$gid] = true;
    }

    // save: a row is written only when it changes
    unset($pairs, $used);
    foreach (array_keys($todo) as $id)
    {
        $id = strval($id);
        $stats['checked']++;
        $values = array("section" => $mod, "record_id" => $id, "status" => "nomatch", "gc_entrance" => "", "gc_cave" => "", "gc_name" => "",
            "distance" => "", "name_score" => "", "score" => "", "candidates" => $candidates[$id]);
        if (isset($proposed[$id]))
        {
            foreach (json_decode($candidates[$id], true) as $candidate)
            {
                if ($candidate['id'] == $proposed[$id])
                {
                    $values = array_merge($values, array("status" => "proposed", "gc_entrance" => $candidate['id'], "gc_cave" => $candidate['cave'],
                        "gc_name" => $candidate['name'], "distance" => strval($candidate['distance']), "name_score" => strval($candidate['name_score']),
                        "score" => strval($candidate['score'])));
                }
            }
        }
        $stats[$values['status']]++;
        if (isset($links[$id]))
        {
            $changed = false;
            foreach ($values as $k => $v)
            {
                if ((string) $links[$id][$k] !== (string) $v)
                    $changed = true;
            }
            if ($changed)
            {
                $values['id'] = $links[$id]['id'];
                $values['username'] = "";
                $values['recordupdate'] = FN_Now();
                $links_table->UpdateRecord($values);
            }
            unset($links[$id]);
        }
        else
        {
            $values['username'] = "";
            $values['recordupdate'] = FN_Now();
            $links_table->InsertRecord($values);
        }
    }
    // proposals left over from a previous run for caves deleted or without coordinates
    foreach ($links as $id => $link)
    {
        if (!isset($present[$id]) && in_array($link['status'], array("proposed", "nomatch")))
            $links_table->DelRecord($link['id']);
    }
    $stats['time'] = FN_Now();
    $stats['user'] = $_FN['user'];
    if (!is_dir(openkis_GrottocenterCacheDir()))
        @mkdir(openkis_GrottocenterCacheDir(), 0777, true);
    @file_put_contents(openkis_GrottocenterCacheDir() . "/last_match_$mod.json", json_encode(array("stats" => $stats, "errors" => $errors)));
    return $stats;
}

/**
 * @param string $mod
 * @return array|null stats and errors of the last openkis_GrottocenterMatch()
 */
function openkis_GrottocenterLastMatch($mod)
{
    $file = openkis_GrottocenterCacheDir() . "/last_match_$mod.json";
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : null;
    return is_array($data) ? $data : null;
}

//------------------------------- matching ------------------------------------<

/**
 * @param array $row record of ctl_grottocenter
 * @return array the values as strings, "" for the empty ones (the MySQL
 * driver stores an empty string as NULL)
 */
function openkis_GrottocenterNormalize($row)
{
    foreach ($row as $k => $v)
        $row[$k] = $v === null ? "" : (string) $v;
    return $row;
}

/**
 * @param string $mod
 * @param string $status only the links with this status, all when empty
 * @return array the links of a section (openkis_GrottocenterNormalize())
 */
function openkis_GrottocenterLinks($mod, $status = "")
{
    $table = FN_XMDBTable("ctl_grottocenter");
    if (!is_object($table))
        return array();
    $restr = array("section" => $mod);
    if ($status != "")
        $restr['status'] = $status;
    $rows = $table->GetRecords($restr);
    $ret = array();
    foreach (is_array($rows) ? $rows : array() as $row)
        $ret[] = openkis_GrottocenterNormalize($row);
    return $ret;
}

/**
 * @param string $mod
 * @param string $id record id
 * @return array|null the link of the record, null when there is none
 */
function openkis_GrottocenterLink($mod, $id)
{
    $table = FN_XMDBTable("ctl_grottocenter");
    if (!is_object($table))
        return null;
    $link = $table->GetRecord(array("section" => $mod, "record_id" => strval($id)));
    return is_array($link) && !empty($link['id']) ? openkis_GrottocenterNormalize($link) : null;
}

/**
 * Card of the cave sheet with the link to the same cave on Grottocenter,
 * shown when an administrator confirmed it (section "grottocenter").
 *
 * @param string $mod
 * @param array $row the cave
 * @return string html, empty when there is no confirmed link
 */
function openkis_GrottocenterCaveCard($mod, $row)
{
    if (empty($row['id']))
        return "";
    $link = openkis_GrottocenterLink($mod, $row['id']);
    if (!$link || $link['status'] != "confirmed" || $link['gc_entrance'] === "")
        return "";
    $url = htmlspecialchars(openkis_GrottocenterEntranceUrl($link['gc_entrance']));
    $html = "<div class=\"card openkis-side-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">Grottocenter</h5></div>";
    $html .= "<div class=\"card-body p-2\">";
    if ($link['gc_name'] !== "" && openkis_GrottocenterAscii($link['gc_name']) != openkis_GrottocenterAscii($row['name']))
        $html .= "<div class=\"small text-muted mb-1\">" . htmlspecialchars($link['gc_name']) . "</div>";
    $html .= "<a class=\"btn btn-sm btn-outline-secondary m-1\" target=\"_blank\" rel=\"noopener\" href=\"$url\">" . FN_Translate("view on Grottocenter") . "</a>";
    $html .= "</div></div>";
    return $html;
}
