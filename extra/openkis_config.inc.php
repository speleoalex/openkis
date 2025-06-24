<?php
global $xmetadb_mysqlhost,$xmetadb_mysqldatabase,$xmetadb_mysqlusername,$xmetadb_mysqlpassword,$_FN_default_database_driver;
$xmetadb_mysqlhost="localhost";
$xmetadb_mysqldatabase="openkis_example";
$xmetadb_mysqlusername="root";
$xmetadb_mysqlpassword="";


$_FN['openkis_custom']="default"; //region identifier, used to load custom functions in extra/openkis/custom

if (file_exists("extra/openkis_config.local.php"))
{

    include "extra/openkis_config.local.php";
}
if (file_exists("openkis_config.local.php"))
{

    include "openkis_config.local.php";
}
