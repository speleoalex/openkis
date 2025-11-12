<?php

/**
 * 
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2024
 */
defined('_FNEXEC') || die('Restricted access');

/**
 * 
 * @global type $_FN
 * @return type
 */
function FN_GetExecuteTimer()
{
    global $_FN;
    return sprintf("%.4f", abs(microtime(true) - $_FN['timestart']));
}

/**
 * 
 * @global type $_FN
 * @return type
 */
function FN_GetPartialTimer()
{
    global $_FN;
    // Initialize the partial timer if not set
    if (empty($_FN['timepartial'])) {
        $_FN['timepartial'] = $_FN['timestart'];
    }
    // Get current time in microseconds
    $mtime = microtime(true);
    // Calculate the partial time difference
    $ret = sprintf("%.4f", $mtime - $_FN['timepartial']);
    // Update the partial timer
    $_FN['timepartial'] = $mtime;
    // Get the total execution time
    $total = FN_GetExecuteTimer();
    // Return the formatted partial and total execution times
    return "partial=$ret - total=$total";
}

/**
 * 
 * @param string $key
 * @param array $var
 * @param string $type
 * @return string
 */
function FN_GetParam($key, $var = false, $type = "")
{
    global $_FN;
    // Default to use $_REQUEST if no variable array is provided
    $var = $var === false ? $_REQUEST : $var;
    $ret = $key === false ? $var : (isset($var[$key]) ? $var[$key] : "");
    // Ensure the return value is not an array
    if (is_array($ret)) {
        $ret = "";
    }
    // Process the return value based on the specified type
    switch ($type) {
        case "html":
            $charset = $_FN['charset_page'] ?? "UTF-8"; // Use null coalescing operator for default charset
            $ret = htmlentities($ret, ENT_QUOTES, $charset);
            break;
        case "int":
            $ret = $ret !== "" ? intval($ret) : 0;
            break;
        case "float":
            $ret = $ret !== "" ? floatval($ret) : 0.0;
            break;
        default:
            if (function_exists($type)) {
                return $type($ret);
            }
            break;
    }
    // Remove any added slashes
    return FN_StripPostSlashes($ret);
}

/**
 * load section.php or section.[lang].html and 
 * return html
 * 
 * @global array $_FN
 * @param string $folder
 * @param bool $usecache
 * @return string 
 */
function FN_HtmlContent($folder, $usecache = true)
{
    $str = "";
    if (file_exists("$folder/section.php")) {
        ob_start();
        include_once "$folder/section.php";
        $str = ob_get_clean();
        return $str;
    } else
        $str = FN_HtmlStaticContent($folder, $usecache);
    return $str;
}

/**
 * load section.[lang].html and 
 * return html
 * 
 * @global array $_FN
 * @param string $folder
 * @param bool $usecache
 * @return string 
 */
function FN_HtmlStaticContent($folder, $usecache = false)
{
    global $_FN;
    static $cache = array();
    if ($usecache) {
        if (!empty($cache[$folder])) {
            return $cache[$folder]; //cache in memory
        }
        if (!empty($_FN['use_cache']) && file_exists("{$_FN['datadir']}/_cache/{$_FN['lang']}" . urlencode($folder) . ".cache")) {
            return file_get_contents("{$_FN['datadir']}/_cache/{$_FN['lang']}" . urlencode($folder) . ".cache");
        }
    }
    $filetoread = "";
    $str = "";
    if (file_exists("$folder/section.{$_FN['lang']}.html")) {
        $filetoread = "$folder/section.{$_FN['lang']}.html";
    } elseif (file_exists("$folder/section.{$_FN['lang_default']}.html")) {
        $filetoread = "$folder/section.{$_FN['lang_default']}.html";
    }
    if ($filetoread) {
        $str = file_get_contents($filetoread);
        $str = FN_RewriteLinksLocalToAbsolute($str, $folder);
    }

    $cache[$folder] = $str;
    if (!empty($_FN['use_cache']))
        FN_Write($str, "{$_FN['datadir']}/_cache/{$_FN['lang']}" . urlencode($folder) . ".cache");
    return $str;
}

/**
 * 
 * @global type $_FN
 * @param type $str
 * @param type $folder
 * @return type
 */
function FN_RewriteLinksLocalToAbsolute($str, $folder)
{
    global $_FN;

    $sdir = $_FN['use_urlserverpath'] ? "http://____replace____/$folder/" : "{$_FN['siteurl']}$folder/";

    // Define patterns and replacements
    $patterns = [
        '/href="(index\.php|#|http:|https:|{)/i',
        '/href=\'(index\.php|#|http:|https:|{)/i',
        '/href="\//',
        '/href=\'\//',
        '/src="(<|\/)/i',
        '/src=\'(<|\/)/i'
    ];
    $replacements = [
        'ferh="$1',
        'ferh=\'$1',
        'ferh="/',
        'ferh=\'/',
        's_r_c="$1',
        's_r_c=\'$1'
    ];

    // Apply replacements
    $str = preg_replace($patterns, $replacements, $str);

    // Replace local URLs with absolute URLs
    $old = "";
    while ($str !== $old) {
        $old = $str;
        $str = preg_replace([
            '/<([^>]+)( background| href| src)=(["\'])([^#:{]*)(["\'])/im',
            '#<([^>]+)(url\((\'?)(?!http))#'
        ], [
            "<\\1\\2=\\3$sdir\\4\\5",
            "<\\1\\2\\3$sdir"
        ], $str);
    }

    // Restore original attribute names
    $str = str_replace(['ferh="', 'ferh=\'', 's_r_c="', 's_r_c=\''], ['href="', 'href=\'', 'src="', 'src=\''], $str);

    // Handle mod_rewrite if enabled
    if ($_FN['enable_mod_rewrite'] > 0 && $_FN['links_mode'] == "html") {
        $langSuffix = ($_FN['lang'] == $_FN['lang_default']) ? '' : ".{$_FN['lang']}";
        $str = preg_replace([
            '/(href=["\'])index\.php\?mod=([A-Z0-9_]+)(["\'])/is'
        ], [
            "$1$2{$langSuffix}.html$3"
        ], $str);
    }

    // Replace placeholder URL if necessary
    if (!empty($_FN['use_urlserverpath'])) {
        $str = str_replace("http://____replace____/", $_FN['sitepath'], $str);
    }

    $str = FN_NormalizeAllPaths($str);
    return $str;
}

/**
 * Include CSS from sections/SECTION/style.css include/css/ , include/themes/THEME/ 
 *
 * @global array $_FN
 * @param bool $include_theme_css
 * @param bool $include_section_css
 * @return string 
 */
function FN_IncludeCSS()
{
    global $_FN;
    $html = "";
    $css = "";
    $sitepath = FN_SitePath();
    $sectionvalues = FN_GetSectionValues($_FN['mod']);
    if (!empty($sectionvalues['type']) && file_exists("{$_FN['src_finis']}/modules/{$sectionvalues['type']}/style.css")) {
        $css .= file_get_contents("{$_FN['src_finis']}/modules/{$sectionvalues['type']}/style.css") . "\n";
    }
    if (file_exists("{$_FN['src_application']}/sections/{$_FN['mod']}/style.css")) {
        $css .= file_get_contents("{$_FN['src_application']}/sections/{$_FN['mod']}/style.css") . "\n";
    }
    $html = "<style>$css</style>";

    //    $html = "<script>window.setTimeout(function(){document.getElementsByTagName('head')[0].innerHTML+='" . addslashes(str_replace("\n", "", $html)) . "';},10);</script>";

    return $html;
}

function FN_SitePath()
{
    global $_FN;
    if (!empty($_FN['use_urlserverpath']))
        $sitepath = $_FN['sitepath'];
    else
        $sitepath = $_FN['siteurl'];
    return $sitepath;
}

/**
 * Include JS from nclude/javascripts/
 * @global array $_FN
 */
function FN_IncludeJS()
{
    global $_FN;
    $sitepath = FN_SitePath();
    $html = "";
    $listjs = glob("{$_FN['src_finis']}/include/javascripts/*.js");
    foreach ($listjs as $file) {
        FN_PathSite($file);
    }
    $listjs = glob("{$_FN['src_application']}/include/javascripts/*.js");
    foreach ($listjs as $file) {
        $html .= "\n\t<script defer=\"defer\" type=\"text/javascript\" src=\"{$sitepath}$file\"></script>";
    }
    return $html;
}

/**
 * Return file path from theme
 * 
 * @param string $file File name
 * @param bool $absolute Whether to return absolute path
 * @return string Path to the file
 */
function FN_FromTheme($file, $absolute = true)
{
    global $_FN;

    // Check if the file path is already absolute
    $isAbsolute = $file[0] === $_FN['slash'] || $file[0] === '/';
    $applicationPath = $isAbsolute ? $file : "{$_FN['src_finis']}/$file";

    // Construct theme path
    $themePath = "{$_FN['src_application']}/themes/{$_FN['theme']}/" .
        ltrim(str_replace("{$_FN['src_finis']}/", "", $applicationPath), '/');

    // Check if the file exists in the theme directory
    if (file_exists(FN_PathSite($themePath))) {
        return $absolute ? $_FN['siteurl'] . $themePath : $themePath;
    }

    // If not found in theme, use FN_PathSite
    return FN_PathSite($file, $absolute);
}

/**
 *
 * @global array $_FN
 * @return int
 */
function FN_Time()
{
    global $_FN;
    return time();
}

/**
 *
 * @global array $_FN
 * @param string $format
 * @return string
 */
function FN_Now($format = "Y-m-d H:i:s")
{
    global $_FN;
    return date("$format", time());
}

/**
 * Gett accesskey from link
 *
 *
 * @global array $_FN
 * @param string $title
 * @param string $link
 * @param string $forcekey
 * @return string
 */
function FN_GetAccessKey(&$title, $link, $forcekey = "")
{
    global $_FN;
    $link = str_replace("&amp;", "&", $link);
    if (!isset($_FN['accesskey']) || !is_array($_FN['accesskey']))
        $_FN['accesskey'] = array();
    $showaccesskey = $_FN['showaccesskey'];
    $titlel = strtolower($title);
    if ($forcekey != "") {
        if ($showaccesskey == 1) // sottolinea gli accesskey
        {
            $title = "[" . $forcekey . "]$title";
        }
        $_FN['accesskey'][$forcekey] = $link;
        return $forcekey;
    }
    //----------cerco un accesskey libero------------
    for ($i = 0; $i < strlen($titlel); $i++) {
        $a = $titlel[$i];
        if (!FN_erg("[a-z]", $a))
            continue;
        //---------se esiste gia' per quel link esco --------------
        if (isset($_FN['accesskey'][$a]) && $_FN['accesskey'][$a] == $link) {
            if ($showaccesskey == 1) // sottolinea gli accesskey
                $title = "[" . $a . "]&nbsp;$title";
            $_FN['accesskey'][$a] = $link;
            return $a;
        }
        //-----tento con le altre lettere ------
        if (!isset($_FN['accesskey'][$a]) && !is_numeric($a)) {
            $_FN['accesskey'][$a] = $link;
            if ($showaccesskey == 1) // sottolinea gli accesskey
            {
                $title = "[" . $a . "]&nbsp;$title";
            }
            return $a;
        }
    }
    $chrs = array(
        'a',
        'b',
        'c',
        'd',
        'e',
        'f',
        'g',
        'h',
        'i',
        'j',
        'k',
        'l',
        'm',
        'n',
        'o',
        'p',
        'q',
        'r',
        's',
        't',
        'u',
        'v',
        'w',
        'x',
        'y',
        'z',
        ',',
        '.',
        '-',
        '+',
        '\\',
        '*',
        '@',
        '#',
        '?',
        '$',
        '!',
        '%',
        '/',
        '(',
        ')',
        '=',
        '^',
        ';',
        ':',
        '.',
        '_',
        '|',
        '*'
    );
    foreach ($chrs as $a) {
        if (!isset($_FN['accesskey'][$a])) {
            if ($showaccesskey == 1) // sottolinea gli accesskey
            {
                $title = "[" . $a . "]$title";
            }
            $_FN['accesskey'][$a] = $link;
            return $a;
        }
    }
    return "";
}

/**
 * @global array $_FN
 * @param string $string
 */
function FN_Tag2Html($string)
{
    $string = str_replace("[:)]", "<img src=\"" . FN_FromTheme("images/emoticon/01.png") . "\" alt=\":)\" />", $string);
    $string = str_replace("[:(]", "<img src=\"" . FN_FromTheme("images/emoticon/02.png") . "\" alt=\":(\" />", $string);
    $string = str_replace("[:o]", "<img src=\"" . FN_FromTheme("images/emoticon/03.png") . "\" alt=\":o\" />", $string);
    $string = str_replace("[:p]", "<img src=\"" . FN_FromTheme("images/emoticon/04.png") . "\" alt=\":p\" />", $string);
    $string = str_replace("[:D]", "<img src=\"" . FN_FromTheme("images/emoticon/05.png") . "\" alt=\":D\" />", $string);
    $string = str_replace("[:!]", "<img src=\"" . FN_FromTheme("images/emoticon/06.png") . "\" alt=\":!\" />", $string);
    $string = str_replace("[:O]", "<img src=\"" . FN_FromTheme("images/emoticon/07.png") . "\" alt=\":O\" />", $string);
    $string = str_replace("[8)]", "<img src=\"" . FN_FromTheme("images/emoticon/08.png") . "\" alt=\"8)\" />", $string);
    $string = str_replace("[;)]", "<img src=\"" . FN_FromTheme("images/emoticon/09.png") . "\" alt=\";)\" />", $string);
    $string = str_replace("\n", "<br />", $string);
    $string = str_replace("\r", "", $string);
    $string = str_replace("[b]", "<b>", $string);
    $string = str_replace("[/b]", "</b>", $string);
    $string = str_replace("[i]", "<i>", $string);
    $string = str_replace("[/i]", "</i>", $string);
    $string = str_replace("[quote]", "<blockquote><hr noshade=\"noshade\" /><i>", $string);
    $string = str_replace("[/quote]", "</i><hr noshade=\"noshade\" /></blockquote>", $string);
    $string = str_replace("[code]", "<blockquote><pre>", $string);
    $string = str_replace("[/code]", "</pre></blockquote>", $string);

    $string = str_replace("[img]", "<br /><img src=\"", $string);
    $string = str_replace("[/img]", "\" alt=\"uploaded_image\" /><br />", $string);
    //$string = preg_replace("/\[youtube\](.+?)\[\/youtube\]/s",'<object width="425" height="355"><param name="movie" value="http://www.youtube.com/v/$1&rel=1"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/$1&rel=1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="355"></embed></object>',$string);
    // text color--->
    $string = str_replace("[red]", "<font color='ff0000'>", $string);
    $string = str_replace("[green]", "<font color='00ff00'>", $string);
    $string = str_replace("[blue]", "<font color='0000ff'>", $string);
    $string = str_replace("[pink]", "<font color='ff00ff'>", $string);
    $string = str_replace("[yellow]", "<font color='ffff00'>", $string);
    $string = str_replace("[cyan]", "<font color='00ffff'>", $string);
    $string = str_replace("[/red]", "</font>", $string);
    $string = str_replace("[/blue]", "</font>", $string);
    $string = str_replace("[/green]", "</font>", $string);
    $string = str_replace("[/pink]", "</font>", $string);
    $string = str_replace("[/yellow]", "</font>", $string);
    $string = str_replace("[/cyan]", "</font>", $string);
    // text color---<
    // WIKIPEDIA --->
    $items = explode("[/wp]", $string);
    for ($i = 0; $i < count($items); $i++) {
        $wp = "";
        if (stristr($items[$i], "[wp")) {
            $wp_lang = preg_replace("/.*\\[wp lang=/s", "", $items[$i]);
            $wp_lang = preg_replace("/\\].*/s", "", $wp_lang);
            $wp = preg_replace("/.*\\[wp.*\\]/s", "", $items[$i]);
            $wp = preg_replace("/\\[\\/wp\\].*/s", "", $wp);
            if ($wp != "") {
                $nuovowp = "<a style=\"text-decoration: none; border-bottom: 1px dashed; color: blue;\" target=\"new\" href=\"http://$wp_lang.wikipedia.org/wiki/$wp\">$wp</a>";
                $string = str_replace("[wp lang=$wp_lang]" . $wp . "[/wp]", $nuovowp, $string);
            }
        }
    }
    // WIKIPEDIA ---<
    $items = "";
    // URLs --->
    $items = explode("[/url]", $string);
    for ($i = 0; $i < count($items); $i++) {
        $url = "";
        if (stristr($items[$i], "[url]")) {
            $url = preg_replace("/.*\\[url\\]/s", "", $items[$i]);
            $url = preg_replace("/\\[\/url\\].*/s", "", $url);
            if ($url != "") {
                if (stristr($url, "http://") == FALSE && stristr($url, "https://") == FALSE) {
                    $nuovourl = "<a target=\"new\" href=\"http://$url\">$url</a>";
                } else {
                    $nuovourl = "<a target=\"new\" href=\"$url\">$url</a>";
                }
                $string = str_replace("[url]" . $url . "[/url]", $nuovourl, $string);
            }
        }
    }
    // URLs ---<
    return ($string);
}

/**
 * 
 * @global array $_FN
 * @param type $event
 * @param type $context
 */
function FN_LogEvent($event, $context = "cms")
{
    global $_FN;
    FN_Install("misc/fndatabase/fn_log.php");
    $table = FN_XMDBTable("fn_log");
    $newvalues = array();
    $newvalues['context'] = preg_replace('/[^a-z0-9]+/', '_', strtolower($context));
    $newvalues['event'] = $event;
    $newvalues['user'] = $_FN['user'];
    $newvalues['ip'] = FN_GetParam("REMOTE_ADDR", $_SERVER, "html");
    $newvalues['date'] = FN_Now();
    $f = $table->InsertRecord($newvalues);
}

/**
 * 
 * @global array $_FN
 * @param type $notificationvalues
 * @param type $users
 * @return type
 */
function FN_AddNotification($notificationvalues, $users)
{
    global $_FN;
    FN_Install("misc/fndatabase/fn_notifications.php");
    if ($users && is_string($users)) {
        if (!FN_GetUser($users)) {
            return;
        }
        $users = array($users);
    }
    $users = array_unique($users);
    $table = FN_XMDBTable("fn_notifications");
    if (is_string($notificationvalues)) {
        $text = $notificationvalues;
        $notificationvalues = array();
        $notificationvalues['text'] = "$text";
    }

    $newvalues = array();
    $newvalues['context'] = preg_replace('/[^a-z0-9]+/', '_', strtolower(FN_GetParam("context", $notificationvalues, "html")));
    $newvalues['text'] = FN_GetParam("text", $notificationvalues, "html");
    $newvalues['link'] = FN_GetParam("link", $notificationvalues, "html");
    $newvalues['ip'] = FN_GetParam("REMOTE_ADDR", $_SERVER, "html");
    $newvalues['date'] = FN_Now();

    foreach ($users as $user) {
        if ($user) {
            $newvalues['username'] = $user;
            $f = $table->InsertRecord($newvalues);
        }
    }
}

/**
 * 
 * @global array $_FN
 * @param type $context
 */
function FN_GetNotificationsUndisplayed($user, $context = "")
{
    global $_FN;
    $table = FN_XMDBTable("fn_notifications");
    //$user = str_replace("'", "\\'", $user);
    $query = "SELECT * FROM fn_notifications WHERE username LIKE '$user' AND displayed <> 1";

    $notifications = FN_XMETADBQuery($query);
    //$notifications = $table->GetRecords(array("username" => $user));
    $ret_notifications = array();
    if (is_array($notifications)) {
        foreach ($notifications as $k => $notification) {
            if ($notification['displayed'] != 1) {
                $tmp = $notification;
                $tmp['human_date'] = FN_FormatDate($notification['date'], true);
                $tmp['link'] = isset($notification['link']) ? $notification['link'] : "";
                $ret_notifications[] = $tmp;
            }
        }
    }

    return $ret_notifications;
}

/**
 * 
 * @global array $_FN
 * @param type $id
 */
function FN_SetNotificationDisplayed($id)
{
    global $_FN;
    $table = FN_XMDBTable("fn_notifications");
    $table->UpdateRecordBypk(array("displayed" => 1), "id", $id);
}

/**
 *
 * @global array $_FN
 * @param string $txt
 */
function FN_Log($txt)
{
    global $_FN;
    FN_LogEvent ($txt);
    if ($_FN['enable_log_email'] == 1) {
        $txtmail = "Log from: {$_FN['sitename']}";
        $txtmail .= "\n\nSite url:{$_FN['siteurl']}";
        $txtmail .= "\n\nLog: $txt";
        FN_SendMail($_FN['log_email_address'], "[fnlog] {$_FN['sitename']}", $txtmail);
    }
}

/**
 *
 * @param string $user
 * @param string $group
 * @return bool
 */
function FN_UserInGroup($user, $group)
{
    $user = FN_GetUser($user);

    if (isset($user['group'])) {
        $usergroups = explode(",", $user['group']);
        if (is_array($usergroups)) {
            $groups = explode(",", $group);
            foreach ($groups as $group) {
                if (in_array($group, $usergroups)) {
                    //dprint_r(" $user, $group true");
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $groupname
 */
function FN_CreateGroupIfNotExists($groupname)
{
    global $_FN;
    $table = FN_XMDBTable("fn_groups");
    $old = $table->GetRecordByPrimaryKey($groupname);
    if (!isset($old['groupname'])) {
        $table->InsertRecord(array("groupname" => $groupname));
    }
}

/**
 *
 * @global array $_FN 
 */
function FN_InitTables($force = false)
{
    global $_FN;
    if (!is_writable($_FN['datadir']))
        return;
    if (!file_exists($_FN['datadir'] . "/_cache"))
        FN_MkDir($_FN['datadir'] . "/_cache");
    if (!empty($_FN['use_cache']) && !file_exists("{$_FN['datadir']}/_cache/html")) {
        FN_MkDir("{$_FN['datadir']}/_cache/html");
    }
    if (!file_exists("{$_FN['datadir']}/{$_FN['database']}")) {
        $ret = mkdir("{$_FN['datadir']}/{$_FN['database']}");
        if (!$ret)
            dprint_r("error create folder: {$_FN['datadir']}/{$_FN['database']}");
    }
    FN_Install("misc/fndatabase/fn_i18n.php", $force);
    FN_Install("misc/fndatabase/fn_sections.php", $force);
    FN_Install("misc/fndatabase/fn_sectionstypes.php", $force);
    FN_Install("misc/fndatabase/fn_blocks.php", $force);
    FN_Install("misc/fndatabase/fn_settings.php", $force);
    FN_Install("misc/fndatabase/fn_oauth_providers.php", $force);
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_users.php")) {
        if (file_exists("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_users.custom.php"))
            FN_Copy("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_users.custom.php", "{$_FN['datadir']}/{$_FN['database']}/fn_users.php");
        else
            FN_Copy("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_users.php", "{$_FN['datadir']}/{$_FN['database']}/fn_users.php");
    }
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_groups.php")) {
        FN_Copy("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_groups.php", "{$_FN['datadir']}/{$_FN['database']}/fn_groups.php");
        $table = FN_XMDBTable("fn_groups");
        $r['groupname'] = 'users';
        $table->InsertRecord($r);
    }
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_avatars") && file_exists("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_avatars")) {
        FN_CopyDir("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_avatars", "{$_FN['datadir']}/{$_FN['database']}/");
    }
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_avatars.php") && file_exists("{$_FN['src_finis']}/include/install/{$_FN['database']}/fn_avatars.php"))
        FN_Copy("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_avatars.php", "{$_FN['datadir']}/{$_FN['database']}/fn_avatars.php");
    if ($force || !file_exists("{$_FN['datadir']}/fndatabase/fn_conditions.php")) {
        FN_Copy("{$_FN['src_finis']}/include/install/misc/fndatabase/fn_conditions.php", "{$_FN['datadir']}/{$_FN['database']}/fn_conditions.php");
        $tcond = FN_XMDBForm("fn_conditions");
        $conditions = $tcond->xmltable->GetRecords();
        if (!is_array($conditions) || count($conditions) == 0) {

            $value['text'] = file_get_contents("{$_FN['src_finis']}/modules/login/conditions/conditions.en.html");
            $value['text_it'] = file_get_contents("{$_FN['src_finis']}/modules/login/conditions/conditions.it.html");
            $value['text_en'] = file_get_contents("{$_FN['src_finis']}/modules/login/conditions/conditions.en.html");
            $value['text_de'] = file_get_contents("{$_FN['src_finis']}/modules/login/conditions/conditions.de.html");
            $value['text_es'] = file_get_contents("{$_FN['src_finis']}/modules/login/conditions/conditions.es.html");
            $value['text_fr'] = file_get_contents("{$_FN['src_finis']}/modules/login/conditions/conditions.fr.html");
            $value['enabled'] = 1;
            $nv = $tcond->xmltable->InsertRecord($value);
        }
    }
    FN_InitSections();
    FN_InitBlocks();
}


/**
 *
 * @param string $string
 * @return string
 */
function FN_StripPostSlashes($string)
{
    $magic_quotes = (bool) ini_get('magic_quotes_gpc');
    if ($magic_quotes)
        return stripslashes($string);
    else
        return ($string);
}

/**
 *
 * @global array $_FN
 * @param string $time
 * @return string
 */
function FN_GetDateTime($time)
{
    global $_FN;
    if (strlen("$time") == 19 || is_string($time)) {
        $time = strtotime($time);
    }
    if (!$time) {
        $time = time();
    }
    $ret = $_FN['days'][date("w", $time)];
    $ret .= date(" d ", $time);
    $tmp = date(" m", $time);
    if ($tmp < 10)
        $tmp = str_replace("0", "", $tmp);
    $ret .= $_FN['months'][$tmp - 1];
    $ret .= date(" Y - ", $time);
    $ret .= date("H:", $time);
    $ret .= date("i", $time);
    return $ret;
}

/**
 * 
 * @global array $_FN
 * @param type $time
 * @param type $showtime
 * @return type
 */
function FN_FormatDate($time, $showtime = true)
{
    global $_FN;
    if (strlen("$time") == 19 || !is_numeric($time)) {
        $time = strtotime($time);
    }
    $ret = $_FN['days'][date("w", $time)];
    $ret .= date(" d ", $time);
    $tmp = date(" m", $time);
    if ($tmp < 10)
        $tmp = str_replace("0", "", $tmp);
    $ret .= $_FN['months'][$tmp - 1];
    $ret .= date(" Y ", $time);
    if ($showtime) {
        $ret .= date("- H:", $time);
        $ret .= date("i", $time);
    }
    return $ret;
}

/**
 *
 * @global array $_FN
 * @return bool
 */
function FN_IsExternalReferer()
{
    global $_FN;
    if (empty($_SERVER['HTTP_REFERER']) || !FN_erg($_FN['siteurl'], $_SERVER['HTTP_REFERER'])) {
        return true;
    }
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $to
 * @param string $subject
 * @param string $body
 * @param bool $ishtml
 * @param type $from
 * @return bool
 */
function FN_SendMail($to, $subject, $body, $ishtml = false, $from = "")
{
    global $_FN;
    $replyto = $from;
    if ($from == "") {
        $from = $_FN['site_email_address'];
        $replyto = $_FN['site_email_address'];
    }
    if (!empty($_FN['FN_SendMail']) && $_FN['FN_SendMail'] != "FN_SendMail") {
        return $_FN['FN_SendMail']($to, $subject, $body, $ishtml, $from);
    }
    if ($to != "") {
        if ($ishtml) {
            $headers = "MIME-Version: 1.0\n" .
                "Content-type: text/html; charset=\"utf-8\"\n" .
                "From: $from\n" .
                "Reply-To: {$replyto}\n" .
                "X-Mailer: PHP/" . phpversion();
        } else {
            $headers = "MIME-Version: 1.0\n" .
                "Content-Type: text/plain; charset = \"utf-8\"\n";
            $headers .= "From: $from\n" .
                "Reply-To: {$replyto}\n" .
                "X-Mailer: PHP/" . phpversion();
        }
        $message = FN_FixNewline($body);
        $headers = FN_FixNewline($headers);
        if (@mail($to, $subject, $message, $headers)) {
            return true;
        }
    }
    return false;
}

/**
 * convert newline in correct format
 *
 * @param string $text
 */
function FN_FixNewline($text)
{
    if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN')) {
        $eol = "\r\n";
    } elseif (strtoupper(substr(PHP_OS, 0, 3) == 'MAC')) {
        $eol = "\r";
    } else {
        $eol = "\n";
    }
    //fix newline
    $text = str_replace("\r\n", "\n", $text);
    $text = str_replace("\r", "", $text);
    $text = str_replace("\n", "$eol", $text);
    return $text;
}

/**
 *
 * @param string $param
 */
function FN_SaveGetPostParam($param, $ignore_post = false, $ignore_get = false, $ignore_cookie = false)
{
    global $_FN;
    $retparam = false;
    if (!$ignore_cookie && isset($_COOKIE[$param])) {
        $retparam = $_COOKIE[$param];
    }
    if (!$ignore_post && isset($_POST[$param]) && !is_array($_POST[$param])) {
        if (!$ignore_cookie) {
            $_COOKIE[$param] = $_POST[$param];
            setcookie($param, $_POST[$param], time() + 999999999, $_FN['urlcookie']);
        }
        $retparam = FN_StripPostSlashes($_POST[$param]);
    } elseif (!$ignore_get && isset($_GET[$param]) && !is_array($_GET[$param])) {
        if (!$ignore_cookie) {
            $_COOKIE[$param] = $_GET[$param];
            setcookie($param, $_GET[$param], time() + 999999999, $_FN['urlcookie']);
        }
        $retparam = FN_StripPostSlashes($_GET[$param]);
    }
    return $retparam;
}

/**
 *
 * @param string $filename
 * @return string
 */
function FN_GetIconByFilename($filename)
{
    $ext = FN_GetFileExtension($filename);
    $ext = strtolower($ext);
    $dimg = "unknown.png";
    switch ($ext) {
        case "sh":
            $dimg = "binhex.png";
            break;
        case "xhtml":
        case "html":
        case "htm":
            $dimg = "web.png";
            break;
        case "inc":
        case "txt":
        case "xml":
        case "css":
        case "":
            $dimg = "text.png";
            break;
        case "png":
        case "bmp":
        case "jpg":
        case "jpeg":
        case "ico":
        case "gif":
            $dimg = "image.png";
            break;
        case "zip":
        case "gz":
            $dimg = "compressed.png";
            break;
        case "mp3":
        case "wav":
            $dimg = "sound.png";
            break;
        case "wma":
        case "mpeg":
        case "rm":
            $dimg = "movie.png";
            break;
        default:
            if (file_exists("images/mime/$ext.png"))
                $dimg = "$ext.png";
            break;
    }
    return FN_FromTheme("images/mime/$dimg");
}

/**
 *
 * @param string $tablename
 */
function FN_GetVarsFromTable($tablename)
{
    $Table = FN_XMDBTable($tablename);
    $items = $Table->GetRecords();

    $var = array();
    if (is_array($items))
        foreach ($items as $item) {
            if (isset($item['varname']) && isset($item['varvalue']))
                $var[$item['varname']] = $item['varvalue'];
        }
    return $var;
}

/**
 *
 * @global array $_FN
 * @param string $folder 
 */
function FN_GetMessagesFromFolder($folder)
{
    global $_FN;
    $tmp = array();
    $tmp_theme = false;
    $rel_folder = str_replace($_FN['src_finis'], "", $folder);
    if (file_exists("$folder/languages/{$_FN['lang']}/lang.csv")) {
        $foldertheme = FN_FromTheme("$folder/languages/{$_FN['lang']}/lang.csv");
        $tmp = FN_GetMessagesFromCsv("$folder/languages/{$_FN['lang']}/lang.csv");
    } elseif (file_exists("$folder/languages/en/lang.csv")) {
        $tmp = FN_GetMessagesFromCsv("$folder/languages/en/lang.csv");
    }
    if (file_exists($_FN['src_finis'] . "/themes/{$_FN['theme']}/$rel_folder/languages/{$_FN['lang']}/lang.csv")) {
        $tmp_theme = FN_GetMessagesFromCsv($_FN['src_finis'] . "/themes/{$_FN['theme']}/$rel_folder/languages/{$_FN['lang']}/lang.csv");
        $tmp = array_merge($tmp, $tmp_theme);
    }
    if (file_exists($_FN['src_application'] . "/themes/{$_FN['theme']}/$rel_folder/languages/{$_FN['lang']}/lang.csv")) {
        $tmp_theme = FN_GetMessagesFromCsv($_FN['src_application'] . "/themes/{$_FN['theme']}/$rel_folder/languages/{$_FN['lang']}/lang.csv");
        $tmp = array_merge($tmp, $tmp_theme);
    }

    return $tmp;
}

/**
 *
 * @global array $_FN
 * @param string $path
 * @param string $title
 * @param string $lang
 */
function FN_SetFolderTitle($path, $title, $lang = "")
{
    global $_FN;
    if ($lang == "")
        $lang = $_FN['lang'];
    FN_Write($title, "$path/title.$lang.fn");
}

/**
 *
 * @global array $_FN
 * @param string $path
 * @return string
 */
function FN_GetFolderTitle($path, $lang = "")
{
    global $_FN;
    if ($lang == "")
        $lang = $_FN['lang'];
    $title = "";
    if (!is_dir($path)) {

        if (file_exists("$path.$lang.fn")) {
            $title = file_get_contents("$path.$lang.fn");
        } elseif (file_exists("$path.i18n.fn")) {
            $title = FN_Translate(file_get_contents("$path.i18n.fn"), "Aa", $lang);
        } else {
            $title = basename($path);
        }
        return $title;
    } elseif (file_exists("$path/title.$lang.fn")) {
        $title = file_get_contents("$path/title.$lang.fn");
    } elseif (file_exists("$path/title.i18n.fn")) {
        $title = FN_Translate(file_get_contents("$path/title.i18n.fn"), "Aa", $lang);
    } elseif (file_exists("$path/title.{$_FN['lang_default']}.fn"))
        $title = file_get_contents("$path/title.{$_FN['lang_default']}.fn");
    elseif (file_exists("$path/title.en.fn"))
        $title = file_get_contents("$path/title.en.fn");
    if ($title === "") {
        $title = basename($path);
    }
    $title = str_replace("{siteurl}", $_FN['siteurl'], $title);
    $title = str_replace("\n", "", $title);
    $title = str_replace("\r", "", $title);

    return $title;
}

/**
 * 
 * @param type $var
 * @param type $tablename
 * @param type $configvars
 * @param array $ignore
 * @return type
 */
function FN_LoadVarsFromTable(&$var, $tablename, $configvars = array(), $ignore = array())
{
    global $_FN;
    $Table = FN_XMDBTable($tablename);
    if (!is_array($ignore))
        $ignore = array();
    $vars_in_table_assoc = FN_GetVarsFromTable($tablename);
    if (is_array($var)) {
        //---clear obsolete vars----------------------------------------------->
        foreach ($vars_in_table_assoc as $k => $v) {
            if (is_array($configvars) && count($configvars) > 0 && !in_array($k, $configvars)) {
                $Table->DelRecord($k);
            }
        }
        //---clear obsolete vars-----------------------------------------------<
        $settingsByKey = array();
        $settings = $Table->GetRecords();
        if (is_array($settings))
            foreach ($settings as $v) {
                if (isset($v['varname'])) {
                    $settingsByKey[$v['varname']] = $v;
                }
            }
        foreach ($var as $k => $v) {
            if (in_array($k, $ignore))
                continue;
            if (!in_array($k, $configvars))
                continue;
            $old = isset($settingsByKey[$k]) ? $settingsByKey[$k] : array();
            if (!@array_key_exists('defaultvalue', $old)) {
                $Table->InsertRecord(array("varname" => $k, "varvalue" => $v, "defaultvalue" => $v));
            } else {
                if ($old['defaultvalue'] != $v) {
                    $Table->UpdateRecord(array("varname" => $k, "defaultvalue" => $v));
                }
            }
            if (isset($old['varvalue']))
                $var[$k] = $old['varvalue'];
        }
    }

    return $var;
}

/**
 *
 * @global array $_FN
 * @param string file
 * @param string $sectionid
 * @return array 
 */
function FN_LoadConfig($fileconfig = "", $sectionid = "", $usecache = true)
{
    global $_FN;
    static $cache = array();
    if (!$usecache)
        $cache = array();
    $tablename = "";
    //---------------------------- empty fileconfig --------------------------->
    $fileconfig_fullpath = $fileconfig;
    if ($fileconfig != "" && !file_exists("$fileconfig") && file_exists("{$_FN['src_finis']}/$fileconfig")) {
        $fileconfig_fullpath = "{$_FN['src_finis']}/$fileconfig";
    }
    //dprint_r($fileconfig_fullpath);
    $fileconfig = str_replace($_FN['src_finis'] . "/", "", $fileconfig);
    if ($fileconfig == "") {
        if ($_FN['block'] != "") {
            $blockvalues = FN_GetBlockValues($_FN['block']);
            $module = $blockvalues['type'];
            if (file_exists("{$_FN['src_finis']}/modules/$module/config.php")) {
                $fileconfig = "modules/{$module}/config.php";
                $fileconfig_fullpath = "{$_FN['src_finis']}/modules/{$module}/config.php";
            } else {
                $fileconfig = "blocks/{$_FN['block']}/config.php";
                $fileconfig_fullpath = "{$_FN['src_application']}/blocks/{$_FN['block']}/config.php";
            }
        } else {
            if (!empty($_FN['sectionvalues']['type'])) {
                $fileconfig = "modules/{$_FN['sectionvalues']['type']}/config.php";
                $fileconfig_fullpath = "{$_FN['src_finis']}/modules/{$_FN['sectionvalues']['type']}/config.php";
            } else {
                if ($sectionid == "") {
                    $sectionid = $_FN['mod'];
                }
                $fileconfig = "{$_FN['src_application']}/sections/{$sectionid}/config.php";
            }
        }
    }
    //---------------------------- empty fileconfig ---------------------------<

    if (preg_match("/^blocks/is", $fileconfig) || preg_match("/^sections/is", $fileconfig) || preg_match("/^modules/is", $fileconfig)) {
        if ($_FN['block'] != "") {
            $sectionid = $_FN['block'];
            $tablename = "fncf_block_{$sectionid}";
        }

        if ($sectionid == "") {
            $sectionid = $_FN['mod'];
        }

        if ($sectionid !== "" && $_FN['block'] == "") {

            $tablename = "fncf_{$sectionid}";
            if (file_exists("{$_FN['src_application']}/sections/$sectionid/default.xml.php")) {

                $sectionvalues = FN_GetSectionValues($sectionid);
                if (!empty($sectionvalues['type'])) {
                    $default = xmetadb_xml2array(file_get_contents("{$_FN['src_application']}/sections/$sectionid/default.xml.php"), "fncf_{$sectionvalues['type']}");
                    $default = (isset($default[0]) && is_array($default[0])) ? $default[0] : array();
                }
            }
        }
    } else {
        if ($fileconfig === "config.php" || $fileconfig === "./config.php") {
            $tablename = "fn_settings";
        } else {
            $tablename = str_replace("/", "_s_", dirname($fileconfig));
            $tablename = str_replace("\\", "_b_", $tablename);
            $tablename = str_replace(".", "_d_", $tablename);
        }
    }

    // dprint_r_arrayxml($tablename);
    // @ob_end_flush();

    if (!empty($cache["$tablename"])) {

        return $cache["$tablename"];
    }


    if ($tablename != "" && !file_exists("{$_FN['datadir']}/fndatabase/$tablename.php")) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<?php exit(0);?>
<tables>
	<field>
		<name>varname</name>
		<type>string</type>
		<frm_help_it></frm_help_it>
		<frm_required>1</frm_required>
		<primarykey>1</primarykey>
	</field>
	<field>
		<name>varvalue</name>
		<type>string</type>
	</field>
	<field>
		<name>defaultvalue</name>
		<type>string</type>
		<frm_show>0</frm_show>
	</field>
	<filename>settings</filename>
</tables>";
        FN_Write($xml, "{$_FN['datadir']}/fndatabase/$tablename.php");
    }

    $config = array();
    $fields = false;

    if (file_exists($fileconfig_fullpath)) {
        include "$fileconfig_fullpath";
        if (!empty($default) && is_array($default)) {
            $config = array_merge($config, $default);
        }
        $fields = array_keys($config);
    }
    if ($tablename != "") {
        FN_LoadVarsFromTable($config, $tablename, $fields);

        $cache[$tablename] = $config;
    }



    if (isset($config['id'])) {
        unset($config['id']);
    }


    return $config;
}

function FN_NormalizeAllPaths($content)
{
    // Regular expression to match href and src attributes
    $pattern = '/(\s(?:href|src)\s*=\s*)([\"\'])([^\"\']+)\2/i';

    // Use preg_replace_callback to process each match
    $normalizedContent = preg_replace_callback($pattern, function ($matches) {
        $attribute = $matches[1];  // href= or src=
        $quote = $matches[2];      // ' or "
        $path = $matches[3];       // The actual URL/path
        // Check if the path is a URL with a protocol
        if (preg_match('~^(?:f|ht)tps?://~i', $path)) {
            // If it's a URL, don't normalize it
            $normalizedPath = $path;
        } else {
            // If it's not a URL, normalize the path
            $normalizedPath = FN_NormalizePath($path);
        }

        // Reconstruct the attribute with the normalized path
        return $attribute . $quote . $normalizedPath . $quote;
    }, $content);

    return $normalizedContent;
}

function FN_NormalizePath($path)
{
    // Check if the path is absolute or starts with ../
    $isAbsolute = strpos($path, '/') === 0;
    $startsWithParentDir = strpos($path, '../') === 0;

    // Split the path into parts using '/' as delimiter
    $parts = explode('/', $path);

    // Initialize an array to hold the normalized parts
    $normalizedParts = [];

    // Count the number of initial '../' sequences
    $parentDirCount = 0;
    while (isset($parts[$parentDirCount]) && $parts[$parentDirCount] === '..') {
        $parentDirCount++;
    }

    // Add the initial '../' sequences to the normalized parts
    for ($i = 0; $i < $parentDirCount; $i++) {
        $normalizedParts[] = '..';
    }

    // Iterate through the remaining parts of the path
    for ($i = $parentDirCount; $i < count($parts); $i++) {
        $part = $parts[$i];
        // Ignore any '.' as it refers to the current directory
        if ($part === '.' || $part === '') {
            continue;
        }

        // If '..' is found, remove the last directory from the normalized path
        if ($part === '..') {
            if (!empty($normalizedParts) && end($normalizedParts) !== '..') {
                array_pop($normalizedParts);
            } else {
                // If we can't go up anymore, add '..' to the path
                $normalizedParts[] = '..';
            }
        } else {
            // Add the current part to the normalized path
            $normalizedParts[] = $part;
        }
    }

    // Join the normalized parts back into a path
    $normalizedPath = implode('/', $normalizedParts);

    // If the original path was absolute, prepend '/' to the normalized path
    if ($isAbsolute) {
        $normalizedPath = '/' . $normalizedPath;
    }

    return $normalizedPath;
}

function FN_RewriteLinksAbsoluteToLocal($content, $directory)
{
    global $_FN;
    $baseURL = $_FN['siteurl'];
    $targetDirURL = $baseURL . $directory . "/";
    $BaseUri = FN_extractBaseUrl($baseURL);
    $targetDirURLnoprotocol = str_replace($BaseUri, "", $targetDirURL);
    $firstpart = str_replace($BaseUri, "", $_FN['siteurl']);

    // Calculate the relative path depth
    $relativePath = str_repeat("../", count(array_filter(explode("/", $directory))));
    // Use regular expressions to target only href and src attributes
    $content = preg_replace_callback(
        '/(href|src)=(["\'])((?:' . preg_quote($targetDirURL, '/') . '|' . preg_quote($targetDirURLnoprotocol, '/') . ').*?)\2/i',
        function ($matches) use ($targetDirURL, $targetDirURLnoprotocol) {
            $url = $matches[3];
            $url = str_replace([$targetDirURL, $targetDirURLnoprotocol], '', $url);
            return $matches[1] . '=' . $matches[2] . $url . $matches[2];
        },
        $content
    );

    // Replace remaining absolute URLs with calculated relative paths
    $content = preg_replace_callback(
        '/(href|src)=(["\'])(' . preg_quote($baseURL, '/') . '|' . preg_quote($firstpart, '/') . ')(.*?)\2/i',
        function ($matches) use ($relativePath) {
            return $matches[1] . '=' . $matches[2] . $relativePath . $matches[4] . $matches[2];
        },
        $content
    );
    return $content;
}

function FN_extractBaseUrl($url)
{
    // Parse the URL
    $parsedUrl = parse_url($url);

    // Check if scheme and host are present
    if (!isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
        return false; // Invalid URL
    }

    // Construct the base URL
    $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

    // If there's a port, add it
    if (isset($parsedUrl['port'])) {
        $baseUrl .= ':' . $parsedUrl['port'];
    }

    return $baseUrl;
}

/**
 *
 * @param string $email
 * @return bool 
 */
function FN_CheckMail($email)
{
    if (preg_match('/^([a-z0-9_\.-])+@(([a-z0-9_-])+\.)+[a-z]{2,128}$/si', trim($email)))
        return true;
    else
        return false;
}

/**
 *
 * @param string $filecontents
 * @param string $filename
 * @param string $HeaderContentType ï¿½
 */
function FN_SaveFile($filecontents, $filename, $HeaderContentType = "application/force-download")
{
    while (
        false !== @ob_end_clean()
    );
    if (!$filename) {
        $filename = "export";
    }
    header("Content-Type: $HeaderContentType");
    header("Content-Disposition: inline; filename=$filename");
    echo "$filecontents";
    die();
}

/**
 * 
 * @param type $filename
 * @param type $delimiter
 * @param type $enclosure
 * @return type
 */
function FN_ReadCsvDatabase($filename, $delimiter, $enclosure = '"')
{
    $row = 1;
    if (!file_exists($filename))
        return array();
    $handle = fopen("$filename", "r");
    $ret = array();
    while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
        if ($row === 1) {
            foreach ($data as $k) {
                while (isset($keys[$k])) {
                    $k .= "_";
                }
                $keys[$k] = $k;
            }
            $row++;
        } else {
            $num = 0;
            $tmp = array();
            foreach ($keys as $k => $val) {
                $tmp[$k] = isset($data[$num]) ? $data[$num] : "";
                $num++;
            }
            if ($tmp)
                $ret[] = $tmp;
            $row++;
        }
    }
    fclose($handle);
    return $ret;
}

/**
 * @param string $str
 * @param string $charsetFrom
 * @param string $charsetTo
 */
function FN_ConvertEncoding($str, $charsetFrom, $charsetTo)
{
    $str_ret = @XMETADB_ConvertEncoding($str, $charsetFrom, $charsetTo);
    if ($str_ret != "")
        return $str_ret;
    return $str;
}

/**
 *
 * @global array $_FN
 * @param string $str
 * @return string 
 */
function FN_FixEncoding($str)
{
    global $_FN;
    $charsetpage = empty($_FN['charset_page']) ? "UTF-8" : $_FN['charset_page'];
    return XMETADB_FixEncoding($str, $charsetpage);
}

/**
 *
 * @param type $url 
 */
function FN_Redirect($url)
{
    while (
        false !== ob_get_clean()
    );
    header("location:$url");
    die();
}

/**
 * 
 */
function FN_ClearCache()
{
    global $_FN;
    FN_RemoveDir("{$_FN['datadir']}/_cache");
    mkdir("{$_FN['datadir']}/_cache");
}

/**
 * 
 * @staticvar int $level
 * @param type $var
 */
function dprint_r_arrayxml($var)
{
    static $level = 0;
    if ($level == 0)
        echo "<pre style='border:1px solid red'>";
    if (is_array($var)) {
        echo "\narray{\n";
        foreach ($var as $k => $v) {
            echo "\t[$k]{\n";
            $level++;

            dprin_r_arrayxml($v);
            $level--;
            echo "\n\t}\n";
        }
        echo "\n}\n";
    } else {
        if (is_string($var))
            echo htmlspecialchars($var);
        else
            print_r($var);
    }
    if ($level == 0)
        echo "</pre>";
}

function FN_GetOpenAuthProviders()
{
    global $_FN;
    if (!file_exists("{$_FN['datadir']}/fndatabase/fn_oauth_providers.php")) {
        return array();
    }
    $table = FN_XMDBTable("fn_oauth_providers");
    $recs = $table->GetRecords(array("enabled" => 1));
    if ($recs) {
        foreach ($recs as $k => $rec) {
            $recs[$k]['urlimage'] = $table->getFilePath($rec, "avatar");
            $recs[$k]['url'] = $_FN['siteurl'] . "{$_FN['selfscript']}?fnloginprovider=" . $rec['id'];
        }
    }
    return $recs ? $recs : array();
}

/**
 * Get the path to a file in the site
 * 
 * @param string $filepath File path
 * @param bool $urlAbsolute Whether to return absolute URL
 * @return string Path to the file
 */
function FN_PathSite($filepath, $urlAbsolute = false)
{
    return FN_FinisPathToApplicationPath($filepath, $urlAbsolute);
}

function FN_FinisPathToApplicationPath($filepath, $urlAbsolute = false)
{
    global $_FN;
    if ($filepath[0] != "{$_FN['slash']}") {
        $filepath = $_FN['src_finis'] . "{$_FN['slash']}$filepath";
    }
    $siteRoot = $_FN['src_application'];
    $sourceRoot = dirname(__DIR__);

    // Normalize path separators
    $ds = DIRECTORY_SEPARATOR;
    $normalizedSourceRoot = str_replace($_FN['slash'], $ds, $sourceRoot);
    $normalizedFilepath = str_replace($_FN['slash'], $ds, $filepath);
    // Get relative path
    $escapedSourceRoot = preg_quote($normalizedSourceRoot, '/');
    $relPath = preg_replace("{$_FN['slash']}" . '^' . $escapedSourceRoot . $_FN['slash'], '', $normalizedFilepath);
    $relPath = ltrim($relPath, $ds);
    $relPath = str_replace($ds, $_FN['slash'], $relPath);

    // Construct destination path
    $destPath = FN_NormalizePath("$siteRoot{$_FN['slash']}$relPath");

    // Copy file if it doesn't exist
    if (!file_exists($destPath)) {
        FN_Copy($filepath, $destPath, true);
    }
    if (is_dir($filepath)) {
        $listFiles = glob("$filepath" . DIRECTORY_SEPARATOR . "*");
        foreach ($listFiles as $file) {
            if (!is_dir($file)) {
                FN_FinisPathToApplicationPath($file);
            }
        }
    }


    // Return absolute URL if requested
    return $urlAbsolute ? $_FN['siteurl'] . $relPath : $relPath;
}

function FN_getCountryFlag($countryCode, $UnicodeCh = false)
{
    // Convert country code to lowercase
    if ($countryCode == "en") {
        $countryCode = "GB";
    }
    $countryCode = strtoupper($countryCode);

    // Check if the country code is valid (2 characters)
    if (strlen($countryCode) === 2) {
        $flag = '';
        $offset = 127397; // Offset Unicode per convertire lettere in emoji regionali
        // Split the country code into an array of characters
        $chars = str_split($countryCode);
        foreach ($chars as $char) {
            $codePoint = ord($char) + $offset;

            // Decide the flag format based on the UnicodeCh parameter
            if ($UnicodeCh) {
                // Decode Unicode character
                $flag .= json_decode('"\u' . dechex($codePoint) . '"');
            } else {
                // Encode HTML entity
                $flag .= '&#x' . strtoupper(dechex($codePoint)) . ';';
            }
        }

        return $flag;
    }

    return $countryCode; // Return country code if invalid
}

/**
 * 
 * @global type $_FN
 * @param type $path
 * @param type $force
 */
function FN_Install($path, $force = false)
{
    global $_FN;
    $path_site = str_replace("/fndatabase/", "/{$_FN['database']}/", $path);
    $path_site = preg_replace('/^misc\//', "{$_FN['datadir']}/", $path_site);
    if (!file_exists("{$_FN['src_application']}/$path") || $force == true) {
        if (file_exists("{$_FN['src_finis']}/include/install/$path")) {
           
            FN_Copy("{$_FN['src_finis']}/include/install/$path", "{$_FN['src_application']}/$path_site");
            //die("{$_FN['src_application']}/$path_site");
        }
    }
}
