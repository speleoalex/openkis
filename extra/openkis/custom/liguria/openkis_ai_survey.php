<?php

/**
 * AI survey analysis endpoint (Liguria only).
 *
 * Called asynchronously from the record edit form: sends the survey image to
 * the AI service (vision model) and returns, as JSON, the compliance checks
 * required by the regional cadastre standard (CampiScheda-20250327). Same
 * prompt as the CLI batch script validate_ai_surveys.php.
 *
 * GET parameters:
 *   mod    = caves | artificials
 *   survey = numeric id of the record in ctl_surveys / ctl_surveys_artificials
 *   cached = 1: return the saved analysis only, without calling the API
 *            ({"ok":false} when there is none or the image has changed)
 *
 * Every successful analysis is saved in {datadir}/_cache/openkis_ai_survey/
 * keyed by the md5 of the image, so the edit form can show the last result
 * as long as the survey image stays the same.
 *
 * Requirements:
 *   - logged in user (any group);
 *   - AI service configured in the configuration section (openkis_AiSettings()).
 *
 * Only images are analyzed (no PDF conversion: exec() is not available on
 * the production host).
 */
// Boot the CMS from the document root: this file lives four levels below it
// (extra/openkis/custom/liguria/). When run from a CLI harness the current
// directory already contains loadfinis.php and is kept as is.
if (!is_file("loadfinis.php"))
{
    $openkis_docroot = __DIR__ . "/../../../..";
    if (isset($_SERVER['SCRIPT_FILENAME']) && $_SERVER['SCRIPT_FILENAME'] !== "")
    {
        // Prefer the served path: __DIR__ resolves symlinks and could point
        // outside the virtual host document root.
        $served_docroot = dirname(dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))));
        if (is_file($served_docroot . "/loadfinis.php"))
        {
            $openkis_docroot = $served_docroot;
        }
    }
    chdir($openkis_docroot);
}
require_once "loadfinis.php";

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-cache, no-store, must-revalidate");

/**
 * Send a JSON reply and stop.
 *
 * @param array $data
 * @param int   $http_code
 */
function openkis_ai_survey_Reply($data, $http_code = 200)
{
    http_response_code($http_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    die();
}

if (!isset($_FN['user']) || $_FN['user'] === "")
{
    openkis_ai_survey_Reply(array("error" => "Accesso richiesto: eseguire il login per usare l'analisi AI"), 403);
}

$ai_settings = openkis_AiSettings();
if (!$ai_settings['enabled'])
{
    openkis_ai_survey_Reply(array("error" => FN_Translate("AI service not configured (Configuration section)")), 500);
}
$ai_model = $ai_settings['model'];

$mod = isset($_GET['mod']) ? $_GET['mod'] : "caves";
if ($mod === "artificials")
{
    $surveys_table = "ctl_surveys_artificials";
    $records_table = "ctl_artificials";
    $survey_code_field = "codeartificial";
}
else
{
    $mod = "caves";
    $surveys_table = "ctl_surveys";
    $records_table = "ctl_caves";
    $survey_code_field = "codecave";
}

$survey_id = isset($_GET['survey']) ? preg_replace('/[^0-9]/', '', (string) $_GET['survey']) : "";
if ($survey_id === "")
{
    openkis_ai_survey_Reply(array("error" => "Parametro 'survey' mancante"), 400);
}

$survey = FN_XMDBTable($surveys_table)->GetRecordByPk($survey_id);
if (!is_array($survey))
{
    openkis_ai_survey_Reply(array("error" => "Rilievo {$survey_id} non trovato in {$surveys_table}"), 404);
}

$photo1 = isset($survey['photo1']) ? trim($survey['photo1']) : "";
if ($photo1 === "")
{
    openkis_ai_survey_Reply(array("error" => "Il rilievo {$survey_id} non ha un'immagine (photo1)"), 404);
}

// basename() against path traversal: the stored value is a plain file name
$image_file = "misc/fndatabase/{$surveys_table}/{$survey_id}/photo1/" . basename($photo1);
if (!is_file($image_file))
{
    openkis_ai_survey_Reply(array("error" => "File immagine non trovato: {$image_file}"), 404);
}

$extension = strtolower(pathinfo($image_file, PATHINFO_EXTENSION));
$allowed_extensions = array("png", "jpg", "jpeg", "gif", "webp");
if (!in_array($extension, $allowed_extensions))
{
    openkis_ai_survey_Reply(array("error" => "Formato '{$extension}' non analizzabile: servono immagini (PNG/JPG/GIF/WEBP)"), 415);
}

// The OpenAI data-URI limit (the usual one of the AI services) is 20 MB: stay below it
$max_bytes = 15 * 1024 * 1024;
if (filesize($image_file) > $max_bytes)
{
    openkis_ai_survey_Reply(array("error" => "Immagine troppo grande (oltre 15 MB)"), 413);
}

$survey_meta = array(
    "id" => $survey_id,
    "name" => isset($survey['name']) ? $survey['name'] : "",
    "date" => isset($survey['date']) ? $survey['date'] : "",
    "author" => isset($survey['author']) ? $survey['author'] : "",
    "file" => basename($photo1),
);

// Saved analyses: one JSON file per survey, reused only while the image
// checksum stays the same
$image_md5 = md5_file($image_file);
$ai_cache_dir = (!empty($_FN['datadir']) ? $_FN['datadir'] : "misc") . "/_cache/openkis_ai_survey";
$ai_cache_file = "{$ai_cache_dir}/{$surveys_table}_{$survey_id}.json";

if (!empty($_GET['cached']))
{
    // Read-only mode: never calls the API (used on page load)
    $saved = is_file($ai_cache_file) ? json_decode(file_get_contents($ai_cache_file), true) : null;
    if (is_array($saved) && isset($saved['image_md5']) && isset($saved['analysis'])
        && $saved['image_md5'] === $image_md5 && is_array($saved['analysis']))
    {
        openkis_ai_survey_Reply(array(
            "ok" => true,
            "cached" => true,
            "analyzed_at" => isset($saved['analyzed_at']) ? $saved['analyzed_at'] : "",
            "survey" => $survey_meta,
            "model" => isset($saved['model']) ? $saved['model'] : "",
            "analysis" => $saved['analysis'],
        ));
    }
    openkis_ai_survey_Reply(array("ok" => false, "cache" => "miss"));
}

// Cadastral record data used by the prompt to cross-check the name drawn on
// the survey. The artificials table may lack some fields: read defensively.
$record_code = isset($survey[$survey_code_field]) ? trim($survey[$survey_code_field]) : "";
$record_name = "";
$record_synonyms = "";
if ($record_code !== "")
{
    $record_code_escaped = str_replace("'", "''", $record_code);
    $record_rows = FN_XMETADBQuery("SELECT * FROM {$records_table} WHERE code = '{$record_code_escaped}'");
    if (is_array($record_rows) && isset($record_rows[0]))
    {
        $record_name = isset($record_rows[0]['name']) ? trim($record_rows[0]['name']) : "";
        $record_synonyms = isset($record_rows[0]['synonyms']) ? trim($record_rows[0]['synonyms']) : "";
    }
}

$cave_info = "";
if ($record_code !== "" || $record_name !== "")
{
    // Cadastral number without the region prefix helps matching labels
    $all_synonyms = str_replace("LI", "", $record_code);
    if ($record_synonyms !== "")
    {
        $all_synonyms .= ", " . $record_synonyms;
    }
    $cave_info = "\n\nINFO GROTTA DAL CATASTO:\n- Numero: {$record_code}\n- Nome ufficiale: {$record_name}\n- Sinonimi: {$all_synonyms}";
}

// Same prompt as validate_ai_surveys.php (proven on the 2025 batch runs)
$prompt = "Analizza questo rilievo speleologico (pianta e/o sezione di grotta) secondo i requisiti del Catasto Grotte della Liguria.

REQUISITI OBBLIGATORI per un rilievo valido (CampiScheda-20250327):
- Il rilievo DEVE contenere sia la vista in PIANTA che la SEZIONE LONGITUDINALE
- Deve esserci la scritta \"PIANTA\" o \"PLANIMETRIA\" sulla vista in pianta
- Deve esserci la scritta \"SEZIONE\", \"SEZ. LONGITUDINALE\" o simile sulla sezione
- Deve avere: scala grafica, punto zero, nord (sulla pianta), dati metrici
- I bordi delle pareti devono essere distinti dai dettagli interni
- Devono essere indicati i topografi/rilevatori

Rispondi alle seguenti domande con Y (sì/presente), N (no/assente) o ? (non sicuro/non determinabile):

1. SCALA: È presente una scala grafica o numerica?
2. NORD: È presente l'indicatore del nord sulla pianta?
3. PUNTO_ZERO: È indicato il punto zero di riferimento?
4. DATI_METRICI: Sono presenti quote altimetriche o dati metrici sul disegno?
5. SCRITTA_PIANTA: C'è la scritta 'PIANTA' o 'PLANIMETRIA'?
6. SCRITTA_SEZIONE: C'è la scritta 'SEZIONE', 'SEZ. LONGITUDINALE' o simile?
7. ENTRAMBE_VISTE: Ci sono ENTRAMBE le viste (pianta E sezione longitudinale)?
8. SPESSORE_BORDO: I bordi delle pareti sono disegnati con spessore adeguato?
9. SPESSORE_DETTAGLI: I dettagli interni (sassi, detriti) sono distinti dai bordi?
10. TOPOGRAFI: Sono indicati i nomi dei topografi/rilevatori?
11. MORFOLOGIA_ESTERNA: È rappresentata la morfologia esterna (superficie)?
12. SEZIONI_TRASV: Ci sono sezioni trasversali disegnate?
13. NOME_CAVITA: È scritto sul disegno il nome o il numero di catasto della grotta?
{$cave_info}



VERIFICA NOME: Se sul rilievo è scritto un nome o numero di grotta, confrontalo con il numero e nome catastale sopra indicati e con i sinonimi. Solo se NON corrisponde con nessuno (considerando che possono esserci abbreviazioni o sinonimi) allora aggiungi nelle note_generali un avviso tipo 'Nome sul rilievo [nome trovato] diverso da catasto'.

Rispondi SOLO con un JSON in questo formato esatto:
{
  \"scala\": \"Y/N/?\",
  \"nord\": \"Y/N/?\",
  \"punto_zero\": \"Y/N/?\",
  \"dati_metrici\": \"Y/N/?\",
  \"scritta_pianta\": \"Y/N/?\",
  \"scritta_sezione\": \"Y/N/?\",
  \"entrambe_viste\": \"Y/N/?\",
  \"spessore_bordo\": \"Y/N/?\",
  \"spessore_dettagli\": \"Y/N/?\",
  \"topografi\": \"Y/N/?\",
  \"morfologia\": \"Y/N/?\",
  \"sezioni_trasv\": \"Y/N/?\",
  \"nome_cavita\": \"Y/N/?\",
  \"note_generali\": \"breve riassunto sulla qualità del rilievo, indicando se conforme allo standard regionale [se presente nome trovato]\"
}

Non aggiungere altro testo, solo il JSON.";

$mime_type = mime_content_type($image_file);
$base64_file = base64_encode(file_get_contents($image_file));

$reply = openkis_AiChat(array(
    array("type" => "text", "text" => $prompt),
    array("type" => "image_url", "image_url" => array("url" => "data:{$mime_type};base64,{$base64_file}")),
));
if (!$reply['ok'])
{
    openkis_ai_survey_Reply(array("error" => "Errore AI ({$reply['error']})"), 502);
}
$analysis = openkis_AiJson($reply['content']);
if (!is_array($analysis))
{
    openkis_ai_survey_Reply(array("error" => "JSON non valido nella risposta AI", "raw" => $reply['content']), 502);
}

// Save the result so the edit form can show it again without a new API call
$analyzed_at = date("Y-m-d H:i");
if (!is_dir($ai_cache_dir))
{
    @mkdir($ai_cache_dir, 0755, true);
}
@file_put_contents($ai_cache_file, json_encode(array(
    "image_md5" => $image_md5,
    "analyzed_at" => $analyzed_at,
    "model" => $ai_model,
    "analysis" => $analysis,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

openkis_ai_survey_Reply(array(
    "ok" => true,
    "cached" => false,
    "analyzed_at" => $analyzed_at,
    "survey" => $survey_meta,
    "model" => $ai_model,
    "analysis" => $analysis,
));
