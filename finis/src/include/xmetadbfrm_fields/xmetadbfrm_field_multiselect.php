<?php

/**
 * multi checkbox  field
 */
class xmetadbfrm_field_multiselect
{

    function __construct()
    {
        
    }

    function show($params)
    {
        global $_FN;
        static $i = 0;
        $i++;
        $inputid_prefix="_{$i}_";
        $html="
<script type=\"text/javascript\" >
var synccheck{$inputid_prefix}_{$params['name']} = function ()
{
    var Obj = document.getElementById('right_$inputid_prefix');
    var str = '';
    var sep = '';
    if (Obj.options){
        var listOptions=Obj.getElementsByTagName('option')
        for (var i in listOptions)
        {
            //    console.log('i='+i+'val='+listOptions[i].value);

            if (!isNaN(i) && listOptions[i] != undefined && listOptions[i].value != undefined)
            {
                str+=sep+listOptions[i].value;
                sep = ',';
            }
        }
    }
    document.getElementById('xmetadbvalue{$inputid_prefix}_{$params['name']}').value=str;
};
var toright{$inputid_prefix}_{$params['name']} = function ()
{
    var Obj = document.getElementById('left_$inputid_prefix');
    var selIndex = Obj.selectedIndex;
    try{var selObj = Obj.options[selIndex];}catch (e){return;}
    var newObj = selObj.cloneNode(true);
    document.getElementById('right_$inputid_prefix').appendChild(newObj);
    Obj.removeChild(selObj);
    synccheck{$inputid_prefix}_{$params['name']}();
};
var toleft{$inputid_prefix}_{$params['name']} = function ()
{
    var Obj = document.getElementById('right_$inputid_prefix');
		
    var selIndex = Obj.selectedIndex;
    try{var selObj = Obj.options[selIndex];}catch (e){return;}
    var newObj = selObj.cloneNode(true);
    document.getElementById('left_$inputid_prefix').appendChild(newObj);
    Obj.removeChild(selObj);
    synccheck{$inputid_prefix}_{$params['name']}();

};
</script>
";

        $tooltip=$params['frm_help'];
        $name=$params['name'];
        $value=$params['value'];

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
        $html.="<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"padding:0px;\"><tr><td  style=\"padding:0px;\"><select style=\"width:200px;\"  id=\"left_$inputid_prefix\" size=\"5\">";
        foreach($options as $k=> $option)
        {

            $sel="";
            $toption=$option;
            if (isset($optionslang[$i]) && $optionslang[$i]!= "")
                $toption=$optionslang[$i];
            if (FN_erg("^$k\$",$value) || FN_erg(",$k,",$value) || FN_erg("^$k,",$value) || FN_erg(",$k\$",$value))
            {
                
            }
            else
            {
                $html.="<option  value=\"".htmlentities($k,ENT_QUOTES,$_FN['charset_page'])."\" title=\"".htmlentities($toption,ENT_QUOTES,$_FN['charset_page'])."\" name=\"__xmetadb_multiselect_".$params['name']."\"  />".htmlentities($toption,ENT_QUOTES,$_FN['charset_page'])."</option> ";
            }
            $i++;
        }
        $html.="</select></td><td style=\"padding:0px;\" >";
        $html.="<img style=\"cursor:pointer\" onclick=\"toleft{$inputid_prefix}_{$params['name']}()\" alt=\"\" src=\"{$_FN['siteurl']}images/left.png\" /><br />";
        $html.="<img style=\"cursor:pointer\" onclick=\"toright{$inputid_prefix}_{$params['name']}()\" alt=\"\" src=\"{$_FN['siteurl']}images/right.png\" />";
        $html.="</td><td style=\"padding:0px;\" ><select  style=\"width:200px;\"  id=\"right_$inputid_prefix\" size=\"5\">";
        foreach($options as $k=> $option)
        {
            $sel="";
            $toption=$option;
            if (isset($optionslang[$i]) && $optionslang[$i]!= "")
                $toption=$optionslang[$i];
            if (FN_erg("^$k\$",$value) || FN_erg(",$k,",$value) || FN_erg("^$k,",$value) || FN_erg(",$k\$",$value))
                $html.="<option  value=\"".htmlentities($k,ENT_QUOTES,$_FN['charset_page'])."\" title=\"$tooltip\" name=\"__xmetadb_multiselect_".$params['name']."\"  />$toption</option> ";
            $i++;
        }

        $html.="</select></td><td>";

        $html.="</td></tr></table>";
        $html.="<input  type=\"hidden\" id=\"xmetadbvalue{$inputid_prefix}_{$params['name']}\" name=\"$name\" value=\"".htmlentities($value,ENT_QUOTES,$_FN['charset_page'])."\"  />";
        $html.="<script type=\"text/javascript\"  >setTimeout(\"synccheck{$inputid_prefix}_{$params['name']}()\",0);</script>";
        return $html;
    }

}