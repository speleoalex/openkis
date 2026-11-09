<?php
/**
 * Liguria, survey sheet of an artificial cavity: embedded 3D viewer of its LAS
 * point cloud (ctl_surveys_artificials.filelas), below the sheet.
 * Included by sections/survey_artificials/viewfooter.php with the record in $row.
 */
require_once "extra/openkis/custom/liguria/liguria_las.inc.php";
if (!empty($row['id']) && !empty($row['filelas']))
{
    echo liguria_LasViewerHtml("ctl_surveys_artificials", $row['id'], $row['filelas'], isset($row['name']) ? $row['name'] : "");
}
