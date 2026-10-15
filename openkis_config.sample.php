<?php
global $_FN;
$_FN['openkis_custom'] = "wish";
$_FN['default_database_driver'] = "mysql";

# MySQL database connection settings
$_FN['xmetadb_mysqlhost'] = "localhost";
$_FN['xmetadb_mysqldatabase'] = "wishdb";
$_FN['xmetadb_mysqlusername'] = "whishdb_user";
$_FN['xmetadb_mysqlpassword'] = "whishdb_password";

# MCP server (openkis_mcp.php), disabled while openkis_mcp_apikey is empty
#$_FN['openkis_mcp_apikey'] = "long-random-key"; // string or array of keys
#$_FN['openkis_mcp_user'] = "admin"; // user recorded as author of the changes
#$_FN['openkis_mcp_readonly'] = 0; // 1 = disable insert/update/delete

FN_IncludeScript("extra/openkis/custom/wish/wish.php");
