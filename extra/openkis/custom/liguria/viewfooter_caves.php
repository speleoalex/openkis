<?php

$icon = "{$_FN['siteurl']}" . openkis_GetIcon($row, $_FN['mod']);


// Blocks pushed into $GLOBALS['openkis_view_side'] are printed by
// sections/caves/viewheader.php in the side column of the sheet.
if (!isset($GLOBALS['openkis_view_side']) || !is_array($GLOBALS['openkis_view_side']))
{
    $GLOBALS['openkis_view_side'] = array();
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
echo openkis_SheetBibliographyCard($biblio_items);
//----tracer tests: the ones starting or ending in this cave------------------->
if (function_exists("openkis_TracingsCaveCard"))
{
    echo openkis_TracingsCaveCard($row['code']);
}
//----species observed in this cave (section fauna_surveys)-------------------->
require_once "extra/openkis/openkis_fauna.inc.php";
echo openkis_FaunaCaveCard($row['code']);
//----paleontological and archaeological findings in this cave---------------->
echo openkis_FindingsCavityCard($row['code']);
//----temperatures: measurements and monitorings in this cave------------------>
if (function_exists("openkis_TemperaturesCaveCard"))
{
    echo openkis_TemperaturesCaveCard($row['code']);
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
function DrawFile($file)
{
    global $_FN;
    $color[0] = "#ffffff";
    $color[1] = "#eaeaea";
    $color2[0] = "#f3e49c";
    $color2[1] = "#f3e4c8";
    $tit = htmlspecialchars(basename($file));
    // the path may contain spaces and special characters: encode it in the URL
    $fileurl = str_replace("%2F", "/", rawurlencode($file));
    $l2 = $_FN['siteurl'] . "ops_getfile.php?f=$fileurl";
    $ext = strtolower(FN_GetFileExtension($file));
    switch ($ext)
    {
        case "pdf":
            $icon = $_FN['siteurl'] . "images/mime/pdf.png";
            break;
        case "doc":
        case "docx":
        case "odt":
        case "rtf":
            $icon = $_FN['siteurl'] . "images/mime/doc.png";
            break;
        case "txt":
        case "csv":
        case "tro":
        case "th":
        case "svx":
            $icon = $_FN['siteurl'] . "images/mime/text.png";
            break;
        default:
            $icon = $_FN['siteurl'] . "images/mime/unknown.png";
    }
    if ($ext == "jpg" || $ext == "jpeg" || $ext == "png")
    {
        $icon = "{$_FN['siteurl']}thumb.php?f=$fileurl&amp;h=300&amp;w=300";
    }
    echo "\n<div class=\"card col-12  col-xs-12 col-sm-3 col-lg-2\"  >";
    echo "<a class=\"\" target=\"_blank\" href=\"$l2\"><img class=\"card-img-top\" src='$icon' alt=\"\" /></a>";
    echo "<div class=\"card-body\">";
    echo "<p class=\"card-text\"><a target=\"_blank\" class=\"\" href=\"$l2\">$tit</a></p>";
    echo "</div>";
    // echo "<div class=\"card-footer bg-transparent border-success\"><a class=\"btn btn-primary\" target=\"_blank\" href=\"$l2\">Apri</a></div>";
    echo "</div>";
}

$id = str_replace("LI", "", $row['code']);

if (file_exists("../nextcloud/data/catasto/files/scansioni_Issel/"))
{
    $idfolder = $str = sprintf("%04d", $id);
    $list = glob("../nextcloud/data/catasto/files/scansioni_Issel/{$id}");

    if ($list || count($list) < 1)
        $list = glob("../nextcloud/data/catasto/files/scansioni_Issel/{$idfolder}");
    if ($list || count($list) < 1)
        $list = glob("../nextcloud/data/catasto/files/scansioni_Issel/{$idfolder}_*");

    if ($list && count($list) > 0)
    {
        echo "<div class=\"card openkis-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">Schede Storiche Catasto G.S.L. Issel</h5></div>";
        echo "<div class=\"card-body\" style=\"max-height:400px;overflow:auto;\">";
        echo "<div class=\"card-columns row\">";
        foreach ($list as $item)
        {
            $files = glob("{$item}/*");
            foreach ($files as $file)
            {
                if (!is_dir($file))
                {
                    DrawFile($file);
                } else
                {
                    $allfiles = glob("$file/*.*");
                    foreach ($allfiles as $file_item)
                    {
                        if (!is_dir($file_item))
                        {
                            DrawFile($file_item);
                        }
                    }
                }
            }
        }
        echo "</div>";
        echo "</div></div>";
    }
}



if (file_exists("../nextcloud/data/catasto/files/scansioni_Zoja/"))
{
    $idfolder = $str = sprintf("%04d", $id);
    $list = glob("../nextcloud/data/catasto/files/scansioni_Zoja/{$id}");
    if ($list || count($list) < 1)
        $list = glob("../nextcloud/data/catasto/files/scansioni_Zoja/{$idfolder}");
    if ($list || count($list) < 1)
        $list = glob("../nextcloud/data/catasto/files/scansioni_Zoja/{$idfolder}_*");
    if ($list || count($list) < 1)
        $list = glob("../nextcloud/data/catasto/files/scansioni_Zoja/{$idfolder}*");

    if ($list && count($list) > 0)
    {
        echo "<div class=\"card openkis-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">Schede Storiche Catasto G.S.L. Issel - Aggiornamento Zoja</h5></div>";
        echo "<div class=\"card-body\" style=\"max-height:400px;overflow:auto;\">";
        echo "<div class=\"card-columns row\">";
        foreach ($list as $item)
        {
            $files = glob("{$item}/*");
            foreach ($files as $file)
            {
                if (!is_dir($file))
                {
                    DrawFile($file);
                } else
                {
                    $allfiles = glob("$file/*.*");
                    foreach ($allfiles as $file_item)
                    {
                        if (!is_dir($file_item))
                        {
                            DrawFile($file_item);
                        }
                    }
                }
            }
        }
        echo "</div>";
        echo "</div></div>";
    }
}



if (file_exists("../nextcloud/data/catasto/files/schede_dsl/"))
{
    $idfolder = $str = sprintf("%04d", $id);
    $list = glob("../nextcloud/data/catasto/files/schede_dsl/{$id}");
    if ($list || count($list) < 1)
        $list = glob("../nextcloud/data/catasto/files/schede_dsl/{$idfolder}");
    if ($list || count($list) < 1)
        $list = glob("../nextcloud/data/catasto/files/schede_dsl/{$idfolder}_*");
    if ($list && count($list) > 0)
    {
        echo "<div class=\"card openkis-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">Documentazione Delegazione Speleologica Ligure</h5></div>";
        echo "<div class=\"card-body\">";
        echo "<div class=\"card-columns row\">";
        if (FN_IsAdmin() || FN_UserInGroup($_FN['user'], "w_caves"))
        {
            foreach ($list as $item)
            {
                $files = glob("{$item}/*");
                foreach ($files as $file)
                {
                    if (!is_dir($file))
                    {
                        if (basename($file) != "Thumbs.db")
                            DrawFile($file);
                    } else
                    {
                        $allfiles = glob("$file/*.*");
                        foreach ($allfiles as $file_item)
                        {
                            if (!is_dir($file_item))
                            {
                                if (basename($file_item) != "Thumbs.db")
                                    DrawFile($file_item);
                            }
                        }
                    }
                }
            }
        } else
        {
            echo "la visualizzazione della documentazione DSL è riservata ai curatori, eseguire il login per visualizzare le schede";
        }

        echo "</div>";
        echo "</div></div>";
    }
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
