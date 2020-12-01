<?php

$config=FN_LoadConfig("","bibliography");
$biblio=new FNDBVIEW($config);
$params['appendquery']="fauna LIKE '{$row['scientific_name']}' OR fauna LIKE '{$row['scientific_name']}|%' OR fauna LIKE '%|{$row['scientific_name']}|%' OR fauna LIKE '%|{$row['scientific_name']}' ";
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


//dprint_r($params);
?>