<?php

if (!defined("FN_AUTH_COST"))
{
    define("FN_AUTH_COST", 10);
}
if (!defined("FN_AUTH_METHOD"))
{
    //PASSWORD_DEFAULT OR PASSWORD_BCRYPT
    if (defined('PASSWORD_DEFAULT'))
    {
        define("FN_AUTH_METHOD", PASSWORD_DEFAULT);
    }
}

function FN_LoginInitUrl($script="")
{
    global $_FN;
    $loginmod = "login";
    if (!$script)
    {
        $script = "index.php";
    }
    if (isset($_FN['mod']) && isset($_FN['sections']) && isset($_FN['sections'][$_FN['mod']]['type']) && $_FN['sections'][$_FN['mod']]['type'] == "login")
    {
        $loginmod = $_FN['mod'];
    }
    elseif (empty($_FN['sections']['login']) || $_FN['sections']['login']['type'] == "login")
    {
        if (isset($_FN['sections']))
        {
            foreach ($_FN['sections'] as $k => $v)
            {
                if ($v['type'] == "login")
                {
                    $loginmod = $k;
                }
            }
        }
    }
    $_FN['urlregister'] = FN_RewriteLink("$script?mod=$loginmod&amp;op=register", "&amp;", true);
    $_FN['urlprofile'] = FN_RewriteLink("$script?mod=$loginmod&amp;op=profile", "&amp;", true);
    $_FN['urleditprofile'] = FN_RewriteLink("$script?mod=$loginmod&amp;op=editreg", "&amp;", true);
    $_FN['urllogin'] = FN_RewriteLink("$script?mod=$loginmod", "&amp;", true);
    $_FN['urlloginform'] = FN_RewriteLink("$script?mod={$_FN['mod']}&fnlogin=login", "&amp;", true);
    $_FN['urllogout'] = FN_RewriteLink("$script?fnlogin=logout", "&amp;", true);
    $_FN['urlpasswordrecovery'] = FN_RewriteLink("$script?mod=$loginmod&op=recovery", "&amp;", true);
    $_FN['urlresendcode'] = false;
    if ($_FN['registration_by_email'])
        $_FN['urlresendcode'] = FN_RewriteLink("$script?mod=$loginmod&op=send_code", "&amp;", true);
}

/**
 * @package Finis_auth_local
 * @param array $arrayfilter
 * @return int
 */
function FN_CountUsers($arrayfilter = false)
{
    $Table = FN_GetUserForm();
    return $Table->xmltable->GetNumRecords($arrayfilter);
}

/**
 * 
 * @return type
 */
function FN_GetUserForm()
{
    static $form = null;
    if (!$form)
    {
        FN_LoginInitUrl();
        $form = FN_XMDBForm("fn_users" );
        $form->formvals['passwd']['frm_type'] = "cryptpasswd";
        $op = FN_GetParam("op", $_GET, "html");
        $pk___xdb_fn_users = FN_GetParam("pk___xdb_fn_users", $_GET, "html");
        if ($op == "editreg" || $pk___xdb_fn_users != "")
            $form->formvals['passwd']['frm_required'] = false;
        $form->LoadFieldsClasses();
        $form->fieldname_active = "active";
        $form->fieldname_user = "username";
        $form->fieldname_password = "passwd";

    }
    return $form;
}

/**
 *
 * @param array $arrayfilter
 * @return array
 */
function FN_GetUsers($arrayfilter = false)
{
    $Table = FN_GetUserForm();
    return $Table->xmltable->GetRecords($arrayfilter);
}

/**
 *
 */
function FN_ManageLogin()
{
    global $_FN;
    FN_LoginInitUrl();
    $fnlogin = FN_GetParam("fnlogin", $_GET);
    $fnuser = FN_GetParam("username", $_POST);
    $fnpwd = FN_GetParam("password", $_POST);
    $captcha_ok = true;
    FN_ManageLoginWithOpenAuthentication();
    //-------------------captcha----------------------------------------------->
    if (!empty($_FN['enable_captcha']))
    {
        $captcha = FN_GetSessionValue("captcha");
        $fnlogincode = FN_GetParam("fnlogin_code", $_POST);
        if ($fnlogincode == "" || empty($captcha['fnlogin_code']) || $captcha['fnlogin_code'] != $fnlogincode)
        {
            $captcha_ok = false;
            FN_SetSessionValue("captcha", array("fnlogin_code" => rand(1000, 9999)));
        }
    }
    //-------------------captcha-----------------------------------------------<
    $rememberlogin = FN_GetParam("rememberlogin", $_POST);
    if ($fnlogin == "login" && $fnuser != "" && $fnpwd != "")
    {
        if (empty($_FN['username_case_sensitive']))
            $fnuser = strtolower($fnuser);
        if ($captcha_ok && FN_VerifyUserPassword($fnuser, $fnpwd))
        {
            FN_Login($fnuser, $rememberlogin);
        }
        else
        {
            FN_Logout();
            $_FN['login_error'] = FN_Translate("error username or password");
        }
    }
    $_FN['user'] = FN_GetParam("fnuser", $_COOKIE);
    if (!FN_CheckUser() || $fnlogin == "logout")
    {
        FN_Logout();
    }
}

/**
 * 
 */
function FN_LoginForm($templateForm = false)
{
    echo FN_HtmlLoginForm($templateForm);
}

/**
 *
 * @global array $_FN
 */
function FN_HtmlLoginForm($templateForm = false, $url = "")
{
    global $_FN;
    FN_LoginInitUrl();

    if ($templateForm)
    {
        if (file_exists($templateForm))
        {
            $templateForm = file_get_contents($templateForm);
            $tplbasepath = dirname($templateForm);
        }
        else
        {
            $tplbasepath = "themes/{$_FN['theme']}";
        }
    }
    if (!$templateForm)
    {
        if (file_exists(FN_PathSite("themes/{$_FN['theme']}/template.{$_FN['mod']}.tp.html")))
        {
            $templateForm = FN_TPL_GetHtmlPart("include FN_HtmlLoginForm", file_get_contents(FN_PathSite("themes/{$_FN['theme']}/template.{$_FN['mod']}.tp.html")));
            $tplbasepath = "{$_FN['src_application']}/themes/{$_FN['theme']}";
        }
    }
    if (!empty($_FN['sectionvalues']['type']) && file_exists("themes/{$_FN['theme']}/template.type.{$_FN['sectionvalues']['type']}.tp.html"))
    {
        $templateForm = FN_TPL_GetHtmlPart("include FN_HtmlLoginForm", file_get_contents("themes/{$_FN['theme']}/template.type.{$_FN['sectionvalues']['type']}.tp.html"));
        $tplbasepath = "{$_FN['src_application']}/themes/{$_FN['theme']}";
    }
    if (!$templateForm)
    {
        if (file_exists("themes/{$_FN['theme']}/template.tp.html"))
        {
            $templateForm = FN_TPL_GetHtmlPart("include FN_HtmlLoginForm", file_get_contents("themes/{$_FN['theme']}/template.tp.html"));
            $tplbasepath = "{$_FN['src_application']}/themes/{$_FN['theme']}";
        }
    }
    if (!$templateForm)
    {
        $templateForm = file_get_contents(FN_FromTheme("{$_FN['src_finis']}/modules/login/login.tp.html", false));
        $tplbasepath = dirname(FN_FromTheme("{$_FN['src_finis']}/modules/login/login.tp.html", false));
    }
    $querystring = FN_GetParam("QUERY_STRING", $_SERVER);
    $querystring = str_replace("&", "&amp;", $querystring);
    $querystring = str_replace("fnlogin=login&amp;", "", $querystring);
    $querystring = str_replace("fnlogin=logout&amp;", "", $querystring);
    $querystring = str_replace("?fnlogin=login", "?", $querystring);
    $querystring = str_replace("?fnlogin=logout", "?", $querystring);
    $querystring = str_replace("&amp;fnlogin=login", "", $querystring);
    $querystring = str_replace("&amp;fnlogin=logout", "", $querystring);
    $querystring .= "&amp;fnlogin=login";
    $scriptname = (basename($_SERVER['PHP_SELF']));
    if ($scriptname == "index.php")
    {
        $querystring = FN_RewriteLink("index.php?" . $querystring, "&amp;", true);
    }
    else
    {
        $querystring = "?" . $querystring;
    }
    if ($url)
    {
        $querystring = $url;
    }
    $tplvars = array();
    $tplvars['login_error'] = "";
    $tplvars['formaction'] = $querystring;
    $tplvars['txtusername'] = (!empty($_FN['username_is_email'])) ? FN_Translate("email") : FN_Translate("username");

    //------------------------------------ captcha ---------------------------->
    $captcha_ok = true;
    $htmlcaptcha = "";
    if (!empty($_FN['enable_captcha']))
    {
        $captcha = FN_GetSessionValue("captcha");
        $fnlogincode = FN_GetParam("fnlogin_code", $_POST);
        if ($fnlogincode == "" || empty($captcha['fnlogin_code']) || $captcha['fnlogin_code'] != $fnlogincode)
            $captcha_ok = false;
        FN_SetSessionValue("captcha", array("fnlogin_code" => rand(1000, 9999)));
        
        $htmlcaptcha .= "<img style=\"vertical-align:middle\" src=\"{$_FN['siteurl']}?fnapp=captcha&amp;t=fnlogin_code&amp;" . time() . "\" alt=\"\" title=\"\" /> <input size=\"4\" name=\"fnlogin_code\"  value = \"\" />";
        $tplvars['htmlcaptcha'] = $htmlcaptcha;
    }
    else
    {
        $templateForm = FN_TPL_ReplaceHtmlPart("captcha", "", $templateForm);
    }
    //------------------------------------ captcha ----------------------------<
    if (empty($_FN['remember_login']))
    {
        $templateForm = FN_TPL_ReplaceHtmlPart("rememberlogin", "", $templateForm);
    }
    if (!empty($_FN['login_error']))
    {
        $tplvars['login_error'] = FN_Translate("authentication failure");
    }
    else
    {
        $templateForm = FN_TPL_ReplaceHtmlPart("loginerror", "", $templateForm);
    }
    if (empty($_FN['enable_registration']))
    {
        $templateForm = FN_TPL_ReplaceHtmlPart("register", "", $templateForm);
    }
    $tplvars['oauth_providers'] = FN_GetOpenAuthProviders();
    
    $html = FN_TPL_ApplyTplString($templateForm, $tplvars, $tplbasepath);

    return $html;
}

/**
 *
 * @global array $_FN
 */
function FN_HtmlLogoutForm($templateForm = false)
{
    global $_FN;
    if ($templateForm)
    {
        $tplbasepath = "{$_FN['src_application']}/themes/{$_FN['theme']}";
    }
    elseif (file_exists(FN_PathSite("themes/{$_FN['theme']}/template.tp.html")))
    {
        $templateForm = FN_TPL_GetHtmlPart("include FN_HtmlLogoutForm", file_get_contents("{$_FN['src_application']}/themes/{$_FN['theme']}/template.tp.html"));
        $tplbasepath = "{$_FN['src_application']}/themes/{$_FN['theme']}";
    }
    if (!$templateForm)
    {
        $templateForm = file_get_contents(FN_FromTheme("{$_FN['src_finis']}/modules/login/logout.tp.html", false));
        $tplbasepath = dirname(FN_FromTheme("{$_FN['src_finis']}/modules/login/logout.tp.html", false));
    }
    $tplvars = array();
    $querystring = FN_GetParam("QUERY_STRING", $_SERVER);
    $querystring = str_replace("&", "&amp;", $querystring);
    $tplvars['formaction'] = "?$querystring&amp;fnlogin=logout";
    $html = FN_TPL_ApplyTplString($templateForm, $tplvars, $tplbasepath);
    return $html;
}

/**
 *
 * @global array $_FN
 */
function FN_LogoutForm($templateForm = false)
{
    echo FN_HtmlLogoutForm($templateForm);
}

/**
 *
 * @global  $_FN
 * @param string $fnuser
 * @param string $fnpwd
 * @return bool
 */
function FN_VerifyUserPassword($fnuser, $fnpwd)
{
    global $_FN;
    if (!empty($_FN['FN_VerifyUserPassword']) && $_FN['FN_VerifyUserPassword'] != "FN_VerifyUserPassword" && function_exists($_FN['FN_VerifyUserPassword']))
    {
        return $_FN['FN_VerifyUserPassword']($fnuser, $fnpwd);
    }
    $lpass = md5($fnpwd);
    $us = FN_GetUser($fnuser);
    $passwd = !empty($us['passwd']) ? $us['passwd'] : "";
    if ($passwd == $lpass)
        return true;
    if ($passwd == $fnpwd)
        return true;

    if (function_exists("password_hash") && function_exists("password_verify"))
    {

        if (password_verify($fnpwd, $passwd))
        {
            return true;
        }
    }
    else
    {
        die("functions password_hash password_verify dont exists ");
    }
    return false;
}

/**
 *
 * @global  $_FN
 * @staticvar array $usercache
 * @param string $user
 * @param bool $usecache
 * @return array
 */
function FN_GetUser($user, $usecache = array())
{
    if ($user == "")
        return null;
    static $usercache = array();
    global $_FN;
    if ($usecache)
    {
        if ($usercache && is_array($usercache) && isset($usercache[$user]))
            return $usercache[$user];
    }
    $table = FN_GetUserForm();
    $UserValues = $table->xmltable->GetRecordByPrimaryKey($user);
    if (is_array($UserValues) && $UserValues['level'] === "")
        $UserValues['level'] = "0";
    if (empty($UserValues['username']))
        return null;
    $_FN['uservalues'] = $usercache[$user] = $UserValues;
    return $usercache[$user];
}

/**
 *
 * @global  $_FN
 * @staticvar array $usercache
 * @param string $user
 * @param bool $usecache
 * @return int
 */
function FN_GetUserLevel($user, $usecache = true)
{
    $uservalues = FN_GetUser($user, $usecache);
    if (!isset($uservalues['level']))
        return -1;
    return $uservalues['level'];
}

/**
 *
 * @param string $user
 * @return string
 */
function FN_GetPassword($user)
{
    $uservalues = FN_GetUser($user, false);
    if (!isset($uservalues['passwd']))
        return null;
    $pass = $uservalues['passwd'];
    return $pass;
}

/**
 * 
 * @global type $_FN
 * @global type $_FN
 * @param type $fnuser
 * @param type $rememberlogin
 * @return boolean
 */
function FN_Login($fnuser, $rememberlogin = false)
{
    global $_FN;
    $password = Fn_GetPassword($fnuser);
    $us = FN_GetUser($fnuser, false);
    if ($us['active'] == 1)
    {
//---------------------url cookie---------------------------------------------->
        global $_FN;
        if (empty($_FN['urlcookie']))
        {
            $urlcookie = FN_GetParam("PHP_SELF", $_SERVER);
            $path = pathinfo($urlcookie);
            $urlcookie = $path["dirname"] . "/";
            $urlcookie = str_replace("\\", "/", $urlcookie);
            if ($urlcookie == "" || $urlcookie == "\\" || $urlcookie == "//")
                $urlcookie = "/";
            $_FN['urlcookie'] = $urlcookie;
        }
//---------------------url cookie----------------------------------------------<
        if (empty($_FN['remember_login']) || $rememberlogin == "")
        {
            setcookie("fnuser", $fnuser, 0, $_FN['urlcookie']);
            setcookie("secid", md5($fnuser . $password), 0, $_FN['urlcookie']);
        }
        else
        {
            setcookie("fnuser", $fnuser, time() + 99999999, $_FN['urlcookie']);
            setcookie("secid", md5($fnuser . $password), time() + 99999999, $_FN['urlcookie']);
        }
        FN_Log("User $fnuser login (function FN_Login).");
        $_FN['user'] = $_COOKIE['fnuser'] = $fnuser;
        $_COOKIE['secid'] = md5($fnuser . $password);

        return true;
    }
    else
    {
        $_FN['login_error'] = "user is not active";
        return false;
    }
}

/**
 *
 * @global  $_FN
 * @param string $fnuser
 */
function FN_Logout()
{
//---------------------url cookie---------------------------------------------->
    global $_FN;
    $forcelogout = true;
    if ($forcelogout)
    {
        $urlcookie = FN_GetParam("PHP_SELF", $_SERVER);
        $path = pathinfo($urlcookie);
        $urlcookie = $path["dirname"] . "/";
        $urlcookie = str_replace("\\", "/", $urlcookie);
        if ($urlcookie == "" || $urlcookie == "\\" || $urlcookie == "//")
            $urlcookie = "/";
        setcookie("secid", "", 0, $urlcookie);
        setcookie("fnuser", "", 0, $urlcookie);
        setcookie("secid", "", 0, "/");
        setcookie("fnuser", "", 0, "/");
    }
//---------------------url cookie----------------------------------------------<

    setcookie("secid", "", 0, $_FN['urlcookie']);
    setcookie("fnuser", "", 0, $_FN['urlcookie']);
    setcookie("secid", "", 0, "/");
    setcookie("fnuser", "", 0, "/");
    $_FN['user'] = "";
    unset($_COOKIE['fnuser']);
    unset($_COOKIE['secid']);
}

/**
 * deleta user
 * @param string $user
 */
function FN_DeleteUser($user)
{
    global $_FN;
    if ($user != "")
    {
        $table = FN_GetUserForm();
        $uservalues = FN_GetUser($user, false);
        $table->xmltable->DelRecord($user);
        if (function_exists("FN_OnDeleteUser"))
        {
            FN_OnDeleteUser($uservalues);
        }
        if (file_exists("{$_FN['datadir']}/fndatabase/fn_users/" . $user))
        {
            remove_dir_rec("{$_FN['datadir']}/fndatabase/fn_users/" . $user);
        }
    }
}

/**
 * @param string $user
 * @param array $newvalues
 */
function FN_UpdateUser($user, $newvalues, $password = "")
{

    if ($user != "")
    {
        if (!isset($newvalues['username']))
            $newvalues['username'] = $user;
        if ($password != "")
            $newvalues['passwd'] = $password;
        if (isset($newvalues['passwd']) && $newvalues['passwd'] == "")
        {
            unset($newvalues['passwd']);
        }
        $table = FN_GetUserForm();
        //dprint_r($newvalues);
        $newvalues = $table->UpdateRecord($newvalues);
        if ($newvalues && function_exists("FN_OnUpdateUser"))
        {
            FN_OnUpdateUser($newvalues, $password);
        }
        FN_GetUser($user, false);
        return $newvalues;
    }
    return false;
}

/**
 *
 * @param array $newvalues
 * @return bool
 */
function FN_AddUser($newvalues, $password = "")
{
    if ($newvalues['username'] != "")
    {
        if (empty($_FN['username_case_sensitive']))
        {
            $newvalues['username'] = strtolower($newvalues['username']);
        }
        $table = FN_GetUserForm();
        $newvalues['registrationdate'] = FN_Now();
        if ($password != "")
            $newvalues['passwd'] = $password;
        if (isset($newvalues['passwd']) && $newvalues['passwd'] == "")
        {
            unset($newvalues['passwd']);
        }
        if ($table->InsertRecord($newvalues))
        {
            if (function_exists("FN_OnAddUser"))
            {
                FN_OnAddUser($newvalues, $password);
            }
            return true;
        }
    }
    return false;
}

/**
 *
 * @global array $_FN
 * @return bool 
 */
function FN_CheckUser()
{
    global $_FN;
    $secid = FN_GetParam("secid", $_COOKIE);
    $UserValues = FN_GetUser($_FN['user']);
    if (empty($secid) || empty($UserValues['passwd']))
        return false;
    $RequiredSecid = md5($_FN['user'] . $UserValues['passwd']);
    if ($RequiredSecid == $secid)
        return true;
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $user
 * @return string 
 */
function FN_GetUserImage($user)
{
    global $_FN;
    $user = FN_GetUser($user);
    if (!empty($user['avatar']))
    {
        $image = urldecode(FN_GetParam("avatar", $user, "html"));
        $image = "{$_FN['datadir']}/{$_FN['database']}/fn_users/{$user['username']}/avatar/$image";
    }
    elseif (!empty($user['avatarimage']))
    {
        $image = urldecode($user['avatarimage']);
        $image = "{$_FN['datadir']}/{$_FN['database']}/fn_avatars/$image/filename/$image";
    }
    else
    {
        if (!empty($_FN['userimage']))
        {
            return $_FN['userimage'];
        }
        $image = FN_FromTheme("images/user.png", false);
    }
    return $_FN['siteurl'] . $image;
}

/**
 *
 * @global array $_FN
 * @param string $user
 * @return string 
 */
function FN_HtmlUserImage($user)
{
    global $_FN;
    $imagesrc = FN_GetUserImage($user);
    $image = "<img alt=\"\" src=\"$imagesrc\" />";
    return $image;
}

/**
 * 
 * @param type $pasword
 */
function FN_PasswordVerifyConstraints($password)
{

    if (function_exists("FN_PasswordVerifyConstraints_overwrite"))
    {

        return FN_PasswordVerifyConstraints_overwrite($password);
    }
//    if (!preg_match('/^[0-9A-Za-z!@#$%]{3,12}$/',$password))
    if (false !== strstr($password, " "))
    {
        return 'the password does not meet the requirements';
    }
    return "";
}

function FN_ManageLoginWithOpenAuthentication()
{

    global $_FN;
    $session_provider = FN_GetSessionValue("session_provider");
    $session_access_token = FN_GetSessionValue("session_access_token");
    $_FN['userimage'] = FN_GetSessionValue("session_userimage");
    // Provider configurations
    $providers = FN_GetOpenAuthProviders();
    if (!$providers)
    {
        return;
    }
    $op = FN_GetParam("fnlogin", $_GET);
    if ($op == "logout")
    {
        FN_SetSessionValue("session_access_token", "");
        FN_SetSessionValue("session_provider", "");
        FN_SetSessionValue("session_userimage", "");
        $providerId = "";
        $session_provider = "";
        $session_access_token = "";
        FN_Logout();
        return;
    }
    $providerId = FN_GetParam("fnloginprovider", $_GET);
    //$redirectUri = $_FN['siteurl'] . "?fnloginprovider=$providerId";
    $redirectUri = $_FN['siteurl'] . "{$_FN['selfscript']}?fnloginprovider=$providerId";
//dprint_r($redirectUri);
    $found_provider = false;
    foreach ($providers as $provider)
    {
        if ($providerId == $provider['id'])
        {
            $found_provider = $provider;
            break;
        }
    }
    if (!$found_provider)
    {
        return;
    }
    $config = $found_provider;

    // Check if the user is already authenticated
    if (empty($session_access_token))
    {
        // If not authenticated, start the OAuth flow
        if (!isset($_GET['code']))
        {
            // Redirect to provider's authorization page
            $params = array(
                'client_id' => $config['client_id'],
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => $config['scope'],
            );

            $authRedirect = $config['auth_url'] . '?' . http_build_query($params);
            header('Location: ' . $authRedirect);
            exit;
        }
        else
        {
            // Exchange the authorization code for an access token
            $postData = array(
                'code' => $_GET['code'],
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            );

            $ch = curl_init($config['token_url']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            curl_close($ch);
            $tokenData = json_decode($response, true);
            if (isset($tokenData['access_token']))
            {
                $session_access_token = $tokenData['access_token'];
                $session_provider = $provider['id'];
                FN_SetSessionValue("session_access_token", $session_access_token);
                FN_SetSessionValue("session_provider", $session_provider);
            }
            else
            {
                return;
            }
        }
    }

// Get user information using the access token
    $ch = curl_init($config['userinfo_url']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $session_access_token));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $userInfo = json_decode($response, true);
    FN_SetSessionValue("session_provider", $session_provider);
    $user_email = isset($userInfo['email']) ? $userInfo['email'] : "";
    $user_image = !empty($userInfo['picture']) ? $userInfo['picture'] : "";
    if (!$user_image || is_array($user_image))
    {
        $user_image = !empty($userInfo['picture']['data']['url']) ? $userInfo['picture']['data']['url'] : "";
    }
    if ($user_email)
    {
        $users = FN_GetUsers(array("email" => $user_email));
        if ($users)
        {
            $uservalues = $users[0];
            FN_Login($uservalues['username']);
        }
        else
        {
            if ($_FN['enable_registration'])
            {
                $uservalues = array("username" => $user_email, "email" => $user_email, "active" => 1);
                $res = FN_AddUser($uservalues, generateSecurePassword());
                FN_Login($uservalues['username']);
            }
        }
        $_FN['userimage'] = $user_image;
        FN_SetSessionValue("session_userimage", $user_image);
        return;
    }
    else
    {
        FN_Logout();
    }
}

function generateSecurePassword($length = 12)
{
    // Define the character sets to use in the password
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $specialChars = '!@#$%^&*()-_=+<>?';

    // Combine all character sets
    $allChars = $lowercase . $uppercase . $numbers . $specialChars;

    // Shuffle the characters to ensure randomness
    $shuffledChars = str_shuffle($allChars);

    // Initialize the password variable
    $password = '';

    // Generate a password of the specified length
    for ($i = 0; $i < $length; $i++)
    {
        // Select a random character from the shuffled character set
        $randomIndex = rand(0, strlen($shuffledChars) - 1);
        $password .= $shuffledChars[$randomIndex];
    }

    return $password;
}
