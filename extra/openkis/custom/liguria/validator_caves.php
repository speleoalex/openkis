<?php

/**
 * Liguria record validator for the "caves" section.
 *
 * Implements the minimum requirements of the cadastre record
 * (doc/validazione, CampiScheda-20250327):
 *   GROTTA:   name, speleometric data (real development, positive,
 *             negative and total depth), survey, description
 *   INGRESSO: coordinates, photo, access itinerary
 * plus uniformity notices on the most recent survey (author, date,
 * accuracy, license), on photo/itinerary authors, on the elevation, on
 * coordinates outside Liguria and on records sharing the same name.
 *
 * Derogations:
 *   - destroyed (destroyed = Y/P) or closed (closed = Y) caves: photo,
 *     itinerary and survey are notices instead of missing data;
 *   - entrance connected to other caves (caveslinks or a ctl_cavesystems
 *     record): survey, description and speleometric data can be registered
 *     on a connected cave or on the cave system, so they are notices when a
 *     connected record provides them. Coordinates, photo and itinerary stay
 *     mandatory for every entrance.
 *
 * The drawing-related checks (scale, north arrow, border thickness, ...)
 * cannot be verified from the record alone: they stay in the CSV/AI
 * validation workflow (validate_generate_report.php).
 *
 * Loaded on demand by openkis_ValidateRecord() in extra/openkis_validator.inc.php.
 */

/**
 * Removes the default findings with the given label and returns them.
 *
 * @param array  $findings passed by reference
 * @param string $label    translated label
 * @return array removed findings
 */
function openkis_LiguriaValidatorTake(&$findings, $label)
{
    $taken = array();
    $kept = array();
    foreach ($findings as $finding)
    {
        if (isset($finding['label']) && $finding['label'] === $label)
        {
            $taken[] = $finding;
        }
        else
        {
            $kept[] = $finding;
        }
    }
    $findings = $kept;
    return $taken;
}

/**
 * Returns the records connected to a cave: the other caves listed in
 * caveslinks and the cave systems (ctl_cavesystems) that include the code,
 * together with their member caves.
 *
 * @param array  $row  ctl_caves record
 * @param string $code cadastre code of the record
 * @return array array("caves" => code => row, "systems" => list of rows)
 */
function openkis_LiguriaValidatorConnected($row, $code)
{
    $codes = array();
    $systems = array();
    $normalize = function ($list)
    {
        $ret = array();
        foreach (explode(",", (string) $list) as $item)
        {
            $item = strtoupper(trim($item));
            if ($item !== "")
            {
                $ret[] = $item;
            }
        }
        return $ret;
    };
    $codeupper = strtoupper($code);
    $allsystems = FN_XMETADBQuery("SELECT * FROM ctl_cavesystems");
    if (is_array($allsystems))
    {
        foreach ($allsystems as $system)
        {
            $members = $normalize(isset($system['caves']) ? $system['caves'] : "");
            if (in_array($codeupper, $members))
            {
                $systems[] = $system;
                $codes = array_merge($codes, $members);
            }
        }
    }
    $codes = array_merge($codes, $normalize(isset($row['caveslinks']) ? $row['caveslinks'] : ""));
    $caves = array();
    foreach (array_unique($codes) as $linkedcode)
    {
        if ($linkedcode === $codeupper)
        {
            continue;
        }
        $linkedrows = FN_XMETADBQuery("SELECT * FROM ctl_caves WHERE code = '" . str_replace("'", "''", $linkedcode) . "'");
        if (is_array($linkedrows) && isset($linkedrows[0]))
        {
            $caves[$linkedcode] = $linkedrows[0];
        }
    }
    return array("caves" => $caves, "systems" => $systems);
}

/**
 * Short description of a cave system for the messages.
 *
 * @param array $system ctl_cavesystems record
 * @return string
 */
function openkis_LiguriaValidatorSystemName($system)
{
    $name = isset($system['name']) ? trim((string) $system['name']) : "";
    if ($name === "" && isset($system['code']))
    {
        $name = trim((string) $system['code']);
    }
    return FN_Translate("complesso") . " " . ($name !== "" ? $name : "#" . $system['id']);
}

/**
 * @param array $row      ctl_caves record values
 * @param array $findings findings produced by the default validator
 * @return array final list of findings
 */
function openkis_ValidateRecord_custom_caves($row, $findings)
{
    // The default rules cover: name, description, coordinates and reference
    // system, elevation, comune, provincia, speleometric data and total
    // depth consistency. Elevation, speleometry and description are
    // rewritten below with the Liguria rules.

    // Elevation (measured or map) is not a minimum requirement: notice only
    foreach (openkis_LiguriaValidatorTake($findings, FN_Translate("Quota")) as $finding)
    {
        $finding['severity'] = "notice";
        $findings[] = $finding;
    }
    // Speleometry: checked field by field below
    openkis_LiguriaValidatorTake($findings, FN_Translate("Dati speleometrici"));
    // Description: may be derogated for connected entrances
    $descriptionmissing = count(openkis_LiguriaValidatorTake($findings, FN_Translate("Descrizione"))) > 0;

    $destroyed = isset($row['destroyed']) && in_array(strtoupper(trim((string) $row['destroyed'])), array("Y", "P"));
    // "S" (si) is still found in older data next to "Y"
    $closed = isset($row['closed']) && in_array(strtoupper(trim((string) $row['closed'])), array("Y", "S"));
    $derogated = $destroyed || $closed;
    $derogationreason = $destroyed ? FN_Translate("cavita' distrutta") : FN_Translate("cavita' chiusa");
    // Severity of photo, itinerary and survey: notice for destroyed/closed caves
    $entranceseverity = $derogated ? "notice" : "missing";
    $entrancesuffix = $derogated ? " (" . $derogationreason . ")" : "";

    if (openkis_ValidatorFieldEmpty($row, "localita"))
    {
        openkis_ValidatorAdd($findings, "notice", "Localita'", FN_Translate("localita' non indicata"));
    }

    if (openkis_ValidatorFieldEmpty($row, "geologicalformation"))
    {
        openkis_ValidatorAdd($findings, "notice", FN_Translate("Formazione geologica"), FN_Translate("formazione geologica non indicata"));
    }

    // Coordinates outside the Liguria bounding box, with a small margin.
    // latitude/longitude are computed WGS84 values and may be stale when
    // the entered coordinates are blank, so the check is skipped then.
    if (!openkis_ValidatorFieldEmpty($row, "latitude_txt") && openkis_ValidatorHasNumber($row, "latitude") && openkis_ValidatorHasNumber($row, "longitude"))
    {
        $lat = (float) $row['latitude'];
        $lon = (float) $row['longitude'];
        if (($lat != 0 || $lon != 0) && ($lat < 43.75 || $lat > 44.70 || $lon < 7.45 || $lon > 10.10))
        {
            openkis_ValidatorAdd($findings, "notice", FN_Translate("Coordinate"), FN_Translate("le coordinate cadono fuori dalla Liguria") . " (" . round($lat, 5) . ", " . round($lon, 5) . "): " . FN_Translate("controllare valori e sistema di riferimento"));
        }
    }

    // INGRESSO: access itinerary
    if (openkis_ValidatorFieldEmpty($row, "itinerary"))
    {
        openkis_ValidatorAdd($findings, $entranceseverity, FN_Translate("Itinerario"), FN_Translate("manca l'itinerario di accesso") . $entrancesuffix);
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
        if ($descriptionmissing)
        {
            openkis_ValidatorAdd($findings, "missing", FN_Translate("Descrizione"), FN_Translate("manca la descrizione della cavita'"));
        }
        foreach (openkis_LiguriaValidatorSpeleometry($row, array("caves" => array(), "systems" => array())) as $finding)
        {
            $findings[] = $finding;
        }
        openkis_ValidatorAdd($findings, "notice", FN_Translate("Numero di catasto"), FN_Translate("numero di catasto non assegnato: controlli su rilievi e foto non eseguiti"));
        return $findings;
    }
    $code_escaped = str_replace("'", "''", $code);
    $connected = openkis_LiguriaValidatorConnected($row, $code);

    // GROTTA: description, possibly registered on a connected record
    if ($descriptionmissing)
    {
        $sources = array();
        foreach ($connected['caves'] as $linkedcode => $linkedrow)
        {
            if (!openkis_ValidatorFieldEmpty($linkedrow, "description"))
            {
                $sources[] = $linkedcode;
            }
        }
        foreach ($connected['systems'] as $system)
        {
            if (!openkis_ValidatorFieldEmpty($system, "description"))
            {
                $sources[] = openkis_LiguriaValidatorSystemName($system);
            }
        }
        if (count($sources))
        {
            openkis_ValidatorAdd($findings, "notice", FN_Translate("Descrizione"), FN_Translate("manca la descrizione di questo ingresso, presente in") . " " . implode(", ", $sources));
        }
        else
        {
            openkis_ValidatorAdd($findings, "missing", FN_Translate("Descrizione"), FN_Translate("manca la descrizione della cavita'"));
        }
    }

    // GROTTA: speleometric data, possibly registered on a connected record
    foreach (openkis_LiguriaValidatorSpeleometry($row, $connected) as $finding)
    {
        $findings[] = $finding;
    }

    // Records with the same name (case and spacing insensitive). Withdrawn
    // numbers all share the placeholder name "NUMERO CANCELLATO".
    $normname = function ($name)
    {
        $name = function_exists("mb_strtolower") ? mb_strtolower(trim((string) $name), "UTF-8") : strtolower(trim((string) $name));
        return preg_replace('/\s+/u', " ", $name);
    };
    $myname = isset($row['name']) ? $normname($row['name']) : "";
    if ($myname !== "" && $myname !== "numero cancellato")
    {
        $homonyms = array();
        $allnames = FN_XMETADBQuery("SELECT id,code,name FROM ctl_caves");
        if (is_array($allnames))
        {
            foreach ($allnames as $other)
            {
                if (isset($row['id'], $other['id']) && $other['id'] == $row['id'])
                {
                    continue;
                }
                if ($normname($other['name']) === $myname)
                {
                    $homonyms[] = trim((string) $other['code']) !== "" ? $other['code'] : "id " . $other['id'];
                }
            }
        }
        if (count($homonyms))
        {
            openkis_ValidatorAdd($findings, "notice", FN_Translate("Omonimia"), FN_Translate("altre schede con lo stesso nome") . ": " . implode(", ", $homonyms) . ". " . FN_Translate("Verificare che non si tratti di un doppione"));
        }
    }

    // INGRESSO: at least one photo (in ctl_photos or attached to the record).
    // Every entrance needs its own photo, also when connected to other caves.
    $photos = FN_XMETADBQuery("SELECT COUNT(*) AS count FROM ctl_photos WHERE codecave = '{$code_escaped}'");
    $photoscount = (is_array($photos) && isset($photos[0]['count'])) ? intval($photos[0]['count']) : 0;
    if ($photoscount == 0 && openkis_ValidatorFieldEmpty($row, "photo1"))
    {
        openkis_ValidatorAdd($findings, $entranceseverity, FN_Translate("Foto"), FN_Translate("nessuna foto dell'ingresso") . $entrancesuffix);
    }

    // GROTTA: at least one survey; uniformity checks on the most recent one
    $surveys = FN_XMETADBQuery("SELECT * FROM ctl_surveys WHERE codecave = '{$code_escaped}'");
    if (!is_array($surveys) || count($surveys) == 0)
    {
        // A connected cave or the cave system may hold the survey of the
        // whole cave
        $sources = array();
        foreach ($connected['caves'] as $linkedcode => $linkedrow)
        {
            $linkedsurveys = FN_XMETADBQuery("SELECT COUNT(*) AS count FROM ctl_surveys WHERE codecave = '" . str_replace("'", "''", $linkedcode) . "'");
            if (is_array($linkedsurveys) && isset($linkedsurveys[0]['count']) && intval($linkedsurveys[0]['count']) > 0)
            {
                $sources[] = $linkedcode;
            }
        }
        foreach ($connected['systems'] as $system)
        {
            if (!openkis_ValidatorFieldEmpty($system, "surveys"))
            {
                $sources[] = openkis_LiguriaValidatorSystemName($system);
            }
        }
        if (count($sources))
        {
            openkis_ValidatorAdd($findings, "notice", FN_Translate("Rilievo"), FN_Translate("nessun rilievo associato a questo ingresso, rilievo presente in") . " " . implode(", ", $sources));
        }
        else
        {
            openkis_ValidatorAdd($findings, $entranceseverity, FN_Translate("Rilievo"), FN_Translate("nessun rilievo associato alla cavita'") . $entrancesuffix);
        }
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

/**
 * Speleometric data: real development and positive, negative and total
 * depth are each required (0 is a valid value). For an entrance connected
 * to other caves a missing value is a notice when a connected cave holds
 * it, or, for development and total depth, when the cave system does.
 * The total depth consistency notice comes from the default rules.
 *
 * @param array $row       ctl_caves record
 * @param array $connected result of openkis_LiguriaValidatorConnected()
 * @return array findings
 */
function openkis_LiguriaValidatorSpeleometry($row, $connected)
{
    $findings = array();
    $speleochecks = array(
        "lenght_total" => FN_Translate("sviluppo reale"),
        "depth_positive" => FN_Translate("dislivello positivo"),
        "depth_negative" => FN_Translate("dislivello negativo"),
        "depth_total" => FN_Translate("dislivello totale"),
    );
    $missing = array();
    $elsewhere = array();
    foreach ($speleochecks as $speleofield => $speleolabel)
    {
        if (!array_key_exists($speleofield, $row) || openkis_ValidatorHasNumber($row, $speleofield))
        {
            continue;
        }
        $sources = array();
        foreach ($connected['caves'] as $linkedcode => $linkedrow)
        {
            if (openkis_ValidatorHasNumber($linkedrow, $speleofield))
            {
                $sources[] = $linkedcode;
            }
        }
        if ($speleofield === "lenght_total" || $speleofield === "depth_total")
        {
            foreach ($connected['systems'] as $system)
            {
                if (openkis_ValidatorHasNumber($system, $speleofield))
                {
                    $sources[] = openkis_LiguriaValidatorSystemName($system);
                }
            }
        }
        if (count($sources))
        {
            $elsewhere[] = $speleolabel . " (" . implode(", ", $sources) . ")";
        }
        else
        {
            $missing[] = $speleolabel;
        }
    }
    if (count($missing))
    {
        openkis_ValidatorAdd($findings, "missing", FN_Translate("Dati speleometrici"), FN_Translate("mancano") . ": " . implode(", ", $missing) . " (" . FN_Translate("0 e' un valore valido") . ")");
    }
    if (count($elsewhere))
    {
        openkis_ValidatorAdd($findings, "notice", FN_Translate("Dati speleometrici"), FN_Translate("non indicati per questo ingresso, presenti nelle schede collegate") . ": " . implode("; ", $elsewhere));
    }
    return $findings;
}
