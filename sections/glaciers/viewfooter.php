<?php
/**
 * @package Flatnux_module_navigator
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * 
 * set sectiontype navigator in YourPage and
 * copy this file in sections/YourPage to execute this script
 * 
 */
#<fnmodule>navigator</fnmodule>
defined('_FNEXEC') or die('Restricted access');
$config=FN_LoadConfig("modules/dbview/config.php","glacial");
$params['fields']="code,latitude,longitude,elevation,name,synonyms,depth_total,depth_negative,depth_positive,lenght_total";
$params['navigate_groups']=array("nv_glaciers"=>$row['code']);
$dbview=new FNDBVIEW($config);
$items=$dbview->GetResults($config,$params);
if (count($items) > 0)
{
    echo openkis_CavesGroupList($items, "glacial", "nv_glaciers={$row['code']}", "Cavità conosciute in questo ghiacciaio: " . count($items));
}


?>