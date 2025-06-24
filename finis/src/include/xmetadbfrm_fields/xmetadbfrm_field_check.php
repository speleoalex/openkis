<?php
//---------check--------------------------------------->
class xmetadbfrm_field_check
{

    function __construct()
    {
        
    }

    function show($params)
    {
        $html = "";
        $toltips = "";
        $oldval = $params['value'];
        $ch = "";
        if ($oldval != "")
            $ch = "checked=\"checked\"";
        if ($oldval != $params['frm_checkon'])
        {
            $ch = "";
        }
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $required = (isset($params['frm_required']) && $params['frm_required'] == 1 ) ? "required=\"required\"" : "";
        $html .= "<input type=\"hidden\" value=\"" . htmlspecialchars($oldval) . "\" name=\"__check__" . $params['name'] . "\"  />";
        $html .= "<input $required $attributes $toltips $ch type=\"checkbox\" value=\"" . $params['frm_checkon'] . "\" name=\"" . $params['name'] . "\"  />";
        return $html;
    }

}

