<?php
/**
 * The dbview sections without a list.tp.html of their own use the layout of
 * the caves list (extra/openkis/templates/list.tp.html, finis
 * $_FN['dbview_list_template']); openkis_ListSettings() gives it the settings
 * of the section of this request. Queued with FN_IncludeScript(): the section
 * and the site settings are known only at the end of the bootstrap.
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$_FN['dbview_list_template'] = "extra/openkis/templates/list.tp.html";
openkis_ListSettings(isset($_FN['mod']) ? $_FN['mod'] : "");
