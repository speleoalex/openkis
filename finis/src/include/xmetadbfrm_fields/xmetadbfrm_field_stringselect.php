<?php
class xmetadbfrm_field_stringselect
{

    function __construct()
    {
        
    }

    function show($params)
    {
        static $ik=0;
        $id_field=$ik."_".$params['name'];
        $options=false;
        $html="<div onmouseover=\"selectover{$id_field}=true\" onmouseout=\"selectover{$id_field}=false\" >";

        if ($options == false)
        {
            $options=array();
            $options_tmp=is_array($params['options']) ? $params['options'] : array();
            /*
              $all = $params['fieldform']->xmltable->GetRecords(false,false,false,false,false,"{$params['name']}");
              // GetRecords($restr = false,$min = false,$length = false,$order = false,$reverse = false,$fields = false)
              foreach ($all as $v)
              {
              $options[]=$v[$params['name']];
              }
              $options = array_unique($options);
             */
            foreach($options_tmp as $option_tmp)
            {
                $options[$option_tmp['value']]=$option_tmp;
            }
            $options=FN_ArraySortByKey($options,"title");

            //dprint_r($options);
            $html.="
<script type=\"text/javascript\" defer=\"defer\">
selectover{$id_field} = false;
xmetadb_field_stringselect=function (idselect,value)
{
    var select = document.getElementById(idselect);
    var alloptions = select.options;
    var tosearch = false;
    var i = 0;
    var found = false;
    for (i in alloptions)
    {
        if (alloptions[i].value != undefined && alloptions[i].value != '')
        {
            tosearch = ''+alloptions[i].value.toLowerCase() + ' '+ alloptions[i].text.toLowerCase();
			try{
            if (tosearch != '' && tosearch.search(value.toLowerCase())<0)
            {
                alloptions[i].disabled = 'disabled';
                alloptions[i].style.display = 'none';
            }
            else
            {
                alloptions[i].style.display = 'block';
                found=true;
                alloptions[i].disabled = false;
                
            }
			}catch(e){alloptions[i].style.display = 'block';
                found=true;
                alloptions[i].disabled = false;}
       }
    }
    if (found)
    {
        select.style.display='block';
    }
    else
    {
        select.style.display='none';
    }
};

</script>

";
        }
        $ik++;
        $size=isset($params['frm_size']) ? $params['frm_size'] : 30;
        $l=(!empty($params['size'])) ? "maxlength=\"{$params['size']}\"" : "";
        $html.="<span style=\"position:relative;\"><input  
        id=\"xmetadb_{$id_field}\" $l 
        title=\"{$params['frm_help']}\" 
        size=\"".$size."\" name=\"{$params['name']}\"  
        style=\"margin-right:0px;\" 
        autocomplete=\"off\" 
        onkeyup=\"xmetadb_field_stringselect('xmetadb_{$id_field}_s',this.value)\" 
        onfocus=\"xdb_show_menu('xmetadb_{$id_field}_s');\"   
        onclick=\"xdb_show_menu('xmetadb_{$id_field}_s');\" 
        id=\"xmetadb_{$id_field}\" $l title=\"{$params['frm_help']}\" size=\"".$size."\" 
        name=\"{$params['name']}\"  ";
        if (!empty($params['frm_uppercase']) && $params['frm_uppercase'] == "uppercase")
        {
            $html.="onchange=\"this.value=this.value.toUpperCase();\"";
        }
        elseif (!empty($params['frm_uppercase']) && $params['frm_uppercase'] == "lowercase")
        {
            $html.="onchange=\"this.value=this.value.toLowerCase();\"";
        }
        $html.="value=\"".str_replace('"','&quot;',$params['value'])."\" />";
        $html.="<img style=\"margin-left:0px;vertical-align:middle;border-left:0px;cursor:pointer\" onclick=\"xdb_toggle_menu('xmetadb_{$id_field}_s')\" alt=\"+\" src=\"".FN_FromTheme("images/fn_down.png")."\" /><br />";


        if (is_array($options))
        {
            $size=count($options);
            if ($size<= 1)
                $size=2;

            if ($size > 5)
                $size=5;
            $html.="<select  id=\"xmetadb_{$id_field}_s\"  style=\"z-index:1;position:absolute;display:none\" size=\"$size\"  onchange=\"document.getElementById('xmetadb_{$id_field}').value = this.options[this.selectedIndex].value;this.style.display='none'\" >";
            foreach($options as $option)
            {
                $value=htmlentities($option['value'],ENT_QUOTES,$params['fieldform']->charset_page);
                $html.="<option onclick=\"xmetadb_{$id_field}_s.onchange();\" value=\"$value\">{$option['title']}</option>";
            }
            $html.="</select>";
        }
        $html.="&nbsp;</span>";
        $html.="
<script type=\"text/javascript\" defer=\"defer\">
xdb_toggle_menu=function(id){
	if(document.getElementById(id).style.display == 'none')
	{
		xdb_show_menu (id);
	} 
	else 
	{
		xdb_hide_menu(id);	
	}
};
xdb_show_menu=function(id)
{
	document.getElementById(id).style.display = 'block';
	
};
xdb_hide_menu=function(id)
{
	document.getElementById(id).style.display ='none';
	
};
xmetadb_{$id_field}_s_hideshow = function()
{
	if (!selectover{$id_field})
		xdb_hide_menu('xmetadb_{$id_field}_s');
};
oldonclick = document.getElementsByTagName('body')[0].getAttribute('onclick');
document.getElementsByTagName('body')[0].setAttribute('onclick',oldonclick+\";xmetadb_{$id_field}_s_hideshow()\");
</script>

";
        $html.="</div>";
        return $html;
    }

    function view($params)
    {
        //$params['options']=  array_unique($params['options']);
        //dprint_r($params['value']);
        $html=htmlspecialchars($params['value']);
        return $html;
    }

}