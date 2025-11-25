<?php
global $_FN;

$_FN['xmetadb_mysqlhost'] = 'localhost';
$_FN['xmetadb_mysqldatabase'] = 'dbcave';
$_FN['xmetadb_mysqlusername'] = 'root';
$_FN['xmetadb_mysqlpassword'] = '';
if ($_FN['xmetadb_mysqldatabase'] == "")
{
	die("insert db config in ".__FILE__);
}
if (!empty($_FN['openkis_custom']))
{
    FN_IncludeScript("extra/openkis/custom/{$_FN['openkis_custom']}/{$_FN['openkis_custom']}.php");
}
?>
