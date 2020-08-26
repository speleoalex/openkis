<?php

/**
 * @package Flatnux_functions
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');

/**
 * make alert
 * @param string message
 *
 */
function FN_Alert($message)
{
    echo FN_HtmlAlert($message);
}

function XMLDBEDITOR_HtmlAlert($message)
{
    return FN_HtmlModalWindow($message);
}
function FN_HtmlAlert($message)
{
    
    return FN_HtmlModalWindow($message);
}


/**
 * print bbcode javascript
 */
function FN_BbcodesJs()
{
    echo FN_HtmlBbcodesJs();
}

/**
 * print bbcode javascript
 */
function FN_HtmlBbcodesJs()
{

    static $str="<script type='text/javascript'>
function insertTags(tag1, tag2, area) {

	var txta = document.getElementsByName(area)[0];
	txta.focus();
	if (document.selection) {
		var sel  = document.selection.createRange();
		sel.text = tag2
			? tag1 + sel.text + tag2
			: tag1;
	}
	else if (txta.selectionStart != undefined) {
		var before = txta.value.substring(0, txta.selectionStart);
		var sel    =  txta.value.substring(txta.selectionStart, txta.selectionEnd);
		var after  = txta.value.substring(txta.selectionEnd, txta.textLength);
		txta.value = tag2
			? before + tag1 + sel + tag2 + after
			: before + \"\" + tag1 + \"\" + after;
	}
}
</script>";
    $html=$str;
    $str="";
    return $html;
}

/**
 *
 * @global array $_FN
 * @param string $area
 * @param string $what
 */
function FN_BbcodesPanel($area,$what)
{
    echo FN_HtmlBbcodesPanel($area,$what);
}

/**
 *
 * @global array $_FN
 * @param string $area
 * @param string $what
 */
function FN_HtmlBbcodesPanel($area,$what)
{
    global $_FN;
    $lang=$_FN['lang'];
    $html="";
    switch($what)
    {
        case "formatting" :
            //bpld
            $html.="<img	onclick=\"javascript:insertTags('[b]', '[/b]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/bold.png")."\"  alt=\"bold\" title=\"bold\"  />";
            //italic
            $html.="<img	onclick=\"javascript:insertTags('[i]', '[/i]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/italic.png")."\"  alt=\"italic\" title=\"italic\"  />";
            //quote
            $html.="<img	onclick=\"javascript:insertTags('[quote]', '[/quote]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/quote.png")."\"  alt=\"quote\" title=\"italic\"  />";
            $html.="
		<img
			onclick=\"javascript:insertTags('[code]', '[/code]', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/code.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"code\" title=\"code\"  />
		<img
			onclick=\"javascript:insertTags('[img]', '[/img]', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/image.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"image\" title=\"image\"  />
		<img
			onclick=\"javascript:insertTags('[red]', '[/red]', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/red.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"red\" title=\"red\"  />
		<img
			onclick=\"javascript:insertTags('[green]', '[/green]', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/green.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"green\" title=\"green\"  />
		<img
			onclick=\"javascript:insertTags('[blue]', '[/blue]', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/blue.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"blue\" title=\"blue\"  />
		<img
			onclick=\"javascript:insertTags('[pink]', '[/pink]', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/pink.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"pink\" title=\"pink\"  />
		<img
			onclick=\"javascript:insertTags('[yellow]', '[/yellow]', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/yellow.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"yellow\" title=\"yellow\"  />
		<img
			onclick=\"javascript:insertTags('[cyan]', '[/cyan]', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/cyan.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"cyan\" title=\"cyan\"  />
		<img
			onclick=\"javascript:insertTags('[url]', '[/url]', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/url.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"url\" title=\"url\"  />
		<img
			onclick=\"javascript:insertTags('[wp lang=$lang]', '[/wp]', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/wikipedia.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"wikipedia\" title=\"wikipedia\"  />";
            break;
        case "emoticons" :
            $html.="
		<img
			onclick=\"javascript:insertTags('[:)]', '', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/01.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"Happy\"  />
		<img
			onclick=\"javascript:insertTags('[:(]', '', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/02.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"\"  />
		<img
			onclick=\"javascript:insertTags('[:o]', '', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/03.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"\"  />
		<img
			onclick=\"javascript:insertTags('[:p]', '', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/04.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"\"  />
		<img
			onclick=\"javascript:insertTags('[:D]', '', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/05.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"\"  />
		<img
			onclick=\"javascript:insertTags('[:!]', '', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/06.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"indifferente\"  />
		<img
			onclick=\"javascript:insertTags('[:O]', '', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/07.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"\"  />
		<img
			onclick=\"javascript:insertTags('[8)]', '', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/08.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"\"  />
		<img
			onclick=\"javascript:insertTags('[;)]', '', '$area')\"
			onmouseover=\"document.getElementsByName('$area')[0].focus()\"
			src=\"".FN_FromTheme("images/emoticon/09.png")."\"
			style=\"border:0px;cursor:pointer\" alt=\"\"  />";
            break;
    }
    return $html;
}

/**
 *
 * @param string $where
 */
function FN_HtmlJsRedirect($where)
{
    $where=str_replace("&amp;","&",$where);
    //$html = "<br /><a href=\"$where\">$where</a><br />";
    $html=("\n<script language=\"javascript\">\nwindow.location='$where'\n</script>\n");
    return $html;
}

/**
 *
 * @param string $where
 */
function FN_JsRedirect($where)
{
    echo FN_HtmlJsRedirect($where);
}

?>
