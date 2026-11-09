<?php

/**
 * Karst area sheet footer. Runs before viewheader.php: echoed html goes below
 * the sheet at full width, blocks pushed into $GLOBALS['openkis_view_side'] go
 * in the side column. Lists the caves and the springs of the area (field
 * "areas", the codes of the areas separated by commas), with a summary of
 * the caves in the side column.
 */
global $_FN;
require_once "extra/openkis/openkis_sheet.inc.php";
$GLOBALS['openkis_view_side'] = array();
$area_code = trim((string) $row['code']);
if ($area_code !== "")
{
    $c = addslashes(str_replace(array("\\", "%", "_"), array("\\\\", "\\%", "\\_"), $area_code));
    $area_filter = "(areas LIKE '$c' OR areas LIKE '$c,%' OR areas LIKE '%,$c,%' OR areas LIKE '%,$c')";
    //---- caves of the area --------------------------------------------------->
    $config = FN_LoadConfig("modules/dbview/config.php", "caves");
    $dbview = new FNDBVIEW($config);
    $params = array();
    $params['fields'] = "id,code,name,comune,latitude,longitude,elevation,elevation_map,elevation_gps,lenght_total,depth_total";
    $params['appendquery'] = $area_filter;
    $items = $dbview->GetResults($config, $params);
    if (is_array($items) && count($items))
    {
        // Keep the existing cave-marker selection while adding the thematic
        // layers for this area. openkis_SheetCavesMapUrl() builds the iframe URL
        // and retains its centre/zoom calculation.
        $map_filter = (count($items) > 300)
            ? "nv_areas=" . rawurlencode($area_code)
            : "filter_code=" . implode(",", array_unique(array_map("rawurlencode", array_column($items, "code"))));
        $map_filter .= "&cave_area=" . rawurlencode($area_code) . "&temperatures=1&tracings=1";
        $map = $map_filter;
        echo openkis_SheetCavesCard(FN_Translate("caves of the area"), $items, array("map" => $map));
        //---- the area in numbers, in the side column ------------------------->
        $total = 0;
        $longest = $deepest = null;
        $elevations = array();
        foreach ($items as $item)
        {
            $length = floatval(str_replace(",", ".", $item['lenght_total']));
            $depth = abs(floatval(str_replace(",", ".", $item['depth_total'])));
            $total += $length;
            if ($length > 0 && ($longest === null || $length > floatval(str_replace(",", ".", $longest['lenght_total']))))
                $longest = $item;
            if ($depth > 0 && ($deepest === null || $depth > abs(floatval(str_replace(",", ".", $deepest['depth_total'])))))
                $deepest = $item;
            foreach (array("elevation", "elevation_map", "elevation_gps") as $f)
            {
                if (is_numeric(str_replace(",", ".", trim((string) $item[$f]))) && floatval(str_replace(",", ".", $item[$f])) != 0)
                {
                    $elevations[] = floatval(str_replace(",", ".", $item[$f]));
                    break;
                }
            }
        }
        $line = function ($title, $value)
        {
            return "<div class=\"row openkis-field\"><div class=\"col-6 openkis-field-title\">$title</div><div class=\"col-6 openkis-field-value text-right text-end\">$value</div></div>";
        };
        $cave_link = function ($item, $value)
        {
            $url = FN_RewriteLink("index.php?mod=caves&op=view&id={$item['id']}");
            return "<a href=\"$url\">" . htmlspecialchars($item['code'] . " " . $item['name']) . "</a><div class=\"small text-muted\">$value</div>";
        };
        $body = $line(FN_Translate("caves"), count($items));
        if ($total > 0)
            $body .= $line(FN_Translate("total development"), number_format($total, 0, ",", ".") . " m");
        if (count($elevations))
            $body .= $line(FN_Translate("elevation"), number_format(min($elevations), 0, ",", ".") . " - " . number_format(max($elevations), 0, ",", ".") . " m");
        if ($longest !== null)
            $body .= "<div class=\"openkis-field\"><div class=\"openkis-field-title\">" . FN_Translate("longest cave") . "</div>" . $cave_link($longest, htmlspecialchars($longest['lenght_total']) . " m") . "</div>";
        if ($deepest !== null)
            $body .= "<div class=\"openkis-field\"><div class=\"openkis-field-title\">" . FN_Translate("deepest cave") . "</div>" . $cave_link($deepest, htmlspecialchars($deepest['depth_total']) . " m") . "</div>";
        $GLOBALS['openkis_view_side'][] = openkis_SheetCard(FN_Translate("the area in numbers"), "<div class=\"card-body p-2 px-3\">$body</div>", array("side" => true));
        //---- the area in numbers ---------------------------------------------<
    }
    //---- caves of the area ---------------------------------------------------<
    //---- springs of the area (when the springs table has the field) --------->
    if (file_exists("sections/springs") && FN_UserCanViewSection("springs"))
    {
        $config = FN_LoadConfig("modules/dbview/config.php", "springs");
        $table = !empty($config['tables']) ? FN_XMDBTable($config['tables']) : false;
        if ($table && isset($table->fields['areas']))
        {
            $dbview = new FNDBVIEW($config);
            $params = array();
            $params['fields'] = "id,code,name,comune,latitude,longitude,elevation,elevation_map,elevation_gps";
            $params['appendquery'] = $area_filter;
            $items = $dbview->GetResults($config, $params);
            echo openkis_SheetCavesCard(FN_Translate("springs of the area"), $items, array("mod" => "springs", "columns" => array("comune", "elevation")));
        }
    }
    //---- springs of the area -------------------------------------------------<
}
