<?php

class xmetadbfrm_field_textopenspeleo
{
    function __construct()
    {
    }

    function htmlfilter($params)
    {
        return $this->show($params);
    }

    function show($params)
    {
        $rows = isset($params['frm_rows']) ? $params['frm_rows'] : 4;
        $cols = isset($params['frm_cols']) ? $params['frm_cols'] : "auto";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $tooltip = isset($params['frm_help']) ? $params['frm_help'] : "";
        $required = (isset($params['frm_required']) && $params['frm_required'] == 1) ? "required=\"required\"" : "";

        $style = "";
        if ($cols == "auto") {
            $cols = "10";
            $style = "width:90%;";
        }

        $onkeyup = "";
        if ($rows == "auto") {
            $onkeyup  = "onkeyup=\"if (this.scrollHeight >= this.offsetHeight){ this.style.height = 10 + this.scrollHeight+'px';}\" ";
            $onkeyup .= "onfocus=\"if (this.scrollHeight >= this.offsetHeight){ this.style.height = 10 + this.scrollHeight+'px';}\" ";
            $onkeyup .= "style=\"{$style}overflow:auto;height:30px;\"";
            $rows = 3;
        }

        $value = isset($params['value']) ? $params['value'] : "";
        $html  = "<textarea $required $attributes style=\"$style\" $onkeyup title=\"$tooltip\" cols=\"$cols\" rows=\"$rows\" name=\"{$params['name']}\">";
        $html .= htmlspecialchars($value);
        $html .= "</textarea>";
        return $html;
    }

    function formtovalue($str, $params)
    {
        return html_entity_decode($str);
    }

    function valuetoform($str)
    {
        return html_entity_decode($str);
    }

    function view($params)
    {
        $value = isset($params['value']) ? $params['value'] : "";
        return str_replace("\n", "<br />", htmlspecialchars($value));
    }
}
