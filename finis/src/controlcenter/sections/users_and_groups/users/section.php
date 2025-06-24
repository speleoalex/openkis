<?php

/**
 * @package Finis_controlcenter
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2005
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 *
 */
defined('_FNEXEC') or die('Restricted access');
global $_FN;
$opt = FN_GetParam("opt", $_GET, "html");



$form = FN_GetUserForm();
$form->fieldname_user = empty($form->fieldname_user) ? "username" : $form->fieldname_user;
$form->fieldname_active = empty($form->fieldname_active) ? "active" : $form->fieldname_active;
if (isset($form->formvals['username']))
{
    $form->formvals['username']['frm_allowupdate'] = "onlyadmin";
}
//--------------------enable disable user-------------------------------------->
$userid = FN_GetParam("userid", $_POST);
if ($userid)
{
    $active = FN_GetParam("{$form->fieldname_active}", $_POST);
    $sendwelcomemessage = FN_GetParam("sendwelcomemessage", $_POST);
    if ($_FN['user'] == $userid || (!FN_IsAdmin() && FN_IsAdmin($userid)))
    {
        FN_Alert(FN_Translate("operation is not permitted"));
    }
    else
    {
        require_once 'modules/login/functions_login.php';
        $uservalues = FN_UpdateUser($userid, array("{$form->fieldname_active}" => $active));
        if ($uservalues && $active && $sendwelcomemessage)
        {
            if (function_exists("FN_SendMailWelcome"))
            {
                FN_SendMailWelcome($uservalues);
            }
        }
    }
}
//--------------------enable disable user--------------------------------------<
//--------------------- process password -------------------------------------->

if (isset($form->formvals['passwd']))
{
    if (isset($_POST['passwd']) && $_POST['passwd'] == "")
    {
        unset($_POST['passwd']);
    }
}

if (isset($form->formvals['password']))
{
    if (isset($_POST['password']) && $_POST['password'] == "")
    {
        unset($_POST['password']);
    }
    $form->formvals['password']['frm_required'] = false;
}
//--------------------- process password --------------------------------------<
//------------------------ display user system fields ------------------------->
$form->formvals['level']['frm_show'] = "1";
$form->formvals[$form->fieldname_active]['frm_show'] = "1";
$form->formvals['group']['frm_show'] = "1";
$form->LoadFieldsClasses();
//------------------------ display user system fields -------------------------<
//----------------------- fields in grid -------------------------------------->
$params = array();
$params['fields'] = "username|email|level|group|EnableDisable()";
if (isset($form->formvals['registrationdate']))
{
    $form->formvals['registrationdate']['frm_show'] = "1";
    $params['fields'] = "username|email|level|group|registrationdate|EnableDisable()";
}
//----------------------- fields in grid --------------------------------------<
//-----------------------------define filters --------------------------------->
$fv = array();
$exlude = array("password", "passwd", "emailhidden", "avatarimage", "avatar", "rnd", "level", "groups", $form->fieldname_active, "ip", "registrationdate");
$fields_filters = array();
foreach ($form->formvals as $k => $v)
{
    if (!in_array($v['name'], $exlude) && $v['frm_type'] == "varchar")
    {
        $fields_filters[] = "%$k%";
    }
}
$params['fields_filters'] = $fields_filters;
//-----------------------------define filters ---------------------------------<

FN_PathSite("images/delete.png");

$params['list_onsave'] = false;
$params['textviewlist'] = "<img style=\"vertical-align:middle;border:0px;\" alt=\"\"  src=\"" . FN_FromTheme("images/users.png") . "\" />&nbsp;" . FN_Translate("user list");
$params['textnew'] = "<img style=\"vertical-align:middle;border:0px;\" alt=\"\"  src=\"" . FN_FromTheme("images/add.png") . "\" />&nbsp;" . FN_Translate("add a user");
$params['textmodify'] = "<img alt=\"" . FN_Translate("modify") . "\" title=\"" . FN_Translate("modify") . "\" style=\"vertical-align:middle;border:0px;\" alt=\"\"  src=\"" . FN_FromTheme("images/modify.png") . "\" />";
$params['textdelete'] = "<img alt=\"" . FN_Translate("delete") . "\" title=\"" . FN_Translate("delete") . "\" style=\"vertical-align:middle;border:0px;\" alt=\"\"  src=\"" . FN_FromTheme("images/delete.png") . "\" />";
$params['function_update'] = "FN_UpdateUserXmldbEditor";
$params['function_insert'] = "FN_InsertUserXmldbEditor";
$params['function_delete'] = "FN_DeleteUserXmldbEditor";



//------------------filter restr all/admin/enabled/disabled-------------------->
$params['restr'] = array();
$link = "fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt";
$get_restr = FN_GetParam("userfilter", $_GET);
if ($get_restr)
{
    $params['restr'] = array_merge($params['restr'], json_decode($get_restr, JSON_OBJECT_AS_ARRAY));
    $link = "fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;userfilter=$get_restr";
}
$params['link'] = "$link";
//------------------filter restr all/admin/enabled/disabled--------------------<
//----------------------------       tabs             ------------------------->
$filter_Enabled = json_encode(array("active" => "1"));
$filter_Disabled = json_encode(array("active" => "0"));
$filter_Administrators = json_encode(array("level" => "10"));
$efilter_Enabled = urlencode(json_encode(array("active" => "1")));
$efilter_Disabled = urlencode(json_encode(array("active" => "0")));
$efilter_Administrators = urlencode(json_encode(array("level" => "10")));
$tabs_restrictions = array();
$tabs_restrictions[] = array(
    "link" => "?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt",
    "active" => ($get_restr == "") ? true : "",
    "title" => FN_Translate("All")
);
$tabs_restrictions[] = array(
    "link" => "?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;userfilter=$efilter_Enabled",
    "active" => ($get_restr == "$filter_Enabled") ? true : "",
    "title" => FN_Translate("show only enabled")
);

$tabs_restrictions[] = array(
    "link" => "?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;userfilter=$efilter_Disabled",
    "active" => ($get_restr == "$filter_Disabled") ? true : "",
    "title" => FN_Translate("show only disabled")
);
$tabs_restrictions[] = array(
    "link" => "?fnapp=controlcenter&mod={$_FN['mod']}&amp;opt=$opt&amp;userfilter=$efilter_Administrators",
    "active" => ($get_restr == "$filter_Administrators") ? true : "",
    "title" => FN_Translate("show administrators")
);

if (file_exists("controlcenter/themes/{$_FN['controlcenter_theme']}/controlcenter/sections/users_and_groups/users/grid.tp.html"))
{
    $params['html_template_grid'] = "controlcenter/themes/{$_FN['controlcenter_theme']}/controlcenter/sections/users_and_groups/users/grid.tp.html";
}
if (empty($params['html_template_grid']))
{
    foreach ($tabs_restrictions as $tab)
    {
        echo "<a " . ($tab['active'] ? " style=\"font-weight:bold\" " : "") . "href=\"{$tab['link']}\"> {$tab['title']}</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
    }
}
//----------------------------       tabs             -------------------------<
$params['tabs_restrictions']=$tabs_restrictions;
FNCC_xmltableeditor($form, $params);

/**
 * 
 * @param type $user
 */
function FN_DeleteUserXmldbEditor($user)
{
    if (!FN_IsAdmin() && (FN_IsAdmin($user) || $_FN['user'] == $user))
    {
        echo FN_Translate("operation is not permitted");
    }
    else
    {
        FN_DeleteUser($user);
    }
}

/**
 * 
 * @param type $newvalues
 * @param type $pk
 * @return type
 */
function FN_UpdateUserXmldbEditor($newvalues, $pk)
{
    $password = "";
    if (isset($newvalues['passwd']))
        $password = $newvalues['passwd'];
    else
    if (isset($newvalues['password']))
        $password = $newvalues['password'];

    $form = FN_GetUserForm();
    $username = !empty($form->fieldname_user) ? $form->fieldname_user : "username";
    FN_UpdateUser($pk, $newvalues, $password);
    return FN_GetUser($newvalues[$username]);
}

/**
 * 
 * @global type $_FN
 * @param type $newvalues
 * @return type
 */
function FN_InsertUserXmldbEditor($newvalues)
{
    global $_FN;
    $password = "";
    if (isset($newvalues['passwd']))
        $password = $newvalues['passwd'];
    else
    if (isset($newvalues['password']))
        $password = $newvalues['password'];
    FN_AddUser($newvalues, $password);
    if (!empty($_FN['error_FN_AddUser']))
    {
        echo($_FN['error_FN_AddUser']);
    }
    $form = FN_GetUserForm();
    $username = !empty($form->fieldname_user) ? $form->fieldname_user : "username";
    return FN_GetUser($newvalues[$username]);
}

/**
 * 
 * @param type $user
 * @param type $Table
 * @return type
 */
function EnableDisable($primarykey, $TableUsers)
{
    global $_FN;
    $get_restr = FN_GetParam("userfilter", $_GET);
    $TableUsers = FN_GetUserForm();
    if (empty($TableUsers->fieldname_active) && !empty($TableUsers->formvals['active']))
    {
        $TableUsers->fieldname_active = "active";
    }
    $values = $TableUsers->xmltable->GetRecordByPrimaryKey($primarykey);
    $opt = FN_GetParam("opt", $_GET);
    $filter = FN_GetParam("filter__xdb_fn_users", $_GET);
    if ($filter)
    {
        $filter = "&amp;filter__xdb_fn_users=" . urlencode($filter);
    }
    if ($get_restr)
    {
        $get_restr = "&amp;userfilter=" . urlencode($get_restr);
    }
    $gopt = "";
    if ($opt != "")
    {
        $gopt = "&amp;opt=$opt";
    }
    $page = FN_GetParam("page___xdb_{$TableUsers->tablename}", $_GET, "int");
    if ($page != "")
    {
        $page = "&amp;page___xdb_{$TableUsers->tablename}=$page";
    }
    $htmlsendmail = "";
    if (function_exists("FN_SendMailWelcome"))
        $htmlsendmail = "<input type=\"checkbox\" name=\"sendwelcomemessage\" value=\"1\"/>" . FN_Translate("send welcome message");
    if ($values["{$TableUsers->fieldname_active}"] != 1)
        return "<form action=\"" . ("?fnapp=controlcenter&mod={$_FN['mod']}&amp;filter__xdb_fn_users=$filter$page$gopt$get_restr") . "\" method=\"post\">&#x1F534;<input name=\"{$TableUsers->fieldname_active}\" value=\"1\" type=\"hidden\"/><input name=\"userid\" value=\"$primarykey\" type=\"hidden\"/><button type=\"submit\">" . FN_Translate("enable") . "</button>$htmlsendmail</form>";
    else
        return "<form action=\"" . ("?fnapp=controlcenter&mod={$_FN['mod']}&amp;filter__xdb_fn_users=$filter$page$gopt$get_restr") . "\" method=\"post\">&#128309;<input name=\"{$TableUsers->fieldname_active}\" value=\"0\" type=\"hidden\"/><input name=\"userid\" value=\"$primarykey\" type=\"hidden\"/><button type=\"submit\">" . FN_Translate("disable") . "</button></form>";
}

?>