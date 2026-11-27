<?php

/**
 * Liguria: AI survey analysis button for the record edit form.
 *
 * Included by extra/openkis/custom/liguria/bottom_edit_{caves,artificials}.php
 * through the region hook in sections/<mod>/bottom_edit.php, so it runs in
 * that scope: $id (record key), $_FN['mod'], $html.
 *
 * If the record has at least one survey with an image, it prepends to the top
 * of the form (the validator banner, prepended afterwards, stays above) a button
 * that calls openkis_ai_survey.php asynchronously and renders the checks of
 * CampiScheda-20250327 returned by the AI service (vision model): mandatory
 * elements first, then the recommended ones, whose absence is not an error.
 * On page load (and when another survey is selected) the last saved analysis
 * is shown automatically via the endpoint's cached=1 read-only mode, which
 * never triggers an API call; the button re-runs the full analysis.
 * The thumbnail of the selected survey is shown next to the result, linked
 * to the full image, so the curator sees which drawing the AI looked at.
 * Nothing is shown when there are no survey images or no API key configured.
 */
if (!isset($id) || $id === "" || empty($_FN['mod']))
{
    return;
}
if (!openkis_AiSettings()['enabled'])
{
    // AI service off or without a key in the configuration section
    return;
}

if ($_FN['mod'] === "artificials")
{
    $ai_surveys_table = "ctl_surveys_artificials";
    $ai_code_field = "codeartificial";
}
else
{
    $ai_surveys_table = "ctl_surveys";
    $ai_code_field = "codecave";
}

// Cadastral code of the record being edited: reuse the row already loaded by
// the validator block when available, otherwise fetch it
$ai_record_code = "";
if (isset($validator_row) && is_array($validator_row) && isset($validator_row['code']))
{
    $ai_record_code = trim((string) $validator_row['code']);
}
else
{
    $ai_record_table = !empty($validator_tablename) ? $validator_tablename : "ctl_{$_FN['mod']}";
    $ai_record_row = FN_XMDBTable($ai_record_table)->GetRecordByPk($id);
    if (is_array($ai_record_row) && isset($ai_record_row['code']))
    {
        $ai_record_code = trim((string) $ai_record_row['code']);
    }
}
if ($ai_record_code === "")
{
    return;
}

$ai_code_escaped = str_replace("'", "''", $ai_record_code);
$ai_surveys = FN_XMETADBQuery("SELECT id, name, date, author, photo1 FROM {$ai_surveys_table} WHERE {$ai_code_field} = '{$ai_code_escaped}'");
if (!is_array($ai_surveys))
{
    return;
}

// Keep only surveys with an image (the endpoint cannot analyze PDFs)
$ai_image_extensions = array("png", "jpg", "jpeg", "gif", "webp");
$ai_surveys_img = array();
foreach ($ai_surveys as $ai_survey)
{
    $ai_photo = isset($ai_survey['photo1']) ? trim($ai_survey['photo1']) : "";
    if ($ai_photo === "")
    {
        continue;
    }
    $ai_ext = strtolower(pathinfo($ai_photo, PATHINFO_EXTENSION));
    if (in_array($ai_ext, $ai_image_extensions))
    {
        $ai_surveys_img[] = $ai_survey;
    }
}
if (count($ai_surveys_img) === 0)
{
    return;
}

// Most recent survey first; dates are free text, strtotime() may fail on
// historical values and then the original order is kept
usort($ai_surveys_img, function ($a, $b)
{
    $ta = isset($a['date']) ? (int) strtotime($a['date']) : 0;
    $tb = isset($b['date']) ? (int) strtotime($b['date']) : 0;
    return $tb - $ta;
});

$ai_endpoint = "{$_FN['siteurl']}extra/openkis/custom/liguria/openkis_ai_survey.php";
$ai_mod_escaped = htmlspecialchars($_FN['mod'], ENT_QUOTES);

// Preview addresses of each survey image, through the table so that the
// protected files hook (openkis_FileUrl) applies; read by the script below
$ai_surveys_xmltable = FN_XMDBTable($ai_surveys_table);
$ai_preview_attributes = function ($survey) use ($ai_surveys_xmltable)
{
    $thumb = $ai_surveys_xmltable->get_thumb($survey, "photo1");
    $file = $ai_surveys_xmltable->get_file($survey, "photo1");
    return " data-thumb=\"" . htmlspecialchars((string) $thumb, ENT_QUOTES) . "\" data-file=\"" . htmlspecialchars((string) $file, ENT_QUOTES) . "\"";
};

// Survey selector: hidden input when there is only one survey
if (count($ai_surveys_img) === 1)
{
    $ai_only = $ai_surveys_img[0];
    $ai_selector = "<input type=\"hidden\" id=\"openkis_ai_survey_select\" value=\"" . htmlspecialchars($ai_only['id'], ENT_QUOTES) . "\"" . $ai_preview_attributes($ai_only) . " />";
}
else
{
    $ai_selector = "<select id=\"openkis_ai_survey_select\" class=\"form-control\" style=\"display:inline-block;width:auto;max-width:60%;margin-right:8px;vertical-align:middle;\">";
    foreach ($ai_surveys_img as $ai_survey)
    {
        $ai_label_parts = array();
        foreach (array("name", "date", "author") as $ai_f)
        {
            if (!empty($ai_survey[$ai_f]))
            {
                $ai_label_parts[] = $ai_survey[$ai_f];
            }
        }
        $ai_label = count($ai_label_parts) ? implode(" - ", $ai_label_parts) : "rilievo {$ai_survey['id']}";
        $ai_selector .= "<option value=\"" . htmlspecialchars($ai_survey['id'], ENT_QUOTES) . "\"" . $ai_preview_attributes($ai_survey) . ">" . htmlspecialchars($ai_label, ENT_QUOTES) . "</option>";
    }
    $ai_selector .= "</select>";
}

$ai_box = <<<HTML
<div id="openkis_ai_survey_box" style="margin:10px 0;">
    {$ai_selector}
    <button type="button" id="openkis_ai_survey_btn" class="btn btn-secondary btn-default">
        Analizza rilievo con AI
    </button>
    <span id="openkis_ai_survey_status" style="margin-left:8px;"></span>
    <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-start;margin-top:8px;">
        <a id="openkis_ai_survey_preview" href="#" target="_blank" rel="noopener" title="Apri il rilievo" style="display:none;flex:0 1 auto;max-width:100%;">
            <img alt="Anteprima del rilievo" loading="lazy" style="display:block;max-width:100%;max-height:320px;border:1px solid #ccc;border-radius:3px;background:#fff;" />
        </a>
        <div id="openkis_ai_survey_result" style="flex:1 1 280px;min-width:0;"></div>
    </div>
</div>
<script>
(function () {
    var btn = document.getElementById("openkis_ai_survey_btn");
    var sel = document.getElementById("openkis_ai_survey_select");
    var status = document.getElementById("openkis_ai_survey_status");
    var box = document.getElementById("openkis_ai_survey_result");
    var preview = document.getElementById("openkis_ai_survey_preview");
    // Thumbnail of the selected survey (falls back to the full image)
    function showPreview() {
        var item = sel.tagName === "SELECT" ? sel.options[sel.selectedIndex] : sel;
        var thumb = item ? (item.getAttribute("data-thumb") || item.getAttribute("data-file")) : "";
        if (!thumb) {
            preview.style.display = "none";
            return;
        }
        preview.href = item.getAttribute("data-file") || thumb;
        preview.firstElementChild.src = thumb;
        preview.style.display = "";
    }
    // Mandatory and recommended elements of the drawing (CampiScheda-20250327);
    // "autore" and "data" are missing from analyses saved before 2026-11-27
    var groups = [
        {title: "Elementi obbligatori", recommended: false, labels: {
            nome_cavita: "Nome o numero della grotta",
            autore: "Autore del disegno",
            data: "Data della topografia",
            scala: "Scala grafica",
            nord: "Nord sulla pianta",
            punto_zero: "Punto zero",
            dati_metrici: "Dati speleometrici (sviluppo, dislivello)",
            entrambe_viste: "Pianta e sezione longitudinale",
            scritta_pianta: "Scritta PIANTA",
            scritta_sezione: "Scritta SEZIONE",
            spessore_bordo: "Bordo grotta a linea spessa",
            spessore_dettagli: "Dettagli distinti dal bordo"
        }},
        {title: "Elementi consigliati (non obbligatori)", recommended: true, labels: {
            topografi: "Rilevatori indicati",
            sezioni_trasv: "Sezioni trasversali",
            morfologia: "Dettagli esterni dell'ingresso"
        }}
    ];
    function badge(value, recommended) {
        var span = document.createElement("span");
        var v = (value || "?").toString().toUpperCase();
        span.textContent = v === "Y" ? "SI" : (v === "N" ? "NO" : "?");
        // a missing recommended element is not an error: grey, not red
        span.style.cssText = "display:inline-block;min-width:2.2em;text-align:center;" +
            "color:#fff;border-radius:3px;padding:1px 4px;margin-right:6px;font-weight:bold;" +
            "background:" + (v === "Y" ? "#28a745" : (v === "N" && !recommended ? "#dc3545" : "#6c757d")) + ";";
        return span;
    }
    function showError(message) {
        var err = document.createElement("div");
        err.className = "alert alert-danger";
        err.textContent = message;
        box.innerHTML = "";
        box.appendChild(err);
    }
    function showResult(data) {
        var panel = document.createElement("div");
        panel.className = "alert alert-info";
        var title = document.createElement("strong");
        var meta = [];
        if (data.survey) {
            if (data.survey.name) { meta.push(data.survey.name); }
            if (data.survey.date) { meta.push(data.survey.date); }
            if (data.survey.file) { meta.push(data.survey.file); }
        }
        title.textContent = "Analisi AI del rilievo" + (meta.length ? " (" + meta.join(" - ") + ")" : "");
        panel.appendChild(title);
        if (data.analyzed_at) {
            var when = document.createElement("div");
            when.style.cssText = "font-size:85%;opacity:0.8;";
            when.textContent = (data.cached ? "Ultima analisi: " : "Analisi del: ") + data.analyzed_at;
            panel.appendChild(when);
        }
        groups.forEach(function (group) {
            var heading = document.createElement("div");
            heading.style.cssText = "margin-top:8px;font-weight:bold;";
            heading.textContent = group.title;
            panel.appendChild(heading);
            var list = document.createElement("div");
            list.style.cssText = "margin-top:4px;column-width:280px;";
            for (var key in group.labels) {
                if (!Object.prototype.hasOwnProperty.call(group.labels, key)) { continue; }
                var row = document.createElement("div");
                row.style.cssText = "margin:2px 0;break-inside:avoid;";
                row.appendChild(badge(data.analysis ? data.analysis[key] : "?", group.recommended));
                row.appendChild(document.createTextNode(group.labels[key]));
                list.appendChild(row);
            }
            panel.appendChild(list);
        });
        if (data.analysis && data.analysis.note_generali) {
            var note = document.createElement("div");
            note.style.marginTop = "6px";
            note.textContent = "Note: " + data.analysis.note_generali;
            panel.appendChild(note);
        }
        var disclaimer = document.createElement("div");
        disclaimer.style.cssText = "margin-top:6px;font-size:85%;opacity:0.8;";
        disclaimer.textContent = "Analisi automatica sperimentale (" + (data.model || "AI") + "): verificare sempre i risultati.";
        panel.appendChild(disclaimer);
        box.innerHTML = "";
        box.appendChild(panel);
        btn.textContent = "Aggiorna analisi AI";
    }
    function runAnalysis(cachedOnly) {
        var surveyId = sel ? sel.value : "";
        if (!surveyId) { return; }
        if (!cachedOnly) {
            btn.disabled = true;
            box.innerHTML = "";
            status.textContent = "Analisi in corso (10-30 secondi)...";
        }
        var url = "{$ai_endpoint}?mod={$ai_mod_escaped}&survey=" + encodeURIComponent(surveyId) + (cachedOnly ? "&cached=1" : "");
        fetch(url, {credentials: "same-origin"})
            .then(function (resp) { return resp.json(); })
            .then(function (data) {
                btn.disabled = false;
                status.textContent = "";
                if (data && data.ok) {
                    showResult(data);
                    return;
                }
                // Cached lookup with no valid saved analysis: stay silent,
                // the user can run the analysis with the button
                if (cachedOnly) { return; }
                showError((data && data.error) ? data.error : "Errore sconosciuto");
            })
            .catch(function (e) {
                btn.disabled = false;
                status.textContent = "";
                if (cachedOnly) { return; }
                showError("Errore di rete: " + e);
            });
    }
    btn.addEventListener("click", function () { runAnalysis(false); });
    if (sel && sel.tagName === "SELECT") {
        sel.addEventListener("change", function () {
            box.innerHTML = "";
            btn.textContent = "Analizza rilievo con AI";
            showPreview();
            runAnalysis(true);
        });
    }
    // Show the preview and the last saved analysis (if the image is
    // unchanged) on page load
    showPreview();
    runAnalysis(true);
})();
</script>
<hr />
HTML;
// Prepend: the box goes above the edit form, below the validator banner
$html = $ai_box . $html;
