<?php

//---------string--------------------------------------->
class xmldbfrm_field_link
{

    function __construct()
    {
        
    }

    function show($params)
    {
        $required = (isset($params['frm_required']) && $params['frm_required'] == 1 ) ? "required=\"required\"" : "";
        $html = "";
        $size = isset($params['frm_size']) ? $params['frm_size'] : 30;
        $oldvalues = $params['oldvalues'];
        $l = (!empty($params['size'])) ? "maxlength=\"{$params['size']}\"" : "";
        $frm_prefix = isset($params['frm_prefix']) ? $params['frm_prefix'] : "";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        if (!empty($params['frm_readonly']))
        {
            $attributes.=" readonly=\"readonly\"";
        }
        $html .= "$frm_prefix<input $required $attributes  $l title=\"{$params['frm_help']}\" size=\"" . $size . "\" name=\"{$params['name']}\"  value=\"" . str_replace('"', '&quot;', $params['value']) . "\" />";
        $frm_suffix = isset($params['frm_suffix']) ? $params['frm_suffix'] : "";
        $html .= $frm_suffix;
        return $html;
    }

    function view($params)
    {
        $value=htmlspecialchars($params['value']);
        $html = "<a target=\"_blank\" href=\"".$value."\">".$value."</a>";
        return $html;
    }

}


?>
