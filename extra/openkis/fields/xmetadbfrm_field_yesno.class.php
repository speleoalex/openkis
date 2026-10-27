<?php

//---------yesno--------------------------------------->
// checkbox shown as yes/no in the record view and in the lists;
// keep <type>check</type> in the table so that an unchecked box is saved as empty
class xmetadbfrm_field_yesno
{

    function show($params)
    {
        $value = $params['value'];
        $checkon = isset($params['frm_checkon']) ? $params['frm_checkon'] : "1";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        // the generic form item gives every input class="form-control", which hides a checkbox
        $attributes = preg_replace('/\bclass="[^"]*"/', '', $attributes);
        $checked = ($value != "" && $value == $checkon) ? "checked=\"checked\"" : "";
        $html = "<input type=\"hidden\" value=\"" . htmlspecialchars($value) . "\" name=\"__check__{$params['name']}\" />";
        $html .= "<span class=\"form-check form-switch\"><input class=\"form-check-input\" $attributes $checked type=\"checkbox\" value=\"" . htmlspecialchars($checkon) . "\" name=\"{$params['name']}\" /></span>";
        return $html;
    }

    function view($params)
    {
        return ($params['value'] != "") ? FN_Translate("yes") : FN_Translate("no");
    }

    function gridview($params)
    {
        return $this->view($params);
    }
}
