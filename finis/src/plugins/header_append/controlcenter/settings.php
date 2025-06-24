<?php
/**
 * @package Finis_header_append
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2012
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
##<fnmodule>google-analytics</fnmodule>
if (!file_exists("{$_FN['datadir']}/header_append.php"))
{
    FN_Write("", "{$_FN['datadir']}/header_append.php");
}
$opt = FN_GetParam("opt", $_GET);
echo FN_Translate("put header code here");
FN_EditContent("{$_FN['datadir']}/header_append.php","?opt=$opt","?opt=$opt");
?>
