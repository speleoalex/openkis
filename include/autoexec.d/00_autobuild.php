<?php
/**
 * @package Flatnux
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
if ($_FN['datadir'] == "")
	$_FN['datadir'] = "misc";
if (file_exists("{$_FN['datadir']}/firstinstall"))
{
	Header("Location: install.php");
	die();
}
//AUTOBUILD -->
if (!is_writable("{$_FN['datadir']}/"))
{
	echo FN_i18n("permissions error");
	echo "<pre>";
	echo "</pre>";
	exit();
}
FN_InitTables();
//---mod rewrite ----->
$checkk_index = basename(FN_GetParam("PHP_SELF",$_SERVER));
if ($checkk_index == "index.php" && $_FN['enable_mod_rewrite'] > 0 && file_exists("./include/flatnux.php"))
{
	header("Cache-Control: no-cache");
	header("Pragma: no-cache");
	if ($_FN['enable_mod_rewrite'] == 1)
	{
		if (function_exists('apache_get_modules'))
		{
			$modules = apache_get_modules();
			$mod_rewrite = in_array('mod_rewrite',$modules);
		}
		else
		{
			$mod_rewrite = getenv('HTTP_MOD_REWRITE') == 'On' ? true : false;
		}
		if (!$mod_rewrite)
		{
			if (FN_IsAdmin())
			{
				//FN_Alert("You have mod_rewrite enabled on Flatnux, but is not enabled on your server");
			}
			$_FN['enable_mod_rewrite'] = 0;
		}
	}
	elseif ($_FN['enable_mod_rewrite'] > 1)
		$mod_rewrite = true;

	if ($mod_rewrite)
	{
		if (basename($_SERVER['SCRIPT_FILENAME']) == "index.php" && file_exists("include/flatnux.php"))
		{
			FN_BuildHtaccess();
		}
	}
}
//<-- AUTOBUID
?>