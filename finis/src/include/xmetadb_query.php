<?php
/**
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2003-2024
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 * @package xmetadb
 * 
 */
class XMETADatabase
{
    private $path;
    private $databasename;    
    private $params;
    
    function __construct($databasename, $path = "misc",$params=array())
    {
        $this->databasename = $databasename;
        $this->path = $path;
        $this->params = $params;
       //dprint_r($this->params);
        
    }
    function encode_preg($str)
    {
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('/', '\\/', $str);
        $str = str_replace('(', '\\(', $str);
        $str = str_replace(')', '\\)', $str);
        $str = str_replace('^', '\\^', $str);
        $str = str_replace('$', '\\$', $str);
        $str = str_replace('*', '\\*', $str);
        $str = str_replace('+', '\\+', $str);
        $str = str_replace('?', '\\?', $str);
        $str = str_replace('[', '\\[', $str);
        $str = str_replace(']', '\\]', $str);
        $str = str_replace('|', '\\|', $str);
        return $str;
    }
    /**
     * Parser SQL
     * es.
     * SELECT * FROM table1 WHERE field1 AS alias1, field2 WHERE field1 = "condition" OR field2 = "condition2" ORDER BY field1 LIMIT 1,10
     *
     * Limitazioni attuali:
     * ancora da implementare INSERT,UPDATE,DELETE
     */
    function Query($query)
    {
        $databasename = $this->databasename;
        static $tblcache = array();
        $qitems = array();
        $qitems['query'] = $query;
        $qitems['fields'] = false;
        $qitems['tablename'] = false;
        $qitems['option'] = false;
        $qitems['orderby'] = false;
        $qitems['min'] = false;
        $qitems['length'] = false;
        $qitems['where'] = false;
        $fieldstoget = false;
        //SELECT
        if (preg_match("/^SELECT/is", $query))
        {
            if (preg_match("/^SELECT( DISTINCT | )([_a-zA-Z0-9, \(\)\*]+|\*|COUNT\(\*\)) FROM ([_a-zA-Z0-9,]+)(.+)/i", "$query ", $t1))
            {
                //campi
                $qitems['fields'] = trim(ltrim($t1[2]));
                $qitems['tablename'] = trim(ltrim($t1[3]));
                $qitems['option'] = trim(ltrim($t1[1]));
                $tablenames = explode(",", $qitems['tablename']);
                $rightselect = $t1[4];
                foreach ($tablenames as $tablename)
                {
                    $cid = $this->path . $databasename . $tablename;
                    if (!isset($tblcache[$cid]))
                        $tblcache[$cid] = XMETATable::xmetadbTable($databasename, $tablename, $this->path,$this->params);
                    //	$tblcache[$cid] = new XMETATable($databasename,$tablename,$this->path);
                    $tbl[$tablename] = &$tblcache[$cid];
                    if (!file_exists($this->path . "/$databasename/{$tablename}.php"))
                        return "xmetadb: unknow table {$tablename}";
                }
                //$tbl = new XMETATable($databasename, $qitems['tablename'], $this->path);
                if ($qitems['tablename'] == "")
                    return "xmetadb: syntax error";
                if (preg_match("/WHERE (.+)/i", $rightselect, $t1))
                {
                    $qitems['where'] = $t1[1];
                }
                else
                {
                    $qitems['where'] = "";
                }
                //---pulisco where ---->
                if (preg_match("/(.+)(ORDER BY )/i", $qitems['where'], $dt3))
                {
                    $qitems['where'] = $dt3[1];
                }
                if (preg_match("/(.+)(LIMIT )/i", $qitems['where'], $dt3))
                {
                    $qitems['where'] = $dt3[1];
                }
                //---pulisco where ----<
                //limit a,b
                if (preg_match("/(.+)LIMIT ([0-9]+),([0-9]+)/i", $rightselect, $dt3))
                {
                    $qitems['min'] = $dt3[2];
                    $qitems['length'] = $dt3[3];
                }
                //order by
                if (preg_match("/(.*)(i:limit|)ORDER BY(.*)(i:limit|)/i", $rightselect, $dt2))
                {
                    $tt = $this->iExplode(" limit ", $dt2[3]);
                    $tt = $tt[0];
                    $tmpwhere = $dt2[1];
                    $qitems['orderby'] = trim(ltrim($tt));
                }
            }
            //----CONDIZIONE---------------------->
            //dprint_r($qitems);
            //dprint_r($where2);
            //----CONDIZIONE----------------------<
            $ret = $this->getrecords($qitems, $tbl);
            $count = false;
            if (stristr($qitems['fields'], "COUNT(*)"))
            {
                // ADDED BY DANIELE FRANZA 2/02/2009: start
                if (preg_match("/ AS /is", $qitems['fields']))
                {
                    $as = $this->iExplode(" AS ", $qitems['fields']);
                    $k2 = trim(ltrim($as[1]));
                    $k1 = trim(ltrim($as[0]));
                }
                else
                {
                    $k1 = $k2 = trim(ltrim($field));
                }
                $count = true;
            }
            if ($count)
                return array(0 => array("$k2" => count($ret)));
            else
                return $ret;
        }
        //DESCRIBE  TODO
        if (preg_match("/^DESCRIBE/is", $query))
        {
            if (preg_match("/^DESCRIBE ([a-zA-Z0-9_]+)/is", "$query ", $t1))
            {
                $qitems['tablename'] = trim(ltrim($t1[1]));
                $cid = $this->path . $databasename . $qitems['tablename'];
                if (!isset($tblcache[$cid]))
                    $tblcache[$cid] = XMETATable::xmetadbTable($databasename, $qitems['tablename'], $this->path,$this->params);
                $t = &$tblcache[$cid];
                $ret = array();
                foreach ($t->fields as $field)
                {
                    $ret[] = array("Field" => $field->name, "Type" => $field->type, "Null" => "NO", "Key" => $field->primarykey, "Extra" => $field->extra);
                }
                return $ret;
            }
        }
        //SHOW TABLES
        if (preg_match("/^SHOW TABLES/is", trim(ltrim($query))))
        {
            $path = ($this->path . "/" . $this->databasename . "/*");
            $files = glob($path);
            $ret = array();
            foreach ($files as $file)
            {
                if (!is_dir($file))
                    $ret[] = array("Tables_in_" . $this->databasename => preg_replace('/.php$/s', '', basename($file)));
            }
            return $ret;
        }
        //INSERT
        if (preg_match("/^INSERT/is", $query))
        {
            if (preg_match("/^INSERT[ ]+INTO([a-zA-Z0-9\\`\\._ ]+)\\(([a-zA-Z_ ,]+)\\)[ ]+VALUES[ ]+\\((.*)\\)/i", "$query ", $t1))
            {
                $qitems['tablename'] = trim(ltrim($t1[1]));
                $qitems['fields'] = trim(ltrim($t1[2]));
                $qitems['values'] = trim(ltrim($t1[3]));
                $cid = $this->path . $databasename . $qitems['tablename'];
                if (!isset($tblcache[$cid]))
                    $tblcache[$cid] = XMETATable::xmetadbTable($databasename, $qitems['tablename'], $this->path,$this->params);
                $tbl = &$tblcache[$cid];
                $fields = explode(",", $qitems['fields']);
                $values = explode(",", $qitems['values']);
                $recordstoinsert = array();
                if (count($fields) == count($values))
                {
                    for ($i = 0; $i < count($fields); $i++)
                    {
                        $recordstoinsert[$fields[$i]] = preg_replace("/^'/", "", preg_replace("/'$/s", "", preg_replace('/^"/s', "", preg_replace('/"$/s', "", $values[$i]))));
                    }
                }
                else
                    return "xmetadb: syntax error";
                return $tbl->InsertRecord($recordstoinsert);
            }
        }
        //UPDATE EXPERIMENTAL TODO
        if (preg_match("/^UPDATE/is", $query))
        {
            if (preg_match("/^UPDATE[ ]+([_a-zA-Z0-9]+)[ ]+SET[ ]+(.*)/i", "$query ", $t1))
            {
                //dprint_r($t1);
                $qitems['tablename'] = trim(ltrim($t1[1]));
                $rightselect = $t1[2];
                $tablename = $qitems['tablename'];
                if (!file_exists($this->path . "/$databasename/{$tablename}.php"))
                    return "xmetadb: unknow table {$tablename}";
                $tbl[$tablename] = XMETATable::xmetadbTable($databasename, $tablename, $this->path,$this->params);
                $qitems['fields'] = $tbl[$tablename]->primarykey;

                if (preg_match("/WHERE (.+)/i", $rightselect, $t1))
                {
                    $pos = strrpos(strtolower($rightselect), " where ") + 7;
                    $qitems['where'] = trim(ltrim(substr($rightselect, $pos)));
                    $qitems['updatestring'] = trim(ltrim(substr($rightselect, 0, $pos - 7)));
                }
                else
                {
                    $qitems['where'] = "";
                }
                //---pulisco where ---->
                if (preg_match("/(.+)(ORDER BY )/i", $qitems['where'], $dt3))
                {
                    $qitems['where'] = $dt3[1];
                }
                if (preg_match("/(.+)(LIMIT )/i", $qitems['where'], $dt3))
                {
                    $qitems['where'] = $dt3[1];
                }
                //---pulisco where ----<
                //limit a,b
                if (preg_match("/(.+)LIMIT ([0-9]+),([0-9]+)/i", $rightselect, $dt3))
                {
                    $qitems['min'] = $dt3[2];
                    $qitems['length'] = $dt3[3];
                }
                //order by
                if (preg_match("/(.*)(i:limit|)ORDER BY(.*)(i:limit|)/i", $rightselect, $dt2))
                {
                    $tt = $this->iExplode(" limit ", $dt2[3]);
                    $tt = $tt[0];
                    $tmpwhere = $dt2[1];
                    $qitems['orderby'] = trim(ltrim($tt));
                }
                //dprint_r($qitems);
                $allrecords = $this->getrecords($qitems, $tbl);
                if (!is_array($allrecords))
                    return null;
                //dprint_r($allrecords);
                $matchesarray = array();
                $updeteitems = explode(",", $qitems['updatestring']);
                foreach ($updeteitems as $v)
                {
                    $tmpupdate = explode("=", $v);
                    $newvalues[trim(ltrim($tmpupdate[0]))] = trim(ltrim($tmpupdate[1], " '\""), " '\"");
                }
                foreach ($allrecords as $recordtodel)
                {
                    $newvalues[$tbl[$tablename]->primarykey] = $recordtodel[$tbl[$tablename]->primarykey];
                    $tbl[$tablename]->UpdateRecord($newvalues);
                }
            }
            //UPDATE UPDATE users SET email = 'speleoalex@pippo',name = 'speleo' WHERE username LIKE '%s%' LIMIT 1,1
        }
        //DELETE TODO
        if (preg_match("/^DELETE/is", $query))
        {
            if (preg_match("/^DELETE[ ]+FROM ([_a-zA-Z0-9,]+)(.+)/i", "$query ", $t1))
            {
                //campi
                $qitems['tablename'] = trim(ltrim($t1[1]));
                $qitems['option'] = trim(ltrim($t1[1]));
                $tablename = $qitems['tablename'];
                $rightselect = $t1[2];
                if (!file_exists($this->path . "/$databasename/{$tablename}.php"))
                    return "xmetadb: unknow table {$tablename}";
                $tbl[$tablename] = XMETATable::xmetadbTable($databasename, $tablename, $this->path,$this->params);
                $qitems['fields'] = $tbl[$tablename]->primarykey;
                if ($qitems['tablename'] == "")
                    return "xmetadb: syntax error";
                if (preg_match("/WHERE (.+)/i", $rightselect, $t1))
                {
                    $qitems['where'] = $t1[1];
                }
                else
                {
                    $qitems['where'] = "";
                }
                //---pulisco where ---->
                if (preg_match("/(.+)(ORDER BY )/i", $qitems['where'], $dt3))
                {
                    $qitems['where'] = $dt3[1];
                }
                if (preg_match("/(.+)(LIMIT )/i", $qitems['where'], $dt3))
                {
                    $qitems['where'] = $dt3[1];
                }
                //---pulisco where ----<
                //limit a,b
                if (preg_match("/(.+)LIMIT ([0-9]+),([0-9]+)/i", $rightselect, $dt3))
                {
                    $qitems['min'] = $dt3[2];
                    $qitems['length'] = $dt3[3];
                }
                //order by
                if (preg_match("/(.*)(i:limit|)ORDER BY(.*)(i:limit|)/i", $rightselect, $dt2))
                {
                    $tt = $this->iExplode(" limit ", $dt2[3]);
                    $tt = $tt[0];
                    $tmpwhere = $dt2[1];
                    $qitems['orderby'] = trim(ltrim($tt));
                }
                $allrecords = $this->getrecords($qitems, $tbl);
                if (!is_array($allrecords))
                    return null;
                foreach ($allrecords as $recordtodel)
                {
                    $pkkey = $tbl[$tablename]->primarykey;
                    $tbl[$tablename]->DelRecord($recordtodel[$pkkey]);
                }
                return "";
            }
        }
        return null;
    }

    private function getrecords($qitems, $tbl)
    {
        //dprint_r($qitems['where']);
        $where2 = $this->convertwhere($qitems['where']);
        //dprint_r($where2);
        //per ottimizzare prendo solo i fields che mi interessano--->
        if ($qitems['fields'] == "*" || preg_match('/COUNT\(\*\)/is', $qitems['fields']))
        {
            $fieldstoget = false;
        }
        else
        {
            //dprint_r($qitems);
            //per performance coinvolgo solamente i fields interessati dalla query
            if ($qitems['orderby'] != "")
            {
                $t = explode(",", $qitems['orderby']);
                foreach ($t as $tf)
                {
                    if (preg_match("/([a-zA-Z0-9_]+)/", $tf, $pm))
                        $fieldstoget[] = trim(ltrim($pm[0]));
                }
            }
            //nei campi
            $t = explode(",", $qitems['fields']);
            foreach ($t as $tf)
            {
                if (preg_match("/([a-zA-Z0-9_]+)/", $tf, $pm))
                    $fieldstoget[] = trim(ltrim($pm[0]));
            }
            //nel where
            $t = $this->iExplode("['", $where2);
            unset($t[0]);
            foreach ($t as $tf)
            {
                $tf = trim(ltrim($tf));
                if (preg_match("/([a-zA-Z0-9_]+)/", $tf, $pm))
                    $fieldstoget[] = trim(ltrim($pm[0]));
            }
            if (!is_array($fieldstoget))
                return "xmetadb: syntax error";
            $fieldstoget = array_unique($fieldstoget);
        }
        //per ottimizzare prendo solo i fields che mi interessano---<
        $allrecords = array();
        //dprint_r($fieldstoget);
        foreach ($tbl as $tablename => $tbl_t)
        {
            if ($fieldstoget)
                foreach ($fieldstoget as $fieldget)
                {
                    if (!isset($tbl_t->fields[$fieldget]))
                    {
                        echo "unknow field $fieldget in table $tablename";
                        return false;
                    }
                }
            //dprint_r($tbl_t->fields[$fieldstoget]);
            //dprint_r($fieldstoget);
            $allrecords_tml = $tbl_t->GetRecords(false, false, false, false, false, $fieldstoget);

            if (is_array($allrecords_tml))
                $allrecords = array_merge($allrecords, $allrecords_tml);
        }
        //dprint_r($qitems);
        //ordinamento -------->
        if ($qitems['orderby'] != "")
        {
            $orders = explode(",", $qitems['orderby']);
            foreach ($orders as $order)
            {
                if (preg_match("/([a-zA-Z0-9_]+)(.*)/s", $order, $orderfields))
                {
                    //dprint_r($order);
                    //dprint_r($orderfields);
                    if (preg_match("/DESC/is", trim(ltrim($orderfields[2]))))
                    {
                        $isdesc = true;
                    }
                    else
                        $isdesc = false;
                    $allrecords = xmetadb_array_sort_by_key($allrecords, trim(ltrim($orderfields[1])), $isdesc);
                }
            }
        }

        //ordinamento --------<
        $i = 0;
        $ret = array();
        //filtro search condition -------------------->
        if (!is_array($allrecords))
            return null;
        foreach ($allrecords as $item)
        {
            $ok = false;
            if ($where2 == "")
            {
                $ok = true;
            }
            else
            {


                try
                {
                    //dprint_r("if ($where2) {".'$ok=true;'."} ");
                    @eval("if ($where2) {" . '$ok=true;' . "} ");
                } catch (ParseError $e)
                {
                    return false;
                }
            }
            if ($ok == false)
                continue;
            if ($qitems['fields'] == "*")
            {
                $tmp = $item;
            }
            else
            {
                $fields = explode(",", $qitems['fields']);
                $tmp = null;
                //alias ------->
                foreach ($fields as $field)
                {
                    if (preg_match("/ AS /is", $field))
                    {
                        $as = $this->iExplode(" AS ", $field);
                        $k2 = trim(ltrim($as[1]));
                        $k1 = trim(ltrim($as[0]));
                    }
                    else
                        $k1 = $k2 = trim(ltrim($field));

                    //fix null value--->
                    foreach ($item as $nk => $v)
                    {
                        if (!isset($item[$nk]))
                            $item[$nk] = false;
                    }
                    //fix null value---<
                    if (!isset($item[$k1]) && strtoupper($k1) != "COUNT(*)")
                    {
                        return "xmetadb: unknow row '$k1' in table {$qitems['tablename']}";                        
                    }
                    if (strtoupper($k1) != "COUNT(*)")
                    {
                        $tmp[$k2] = $item[$k1];
                    }
                    else
                    {
                        if (isset($item[$k2]))
                        {
                            $tmp[$k2] = $item[$k2];                           
                        }                     
                    }
                }
                //alias -------<
            }

            //----distinct------------->
            if (strtoupper($qitems['option']) == "DISTINCT")
                if ($this->array_in_array($tmp, $ret))
                {
                    continue;
                }
            //----distinct-------------<
            $i++;
            //----min length----------->
            if (($qitems['min']) && $i < $qitems['min'])
                continue;
            if (($qitems['min'] && $qitems['length']) && ($i) >= ($qitems['min'] + $qitems['length']))
                break;
            //----min length----------->

            $ret[] = $tmp;
        }


        //filtro search condition --------------------<
        return $ret;
    }

    private function convertwhere($where)
    {
        $where2 = trim(ltrim($where));
        //fix cases: category LIKE '%Innamorati dell\'arte%' 
        $apice = "_apice_";
        while (false !== strpos($where2, $apice))
        {
            $apice .= "_";
        }
        $where2 = str_replace("\\'", "$apice", $where2);

        $where2 = preg_replace("/([a-zA-Z0-9_ ]+) = ([a-zA-Z0-9_'\"]+)/i", '${1} == ${2}', $where2);
        // = -> ==
        $where2 = preg_replace("/([a-zA-Z0-9_ ]+) = ([a-zA-Z0-9_'\"]+)/i", '${1} == ${2}', $where2);
        // LIKE -> ==
        $where2 = preg_replace("/([a-zA-Z0-9_ ]+) LIKE ([a-zA-Z0-9_'\"]+)/i", '${1} == ${2}', $where2);
        // field = 'b'
        $where2 = preg_replace("/(\\w+)( <> | LIKE | > | >= | == | <= | < )(['|\\\"])([^'|^\\\"|^%]+)(['|\\\"])/", '\$item[\'${1}\'] ${2} ${3}${4}${5}', $where2);
        // field = ''
        $where2 = preg_replace("/(\\w+)( <> | LIKE | > | >= | == | <= | < )(['|\\\"])(['|\\\"])/", '\$item[\'${1}\'] ${2} ${3}${4}', $where2);
        // field = 1
        $where2 = preg_replace("/(\\w+)( <> | LIKE | > | >= | == | <= | < )([0-9]+)/", '\$item[\'${1}\'] ${2} "${3}"', $where2);
        // field1 = field2
        $where2 = preg_replace("/(\\w+)( <> | LIKE | > | >= | == | <= | < )([\\w]+)/", '\$item[\'${1}\'] ${2} $item[\'${3}\']', $where2);
        // fiels LIKE "%t%"
        $where2 = preg_replace('/(\w+)[\040]+(==)[\040]+([\'])%([^\']*?)%([\'])/i', 'preg_match("/".XMETADatabase::encode_preg("${4}")."/i",(\$item[\'${1}\']))', $where2);
        $where2 = preg_replace('/(\w+)[\040]+(==)[\040]+([\"])%([^\"]*?)%([\"])/i', 'preg_match("/".XMETADatabase::encode_preg("${4}")."/i",(\$item[\'${1}\']))', $where2);
        // fiels LIKE "%t"
        $where2 = preg_replace('/(\w+)[\040]+(==)[\040]+([\'])%([^\']*?)([\'])/i', 'preg_match("/".XMETADatabase::encode_preg("${4}")."\$/i",(\$item[\'${1}\']))', $where2);
        $where2 = preg_replace('/(\w+)[\040]+(==)[\040]+([\"])%([^\"]*?)([\"])/i', 'preg_match("/".XMETADatabase::encode_preg("${4}")."\$/i",(\$item[\'${1}\']))', $where2);
        // fiels LIKE "t%"
        $where2 = preg_replace('/(\w+)[\040]+(==)[\040]+([\'])([^\']*?)%([\'])/i', 'preg_match("/^".XMETADatabase::encode_preg("${4}")."/i",(\$item[\'${1}\']))', $where2);
        $where2 = preg_replace('/(\w+)[\040]+(==)[\040]+([\"])([^\"]*?)%([\"])/i', 'preg_match("/^".XMETADatabase::encode_preg("${4}")."/i",(\$item[\'${1}\']))', $where2);
        // fiels <> "%t%"
        $where2 = preg_replace('/(\w+)[\040]+(<>)[\040]+([\"])%([^\"]*?)%([\"])/i', '!preg_match("/".XMETADatabase::encode_preg("${4}")."/i",(\$item[\'${1}\']))', $where2);
        // fiels <> '%t%'
        $where2 = preg_replace('/(\w+)[\040]+(<>)[\040]+([\'])%([^\']*?)%([\'])/i', '!preg_match(\'/\'.XMETADatabase::encode_preg(\'${4}\').\'/i\',(\$item[\'${1}\']))', $where2);
        //dprint_r($where2);
        $where2 = str_replace("$apice", "\\'", $where2);
        //dprint_r("w2=$where2");
        return $where2;
    }

    function array_in_array($needle, $haystack)
    {
        //Make sure $needle is an array for foreach
        if (!is_array($haystack))
            return false;
        if (!is_array($needle))
            $needle = array($needle);
        //For each value in $needle, return TRUE if in $haystack
        foreach ($haystack as $line)
        {
            if (!strcmp(serialize($line), serialize($needle)))
                return true;
        }
        return false;
    }

    function iExplode($Delimiter, $String, $Limit = '')
    {
        $tmpString = strtoupper($String);
        $tmpDelimiter = strtoupper($Delimiter);
        $tmpret = explode($tmpDelimiter, $tmpString);
        $start = 0;
        foreach ($tmpret as $r)
        {
            $length = strlen($r);
            $ret[] = substr($String, $start, $length);
            $start += strlen($r . $Delimiter);
        }
        return ($ret);
    }
}

?>
