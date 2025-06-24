<?php

//---------password--------------------------------------->
class xmetadbfrm_field_password
{

    function __construct()
    {
        
    }

    function show($params)
    {
        if (!empty($params['is_update']))
            $params['value'] = "";
        $params['value'] = "";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $required = (isset($params['frm_required']) && $params['frm_required'] == 1 ) ? "required=\"required\"" : "";

        $html = "";
        $toltips = ($params['frm_help'] != "") ? "title=\"" . $params['frm_help'] . "\"" : "";
        $html .= "<input $required autocomplete=\"off\"  $attributes  $toltips value=\"" . str_replace('"', '\\"', $params['value']) . "\"  name=\"" . $params['name'] . "\" type=\"password\" />\n";
        return $html;
    }

    function view($params)
    {
        $html = "***";
        return "***";
    }

}

//---------password---------------------------------------<
