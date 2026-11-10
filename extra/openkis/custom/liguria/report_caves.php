<?php

/**
 * Liguria: columns of the caves report (sections/reports/report.inc.php).
 *
 * "Historical sheets": which scanned sheets the cave has in the Nextcloud
 * folders shown on the record page (viewfooter_caves.php): DSL sheets,
 * Issel cadastre sheets and the Zoja update. Folders are named after the
 * cadastre number, "0012_Name".
 */

/**
 * @param array $report built by openkis_ReportBuild()
 * @return array
 */
function openkis_Report_custom_caves($report)
{
    $sources = array(
        "DSL" => "../nextcloud/data/catasto/files/schede_dsl",
        "Issel" => "../nextcloud/data/catasto/files/scansioni_Issel",
        "Zoja" => "../nextcloud/data/catasto/files/scansioni_Zoja",
    );
    // cadastre number => sources with a folder, one directory listing each
    $sheets = array();
    foreach ($sources as $source => $dir)
    {
        foreach (is_dir($dir) ? scandir($dir) : array() as $entry)
        {
            if (preg_match('/^([0-9]+)/', $entry, $matches) && is_dir("$dir/$entry"))
            {
                $sheets[intval($matches[1])][$source] = $source;
            }
        }
    }

    $columns = array();
    foreach ($report['columns'] as $key => $title)
    {
        $columns[$key] = $title;
        if ($key === "checks")
        {
            $columns['historical_sheets'] = "historical sheets";
        }
    }
    $report['columns'] = $columns;
    foreach ($report['rows'] as $i => $row)
    {
        $found = array();
        if (preg_match('/([0-9]+)/', $row['code'], $matches) && isset($sheets[intval($matches[1])]))
        {
            $found = array_values($sheets[intval($matches[1])]);
        }
        $report['rows'][$i]['cells']['historical_sheets'] = openkis_ReportCell(implode(", ", $found), count($found) ? "ok" : "");
    }
    return $report;
}
