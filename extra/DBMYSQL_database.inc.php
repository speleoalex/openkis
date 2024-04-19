<?php

/**
 * @package Flatnux
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2012
 */
global $_FN;

//dprint_r($_FN);
/**
 *
 * @global string $xmldb_mysqldatabase
 * @global string $xmldb_mysqlusername
 * @global string $xmldb_mysqlpassword
 * @global string $xmldb_mysqlhost
 * @global array $_FN
 * @staticvar boolean $conn
 * @param string $query
 * @param bool $rollbackOnFail
 * @param bool $returnhandle
 * @return null|boolean|array 
 */
function DBMYSQL_Query($query, $rollbackOnFail = false, $returnhandle = false)
{
    //DBMYSQL_Debug($query);
    //innodb_rollback_on_timeout
    global $xmldb_mysqldatabase, $xmldb_mysqlusername, $xmldb_mysqlpassword, $xmldb_mysqlhost;
    global $DBMYSQL_timezone;
    global $mysqlconn;
    if (empty($DBMYSQL_timezone))
    {
        $DBMYSQL_timezone = "+2:00";
    }
    if (!is_object($mysqlconn))
    {
        $mysqlconn = new mysqli("$xmldb_mysqlhost", "$xmldb_mysqlusername", "$xmldb_mysqlpassword", "$xmldb_mysqldatabase");
        if (false === $mysqlconn)
        {
            return null;
        }
        DBMYSQL_Query("SET time_zone = '$DBMYSQL_timezone'");
    }
    $result = $mysqlconn->query($query);

    //DBMYSQL_Debug("$query\n{$result->num_rows} rows - ".sprintf("%.4f",abs($diff)));
    if (DBMYSQL_QueryError() != "")
    {
        DBMYSQL_Debug("errore all esecuzione di: $query");
        if ($rollbackOnFail)
        {
            DBMYSQL_Rollback();
        }
        return false;
    }
    if (isset($result->num_rows) && $result->num_rows > 0)
    {
        $res = array();
        if ($returnhandle)
        {
            return $result;
        }
        else
        {
            while ($tmp = $result->fetch_assoc())
            {
                $res[] = $tmp;
            }
        }
        @mysqli_free_result($result);
        return $res;
    }
    else
    {
        if (isset($result->num_rows) && $result->num_rows == 0 && preg_match('/^select/si', trim(ltrim($query))))
        {
            return array();
        }
        if (is_object($result))
        {
            @mysqli_free_result($result);
        }
        return true;
    }
}

/**
 *
 * @global object $mysqlconn
 * @return boolean 
 */
function DBMYSQL_QueryError()
{
    global $mysqlconn;
    if (!is_object($mysqlconn))
    {
        return false;
    }
    //dprint_r($mysqlconn->error);
    //verificare .... dalla 5.3 OK
    if ($mysqlconn->error)
        DBMYSQL_Debug($mysqlconn->error);
    return $mysqlconn->error;
}

/**
 *
 * @return boolean 
 */
function DBMYSQL_StartTransaction()
{
    if (!DBMYSQL_Query("START TRANSACTION"))
    {
        DBMYSQL_Debug("START TRANSACTION fallita a " . __FILE__ . ":" . __LINE__);
        return false;
    }
    return true;
}

/**
 *
 * @return boolean 
 */
function DBMYSQL_Rollback()
{
    if (!DBMYSQL_Query("ROLLBACK"))
    {
        DBMYSQL_Debug("ROLLBACK fallita a " . __FILE__ . ":" . __LINE__);
        return false;
    }
    return true;
}

/**
 *
 * @return boolean 
 */
function DBMYSQL_Commit()
{
    if (!DBMYSQL_Query("COMMIT"))
    {
        DBMYSQL_Debug("COMMIT fallita a " . __FILE__ . ":" . __LINE__);
        return false;
    }
    return true;
}

/**
 *
 * @param bool $rollbackOnFail
 * @return type 
 */
function DBMYSQL_LastInsertId($rollbackOnFail = false)
{
    $res = DBMYSQL_Query("select last_insert_id() AS ID", $rollbackOnFail);
    //DBMYSQL_Debug($res);
    return isset($res[0]['ID']) ? $res[0]['ID'] : false;
}

/**
 *
 * @global array $_FN
 * @param string|int $datetime
 * @return string|int 
 */
function DBMYSQL_TimeUserToServer($datetime, $forcedatetimeformat = "Y-m-d H:i:s", $timezone = "")
{
    global $_FN;
    $forcedatetimeformat = "Y-m-d H:i:s";
    $DateTimeZoneUTC = new DateTimeZone("UTC");
    $DateTimeUTC = new DateTime("NOW", $DateTimeZoneUTC);
    if (empty($_FN['uservalues']['timezone']) && $timezone == "")
        return $datetime;
    if ($timezone == "")
        $timezone = $_FN['uservalues']['timezone'];
    $tmpDateTimeZone = new DateTimeZone($timezone);
    $jet_lag = $tmpDateTimeZone->getOffset($DateTimeUTC);
    $s = "-";
    $sum = $jet_lag;
    if ($sum < 0)
    {
        $sum = abs($sum);
        $s = "+";
    }
    if (preg_match("#^[0-9]+$#", $datetime) || preg_match("#^-[0-9]+$#", $datetime))
    {
        if ($s == "-")
            $datetime -= $sum;
        else
            $datetime += $sum;
    }
    elseif (preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]$/', $datetime))
    {
        $datetime = strtotime("$datetime $s {$sum} seconds");
        return date("Y-m-d H:i:s", $datetime);
    }
    elseif (preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$/', $datetime))
    {
        if ($forcedatetimeformat)
            $format = "Y-m-d H:i:s";
        else
            $format = "Y-m-d";
        $datetime = strtotime("$datetime $s {$sum} seconds");
        return date("$format", $datetime);
    }
    return $datetime;
}

/**
 * 
 * @global array $_FN
 * @param string|int $datetime
 * @return string|int 
 */
function DBMYSQL_TimeServerToUser($datetime)
{
    global $_FN;
    $DateTimeZoneUTC = new DateTimeZone("UTC");
    $DateTimeUTC = new DateTime("NOW", $DateTimeZoneUTC);
    if (empty($_FN['uservalues']['timezone']))
        $_FN['uservalues']['timezone'] = "UTC";
    $tmpDateTimeZone = new DateTimeZone($_FN['uservalues']['timezone']);
    $jet_lag = $tmpDateTimeZone->getOffset($DateTimeUTC);
    $s = "+";
    if ($jet_lag < 0)
        $s = "";
    if (preg_match("#^[0-9]+$#", $datetime) || preg_match("#^-[0-9]+$#", $datetime))
    {
        $datetime += $jet_lag;
    }
    elseif (preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]$/', $datetime))
    {
        $datetime = strtotime("$datetime $s {$jet_lag} seconds");
        return date("Y-m-d H:i:s", $datetime);
    }
    elseif (preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$/', $datetime))
    {
        $datetime = strtotime("$datetime $s {$jet_lag} seconds");
        return date("Y-m-d", $datetime);
    }

    return $datetime;
}

/**
 *
 * @param type $table
 * @param type $values
 * @return type 
 */
function DBMYSQL_InsertRecord($table, $values)
{
    if (!is_array($values) || count($values) == 0)
        return false;
    $query = "INSERT INTO $table (";
    foreach ($values as $k => $v)
    {
        $tf[] = "`$k`";
    }
    $query .= implode(",", $tf);
    $query .= ") VALUES (";
    $tf = array();
    foreach ($values as $k => $v)
    {
        $tf[] = "'" . DBMYSQL_AddSlashInQuery($v) . "'";
    }
    $query .= implode(",", $tf);
    $query .= ");";
    $ret = DBMYSQL_Query($query);

    return $ret;
}

/**
 *
 * @param type $value
 * @return type 
 */
function DBMYSQL_AddSlashInQuery($v)
{
    $v = htmlentities($v, ENT_NOQUOTES, "UTF-8");
    $v = str_replace('\\', "\\\\", $v);
    $v = str_replace("'", "''", $v);
    return $v;
}

/**
 *
 * @param type $tablename
 * @param type $values
 * @param type $pkey
 * @param type $pvalue
 * @return type 
 */
function DBMYSQL_UpdateRecord($tablename, $values, $pkey, $pvalue)
{
    dprint_r($values);
    $query = "UPDATE {$tablename} SET ";
    $n = count($values);
    foreach ($values as $k => $value)
    {
        if (!is_numeric($k))
        {
            $query .= "`$k`=";
            $query .= "'" . DBMYSQL_AddSlashInQuery($value) . "'";
            if ($n-- > 1)
                $query .= ",";
        }
    }
    $query .= " WHERE `$pkey`=";
    $query .= "'$pvalue' ";
    // die($query);
    $ret = DBMYSQL_Query($query);
    return $ret;
}

function DBMYSQL_InitTimezones()
{
    global $_FN;
    //----------------------timezones---------------------------------------------->
    if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fn_timezones") && function_exists("timezone_identifiers_list"))
    {
        $allzones = array();
        $DateTimeZoneUTC = new DateTimeZone("UTC");
        $DateTimeUTC = new DateTime("2012-01-01 00:00:00", $DateTimeZoneUTC);
        $t = FN_XmlTable("fn_timezones");
        $zones = timezone_identifiers_list();
        foreach ($zones as $k => $zone)
        {
            $tmpDateTimeZone = new DateTimeZone($zone);
            $timeOffset = $tmpDateTimeZone->getOffset($DateTimeUTC);
            $allzones[$k]['offset'] = $timeOffset;
            $allzones [$k]['identifier'] = $zone;
            if (!$t->GetRecordByPk($zone))
                $t->InsertRecord($allzones [$k]);
        }
    }
//----------------------timezones----------------------------------------------<
}

/**
 * 
 * @param type $message
 */
function DBMYSQL_Debug($message)
{
    //print_r($message);
}

?>