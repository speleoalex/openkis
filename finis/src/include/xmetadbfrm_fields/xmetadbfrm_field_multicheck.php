<?php


/**
 * multi checkbox  field
 */
class xmetadbfrm_field_multicheck
{

    function __construct()
    {
        
    }

    function show($params)
    {
        static $i =0;
        $i++;
//        $inputid_prefix=md5(serialize($params));
        $inputid_prefix = "$i";
        $html="
<script type=\"text/javascript\" >
var synccheck{$inputid_prefix}_{$params['name']} = function (id)
{
	var divitems = document.getElementById( id ).childNodes;
	var sep='';
	var str='';
	for (var i in divitems)
	{
		items=divitems[i].childNodes;
		for (var i in items)
		{
			if (items[i].checked == true )
			{
				str = str+ sep + items[i].value
				sep=',';
			}
		}
	}
	document.getElementById('xmetadbvalue{$inputid_prefix}_{$params['name']}').value=str;
};
</script>
";

        $tooltip=$params['frm_help'];
        $name=$params['name'];
        $value=$params['value'];
        //dprint_r($params);
        $options=array();
        if (!isset($params['options']))
        {
            $options=explode(",",$params['frm_options']);
            $optionslang=array();
            if (isset($params['frm_options_'.$params['lang']]))
            {
                $optionslang=explode(",",$params['frm_options_'.$params['lang']]);
            }
        }
        else
        {
            $_options=$params['options'];
            if (is_array($_options))
                foreach($_options as $opt)
                {
                    $options[$opt['value']]=$opt['title'];
                }
        }
        $i=0;
        $toenable=$todisable="";
        $jexecute="synccheck{$inputid_prefix}_{$params['name']}('xmetadbck{$inputid_prefix}_{$params['name']}');";
        $html.="<div id=\"xmetadbck{$inputid_prefix}_{$params['name']}\" >";
        foreach($options as $k=> $option)
        {
            $jsonclick="onclick=\"$jexecute\" onchange=\"$jexecute\" ";
            $sel="";
            $toption=$option;
            if (isset($optionslang[$i]) && $optionslang[$i]!= "")
                $toption=$optionslang[$i];
            if (FN_erg("^$k\$",$value) || FN_erg(",$k,",$value) || FN_erg("^$k,",$value) || FN_erg(",$k\$",$value))
                $sel="checked=\"checked\"";
            $html.="<label style=\"white-space:nowrap\" ><input $sel $jsonclick type=\"checkbox\" value=\"$k\" title=\"$tooltip\" name=\"__xmetadb_multicheck_".$params['name']."\"  />&nbsp;$toption</label>&nbsp;&nbsp; ";
            $i++;
        }
        $html.="<input type=\"hidden\" id=\"xmetadbvalue{$inputid_prefix}_{$params['name']}\" name=\"$name\" value=\"$value\" />";
        $html.="</div>";
        $html.="<script type=\"text/javascript\"  >setTimeout(\"$jexecute\",0);</script>";
        return $html;
    }

}

