<?php
/**
 * Survey sheet header: the code of the cavity (and of the cave system) is shown
 * as a link to its sheet, followed by the name of the cavity; the date of the
 * last update is always shown (openkis_SheetShowLastUpdate()).
 *
 * Included by FNDBVIEW::ViewRecordPage() with the form of the record in $Table
 * before the record is rendered with it (openkis_SheetLinkCodes()). Prints nothing.
 */
require_once "extra/openkis/openkis_sheet.inc.php";
if (isset($Table) && is_object($Table))
{
    openkis_SheetLinkCodes($Table);
    openkis_SheetShowLastUpdate($Table);
}
?>
