<?php
/**
 * @package Flatnux_module_news
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
global $_FN;
include_once("{$_FN['src_finis']}/modules/news/functions.php");
$config = FN_LoadConfig("{$_FN['src_finis']}/modules/news/config.php");
$NEWS = new FNNEWS($config);
FN_LoadMessagesFolder("{$_FN['src_finis']}/modules/news");
$_FN["news_tablename"] = $config['tablename'];
$_FN["news_enablecomments"] = $config['enablecomments'];
$_FN["news_guestnews"] = $config['guestnews'];
$_FN["news_signews"] = $config['signews'];
$_FN["news_generate_googlesitemap"] = $config['generate_googlesitemap'];
$_FN["news_guestcomment"] = $config['guestcomment'];
$_FN['force_htmleditor'] = $config['htmleditornews'];
if ( $_FN["news_tablename"] == "" )
{
	$_FN["news_tablename"] = $_FN['mod'];
}
$op = FN_GetParam("mode", $_GET, "flat");
if ( $op == "editarguments" )
{
	$NEWS->ArgumentsAdmin();
}
elseif ( $op == "editconfig" )
{
	$NEWS->ConfigurationAdmin();
}
else
{
	$NEWS->NewsAdmin();
}