<?php
global $_FN;
openkis_UpdateCoordsIfChanged("artificials");
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

    // items linked to this code, through the bibliography link table
    $biblio_items = array();
    $biblio_filter = openkis_BibliographyFilter($row['code'], "codeartificials");
    if ($biblio_filter !== false)
    {
        $config = FN_LoadConfig("", "bibliography");
        $biblio = new FNDBVIEW($config);
        $params['appendquery'] = $biblio_filter;
        $params['fields'] = "id,title,authors,year";
        $biblio_items = $biblio->GetResults(false, $params);
        $biblio_items = FN_ArraySortByKey($biblio_items, "year");
    }
    if (is_array($biblio_items) && count($biblio_items))
    {
        echo openkis_BibliographyAlertCard($biblio_items);
    }

// paleontological and archaeological findings in this cavity
echo openkis_FindingsCavityCard($row['code'], "codeartificial");
// regional additions to the sheet (extra/openkis/custom/<region>/viewfooter_artificials.php)
if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php"))
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php");
