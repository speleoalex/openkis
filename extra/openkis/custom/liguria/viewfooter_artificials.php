<?php
/**
 * Liguria, artificial cavity sheet: embedded 3D viewer of the LAS point clouds
 * of its surveys (ctl_surveys_artificials.filelas), below the sheet.
 * Included by sections/artificials/viewfooter.php inside the method that renders
 * the record: $row is a local variable there, do not declare it global.
 */
global $_FN;
require_once "extra/openkis/custom/liguria/liguria_las.inc.php";
if (empty($row['code']))
    return;
$config = FN_LoadConfig("", "survey_artificials");
$survey = new FNDBVIEW($config);
$params = array();
$params['appendquery'] = "codeartificial LIKE '" . addslashes($row['code']) . "'";
$params['fields'] = "id,name,filelas,priority";
$items = $survey->GetResults(false, $params);
if (!is_array($items) || !count($items))
    return;
$items = FN_ArraySortByKey($items, "priority");
foreach ($items as $survey_item)
{
    echo liguria_LasViewerHtml("ctl_surveys_artificials", $survey_item['id'], $survey_item['filelas'], $survey_item['name']);
}
