<?php
/**
 * Rewrites sections/<section>/default.xml.php of every section from the
 * current settings (FN_UpdateDefaultXML). Maintenance tool, for the
 * administrators of the site only.
 */
global $_FN;
require_once "loadfinis.php";
if (!FN_IsAdmin())
{
    http_response_code(403);
    header("Cache-Control: no-store");
    die("Forbidden");
}
//$section="",$recursive=false,$onlyreadable=true,$hidden=false,$onlyenabled=true,$nocache=false
$sections=FN_GetSections("",true,false,true,false,true);
foreach($sections as $section)
{
    dprint_r($section);
    FN_UpdateDefaultXML($section,false);
}
?>
