<?php 
class xmetadbfrm_field_int
{

    function __construct()
    {
        
    }

    function show($params)
    {
        $html = "";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $required = (isset($params['frm_required']) && $params['frm_required'] == 1 ) ? "required=\"required\"" : "";

        $toltips = ($params['frm_help'] != "") ? "title=\"" . $params['frm_help'] . "\"" : "";
        $size = isset($params['frm_size']) ? $params['frm_size'] : 30;
        $languagesfield = $params['languagesfield'];
        $oldvalues = $params['oldvalues'];
        $l = (!empty($params['size'])) ? "maxlength=\"{$params['size']}\"" : "";
        $frm_prefix = isset($params['frm_prefix']) ? $params['frm_prefix'] : "";
        $html .= "$frm_prefix <input $attributes $required onkeyup=\"this.value = this.value.replace(/[^01234567890-]/i, '');\"  $l title=\"{$params['frm_help']}\" size=\"" . $size . "\" name=\"{$params['name']}\"  value=\"" . str_replace('"', '&quot;', $params['value']) . "\" />";
        $frm_suffix = isset($params['frm_suffix']) ? $params['frm_suffix'] : "";
        $html .= $frm_suffix;
        return $html;
    }

    function view($params)
    {
        $html = htmlspecialchars($params['value']);
        return $html;
    }

}
