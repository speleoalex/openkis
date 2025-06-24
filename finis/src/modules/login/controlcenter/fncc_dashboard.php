<?php
/**
 * @package Finis_module_login
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined( '_FNEXEC' ) or die( 'Restricted access' );
global $_FN;
$DB = new XMETADatabase("fndatabase", $_FN['datadir']);
//FN_LoadMessagesFolder("{$_FN['src_application']}/sections/login/");
//published
echo "<b>".FN_Translate("users").":</b><br />";
$allusers = $DB->query("SELECT username FROM fn_users");
$num_users = count($allusers);
echo "<a href=\"index.php?fnapp=controlcenter&opt=users_and_groups/users\">" . FN_Translate("users") . "</a> : $num_users <br />";
$allusers = $DB->query("SELECT username FROM fn_users WHERE active <> '1'");
$num_users = count($allusers);
echo "<a href=\"index.php?fnapp=controlcenter&opt=users_and_groups/users\">" . FN_Translate("not active users") . "</a> : $num_users<br />";
$allusers = $DB->query("SELECT username FROM fn_users WHERE level >= '10'");
$num_users = count($allusers);
echo "<a href=\"index.php?fnapp=controlcenter&opt=users_and_groups/users\">" . FN_Translate("administrators") . "</a> : $num_users <br />";


?>