<?php
/**
 * @package Flatnux_blocks
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');

if (!function_exists("pmbuttons"))
{
	function pmbuttons()
	{
		echo "<br /><br />" . FN_Translate("font size") . "<br />";
		echo "<button class=\"decreaseFontSize\" title=\"" . FN_Translate("decrease font size") . "\"  accesskey=\"-\" onclick=\"decreaseFontSize()\">-</button>&nbsp;<button class=\"increaseFontSize\" title=\"" . FN_Translate("increase font size") . "\" onclick=\"increaseFontSize()\"  accesskey=\"+\" >+</button>";
		echo "";
		echo '
	<script type="text/javascript">
measureUnit = "px";minSize = 1;minStyleSize = 10;maxSize = 6;
maxStyleSize = 30;startSize = 1;startStyleSize = 10;stepSize = 1;
stepStyleSize = 2;var keyin = 61;var keyinCAPS = 43;var keyout = 45;
var keyoutCAPS = 95;var keyinIe = 46;var keyinIeCAPS = 62;var keyoutIe = 44;
var keyoutIeCAPS = 60;var zoomFactor = 1.1;var maxZoom = 4.096;var minZoom = 0.625;
var startDecZoom = 0.7;var startIncZoom = 1.3;userExpiry = 365 * 24 * 60 * 60 * 1000;
alertEnabled = false;allowInputResize = false;

function searchTags(childTree, level) {
  var retArray = new Array();
  var tmpArray = new Array();
  var j = 0;
  var childName = "";
  for (var i=0; i<childTree.length; i++) {
    childName = childTree[i].nodeName;
    if (childTree[i].hasChildNodes()) {
      if ((childTree[i].childNodes.length == 1) && (childTree[i].childNodes[0].nodeName == "#text"))
        retArray[j++] = childTree[i];
      else {
        tmpArray = searchTags(childTree[i].childNodes, level+1);
        for (var k=0;k<tmpArray.length; k++)
          retArray[j++] = tmpArray[k];
        retArray[j++] = childTree[i];
      }
    }
    else
      retArray[j++] = childTree[i];
  }
  return(retArray);
}
function changeFontSize(stepSize, stepStyleSize) {
  if (document.body) {
    var myObj = searchTags(document.body.childNodes, 0);
    var myStepSize = stepSize;
    var myStepStyleSize = stepStyleSize;
    myObjNumChilds = myObj.length;
    for (i=0; i<myObjNumChilds; i++) {
      myObjName = myObj[i].nodeName;
      if (myObjName != "#text" && myObjName != "HTML" &&
          myObjName != "HEAD" && myObjName != "TITLE" &&
          myObjName != "STYLE" && myObjName != "SCRIPT" &&
          myObjName != "BR" && myObjName != "TBODY" &&
          myObjName != "#comment" && myObjName != "FORM") {
        if (!allowInputResize && myObjName == "INPUT") continue;
        size = parseInt(myObj[i].getAttribute("size"));
        if (myObj[i].currentStyle)
          styleSize = parseInt(myObj[i].currentStyle.fontSize);
        else
          styleSize = parseInt(window.getComputedStyle(myObj[i], null).fontSize);
        if (isNaN(size) || (size < minSize) || (size > maxSize))
          size = startSize;
        if (isNaN(styleSize) || (styleSize < minStyleSize) || (styleSize > maxStyleSize))
          styleSize = startStyleSize;
        if ( ((size > minSize) && (size < maxSize)) ||
             ((size == minSize) && (stepSize > 0)) ||
             ((size == maxSize) && (stepSize < 0))) {
          myObj[i].setAttribute("size", size+myStepSize);
        }
        if ( ((styleSize > minStyleSize) && (styleSize < maxStyleSize)) ||
             ((styleSize == minStyleSize) && (stepStyleSize > 0)) ||
             ((styleSize == maxStyleSize) && (stepStyleSize < 0))) {
          newStyleSize = styleSize+myStepStyleSize;
          myObj[i].style.fontSize = newStyleSize+measureUnit;
        }
      } // End if condition ("only some tags")
    } // End main for cycle
  } // End if condition ("document.body exists")
} // End function declaration

increaseFontSize = function () {
  if (document.body) {
    changeFontSize(stepSize, stepStyleSize, false);
  }
  else {
    if (alertEnabled) {
      alert("Spiacente, il tuo browser non supporta questa funzione");
    }
  }
}

decreaseFontSize = function () {
  if (document.body) {
    myStepSize = -stepSize;
    myStepStyleSize = -stepStyleSize;
    changeFontSize(myStepSize, myStepStyleSize, false);
  }
  else {
    if (alertEnabled) {
      alert("Spiacente, il tuo browser non supporta questa funzione");
    }
  }
}

function zoomin() {
  if (window.parent.document.body.style.zoom < maxZoom) {
    if (window.parent.document.body.style.zoom > 0) {
      window.parent.document.body.style.zoom *= zoomFactor;
    }
    else {
      window.parent.document.body.style.zoom = startIncZoom;
    }
  }
  else {
    if (alertEnabled) {
      alert("Warning: Max size reached");
    }
  }
}

function zoomout() {
  if ( (window.parent.document.body.style.zoom > minZoom) ||
       (window.parent.document.body.style.zoom == 0) ) {
    if (window.parent.document.body.style.zoom > 0) {
      window.parent.document.body.style.zoom /= zoomFactor;
    }
    else {
      window.parent.document.body.style.zoom = startDecZoom;
    }
  }
  else {
    if (alertEnabled) {
      alert("Warning: Min size reached");
    }
  }
}

</script>
';
	}
}
global $_FN;
$_AKSHOW=FN_Translate("accesskey on");
$_AKHIDE=FN_Translate("accesskey off");
$_SITE_HC=FN_Translate("high contrast");
$_SITE_LC=FN_Translate("normal contrast");
if (file_exists("themes/{$_FN['theme']}_HC"))
{
	$a=FN_GetAccessKey($_SITE_HC,"theme={$_FN['theme']}_HC");
	echo "<div style=\"text-align:center\"><a accesskey=\"$a\" href=\"?theme={$_FN['theme']}_HC\">$_SITE_HC</a></div>";
}
else
{
	if (fn_erg('_HC$',$_FN['theme']))
	{
		$theme_lc=FN_erg_replace("_HC","",$_FN['theme']);
		$a=FN_GetAccessKey($_SITE_LC,"theme=$theme_lc");

		echo "<div style=\"text-align:center\"><a accesskey=\"$a\" href=\"?theme=$theme_lc\">$_SITE_LC</a>";
		pmbuttons();
		echo "</div>";
	}
}
if ($_FN['showaccesskey'] == 0)
{
	$a=FN_GetAccessKey($_AKSHOW,"showaccesskey=1");
	echo "<div style=\"text-align:center\"><a accesskey=\"$a\" href=\"?showaccesskey=1\">[$a]$_AKSHOW</a></div>";
}
else
{
	$a=FN_GetAccessKey($_AKHIDE,"showaccesskey=0");
	echo "<div style=\"text-align:center\"><a accesskey=\"$a\" href=\"?showaccesskey=0\">$_AKHIDE</a></div>";
}
?>