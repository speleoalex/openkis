<?php
/**
 * Map of the caves and artificial cavities with fauna sightings (section
 * fauna_surveys): the points of openkis_fauna.php on a light base layer,
 * coloured by ecological category or by class of the species observed, sized
 * by their number (OPS_Map.addFaunaLayerFromUrl). The page is listed in the
 * menu for the groups of its definition (the same as fauna_surveys) and
 * shows the map only to the users who may view fauna_surveys: whatever the
 * groups of this page, the points are those of the sightings the user can see.
 */
global $_FN;
require_once "extra/openkis/openkis_sheet.inc.php";
if (!file_exists("sections/fauna_surveys") || !FN_UserCanViewSection("fauna_surveys"))
{
    echo FN_HtmlStaticContent("sections/" . $_FN['mod']);
    echo "<div class=\"alert alert-warning\">" . FN_Translate("the fauna map is reserved to the users who may view the fauna surveys") . "</div>";
}
else
{
    echo openkis_FullPageMap("fauna=1", FN_Translate("fauna map"), FN_Translate("each point is a cave or an artificial cavity with fauna surveys: its colour is the ecological category of the species most bound to the underground among those observed there (or, switching the legend, their most frequent class), its size is the number of species; the legend hides the categories and the popup lists the species"));
}
// the static content is printed above the map: the "finis" module must not print it again after this file
$SECTION = null;
