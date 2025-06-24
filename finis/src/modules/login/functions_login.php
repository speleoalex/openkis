<?php

/**
 * @package Finis_module_login
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */

/**
 *
 * @global array $_FN
 * @return  
 */
function FNREG_ManageRecovery()
{
    global $_FN;
    $_FN['result'] = array();
    $config = FN_LoadConfig("{$_FN['src_finis']}/modules/login/config.php");
    $postuser = FN_GetParam("username", $_REQUEST, "html");
    $getuser = FN_GetParam("user", $_REQUEST, "html");
    $rnd = FN_GetParam("rnd", $_GET, "html");
    $errors = "";
    $tplvars = $_FN;
    $tplvars['txtusername'] = FN_Translate("username");
    $body = "";
    // step 1 generate rnd and send -------->
    if ($postuser != "")
    {
        if (!FN_GetUser($postuser))
        {
            $errors = $postuser . ": " . fn_i18n("user does not exist");
        }
        else
        {
            $uservalues = FN_GetUser($postuser);
            $newrnd = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1, 10))), 1, 10);
            FN_UpdateUser($postuser, array("rnd" => $newrnd));
            $server = FN_RewriteLink("index.php?mod={$_FN['mod']}&op=recovery&user=$postuser&rnd=$newrnd&lang={$_FN['lang']}", "&", true);
            //$body=FN_Translate("password recovery")."\n"."\n".fn_i18n("to retrieve the password, please follow this link").":\n$server\n\n".FN_Translate("a new password will be generated and sent to your email address");
            $subject = FN_i18n("password recovery");
            $tpl_filemail = FN_FromTheme("{$_FN['src_finis']}/modules/login/mailrecovery_step1.tp.html", false);
            if (empty($config['change_password_online']))
            {
                $tpl_filemail = FN_FromTheme("{$_FN['src_finis']}/modules/login/mailrecovery_step1.tp.html", false);
            }
            else
            {
                $tpl_filemail = FN_FromTheme("{$_FN['src_finis']}/modules/login/mailrecovery_step1_password.tp.html", false);
            }
            if (file_exists($tpl_filemail))
            {
                $uservalues['url'] = $server;
                $body = FN_TPL_ApplyTplFile($tpl_filemail, $uservalues);
                $tmpsubject = get_xml_single_element("title", $body);
                if ($tmpsubject)
                {
                    $subject = $tmpsubject;
                }
            }
            //send mail
            $email = trim(ltrim($uservalues['email']));
            if ($email == "" && FN_CheckMail($uservalues[$username_field]))
            {
                $email = $uservalues[$username_field];
            }
            if (FN_CheckMail($email))
            {
                FN_SendMail($email, $subject, $body, false !== stristr($body, "<html"));
                FN_Log("User $postuser recover password step 1.");
                $tplvars['txtresults'] = FN_Translate("an email has been sent to your email address with instructions to recover your password");
            }
            else
            {
                $tplvars['txtresults'] = FN_Translate("error");
            }
            $tplfile = FN_FromTheme("{$_FN['src_finis']}/modules/login/passwordrecovery_step1.tp.html", false);
            $tplbasepath = dirname($tplfile) . "/";
            $templateForm = file_get_contents($tplfile);
            if ($_FN['username_is_email'])
            {
                $tplvars['txtusername'] = FN_Translate("email");
            }
            $tplvars['urlnext'] = FN_RewriteLink("index.php?mod=" . $_FN['mod'], "&", true);
            $templateFormHtml = FN_TPL_ApplyTplString($templateForm, $tplvars, $tplbasepath);
            echo $templateFormHtml;
            $_FN['return']['txtresults'] = $tplvars['txtresults'];
            return;
        }
    }
    // step 1 generate rnd and send ---------<
    // step 2 generate password and send ---->
    if ($getuser != "" && $rnd != "")
    {
        $txtresults = "";
        $uservalues = FN_GetUser($getuser);
        $email = trim(ltrim($uservalues['email']));
        if ($email == "" && FN_CheckMail($uservalues[$username_field]))
        {
            $email = $uservalues[$username_field];
        }
        if (!$uservalues)
        {
            $errors = FN_i18n("user does not exist");
        }
        if ($uservalues['rnd'] == "")
        {
            $errors = FN_Translate("the password has been sent to your email address, if you have not yet received it occurs in spam or repeat the procedure password recovery");
        }
        elseif ($uservalues['rnd'] != $rnd)
        {
            $errors = FN_i18n("authentication failure");
        }
        if ($errors == "")
        {
            if (empty($config['change_password_online']))
            {
                $chrs = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
                $newpass = "";
                for ($i = 0; $i < 10; $i++)
                {
                    $ch = rand(0, 35);
                    $newpass .= $chrs[$ch];
                }
                $subject = $_FN['sitename'] . " - " . FN_i18n("password recovery");
                $body = fn_i18n("password recovery") . "\n" . "\n" . fn_i18n("this is the new password") . " : $newpass\n\n";
                $tpl_filemail = FN_FromTheme("{$_FN['src_finis']}/modules/login/mailrecovery_step2.tp.html", false);
                if (file_exists($tpl_filemail))
                {
                    $uservalues['password'] = $newpass;
                    $body = FN_TPL_ApplyTplFile($tpl_filemail, $uservalues);
                    $tmpsubject = get_xml_single_element("title", $body);
                    if ($tmpsubject)
                    {
                        $subject = $tmpsubject;
                    }
                }
                FN_UpdateUser($getuser, array("rnd" => ""), $newpass);
                //send mail
                FN_SendMail($email, $subject, $body, false !== stristr($body, "<html"));
                FN_Log("User $getuser recover password. step 2, password sended");
                $txtresults = FN_Translate("the password has been sent to your email address, if you have not yet received it occurs in spam or repeat the procedure password recovery.");
            }
            else
            {

                $tplfile = FN_FromTheme("{$_FN['src_finis']}/modules/login/passwordrecovery_step2_password.tp.html", false);
                $tplbasepath = dirname($tplfile) . "/";
                $templateForm = file_get_contents($tplfile);
                $tplvars['formaction'] = FN_RewriteLink("index.php?mod=" . $_FN['mod'] . "&rnd=$rnd&user=$getuser&updateuser=1&op=recovery", "&", true);
                $tplvars['errors'] = "";
                //update password 
                if (empty($_REQUEST['__NOSAVE']) && empty($_REQUEST['fnlogin']) && isset($_REQUEST['updateuser']) && isset($_POST['newpassword']))
                {
                    $form = FN_GetUserForm();
                    $username_field = empty($form->fieldname_user) ? "username" : $form->fieldname_user;
                    $active_field = empty($form->fieldname_active) ? "username" : $form->fieldname_active;
                    $password_field = empty($form->fieldname_password) ? "passwd" : $form->fieldname_password;

                    $newvalues = $uservalues;
                    $passwd = FN_GetParam("newpassword", $_POST, "html");
                    $passwd2 = FN_GetParam("newpassword_retype", $_POST, "html");
                    if (empty($passwd))
                    {
                        $errors = FN_Translate("enter a password");
                    }
                    if ($passwd != $passwd2)
                    {
                        $errors = FN_Translate("passwords do not match");
                    }
                    if (function_exists("FN_PasswordVerifyConstraints"))
                    {
                        $errors = FN_PasswordVerifyConstraints($passwd);
                    }
                    if ($errors)
                    {
                        //form update password with errors
                        $tplvars['errors'] = $errors;
                        $templateForm = FN_TPL_ApplyTplString($templateForm, $tplvars, $tplbasepath);
                        echo $templateForm;
                        return;
                    }
                    else
                    {
                        FN_UpdateUser($getuser, array("rnd" => ""), $passwd);
                        $txtresults = FN_Translate("the password has been successfully changed");
                    }
                }
                else
                {
                    //form update password
                    $templateForm = FN_TPL_ApplyTplString($templateForm, $tplvars, $tplbasepath);
                    echo $templateForm;
                    return;
                }
            }
        }
        else
        {
            $txtresults = $errors;
        }
        $tplfile = FN_FromTheme("{$_FN['src_finis']}/modules/login/passwordrecovery_step2.tp.html", false);
        $tplbasepath = dirname($tplfile) . "/";
        $templateForm = file_get_contents($tplfile);
        $tplvars['txtusername'] = FN_Translate("username");
        $tplvars['txtresults'] = $txtresults;
        if ($_FN['username_is_email'])
        {
            $tplvars['txtusername'] = FN_Translate("email");
        }
        $tplvars['urlnext'] = FN_RewriteLink("index.php?mod=" . $_FN['mod'], "&", true);
        $templateForm = FN_TPL_ApplyTplString($templateForm, $tplvars, $tplbasepath);
        echo $templateForm;
        $_FN['return']['txtresults'] = $tplvars['txtresults'];

        return;
    }
    // step 2 generate password and send ----<
    //request password recovery ---->
    $tplfile = FN_FromTheme("{$_FN['src_finis']}/modules/login/passwordrecovery.tp.html", false);
    $tplbasepath = dirname($tplfile) . "/";
    $templateForm = file_get_contents($tplfile);
    $tplvars = $_FN;
    $tplvars['txtusername'] = FN_Translate("username");
    if ($_FN['username_is_email'])
    {
        $tplvars['txtusername'] = FN_Translate("email");
    }
    $tplvars['formaction'] = FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;op=recovery", "&amp;", true);
    $tplvars['urlcancel'] = FN_RewriteLink("index.php?mod=" . $_FN['mod'], "&", true);
    $tplvars['login_error'] = $errors;
    $templateForm = FN_TPL_ApplyTplString($templateForm, $tplvars, $tplbasepath);
    if (empty($tplvars['login_error']))
    {
        $templateForm = FN_TPL_ReplaceHtmlPart("loginerror", "", $templateForm);
    }
    echo $templateForm;
    if (!empty($tplvars['txtresults']))
        $_FN['return']['txtresults'] = $tplvars['txtresults'];
    if (!empty($errors))
        $_FN['return']['error'] = $errors;

    //request password recovery ----<
}

/**
 *
 * @global array $_FN
 * @param string $user
 */
function FNREG_ManageEditRegister($user = "")
{
    global $_FN;
    $errors = array();
    $form = FN_GetUserForm();
    $username_field = empty($form->fieldname_user) ? "username" : $form->fieldname_user;
    $active_field = empty($form->fieldname_active) ? "username" : $form->fieldname_active;
    if (isset($_FN['return']['uservalues']['password']))
    {
        unset($_FN['return']['uservalues']['password']);
    }
    $form->SetLayout("table");
    if ($user == "")
        $user = $_FN['user'];
    $oldvalues = FN_GetUser($user);
    $newvalues = $oldvalues;
    $_FN['return']['fields'] = $form->formvals;
    $_FN['return']['uservalues'] = $newvalues;
    $reg_ok = false;
    $postvar = $_POST;
    if ($oldvalues)
    {
        if (isset($postvar['updateuser']))
        {
            foreach ($form->formvals as $key => $fieldvalue)
            {
                if (isset($fieldvalue['type']) && ($fieldvalue['type'] == 'image' || $fieldvalue['type'] == 'file'))
                {
                    if (isset($_FILES[$key]['name']))
                    {
                        if ($_FILES[$key]['name'] != "")
                        {
                            $newvalues[$key] = FN_GetParam("name", $_FILES[$key]);
                        }
                    }
                    if (isset($postvar["__isnull__$key"]))
                    {
                        $newvalues[$key] = "";
                    }
                }
                else
                {
                    if (isset($postvar[$key]))
                    {
                        $newvalues[$key] = FN_GetParam($key, $postvar, "html");
                    }
                }
            }
        }

        $newvalues[$form->xmltable->primarykey] = $oldvalues[$form->xmltable->primarykey];
        if (!FN_IsAdmin())
        {
            $newvalues['email'] = $oldvalues['email'];
            $newvalues[$active_field] = $oldvalues[$active_field];
            $newvalues['level'] = $oldvalues['level'];
            $newvalues['group'] = $oldvalues['group'];
        }
        else
        {
            if (isset($_POST[$active_field]))
                $newvalues[$active_field] = FN_GetParam($active_field, $_POST, "html");
            if (isset($_POST['level']))
                $newvalues['level'] = FN_GetParam("level", $_POST, "html");
            $groups = FN_GetGroups();
            $insgroup = array();
            foreach ($groups as $g)
            {
                if (isset($_POST['group-' . $g]))
                {
                    $insgroup[] = $g;
                }
            }
            $newvalues['group'] = implode(",", $insgroup);
        }
        array_merge($_FN['return'], $newvalues);
        if (empty($_REQUEST['__NOSAVE']) && empty($_REQUEST['fnlogin']))
            if (isset($_POST['updateuser']))
            {
                $errors = $form->Verify($newvalues, true);
                if (count($errors) == 0)
                {
                    $reg_ok = true;
                }
                else
                {
                    $_FN['return']['errors'] = $errors;
                }
            }
    }

    if (!$reg_ok)
    {
        echo "";
        $us = "";
        if (FN_IsAdmin() && $user != $_FN['user'])
            $us = "&user=$user";
        echo "";
        $templateForm = '<form enctype="multipart/form-data" action="{formaction}" method="post" name="register" >
    <h2>{i18n:Edit profile}:</h2>
    <table style="margin:auto">
        <!-- contents -->
        <!-- group -->
        <tr><td colspan="2" style="text-align:center"><b>{groupname}</b></td></tr>
        <!-- end_group -->
        <!-- item -->
        <tr>
            <td valign="top">{title}<!-- error --><span style="color:red"><br />{error}</span><!-- end_error --></td>
            <td valign="top">{input}</td>
        </tr>
        <!-- end_item -->
        <!-- endgroup -->
        <!-- end_endgroup -->
        <!-- end_contents -->
        <tr>
            <td>
                <input name="updateuser" value="1" type="hidden" />
                <button type="submit">{i18n:Save}</button>
                <button onclick="window.location = \'{urlcancel}\'">{i18n:Cancel}</button>
            </td>
        </tr>
    </table>
</form>
';

        //splx non lo prende start --->
        //carica form.tp.html del tema ma non editreg.tp.html
        $tplfile = FN_FromTheme("{$_FN['src_finis']}/modules/login/editreg.tp.html", false);
        $tplbasepath = dirname($tplfile) . "/";
        if (file_exists($tplfile))
        {
            $templateForm = file_get_contents($tplfile);
        }
        $tplvars = $_FN;
        $tplvars['text_on_update_fail'] = "";
        $tplvars['text_on_update_ok'] = "";
        $tplvars['text_on_insert_fail'] = "";
        $tplvars['text_on_insert_ok'] = "";
        $tplvars['formaction'] = FN_RewriteLink("index.php?mod=" . $_FN['mod'] . "&amp;op=editreg$us");
        $tplvars['urlcancel'] = FN_RewriteLink("index.php?mod=" . $_FN['mod']);
        $templateForm = FN_TPL_ApplyTplString($templateForm, $tplvars, $tplbasepath);
        $templateForm = str_replace("{json}", json_encode(array("errors" => $errors, "fields" => $form->formvals), JSON_FORCE_OBJECT), $templateForm);
        $form->SetlayoutTemplate($templateForm);
        //splx non lo prende end ---<
        $form->ShowUpdateForm($newvalues[$form->xmltable->primarykey], FN_IsAdmin(), false, $errors);

        array_merge($_FN['return'], $tplvars);
    }
    else
    {
        if (false != FN_UpdateUser($newvalues[$username_field], $newvalues))
        {
            //FN_Log("User updated:{$newvalues['username']}");
            echo FN_Translate("the data were successfully updated");
            FN_Login($newvalues[$username_field]);
            array_merge($_FN['return'], $newvalues);
        }
        else
        {
            echo FN_Translate("error");
            $_FN['return']['error'] = "error";
            echo "<br /><br /><a href=\"javascript:history.back()\">&lt;&lt; " . FN_Translate("back") . "</a>";
        }
        echo "<br /><br /><a href=\"" . FN_RewriteLink("index.php?mod={$_FN['mod']}") . "\">" . FN_Translate("next") . " &gt;&gt;</a>";
    }
}

/**
 * 
 * @global array $_FN
 * @param type $uservalues
 * @param type $sendwelcomemessage
 * @return type
 */
function FNREG_ConfirmUser($uservalues, $sendwelcomemessage = 1)
{
    global $_FN;
    $form = FN_GetUserForm();
    $username_field = empty($form->fieldname_user) ? "username" : $form->fieldname_user;
    $active_field = empty($form->fieldname_active) ? "username" : $form->fieldname_active;
    $user = $uservalues[$username_field];
    $oldvalues = FN_GetUser($user, false);
    if ($oldvalues["$active_field"] == 1)
    {
        $sendwelcomemessage = false;
    }
    FN_UpdateUser($user, array("$active_field" => 1));
    if ($sendwelcomemessage == false)
    {
        return;
    }
    if (function_exists("FN_SendMailWelcome"))
    {
        FN_SendMailWelcome($uservalues);
    }
    else
    {
        $ishtml = true;
        $tpl_filemail = FN_FromTheme("{$_FN['src_finis']}/modules/login/mailwelcome.{$_FN['lang']}.tp.html", false);
        if (!file_exists($tpl_filemail))
            $tpl_filemail = FN_FromTheme("{$_FN['src_finis']}/modules/login/mailwelcome.tp.html", false);
        $subject = FN_Translate("confirm registration site") . " " . $_FN['sitename'];
        if (file_exists("{$_FN['datadir']}/messages/WelcomeMessage.{$_FN['lang']}.txt")) //obsolete
        {
            $tpl_filemail = "{$_FN['datadir']}/messages/WelcomeMessage.{$_FN['lang']}.txt";
            $ishtml = false;
        }
        if (file_exists("{$_FN['datadir']}/messages/mailwelcome.{$_FN['lang']}.tp.html"))
        {
            $tpl_filemail = "{$_FN['datadir']}/messages/mailwelcome.{$_FN['lang']}.tp.html";
            $ishtml = true;
        }
        $tplvalues = $uservalues;
        $tplvalues['username'] = $uservalues[$username_field];
        $tplvalues['sitename'] = $_FN['sitename'];
        $tplvalues['siteurl'] = $_FN['siteurl'];
        $mailbody = FN_TPL_ApplyTplFile($tpl_filemail, $tplvalues);
        $mailbody = str_replace("!USERNAME!", $uservalues[$username_field], $mailbody);
        $mailbody = str_replace("!SITENAME!", $_FN['sitename'], $mailbody);
        $mailbody = str_replace("!SITEURL!", $_FN['siteurl'], $mailbody);
        $mailbody = FN_FixNewline($mailbody);
        $tmpsubject = get_xml_single_element("title", $mailbody);
        if ($tmpsubject)
        {
            $subject = $tmpsubject;
        }
        if ($uservalues['email'])
        {
            FN_SendMail($uservalues['email'], $subject, $mailbody, $ishtml);
        }
    }
}

/**
 *
 * @global array $_FN
 * @return bool
 */
function FNREG_ManageRegister($actionform = "")
{
    
    global $_FN;
    $config = FN_LoadConfig("{$_FN['src_finis']}/modules/login/config.php");
    $form = FN_GetUserForm();
    $username_field = empty($form->fieldname_user) ? "username" : $form->fieldname_user;
    $active_field = empty($form->fieldname_active) ? "username" : $form->fieldname_active;
    $password_field = empty($form->fieldname_password) ? "passwd" : $form->fieldname_password;

    $tplvalues = array("message" => "");
    $tplfile = FN_FromTheme("{$_FN['src_finis']}/modules/login/manageregister.tp.html", false);
    $op = FN_GetParam("op", $_GET);
    $conditions = FN_XMETADBQuery("SELECT * FROM fn_conditions WHERE enabled LIKE '1' ORDER BY position");

    $sendRegistrationCode = false;

    if (!is_array($conditions))
    {
        $conditions = array();
    }
    //-----resend registration code-------------------------------------------->
    if ($op == "send_code")
    {
        
        $tplfileresend = FN_FromTheme("{$_FN['src_finis']}/modules/login/resend.tp.html", false);
        $tplvalues['error'] = false;
        $tplvalues['formaction'] = FN_RewriteLink("index.php?mod={$_FN['mod']}&op=send_code");
        $user = trim(ltrim(FN_GetParam("username", $_REQUEST, "html")));
        if ($user)
        {
            $uservalues = FN_GetUser($user);
            $email = $uservalues['email'];
            if ($uservalues)
            {
                $sendRegistrationCode = true;
            }
            else
            {
                $tplvalues['error'] = FN_Translate("the email address entered does not match any user");
            }
        }
        if (!$sendRegistrationCode)
        {

            $tplvalues['txtusername'] = $_FN['username_is_email'] ? FN_Translate("email") : FN_Translate("username");
            echo FN_TPL_ApplyTplFile($tplfileresend, $tplvalues);
            $_FN['return'] = $tplvalues;
            return true;
        }
    }
    //-----resend registration code--------------------------------------------<
    if ($op == "end_reg")
    {
        $user = trim(ltrim(FN_GetParam("user", $_GET, "html")));
        $id = trim(ltrim(FN_GetParam("id", $_GET, "html")));

        if ($user == "" && $id != "")
        {
            $users = FN_GetUsers(array("rnd" => $id));
            if (is_array($users) && count($users) == 1)
            {
                $uservalues = $users[0];
                $user = $uservalues[$username_field];
            }
        }
        else
        {
            $uservalues = FN_GetUser($user);
        }
        $message = "";
        if ($user != "" && $id != "" && isset($uservalues['rnd']) && $uservalues['rnd'] == $id)
        {
            FNREG_ConfirmUser($uservalues, !empty($config['send_welcome_message']));
            //complete registration-------------------------------------------->
            if (function_exists("FN_OnConfirmUser"))
            {
                if (!$uservalues[$active_field])
                    FN_OnConfirmUser($uservalues);
            }
            if (file_exists("{$_FN['src_finis']}/modules/login/manageregister_completed.tp.html"))
                $tplfile = "{$_FN['src_finis']}/modules/login/manageregister_completed.tp.html";
            if (file_exists("themes/{$_FN['theme']}/modules/login/manageregister_completed.tp.html"))
                $tplfile = "themes/{$_FN['theme']}/modules/login/manageregister_completed.tp.html";
            $tplvalues['message'] = FN_Translate("registration has been completed");
            echo FN_TPL_ApplyTplFile($tplfile, $tplvalues);
            $_FN['return'] = $tplvalues;
            return true;
            //complete registration--------------------------------------------<
        }
        else
        {
            $tplvalues['message'] = FN_Translate("error registration");
        }
        echo FN_TPL_ApplyTplFile($tplfile, $tplvalues);
        $_FN['return']['result'] = $tplvalues;
        return false;
    }
    $newvalues = $form->getbypost();

    foreach ($form->formvals as $key => $value)
    {
        if (isset($value['type']) && ($value['type'] == 'image' || $value['type'] == 'file') && isset($_FILES[$key]['name']))
        {
            if ($_FILES[$key]['name'] != "")
            {
                $newvalues[$key] = FN_GetParam("name", $_FILES[$key]);
            }
        }
        else
        {
            $newvalues[$key] = FN_GetParam($key, $_POST, "flat");
        }
    }
    if (!empty($_FN['registration_by_email']))
    {
        $newvalues[$active_field] = 0;
    }
    else
    {
        $newvalues[$active_field] = 1;
    }
    $newvalues['level'] = 0;

    if (!empty($_FN['username_is_email']) && !empty($form->formvals['username']))
    {
        $form->formvals['username']['frm_show'] = 0;
        $form->formvals['username']['frm_required'] = 0;
        $form->formvals['email']['frm_validator'] = "FN_CheckMail";
        $form->formvals['email']['unique'] = 1;
        $form->formvals['username']['frm_validchars'] .= "01234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-@.";
        if (!empty($newvalues['email']))
        {
            $newvalues['username'] = $newvalues['email'];
        }
    }
    $rnd = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1, 5))), 1, 5);
    $newvalues['rnd'] = $rnd;
    $newvalues['ip'] = FN_GetParam("REMOTE_ADDR", $_SERVER, "html");
    $insgroup = array(0 => "users");
    if (FN_IsAdmin())
    {
        $newvalues[$active_field] = FN_GetParam("active", $_POST, "html");
        $newvalues['level'] = FN_GetParam("level", $_POST, "html");
        $groups = FN_GetGroups();
        foreach ($groups as $g)
        {
            if (isset($_POST['group-' . $g]))
            {
                $insgroup[] = $g;
            }
        }
    }
    else
    {
        $newvalues[$active_field] = ( $_FN['registration_by_email'] == 1 ) ? 0 : 1;
    }
    $newvalues['group'] = implode(",", $insgroup);
    $errors = array();

    if (empty($_REQUEST['__NOSAVE']) && empty($_REQUEST['fnlogin']))
        if (isset($_POST['email']) || isset($_POST[$username_field]))
        {

            $errors = $form->Verify($newvalues);
            if (function_exists("FN_PasswordVerifyConstraints"))
            {
                $passwd = FN_GetParam("$password_field", $_REQUEST, "flat");
                $error_password = FN_PasswordVerifyConstraints($passwd);
                if ($error_password)
                {
                    $errors[$password_field] = array("title" => FN_Translate("password"), "field" => $password_field, "error" => FN_Translate("$error_password"));
                }
            }
            $conditions_ok = true;
            foreach ($conditions as $condition)
            {
                if (empty($condition['optional']) && empty($_POST['conditions_' . $condition['id']]))
                {
                    $errors['conditions'] = array("title" => FN_Translate("conditions of registration users"), "field" => "captcha", "error" => FN_Translate("to register is required to accept"));
                    $conditions_ok = false;
                }
            }




            //---check captcha----------------------------------------------------->
            $captcha_ok = true;
            if (!empty($config['enable_captcha']))
            {
                $captcha = FN_GetSessionValue("captcha");
                $security_code = FN_GetParam("security_code", $_POST);
                if (empty($captcha['security_code']) || $captcha['security_code'] != $security_code)
                {
                    $captcha_ok = false;
                    $errors['security_code'] = array("title" => FN_Translate("captcha"), "field" => "captcha", "error" => FN_Translate("incorrect security code"));
                }
            }
            //---check captcha-----------------------------------------------------<
            if (count($errors) == 0 && $conditions_ok == true && $captcha_ok == true)
            {
                $email = $newvalues['email'];
                $name = $newvalues[$username_field];
                if (!empty($_FN['registration_by_email']) && $newvalues[$active_field] != 1)
                {
                    if ($ret = FN_AddUser($newvalues))
                    {
                        $sendRegistrationCode = true;
                        $user = $newvalues[$username_field];
                    }
                    else
                    {
                        $tplvalues['message'] .= FN_Translate("error");
                    }
                }
                else
                {
                    $tplvalues['message'] .= "<br />" . FN_Translate("registration has been completed") . "<br />";
                    FN_AddUser($newvalues);
                    FNREG_ConfirmUser($newvalues, !empty($config['send_welcome_message']));
                    if (function_exists("FN_OnConfirmUser"))
                    {
                        FN_OnConfirmUser($newvalues);
                    }
                    else
                    {
                        FN_Login($newvalues[$username_field]);
                        echo FN_TPL_ApplyTplFile($tplfile, $tplvalues);
                    }
                    return true;
                }
            }
        }
    //-----------------registration (post values)------------------------------<


    if ($sendRegistrationCode)
    {
        $newvalues = FN_GetUser($user);
        $name = $newvalues[$username_field];
        $rnd = $newvalues['rnd'];
        if ($rnd == "")
        {
            $rnd = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', mt_rand(1, 5))), 1, 5);
            FN_UpdateUser($name, array("rnd" => $rnd));
        }
        $subject = FN_Translate("confirm registration site") . " " . $_FN['sitename'];
        $link = FN_RewriteLink("index.php?mod=" . $_FN['mod'] . "&op=end_reg&id=$rnd", "&", true);
        $tpl_filemail = FN_FromTheme("{$_FN['src_finis']}/modules/login/mailconfirm.{$_FN['lang']}.tp.html", false);
        $ishtml = true;
        if (!file_exists($tpl_filemail))
            $tpl_filemail = FN_FromTheme("{$_FN['src_finis']}/modules/login/mailconfirm.tp.html", false);
        $tplvalues = $newvalues;
        $tplvalues['message'] = "";
        $tplvalues['username'] = $newvalues[$username_field];
        $tplvalues['sitename'] = $_FN['sitename'];
        $tplvalues['siteurl'] = $_FN['siteurl'];
        $tplvalues['url'] = $link;
        $mailbody = FN_TPL_ApplyTplFile($tpl_filemail, $tplvalues);
        $mailbody = str_replace("!CONFIRMREGISTRATIONADDRESS!", $link, $mailbody);
        $mailbody = str_replace("!USERNAME!", $name, $mailbody);
        $mailbody = str_replace("!SITENAME!", $_FN['sitename'], $mailbody);
        $mailbody = str_replace("!SITEURL!", $_FN['sitename'], $mailbody);
        if (!strstr($mailbody, $link))
            $mailbody .= "\n$link";
        $mailbody = FN_FixNewline($mailbody);
        $tmpsubject = get_xml_single_element("title", $mailbody);
        if ($tmpsubject)
        {
            $subject = $tmpsubject;
        }
        if (function_exists("FN_SendRegistrationCode"))
        {
            return FN_SendRegistrationCode($newvalues, $link);
        }
        if (!FN_SendMail($email, $subject, $mailbody, $ishtml))
        {

            $tplvalues['message'] .= FN_Translate("the system failed to send the confirmation email");
        }
        else
        {
            $tplvalues['message'] .= "<br /><br />" . FN_Translate("it has been sended one email to you to the address") . " " . htmlentities($newvalues['email']) . " " . FN_i18n("with the instructions to complete the registration") . "<br />";
            $tplvalues['message'] .= FN_Translate("check your inbox");
//            $tplvalues['message'].="<br /><br /><div ><a href=\"".FN_RewriteLink("index.php")."\" >".FN_Translate("next")." &gt;&gt;&gt;</a></div>";
        }
        echo FN_TPL_ApplyTplFile($tplfile, $tplvalues);
        return false;
    }
//--------------------registration form---------------------------------------->
    if ($actionform == "")
    {
        $actionform = FN_RewriteLink("index.php?mod=" . $_FN['mod'] . "&amp;op=register");
    }

    $templateForm = '<form enctype="multipart/form-data" action="{formaction}" method="post" name="register" >
    <h2>{i18n:Edit profile}:</h2>
    <table style="margin:auto">
        <!-- contents -->
        <!-- group -->
        <tr><td colspan="2" style="text-align:center"><b>{groupname}</b></td></tr>
        <!-- end_group -->
        <!-- item -->
        <tr>
            <td valign="top">{title}<!-- error --><span style="color:red"><br />{error}</span><!-- end_error --></td>
            <td valign="top">{input}</td>
        </tr>
        <!-- end_item -->
        <!-- endgroup -->
        <!-- end_endgroup -->
        <!-- end_contents -->
        <!-- captcha -->
        
        <!-- endcaptcha -->
        <!-- conditions -->
        
        <!-- endconditions -->
 

         <tr>
            <td>
                <input name="updateuser" value="1" type="hidden" />
                <button type="submit">{i18n:Save}</button>
                <button onclick="window.location = \'{urlcancel}\'">{i18n:Cancel}</button>
            </td>
        </tr>
    </table>
</form>
';
    $tplfile = FN_FromTheme("{$_FN['src_finis']}/modules/login/register.tp.html", false);
    $tplbasepath = dirname(FN_FromTheme("{$_FN['src_finis']}/modules/login/register.tp.html", false)) . "/";
    if (file_exists($tplfile))
    {
        $templateForm = file_get_contents($tplfile);
    }

    $tplvars = $_FN;
    $tplvars['formaction'] = $actionform;
    $tplvars['urlcancel'] = FN_RewriteLink("index.php?mod=" . $_FN['mod']);
    $out = array();
    $tp_captcha = preg_match('/<!-- captcha -->(.*)<!-- endcaptcha -->/is', $templateForm, $out);
    $tp_captcha = !isset($out[0]) ? "" : $out[0];
    $tp_conditions = preg_match('/<!-- conditions -->(.*)<!-- endconditions -->/is', $templateForm, $out);
    $tp_conditions = !isset($out[0]) ? "" : $out[0];

    $htmlcaptcha = "";
    //----------------captcha--------------->
    if (!empty($config['enable_captcha']))
    {
        $tplvars['txt_error_security_code'] = "";
        FN_SetSessionValue("captcha", array("security_code" => rand(1000, 9999)));
        $htmlcaptcha .= "<img style=\"\" src=\"{$_FN['siteurl']}?fnapp=captcha\" alt=\"\" title=\"\" /> <input style=\"\" size=\"4\" autocomplete=\"off\" name=\"security_code\"  value = \"\" />";
        if (isset($_POST['security_code']) && $security_code != $captcha['security_code'])
            $tplvars['txt_error_security_code'] = FN_Translate("incorrect security code");
        $tplvars['htmlcaptcha'] = $htmlcaptcha;
    }
    else
    {
        $templateForm = str_replace($tp_captcha, "", $templateForm);
    }
    //----------------captcha---------------<
    //----------------conditions------------>
    $htmlconditions = "";
    if (is_array($conditions) && count($conditions) > 0)
    {
        $tcond = FN_XMDBForm("fn_conditions");
        $tpvars_conditions = array();
        foreach ($conditions as $condition)
        {
            $tpvars_conditions_item = array("title" => "", "text" => "", "checked" => "", "error" => "", "checked" => "");
            $htmlconditions .= "<div>";
            $condition = $tcond->GetRecordTranslatedByPrimarykey($condition['id']);
            if (!empty($condition['title']))
            {
                $tpvars_conditions_item['title'] = $condition['title'];
                $htmlconditions .= "<b>" . $condition['title'] . "</b><br />";
            }
            $tpvars_conditions_item['text'] = $condition['text'];
            $htmlconditions .= "<div style=\"\" >";
            $htmlconditions .= $condition['text'];
            $htmlconditions .= "</div>";
            $ck = "";
            if (!empty($_POST['conditions_' . $condition['id']]))
            {
                $tpvars_conditions_item['checked'] = 'checked';
                $ck = "checked=\"checked\"";
            }
            $required = !empty($condition['optional']) ? "" : "required=\"required\"";
            $tpvars_conditions_item['required'] = !empty($condition['optional']) ? "" : "required";
            $tpvars_conditions_item['name'] = "conditions_{$condition['id']}";
            $tpvars_conditions_item['title_accept'] = FN_Translate("accept");

            $htmlconditions .= "<label><input $required  name=\"conditions_{$condition['id']}\" type=\"checkbox\" $ck/> " . FN_Translate("accept") . "</label>";
            if (!empty($_POST) && empty($condition['optional']) && empty($_POST['conditions_' . $condition['id']]))
            {
                $htmlconditions .= " <span style=\"background-color:#ffffff;color:red\">" . FN_Translate("to register is required to accept") . "</span>";
                $tpvars_conditions_item['error'] = FN_Translate("to register is required to accept");
            }
            $htmlconditions .= "</div>";
            $tpvars_conditions[] = $tpvars_conditions_item;
        }
        $tplvars['htmlconditions'] = $htmlconditions;
        $tplvars['conditions'] = $tpvars_conditions;
    }
    else
    {
        $templateForm = str_replace($tp_conditions, "", $templateForm);
    }

    //----------------conditions------------<
    //dprint_r($errors);
    $templateForm = FN_TPL_ApplyTplString($templateForm, $tplvars, $tplbasepath);
    $templateForm = str_replace("{json}", json_encode(array("errors" => $errors, "fields" => $form->formvals), JSON_FORCE_OBJECT), $templateForm);
    $form->SetlayoutTemplate($templateForm);
    
    $form->ShowInsertForm(FN_IsAdmin(), $newvalues, $errors);
    //--------------------registration form----------------------------------------<

    $_FN['return']['errors'] = $errors;
    $_FN['return']['fields'] = $form->formvals;

    return false;
}

/**
 *
 * @global array $_FN
 * @return string
 */
function FNREG_GetConfirmationMessage()
{
    global $_FN;
    if (!file_exists("{$_FN['datadir']}/messages/ConfirmationMessage.{$_FN['lang']}.txt"))
    {
        return FN_i18n("to complete the registration click on the following link") . ":\n!CONFIRMREGISTRATIONADDRESS!";
    }
    else
    {
        return file_get_contents("{$_FN['datadir']}/messages/ConfirmationMessage.{$_FN['lang']}.txt");
    }
}

/**
 *
 * @global array $_FN
 * @return string
 */
function FNREG_GetWelcomeMessage()
{
    global $_FN;
    if (!file_exists("{$_FN['datadir']}/messages/WelcomeMessage.{$_FN['lang']}.txt"))
    {
        return FN_i18n("welcome !USERNAME!, you are now registered to") . ": !SITENAME!\n\n!SITEURL!";
    }
    else
    {
        return file_get_contents("{$_FN['datadir']}/messages/WelcomeMessage.{$_FN['lang']}.txt");
    }
}

//---------ux_conditions--------------------------------------->
/**
 *
 * @author alessandro
 *
 */
class xmetadbform_field_conditions_check
{

    function __construct()
    {
        
    }

    function show($params)
    {
        global $_FN;
        $this->starttagtitle = isset($params['frm_starttagtitle']) ? $params['frm_starttagtitle'] : "<tr><td  valign=\"top\" >";
        $this->endtagtitle = isset($params['frm_endtagtitle']) ? $params['frm_endtagtitle'] : "</td>";
        $this->starttagvalue = isset($params['frm_starttagvalue']) ? $params['frm_starttagvalue'] : "<td valign=\"top\" >";
        $this->endtagvalue = isset($params['frm_endtagvalue']) ? $params['frm_endtagvalue'] : "</td></tr>";
        $lang_user = $_FN['lang'];
        if (!file_exists($_FN['datadir'] . "/conditions"))
        {
            mkdir($_FN['datadir'] . "/conditions");
        }
        $file_conditions = $_FN['datadir'] . "/conditions/conditions.$lang_user.html";
        if (!file_exists($file_conditions))
        {
            if (file_exists("{$_FN['src_finis']}/modules/login/conditions/conditions.$lang_user.html"))
            {
                FN_Copy("{$_FN['src_finis']}/modules/login/conditions/conditions.$lang_user.html", $file_conditions);
            }
        }
        $toltips = "";
        $strhiddenfield = $params['strhiddenfield'];
        $oldval = $params['value'];
        echo $this->starttagtitle . $params['title'] . $this->endtagtitle;
        echo $this->starttagvalue;
        $ch = "";
        if ($oldval != "")
            $ch = "checked=\"checked\"";
        if ($oldval != "yes")
        {
            $ch = "";
        }
        echo "\n$strhiddenfield ";
        echo "<input type=\"hidden\" value=\"" . htmlspecialchars($oldval) . "\" name=\"__check__" . $params['name'] . "\"  />";
        echo "<div style=\"border:1px inset;overflow:auto;height:100px;width:100%;\" >";
        echo file_get_contents($file_conditions);
        echo "</div>";
        echo "<input name=\"" . $params['name'] . "\" type=\"hidden\" value=\"no\" />";
        echo "<br />" . FN_Translate("I accept") . "&nbsp;<input style=\"vertical-align:middle\" $toltips $ch type=\"checkbox\" value=\"yes\" name=\"" . $params['name'] . "\"  />";
        echo $this->endtagvalue;
    }

}

/**
 * contitions field validator
 * @param $value
 * @return misc true or error message
 */
function fn_accept_condition_validator($value)
{
    if ($value != "yes")
    {
        return FN_Translate("you must accept the terms of use");
    }
    else
        return true;
}

//---------ux_conditions---------------------------------------<
?>