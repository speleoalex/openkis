<?php
/**
 * Liguria, survey sheet of a cave: embedded 3D viewer of its LAS point cloud
 * (ctl_surveys.filelas), below the sheet.
 * Included by sections/survey/viewfooter.php with the record in $row.
 */
require_once "extra/openkis/custom/liguria/liguria_las.inc.php";
if (!empty($row['id']) && !empty($row['filelas']))
{
    echo liguria_LasViewerHtml("ctl_surveys", $row['id'], $row['filelas'], isset($row['name']) ? $row['name'] : "");
}
