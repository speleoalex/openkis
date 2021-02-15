<?php
global $xmldb_mysqlhost,$xmldb_mysqldatabase,$xmldb_mysqlusername,$xmldb_mysqlpassword,$_FN_default_database_driver;

$xmldb_mysqlhost = 'localhost';
$xmldb_mysqldatabase = 'dbcave';
$xmldb_mysqlusername = 'root';
$xmldb_mysqlpassword = '';
if ($xmldb_mysqldatabase == "")
{
	die("insert db config in ".__FILE__);
}
if (!empty($_FN['openkis_custom']))
{
    FN_IncludeScript("extra/openkis/custom/{$_FN['openkis_custom']}/{$_FN['openkis_custom']}.php");
}
?>
