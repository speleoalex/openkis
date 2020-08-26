<?php

class CS_DB
{

    var
            $is_connected=false;
    var
            $mysqlconn=null;

    /**
     * 
     * @global type $xmldb_mysqldatabase
     * @global type $xmldb_mysqlusername
     * @global type $xmldb_mysqlpassword
     * @global type $xmldb_mysqlhost
     * @param type $database
     * @param type $host
     * @param type $username
     * @param type $password
     */
    function __construct($database="",$host="",$username="",$password="")
    {
        global $xmldb_mysqldatabase,$xmldb_mysqlusername,$xmldb_mysqlpassword,$xmldb_mysqlhost;
        $this->database=($database=== "") ? $xmldb_mysqldatabase : "$database";
        $this->host=($host=== "") ? $xmldb_mysqlhost : "$host";
        $this->password=($password=== "") ? $xmldb_mysqlpassword : "$password";
        $this->username=($username=== "") ? $xmldb_mysqlusername : "$username";
    }

    /**
     * 
     * @throws Database_Exception
     */
    function Connect()
    {
        if (!is_object($this->mysqlconn) || $this->is_connected=== false)
        {
            $this->mysqlconn=new mysqli($this->host,$this->username,$this->password,$this->database);
        }
        if ($this->mysqlconn->connect_error)
        {
            $this->is_connected=false;
            throw new Database_Exception($this->mysqli->connect_error);
        }
        else
        {
            $this->is_connected=true;
        }
    }

    /**
     * 
     */
    function StartTransaction()
    {
        $this->QueryToHandle("START TRANSACTION");
    }

    /**
     * 
     * @return type
     */
    function Commit()
    {
        return $this->QueryToHandle("COMMIT");
    }

    /**
     * 
     * @return type
     */
    function Rollback()
    {
        return $this->QueryToHandle("ROLLBACK");
    }

    /**
     * 
     * @param type $query
     * @return boolean
     */
    function QueryToArray($query)
    {
        $result=$this->QueryToHandle($query);
        if (isset($result->num_rows))
        {
            $retVal=array();
            if ($result->num_rows== 0)
            {
                if (preg_match('/^select/si',trim(ltrim($query))))
                {
                    $retVal=array();
                }
                else
                {
                    $retVal=true;
                }
            }
            else
            {
                while($tmp=$result->fetch_array(MYSQLI_ASSOC))
                {
                    $retVal[]=$tmp;
                }
            }
            $result->close();
            return $retVal;
        }
        else
        {
            return false;
        }
    }

    /**
     * 
     * @param type $query
     * @return boolean
     * @throws Database_Exception
     */
    function QueryToHandle($query)
    {
        $this->connect();
        $result=$this->mysqlconn->query($query);
        if (!$result)
        {
            throw new Database_Exception($this->mysqlconn->error,$query);
            return false;
        }
        return $result;
    }

    /**
     * 
     * @param type $query
     * @param type $rollbackOnFail
     * @param type $returnhandle
     * @param type $force_db
     * @return boolean
     */
    function Query($query,$rollbackOnFail=false,$returnhandle=false,$force_db=false)
    {
        if ($force_db)
        {
            $tmp=new CS_DB($force_db);
            $ret=$tmp->query($query,$rollbackOnFail,$returnhandle);
            $tmp->close();
            return $ret;
        }
        else
        {
            $res=$this->QueryToHandle($query);
            if (!$res)
            {
                if (!$force_db && $rollbackOnFail)
                {
                    $this->rollback();
                }
                return false;
            }
            if ($returnhandle)
            {
                return $res;
            }
        }
    }

    /**
     * 	
     */
    function Disconnect()
    {
        if (is_object($this->mysqlconn) || $this->is_connected=== true)
        {
            $this->mysqlconn->close();
        }
        $this->is_connected=false;
    }

    function LastInsertId()
    {
        $res=$this->QueryToArray("select last_insert_id() AS ID");
        return isset($res[0]['ID']) ? $res[0]['ID'] : false;
    }

   

}

/**
 * 
 */
class Database_Exception extends Exception
{

    public
            $errors;
    public
            $query;

    public
            function __construct($a,$b="")
    {
        $this->errors=$a;
        $this->query=$b;
        parent::__construct("database exception: $this->errors");
    }

}

?>