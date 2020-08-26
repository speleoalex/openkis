<?php

/**
 * multi checkbox  field
 */
class xmldbfrm_field_multicave
{

    function show($params)
    {
        static $inputid_prefix=1;
        $inputid_prefix++;
        $html="
<script type=\"text/javascript\" >
var synccheck{$inputid_prefix}_{$params['name']} = function (id)
{
    var divitems = document.getElementById( id ).childNodes;
    var sep='';
    var str='';
    for (var i in divitems)
    {
        items=divitems[i].childNodes
        for (var i in items)
        {
            if (items[i].checked == true )
            {
                str = str+ sep + items[i].value
                sep=',';
            }
        }
    }
    document.getElementById('xmldbvalue{$inputid_prefix}_{$params['name']}').value=str;
    document.getElementById('xmldbvalue{$inputid_prefix}_{$params['name']}_txt').innerHTML=str;
        
}

var dofilter{$inputid_prefix}_{$params['name']} = function(id,text){
	var divitems = document.getElementById( id ).childNodes;
	var sep='';
	for (var i in divitems)
	{
            try{
            var strItem = divitems[i].innerHTML.replace(/(<([^>]+)>)/ig,\"\");
            if (strItem !=\"\")
            {
                if (strItem.toLowerCase().search(text.toLowerCase())>=0)
                {
                    divitems[i].style.display='block';
                }
                else{
                    divitems[i].style.display='none';
                
                }
            }
            }catch(e){}
	}
    
}
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
        natsort($options);
        $jexecute="synccheck{$inputid_prefix}_{$params['name']}('xmldbck{$inputid_prefix}_{$params['name']}');";

        $html.="<div class=\"input-group \">";
        $html.="<input class=\"form-control\" style=\"width:100px;display:inline\" placeholder=\"".FN_Translate("search")."\" 
            onclick=\"$('#xmldbck{$inputid_prefix}_{$params['name']}').show();\" id=\"filter{$inputid_prefix}_{$params['name']}\" 
                type=\"text\" 
                onkeyup=\"dofilter{$inputid_prefix}_{$params['name']}('xmldbck{$inputid_prefix}_{$params['name']}',this.value);\"
               
 />";
        $html.="<button class=\"btn btn-primary dropdown-toggle\" 
            id=\"btn{$inputid_prefix}_{$params['name']}\" 
            type=\"button\" 
            onclick=\"$('#xmldbck{$inputid_prefix}_{$params['name']}').toggle();return false;\" >".FN_Translate("add")."</button>";
        
        $html.="</div>";
        $html.="<input class=\"form-control\"
            onclick=\"$('#xmldbck{$inputid_prefix}_{$params['name']}').toggle();return false;\" 
                type=\"text\" id=\"xmldbvalue{$inputid_prefix}_{$params['name']}\" 
                    name=\"$name\" 
                        value=\"$value\" />";        
        //$html.="<div><span onclick=\"$('#xmldbck{$inputid_prefix}_{$params['name']}').toggle();\" id=\"xmldbvalue{$inputid_prefix}_{$params['name']}_txt\"></span>&nbsp;</div>";
        $html.="<div id=\"xmldbck{$inputid_prefix}_{$params['name']}\" style=\"z-index:1;display:none;position:absolute;background-color:#ffffff;color:#000000;border:1px inset;height:150px;width:500px;overflow:auto;\">";        
        foreach($options as $k=> $option)
        {
            $jsonclick="onclick=\"$jexecute\" onchange=\"$jexecute\" ";
            $sel="";
            $toption=$option;
            if (isset($optionslang[$i]) && $optionslang[$i]!= "")
                $toption=$optionslang[$i];
            if (FN_erg("^$k\$",$value) || FN_erg(",$k,",$value) || FN_erg("^$k,",$value) || FN_erg(",$k\$",$value))
                $sel="checked=\"checked\"";
            $html.="<div style=\"white-space:nowrap\" ><input id=\"xmldbck{$inputid_prefix}_{$params['name']}$k\" $sel $jsonclick type=\"checkbox\" value=\"$k\" /><label for=\"xmldbck{$inputid_prefix}_{$params['name']}$k\">$toption</label></div> ";
            $i++;
        }
        $html.="</div> ";


        $html.="<script type=\"text/javascript\"  >setTimeout(\"$jexecute\",0);</script>";
        return $html;
    }

    /**
     * 
     * @param type $params
     * @return string
     */
    function view($params)
    {
        require_once "modules/dbview/FNDBVIEW.php";
        $mod=str_replace("ctl_","",$params['foreignkey']);
        //$config=FN_LoadConfig("modules/dbview/config.php",$mod);
       // $dbview=new FNDBVIEW($config);
        $table=FN_XmlTable($params['foreignkey']);
        $bykey=array();
        foreach ($params['options'] as $k=>$v)
        {
            $bykey[$v['value']]=$v['title'];
        }
        $htmlout=false;
        $htmlout_s=array();
        $caves=explode(",",$params['values'][$params['name']]);
        foreach($caves as $cave)
        {
            if (isset($bykey[$cave]))
            {   
                $item=$table->GetRecord(array($params['fk_link_field']=> $cave));
                $link=FN_RewriteLink("index.php?mod=$mod&amp;op=view&amp;id={$item['id']}");
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

?>