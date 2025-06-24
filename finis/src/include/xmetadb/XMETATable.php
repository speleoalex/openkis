<?php

include_once __DIR__ . "/XMETAField.php";

/**
 * Classe per la gestione dei files xml per avere funzioni
 * simili a quelle di un database.
 * I dati sono salvati in files xml con estensione .php
 * <?php exit(0);?> all' inizio del file permette che questo non venga
 * visualizzato da un accesso diretto.
 * Il sistema e' composto da un file che descrive la tabella e di uno
 * o piu' files che contengono i dati.
 *
 * ESEMPIO :
 * -----------FILE DI DESCRIZIONE-----
 *
 * /misc/plugins/stati.php
 *
 * <?php exit(0);?>
 * <tables>
 * <field>
 * <name>unirecid</name>
 * <type>string</type>
 * </field>
 * <field>
 * <name>Codice</name>
 * <type>string</type>
 * </field>
 * <field>
 * <name>Nazione</name>
 * <type>string</type>
 * </field>
 * <field>
 * <name>CodiceISO</name>
 * <type>string</type>
 * </field>
 * <driver>xmlphp</driver>
 * </tables>
 *
 * I dati vengono salvati a seconda del driver utilizzato.
 * il driver di default e' xmlphp
 *
 * --------------FILE DEI DATI xmlphp--------
 * /misc/plugins/stati/stati.php
 * <plugins>
 * <!-- Tabella stati -->
 * <stati>
 * <unirecid>MOAS200312191548500468000002</unirecid>
 * <Codice>I</Codice>
 * <Nazione>ITALIA</Nazione>
 * <en>ITALY</en>
 * <it>ITALIA</it>
 * <iva>0</iva>
 * </stati>
 * <stati>
 * <unirecid>CASH200410080948160634006779</unirecid>
 * <Codice>D</Codice>
 * <Nazione>GERMANY</Nazione>
 * <CodiceISO>DE</CodiceISO>
 * </stati>
 */
class XMETATable extends stdClass
{

    var $databasename;
    var $tablename;
    var $primarykey;
    var $filename;
    var $indexfield;
    var $connection;
    var $driverclass = false;
    var $driver = "xmlphp";
    var $fields = array();
    var $path;
    var $numrecords = -1;
    var $numrecordscache = array();
    var $usecachefile = 0;
    var $xmlfieldname;
    var $xmltagroot;
    var $pathdata = "";
    var $xmldescriptor = null;
    var $datafile = null;
    var $defaultdriver = null;
    var $siteurl;
    var $charset_page;
    var $requiredtext;
    var $charset_storage;

    static function now()
    {
        return date("Y-m-d H:i:s", time());
    }

    static function xmetadbTable($databasename, $tablename, $path = "misc", $params = array())
    {
        static $tables = array();
        if (is_array($tablename))
        {
            return new XMETATable($databasename, $tablename, $path, $params);
        }
        $assoc = is_array($params) ? md5(serialize(ksort($params))) : "";
        $id = "$databasename," . $tablename . ",$path;" . $assoc;
        if (!isset($tables[$id]))
        {
            $tables[$id] = new XMETATable($databasename, $tablename, $path, $params);
        }
        return $tables[$id];
    }

    static function createMetadbDatabase($databasename, $path = ".")
    {
        if (file_exists("$path/$databasename"))
            return "database $databasename already exists";
        if (!is_writable("$path/"))
            return "database not writable";
        mkdir("$path/$databasename");
        return false;
    }

    static function createMetadbTable($databasename, $tablename, $fields, $path = ".", $singlefilename = false)
    {
        if (!file_exists("$path/$databasename") || !is_dir("$path/$databasename"))
            return "xml databse not exists";
        if (file_exists("$path/$databasename/$tablename") && file_exists("$path/$databasename/$tablename.php"))
            return "xml table exists";
        if (!is_writable("$path/$databasename/"))
            return "xml database not writable";
        $str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?php exit(0);?>\n<tables>";
        foreach ($fields as $field)
        {
            $str .= "\n\t<field>";
            foreach ($field as $key => $value)
            {
                $str .= "\n\t\t<$key>$value</$key>";
            }
            $str .= "\n\t</field>";
        }
        if ($singlefilename != false)
        {
            if (is_array($singlefilename))
            {
                foreach ($singlefilename as $key => $values)
                {
                    $str .= "\n\t<$key>" . xmlenc($values) . "</$key>";
                }
            }
            else
            {
                $str .= "\n<filename>$singlefilename</filename>";
            }
        }
        $str .= "\n</tables>";
        if (!file_exists("$path/$databasename/$tablename"))
        {
            mkdir("$path/$databasename/$tablename");
        }
        $file = fopen("$path/$databasename/$tablename.php", "w");
        fwrite($file, $str);
        fclose($file);
        return false;
    }

    static function meteDatabaseExists($databasename, $path = ".", $conn = false)
    {
        return (file_exists("$path/$databasename"));
    }

    static function metaTableExists($databasename, $tablename, $path = ".")
    {
        return (file_exists("$path/$databasename/$tablename") && file_exists("$path/$databasename/$tablename.php"));
    }

    function __construct($databasename, $tablename, $path = "misc", $params = array())
    {
        $this->connection = false;
        $this->driverclass = false;
        $this->driver = "";
        $this->tablename = $tablename;
        $this->databasename = $databasename;
        $this->fields = array();
        $this->path = $path;
        $this->numrecords = -1;
        $this->numrecordscache = array();
        $this->usecachefile = 0;
        $this->xmlfieldname = $tablename;
        $this->xmltagroot = $this->databasename;
        $this->pathdata = "";
        $this->params = array();
        if (!empty($params['default_database_driver']))
        {
            $this->defaultdriver = $params['default_database_driver'];
        }

        if (!empty($params['default_database_driver']))
        {
            $this->defaultdriver = $params['default_database_driver'];
        }

        //if is xml
        if (is_array($tablename))
        {
            $this->xmldescriptor = $tablename['xml'];
            $fields = xmetadb_xml2array($this->xmldescriptor, "field", false);
            if (!is_array($fields))
                return false;
            if (isset($tablename['tablename']))
                $this->tablename = $tablename['tablename'];
            else
                trigger_error("tablename is not set", E_USER_ERROR);

            foreach ($fields as $field)
            {
                $xmlfield = new XMETAField($fields, $field['name']);
                $this->fields[$field['name']] = $xmlfield;
            }
        }
        else
        {
            if ($tablename == "")                
                trigger_error("tablename is empty",E_USER_ERROR);
            $this->tablename = $tablename;
            if (!file_exists("$path/$databasename/{$this->tablename}.php"))
            {
                return false;
            }
            if (!file_exists("$path/$databasename/{$this->tablename}"))
            {
                if (!is_writable("$path/$databasename/"))
                    return false;
                mkdir("$path/$databasename/{$this->tablename}");
            }
            //fix old escriptor--->
            $tmp = file_get_contents("$path/$databasename/{$this->tablename}.php");
            $this->xmldescriptor = $tmp;
            if (false !== strpos($tmp, "multilinguage"))
            {
                if (is_writable("$path/$databasename/{$this->tablename}.php"))
                {
                    $tmp = str_replace("multilinguage", "multilanguage", $tmp);
                    $h = fopen("$path/$databasename/{$this->tablename}.php", "w");
                    fwrite($h, $tmp);
                    fclose($h);
                }
            }
            $this->xmldescriptor = $tmp;
            //fix old escriptor---<
            $this->usecachefile = get_xml_single_element("usecachefile", $this->xmldescriptor);
            $this->indexfield = get_xml_single_element("indexfield", $this->xmldescriptor);
            $this->pathdata = get_xml_single_element("pathdata", $this->xmldescriptor);

            if (!file_exists("$path/$databasename/{$this->tablename}.php"))
                return false;

            //dprint_r("$path/$databasename/{$this->tablename}.php");
            $fields = xmetadb_readDatabase("$path/$databasename/{$this->tablename}.php", "field");
            if (!is_array($fields))
                return false;
            $this->primarykey = '';
            foreach ($fields as $field)
            {
                $xmlfield = new XMETAField("$path/$databasename/{$this->tablename}.php", $field['name']);
                $this->fields[$field['name']] = $xmlfield;
            }
        }
        $this->datafile = $this->path . "/" . $this->databasename . "/" . $this->tablename . "/";
        $this->xmlfieldname = $this->tablename;
        // cerca la chiave primaria
        $this->primarykey = array();
        foreach ($fields as $field)
        {
            if (isset($field['primarykey']) && $field['primarykey'] == "1")
                $this->primarykey[] = $field['name'];
        }
        if (count($this->primarykey) == 1 && isset($this->primarykey[0]))
        {
            $this->primarykey = $this->primarykey[0];
        }
        if (is_array($params))
        {
            foreach ($params as $k => $v)
            {
                $this->params[$k] = $v;
                // $this->$k = $v;
            }
        }

        $this->setDriver();
    }

    function setField($fieldname, $values)
    {
        if ($fieldname && is_array($values))
        {
            foreach ($values as $propriety_name => $value)
            {
                $this->fields[$fieldname]->$propriety_name = $value;
            }
        }
    }

    function setDriver($drivertype = "")
    {
        $this->driver = $drivertype;
        //modalita' database---->
        if (!$this->driver)
        {
            $this->driver = get_xml_single_element("driver", $this->xmldescriptor);
        }
        if ($this->driver == "" && $this->defaultdriver != "")
        {
            $this->driver = $this->defaultdriver;
        }
        if ($this->driver == "")
        {
            $this->driver = "xmlphp";
        }



        if (file_exists(__DIR__ . "/XMETATable_{$this->driver}.php"))
        {
            include_once(__DIR__ . "/XMETATable_{$this->driver}.php");
        }

        $classname = "XMETATable_" . $this->driver;

        if (!class_exists($classname))
        {
            trigger_error("xmetadberror: $classname not exists in table {$this->tablename}", E_USER_ERROR);
        }
        $this->driverclass = new $classname($this, $this->params);
        if (!is_object($this->driverclass))
            trigger_error("xmetadberror: $this->proprieties = array();>driverclass", E_USER_ERROR);
        $this->sendFileToClient();
    }

    function sendFileToClient()
    {
        $unirecid = FN_GetParam("unirecid", $_REQUEST);
        $recordkey = FN_GetParam("recordkey", $_REQUEST);
        $uid = FN_GetParam("uid", $_REQUEST);
        $xmetadbgetfile = FN_GetParam("xmetadbgetfile", $_REQUEST);
        if (!$xmetadbgetfile || $recordkey == "" || $uid == "" || $unirecid === "")
        {
            return;
        }
        $databasename = $this->databasename;
        $path = realpath($this->path);
        $recordvalues = $this->GetRecordByPrimaryKey($unirecid);
        $filename = $recordvalues[$recordkey];
        $value = isset($recordvalues[$recordkey]) ? $recordvalues[$recordkey] : null;
        $tablepath = $this->FindFolderTable($recordvalues);
        if ($value != "")
        {
            if (!empty($recordvalues[$recordkey . "_base64data"]))
            {
                if ($uid === md5($recordvalues[$recordkey . "_base64data"]))
                {
                    $filecontents = base64_decode($recordvalues[$recordkey . "_base64data"]);
                    FN_SaveFile($filecontents, $filename);
                    return;
                }
            }
            if (file_exists("$path/$databasename/$tablepath/$unirecid/$recordkey/$value"))
            {
                $filecontents = file_get_contents("$path/$databasename/$tablepath/$unirecid/$recordkey/$value");
                if (md5($filecontents) === $uid)
                {
                    FN_SaveFile($filecontents, $filename);
                    return;
                }
            }
        }
    }

    function getFilePath($recordvalues, $recordkey)
    {
        if ($recordkey == "")
        {
            return false;
        }
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = realpath($this->path);
        $unirecid = $recordvalues[$this->primarykey];
        if (!isset($recordvalues[$recordkey]))
        {
            $recordvalues = $this->GetRecord($recordvalues);
        }

        $value = isset($recordvalues[$recordkey]) ? $recordvalues[$recordkey] : null;
        $tablepath = $this->FindFolderTable($recordvalues);
        if ($value != "")
        {
            if (!empty($recordvalues[$recordkey . "_base64data"]))
            {
                $uid = md5($recordvalues[$recordkey . "_base64data"]);
                return "?xmetadbgetfile=1&unirecid=$unirecid&recordkey=$recordkey&uid=$uid";
            }
            // dprint_r($this->path . "/$databasename/$tablepath/$unirecid/$recordkey/" . $value);
            return $this->path . "/$databasename/$tablepath/$unirecid/$recordkey/" . $value;
        }
        return false;
    }

    function getThumbPath($recordvalues, $recordkey)
    {
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = realpath($this->path);
        $ret = "";
        $unirecid = $recordvalues[$this->primarykey];
        if (!isset($recordvalues[$recordkey]))
            $recordvalues = $this->GetRecord($recordvalues);
        $value = $recordvalues[$recordkey];
        $tablepath = $this->FindFolderTable($recordvalues);
        if (file_exists("$path/$databasename/$tablepath/$unirecid/$recordkey/thumbs/$value.jpg"))
        {
            return $this->path . "/$databasename/$tablepath/$unirecid/$recordkey/thumbs/$value.jpg";
        }
        return $this->getFilePath($recordvalues, $recordkey);
    }

    //-----metodi del driver---------------->

    function get_file($recordvalues, $recordkey)
    {
        $php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : "";
        $dirname = dirname($php_self);
        if ($dirname == "/" || $dirname == "\\")
            $dirname = "";
        $protocol = "http://";
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
            $protocol = "https://";
        $siteurl = "$protocol" . $_SERVER['HTTP_HOST'] . $dirname;
        if (substr($siteurl, strlen($siteurl) - 1, 1) != "/")
        {
            $siteurl = $siteurl . "/";
        }
        $file = $this->getFilePath($recordvalues, $recordkey);
        if ($file && $file[0] == "?")
        {
            return "$siteurl" . $file;
        }

        if ($file && file_exists($file))
        {
            return "$siteurl" . $file;
        }
        return false;
    }

    function get_thumb($recordvalues, $recordkey)
    {
        $file = $this->getThumbPath($recordvalues, $recordkey);

        if ($file && file_exists($file))
        {
            $php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : "";
            $dirname = dirname($php_self);
            if ($dirname == "/" || $dirname == "\\")
                $dirname = "";
            $protocol = "http://";
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
                $protocol = "https://";
            $siteurl = "$protocol" . $_SERVER['HTTP_HOST'] . $dirname;
            if (substr($siteurl, strlen($siteurl) - 1, 1) != "/")
            {
                $siteurl = $siteurl . "/";
            }
            return "$siteurl" . $file;
        }
        return false;
    }

    function SetFile($key, $filepath, $filename = "")
    {
        if (!file_exists($filepath))
        {
            dprint_r("$filepath not exists");
        }
        $_FILES[$key]['tmp_name'] = realpath($filepath);

        if ($filename == "")
            $filename = basename($filepath);
        $_FILES[$key]['name'] = $filename;
    }

    //-----metodi del driver---------------->
    function GetNumRecords($restr = null)
    {
        return $this->driverclass ? $this->driverclass->GetNumRecords($restr) : null;
    }

    function GetRecords($restr = false, $min = false, $length = false, $order = false, $reverse = false, $fields = false)
    {

        return $this->driverclass ? $this->driverclass->GetRecords($restr, $min, $length, $order, $reverse, $fields) : null;
    }

    function GetRecord($restr = false)
    {

        return $this->driverclass ? $this->driverclass->GetRecord($restr) : null;
    }

    function GetRecordByPrimaryKey($unirecid)
    {
        return $this->driverclass ? $this->driverclass->GetRecordByPrimaryKey($unirecid) : null;
    }

    function GetAutoincrement($field)
    {
        return $this->driverclass ? $this->driverclass->GetAutoincrement($field) : null;
    }

    function InsertRecord($values)
    {
        if (defined("XMETADB_DEBUG_FILE_LOG") && XMETADB_DEBUG_FILE_LOG)
        {
            file_put_contents(XMETADB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " {$this->tablename}" . "\n", FILE_APPEND);
            if ($this->tablename == "fn_settings")
            {
                file_put_contents(XMETADB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " values: " . json_encode($values) . "\n", FILE_APPEND);
            }
        }
        $this->SetLastUpdateTime();
        return $this->driverclass ? $this->driverclass->InsertRecord($values) : null;
    }

    function SetLastUpdateTime()
    {
        @touch("{$this->path}/{$this->databasename}/{$this->tablename}/updated");
    }

    /**
     * 
     * @return type
     */
    function GetLastUpdateTime()
    {
        if (file_exists("{$this->path}/{$this->databasename}/{$this->tablename}/updated"))
        {
            return (filectime("{$this->path}/{$this->databasename}/{$this->tablename}/updated"));
        }
        else
        {
            return (filectime("{$this->path}/{$this->databasename}/{$this->tablename}.php"));
        }
    }

    function DelRecord($pkvalue)
    {
        if (defined("XMETADB_DEBUG_FILE_LOG") && XMETADB_DEBUG_FILE_LOG)
        {
            file_put_contents(XMETADB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " {$this->tablename}" . "\n", FILE_APPEND);
            if ($this->tablename == "fn_settings")
            {
                file_put_contents(XMETADB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " value: $pkvalue\n", FILE_APPEND);
            }
        }
        $this->SetLastUpdateTime();
        return $this->driverclass ? $this->driverclass->DelRecord($pkvalue) : null;
    }

    function GetFileRecord($pkey, $pvalue)
    {
        return $this->driverclass ? $this->driverclass->GetFileRecord($pkey, $pvalue) : null;
    }

    function Truncate()
    {
        return $this->driverclass ? $this->driverclass->Truncate() : null;
    }

    function GetRecordByPk($pvalue)
    {
        return $this->driverclass ? $this->driverclass->GetRecordByPk($pvalue) : null;
    }

    function UpdateRecordBypk($values, $pkey, $pvalue)
    {
        if (defined("XMETADB_DEBUG_FILE_LOG") && XMETADB_DEBUG_FILE_LOG)
        {
            file_put_contents(XMETADB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " {$this->tablename}" . "\n", FILE_APPEND);
            if ($this->tablename == "fn_settings")
            {
                file_put_contents(XMETADB_DEBUG_FILE_LOG, FN_Now() . " " . __METHOD__ . " values: " . json_encode($values) . "\n", FILE_APPEND);
            }
        }
        $this->SetLastUpdateTime();
        return $this->driverclass ? $this->driverclass->UpdateRecordBypk($values, $pkey, $pvalue) : null;
    }

    function UpdateRecord($values, $pkvalue = false)
    {
        if (is_array($this->primarykey))
        {
            if ($pkvalue && !is_array($pkvalue))
                return false;
            if ($pkvalue !== false)
                $unirecid = $pkvalue;
            else
            {
                $unirecid = array();
                foreach ($this->primarykey as $pkk)
                {
                    $unirecid[$pkk] = $values[$this->$pkk];
                }
            }
        }
        else
        {
            if (!isset($values[$this->primarykey]) && $pkvalue === false)
                return false;
            if ($pkvalue !== false)
                $unirecid = $pkvalue;
            else
                $unirecid = $values[$this->primarykey];
        }

        return $this->UpdateRecordBypk($values, $this->primarykey, $unirecid);
    }

    //-----metodi del driver----------------<
    function FindFolderTable($oldvalues)
    {

        if (!isset($oldvalues[$this->primarykey]))
        {
            return false;
        }
        $id = $oldvalues[$this->primarykey];
        $key = $this->primarykey;
        $databasename = $this->databasename;
        $dirtable_oldvalue = $this->tablename;
        if ($this->pathdata)
            $dirtable_oldvalue = $this->pathdata;

        $path = realpath($this->path);
        $found = false;
        $notexists = false;
        //-----------------first folder---------------------------------------->
        $oldfileimage = "$path/$databasename/$dirtable_oldvalue/$id";
        //dprint_r($oldfileimage);
        if (file_exists($oldfileimage))
        {
            return $dirtable_oldvalue;
        }
        //-----------------first folder----------------------------------------<
        $i = 1;
        $ret = $dirtable_oldvalue;
        $max = count(glob("$path/$databasename/*"));
        while ($i < $max)
        {
            $tmp = explode(".", $dirtable_oldvalue);
            $dirtable_oldvalue = $tmp[0] . ".$i";
            $oldfileimage = "$path/$databasename/$dirtable_oldvalue/$id/";
            if (file_exists($oldfileimage))
            {
                $ret = $dirtable_oldvalue;
            }
            $i++;
        }

        return $ret;
    }

    function Copy($s, $d)
    {
        global $_FN;
        if (!file_exists($s) || is_dir($s))
        {
            return false;
        }
        $contents = file_get_contents($s);
        if (is_dir($d))
        {
            $d .= $_FN['slash'] . basename($s);
        }
        //dprint_r($s." ".$d);
        $h = fopen($d, "wb");
        if ($h === false)
            return false;
        fwrite($h, $contents);
        fclose($h);
        if ($contents != file_get_contents($d))
        {
            @unlink($d);
            return false;
        }
        return true;
    }

    /**
     * gestfiles
     * Gestione ei files ricevuti per post
     * @param array $values
     */
    function gestfiles($values, $oldvalues = null)
    {

        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = realpath($this->path);
        $newvalues = $values;
        //----gestione campi d tipo FILES o IMAGE
        if (is_array($this->primarykey) || !isset($newvalues[$this->primarykey]))
            return;
        $unirecid = $newvalues[$this->primarykey];
        $dirtable_new = false;
        if (isset($oldvalues[$this->primarykey]) && $oldvalues[$this->primarykey] != $values[$this->primarykey])
        {
            $dirtable = $this->FindFolderTable($oldvalues);
            if (false !== $dirtable)
            {
                if (file_exists("$path/$databasename/$dirtable/" . $oldvalues[$this->primarykey]))
                {
                    rename("$path/$databasename/$dirtable/" . $oldvalues[$this->primarykey], "$path/$databasename/$dirtable/" . $values[$this->primarykey]);
                    $dirtable_new = $this->FindFolderTable($oldvalues);
                }
            }
        }
        elseif (isset($oldvalues[$this->primarykey]))
        {
            $dirtable_new = $this->FindFolderTable($oldvalues);
        }
        if ($dirtable_new === false)
        {
            $i = 1;
            $maxfiles = intval(_MAX_FILES_PER_FOLDER);
            $dirtable_new = $tablename;
            if ($this->pathdata)
                $dirtable_oldvalue = $this->pathdata;

            while (file_exists("$path/$databasename/$dirtable_new") && count(glob("$path/$databasename/$dirtable_new/*")) >= $maxfiles)
            {
                $tmp = explode(".", $dirtable_new);
                $dirtable_new = $tmp[0] . ".$i";
                $i++;
            }
        }


        //die ($dirtable_new);
        foreach ($newvalues as $key => $value)
        {
            $type = isset($this->fields[$key]) ? $this->fields[$key] : null;
            if (isset($type->type) && ($type->type == 'file' || $type->type == 'image'))
            {
                //cancello i vecchi record se esiste il nuovo
                $dirtable_oldvalue = false;
                if (isset($values[$this->primarykey]))
                {

                    if (isset($_FILES[$key]['tmp_name']) && $_FILES[$key]['tmp_name'] != "" && $oldvalues != null && isset($values[$key])) // se e' un aggiornamento
                    {
                        //find folder--->
                        $dirtable_oldvalue = $this->FindFolderTable($values);
                        if ($dirtable_oldvalue == false)
                            $dirtable_oldvalue = $tablename;
                        //find folder---<
                    }
                    if (!empty($values[$this->primarykey]) && !empty($oldvalues[$key]))
                    {

                        $oldfileimage = "$path/$databasename/$dirtable_oldvalue/" . $values[$this->primarykey] . "/" . $key . "/" . $oldvalues[$key];
                        $oldfilethumb = "$path/$databasename/$dirtable_oldvalue/" . $values[$this->primarykey] . "/" . $key . "/thumbs/" . $oldvalues[$key] . ".jpg";
                        if ($dirtable_oldvalue != false && $oldvalues[$key] != "" && file_exists($oldfilethumb))
                        {
                            unlink($oldfilethumb);
                        }
                        if ($dirtable_oldvalue != false && $oldvalues[$key] != "" && file_exists($oldfileimage))
                        {
                            unlink($oldfileimage);
                        }

                        // cancellazione di un record
                        if (isset($_POST["__isnull__$key"]) && $_POST["__isnull__$key"] == "null")
                        {
                            if (isset($this->fields[$key . "_base64data"]))
                            {
                                $values[$key . "_base64data"] = "";
                                $r = $this->UpdateRecord(array("{$this->primarykey}" => $values[$this->primarykey], $key . "_base64data" => $values[$key . "_base64data"]));
                            }

                            $dirtable_oldvalue = $this->FindFolderTable($values);
                            $oldfileimage = "$path/$databasename/$dirtable_oldvalue/" . $values[$this->primarykey] . "/" . $key . "/" . $oldvalues[$key];
                            $oldfilethumb = "$path/$databasename/$dirtable_oldvalue/" . $values[$this->primarykey] . "/" . $key . "/thumbs/" . $oldvalues[$key] . ".jpg";
                            if ($oldvalues[$key] != "" && file_exists($oldfilethumb))
                            {
                                unlink($oldfilethumb);
                                rmdir(dirname($oldfilethumb));
                            }
                            if ($oldvalues[$key] != "" && file_exists($oldfileimage))
                            {
                                unlink($oldfileimage);
                                rmdir(dirname($oldfileimage));
                            }
                        }
                    }
                }
                if (isset($_FILES[$key]['tmp_name']) && $_FILES[$key]['tmp_name'] != "")
                {
                    $name_clean = $_FILES["$key"]['name'];
                    if (ini_get('magic_quotes_gpc') == 1)
                    {
                        $name_clean = stripslashes($_FILES["$key"]['name']);
                    }
                    $name_clean = str_replace("\\", "", $name_clean);
                    $name_clean = str_replace("/", "", $name_clean);

                    //die ($name_clean);
                    if (preg_match('/.php/is', $name_clean) || preg_match('/.php3/is', $name_clean) || preg_match('/.php4/is', $name_clean) || preg_match('/.php5/is', $name_clean) || preg_match('/.phtml/is', $name_clean))
                    {
                        touch("$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean);
                    }
                    else
                    {
                        if (isset($this->fields[$key . "_base64data"]))
                        {
                            $values[$key . "_base64data"] = base64_encode(file_get_contents($_FILES[$key]['tmp_name']));
                            $r = $this->UpdateRecord(array("{$this->primarykey}" => $values[$this->primarykey], $key . "_base64data" => $values[$key . "_base64data"]));
                        }
                        else
                        {

                            if (!file_exists("$path/$databasename/$dirtable_new/"))
                            {
                                mkdir("$path/$databasename/$dirtable_new/");
                            }
                            if (!file_exists("$path/$databasename/$dirtable_new/$unirecid"))
                            {
                                mkdir("$path/$databasename/$dirtable_new/$unirecid");
                            }
                            if (!file_exists("$path/$databasename/$dirtable_new/$unirecid/$key"))
                            {
                                mkdir("$path/$databasename/$dirtable_new/$unirecid/$key");
                            }

                            //workarround: alla insert non funziona move_uploaded_file
                            //se elimino il file temporaneo non funziona nemmeno copy  
                            if ($oldvalues)
                            {
                                move_uploaded_file($_FILES[$key]['tmp_name'], "$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean);
                                if (!file_exists("$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean))
                                {
                                    $this->Copy($_FILES[$key]['tmp_name'], "$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean);
                                }
                                if (!file_exists("$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean))
                                {
                                    trigger_error("failed copy {$_FILES[$key]['tmp_name']} to " . "$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean, E_USER_WARNING);
                                }
                            }
                            else
                            {

                                $tmpname = $_FILES[$key]['tmp_name'];
                                FN_Copy($tmpname, "$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean);
                                if (!file_exists("$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean))
                                {
                                    trigger_error("failed copy {$_FILES[$key]['tmp_name']} to " . "$path/$databasename/$dirtable_new/$unirecid/$key/" . $name_clean, E_USER_WARNING);
                                }
                            }
                            $create_thumb[$key] = true;
                        }
                    }
                }
            }
        }
        //---------------- creazione anteprime per le immagini ----------------------

        foreach ($this->fields as $field)
        {
            switch ($field->type)
            {
                case "image":
                    if (isset($values[$field->name]) && $values[$field->name] != "") // se il campo e' stato aggiornato
                    {
                        $dirtable = $dirtable_new;
                        if ($this->pathdata)
                            $dirtable = $this->pathdata;

                        $fileimage = isset($values[$this->primarykey]) ? "$path/$databasename/$dirtable/" . $values[$this->primarykey] . "/" . $field->name . "/" . $values[$field->name] : "";
                        $filethumb = isset($values[$this->primarykey]) ? "$path/$databasename/$dirtable/" . $values[$this->primarykey] . "/" . $field->name . "/thumbs/" . $values[$field->name] . ".jpg" : false;
                        if (file_exists($fileimage) && (isset($create_thumb[$key]) || ($filethumb && !file_exists($filethumb))))
                        {
                            $size = isset($field->thumbsize) ? $field->thumbsize : 22;
                            $size_w = isset($field->thumbsize_w) ? $field->thumbsize_w : "";
                            $size_h = isset($field->thumbsize_h) ? $field->thumbsize_h : "";
                            if ($size < 16)
                                $size = 16;
                            xmetadb_create_thumb($fileimage, $size, $size_h, $size_w);
                        }
                    }
                    break;
            }
        }
    }
}
