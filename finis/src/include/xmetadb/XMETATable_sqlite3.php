<?php

/**
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2014
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * @package xmetadb
 *
 */
/**
 * xmetadb_sqlite.php created on 13/feb/2014
 * driver sqlite per xmetadb
 * permette di inserire i dati in una tabella sqlite
 * il descrittore della tabella deve contenere:
 *
 * <driver>sqlite</driver>
 * <host>sqliteserverhost</host>
 * <user>sqliteusername</user>
 * <password>sqlitepassword</password>
 *
 *
 *
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 */
global $xmetadb_sqlitedatabase, $xmetadb_sqliteusername, $xmetadb_sqlitefilename;

class XMETATable_sqlite3 extends stdClass
{

    function __construct(& $xmltable, $params = false)
    {
        if (!class_exists("SQLite3"))
        {
            die("class SQLite3 doesn't exists");
        }
        $this->xmltable = &$xmltable;
        $this->tablename = & $xmltable->tablename;
        $this->databasename = & $xmltable->databasename;
        $this->fields = & $xmltable->fields;
        $this->path = & $xmltable->path;
        $this->numrecords = & $xmltable->numrecords;
        $this->primarykey = &$xmltable->primarykey;
        $this->xmldescriptor = &$xmltable->xmldescriptor;
        $this->sqlitefields = array();
        $this->nullfields = array();
        $this->sqlite_error = false;
        $this->maxautoincrement = array();
        if (is_array($params))
        {
            foreach ($params as $k => $v)
            {
                $this->$k = $v;
            }
        }
        $path = $this->path;
        $databasename = $this->databasename;
        $this->sqlitedatabasename = $this->databasename;
        $tablename = $this->tablename;
        $xml = $this->xmldescriptor;
        //----Mysql---->
        $sqlite['filename'] = get_xml_single_element("sqlite3filename", $xml);
        $sqlite['database'] = get_xml_single_element("database", $xml);
        $sqltable = get_xml_single_element("sqltable", $xml);
        if ($sqlite['filename'] == "")
            $sqlite['filename'] = $path . "/$databasename.sqlite3";
        if ($sqltable == "")
            $sqltable = $this->tablename;
        $this->sqltable = $sqltable;
        // se sono impostate connessioni a livello globale nypasso le impostazioni della tabella
        global $xmetadb_sqlitedatabase, $xmetadb_sqlitefilename;
        if ($xmetadb_sqlitedatabase != "")
        {
            $sqlite['database'] = $xmetadb_sqlitedatabase;
            $sqlite['filename'] = $xmetadb_sqlitefilename;
        }
        if (is_array($params))
        {
            foreach ($params as $k => $v)
            {
                $sqlite[$k] = $v;
            }
        }
        if ($sqlite['database'] == "")
            $sqlite['database'] = $this->databasename;
        $this->sqlitedatabasename = $sqlite['database'];

        if ($sqlite['filename'] != "")
        {
            $xmltable->connection = $sqlite;
            $this->connection = & $xmltable->connection;
        }
//		try
        {
            $this->conn = new SQLite3($sqlite['filename'], SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        }
//		catch(Exception $e)
//		{
//			dprint_r($e);
//			$this->conn = false;
//		}
        if ($this->conn)
        {

            $this->dbQuery('PRAGMA encoding = "UTF-8";');
            $result = $this->dbQuery("SELECT name FROM sqlite_master WHERE type='table'");
            $exists = false;
            if ($result)
            {
                foreach ($result as $tmp)
                {
                    if ($tmp['name'] == $this->sqltable)
                        $exists = true;
                }
            }
            //crea la tabella----->
            if (!$exists)
            {
                //die ("ccc");
                $fields = $this->fields;
                //dprint_r($fields);
                $query = "CREATE TABLE {$this->sqltable} (";
                $n = count($fields);
                foreach ($fields as $field)
                {
                    $field = get_object_vars($field);
                    if (!isset($field['type']) || $field['type'] == "string")
                        $field['type'] = "varchar";
                    $query .= "'" . $field['name'] . "' ";
                    $field['size'] = isset($field['size']) ? $field['size'] : "";
                    switch ($field['type'])
                    {
                        case "innertable" :
                            break;
                        case "text" :
                        case "html" :
                            $query .= " TEXT";
                            break;
                        case "int" :
                            $query .= " INT";
                            break;
                        default : //forzo tutto a varchar
                            $query .= " VARCHAR";
                            $field['size'] = "255";
                            break;
                    }
                    if ($field['size'] != "")
                        $query .= "(" . $field['size'] . ")";
                    $query .= " ";
                    if (isset($field['extra']) && $field['extra'] == "autoincrement")
                    {
                        if ($field['type'] == "int")
                        {
                            $query .= " AUTO_INCREMENT ";
                        }
                    }
                    if (isset($field['primarykey']) && $field['primarykey'] == "1")
                    {
                        $query .= "  PRIMARY KEY ";
                    }
                    //$query .= "  NOT NULL ";
                    if ($n-- > 1)
                        $query .= ",";
                }
                $query .= ")";
                //dprint_r($query);
                if (!$this->dbQuery($query))
                {
                    echo("error:" . $this->sqlite_error);
                }
                //transfert xml data into sqlite
                $tmpRecords = xmetadb_readDatabase("$path/" . $databasename . "/" . $tablename, $tablename, false, false);
//			dprint_r($tmpRecords);
                foreach ($tmpRecords as $rec)
                {
                    $this->InsertRecord($rec);
                }
            }
            //crea la tabella-----<
            //die ("creata");
            //--sincronizzo i campi --->
            $xmlfield = $this->fields;
            $result = $this->dbQuery("PRAGMA table_info(" . $this->sqltable . "); ");
            $exists = false;

            if ($result)
            {
                foreach ($result as $tmp)
                {
                    $sqlite_fields[$tmp['name']] = $tmp;
                    if ($tmp['notnull'] == "0")
                    {
                        $this->nullfields[$tmp['name']] = $tmp['name'];
                    }
                }
                //dprint_r($this->nullfields);
            }
            else
            {
                echo $this->sqlite_error;
                return false;
            }


            foreach ($xmlfield as $fieldname => $fieldvalues)
            {
                if (!isset($sqlite_fields[$fieldname]) && $fieldvalues->type != "innertable")
                {
                    $field = get_object_vars($fieldvalues);
                    echo "add field $fieldname";
                    $query = "ALTER TABLE " . $this->sqltable . " ADD COLUMN $fieldname ";
                    $field['size'] = isset($field['size']) ? $field['size'] : "";
                    switch ($field['type'])
                    {
                        case "text" :
                        case "html" :
                            $query .= " TEXT";
                            break;
                        case "int" :
                            $query .= " INT";
                            break;
                        default : //forzo tutto a varchar
                            $query .= " VARCHAR";
                            $field['size'] = "255";
                            break;
                    }
                    if ($field['size'] != "")
                        $query .= "(" . $field['size'] . ")";
                    //if ($field['type'] != "int")
                    //	$query .= " CHARACTER SET utf8 COLLATE utf8_general_ci";
                    $query .= " ";
                    if (isset($field['extra']) && $field['extra'] == "autoincrement")
                    {
                        if ($field['type'] == "int")
                            $query .= " AUTO_INCREMENT ";
                    }
                    //$query .= " NOT NULL";
                    //dprint_r($query);
                    //die();

                    if (!$this->dbQuery($query))
                    {
                        echo ("add field error");
                        return false;
                    }
                }
            }
            //dprint_r($xmlfield);
            //dprint_r($sqlite_fields);
            $this->sqlitefields = $sqlite_fields;
            //--sincronizzo i campi ---<
            //sqlite_close();
        }
        else
        {
            echo ($this->sqlite_error);
            return false;
        }
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
    function GetRecords($restr = false, $min = false, $length = false, $order = false, $reverse = false, $fields = false)
    {
        if (!$fields)
        {
            $fields = "*";
        }
        else
        {
            if (is_array($fields))
                $fields = implode("|", $fields);
            $fields = '[' . str_replace("|", "],[", $fields) . ']';
        }
        $query = "SELECT $fields FROM {$this->sqltable}";
        if (is_array($restr) && count($restr) > 0)
        {
            $query .= " WHERE ";
            $and = "";
            foreach ($restr as $h => $v)
            {
                $query .= " $and [$h] LIKE '" . SQLite3::escapeString($v) . "' ";
                $and = "AND";
            }
        }
        if (is_string($restr))
        {
            $query .= " WHERE $restr";
        }

        if ($order !== false && $order !== "" && isset($this->fields[$order]))
        {
            $query .= " ORDER BY  $order";
        }
        else
        {
            if ($order !== false && $order !== "")
            {
                $query .= " ORDER BY ";
                $sepOrder = "";
                $order = explode(",", $order);
                foreach ($order as $v)
                {
                    $newmode = "ASC";
                    $newmodes = explode(":", $v);
                    if (!empty($newmodes[1]))
                        $newmode = $newmodes[1];
                    $orders[$newmodes[0]] = $newmode;
                }
                foreach ($orders as $order => $mode)
                {
                    if (isset($this->fields[$order]))
                    {
                        $query .= "$sepOrder `$order`";
                        $sepOrder = ",";
                        $query .= " $mode";
                    }
                }
            }
        }



        if ($reverse)
        {
            $query .= " DESC";
        }
        if ($min !== false)
        {
            $query .= " LIMIT $min";
            if ($length !== false)
            {
                $query .= ",$length";
            }
        }
        return $this->dbQuery($query);
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
        if (is_array($rec) && isset($rec[0]))
        {
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
        //dprint_r($query);
        if (!isset($this->conn) || !$this->conn)
        {
            $this->sqlite_error = "connection error";
            return false;
        }
        $results = $this->conn->query($query);
        $res = null;
        if ($results)
        {
            $res = array();
            if (preg_match("/^INSERT /is", $query))
                return true;
            if (preg_match("/^UPDATE /is", $query))
            {
                $this->maxautoincrement = array();
                return true;
            }
            if (preg_match("/^CREATE /is", $query))
            {
                $this->maxautoincrement = array();
                return true;
            }
            if (preg_match("/^DELETE /is", $query))
            {
                $this->maxautoincrement = array();
                return true;
            }
            if (preg_match("/^DROP /is", $query))
            {
                $this->maxautoincrement = array();
                return true;
            }
            if (preg_match("/^TRUNCATE /is", $query))
            {
                $this->maxautoincrement = array();
                return true;
            }
            if (preg_match("/^ALTER /is", $query))
            {
                $this->maxautoincrement = array();
                return true;
            }

            while (false !== ($tmp = @$results->fetchArray(SQLITE3_ASSOC)))
            {
                if (!is_array($tmp))
                {
                    return true;
                }
                $tmp2 = array();
                foreach ($tmp as $k => $t)
                {
                    $tmp2[str_replace("'", "", $k)] = $t;
                }
                $res[] = $tmp2;
            }
        }
        else
        {
            die("error in $query");
        }
        $this->sqlite_error = $this->conn->lastErrorMsg();
        return $res;
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
        if ($this->connection)
        {
            if (!$this->conn)
                die("error connection");
            $query = "SELECT * FROM {$this->sqltable} WHERE $pkey LIKE '$pvalue'";
            $result = $this->dbQuery($query);
            if (!isset($result[0]))
            {
                return null;
            }
            //$res = sqlite_fetch_assoc($result);
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
        if (is_array($this->nullfields) && is_array($res))
        {
            foreach ($res as $k => $v)
            {
                if ($res[$k] === NULL)
                    $res[$k] = "";
            }
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
        if ($this->connection)
        {
            if (!$this->conn)
                die(sqlite_error());
            $pkey = $this->primarykey;
            if ($this->fields[$this->primarykey]->type == "int")
                $query = "DELETE FROM {$this->sqltable} WHERE $pkey LIKE " . $pkvalue;
            else
                $query = "DELETE FROM {$this->sqltable} WHERE $pkey LIKE '" . SQLite3::escapeString($pkvalue) . "'";
            $result = $this->dbQuery($query);
            if (!$result)
            {
                echo $this->sqlite_error;
                return false;
            }
            if (!strpos($pkvalue, "..") !== false && file_exists("$path/$databasename/$tablename/$pkvalue/") && is_dir("$path/$databasename/$tablename/$pkvalue/"))
                xmetadb_remove_dir_rec("$path/$databasename/$tablename/$pkvalue");
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
            die("error truncate");
        $result = $this->dbQuery($query);
        if (!$result)
        {
            echo $this->sqlite_error;
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
        if ($this->connection)
        {
            if ($this->conn)
            {
                $seldb = true;
                $query = "INSERT INTO " . $this->sqltable . " (";
                if (!isset($values[$this->primarykey]))
                    $values[$this->primarykey] = "";
                $n = count($values);
                $tf = array();
                foreach ($values as $k => $v)
                {
                    if (isset($this->fields[$k]))
                    {
                        //------autoincrement--->
                        if (isset($this->fields[$k]->extra) && $this->fields[$k]->extra == "autoincrement")
                        {
                            if (!isset($this->fields[$k]->nativeautoincrement) || $this->fields[$k]->nativeautoincrement != 1)
                            {
                                if (!isset($values[$k]) || $values[$k] == "")
                                {
                                    $newid = $this->GetAutoincrement($k);
                                    $values[$k] = $newid;
                                    $v = $newid;                                    
                                }
                                else
                                {
                                    $this->maxautoincrement = array();
                                }
                            }
                        }
                        //------autoincrement---<
                        $tf[] = "'$k'";
                    }
                }
                $query .= implode(",", $tf);
                $query .= ") VALUES (";
                $tf = array();
                foreach ($values as $k => $v)
                {
                    if (isset($this->fields[$k])) // 'IF' ADDED BY DANIELE FRANZA 28/03/2009
                    {
                        if (isset($this->sqlitefields[$k]['notnull']) && $this->sqlitefields[$k]['notnull'] != "0" && $v == "")
                        {
                            $tf[] = "NULL";
                        }
                        else
                        {
                            if ($this->fields[$k]->type == "int")
                                $tf[] = $v;
                            else
                            {
                                $v = SQLite3::escapeString($v);
                                $tf[] = "'$v'";
                            }
                        }
                    }
                }
                $query .= implode(",", $tf);
                $query .= ");";
            }

            $ret = $this->dbQuery($query);
            if (!$ret)
            {
                echo ("error insert");
                return false;
            }

            if (!isset($values[$this->primarykey]) || $values[$this->primarykey] == "")
            {
                $lastid = $this->dbQuery("SELECT * FROM {$this->sqltable} where {$this->primarykey} LIKE last_insert_rowid();");
                $values = $lastid[0];
            }
            $this->gestfiles($values);
            return $values;
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
        if (is_array($this->connection))
        {
            if ($this->conn)
            {
                $existsvalues = $this->GetRecordByPk($pvalue);
                if (!isset($existsvalues[$pkey]))
                    return false;
                // $oldvalues = ($values[$pkey] != $pvalue ) ? $existsvalues : null;
                $oldvalues = $existsvalues;
                $query = "UPDATE {$this->sqltable} SET ";
                $values2 = array();
                foreach ($values as $k => $value)
                {
                    //if ($values[$k] != $existsvalues[$k])//accorcio la query
                    if (isset($this->fields[$k]))
                        $values2[$k] = $values[$k];
                }
                $n = count($values2);
                if ($n == 0) //se non c'e' nulla da aggiornare
                    return $existsvalues;

                foreach ($values2 as $k => $value)
                {
                    if (isset($this->fields[$k]))
                    {
                        $query .= "[$k]=";
                        //dprint_r($this->sqlitefields);
                        if ($this->sqlitefields[$k]['notnull'] != "0" && $value == "")
                        {
                            $query .= "NULL";
                        }
                        else
                        {
                            if ($this->fields[$k]->type == "int")
                                $query .= SQLite3::escapeString($value);
                            else
                                $query .= "'" . SQLite3::escapeString($value) . "'";
                        }
                        if ($n-- > 1)
                            $query .= ",";
                    }
                }
                $query .= " WHERE $pkey=";
                if ($this->fields[$pkey]->type == "int")
                {
                    $query .= "$pvalue ";
                }
                else
                {
                    $query .= "'$pvalue' ";
                }
                $ret = $this->dbQuery($query);
                $this->gestfiles($values, $oldvalues);
                if (!$ret)
                {
                    return sqlite_error();
                }
                $newvalues = $this->GetRecordByPk($values[$pkey]);
            }
            else
            {
                return sqlite_error();
            }
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
        if (is_array($restr) && count($restr) > 0)
        {
            $query .= " WHERE ";
            $and = "";
            foreach ($restr as $h => $v)
            {
                $query .= " $and $h LIKE '$v' ";
                $and = "AND";
            }
        }
        if (is_string($restr))
        {
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
        if (file_exists("$path/$databasename/$tablename/$unirecid/$recordkey/thumbs/$value.jpg"))
        {
            $php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : "";
            $dirname = dirname($php_self);
            if ($dirname == "/" || $dirname == "\\")
            {
                $dirname = "";
            }
            $protocol = "http://";
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
                $protocol = "https://";
            $siteurl = "$protocol" . $_SERVER['HTTP_HOST'] . $dirname;
            if (substr($siteurl, strlen($siteurl) - 1, 1) != "/")
            {
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
        if (!isset($this->maxautoincrement[$field]))
        {
            $record = $this->dbQuery("SELECT MAX(CAST($field AS Int)) AS $field FROM {$this->sqltable} WHERE $field NOT LIKE '%[a-z]%' ");
            if (!isset($record[0][$field]))
            {
                return 1;
            }
            $this->maxautoincrement[$field] = $record[0][$field];
        }
        $this->maxautoincrement[$field] = $this->maxautoincrement[$field] + 1;
        return $this->maxautoincrement[$field];
    }

}

?>