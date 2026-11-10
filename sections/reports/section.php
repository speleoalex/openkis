<?php

/**
 * Report of the caves (logic in report.inc.php).
 *
 * GET  download=csv          the report as CSV
 * POST op=refresh, token     rebuild the saved report (logged users)
 * POST op=ai, id, token      AI analysis of one record, JSON (administrators)
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
require_once __DIR__ . "/report.inc.php";

$mod = "caves";
FN_LoadMessagesFolder("modules/dbview");
$isadmin = FN_IsAdmin();
$op = FN_GetParam("op", $_POST);
$tokenok = $_FN['user'] != "" && hash_equals(openkis_ReportToken(), (string) FN_GetParam("token", $_POST));

if ($op === "ai")
{
    $reply = array("ok" => false, "error" => FN_Translate("you may not do that"));
    $id = preg_replace('/[^0-9]/', '', (string) FN_GetParam("id", $_POST));
    if ($isadmin && $tokenok && $id !== "")
    {
        $result = openkis_ReportAiAnalyze($mod, $id);
        $reply = array("ok" => $result['ok'], "error" => $result['error']);
        if ($result['ok'])
        {
            $row = FN_XMDBTable("ctl_$mod")->GetRecordByPrimaryKey($id);
            $reply['html'] = openkis_ReportAiHtml($id, (string) $row['recordupdate'], $result['saved']);
        }
    }
    while (ob_get_level())
    {
        ob_end_clean();
    }
    header("Content-Type: application/json; charset=utf-8");
    header("Cache-Control: no-store");
    echo json_encode($reply, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}
if ($op === "refresh" && $tokenok)
{
    openkis_ReportBuild($mod);
    FN_Redirect(FN_RewriteLink("index.php?mod={$_FN['mod']}", "&", true));
}

$report = openkis_ReportLoad($mod);
if ($report === null)
{
    $report = openkis_ReportBuild($mod);
}
// rows of records restricted to groups the user is not in
$hidden = array_flip(openkis_HiddenItemCodes("ctl_$mod"));
$rows = array();
foreach ($report['rows'] as $row)
{
    if (!isset($hidden[$row['code']]))
    {
        $rows[] = $row;
    }
}

if (FN_GetParam("download", $_GET) === "csv")
{
    while (ob_get_level())
    {
        ob_end_clean();
    }
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=report_{$mod}_" . date("Y-m-d") . ".csv");
    $output = fopen("php://output", "w");
    $headers = array();
    foreach ($report['columns'] as $title)
    {
        $headers[] = FN_Translate($title);
    }
    fputcsv($output, $headers);
    foreach ($rows as $row)
    {
        $line = array();
        foreach (array_keys($report['columns']) as $key)
        {
            $cell = isset($row['cells'][$key]) ? $row['cells'][$key] : openkis_ReportCell("");
            $text = openkis_ReportCellText($cell);
            $line[] = count($cell['d']) ? trim("$text " . implode("; ", $cell['d'])) : $text;
        }
        fputcsv($output, $line);
    }
    exit();
}

$headers = array();
foreach ($report['columns'] as $title)
{
    $headers[] = array("text" => htmlspecialchars(FN_Translate($title)));
}
if ($isadmin)
{
    $headers[] = array("text" => htmlspecialchars(FN_Translate("AI analysis")));
}
$tablerows = array();
foreach ($rows as $row)
{
    $cols = array();
    foreach (array_keys($report['columns']) as $key)
    {
        $cell = isset($row['cells'][$key]) ? $row['cells'][$key] : openkis_ReportCell("");
        $html = openkis_ReportCellHtml($cell);
        if ($key === "code")
        {
            // absolute: the theme rewrites relative links with regular expressions on the whole page
            $html = "<a target=\"_blank\" href=\"" . FN_RewriteLink("index.php?mod={$mod}&amp;op=view&amp;id=" . urlencode($row['id']), "&amp;", true) . "\">$html</a>";
        }
        $cols[] = array("text" => $html);
    }
    if ($isadmin)
    {
        $cols[] = array("text" => openkis_ReportAiHtml($row['id'], $row['recordupdate'], openkis_ReportAiLoad($mod, $row['id'])));
    }
    $tablerows[] = array("cols" => $cols);
}

$vars = array();
$vars['title'] = htmlspecialchars(FN_Translate("report"));
$vars['generated_at'] = htmlspecialchars($report['generated_at']);
$vars['count'] = count($rows);
$vars['csvurl'] = FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;download=csv", "&amp;", true);
$vars['formurl'] = FN_RewriteLink("index.php?mod={$_FN['mod']}", "&amp;", true);
$vars['canrefresh'] = $_FN['user'] != "";
$vars['isadmin'] = $isadmin;
$vars['token'] = $_FN['user'] != "" ? openkis_ReportToken() : "";
$vars['table'] = array("headers" => $headers, "rows" => $tablerows);
echo FN_TPL_ApplyTplFile("sections/{$_FN['mod']}/table.tp.html", $vars);

/**
 * Plain text of a cell (yes/no cells are translated here).
 *
 * @param array $cell
 * @return string
 */
function openkis_ReportCellText($cell)
{
    if ($cell['t'] === "" && ($cell['s'] === "ok" || $cell['s'] === "bad"))
    {
        return FN_Translate($cell['s'] === "ok" ? "yes" : "no");
    }
    return $cell['t'];
}

/**
 * HTML of a cell: coloured text, the detail lines in a disclosure.
 *
 * @param array $cell
 * @return string
 */
function openkis_ReportCellHtml($cell)
{
    $text = htmlspecialchars(openkis_ReportCellText($cell));
    if ($cell['s'] !== "")
    {
        $text = "<span class=\"r-{$cell['s']}\">$text</span>";
    }
    if (!count($cell['d']))
    {
        return $text;
    }
    $html = "<details><summary>$text</summary><ul>";
    foreach ($cell['d'] as $line)
    {
        $html .= "<li>" . htmlspecialchars($line) . "</li>";
    }
    return "$html</ul></details>";
}

/**
 * Cell of the AI analysis of a record, with the button that runs it.
 *
 * @param string     $id
 * @param string     $recordupdate current last change of the record
 * @param array|null $saved        openkis_ReportAiLoad()
 * @return string
 */
function openkis_ReportAiHtml($id, $recordupdate, $saved)
{
    $html = "<div class=\"r-ai\" data-id=\"" . htmlspecialchars($id) . "\">";
    if ($saved)
    {
        $analysis = $saved['analysis'];
        $verdict = isset($analysis['valutazione']) ? strtolower(trim((string) $analysis['valutazione'])) : "";
        $statuses = array("buona" => "ok", "sufficiente" => "warn", "insufficiente" => "bad");
        $status = isset($statuses[$verdict]) ? $statuses[$verdict] : "";
        $html .= "<details><summary><span class=\"r-$status\">" . htmlspecialchars($verdict !== "" ? $verdict : "?") . "</span> ";
        $html .= "<small>" . htmlspecialchars($saved['analyzed_at']) . "</small>";
        if ((string) $saved['recordupdate'] !== (string) $recordupdate)
        {
            $html .= " <small class=\"r-warn\">" . htmlspecialchars(FN_Translate("record changed after the analysis")) . "</small>";
        }
        $html .= "</summary>";
        if (!empty($analysis['sintesi']))
        {
            $html .= "<p>" . htmlspecialchars((string) $analysis['sintesi']) . "</p>";
        }
        foreach (array("problemi" => "problems", "suggerimenti" => "suggestions") as $key => $title)
        {
            if (!empty($analysis[$key]) && is_array($analysis[$key]))
            {
                $html .= "<strong>" . htmlspecialchars(FN_Translate($title)) . "</strong><ul>";
                foreach ($analysis[$key] as $line)
                {
                    $html .= "<li>" . htmlspecialchars(is_scalar($line) ? (string) $line : json_encode($line)) . "</li>";
                }
                $html .= "</ul>";
            }
        }
        $html .= "<small>" . htmlspecialchars(FN_Translate("automatic analysis") . " (" . $saved['model'] . "): " . FN_Translate("always check the results")) . "</small>";
        $html .= "</details>";
    }
    $html .= "<button type=\"button\" class=\"btn btn-sm btn-outline-secondary\">" . htmlspecialchars(FN_Translate($saved ? "analyze again" : "analyze")) . "</button>";
    return "$html</div>";
}
