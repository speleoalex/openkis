<?php

/**
 * Get Sections
 *
 * @global array $_FN
 * @param string $section
 * @param array $recursive
 * @param bool $onlyreadable
 * @param bool $hidden
 * @param bool $onlyenabled
 * @param type $nocache
 * @return array 
 */
function FN_GetSections($section = "", $recursive = false, $onlyreadable = true, $hidden = false, $onlyenabled = true, $nocache = false)
{
    global $_FN;
    static $cache = false;
    static $allsections = false;
    if ($nocache || !$allsections)
    {
        if (empty($_FN['sections']) || $nocache)
        {
            $_FN['sections'] = false;
            $_FN['sections'] = FN_GetAllSections();
        }
        $cache = array();
        $allsections = $_FN['sections'];
    }
    if ($section === false)
        $section = "";
    $idcache = $section . "|" . $recursive . "|" . $onlyreadable . "|" . $hidden . "|" . $onlyenabled;
    if (isset($cache[$idcache]))
    {
        return $cache[$idcache];
    }
    $sect_db = array();
//---------------------   get all sections from database   -------------------->


    if ($recursive)
    {
        $sections = $allsections;
    }
    else
    {
        foreach ($allsections as $sectionvalues)
        {
            $parents[$sectionvalues['parent']][] = $sectionvalues;
        }
        $sections = isset($parents[$section]) ? $parents[$section] : array();
    }


//---------------------   get all sections from database   --------------------<

    foreach ($sections as $sectionvalues)
    {

        if (!file_exists("{$_FN['src_application']}/sections/{$sectionvalues['id']}"))
        {
            continue;
        }
        //only readable
        if ($onlyreadable)
        {
            if (!FN_UserCanViewSection($sectionvalues['id']))
                continue;
        }
//not hidden
        if (!$hidden)
        {
            if (!empty($sectionvalues['hidden']))
                continue;
        }
//sections enabled
        if ($onlyenabled)
        {
            if (!FN_SectionIsEnabled($sectionvalues['id']))
                continue;
        }

        $sectionvalues['link'] = FN_RewriteLink("index.php?mod={$sectionvalues['id']}");
        $suffix = FN_LangSuffix();

        if (empty($sectionvalues["title" . $suffix]))
        {
            if ($_FN['lang'] == $_FN['lang_default'] && empty($sectionvalues["title"]))
            {
                $sectionvalues['title'] = "_{$_FN['lang_default']}_ $suffix __" . FN_GetFolderTitle("{$_FN['src_application']}/sections/{$sectionvalues['id']}");
            }
        }
        else
            $sectionvalues['title'] = FN_ConvertEncoding($sectionvalues["title" . FN_LangSuffix()], "UTF-8", $_FN['charset_page']);
        $title = $sectionvalues['title'];
        if (empty($sectionvalues['image']))
            $sectionvalues['image'] = FN_FromTheme("{$_FN['src_application']}/sections/{$sectionvalues['id']}/icon.png", false);
        if (!file_exists($sectionvalues['image']))
            $sectionvalues['image'] = FN_FromTheme("images/section.png", false);
        $siteurl = empty($_FN['use_urlserverpath']) ? $_FN['siteurl'] : $_FN['sitepath'];
        $sectionvalues['image'] = $siteurl . $sectionvalues['image'];
        FN_GetAccessKey($title, "index.php?mod={$sectionvalues['id']}", $sectionvalues['accesskey']);
        $sect_db[$sectionvalues['id']] = $sectionvalues;
    }
    //dprint_r( $sect_db);
    //------------------make section tree-------------------------------------->
    foreach ($sect_db as $section)
    {
        $pathParents = array();
        $parentId = $section['parent'];
        while ($parentId != "" && !in_array($parentId, $pathParents))
        {
            $pathParents[] = $parentId;
            $parentId = isset($sect_db[$parentId]['parent']) ? $sect_db[$parentId]['parent'] : "";
        }
        $sect_db[$section['id']]['path'] = array_reverse($pathParents);
    }
    //------------------make section tree--------------------------------------<
    $cache[$idcache] = $sect_db;

    return $sect_db;
}

/**
 *
 * @param string $section
 * @return array
 */
function FN_GetSectionValues($section, $usecache = true)
{
    global $_FN;
    static $cache = array();
    static $cachesections = false;
    if (!$usecache)
    {
        $_FN['sections'] = FN_GetAllSections();
        $cachesections = false;
    }
    if ($usecache && isset($cache[$_FN['lang']][$section]))
    {
        return $cache[$_FN['lang']][$section];
    }
    if (!$cachesections)
    {
        $cachesections = array();
        $cachesections = $_FN['sections'];
    }
    if (!isset($cachesections[$section]))
    {
        return false;
    }
    $values = $cachesections[$section];
    if (empty($values["title" . FN_LangSuffix()]))
    {
        if ($_FN['lang'] != FN_LangSuffix() && $values["title"] == "")
        {
            $values['title'] = FN_GetFolderTitle("{$_FN['src_application']}/sections/$section");
        }
    }
    else
    {
        $values['title'] = $values["title" . FN_LangSuffix()];
    }
    $values['link'] = FN_RewriteLink("index.php?mod={$values['id']}", "", true);

    if (empty($values['type']) && !empty($_FN['default_section_type']))
    {
        $values['type'] = $_FN['default_section_type'];
    }

    $cache[$_FN['lang']][$section] = $values;

    return $values;
}

/**
 * 
 * @global array $_FN
 * @return type
 */
function FN_GetAllSections()
{
    global $_FN;
    if (!empty($_FN['sections']))
    {
        return $_FN['sections'];
    }
    $table = FN_XMDBForm("fn_sections");
    $all = $table->xmltable->GetRecords();
    if (!is_array($all))
        return array();
    $all = xmetadb_array_natsort_by_key($all, "position");
    $allByKey = array();
    $suffix = FN_LangSuffix();

    foreach ($all as $item)
    {
        $allByKey[$item['id']] = $item;
        if (!empty($allByKey[$item['id']]['title' . $suffix]))
            $allByKey[$item['id']]['title'] = $allByKey[$item['id']]['title' . $suffix];
        if (!empty($allByKey[$item['id']]['description' . $suffix]))
            $allByKey[$item['id']]['description'] = $allByKey[$item['id']]['description' . $suffix];
    }
    $_FN['sections'] = $allByKey;
    return $allByKey;
}

/**
 * 
 * @global array $_FN
 * @return type
 */
function FN_GetAllSectionTypes()
{
    global $_FN;
    if (!empty($_FN['sectionstypes']))
    {
        return $_FN['sectionstypes'];
    }

    $table = FN_XMDBForm("fn_sectionstypes");
    $all = $table->xmltable->GetRecords();
    if (!is_array($all))
        return array();

    $allByKey = array();
    foreach ($all as $item)
    {
        $allByKey[$item['name']] = $item;
    }
    $_FN['sectionstypes'] = $allByKey;
    return $allByKey;
}

/**
 * Init Sections
 *
 * @global array $_FN
 * @return array 
 */
function FN_InitSections()
{
    global $_FN;
//sections in database -------------------------------------------------------->
    $sections = $_FN['sections'];
    $flag_mod = false;
    $flag_mod_st = false;
    $sect_db = array();
    $posmax = 0;
    if (is_array($sections))
    {
        foreach ($sections as $section)
        {
            if (!file_exists("{$_FN['src_application']}/sections/{$section['id']}"))
            {
                $table = FN_XMDBForm("fn_sections");
                $table->xmltable->DelRecord($section['id']);
                $flag_mod = true;
                continue;
            }
            $sect_db[$section['id']] = $section;
            if ($section['position'] >= $posmax)
                $posmax = $section['position'];
        }
    }
//sections in database --------------------------------------------------------<
//sections in filesystem ------------------------------------------------------>
    $sectionsdirs = glob("{$_FN['src_application']}/sections/*");
    $sections = array();
    foreach ($sectionsdirs as $section)
    {
        $tmp = array();
        if (is_dir($section))
        {
            $section = basename($section);
            if (isset($sect_db[$section]))
                continue;
            $defaultxmlfile = file_exists("{$_FN['src_application']}/sections/$section/default.xml.php") ? "{$_FN['src_application']}/sections/$section/default.xml.php" : "{$_FN['src_application']}/sections/$section/default.xml.php";
            if (file_exists($defaultxmlfile))
            {
                $default = xmetadb_xml2array(file_get_contents($defaultxmlfile), "fn_sections");
                if (isset($default[0]) && is_array($default[0]))
                {
                    $tmp = $default[0];
                }
            }
            $tmp['id'] = $section;
            foreach ($_FN['listlanguages'] as $l)
            {
                if (file_exists("{$_FN['src_application']}/sections/$section/title.$l.fn"))
                {
                    $tmp['title_' . $l] = file_get_contents("{$_FN['src_application']}/sections/$section/title.$l.fn");
                }
                elseif (file_exists("{$_FN['src_application']}/sections/$section/title.i18n.fn"))
                {
                    $tmp['title_' . $l] = FN_Translate(file_get_contents("{$_FN['src_application']}/sections/$section/title.i18n.fn"), "Aa", $l);
                }
            }
            $tmp['title'] = isset($tmp['title_' . $_FN['lang_default']]) ? $tmp['title_' . $_FN['lang_default']] : $section;
            $tmp['link'] = FN_RewriteLink("index.php?mod=$section");
            foreach ($_FN['listlanguages'] as $lang)
            {
                if (file_exists("{$_FN['src_application']}/sections/$section/title.{$lang}.fn"))
                    $tmp["title" . FN_LangSuffix($lang)] = file_get_contents("{$_FN['src_application']}/sections/$section/title.{$lang}.fn");
            }
            $tmp['status'] = empty($tmp['status']) ? 1 : $tmp['status'];
            $tmp['sectionpath'] = "sections";
            if (!isset($sect_db[$tmp['id']]))
            {
                if (empty($tmp['position']))
                {
                    $tmp['position'] = $posmax + 1;
                    $posmax++;
                }
                $table = FN_XMDBForm("fn_sections");
                $ret = $table->xmltable->InsertRecord($tmp);
                $flag_mod = true;
            }
        }
    }


//sections in filesystem ------------------------------------------------------>
//------------- modules  ------------------------------------------------------>
    $sectionstypes = glob("{$_FN['src_finis']}/modules/*");
    foreach ($sectionstypes as $sectiontype)
    {
        if (is_dir($sectiontype))
        {
            $sectiontype = basename($sectiontype);
            if (!isset($_FN['sectionstypes'][$sectiontype]))
            {
                $tmp = array();
                $defaultxmlfile = file_exists("{$_FN['src_finis']}/modules/$sectiontype/default.xml.php") ? "{$_FN['src_finis']}/modules/$sectiontype/default.xml.php" : "{$_FN['src_finis']}/modules/$sectiontype/default.xml";
                if (file_exists("$defaultxmlfile"))
                {
                    $default = xmetadb_xml2array(file_get_contents("$defaultxmlfile"), "fncf_$sectiontype");
                    //$default=xmetadb_xml2array(file_get_contents("$defaultxmlfile"),"fn_sectionstype");
                    if (isset($default[0]) && is_array($default[0]))
                    {
                        $tmp = $default[0];
                    }
                }
                $tmp['name'] = $sectiontype;
                if (empty($tmp['title']))
                    $tmp['title'] = str_replace("_", " ", $tmp['name']);
                $flag_mod_st = true;
                $table = FN_XMDBTable("fn_sectionstypes");
                $table->InsertRecord($tmp);
            }
        }
    }
    $sectionstypes_local = glob("{$_FN['src_application']}/modules/*");        
    foreach ($sectionstypes_local as $sectiontype)
    {
        if (is_dir($sectiontype))
        {
            $sectiontype = basename($sectiontype);
            if (!isset($_FN['sectionstypes'][$sectiontype]))
            {
                $tmp = array();
                $defaultxmlfile = file_exists("{$_FN['src_application']}/modules/$sectiontype/default.xml.php") ? "{$_FN['src_application']}/modules/$sectiontype/default.xml.php" : "{$_FN['src_application']}/modules/$sectiontype/default.xml";
                if (file_exists("$defaultxmlfile"))
                {
                    $default = xmetadb_xml2array(file_get_contents("$defaultxmlfile"), "fncf_$sectiontype");
                    if (isset($default[0]) && is_array($default[0]))
                    {
                        $tmp = $default[0];
                    }
                }
                $tmp['name'] = $sectiontype;
                if (empty($tmp['title']))
                    $tmp['title'] = str_replace("_", " ", $tmp['name']);
                $flag_mod_st = true;
                $table = FN_XMDBTable("fn_sectionstypes");
                $table->InsertRecord($tmp);
            }
        }
    }   
    $sectionstypes = $_FN['sectionstypes'];
    foreach ($sectionstypes as $sectiontype)
    {
        if (!is_dir("{$_FN['src_finis']}/modules/" . $sectiontype['name']) && !is_dir("{$_FN['src_application']}/modules/" . $sectiontype['name']))
        {
            $flag_mod_st = true;
            $table = FN_XMDBTable("fn_sectionstypes");
            //$table->DelRecord($sectiontype['name']);
        }
    }
    
    if ($flag_mod)
    {
        $_FN['sections'] = FN_GetAllSections();
    }


    if ($flag_mod_st)
    {
        $_FN['sectionstypes'] = FN_GetAllSectionTypes();
    }
//------------- modules  ------------------------------------------------------<
    return $sections;
}

/**
 * 
 * @global type $_FN
 * @param type $section
 * @return type
 */
function FN_GetSectionValuesAndLoadConfig($section = "")
{
    global $_FN;

    // Use the provided section or default to the module from the global configuration
    $section = $section ?: $_FN['mod'];

    // Retrieve section values
    $sectionValues = FN_GetSectionValues($section);

    // Load messages from the application section directory
    FN_LoadMessagesFolder("{$_FN['src_application']}/sections/{$section}");

    // If a type is specified, load messages from the corresponding module type directory
    if (!empty($sectionValues['type']))
    {
        FN_LoadMessagesFolder("{$_FN['src_finis']}/modules/{$sectionValues['type']}");
    }

    // Return the retrieved section values
    return $sectionValues;
}

/**
 * if user can view the page load html sections and the administrator options
 * 
 * @global array $_FN
 * @param string $section
 * @return string
 */
function FN_HtmlSection($section = "")
{

    global $_FN;
    static $sectioncontents = false;
    if ($sectioncontents !== false)
    {
        return $sectioncontents;
    }

    // Use the section parameter or default to the module from the global configuration
    $section = $section ?: $_FN['mod'];

    // Check if the user has permission to view the section
    if (!FN_UserCanViewSection($section))
    {
        return FN_i18n("you don't have permission to view this page");
    }

    $html = "";
    $htmlconfig = "";

    // Check if the current script is the index.php
    if (basename($_SERVER['SCRIPT_FILENAME']) === "index.php")
    {
        include_once __DIR__ . "/admin.php";
        $htmlconfig = FN_HtmlAdminOptions();
    }

    // Get the modcont parameter from the GET request
    $modcont = FN_GetParam("opt", $_GET, "flat");
    if ($modcont &&
            !file_exists($modcont) &&
            !file_exists("{$_FN['src_finis']}/$modcont") &&
            !file_exists(dirname("{$_FN['src_application']}/$modcont"))
            )
    {
        $modcont = false;
        
    }
    
    // Generate HTML based on the modcont parameter
    $html = $modcont ? FN_HtmlOnlineAdmin($modcont) : FN_RunSection($section, true) . $htmlconfig;
    $sectioncontents = $html;

    return $html;
}

/**
 * 
 * @param type $folder
 * @param type $return_html
 * @return string
 */
function FN_RunFolder($folder, $return_html)
{
    $filePath = "$folder/section.php";
    $output = "";

    // Check if the section.php file exists in the folder
    if (file_exists($filePath))
    {
        if ($return_html)
        {
            // Start output buffering to capture the included file's output
            ob_start();
            include_once $filePath;
            // Get the buffered output and clean the buffer
            $output = ob_get_clean();
        }
        else
        {
            // Simply include the file if no HTML return is required
            include_once $filePath;
        }
    }
    else
    {
        // Generate static HTML content if section.php does not exist
        $output = FN_HtmlStaticContent($folder);
        if (!$return_html)
        {
            // Output the content immediately if not returning HTML
            echo $output;
            $output = ""; // Reset output since it's already displayed
        }
    }

    // Return the output HTML if required
    return $output;
}

/**
 * 
 * @global array $_FN
 * @param type $section
 * @param type $return_html
 * @return string
 */
function FN_RunSection($section, $return_html)
{
    global $_FN;
    $sectionvalues = FN_GetSectionValuesAndLoadConfig($section);
    // Determine the folder path for the section or module
    if (!empty($sectionvalues['type']) && file_exists("{$_FN['src_application']}/modules/{$sectionvalues['type']}/section.php"))
    {
        $folder = "{$_FN['src_application']}/modules/{$sectionvalues['type']}";
    }
    elseif (!empty($sectionvalues['type']) && file_exists("{$_FN['src_finis']}/modules/{$sectionvalues['type']}"))
    {
        $folder = "{$_FN['src_finis']}/modules/{$sectionvalues['type']}";
    }
    else
    {
        $folder = "{$_FN['src_application']}/sections/{$sectionvalues['id']}";
    }


    $str = "";

    // Check if the section.php file exists in the folder
    if (file_exists("$folder/section.php"))
    {
        if ($return_html)
        {

            // Start output buffering
            ob_start();            
            include_once "$folder/section.php";
            // Get the contents of the buffer
            $str = ob_get_clean();
            return $str;
        }
        else
        {
            // Directly include the file, outputting its content
            include_once "$folder/section.php";
        }
    }
    else
    {
        // Generate static HTML content if section.php does not exist
        $str = FN_HtmlStaticContent($folder, false);
    }
    if ($return_html)
        return $str;
    else
        echo $str;
    return "";
}

/**
 *
 * @param string $section
 * @return bool 
 */
function FN_SectionExists($section)
{
    $ret = FN_GetSectionValues($section);
    if (isset($ret['id']))
        return true;
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $section_to_check_id
 * @param string $section
 * @return bool
 */
function FN_SectionIsInsideThis($section_to_check_id, $section = "")
{
    
    global $_FN;
    if ($section == "")
        $section = $_FN['mod'];
    $tmpsection = FN_GetSectionValues($section);
    $section_to_check = FN_GetSectionValues($section_to_check_id);
    while (isset($tmpsection['parent']) && $tmpsection['parent'] != false)
    {
        if (isset($tmpsection['parent']) && $tmpsection['parent'] == $section_to_check['id'])
        {
            return true;
        }
        if ($tmpsection['parent'] != $tmpsection['id'])
        {
            $tmpsection = FN_GetSectionValues($tmpsection['parent']);            
        }        
        else
        {
            return false;
        }
    }
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $section
 * @return array 
 */
function FN_GetSectionsTree($section = "")
{
    global $_FN;
    if ($section == "")
    {
        $section = $_FN['mod'];
    }
    if ($section == "")
    {
        return array();
    }
    $section = FN_GetSectionValues($section);
    if (!$section)
        return array();
    $section['active'] = true;
    $tree[] = $section;
    $parents = array();
    while ($section['parent'] != "")
    {
        $section = FN_GetSectionValues($section['parent']);
        $section['active'] = "";
        if (in_array($section['id'], $parents))
            break;
        $tree[] = $section;
        $parents[] = $section['id'];
    }
    $tree = array_reverse($tree);
    return $tree;
}

/**
 *
 * @param string $section
 */
function FN_SectionIsHidden($section = "")
{
    if ($section == "")
        $section = $_FN['mod'];
    $section = FN_GetSectionValues($section);
    if (!empty($section['hidden']))
        return true;
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $section
 * @return bool
 */
function FN_SectionIsEnabled($section = "")
{
    global $_FN;
    if ($section == "")
        $section = $_FN['mod'];
    $section = FN_GetSectionValues($section);
    if (empty($section['status']))
        return false;
    $curtime = FN_Time();
    if ($section['startdate'] != "" && $curtime < strtotime($section['startdate']))
    {
        return false;
    }
    if ($section['enddate'] != "" && $curtime > strtotime($section['enddate']))
    {
        return false;
    }
    return true;
}

/**
 *
 * @param string $sectiontitle
 */
function FN_MakeSectionId($sectiontitle)
{
    global $_FN;
    $sectionname = strtolower(str_replace(" ", "_", $sectiontitle));
    $sectionname = preg_replace("/" . @html_entity_decode("&agrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "a", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&egrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "e", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&igrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "i", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&ograve;", ENT_QUOTES, $_FN['charset_page']) . "/s", "o", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&ugrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "u", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Agrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "a", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Egrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "e", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Igrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "i", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Ograve;", ENT_QUOTES, $_FN['charset_page']) . "/s", "o", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Ugrave;", ENT_QUOTES, $_FN['charset_page']) . "/s", "u", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&aacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "a", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&eacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "e", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&iacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "i", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&oacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "o", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&uacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "u", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Aacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "a", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Eacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "e", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Iacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "i", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Oacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "o", $sectionname);
    $sectionname = preg_replace("/" . @html_entity_decode("&Uacute;", ENT_QUOTES, $_FN['charset_page']) . "/s", "u", $sectionname);
    $sectionname = preg_replace("/[^A-Z^a-z_0123456789]/s", "", $sectionname);
    $t = "";
    while (1)
    {
        $sectionname = $sectionname . $t;
        if (!FN_SectionExists($sectionname))
        {
            break;
        }
        else
        {
            if ($t == "")
                $t = 0;
        }
        $t++;
    }
    return $sectionname;
}

/**
 *
 * @global array $_FN
 * @param string $block
 * @return bool
 */
function FN_BlockIsEnabled($block)
{
    global $_FN;
    $block = FN_GetBlockValues($block);
    if (isset($_FN['sectionvalues']['blocks']) && !empty($_FN['sectionvalues']['blocksmode']))
    {
        $blocks = explode(",", $_FN['sectionvalues']['blocks']);
        if ($_FN['sectionvalues']['blocksmode'] == "hide")
        {
            if (in_array($block['id'], $blocks))
                return false;
        }
        elseif ($_FN['sectionvalues']['blocksmode'] == "show")
        {
            if (!in_array($block['id'], $blocks))
                return false;
        }
    }

    if (!empty($block['blocksmode']))
    {
        $sections = explode(",", $block['sections']);
        if ($block['blocksmode'] == "hide")
        {
            if (in_array($_FN['sectionvalues']['id'], $sections))
                return false;
        }
        elseif ($block['blocksmode'] == "show")
        {
            if (!in_array($_FN['sectionvalues']['id'], $sections))
                return false;
        }
    }

    if (empty($block['status']))
        return false;
    $curtime = FN_Time();
    if ($block['startdate'] != "" && $curtime < strtotime($block['startdate']))
    {
        return false;
    }
    if ($block['enddate'] != "" && $curtime > strtotime($block['enddate']))
    {
        return false;
    }
    return true;
}

/**
 * get html block
 * 
 * @param string $block
 * @return string 
 */
function FN_HtmlBlock($block)
{
    global $_FN;
    static $htmls = array();
    if (isset($htmls[$block]))
    {
        return $htmls[$block];
    }
    $_FN['block'] = $block;
    $blockvalues = FN_GetBlockValues($block);
    if (!empty($blockvalues['type']) && file_exists("{$_FN['src_application']}/modules/{$blockvalues['type']}") && FN_erg("^block_", $blockvalues['type']))
    {
        $html = FN_HtmlContent("{$_FN['src_application']}/modules/{$blockvalues['type']}");
    }
    elseif (!empty($blockvalues['type']) && file_exists("{$_FN['src_finis']}/modules/{$blockvalues['type']}") && FN_erg("^block_", $blockvalues['type']))
    {
        $html = FN_HtmlContent("{$_FN['src_finis']}/modules/{$blockvalues['type']}");
    }
    else
    {
        $html = FN_HtmlContent("{$_FN['src_application']}/blocks/$block");
    }
    $htmls[$block] = $html;
    $_FN['block'] = "";
    return $htmls[$block];
}

/**
 * 
 * @global array $_FN
 * @param type $newvalues
 */
function FN_UpdateDefaultXML($newvalues)
{
    global $_FN;

    if (is_writable("{$_FN['src_application']}/sections/{$newvalues['id']}"))
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?php exit(0);?>\n<fn_sections>\n";
        foreach ($newvalues as $k => $v)
        {
            if ($k !== "id" && !is_array($v))
                $xml .= "\t<$k>" . htmlentities("$v") . "</$k>\n";
        }
        $xml .= "</fn_sections>";
        //die ("{$_FN['datadir']}/fndatabase/fncf_{$newvalues['id']}.php");
        if ($newvalues['type'] != "" && file_exists("{$_FN['datadir']}/fndatabase/fncf_{$newvalues['id']}.php"))
        {

            $table = FN_XMDBTable("fncf_{$newvalues['id']}");
            $values = $table->GetRecords();
            $xml .= "\n";
            $xml .= "\n<fncf_{$newvalues['type']}>\n";
            foreach ($values as $k => $v)
            {
                $xml .= "\t<{$v['varname']}>" . htmlentities($v['varvalue']) . "</{$v['varname']}>\n";
            }
            $xml .= "</fncf_{$newvalues['type']}>";
        }
        file_put_contents("{$_FN['src_application']}/sections/{$newvalues['id']}/default.xml.php", $xml);
    }
}

/**
 *
 * @global array $_FN 
 */
function FN_FixSections()
{
    global $_FN;
    $sections = $_FN['sections'];
    $flag_mod = false;
    foreach ($sections as $section)
    {
        if ($section['parent'] != "")
        {
            if (!isset($sections[$section['parent']]))
            {
                $section['parent'] = "";
                $table = FN_XMDBTable("fn_sections");
                $table->UpdateRecord($section);
                $flag_mod = true;
            }
        }
    }
    if ($flag_mod)
    {
        $_FN['sections'] = FN_GetAllSections();
    }
}

/**
 * Init Sections
 * 
 * @global array $_FN
 * @return array 
 */
function FN_InitBlocks()
{
    global $_FN;
//sections in database
    $sect_db = $_FN['blocks'];
    $blocksdirs = glob("{$_FN['src_application']}/blocks/*");
    $blocks = array();
    $flag_mod = false;
//sections in filesystem
    foreach ($blocksdirs as $block)
    {
        $tmp = array();
        if (is_dir($block))
        {
            $block = basename($block);
            $tmp['where'] = "left";
            $defaultxmlfile = file_exists("{$_FN['src_application']}/blocks/$block/default.xml.php") ? "{$_FN['src_application']}/blocks/$block/default.xml.php" : "{$_FN['src_application']}/blocks/$block/default.xml";
            if (file_exists("$defaultxmlfile"))
            {
                $default = xmetadb_xml2array(file_get_contents("$defaultxmlfile"), "blocks");
                if (isset($default[0]) && is_array($default[0]))
                {
                    $tmp = $default[0];
                }
            }
            $tmp['id'] = $block;
            $tmp['title'] = $block;
            foreach ($_FN['listlanguages'] as $lang)
            {
                if (file_exists("{$_FN['src_application']}/blocks/$block/title.{$lang}.fn"))
                    $tmp["title" . FN_LangSuffix($lang)] = file_get_contents("{$_FN['src_application']}/blocks/$block/title.{$lang}.fn");
                elseif (file_exists("{$_FN['src_application']}/blocks/$block/title.i18n.fn"))
                {
                    $tmp['title_' . $lang] = FN_Translate(file_get_contents("{$_FN['src_application']}/blocks/$block/title.i18n.fn"), "Aa", $lang);
                }
            }
            $tmp['title'] = isset($tmp['title_' . $_FN['lang_default']]) ? $tmp['title_' . $_FN['lang_default']] : $tmp['title'];
            $tmp['status'] = empty($tmp['status']) ? 1 : $tmp['status'];
            if (!isset($sect_db[$tmp['id']]))
            {
                $table = FN_XMDBForm("fn_blocks");
                $table->xmltable->InsertRecord($tmp);
                $flag_mod = true;
            }
        }
    }
    if ($flag_mod)
    {
        $_FN['blocks'] = FN_GetAllBlocks();
    }
    return $blocks;
}

/**
 * 
 */
function FN_OnSitemapChange()
{
    global $_FN;
    if (file_exists("{$_FN['src_finis']}/include/on_site_change.d/") && false != ($handle = opendir("{$_FN['src_finis']}/include/on_site_change.d/")))
    {
        $filestorun = array();
        while (false !== ($file = readdir($handle)))
        {
            if (FN_GetFileExtension($file) == "php" && !preg_match("/^none_/si", $file))
                $filestorun[] = $file;
        }
        closedir($handle);
        FN_NatSort($filestorun);
        foreach ($filestorun as $runfile)
        {
            include ("{$_FN['src_finis']}/include/on_site_change.d/$runfile");
        }
    }
}

/**
 * 
 * @global array $_FN
 * @return type
 */
function FN_GetAllBlocks()
{
    global $_FN;
    if (!empty($_FN['blocks']))
        return $_FN['blocks'];
    $table = FN_XMDBForm("fn_blocks");
    $all = $table->xmltable->GetRecords();
    if (!is_array($all))
        return array();
    $all = xmetadb_array_natsort_by_key($all, "position");
    $allByKey = array();
    foreach ($all as $item)
    {
        $allByKey[$item['id']] = $item;
    }
    $_FN['blocks'] = $allByKey;
    return $allByKey;
}

/**
 *
 * @global array $_FN
 * @param string $where
 * @return array
 */
function FN_GetBlocks($where, $onlyreadable = true, $onlyenabled = true)
{
    global $_FN;
    $blocks = $_FN['blocks'];
    $ret_blocks = array();
    foreach ($blocks as $blockvalues)
    {
        if ($where != $blockvalues['where'])
        {
            continue;
        }
        if ($onlyreadable && FN_BlockIsEnabled($blockvalues['id']) == false)
        {
            continue;
        }
        if ($onlyenabled && FN_UserCanViewBlock($blockvalues['id']) == false)
        {
            continue;
        }
        //--language from module or section ----->
        FN_LoadMessagesFolder($_FN['src_application'] . "/blocks/{$blockvalues['id']}");
        if (!empty($blockvalues['type']))
        {
            FN_LoadMessagesFolder($_FN['src_finis'] . "/modules/{$blockvalues['type']}");
        }
        //--language from module or section -----<
        if (empty($blockvalues["title" . FN_LangSuffix()]))
        {
            $blockvalues['title'] = FN_GetFolderTitle("{$_FN['src_application']}/blocks/{$blockvalues['id']}");
        }
        else
            $blockvalues['title'] = $blockvalues["title" . FN_LangSuffix()];

        if ($blockvalues['hidetitle'])
            $blockvalues['title'] = "";
        $ret_blocks[$blockvalues['id']] = $blockvalues;
    }
    //dprint_r($ret_blocks);

    return $ret_blocks;
}

/**
 *
 * @param string $section
 * @return array
 */
function FN_GetBlockValues($section, $usecache = true)
{
    global $_FN;
    static $cache = array();
    static $cachesections = false;
    if (!$usecache)
    {
        $_FN['blocks'] = FN_GetAllBlocks();
        $cachesections = false;
        $cache = array();
    }
    if (isset($cache[$_FN['lang']][$section]))
    {
        return $cache[$_FN['lang']][$section];
    }
    if (!$cachesections)
    {
        $cachesections = $_FN['blocks'];
    }
    if (!isset($cachesections[$section]))
    {
        return false;
    }
    $values = $cachesections[$section];
    if (empty($values["title" . FN_LangSuffix()]))
    {
        $values['title'] = FN_GetFolderTitle("{$_FN['src_application']}/blocks/$section");
    }
    else
    {
        $values['title'] = $values["title" . FN_LangSuffix()];
    }
    $cache[$_FN['lang']][$section] = $values;
    return $values;
}
