<?php
global $_FN;
// default values, override them in extra/openkis_config.local.php
$_FN['default_database_driver']="mysql";
$_FN['xmetadb_mysqlhost']="localhost";
$_FN['xmetadb_mysqldatabase']="openkis_example";
$_FN['xmetadb_mysqlusername']="root";
$_FN['xmetadb_mysqlpassword']="";


$_FN['openkis_custom']=""; //region identifier, loads extra/openkis/custom/<region>/<region>.php (e.g. "liguria"); empty = no customisation

if (file_exists("extra/openkis_config.local.php"))
{

    include "extra/openkis_config.local.php";
}
if (file_exists("openkis_config.local.php"))
{

    include "openkis_config.local.php";
}
