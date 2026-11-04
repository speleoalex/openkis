<?php
/**
 * Liguria, artificial cavity sheet: embedded 3D viewer of the LAS point clouds
 * of its surveys (ctl_surveys_artificials.filelas), below the sheet. The file
 * is served by openkis_file.php (protected files), only to who may read it.
 */
global $_FN, $row;
if (empty($row['code']))
    return;
$config = FN_LoadConfig("", "survey_artificials");
$survey = new FNDBVIEW($config);
$params = array();
$params['appendquery'] = "codeartificial LIKE '" . addslashes($row['code']) . "'";
$params['fields'] = "id,title,filelas,priority";
$items = $survey->GetResults(false, $params);
if (!is_array($items) || !count($items))
    return;
$items = FN_ArraySortByKey($items, "priority");
foreach ($items as $survey_item)
{
    if (empty($survey_item['filelas']) || !file_exists("misc/fndatabase/ctl_surveys_artificials/{$survey_item['id']}/filelas/{$survey_item['filelas']}"))
        continue;
    if (!openkis_FileAccess("ctl_surveys_artificials", $survey_item['id'], "filelas"))
        continue;
    $lasurl = "{$_FN['siteurl']}" . openkis_CaveViewerFile("ctl_surveys_artificials", $survey_item['id'], "filelas", $survey_item['filelas']);
    $las_href = "{$_FN['siteurl']}lasviewer/viewer.html?url=" . urlencode($lasurl);
    echo "<h5>" . FN_Translate("3D Viewer") . ": " . htmlspecialchars((string) $survey_item['title']) . "</h5>";
    echo "<a class=\"btn btn-primary m-1\" href=\"$lasurl\" target=\"_blank\">" . FN_Translate("download") . " 3D</a> ";
    echo "<a class=\"btn btn-primary m-1\" href=\"$las_href\" target=\"_blank\">" . FN_Translate("open") . " 3D</a>";
    echo "<iframe style=\"width:100%;height:800px;border:0px\" src=\"$las_href\" allowfullscreen allow=\"fullscreen\"></iframe>";
}
