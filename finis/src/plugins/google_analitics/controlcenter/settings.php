<?php
/**
 * @package Finis_GoogleAnalytics
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2012
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
##<fnmodule>google-analytics</fnmodule>
if (!file_exists("{$_FN['datadir']}/google_analytics.php"))
{
    FN_Write("", "{$_FN['datadir']}/google_analytics.php");
}
$opt = FN_GetParam("opt", $_GET);
echo FN_Translate("put google analytics code here");
FN_EditContent("{$_FN['datadir']}/google_analytics.php","?opt=$opt","?opt=$opt");
echo "<br /><br /><a target=\"_blank\" href=\"https://www.google.com/analytics/\">".FN_Translate("go to google analytics")."</a>";
?>
