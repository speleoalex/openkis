<?php
/**
 * Spring sheet footer. Runs before viewheader.php: blocks pushed into
 * $GLOBALS['openkis_view_side'] are printed by viewheader.php in the side
 * column, while echoed html goes below the sheet at full width.
 * A region can replace it with extra/openkis/custom/{custom}/viewfooter_springs.php.
 */
global $_FN;
require_once "extra/openkis/openkis_sheet.inc.php";
$GLOBALS['openkis_view_side'] = array();
if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php");
}
else
{
    // map, route links and coordinates in the side column
    $GLOBALS['openkis_view_side'] = openkis_SheetMapCards($_FN['mod'], $row);
    // tracer tests detected at this spring
    if (function_exists("openkis_TracingsSpringCard"))
    {
        echo openkis_TracingsSpringCard($row['code']);
    }
}
