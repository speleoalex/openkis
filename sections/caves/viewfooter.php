<?php

global $_FN;
/**
 * Cave sheet footer. Runs before viewheader.php: blocks pushed into
 * $GLOBALS['openkis_view_side'] are printed by viewheader.php in the side
 * column, while echoed html goes below the sheet at full width.
 */
require_once "extra/openkis/openkis_sheet.inc.php";
$GLOBALS['openkis_view_side'] = array();
if (!empty($_FN['openkis_custom']) && file_exists("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php"))
{

    include("extra/openkis/custom/{$_FN['openkis_custom']}/viewfooter_{$_FN['mod']}.php");

} else
{
    $icon = "{$_FN['siteurl']}" . openkis_GetIcon($row, $_FN['mod']);
    // map, route links and coordinates (decimal, DMS, UTM) in the side column
    $GLOBALS['openkis_view_side'] = openkis_SheetMapCards($_FN['mod'], $row);
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
    // tracer tests starting or ending in this cave
    if (function_exists("openkis_TracingsCaveCard"))
    {
        echo openkis_TracingsCaveCard($row['code']);
    }
    // species observed in this cave (section fauna_surveys)
    require_once "extra/openkis/openkis_fauna.inc.php";
    echo openkis_FaunaCaveCard($row['code']);
    // temperature measurements and monitorings in this cave
    if (function_exists("openkis_TemperaturesCaveCard"))
    {
        echo openkis_TemperaturesCaveCard($row['code']);
    }
    //survey: 3D viewer of the lox files, below the sheet ---------------------->
    foreach (openkis_SheetCaveSurveys($row['code']) as $survey_item)
    {
        if (!empty($survey_item['filelox']))
        {
            $file = urlencode("misc/fndatabase/ctl_surveys/{$survey_item['id']}/filelox/{$survey_item['filelox']}");
            $iframe_href = "{$_FN['siteurl']}cave_viewer.php?f={$file}";
            echo "<iframe style=\"width:100%;height:800px;border:0px\" src=\"$iframe_href\"></iframe>";
        }
    }
    //survey-------------------------------------------------------------------<

}
// The surveys of the cave: the inner table ctl_surveys is rendered here from
// one query (openkis_SheetInnerCards, same template detail_inner.tp.html) and
// removed from the inner tables that FNDBVIEW::ViewRecordPage() would render
// one record at a time (four queries each). $Table is the form of the sheet,
// in scope because this file is included by ViewRecordPage().
if (isset($Table) && is_object($Table) && !empty($Table->innertables) && is_array($Table->innertables))
{
    foreach ($Table->innertables as $k => $v)
    {
        if (isset($v['tablename']) && $v['tablename'] == "ctl_surveys")
        {
            $title = isset($v["frm_{$_FN['lang']}"]) ? $v["frm_{$_FN['lang']}"] : $v['tablename'];
            $surveys_html = openkis_SheetInnerCards("ctl_surveys", openkis_SheetCaveSurveys($row['code']), $title);
            if ($surveys_html != "")
                $GLOBALS['openkis_view_side'][] = $surveys_html;
            unset($Table->innertables[$k]);
        }
    }
}
// the same cave on Grottocenter, when the link is confirmed (section "grottocenter"),
// also with the regional sheets
if (function_exists("openkis_GrottocenterCaveCard"))
{
    $gc_card = openkis_GrottocenterCaveCard($_FN['mod'], $row);
    if ($gc_card != "")
        $GLOBALS['openkis_view_side'][] = $gc_card;
}
?>