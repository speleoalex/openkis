<?php

/**
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2009
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * @package xmldb
 *
 */
/**
 * xmldb_mysql.php created on 13/gen/2009
 * driver mysql per xmldb
 * allows you to enter data into a mysql table
 * :
 *
 * xml descriptor:
 * <driver>mysql</driver>
 * <host>mysqlserverhost</host>
 * <user>mysqlusername</user>
 * <password>mysqlpassword</password>
 *
 *
 *
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 */
global $xmldb_mysqldatabase, $xmldb_mysqlusername, $xmldb_mysqlpassword, $xmldb_mysqlhost;
global $xmldb_mysqlcurrentdb, $xmldb_mysqlconnection;

class XMLTable_mysql
{

    function __construct(&$xmltable, $params = false)
    {
        static $dbcache;

        $this->xmltable = &$xmltable;
        $this->tablename = &$xmltable->tablename;
        $this->databasename = &$xmltable->databasename;
        $this->fields = &$xmltable->fields;
        $this->path = &$xmltable->path;
        $this->numrecords = &$xmltable->numrecords;
        $this->primarykey = &$xmltable->primarykey;
        $this->xmldescriptor = &$xmltable->xmldescriptor;
        $this->mysqlfields = array();
        $this->nullfields = array();
        if (is_array($params)) {
            foreach ($params as $k => $v) {
                $this->$k = $v;
            }
        }
        $path = $this->path;
        $databasename = $this->databasename;
        $this->mysqldatabasename = $this->databasename;
        $tablename = $this->tablename;
        $xml = $this->xmldescriptor;
        //----Mysql---->
        $mysql['host'] = get_xml_single_element("host", $xml);
        $mysql['user'] = get_xml_single_element("user", $xml);
        $mysql['password'] = get_xml_single_element("password", $xml);
        $mysql['port'] = get_xml_single_element("port", $xml);
        $mysql['database'] = get_xml_single_element("database", $xml);
        if (empty($params['sqltable'])) {
            $sqltable = get_xml_single_element("sqltable", $xml);
        } else {
            $sqltable = $params['sqltable'];
        }
        if ($sqltable == "")
            $sqltable = $this->tablename;
        $this->sqltable = $sqltable;

        // se sono impostate connessioni a livello globale passo le impostazioni della tabella
        global $xmldb_mysqldatabase, $xmldb_mysqlusername, $xmldb_mysqlpassword, $xmldb_mysqlhost, $xmldb_timezone;
        if ($xmldb_mysqlhost != "" && $xmldb_mysqldatabase != "" && $xmldb_mysqlusername != "") {
            $mysql['host'] = $xmldb_mysqlhost;
            $mysql['user'] = $xmldb_mysqlusername;
            $mysql['password'] = $xmldb_mysqlpassword;
            $mysql['database'] = $xmldb_mysqldatabase;
        }
        if (is_array($params)) {
            foreach ($params as $k => $v) {
                $mysql[$k] = $v;
            }
        }
        if ($mysql['database'] == "")
            $mysql['database'] = $this->databasename;
        $this->mysqldatabasename = $mysql['database'];
        if ($mysql['port'] == "")
            $mysql['port'] = 3306;
        if ($mysql['host'] != "" && $mysql['user'] != "") {
            $xmltable->connection = $mysql;
            $this->connection = &$xmltable->connection;
        }
        global $xmldb_mysqlconnection;
        if (!$xmldb_mysqlconnection) {
            $xmldb_mysqlconnection = new mysqli($mysql['host'], $mysql['user'], $mysql['password']);
        }
        if ($xmldb_mysqlconnection) {
            $this->conn = $xmldb_mysqlconnection;
            if (empty($this->mysqldatabasename)) {
                $this->mysqldatabasename = $this->databasename;
            }

            //print_r($xmldb_mysqlconnection);
            $this->conn->query("SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'");
            if ($xmldb_timezone) {
                $this->conn->query("SET time_zone = '$xmldb_timezone'");
            }
            try {
                $this->conn->select_db($this->mysqldatabasename);
                $exists = true;
            } catch (Exception $e) {
                $exists = false;
            }

            if (!$exists) {
                $result = $this->conn->query("SHOW databases");
                $exists = false;
                //dprint_r($result);
                global $xmldb_mysqlcurrentdb;
                if ($xmldb_mysqlcurrentdb == $this->mysqldatabasename) {
                    $exists = true;
                } else {
                    try {
                        $this->conn->select_db($this->mysqldatabasename);
                        $exists = true;
                        $xmldb_mysqlcurrentdb = $this->mysqldatabasename;
                    } catch (Exception $e) {
                        $exists = false;
                    }
                }
                if (!$exists) {
                    if (false == $this->conn->query("CREATE DATABASE {$mysql['database']}")) {
                        echo ($this->conn->error);
                        return;
                    }
                }
            }
            $exists = false;
            if (empty($dbcache['tables'][$this->mysqldatabasename]))
                $dbcache['tables'][$this->mysqldatabasename] = $this->dbQuery("SHOW tables");
            $result = $dbcache['tables'][$this->mysqldatabasename];

            if ($result) {
                foreach ($result as $tmp) {
                    if ($tmp['Tables_in_' . $mysql['database']] == $this->sqltable)
                        $exists = true;
                }
            }


            //crea la tabella----->
            if (!$exists) {
                @ini_set("max_execution_time", "600");

                $fields = $this->fields;
                //dprint_r($fields);
                $query = "CREATE TABLE `{$this->sqltable}` (";
                $n = count($fields);
                foreach ($fields as $field) {
                    $field = get_object_vars($field);
                    if (!isset($field['type']) || $field['type'] == "string")
                        $field['type'] = "varchar";
                    $query .= "`" . $field['name'] . "`";
                    $field['size'] = isset($field['size']) ? $field['size'] : "";
                    $default = "''";
                    switch ($field['type']) {
                        case "innertable":
                            break;
                        case "text":
                        case "html":
                            $query .= " TEXT";
                            break;
                        case "int":
                            $query .= " INT";
                            $default = "0";
                            break;
                        default: //forzo tutto a varchar
                            $query .= " VARCHAR";
                            $field['size'] = "255";
                            break;
                    }
                    if ($field['size'] != "")
                        $query .= "(" . $field['size'] . ")";
                    $query .= " ";
                    if (isset($field['extra']) && $field['extra'] == "autoincrement") {
                        if ($field['type'] == "int") {
                            $query .= " AUTO_INCREMENT ";
                        }
                    }
                    if (isset($field['primarykey']) && $field['primarykey'] == "1") {
                        $query .= "  PRIMARY KEY ";
                    } else {
                        $query .= " DEFAULT $default ";
                    }
                    $query .= " NOT NULL ";
                    if ($n-- > 1)
                        $query .= ",";
                }
                $query .= ") DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_bin";

                if (!$this->dbQuery($query)) {
                    die($this->conn->error);
                }


                $dbcache['tables'][$this->mysqldatabasename] = $this->dbQuery("SHOW tables");
                //transfert xml data into mysql
                $tmpRecords = xmldb_readDatabase("$path/" . $databasename . "/" . $tablename, $tablename, false, false);

                foreach ($tmpRecords as $rec) {

                    $this->InsertRecord($rec);
                }
            }
            //crea la tabella-----<
            //--sincronizzo i campi --->
            if (empty($dbcache[$this->mysqldatabasename][$sqltable]['describe']))
                $dbcache[$this->mysqldatabasename][$sqltable]['describe'] = $this->dbQuery("DESCRIBE " . $this->sqltable);
            $xmlfield = $this->fields;
            $result = $dbcache[$this->mysqldatabasename][$sqltable]['describe'];
            $exists = false;
            if ($result) {
                foreach ($result as $tmp) {
                    if (!is_array($tmp))
                        return true;
                    $mysql_fields[$tmp['Field']] = $tmp;
                    if ($tmp['Null'] == "YES") {
                        $this->nullfields[$tmp['Field']] = $tmp['Field'];
                    }
                }
            } else {
                echo $this->conn->error;
                return false;
            }
            $flag_tablechanged = false;
            foreach ($xmlfield as $fieldname => $fieldvalues) {
                if (!isset($mysql_fields[$fieldname]) && $fieldvalues->type != "innertable") {
                    $field = get_object_vars($fieldvalues);
                    echo "add field $fieldname";
                    $query = "ALTER TABLE `" . $this->sqltable . "` ADD `$fieldname` ";
                    $field['size'] = isset($field['size']) ? $field['size'] : "";
                    $default = "";
                    switch ($field['type']) {
                        case "text":
                        case "html":
                            $query .= " TEXT";
                            break;
                        case "int":
                            $query .= " INT";
                            $default = 0;
                            break;
                        default: //forzo tutto a varchar
                            $query .= " VARCHAR";
                            $field['size'] = "255";
                            break;
                    }
                    if ($field['size'] != "")
                        $query .= "(" . $field['size'] . ")";
                    if ($field['type'] != "int")
                        $query .= " CHARACTER SET utf8 COLLATE utf8_bin";
                    $query .= " ";
                    if (isset($field['extra']) && $field['extra'] == "autoincrement") {
                        if ($field['type'] == "int")
                            $query .= " AUTO_INCREMENT ";
                    }

                    if ($default != "") {
                        $query .= " NOT NULL DEFAULT '$default'";
                    } else {
                        $query .= "  NULL";
                    }
                    if (!$this->dbQuery($query)) {
                        echo ($this->conn->error);
                        return false;
                    }
                    $flag_tablechanged = true;
                }
            }
            if ($flag_tablechanged)
                $dbcache[$this->mysqldatabasename][$sqltable]['describe'] = $this->dbQuery("DESCRIBE " . $this->sqltable);

            $this->mysqlfields = $mysql_fields;
            //--sincronizzo i campi ---<
        } else {
            echo ($this->conn->error);
            return false;
        }
        //	dprint_r($this->fields);
        //	die();
        return true;
        //<----Mysql----
    }

    /**
     * get records in table
     *
     * @param array $restr
     * @param int $min
     * @param int $length
     * @param string $order
     * @param bool $reverse
     * @param array $fields
     * @return array
     */
    function GetRecords($restr = false, $min = false, $length = false, $order = false, $reverse = false, $fields = array())
    {
        if ($fields === false) {
            $fields = array();
        }
        $tablename = $this->tablename;
        if (!$fields) {
            foreach ($this->fields as $ff => $vv) {
                $fields[] = $ff;
            }
        }
        if (is_array($fields))
            $fields = implode("|", $fields);
        $fields = '`' . str_replace("|", "`,`", $fields) . '`';
        $query = "SELECT $fields FROM {$this->sqltable}";
        if (is_array($restr) && count($restr) > 0) {
            $query .= " WHERE ";
            $and = "";
            foreach ($restr as $h => $v) {
                $query .= " $and `$h` LIKE '" . addslashes($v) . "' ";
                $and = "AND";
            }
        } elseif (is_string($restr)) {
            if (trim(ltrim($restr)) !== "") {
                $query .= " WHERE $restr";
            }
        }
        if ($order !== false && $order !== "" /* && isset($this->fields[$order]) */) {
            $query .= " ORDER BY ";
            $sepOrder = "";
            $order = explode(",", $order);
            foreach ($order as $v) {
                $newmode = "ASC";
                $newmodes = explode(":", $v);
                if (!empty($newmodes[1]))
                    $newmode = $newmodes[1];
                $orders[$newmodes[0]] = $newmode;
                if ($reverse && count($order) == 1) {
                    $orders[$newmodes[0]] = "DESC";
                }
            }
            foreach ($orders as $order => $mode) {
                if (isset($this->fields[$order])) {
                    $query .= "$sepOrder `$order`";
                    $sepOrder = ",";
                    $query .= " $mode";
                }
            }
        }
        if ($min !== false) {
            $query .= " LIMIT $min";
            if ($length !== false) {
                $query .= ",$length";
            }
        }
        //dprint_r($query);
        $ret = $this->dbQuery($query);
        return $ret;
    }

    /**
     * get single record
     *
     * @param array $restr
     * @return array
     */
    function GetRecord($restr = false)
    {
        $rec = $this->GetRecords($restr, 0, 1);
        if (is_array($rec) && isset($rec[0])) {
            return $rec[0];
        }
        return null;
    }

    /**
     * dbQuery
     *
     * @param string query
     */
    function dbQuery($query)
    {
        try {
            //          dprint_r($query);
            if (!isset($this->conn) || !$this->conn) {
                echo ($this->conn->error);
                return false;
            }
            global $xmldb_mysqlcurrentdb;
            if ($xmldb_mysqlcurrentdb != $this->mysqldatabasename && $this->conn->select_db($this->mysqldatabasename)) {
                $xmldb_mysqlcurrentdb = $this->mysqldatabasename;
            }
            $result = $this->conn->query($query);
            if ($result === false) {
                return false;
            }
            $res = null;
            if (is_object($result) && method_exists($result, "fetch_assoc")) {
                if (preg_match("/^UPDATE /is", $query))
                    return true;
                if (preg_match("/^INSERT /is", $query))
                    return true;
                //			if (!is_resource($result))
                //				return true;
                $res = array();
                while ($tmp = $result->fetch_assoc()) {
                    $tmp = $this->fix_null($tmp);
                    $res[] = $tmp;
                }
            } else {
                return $result;
            }
            //dprint_r($res);
            //die();
            return $res;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * alias GetRecordByPk
     *
     * @param string $pvalue
     * @return array
     */
    function GetRecordByPrimaryKey($pvalue)
    {
        return $this->GetRecordByPk($pvalue);
    }

    /**
     * GetRecordByPk
     * torna il record passandogli la chiave primaria
     * @param string $pvalue valore chiave
     */
    function GetRecordByPk($pvalue)
    {
        $tablename = $this->tablename;
        $pkey = $this->primarykey;
        // se i dati sono su database --->
        if ($this->connection && !empty($pkey)) {
            if (!$this->conn)
                die($this->conn->error);
            #$this->conn->select_db ($this->mysqldatabasename);
            $query = "SELECT * FROM {$this->sqltable} WHERE $pkey LIKE '$pvalue'";
            $result = $this->dbQuery($query);
            if (!isset($result[0])) {
                return null;
            }
            //$res = mysql_fetch_assoc($result);
            $res = $this->fix_null($result[0]);
            return $res;
        }
        // <--- se i dati sono su database
        return false;
    }

    /**
     * convert NULL in ""
     * @param $res
     */
    function fix_null($res)
    {
        //print_r($this->nullfields);
        if (is_array($this->nullfields) && is_array($res)) {
            foreach ($res as $k => $v) {
                if ($res[$k] === NULL)
                    $res[$k] = "";
            }
            /*
              foreach ( $this->nullfields as $k=>$v )
              {
              if ( @$res[$k] === NULL )
              $res[$k] = "";
              }
             */
        }
        return $res;
    }

    /**
     * DelRecord
     * Elimina un record.
     * @param string $unirecid
     * <b>$values[$this->primarykey] deve essere presente</b>
     * @return array record appena inserito o null
     * */
    function DelRecord($pkvalue)
    {
        $path = $this->path;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        if ($this->connection) {
            if (!$this->conn)
                die($this->conn->error);
            $pkey = $this->primarykey;
            if ($this->fields[$this->primarykey]->type == "int")
                $query = "DELETE FROM {$this->sqltable} WHERE $pkey LIKE " . $pkvalue;
            else
                $query = "DELETE FROM {$this->sqltable} WHERE $pkey LIKE '" . addslashes($pkvalue) . "'";
            $result = $this->dbQuery($query);
            //mysql_close($connessione);
            if (!$result) {
                echo $this->conn->error;
                return false;
            }
            if (!strpos($pkvalue, "..") !== false && file_exists("$path/$databasename/$tablename/$pkvalue/") && is_dir("$path/$databasename/$tablename/$pkvalue/"))
                xmldb_remove_dir_rec("$path/$databasename/$tablename/$pkvalue");
            return true;
        }
        return false;
    }

    /**
     * truncate table
     *
     * @return unknown
     */
    function Truncate()
    {
        if (!$this->conn)
            die($this->conn->error);
        $result = $this->dbQuery("truncate " . $this->sqltable);
        if (!$result) {
            echo $this->conn->error;
            return false;
        }
        return true;
    }

    /**
     * InsertRecord
     * Aggiunge un record
     *
     * @param array $values
     * */
    function InsertRecord($values)
    {
        if (!empty($this->conn)) {

            // if ($this->conn)
            {
                $seldb = true;
                $query = "INSERT INTO `" . $this->sqltable . "` (";
                if (!isset($values[$this->primarykey]))
                    $values[$this->primarykey] = "";
                $n = count($values);
                $tf = array();
                foreach ($values as $k => $v) {
                    if (isset($this->fields[$k])) {
                        //------autoincrement--->
                        if (isset($this->fields[$k]->extra) && $this->fields[$k]->extra == "autoincrement") {
                            if (!isset($this->fields[$k]->nativeautoincrement) || $this->fields[$k]->nativeautoincrement != 1) {
                                if (!isset($values[$k]) || $values[$k] == "") {
                                    $newid = $this->GetAutoincrement($k);
                                    $values[$k] = $newid;
                                    $v = $newid;
                                    $this->maxautoincrement[$k] = $newid;
                                }
                            }
                        }
                        //------autoincrement---<
                        $tf[] = "`$k`";
                    }
                }
                $query .= implode(",", $tf);
                $query .= ") VALUES (";
                $tf = array();
                foreach ($values as $k => $v) {
                    if (isset($this->fields[$k])) // 'IF' ADDED BY DANIELE FRANZA 28/03/2009
                    {
                        if (isset($this->mysqlfields[$k]['Null']) && $this->mysqlfields[$k]['Null'] == "YES" && $v == "") {
                            $tf[] = "NULL";
                        } else {
                            if ($this->fields[$k]->type == "int" && $v !== '' && $v !== NULL)
                                $tf[] = $v;
                            else {
                                $v = str_replace('\\', "\\\\", $v);
                                $tf[] = "'" . str_replace("'", "\\'", $v) . "'";
                            }
                        }
                    }
                }
                $query .= implode(",", $tf);
                $query .= ");";
            }

            $ret = $this->dbQuery($query);
            if (!$ret) {
                echo ($this->conn->error);
                return false;
            }
            if (!isset($values[$this->primarykey]) || $values[$this->primarykey] == "") {
                $lastid = $this->dbQuery("SELECT * FROM {$this->sqltable} where {$this->primarykey} LIKE LAST_INSERT_ID();");
                /*
                  $lastid = $this->dbQuery("SELECT LAST_INSERT_ID() FROM {$this->sqltable};");
                  if ( !isset($lastid[0]['LAST_INSERT_ID()']) )
                  {
                  echo ($this->conn->error);
                  return false;
                  }
                  $values[$this->primarykey] = $lastid[0]['LAST_INSERT_ID()'];

                 */
                $values = $lastid[0];
            }
            $this->gestfiles($values);
            return $values;
        }
        return false;
    }

    /**
     * InsertRecord
     * Aggiunge un record
     *
     * @param array $values
     * */
    function InsertRecordFast($values)
    {
        if ($this->connection) {
            if ($this->conn) {
                $seldb = true;
                $query = "INSERT INTO `" . $this->sqltable . "` (";
                if (!isset($values[$this->primarykey]))
                    $values[$this->primarykey] = "";
                $n = count($values);
                $tf = array();
                foreach ($values as $k => $v) {
                    if (isset($this->fields[$k])) {
                        //------autoincrement--->
                        if (isset($this->fields[$k]->extra) && $this->fields[$k]->extra == "autoincrement") {
                            if (!isset($this->fields[$k]->nativeautoincrement) || $this->fields[$k]->nativeautoincrement != 1) {
                                if (!isset($values[$k]) || $values[$k] == "") {
                                    $newid = $this->GetAutoincrement($k);
                                    $values[$k] = $newid;
                                    $v = $newid;
                                    $this->maxautoincrement[$k] = $newid;
                                }
                            }
                        }
                        //------autoincrement---<
                        $tf[] = "`$k`";
                    }
                }
                $query .= implode(",", $tf);
                $query .= ") VALUES (";
                $tf = array();
                foreach ($values as $k => $v) {
                    if (isset($this->fields[$k])) // 'IF' ADDED BY DANIELE FRANZA 28/03/2009
                    {
                        if ($this->mysqlfields[$k]['Null'] == "YES" && $v == "") {
                            $tf[] = "NULL";
                        } else {
                            if ($this->fields[$k]->type == "int")
                                $tf[] = $v;
                            else {
                                $v = str_replace('\\', "\\\\", $v);
                                $tf[] = "'" . str_replace("'", "\\'", $v) . "'";
                            }
                        }
                    }
                }
                $query .= implode(",", $tf);
                $query .= ");";
            }
            global $xmldb_mysqlcurrentdb;
            if ($xmldb_mysqlcurrentdb != $this->mysqldatabasename && $this->conn->select_db($this->mysqldatabasename, $this->conn)) {
                $xmldb_mysqlcurrentdb != $this->mysqldatabasename;
            }
            if (!$this->conn->query($query)) {
                echo ($this->conn->error);
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * UpdateRecordBypk
     * aggiorna il record passandogli la chiave primaria
     * @param array $values
     * @param string $pkey
     * @param string $pvalue
     */
    function UpdateRecordBypk($values, $pkey, $pvalue)
    {

        $tablename = $this->tablename;
        if (is_array($this->connection)) {
            if ($this->conn) {
                $existsvalues = $this->GetRecordByPk($pvalue);
                if (!isset($existsvalues[$pkey]))
                    return false;
                // $oldvalues = ($values[$pkey] != $pvalue ) ? $existsvalues : null;
                $oldvalues = $existsvalues;
                $query = "UPDATE `{$this->sqltable}` SET ";
                $values2 = array();
                foreach ($values as $k => $value) {
                    //if ($values[$k] != $existsvalues[$k])//accorcio la query
                    if (isset($this->fields[$k]))
                        $values2[$k] = $values[$k];
                }
                $n = count($values2);
                if ($n == 0) //se non c'e' nulla da aggiornare
                    return $existsvalues;

                foreach ($values2 as $k => $value) {
                    if (isset($this->fields[$k])) {
                        $query .= "`$k`=";
                        if ($this->mysqlfields[$k]['Null'] == "YES" && $value == "") {
                            $query .= "NULL";
                        } else {
                            if ($this->fields[$k]->type == "int")
                                $query .= addslashes($value);
                            else
                                $query .= "'" . addslashes($value) . "'";
                        }
                        if ($n-- > 1)
                            $query .= ",";
                    }
                }
                $query .= " WHERE `$pkey`LIKE ";
                if ($this->fields[$pkey]->type == "int")
                    $query .= "$pvalue ";
                else
                    $query .= "'$pvalue' ";
                $ret = $this->dbQuery($query);
                $this->gestfiles($values, $oldvalues);
                if (!$ret) {
                    return $this->conn->error;
                }
                $newvalues = $this->GetRecordByPk($pvalue);
            } else
                return $this->conn->error;
            return $newvalues;
        }
        return false;
    }

    /**
     *
     * @param type $values
     * @return type 
     */
    function UpdateRecordFast($values)
    {
        $tablename = $this->tablename;
        $pkey = $this->primarykey;
        $pvalue = $values[$pkey];
        if (is_array($this->connection)) {
            if ($this->conn) {
                $query = "UPDATE `{$this->sqltable}` SET ";
                $values2 = array();
                foreach ($values as $k => $value) {
                    if (isset($this->fields[$k]))
                        $values2[$k] = $values[$k];
                }
                $n = count($values2);
                if ($n == 0) //se non c'e' nulla da aggiornare
                    return $this->GetRecordByPk($pvalue);;
                foreach ($values2 as $k => $value) {
                    if (isset($this->fields[$k])) {
                        $query .= "`$k`=";
                        if ($this->mysqlfields[$k]['Null'] == "YES" && $value == "") {
                            $query .= "NULL";
                        } else {
                            if ($this->fields[$k]->type == "int")
                                $query .= addslashes($value);
                            else
                                $query .= "'" . addslashes($value) . "'";
                        }
                        if ($n-- > 1)
                            $query .= ",";
                    }
                }
                $query .= " WHERE `$pkey`=";
                if ($this->fields[$pkey]->type == "int" || $this->fields[$pkey]->type == "float")
                    $query .= "$pvalue ";
                else
                    $query .= "'$pvalue' ";

                $ret = $this->dbQuery($query);
                if (!$ret) {
                    return $this->conn->error;
                }
                $newvalues = $this->GetRecordByPk($pvalue);
            } else
                return $this->conn->error;
            return $newvalues;
        }
        return false;
    }

    /**
     * GetNumRecords
     * return records count
     * 
     * @param type $restr
     * @return type 
     */
    function GetNumRecords($restr = null)
    {
        $query = "SELECT COUNT(*) AS C FROM " . $this->sqltable;
        if (is_array($restr) && count($restr) > 0) {
            $query .= " WHERE ";
            $and = "";
            foreach ($restr as $h => $v) {
                $query .= " $and $h LIKE '$v' ";
                $and = "AND";
            }
        } elseif (is_string($restr) && trim(ltrim($restr)) !== "") {
            $query .= " WHERE $restr";
        }

        $ret = $this->dbQuery($query);
        //dprint_r($query);
        if (isset($ret[0]['C']))
            return $ret[0]['C'];
        return 0;
    }

    /**
     *
     * @param type $values
     * @param type $oldvalues 
     */
    function gestfiles($values, $oldvalues = null)
    {
        $this->xmltable->gestfiles($values, $oldvalues);
    }

    /**
     *
     * @param type $recordvalues
     * @param type $recordkey
     * @return type 
     */
    function get_thumb($recordvalues, $recordkey)
    {
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = realpath($this->path);
        $unirecid = $recordvalues[$this->primarykey];
        if (!isset($recordvalues[$recordkey]))
            $recordvalues = $this->GetRecord($recordvalues);
        $value = $recordvalues[$recordkey];
        if (file_exists("$path/$databasename/$tablename/$unirecid/$recordkey/thumbs/$value.jpg")) {
            $php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : "";
            $dirname = dirname($php_self);
            if ($dirname == "/" || $dirname == "\\") {
                $dirname = "";
            }
            $protocol = "http://";
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
                $protocol = "https://";
            $siteurl = "$protocol" . $_SERVER['HTTP_HOST'] . $dirname;
            if (substr($siteurl, strlen($siteurl) - 1, 1) != "/") {
                $siteurl = $siteurl . "/";
            }
            return "$siteurl" . $this->path . "/$databasename/$tablename/$unirecid/$recordkey/thumbs/$value.jpg";
        }
        return false;
    }

    /**
     * GetAutoincrement
     *
     * gestisce l' autoincrement di un campo della tabella
     *
     * @param string nome del campo
     * @return indice disponibile
     */
    function GetAutoincrement($field)
    {
        //die ("vvvv");
        static $last = "";
        if (isset($this->maxautoincrement[$field])) {
            return $this->maxautoincrement[$field] + 1;
        }
        //todo autoincrement offset SHOW VARIABLES;
        $record = $this->dbQuery("SELECT MAX(CAST($field AS UNSIGNED)) AS $field FROM {$this->sqltable} WHERE $field NOT LIKE '%[a-z]%' ");
        //dprint_r ("SELECT MAX(CAST($field AS UNSIGNED)) AS $field FROM {$this->sqltable} WHERE $field NOT LIKE '%[a-z]%' ");
        //dprint_r($record);
        if (!isset($record[0][$field]))
            return 1;
        $max = $record[0][$field];
        //dprint_r($max);
        return intval($max) + 1;
    }
}

/**
 * xml_to_sql
 * Trasforma una tabella xml in una tabella sql
 *
 */
function xml_to_sql($databasename, $tablename, $xmlpath, $connection, $dropold = false)
{
    // leggo i dati dalla tabella xml
    $TableXml = new XMLTable($databasename, $tablename, $xmlpath);
    //$records = $TableXml->GetRecords();
    //die();
    if (!isset($connection['sqltable'])) {
        $connection['sqltable'] = $tablename;
    }
    if (isset($TableXml->connection) && is_array($TableXml->connection)) {
        echo "this is already sql database";
        return false;
    }
    if (!isset($connection['database'])) {
        $connection['database'] = $databasename;
    }
    global $xmldb_mysqlconnection;
    if (!$xmldb_mysqlconnection) {
        $xmldb_mysqlconnection = @mysql_connect($connection['host'], $connection['user'], $connection['password']);
    }
    if (!$xmldb_mysqlconnection) {
        //echo "connection failed<br />";
        echo $this->conn->error;
        return false;
    }
    //modifico le proprieta' della tabella xml
    $oldfilestring = file_get_contents($xmlpath . "/$databasename/$tablename.php");
    $strnew = "\n\t<driver>mysql</driver>";
    $strnew .= "\n\t<host>" . $connection['host'] . "</host>";
    $strnew .= "\n\t<user>" . $connection['user'] . "</user>";
    $strnew .= "\n\t<password>" . $connection['password'] . "</password>";
    $strnew .= "\n\t<database>" . $connection['database'] . "</database>";
    $strnew .= "\n\t<sqltable>" . $connection['sqltable'] . "</sqltable>";
    $strnew .= "\n\t<port>" . $connection['port'] . "</port>";
    $newfilestring = preg_replace('/<\/tables>$/s', xmldb_encode_preg_replace2nd($strnew) . "\n</tables>", trim(($oldfilestring))) . "\n";
    //die("<pre>".htmlspecialchars($newfilestring)."</pre>");
    $file = fopen($xmlpath . "/$databasename/$tablename.php", "w");
    fwrite($file, $newfilestring);
    $TableSql = new XMLTable($databasename, $tablename, $xmlpath);
    return true;
}
