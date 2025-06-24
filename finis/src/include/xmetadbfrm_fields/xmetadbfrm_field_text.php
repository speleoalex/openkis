<?php
class xmetadbfrm_field_text
{

    function __construct()
    {
        
    }

    function show($params)
    {
        $html = "";
        $rows = isset($params['frm_rows']) ? $params['frm_rows'] : 4;
        $cols = isset($params['frm_cols']) ? $params['frm_cols'] : "auto";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";

        $style = "";
        if ($cols == "auto")
        {
            $cols = "10";
            $style = "width:90%;";
        }
        $tooltip = $params['frm_help'];
        $onkeyup = "";
        if ($rows == "auto")
        {
            $onkeyup = "onkeyup=\"if (this.scrollHeight >= this.offsetHeight){ this.style.height = 10 + this.scrollHeight+'px';}\" ";
            $onkeyup .= "onfocus=\"if (this.scrollHeight >= this.offsetHeight){ this.style.height = 10 + this.scrollHeight+'px';}\" ";
            $onkeyup .= "style=\"{$style}overflow:auto;height:30px;\"";
            $rows = 3;
        }
        $html = "";
        $required = (isset($params['frm_required']) && $params['frm_required'] == 1 ) ? "required=\"required\"" : "";

        $html .= "<textarea $required $attributes style=\"$style\" $onkeyup title=\"$tooltip\" cols=\"" . $cols . "\"  rows=\"" . $rows . "\"  name=\"{$params['name']}\"  >";
        $html .= htmlspecialchars($params['value']);
        $html .= "</textarea>";
        return $html;
    }

    function view($params)
    {
        $html = "";
        $html .= str_replace("\n", "<br />", htmlspecialchars($params['value']));
        return $html;
    }

}
