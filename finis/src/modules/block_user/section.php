<?php

/**
 * @package Finis_module_login
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 1011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
require_once("{$_FN['src_finis']}/modules/login/functions_login.php");
if ($_FN['user'] == "")
{
    $template = file_exists("themes/{$_FN['theme']}/modules/block_user/login.tp.html") ? "themes/{$_FN['theme']}/modules/block_user/login.tp.html" : "";
    FN_LoginForm($template);
}
else
{
    $block = basename(__DIR__);
    $template = file_exists("themes/{$_FN['theme']}/modules/{$block}/profile.tp.html") ? file_get_contents("themes/{$_FN['theme']}/modules/{$block}/profile.tp.html") : "";
    if ($template)
    {
        $vars = $_FN;
        $vars['username']=$_FN['user'];
        $vars['urlimage']=FN_GetUserImage($_FN['user']);
        
        echo FN_TPL_ApplyTplString($template, $vars, "themes/{$_FN['theme']}/modules/{$_FN['block']}/");
    }
    else
    {
        echo "<div  class=\"fnlogin\" style=\"text-align:center\">";
        echo "<a href=\"" . FN_RewriteLink("index.php?mod=login&amp;op=editreg") . "\">{$_FN['user']}</a><br />";
        echo FN_HtmlUserImage($_FN['user']);
        echo "<br /><br />";
        FN_LogoutForm();
        echo "</div>";
    }
}
