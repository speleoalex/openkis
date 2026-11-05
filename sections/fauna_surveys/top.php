<?php

/**
 * List of the fauna sightings: the fauna map above the list (the caves and
 * artificial cavities with sightings, openkis_fauna.php), framed on the
 * points, with the link to the full-page map (section fauna_map). Only the
 * users who may view this section get here, and the map returns their
 * sightings only.
 */
global $_FN;
echo "<div class=\"card openkis-card mb-3\"><div class=\"card-body p-0\">";
echo "<iframe style=\"border:0px;width:100%;height:450px;display:block\" title=\"" . FN_Translate("fauna map") . "\" src=\"{$_FN['siteurl']}bs_map.htm?fauna=1&lat={$_FN['default_latitude']}&lon={$_FN['default_longitude']}&zoom={$_FN['default_zoom']}\"></iframe>";
echo "</div><div class=\"card-footer small text-muted\">" . FN_Translate("the colour of a point is the ecological category of the species most bound to the underground, its size the number of species");
if (file_exists("sections/fauna_map") && FN_UserCanViewSection("fauna_map"))
{
    echo " - <a href=\"" . FN_RewriteLink("index.php?mod=fauna_map") . "\">" . FN_Translate("full-page map") . "</a>";
}
echo "</div></div>";
