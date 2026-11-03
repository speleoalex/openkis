<?php
/**
 * Serves one uploaded file of the cave register (survey drawing, photo,
 * attachment, PDF, 3d model...) behind an opaque address and a per-record
 * permission check.
 *
 * The address carries only a token (f=) that encrypts the table, record id,
 * field and file name. This script decrypts it, loads the record, checks the
 * name still matches, applies openkis_FileAccess() and only then streams the
 * file. The storage path never appears in a link, an export or a header, and
 * the token cannot be edited to reach another file. Direct access to the data
 * folder is blocked by the web server, so this is the only way in.
 */
global $_FN;
require_once "loadfinis.php";

$token = FN_GetParam("f", $_GET, "flat");
$data = function_exists("openkis_FileTokenDecode") ? openkis_FileTokenDecode($token) : false;
if (!$data)
    openkis_FileNotFound();

$table = $data['table'];
$id = $data['id'];
$field = $data['field'];
$name = $data['filename'];
$thumb = $data['thumb'];

// A valid token is one this site issued, so the table is always a real one
// (the token is encrypted with the site secret and cannot be forged). The
// permission check below is openkis_FileAccess(): the cave register follows
// its groups, other tables keep the behaviour they had before.
$tb = FN_XMDBTable($table);
if (!$tb)
    openkis_FileNotFound();
$record = $tb->GetRecordByPrimaryKey($id);
if (!is_array($record) || !isset($record[$tb->primarykey]))
    openkis_FileNotFound();
// the file name in the token must still be the one on the record: a token
// made for an old value cannot serve a file the field no longer points at
if (!isset($record[$field]) || strval($record[$field]) !== strval($name) || $name === "")
    openkis_FileNotFound();

// permission check (same rule as the register pages)
if (!openkis_FileAccess($table, $id, $field))
{
    http_response_code(403);
    header("Cache-Control: no-store");
    header("Content-Type: text/plain; charset=utf-8");
    die("Forbidden");
}

$file = $thumb ? $tb->getThumbPath($record, $field) : $tb->getFilePath($record, $field);
if (!$file || $file[0] == "?" || !file_exists($file) || !is_file($file))
    openkis_FileNotFound();
// defence in depth: the resolved file must stay inside the data folder
$real = realpath($file);
$datadir = realpath($_FN['datadir']);
if (!$real || !$datadir || strpos($real, $datadir . DIRECTORY_SEPARATOR) !== 0)
    openkis_FileNotFound();

// clear any buffered output before streaming; the session is not needed any
// more, so other requests of the same visitor are not held while a big file
// (a point cloud, a 3d model) is being sent
while (false !== @ob_end_clean());
if (session_status() == PHP_SESSION_ACTIVE)
    session_write_close();

$mime = openkis_FileMime($real, $name);
$etag = '"' . md5($real . "|" . filemtime($real) . "|" . filesize($real)) . '"';
header("Content-Type: $mime");
header("Content-Length: " . filesize($real));
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, max-age=86400");
header("ETag: $etag");
// images and the 3d model open inline; everything else downloads
$inline = (strpos($mime, "image/") === 0) || in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), array("lox", "3d", "plt"));
$disp = $inline ? "inline" : "attachment";
$dispname = str_replace(array('"', "\r", "\n", "\\"), "", basename($name));
header("Content-Disposition: $disp; filename=\"" . preg_replace('/[^\x20-\x7e]/', "_", $dispname) . "\"; filename*=UTF-8''" . rawurlencode($dispname));
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag)
{
    http_response_code(304);
    exit();
}
readfile($real);
exit();

/**
 * 404 with no caching, then stop. Keeps the "missing" and "forbidden" answers
 * apart so a wrong token looks the same as a wrong id.
 */
function openkis_FileNotFound()
{
    http_response_code(404);
    header("Cache-Control: no-store");
    header("Content-Type: text/plain; charset=utf-8");
    die("Not found");
}

/**
 * Best-effort MIME type from the file content, then the stored name.
 *
 * @param string $real path on disk
 * @param string $name stored file name (for the extension)
 * @return string
 */
function openkis_FileMime($real, $name)
{
    $mime = "";
    if (function_exists("finfo_open"))
    {
        $fi = @finfo_open(FILEINFO_MIME_TYPE);
        if ($fi)
        {
            $mime = @finfo_file($fi, $real);
            @finfo_close($fi);
        }
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    // never serve register uploads as runnable types, whatever the content
    // sniffing says
    $dangerous = array("text/html", "application/xhtml+xml", "image/svg+xml", "application/javascript", "text/javascript", "application/x-php");
    if ($mime === "" || in_array($mime, $dangerous))
    {
        $byext = array(
            "jpg" => "image/jpeg", "jpeg" => "image/jpeg", "png" => "image/png",
            "gif" => "image/gif", "webp" => "image/webp", "bmp" => "image/bmp",
            "pdf" => "application/pdf", "zip" => "application/zip",
            "kml" => "application/vnd.google-earth.kml+xml",
            "dwg" => "application/acad", "dxf" => "image/vnd.dxf",
            "lox" => "application/octet-stream", "3d" => "application/octet-stream",
            "plt" => "application/octet-stream",
        );
        $mime = isset($byext[$ext]) ? $byext[$ext] : "application/octet-stream";
    }
    return $mime;
}
