<?php
global $_FN;
// default values, override them in extra/openkis_config.local.php
$_FN['default_database_driver']="mysql";
$_FN['xmetadb_mysqlhost']="localhost";
$_FN['xmetadb_mysqldatabase']="openkis_example";
$_FN['xmetadb_mysqlusername']="root";
$_FN['xmetadb_mysqlpassword']="";


// only administrators and the members of this group can use the MCP server (personal API keys)
$_FN['apikey_groups']="mcp_users";
// users allowed to use the write tools of the MCP server (administrators always can)
$_FN['openkis_mcp_write_group']="mcp_write";
$_FN['openkis_custom']=""; //region identifier, loads extra/openkis/custom/<region>/<region>.php (e.g. "liguria"); empty = no customisation

if (file_exists("extra/openkis_config.local.php"))
{

    include "extra/openkis_config.local.php";
}
if (file_exists("openkis_config.local.php"))
{

    include "openkis_config.local.php";
}
