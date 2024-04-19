<?php

/**
 * @package Flatnux_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
$tplform_file = FNCC_FromTheme("controlcenter/sections/users_and_groups/profile/form.tp.html", false);
$tplpage_file = FNCC_FromTheme("controlcenter/sections/users_and_groups/profile/page.tp.html", false);
$opt = FN_GetParam("opt", $_GET);
FNCC_ManageEditRegister($_FN['user'], "controlcenter.php?opt=$opt", "controlcenter.php?opt=$opt", $tplform_file, $tplpage_file);

/**
 *
 * @global array $_FN
 * @param string $user
 */
function FNCC_ManageEditRegister($user = "", $formaction = "", $urlcancel = "", $tplform_file = "", $tplpage_file = "")
{
    global $_FN;
    $opt = FN_GetParam("opt", $_GET);
    if (!$formaction)
        $formaction = FN_RewriteLink("controlcenter.php?opt=$opt");
    if (!$urlcancel)
        $urlcancel = FN_RewriteLink("controlcenter.php");
    if ($user == "")
    {
        $user = $_FN['user'];
    }
    $errors = array();
    $form = FN_GetUserForm();
    $username_field = empty($form->fieldname_user) ? "username" : $form->fieldname_user;
    $active_field = empty($form->fieldname_active) ? "active" : $form->fieldname_active;
    $tplvars = $_FN;
    $tplvars['formaction'] = $formaction;
    $tplvars['urlcancel'] = $urlcancel;


    //---------------template page -------------------------------------------->

    $templatePage = "{htmlform}<br />{message}<br />{error}";


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
    $tplbasepath_page = "";
    $tplbasepath_form = "";

    if (file_exists($tplform_file))
    {
        $templateForm = file_get_contents($tplform_file);
        $tplbasepath_form = dirname($tplform_file) . "/";
    }
    if (file_exists($tplpage_file))
    {
        $templatePage = file_get_contents($tplpage_file);
        $tplbasepath_page = dirname($tplpage_file) . "/";
    }

    //---------------template page --------------------------------------------<


    if (isset($form->formvals[$form->fieldname_password]))
    {
        if (isset($_POST[$form->fieldname_password]) && $_POST[$form->fieldname_password] == "")
        {
            unset($_POST[$form->fieldname_password]);
        }
    }
    if ($active_field)
    {
        $form->formvals[$active_field]['frm_show'] = 0;
        $form->LoadFieldsClasses();
    }

    $form->SetLayout("table");
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
                } else
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
        } else
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
                } else
                {
                    $_FN['return']['errors'] = $errors;
                }
            }
    }
    if (!$reg_ok)
    {
        $us = "";
        if (FN_IsAdmin() && $user != $_FN['user'])
            $us = "&user=$user";
        $templateForm = FN_TPL_ApplyTplString($templateForm, $tplvars, $tplbasepath_form);
        $templateForm = str_replace("{json}", json_encode(array("errors" => $errors, "fields" => $form->formvals), JSON_FORCE_OBJECT), $templateForm);
        $form->SetlayoutTemplate($templateForm);
        array_merge($_FN['return'], $tplvars);
        $tplvars['htmlform'] = $form->HtmlShowUpdateForm($newvalues[$form->xmltable->primarykey], FN_IsAdmin(), false, $errors);
    } else
    {
        $tplvars['htmlform'] = "";
        if (false != FN_UpdateUser($newvalues[$username_field], $newvalues))
        {
            $tplvars['message'] = FN_Translate("the data were successfully updated");
            FN_Login($newvalues[$username_field]);
            array_merge($_FN['return'], $newvalues);
        } else
        {
            $tplvars['message'] = FN_Translate("error");
            $tplvars['error'] = FN_Translate("error");
            $_FN['return']['error'] = "error";
        }
    }
    $tplvars['message'] = "";
    $tplvars['error'] = "";
    echo FN_TPL_ApplyTplString($templatePage, $tplvars, $tplbasepath_page);

    if (isset($_FN['return']['uservalues'][$form->fieldname_password]))
    {
        unset($_FN['return']['uservalues'][$form->fieldname_password]);
    }
    if (isset($_FN['return']['uservalues']['password']))
    {
        unset($_FN['return']['uservalues']['password']);
    }
}

?>