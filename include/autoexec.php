<?php
/**
 * @package Flatnux
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */

defined('_FNEXEC') or die('Restricted access');
if (file_exists("include/autoexec.d/") && false != ($handle=opendir('include/autoexec.d/')))
{
	$filestorun=array();
	while (false !== ($file=readdir($handle)))
		if (FN_GetFileExtension($file) == "php" && !preg_match("/^none_/si",$file))
			$filestorun[]=$file;
	closedir($handle);
	FN_NatSort($filestorun);
	foreach ($filestorun as $runfile)
	{
		include ("include/autoexec.d/$runfile");
	}
}
?>