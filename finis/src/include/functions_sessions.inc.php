<?php

/**
 *
 * @global array $_FN
 * @param string $varname
 * @return variant 
 */
function FN_GetSessionValue($varname)
{
    global $_FN;
    //---------------get sid--------------------------------------------------->
    $_FN['fnsid'] = FN_GetParam("fnsid", $_REQUEST, "html");
    if (empty($_FN['fnsid']))
        $_FN['fnsid'] = FN_GetParam("fnsid", $_COOKIE, "html");
    if (empty($_FN['fnsid']))
    {
        $_FN['fnsid'] = uniqid("_") . uniqid("x");
        setcookie("fnsid", $_FN['fnsid'], time() + 999999999, $_FN ['urlcookie']);
        $_COOKIE["fnsid"] = $_FN['fnsid'];
    }
    $_FN['return']['fnsid'] = $_FN['fnsid'];

    //---------------get sid---------------------------------------------------<
    if (empty($_FN['fnsid']))
    {
        return null;
    }
    if (file_exists("{$_FN['datadir']}/_sessions/{$_FN['fnsid']}.session"))
    {
        $var = unserialize(file_get_contents("{$_FN['datadir']}/_sessions/{$_FN['fnsid']}.session"));
        if (isset($var[$varname]))
            return $var[$varname];
    }
    return null;
}

/**
 *
 * @global type $_FN
 * @param string $key
 * @param variant $value 
 */
function FN_SetSessionValue($key, $value)
{
    global $_FN;
    FN_ClearOldSessions();
    //---------------get sid--------------------------------------------------->
    $_FN['fnsid'] = FN_GetParam("fnsid", $_COOKIE, "html");
    if (empty($_FN['fnsid']))
    {
        $_FN['fnsid'] = uniqid("1") . uniqid("0");
        setcookie("fnsid", $_FN['fnsid'], time() + 999999999, $_FN ['urlcookie']);
        $_COOKIE["fnsid"] = $_FN['fnsid'];
    }
    $_FN['return']['fnsid'] = $_FN['fnsid'];

    //---------------get sid---------------------------------------------------<
    if (!file_exists("{$_FN['datadir']}/_sessions/"))
    {
        FN_MkDir("{$_FN['datadir']}/_sessions");
    }
    $session = array();
    if (file_exists("{$_FN['datadir']}/_sessions/{$_FN['fnsid']}.session"))
    {
        $session = unserialize(file_get_contents("{$_FN['datadir']}/_sessions/{$_FN['fnsid']}.session"));
        //dprint_r("old:");
        //dprint_r($session);
    }
    if (is_array($value))
    {
        $session[$key] = array();
        foreach ($value as $k => $v)
        {
            $session[$key][$k] = $v;
        }
    }
    else
    {
        $session[$key] = $value;
    }
    FN_Write(serialize($session), "{$_FN['datadir']}/_sessions/{$_FN['fnsid']}.session");
}

/**
 * clean old files
 */
function FN_ClearOldSessions()
{
    global $_FN;
    $sessions = glob("{$_FN['datadir']}/_sessions/*.session");
    if (is_array($sessions))
        foreach ($sessions as $sessionfile)
        {
            if (time() - filectime($sessionfile) > 3600)
            {
                FN_Unlink($sessionfile);
            }
        }
}


/**
 * 
 * @global array $_FN
 * @param type $varname
 * @param type $maxtime
 * @return type
 */
function FN_GetGlobalVarValue($varname, $maxtime = false)
{
    global $_FN;
    $filename = "{$_FN['datadir']}/_cache/" . md5($varname) . ".cache";
    //$filename= sys_get_temp_dir()."/".md5($varname).".cache";

    if (file_exists($filename) && !FN_FileIsLocked($filename))
    {
        if ($maxtime && $maxtime > filectime($filename))
        {
            unlink($filename);
            return null;
        }
        $var = unserialize(file_get_contents($filename));
        if (!$var)
        {
            @unlink($filename);
            return null;
        }
        return $var;
    }
    return null;
}

/**
 * 
 * @global array $_FN
 * @param type $varname
 * @param type $value
 */
function FN_SetGlobalVarValue($varname, $value)
{
    global $_FN;
    //---------------get sid--------------------------------------------------->
    $filename = "{$_FN['datadir']}/_cache/" . md5($varname) . ".cache";
    //$filename= sys_get_temp_dir()."/".md5($varname).".cache";
    //---------------get sid---------------------------------------------------<
    if (!file_exists("{$_FN['datadir']}/_cache/"))
    {
        FN_MkDir("{$_FN['datadir']}/_cache");
    }
    if (!FN_FileIsLocked($filename))
    {
        FN_LockFile($filename);
        FN_Write($res = serialize($value), $filename);
        FN_UNLockFile($filename);
        if (!$res)
        {
            unlink($filename);
        }
        return true;
    }
    return false;
}


/**
 *
 * @global array $_FN
 * @param string $varname
 * @return variant 
 */
function FN_GetUserSessionValue($varname)
{
    global $_FN;

    if (empty($_FN['user']))
    {
        return null;
    }
    $t = FN_XMDBTable("fn_userssessions");
    $values = $t->GetRecord(array("username" => $_FN['user'], "varname" => $varname));
    if (isset($values['varname']))
    {
        return $values['varvalue'];
    }
    return null;
}

/**
 *
 * @global type $_FN
 * @param string $key
 * @param variant $value 
 */
function FN_SetUserSessionValue($varname, $value)
{
    global $_FN;
    if ($_FN['user'] == "")
        return;
    if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_userssessions.php"))
    {
        FN_Write("<?php exit(0);?>
<tables>
	<field>
		<name>id</name>
		<frm_show>0</frm_show>
		<extra>autoincrement</extra>
		<primarykey>1</primarykey>
	</field>
	<field>
		<name>username</name>
		<frm_it>Nome utente</frm_it>		
		<frm_show>onlyadmin</frm_show>
		<frm_setonlyadmin>1</frm_setonlyadmin>
	</field>
	<field>
		<name>varname</name>
	</field>
	<field>
		<name>varvalue</name>
	</field>
        <indexfield>username</indexfield>
</tables>
", "{$_FN['datadir']}/{$_FN['database']}/fn_userssessions.php");
    }
    $t = FN_XMDBTable("fn_userssessions");
    $values = $t->GetRecord(array("username" => $_FN['user'], "varname" => $varname));
    if (isset($values['varname']))
    {
        $values['varvalue'] = $value;
        $t->UpdateRecord($values);
    }
    else
    {
        $t->InsertRecord(array("username" => $_FN['user'], "varname" => $varname, "varvalue" => $value));
    }
}
