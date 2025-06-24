<?php

/**
 * multi checkbox field
 */
class xmetadbfrm_field_multicave
{
    function encode_preg($str)
    {
        // Replace special characters for regex
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('(', '\\(', $str);
        $str = str_replace(')', '\\)', $str);
        $str = str_replace('^', '\\^', $str);
        $str = str_replace('$', '\\$', $str);
        $str = str_replace('*', '\\*', $str);
        $str = str_replace('+', '\\+', $str);
        $str = str_replace('?', '\\?', $str);
        $str = str_replace('[', '\\[', $str);
        $str = str_replace(']', '\\]', $str);
        $str = str_replace('|', '\\|', $str);
        return $str;
    }

    function show($params)
    {
        static $inputid_prefix = 1;
        $inputid_prefix++;
        $separator = !empty($params['frm_separator']) ? $params['frm_separator'] : ",";
        $html = "
<script type=\"text/javascript\" >
var synccheck{$inputid_prefix}_{$params['name']} = function (id)
{
    var divitems = document.getElementById(id).childNodes;
    var sep = '';
    var str = '';
    divitems.forEach(function(divitem) {
        var items = divitem.childNodes;
        items.forEach(function(item) {
            if (item.checked === true) {
                str = str + sep + item.value;
                sep = '$separator';
            }
        });
    });
    document.getElementById('xmldbvalue{$inputid_prefix}_{$params['name']}').value = str;
}

var dofilter{$inputid_prefix}_{$params['name']} = function(id, text) {
    var divitems = document.getElementById(id).childNodes;
    divitems.forEach(function(divitem) {
        try {
            var strItem = divitem.innerHTML.replace(/(<([^>]+)>)/ig, \"\");
            if (strItem !== \"\") {
                divitem.style.display = strItem.toLowerCase().search(text.toLowerCase()) >= 0 ? 'block' : 'none';
            }
        } catch (e) {}
    });
}
</script>
";

        $tooltip = $params['frm_help'];
        $name = $params['name'];
        $value = $params['value'];
        $options = array();
        if (!isset($params['options'])) {
            $options = explode(",", $params['frm_options']);
            $optionslang = array();
            if (isset($params['frm_options_' . $params['lang']])) {
                $optionslang = explode(",", $params['frm_options_' . $params['lang']]);
            }
        } else {
            $_options = $params['options'];
            if (is_array($_options)) {
                foreach ($_options as $opt) {
                    $options[$opt['value']] = $opt['title'];
                }
            }
        }
        $i = 0;
        natsort($options);
        $jexecute = "synccheck{$inputid_prefix}_{$params['name']}('xmldbck{$inputid_prefix}_{$params['name']}');";

        $html .= "<div class=\"input-group \">";
        $html .= "<input class=\"form-control\" style=\"width:100px;display:inline\" placeholder=\"" . FN_Translate("search") . "\" 
            onclick=\"document.getElementById('xmldbck{$inputid_prefix}_{$params['name']}').style.display='block';\" id=\"filter{$inputid_prefix}_{$params['name']}\" 
                type=\"text\" 
                onkeyup=\"dofilter{$inputid_prefix}_{$params['name']}('xmldbck{$inputid_prefix}_{$params['name']}',this.value);\"
               
 />";
        $html .= "<button class=\"btn btn-primary dropdown-toggle\" 
            id=\"btn{$inputid_prefix}_{$params['name']}\" 
            type=\"button\" 
            onclick=\"var elem = document.getElementById('xmldbck{$inputid_prefix}_{$params['name']}'); elem.style.display = (elem.style.display === 'none' || elem.style.display === '') ? 'block' : 'none'; return false;\" >" . FN_Translate("add") . "</button>";

        $html .= "</div>";
        $html .= "<input class=\"form-control\"
            onclick=\"var elem = document.getElementById('xmldbck{$inputid_prefix}_{$params['name']}'); elem.style.display = (elem.style.display === 'none' || elem.style.display === '') ? 'block' : 'none'; return false;\" 
                type=\"text\" id=\"xmldbvalue{$inputid_prefix}_{$params['name']}\" 
                    name=\"$name\" 
                        value=\"$value\" />";
        $html .= "<div id=\"xmldbck{$inputid_prefix}_{$params['name']}\" style=\"z-index:1;display:none;position:absolute;background-color:#ffffff;color:#000000;border:1px inset;height:150px;width:500px;overflow:auto;\">";
        foreach ($options as $k => $option) {
            $jsonclick = "onclick=\"$jexecute\" onchange=\"$jexecute\" ";
            $sel = "";
            $toption = $option;
            if (isset($optionslang[$i]) && $optionslang[$i] != "") {
                $toption = $optionslang[$i];
            }
            $separator_enc = $this->encode_preg($separator);

            $k_enc = $this->encode_preg($k);

            $value = trim(ltrim($value));
            if (FN_erg("^$k_enc\$", $value) || FN_erg("{$separator_enc}$k_enc{$separator_enc}", $value) || FN_erg("^$k_enc{$separator_enc}", $value) || FN_erg("{$separator_enc}$k_enc\$", $value)) {
                $sel = "checked=\"checked\"";
            }
            $html .= "<div style=\"white-space:nowrap\" ><input id=\"xmldbck{$inputid_prefix}_{$params['name']}$k\" $sel $jsonclick type=\"checkbox\" value=\"$k\" /><label for=\"xmldbck{$inputid_prefix}_{$params['name']}$k\">$toption</label></div> ";
            $i++;
        }
        $html .= "</div> ";

        $html .= "<script type=\"text/javascript\"  >setTimeout(\"$jexecute\",0);</script>";
        return $html;
    }


    /**
     * 
     * @param type $params
     * @return string
     */
    function view($params)
    {
        global $_FN;
        require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
        $separator=!empty($params['frm_separator']) ? $params['frm_separator'] : ",";
        $mod=str_replace("ctl_","",$params['foreignkey']);
        //$config=FN_LoadConfig("modules/dbview/config.php",$mod);
        // $dbview=new FNDBVIEW($config);
        $table=FN_XMDBTable($params['foreignkey']);
        $bykey=array();

        foreach($params['options'] as $k=> $v)
        {
            $valueEnc=str_replace(".","__DOT__",$v['value']);
            $bykey["{$valueEnc}"]=$v['title'];
        }
        $htmlout=false;
        $htmlout_s=array();
        $caves=explode("$separator",$params['values'][$params['name']]);
        foreach($caves as $cave)
        {
            if (isset($bykey[$cave]))
            {
                $item=$table->GetRecord(array($params['fk_link_field']=>$cave));
                $primarykey=$table->primarykey;
                $link=FN_RewriteLink("index.php?mod=$mod&amp;op=view&amp;id={$item[$primarykey]}");
                $htmlout_s[]="\n<li><a href=\"$link\" >{$bykey[$cave]}</a></li> ";
            }
        }
        if (count($htmlout_s) > 0)
        {
            $htmlout="<ul>".implode("",$htmlout_s)."</ul>";
        }
        return $htmlout;
    }

}