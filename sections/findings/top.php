<?php

/**
 * List of the findings: the map of the findings above the list (the caves and
 * artificial cavities with findings, openkis_findings.php), framed on the
 * points, with the link to the full-page map (section findings_map). The
 * restricted findings show only their category on the map.
 */
global $_FN;
$extra = "";
if (file_exists("sections/findings_map") && FN_UserCanViewSection("findings_map"))
    $extra = "<a href=\"" . FN_RewriteLink("index.php?mod=findings_map") . "\">" . FN_Translate("full-page map") . "</a>";
echo openkis_ListMapCard("findings=1", FN_Translate("the colour of a point is the category of its findings, its size the number of findings"), FN_Translate("findings map"), $extra);
