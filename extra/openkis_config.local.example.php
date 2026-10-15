<?php
/**
 * OpenKIS local configuration.
 * Copy this file to extra/openkis_config.local.php and edit the values.
 * The file is ignored by git: never commit real credentials.
 */
global $_FN;

// Database driver: "mysql" (recommended) or "xmlphp" (flat files, testing only)
$_FN['default_database_driver'] = 'mysql';

// MySQL / MariaDB connection. The database is created automatically if the
// user has the CREATE privilege (e.g. XAMPP root), otherwise create it first.
$_FN['xmetadb_mysqlhost'] = 'localhost';
$_FN['xmetadb_mysqldatabase'] = 'openkis';
$_FN['xmetadb_mysqlusername'] = 'root';
$_FN['xmetadb_mysqlpassword'] = '';

// Regional customisation: name of a folder in extra/openkis/custom/
// (example, italia, liguria, lombardia, marche, piemonte, umbria, wish).
// Leave empty for a generic installation.
$_FN['openkis_custom'] = '';

if ($_FN['xmetadb_mysqldatabase'] == "")
{
    die("insert db config in " . __FILE__);
}
if (!empty($_FN['openkis_custom']))
{
    FN_IncludeScript("extra/openkis/custom/{$_FN['openkis_custom']}/{$_FN['openkis_custom']}.php");
}
