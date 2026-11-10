<?php

/**
 * Client of the AI service (any OpenAI compatible chat completions API) and
 * AI check of a cave record, used by the edit form ("Verifica scheda con
 * AI") and by the report.
 *
 * The administrator switches the service on and sets key, address and model
 * in the configuration section (configopenkis, extra/openkis/config.php:
 * ai_enabled, ai_apikey, ai_url, ai_model, ai_reasoning_effort); no AI
 * button is shown while it is off. Key and model fall back to
 * $_FN['openai_apikey'] / $_FN['openai_model'] of openkis_config.local.php.
 * The key never goes in the code: the repositories are public.
 */

/**
 * AI service settings.
 *
 * @global array $_FN
 * @return array enabled (switched on and with a key), apikey, url, model,
 *               reasoning_effort ("" = not sent)
 */
function openkis_AiSettings()
{
    global $_FN;
    static $settings = null;
    if ($settings !== null)
    {
        return $settings;
    }
    $config = FN_LoadConfig("extra/openkis/config.php");
    $value = function ($key, $legacy, $default) use ($config, $_FN)
    {
        if (isset($config[$key]) && trim((string) $config[$key]) !== "")
        {
            return trim((string) $config[$key]);
        }
        if ($legacy !== "" && !empty($_FN[$legacy]))
        {
            return trim((string) $_FN[$legacy]);
        }
        return $default;
    };
    $settings = array(
        "enabled" => false,
        "apikey" => $value("ai_apikey", "openai_apikey", ""),
        // base address of the API (https://api.openai.com/v1, https://ollama.com/v1, ...)
        // or the full chat completions endpoint
        "url" => preg_replace('#(/chat/completions)?/*$#', '', $value("ai_url", "", "https://api.openai.com/v1")) . "/chat/completions",
        "model" => $value("ai_model", "openai_model", "gpt-4o-mini"),
        // sent only when set: models without reasoning reject the parameter
        "reasoning_effort" => in_array($value("ai_reasoning_effort", "", ""), array("none", "low", "medium", "high"), true) ? $value("ai_reasoning_effort", "", "") : "",
    );
    $settings['enabled'] = $value("ai_enabled", "", "0") === "1" && $settings['apikey'] !== "";
    return $settings;
}

/**
 * Sends one user message to the AI service.
 *
 * @param string|array $content text, or the list of content parts of the
 *                              chat completions API (text and image_url)
 * @param int          $max_tokens reasoning models spend part of it before the
 *                                 answer: a low limit gives an empty reply
 * @return array ok, content (reply text), model, error (message for the user)
 */
function openkis_AiChat($content, $max_tokens = 16000)
{
    $settings = openkis_AiSettings();
    $ret = array("ok" => false, "content" => "", "model" => $settings['model'], "error" => "");
    if (!$settings['enabled'])
    {
        $ret['error'] = FN_Translate("AI service not configured (Configuration section)");
        return $ret;
    }
    if (!function_exists("curl_init"))
    {
        $ret['error'] = "curl not available";
        return $ret;
    }
    $payload = array(
        "model" => $settings['model'],
        "messages" => array(array("role" => "user", "content" => $content)),
        "max_tokens" => intval($max_tokens),
    );
    if ($settings['reasoning_effort'] !== "")
    {
        $payload['reasoning_effort'] = $settings['reasoning_effort'];
    }
    set_time_limit(180);
    $ch = curl_init($settings['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $settings['apikey'],
    ));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    // no curl_close(): a no-op since PHP 8.0, deprecated in 8.5
    if ($response === false)
    {
        $ret['error'] = FN_Translate("network error") . ": $curl_error";
        return $ret;
    }
    $decoded = json_decode($response, true);
    if ($http_code !== 200)
    {
        // the API error message, never the raw payload
        $ret['error'] = "HTTP $http_code" . (isset($decoded['error']['message']) ? ": " . $decoded['error']['message'] : "");
        return $ret;
    }
    if (!isset($decoded['choices'][0]['message']['content']) || trim((string) $decoded['choices'][0]['message']['content']) === "")
    {
        $ret['error'] = FN_Translate("invalid AI reply");
        return $ret;
    }
    $ret['ok'] = true;
    $ret['content'] = $decoded['choices'][0]['message']['content'];
    return $ret;
}

/**
 * JSON object of a reply: the whole text, or the outermost {...} block when
 * the model wraps it in other text or a code fence.
 *
 * @param string $content
 * @return array|null
 */
function openkis_AiJson($content)
{
    $data = json_decode(trim($content), true);
    if (!is_array($data) && preg_match('/\{.*\}/s', $content, $matches))
    {
        $data = json_decode($matches[0], true);
    }
    return is_array($data) ? $data : null;
}

/**
 * Path of the saved AI check of a record.
 *
 * @global array $_FN
 * @param string $mod
 * @param string $id
 * @return string
 */
function openkis_AiRecordFile($mod, $id)
{
    global $_FN;
    return "{$_FN['datadir']}/_cache/openkis_ai_record/ctl_" . preg_replace('/[^a-z0-9_]/', '', $mod) . "_" . preg_replace('/[^0-9]/', '', (string) $id) . ".json";
}

/**
 * Saved AI check of a record, or null.
 *
 * @param string $mod
 * @param string $id
 * @return array|null analyzed_at, model, recordupdate, analysis
 */
function openkis_AiRecordLoad($mod, $id)
{
    $file = openkis_AiRecordFile($mod, $id);
    $saved = is_file($file) ? json_decode(file_get_contents($file), true) : null;
    return (is_array($saved) && isset($saved['analysis']) && is_array($saved['analysis'])) ? $saved : null;
}

/**
 * True when the current user may run the AI check of a record: the service
 * is on and the user is an administrator or may edit the record.
 *
 * @global array $_FN
 * @param string $mod
 * @param string $id
 * @return bool
 */
function openkis_AiRecordCanRun($mod, $id)
{
    global $_FN;
    $settings = openkis_AiSettings();
    if (!$settings['enabled'] || $_FN['user'] == "")
    {
        return false;
    }
    return FN_IsAdmin() || openkis_FileDbview($mod)->CanEditRecord($id, "ctl_$mod");
}

/**
 * Anti CSRF token of the AI check, bound to the logged user.
 *
 * @global array $_FN
 * @return string
 */
function openkis_AiRecordToken()
{
    global $_FN;
    return hash_hmac("sha256", "openkis-ai-record|" . $_FN['user'], FN_GetSiteSecret());
}

/**
 * Has the AI service check a cave record against the cadastre rules (errors
 * in the texts, gaps, inconsistencies between texts and data) and saves the
 * result.
 *
 * @param string $mod
 * @param string $id
 * @return array ok, error, saved
 */
function openkis_AiRecordAnalyze($mod, $id)
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

    $prompt = "Sei un curatore di un catasto speleologico regionale italiano. Verifica la scheda di grotta qui sotto.

Requisiti della scheda: nome (univoco, non offensivo), dati speleometrici misurati (sviluppo reale, dislivelli positivo, negativo e totale = somma dei valori assoluti), rilievo con disegno (pianta e sezione), descrizione, coordinate dell'ingresso, foto dell'ingresso, itinerario di accesso.
La descrizione dovrebbe trattare: morfologia, pericoli, idrologia (corsi d'acqua, laghi, sifoni), roccia, pozzi e armo, collegamenti con altre grotte, possibili prosecuzioni, storia esplorativa, percorribilita' (attrezzatura, acqua, fango, strettoie).
L'itinerario deve permettere di ritrovare l'ingresso.
Il numero di catasto si scrive in piu' modi equivalenti: LI12, 12 Li, 12 Li(GE), 12 Li/GE: non sono errori.

Cerca in particolare:
- errori nei testi: refusi, parole o frasi troncate, testo incomprensibile, testo che parla di un'altra grotta, testo incollato nel campo sbagliato;
- difformita' fra testi e dati: misure, quote, dislivelli, pozzi, nomi, comune o localita' citati nei testi diversi da quelli dei campi; andamento, idrologia, chiusa/distrutta in contrasto con la descrizione.

DATI DELLA SCHEDA:
{$data}
CONTROLLI AUTOMATICI GIA' ESEGUITI: sono gia' noti al curatore, non riportarli. Concentrati su quello che solo la lettura dei testi puo' rivelare.
" . ($findings !== "" ? $findings : "- nessuna segnalazione\n") . "
Rispondi SOLO con un JSON in questo formato:
{
  \"valutazione\": \"buona\" | \"sufficiente\" | \"insufficiente\",
  \"sintesi\": \"una o due frasi sullo stato della scheda\",
  \"errori\": [\"errori nei testi, citando il campo e il passo\"],
  \"difformita\": [\"difformita' fra testi e dati, citando i valori\"],
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
    $file = openkis_AiRecordFile($mod, $id);
    if (!is_dir(dirname($file)))
    {
        @mkdir(dirname($file), 0755, true);
    }
    @file_put_contents($file, json_encode($saved, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return array("ok" => true, "error" => "", "saved" => $saved);
}

/**
 * Block of the AI check of a record: last result (collapsed in the report,
 * open in the edit form) and the button that runs it. The button works with
 * the script of openkis_AiRecordScript(), once per page.
 *
 * @param string     $mod
 * @param string     $id
 * @param string     $recordupdate current last change of the record
 * @param array|null $saved        openkis_AiRecordLoad()
 * @param bool       $open         result shown open
 * @return string
 */
function openkis_AiRecordHtml($mod, $id, $recordupdate, $saved, $open = false)
{
    $html = "<div class=\"openkis-ai-record\" data-mod=\"" . htmlspecialchars($mod) . "\" data-id=\"" . htmlspecialchars($id) . "\" data-open=\"" . ($open ? "1" : "") . "\">";
    if ($saved)
    {
        $analysis = $saved['analysis'];
        $verdict = isset($analysis['valutazione']) ? strtolower(trim((string) $analysis['valutazione'])) : "";
        $colors = array("buona" => "#1e7b34", "sufficiente" => "#8a5a00", "insufficiente" => "#b02a37");
        $badge = isset($colors[$verdict]) ? "background:{$colors[$verdict]};" : "background:#6c757d;";
        $html .= "<details" . ($open ? " open" : "") . "><summary><span style=\"{$badge}color:#fff;border-radius:3px;padding:0 6px;\">" . htmlspecialchars($verdict !== "" ? $verdict : "?") . "</span> ";
        $html .= "<small>" . htmlspecialchars($saved['analyzed_at']) . "</small>";
        if ((string) $saved['recordupdate'] !== (string) $recordupdate)
        {
            $html .= " <small>(" . htmlspecialchars(FN_Translate("record changed after the analysis")) . ")</small>";
        }
        $html .= "</summary>";
        if (!empty($analysis['sintesi']))
        {
            $html .= "<p>" . htmlspecialchars((string) $analysis['sintesi']) . "</p>";
        }
        // "problemi": analyses saved before the errors/inconsistencies split
        foreach (array("errori" => "errors in the texts", "difformita" => "inconsistencies", "problemi" => "problems", "suggerimenti" => "suggestions") as $key => $title)
        {
            if (!empty($analysis[$key]) && is_array($analysis[$key]))
            {
                $html .= "<strong>" . htmlspecialchars(FN_Translate($title)) . "</strong><ul>";
                foreach ($analysis[$key] as $line)
                {
                    $html .= "<li>" . htmlspecialchars(is_scalar($line) ? (string) $line : json_encode($line, JSON_UNESCAPED_UNICODE)) . "</li>";
                }
                $html .= "</ul>";
            }
        }
        $html .= "<small>" . htmlspecialchars(FN_Translate("automatic analysis") . " (" . $saved['model'] . "): " . FN_Translate("always check the results")) . "</small>";
        $html .= "</details>";
    }
    $html .= "<button type=\"button\" class=\"btn btn-sm btn-outline-secondary\" style=\"min-height:44px\">" . htmlspecialchars(FN_Translate($saved ? "check the record again with AI" : "check the record with AI")) . "</button>";
    return "$html</div>";
}

/**
 * Script of the AI check buttons (openkis_AiRecordHtml()): posts to the
 * report section, which runs the check, and puts the returned block in
 * place of the old one.
 *
 * @global array $_FN
 * @return string
 */
function openkis_AiRecordScript()
{
    global $_FN;
    $config = json_encode(array(
        "url" => FN_RewriteLink("index.php?mod=reports", "&", true),
        "token" => openkis_AiRecordToken(),
        "running" => FN_Translate("analysis in progress (up to a minute)..."),
        "error" => FN_Translate("network error"),
    ), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
    return "<script>
(function () {
    var cfg = $config;
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.openkis-ai-record button') : null;
        if (!btn) { return; }
        var box = btn.closest('.openkis-ai-record');
        var status = box.querySelector('[role=status]');
        if (!status) { status = document.createElement('div'); status.setAttribute('role', 'status'); box.appendChild(status); }
        var body = new FormData();
        body.append('op', 'ai');
        body.append('mod', box.getAttribute('data-mod'));
        body.append('id', box.getAttribute('data-id'));
        body.append('open', box.getAttribute('data-open'));
        body.append('token', cfg.token);
        btn.disabled = true;
        status.textContent = cfg.running;
        fetch(cfg.url, {method: 'POST', body: body, credentials: 'same-origin'})
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                if (data && data.ok && data.html) { box.outerHTML = data.html; return; }
                btn.disabled = false;
                status.textContent = (data && data.error) ? data.error : '?';
            })
            .catch(function (err) { btn.disabled = false; status.textContent = cfg.error + ': ' + err; });
    });
})();
</script>";
}

/**
 * Handles the POST of the AI check buttons (op=ai, mod, id, open, token) and
 * replies with JSON: ok, error, html (the new block).
 *
 * @global array $_FN
 */
function openkis_AiRecordReply()
{
    global $_FN;
    $mod = FN_GetParam("mod", $_POST) === "caves" ? "caves" : "";
    $id = preg_replace('/[^0-9]/', '', (string) FN_GetParam("id", $_POST));
    $reply = array("ok" => false, "error" => FN_Translate("you may not do that"));
    if ($mod !== "" && $id !== "" && $_FN['user'] != ""
        && hash_equals(openkis_AiRecordToken(), (string) FN_GetParam("token", $_POST)) && openkis_AiRecordCanRun($mod, $id))
    {
        $result = openkis_AiRecordAnalyze($mod, $id);
        $reply = array("ok" => $result['ok'], "error" => $result['error']);
        if ($result['ok'])
        {
            $reply['html'] = openkis_AiRecordHtml($mod, $id, $result['saved']['recordupdate'], $result['saved'], FN_GetParam("open", $_POST) === "1");
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
