<?php

/**
 * @package Finis
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
if ($_FN['datadir'] == "")
    $_FN['datadir'] = "misc";
if (!file_exists($_FN ['datadir']))
{
    @mkdir($_FN ['datadir']);
    touch("{$_FN ['datadir']}/firstinstall");
}

if (file_exists("{$_FN['datadir']}/firstinstall"))
{
    Header("Location: index.php?fnapp=install");
    die();
}
//AUTOBUILD -->
if (!is_writable("{$_FN['datadir']}/"))
{
    echo FN_i18n("permissions error: {$_FN['datadir']}");
    exit();
}


if (!is_writable("{$_FN['src_application']}/sections/"))
{
    echo FN_i18n("permissions error: {$_FN['src_application']}/sections/");
    exit();
}

FN_InitTables();
//---mod rewrite ----->
$checkk_index = basename(FN_GetParam("PHP_SELF", $_SERVER));
if ($checkk_index == "index.php" && $_FN['enable_mod_rewrite'] > 0 && file_exists("{$_FN['src_finis']}/include/finis.php"))
{
    header("Cache-Control: no-cache");
    header("Pragma: no-cache");
    if ($_FN['enable_mod_rewrite'] == 1)
    {
        if (function_exists('apache_get_modules'))
        {
            $modules = apache_get_modules();
            $mod_rewrite = in_array('mod_rewrite', $modules);
        }
        else
        {
            $mod_rewrite = getenv('HTTP_MOD_REWRITE') == 'On' ? true : false;
        }
        if (!$mod_rewrite)
        {
            if (FN_IsAdmin())
            {
                //FN_Alert("You have mod_rewrite enabled on Finis, but is not enabled on your server");
            }
            $_FN['enable_mod_rewrite'] = 0;
        }
    }
    elseif ($_FN['enable_mod_rewrite'] > 1)
        $mod_rewrite = true;

    if ($mod_rewrite)
    {
        if (basename($_SERVER['SCRIPT_FILENAME']) == "index.php" )
        {
            FN_BuildHtaccess();
        }
    }
}
//<-- AUTOBUID
?>