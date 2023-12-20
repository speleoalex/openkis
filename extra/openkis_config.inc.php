<?php
global $xmldb_mysqlhost,$xmldb_mysqldatabase,$xmldb_mysqlusername,$xmldb_mysqlpassword,$_FN_default_database_driver;
$xmldb_mysqlhost="localhost";
$xmldb_mysqldatabase="openkis_example";
$xmldb_mysqlusername="root";
$xmldb_mysqlpassword="";


$_FN['openkis_custom']="default"; //region identifier, used to load custom functions in extra/openkis/custom

if (file_exists("extra/openkis_config.local.php"))
{

    include "extra/openkis_config.local.php";
}
if (file_exists("openkis_config.local.php"))
{

    include "openkis_config.local.php";
}

?>
