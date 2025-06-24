<?php

//---------password--------------------------------------->
class xmetadbfrm_field_md5password
{

    function show($params)
    {
        $toltips=($params['frm_help']!= "") ? "title=\"".$params['frm_help']."\"" : "";
        return "<input $toltips value=\"\" autocomplete=\"off\" name=\"".$params['name']."\" type=\"password\" />\n";
    }

    function formtovalue($str)
    {
        return md5($str);
    }

}

