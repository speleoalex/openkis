<?php

/**
 * Report of the caves: what each record holds (surveys with a drawing,
 * photos, texts, speleometry) and the findings of the record validator
 * (extra/openkis_validator.inc.php plus the regional rules).
 *
 * Building it reads every cave, so the result is saved in
 * {datadir}/_cache/openkis_report/<mod>.json and rebuilt only with the
 * "Aggiorna" button. Rows are saved for every record: the ones the user may
 * not view are left out when the page is shown.
 *
 * A region adds or changes columns in
 * extra/openkis/custom/<region>/report_<mod>.php, defining
 *   function openkis_Report_custom_<mod>($report)
 * that receives and returns the report (see openkis_ReportBuild()).
 *
 * Administrators can have each record judged by the AI service: the result
 * is saved in {datadir}/_cache/openkis_ai_record/<table>_<id>.json and shown
 * in the report, which is not rebuilt.
 */
defined('_FNEXEC') or die('Restricted access');

/**
 * One cell of the report.
 *
 * @param string $text   shown text; "" with status ok/bad = translated yes/no
 * @param string $status "ok", "warn", "bad" or ""
 * @param array  $detail lines shown on demand and added to the CSV
 * @return array
 */
function openkis_ReportCell($text, $status = "", $detail = array())
{
    return array("t" => (string) $text, "s" => $status, "d" => array_values($detail));
}

/**
 * Yes/no cell: the text is translated when the page is shown.
 *
 * @param bool $ok
 * @return array
 */
function openkis_ReportYesNo($ok)
{
    return openkis_ReportCell("", $ok ? "ok" : "bad");
}

/**
 * Number cell, red when zero.
 *
 * @param int $count
 * @return array
 */
function openkis_ReportCount($count)
{
    return openkis_ReportCell((string) $count, $count > 0 ? "ok" : "bad");
}

/**
 * Path of the saved report.
 *
 * @global array $_FN
 * @param string $mod
 * @return string
 */
function openkis_ReportCacheFile($mod)
{
    global $_FN;
    return "{$_FN['datadir']}/_cache/openkis_report/" . preg_replace('/[^a-z0-9_]/', '', $mod) . ".json";
}

/**
 * Saved report, or null when it was never built.
 *
 * @param string $mod
 * @return array|null
 */
function openkis_ReportLoad($mod)
{
    $file = openkis_ReportCacheFile($mod);
    $report = is_file($file) ? json_decode(file_get_contents($file), true) : null;
    return (is_array($report) && isset($report['columns'], $report['rows'])) ? $report : null;
}

/**
 * Saves data as JSON in the cache folder.
 *
 * @param string $file
 * @param array  $data
 * @return bool
 */
function openkis_ReportWriteJson($file, $data)
{
    if (!is_dir(dirname($file)))
    {
        @mkdir(dirname($file), 0755, true);
    }
    return false !== @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

/**
 * Builds the report of the caves and saves it.
 *
 * Report: generated_at, columns (key => English title, translated when
 * shown), rows (id, code, groupview, recordupdate, cells: key => cell of
 * openkis_ReportCell()).
 *
 * @global array $_FN
 * @param string $mod section of the records (caves)
 * @return array
 */
function openkis_ReportBuild($mod)
{
    global $_FN;
    set_time_limit(600);
    $config = FN_LoadConfig("modules/dbview/config.php", $mod);

    $columns = array(
        "code" => "code",
        "name" => "name",
        "surveys" => "surveys",
        "drawings" => "surveys with drawing",
        "entrance" => "photo entrance",
        "photos" => "photos",
        "description" => "description",
        "itinerary" => "itinerary",
        "position" => "position",
        "elevation" => "elevation",
        "speleometry" => "speleometric data",
        "checks" => "record checks",
        "updatedby" => "updated by",
        "recordupdate" => "date updated",
        "visible" => "visible to",
        "documents" => "Documents",
        "status" => "Status",
    );
    if (empty($config['documents_folder']))
    {
        unset($columns['documents']);
    }

    // surveys and photos: one read of each table, counted per cave
    $surveys = array();
    foreach ((array) FN_XMDBTable("ctl_surveys")->GetRecords(false, false, false, false, false, "codecave|photo1|filepdf|file") as $survey)
    {
        $code = strtoupper(trim((string) $survey['codecave']));
        if (!isset($surveys[$code]))
        {
            $surveys[$code] = array("total" => 0, "drawings" => 0);
        }
        $surveys[$code]['total']++;
        if (openkis_SurveyHasDrawing($survey))
        {
            $surveys[$code]['drawings']++;
        }
    }
    $photos = array();
    foreach ((array) FN_XMDBTable("ctl_photos")->GetRecords(false, false, false, false, false, "codecave") as $photo)
    {
        $code = strtoupper(trim((string) $photo['codecave']));
        $photos[$code] = isset($photos[$code]) ? $photos[$code] + 1 : 1;
    }

    $users = array();
    $rows = array();
    foreach ((array) FN_XMDBTable("ctl_$mod")->GetRecords() as $item)
    {
        if (isset($item['recorddeleted']) && (string) $item['recorddeleted'] === "1")
        {
            continue;
        }
        $code = trim((string) $item['code']);
        $codekey = strtoupper($code);
        $cells = array();
        $cells['code'] = openkis_ReportCell($code);
        $cells['name'] = openkis_ReportCell($item['name']);

        $count = isset($surveys[$codekey]) ? $surveys[$codekey] : array("total" => 0, "drawings" => 0);
        $cells['surveys'] = openkis_ReportCount($count['total']);
        $cells['drawings'] = openkis_ReportCount($count['drawings']);
        if ($count['total'] > 0 && $count['drawings'] == 0)
        {
            $cells['drawings']['d'][] = FN_Translate("only survey data (polygonal, kml, lox): no drawing");
        }
        $cells['entrance'] = openkis_ReportYesNo(!openkis_ValidatorFieldEmpty($item, "photo1"));
        $cells['photos'] = openkis_ReportCount(isset($photos[$codekey]) ? $photos[$codekey] : 0);
        $cells['description'] = openkis_ReportYesNo(!openkis_ValidatorFieldEmpty($item, "description"));
        $cells['itinerary'] = openkis_ReportYesNo(!openkis_ValidatorFieldEmpty($item, "itinerary"));
        // entered coordinates: latitude/longitude are computed and hold 0 when missing
        $cells['position'] = openkis_ReportYesNo(!openkis_ValidatorFieldEmpty($item, "latitude_txt") && !openkis_ValidatorFieldEmpty($item, "longitude_txt"));
        $cells['elevation'] = openkis_ReportYesNo(openkis_ValidatorHasNumber($item, "elevation") || openkis_ValidatorHasNumber($item, "elevation_map"));

        // required speleometry (CampiScheda): real development and the three depths
        $present = 0;
        foreach (array("lenght_total", "depth_positive", "depth_negative", "depth_total") as $field)
        {
            $present += openkis_ValidatorHasNumber($item, $field) ? 1 : 0;
        }
        $cells['speleometry'] = openkis_ReportCell("$present/4", $present == 4 ? "ok" : ($present == 0 ? "bad" : "warn"));

        $findings = openkis_ValidateRecord($mod, $item);
        $missing = 0;
        $lines = array();
        foreach ($findings as $finding)
        {
            $ismissing = $finding['severity'] === "missing";
            $missing += $ismissing ? 1 : 0;
            $lines[] = ($ismissing ? "[" . FN_Translate("missing") . "] " : "") . $finding['label'] . ": " . $finding['message'];
        }
        $notices = count($findings) - $missing;
        $cells['checks'] = openkis_ReportCell("$missing / $notices", $missing ? "bad" : ($notices ? "warn" : "ok"), $lines);

        $username = (string) $item['userupdate'];
        if (!isset($users[$username]))
        {
            $uservalues = $username !== "" ? FN_GetUser($username) : null;
            $users[$username] = !empty($uservalues['name']) ? trim($uservalues['name'] . " " . $uservalues['surname']) : ($username === "admin" ? "" : $username);
        }
        $cells['updatedby'] = openkis_ReportCell($users[$username]);
        $cells['recordupdate'] = openkis_ReportCell($item['recordupdate']);
        $cells['visible'] = openkis_ReportCell(trim((string) $item['groupview']) === "" ? FN_Translate("all") : $item['groupview']);
        if (isset($columns['documents']))
        {
            $documents = openkis_ReportDocuments($config['documents_folder'], $code);
            $cells['documents'] = openkis_ReportCount($documents);
        }
        $cells['status'] = openkis_ReportCell(isset($item['status']) && $item['status'] !== "" ? FN_Translate($item['status']) : "");

        $rows[] = array(
            "id" => $item['id'],
            "code" => $code,
            "groupview" => (string) $item['groupview'],
            "recordupdate" => (string) $item['recordupdate'],
            "cells" => $cells,
        );
    }
    $rows = xmetadb_array_natsort_by_key($rows, "code");

    $report = array("generated_at" => date("Y-m-d H:i"), "columns" => $columns, "rows" => array_values($rows));
    if (!empty($_FN['openkis_custom']))
    {
        $customfile = "extra/openkis/custom/{$_FN['openkis_custom']}/report_{$mod}.php";
        if (file_exists($customfile))
        {
            include_once $customfile;
        }
        $customfunction = "openkis_Report_custom_{$mod}";
        if (function_exists($customfunction))
        {
            $report = $customfunction($report);
        }
    }
    openkis_ReportWriteJson(openkis_ReportCacheFile($mod), $report);
    return $report;
}

/**
 * Files in the documents folder of a record: <code>, <number as 0001> or
 * <0001>_<name>.
 *
 * @param string $folder documents_folder of the section
 * @param string $code
 * @return int
 */
function openkis_ReportDocuments($folder, $code)
{
    $number = sprintf("%04d", intval(preg_replace('/[^0-9]/', '', $code)));
    foreach (array($code, $number, "{$number}_*") as $pattern)
    {
        $dirs = glob(rtrim($folder, "/") . "/" . $pattern, GLOB_ONLYDIR);
        if ($dirs)
        {
            return count((array) glob("{$dirs[0]}/*.*"));
        }
    }
    return 0;
}

/**
 * Anti CSRF token of the report buttons, bound to the logged user.
 *
 * @global array $_FN
 * @return string
 */
function openkis_ReportToken()
{
    global $_FN;
    return hash_hmac("sha256", "openkis-report|" . $_FN['user'], FN_GetSiteSecret());
}

/**
 * Path of the saved AI analysis of a record.
 *
 * @global array $_FN
 * @param string $mod
 * @param string $id
 * @return string
 */
function openkis_ReportAiFile($mod, $id)
{
    global $_FN;
    return "{$_FN['datadir']}/_cache/openkis_ai_record/ctl_" . preg_replace('/[^a-z0-9_]/', '', $mod) . "_" . preg_replace('/[^0-9]/', '', (string) $id) . ".json";
}

/**
 * Saved AI analysis of a record, or null.
 *
 * @param string $mod
 * @param string $id
 * @return array|null analyzed_at, model, recordupdate, analysis
 */
function openkis_ReportAiLoad($mod, $id)
{
    $file = openkis_ReportAiFile($mod, $id);
    $saved = is_file($file) ? json_decode(file_get_contents($file), true) : null;
    return (is_array($saved) && isset($saved['analysis']) && is_array($saved['analysis'])) ? $saved : null;
}

/**
 * Has the AI service judge a record against the cadastre rules and saves
 * the result.
 *
 * @param string $mod
 * @param string $id
 * @return array ok, error, saved
 */
function openkis_ReportAiAnalyze($mod, $id)
{
    $row = FN_XMDBTable("ctl_$mod")->GetRecordByPrimaryKey($id);
    if (!is_array($row))
    {
        return array("ok" => false, "error" => FN_Translate("record not found"));
    }
    $fields = array(
        "code" => "Numero di catasto", "name" => "Nome", "synonyms" => "Sinonimi", "comune" => "Comune",
        "provincia" => "Provincia", "localita" => "Localita'", "latitude_txt" => "Coordinate ingresso, latitudine / Nord",
        "longitude_txt" => "Coordinate ingresso, longitudine / Est", "coordinates_type" => "Sistema di coordinate",
        "areas" => "Area carsica",
        "geologicalformation" => "Formazione geologica", "lenght_total" => "Sviluppo reale (m)",
        "lenght_planimetric" => "Sviluppo planimetrico (m)", "depth_positive" => "Dislivello positivo (m)",
        "depth_negative" => "Dislivello negativo (m)", "depth_total" => "Dislivello totale (m)",
        "elevation" => "Quota (m)", "elevation_map" => "Quota cartografica (m)", "trend" => "Andamento",
        "hydrology" => "Idrologia", "practicability" => "Percorribilita'", "closed" => "Chiusa",
        "destroyed" => "Distrutta", "marine" => "Cavita' marina", "caveslinks" => "Grotte collegate",
        "description" => "Descrizione", "itinerary" => "Itinerario di accesso", "wells" => "Pozzi / armo",
        "history" => "Storia esplorativa", "geology" => "Geologia", "meteorology" => "Meteorologia",
        "fauna" => "Fauna", "notes" => "Note",
    );
    $data = "";
    foreach ($fields as $field => $label)
    {
        if (!openkis_ValidatorFieldEmpty($row, $field))
        {
            $value = trim(strip_tags(html_entity_decode((string) $row[$field], ENT_QUOTES, "UTF-8")));
            $data .= "- $label: " . (function_exists("mb_substr") ? mb_substr($value, 0, 6000, "UTF-8") : substr($value, 0, 6000)) . "\n";
        }
    }
    $code = trim((string) $row['code']);
    $surveys = $code === "" ? array() : (array) FN_XMDBTable("ctl_surveys")->GetRecords(array("codecave" => $code));
    $drawings = count(array_filter($surveys, "openkis_SurveyHasDrawing"));
    $photos = $code === "" ? array() : (array) FN_XMDBTable("ctl_photos")->GetRecords(array("codecave" => $code));
    $data .= "- Rilievi: " . count($surveys) . " (con disegno: $drawings)\n";
    $data .= "- Foto: " . count($photos) . (openkis_ValidatorFieldEmpty($row, "photo1") ? "" : " + foto dell'ingresso") . "\n";
    $findings = "";
    foreach (openkis_ValidateRecord($mod, $row) as $finding)
    {
        $findings .= "- " . ($finding['severity'] === "missing" ? "[mancante] " : "") . "{$finding['label']}: {$finding['message']}\n";
    }

    $prompt = "Sei un curatore di un catasto speleologico regionale italiano. Valuta la scheda di grotta qui sotto.

Requisiti della scheda: nome (univoco, non offensivo), dati speleometrici misurati (sviluppo reale, dislivelli positivo, negativo e totale = somma dei valori assoluti), rilievo con disegno (pianta e sezione), descrizione, coordinate dell'ingresso, foto dell'ingresso, itinerario di accesso.
La descrizione dovrebbe trattare: morfologia, pericoli, idrologia (corsi d'acqua, laghi, sifoni), roccia, pozzi e armo, collegamenti con altre grotte, possibili prosecuzioni, storia esplorativa, percorribilita' (attrezzatura, acqua, fango, strettoie).
L'itinerario deve permettere di ritrovare l'ingresso.
Controlla la coerenza fra descrizione, dati speleometrici e caratteristiche (andamento, idrologia, chiusa/distrutta).

DATI DELLA SCHEDA:
{$data}
CONTROLLI AUTOMATICI GIA' ESEGUITI: sono gia' noti al curatore, non riportarli ne' fra i problemi ne' fra i suggerimenti. Concentrati su quello che solo la lettura dei testi puo' rivelare (contenuto e qualita' di descrizione e itinerario, incoerenze fra testi e dati).
" . ($findings !== "" ? $findings : "- nessuna segnalazione\n") . "
Rispondi SOLO con un JSON in questo formato:
{
  \"valutazione\": \"buona\" | \"sufficiente\" | \"insufficiente\",
  \"sintesi\": \"una o due frasi sullo stato della scheda\",
  \"problemi\": [\"incoerenze o lacune trovate nei testi e nei dati\"],
  \"suggerimenti\": [\"cosa aggiungere o correggere, in ordine di importanza\"]
}";
    $reply = openkis_AiChat($prompt);
    if (!$reply['ok'])
    {
        return array("ok" => false, "error" => $reply['error']);
    }
    $analysis = openkis_AiJson($reply['content']);
    if (!is_array($analysis))
    {
        return array("ok" => false, "error" => FN_Translate("invalid AI reply"));
    }
    $saved = array(
        "analyzed_at" => date("Y-m-d H:i"),
        "model" => $reply['model'],
        "recordupdate" => (string) $row['recordupdate'],
        "analysis" => $analysis,
    );
    openkis_ReportWriteJson(openkis_ReportAiFile($mod, $id), $saved);
    return array("ok" => true, "error" => "", "saved" => $saved);
}
