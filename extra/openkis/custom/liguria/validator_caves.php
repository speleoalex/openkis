<?php

/**
 * Liguria record validator for the "caves" section.
 *
 * Implements the minimum requirements of the cadastre record
 * (doc/validazione, CampiScheda-20250327):
 *   GROTTA:   name, speleometric data, survey, description
 *   INGRESSO: coordinates, photo, access itinerary
 * plus uniformity notices on the most recent survey (author, date,
 * accuracy, license) and on photo/itinerary authors.
 *
 * The drawing-related checks (scale, north arrow, border thickness, ...)
 * cannot be verified from the record alone: they stay in the CSV/AI
 * validation workflow (validate_generate_report.php).
 *
 * Loaded on demand by openkis_ValidateRecord() in extra/openkis_validator.inc.php.
 */

/**
 * @param array $row      ctl_caves record values
 * @param array $findings findings produced by the default validator
 * @return array final list of findings
 */
function openkis_ValidateRecord_custom_caves($row, $findings)
{
    // The default rules already cover: name, description, coordinates and
    // reference system, elevation (elevation or elevation_map), comune,
    // provincia, speleometric data and total depth consistency.

    if (openkis_ValidatorFieldEmpty($row, "localita"))
    {
        openkis_ValidatorAdd($findings, "notice", "Localita'", FN_Translate("localita' non indicata"));
    }

    if (openkis_ValidatorFieldEmpty($row, "geologicalformation"))
    {
        openkis_ValidatorAdd($findings, "notice", FN_Translate("Formazione geologica"), FN_Translate("formazione geologica non indicata"));
    }

    // INGRESSO: access itinerary
    if (openkis_ValidatorFieldEmpty($row, "itinerary"))
    {
        openkis_ValidatorAdd($findings, "missing", FN_Translate("Itinerario"), FN_Translate("manca l'itinerario di accesso"));
    }
    elseif (openkis_ValidatorFieldEmpty($row, "authoritinerary"))
    {
        openkis_ValidatorAdd($findings, "notice", FN_Translate("Autore itinerario"), FN_Translate("itinerario presente ma senza autore"));
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

    // INGRESSO: at least one photo (in ctl_photos or attached to the record)
    $photos = FN_XMETADBQuery("SELECT COUNT(*) AS count FROM ctl_photos WHERE codecave = '{$code_escaped}'");
    $photoscount = (is_array($photos) && isset($photos[0]['count'])) ? intval($photos[0]['count']) : 0;
    if ($photoscount == 0 && openkis_ValidatorFieldEmpty($row, "photo1"))
    {
        openkis_ValidatorAdd($findings, "missing", FN_Translate("Foto"), FN_Translate("nessuna foto dell'ingresso"));
    }

    // GROTTA: at least one survey; uniformity checks on the most recent one
    $surveys = FN_XMETADBQuery("SELECT * FROM ctl_surveys WHERE codecave = '{$code_escaped}'");
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
        $surveychecks = array(
            "name" => FN_Translate("nome della cavita' sul disegno"),
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
    }

    return $findings;
}
