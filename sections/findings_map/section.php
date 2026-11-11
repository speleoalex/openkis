<?php
/**
 * Map of the caves and artificial cavities with paleontological and
 * archaeological findings (section findings): the points of
 * openkis_findings.php on a light base layer, coloured by category, sized by
 * the number of findings, with the icons of the types of finds
 * (OPS_Map.addFindingsLayerFromUrl). The page is for everybody: the endpoint
 * leaves out the caves hidden to the user and the finds and titles of the
 * restricted findings.
 */
global $_FN;
require_once "extra/openkis/openkis_sheet.inc.php";
if (!file_exists("sections/findings") || !FN_UserCanViewSection("findings"))
{
    echo FN_HtmlStaticContent("sections/" . $_FN['mod']);
    echo "<div class=\"alert alert-warning\">" . FN_Translate("the findings map is reserved to the users who may view the findings") . "</div>";
}
else
{
    echo openkis_FullPageMap("findings=1", FN_Translate("findings map"), FN_Translate("each point is a cave or an artificial cavity with paleontological or archaeological findings: its colour is their category (mixed when there are more), its size is the number of findings, the icons above it are the types of finds; the legend hides the categories and the popup lists the findings from the oldest"));
}
// the static content is printed above the map: the "finis" module must not print it again after this file
$SECTION = null;
