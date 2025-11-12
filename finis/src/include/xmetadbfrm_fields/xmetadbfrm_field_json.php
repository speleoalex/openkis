<?php
class xmetadbfrm_field_json
{

    function arrayToList($in, $level = 0)
    {
        if (!is_array($in)) {
            return htmlspecialchars($in);
        }        
        $str = "<ul>";        
        foreach ($in as $k => $v) {
            if (is_array($v)) {
                $str .= "<li>";
                $str .= "<strong>" . htmlspecialchars($k) . ":</strong>";
                $str .= $this->arrayToList($v, $level + 1);
                $str .= "</li>";
            } else {
                $str .= "<li>";
                $str .= "<strong>" . htmlspecialchars($k) . ":</strong> ";
                
                if (is_numeric($v)) {
                    $str .= "<code>" . htmlspecialchars($v) . "</code>";
                } elseif (is_bool($v)) {
                    $text = $v ? "true" : "false";
                    $str .= "<kbd>{$text}</kbd>";
                } elseif (preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) {
                    $str .= "<time>" . htmlspecialchars($v) . "</time>";
                } else {
                    $str .= "<span>" . htmlspecialchars($v) . "</span>";
                }
                $str .= "</li>";
            }
        }
        
        $str .= "</ul>";
        return $str;
    }

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
        $jsonData = @json_decode($params['value'], JSON_OBJECT_AS_ARRAY);
        if ($jsonData)
        {
            $html .= $this->arrayToList($jsonData);
        }
        else 
        {
            $html .= "<pre>" . print_r($params['value'], true) . "</pre>";
        }
        return $html;
    }
}
