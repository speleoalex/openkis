<?php

/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$op = FN_GetParam("opt", $_GET);
$themetoedit = FN_GetParam("themetoedit", $_GET);
$edit = FN_GetParam("edit", $_GET);
$editconf = FN_GetParam("editconf", $_GET);
$editoptions = FN_GetParam("editoptions", $_GET);
$edit = FN_GetParam("edit", $_GET);

if ($themetoedit == "")
{
    $themetoedit = $_FN['theme_default'];
}

$path_config_file = "";
if (file_exists("{$_FN['src_finis']}/themes/$themetoedit/$edit"))
{
    $path_config_file = "{$_FN['src_finis']}/themes/$themetoedit/$edit";    
}
elseif (file_exists("{$_FN['src_application']}/themes/$themetoedit/$edit"))
{
    $path_config_file ="{$_FN['src_application']}/themes/$themetoedit/$edit";
    
}

//-----------edit theme-------------------------------------------------------->
if ($edit != "" && $path_config_file)
{
    FN_EditContent(
            "$path_config_file"
            , "?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;themetoedit=$themetoedit&amp;edit=$edit"
            , "?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;themetoedit=$themetoedit"
            , array("css_file" => "themes/$themetoedit/style.css", "fullpage" => true)
    );
}
//-----------edit theme--------------------------------------------------------<
elseif ($editconf != "")
{
    $theme = $themetoedit;
    if (count($_POST) > 0 && empty($_POST['copy_theme_from']) && empty($_POST['oldimageimage']))
    {
        FN_JsRedirect("?fnapp=controlcenter&mod={$_FN['mod']}&opt=$opt&themetoedit=$themetoedit&editconf=$editconf");
    }
    else
    {
        echo "<button onclick=\"resizeThumb(900,250)\">900x250</button>";
        echo "<button onclick=\"resizeThumb(800,600)\">800x600</button>";
        echo "<button onclick=\"resizeThumb(320,240)\">320x240</button>";
        echo "<button onclick=\"resizeThumb(250,900)\">250x900</button>";
        echo "<button onclick=\"resizeThumb(250,900)\">250x900</button>";
        echo "$themetoedit<iframe style=\"border:1px solid inset;height:250px;width:900px\" src=\"index.php?themepreview=$themetoedit\"></iframe>";
        echo "<br /><img alt=\"\" style=\"vertical-align:middle\" src=\"images/left.png\" /> <a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;themetoedit=$themetoedit\">" . FN_i18n("back") . "</a>";
    }
    echo FNCC_HtmlEditConfFile("$path_config_file/themes/{$theme}/config.php", "?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;themetoedit=$themetoedit&amp;editconf=$editconf", ""
    );
}
else
{

    $theme = FN_GetParam("theme", $_POST, "html");
    $list_themes = get_list_themes();
    if (0)
    {
        foreach ($list_themes as $theme)
        {
            $image = "images/px_transparent.png";
            if (file_exists("themes/$theme/screenshot.png"))
                $image = "themes/$theme/screenshot.png";
            echo "<button onclick=\"document.getElementById('thumb').src='{$_FN['siteurl']}index.php?theme=$theme';\" style=\"text-shadow:1px 1px 1px #ffffff;color:#000000;overflow:hidden;text-align:center;background-color:#ffffff;padding:0px;margin:2px;border:1px solid #dddddd;height:100px;width:150px;float:left;background-image:url($image)\">$theme</button>";
        }
        echo "<br style=\"clear:both\" />";
    }
    if ($theme != "" && file_exists("themes/$theme"))
    {
        $t = FN_XMDBTable("fn_settings");
        $t->UpdateRecord(array("varname" => "theme", "varvalue" => $theme));
    }
    echo "
<div>
	<form method=\"post\" action=\"\" name=\"feditth\">";
    echo FN_i18n("theme") . " : <select name=\"theme\" 
        onchange=\"window.location='?fnapp=controlcenter&mod=" . $_FN['mod'] . "&themetoedit='+ document.feditth.theme.options[document.feditth.theme.selectedIndex].text + '&amp;opt=$op'\" >";
    foreach ($list_themes as $theme_)
    {
        echo "\n<option ";
        if ($themetoedit == $theme_)
        {
            echo ' selected="selected" ';
        }
        echo ">$theme_</option>";
    }
    echo "</select>";
    echo "<button type=\"submit\" >" . FN_i18n("apply this theme") . "</button>";
    echo "
	</form>
</div>
";

    $theme = $themetoedit;
    if (count($_POST) > 0 && empty($_POST['copy_theme_from']) && empty($_POST['oldimageimage']))
    {
        FN_JsRedirect("?fnapp=controlcenter&mod={$_FN['mod']}&opt=$opt&themetoedit=$themetoedit&editconf=$editconf");
    }
    else
    {
        echo "
<script>
function resizeThumb(w,h){
	document.getElementById('thumb').style.width=w;
	document.getElementById('thumb').style.height=h;
}
</script>
";
        echo "<h3>" . FN_Translate("preview") . "</h3>";
        echo "<div style=\"line-height:35px;background-color:#ffffff\">";
        echo "<img style=\"vertical-align:middle\" src=\"" . FN_FinisPathToApplicationPath("controlcenter/sections/settings/look/monitor.png") . "\" />" . " ";
        echo "<button onclick=\"resizeThumb('100%','700px')\">Full</button>";
        echo "<button onclick=\"resizeThumb('1024px','768px')\">1024x768</button>";
        echo "<button onclick=\"resizeThumb('800px','600px')\">800x600</button>";
        echo "<button onclick=\"resizeThumb('320px','240px')\">320x240</button>";
        echo "<button onclick=\"resizeThumb('640px','480px')\">640x480</button>";
        echo "</div>";
        echo "<iframe id=\"thumb\" style=\"border:1px solid;height:250px;width:100%\" src=\"index.php?themepreview=$themetoedit\"></iframe>";
//		echo "<br /><img alt=\"\" style=\"vertical-align:middle\" src=\"images/left.png\" /> <a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;themetoedit=$themetoedit\">".FN_i18n("back")."</a>";
        echo "<div style=\"text-align:right\"><img border=\"\" alt=\"\" style=\"vertical-align:middle\"src=\"" . FN_FromTheme("images/modify.png") . "\" />&nbsp;";
        if (file_exists("{$_FN['src_application']}/themes/$themetoedit/structure.php"))
            echo "<a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;themetoedit=$themetoedit&amp;edit=structure.php\">" . FN_i18n("modify") . " <b>\"$themetoedit\"</b> (structure.php, " . FN_i18n("for advanced users only") . ")</a><br />";
        if (file_exists("{$_FN['src_application']}/themes/$themetoedit/template.tp.html"))
            echo "<a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;themetoedit=$themetoedit&amp;edit=template.tp.html\">" . FN_i18n("modify") . " <b>\"$themetoedit\"</b> (index.tp.html, " . FN_i18n("for advanced users only") . ")</a>";

        echo "</div>";
    }
    $filetoedit = "themes/$themetoedit/config.php";
    if (file_exists("{$_FN['src_finis']}/themes/$themetoedit/config.php"))
    {
        $filetoedit = "{$_FN['src_finis']}/themes/$themetoedit/config.php" ;
    }
    if ($filetoedit)
    {
        echo "\n<fieldset>";
        echo "<legend>" . FN_Translate("settings") . " '$themetoedit'</legend>";
        echo FNCC_HtmlEditConfFile($filetoedit, "?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;themetoedit=$themetoedit", "");
        echo "</fieldset>";
    }



    //--------------------CSS list ------------------------------------------->
    if (is_writable("themes/$themetoedit") && FN_UserCanEditFolder("themes/$themetoedit"))
    {
        echo "<div style=\"\">";
        $listfiles = glob("themes/$themetoedit/*.css");
        if (count($listfiles) > 0)
        {
            echo "<table>";
            echo "<tr><td colspan=\"2\"><h3>CSS</h3></td></tr>";
            foreach ($listfiles as $file)
            {
                $file2 = basename($file);
                $urlcss = $_FN['siteurl'] . "/themes/$themetoedit/$file2";
                echo "<tr><td>" . basename("$file") . "</td><td><a href=\"?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;edit=$file2&amp;themetoedit=$themetoedit\">[" . FN_i18n("modify") . "]</a></td><td><a href=\"http://jigsaw.w3.org/css-validator/validator?uri=$urlcss&amp;profile=css21&amp;usermedium=all\" onclick=\"window.open(this.href);return false;\">validate</a></td></tr>";
            }
            echo "</table>";
        }
        echo "</div>";
    }
    //--------------------CSS list -------------------------------------------<
    //---------------------------images---------------------------------------->
    $errors = array();
    $oldimageimage = FN_GetParam("oldimageimage", $_POST, "flat");

    if ($oldimageimage != "" && file_exists($oldimageimage) && !empty($_FILES['image']['tmp_name']))
    {
        $oldimagevalues = getimagesize($oldimageimage);
        $newimagevalues = getimagesize($_FILES['image']['tmp_name']);
        //    if ($imagevalues[])

        if ($oldimagevalues['mime'] != $newimagevalues['mime'])
        {
            $errors[] = FN_Translate("the image must be of the same type and of the same size");
        }

        if (count($errors) > 0)
        {
            FN_Alert(implode($errors, ", \n"));
        }
        else
        {
            if (!move_uploaded_file($_FILES['image']['tmp_name'], "$oldimageimage"))
            {
                FN_Alert(FN_Translate("error") . ": " . FN_Translate("file not created"));
            }
            else
            {
                FN_Alert(FN_Translate("the data were successfully updated"));
            }
        }
        /*
          dprint_r($errors);
          dprint_r($oldimagevalues);
          dprint_r($newimagevalues);
         */
    }


    // die();
    echo "<h3>" . FN_Translate("images") . "</h3>";
    echo "<div style=\"clear:both;text-align:center\">";
    $images = listimages("themes/$themetoedit/*");
    foreach ($images as $image)
    {
        $id = md5($image['filename']);
        echo "<form id=\"$id\" enctype=\"multipart/form-data\"  style=\"margin:10px;display:block;height:250px;width:250px;float:left;overflow:hidden;border:1px solid #dadada\" method=\"post\" action=\"{$_FN['siteurl']}/{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&themetoedit=$themetoedit\">";
        $urlimage = "{$_FN['siteurl']}{$image['filename']}";
        echo "<b>" . basename($image['filename']) . "</b><br />";
        echo "<a href=\"$urlimage\" target=\"preview\"><img style=\"max-height:46px;max-width:64px;\" src=\"$urlimage?" . time() . "\" tutle=\"{$image['filename']}\" /></a><br />";
        echo "<p>" . FN_Translate("size") . ":{$image['h']}x{$image['w']}<br />";
        echo "" . FN_Translate("type") . ":{$image['mime']}</p>";
        echo "<p><b>" . FN_Translate("replace") . ":</b></p>";
        echo "<p><input type=\"hidden\" name=\"oldimageimage\" value=\"{$image['filename']}\"/></p>";
        echo "<p><input type=\"file\" name=\"image\"/></p>";
//        echo "<p><input checked=\"checked\" type=\"checkbox\" name=\"resize\" value=\"1\"/>".FN_Translate ("resize")."</p>";
        echo "<button type=\"submit\">" . FN_Translate("apply changes") . "</button>";
        echo "</form>";
    }
    echo "<br style=\"clear:both\" />";
    echo "</div>";
    //---------------------------images----------------------------------------<

    if (is_writable("themes") && FN_UserCanEditFolder("themes"))
    {

        $error = "";
        $copy_theme_from = FN_GetParam("copy_theme_from", $_POST, "html");
        $copy_theme_to = FN_GetParam("copy_theme_to", $_POST, "html");
        if ($copy_theme_to != "" && $copy_theme_from != "" && is_dir("themes/$copy_theme_from"))
        {
            $copy_theme_to = str_replace(" ", "_", $copy_theme_to);
            if (preg_match("/[A-Za-z0-9_]+/", $copy_theme_to) != true)
            {
                $error = FN_Translate("have entered illegal characters");
            }
            elseif (file_exists("themes/$copy_theme_to"))
            {
                $error = FN_Translate("a file already exists with this name");
            }
            if ($error == "")
            {
                $ret = FN_CopyDir("themes/$copy_theme_from", "themes/$copy_theme_to");
                if ($ret && file_exists("themes/$copy_theme_to"))
                {
                    FN_JsRedirect("?fnapp=controlcenter&mod={$_FN['mod']}&opt=$opt&themetoedit=$copy_theme_to&newthemesuccess=1");
                }
                else
                {
                    FN_Alert(FN_Translate("error"));
                }
            }
        }
        $success = FN_GetParam("newthemesuccess", $_GET);
        if ($success == 1)
        {
            FN_Alert(FN_Translate("the new theme has been created"));
        }
        echo "<h3>" . FN_Translate("create new theme") . "</h3>";
        echo "<form method=\"post\" action=\"index.php?fnapp=controlcenter&opt=$op\" name=\"themecopy\">";
        echo FN_i18n("Starting theme") . " : ";
        echo "<select name=\"copy_theme_from\" >";
        $theme_sel = $copy_theme_from;
        if ($theme_sel == "")
            $theme_sel = $themetoedit;
        if ($theme_sel == "")
            $theme_sel = $_FN['theme'];

        foreach ($list_themes as $theme_)
        {
            echo "\n<option";
            if ($theme_sel == $theme_)
            {
                echo ' selected="selected" ';
            }
            echo ">$theme_</option>";
        }
        echo "</select> ";
        echo FN_TRanslate("name") . " <input type=\"text\" name=\"copy_theme_to\" value=\"\"/> <span style=\"color:red\">$error</span>";
        echo "<br /><button type=\"submit\" >" . FN_i18n("make theme") . "</button>";
        echo "
	</form>        ";
    }
}

/**
 * 
 * @return array
 */
function get_list_themes()
{
    global $_FN;
    $directories = [
        "{$_FN['src_application']}/themes/",
        "{$_FN['src_finis']}/themes/"
    ];

    $list_themes = [];

    // Iterate over each directory to read themes
    foreach ($directories as $directory) {
        if ($handle = opendir($directory)) {
            while (false !== ($file = readdir($handle))) {
                // Check if the file is a directory and not a special directory
                if ($file !== "." && $file !== ".." && is_dir($directory . $file)) {
                    $list_themes[] = $file;
                }
            }
            closedir($handle);
        }
    }

    // Remove duplicates and sort naturally
    $list_themes = array_unique($list_themes);
    natsort($list_themes);

    return $list_themes;
}


/**
 * 
 * @param type $pattern
 * @param type $flags
 * @return type
 */
function listimages($pattern, $flags = 0)
{
    $_files = glob($pattern, $flags);
    $files = array();
    foreach ($_files as $_file)
    {
        if (!is_dir($_file))
        {
            $imagevalues = @getimagesize($_file);

            if ($imagevalues)
            {
                $image['w'] = $imagevalues[0];
                $image['h'] = $imagevalues[1];
                $image['mime'] = $imagevalues['mime'];
                $image['filename'] = $_file;
                $files[] = $image;
            }
        }
    }
    foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir)
    {
        $files = array_merge($files, listimages($dir . '/' . basename($pattern), $flags));
    }
    return $files;
}

?>