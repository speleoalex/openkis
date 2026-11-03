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
// record validator: rules to switch off, comma separated (name, description, coordinates,
// reference_system, elevation, comune, provincia, speleometric, depth_total or the label of a
// regional rule); "all" hides the banner in the edit forms
$_FN['openkis_validator_rules_off']="";
// map proxy (openkis_proxy.php): hosts allowed besides the ones used in bs_map/conf/layers_*.js
// and the host of this site, comma separated (e.g. "wms.example.org,tiles.example.org")
$_FN['openkis_proxy_hosts']="";
$_FN['openkis_custom']=""; //region identifier, loads extra/openkis/custom/<region>/<region>.php (e.g. "liguria"); empty = no customisation

// cache of the sheets for the visitors who are not logged in (finis dbview, files in
// misc/_cache/pages/<section>/): section => seconds of validity, 0 = off. A sheet is
// rebuilt when one of the tables below changes, when the code changes, after the validity
// time, and dropped when its record is saved or deleted; Control center -> Utilities ->
// Refresh map data (or refresh-cache.php) empties the whole cache.
$_FN['dbview_view_cache'] = array("caves" => 86400, "artificials" => 86400, "springs" => 86400, "glacial" => 86400);
$openkis_cache_common = array("ctl_bibliography", "ctl_bibliography_links", "ctl_tracings", "ctl_temperatures",
    "ctl_faunacave", "ctl_fauna_surveys", "ctl_fauna", "ctl_attachments", "ctl_photos", "ctl_grottocenter",
    "ctl_coordinatestypes", "ctl_areas", "ctl_cavesystems", "ctl_licenses", "ctl_geologicalformations");
$_FN['dbview_view_cache_tables'] = array(
    "caves" => array_merge(array("ctl_caves", "ctl_surveys"), $openkis_cache_common),
    "artificials" => array_merge(array("ctl_artificials", "ctl_surveys_artificials", "ctl_art_types", "ctl_art_categories"), $openkis_cache_common),
    "springs" => array_merge(array("ctl_springs", "ctl_caves"), $openkis_cache_common),
    "glacial" => array_merge(array("ctl_glacial", "ctl_surveys_glacial", "ctl_glaciers"), $openkis_cache_common),
);
unset($openkis_cache_common);
// code that renders the sheets, besides the section and dbview files: a change rebuilds them
$_FN['dbview_view_cache_files'] = array("extra/openkis/*.php", "extra/openkis/fields/*.php", "extra/openkis/custom/*/*.php",
    "extra/*.inc.php", "extra/openkis/languages/*/*.csv", "themes/*/languages/*/*.csv");

if (file_exists("extra/openkis_config.local.php"))
{

    include "extra/openkis_config.local.php";
}
if (file_exists("openkis_config.local.php"))
{

    include "openkis_config.local.php";
}
