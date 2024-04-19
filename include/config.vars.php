<?php
//xmldb
global $xmldb_mysqlhost,$xmldb_mysqldatabase,$xmldb_mysqlusername,$xmldb_mysqlpassword,$_FN_default_auth_method,$_FN_datadir;
global $xmldb_default_driver;
//specific options for the mysql driver:
$xmldb_mysqlhost = "localhost";
$xmldb_mysqldatabase = "fndatabase";
$xmldb_mysqlusername = "root";
$xmldb_mysqlpassword = "";


//Flatnux
global $_FN;
global $_FN_display_errors;
global $_FN_upload_max_filesize;
global $_FN_datadir;
global $_FN_default_auth_method;
global $_FN_default_database_driver;

//display error
$_FN_display_errors = "on";
//max upload file size
$_FN_upload_max_filesize = "20M";
//writable folder that contains data
$_FN_datadir="misc";
//authentication method
$_FN_default_auth_method = "local";
//default driver: xmlphp,sqlite3,sqlite,csv,serialize,mysql,mssql
$_FN_default_database_driver = "xmlphp";
if (file_exists("config.vars.local.php"))
{
    include("config.vars.local.php");
}
?>
