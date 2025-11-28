<?php
global $xmldb_mysqlhost, $xmldb_mysqldatabase, $xmldb_mysqlusername, $xmldb_mysqlpassword;
global $_FN;

$_FN['openkis_custom'] = "wish";

if ($_SERVER['HTTP_HOST'] != "localhost") {
	// Produzione
	$_FN['xmetadb_mysqlhost'] = "speleoitsqwish.mysql.db";
	$_FN['xmetadb_mysqldatabase'] = "speleoitsqwish";
	$_FN['xmetadb_mysqlusername'] = "speleoitsqwish";
	$_FN['xmetadb_mysqlpassword'] = "Wishicos09";
} else {
	// Locale
	$_FN['xmetadb_mysqlhost'] = "localhost";
	$_FN['xmetadb_mysqldatabase'] = "openkis_wish";
	$_FN['xmetadb_mysqlusername'] = "root";
	$_FN['xmetadb_mysqlpassword'] = "";
}

// Compatibilita con DBMYSQL_database.inc.php (variabili globali legacy)
$xmldb_mysqlhost = $_FN['xmetadb_mysqlhost'];
$xmldb_mysqldatabase = $_FN['xmetadb_mysqldatabase'];
$xmldb_mysqlusername = $_FN['xmetadb_mysqlusername'];
$xmldb_mysqlpassword = $_FN['xmetadb_mysqlpassword'];

if (empty($_FN['xmetadb_mysqldatabase'])) {
	die("insert db config in " . __FILE__);
}

FN_IncludeScript("extra/openkis/custom/wish/wish.php");


?>
