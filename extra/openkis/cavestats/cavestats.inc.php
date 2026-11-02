<?php
/**
 * Summary of the natural caves in the register (ctl_caves only: artificial
 * cavities and other tables are not counted): caves per province, surveys,
 * photos and bibliography linked to a cave, the longest and the deepest caves,
 * the latest updated sheets. Same summary as the home of the Liguria register.
 *
 * Shown by a regional customisation, e.g. below the cave map
 * (extra/openkis/custom/<region>/footer_map_caves.php):
 *
 *   require_once "extra/openkis/cavestats/cavestats.inc.php";
 *   echo openkis_CaveStats(array(
 *       "region" => "Lombardia",                    // "caves in Lombardia"
 *       "provinces" => array("BG" => "Bergamo"),    // code => name, otherwise the code
 *   ));
 *
 * Rankings and latest updates list only the caves the user may see: with the
 * view permission of the caves section the ones whose sheet they can open
 * (per-record groupview, as dbview); without it the ones published on the map
 * (public_map, as openkis_SectionMapAccess()), without a link to the sheet.
 */

/**
 * Messages of the summary in the current language. They do not replace the
 * translations already loaded (the summary is printed inside other pages).
 */
function openkis_CaveStatsLoadMessages()
{
    global $_FN, $_FNMESSAGE;
    $file = __DIR__ . "/languages/{$_FN['lang']}/lang.csv";
    if (!file_exists($file))
        return;
    foreach (FN_GetMessagesFromCsv($file) as $k => $v)
    {
        if (!isset($_FNMESSAGE[$_FN['lang']][$k]))
            $_FNMESSAGE[$_FN['lang']][$k] = $v;
    }
}

/**
 * Format a measure in metres: thousands separator, decimals only when present.
 * @param float $value
 * @return string
 */
function openkis_CaveStatsFormatMetres($value)
{
    $decimals = (abs($value - round($value)) > 0.001) ? 1 : 0;
    return number_format($value, $decimals, ",", ".");
}

/**
 * Format a count with the thousands separator.
 * @param int $value
 * @return string
 */
function openkis_CaveStatsFormatCount($value)
{
    return number_format($value, 0, ",", ".");
}

/**
 * Parse a numeric field stored as text ("60.6", "-127", "1,5").
 * @param string $value
 * @return float|false
 */
function openkis_CaveStatsParseNumber($value)
{
    $value = str_replace(",", ".", trim((string) $value));
    if ($value === "" || !is_numeric($value))
    {
        return false;
    }
    return abs(floatval($value));
}

/**
 * Whether the current user may see a cave in the rankings.
 * @param array $cave
 * @param string $access "all" (view permission of the caves section) or "public"
 * @param array $config caves section config
 * @return bool
 */
function openkis_CaveStatsCanViewCave($cave, $access, $config)
{
    global $_FN;
    if ($access == "public")
    {
        return (isset($cave['public_map']) && $cave['public_map'] == "Y" && empty($cave['groupview']));
    }
    if (FN_IsAdmin())
    {
        return true;
    }
    if (empty($config['enable_permissions_each_records']) || empty($cave['groupview']))
    {
        return true;
    }
    if ($_FN['user'] == "")
    {
        return false;
    }
    $groups = $cave['groupview'];
    if (!empty($config['groupinsert']))
    {
        $groups .= "," . $config['groupinsert'];
    }
    if (!empty($config['groupadmin']))
    {
        $groups .= "," . $config['groupadmin'];
    }
    return FN_UserInGroup($_FN['user'], $groups);
}

/**
 * Ranking of the caves by a measure. Entrances of the same system (linked
 * through caveslinks) with the same value are shown once, with the number of
 * entrances.
 * @param array $caves visible caves
 * @param string $field measure field
 * @param array $groupof code => system key
 * @param array $linkedto code => number of caves linking to it
 * @param int $limit
 * @return array
 */
function openkis_CaveStatsRanking($caves, $field, $groupof, $linkedto, $limit)
{
    $measures = array();
    foreach ($caves as $k => $cave)
    {
        $value = isset($cave[$field]) ? openkis_CaveStatsParseNumber($cave[$field]) : false;
        if ($value === false || $value <= 0)
        {
            continue;
        }
        $measures[$k] = $value;
    }
    // candidates: the highest values down to the $limit-th distinct one, with all
    // the caves sharing it (entrances of a row have the same value); the full
    // comparison below runs on these only
    arsort($measures, SORT_NUMERIC);
    $list = array();
    $distinct = 0;
    $last = null;
    foreach ($measures as $k => $value)
    {
        if ($value !== $last)
        {
            if ($distinct >= $limit)
            {
                break;
            }
            $distinct++;
            $last = $value;
        }
        $cave = $caves[$k];
        $cave['measure'] = $value;
        $list[] = $cave;
    }
    // highest value first; among entrances with the same value prefer the one
    // describing the whole cave, then the one the others link to, then the lowest code
    usort($list, function ($a, $b) use ($linkedto) {
        if ($a['measure'] != $b['measure'])
        {
            return ($a['measure'] < $b['measure']) ? 1 : -1;
        }
        $sa = (isset($a['lenght_scope']) && $a['lenght_scope'] == "total") ? 1 : 0;
        $sb = (isset($b['lenght_scope']) && $b['lenght_scope'] == "total") ? 1 : 0;
        if ($sa != $sb)
        {
            return $sb - $sa;
        }
        $la = isset($linkedto[$a['code']]) ? $linkedto[$a['code']] : 0;
        $lb = isset($linkedto[$b['code']]) ? $linkedto[$b['code']] : 0;
        if ($la != $lb)
        {
            return $lb - $la;
        }
        return intval(preg_replace('/\D/', '', $a['code'])) - intval(preg_replace('/\D/', '', $b['code']));
    });
    $ranking = array();
    $seen = array();
    foreach ($list as $cave)
    {
        $key = $groupof[$cave['code']] . "|" . $cave['measure'];
        if (isset($seen[$key]))
        {
            $ranking[$seen[$key]]['entrances']++;
            continue;
        }
        if (count($ranking) >= $limit)
        {
            continue;
        }
        $cave['entrances'] = 1;
        $seen[$key] = count($ranking);
        $ranking[] = $cave;
    }
    return $ranking;
}

/**
 * Municipality name in title case, Italian particles in lower case
 * ("BORGHETTO DI VARA" -> "Borghetto di Vara", "VILLA D'ALME'" -> "Villa d'Alme'").
 * @param string $name
 * @return string
 */
function openkis_CaveStatsPlaceName($name)
{
    $name = mb_convert_case(mb_strtolower(trim((string) $name), "UTF-8"), MB_CASE_TITLE, "UTF-8");
    $name = preg_replace_callback("/'(\p{Ll})/u", function ($m) {
        return "'" . mb_strtoupper($m[1], "UTF-8");
    }, $name);
    return preg_replace_callback("/(?<=\s)(Di|Del|Della|Delle|Dei|Degli|De|Da|Dal|Dalla|In|Sul|Sulla|Sull|Al|Alla|E|Il|Con|D)(?=\s|')/u", function ($m) {
        return mb_strtolower($m[1], "UTF-8");
    }, $name);
}

/**
 * Name, code and place of a cave in a ranking row; the name links to the
 * sheet when the user can open it.
 * @param array $cave
 * @param bool $link
 * @return array html of the name with the code, place
 */
function openkis_CaveStatsCaveCells($cave, $link)
{
    $name = htmlspecialchars($cave['name']);
    if ($link)
    {
        $url = FN_RewriteLink("index.php?mod=caves&op=view&id={$cave['id']}");
        $name = "<a href=\"$url\">$name</a>";
    }
    $name .= " <span class=\"badge badge-dark openkis-sheet-code\">" . htmlspecialchars($cave['code']) . "</span>";
    $place = trim(openkis_CaveStatsPlaceName($cave['comune']) . ($cave['provincia'] != "" ? " ({$cave['provincia']})" : ""));
    return array($name, htmlspecialchars($place));
}

/**
 * Html of a ranking table.
 * @param string $title
 * @param string $label column label of the measure
 * @param array $ranking
 * @param bool $link
 * @return string
 */
function openkis_CaveStatsRankingHtml($title, $label, $ranking, $link)
{
    $html = "<div class=\"col-lg-6 mb-3\"><div class=\"card openkis-card h-100\">";
    $html .= "<div class=\"card-header\"><h5 class=\"mb-0\">" . htmlspecialchars($title) . "</h5></div>";
    $html .= "<div class=\"card-body p-0\"><table class=\"table table-sm table-hover mb-0 openkis-home-ranking\">";
    $html .= "<thead><tr><th class=\"text-right text-end\">#</th><th>" . FN_Translate("cave") . "</th><th class=\"text-right text-end\">" . htmlspecialchars($label) . "</th></tr></thead><tbody>";
    $pos = 0;
    foreach ($ranking as $cave)
    {
        $pos++;
        list($name, $place) = openkis_CaveStatsCaveCells($cave, $link);
        $html .= "<tr><td class=\"text-right text-end text-muted\">$pos</td><td>$name<br /><small class=\"text-muted\">$place";
        if ($cave['entrances'] > 1)
        {
            $html .= " &middot; " . $cave['entrances'] . " " . FN_Translate("entrances in the register", "");
        }
        $html .= "</small></td><td class=\"text-right text-end text-nowrap\"><strong>" . openkis_CaveStatsFormatMetres($cave['measure']) . " m</strong></td></tr>";
    }
    $html .= "</tbody></table></div></div></div>";
    return $html;
}

/**
 * Counters of the documents linked to the caves in the register: surveys,
 * caves with at least one survey, photos (archive and entrance photos),
 * bibliography entries citing at least one cave.
 * @param array $caves all the records of ctl_caves
 * @param array $inregister code => code of the caves in the register
 * @return array label => count
 */
function openkis_CaveStatsDocumentCounters($caves, $inregister)
{
    $surveys = 0;
    $caves_with_survey = array();
    $photos = 0;
    $bibliography = 0;
    $table = FN_XMDBTable("ctl_surveys");
    if ($table && isset($table->fields['codecave']))
    {
        $deleted = isset($table->fields['recorddeleted']);
        $rows = $table->GetRecords(false, false, false, false, false, $deleted ? "id|codecave|recorddeleted" : "id|codecave");
        if (is_array($rows))
        {
            foreach ($rows as $row)
            {
                $code = trim((string) $row['codecave']);
                if ($code != "" && isset($inregister[$code]) && ($deleted == false || empty($row['recorddeleted'])))
                {
                    $surveys++;
                    $caves_with_survey[$code] = true;
                }
            }
        }
    }
    $table = FN_XMDBTable("ctl_photos");
    if ($table && isset($table->fields['codecave']))
    {
        $rows = $table->GetRecords(false, false, false, false, false, "id|codecave");
        if (is_array($rows))
        {
            foreach ($rows as $row)
            {
                if (isset($inregister[trim((string) $row['codecave'])]))
                {
                    $photos++;
                }
            }
        }
    }
    foreach ($caves as $cave)
    {
        if (isset($inregister[$cave['code']]) && !empty($cave['photo1']))
        {
            $photos++;
        }
    }
    $table = FN_XMDBTable("ctl_bibliography");
    if ($table && isset($table->fields['codecaves']))
    {
        $rows = $table->GetRecords(false, false, false, false, false, "id|codecaves");
        if (is_array($rows))
        {
            foreach ($rows as $row)
            {
                foreach (explode(",", (string) $row['codecaves']) as $code)
                {
                    if (isset($inregister[trim($code)]))
                    {
                        $bibliography++;
                        break;
                    }
                }
            }
        }
    }
    return array(
        FN_Translate("surveys", "") => $surveys,
        FN_Translate("caves with at least one survey", "") => count($caves_with_survey),
        FN_Translate("photos", "") => $photos,
        FN_Translate("bibliography entries", "") => $bibliography,
    );
}

/**
 * Caves updated most recently.
 * @param array $caves visible caves
 * @param int $limit
 * @return array
 */
function openkis_CaveStatsLatestUpdates($caves, $limit)
{
    $dates = array();
    foreach ($caves as $k => $cave)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $cave['recordupdate']))
        {
            $dates[$k] = (string) $cave['recordupdate'];
        }
    }
    arsort($dates, SORT_STRING);
    $list = array();
    foreach (array_slice(array_keys($dates), 0, $limit) as $k)
    {
        $list[] = $caves[$k];
    }
    return $list;
}

/**
 * Html of the latest updated caves.
 * @param string $title
 * @param array $caves
 * @param bool $link
 * @return string
 */
function openkis_CaveStatsLatestHtml($title, $caves, $link)
{
    if (count($caves) == 0)
    {
        return "";
    }
    $html = "<div class=\"card openkis-card mb-3\">";
    $html .= "<div class=\"card-header\"><h5 class=\"mb-0\">" . htmlspecialchars($title) . "</h5></div>";
    $html .= "<div class=\"card-body p-0\"><table class=\"table table-sm table-hover mb-0 openkis-home-ranking\">";
    $html .= "<thead><tr><th>" . FN_Translate("cave") . "</th><th class=\"d-none d-sm-table-cell\">" . FN_Translate("municipality") . "</th><th class=\"text-right text-end\">" . FN_Translate("updated on") . "</th></tr></thead><tbody>";
    foreach ($caves as $cave)
    {
        list($name, $place) = openkis_CaveStatsCaveCells($cave, $link);
        $date = substr($cave['recordupdate'], 8, 2) . "/" . substr($cave['recordupdate'], 5, 2) . "/" . substr($cave['recordupdate'], 0, 4);
        $html .= "<tr><td>$name<small class=\"text-muted d-block d-sm-none\">$place</small></td>";
        $html .= "<td class=\"d-none d-sm-table-cell text-muted\">$place</td>";
        $html .= "<td class=\"text-right text-end text-nowrap\">$date</td></tr>";
    }
    $html .= "</tbody></table></div></div>";
    return $html;
}

/**
 * Html of a counter card.
 * @param int $count
 * @param string $label
 * @param string $class column classes
 * @param string $extra extra classes of the card
 * @return string
 */
function openkis_CaveStatsCounterHtml($count, $label, $class, $extra = "")
{
    $html = "<div class=\"$class mb-3\"><div class=\"card h-100 text-center openkis-home-counter $extra\"><div class=\"card-body py-3\">";
    $html .= "<div class=\"openkis-home-number\">" . openkis_CaveStatsFormatCount($count) . "</div><div class=\"text-muted small\">" . htmlspecialchars($label) . "</div></div></div></div>";
    return $html;
}

/**
 * Summary of the natural caves in the register.
 * @param array $options region: name of the region ("caves in <region>");
 *                       provinces: province code => name
 * @return string
 */
function openkis_CaveStats($options = array())
{
    global $_FN;
    openkis_CaveStatsLoadMessages();
    $region = isset($options['region']) ? $options['region'] : "";
    $province_names = isset($options['provinces']) ? $options['provinces'] : array();
    $table = FN_XMDBTable("ctl_caves");
    if (!$table)
    {
        return "";
    }
    $fields = array();
    foreach (array("id", "code", "name", "comune", "provincia", "groupview", "public_map", "status", "recorddeleted",
             "caveslinks", "lenght_total", "lenght_scope", "depth_total", "photo1", "recordupdate") as $field)
    {
        if (isset($table->fields[$field]))
        {
            $fields[] = $field;
        }
    }
    $caves = $table->GetRecords(false, false, false, false, false, implode("|", $fields));
    if (!is_array($caves) || count($caves) == 0)
    {
        return "";
    }
    $access = openkis_SectionMapAccess("caves");
    $config = FN_LoadConfig("modules/dbview/config.php", "caves");

    $total = 0;
    $byprovince = array();
    $visible = array();
    $parent = array();
    $linkedto = array();
    foreach ($caves as $k => $cave)
    {
        $cave += array("comune" => "", "provincia" => "", "caveslinks" => "", "photo1" => "", "recordupdate" => "");
        $caves[$k] = $cave;
        // only caves in the register: with a code, not deleted, not rejected
        if (trim((string) $cave['code']) == "" || !empty($cave['recorddeleted']) || (isset($cave['status']) && $cave['status'] == "rejected"))
        {
            continue;
        }
        $total++;
        $prov = strtoupper(trim((string) $cave['provincia']));
        if ($prov != "")
        {
            $byprovince[$prov] = isset($byprovince[$prov]) ? $byprovince[$prov] + 1 : 1;
        }
        $parent[$cave['code']] = $cave['code'];
        if ($access != "" && openkis_CaveStatsCanViewCave($cave, $access, $config))
        {
            $visible[] = $cave;
        }
    }
    // group the entrances of the same system through caveslinks (union-find on codes)
    $find = function ($code) use (&$parent) {
        while ($parent[$code] != $code)
        {
            $parent[$code] = $parent[$parent[$code]];
            $code = $parent[$code];
        }
        return $code;
    };
    foreach ($caves as $cave)
    {
        if (!isset($parent[$cave['code']]) || trim((string) $cave['caveslinks']) == "")
        {
            continue;
        }
        foreach (explode(",", $cave['caveslinks']) as $link)
        {
            $link = trim($link);
            if ($link == "" || $link == $cave['code'] || !isset($parent[$link]))
            {
                continue;
            }
            $linkedto[$link] = isset($linkedto[$link]) ? $linkedto[$link] + 1 : 1;
            $ra = $find($cave['code']);
            $rb = $find($link);
            if ($ra != $rb)
            {
                $parent[$ra] = $rb;
            }
        }
    }
    $groupof = array();
    foreach ($parent as $code => $p)
    {
        $groupof[$code] = $find($code);
    }

    $counters = openkis_CaveStatsDocumentCounters($caves, $parent);
    $latest = openkis_CaveStatsLatestUpdates($visible, 10);
    $longest = openkis_CaveStatsRanking($visible, "lenght_total", $groupof, $linkedto, 10);
    $deepest = openkis_CaveStatsRanking($visible, "depth_total", $groupof, $linkedto, 10);
    $link = ($access == "all");

    $html = "<div class=\"openkis-home-stats my-4\">";
    $html .= "<h4 class=\"mb-3\">" . FN_Translate("the natural caves in the register") . "</h4>";
    // with many provinces the total gets a row of its own
    ksort($byprovince);
    $many = count($byprovince) > 5;
    $html .= "<div class=\"row openkis-home-counters mb-2\">";
    $label = ($region != "") ? FN_Translate("caves in", "") . " $region" : FN_Translate("caves in the register", "");
    $html .= openkis_CaveStatsCounterHtml($total, $label, $many ? "col-12" : "col-12 col-md", "openkis-home-total");
    if ($many)
    {
        $html .= "</div><div class=\"row openkis-home-counters mb-2\">";
    }
    foreach ($byprovince as $prov => $count)
    {
        $name = isset($province_names[$prov]) ? $province_names[$prov] : $prov;
        $html .= openkis_CaveStatsCounterHtml($count, $name, $many ? "col-6 col-sm-4 col-md-3 col-lg" : "col-6 col-md");
    }
    $html .= "</div>";
    $html .= "<div class=\"row openkis-home-counters mb-2\">";
    foreach ($counters as $label => $count)
    {
        $html .= openkis_CaveStatsCounterHtml($count, $label, "col-6 col-md-3");
    }
    $html .= "</div>";
    if (count($longest) > 0 || count($deepest) > 0)
    {
        $html .= "<div class=\"row\">";
        $html .= openkis_CaveStatsRankingHtml(FN_Translate("the 10 longest caves"), FN_Translate("development"), $longest, $link);
        $html .= openkis_CaveStatsRankingHtml(FN_Translate("the 10 deepest caves"), FN_Translate("depth"), $deepest, $link);
        $html .= "</div>";
    }
    $html .= openkis_CaveStatsLatestHtml(FN_Translate("the latest updated caves"), $latest, $link);
    $html .= "<p class=\"text-muted small\">" . FN_Translate("Natural caves only: surveys, photos and bibliography entries are the ones linked to a cave in the register, and the photos include the entrance photos. Entrances of the same system with the same values are shown once.", "");
    if ($access == "public")
    {
        $html .= " " . FN_Translate("Rankings and latest updates show only the caves published on the map.", "");
    }
    elseif ($_FN['user'] == "")
    {
        $html .= " " . FN_Translate("Rankings and latest updates show only the caves that can be viewed without logging in.", "");
    }
    $html .= "</p></div>";
    return $html;
}
