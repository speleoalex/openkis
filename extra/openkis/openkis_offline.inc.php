<?php
/**
 * Offline use of the site installed as an application (docs/piano_offline_webapp_2026-11-03.md).
 *
 * The service worker openkis_sw.js keeps the pages an anonymous visitor gets (finis marks
 * them with "X-Finis-Visitor: public") and shows them again without network; the page
 * "offline" (sections/offline) downloads the sheets of whole sections as fragments
 * (fnajax=1) that the worker puts back in the theme shell of their section (fnshell=1).
 * openkis_offline.php gives the worker and that page what they need: versions, sections,
 * record indexes.
 *
 * $_FN['openkis_offline'] = 0 in the configuration turns it off: the pages then remove
 * the worker and its caches from the browsers that installed it.
 * $_FN['openkis_offline_concurrency'] = parallel requests of a download (default 2: the
 * shared hosting has few database connections).
 * $_FN['openkis_offline_version'] = any text, changed to force the browsers to fetch the
 * theme shells and the static files again.
 */

/**
 * @return bool
 */
function openkis_OfflineEnabled()
{
    global $_FN;
    return !isset($_FN['openkis_offline']) || !empty($_FN['openkis_offline']);
}

/**
 * With $_FN['openkis_offline_menu_users_only'] (set by a region in
 * extra/openkis/custom/<region>/<region>.php) the menu entry of the page "Offline use"
 * (sections/offline) is only for the logged users. Run by include/autoexec.d, after
 * FN_InitSections() has reloaded the sections and before the menu is drawn. The page itself
 * stays public on purpose: the application keeps the copy of it that an anonymous visitor
 * gets (the offline search, also shown for the pages that were not kept), so restricting
 * the section would keep "you don't have permission" there, for the logged users too.
 */
function openkis_OfflineMenuOnlyForUsers()
{
    global $_FN;
    if (empty($_FN['openkis_offline_menu_users_only']) || $_FN['user'] != "")
        return;
    // FN_InitSections() leaves the list empty, FN_GetSections() would read it again anyway
    if (empty($_FN['sections']))
        $_FN['sections'] = FN_GetAllSections();
    if (isset($_FN['sections']['offline']))
        $_FN['sections']['offline']['hidden'] = 1;
}

/**
 * Hash of the sizes and times of a list of files (globs), for the versions below.
 *
 * @param array $patterns glob patterns relative to the site folder
 * @return string
 */
function openkis_OfflineFilesHash($patterns)
{
    $parts = array();
    foreach ($patterns as $pattern)
    {
        $files = glob($pattern);
        if (!is_array($files))
            continue;
        sort($files);
        foreach ($files as $file)
        {
            if (is_file($file))
                $parts[] = $file . ":" . filesize($file) . ":" . filemtime($file);
        }
    }
    return md5(implode("|", $parts));
}

/**
 * Last change of some tables (the "updated" markers of XMETATable), 0 for the missing ones.
 *
 * @param array $tables
 * @return string
 */
function openkis_OfflineTablesStamp($tables)
{
    global $_FN;
    // the files read by XMETATable::GetLastUpdateTime(), without opening the tables (no
    // database query on every page)
    $stamp = array();
    foreach ($tables as $tablename)
    {
        $base = "{$_FN['datadir']}/{$_FN['database']}/$tablename";
        if (file_exists("$base/updated"))
            $stamp[] = filectime("$base/updated");
        elseif (file_exists("$base.php"))
            $stamp[] = filectime("$base.php");
        else
            $stamp[] = 0;
    }
    return implode(",", $stamp);
}

/**
 * Version of what the worker keeps for every page: the theme shells (menu, settings,
 * template) and the static files of the theme, of the map and of the offline scripts.
 * A new value makes the browsers install the worker again, which fetches them again.
 *
 * @return string 12 hex characters
 */
function openkis_OfflineVersion()
{
    global $_FN;
    static $version = null;
    if ($version !== null)
        return $version;
    $theme = basename($_FN['theme']);
    $files = openkis_OfflineFilesHash(array(
        "openkis_sw.js",
        "openkis_offline.js",
        "openkis_offline.php",
        "extra/openkis/openkis_offline.inc.php",
        "bs_map.htm",
        "themes/$theme/*.tp.html",
        "themes/$theme/*.css",
        "themes/$theme/*.js",
        "themes/$theme/config.php",
        "themes/$theme/manifest.json",
    ));
    $tables = openkis_OfflineTablesStamp(array("fn_sections", "fn_settings", "themes_s_$theme"));
    $manual = isset($_FN['openkis_offline_version']) ? strval($_FN['openkis_offline_version']) : "";
    $version = substr(md5("$files|$tables|$theme|$manual|{$_FN['siteurl']}"), 0, 12);
    return $version;
}

/**
 * Version of the code that renders the sheets and the lists: the downloaded sheets made by
 * another version are downloaded again at the next update.
 *
 * @return string 12 hex characters
 */
function openkis_OfflinePagesVersion()
{
    global $_FN;
    static $version = null;
    if ($version !== null)
        return $version;
    $theme = basename($_FN['theme']);
    $files = openkis_OfflineFilesHash(array(
        "extra/openkis/openkis_sheet.inc.php",
        "extra/openkis/openkis_listview.inc.php",
        "extra/openkis/templates/*.tp.html",
        "sections/*/view*.php",
        "sections/*/*.tp.html",
        "modules/dbview/*.tp.html",
        "themes/$theme/modules/dbview/*",
        "{$_FN['src_finis']}/modules/dbview/*.php",
        "{$_FN['src_finis']}/modules/dbview/*.tp.html",
        "{$_FN['src_finis']}/modules/dbview/traits/*.php",
    ));
    $custom = !empty($_FN['openkis_custom']) ? openkis_OfflineFilesHash(array("extra/openkis/custom/" . basename($_FN['openkis_custom']) . "/*.php")) : "";
    $version = substr(md5("$files|$custom|$theme|{$_FN['siteurl']}"), 0, 12);
    return $version;
}

/**
 * Absolute address of a page of the site, written as the links of the pages write it.
 *
 * @param string $href e.g. "index.php?mod=caves&op=view&id=88"
 * @return string
 */
function openkis_OfflineLink($href)
{
    return FN_RewriteLink($href, "&", true);
}

/**
 * Sections whose sheets can be downloaded: the record sections (dbview) the current
 * visitor may read, enabled, with their table.
 *
 * @return array mod => array(mod, title, table)
 */
function openkis_OfflineSections()
{
    $ret = array();
    $sections = FN_GetSections("", true, true, true, true);
    if (!is_array($sections))
        return $ret;
    foreach ($sections as $section)
    {
        if (empty($section['id']) || !openkis_IsRecordSection($section) || !FN_UserCanViewSection($section['id']))
            continue;
        if (!file_exists("sections/{$section['id']}"))
            continue;
        $config = FN_LoadConfig("modules/dbview/config.php", $section['id']);
        $tables = !empty($config['tables']) ? explode(",", $config['tables']) : array();
        if (empty($tables[0]) || !FN_XMDBTable($tables[0]))
            continue;
        $ret[$section['id']] = array(
            "mod" => $section['id'],
            "title" => html_entity_decode(strip_tags($section['title']), ENT_QUOTES, "UTF-8"),
            "table" => $tables[0],
            "config" => $config,
        );
    }
    return $ret;
}

/**
 * Index of the records of a section the current visitor may read: what the offline
 * search looks into and the list of the sheets to download. Values as stored (codes of
 * the areas, names of the municipalities), one array per record in the order of "fields".
 *
 * @param string $mod
 * @return array|false false for a section that cannot be downloaded
 */
function openkis_OfflineIndex($mod)
{
    global $_FN;
    $sections = openkis_OfflineSections();
    if (!isset($sections[$mod]))
        return false;
    require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
    $config = FN_LoadConfig("modules/dbview/config.php", $mod);
    $tablename = $sections[$mod]['table'];
    $table = FN_XMDBTable($tablename);
    $pk = $table->primarykey;
    $wanted = array($pk);
    foreach (array("titlefield", "search_fields", "search_partfields", "navigate_groups") as $key)
    {
        if (!empty($config[$key]))
            $wanted = array_merge($wanted, explode(",", $config[$key]));
    }
    $wanted = array_merge($wanted, array("synonyms", "latitude", "longitude", "elevation", "depth_total", "lenght_total", "recordupdate"));
    $fields = array();
    foreach ($wanted as $field)
    {
        $field = trim($field);
        // no people in a public index (author and editor of the record)
        if (in_array($field, array("username", "userupdate", "groupview", "groupinsert")))
            continue;
        if ($field !== "" && isset($table->fields[$field]) && !in_array($field, $fields))
            $fields[] = $field;
    }
    $imagefield = (!empty($config['image_titlefield']) && isset($table->fields[$config['image_titlefield']])) ? $config['image_titlefield'] : "";
    $select = $fields;
    if ($imagefield !== "" && !in_array($imagefield, $select))
        $select[] = $imagefield;
    $dbview = new FNDBVIEW($config);
    $idresult = false;
    $results = $dbview->GetResults($config, array("fields" => implode(",", $select)), $idresult);
    $records = array();
    if (is_array($results))
    {
        foreach ($results as $row)
        {
            $record = array();
            foreach ($fields as $field)
            {
                $record[] = isset($row[$field]) ? strval($row[$field]) : "";
            }
            if ($imagefield !== "")
                $record[] = empty($row[$imagefield]) ? 0 : 1;
            $records[] = $record;
        }
    }
    // labels of the fields in the language of the page, as the sheets write them
    $labels = array();
    foreach ($fields as $field)
    {
        $def = $table->fields[$field];
        $label = "";
        if (!empty($def->{"frm_{$_FN['lang']}"}))
            $label = $def->{"frm_{$_FN['lang']}"};
        elseif (!empty($def->frm_i18n))
            $label = FN_Translate($def->frm_i18n);
        else
            $label = FN_Translate(str_replace("_", " ", $field));
        $labels[$field] = html_entity_decode(strip_tags($label), ENT_QUOTES, "UTF-8");
    }
    if ($imagefield !== "")
        $fields[] = "hasimage";
    $titles = !empty($config['titlefield']) ? explode(",", $config['titlefield']) : array($pk);
    return array(
        "mod" => $mod,
        "lang" => $_FN['lang'],
        "title" => $sections[$mod]['title'],
        "pk" => $pk,
        "titlefields" => array_values(array_intersect($titles, $fields)),
        "fields" => $fields,
        "labels" => $labels,
        // address of a sheet: {id} replaced by the primary key
        "url" => str_replace("OKISID", "{id}", openkis_OfflineLink("index.php?mod=$mod&op=view&id=OKISID")),
        "list" => openkis_OfflineLink("index.php?mod=$mod"),
        "records" => $records,
    );
}

/**
 * What the worker and the offline page need: versions, addresses, sections.
 *
 * @return array
 */
function openkis_OfflineInfo()
{
    global $_FN;
    $lang = $_FN['lang'];
    $sections = array();
    $shells = array();
    foreach (openkis_OfflineSections() as $mod => $section)
    {
        require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
        $dbview = new FNDBVIEW($section['config']);
        $sections[] = array(
            "mod" => $mod,
            "title" => $section['title'],
            "count" => intval($dbview->GetResultsCount($section['config'], array())),
            "list" => openkis_OfflineLink("index.php?mod=$mod"),
            "index" => "{$_FN['siteurl']}openkis_offline.php?op=index&mod=$mod&lang=$lang",
        );
        $shells[] = "{$_FN['siteurl']}index.php?mod=$mod&lang=$lang&fnshell=1";
    }
    // map: the page of the map, the map sections and the layers of the record sections
    $map = array("pages" => array(), "data" => array("{$_FN['siteurl']}openkis_map_access.js.php"));
    foreach (FN_GetSections("", true, true, true, true) as $section)
    {
        if (!empty($section['id']) && (strpos($section['id'], "map_") === 0 || in_array($section['id'], array("fauna_map", "findings_map"))) && FN_UserCanViewSection($section['id']) && file_exists("sections/{$section['id']}"))
            $map['pages'][] = openkis_OfflineLink("index.php?mod={$section['id']}");
    }
    foreach (array("caves", "springs", "artificials", "glacial") as $mod)
    {
        if (file_exists("sections/$mod") && openkis_SectionMapAccess($mod) !== "")
            $map['data'][] = "{$_FN['siteurl']}openkis_kml.php?mod=$mod";
    }
    // other layers of the map (tracer test arrows, temperature, fauna and findings points)
    foreach (array("tracings" => "openkis_tracings.php", "temperatures" => "openkis_temperatures.php", "fauna_surveys" => "openkis_fauna.php", "findings" => "openkis_findings.php") as $mod => $endpoint)
    {
        if (file_exists("sections/$mod") && file_exists($endpoint) && FN_UserCanViewSection($mod))
            $map['data'][] = "{$_FN['siteurl']}$endpoint";
    }
    return array(
        "version" => openkis_OfflineVersion(),
        "pages" => openkis_OfflinePagesVersion(),
        "lang" => $lang,
        "siteurl" => $_FN['siteurl'],
        "home" => $_FN['siteurl'],
        "offline" => openkis_OfflineLink("index.php?mod=offline"),
        "concurrency" => max(1, min(6, isset($_FN['openkis_offline_concurrency']) ? intval($_FN['openkis_offline_concurrency']) : 2)),
        "sections" => $sections,
        "shells" => $shells,
        "map" => $map,
        "mapentry" => "{$_FN['siteurl']}bs_map.htm",
    );
}

/**
 * Texts of the offline scripts, in the language of the page.
 *
 * @return array
 */
function openkis_OfflineTexts()
{
    $texts = array(
        "offline" => "offline",
        "offline copy" => "offline copy",
        "copy of" => "copy of",
        "no connection" => "no connection",
        "online again" => "online again",
        "reload" => "reload",
        "page not available offline" => "this page is not available offline",
        "search" => "search",
        "search placeholder" => "code, name, synonym, municipality...",
        "results" => "results",
        "no results" => "no results",
        "offline use" => "offline use",
        "downloaded" => "downloaded",
        "download" => "download",
        "update" => "update",
        "remove" => "remove",
        "cancel" => "cancel",
        "sheets" => "sheets",
        "images" => "images",
        "map" => "map",
        "map and search" => "map and search",
        "never" => "never",
        "all sections" => "all sections",
        "offline data of" => "offline data of",
        "last update" => "last update",
        "space used" => "space used",
        "not supported" => "this browser cannot keep the site offline",
        "install hint" => "install the site as an application (browser menu, \"Add to Home screen\" or \"Install\") and download the sections you need before going where there is no network",
        "logged hint" => "only the public content is downloaded: what you see as a logged user is not kept",
        "download error" => "some pages could not be downloaded, retry later",
        "keep open" => "keep this page open until the download ends",
        "remove all" => "remove everything",
        "confirm remove" => "remove the offline copies of this site from this device?",
        "updates available" => "updates available",
        "up to date" => "up to date",
        "available offline" => "available offline",
        "open" => "open",
        "persist denied" => "the browser may delete the offline data when space is low",
        "worker missing" => "the offline service is starting: reload the page in a few seconds",
    );
    $ret = array();
    foreach ($texts as $key => $english)
    {
        // as written in the translation (the scripts put the capital letters where needed)
        $ret[$key] = FN_Translate($english, "");
    }
    return $ret;
}

/**
 * Settings of openkis_offline.js, printed in the head of every page.
 *
 * @return array
 */
function openkis_OfflineClientConfig()
{
    global $_FN;
    $version = openkis_OfflineVersion();
    $scope = parse_url($_FN['siteurl'], PHP_URL_PATH);
    return array(
        "enabled" => openkis_OfflineEnabled(),
        "version" => $version,
        "sw" => "{$_FN['siteurl']}openkis_sw.js?v=$version",
        "scope" => $scope ? $scope : "/",
        "siteurl" => $_FN['siteurl'],
        "info" => "{$_FN['siteurl']}openkis_offline.php?op=info&lang={$_FN['lang']}",
        "offline" => openkis_OfflineLink("index.php?mod=offline"),
        "lang" => $_FN['lang'],
        "logged" => ($_FN['user'] != ""),
        "texts" => openkis_OfflineTexts(),
    );
}

/**
 * Html for the head of the pages: settings and script of the offline use.
 *
 * @return string
 */
function openkis_OfflineHead()
{
    global $_FN;
    $config = openkis_OfflineClientConfig();
    $json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    return "\n<script>window.OPENKIS_OFFLINE=$json;</script>"
        . "\n<script defer=\"defer\" src=\"{$_FN['siteurl']}openkis_offline.js?v={$config['version']}\"></script>\n";
}
