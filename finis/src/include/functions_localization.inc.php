<?php


/**
 * translate the string
 *
 * @param string $constant
 * @param string $lang
 * @param string $mode
 * @return string
 */
function FN_i18n($constant, $language = "", $uppercasemode = "")
{
    global $_FN, $_FNMESSAGE;
    $ebabledb = false;
    $old = false;
    $constant_clean = strtolower($constant);
    $lang = $_FN['lang'];
    if ($language == "")
        $language = $lang;
    if (!isset($_FNMESSAGE[$language]))
    {
        $_FNMESSAGE[$language] = FN_GetMessagesFromCsv("{$_FN['src_finis']}/languages/$language/lang.csv");
    }

    $text = "";
    if ($constant != "")
    {
        if (isset($_FNMESSAGE[$language][$constant]))
        {
            $text = $_FNMESSAGE[$language][$constant];
        }
        elseif (isset($_FNMESSAGE[$language][$constant]))
        {
            $text = $_FNMESSAGE[$language][$constant];
        }
        elseif (isset($_FNMESSAGE[$language][$constant_clean]))
        {
            $text = $_FNMESSAGE[$language][$constant_clean];
        }
        else
        {
            $text = "" . str_replace("_", " ", $constant);
            $text = "$text";
        }
    }
    switch ($uppercasemode)
    {
        case "";
            break;
        case "Aa":
            $text = ucfirst($text);
            break;
        case "aa":
            $text = strtolower($text);
            break;
        case "AA":
            $text = strtoupper($text);
            break;
        case "Aa Aa":
            $text = ucwords($text);
            break;
    }
    $text = FN_ConvertEncoding($text, $_FN['charset_lang'], $_FN['charset_page']);
    return $text;
}

/**
 *
 * @param string $english_string
 * @param string $uppercasemode
 * @param string $language
 * @return string 
 */
function FN_Translate($english_string, $uppercasemode = "Aa", $language = "")
{
    return FN_i18n($english_string, $language, $uppercasemode);
}



/**
 *
 * @param string $filename
 */
function FN_GetMessagesFromCsv($filename)
{
    static $messages = array();
    if (!file_exists($filename))
        return $messages;
    if (isset($messages[$filename]))
        return $messages[$filename];
    $messages[$filename] = array();
    $first = true;
    $handle = fopen("$filename", "r");
    while (($data = fgetcsv($handle, 5000, ",")) !== false)
    {
        if ($first == true)
        {
            $first = false;
            continue;
        }
        if (isset($data[1]))
        {
            $messages[$filename][$data[0]] = $data[1];
        }
    }
    fclose($handle);
    return $messages[$filename];
}



/**
 *
 * @global array $_FN
 * @global array $_FNMESSAGES
 * @param string $filename
 */
function FN_LoadMessagesFolder($folder)
{
    global $_FNMESSAGE, $_FN;
    $tmp = FN_GetMessagesFromFolder($folder);
    if (is_array($tmp))
    {
        foreach ($tmp as $k => $v)
        {
            $_FNMESSAGE[$_FN['lang']][$k] = $v;
        }
    }
    if (!empty($_FNMESSAGE[$_FN['lang']]["_CHARSET"]))
    {
        $_FN['charset_lang'] = $_FNMESSAGE[$_FN['lang']]["_CHARSET"];
    }
}


/**
 *
 * @global array $_FN
 * @param string $folder
 * @param string $lang
 * @return array 
 */
function FN_LoadMessagesFromFolder($folder, $lang)
{
    global $_FN;
    $messages = array();
    if (file_exists("$folder/languages/$lang/lang.csv"))
    {
        $messages = FN_GetMessagesFromCsv("$folder/languages/$lang/lang.csv");
    }
    else
    if (file_exists("$folder/languages/{$_FN['lang_default']}/lang.csv"))
    {
        $messages = FN_GetMessagesFromCsv("$folder/languages/{$_FN['lang_default']}/lang.csv");
    }
    else
    if (file_exists("$folder/languages/en/lang.csv"))
    {
        $messages = FN_GetMessagesFromCsv("$folder/languages/en/lang.csv");
    }
    return $messages;
}



/**
 *
 * @global array $_FN
 * @param string $lang
 * @return string 
 */
function FN_LangSuffix($lang = "")
{
    global $_FN;
    if ($lang == "")
        $lang = $_FN['lang'];
    /* 	if ( $lang == $_FN['lang_default'] )
      return "";
      else */
    return "_$lang";
}


