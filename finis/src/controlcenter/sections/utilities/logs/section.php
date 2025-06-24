<?php
/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
$opt=FN_GetParam("opt",$_GET);
$log=FN_GetParam("log",$_GET);
global $_FN;
if ($log == "")
{
	$logs=glob("{$_FN['datadir']}/log/*.php");
	echo FN_Translate("date").":";
	foreach ($logs as $log)
	{
		$filename=basename($log);
		$date=substr($filename,0,7);
		echo "<br /><a href=\"?fnapp=controlcenter&opt=$opt&amp;log=$filename\">" . $date . "</a>";
	}
}
else
{
	$contents=file("{$_FN['datadir']}/log/$log");
	unset($contents[0]);
	$rows=array();
	foreach ($contents as $row)
	{
		$row=explode(";",$row);
		$rows[]=$row;
	}
	echo "<a href=\"?fnapp=controlcenter&opt=$opt\">".  FN_Translate("back")."</a>";
	FNLOG_PrintTable($rows);
	
}
/**
 *
 * @param array $rows 
 */
function FNLOG_PrintTable($rows)
{
	if (is_array($rows) && count($rows) > 0)
	{
		echo "<table>";
		echo "<tr>".
		"<td>".  FN_Translate("data")."</td>".
		"<td>".  FN_Translate("ip")."</td>".
		"<td>".  FN_Translate("file")."</td>".
		"<td>".  FN_Translate("section")."</td>".
		"<td>".  FN_Translate("username")."</td>".
		"<td>".  FN_Translate("event")."</td>".
		
		"</tr>";
		foreach ($rows as $row)
		{
			echo "<tr>";
			foreach ($row as $item)
			{
				echo "<td>{$item}</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
}
?>