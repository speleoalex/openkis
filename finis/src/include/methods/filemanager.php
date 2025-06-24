<?php
ob_start();
global $_FN;
ini_set("display_errors","on");
error_reporting(E_ALL);
$FINIS = new FINIS();
$style = file_get_contents("{$_FN['src_finis']}/modules/filemanager/style.css");
echo '<!DOCTYPE html>
<html><head>

    <meta name="viewport" content="initial-scale = 1.0, maximum-scale = 1.0, user-scalable = no, width = device-width">    
    <style>
    '.$style.'
        html, body {            height:                 100% } 
        * {
            font-family: Verdana, Arial;
            font-size: 12px;
            margin: 0px;
            padding: 0px;
        }
        td {
            font: 12px Arial, Helvetica, sans-serif;
        }
        form {
            font: 12px Arial, Helvetica, sans-serif;
        }
        body {
            background-color: #ffffff;
            color: #000000;
            margin: 0px;
            padding: 0px;
            top:0px;
            left:0px;
                  }
        a {
            text-decoration: none;
            color: #000000;
        }
        a:hover {
            text-decoration: underline;
        }
        form {
            border: 0px;
        }
    </style>
    <script type="text/javascript">
        function check(url)
        {
            if (confirm("'.FN_i18n("are you sure you want to do it?").'"))
                window.location = url;
        }
    </script>
    <title>Filemanager</title>
</head>';

header("Content-Type: text/html; charset={$_FN['charset_page']}");
$sess_filemanager_editor=FN_GetParam("filemanager_editor",$_GET,"html");
$sess_filemanager_editor=basename($sess_filemanager_editor);
FN_PathSite("{$_FN['src_finis']}/modules/filemanager/style.css");
FN_PathSite("{$_FN['src_finis']}/modules/filemanager/view-list-details.png");
FN_PathSite("{$_FN['src_finis']}/modules/filemanager/view-list-icons.png");
FN_PathSite("{$_FN['src_finis']}/modules/filemanager/folder-new.png");

if (strstr($sess_filemanager_editor,"..") || strstr($sess_filemanager_editor,'/') || strstr($sess_filemanager_editor,'\\'))
{
    $sess_filemanager_editor=basename($sess_filemanager_editor);

}
if ($sess_filemanager_editor!= "" && file_exists("{$_FN['src_finis']}/include/htmleditors/$sess_filemanager_editor/filemanager.php"))
{
    include ("{$_FN['src_finis']}/include/htmleditors/$sess_filemanager_editor/filemanager.php");
    
}
else
{
    echo "<body>";
    $opener=FN_GetParam("opener",$_GET);
    echo "
<script type=\"text/javascript\" >
// function called by the filemanager when the file is selected
function insertElement(URL) {
";
    if ($opener!= "")
    {
        echo "
    try{
    window.opener.document.getElementById('$opener').value = URL;
    }catch (e){alert(e)}";
    }
    echo "window.close();
}
</script>
";
    $FINIS->runFolder("{$_FN['src_finis']}/modules/filemanager/",false);
//    echo FN_HtmlContent("{$_FN['src_finis']}/modules/filemanager/");
    $mime=FN_GetParam("mime",$_GET,"html");
    $dir=FN_GetParam("dir",$_GET,"html");
    $dir=FN_RelativePath($dir);
    if (!empty($_GET['linklocalfs']) && empty($_GET['opmod']))
    {
        echo "<button onclick=\"insertElement('$dir"."')\" >".FN_i18n("insert")."</button>";
    }
    if (!empty($_GET['linklocalpages']) && empty($_GET['opmod']))
    {
        $html="<div style=\"position:absolute;bottom:0px;\">".FN_i18n("link to local page",false,"Aa").": <select id=\"sectionstree\" >";
        $sections=FN_GetSections(false,true,true,true,true);
//sort sections --------------------------------------------------------------->
        $sections=FNCC_SortSectionsByTree("",$sections);
//sort sections ---------------------------------------------------------------<		
        foreach($sections as $section)
        {
            $margin=(count($section['path']) * 10)."px";
            $html.="<option title=\"".htmlspecialchars($section['title'])."\" style=\"font-size:10px;width:200px;overflow:hidden;padding-left:$margin;\" value=\"{$_FN['siteurl']}index.php?mod={$section['id']}\" >".htmlspecialchars($section['title'])."</option>";
        }
        $html.="</select>";
        echo "$html<button onclick=\"insertElement(document.getElementById('sectionstree').options[document.getElementById('sectionstree').selectedIndex].value)\">".FN_i18n("insert")."</button>";
        echo "</div>";
    }

    echo "</body></html>";
    
}

