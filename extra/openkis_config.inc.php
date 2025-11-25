<?php
global $_FN;
$_FN['xmetadb_mysqlhost']="localhost";
$_FN['xmetadb_mysqldatabase']="openkis_example";
$_FN['xmetadb_mysqlusername']="root";
$_FN['xmetadb_mysqlpassword']="";


$_FN['openkis_custom']="default"; //region identifier, used to load custom functions in extra/openkis/custom

if (file_exists("extra/openkis_config.local.php"))
{

    include "extra/openkis_config.local.php";
}
if (file_exists("openkis_config.local.php"))
{

    include "openkis_config.local.php";
}
