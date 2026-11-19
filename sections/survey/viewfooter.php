<?php
global $_FN;
if (!empty($row['filelox']) && openkis_FileAccess("ctl_surveys", $row['id'], "filelox"))
{
    $file = urlencode(openkis_CaveViewerFile("ctl_surveys", $row['id'], "filelox", $row['filelox']));
    echo openkis_TplApply("cave_viewer", array(
        "src" => "{$_FN['siteurl']}cave_viewer.php?f={$file}",
        "open" => FN_Translate("open"),
    ));
}
// regional additions to the sheet (extra/openkis/custom/<region>/viewfooter_survey.php)
if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php"))
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php");
