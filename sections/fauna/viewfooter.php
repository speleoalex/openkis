<?php
/**
 * Species sheet footer. Runs before viewheader.php: echoed html goes below
 * the sheet at full width, blocks pushed into $GLOBALS['openkis_view_side'] go
 * in the side column. Lists the caves where the species was observed and the
 * bibliography that names it. A region can replace it with
 * extra/openkis/custom/{custom}/viewfooter_fauna.php.
 */
global $_FN;
require_once "extra/openkis/openkis_sheet.inc.php";
$GLOBALS['openkis_view_side'] = array();
if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php"))
{
    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php");
}
else
{
    // caves where the species was observed (section fauna_surveys)
    require_once "extra/openkis/openkis_fauna.inc.php";
    echo openkis_FaunaSpeciesCard($row['scientific_name']);
    // bibliography: the field "fauna" lists the species separated by "|"
    $name_sql = addslashes($row['scientific_name']);
    $config = FN_LoadConfig("", "bibliography");
    $biblio = new FNDBVIEW($config);
    $params = array();
    $params['appendquery'] = "(fauna LIKE '$name_sql' OR fauna LIKE '$name_sql|%' OR fauna LIKE '%|$name_sql|%' OR fauna LIKE '%|$name_sql')";
    $params['fields'] = "id,title,authors,year";
    $biblio_items = $biblio->GetResults(false, $params);
    if (is_array($biblio_items) && count($biblio_items))
    {
        echo openkis_SheetBibliographyCard(FN_ArraySortByKey($biblio_items, "year"));
    }
}
