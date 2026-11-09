<?php
/**
 * Glacier sheet header: the date of the last update is always shown
 * (openkis_SheetShowLastUpdate(), extra/openkis/openkis_sheet.inc.php).
 *
 * Included by FNDBVIEW::ViewRecordPage() with the form of the record in $Table
 * before the record is rendered with it. Prints nothing.
 */
require_once "extra/openkis/openkis_sheet.inc.php";
if (isset($Table) && is_object($Table))
{
    openkis_SheetShowLastUpdate($Table);
}
?>
