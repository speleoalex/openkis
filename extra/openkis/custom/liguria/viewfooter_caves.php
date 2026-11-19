<?php

$icon = "{$_FN['siteurl']}" . openkis_GetIcon($row, $_FN['mod']);


// Blocks pushed into $GLOBALS['openkis_view_side'] are printed by
// sections/caves/viewheader.php in the side column of the sheet, those pushed
// into $GLOBALS['openkis_view_main'] in the main column under the field cards.
if (!isset($GLOBALS['openkis_view_side']) || !is_array($GLOBALS['openkis_view_side']))
{
    $GLOBALS['openkis_view_side'] = array();
}
if (!isset($GLOBALS['openkis_view_main']) || !is_array($GLOBALS['openkis_view_main']))
{
    $GLOBALS['openkis_view_main'] = array();
}
//---- map, route links and coordinates (decimal, DMS, UTM) in the side column -->
require_once "extra/openkis/openkis_sheet.inc.php";
$GLOBALS['openkis_view_side'] = array_merge($GLOBALS['openkis_view_side'], openkis_SheetMapCards($_FN['mod'], $row));
// items linked to this code, through the bibliography link table
$biblio_items = array();
$biblio_filter = openkis_BibliographyFilter($row['code'], "codecaves");
if ($biblio_filter !== false)
{
    $config = FN_LoadConfig("", "bibliography");
    $biblio = new FNDBVIEW($config);
    $params['appendquery'] = $biblio_filter;
    $params['fields'] = "id,title,authors,year";
    $biblio_items = $biblio->GetResults(false, $params);
    $biblio_items = FN_ArraySortByKey($biblio_items, "year");
}
//----bibliography card in the main column, under the field cards-------------->
if (($biblio_html = openkis_SheetBibliographyCard($biblio_items)) != "")
    $GLOBALS['openkis_view_main'][] = $biblio_html;
//----tracer tests: the ones starting or ending in this cave, in the main column
if (function_exists("openkis_TracingsCaveCard"))
{
    if (($card_html = openkis_TracingsCaveCard($row['code'])) != "")
        $GLOBALS['openkis_view_main'][] = $card_html;
}
//----species observed in this cave (section fauna_surveys), in the main column
require_once "extra/openkis/openkis_fauna.inc.php";
if (($card_html = openkis_FaunaCaveCard($row['code'])) != "")
    $GLOBALS['openkis_view_main'][] = $card_html;
//----paleontological and archaeological findings in this cave---------------->
echo openkis_FindingsCavityCard($row['code']);
//----temperatures: measurements and monitorings in this cave, in the main column
if (function_exists("openkis_TemperaturesCaveCard"))
{
    if (($card_html = openkis_TemperaturesCaveCard($row['code'])) != "")
        $GLOBALS['openkis_view_main'][] = $card_html;
}
//----survey------------------------------------------------------------------->
$config = FN_LoadConfig("", "survey");
$survey = new FNDBVIEW($config);
$params['appendquery'] = "codecave LIKE '{$row['code']}'";
$params['fields'] = "id,name,filelox,filelas,priority";

$items = array();

$items = $survey->GetResults($config, $params);
if (is_array($items) && count($items))
{
    $items = FN_ArraySortByKey($items, "priority");
    $html3d = "";
    $htmllas = "";
    $count3d = 0;
    foreach ($items as $survey_item)
    {
        // LAS point cloud: embedded viewer, same as artificial cavities
        if (!empty($survey_item['filelas']) && file_exists("misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelas/{$survey_item['filelas']}") && openkis_FileAccess("ctl_surveys", $survey_item['id'], "filelas"))
        {
            $lasurl = "{$_FN['siteurl']}" . openkis_CaveViewerFile("ctl_surveys", $survey_item['id'], "filelas", $survey_item['filelas']);
            $las_href = "{$_FN['siteurl']}lasviewer/viewer.html?url=" . urlencode($lasurl);
            $htmllas .= "<h5>" . FN_Translate("3D Viewer") . ": " . htmlspecialchars($survey_item['name']) . "</h5>";
            $htmllas .= "<a class=\"btn btn-primary m-1\" href=\"$lasurl\" target=\"_blank\">" . FN_Translate("download") . " 3D</a>";
            $htmllas .= "<a class=\"btn btn-primary m-1\" href=\"$las_href\" target=\"_blank\">" . FN_Translate("open") . " 3D</a>";
            $htmllas .= "<iframe style=\"width:100%;height:800px;border:0px\" src=\"$las_href\" allowfullscreen allow=\"fullscreen\"></iframe>";
        }
        if (!empty($survey_item['filelox']) && file_exists("misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelox/{$survey_item['filelox']}") && openkis_FileAccess("ctl_surveys", $survey_item['id'], "filelox"))
        {
            $count3d++;
            $file = urlencode(openkis_CaveViewerFile("ctl_surveys", $survey_item['id'], "filelox", $survey_item['filelox']));
            $iframe_href = "{$_FN['siteurl']}openkis_cave_viewer.php?f={$file}";
            // Use the survey name as button label, fall back to a numbered generic label
            $title3d = trim((string) $survey_item['name']);
            if ($title3d === "")
            {
                $title3d = "Modello 3D" . ($count3d > 1 ? " $count3d" : "");
            }
            $html3d .= "<a class=\"btn btn-sm btn-primary m-1\" href=\"$iframe_href\" target=\"_blank\">&#10696; " . htmlspecialchars($title3d) . "</a>";
        }
    }
    if ($html3d !== "")
    {
        $GLOBALS['openkis_view_side'][] = "<div class=\"card openkis-side-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">" . FN_Translate("3D models") . "</h5></div><div class=\"card-body p-2\">$html3d</div></div>";
    }
    if ($htmllas !== "")
    {
        echo "<div class=\"card openkis-card mb-3\"><div class=\"card-body\">";
        echo $htmllas;
        echo "</div></div>";
    }
}

//----survey-------------------------------------------------------------------<
//-----------SCANSIONI--------------------------------------------------------->
/**
 * Files of the historical sheets of a cave: the scans of the old cadastral
 * registers, one folder per cave ("0042_name"), files one level of subfolders
 * deep. Folders are looked up by exact code, then zero padded, then zero padded
 * with $wildcard ("_*" for the archives that separate the code from the title,
 * "*" for the ones that do not). Windows thumbnails are skipped.
 *
 * @param string $basedir folder of the archive
 * @param string $id cave code without the regional prefix
 * @param string $wildcard suffix pattern of the folder names
 * @return array file paths, empty when the cave has no sheet
 */
function openkis_LiguriaHistoricFiles($basedir, $id, $wildcard = "_*")
{
    $padded = sprintf("%04d", $id);
    $folders = array();
    foreach (array("{$basedir}/{$id}", "{$basedir}/{$padded}", "{$basedir}/{$padded}{$wildcard}") as $pattern)
    {
        $folders = glob($pattern);
        if ($folders && count($folders))
            break;
    }
    $files = array();
    foreach ((array) $folders as $folder)
    {
        foreach ((array) glob("{$folder}/*") as $item)
        {
            if (is_dir($item))
                $files = array_merge($files, (array) glob("{$item}/*.*"));
            else
                $files[] = $item;
        }
    }
    return array_values(array_filter($files, function ($file)
    {
        return !is_dir($file) && basename($file) != "Thumbs.db";
    }));
}

/**
 * Card of one file of the historical sheets: icon of the type (a thumbnail for
 * images), link to ops_getfile.php. Template historic_file_card.tp.html.
 *
 * @param string $file path of the file
 * @return string html
 */
function openkis_LiguriaHistoricFileCard($file)
{
    global $_FN;
    // the path may contain spaces and special characters: encode it in the URL
    $fileurl = str_replace("%2F", "/", rawurlencode($file));
    $ext = strtolower(FN_GetFileExtension($file));
    if (in_array($ext, array("doc", "docx", "odt", "rtf")))
        $icon = "images/mime/doc.png";
    elseif (in_array($ext, array("txt", "csv", "tro", "th", "svx")))
        $icon = "images/mime/text.png";
    elseif ($ext == "pdf")
        $icon = "images/mime/pdf.png";
    else
        $icon = "images/mime/unknown.png";
    if ($ext == "jpg" || $ext == "jpeg" || $ext == "png")
        $icon = "thumb.php?f={$fileurl}&amp;h=300&amp;w=300";
    return openkis_TplApply("historic_file_card", array(
        "url" => $_FN['siteurl'] . "ops_getfile.php?f={$fileurl}",
        "icon" => $_FN['siteurl'] . $icon,
        "title" => htmlspecialchars(basename($file)),
    ));
}

/**
 * Card with a grid of files of the historical sheets, template
 * historic_files_card.tp.html; $content is the files or a message.
 *
 * @param string $title
 * @param string $content html inside the grid
 * @param bool $scroll true when the body scrolls (many files)
 * @return string html
 */
function openkis_LiguriaHistoricFilesCard($title, $content, $scroll)
{
    return openkis_TplApply("historic_files_card", array(
        "title" => $title,
        "content" => $content,
        "scroll" => $scroll,
    ));
}

$id = str_replace("LI", "", $row['code']);
$archives = array(
    "scansioni_Issel" => array("title" => "Schede Storiche Catasto G.S.L. Issel", "wildcard" => "_*", "scroll" => true),
    "scansioni_Zoja" => array("title" => "Schede Storiche Catasto G.S.L. Issel - Aggiornamento Zoja", "wildcard" => "*", "scroll" => true),
    "schede_dsl" => array("title" => "Documentazione Delegazione Speleologica Ligure", "wildcard" => "_*", "scroll" => false, "restricted" => true),
);
foreach ($archives as $folder => $archive)
{
    $files = openkis_LiguriaHistoricFiles("../nextcloud/data/catasto/files/{$folder}", $id, $archive['wildcard']);
    if (!count($files))
        continue;
    if (!empty($archive['restricted']) && !FN_IsAdmin() && !FN_UserInGroup($_FN['user'], "w_caves"))
        $content = "la visualizzazione della documentazione DSL è riservata ai curatori, eseguire il login per visualizzare le schede";
    else
        $content = implode("", array_map("openkis_LiguriaHistoricFileCard", $files));
    echo openkis_LiguriaHistoricFilesCard($archive['title'], $content, $archive['scroll']);
}
//-----------SCANSIONI---------------------------------------------------------<


//------------------generazione scheda catastale------------------------>
if (!empty($_GET['export_pdf']))
{
    include(__DIR__."/pdf/pdf.php");
    die();
}
$urlexport = FN_RewriteLink("index.php?mod={$_FN['mod']}&op=view&id={$row['id']}&export_pdf=1");
$urlexportpdf = FN_RewriteLink("index.php?mod={$_FN['mod']}&op=view&id={$row['id']}&export_pdf=1&format=pdf");
$side = "<div class=\"card openkis-side-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">" . FN_Translate("cadastral sheet") . "</h5></div><div class=\"card-body p-2\">";
$side .= "<a class=\"btn btn-sm btn-primary m-1\" href=\"$urlexport\" target=\"_blank\">" . FN_Translate("Genera scheda catastale") . "</a>";
$side .= "<a class=\"btn btn-sm btn-secondary m-1\" href=\"$urlexportpdf\" target=\"_blank\">Scarica PDF</a>";
$side .= "</div></div>";
$GLOBALS['openkis_view_side'][] = $side;

//------------------generazione scheda catastale------------------------<


?>
