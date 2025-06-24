<?php
/**
 * radio field
 */
class xmetadbfrm_field_radio
{

    function show($params)
    {
        static $id = 0;
        $html = "";
        $tooltip = $params['frm_help'];
        $name = $params['name'];
        $value = $params['value'];
        $options = $params['fieldform']->formvals[$name]['options'];
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $attributes = explode(",", $attributes);
        $attributes_input = $attributes[0];
        $attributes_label = isset($attributes[1]) ? $attributes[1] : "";
        $i = 0;
        $toenable = $todisable = "";
        if (isset($params['frm_options_enable']))
        {
            $toenable = explode(",", $params['frm_options_enable']);
        }
        if (isset($params['frm_options_disable']))
        {
            $todisable = explode(",", $params['frm_options_disable']);
        }
        $jexecute = "";
        foreach ($options as $k => $option)
        {
            if (!isset($option['value']))
            {
                trigger_error("xmetadb_frm missing option in field $name", E_USER_NOTICE);
                continue;
            }
            $jsonclick = $js = "";
            if (is_array($toenable) && isset($toenable[$k]))
            {
                $enableitems = explode("|", $toenable[$k]);
                foreach ($enableitems as $it)
                {
                    $js .= "if(document.getElementsByName('$it')[0]!=undefined)document.getElementsByName('$it')[0].disabled=false;";
                }
            }
            if (is_array($todisable) && isset($todisable[$k]))
            {
                $disableitems = explode("|", $todisable[$k]);
                foreach ($disableitems as $it)
                {
                    $js .= "if(document.getElementsByName('$it')[0]!=undefined)document.getElementsByName('$it')[0].disabled=true;";
                }
            }
            if ($value === $option['value'])
            {
                $jexecute .= $js;
            }
            if ($js != "")
            {
                $jsonclick = "onclick=\"$js\"";
            }
            $sel = "";
            $toption = $option['title'];
            if ($value === $option['value'])
                $sel = "checked=\"checked\"";
            $id++;
            $required = (isset($params['frm_required']) && $params['frm_required'] == 1 ) ? "required=\"required\"" : "";
            $html .= "<label $attributes_label for=\"xmetadbradio{$name}{$id}\" style=\"white-space:nowrap\" ><input $required $attributes_input  id=\"xmetadbradio{$name}{$id}\"  $sel $jsonclick type=\"radio\" value=\"{$option['value']}\" title=\"$tooltip\" name=\"" . $name . "\"  />$toption</label> ";
            $i++;
        }
        $html .= "<script type=\"text/javascript\"  >setTimeout(\"$jexecute\",0);</script>";
        return $html;
    }

}
