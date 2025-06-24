<?php

//---------uppercase--------------------------------------->
class xmetadbfrm_field_uppercase
{

    function show($params)
    {

        $frm_prefix = isset($params['frm_prefix']) ? $params['frm_prefix'] : "";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $size = isset($params['frm_size']) ? $params['frm_size'] : 30;
        $l = (!empty($params['size'])) ? "maxlength=\"{$params['size']}\"" : "";
        $required = (isset($params['frm_required']) && $params['frm_required'] == 1 ) ? "required=\"required\"" : "";
        static $htmljs = "<script type=\"text/javascript\">
		var check_upper = function(input,change)
		{
			var str = input.value.toString();
			if (change)
			{
				str = str.replace(/\\s+$/,'');
				str = str.replace(/^\\s+/,'');
			}
			str = str.toUpperCase();
			
			input.value = str;
		}
		</script>";
        //-----------------------------------------
        $html = "$htmljs";
        $htmljs = "";
        $suff = "";
        if (isset($params['frm_suffix']))
            $suff = $params['frm_suffix'];

        $html .= "$frm_prefix<input  onchange=\"check_upper(this,true)\" $required $attributes  $l title=\"{$params['frm_help']}\" size=\"" . $size . "\" name=\"{$params['name']}\"  value=\"" . str_replace('"', '&quot;', $params['value']) . "\" />";
//        $html.="<input  onchange=\"check_upper(this,true)\"  title=\"$tooltip\" size=\"".$size."\" name=\"{$params['name']}\"  value=\"".add_db_sl($params['value'])."\" />$suff";
        //-----------------------------------------
        return $html;
    }
}

