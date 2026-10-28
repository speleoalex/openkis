<?php

$icon = "{$_FN['siteurl']}" . openkis_GetIcon($row, $_FN['mod']);


// Blocks pushed into $GLOBALS['openkis_view_side'] are printed by
// sections/caves/viewheader.php in the side column of the sheet.
if (!isset($GLOBALS['openkis_view_side']) || !is_array($GLOBALS['openkis_view_side']))
{
    $GLOBALS['openkis_view_side'] = array();
}
if (!empty($row['latitude']))
{
    $zoom = 17;
    $baselayer = "";
    if (false !== strstr($row['coordinates_type'], "IGM") || false !== strstr($row['original_coordinates_type'], "IGM")) //
    {
        $baselayer = "IGM 1:25000";
    }
    //---- map card ---------------------------------------------------------->
    $side = "<div class=\"card openkis-side-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">" . FN_Translate("map") . "</h5></div>";
    $side .= "<iframe style=\"border:0px;width:100%;height:300px;\" src=\"{$_FN['siteurl']}bs_map.htm?mod={$_FN['mod']}&baselayer={$baselayer}&point=circle&lat={$row['latitude']}&lon={$row['longitude']}&zoom=$zoom&history={$row['id']}\"></iframe>";
    $side .= "<div class=\"card-body p-2\">";
    $side .= "<div class=\"small text-muted mb-1\">" . FN_Translate("calculate the route") . "</div>";
    $gmap_link = "//www.google.it/maps/dir//" . $row['latitude'] . "," . $row['longitude'] . "/@" . $row['latitude'] . "," . $row['longitude'] . ",18z";
    $side .= "<a class=\"btn btn-sm btn-outline-secondary m-1\" target=\"_blank\" href=\"$gmap_link\">GMaps</a>";
    $ors_link = "https://maps.openrouteservice.org/directions?n1=44.075342&n2=9.807849&n3=17&a=null,null," . $row['latitude'] . "," . $row['longitude'] . "&b=0&c=0&k1=it&k2=km";
    $side .= "<a class=\"btn btn-sm btn-outline-secondary m-1\" target=\"_blank\" href=\"$ors_link\">OpenRouteService</a>";
    $osmanLink = "https://www.openstreetmap.org/directions?engine=graphhopper_foot&route={$row['latitude']}%2C{$row['longitude']}%3B{$row['latitude']}%2C{$row['longitude']}";
    $side .= "<a class=\"btn btn-sm btn-outline-secondary m-1\" target=\"_blank\" href=\"$osmanLink\">OpenStreetmap</a>";
    $forcename = openkis_TextToAscii($row['name']);
    $side .= "<a class=\"btn btn-sm btn-outline-secondary m-1\" target=\"_blank\" href=\"{$_FN['siteurl']}openkis_kml.php?mod={$_FN['mod']}&big_icons=1&filter_code=" . urlencode($row['code']) . "&filename=$forcename&absolute=1\">KML</a>";
    $side .= "</div></div>";
    $GLOBALS['openkis_view_side'][] = $side;
    //---- map card ----------------------------------------------------------<

    //---- coordinates card: decimal degrees, sexagesimal (DMS) and UTM ------>
    $position = openkis_GetItemPosition($row);
    $side = "<div class=\"card openkis-side-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">" . FN_Translate("coordinates WGS84") . "</h5></div>";
    $side .= "<div class=\"card-body p-2 small\">";
    $side .= "<div class=\"text-muted\">Gradi decimali (Lon, Lat)</div><input class=\"form-control form-control-sm mb-2\" readonly value=\"{$row['longitude']},{$row['latitude']}\" onclick=\"this.select();\" />";
    $side .= "<div class=\"text-muted\">Gradi sessagesimali (Lon, Lat)</div><input class=\"form-control form-control-sm mb-2\" readonly value=\"{$position['lon_dms']},{$position['lat_dms']}\" onclick=\"this.select();\" />";
    $side .= "<div class=\"text-muted\">UTM (X, Y, fuso)</div><input class=\"form-control form-control-sm\" readonly value=\"{$position['x']},{$position['y']},{$position['zone']}\" onclick=\"this.select();\" />";
    $side .= "</div></div>";
    $GLOBALS['openkis_view_side'][] = $side;
    //---- coordinates card --------------------------------------------------<
}
$config = FN_LoadConfig("", "bibliography");
$biblio = new FNDBVIEW($config);
$params['appendquery'] = "codecaves LIKE '{$row['code']}' OR codecaves LIKE '{$row['code']},%' OR codecaves LIKE '%,{$row['code']},%' OR codecaves LIKE '%,{$row['code']}' ";
$params['fields'] = "id,title,authors,year";
$biblio_items = $biblio->GetResults(false, $params);
$biblio_items = FN_ArraySortByKey($biblio_items, "year");
if (is_array($biblio_items) && count($biblio_items))
{
    echo "<div class=\"card openkis-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">" . FN_Translate("bibliography") . "</h5></div>";
    echo "<div class=\"card-body p-0\"><div class=\"table-responsive\"><table class=\"table table-sm table-striped mb-0\">";
    foreach ($biblio_items as $biblio_item)
    {
        $url = FN_RewriteLink("index.php?mod=bibliography&op=view&id={$biblio_item['id']}");
        echo "<tr><td class=\"text-nowrap\">{$biblio_item['year']}</td><td><a href=\"$url\">{$biblio_item['title']}</a></td><td>{$biblio_item['authors']}</td><td class=\"text-right\"><a class=\"btn btn-sm btn-outline-primary\" href=\"$url\">" . FN_Translate("view") . "</a></td></tr>";
    }
    echo "</table></div></div></div>";
}
//----tracer tests: the ones starting or ending in this cave------------------->
if (function_exists("openkis_TracingsCaveCard"))
{
    echo openkis_TracingsCaveCard($row['code']);
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
        if (!empty($survey_item['filelas']) && file_exists("misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelas/{$survey_item['filelas']}"))
        {
            $lasurl = "{$_FN['siteurl']}misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelas/" . rawurlencode($survey_item['filelas']);
            $las_href = "{$_FN['siteurl']}lasviewer/viewer.html?url=" . urlencode($lasurl);
            $htmllas .= "<h5>" . FN_Translate("3D Viewer") . ": " . htmlspecialchars($survey_item['name']) . "</h5>";
            $htmllas .= "<a class=\"btn btn-primary m-1\" href=\"$lasurl\" target=\"_blank\">" . FN_Translate("download") . " 3D</a>";
            $htmllas .= "<a class=\"btn btn-primary m-1\" href=\"$las_href\" target=\"_blank\">" . FN_Translate("open") . " 3D</a>";
            $htmllas .= "<iframe style=\"width:100%;height:800px;border:0px\" src=\"$las_href\" allowfullscreen allow=\"fullscreen\"></iframe>";
        }
        if (!empty($survey_item['filelox']) && file_exists("misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelox/{$survey_item['filelox']}"))
        {
            $count3d++;
            $file = urlencode("misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelox/{$survey_item['filelox']}");
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
