<?php

//---------password--------------------------------------->
class xmetadbfrm_field_md5passwd
{

    function __construct()
    {
        
    }

    function show($params)
    {
        if ($params['is_update'])
            $params['value'] = "";
        $html = "";
        $toltips = ($params['frm_help'] != "") ? "title=\"" . $params['frm_help'] . "\"" : "";
        $html .= "<input  $toltips value=\"" . str_replace('"', '\\"', $params['value']) . "\" autocomplete=\"off\" name=\"" . $params['name'] . "\" type=\"password\" />\n";
        return $html;
    }

    function view($params)
    {
        $html .= "***";
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
        $str = md5($str);
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
