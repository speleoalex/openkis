<?php
/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$opt = FN_GetParam("opt",$_GET,"html");
$table = FN_XMDBForm("fn_cc_users");
$table->formvals['ccsections']['options'] = FNCC_GetMenuItemsLinks();
$table->formvals['default']['options'] = FNCC_GetMenuItemsLinks();

FNCC_XMETATableEditor($table);
/**
 *
 * @global global $_FN
 * @return type 
 */
function FNCC_GetMenuItemsLinks()
{
	global $_FN;
	$opt = FN_GetParam("opt",$_GET);
	$ret = array();
	$menu = array();
	$dirs = FN_ListDir("controlcenter/sections/");
	FN_NatSort($dirs);
	foreach ($dirs as $sectiongroup)
	{
		$sections = FN_ListDir("controlcenter/sections/$sectiongroup");
		foreach ($sections as $section)
		{
			$item['title'] = FN_GetFolderTitle("controlcenter/sections/$sectiongroup/$section");
			$item['value'] = "$sectiongroup/$section";
			$ret[] = $item;
			if ($sectiongroup == "settings" && $section == "cms")
			{
				$_sectionsIngroup = array();
				$dirsconf = FNCC_GetSectionsConfigs();
				foreach ($dirsconf as $_section)
				{
					$item['title'] = $_section['title'];
					$item['value'] = "{$_section['opt']}";
					$ret[] = $item;
				}
				//customs configs----<
				//plugins configs---->
				$dirsconf = FNCC_GetPluginsConfigs();
				foreach ($dirsconf as $_section)
				{
					$item['title'] = $_section['title'];
					$item['value'] = "rnt_ccnf_config_plugin_{$_section['id']}";
					$ret[] = $item;
				}
				//plugins configs----<
//---------------get list of config.php in plugins and sections----------------<				
			}
		}
	}
//---------------get settings.php in plugins and sections --------------------->
	//customs configs config---->
	$dirs = FNCC_GetSectionsSettings();
	foreach ($dirs as $section)
	{
		$item['value'] = "{$section['opt']}";
		$item['title'] = $section['title']."";
		$ret[] = $item;
	}
	//customs configs----<
	//plugins configs---->
	$dirs = FNCC_GetPluginsSettings();
	foreach ($dirs as $section)
	{
		$item['value'] = "rnt_ccnf_plugin_{$section['id']}";
		$item['title'] = $section['title'];
		$ret[] = $item;
	}
//---------------get settings.php in plugins and sections ---------------------<		
	//dprint_r($ret);
	return $ret;
}
