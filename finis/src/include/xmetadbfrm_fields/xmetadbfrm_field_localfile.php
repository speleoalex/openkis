<?php


//---------localfile--------------------------------------->
/**
 * 
 * @author alessandro
 *
 */
class xmetadbfrm_field_localfile
{

    function show($params)
    {
        global $_FN;
        $fmpath=isset($params['frm_path']) ? $params['frm_path'] : "{$_FN['datadir']}";
        $mime=isset($params['frm_mime']) ? "mime={$params['frm_mime']}&linklocalfs=1&" : "mime=all&linklocalfs=1&";
        $toltips=($params['frm_help']!= "") ? "title=\"".$params['frm_help']."\"" : "";
        $html="";
        $size=isset($params['frm_size']) ? $params['frm_size'] : 30;
        $idop="localfile{$params['name']}";
        $oldvalues=$params['oldvalues'];
        if ($params['value']!= "")
        {
            if (is_dir($params['value']))
                $fmpath=htmlspecialchars($params['value']);
            else
                $fmpath=htmlspecialchars(dirname($params['value']));
        }
        $html.="<input $toltips size=\"".$size."\" name=\"{$params['name']}\" id=\"$idop\" value=\"".str_replace('"','&quot;',$params['value'])."\" />";
        $onclick="tmp = window.open('{$_FN['siteurl']}filemanager.php?{$mime}dir={$fmpath}&local=1&mode=t&filemanager_editor=local&opener=$idop','filemanager','toolbar= 0,location= 0,directories= 0,status= 0,menubar= 0,scrollbars= 1,resizable= 1,width=640,height=480');tmp.focus();return false;";
        $html.="<button onclick=\"$onclick\">".fn_i18n("search")."</button>";
        return $html;
    }

    function view($params)
    {
        global $_FN;
        $html="";
        $val=htmlspecialchars(FN_RelativePath($params['value']));
        if (is_dir($params['value']))
            $html.="<iframe style=\"border:0px;height:400px;width:500px\" src=\"{$_FN['siteurl']}filemanager.php?dir=$val&mime=all\">$val</iframe>";
        else
            $html.="<a href=\"$val\">$val</a>";
        return $html;
    }

}

