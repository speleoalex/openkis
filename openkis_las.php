<?php
/**
 * Old entry point of the LAS point clouds: openkis_las.php?f=<path of the
 * file in the data folder>. The pages now link the files through
 * openkis_file.php; this script stays for old links and serves only a .las
 * file of a record (misc/fndatabase/<table>/<id>/<field>/<name>.las), to who
 * may read it (openkis_FileAccess()).
 */
global $_FN;
require_once "loadfinis.php";
$f = FN_GetParam("f", $_GET);
$database = empty($_FN['database']) ? "fndatabase" : $_FN['database'];
$parts = explode("/", str_replace("\\", "/", $f));
$name = count($parts) ? array_pop($parts) : "";
$field = count($parts) ? array_pop($parts) : "";
$id = count($parts) ? array_pop($parts) : "";
$table = count($parts) ? array_pop($parts) : "";
$real = realpath($f);
$datadir = realpath("{$_FN['datadir']}/$database");
$t = (preg_match('/^[A-Za-z0-9_]+$/', $table) && preg_match('/^[A-Za-z0-9_]+$/', $field) && $id !== "" && strtolower(pathinfo($name, PATHINFO_EXTENSION)) == "las"
    && file_exists("{$_FN['datadir']}/$database/$table.php")) ? FN_XMDBTable($table) : false;
$record = $t ? $t->GetRecordByPrimaryKey($id) : false;
if (!$real || !$datadir || strpos($real, $datadir . DIRECTORY_SEPARATOR) !== 0 || !is_file($real)
    || !is_array($record) || !isset($record[$field]) || strval($record[$field]) !== $name || realpath($t->getFilePath($record, $field)) !== $real)
{
    http_response_code(404);
    die();
}
if (!openkis_FileAccess($table, $id, $field))
{
    http_response_code(403);
    header("Cache-Control: no-store");
    die();
}
while (false !== @ob_end_clean());
if (session_status() == PHP_SESSION_ACTIVE)
    session_write_close();
header("Content-Type: application/octet-stream");
header("Content-Length: " . filesize($real));
header("X-Content-Type-Options: nosniff");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: private, max-age=86400");
readfile($real);
