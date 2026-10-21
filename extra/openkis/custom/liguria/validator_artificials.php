<?php

/**
 * Liguria record validator for the "artificials" section.
 *
 * Mirrors the minimum requirements used for natural caves
 * (doc/validazione, CampiScheda-20250327), adapted to the artificial
 * cavities tables:
 *   CAVITA':  name, speleometric data, survey, description
 *   INGRESSO: coordinates, photo, access itinerary
 * plus uniformity notices on the most recent survey (author, date,
 * accuracy, license) and on the record photo author.
 *
 * Related tables use the codeartificial foreign key:
 *   ctl_surveys_artificials.codeartificial
 *   ctl_photos_artificials.codeartificial
 *
 * Loaded on demand by openkis_ValidateRecord() in extra/openkis_validator.inc.php.
 */
require_once __DIR__ . "/ai_survey_analysis.inc.php";

/**
 * @param array $row      ctl_artificials record values
 * @param array $findings findings produced by the default validator
 * @return array final list of findings
 */
function openkis_ValidateRecord_custom_artificials($row, $findings)
{
    // The default rules already cover: name, description, coordinates and
    // reference system, elevation (elevation or elevation_map), comune,
    // provincia, speleometric data and total depth consistency.

    if (openkis_ValidatorFieldEmpty($row, "localita"))
    {
        openkis_ValidatorAdd($findings, "notice", "Localita'", FN_Translate("localita' non indicata"));
    }

    if (openkis_ValidatorFieldEmpty($row, "typology"))
    {
        openkis_ValidatorAdd($findings, "notice", FN_Translate("Tipologia"), FN_Translate("tipologia non indicata"));
    }

    // INGRESSO: access itinerary (ctl_artificials has no itinerary author field)
    if (openkis_ValidatorFieldEmpty($row, "itinerary"))
    {
        openkis_ValidatorAdd($findings, "missing", FN_Translate("Itinerario"), FN_Translate("manca l'itinerario di accesso"));
    }

    // Photo attached to the record: when present it must have an author
    if (!openkis_ValidatorFieldEmpty($row, "photo1") && openkis_ValidatorFieldEmpty($row, "authorphoto1"))
    {
        openkis_ValidatorAdd($findings, "notice", FN_Translate("Autore foto"), FN_Translate("foto presente ma senza autore"));
    }

    // The checks below need the cadastre code to query the related tables
    $code = isset($row['code']) ? trim((string) $row['code']) : "";
    if ($code === "")
    {
        openkis_ValidatorAdd($findings, "notice", FN_Translate("Numero di catasto"), FN_Translate("numero di catasto non assegnato: controlli su rilievi e foto non eseguiti"));
        return $findings;
    }
    $code_escaped = str_replace("'", "''", $code);

    // INGRESSO: at least one photo (in ctl_photos_artificials or attached to the record)
    $photos = FN_XMETADBQuery("SELECT COUNT(*) AS count FROM ctl_photos_artificials WHERE codeartificial = '{$code_escaped}'");
    $photoscount = (is_array($photos) && isset($photos[0]['count'])) ? intval($photos[0]['count']) : 0;
    if ($photoscount == 0 && openkis_ValidatorFieldEmpty($row, "photo1"))
    {
        openkis_ValidatorAdd($findings, "missing", FN_Translate("Foto"), FN_Translate("nessuna foto dell'ingresso"));
    }

    // CAVITA': at least one survey; uniformity checks on the most recent one
    $surveys = FN_XMETADBQuery("SELECT * FROM ctl_surveys_artificials WHERE codeartificial = '{$code_escaped}'");
    if (!is_array($surveys) || count($surveys) == 0)
    {
        openkis_ValidatorAdd($findings, "missing", FN_Translate("Rilievo"), FN_Translate("nessun rilievo associato alla cavita'"));
    }
    else
    {
        // Most recent survey first; dates are free text, strtotime() may
        // fail on historical values and then the original order is kept.
        usort($surveys, function ($a, $b)
        {
            $ta = isset($a['date']) ? (int) strtotime($a['date']) : 0;
            $tb = isset($b['date']) ? (int) strtotime($b['date']) : 0;
            return $tb - $ta;
        });
        $latest = $surveys[0];
        // The cave name on the drawing is not a record field: it is checked
        // by the AI analysis below, when available
        $surveychecks = array(
            "author" => FN_Translate("autore del disegno"),
            "date" => FN_Translate("data del rilevamento"),
            "accuracy" => FN_Translate("grado di precisione"),
            "license" => FN_Translate("licenza di utilizzo"),
        );
        $missingparts = array();
        foreach ($surveychecks as $surveyfield => $surveylabel)
        {
            if (openkis_ValidatorFieldEmpty($latest, $surveyfield))
            {
                $missingparts[] = $surveylabel;
            }
        }
        if (count($missingparts))
        {
            openkis_ValidatorAdd($findings, "notice", FN_Translate("Rilievo piu' recente"), FN_Translate("dati mancanti") . ": " . implode(", ", $missingparts));
        }
        openkis_LiguriaAiSurveyFindings($findings, "ctl_surveys_artificials", $latest);
    }

    return $findings;
}
