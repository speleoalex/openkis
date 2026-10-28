<?php
/**
 * Proxy for the map layers of other domains (WMS servers, KML files) that do
 * not send CORS headers: openkis_proxy.php/<url>, used in bs_map/conf/layers_*.js.
 *
 * Only http(s) URLs on the allowed hosts are fetched (see
 * openkis_ProxyAllowedHosts()), redirects are followed only towards those
 * hosts, and only map content types (images, XML/KML/GML, JSON) are passed
 * on: anything else is sent as text/plain, so a page of another site never
 * runs as HTML on this domain.
 */
ob_start();
global $is_gzip;
global $_FN;
require_once "loadfinis.php";
$REQUEST_URI = $_SERVER['REQUEST_URI'];
$url = explode("openkis_proxy.php/", $REQUEST_URI, 2);
while (false !== @ob_end_clean());
if (!isset($url[1]) || $url[1] === "")
{
    openkis_ProxyFail(400);
}
$url = $url[1];
if (!preg_match('#^https?://#i', $url))
{
    // a path of this site
    $url = $_FN['siteurl'] . ltrim($url, "/");
}
$contents = getWebPage($url);
if ($contents === false)
{
    openkis_ProxyFail(403);
}
if ($contents['errno'] || empty($contents['http_code']))
{
    // no answer (timeout, DNS, refused): an error, not an empty "200" page
    openkis_ProxyFail(502);
}
// the upstream status, so a map server's 503 isn't passed on as 200
http_response_code($contents['http_code']);
header("Content-Type: " . openkis_ProxyContentType(isset($contents['content_type']) ? $contents['content_type'] : ""));
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'none'; sandbox");
if ($is_gzip)
{
    header("Content-Encoding: gzip");
}
header("Access-Control-Allow-Origin: *");
die($contents['content']);

/**
 * Ends the request with an empty answer.
 *
 * @param int $status HTTP status
 */
function openkis_ProxyFail($status)
{
    http_response_code($status);
    header("Access-Control-Allow-Origin: *");
    die();
}

/**
 * Hosts the proxy may fetch from: the hosts of the openkis_proxy.php/ URLs
 * in bs_map/conf/layers_*.js (so a layer added there works without further
 * settings), the host of this site and the ones listed in
 * $_FN['openkis_proxy_hosts'] (comma separated, or an array), each with and
 * without "www.".
 *
 * @return array lower case host names
 */
function openkis_ProxyAllowedHosts()
{
    global $_FN;
    static $hosts = null;
    if ($hosts !== null)
    {
        return $hosts;
    }
    $hosts = array();
    $files = glob(__DIR__ . "/bs_map/conf/layers_*.js");
    foreach (is_array($files) ? $files : array() as $file)
    {
        if (preg_match_all('#openkis_proxy\.php/https?://([a-z0-9.-]+)#i', (string) @file_get_contents($file), $m))
        {
            $hosts = array_merge($hosts, $m[1]);
        }
    }
    $hosts[] = parse_url($_FN['siteurl'], PHP_URL_HOST);
    if (!empty($_FN['openkis_proxy_hosts']))
    {
        $extra = is_array($_FN['openkis_proxy_hosts']) ? $_FN['openkis_proxy_hosts'] : explode(",", $_FN['openkis_proxy_hosts']);
        $hosts = array_merge($hosts, $extra);
    }
    $hosts = array_filter(array_map("strtolower", array_map("trim", $hosts)));
    // a site often redirects between "example.org" and "www.example.org"
    foreach ($hosts as $host)
    {
        $hosts[] = (strpos($host, "www.") === 0) ? substr($host, 4) : "www.$host";
    }
    $hosts = array_values(array_unique($hosts));
    return $hosts;
}

/**
 * @param string $url
 * @return bool true for an http(s) URL on an allowed host and a web port
 */
function openkis_ProxyUrlAllowed($url)
{
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']))
    {
        return false;
    }
    if (!in_array(strtolower($parts['scheme']), array("http", "https"), true))
    {
        return false;
    }
    if (isset($parts['port']) && !in_array(intval($parts['port']), array(80, 443, 8080, 8443), true))
    {
        return false;
    }
    if (isset($parts['user']) || isset($parts['pass']))
    {
        return false;
    }
    return in_array(strtolower($parts['host']), openkis_ProxyAllowedHosts(), true);
}

/**
 * Content type sent to the browser: the upstream one for map content,
 * text/plain for anything else (HTML, SVG, scripts...).
 *
 * @param string $type upstream Content-Type
 * @return string
 */
function openkis_ProxyContentType($type)
{
    $mime = strtolower(trim(explode(";", (string) $type)[0]));
    $allowed = array("image/png", "image/jpeg", "image/jpg", "image/gif", "image/webp", "image/tiff", "image/bmp",
        "text/xml", "application/xml", "application/json", "application/geo+json", "text/plain",
        "application/vnd.google-earth.kml+xml", "application/vnd.google-earth.kmz", "application/vnd.google-earth.gpx+xml");
    if (in_array($mime, $allowed, true) || strpos($mime, "application/vnd.ogc.") === 0)
    {
        return trim($type);
    }
    return "text/plain; charset=utf-8";
}

/**
 * Fetches $url, following up to 5 redirects one at a time so that each
 * target is checked against the allowed hosts.
 *
 * @global int $is_gzip set when the content is gzip encoded
 * @param string $url
 * @return array|false curl_getinfo() plus errno, errmsg and content; false
 * when the URL or a redirect target is not allowed
 */
function getWebPage($url)
{
    global $is_gzip;
    global $_FN;
    $is_gzip = 0;
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false, // redirects are followed below, after the host check
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1",
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT => 120,
    );
    for ($redirects = 0; ; $redirects++)
    {
        if ($redirects > 5 || !openkis_ProxyUrlAllowed($url))
        {
            return false;
        }
        // curl reads the port from the URL itself: the old code split the URL
        // on ":" and took a WMS parameter like "CRS=EPSG:3857" as the port
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $ret = curl_exec($ch);
        $location = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (in_array($code, array(301, 302, 303, 307, 308)) && $location)
        {
            curl_close($ch);
            $url = $location;
            continue;
        }
        break;
    }
    $sections = explode("\x0d\x0a\x0d\x0a", (string) $ret, 2);
    if (!isset($sections[1]))
    {
        $sections[1] = "";
    }
    while (!strncmp($sections[1], 'HTTP/', 5))
    {
        $sections = explode("\x0d\x0a\x0d\x0a", $sections[1], 2);
        if (!isset($sections[1]))
        {
            $sections[1] = "";
        }
    }
    $headers = $sections[0];
    $data = $sections[1];
    if (preg_match('/^Content-Encoding: gzip/mi', $headers))
    {
        $is_gzip = 1;
    }
    else
    {
        if (!empty($_FN['enable_compress_gzip']))
        {
            $data = gzencode($data);
            $is_gzip = 1;
        }
    }
    $err = curl_errno($ch);
    $errmsg = curl_error($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);
    $header['errno'] = $err;
    $header['errmsg'] = $errmsg;
    $header['content'] = $data;
    return $header;
}
