<?php

/**
 * AI survey analysis endpoint (Liguria only).
 *
 * Called asynchronously from the record edit form: sends the survey image to
 * the OpenAI vision API and returns, as JSON, the compliance checks required
 * by the regional cadastre standard (CampiScheda-20250327). Same prompt and
 * model as the CLI batch script validate_ai_surveys.php.
 *
 * GET parameters:
 *   mod    = caves | artificials
 *   survey = numeric id of the record in ctl_surveys / ctl_surveys_artificials
 *
 * Requirements:
 *   - logged in user (any group);
 *   - $_FN['openai_apikey'] set in openkis_config.local.php (never in code:
 *     the repositories are public).
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

$openai_apikey = isset($_FN['openai_apikey']) ? trim($_FN['openai_apikey']) : "";
if ($openai_apikey === "")
{
    openkis_ai_survey_Reply(array("error" => "Chiave OpenAI non configurata (openai_apikey in openkis_config.local.php)"), 500);
}
$openai_model = !empty($_FN['openai_model']) ? $_FN['openai_model'] : "gpt-4o-mini";

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

// The OpenAI data-URI limit is 20 MB: stay below it
$max_bytes = 15 * 1024 * 1024;
if (filesize($image_file) > $max_bytes)
{
    openkis_ai_survey_Reply(array("error" => "Immagine troppo grande (oltre 15 MB)"), 413);
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
  \"note_generali\": \"breve riassunto sulla qualità del rilievo, indicando se conforme allo standard regionale [se presente nome trovato]\"
}

Non aggiungere altro testo, solo il JSON.";

$mime_type = mime_content_type($image_file);
$base64_file = base64_encode(file_get_contents($image_file));

$payload = array(
    "model" => $openai_model,
    "messages" => array(
        array(
            "role" => "user",
            "content" => array(
                array("type" => "text", "text" => $prompt),
                array(
                    "type" => "image_url",
                    "image_url" => array("url" => "data:{$mime_type};base64,{$base64_file}"),
                ),
            ),
        ),
    ),
    "max_tokens" => 500,
);

set_time_limit(180);
$ch = curl_init("https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "Authorization: Bearer " . $openai_apikey,
));
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($response === false)
{
    openkis_ai_survey_Reply(array("error" => "Errore di rete verso OpenAI: {$curl_error}"), 502);
}
if ($http_code !== 200)
{
    // Pass the API error message along, without the raw payload
    $api_error = "HTTP {$http_code}";
    $decoded_error = json_decode($response, true);
    if (isset($decoded_error['error']['message']))
    {
        $api_error .= ": " . $decoded_error['error']['message'];
    }
    openkis_ai_survey_Reply(array("error" => "Errore OpenAI ({$api_error})"), 502);
}

$result = json_decode($response, true);
if (!isset($result['choices'][0]['message']['content']))
{
    openkis_ai_survey_Reply(array("error" => "Risposta API non valida"), 502);
}
$content = $result['choices'][0]['message']['content'];

// The model is asked for a flat JSON object: decode it directly or extract
// the first {...} block (same approach as the CLI batch script)
$analysis = json_decode(trim($content), true);
if (!is_array($analysis) && preg_match('/\{[^}]+\}/s', $content, $matches))
{
    $analysis = json_decode($matches[0], true);
}
if (!is_array($analysis))
{
    openkis_ai_survey_Reply(array("error" => "JSON non valido nella risposta AI", "raw" => $content), 502);
}

openkis_ai_survey_Reply(array(
    "ok" => true,
    "survey" => array(
        "id" => $survey_id,
        "name" => isset($survey['name']) ? $survey['name'] : "",
        "date" => isset($survey['date']) ? $survey['date'] : "",
        "author" => isset($survey['author']) ? $survey['author'] : "",
        "file" => basename($photo1),
    ),
    "model" => $openai_model,
    "analysis" => $analysis,
));
