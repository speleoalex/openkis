<?php

/**
 * 
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */

/**
 *
 * @global array $_FN
 * @return array
 */
function FN_GetGroups()
{
    global $_FN;
    $groups = array();
    $table = FN_XMDBTable("fn_groups");
    $grouplist = $table->GetRecords();
    foreach ($grouplist as $g)
        $groups[] = $g['groupname'];
    return $groups;
}

/**
 * 
 * @global array $_FN 
 */
function FN_IsAdmin($user = false)
{
    global $_FN;
    if ($user === false)
        $user = $_FN['user'];
    $uservalues = FN_GetUser($user);
    if (!empty($uservalues['level']) && $uservalues['level'] >= 10)
        return true;
    return false;
}

/**
 *
 * @return bool
 */
function FN_UserCanEditFolder($folder)
{
    global $_FN;
    return FN_CanModify($_FN['user'], $folder);
}

/**
 *
 * @return bool
 */
function FN_UserCanEditFile($filename)
{
    global $_FN;
    return FN_CanModifyFile($_FN['user'], $filename);
}

/**
 *
 * @param string $user
 * @param string $fileorfolder
 * @return bool
 */
function FN_CanModify($user, $fileorfolder)
{
    global $_FN;
    if ($fileorfolder == "")
        $fileorfolder = ".";
    //dprint_r($fileorfolder);
    if (!@realpath($fileorfolder) || !file_exists(realpath($fileorfolder)))
        return false;
    if (is_dir($fileorfolder))
        return FN_CanModifyFolder($user, $fileorfolder);
    else
        return FN_CanModifyFile($user, $fileorfolder);
}

/**
 *
 * @param string $user
 * @param string $fileorfolder
 * @return bool
 */
function FN_CanView($user, $fileorfolder)
{
    global $_FN;
    if ($fileorfolder == "")
        $fileorfolder = ".";
    if (!@realpath($fileorfolder) || !file_exists(realpath($fileorfolder)))
        return false;
    if (is_dir($fileorfolder))
        return FN_CanViewFolder($user, $fileorfolder);
    else
        return FN_CanViewFile($user, $fileorfolder);
}

/**
 *
 * @param string $user
 * @param string $file
 * @return bool
 */
function FN_CanViewFile($user, $file)
{
    if (FN_CanModifyFile($user, $file)) {
        return true;
    }
    if (FN_CanViewFolder($user, dirname($file))) {
        if (FN_GetFileExtension($file) != "php")
            return true;
    }
    return false;
}

/**
 *
 * @param string $user
 * @param string $folder
 * @return bool
 */
function FN_CanViewFolder($user, $folder)
{
    global $_FN;
    if (FN_CanModifyFolder($user, $folder)) {
        return true;
    }
    if ($user != "") {
        $folder = FN_RelativePath($folder);
        $tmp = explode("/", $folder);
        if ($tmp[0] == $_FN['datadir'] && isset($tmp[2]) && $tmp[1] == "media" && FN_UsersInGroup($tmp[2])) {
            return true;
        }
    }
    return false;
}

/**
 *
 * @param string $user
 * @param string $folder
 * @return bool
 */
function FN_CanModifyFile($user, $file)
{
    if (FN_IsAdmin($user))
        return true;
    if (FN_CanModify($user, dirname($file))) {
        if (FN_GetFileExtension($file) == "html")
            return true;
    }
    if (FN_CanModifyFolder($user, dirname($file))) {
        if (FN_GetFileExtension($file) != "php") {
            return true;
        }
    }
    return false;
}

/**
 *
 * @param string $user
 * @param string $folder
 * @return bool
 */
function FN_CanModifyFolder($user, $folder)
{
    global $_FN;
    if (FN_IsAdmin($user))
        return true;
    $folder = FN_RelativePath($folder);
    $sl = str_replace('\\', '\\\\', $_FN['slash']);

    if ($user != "" && FN_erg("^sections$sl", $folder)) {
        $sectionid = basename($folder);
        return FN_UserCanEditSection($sectionid, $user);
    } elseif ($user != "" && FN_erg("^blocks$sl", $folder)) {
        $sectionid = basename($folder);
        return FN_UserCanEditBlock($sectionid, $user);
    }
    $folder = FN_RelativePath($folder);
    $tmp = explode("/", $folder);
    if ($tmp[0] == $_FN['datadir'] && isset($tmp[2]) && $tmp[1] == "media" && FN_UsersInGroup($tmp[2])) {
        return true;
    }

    return false;
}

/**
 *
 * @global array $_FN
 * @param string $section
 * @param string $user
 * @return bool
 */
function FN_UserCanEditSection($section = "", $user = "")
{
    global $_FN;
    if (FN_IsAdmin())
        return true;
    if ($section == "")
        $section = $_FN['mod'];
    if ($user == "")
        $user = $_FN['user'];
    if ($user == "")
        return false;
    $section = FN_GetSectionValues($section);
    if ($section['group_edit'] == "")
        return false;
    $groups = explode(",", $section['group_edit']);
    foreach ($groups as $group) {
        if (FN_UserInGroup($user, $group))
            return true;
    }
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $section
 * @param string $user
 * @return bool
 */
function FN_UserCanEditBlock($block = "", $user = "")
{
    global $_FN;
    if (FN_IsAdmin())
        return true;
    if ($block == "")
        $block = $_FN['block'];
    if ($user == "")
        $user = $_FN['user'];
    if ($user == "")
        return false;
    $block = FN_GetBlockValues($block);
    if ($block['group_edit'] == "")
        return false;
    $groups = explode(",", $block['group_edit']);
    foreach ($groups as $group) {
        if (FN_UserInGroup($user, $group))
            return true;
    }
    return false;
}

/**
 *
 * @global array $_FN
 * @param string $section
 * @param string $user
 */
function FN_UserCanViewSection($section = "", $user = "")
{
    global $_FN;
    if ($section == "")
        $section = $_FN['mod'];
    if ($user == "")
        $user = $_FN['user'];
    $section = FN_GetSectionValues($section);
    if (!$section) {
        return false;
    }
    if (FN_IsAdmin($user))
        return true;
    $uservalues = FN_GetUser($user);
    if (!isset($uservalues['level']))
        $uservalues['level'] = -1;
    if ($section['level'] !== "" && $section['level'] >= 0 && $section['level'] > $uservalues['level'])
        return false;
    if ($section['group_view'] == "")
        return true;
    $groups = explode(",", $section['group_view']);
    foreach ($groups as $group) {
        if (FN_UserInGroup($user, $group))
            return true;
    }
    return false;
}

/**
 *
 * @global array $_FN
 * @param array $block
 * @param string $user
 */
function FN_UserCanViewBlock($block, $user = "")
{
    global $_FN;
    if ($user == "")
        $user = $_FN['user'];


    if (FN_IsAdmin($user))
        return true;

    $uservalues = FN_GetUser($user);
    $block = FN_GetBlockValues($block);
    if (!isset($uservalues['level']))
        $uservalues['level'] = -1;
    if ($block['level'] > 0 && $block['level'] > $uservalues['level'])
        return false;
    if ($block['group_view'] == "")
        return true;
    $groups = explode(",", $block['group_view']);
    foreach ($groups as $group) {
        if (FN_UserInGroup($user, $group))
            return true;
    }
    return false;
}

/**
 *
 * @param string $user
 * @param string $group
 * @return array 
 */
function FN_AddUserInGroup($user, $group)
{
    $uservalues = FN_GetUser($user);
    unset($uservalues['passwd']);
    $groups = explode(",", $uservalues["group"]);
    if (!in_array($group, $groups)) {
        $uservalues["group"] = $uservalues["group"] . "," . $group;
        if (FN_UpdateUser($user, $uservalues)) {
            return $uservalues;
        }
    } else {
        return true;
    }
    return false;
}

/**
 *
 * @param string $group
 * @return array 
 */
function FN_UsersInGroup($group)
{
    global $_FN;
    $users = FN_GetUsers();
    $usersingroup = false;
    foreach ($users as $user) {
        if (FN_UserInGroup($user["username"], $group)) {
            $usersingroup[] = $user["username"];
        }
    }
    return $usersingroup;
}
