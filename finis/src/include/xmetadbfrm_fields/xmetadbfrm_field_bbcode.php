<?php
//---------bbcode--------------------------------------->
class xmetadbfrm_field_bbcode
{

    function __construct()
    {
        
    }

    function show($params)
    {
        $html="";
        $rows=isset($params['frm_rows']) ? $params['frm_rows'] : 4;
        $cols=isset($params['frm_cols']) ? $params['frm_cols'] : 20;
        $tooltip=$params['frm_help'];
        $onkeyup="";
        $style="";
        if ($cols == "auto")
        {
            $cols="80";
            $style="width:90%;";
        }
        if ($rows == "auto")
        {
            $onkeyup="onkeyup=\"if (this.scrollHeight >= this.offsetHeight){ this.style.height = 10 + this.scrollHeight+'px';}\" ";
            $onkeyup.="onfocus=\"if (this.scrollHeight >= this.offsetHeight){ this.style.height = 10 + this.scrollHeight+'px';}\" ";
            $onkeyup.="style=\"overflow:auto;height:30px;\"";
            $rows=3;
        }
        $html="";
        $html.=FN_HtmlBbcodesJs();
        $html.=FN_HtmlBbcodesPanel($params['name'],"formatting");
        $html.=FN_HtmlBbcodesPanel($params['name'],"emoticons");
        $html.="<br /><textarea style=\"$style\" $onkeyup title=\"$tooltip\" cols=\"".$cols."\"  rows=\"".$rows."\"  name=\"{$params['name']}\"  >";
        $html.=htmlspecialchars($params['value']);
        $html.="</textarea>";
        return $html;
    }

    function view($params)
    {
        $html="";
        $html.=FN_Tag2Html(str_replace("\n","<br />",htmlspecialchars($params['value'])));
        return $html;
    }

}

