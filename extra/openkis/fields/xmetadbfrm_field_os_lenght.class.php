<?php
//---------lenght---------------------------------------<
class xmetadbfrm_field_os_lenght
{

    function show($params)
    {
        $html="";
        $toltips=($params['frm_help']!="")?"title=\"".$params['frm_help']."\"":"";
        $size=isset($params['frm_size'])?$params['frm_size']:30;
        //-----------------------------------------
        static $htmljs="<script type=\"text/javascript\">
var check_num = function(input,change)
{
	var str = input.value.toString();
	str = str.replace(/\\s+$/,'');
	str = str.replace(',','.');
	str = str.replace(/[^0123456789.\-]+/,'');
	input.value = str;
	//alert (str);
}
</script>";
        $html.= "$htmljs";
        $htmljs="";
        $oldvalues=$params['oldvalues'];
        $tooltip=$params['frm_help'];
        $suff="";
        if(isset($params['frm_suffix']))
            $suff=$params['frm_suffix'];
        $html.= "<input onblur=\"check_num(this,false)\" onchange=\"check_num(this,false)\" title=\"$tooltip\" size=\"".$size."\" name=\"{$params['name']}\"  value=\"".add_db_sl($params['value'])."\" />$suff";
        //-----------------------------------------
        return $html;
    }

}

//---------lenght---------------------------------------< 
?>
