<?php

/**
 * driver xmlphp per Xmltable
 *
 */
class XMETATable_xmlphp extends stdClass
{

    var $databasename;
    var $tablename;
    var $primarykey;
    var $filename;
    var $indexfield;
    var $fields;
    var $xmltable;
    var $path;
    var $numrecords;
    var $usecachefile;
    var $xmldescriptor;
    var $xmlfieldname;
    var $datafile;
    var $xmltagroot;
    var $defaultdriver;
    var $driver;
    var $siteurl;
    var $charset_page;
    var $requiredtext;
    var $cache_filerecord;
    var $charset_storage;
    var $numrecordscache;

    function __construct(&$xmltable, $params = false)
    {
        $this->xmltable = &$xmltable;
        $this->tablename = &$xmltable->tablename;
        $this->databasename = &$xmltable->databasename;
        $this->fields = &$xmltable->fields;
        $this->path = &$xmltable->path;
        $this->numrecords = &$xmltable->numrecords;
        $this->usecachefile = &$xmltable->usecachefile;
        $this->filename = &$xmltable->filename;
        $this->indexfield = &$xmltable->indexfield;
        $this->primarykey = &$xmltable->primarykey;
        $this->driver = &$xmltable->driver;
        $this->xmldescriptor = &$xmltable->xmldescriptor;
        $this->xmlfieldname = &$xmltable->xmlfieldname;
        $this->datafile = &$xmltable->datafile;
        $this->xmltagroot = &$xmltable->xmltagroot;
        //propriera' relative a i file xml
        $path = $this->path;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        // dati su singolo file
        $this->filename = get_xml_single_element("filename", $this->xmldescriptor);
        if (is_array($params))
        {
            foreach ($params as $k => $v)
            {
                // if (isset($this->$k))
                {
                    $this->$k = $v;
                }
            }
        }
        //dprint_r($this->datafile);
        return true;
    }

    /**
     * GetNumRecords
     * Torna il numero di records
     */
    function GetNumRecords($restr = null)
    {
        $cacheid = $restr;
        if (is_array($restr))
            $cacheid = implode("|", $restr);
        if ($restr == null)
            $cacheid = " ";
        $cacheid = md5($cacheid);
        if (isset($this->numrecordscache[$cacheid]))
        {
            return $this->numrecordscache[$cacheid];
        }
        $c = count($this->GetRecords($restr, false, false, false, false, $this->primarykey));
        $this->numrecordscache[$cacheid] = $c;
        if ($restr == null)
            $this->numrecords = $c;
        return $c;
    }

    function ClearCachefile()
    {
        if ($this->usecachefile != 1)
            return;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        $files = glob($cachefile = "$path/" . $databasename . "/cache/$tablename*");
        if (is_array($files))
            foreach ($files as $file)
            {
                @unlink($file);
            }
    }

    /**
     * GetRecords
     * recupera tutti i records
     */
    function GetRecords($restr = false, $min = false, $length = false, $order = false, $reverse = false, $fields = false)
    {

        $ret = array();
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        $fieldname = $this->xmlfieldname;
        if ($order && !isset($this->fields[$order]))
        {
            $order = false;
        }

        if (is_array($fields))
        {
            $fields = implode("|", $fields);
        }
        $tmf = "";
        if ($fields != false && is_array($restr))
        {
            foreach ($restr as $key => $value)
                $fields .= "|$key";
        }
        $rc = $restr;
        if (is_array($restr))
            $rc = implode("|", $restr);
        if ($restr && is_string($restr))
        {
            die("TODO xmetadb: not yet implemented function for this driver");
        }

        //cache su file---->
        if ($this->usecachefile == 1)
        {
            $cacheindex = $rc . $min . $length . $order . $reverse . $fields;
            if (!file_exists("$path/" . $databasename . "/cache"))
                mkdir("$path/" . $databasename . "/cache");
            $cachefile = "$path/" . $databasename . "/cache/" . $tablename . "." . md5($cacheindex) . ".cache";
            if (file_exists($cachefile))
            {
                $ret = file_get_contents($cachefile);
                $ret = @unserialize($ret);
                //dprint_r("[$cachefile]");
                //dprint_r ($ret);
                if ($ret !== false)
                    return $ret;
            }
        }
        //cache su file----<
        // filtro i field che non sono associati alla tabella
        if ($fields === false)
        {
            $fields = array();
            foreach ($this->fields as $v)
            {
                $fields[] = $v->name;
            }
            $fields = implode("|", $fields);
        }
        $all = xmetadb_readDatabase($this->datafile, $fieldname, $fields, false);

        if ($all === false) //il file non esiste
        {
            return array();
        }
        if ($all === null) //errore lettura
        {
            return null;
        }
        //se il campo manca lo forzo a default oppure null
        foreach ($all as $k => $r)
        {
            foreach ($this->fields as $field)
            {
                if (!isset($r[$field->name]))
                    $r[$field->name] = isset($this->fields[$field->name]->defaultvalue) ? $this->fields[$field->name]->defaultvalue : null;
            }
            $all[$k] = $r;
        }
        if (is_array($restr))
        {
            $ret = array();
            foreach ($all as $r)
            {
                $ok = true;
                foreach ($restr as $key => $value)
                {
                    //-----%xxx%------>
                    if (isset($restr[$key]) && preg_match("/^%/s", $restr[$key]) && preg_match('/%$/s', $restr[$key]))
                    {

                        $t = xmetadb_encode_preg(substr($restr[$key], 1, strlen($restr[$key]) - 2));
                        if (preg_match("/$t/is", $r[$key]) == false)
                        {
                            $ok = false;
                            break;
                        }
                    }
                    elseif (isset($restr[$key]) && preg_match("/%" . '$/is', $restr[$key]))
                    {

                        $t = xmetadb_encode_preg(substr($restr[$key], 0, strlen($restr[$key]) - 1));
                        if (preg_match("/^$t/is", $r[$key]) == false)
                        {
                            $ok = false;
                            break;
                        }
                    }
                    elseif (isset($restr[$key]) && preg_match("/^%/is", $restr[$key]))
                    {
                        $t = xmetadb_encode_preg(substr($restr[$key], 1));

                        if (preg_match("/" . $t . '$/is', $r[$key]) == false)
                        {
                            $ok = false;
                            break;
                        }
                    }
                    //-----%xxx%------<
                    else
                    {
                        if (!isset($r[$key]) || $r[$key] != $restr[$key])
                        {
                            $ok = false;
                            break;
                        }
                    }
                }
                if ($ok == true)
                {
                    $ret[] = $r;
                }
            }
        }
        else
            $ret = $all;
        //ordinamento dei records ------>

        if ($order !== false && $order !== "" && /*  isset($this->fields[$order]) && */ is_array($ret))
        {
            $ret = xmetadb_array_sort_by_key($ret, $order);

            /*
              $newret=array();
              foreach($ret as $key=> $value)
              {
              if (isset($value[$order]))
              {
              $i=0;
              $r=$value[$order]."0";
              while(isset($newret[$r.$i]))
              {
              $i++;
              }
              $newret["$r"."$i"]=$ret[$key];
              }
              else
              {
              $i=0;
              $r="";
              while(isset($newret[$r.$i]))
              {
              $i++;
              }
              $newret["$r"."$i"]=$ret[$key];
              }
              }
              ksort($newret);
              $ret=$newret;
             * */
        }
        if ($reverse)
        {
            $ret = array_reverse($ret);
        }
        //ordinamento dei records ------<
        // minimo e massimo
        if ($min != false && $length != false)
            $ret = array_slice($ret, $min - 1, $length);
        $ret = array_values($ret);
        //cache su file---->
        if ($this->usecachefile == 1)
        {
            $cachestring = serialize($ret);
            $fp = fopen($cachefile, "wb");
            fwrite($fp, $cachestring);
            fclose($fp);
        }
        //cache su file----<

        return $ret;
    }

    /**
     * GetRecord
     * recupera un singolo record
     *
     * @param array restrizione
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
     * GetRecordByUnirecid
     *
     * Torna un record in formato array partendo dall' unirecid (nomefile)
     * */
    function GetRecordByPrimaryKey($unirecid)
    {
        return $this->GetRecordByPk($unirecid);
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
        /*
          if (isset($this->maxautoincrement[$field]))
          {
          //dprint_r("Xai=".($this->maxautoincrement[$field] + 1));
          return $this->maxautoincrement[$field] + 1;
          }
         */
        $records = $this->GetRecords();
        $max = 0;
        $contrec = 0;
        if (is_array($records))
        {
            foreach ($records as $rec)
            {
                $contrec++;
                if (isset($rec[$field]) && intval($rec[$field]) > intval($max))
                    $max = intval($rec[$field]);
            }
        }
        $this->numrecords = $contrec;
        //		return ($max + 1).uniqid(".");
        return $max + 1;
    }

    /**
     * InsertRecord
     * Aggiunge un record
     *
     * @param array $values
     * */
    function InsertRecord($values)
    {
        //dprint_r($values);
        $this->numrecords = -1;
        $this->numrecordscache = array();
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        for ($tlock = time(); $this->dbIsLocked();)
        {
            if (time() - $tlock > _MAX_LOCK_TIME)
            {
                $this->dbunlock();
                return "error:table is locked";
            }
            usleep(rand(1, 500));
        }
        if (!$this->dblock())
        {
            return "error:table lock failed";
        }
        if (isset($values[$this->primarykey]))
        {
            $t = $this->GetRecordByPrimaryKey($values[$this->primarykey]);
            if (is_array($t))
            {
                $this->dbunlock();
                return "error:there is already a record with this primary key {$this->primarykey}={$values[$this->primarykey]}";
            }
        }
        foreach ($this->fields as $f)
        {
            if (!isset($values[$f->name]) || (isset($values[$f->name]) && $values[$f->name] == ""))
            {
                if (isset($this->fields[$f->name]->extra) && $this->fields[$f->name]->extra == "autoincrement")
                {
                    $newid = $this->GetAutoincrement($f->name);
                    $values[$f->name] = $newid;
                    $this->maxautoincrement[$f->name] = $newid;
                }
            }
            if ((!isset($values[$f->name]) || $values[$f->name] === null) && (isset($this->fields[$f->name]->defaultvalue) && $this->fields[$f->name]->defaultvalue != ""))
            {
                $dv = $this->fields[$f->name]->defaultvalue;
                $fname = $f->name;
                $rv = "";
                eval("\$rv=\"$dv\";");
                $rv = str_replace("\\", "\\\\", $rv);
                $rv = str_replace("'", "\\'", $rv);
                eval("\$values" . "['$fname'] = '$rv' ;");
            }
        }
        if (!isset($values[$this->primarykey]) || $values[$this->primarykey] == "")
        {
            $this->dbunlock();
            return "error:missing the primary key in table  $tablename";
        }
        // cerco il file da modificare o creare----->
        if (!preg_match("/\\/$/si", $this->datafile)) //datafile
            $xmltowritefullpath = $this->datafile;
        else
        {
            $unirecid = urlencode($values[$this->primarykey]);
            $xmltowritefullpath = "{$this->datafile}" . $unirecid . ".php"; //default
            if ($this->filename != "")
            {
                $xmltowritefullpath = "{$this->datafile}" . urlencode($this->filename) . ".php"; //filename
            }
            if ($this->indexfield != "" && isset($values[$this->indexfield]))
            {
                $xmltowritefullpath = "{$this->datafile}" . urlencode($values[$this->indexfield]) . ".php"; //indexfield
            }
        }
        // cerco il file da modificare o creare-----<
        // se esiste gia'
        if (file_exists($xmltowritefullpath))
        {
            $readok = false;
            for ($i = 0; $i < _MAX_FILE_ACCESS_ATTEMPTS; $i++)
            {
                $oldfilestring = file_get_contents($xmltowritefullpath);
                if (strpos($oldfilestring, "</{$this->xmltagroot}") !== false)
                {
                    $readok = true;
                    break;
                }
            }
            if (!$readok)
            {
                $this->dbunlock();
                return "error:insert record db is locked";
            }
            $str = "\t<{$this->xmlfieldname}>";
            foreach ($this->fields as $field)
            {
                $valtowrite = isset($values[$field->name]) ? $values[$field->name] : "";
                $valtowrite = xmlenc("$valtowrite");
                $str .= "\n\t\t<" . $field->name . ">" . $valtowrite . "</" . $field->name . ">";
            }
            $str .= "\n\t</{$this->xmlfieldname}>\n</{$this->xmltagroot}>";
            $newfilestring = preg_replace('/<\/' . $this->xmltagroot . '>$/s', xmetadb_encode_preg_replace2nd($str), trim(ltrim($oldfilestring)));
            if (file_exists("$xmltowritefullpath") && !is_writable("$xmltowritefullpath"))
            {
                $this->dbunlock();
                return "error:not file writable";
            }
            $handle = fopen($xmltowritefullpath, "w");
            fwrite($handle, $newfilestring);
            fclose($handle);
        }
        else
        {
            $str = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?php exit(0);?>";
            $str .= "\n<{$this->xmltagroot}>\n\t<{$this->xmlfieldname}>";
            foreach ($this->fields as $field)
            {
                $valtowrite = isset($values[$field->name]) ? $values[$field->name] : "";
                $valtowrite = xmlenc("$valtowrite");
                $str .= "\n\t\t<" . $field->name . ">" . $valtowrite . "</" . $field->name . ">";
            }
            $str .= "\n\t</{$this->xmlfieldname}>\n</{$this->xmltagroot}>";
            if (file_exists("$xmltowritefullpath") && !is_writable("$xmltowritefullpath"))
            {
                $this->dbunlock();
                return false;
            }
            if (!file_exists(dirname("$xmltowritefullpath")))
                mkdir(dirname("$xmltowritefullpath"));
            $handle = fopen($xmltowritefullpath, "w");
            fwrite($handle, $str);
            fclose($handle);
        }
        $this->xmltable->gestfiles($values);
        $this->dbunlock();
        $this->ClearCachefile();
        xmetadb_readDatabase($xmltowritefullpath, $this->xmlfieldname, false, false);
        return $values;
    }

    /**
     * 
     * @return type
     */
    function dbIsLocked()
    {
        if ($this->tablename == "____empty_____")
            return false;
        if (file_exists("{$this->path}/{$this->databasename}/{$this->tablename}/lock"))
        {
            if (!empty($_GET['debug']))
            {
                die("table is locked " . $this->tablename);
            }

            return true;
        }
        return false;
    }

    /**
     * 
     * @return type
     */
    function dblock()
    {
        if ($this->tablename == "____empty_____")
            return true;
        if (false !== ($fp = @fopen("{$this->path}/{$this->databasename}/{$this->tablename}/lock", "x")))
        {
            fclose($fp);
            return true;
        }
        return false;
    }

    /**
     * 
     * @return type
     */
    function dbunlock()
    {
        if ($this->tablename == "____empty_____")
        {
            return true;
        }
        $r = unlink("{$this->path}/{$this->databasename}/{$this->tablename}/lock");
        return $r;
    }

    /**
     * elimina tutti i dati da una tabella
     */
    function Truncate()
    {
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        $this->numrecords = -1;
        $this->numrecordscache = array();
        if ($tablename == "____empty_____")
        {
            if (!empty($this->datafile))
                unlink($this->datafile);
        }
        $oldfiles = glob("$path/$databasename/$tablename/*.php");
        xmetadb_remove_dir_rec("$path/$databasename/$tablename");
        $this->ClearCachefile();
        foreach ($oldfiles as $oldfile)
            xmetadb_readDatabase($oldfile, $this->xmlfieldname, false, false);
        return true;
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
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        $this->numrecords = -1;
        $this->numrecordscache = array();
        $oldfile = $this->GetFileRecord($this->primarykey, $pkvalue);
        $dirold = dirname($oldfile) . "/" . basename($oldfile, ".php");
        if (!file_exists($oldfile))
            return false;
        if (preg_match("/\\/$/si", $this->datafile))
            if (!strpos($pkvalue, "..") !== false && file_exists("{$this->datafile}$pkvalue/") && is_dir("{$this->datafile}$pkvalue/"))
                xmetadb_remove_dir_rec("{$this->datafile}$pkvalue");
        $this->ClearCachefile();
        $n = xmetadb_readDatabase($oldfile, $this->xmlfieldname, false, false);
        // se e' l' ultimo record
        if (is_array($n) && count($n) == 1)
        {
            if (preg_match("/\\/$/si", $this->datafile))
            {
                @unlink($oldfile);
                if (file_exists($oldfile) && is_dir($oldfile))
                {
                    xmetadb_remove_dir_rec($oldfile);
                }
            }
            xmetadb_readDatabase($oldfile, $this->xmlfieldname, false, false);
            return true;
        }
        $pkey = $this->primarykey;
        $pvalue = $pkvalue;
        $readok = false;
        for ($i = 0; $i < _MAX_FILE_ACCESS_ATTEMPTS; $i++)
        {
            if (!file_exists($oldfile)) //errore
                break;
            $oldfilestring = file_get_contents("$oldfile");
            if (strpos($oldfilestring, "</{$this->xmltagroot}>") !== false)
            {
                $readok = true;
                break;
            }
        }
        if (!$readok)
        {
            return false;
        }
        $oldfilestring = xmetadb_removexmlcomments($oldfilestring);
        $strnew = "";
        $newfilestring = preg_replace('/<' . $this->xmlfieldname . '>([^(' . $this->xmlfieldname . ')]*)<' . $pkey . '>' . $pvalue . '<\/' . $pkey . '>(.*?)<\/' . $this->xmlfieldname . '>/s', $strnew, $oldfilestring);
        $newfilestring = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?php exit(0);?>\n" . trim($newfilestring, "\n ");
        $file = fopen($oldfile, "w");
        fwrite($file, $newfilestring);
        fclose($file);
        $this->ClearCachefile();
        xmetadb_readDatabase($oldfile, $this->xmlfieldname, false, false);
        return true;
    }

    /**
     * GetFileRecord
     * torna il nome del file che contiene il record
     * @param string $pkey
     * @param string $pvalue
     */
    function GetFileRecord($pkey, $pvalue)
    {
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;

        //$pvalue=urlencode($pvalue);
        if (!preg_match("/\\/$/si", $this->datafile))
        {
            return $this->datafile;
        }
        //guardo prima quelo con la chiave primaria
        if (file_exists($this->datafile . "/" . urlencode($pvalue) . ".php"))
        {
            $data = file_get_contents($this->datafile . "/" . urlencode($pvalue) . ".php");
            $data = xmetadb_removexmlcomments($data);
            if (preg_match('/<' . $tablename . '>(.*)<' . $pkey . '>' . xmlenc(xmetadb_encode_preg($pvalue)) . '<\/' . $pkey . '>/s', $data))
            {
                $this->cache_filerecord[$pvalue] = $this->datafile . "/" . urlencode($pvalue) . ".php";
                return $this->datafile . "/" . urlencode($pvalue) . ".php";
            }
        }

        //cerco in tutti i files
        $pvalue = xmlenc($pvalue);
        $pvalue = xmetadb_encode_preg($pvalue);
        if (!file_exists($this->datafile))
            return false;
        $handle = opendir($this->datafile);
        while (false !== ($file = readdir($handle)))
        {
            $tmp2 = null;
            if (preg_match('/.php$/s', $file) and !is_dir($this->datafile . "/$file"))
            {
                $data = file_get_contents($this->datafile . "/$file");
                $data = xmetadb_removexmlcomments($data);
                ///dprint_xml('/<' . $pkey . '>' . $pvalue . '<\/' . $pkey . '>/s');
                //dprint_r($this->datafile . "/$file");
                if (preg_match('/<' . $pkey . '>' . $pvalue . '<\/' . $pkey . '>/s', $data))
                {
                    $this->cache_filerecord[$pvalue] = $this->datafile . "/$file";
                    return $this->datafile . "/$file";
                }
            }
        }

        return false;
    }

    /**
     * GetRecordByPk
     * torna il record passandogli la chiave primaria
     * @param string $pvalue valore chiave
     */
    function GetRecordByPk($pvalue)
    {
        $pkey = $this->primarykey;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        //cache su file---->
        if (!is_array($pkey) && $this->usecachefile == 1)
        {
            $cacheindex = $pvalue;
            if (!file_exists("$path/" . $databasename . "/cache"))
                mkdir("$path/" . $databasename . "/cache");
            $cachefile = "$path/" . $databasename . "/cache/" . $tablename . "." . urlencode($pvalue) . ".cache";
            if (file_exists($cachefile))
            {
                $ret = file_get_contents($cachefile);
                $ret = @unserialize($ret);
                if ($ret !== false)
                    return $ret;
            }
        }
        //cache su file----<
        $old = $this->GetFileRecord($pkey, $pvalue);
        //dprint_r("$pkey.$old.$pvalue");
        $values = xmetadb_readDatabase($old, $this->xmlfieldname);
        $ret = false;
        $found = false;
        if (!is_array($values))
        {
            return $values;
        }
        foreach ($values as $value)
        {
            if ($value[$pkey] == ($pvalue))
            {
                $found = true;
                $ret = $value;
                break;
            }
        }
        //riempo i campi che mancano
        if ($found)
            foreach ($this->fields as $field)
            {
                if (!isset($ret[$field->name]))
                    $ret[$field->name] = isset($field->defaultvalue) ? $field->defaultvalue : null;
            }
        //cache su file---->
        if ($this->usecachefile == 1)
        {
            $cachestring = serialize($ret);
            $fp = fopen($cachefile, "wb");
            fwrite($fp, $cachestring);
            fclose($fp);
        }
        //cache su file----<
        return $ret;
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
        if (!isset($values[$pkey]))
            $values[$pkey] = $pvalue;
        $databasename = $this->databasename;
        $tablename = $this->tablename;
        $path = $this->path;
        $strnew = "";
        {
            $old = $this->GetFileRecord($pkey, $pvalue);
            if (!file_exists($old))
                return false;
            //$oldfilestring = file_get_contents($old);
            $readok = false;
            for ($i = 0; $i < _MAX_FILE_ACCESS_ATTEMPTS; $i++)
            {
                $oldfilestring = file_get_contents($old);
                if (strpos($oldfilestring, "</") !== false)
                {
                    $readok = true;
                    break;
                }
            }
            if (!$readok)
            {
                return "error update";
            }

            $oldfilestring = xmetadb_removexmlcomments($oldfilestring);
            $oldvalues = $newvalues = $this->GetRecordByPk($pvalue);
            if (isset($values[$this->primarykey]) && $values[$this->primarykey] != $pvalue)
            {
                $tatget = $this->GetRecordByPk($values[$this->primarykey]);
                if ($tatget)
                {
                    return "duplicate primarykey";
                }
            }
            //dprint_r($oldvalues);
            //die();
            foreach ($values as $key => $value)
            {
                $newvalues[$key] = $value;
            }
            $oldvalues[$this->primarykey] = $pvalue;
            $this->xmltable->gestfiles($values, $oldvalues);
            //compongo il nuovo xml per il record da aggiornare
            $strnew = "<{$this->xmlfieldname}>";
            foreach ($newvalues as $key => $value)
            {
                if (is_array($value))
                {
                    error_log("$value is not array");
                }
                $strnew .= "\n\t\t<$key>" . xmlenc("$value") . "</$key>";
            }
            $strnew .= "\n\t</{$this->xmlfieldname}>";
            $strnew = xmetadb_encode_preg_replace2nd($strnew);
            $pvalue = xmlenc($pvalue);
            $pvalue = xmetadb_encode_preg($pvalue);
            $newfilestring = preg_replace('/<' . $this->xmlfieldname . '>([^(' . $this->xmlfieldname . ')]*)<' . $pkey . '>' . $pvalue . '<\/' . $pkey . '>(.*?)<\/' . $this->xmlfieldname . '>/s', $strnew, $oldfilestring);
            $newfilestring = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?php exit(0);?>\n" . trim(ltrim($newfilestring));
            if (!is_writable($old))
            {
                echo ("$old is readonly,I can't update");
                return ("$old is readonly,I can't update");
            }
            $handle = fopen($old, "w");
            fwrite($handle, $newfilestring);
            $this->ClearCachefile();
            $newvalues = xmetadb_readDatabase($old, $this->xmlfieldname, false, false); //aggiorna la cache
            $newvalues = $this->GetRecordByPk($values[$pkey]);

            if (!isset($newvalues[$pkey]))
                return false;
            return $newvalues;
        }
    }
}
