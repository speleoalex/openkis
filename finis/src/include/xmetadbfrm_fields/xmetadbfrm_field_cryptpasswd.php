<?php

class xmetadbfrm_field_cryptpasswd
{

    function __construct()
    {
        
    }

    function show($params)
    {
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        if ($params['is_update'])
            $params['value'] = "";
        $html = "";
        $toltips = ($params['frm_help'] != "") ? "title=\"" . $params['frm_help'] . "\"" : "";
        $html .= "<input $attributes  $toltips value=\"" . str_replace('"', '\\"', $params['value']) . "\" autocomplete=\"new-password\" name=\"" . $params['name'] . "\" type=\"password\" />\n";
        return $html;
    }

    function view($params)
    {
        $html = "***";
        return "***";
    }

    /**
     *
     * @param string $str
     * @param array $params
     * @return string 
     */
    function formtovalue($str, $params)
    {
        if ($str == "")
            return "";
        $options = array('cost' => FN_AUTH_COST);
        if (function_exists("password_hash"))
        {
            $str = @password_hash($str, FN_AUTH_METHOD, $options);
        }
        else
        {
            $str = md5($str);
        }
        return $str;
    }

    /**
     * 
     * @param type $str
     * @return string
     */
    function valuetoform($str)
    {
        return "";
    }

}
