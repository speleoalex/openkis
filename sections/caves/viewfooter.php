<?php

global $_FN;
/**
 * Cave sheet footer. Runs before viewheader.php: blocks pushed into
 * $GLOBALS['openkis_view_side'] are printed by viewheader.php in the side
 * column, while echoed html goes below the sheet at full width.
 */
$GLOBALS['openkis_view_side'] = array();
$icon = "{$_FN['siteurl']}" . openkis_GetIcon($row, $_FN['mod']);
if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php"))
{

    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php");

} else
{
    $icon = "{$_FN['siteurl']}" . openkis_GetIcon($row, $_FN['mod']);
    if (!empty($row['latitude']))
    {
        $zoom = 17;
        $baselayer = "";
        if (false !== strstr($row['coordinates_type'], "IGM") || false !== strstr($row['original_coordinates_type'], "IGM")) //
        {
            $baselayer = "IGM 1:25000";
        }
        // map card, shown in the side column
        $side = "<div class=\"card openkis-side-card mb-3\"><div class=\"card-header\"><h5 class=\"mb-0\">" . FN_Translate("map") . "</h5></div>";
        $side .= "<iframe style=\"border:0px;width:100%;height:300px;\" src=\"{$_FN['siteurl']}bs_map.htm?mod={$_FN['mod']}&baselayer={$baselayer}&point=circle&lat={$row['latitude']}&lon={$row['longitude']}&zoom=$zoom&history={$row['id']}\"></iframe>";
        $side .= "<div class=\"card-body p-2\">";
        $side .= "<div class=\"small text-muted mb-1\">" . FN_Translate("coordinates WGS84") . "</div>";
        $side .= "<input class=\"form-control form-control-sm mb-2\" readonly value=\"{$row['latitude']},{$row['longitude']}\" onclick=\"this.select();\" />";
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
    //survey------------------------------------------------------------------->
    $config = FN_LoadConfig("", "survey");
    $survey = new FNDBVIEW($config);
    $params['appendquery'] = "codecave LIKE '{$row['code']}'";
    $params['fields'] = "id,title,filelox,priority";
    $items = $survey->GetResults(false, $params);
    $items = FN_ArraySortByKey($items, "priority");
    if (is_array($items) && count($items))
    {
        foreach ($items as $survey_item)
        {
            if (!empty($survey_item['filelox']))
            {
                $file = urlencode("misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelox/{$survey_item['filelox']}");
                $iframe_href = "{$_FN['siteurl']}cave_viewer.php?f={$file}";
                //echo "<br/><a href=\"$iframe_href\" target=\"_blank\">" . FN_Translate("open") . "</a><br />";
                echo "<iframe style=\"width:100%;height:800px;border:0px\" src=\"$iframe_href\"></iframe>";
            }
        }
    }
    //survey-------------------------------------------------------------------<
    
}
?>