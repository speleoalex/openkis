<?php
global $_FN;
$_FN['openkis_custom'] = "wish";
$_FN['default_database_driver'] = "mysql";

# MySQL database connection settings
$_FN['xmetadb_mysqlhost'] = "localhost";
$_FN['xmetadb_mysqldatabase'] = "wishdb";
$_FN['xmetadb_mysqlusername'] = "whishdb_user";
$_FN['xmetadb_mysqlpassword'] = "whishdb_password";

FN_IncludeScript("extra/openkis/custom/wish/wish.php");
