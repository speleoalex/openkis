<?php

/**
 * List of the findings: the map of the findings above the list (the caves and
 * artificial cavities with findings, openkis_findings.php), framed on the
 * points, with the link to the full-page map (section findings_map). The
 * restricted findings show only their category on the map.
 */
global $_FN;
echo "<div class=\"card openkis-card mb-3\"><div class=\"card-body p-0\">";
echo "<iframe style=\"border:0px;width:100%;height:450px;display:block\" title=\"" . FN_Translate("findings map") . "\" src=\"{$_FN['siteurl']}bs_map.htm?findings=1&lat={$_FN['default_latitude']}&lon={$_FN['default_longitude']}&zoom={$_FN['default_zoom']}\"></iframe>";
echo "</div><div class=\"card-footer small text-muted\">" . FN_Translate("the colour of a point is the category of its findings, its size the number of findings");
if (file_exists("sections/findings_map") && FN_UserCanViewSection("findings_map"))
{
    echo " - <a href=\"" . FN_RewriteLink("index.php?mod=findings_map") . "\">" . FN_Translate("full-page map") . "</a>";
}
echo "</div></div>";
