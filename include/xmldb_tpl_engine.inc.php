<?php
/**
 * find <!-- $partname -->(.*)<!-- end$partname -->
 * 
 * @param type $partname
 * @param type $tp_str
 * @param type $default
 * @return type
 */
function TPL_GetHtmlPart($partname, $tp_str, $default = "")
{
    $out = array();
    if (preg_match("/<!-- $partname -->.*<!-- $partname -->/s", $tp_str))//se il nome del nodo contiene un elemento con lo stesso nome
    {
        $tmp = explode("<!-- $partname -->", $tp_str);

        $tmp = $tmp[1];
        if (false !== strpos($tmp, "<!-- end $partname -->"))
            $tmp = explode("<!-- end $partname -->", $tmp);
        elseif (false !== strpos($tmp, "<!-- end$partname -->"))
            $tmp = explode("<!-- end$partname -->", $tmp);
        elseif (false !== strpos($tmp, "<!-- end_$partname -->"))
            $tmp = explode("<!-- end_$partname -->", $tmp);
        if (is_array($tmp))
        {
            $tmp = $tmp[0];
            $tp_str = "<!-- $partname -->" . $tmp . "<!-- end $partname -->";
            return $tp_str;
        }
    }
    preg_match("/<!-- $partname -->(.*)<!-- end$partname -->/is", $tp_str, $out) || preg_match("/<!-- $partname -->(.*)<!-- end $partname -->/is", $tp_str, $out) || preg_match("/<!-- $partname -->(.*)<!-- end_$partname -->/is", $tp_str, $out)
    ;
    $tp_str = empty($out[0]) ? $default : $out[0];
    return $tp_str;
}
/**
 * 
 * @staticvar array $cache
 * @param type $partname
 * @param type $tp_str
 * @param type $default
 * @return string|array
 */
function TPL_GetHtmlParts($partname, $tp_str, $default = "")
{
    static $cache = array();
    $md5 = md5($partname . $tp_str . $default);
    if (isset($cache[$md5]))
    {
        return $cache[$md5];
    }
    $out = array();
    $ret = array();
    if (preg_match("/<!-- $partname -->.*<!-- $partname -->/s", $tp_str))//se il nome del nodo contiene un elemento con lo stesso nome
    {
        $tmp = explode("<!-- $partname -->", $tp_str);
        $i = 1;
        while (isset($tmp[$i]))
        {
            $tmp2 = $tmp[$i];
            if (false !== strpos($tmp2, "<!-- end $partname -->"))
                $tmp2 = explode("<!-- end $partname -->", $tmp2);
            elseif (false !== strpos($tmp2, "<!-- end$partname -->"))
                $tmp2 = explode("<!-- end$partname -->", $tmp2);
            if (is_array($tmp2))
            {
                $tmp2 = $tmp2[0];
                $tp_str = "<!-- $partname -->" . $tmp2 . "<!-- end $partname -->";
                $ret[] = $tp_str;
            }
            $i++;
        }
        return $ret;
    }
    preg_match("/<!-- $partname -->(.*)<!-- end$partname -->/is", $tp_str, $out) || preg_match("/<!-- $partname -->(.*)<!-- end $partname -->/is", $tp_str, $out);
    $tp_str = empty($out[0]) ? $default : $out[0];
    if ($tp_str)
    {
        return array(0 => $tp_str);
    }
    return array();
}
/**
 * 
 * @param type $partname
 * @param type $replace
 * @param type $tp_str
 * @param type $default
 * @return type
 */
function TPL_ReplaceHtmlPart($partname, $replace, $tp_str, $default = "")
{
    $tp_str_tmp = TPL_GetHtmlPart($partname, $tp_str, $default);
    $str_out = str_replace($tp_str_tmp, $replace, $tp_str);
    return $str_out;
}

/**
 * 
 * @global string $tpl_skeep
 * @param type $str
 * @return type
 */
function TPL_encode($str)
{
    global $tpl_skeep;
    if (!isset($str))
        return "";
    if (!$tpl_skeep)
        $tpl_skeep = "__skeep___graph_";
    $str = str_replace("{", $tpl_skeep, $str);
    return $str;
}

/**
 * 
 * @global string $tpl_skeep
 * @param type $str
 * @return type
 */
function TPL_decode($str)
{
    global $tpl_skeep;
    $str = str_replace($tpl_skeep, "{", $str);
    return $str;
}

/**
 * 
 * @param type $tplname
 * @param type $vars
 * @param type $config
 * @return type
 */
function TPL_ApplyTplFile($tplname, $vars, $config)
{
    $str = "";
    if (file_exists($tplname))
        $str = file_get_contents($tplname);
    $basepath = dirname($tplname) . "/";
    return TPL_ApplyTplString($str, $vars, $basepath, $config);
}

/**
 * 
 * @staticvar int $recursion
 * @param type $str
 * @param type $vars
 * @param type $basepath
 * @param type $config
 * @return type
 */
function TPL_ApplyTplString($str, $vars, $basepath = false, $config = array())
{
    global $_TPL_DEBUG;
    /*

      $config['lang_default']=isset($config['lang_default'])?$config['lang_default']:"";
      $config['siteurl']=isset($config['siteurl'])?$config['siteurl']:"";
      $config['lang']=isset($config['lang'])?$config['lang']:"";
      $config['enable_mod_rewrite']=isset($config['enable_mod_rewrite'])?$config['enable_mod_rewrite']:"";
      $config['use_urlserverpath']=isset($config['use_urlserverpath'])?$config['use_urlserverpath']:"";
      $config['sitepath']=isset($config['sitepath'])?$config['sitepath']:"";
     */
    static $recursion = 0;
    $recursion++;
    if ($recursion > 5)
    {
        $recursion--;
        return $str;
    }
    if (is_string($vars))
    {
        $vars=array("item"=>$vars);
    }
    foreach ($config as $k => $v)
    {
        if (!isset($vars[$k]) && !is_object($v))
        {
            $vars[$k] = $v;
        }
    }
    $arrayvars = array();
    $match = "";
    if (preg_match_all('/\{([a-zA-Z0-9_&]+)\}/m', $str, $match))
    {
        foreach ($match[1] as $tplvar)
        {
            $tplvar = str_replace("_&", "", $tplvar);
            $arrayvars[$tplvar] = null;
        }
    }
    foreach ($arrayvars as $k => $v)
    {
        if (isset($vars[$k]))
        {
            $arrayvars[$k] = $vars[$k];
        }
    }
    $old = "";
    {
        $str = str_replace("href='#", "ferh='#", $str);
        $str = str_replace("href=\"#", "ferh=\"#", $str);
        $str = str_replace("href=\"//", "ferh=\"//", $str);
        $str = str_replace("href='//", "ferh='//", $str);
        $str = str_replace("src='#", "rcs='#", $str);
        $str = str_replace("src=\"#", "rcs=\"#", $str);
        $str = str_replace("src=\"//", "rcs=\"//", $str);
        $str = str_replace("src='//", "rcs='//", $str);
        $siteurl = isset($config['siteurl']) ? $config['siteurl'] : "";
        $use_urlserverpath = isset($vars['use_urlserverpath']) ? $vars['use_urlserverpath'] : "";
        if (!empty($config['use_urlserverpath']))
            $siteurl = "http://____replace____/";
        if ($basepath)
        {
            if ($config['enable_mod_rewrite'] > 0 && $config['links_mode'] == "html")
            {
                if ($config['lang'] == $config['lang_default'])
                {
                    $str = preg_replace("/(href=\"index.php\?mod=)([A-Z0-9_]+)\"/is", "href=\"{$siteurl}\$2.html\"", $str);
                    $str = preg_replace("/(href='index.php\?mod=)([A-Z0-9_]+)'/is", "href=\"{$siteurl}\$2.html\"", $str);
                }
                else
                {
                    $str = preg_replace("/(href=\"index.php\?mod=)([A-Z0-9_]+)\"/is", "href=\"{$siteurl}\$2.{$config['lang']}.html\"", $str);
                    $str = preg_replace("/(href='index.php\?mod=)([A-Z0-9_]+)'/is", "href=\"{$siteurl}\$2.{$config['lang']}.html\"", $str);
                }
            }
            while ($old != $str)
            {
                $old = $str;
                $str = preg_replace("/<([^>]+)( background| href| src)=(\")([^:^{]*)(\")/im", "<\\1\\2=\\3{$siteurl}$basepath\\4\\3", $str);
                $str = preg_replace("/<([^>]+)( background| href| src)=(\')([^:^{]*)(\')/im", "<\\1\\2=\\3{$siteurl}$basepath\\4\\3", $str);
                $str = preg_replace('#<([^>]+)(url\(\'(?!http))#', '<$1$2$3' . $siteurl . $basepath . '', $str);
            }
        }
        $str = str_replace("ferh=\"", "href=\"", $str);
        $str = str_replace("ferh='", "href='", $str);
        $str = str_replace("rcs=\"", "src=\"", $str);
        $str = str_replace("rcs='", "src='", $str);
    }
    $strout = $str;
    $listparams = "<pre>";
    foreach ($arrayvars as $key => $value)
    {
        $strout = str_replace("<!-- if {" . $key . "}", "<!-- if {_&" . $key . "}", $strout);
        $strout = str_replace("<!-- end if {" . $key . "}", "<!-- end if {_&" . $key . "}", $strout);
        $strout = str_replace("<!-- if not {" . $key . "}", "<!-- if not {_&" . $key . "}", $strout);
        $strout = str_replace("<!-- end if not {" . $key . "}", "<!-- end if not {_&" . $key . "}", $strout);
        $strout = str_replace("<!-- foreach {" . $key . "}", "<!-- foreach {_&" . $key . "}", $strout);
        $strout = str_replace("<!-- end foreach {" . $key . "}", "<!-- end foreach {_&" . $key . "}", $strout);
    }
    foreach ($arrayvars as $key => $value)
    {
        if (is_array($value))
        {
            //array   --->
            $html_template_array_items = TPL_GetHtmlParts("foreach {_&" . $key . "}", $strout);

            foreach ($html_template_array_items as $html_template_array)
            {
                $html_template_array_clean = TPL_str_replace_first("<!-- foreach {_&" . $key . "} -->", "", $html_template_array);
                $html_template_array_clean = TPL_str_replace_last("<!-- end foreach {_&" . $key . "} -->", "", $html_template_array_clean);
                if ($html_template_array_clean == "<!-- recursion -->")
                {
                    $html_template_array_clean=$strout;
                }
                if ($html_template_array)
                {
                    $html_array = "";
                    foreach ($value as $item)
                    {
                        $html_array .= TPL_ApplyTplString($html_template_array_clean, $item, $basepath, $config);
                    }
                    $strout = str_replace($html_template_array, $html_array, $strout);
                }
            }
            //array   ---<
        }
    }
    foreach ($arrayvars as $key => $value)
    {
        //if----
        $html_template_if_items = TPL_GetHtmlParts("if {_&" . $key . "}", $strout);
        if ($html_template_if_items)
        {
            foreach ($html_template_if_items as $html_template_if)
            {
                $html_array = "";
                if ($value)
                {
                    $html_template_if_clean = TPL_str_replace_first("<!-- if {_&" . $key . "} -->", "", $html_template_if);
                    $html_template_if_clean = TPL_str_replace_last("<!-- end if {_&" . $key . "} -->", "", $html_template_if_clean);
                    if (is_array($value))
                    {
                        $html_array = TPL_ApplyTplString($html_template_if_clean, $value, $basepath, $config);
                    }
                    else
                    {
                        $html_array = TPL_ApplyTplString($html_template_if_clean, $arrayvars, $basepath, $config);
                    }
                }
                $strout = TPL_ReplaceHtmlPart("if {_&" . $key . "}", $html_array, $strout);
            }
        }
        //end if---
        //if not----
        $html_template_if_items = TPL_GetHtmlParts("if not {_&" . $key . "}", $strout);
        if ($html_template_if_items)
        {
            foreach ($html_template_if_items as $html_template_if)
            {
                $html_array = "";
                if (!$value)
                {
                    $html_template_if_clean = TPL_str_replace_first("<!-- if not {_&" . $key . "} -->", "", $html_template_if);
                    $html_template_if_clean = TPL_str_replace_last("<!-- end if not {_&" . $key . "} -->", "", $html_template_if_clean);
                    $html_array = TPL_ApplyTplString($html_template_if_clean, $arrayvars, $basepath, $config);
                }
                $strout = TPL_ReplaceHtmlPart("if not {_&" . $key . "}", $html_array, $strout);
            }
        }
        //end if not---        
    }
    foreach ($arrayvars as $key => $value)
    {
        if ($value !== null && (is_string($value) || is_numeric($value) || is_array($value) ))
        {
            if (is_array($value))
            {
                $value = "array(" . count($value) . ")";
            }
            $listparams .= "$key = " . htmlentities($value) . "\n";
            $strout = str_replace("_startvar_" . $key . "_endvar_", "{" . $key . "}", $strout);
            $strout = str_replace("{" . $key . "}", TPL_encode($value), $strout);
        }
    }
    $listparams .= "</pre>";
    $strout = str_replace("{listvars}", $listparams, $strout);
    $i18n = array();
    preg_match_all("/{i18n:([^\}]+)}/", $strout, $i18n);
    if (isset($i18n[1]))
    {
        foreach ($i18n[1] as $i18n_item)
        {
            $mode = "";
            $i18n_item_tmp = str_replace("?", "", $i18n_item);
            if (preg_match("/^[A-Z]/s", $i18n_item) && preg_match("/[a-z]$/s", $i18n_item_tmp))
            {
                $mode = "Aa";
            }
            elseif (preg_match("/^[a-z]/s", $i18n_item) && preg_match("/[a-z]$/s", $i18n_item_tmp))
            {
                $mode = "aa";
            }
            elseif (preg_match("/^[A-A]/s", $i18n_item) && preg_match("/[A-Z]$/s", $i18n_item_tmp))
            {
                $mode = "AA";
            }
            $strout = str_replace("{i18n:$i18n_item}", TPL_Translate(strtolower("$i18n_item"), $mode), $strout);
        }
    }

    if ($recursion == 1)
    {
        if (!empty($config['use_urlserverpath']))
            $strout = str_replace($siteurl, $config['sitepath'], $strout);
    }

    foreach ($arrayvars as $ks => $kv)
    {
        if ($recursion == 1)
        {

            $strout = str_replace("<!-- if {_&" . $ks . "} -->", "", $strout);
            $strout = str_replace("<!-- end if {_&" . $ks . "} -->", "", $strout);
            $strout = str_replace("<!-- if not {_&" . $ks . "} -->", "", $strout);
            $strout = str_replace("<!-- end if not {_&" . $ks . "} -->", "", $strout);
            $strout = str_replace("<!-- foreach {_&" . $ks . "} -->", "", $strout);
            $strout = str_replace("<!-- end foreach {_&" . $ks . "} -->", "", $strout);
        }
        else
        {
            $strout = str_replace("<!-- if {_&" . $ks . "} -->", "<!-- if {" . $ks . "} -->", $strout);
            $strout = str_replace("<!-- end if {_&" . $ks . "} -->", "<!-- end if {" . $ks . "} -->", $strout);
            $strout = str_replace("<!-- if not {_&" . $ks . "} -->", "<!-- if not {" . $ks . "} -->", $strout);
            $strout = str_replace("<!-- end if not {_&" . $ks . "} -->", "<!-- end if not {" . $ks . "} -->", $strout);
            $strout = str_replace("<!-- foreach {_&" . $ks . "} -->", "<!-- foreach {" . $ks . "} -->", $strout);
            $strout = str_replace("<!-- end foreach {_&" . $ks . "} -->", "<!-- end foreach {" . $ks . "} -->", $strout);
        }
    }

    $ret = TPL_decode($strout);
    $recursion--;
    return $ret;
}

/**
 * 
 * @param type $str
 * @param type $mode
 * @return type
 */
function TPL_Translate($str, $mode)
{
    return FN_Translate($str, $mode);
}

/**
 * 
 * @param type $search
 * @param type $replace
 * @param type $subject
 * @return type
 */
function TPL_str_replace_first($search, $replace, $subject)
{
    return is_numeric($pos = strpos($subject, $search)) ? substr_replace($subject, $replace, $pos, strlen($search)) : $subject;
}

/**
 * 
 * @param type $search
 * @param type $replace
 * @param type $subject
 * @return type
 */
function TPL_str_replace_last($search, $replace, $subject)
{
    return is_numeric($pos = strrpos($subject, $search)) ?
            substr_replace($subject, $replace, $pos, strlen($search)) : $subject;
}

?>