<?php

class xmetadbfrm_field_varchar_uppercase
{

    function show($params)
    {
        $toltips=($params['frm_help']!= "") ? "title=\"".$params['frm_help']."\"" : "";
        $size=isset($params['frm_size']) ? $params['frm_size'] : 30;
        //-----------------------------------------
        $html="<script type=\"text/javascript\">
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
		};
		</script>";
        $oldvalues=$params['oldvalues'];
        $tooltip=$params['frm_help'];
        $suff="";
        if (isset($params['frm_suffix']))
            $suff=$params['frm_suffix'];
        $html.="<input onchange=\"check_upper(this,true)\"  title=\"$tooltip\" size=\"".$size."\" name=\"{$params['name']}\"  value=\"".str_replace('"','\\"',$params['value'])."\" />$suff";
        //-----------------------------------------
        return $html;
    }

}
