<?php

/**
 * Saved AI survey analyses (Liguria only).
 *
 * openkis_ai_survey.php stores every successful analysis as JSON in
 * {datadir}/_cache/openkis_ai_survey/<table>_<id>.json together with the md5
 * of the analyzed image. These helpers give the validators read access to
 * that result, so that drawing-related checks (cave name on the drawing,
 * ...) are reported only after a curator has actually run the analysis and
 * only while the survey image is unchanged.
 */

/**
 * Path of the saved analysis for a survey record.
 *
 * @global array  $_FN
 * @param string $surveys_table ctl_surveys | ctl_surveys_artificials
 * @param string $survey_id     primary key of the survey record
 * @return string
 */
function openkis_LiguriaAiSurveyCacheFile($surveys_table, $survey_id)
{
    global $_FN;
    $dir = (!empty($_FN['datadir']) ? $_FN['datadir'] : "misc") . "/_cache/openkis_ai_survey";
    return "{$dir}/{$surveys_table}_" . preg_replace('/[^0-9]/', '', (string) $survey_id) . ".json";
}

/**
 * Saved analysis of a survey, or null when there is none or the survey
 * image changed after the analysis.
 *
 * @param string $surveys_table ctl_surveys | ctl_surveys_artificials
 * @param array  $survey        survey record (id, photo1)
 * @return array|null array(image_md5, analyzed_at, model, analysis)
 */
function openkis_LiguriaAiSurveyAnalysis($surveys_table, $survey)
{
    if (!is_array($survey) || !isset($survey['id']))
    {
        return null;
    }
    $cachefile = openkis_LiguriaAiSurveyCacheFile($surveys_table, $survey['id']);
    if (!is_file($cachefile))
    {
        return null;
    }
    $saved = json_decode(file_get_contents($cachefile), true);
    if (!is_array($saved) || empty($saved['image_md5']) || !isset($saved['analysis']) || !is_array($saved['analysis']))
    {
        return null;
    }
    $photo1 = isset($survey['photo1']) ? trim($survey['photo1']) : "";
    if ($photo1 === "")
    {
        return null;
    }
    // Same path convention as openkis_ai_survey.php
    $image_file = "misc/fndatabase/{$surveys_table}/" . preg_replace('/[^0-9]/', '', (string) $survey['id']) . "/photo1/" . basename($photo1);
    if (!is_file($image_file) || md5_file($image_file) !== $saved['image_md5'])
    {
        return null;
    }
    return $saved;
}

/**
 * Findings about the most recent survey that can only be known from the
 * AI analysis of the drawing. Nothing is reported until the analysis has
 * been run from the edit form ("Analizza rilievo con AI").
 *
 * @param array  $findings      passed by reference
 * @param string $surveys_table ctl_surveys | ctl_surveys_artificials
 * @param array  $survey        most recent survey record
 */
function openkis_LiguriaAiSurveyFindings(&$findings, $surveys_table, $survey)
{
    $saved = openkis_LiguriaAiSurveyAnalysis($surveys_table, $survey);
    if ($saved === null)
    {
        return;
    }
    $analysis = $saved['analysis'];
    $when = !empty($saved['analyzed_at']) ? " " . $saved['analyzed_at'] : "";
    $label = FN_Translate("Rilievo piu' recente, analisi AI del") . $when;

    // Question 13 of the prompt; analyses saved before it exist without it
    if (isset($analysis['nome_cavita']) && strtoupper(trim((string) $analysis['nome_cavita'])) === "N")
    {
        openkis_ValidatorAdd($findings, "notice", $label, FN_Translate("nome della cavita' non presente sul disegno"));
    }
    // The prompt asks for this exact wording when the drawn name does not
    // match the cadastre name, number or synonyms
    if (!empty($analysis['note_generali']) && preg_match('/diverso da catasto/i', $analysis['note_generali']))
    {
        openkis_ValidatorAdd($findings, "notice", $label, FN_Translate("nome sul rilievo diverso da quello del catasto") . ": " . trim($analysis['note_generali']));
    }
}
