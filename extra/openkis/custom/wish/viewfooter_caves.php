<?php

$icon = "{$_FN['siteurl']}" . openkis_GetIcon($row, $_FN['mod']);
$t_wish = FN_XmlTable("ctl_wishregioni");
$regione = $t_wish->GetRecord(array("id"=>$row['id_regione']));
//dprint_r($row);
//dprint_r($regione);
$img = $t_wish->getFilePath($regione, "photo1");
$url = $regione['portal'];
$link = $row['link'];

$title = $regione['title'];

echo "<div class=\"alert alert-info\">";
if ($title)
    echo "<h3 class=\"\">Dato fornito da: <b>$title</b></h3>";
echo "<div class=\"text-right\"><a target=\"_blank\"  href=\"$url\"><img src=\"$img\" style=\"max-width:100px;max-height:100px;\" ></a></div><br />";

echo "<div><br /><a class=\"btn btn-primary\" target=\"_blank\" href=\"$link\"> Visualizza la scheda completa</a></div>";
echo "</div>";
if (!empty($row['latitude']))
{
    $zoom = 17;
    $baselayer = "";
    if (false !== strstr($row['coordinates_type'], "IGM") || false !== strstr($row['original_coordinates_type'], "IGM")) //
    {
        $baselayer = "IGM 1:25000";
    }
    echo "<iframe style=\"border:0px;width:100%;height:400px;\" src=\"{$_FN['siteurl']}bs_map.htm?mod={$_FN['mod']}&baselayer={$baselayer}&point=circle&lat={$row['latitude']}&lon={$row['longitude']}&zoom=$zoom&history={$row['id']}\"></iframe>";

    $gmap_link = "//www.google.it/maps/dir//" . $row['latitude'] . "," . $row['longitude'] . "/@" . $row['latitude'] . "," . $row['longitude'] . ",18z";
    echo "<div class=\"alert alert-info\">";
    echo "<h5>" . FN_Translate("calculate the route") . ":</h5>";

    echo " <a class=\"btn btn-secondary\" target=\"_blank\" href=\"$gmap_link\">GMaps</a>";

    $gmap_link = "https://maps.openrouteservice.org/directions?n1=44.075342&n2=9.807849&n3=17&a=null,null," . $row['latitude'] . "," . $row['longitude'] . "&b=0&c=0&k1=it&k2=km";
    echo " <a class=\"btn btn-secondary\" target=\"_blank\" href=\"$gmap_link\">OpenRouteService</a>";


    $osmanLink = "https://www.openstreetmap.org/directions?engine=graphhopper_foot&route={$row['latitude']}%2C{$row['longitude']}%3B{$row['latitude']}%2C{$row['longitude']}";
    echo " <a class=\"btn btn-secondary\" target=\"_blank\" href=\"$osmanLink\">OpenStreetmap</a>";
    $forcename = openkis_TextToAscii($row['name']);
    echo " <a class=\"btn btn-secondary\" target=\"_blank\" href=\"{$_FN['siteurl']}openkis_kml.php?mod={$_FN['mod']}&big_icons=1&filter_code=" . urlencode($row['code']) . "&filename=$forcename&absolute=1\">Scarica kml</a>";
    echo "</div><br />";


    $position=openkis_GetItemPosition($row);
    echo "<div>Coordinate  WGS84 (Lon,Lat):<input class=\"form-input\" value=\"{$row['longitude']},{$row['latitude']}\" onclick=\"this.select();\" /></div>";
    
    echo "<div>Coordinate  WGS84 (Lon,Lat):<input class=\"form-input\" value=\"{$position['lon_dms']},{$position['lat_dms']}\" onclick=\"this.select();\" /></div>";
   
    echo "<div>Coordinate  WGS84 UTM (x,y,zone):<input class=\"form-input\" value=\"{$position['x']},{$position['y']},{$position['zone']}\" onclick=\"this.select();\" /></div>";
    
    
    
}
$config = FN_LoadConfig("", "bibliography");
$biblio = new FNDBVIEW($config);
$params['appendquery'] = "codecaves LIKE '{$row['code']}' OR codecaves LIKE '{$row['code']},%' OR codecaves LIKE '%,{$row['code']},%' OR codecaves LIKE '%,{$row['code']}' ";
$params['fields'] = "id,title,authors,year";
$biblio_items = $biblio->GetResults(false, $params);
$biblio_items = FN_ArraySortByKey($biblio_items, "year");
if (is_array($biblio_items) && count($biblio_items))
{
    echo "<div class=\"alert alert-warning\">";
    echo "<h3>" . FN_Translate("bibliography") . "</h3>";
    echo "<table class=\"table table-responsive\">";
    foreach ($biblio_items as $biblio_item)
    {
        $url = FN_RewriteLink("index.php?mod=bibliography&op=view&id={$biblio_item['id']}");
        echo "<tr><td>{$biblio_item['year']}</td><td>{$biblio_item['title']}</td><td>{$biblio_item['authors']}</td><td><a class=\"btn btn-primary\" href=\"$url\">" . FN_Translate("view") . "</a></td></tr>";
    }
    echo "</table>";
    echo "</div>";
}
//----survey------------------------------------------------------------------->
$config = FN_LoadConfig("", "survey");
$survey = new FNDBVIEW($config);
$params['appendquery'] = "codecave LIKE '{$row['code']}'";
$params['fields'] = "id,name,filelox,priority";

$items = array();

$items = $survey->GetResults($config, $params);
if (is_array($items) && count($items))
{
    $items = FN_ArraySortByKey($items, "priority");
    foreach ($items as $survey_item)
    {
        if (!empty($survey_item['filelox']) && file_exists("misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelox/{$survey_item['filelox']}"))
        {
            $file = urlencode("misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelox/{$survey_item['filelox']}");
            $iframe_href = "{$_FN['siteurl']}openkis_cave_viewer.php?f={$file}";
//            echo "<iframe style=\"width:100%;height:800px;border:0px\" src=\"$iframe_href\"></iframe>";
 //           dprint_r($survey_item);
            echo "<br/>{$survey_item['name']}: <a class=\"btn btn-primary\" href=\"$iframe_href\" target=\"_blank\">" . FN_Translate("3D Viewer") . "</a><br />";
        }
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
    $icon = $_FN['siteurl'] . "images/mime/pdf.png";
    $tit = basename($file);
    $l2 = $_FN['siteurl'] . "ops_getfile.php?f=$file";
    $ext = strtolower(FN_GetFileExtension($file));
    if ($ext == "jpg" || $ext == "jpeg" || $ext == "png")
    {
        $icon = "{$_FN['siteurl']}/thumb.php?f=$file&amp;h=300&amp;w=300";
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
        echo "<h3>Schede storiche Issel:</h3>";
        echo "<div style=\"max-height:400px;overflow:auto;border:1px solid;padding:10px;\">";
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
        echo "</div>";
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
        echo "<h3>Schede storiche Issel - Aggiornamento Zoja:</h3>";
        echo "<div style=\"max-height:400px;overflow:auto;border:1px solid;padding:10px;\">";
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
        echo "</div>";
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
        echo "<h3>Documentazione Delegazione Speleologica Ligure</h3>";
        echo "<div class=\"alert alert-warning\">";
        echo "<div class=\"card-columns row\">";
        if (FN_IsAdmin() || FN_UserInGroup($_FN['user'], "catastoscrittura"))
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
        echo "</div>";
    }
}


//-----------SCANSIONI---------------------------------------------------------<




?>
