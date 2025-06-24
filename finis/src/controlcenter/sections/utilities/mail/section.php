<?php

/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;

$opt=FN_GetParam("opt",$_GET,"html");

$from=FN_GetParam("from",$_POST,"html");
$to=FN_GetParam("to",$_REQUEST,"html");
$body=FN_GetParam("body",$_POST,"html");
$subject=FN_GetParam("subject",$_POST,"html");

if (!empty($_POST['to']))
{
    $to=FN_GetParam("to",$_POST,"html");
    if (FN_SendMail($to,$subject,$body,false,$from))
    {
        FN_Alert(FN_Translate("the message has been sent"));
    }
    else
    {
        FN_Alert(FN_Translate("there was an error sending the email"));
    }
}
if ($from == "")
{
//	$from = $_FN['site_email_address'];
}
if (FN_IsAdmin())
{
    echo "<form method=\"post\" action=\"?fnapp=controlcenter&opt=$opt\">";
    echo FN_Translate("from");
    echo "<br /><input value=\"$from\" name=\"from\" type=\"text\" />";
    echo "(default: {$_FN['site_email_address']})<br />";
    echo FN_Translate("to");
    echo "<br /><input value=\"$to\" name=\"to\" type=\"text\" /><br />";
    echo FN_Translate("subject");
    echo "<br /><input value=\"$subject\" name=\"subject\" type=\"text\" /><br />";
    echo FN_Translate("body");
    echo "<br /><textarea name=\"body\" cols=\"80\" rows=\"10\">$body";
    echo "</textarea><br />";

    echo "
	<button type=\"submit\">".FN_Translate("send")."</button>
";
    echo "</form>";
}
?>
