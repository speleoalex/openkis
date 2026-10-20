<?php

/**
 * OpenKIS record validator.
 *
 * Non-blocking validation of cadastre records: the findings are shown as an
 * informative banner in the edit form (sections/<mod>/bottom_edit.php) and
 * never prevent saving.
 *
 * A region can extend or replace the default rules by providing:
 *   extra/openkis/custom/<region>/validator_<mod>.php
 * defining:
 *   function openkis_ValidateRecord_custom_<mod>($row, $findings)
 * which receives the record and the default findings and returns the final
 * list of findings (it may add, remove or rewrite entries).
 *
 * A finding is an associative array:
 *   array(
 *     "severity" => "missing" | "notice", // missing = required data absent,
 *                                         // notice  = data to complete or make uniform
 *     "label"    => short field/group name,
 *     "message"  => explanation shown to the user,
 *   )
 */

/**
 * Returns true when a text field is absent from the record or blank.
 *
 * @param array  $row
 * @param string $field
 * @return bool
 */
function openkis_ValidatorFieldEmpty($row, $field)
{
    if (!is_array($row) || !array_key_exists($field, $row))
    {
        return true;
    }
    return trim((string) $row[$field]) === "";
}

/**
 * Returns true when a numeric field holds a value. "0" is a valid value
 * (e.g. a horizontal cave has depth 0), only null/blank count as absent.
 *
 * @param array  $row
 * @param string $field
 * @return bool
 */
function openkis_ValidatorHasNumber($row, $field)
{
    if (!is_array($row) || !array_key_exists($field, $row))
    {
        return false;
    }
    return $row[$field] !== null && trim((string) $row[$field]) !== "";
}

/**
 * Appends a finding to the list.
 *
 * @param array  $findings passed by reference
 * @param string $severity "missing" or "notice"
 * @param string $label
 * @param string $message
 */
function openkis_ValidatorAdd(&$findings, $severity, $label, $message)
{
    $findings[] = array(
        "severity" => $severity,
        "label" => $label,
        "message" => $message,
    );
}

/**
 * Default region-independent rules. Every check is guarded on the field
 * existence so the same rules can run on tables with a different layout
 * (caves, artificials, ...).
 *
 * @param string $mod section name (e.g. "caves")
 * @param array  $row record values
 * @return array list of findings
 */
function openkis_ValidateRecordDefault($mod, $row)
{
    $findings = array();
    if (!is_array($row))
    {
        return $findings;
    }

    if (array_key_exists("name", $row) && openkis_ValidatorFieldEmpty($row, "name"))
    {
        openkis_ValidatorAdd($findings, "missing", FN_Translate("Nome"), FN_Translate("manca il nome della cavita'"));
    }

    if (array_key_exists("description", $row) && openkis_ValidatorFieldEmpty($row, "description"))
    {
        openkis_ValidatorAdd($findings, "missing", FN_Translate("Descrizione"), FN_Translate("manca la descrizione della cavita'"));
    }

    // Entrance coordinates and reference system
    if (array_key_exists("latitude_txt", $row))
    {
        if (openkis_ValidatorFieldEmpty($row, "latitude_txt"))
        {
            openkis_ValidatorAdd($findings, "missing", FN_Translate("Coordinate"), FN_Translate("mancano le coordinate dell'ingresso"));
        }
        elseif (array_key_exists("coordinates_type", $row) && openkis_ValidatorFieldEmpty($row, "coordinates_type"))
        {
            openkis_ValidatorAdd($findings, "notice", FN_Translate("Sistema di riferimento"), FN_Translate("coordinate presenti ma senza sistema di riferimento"));
        }
    }

    // Elevation: the map elevation (elevation_map) is an accepted alternative
    // to the measured one, so the record is fine when either is present.
    if (array_key_exists("elevation", $row) || array_key_exists("elevation_map", $row))
    {
        if (!openkis_ValidatorHasNumber($row, "elevation") && !openkis_ValidatorHasNumber($row, "elevation_map"))
        {
            openkis_ValidatorAdd($findings, "missing", FN_Translate("Quota"), FN_Translate("manca la quota dell'ingresso (altimetrica o cartografica)"));
        }
    }

    if (array_key_exists("comune", $row) && openkis_ValidatorFieldEmpty($row, "comune"))
    {
        openkis_ValidatorAdd($findings, "notice", FN_Translate("Comune"), FN_Translate("comune non indicato"));
    }
    if (array_key_exists("provincia", $row) && openkis_ValidatorFieldEmpty($row, "provincia"))
    {
        openkis_ValidatorAdd($findings, "notice", FN_Translate("Provincia"), FN_Translate("provincia non indicata"));
    }

    // Speleometric data: at least one measure must be present, and the total
    // depth must match |positive| + |negative| (0.1 m tolerance for rounding,
    // same rule as the validation report).
    $speleofields = array("depth_positive", "depth_negative", "depth_total", "lenght_total", "lenght_planimetric", "lenght_extension");
    $present = 0;
    foreach ($speleofields as $speleofield)
    {
        if (openkis_ValidatorHasNumber($row, $speleofield))
        {
            $present++;
        }
    }
    if (array_key_exists("depth_total", $row) || array_key_exists("lenght_total", $row))
    {
        if ($present == 0)
        {
            openkis_ValidatorAdd($findings, "missing", FN_Translate("Dati speleometrici"), FN_Translate("nessun dato speleometrico (dislivelli, sviluppi)"));
        }
        elseif (openkis_ValidatorHasNumber($row, "depth_total") && (openkis_ValidatorHasNumber($row, "depth_positive") || openkis_ValidatorHasNumber($row, "depth_negative")))
        {
            $positive = openkis_ValidatorHasNumber($row, "depth_positive") ? abs((float) $row['depth_positive']) : 0.0;
            $negative = openkis_ValidatorHasNumber($row, "depth_negative") ? abs((float) $row['depth_negative']) : 0.0;
            if (abs(($positive + $negative) - abs((float) $row['depth_total'])) >= 0.1)
            {
                openkis_ValidatorAdd($findings, "notice", FN_Translate("Dislivello totale"), FN_Translate("il dislivello totale non corrisponde alla somma di positivo e negativo"));
            }
        }
    }

    return $findings;
}

/**
 * Validates a record: runs the default rules, then the regional override
 * when extra/openkis/custom/<region>/validator_<mod>.php exists and defines
 * openkis_ValidateRecord_custom_<mod>().
 *
 * @global array $_FN
 * @param string $mod section name (e.g. "caves")
 * @param array  $row record values
 * @return array list of findings (empty when the record is complete)
 */
function openkis_ValidateRecord($mod, $row)
{
    global $_FN;
    $findings = openkis_ValidateRecordDefault($mod, $row);
    if (!empty($_FN['openkis_custom']))
    {
        $customfile = "extra/openkis/custom/{$_FN['openkis_custom']}/validator_{$mod}.php";
        if (file_exists($customfile))
        {
            include_once ($customfile);
        }
        $customfunction = "openkis_ValidateRecord_custom_{$mod}";
        if (function_exists($customfunction))
        {
            $findings = $customfunction($row, $findings);
        }
    }
    return is_array($findings) ? $findings : array();
}

/**
 * Renders the findings as a Bootstrap alert to show above the edit form.
 * Returns a green confirmation when there is nothing to report.
 *
 * @param array $findings
 * @return string HTML
 */
function openkis_ValidatorHtml($findings)
{
    $html = "<div class=\"openkis_validator\">";
    if (empty($findings))
    {
        $html .= "<div class=\"alert alert-success\">";
        $html .= FN_Translate("La scheda contiene tutti i dati minimi richiesti") . ".";
        $html .= "</div>";
        $html .= "</div>";
        return $html;
    }
    $missing = array();
    $notices = array();
    foreach ($findings as $finding)
    {
        if (isset($finding['severity']) && $finding['severity'] === "missing")
        {
            $missing[] = $finding;
        }
        else
        {
            $notices[] = $finding;
        }
    }
    $html .= "<div class=\"alert alert-warning\">";
    $html .= "<h5>" . FN_Translate("Controllo scheda") . "</h5>";
    if (count($missing))
    {
        $html .= "<strong>" . FN_Translate("Dati minimi mancanti") . ":</strong>";
        $html .= "<ul>";
        foreach ($missing as $finding)
        {
            $html .= "<li><b>" . htmlspecialchars($finding['label']) . "</b>: " . htmlspecialchars($finding['message']) . "</li>";
        }
        $html .= "</ul>";
    }
    if (count($notices))
    {
        $html .= "<strong>" . FN_Translate("Da completare o uniformare") . ":</strong>";
        $html .= "<ul>";
        foreach ($notices as $finding)
        {
            $html .= "<li><b>" . htmlspecialchars($finding['label']) . "</b>: " . htmlspecialchars($finding['message']) . "</li>";
        }
        $html .= "</ul>";
    }
    $html .= "<small>" . FN_Translate("Il salvataggio non e' bloccato: queste segnalazioni servono per la validazione della scheda") . ".</small>";
    $html .= "</div>";
    $html .= "</div>";
    return $html;
}
