<?php
/**
 * 
 * @package Finis-htmleditors-ckeditor4
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
?><body>
        <?php
        $CKEditorFuncNum=FN_GetParam("CKEditorFuncNum",$_GET,"html");
        if ($CKEditorFuncNum!= "")
        {
            echo "
<script type=\"text/javascript\" language=\"javascript\">
	window.opener.CKEditorFuncNum='$CKEditorFuncNum';
		window.resizeTo( 640,480 );
</script>
";
        }
        ?>
        <script type="text/javascript" language="javascript">
            // funzione chiamata dal filemanager una volta selezionato il file
            function insertElement(URL) {
                //alert(URL);
                window.opener.CKEDITOR.tools.callFunction(window.opener.CKEditorFuncNum, URL, function () {
                    var element, dialog = this.getDialog();
                    //alert(dialog.getName());					
                });
                window.close();
            }
        </script>
        <?php
        if (count($_FILES) > 0 && isset($_FILES['upload']))
        {
            $dir=FN_GetParam("dir",$_GET);
            fmck_UploadFile($dir);
        }
        else
        {
            include_once ("{$_FN['src_finis']}/modules/filemanager/section.php");
// link to local section ------>
            if (empty($_GET['mime']) && empty($_GET['opmod']))
            {
                $html="<div style=\"background-color:#ffffff;position:absolute;bottom:0px;font-size:12px;line-height:12px;padding:0px;height:20px;display:block;width:100%;border:1px;border-top:1px solid;\">".FN_i18n("link to local page",false,"Aa").": 
					<select style=\"vertical-align:middle;font-size:12px;width:300px;overflow:hidden;\" id=\"sectionstree\" >";
                $sections=FN_GetSections(false,true,true,true,true);
//sort sections --------------------------------------------------------------->
                $sections=FNCC_SortSectionsByTree("",$sections);
//sort sections ---------------------------------------------------------------<
                foreach($sections as $section)
                {
                    $margin=(count($section['path']) * 10)."px";
                    $html.="<option title=\"".htmlspecialchars($section['title'])."\" style=\"font-size:10px;padding-left:$margin;\" value=\"{$_FN['siteurl']}index.php?mod={$section['id']}\" >".htmlspecialchars($section['title'])."</option>";
                }
                $html.="</select>";
                $html.=" <button style=\"vertical-align:middle;font-size:12px;\" onclick=\"insertElement(document.getElementById('sectionstree').options[document.getElementById('sectionstree').selectedIndex].value)\">".FN_i18n("insert")."</button>";
                $html.="</div>";
                echo "$html";
            }
// link to local section ------<
        }

        function fmck_UploadFile($dir)
        {
            global $_FN;
            $file_clean=FN_StripPostSlashes($_FILES['upload']['name']);
            if (file_exists($dir."/".$file_clean))
            {
                echo(FN_i18n("the file already exists"));
                return;
            }
            if (!FN_CanModifyFile($_FN['user'],$dir."/".$file_clean))
            { // se non e' un file valido
                echo(FN_i18n("operation is not permitted")." - ".FN_i18n("file not created"));
            }
            else
            {
                if (!move_uploaded_file($_FILES['upload']['tmp_name'],$dir."/".$file_clean))
                {
                    echo (FN_Translate("error").":".FN_i18n("file not created"));
                }
                else
                {
                    echo "".$file_clean.": ".FN_Translate("file was uploaded succesfully","aa")."";
                }
            }
        }
        ?>
</body>