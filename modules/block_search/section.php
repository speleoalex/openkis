<?php

/**
 * @package Flatnux_block_search
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * 
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
//-----------------------------get request vars-------------------------------->
$q=trim(ltrim(FN_GetParam("q",$_REQUEST,"html")));
$method=FN_GetParam("method",$_REQUEST,"html");
$where=FN_GetParam("where",$_REQUEST,"html");
$op=FN_GetParam("op",$_REQUEST,"html");
$maxres=100;
$contares=0;
//-----------------------------get request vars--------------------------------<
//----------------------------print search form-------------------------------->
$htmlTpl=file_get_contents(FN_FromTheme("modules/block_search/search.tp.html",false));
$params=array();
$params['form_action'] = FN_RewriteLink("index.php?mod=search&amp;op=$op");
$params['formaction'] = FN_RewriteLink("index.php?mod=search&amp;op=$op");
$params['form_inputs'] = "<input type=\"hidden\" name=\"where\" value = \"$where\" /><input type=\"hidden\" name=\"op\" value = \"$op\" />";
$basepath= dirname(FN_FromTheme("modules/block_search/search.tp.html",false));
$html=FN_TPL_ApplyTplString($htmlTpl,$params,$basepath);
$html=preg_replace("/<option ([^>]*)(value=\"$method\"|value='$method')([^>]*)>/im","<option selected=\"selected\" \\1 \\2 \\3>",$html);
echo $html;
//----------------------------print search form--------------------------------<
?>
