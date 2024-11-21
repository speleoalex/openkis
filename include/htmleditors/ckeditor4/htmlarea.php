<?php
/**
 * 
 * @package Flatnux-htmleditors-ckeditor4
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2017
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */

/**
 * 
 * @global type $_FN
 * @staticvar boolean $jsfck
 * @param type $name
 * @param int $cols
 * @param int $rows
 * @param type $text
 * @param type $defaultdir
 * @param type $editor_params
 * @return string
 */
function FN_HtmlHtmlArea($name,$cols,$rows,$text="",$defaultdir="",$editor_params=false)
{
    global $_FN;
    
    $siteurl=$_FN['siteurl'];
    $filetomod=FN_GetParam("file",$_GET);
    $htmleditor = basename(__DIR__);
    if ($defaultdir== "")
    {
        if (preg_match('/^sections\//',$filetomod))
        {
            $dirtoopen=dirname($filetomod);
        }
        else
            $dirtoopen=$_FN['datadir'];
    }else
    {
        $dirtoopen=$defaultdir;
    }
    $str=str_replace("&","&amp;",$text);
    $str=str_replace("<","&lt;",$str);
    $str=str_replace(">","&gt;",$str);
    $l="en";
    if (file_exists("include/htmleditors/{$htmleditor}/ckeditor/lang/{$_FN['lang']}.js"))
        $l=$_FN['lang'];
    $config['toolbar']="Full";
    $config['fckcolor']="d4d7d0";
    $config=FN_LoadConfig("include/htmleditors/{$htmleditor}/config.php");
    
    
    if (empty($config['skin']) || !file_exists("include/htmleditors/{$htmleditor}/ckeditor/skins/{$config['skin']}"))
    {
      //  $config['skin']="moono-lisa";
    }
    static $jsfck=true;
    $html="";
    if ($jsfck)
    {
        $html.="
<script type=\"text/javascript\" src=\"{$siteurl}include/htmleditors/{$htmleditor}/ckeditor/ckeditor.js\"></script>";
    }

    $h=200;
    $w="99%";
    if ($cols== "auto")
    {
        $w="99%";
        $cols=80;
    }
    elseif (intval($cols)!= 0)
        $w=$cols * 10;
    if (intval($rows)!= 0)
        $h=$rows * 10 + 200;
    if (strpos("%",chr($h))=== false && strpos("px",chr($h))=== false)
    {
        $h.="px";
    }
    if (strpos("%","$w")=== false && strpos("px","$w")=== false)
    {
        $w.="px";
    }
    if ($cols== 0)
        $cols=80;
    if ($rows== 0)
        $rows=5;
    $html.="
<textarea  id=\"fckeditor$name\" name=\"$name\" cols=\"$cols\" rows=\"$rows\" >".$str."</textarea>
<script type=\"text/javascript\">
//<![CDATA[
var css = new Array();
";

    if (file_exists("themes/{$_FN['theme_default']}/ckeditor/style.css"))
        $html.="css[0]='{$siteurl}themes/{$_FN['theme_default']}/ckeditor/style.css?".time()."';";
    elseif (file_exists("themes/{$_FN['theme_default']}/style.css"))
        $html.="css[0]='{$siteurl}themes/{$_FN['theme_default']}/style.css';";
    if (!empty($editor_params['toolbar']))
    {
        $config['toolbar']=$editor_params['toolbar'];
    }
    if (file_exists("include/htmleditors/{$htmleditor}/toolbars/{$config['toolbar']}/toolbar.js"))
        $config['toolbar']=file_get_contents("include/htmleditors/{$htmleditor}/toolbars/{$config['toolbar']}/toolbar.js");
    else
        $config['toolbar']="Full";

    if (isset($editor_params['css_file']))
    {

        $html.="css[1]='{$editor_params['css_file']}';";
    }
    $fullpage="false";
    if (isset($editor_params['fullpage']) && $editor_params['fullpage'])
    {
        $fullpage="true";
    }
    $html.="
editor = CKEDITOR.replace( 'fckeditor$name',
    {
        image2_prefillDimensions : false,
        allowedContent : true,
        language : '$l',
        skin : '{$config['skin']}',
        baseHref : '{$_FN['siteurl']}',
        width: '$w',
        height:'$h',
        toolbar: {$config['toolbar']},
        filebrowserBrowseUrl : '{$_FN['siteurl']}filemanager.php?mode=t&filemanager_editor={$htmleditor}&dir=$dirtoopen',
        filebrowserImageBrowseUrl : '{$_FN['siteurl']}filemanager.php?mode=t&filemanager_editor={$htmleditor}&dir=$dirtoopen&mime=image',
        filebrowserUploadUrl : '{$_FN['siteurl']}filemanager.php?mode=t&filemanager_editor={$htmleditor}&dir=$dirtoopen',
        filebrowserWindowWidth : '640',
        filebrowserWindowHeight : '480',
        fullPage : $fullpage ,
        contentsCss:css, 
        uiColor: '{$config['fckcolor']}'
     } );
//]]>

CKEDITOR.instances.fckeditor$name.on('change', function() { 
    var text = document.getElementById('fckeditor$name').value;
    document.getElementById('fckeditor$name').value =      editor.getData();
    try{
    set_changed();
    }catch(e){
    }
    
});

</script>
";
    if ($jsfck && FN_IsAdmin())
    {
        $html.="<div style=\"text-align:right\" ><a href=\"{$siteurl}?opt=include/htmleditors/{$htmleditor}/config.php\">  ".FN_i18n("configure")." ckeditor </a></div>";
    }
    $jsfck=false;
    return $html;
}

?>