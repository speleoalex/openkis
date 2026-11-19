<?php

/**
 * List of the fauna sightings: the fauna map above the list (the caves and
 * artificial cavities with sightings, openkis_fauna.php), framed on the
 * points, with the link to the full-page map (section fauna_map). Only the
 * users who may view this section get here, and the map returns their
 * sightings only.
 */
global $_FN;
$extra = "";
if (file_exists("sections/fauna_map") && FN_UserCanViewSection("fauna_map"))
    $extra = "<a href=\"" . FN_RewriteLink("index.php?mod=fauna_map") . "\">" . FN_Translate("full-page map") . "</a>";
echo openkis_ListMapCard("fauna=1", FN_Translate("the colour of a point is the ecological category of the species most bound to the underground, its size the number of species"), FN_Translate("fauna map"), $extra);
