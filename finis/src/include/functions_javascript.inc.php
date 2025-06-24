<?php

/**
 * 
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
function FN_HtmlBbcodesPanel($area, $what)
{
    global $_FN;
    $lang = $_FN['lang'];
    $html = "";
    switch ($what)
    {
        case "formatting":
            // Bold
            $html .= "<span onclick=\"javascript:insertTags('[b]', '[/b]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"bold\">&#x1F4D1;</span>";
            // Italic
            $html .= "<span onclick=\"javascript:insertTags('[i]', '[/i]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"italic\">&#x1F4DD;</span>";
            // Quote
            $html .= "<span onclick=\"javascript:insertTags('[quote]', '[/quote]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"quote\">&#x1F4AC;</span>";
            // Code
            $html .= "<span onclick=\"javascript:insertTags('[code]', '[/code]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"code\">&#x1F4BB;</span>";
            // Image
            $html .= "<span onclick=\"javascript:insertTags('[img]', '[/img]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"image\">&#x1F5BC;</span>";
            // Colors
            $html .= "<span onclick=\"javascript:insertTags('[red]', '[/red]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"red\">&#x1F534;</span>";
            $html .= "<span onclick=\"javascript:insertTags('[green]', '[/green]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"green\">&#x1F7E2;</span>";
            $html .= "<span onclick=\"javascript:insertTags('[blue]', '[/blue]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"blue\">&#x1F535;</span>";
            $html .= "<span onclick=\"javascript:insertTags('[pink]', '[/pink]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"pink\">&#x1F497;</span>";
            $html .= "<span onclick=\"javascript:insertTags('[yellow]', '[/yellow]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"yellow\">&#x1F7E1;</span>";
            $html .= "<span onclick=\"javascript:insertTags('[cyan]', '[/cyan]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"cyan\">&#x1F537;</span>";
            // URL
            $html .= "<span onclick=\"javascript:insertTags('[url]', '[/url]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"url\">&#x1F517;</span>";
            // Wikipedia
            $html .= "<span onclick=\"javascript:insertTags('[wp lang=$lang]', '[/wp]', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"wikipedia\">&#x1F4F0;</span>";
            break;
        case "emoticons":
            // Happy
            $html .= "<span onclick=\"javascript:insertTags('[:)]', '', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"Happy\">&#x1F600;</span>";
            // Sad
            $html .= "<span onclick=\"javascript:insertTags('[:(]', '', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"Sad\">&#x1F641;</span>";
            // Surprised
            $html .= "<span onclick=\"javascript:insertTags('[:o]', '', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"Surprised\">&#x1F62E;</span>";
            // Tongue out
            $html .= "<span onclick=\"javascript:insertTags('[:p]', '', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"Tongue out\">&#x1F61B;</span>";
            // Grinning
            $html .= "<span onclick=\"javascript:insertTags('[:D]', '', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"Grinning\">&#x1F603;</span>";
            // Indifferent
            $html .= "<span onclick=\"javascript:insertTags('[:!]', '', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"Indifferent\">&#x1F610;</span>";
            // Shocked
            $html .= "<span onclick=\"javascript:insertTags('[:O]', '', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"Shocked\">&#x1F632;</span>";
            // Cool
            $html .= "<span onclick=\"javascript:insertTags('[8)]', '', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"Cool\">&#x1F60E;</span>";
            // Wink
            $html .= "<span onclick=\"javascript:insertTags('[;)]', '', '$area')\" onmouseover=\"document.getElementsByName('$area')[0].focus()\" style=\"cursor:pointer;\" title=\"Wink\">&#x1F609;</span>";
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
