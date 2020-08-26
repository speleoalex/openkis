<?php

/**
 * @package Flatnux_module_login
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
defined('_FNEXEC') or die('Restricted access');
require_once("modules/login/functions_login.php");
global $_FN;
$op=FN_GetParam("op",$_GET);
$username=FN_GetParam("user",$_GET);
if (!FN_IsAdmin())
    $username=$_FN['user'];
switch($op)
{
    case "register":
    case "end_reg":
    case "send_code":
        if (!empty($_FN['enable_registration']))
        {
            FNREG_ManageRegister();
        }
        break;
    case "editreg":
        //if (!empty($_FN['enable_registration']) || FN_IsAdmin())
        {
            FNREG_ManageEditRegister($username);
        }
        break;
    case "recovery":
        FNREG_ManageRecovery();
        break;

    default:
        if (empty($op))
        {
            echo FN_HtmlContent("sections/{$_FN['mod']}");
        }
        if ($_FN['user']== "")
        {
            $templateForm=false;
            $tppath=FN_FromTheme("modules/login/login.tp.html",false);
            if (file_exists($tppath))
            {
                $templateForm=file_get_contents(FN_FromTheme("modules/login/login.tp.html",false));
                
            }
            FN_LoginForm($templateForm);
        }
        else
        {
            $templateStr=file_get_contents(FN_FromTheme("modules/login/profile.tp.html",false));
            $tplbasepath=dirname(FN_FromTheme("modules/login/profile.tp.html",false))."/";
            $tplvars=FN_GetUser($_FN['user']);
            $tplvars['urleditprofile']=FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;op=editreg");
            $tplvars['username']=$_FN['user'];
            $tplvars['urlimage']=FN_GetUserImage($_FN['user']);
            $tplvars['urllogout']=FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;fnlogin=logout");
            foreach($tplvars as $k=> $var)
            {
                if ($k!="password")
                    $_FN['return'][$k]=$var;
            }
            echo FN_TPL_ApplyTplString($templateStr,$tplvars,$tplbasepath);
        }
        break;
}
?>