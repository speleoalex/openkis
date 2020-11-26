<?php
global $_FN;
openkis_UpdateCoords("artificials",true);
if (!empty($row['latitude']))
{
    $zoom=17;
    $baselayer="";
    if (false!== strstr($row['coordinates_type'],"IGM") || false!== strstr($row['original_coordinates_type'],"IGM"))
    {
        $baselayer="IGM 1:25000";
    }
    echo "<iframe style=\"border:0px;width:100%;height:400px;\" src=\"{$_FN['siteurl']}bs_map.htm?mod={$_FN['mod']}&baselayer={$baselayer}&point=circle&lat={$row['latitude']}&lon={$row['longitude']}&zoom=$zoom\"></iframe>";
}

    $config=FN_LoadConfig("","bibliography");
    $biblio=new FNDBVIEW($config);
    $params['appendquery']="codeartificials LIKE '{$row['code']}' OR codeartificials LIKE '{$row['code']},%' OR codeartificials LIKE '%,{$row['code']},%' OR codeartificials LIKE '%,{$row['code']}' ";
    $params['fields']="id,title,authors,year";
    $biblio_items=$biblio->GetResults(false,$params);
    $biblio_items=FN_ArraySortByKey($biblio_items,"year");
    if (is_array($biblio_items) && count($biblio_items))
    {
        echo "<div class=\"alert alert-warning\">";
        echo "<h3>".FN_Translate("bibliography")."</h3>";
        echo "<table class=\"table table-responsive\">";
        foreach($biblio_items as $biblio_item)
        {
            $url=FN_RewriteLink("index.php?mod=bibliography&op=view&id={$biblio_item['id']}");
            echo "<tr><td>{$biblio_item['year']}</td><td>{$biblio_item['title']}</td><td>{$biblio_item['authors']}</td><td><a class=\"btn btn-primary\" href=\"$url\">".FN_Translate("view")."</a></td></tr>";
        }
        echo "</table>";
        echo "</div>";
    }

?>
