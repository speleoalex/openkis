<?php
/**
 * Run by finis (include/autoexec.php) on every page of the site, after FN_InitSections():
 * the menu entry "Offline use" only for the logged users when the region asks for it
 * ($_FN['openkis_offline_menu_users_only'], extra/openkis/openkis_offline.inc.php).
 */
defined('_FNEXEC') or die('Restricted access');
if (function_exists("openkis_OfflineMenuOnlyForUsers"))
{
    openkis_OfflineMenuOnlyForUsers();
}
