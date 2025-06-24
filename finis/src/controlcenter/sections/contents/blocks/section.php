<?php
/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
$modcont=FN_GetParam("edit",$_GET);
$blockid=FN_GetParam("block",$_GET);
global $editType;
$editType=null;
//----------------------modify------------------------------------------------->
//----------------------modify------------------------------------------------->
//----------------------modify------------------------------------------------->
if ($modcont)
{
    $filename=basename($modcont);
    if (basename($filename)=="config.php"&&$blockid!="")
    {
        $_FN['block']=$blockid;
        echo FNCC_HtmlEditConfFile("{$_FN['src_finis']}/$modcont","{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&edit=$modcont&amp;block=$blockid","{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt");
        $_FN['block']="";
    }
    else
    {
        $filedir=dirname($modcont);
        $lang=str_replace(".html","",str_replace("section.","",$filename));

        $editor_params=false;
        if (file_exists(dirname($modcont)."/style.css"))
        {
            $editor_params['css_file']=dirname($modcont)."/style.css";
        }
        if (!file_exists($modcont))
        {
            if (isset($_GET['create']))
            {
                //---from default language ---------------------------------------->
                if (!file_exists($modcont))
                {
                    if (isset($_GET['copy']))
                    {
                        $text=file_get_contents("$filedir/section.{$_FN['lang_default']}.html");
                        FN_Write($text,$modcont);
                    }
                }
                //---from default language ----------------------------------------<
                FN_EditContent($modcont,"{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&edit=$modcont","?fnapp=controlcenter&opt=$opt",$editor_params);
            }
            else
            {
                echo "$modcont ".FN_i18n("does not exist")."<br /><br />";
                echo FN_i18n("you want to create the file?");
                echo "<br /><br />";
                echo "<button onclick=\"window.location='{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt'\" >".FN_Translate("no")."</button> ";
                echo "<button onclick=\"window.location='{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&amp;edit=$modcont&amp;create'\" >".FN_Translate("yes")."</button> ";

                if ($lang!=$_FN['lang_default']&&file_exists("$filedir/section.{$_FN['lang_default']}.html"))
                {
                    echo " <a href=\"{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&amp;edit=$modcont&amp;create&amp;copy\" >[".FN_Translate("copy contents from the default translation and edit it")."]</a> ";
                }
            }
        }
        else
        {
            $_FN['editor_folder']=dirname($modcont);
            FN_EditContent($modcont,"{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&edit=$modcont","?fnapp=controlcenter&opt=$opt",$editor_params);
        }
    }
    
}
//----------------------modify-------------------------------------------------<
//----------------------modify-------------------------------------------------<
//----------------------modify-------------------------------------------------<
else
{
    $params=array();
    $params['enablenew']=true;
    $params['textmodify']=FN_Translate("configure");
    $params['textsave']=FN_Translate("save");
    $params['list_onsave']=false;
    $params['fields']="id|block_type|title|status|startdate|enddate|level|group_view|group_edit";
    $params['function_on_insert']="OnInsert";
    $params['function_on_update']="OnUpdate";
    $params['function_on_delete']="OnDelete";
    $params['list_onupdate']=false;
    $params['textviewlist']="";
    $params['textnew']=FN_Translate("create a new block");
    $params['textcancel']=FN_Translate("back to")." ".FN_Translate("list of blocks");
    $params['function_on_update']="FNCC_OnUpdateBlock";
    $params['function_on_insert']="FNCC_OnInsertBlock";

    $op___xdb_fn_blocks=FN_GetParam("op___xdb_fn_blocks",$_GET);
    //-----blocks editor ------------------------------------------------------>
    ob_start();
    if (empty($_POST['savefileconfig']))
    {
        if (empty($_GET['op___xdb_fn_blocks']))
            echo "<h3>".FN_Translate("list of blocks").":</h3>";
        FNCC_xmltableeditor("fn_blocks",$params);
    }
    $htmleditor=ob_get_clean();
    //-----blocks editor ------------------------------------------------------<
    if ($op___xdb_fn_blocks==""||$op___xdb_fn_blocks=="del")
    {
        echo "<p><button type=\"button\" onclick=\"window.location='?fnapp=controlcenter&op___xdb_fn_blocks=insnew&opt=$opt'\"><img src=\"images/add.png\" alt=\"\" /> ".FN_Translate("create a new block")."</button></p>";
    }

    echo html_BlocksEditor();
    echo $htmleditor;
    $pk___xdb_fn_blocks=FN_GetParam('pk___xdb_fn_blocks',$_GET);
    if ($pk___xdb_fn_blocks)
    {
        echo "<fieldset><legend>PHP code</legend><input onfocus=\"this.select();\" style=\"width:100%\" value=\"<?php echo FN_HtmlBlock('$pk___xdb_fn_blocks'); ?>\" /></fieldset>";
    }
    $block=false;

    if (!empty($_POST['id']))
    {
        $block=FN_GetBlockValues($_POST['id'],false);
        $blockId=FN_GetParam("id",$_POST,"html");
    }
    elseif ($pk___xdb_fn_blocks)
    {
        $block=FN_GetBlockValues($pk___xdb_fn_blocks,false);
        $blockId=FN_GetParam("pk___xdb_fn_blocks",$_GET,"html");
    }
    if ($editType!==null)
        $block['type']=$editType;
    if (isset($block['id']) && file_exists("{$_FN['src_application']}/blocks/{$block['id']}")&&!empty($block['type'])&&file_exists("{$_FN['src_finis']}/modules/{$block['type']}/config.php"))
    {
        //echo "<br /><div><a href=\"?edit=modules/block_calendar/config.php&opt=$opt&block=$pk___xdb_fn_blocks\">" . FN_Translate("configure module") . " {$block['type']}</a></div>";
        //---------------block settings---------------------------------------->
        $sectiontype=FN_GetParam("type",$_POST,"html");
        $mod="";
        if (!empty($blockId))
        {
            if (file_exists("{$_FN['src_finis']}/modules/{$block['type']}/config.php"))
            {
                $tableHtmlattibutes="";
                echo "<fieldset>";
                echo "<legend>".FN_Translate("module options that is loaded in this block")."</legend>";
                $formaction="{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&amp;op___xdb_fn_blocks=insnew&amp;pk___xdb_fn_blocks=$blockId";
                $formexit="{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt";
                echo FNCC_HtmlEditConfFile("{$_FN['src_finis']}/modules/{$block['type']}/config.php",$formaction,$formexit,false,false,$mod,$blockId,$tableHtmlattibutes);
                echo "</fieldset>";
            }
        }
        
        //---------------block settings----------------------------------------<
    }
}


/**
 *
 * @param type $newvalues
 * @param type $oldvalues 
 */
function OnUpdate($newvalues,$oldvalues)
{
    if ($oldvalues['id']!=$newvalues['id'])
    {
        FN_Rename("{$_FN['src_application']}/blocks/{$oldvalues['id']}","{$_FN['src_application']}/blocks/{$newvalues['id']}");
    }
    FN_ClearCache();
}

/**
 *
 * @param type $newvalues 
 */
function OnInsert($newvalues)
{
    FN_MkDir("{$_FN['src_application']}/blocks/{$newvalues['id']}");
    FN_Log("created new block:{$newvalues['id']}");
    FN_ClearCache();
}

/**
 *
 * @param type $oldvalues 
 */
function OnDelete($oldvalues)
{
    if ($oldvalues['id']!="")
    {
        FN_RemoveDir("{$_FN['src_application']}/blocks/{$oldvalues['id']}");
        FN_Log("deleted block:{$oldvalues['id']}");
    }
}

/**
 *
 * @param array $block 
 */
function PrintBlockOptions($block)
{
    global $_FN;
    $opt=FN_GetParam("opt",$_GET);
    $block=FN_GetBlockValues($block['id']);
    $html="";
    $html.="<div id=\"block_{$block['id']}\" style=\"border:1px solid #888888;margin:5px;background-color:#f4f4f4;\">";
    $html.="<img title=\"".FN_Translate("move left")."\" onclick=\"moveLeft(this.parentNode);\" style=\"cursor:pointer\" src=\"images/fn_left.png\" alt=\"\">";
    $html.="<img title=\"".FN_Translate("move up")."\"onclick=\"moveUp(this.parentNode);\" style=\"cursor:pointer\" src=\"images/fn_up.png\" alt=\"\">";
    $html.="<img title=\"".FN_Translate("move down")."\"onclick=\"moveDown(this.parentNode);\" style=\"cursor:pointer\" src=\"images/fn_down.png\" alt=\"\">";
    $html.="<img title=\"".FN_Translate("move right")."\"onclick=\"moveRight(this.parentNode);\" style=\"cursor:pointer\" src=\"images/fn_right.png\" alt=\"\">";
    //dprint_r($block);
    if ($block['level']!=""||$block['group_view']!="")
        $html.="<img src=\"images/locked.png\" style=\"float:right\" />";
    if ($block['hidetitle']=="")
        $html.="&nbsp;&nbsp;&nbsp;<b>".$block['title']."</b>";
    else
        $html.="&nbsp;&nbsp;&nbsp;<span style=\"color:#dadada\">".$block['title']."</span>";
    $html.="<hr />";

    if ($block['type']!="")
        $html.="<div style=\"text-align:center\" >".FN_Translate("block type").":".$block['type']."</div>";
    elseif (file_exists("{$_FN['src_application']}/blocks/{$block['type']}/section.php"))
    {
        $html.="<div style=\"text-align:center\" >".$block['type']."</div>";
    }
    else
    {
        $html.="<div style=\"text-align:center\" >html</div>";
    }
    $html.="<div style=\"text-align:right\">";

    $html.=(($block['status']) ? "<span style=\"color:green\">".FN_i18n("published") : "<span style=\"color:red\">".FN_i18n("not published"))."</span><br />";

    //pagine --->


    $border="border:1px solid #ffffff";
    if (file_exists("{$_FN['src_application']}/blocks/{$block['id']}/section.php"))
        $border="border:1px solid #00ff00";
    $html.="<a  title=\"".FN_Translate("configure block")."\" href=\"{$_FN['controlcenter']}?fnapp=controlcenter&opt=$opt&amp;pk___xdb_fn_blocks={$block['id']}&amp;op___xdb_fn_blocks=insnew\">&#128295;</a>&nbsp;";
    $html.="<a href=\"{$_FN['controlcenter']}?fnapp=controlcenter&edit=blocks/{$block['id']}/section.php&amp;opt=$opt\">PHP</a>";



    foreach($_FN['listlanguages'] as $l)
    {
        $border="border:1px solid #ffffff";
        if (file_exists("{$_FN['src_application']}/blocks/{$block['id']}/section.$l.html"))
            $border="border:1px solid #00ff00";
        $link="{$_FN['controlcenter']}?fnapp=controlcenter&edit=blocks/{$block['id']}/section.$l.html&amp;opt=$opt";
        $html.=" <a style=\"$border\" href=\"$link\" title=\"".FN_Translate("edit")." - section.$l.html\" >".FN_getCountryFlag($l)."</a>";
    }
    //pagine ---<
    $html.="</div>";
    $html.="</div>";
    return $html;
}

/**
 *
 * @return string 
 */
function html_BlocksEditor()
{
    $html="";
    ob_start();
    ?>
    <script type="text/javascript" >
        function moveUp(node)
        {
            var target = findPrev(node);
            if (target != null && target.id != "")
                movenode(node, target);
            else
                moveTop(node);
            syncdivs();
        }
        function moveDown(node)
        {
            var target = findNext(node);
            if (target != null && target.id != "")
                moveUp(target);
            else
                moveBottom(node)
            syncdivs();
        }
        function moveLeft(node)
        {
            var target = document.getElementById("blocks-left").firstChild;
            if (target != null)
                movenode(node, target);

            syncdivs();
        }
        function moveRight(node)
        {
            var target = document.getElementById("blocks-right").firstChild;
            if (target != null)
                movenode(node, target);
            syncdivs();
        }
        function moveTop(node)
        {
            var target = document.getElementById("blocks-top").firstChild;
            if (target != null)
                movenode(node, target);
            syncdivs();
        }
        function moveBottom(node)
        {
            var target = document.getElementById("blocks-bottom").firstChild;
            if (target != null)
                movenode(node, target);
            syncdivs();
        }

        var movenode = function (node, nodetarget)
        {
            var tmpnode = node;
            var parent = nodetarget.parentNode;
            //parent.removeChild(node);
            parent.insertBefore(tmpnode, nodetarget);
        }

        findPrev = function (node) {
            var listNodes = node.parentNode.childNodes;
            var prev = null;
            if (listNodes != undefined)
                for (var i = 0; listNodes[i] != undefined && listNodes[i].id != undefined; i++)
                {
                    if (listNodes[i].id == node.id)
                        return prev;
                    prev = listNodes[i];
                }
            return prev;
        }
        findNext = function (node) {
            var listNodes = node.parentNode.childNodes;
            var prev = null;
            for (var i = 0; listNodes[i] != undefined && listNodes[i].id != undefined; i++)
            {
                if (listNodes[i].id == node.id)
                {
                    if (listNodes[i + 1] != undefined && listNodes[i + 1].id != undefined)
                    {
                        return listNodes[i + 1];
                    }
                }
            }
            return prev;
        }
        syncdivs = function ()
        {
            syncdiv("blocks-top");
            syncdiv("blocks-right");
            syncdiv("blocks-left");
            syncdiv("blocks-bottom");
        }

        syncdiv = function (idblocks)
        {
            var sep = "";
            document.getElementById("list_" + idblocks).value = "";
            divitems = document.getElementById(idblocks).childNodes;
            if (divitems != undefined)
            {
                for (var i in divitems)
                {
                    if (divitems[i] != undefined && divitems[i].id != "")
                    {
                        document.getElementById("list_" + idblocks).value += sep;
                        document.getElementById("list_" + idblocks).value += divitems[i].id;
                        sep = ",";
                    }
                }
            }
            set_changed();
        }

        function set_changed()
        {
            try {
                var allLinks = document.getElementsByTagName('a');
                for (var i in allLinks)
                {
                    if (!allLinks[i].onclick || allLinks[i].onclick == '' || allLinks[i].onclick == undefined && allLinks[i].href)
                    {
                        if (allLinks[i].setAttribute)
                        {
                            allLinks[i].setAttribute('onclick', 'return confirm_exitnosave()');
                        }
                    }
                }
            } catch (e) {
            }
        }

        function confirm_exitnosave()
        {
            if (confirm('<?php echo FN_Translate("want to exit without saving?")?>'))
            {
                return true;
            }
            return false;
        }

    </script>
    <?php
    $html.=ob_get_clean();
//----------------aggiornamento posizioni-------------------------------------->
    if (isset($_POST['list_blocks-top']))
    {
        $table=FN_XMDBTable("fn_blocks");
        $allblocksTmp=$table->GetRecords();
        $allblocks=array();
        foreach($allblocksTmp as $block)
        {
            $allblocks[$block['id']]=$block;
        }
        $locations=array("top","left","bottom","right");
        //update blocks----->
        foreach($locations as $where)
        {
            $top_tmp=FN_GetParam("list_blocks-{$where}",$_POST);
            $top_tmp=explode(",",$top_tmp);

            $pos=1;
            foreach($top_tmp as $bl)
            {
                $idblock=FN_erg_replace('^block_','',$bl);
                if (isset($allblocks[$idblock]))
                {

                    $allblocks[$idblock]['where']="$where";
                    $allblocks[$idblock]['position']=$pos;
                    $pos++;
                    //dprint_r($allblocks[$idblock]);
                    $table->UpdateRecord($allblocks[$idblock]);
                }
            }
        }
        //update blocks-----<
        FN_ClearCache();
        FN_Alert(FN_Translate("the data were successfully updated"));
        FN_GetAllBlocks();
    }
//----------------aggiornamento posizioni--------------------------------------<
    if (empty($_GET['op___xdb_fn_blocks'])||$_GET['op___xdb_fn_blocks']=="del")
    {
        $blocks_right=FN_GetBlocks("right",false,false);
        $blocks_left=FN_GetBlocks("left",false,false);
        $blocks_top=FN_GetBlocks("top",false,false);
        $blocks_bottom=FN_GetBlocks("bottom",false,false);
        $opt=FN_GetParam("opt",$_GET);

        $html.="<p>";
        $html.=FN_Translate("move left")."<img title=\"".FN_Translate("move left")."\" src=\"images/fn_left.png\" alt=\"\"> ";
        $html.=FN_Translate("move up")."<img title=\"".FN_Translate("move up")."\" src=\"images/fn_up.png\" alt=\"\"> ";
        $html.=FN_Translate("move down")."<img title=\"".FN_Translate("move down")."\"src=\"images/fn_down.png\" alt=\"\"> ";
        $html.=FN_Translate("move right")."<img title=\"".FN_Translate("move right")."\" src=\"images/fn_right.png\" alt=\"\">";
        $html.="</p>";

//----------------------------FORM----------------------------------------------       
        $html.="<form method=\"post\" action=\"?fnapp=controlcenter&opt=$opt\">";
        $html.="<div style=\"width:100%;display:block;border:1px solid #dddddd;padding:10px;margin:auto;background-color:#ffffff;border-radius:10px\">";
//----------------------top---------------------------------------------------->
        $html.="<div id=\"blocks-top\"  style=\"border:1px solid #dadada;clear:both;margin:10px;margin-bottom:0px;\">";
        if (count($blocks_top)>0)
        {
            foreach($blocks_top as $k=> $block)
            {
                $html.=PrintBlockOptions($block);
            }
        }
        $html.="<div style=\"clear:both;\">";
        $html.="<br />";
        $html.="</div>";
        $html.="</div>";
//----------------------top----------------------------------------------------<
//----------------------right-------------------------------------------------->
        $html.="<div id=\"blocks-right\" style=\"float:right;width:200px;display:block;border:1px solid  #dadada;margin:10px;\">";
        if (count($blocks_right)>0)
        {
            foreach($blocks_right as $k=> $block)
            {
                $html.=PrintBlockOptions($block);
            }
        }
        $html.="<div  style=\"clear:both;\">";
        $html.="<br />";
        $html.="</div>";
        $html.="</div>";
//----------------------right--------------------------------------------------<
//-----------------------left-------------------------------------------------->
        $html.="<div id=\"blocks-left\" style=\"float:left;width:200px;display:block;border:1px solid #dadada;margin:10px;clear:left;\">";
        if (count($blocks_left)>0)
        {
            foreach($blocks_left as $k=> $block)
            {
                $html.=PrintBlockOptions($block);
            }
        }
        $html.="<div style=\"clear:both;\">";
        $html.="<br />";

        $html.="</div>";
        $html.="</div>";
//-----------------------left--------------------------------------------------<
//-----------------------bottom------------------------------------------------>
        $html.="<div id=\"blocks-bottom\" style=\"border:1px solid #dadada;clear:both;margin:10px;\">";
        if (count($blocks_bottom)>0)
        {
            foreach($blocks_bottom as $k=> $block)
            {
                $html.=PrintBlockOptions($block);
            }
        }
        $html.="<div  style=\"clear:both;\">";
        $html.="<br />";

        $html.="</div>";

        $html.="</div>";
//-----------------------bottom------------------------------------------------<
        $html.="<button type=\"submit\">".FN_Translate("save")."</button>";
        $html.="&nbsp;<button id=\"btn_exit\" onclick=\"window.location='?fnapp=controlcenter&opt=$opt';return false;\" >".FN_Translate("cancel")."</button>";

        $html.="<input type=\"hidden\" id=\"list_blocks-top\" name=\"list_blocks-top\" value=\"\" />";
        $html.="<input type=\"hidden\" id=\"list_blocks-bottom\" name=\"list_blocks-bottom\" value=\"\" />";
        $html.="<input type=\"hidden\" id=\"list_blocks-left\" name=\"list_blocks-left\" value=\"\" />";
        $html.="<input type=\"hidden\" id=\"list_blocks-right\" name=\"list_blocks-right\" value=\"\" />";

        $html.="<br  style=\"clear:both\" />";

        $html.="</div>";
        $html.="</form>";
        $html.="<br />";
//dprint_r($blocks_right);
    }
    return $html;
}

/**
 * 
 * @global type $editType
 * @param type $newvalues
 * @param type $oldvalues
 */
function FNCC_OnUpdateBlock($newvalues,$oldvalues)
{
    global $editType;
    if (isset($oldvalues["type"])&&isset($newvalues["type"]))
    {
        $editType=$newvalues["type"];
        FN_OnSitemapChange();
    }
}

/**
 * 
 * @global type $editType
 * @param type $newvalues
 */
function FNCC_OnInsertBlock($newvalues)
{
    global $editType;
    if (isset($oldvalues["type"]))
    {
        $editType=$newvalues["type"];
        FN_OnSitemapChange();
    }
}
?>