<?php

/**
 * Section: Grottocenter
 *
 * Links between the caves and the entrances of Grottocenter
 * (https://grottocenter.org), and the Karstlink export (openkis_karstlink.php).
 * The search (openkis_GrottocenterMatch()) reads the public Grottocenter API
 * and proposes the most likely entrance for each cave; the reviewer confirms
 * the proposals one by one or all those above a score, picks another
 * candidate, enters an entrance id by hand, or marks the cave as not on
 * Grottocenter. The confirmed links are shown on the cave sheet and exported
 * as owl:sameAs.
 *
 * Reserved to the administrators of the caves section (groupadmin) and of the
 * site; the settings, which change the public export, to the site
 * administrators.
 *
 * @package OpenKIS
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
okgc_LoadMessages(__DIR__);
require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";

$okgc_mod = "caves";
$okgc_link = "index.php?mod=" . urlencode($_FN['mod']);
if ($_FN['user'] == "")
{
    echo "<p>" . FN_Translate("log in to manage the links with Grottocenter") . "</p>";
    return;
}
if (!openkis_GrottocenterIsCaveSection($okgc_mod) || !okgc_CanManage($okgc_mod))
{
    echo "<p>" . FN_Translate("reserved to the administrators of the caves section") . "</p>";
    return;
}
if (FN_GetParam("op", $_GET) == "settings" && FN_IsAdmin())
{
    echo "<p><a href=\"$okgc_link\">&larr; Grottocenter</a></p>";
    echo FN_HtmlEditConfFile("sections/grottocenter/config.php", "$okgc_link&amp;op=settings", $okgc_link);
    return;
}

$okgc_table = FN_XMDBTable("ctl_grottocenter");
$okgc_settings = openkis_GrottocenterConfig();
$okgc_status = FN_GetParam("status", $_GET);
$okgc_q = trim(FN_GetParam("q", $_GET));
$okgc_page = max(1, intval(FN_GetParam("page", $_GET)));
$okgc_message = "";
$okgc_errors = array();

//--------------------------- actions (POST only) ---------------------------->
$okgc_action = FN_GetParam("okgc_action", $_POST);
if ($okgc_action != "")
{
    $record_id = trim(FN_GetParam("record_id", $_POST));
    if (!hash_equals(okgc_Token(), (string) FN_GetParam("okgc_token", $_POST)))
    {
        $okgc_errors[] = FN_Translate("invalid request, reload the page and try again");
    }
    elseif ($okgc_action == "match" || $okgc_action == "reload")
    {
        if ($okgc_action == "reload")
            openkis_GrottocenterClearCache();
        $stats = openkis_GrottocenterMatch($okgc_mod, $okgc_errors);
        $okgc_message = ucfirst(okgc_StatsText($stats));
        FN_LogEvent("Grottocenter: search of the links by {$_FN['user']}, $okgc_message", "openkis");
        if ($okgc_status == "")
            $okgc_status = "proposed";
    }
    elseif ($okgc_action == "confirm_all")
    {
        $min = floatval($okgc_settings['auto_confirm_score']);
        $n = 0;
        foreach (openkis_GrottocenterLinks($okgc_mod, "proposed") as $row)
        {
            if ($row['gc_entrance'] !== "" && floatval($row['score']) >= $min)
            {
                $okgc_table->UpdateRecord(array("id" => $row['id'], "status" => "confirmed", "username" => $_FN['user'], "recordupdate" => FN_Now()));
                $n++;
            }
        }
        $okgc_message = FN_Translate("links confirmed") . ": $n";
        FN_LogEvent("Grottocenter: $n links confirmed together (score >= $min) by {$_FN['user']}", "openkis");
    }
    elseif (!preg_match('/^[0-9]+$/', $record_id))
    {
        $okgc_errors[] = FN_Translate("error");
    }
    elseif ($okgc_action == "confirm")
    {
        $gc_id = trim(FN_GetParam("gc_manual", $_POST));
        if ($gc_id === "")
            $gc_id = trim(FN_GetParam("gc_choice", $_POST));
        if (!preg_match('/^[0-9]+$/', $gc_id))
        {
            $okgc_errors[] = FN_Translate("choose a candidate or enter the id of a Grottocenter entrance");
        }
        elseif (okgc_Confirm($okgc_mod, $record_id, $gc_id, $error, $distance))
        {
            $okgc_message = FN_Translate("link confirmed");
            if ($distance !== "" && floatval($distance) > floatval($okgc_settings['max_distance']))
                $okgc_errors[] = FN_Translate("the Grottocenter entrance is far from the cave, check the link") . ": $distance m";
        }
        else
        {
            $okgc_errors[] = $error;
        }
    }
    elseif ($okgc_action == "missing")
    {
        okgc_Save($okgc_mod, $record_id, array("status" => "missing", "gc_entrance" => "", "gc_cave" => "", "gc_name" => "", "distance" => "", "name_score" => "", "score" => ""));
        $okgc_message = FN_Translate("marked as not on Grottocenter");
    }
    elseif ($okgc_action == "reset")
    {
        $link = openkis_GrottocenterLink($okgc_mod, $record_id);
        if ($link)
            $okgc_table->DelRecord($link['id']);
        $okgc_message = FN_Translate("link removed: the next search will propose it again");
    }
}
//--------------------------- actions (POST only) ----------------------------<

//--------------------------- data ------------------------------------------->
$okgc_caves = array();
$okgc_withcoords = 0;
$caves_table = FN_XMDBTable("ctl_caves");
$deleted = isset($caves_table->fields['recorddeleted']) ? ",recorddeleted" : "";
$rows = FN_XMETADBQuery("SELECT id,code,name,latitude,longitude$deleted FROM ctl_caves");
foreach (is_array($rows) ? $rows : array() as $row)
{
    if (!empty($row['recorddeleted']))
        continue;
    $row['hascoords'] = openkis_GrottocenterValidCoords($row['latitude'], $row['longitude']);
    if ($row['hascoords'])
        $okgc_withcoords++;
    $okgc_caves[strval($row['id'])] = $row;
}
$okgc_links = array();
$okgc_counts = array("proposed" => 0, "nomatch" => 0, "confirmed" => 0, "missing" => 0);
foreach (openkis_GrottocenterLinks($okgc_mod) as $row)
{
    if (!isset($okgc_caves[$row['record_id']]) || !isset($okgc_counts[$row['status']]))
        continue;
    $okgc_links[$row['record_id']] = $row;
    $okgc_counts[$row['status']]++;
}
$okgc_unchecked = 0;
foreach ($okgc_caves as $id => $cave)
{
    if ($cave['hascoords'] && !isset($okgc_links[$id]))
        $okgc_unchecked++;
}
if (!isset($okgc_counts[$okgc_status]))
    $okgc_status = $okgc_counts['proposed'] > 0 ? "proposed" : "confirmed";
//--------------------------- data -------------------------------------------<

echo "<div class=\"okgc\">";
if ($okgc_message != "")
    echo "<div class=\"alert alert-success\">" . okgc_h($okgc_message) . "</div>";
foreach ($okgc_errors as $error)
    echo "<div class=\"alert alert-danger\">" . okgc_h($error) . "</div>";

//--------------------------- intro and export ------------------------------->
echo "<p>" . FN_Translate("Grottocenter is the collaborative database of the caves of the world. Here the caves of the register are linked to the same caves on Grottocenter: the search proposes for each cave the nearest Grottocenter entrance with a similar name, you confirm the right ones. The confirmed links appear on the cave sheet. Nothing is written on Grottocenter.") . "</p>";
echo "<h3>" . FN_Translate("Karstlink export") . "</h3>";
echo "<p>" . FN_Translate("The caves visible to the public, with coordinates, in the Karstlink format of the UIS that Grottocenter and the other cave databases can import") . ":</p>";
$csv = "{$_FN['siteurl']}openkis_karstlink.php?mod=$okgc_mod";
echo "<div class=\"input-group mb-2\" style=\"max-width:50em\"><span class=\"input-group-text\">CSV</span><input class=\"form-control\" readonly=\"readonly\" onclick=\"this.select()\" value=\"" . okgc_h($csv) . "\" /><a class=\"btn btn-outline-secondary\" href=\"" . okgc_h($csv) . "\">" . FN_Translate("download") . "</a></div>";
echo "<div class=\"input-group mb-2\" style=\"max-width:50em\"><span class=\"input-group-text\">JSON-LD</span><input class=\"form-control\" readonly=\"readonly\" onclick=\"this.select()\" value=\"" . okgc_h("$csv&format=jsonld") . "\" /><a class=\"btn btn-outline-secondary\" target=\"_blank\" href=\"" . okgc_h("$csv&format=jsonld") . "\">" . FN_Translate("open") . "</a></div>";
echo "<ul class=\"small\">";
echo "<li>" . FN_Translate("source") . ": <b>" . okgc_h($okgc_settings['source_name']) . "</b></li>";
echo "<li>" . FN_Translate("license") . ": " . ($okgc_settings['license'] !== "" ? "<b>" . okgc_h($okgc_settings['license']) . "</b>" : "<span class=\"text-danger\">" . FN_Translate("not set: set it before giving the export to Grottocenter") . "</span>") . "</li>";
echo "<li>" . FN_Translate("description and itinerary") . ": " . (!empty($okgc_settings['export_texts']) ? FN_Translate("exported") : FN_Translate("not exported")) . "</li>";
echo "</ul>";
if (FN_IsAdmin())
    echo "<p><a class=\"btn btn-sm btn-outline-secondary\" href=\"$okgc_link&amp;op=settings\">" . FN_Translate("settings") . "</a></p>";
//--------------------------- intro and export -------------------------------<

//--------------------------- search ----------------------------------------->
$token = okgc_Token();
echo "<h3>" . FN_Translate("Links with Grottocenter") . "</h3>";
$last = openkis_GrottocenterLastMatch($okgc_mod);
if ($last)
{
    echo "<p class=\"small text-muted\">" . FN_Translate("last search") . ": " . okgc_h($last['stats']['time']) . " (" . okgc_h($last['stats']['user']) . "), " . okgc_h(okgc_StatsText($last['stats'])) . "</p>";
    foreach ((array) $last['errors'] as $error)
        echo "<p class=\"small text-danger\">" . okgc_h($error) . "</p>";
}
echo "<form method=\"post\" action=\"$okgc_link\" class=\"mb-3\">";
echo "<input type=\"hidden\" name=\"okgc_token\" value=\"$token\" />";
echo "<button class=\"btn btn-primary btn-sm m-1\" type=\"submit\" name=\"okgc_action\" value=\"match\">" . FN_Translate("search the links") . "</button>";
echo "<button class=\"btn btn-outline-primary btn-sm m-1\" type=\"submit\" name=\"okgc_action\" value=\"reload\" title=\"" . okgc_h(FN_Translate("the Grottocenter data are kept for some hours: download them again before the search")) . "\">" . FN_Translate("download the Grottocenter data again and search") . "</button>";
$min = floatval($okgc_settings['auto_confirm_score']);
$n = 0;
foreach ($okgc_links as $link)
{
    if ($link['status'] == "proposed" && floatval($link['score']) >= $min)
        $n++;
}
if ($n > 0)
{
    $label = FN_Translate("confirm the proposals with a score of at least") . " $min% ($n)";
    echo "<button class=\"btn btn-success btn-sm m-1\" type=\"submit\" name=\"okgc_action\" value=\"confirm_all\" onclick=\"return confirm('" . addslashes(okgc_h($label)) . "?')\">" . okgc_h($label) . "</button>";
}
echo "</form>";
echo "<p class=\"small text-muted\">" . FN_Translate("The search reads the Grottocenter entrances within") . " " . okgc_h($okgc_settings['max_distance']) . " m " . FN_Translate("of each cave and scores them on the distance and on the names; it can take some minutes. The links confirmed or marked as not on Grottocenter are kept.", "") . "</p>";
//--------------------------- search -----------------------------------------<

//--------------------------- list ------------------------------------------->
$labels = array("proposed" => FN_Translate("to be checked"), "nomatch" => FN_Translate("no match"), "confirmed" => FN_Translate("confirmed"), "missing" => FN_Translate("not on Grottocenter"));
echo "<ul class=\"nav nav-tabs mb-2\">";
foreach ($labels as $status => $label)
{
    $active = $status == $okgc_status ? " active" : "";
    echo "<li class=\"nav-item\"><a class=\"nav-link$active\" href=\"$okgc_link&amp;status=$status\">" . okgc_h($label) . " <span class=\"badge bg-secondary\">{$okgc_counts[$status]}</span></a></li>";
}
echo "</ul>";
echo "<p class=\"small text-muted\">" . FN_Translate("caves with coordinates") . ": $okgc_withcoords, " . FN_Translate("not yet searched", "") . ": $okgc_unchecked</p>";
echo "<form method=\"get\" action=\"index.php\" class=\"mb-2\"><input type=\"hidden\" name=\"mod\" value=\"" . okgc_h($_FN['mod']) . "\" /><input type=\"hidden\" name=\"status\" value=\"" . okgc_h($okgc_status) . "\" />";
echo "<div class=\"input-group input-group-sm\" style=\"max-width:25em\"><input type=\"text\" class=\"form-control\" name=\"q\" value=\"" . okgc_h($okgc_q) . "\" placeholder=\"" . okgc_h(FN_Translate("code or name")) . "\" /><button class=\"btn btn-outline-secondary\" type=\"submit\">" . FN_Translate("search") . "</button></div></form>";

$list = array();
foreach ($okgc_links as $id => $link)
{
    if ($link['status'] != $okgc_status)
        continue;
    $cave = $okgc_caves[$id];
    if ($okgc_q != "" && stripos("{$cave['code']} {$cave['name']} {$link['gc_name']}", $okgc_q) === false)
        continue;
    $list[] = $link;
}
usort($list, function ($a, $b) use ($okgc_status, $okgc_caves) {
    if ($okgc_status == "proposed" && $a['score'] != $b['score'])
        return floatval($b['score']) < floatval($a['score']) ? -1 : 1;
    if ($okgc_status == "confirmed" || $okgc_status == "missing")
        return strcmp($b['recordupdate'], $a['recordupdate']);
    return strnatcasecmp((string) $okgc_caves[$a['record_id']]['code'], (string) $okgc_caves[$b['record_id']]['code']);
});
$perpage = 50;
$pages = max(1, ceil(count($list) / $perpage));
$okgc_page = min($okgc_page, $pages);
$pagelink = "$okgc_link&amp;status=" . urlencode($okgc_status) . ($okgc_q != "" ? "&amp;q=" . urlencode($okgc_q) : "");
if (count($list) == 0)
{
    echo "<p>" . FN_Translate("no caves") . "</p>";
}
else
{
    echo "<table class=\"table table-sm table-striped align-middle\"><thead><tr><th>" . FN_Translate("cave") . "</th><th>Grottocenter</th><th></th></tr></thead><tbody>";
    foreach (array_slice($list, ($okgc_page - 1) * $perpage, $perpage) as $link)
        okgc_HtmlRow($okgc_mod, $okgc_caves[$link['record_id']], $link, "$pagelink&amp;page=$okgc_page", $token);
    echo "</tbody></table>";
}
if ($pages > 1)
{
    echo "<nav><ul class=\"pagination pagination-sm flex-wrap\">";
    for ($p = 1; $p <= $pages; $p++)
        echo "<li class=\"page-item" . ($p == $okgc_page ? " active" : "") . "\"><a class=\"page-link\" href=\"$pagelink&amp;page=$p\">$p</a></li>";
    echo "</ul></nav>";
}
//--------------------------- list -------------------------------------------<
echo "</div>";

/**
 * Messages of the section in the current language. Not FN_LoadMessagesFolder():
 * for a folder outside finis it copies the file into the site root under its
 * absolute path (home/<user>/.../languages/it/lang.csv, FN_FromTheme()).
 * @param string $folder
 */
function okgc_LoadMessages($folder)
{
    global $_FN, $_FNMESSAGE;
    $file = "$folder/languages/{$_FN['lang']}/lang.csv";
    if (!file_exists($file))
        return;
    foreach (FN_GetMessagesFromCsv($file) as $k => $v)
        $_FNMESSAGE[$_FN['lang']][$k] = $v;
}

/**
 * The administrators of the caves section and of the site
 * @param string $mod
 * @return bool
 */
function okgc_CanManage($mod)
{
    $dbview = new FNDBVIEW(FN_LoadConfig("modules/dbview/config.php", $mod));
    return $dbview->IsAdmin();
}

/**
 * Anti CSRF token, bound to the session (same secret as the control center)
 * @return string
 */
function okgc_Token()
{
    $secret = FN_GetUserSessionValue('fn_csrf_secret');
    if (empty($secret))
    {
        $secret = bin2hex(random_bytes(16));
        FN_SetUserSessionValue('fn_csrf_secret', $secret);
    }
    return hash_hmac('sha256', "grottocenter", $secret);
}

/**
 * Writes the link of a record, creating it when missing
 * @param string $mod
 * @param string $record_id
 * @param array $values
 */
function okgc_Save($mod, $record_id, $values)
{
    global $_FN;
    $table = FN_XMDBTable("ctl_grottocenter");
    $values['username'] = $_FN['user'];
    $values['recordupdate'] = FN_Now();
    $link = openkis_GrottocenterLink($mod, $record_id);
    if ($link)
    {
        $values['id'] = $link['id'];
        $table->UpdateRecord($values);
    }
    else
    {
        $values['section'] = $mod;
        $values['record_id'] = $record_id;
        $table->InsertRecord($values);
    }
}

/**
 * Confirms the link of a record to a Grottocenter entrance: one of the
 * candidates of the last search, or any entrance read from the API.
 *
 * @param string $mod
 * @param string $record_id
 * @param string $gc_id
 * @param string $error set on failure
 * @param string $distance set to the distance in metres between the cave and the entrance, "" when unknown
 * @return bool
 */
function okgc_Confirm($mod, $record_id, $gc_id, &$error, &$distance)
{
    $error = "";
    $distance = "";
    $link = openkis_GrottocenterLink($mod, $record_id);
    $candidate = null;
    $candidates = $link ? json_decode($link['candidates'], true) : null;
    foreach (is_array($candidates) ? $candidates : array() as $c)
    {
        if (strval($c['id']) === $gc_id)
            $candidate = $c;
    }
    if ($candidate === null)
    {
        $entrance = openkis_GrottocenterEntrance($gc_id, $error);
        if (!$entrance)
        {
            $error = FN_Translate("Grottocenter entrance not found") . " ($gc_id)" . ($error != "" ? ": $error" : "");
            return false;
        }
        $candidate = array("id" => $entrance['id'], "cave" => $entrance['cave'], "name" => $entrance['name'], "distance" => "", "name_score" => "", "score" => "");
        $cave = FN_XMETADBQuery("SELECT latitude,longitude FROM ctl_caves WHERE id LIKE '" . intval($record_id) . "'");
        if (is_array($cave) && count($cave) && $entrance['latitude'] !== null && floatval($cave[0]['latitude']) != 0)
        {
            $candidate['distance'] = intval(round(openkis_GrottocenterDistance(floatval($cave[0]['latitude']), floatval($cave[0]['longitude']), $entrance['latitude'], $entrance['longitude'])));
        }
    }
    okgc_Save($mod, $record_id, array("status" => "confirmed", "gc_entrance" => strval($candidate['id']), "gc_cave" => strval($candidate['cave']),
        "gc_name" => (string) $candidate['name'], "distance" => strval($candidate['distance']), "name_score" => strval($candidate['name_score']),
        "score" => strval($candidate['score'])));
    $distance = strval($candidate['distance']);
    return true;
}

/**
 * A row of the list: the cave, the Grottocenter entrance (or the
 * candidates) and the actions
 *
 * @param string $mod
 * @param array $cave id, code, name
 * @param array $link record of ctl_grottocenter
 * @param string $action url of the forms
 * @param string $token
 */
function okgc_HtmlRow($mod, $cave, $link, $action, $token)
{
    global $_FN;
    $sheet = "{$_FN['siteurl']}index.php?mod=" . urlencode($mod) . "&amp;op=view&amp;id=" . urlencode($cave['id']);
    echo "<tr><td><a href=\"$sheet\" target=\"_blank\"><b>" . okgc_h($cave['code']) . "</b></a><br />" . okgc_h($cave['name']) . "</td><td>";
    if ($link['gc_entrance'] !== "")
    {
        echo "<a href=\"" . okgc_h(openkis_GrottocenterEntranceUrl($link['gc_entrance'])) . "\" target=\"_blank\" rel=\"noopener\">" . okgc_h($link['gc_name'] !== "" ? $link['gc_name'] : "#" . $link['gc_entrance']) . "</a>";
        echo " <small class=\"text-muted\">#" . okgc_h($link['gc_entrance']) . "</small><br />";
        echo "<small>" . okgc_Measures($link) . "</small>";
    }
    $candidates = json_decode($link['candidates'], true);
    $candidates = is_array($candidates) ? $candidates : array();
    $others = array();
    foreach ($candidates as $c)
    {
        if (strval($c['id']) !== $link['gc_entrance'])
            $others[] = "<a href=\"" . okgc_h(openkis_GrottocenterEntranceUrl($c['id'])) . "\" target=\"_blank\" rel=\"noopener\">" . okgc_h($c['name'] !== "" ? $c['name'] : "#{$c['id']}") . "</a> <small class=\"text-muted\">(" . okgc_Measures($c) . ")</small>";
    }
    if (count($others) && ($link['status'] == "proposed" || $link['status'] == "nomatch"))
        echo "<div class=\"small mt-1\">" . FN_Translate("other candidates") . ": " . implode("; ", $others) . "</div>";
    echo "</td><td style=\"min-width:18em\">";
    echo "<form method=\"post\" action=\"$action\" class=\"d-flex flex-wrap gap-1 align-items-center\">";
    echo "<input type=\"hidden\" name=\"okgc_token\" value=\"$token\" /><input type=\"hidden\" name=\"record_id\" value=\"" . okgc_h($cave['id']) . "\" />";
    if ($link['status'] == "proposed" || $link['status'] == "nomatch")
    {
        if (count($candidates))
        {
            echo "<select name=\"gc_choice\" class=\"form-select form-select-sm\" style=\"width:auto;max-width:16em\">";
            foreach ($candidates as $c)
            {
                $selected = strval($c['id']) === $link['gc_entrance'] ? " selected=\"selected\"" : "";
                echo "<option value=\"" . okgc_h($c['id']) . "\"$selected>" . okgc_h(($c['name'] !== "" ? $c['name'] : "#{$c['id']}") . " - {$c['distance']} m, {$c['score']}%") . "</option>";
            }
            echo "</select>";
        }
        echo "<input type=\"text\" name=\"gc_manual\" class=\"form-control form-control-sm\" style=\"width:6em\" placeholder=\"id\" title=\"" . okgc_h(FN_Translate("id of another Grottocenter entrance")) . "\" />";
        echo "<button class=\"btn btn-success btn-sm\" type=\"submit\" name=\"okgc_action\" value=\"confirm\">" . FN_Translate("confirm") . "</button>";
        echo "<button class=\"btn btn-outline-secondary btn-sm\" type=\"submit\" name=\"okgc_action\" value=\"missing\">" . FN_Translate("not on Grottocenter") . "</button>";
    }
    else
    {
        echo "<small class=\"text-muted\">" . okgc_h($link['username']) . " " . okgc_h(substr($link['recordupdate'], 0, 10)) . "</small> ";
        echo "<button class=\"btn btn-outline-danger btn-sm\" type=\"submit\" name=\"okgc_action\" value=\"reset\">" . FN_Translate("undo") . "</button>";
    }
    echo "</form></td></tr>";
}

/**
 * @param array $c a candidate or a link: distance, name_score, score
 * @return string html
 */
function okgc_Measures($c)
{
    $parts = array();
    if ((string) $c['distance'] !== "")
        $parts[] = okgc_h($c['distance']) . " m";
    if ((string) $c['name_score'] !== "")
        $parts[] = FN_Translate("name", "") . " " . okgc_h($c['name_score']) . "%";
    if ((string) $c['score'] !== "")
    {
        $score = intval($c['score']);
        $class = $score >= 90 ? "text-success" : ($score >= 60 ? "text-warning" : "text-danger");
        $parts[] = "<span class=\"$class\">" . FN_Translate("score", "") . " $score%</span>";
    }
    return implode(", ", $parts);
}

/**
 * @param array $stats from openkis_GrottocenterMatch()
 * @return string
 */
function okgc_StatsText($stats)
{
    return FN_Translate("caves searched", "") . " {$stats['checked']}, " . FN_Translate("to be checked", "") . " {$stats['proposed']}, "
        . FN_Translate("no match", "") . " {$stats['nomatch']}, " . FN_Translate("already decided", "") . " {$stats['kept']}"
        . ($stats['skipped'] ? ", " . FN_Translate("not searched (Grottocenter not reachable)", "") . " {$stats['skipped']}" : "")
        . ", " . FN_Translate("Grottocenter entrances read", "") . " {$stats['entrances']}";
}

/**
 * HTML escape that accepts null values
 * @param mixed $s
 * @return string
 */
function okgc_h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES);
}
