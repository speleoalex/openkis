<?php
/**
 * 
 * @package Finis-htmleditors-ckeditor4
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
    if (file_exists("{$_FN['src_finis']}/include/htmleditors/{$htmleditor}/ckeditor/lang/{$_FN['lang']}.js"))
        $l=$_FN['lang'];
    $config['toolbar']="Full";
    $config['fckcolor']="d4d7d0";
    $config=FN_LoadConfig("{$_FN['src_finis']}/include/htmleditors/{$htmleditor}/config.php");
    
    
    if (empty($config['skin']) || !file_exists("{$_FN['src_finis']}/include/htmleditors/{$htmleditor}/ckeditor/skins/{$config['skin']}"))
    {
      //  $config['skin']="moono-lisa";
    }
    static $jsfck=true;
    $html="";
    if ($jsfck)
    {
        $html.="
<script>
function loadQuillAndInitializeEditor() {
      // Aggiungere il CSS di Quill
      var link = document.createElement('link');
      link.href = 'https://cdn.quilljs.com/1.3.6/quill.snow.css';
      link.rel = 'stylesheet';
      document.head.appendChild(link);
      // Aggiungere il JS di Quill
      var script = document.createElement('script');
      script.src = 'https://cdn.quilljs.com/1.3.6/quill.js';
      script.onload = function() {

      };
      document.body.appendChild(script);
    }

    function initializeEditor(id) {
        id='#'+id;
        varidtextarea='textarea'+id;
        var quill = new Quill(id, {
            theme: 'snow'
        });
        quill.on('text-change', function() {

           // document.getElementById(varidtextarea).html(quill.getContents());
        });
        }
    loadQuillAndInitializeEditor();


    // Chiamare la funzione per inizializzare l'editor
  </script>";
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
    $html.="<div  id=\"htmleditor$name\" >$str</div>
    <textarea id=\"textareahtmleditor$name\"  name=\"$name\" cols=\"$cols\" rows=\"$rows\" >".$str."</textarea>
<script>
addEventListener(\"load\", (event) => {initializeEditor(\"htmleditor$name\");});
</script>
";
    if ($jsfck && FN_IsAdmin())
    {
//        $html.="<div style=\"text-align:right\" ><a href=\"{$siteurl}?opt=include/htmleditors/{$htmleditor}/config.php\">  ".FN_i18n("configure")." ckeditor </a></div>";
    }
    $jsfck=false;
    return $html;
}

?>