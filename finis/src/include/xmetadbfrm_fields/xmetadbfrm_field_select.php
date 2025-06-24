<?php
//---------select--------------------------------------->
class xmetadbfrm_field_select
{

    function __construct()
    {
        
    }

    function show($params)
    {
        $html = "";
        $toltips = "";
        $fieldform_values = $params;
        $script = "";
        $scriptfirst = "";
        $optionname = "";
        $divid = "";
        $attributes = isset($params["htmlattributes"]) ? $params["htmlattributes"] : "";
        $thumbsize = isset($fieldform_values['thumbsize']) ? $fieldform_values['thumbsize'] : "";
        if (isset($fieldform_values['frm_show_image']) && $fieldform_values['frm_show_image'] != "")
        {
            $divid = "fkimg_" . $fieldform_values['name'];
            $script = "onchange=\"this.options[this.selectedIndex].onfocus()\"";
            $script .= " onkeyup=\"this.options[this.selectedIndex].onfocus()\"";
            $scriptfirst = "onfocus=\"document.getElementById('$divid').innerHTML = ''\"";
        }
        $html .= "<select $attributes $toltips $script name=\"" . $fieldform_values['name'] . "\" >";

        $htmlfirst = "\n<option $scriptfirst";
        $htmlfirst .= " label=\"\" value=\"\">----</option>";

        $options = array();
        $optionselected = null;
        $oldvalimage = "";
        $htmloptions = "";
        if (is_array($fieldform_values['options']))
        {
            foreach ($fieldform_values['options'] as $option)
            {
                $options[$option['value']]['name'] = ucfirst($option['title']);
                $options[$option['value']]['value'] = $option['value'];
                if ($option['value'] === "")
                {
                    $htmlfirst = "";
                }
                if ($option['value'] == $fieldform_values['value']) //gestire == e ===
                {
                    $optionselected = $option['value'];
                }
                if (isset($fieldform_values['frm_show_image']) && $fieldform_values['frm_show_image'] != "")
                {
                    $options[$option['value']]['image'] = $option['frm_show_image'];
                    $options[$option['value']]['thumbsize'] = isset($option['thumbsize']) ? $option['thumbsize'] + 3 : 0;
                    if ($thumbsize)
                    {
                        $options[$option['value']]['thumbsize'] = $thumbsize;
                    }
                }
            }
        }
        $options = xmetadb_array_natsort_by_key($options, "name");
        $himg = 0;
        foreach ($options as $option)
        {
            $optionname = $jj = "";
            if (isset($option['image']) && $option['image'] != "")
                $optionname = "<img style='padding:0px;border:0px;margin:0px;' src='{$params['fieldform']->siteurl}" . $option['image'] . "' alt='' />";
            if ($option['value'] == $optionselected)
            {
                $selected = " selected=\"selected\" ";
                $oldvalimage = $optionname;
            }
            else
                $selected = "";
            if (isset($option['thumbsize']) && $option['thumbsize'] > $himg)
            {
                $himg = $option['thumbsize'];
            }
            if ($divid != "")
                $jj = "onfocus=\"document.getElementById('$divid').innerHTML = '" . addslashes($optionname) . "';\"";
            $htmloptions .= "\n\t<option $selected $jj value=\"" . $option['value'] . "\" >" . $option['name'] . "</option>";
        }


        $html .= "\n$htmlfirst$htmloptions</select>\n";
        $himg += 5;
        if (isset($fieldform_values['frm_show_image']) && $fieldform_values['frm_show_image'] != "")
        {
            $html .= "\n<div style=\"height:$himg" . "px;overflow:auto;padding:0px;\" id=\"$divid\">" . $oldvalimage . "</div>";
        }
        //-----filtro su altro elemento----->
        if (isset($fieldform_values['fk_filter_field']) && $fieldform_values['fk_filter_field'] != "")
        {
            $clausule = explode("=", $fieldform_values['fk_filter_field']);
            if (isset($clausule[1]))
            {
                //prende tutte le clausule separate da virgola
                $clausules = explode(",", $fieldform_values['fk_filter_field']);
                $restr = array();
                foreach ($clausules as $claus_item)
                {
                    $clausule = explode("=", $claus_item);
                    if (isset($clausule[1]))
                    {
                        $cname2 = $clausule[1];
                        //se e' di tipo pippo='pippo'
                        if ($cname2[0] == "'" && $cname2[strlen($cname2) - 1] == "'")
                        {
                            
                        }
                        else
                        {
                            $html .= "<script type=\"text/javascript\" >
try{
var el{$fieldform_values['name']}=document.getElementsByName('{$clausule[1]}')[0];
var options=document.getElementsByName('{$fieldform_values['name']}')[0];
el{$fieldform_values['name']}.onchange=function()
{
	var inp;
	inp = document.createElement('input');
	inp.type='text';
	inp.name='__NOSAVE';
	inp.value='__NOSAVE';
	var div;
	div = document.createElement('div');
	div.innerHTML='loading...';
	try{
		div.style.backgroundColor='#000000';
		div.style.color='#ffffff';
		div.style.display='block';
		div.style.position='absolute';
		div.style.width='100%';
		div.style.height='100%';
		div.style.top='0px';
		div.style.left='0px';
		div.style.opacity='0.8';
		div.style.filter='alpha(opacity=80)';
		inp.style.position='absolute';
		inp.style.height='0px';
		inp.style.width='0px';
		inp.style.overflow='hidden';
	}
	catch(e)
	{
       // alert(e);
	}
	el{$fieldform_values['name']}.parentNode.appendChild(inp);
	document.getElementsByTagName('body')[0].appendChild(div);
	inp.form.submit();
}
}catch (e){
    // alert(e);
    }
</script>";
                        }
                    }
                }
            }
        }
//-----filtro su altro elemento-----<
        return $html;
    }

}

//---------select---------------------------------------<
