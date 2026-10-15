<?php

class xmetadbfrm_field_htmlopenspeleo
{
    function __construct()
    {
    }

    function show($params)
    {
        global $_FN;

        $rows    = isset($params['frm_rows']) ? $params['frm_rows'] : 4;
        $cols    = isset($params['frm_cols']) ? $params['frm_cols'] : "auto";
        $tooltip = isset($params['frm_help']) ? $params['frm_help'] : "";
        $name    = $params['name'];
        $value   = isset($params['value']) ? $params['value'] : "";

        $editor = isset($_FN['htmleditor']) ? $_FN['htmleditor'] : "0";
        if (!empty($_FN['force_htmleditor'])) {
            $editor = $_FN['force_htmleditor'];
        }

        if ($editor != "0" && file_exists("include/htmleditors/{$editor}/htmlarea.php")) {
            require_once("include/htmleditors/{$editor}/htmlarea.php");
            $defaultdir = isset($_FN['editor_folder']) ? $_FN['editor_folder'] : false;
            return FN_HtmlHtmlArea($name, $cols, $rows, $value, $defaultdir, array("toolbar" => "Openspeleo"));
        }

        $html  = "<textarea title=\"$tooltip\" cols=\"$cols\" rows=\"$rows\" name=\"$name\">";
        $html .= htmlspecialchars($value);
        $html .= "</textarea>";
        return $html;
    }

    function formtovalue($str, $params)
    {
        return $str;
    }

    function valuetoform($str)
    {
        return $str;
    }

    function view($params)
    {
        $value = isset($params['value']) ? $params['value'] : "";
        return $value;
    }
}
