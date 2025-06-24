<?php

//---------html--------------------------------------->
class xmetadbfrm_field_html
{

    function __construct()
    {
        
    }

    function show($params)
    {
        $html = "";
        $rows = isset($params['frm_rows']) ? $params['frm_rows'] : 4;
        $cols = isset($params['frm_cols']) ? $params['frm_cols'] : "auto";
        $languagesfield = $params['languagesfield'];
        $oldvalues = $params['oldvalues'];
        $tooltip = $params['frm_help'];
        if (isset($_POST[$params['name']]))
            $params['value'] = $this->formtovalue($params['value'], $params);
        $html .= $this->html_field_html($params['name'], $params['value'], $rows, $cols, $tooltip);
        return $html;
    }

    /**
     *
     * @param string $str
     * @param array $params
     * @return string 
     */
    function formtovalue($str, $params)
    {
        //$str=FN_RewriteLinksAbsoluteToLocal($str,".");
        return $str;
    }

    function valuetoform($str)
    {
        //$str=FN_RewriteLinksLocalToAbsolute($str,".");
        return $str;
    }

    /**
     * html_field_html
     * show html field
     *
     */
    function html_field_html($name, $value, $rows, $cols, $tooltip)
    {
        $html = "";
        if (function_exists("xmetadb_frm_field_html_overwrite"))
            return xmetadb_frm_field_html_overwrite($name, $value, $rows, $cols, $tooltip);
        $html .= "<textarea title=\"$tooltip\" cols=\"" . $cols . "\"  rows=\"" . $rows . "\"  name=\"$name\"  >";
        $html .= htmlspecialchars($value);
        $html .= "</textarea>";
        return $html;
    }
}

//---------html---------------------------------------<
