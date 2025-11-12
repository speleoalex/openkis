<?php
class xmetadbfrm_field_float
{

    function __construct()
    {

    }

    function show($params)
    {
        $html = "";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $required = (isset($params['frm_required']) && $params['frm_required'] == 1 ) ? "required=\"required\"" : "";

        // Support for min, max, and step attributes
        $min = isset($params["min"]) ? $params["min"] : "";
        $max = isset($params["max"]) ? $params["max"] : "";
        $step = isset($params["step"]) ? $params["step"] : "0.1";

        if ($min != "")
            $attributes .= " min=\"$min\"";
        if ($max != "")
            $attributes .= " max=\"$max\"";

        $attributes .= " step=\"$step\"";

        $size = isset($params['frm_size']) ? $params['frm_size'] : 30;
        $l = (!empty($params['size'])) ? "maxlength=\"{$params['size']}\"" : "";
        $frm_prefix = isset($params['frm_prefix']) ? $params['frm_prefix'] : "";

        // Allow digits, minus sign, and decimal point
        $html .= "$frm_prefix <input type=\"number\" $attributes $required  $l title=\"{$params['frm_help']}\" size=\"" . $size . "\" name=\"{$params['name']}\"  value=\"" . str_replace('"', '&quot;', $params['value']) . "\" />";

        $frm_suffix = isset($params['frm_suffix']) ? $params['frm_suffix'] : "";
        $html .= $frm_suffix;
        return $html;
    }

    function view($params)
    {
        // Format float value for display (2 decimal places by default)
        $decimals = isset($params['decimals']) ? $params['decimals'] : 2;
        $value = floatval($params['value']);
        $html = number_format($value, $decimals, '.', '');
        return $html;
    }

}
