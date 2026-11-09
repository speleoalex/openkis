<?php
/**
 * Liguria: 3D viewer of the LAS point clouds of the surveys (field filelas of
 * ctl_surveys and ctl_surveys_artificials, viewer in lasviewer/). The file is
 * served by openkis_file.php (protected files), only to who may read it.
 */

/**
 * Title, download and open buttons and the embedded viewer of the LAS file of
 * a survey; an empty string when the file is missing or the user may not read it.
 *
 * @param string $table ctl_surveys or ctl_surveys_artificials
 * @param string $id id of the survey
 * @param string $filename value of the field filelas
 * @param string $title title shown above the viewer, the file name when empty
 * @return string
 */
function liguria_LasViewerHtml($table, $id, $filename, $title = "")
{
    global $_FN;
    $filename = (string) $filename;
    if ($filename === "" || !file_exists("misc/fndatabase/$table/$id/filelas/$filename"))
        return "";
    if (!openkis_FileAccess($table, $id, "filelas"))
        return "";
    $lasurl = "{$_FN['siteurl']}" . openkis_CaveViewerFile($table, $id, "filelas", $filename);
    $las_href = "{$_FN['siteurl']}lasviewer/viewer.html?url=" . urlencode($lasurl);
    $title = trim((string) $title);
    if ($title === "")
        $title = $filename;
    $html = "<h5>" . FN_Translate("3D Viewer") . ": " . htmlspecialchars($title) . "</h5>";
    $html .= "<a class=\"btn btn-primary m-1\" href=\"" . htmlspecialchars($lasurl) . "\" target=\"_blank\">" . FN_Translate("download") . " 3D</a> ";
    $html .= "<a class=\"btn btn-primary m-1\" href=\"" . htmlspecialchars($las_href) . "\" target=\"_blank\">" . FN_Translate("open") . " 3D</a>";
    $html .= "<iframe style=\"width:100%;height:800px;border:0px\" src=\"" . htmlspecialchars($las_href) . "\" allowfullscreen allow=\"fullscreen\"></iframe>";
    return $html;
}
