<?php

/**
 * @package Flatnux_module_navigator
 * @author Alessandro Vernassa <speleoalex@gmail.com>
 * @copyright Copyright (c) 2011
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License
 */
#<fnmodule>navigator</fnmodule>
global $_FN;

//ini_set('max_input_vars', 30000);
//------------------- tabella permessi tabelle -------------------------



function FNNAV_Init()
{
    global $_FN;
    $htmlLog="";
    if (!file_exists("{$_FN['datadir']}/fndatabase/fieldusers"))
    {
        $sfields=array();
        $sfields[0]['name']="unirecid";
        $sfields[0]['primarykey']="1";
        $sfields[0]['extra']="autoincrement";
        $sfields[1]['name']="username";
        $sfields[2]['name']="tablename";
        $sfields[3]['name']="table_unirecid";
        $htmlLog.=createxmltable("fndatabase","fieldusers",$sfields,$_FN['datadir']);
    }
    $config=FN_LoadConfig();
    $tablename=$config['tables'];

//--------------- creazione tabelle ------------------------------->
    if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/{$tablename}") || !file_exists("{$_FN['datadir']}/{$_FN['database']}/{$tablename}.php"))
    {
        $str_table=file_get_contents("modules/navigator/install/fn_files.php");
        $str_table=str_replace("fn_files",$tablename,$str_table);
        FN_Write($str_table,$_FN['datadir']."/".$_FN['database']."/$tablename.php");
    }

    if ($config['enable_history'] && !file_exists("{$_FN['datadir']}/{$_FN['database']}/{$tablename}_versions.php"))
    {
        $Table=FN_XmlTable($tablename);
        if (!isset($Table->fields['recorddeleted']))
        {
            $tfield['name']="userupdate";
            $tfield['type']="varchar";
            $tfield['frm_show']="0";
            addxmltablefield($Table->databasename,$Table->tablename,$tfield,$Table->path);
        }

        $str_table=file_get_contents($_FN['datadir']."/".$_FN['database']."/$tablename.php");
        $str_table=str_replace("<primarykey>1</primarykey>","",$str_table);
        $str_table=str_replace("<tables>","<tables>
    <field>
		<name>idversions</name>
		<primarykey>1</primarykey>
		<extra>autoincrement</extra>
		<type>string</type>
	</field>",$str_table);
        FN_Write($str_table,$_FN['datadir']."/".$_FN['database']."/{$tablename}_versions.php");
    }
//------------------- tabella delle statistiche -------------------------
    if ($config['enable_statistics']== 1)
    {
        if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/{$tablename}"."_stat") || !file_exists("{$_FN['datadir']}/{$_FN['database']}/{$tablename}"."_stat.php"))
        {
            //$htmlLog.= "<br>creazione statistiche $tablename";
            $sfields=array();
            $sfields[0]['name']="unirecid";
            $sfields[0]['primarykey']="1";
            $sfields[1]['name']="view";
            $htmlLog.=createxmltable($_FN['database'],$tablename."_stat",$sfields,$_FN['datadir']);
        }
    }
//------------------- tabella delle ranks -------------------------
    if ($config['enableranks'] && !file_exists("{$_FN['datadir']}/{$_FN['database']}/{$tablename}_"."ranks.php"))
    {
        $str="<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<?php exit (0)?>
<tables>
	<field>
		<name>unirecid</name>
		<primarykey>1</primarykey>
		<extra>autoincrement</extra>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>insert</name>
		<type>string</type>
		<frm_show>0</frm_show>
	</field>
	<field>
		<name>username</name>
		<type>string</type>
		<frm_show>0</frm_show>
		<frm_required>1</frm_required>
	</field>
	<field>
		<name>rank</name>
	</field>
	<field>
		<name>unirecidrecord</name>
		<frm_show>0</frm_show>
	</field>
	<indexfield>unirecidrecord</indexfield>
</tables>
";
        FN_Write($str,"{$_FN['datadir']}/{$_FN['database']}/{$tablename}_"."ranks.php");
    }
//------------------- tabella permessi tabelle -------------------------
    if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/fieldusers"))
    {
        $sfields=array();
        $sfields[0]['name']="unirecid";
        $sfields[0]['primarykey']="1";
        $sfields[0]['extra']="autoincrement";
        $sfields[1]['name']="username";
        $sfields[2]['name']="tablename";
        $sfields[3]['name']="table_unirecid";
        $htmlLog.=createxmltable($_FN['database'],"fieldusers",$sfields,$_FN['datadir']);
    }
//------------------- tabella permessi tabelle -------------------------
    if ($config['enable_permissions_each_records'] && $config['permissions_records_groups']!= "")
    {
        $tmp=explode(",",$config['permissions_records_groups']);
        foreach($tmp as $group)
        {
            FN_CreateGroupIfNotExists($group);
        }
    }
//------------------- tabella commenti-------------------------
    if ($config['enablecomments'] && !file_exists("{$_FN['datadir']}/{$_FN['database']}/{$tablename}"."_comments") || !file_exists("{$_FN['datadir']}/{$_FN['database']}/{$tablename}"."_comments.php"))
    {
        $sfields=array();
        $sfields[0]['name']="unirecid";
        $sfields[0]['primarykey']="1";
        $sfields[0]['extra']="autoincrement";
        $sfields[0]['frm_show']="0";
        $sfields[1]['name']="insert";
        $sfields[1]['type']="string";
        $sfields[1]['defaultvalue']="";
        $sfields[1]['frm_show']="0";
        $sfields[3]['name']="username";
        $sfields[3]['type']="string";
        $sfields[3]['frm_show']="0";
        $sfields[3]['frm_required']="1";
        $sfields[4]['name']="title";
        $sfields[4]['frm_it']="Titolo";
        $sfields[4]['frm_i18n']="title";
        $sfields[4]['type']="string";
        $sfields[4]['frm_required']="1";
        $sfields[5]['name']="comment";
        $sfields[5]['frm_it']="Commento";
        $sfields[5]['frm_i18n']="comment";
        $sfields[5]['type']="text";
        $sfields[5]['frm_rows']="10";
        $sfields[5]['frm_required']="1";
        $sfields[5]['frm_cols']="80";
        $sfields[6]['name']="unirecidrecord";
        $sfields[6]['frm_show']="0";
        $htmlLog.=createxmltable($_FN['database'],"{$tablename}"."_comments",$sfields,$_FN['datadir']);
        return $htmlLog;
    }
//--------------- creazione tabelle -------------------------------<
}

function FNNAV_NavigationMode()
{
    $config=FN_LoadConfig();
    $navigate=FN_GetParam("nav",$_GET);
    if (!isset($_GET['nav']))
    {
        if ($config['default_show_groups']== 1)
        {
            $navigate=1;
        }
        else
        {
            $navigate=0;
        }
    }
    return $navigate;
}

/**
 *
 * @global array $_FN
 * @staticvar boolean $listok
 * @param array $config
 * @param array $params
 * @return array 
 */
function FNNAV_GetResults($config=false,$params=false)
{
    global $_FN;
    static $listok=false;
    //------------------------------load config-------------------------------->
    if ($config== false)
    {
        $config=FN_LoadConfig();
    }

    $search_options=$config['search_options']!= "" ? explode(",",$config['search_options']) : array();
    $search_min=$config['search_min']!= "" ? explode(",",$config['search_min']) : array();
    $search_partfields=$config['search_partfields']!= "" ? explode(",",$config['search_partfields']) : array();
    $search_fields=$config['search_fields']!= "" ? explode(",",$config['search_fields']) : array();
    $tables=explode(",",$config['tables']);
    $groups=($config['navigate_groups']!= "") ? explode(",",$config['navigate_groups']) : array();
    //------------------------------load config--------------------------------<
    if ($params=== false)
        $params=$_GET;
    $q=FN_GetParam("q",$params);
    $navigate=FNNAV_NavigationMode();
    $listfind=explode(" ",$q);
    $order=FN_GetParam("order",$params);
    $desc=FN_GetParam("desc",$params);
    $rule=FN_GetParam("rule",$params);
    $rulequery="";
    if ($rule!= "" && !empty($config['table_rules']))
    {
        $tablerules=FN_XmlTable($config['table_rules']);
        $rulevalues=$tablerules->GetRecordByPrimaryKey($rule);
        if (!empty($rulevalues['function']) && function_exists($rulevalues['function']))
        {
            return $rulevalues['function']($rulevalues);
        }
        elseif (!empty($rulevalues['query']))
        {
            $rulequery="{$rulevalues['query']}";
        }
    }
    if ($order== "")
    {
        $order=$config['defaultorder'];
        if ($desc== "")
            $desc=1;
    }
    $viewmode=FN_GetParam("viewmode",$params);
    foreach($tables as $tablename)
    {
        $t=FN_XmlForm($tablename);
        $fields=array();
        $ftoread=$groups;
        $ftoread[]=$t->xmltable->primarykey;
        $ftoread=implode(",",$ftoread);
        $query="SELECT $ftoread FROM $tablename WHERE   ";
        $wherequery="";
        $and="";
        if (!empty($rulequery))
        {
            $wherequery=" $rulequery ";
            $and="AND";
        }

        if ($config['enable_permissions_each_records'] && isset($t->formvals['groupview']) && !FN_IsAdmin())
        {
            $exists_group=false;
            $wherequery.="(";
            $usergroups=FN_GetUser($_FN['user']);
            $usergroups=isset($usergroups['group']) ? explode(",",$usergroups['group']) : array("");

            $wherequery.="username LIKE '{$_FN['user']}' OR groupview LIKE ''";
            $or=" OR";
            foreach($usergroups as $usergroup)
            {
                if ($usergroup!= "")
                {
                    $wherequery.="$or groupview LIKE '$usergroup' OR groupview LIKE '%$usergroup' OR groupview LIKE '$usergroup%' ";
                    $or="OR";
                    $exists_group=true;
                }
            }
            $wherequery.=") ";
            $and=" AND ";
        }

        if ($order== "")
        {
            $order=$t->xmltable->primarykey;
            if ($desc== "")
                $desc=1;
        }

        if (isset($t->xmltable->fields['recorddeleted']))
        {
            $wherequery.="$and recorddeleted <> '1'";
            $and="AND";
        }
        if ($config['appendquery']!= "")
        {
            $wherequery.="$and {$config['appendquery']} ";
            $and="AND";
        }
        $method=" OR ";
        $endmethod="";
        //-----------------------ricerca del testo ---------------------------->
        $findtextquery="";
        $tmpmethod="";
        foreach($t->xmltable->fields as $fieldstoread=> $fieldvalues)
        {
            if ($fieldstoread!= "insert" && $fieldstoread!= "update" && $fieldstoread!= "unirecid" && $fieldstoread!= "unirecid" && $fieldvalues->type!= "check")
            {
                foreach($listfind as $f)
                {
                    if ($f!= "")
                    {
                        if (isset($fieldvalues->foreignkey) && isset($fieldvalues->fk_link_field))
                        {
                            $fk=FN_XmlTable($fieldvalues->foreignkey);
                            $fkshow=explode(",",$fieldvalues->fk_show_field);
                            $fkfields="";
                            if ($fieldvalues->fk_show_field!= "")
                                $fkfields=",".$fieldvalues->fk_show_field;
                            //prendo il primo
                            $fk_query="SELECT {$fieldvalues->fk_link_field}$fkfields FROM {$fieldvalues->foreignkey} WHERE ";
                            $or="";
                            foreach($fkshow as $fkitem)
                            {
                                $fk_query.="$or {$fkitem} LIKE '%".addslashes($f)."%'";
                                $or="OR";
                            }
                            if (!isset($listok[$f][$fieldvalues->foreignkey]))
                            {
                                $rt=FN_XMLQuery($fk_query);
                                $listok[$f][$fieldvalues->foreignkey]=$rt;
                            }
                            if (is_array($listok[$f][$fieldvalues->foreignkey]) && count($listok[$f][$fieldvalues->foreignkey]) > 0)
                            {
                                $findtextquery_tmp=" $tmpmethod (";
                                $m="";
                                $exists_tmp=false;
                                foreach($listok[$f][$fieldvalues->foreignkey] as $fk_item)
                                {
                                    //dprint_r($fk_item);
                                    $vv="";
                                    if (isset($fk_item[$fieldvalues->fk_link_field]))
                                    {
                                        $exists_tmp=true;
                                        $vv=str_replace("'","\\'",$fk_item[$fieldvalues->fk_link_field]);
                                        $findtextquery_tmp.="$m $fieldstoread = '$vv'";
                                        $m=" OR ";
                                    }
                                }
                                $findtextquery_tmp.=")";
                                if (!$exists_tmp)
                                    $findtextquery_tmp="";
                                $tmpmethod=$method;
                            }
                            else
                            {
                                $findtextquery_tmp=" $tmpmethod (".$fieldstoread." LIKE '%".addslashes($f)."%') ";
                            }
                            $findtextquery.=$findtextquery_tmp;
                        }
                        else
                        {
                            $findtextquery.=" $tmpmethod ".$fieldstoread." LIKE '%".addslashes($f)."%' ";
                        }
                        $tmpmethod=$method;
                    }
                }
                $tmpmethod=" OR ";
            }
        }
        if ($findtextquery!= "")
        {
            $wherequery.="$and ($findtextquery) ";
            $and="AND";
        }
        //-----------------------ricerca del testo ----------------------------<
        //---check ---->
        $_tables[$tablename]=FN_XmlForm($tablename);
        //dprint_r($_tables);
        foreach($search_options as $option)
        {
            $checkquery="";
            $tmet="";
            if (isset($_tables[$tablename]->formvals[$option]['options']) && is_array($_tables[$tablename]->formvals[$option]['options']))
            {
                foreach($_tables[$tablename]->formvals[$option]['options'] as $c)
                {
                    $otitle=$c['title'];
                    $ovalue=$c['value'];
                    $ogetid="s_opt_{$option}_{$tablename}_{$c['value']}";
                    $sopt=FN_GetParam($ogetid,$params,"html");
                    if ($sopt!= "")
                    {
                        $checkquery.=" $tmet $option LIKE '$ovalue' ";
                        $tmet="OR";
                    }
                }
            }
            if ($checkquery!= "")
            {
                $wherequery.="$and ($checkquery) ";
                $and="AND";
            }
        }
        //---check ----<
        //min---->
        $minquery="";
        $tmet="";
        foreach($search_min as $min)
        {
            if (isset($_tables[$tablename]->formvals[$min]))
            {
                $getmin=FN_GetParam("min_$min",$params,"html");
                if ($getmin!= "")
                {
                    $getmin=intval($getmin);
                    $minquery.=" $tmet $min > $getmin ";
                    $tmet="AND";
                }
            }
        }
        if ($minquery!= "")
        {
            $wherequery.="$and ($minquery) ";
            $and="AND";
        }
        //min----<
        //searchfields---->
        $sfquery="";
        $tmet="";
        foreach($search_fields as $sfield)
        {
            if (isset($_tables[$tablename]->formvals[$sfield]))
            {
                $get_sfield=FN_GetParam("sfield_$sfield",$params,"html");
                if ($get_sfield!= "")
                {
                    $sfquery.=" $tmet ($sfield LIKE '$get_sfield' OR $sfield LIKE '$get_sfield.%') ";
                    $tmet="AND";
                }
            }
        }
        if ($sfquery!= "")
        {
            $wherequery.="$and ($sfquery) ";
            $and="AND";
        }
        //searchfields----<
        //searchpartfields---->
        $sfquery="";
        $tmet="";
        foreach($search_partfields as $sfield)
        {
            if (isset($_tables[$tablename]->formvals[$sfield]))
            {
                $get_sfield=FN_GetParam("spfield_$sfield",$params,"html");
                if ($get_sfield!= "")
                {
                    $sfquery.=" $tmet $sfield LIKE '%$get_sfield%' ";
                    $tmet="AND";
                }
            }
        }
        if ($sfquery!= "")
        {
            $wherequery.="$and ($sfquery) ";
            $and="AND";
        }
        //searchpartfields----<
        //-----------------------record is visible only creator---------------->
        if ($config['viewonlycreator']== 1)
        {
            if (!FN_IsAdmin())
            {

                if ($_FN['user']!= "")
                {
                    $wherequery.="$and (username LIKE '{$_FN['user']}' OR username LIKE '%,{$_FN['user']}' OR username LIKE '%,{$_FN['user']},%' OR username LIKE '%,{$_FN['user']}') ";


                    $listusers=FN_XmlTable("fieldusers");
                    $MyRecords=$listusers->GetRecords(array("tablename"=>$tablename,"username"=>$_FN['user']));
                    if (is_array($MyRecords))
                    {
                        foreach($MyRecords as $MyRecord)
                        {
                            $wherequery.="OR {$_tables[$tablename]->xmltable->primarykey} = '{$MyRecord['table_unirecid']}'";
                        }
                    }
                }
            }
            $and="AND";
        }
        //-----------------------record is visible only creator----------------<
        if ($navigate== 1)
        {
            $groupquery="";
            $tmet="";
            foreach($groups as $group)
            {
                if (isset($_GET["nv_{$group}"]))
                {
                    $navigate=FN_GetParam("nv_{$group}",$params);
                    $groupquery.="$tmet $group LIKE '".addslashes($navigate)."' ";
                    $tmet="AND";
                }
            }
            if ($groupquery!= "")
            {
                $wherequery.="$and ($groupquery) ";
                $and="AND";
            }
        }

        if ($wherequery== "")
            $wherequery="1";
        $orderquery="";
        if ($order!= "")
        {
            $orderquery.=" ORDER BY $order";
            if ($desc!= "")
                $orderquery.=" DESC";
        }
        $query="$query $wherequery $orderquery";
        $usenative=true;
        if (isset($_GET['debug']))
        {
            dprint_r(__FILE__." ".__LINE__." : ".FN_GetExecuteTimer());
        }
        if (!empty($config['search_query_native_mysql']))
        {
            $xmltable=FN_XmlTable($tablename);
            $query=str_replace("FROM $tablename WHERE","FROM {$xmltable->driverclass->sqltable} WHERE",$query);
            $res[$tablename]=$xmltable->driverclass->dbQuery($query);
        }
        else
        {
            $res[$tablename]=FN_XMLQuery($query);
        }

        //DEBUG: print query
        if (isset($_GET['debug']))
        {
            echo ("<div>".$query."</div>");
            dprint_r(__FILE__." ".__LINE__." : ".FN_GetExecuteTimer());
        }
//----------------export------------------------------------------------------->
        if (!empty($res[$tablename]) && !empty($config['enable_export']) && isset($_GET['export']))
        {
            $first=true;
            $csvres=array();
            foreach($res[$tablename] as $row)
            {
                $rec=$_tables[$tablename]->xmltable->GetRecordByPrimarykey($row[$_tables[$tablename]->xmltable->primarykey]);
                if ($first)
                {
                    $first=false;
                    foreach($rec as $k=> $v)
                    {
                        $title=$k;
                        if (isset($_tables[$tablename]->formvals[$k]['title']))
                            $title=$_tables[$tablename]->formvals[$k]['title'];
                        $r[$k]=$title;
                    }
                    $csvres[]=$r;
                }
                $csvres[]=$rec;
                //break;
            }
            FNNAV_SaveToCSV($csvres,"export.csv");
        }
//----------------export------------------------------------------------------->		
    }
    //  dprint_r(__LINE__." : ".FN_GetExecuteTimer());

    return $res;
}

/**
 *
 * @param type $data 
 */
function FNNAV_SaveToCSV($data,$filename)
{
    $sep=",";
    $str="";
    foreach($data as $row)
    {
        $arraycols=array();
        foreach($row as $cell)
        {
            $arraycols[]="\"".str_replace("\"","\"\"",$cell)."\"";
        }
        $str.=implode($sep,$arraycols)."\n";
    }
    FN_SaveFile($str,$filename,"application/vnd.ms-excel");
}

/**
 *
 * @global array $_FN
 * @param type $params
 * @param type $sep
 * @param type $norewrite
 * @return string 
 */
function FNNAV_MakeLink($params=false,$sep="&amp;",$norewrite=false)
{

    global $_FN;
    $blank="____k_____";
    $register=array("mod","op","q","page","order","desc","nav","rule","viewmode");
    $search_min="";
    $search_options="";
    $navigate_groups="";
    $search_fields="";
    $search_partfields="";
    $config=FN_LoadConfig();
    $tmp=explode(",",$config['search_min']);
    foreach($tmp as $key)
    {
        $register[]="min_".$key;
    }
    $tmp=explode(",",$config['search_fields']);
    foreach($tmp as $key)
    {
        $register[]="sfield_".$key;
    }
    $tmp=explode(",",$config['search_partfields']);
    foreach($tmp as $key)
    {
        $register[]="spfield_".$key;
    }
    $link=array();
    foreach($_GET as $key=> $value)
    {
        if (in_array($key,$register) || fn_erg("^s_opt_",$key) || fn_erg("^mint_",$key) || fn_erg("^nv_",$key))
        {
            $link[$key]="$key=".FN_GetParam("$key",$_GET);
        }
    }
    if (is_array($params))
    {
        foreach($params as $key=> $value)
        {
            $link[$key]="$key=".urlencode($params[$key]);
            if ($params[$key]=== null)
                unset($link[$key]);
            elseif ($params[$key]=== "")
                $link[$key]="$key=$blank";
        }
    }

    $link="index.php?".implode($sep,$link);
    if ($norewrite)
        return $_FN['siteurl'].str_replace($blank,"",$link);
    return str_replace($blank,"",FN_RewriteLink($link,$sep,true));
}

/**
 *
 * @param type $text
 * @param type $blacklist
 * @return type 
 */
function FNNAV_SecureHtml($text,$blacklist="script,iframe,frame,object,embed")
{
    $blacklist=explode(",",$blacklist);
    $ok=false;
    while($ok== false)
    {
        $ok=true;
        foreach($blacklist as $itemtag)
        {
            while(preg_match("/<$itemtag/s",$text))
            {
                $ok=false;
                $text=preg_replace("/<$itemtag/s","",$text);
                $text=preg_replace("/<\\/$itemtag>/s","",$text);
            }
        }
    }
    return $text;
}

/**
 *
 * @global array $_FN
 * @param type $file 
 */
function FNNAV_GoDownload($file)
{
    global $_FN;
    //--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
    //--config--<
    // evita di accedere a directory esterne
    if (stristr($file,".."))
        die(FN_Translate("you may not do that"));
    // se il file non esiste lo crea

    if ($config['enablestats']== 1)
    {
        if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/$tablename"."_download_stat") || !file_exists("{$_FN['datadir']}/{$_FN['database']}/$tablename"."_stat.php"))
        {
            //$html .= "<br>creazione statistiche $tablename";
            $sfields=array();
            $sfields[1]['name']="filename";
            $sfields[1]['primarykey']="1";
            $sfields[2]['name']="numdownload";
            $sfields[2]['defaultvalue']="0";
            createxmltable($_FN['database'],$tablename."_download_stat",$sfields,$pathdatabase);
        }
        $stat=FN_XmlTable($tablename."_download_stat");
        $oldval=$stat->GetRecordByPrimaryKey($file);
        $r['filename']=$file;
        if ($oldval== null)
        {
            $r['numdownload']=1;
            $stat->InsertRecord($r);
        }
        else
        {
            //incrementa download
            $r['numdownload']=$oldval['numdownload'] + 1;
            $stat->UpdateRecord($r);
        }
    }
    FN_SaveFile("{$_FN['datadir']}/{$_FN['database']}/$tablename/$file");
}

/**
 *
 * @param string $unirecid 
 */
function FNNAV_GetUsersComments($unirecid)
{
    global $_FN;
    //--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
    //--config--<
    $comments=FN_XMLQuery("SELECT DISTINCT username FROM {$tablename}_comments WHERE unirecidrecord LIKE '$unirecid'");
    $ret=false;
    foreach($comments as $comment)
    {
        $user=FN_GetUser($comment['username']);
        if (isset($user['email']))
        {
            $ret[$user['email']]=$user;
        }
    }
    return $ret;
}

/**
 * writecomment
 * aggiunge un commento al record
 * 
 * @param string unirecid record
 */
function FNNAV_WriteComment($unirecid)
{
    global $_FN;
    //--config-->
    $config=FN_LoadConfig();
    $html="";
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
    //--config--<
    $tablelinks=FN_XmlForm("$tablename"."_comments");
    $tablelinks->SetLayout("flat");
    $err=$newvalues=array();
    $exitlink=FNNAV_MakeLink(array("op"=>"view","id"=>$unirecid),"&");
    $formlink=FNNAV_MakeLink(array("op"=>"writecomment","id"=>$unirecid),"&");
    if (isset($_POST['comment']))
    {
        $newvalues=$tablelinks->getbypost();
        $newvalues['comment']=htmlspecialchars($newvalues['comment']);
        $newvalues['unirecidrecord']=$unirecid;
        $newvalues['username']=$_FN['user'];
        $newvalues['insert']=time();
        $err=$tablelinks->Verify($newvalues);
        if (count($err)== 0)
        {
            $tablelinks->xmltable->InsertRecord($newvalues);
//---------- send mail -------------------------------------------------------->
            if (!empty($config['enable_comments_notify']))
            {
                $Table=FN_XmlForm($tablename);
                $row=$Table->xmltable->GetRecordByPrimarykey($unirecid);
                $uservalues=FN_GetUser($newvalues['username']);
                $rname=$row[$Table->xmltable->primarykey];
                if (isset($row['name']))
                    $rname=$row['name'];
                else
                {
                    foreach($Table->xmltable->fields as $gk=> $g)
                    {
                        if (!isset($g->frm_show) || $g->frm_show!= 0)
                        {
                            $rname=$row[$gk];
                            break;
                        }
                    }
                }
                $usercomments=FNNAV_GetUsersComments($unirecid);
                if (!empty($uservalues['email']))
                {
                    $usercomments[$uservalues['email']]=$uservalues;
                }

                $userlang=$_FN['lang_default'];
                $usersended=array();
                //-------email to comment ownwer------------------------------->
                foreach($usercomments as $usercomment)
                {
                    if (isset($usercomment['lang']))
                        $userlang=$usercomment['lang'];
                    //dprint_r($uservalues);
                    //dprint_r($usercomment);
                    if ($uservalues['email']== $usercomment['email']) //onwer
                    {
                        $body=$_FN['user']." ".FN_Translate("added a comment to your content","aa");
                    }
                    else
                    {
                        $body=$_FN['user']." ".FN_Translate("added a comment","aa");
                    }
                    $body.="<br /><br />$rname<br /><br />".FN_Translate("to see the comments go to this address","aa",$userlang);
                    $link=FN_RewriteLink("index.php?mod={$_FN['mod']}&op=view&id=$unirecid","&",true);
                    $body.="<br /><a href=\"$link\">$link</a><br /><br />";
                    if (!isset($usersended[$usercomment['email']]))
                    {
                        FN_SendMail($usercomment['email'],$_FN['sitename']."-".$_FN['sectionvalues']['title'],$body,true);
                    }
                    $usersended[$usercomment['email']]=$usercomment['email'];
                }
                //-------email to comment ownwer-------------------------------<
                //-------email to recotd ownwer-------------------------------->
                $MyTable=FN_XmlForm($tablename);
                $Myrow=$MyTable->xmltable->GetRecordByPrimaryKey($unirecid);
                $Myuser_record=FN_GetUser($Myrow['username']);
                if (!isset($usersended[$Myuser_record['email']]))
                {
                    FN_SendMail($Myuser_record['email'],$_FN['sitename']."-".$_FN['sectionvalues']['title'],$body,true);
                }
                //-------email to recotd ownwer--------------------------------<
            }
//---------- send mail --------------------------------------------------------<
        }
        $html.=FN_Translate("the message has been sent")."<br />";
        $html.="<button type=\"button\" class=\"button\" onclick=\"window.location='$exitlink'\" >".FN_Translate("next")."</button>";
        return $html;
    }

    if ($_FN['user']!= "" && $unirecid!= "")
    {
        $html.="<br />";
        $html.="\n<form method=\"post\" enctype=\"multipart/form-data\" action=\"$formlink\" >";
        $html.="\n<table>";
        $html.="\n<tr><td colspan=\"2\"><b>".FN_Translate("add comment")."</b></tr></td>";
        $html.="\n<tr><td colspan=\"2\">".FN_Translate("required fields")."</tr></td>";
        $html.="\n<tr><td colspan=\"2\">";
        $html.=FN_htmlBbcodesPanel("comment","formatting");
        $html.=FN_htmlBbcodesPanel("comment","emoticons");
        $html.=FN_htmlBbcodesJs();
        $html.="<br />";
        $html.=$tablelinks->HtmlShowInsertForm(false,$newvalues,$err);
        $html.="\n</td></tr>";
        $html.="\n<tr><td colspan=\"2\"><input class=\"submit\" type=\"submit\" value=\"".FN_Translate("save")."\"/>";

        $html.="<input type='button' class='button' onclick='window.location=(\"$exitlink\")'  value='".FN_Translate("cancel")."' />";
        $html.="</tr></td>";
        $html.="\n";
        $html.="\n</table>";
        $html.="\n</form>";
    }
    $html.=FNNAV_ViewComments($unirecid);
    return $html;
}

/**
 * viewcomments
 * visualizza i commenti associati al record
 * 
 * @param string unirecid record
 */
function FNNAV_ViewComments($unirecid)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
//--config--<
    $tablelinks=FN_XmlForm("$tablename"."_comments");
    $r['unirecidrecord']=$unirecid;
    $commenti=$tablelinks->xmltable->GetRecords($r);
    $html="";
    if ($config['enablecomments'])
    {
        $html.="<a name=\"___comments\" id=\"___comments\"></a>";
        $html.=FN_HtmlOpenTableTitle(FN_Translate("comments")." :");
        if (is_array($commenti))
            foreach($commenti as $commento)
            {
                $html.="<div class=\"fnfilescomment\">";
                $html.="<b>".FN_Translate("from")."</b> ".$commento['username']." ";
                $html.="<b>".FN_Translate("date").":</b> ".(fn_GetDateTime($commento['insert']))."<br /><br />";
                $html.="<b>".htmlspecialchars($commento['title'])."</b><br />";
                $html.=FN_Tag2Html($commento['comment'])."<br /><br /><br />";
                if (FN_IsAdmin())
                {
                    $unirecidrecord=$commento['unirecid'];
                    $html.="<a href=\"javascript:check('?mod={$_FN['mod']}&op=delcomment&id=$unirecid&unirecidrecord=$unirecidrecord')\" >".FN_Translate("delete")."</a>";
                }
                $html.="</div>";
                $html.="<hr />";
            }
        if ($_GET['op']!= "writecomment")
        {
            if ($_FN['user']!= "" && $config['enablecomments']!= 0)
            {
                $html.="<br />[<img style=\"vertical-align:middle\" src=\"".FN_FromTheme("images/add.png")."\" alt=\"\" title=\"\" />&nbsp;";
                $html.="<a href=\"index.php?mod={$_FN['mod']}&amp;op=writecomment&amp;id=$unirecid\" >".FN_Translate("add comment")."</a>]<br />";
            }
            else
            {
                $html.=FN_Translate("you must be registered to")." ".$_FN['sitename']." ".FN_i18n("to post a comment");
                $html.=FN_HtmlLoginForm();
            }
        }
        $html.=FN_HtmlCloseTableTitle();
    }
    return $html;
}

/**
 * delcomment
 * 
 * elimina un commento dal record
 * 
 * @param string unirecid record 
 */
function FNNAV_DelComment($unirecid)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
//--config--<
    $html="";
    $tablelinks=FN_XmlForm("$tablename"."_comments");
    if (FN_IsAdmin() && isset($_GET['unirecidrecord']) && $_GET['unirecidrecord']!= "")
    {
        $r['unirecid']=$_GET['unirecidrecord'];
        $tablelinks->xmltable->DelRecord($r['unirecid']);
        $html.=FN_Translate("the comment was deleted")."<br />";
        FN_Log("{$_FN['mod']}",$_SERVER['REMOTE_ADDR']."||".$_FN['user']."||Table $tablename delete comments in record $unirecid");
        $Table=FN_XmlForm($_FN['database']);
        $newvalues=$Table->xmltable->GetRecordByPrimaryKey($unirecid);
        $newvalues['update']=time();
        $Table->xmltable->UpdateRecord($newvalues);
    }
    $html.=FNNAV_ViewComments($unirecid);
    return $html;
}

/**
 *
 * @global array $_FN
 * @param type $unirecid 
 */
function FNNAV_DelRecordForm($unirecid)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
//--config--<
    $html="";
    $Table=FN_XmlTable($tablename);
    $row=$Table->GetRecordByPrimaryKey($unirecid);
    if (empty($config['enable_delete']) || $row== null)
        die(FN_Translate("you may not do that"));

    if (!FNNAV_IsAdminRecord($row))
        die(FN_Translate("you may not do that"));

    //hide record 
    if (!empty($config['hide_on_delete']))
    {
        if (!isset($Table->fields['recorddeleted']))
        {
            $tfield['name']="recorddeleted";
            $tfield['type']="bool";
            $tfield['frm_show']="0";

            addxmltablefield($Table->databasename,$Table->tablename,$tfield,$Table->path);
        }
        $newvalues=array("unirecid"=>$unirecid,"recorddeleted"=>1);
        $Table->UpdateRecord($newvalues);
    }
    //delete record
    else
    {
        if ($row!= null)
            $Table->DelRecord($unirecid);
        // elimino i permessi sul record
        $restr=array();
        $listusers=FN_XmlTable("fieldusers");
        $restr['table_unirecid']=$row[$Table->primarykey];
        $restr['tablename']=$tablename;
        $list_field=$listusers->GetRecords($restr);
        if (is_array($list_field))
        {
            foreach($list_field as $field)
            {
                $listusers->DelRecord($field['unirecid']);
            }
        }
        $Table->DelRecord($unirecid);
        if (isset($_FN['modparams'][$_FN['mod']]['editorparams']['table']['function_on_delete']))
        {
            $function=$_FN['modparams'][$_FN['mod']]['editorparams']['table']['function_on_delete'];
            if (function_exists($function))
            {
                $function($newvalues);
            }
        }
    }
    FNNAV_WriteSitemap();
    $html.="<br />".FN_Translate("record was deleted");
    $html.="";
    $link=FNNAV_MakeLink(array("op"=>null)); //list link
    $html.="<br /><br /><button onclick=\"window.location='$link'\"><img border=\"0\" style=\"vertical-align:middle\" src=\"".FN_FromTheme("images/left.png")."\" alt=\"\">&nbsp;".FN_Translate("go to the contents list")."</button>";
    return $html;
}

/**
 *
 * @global array $_FN
 * @param string $unirecid
 * @param object $Table
 * @param array $errors
 * @return type 
 */
function FNNAV_EditRecordForm($unirecid,$Table,$errors=array(),$reloadDataFromDb=false)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
//--config--<
    $tb=FN_XmlTable($tablename);
    $row=$tb->GetRecordByPk($unirecid);
    $html="";
    if (!FNNAV_IsAdminRecord($row))
    {
//----------visualizza modifica se l' utente e' abilitato ------------>>
        $html.="<div class=\"fnfilesuserbar\" >";
        $html.=FNNAV_HtmlToolbar($config,$row);
        $html.="</div>";
//----------visualizza modifica se l' utente e' abilitato ------------<<		
        $html.=FNNAV_Request($unirecid);

        return $html;
    }
    $html.="
<script type=\"text/javascript\">
//<!--
function set_changed()
{
    var allButtons = document.getElementsByTagName('*');
    for (var i in allButtons)
    {
        if (allButtons[i].onclick)
        {
            try{
            if (allButtons[i].getAttribute && allButtons[i].getAttribute('onclick').indexOf('window.location')==0)
            {
                //console.log(allButtons[i].getAttribute('onclick'));
                allButtons[i].setAttribute('onclick','if (confirm_exitnosave()){'+allButtons[i].getAttribute('onclick')+'}');
            }
            }catch(e){ console.log(e);}
        }
    }
      var allLinks = document.getElementsByTagName('a');
	for (var i in allLinks)
	{
		if (!allLinks[i].onclick || allLinks[i].onclick=='' || allLinks[i].onclick==undefined && allLinks[i].href )
		{
                try{
			if (allLinks[i].setAttribute)
			{
				allLinks[i].setAttribute('onclick','return confirm_exitnosave()');
			}
                        }catch(e){ console.log(e);}
		}
	}
}
function confirm_exitnosave()
{
	if(confirm ('".addslashes(FN_Translate("you exit without to save?"))."'))
	{
		return true;
	}
	return false;
}
//-->
</script>	
";
    if (isset($_POST['__NOSAVE']))
    {
        $html.="
<script type=\"text/javascript\">
//<!--
set_changed();
//-->
</script>";
    }
//----------visualizza modifica se l' utente e' abilitato ------------>>
    $html.="<div class=\"fnfilesuserbar\" >";
    $html.=FNNAV_HtmlToolbar($config,$row);
    $html.="</div>";

//----------visualizza modifica se l' utente e' abilitato ------------<<
    //----template--------->
    $tplfile=file_exists("sections/{$_FN['mod']}/formedit.tp.html") ? "sections/{$_FN['mod']}/formedit.tp.html" : FN_FromTheme("modules/navigator/formedit.tp.html",false);
    $template=file_get_contents($tplfile);
    $tpvars=array();
    $tpvars['formaction']=FNNAV_MakeLink(array("op"=>"updaterecord","id"=>$unirecid),"&amp;"); //index.php?mod={$_FN['mod']}&amp;op=updaterecord&amp;id=$unirecid
    $tpvars['urlcancel']=FNNAV_MakeLink(array("op"=>null,"id"=>null),"&");
    $template=FN_TPL_ApplyTplString($template,$tpvars);
    $Table->SetlayoutTemplate($template);    //----template---------<    

    if (!isset($_GET['inner']))
    {
        $forcelang=isset($_GET['forcelang']) ? $_GET['forcelang'] : $_FN['lang'];
        if ($reloadDataFromDb)
            $nv=$row;
        else
            $nv=$Table->getbypost();
        $html.=$Table->HtmlShowUpdateForm($unirecid,FN_IsAdmin(),$nv,$errors);
        $pk=$Table->xmltable->primarykey;
    }

//editor inner tables ----------------------------------------------------->
    if ($Table->innertables)
    {

        foreach($Table->innertables as $k=> $v)
        {
            if (isset($_GET['inner']))
            {
                if (!isset($_GET["op___xdb_".$v['tablename']]))
                {
                    //dprint_r($_FN);
                    continue;
                }
            }

            $params=array();
            if (isset($_FN['modparams'][$_FN['mod']]['editorparams']['innertables'][$v["tablename"]]))
                $params=$_FN['modparams'][$_FN['mod']]['editorparams']['innertables'][$v["tablename"]];

            $title=$v['tablename'];
            $innertablemaxrows=isset($v['innertablemaxrows']) ? $v['innertablemaxrows'] : "";

            $tmptable=FN_XmlForm($v["tablename"],$params);
            if (FNNAV_CanEditRecord($Table->xmltable->primarykey,$v["tablename"]))
            {
                $v['enabledelete']=true;
            }


            if (isset($v["frm_{$_FN['lang']}"]))
                $title=$v["frm_{$_FN['lang']}"];
            $html.="<div class=\"FNNAV_innerform\">";
            $innertile=$title;

            if (isset($_GET['inner']))
            {
                $innertile="{$_FN['sections'][$_FN['mod']]['title']} -&gt; {$title}";
                $tmptitle=explode(",",$config['titlefield']);
                foreach($tmptitle as $tmp_t)
                {
                    $sep=" -&gt; ";
                    if (!empty($row[$tmp_t]))
                    {
                        $innertile.="$sep".$row[$tmp_t];
                        $sep=" ";
                    }
                }
            }
            $html.="<h3>$innertile</h3>";
            $params['path']=$Table->path;
            $params['enableedit']=true;
            $params['maxrows']=$innertablemaxrows;
            $params['enablenew']=(!isset($v["enablenew"]) || $v["enablenew"]== 1);
            $params['enabledelete']=(!empty($v["enabledelete"]));
            $tplfile=file_exists("sections/{$_FN['mod']}/forminner.tp.html") ? "sections/{$_FN['mod']}/forminner.tp.html" : FN_FromTheme("modules/navigator/forminner.tp.html",false);
            $templateInner=file_get_contents($tplfile);
            $params['layout_template']=$templateInner;
            $link=FNNAV_MakeLink(array("op"=>"edit","id"=>$unirecid,"inner"=>"1"),"&",true);
            $link=explode("index.php?",$link);
            $params['link']=$link[1];
            $link=FNNAV_MakeLink(array("op"=>"edit","id"=>$unirecid,"inner"=>null),"&",true);
            $params['link_listmode']=$link;
            $params['textviewlist']="";
            if (isset($v['innertablefields']) && $v['innertablefields']!= "")
            {
                $params['fields']=str_replace(",","|",$v['innertablefields']);  //innertablefields	
            }


            //op___xdb_
            $t=explode(",",$v["linkfield"]);
            if (isset($t[1]) && $t[1]!= "" && isset($row[$t[0]]))
                $params['restr']=array($t[1]=>$row[$t[0]]);
            $params['restr']=isset($params['restr']) ? $params['restr'] : false;
            $params['forcenewvalues']=$params['forceupdatevalues']=$params['restr'];
            //dprint_r($params);
            if (isset($v["tablename"]) && isset($row[$Table->xmltable->primarykey]))
            {
                ob_start();
                $params['textnew']=FN_Translate("add a new item into")." ".$title;
                FN_xmltableeditor($v["tablename"],$params);
                $html.=ob_get_clean();
            }
            
            
            /*
              if (isset($_GET["op___xdb_".$v['tablename']]))
              {
              $linknewinner = "index.php?page___xdb_{$v['tablename']}=&desc___xdb_{$v['tablename']}=&op___xdb_{$v['tablename']}=insnew&page___xdb_{$v['tablename']}=&mod={$_FN['mod']}&op=edit&id=1&inner=1";
              $html.="&nbsp;<button type=\"button\" onclick=\"window.location='?".str_replace("&amp;","&",$linknewinner)."'\" >".FN_Translate("add a new item into")."</button>";
              }
             */
            $html.="</div>";
        }
    }

//editor inner tables -----------------------------------------------------<
    if (empty($_GET['embed']) && empty($_GET['inner']))
    {
        $listlink=FNNAV_MakeLink(array("op"=>null,"id"=>null),"&");
        $html.="<br /><br />";
        $linkCopyAndNew=FN_RewriteLink("index.php?op=new&id=$unirecid","&",true);
        $html.="<button type=\"button\" onclick=\"document.getElementById('frmedit').action='$linkCopyAndNew';document.getElementById('frmedit').submit();\" ><img style=\"vertical-align:middle\" src=\"".FN_FromTheme("images/modify.png")."\" alt=\"\">&nbsp;".FN_Translate("copy data and add new")."</button>";

        $html.="<button type=\"button\" onclick=\"window.location='$listlink'\"><img style=\"vertical-align:middle\" src=\"".FN_FromTheme("images/up.png")."\" alt=\"\">&nbsp;".FN_Translate("view list")."</button>";
        $link=FNNAV_MakeLink(array("op"=>"view","id"=>$unirecid,"inner"=>null));

        $html.=" <button type=\"button\" id=\"exitform2\"  onclick=\"window.location='$link'\"><img style=\"vertical-align:middle\" src=\"".FN_FromTheme("images/left.png")."\" alt=\"\">&nbsp;".FN_Translate("exit and view")."</button>";
    }
    else
    {

        $editlink=FNNAV_MakeLink(array("op"=>"edit","id"=>$unirecid,"inner"=>null),"&");
        $html.="<br />
		<br />
		<button onclick=\"window.location='$editlink'\" >
		<img border=\"0\" style=\"vertical-align:middle\" src=\"".FN_FromTheme("images/left.png")."\" alt=\"\" />&nbsp;".FN_Translate("back")."</button>";
    }
    return $html;
}

/**
 *
 * @global array $_FN
 * @param object $Table
 * @param array $errors 
 */
function FNNAV_NewRecordForm($Table,$errors=array())
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
//--config--<
    //----template--------->
    $tplfile=file_exists("sections/{$_FN['mod']}/form.tp.html") ? "sections/{$_FN['mod']}/form.tp.html" : FN_FromTheme("modules/navigator/form.tp.html",false);
    $template=file_get_contents($tplfile);
    $tpvars=array();
    $tpvars['formaction']=FNNAV_MakeLink(array("op"=>"new"),"&amp;");
    $tpvars['urlcancel']=FNNAV_MakeLink(array("op"=>null,"id"=>null),"&");
    $template=FN_TPL_ApplyTplString($template,$tpvars);
    $Table->SetlayoutTemplate($template);
    $html="";
    //----template---------<
//----gestione esci senza salvare ------->
    $html.="
<script type=\"text/javascript\">
function set_changed()
{
try{
    document.getElementById('exitform').setAttribute('onclick','confirm_exitnosave()');
    }catch(e){}
}
function confirm_exitnosave()
{
    if(confirm ('".addslashes(FN_Translate("you exit without to save?"))."'))
    {
        window.location='?mod={$_FN['mod']}';
    }
}
</script>";

    if (isset($_POST['__NOSAVE']))
    {
        $html.="
<script type=\"text/javascript\">
set_changed();
</script>";
    }
//----gestione esci senza salvare -------<
    $nv=$Table->getbypost();
    $Table->ShowInsertForm(FN_IsAdmin(),$nv,$errors);
}

/**
 *
 * @global array $_FN
 * @param object $Table 
 */
function FNNAV_UpdateRecord($Table)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
//--config--<
    $Table=FN_XmlForm($tablename);
    $username=$_FN['user'];
    if ($username== "")
        die(FN_Translate("you may not do that"));
    $newvalues=$Table->getbypost();
    if (isset($_POST["_xmldbform_pk_".$Table->xmltable->primarykey]))
        $pkold=FN_GetParam("_xmldbform_pk_".$Table->xmltable->primarykey,$_POST);
    else
        $pkold=FN_GetParam($Table->xmltable->primarykey,$_POST);
    $pk=FN_GetParam($Table->xmltable->primarykey,$_POST);
    $oldvalues=$Table->xmltable->GetRecordByPrimarykey($pkold);
    if (!FNNAV_CanAddRecord() && !FNNAV_UserCanEditField($username,$oldvalues))
        return (FN_Translate("you may not do that"));
    $toupdate=false;
    if (is_array($oldvalues))
        foreach($oldvalues as $k=> $v)
        {
            if (isset($newvalues[$k]) && $oldvalues[$k]!== $newvalues[$k])
            {
                $toupdate=true;
                break;
            }
            if (isset($newvalues[$k]) && $newvalues[$k]!= "" && $oldvalues[$k]== $newvalues[$k] && ($Table->xmltable->fields[$k]->type== "file" || $Table->xmltable->fields[$k]->type== "image"))
            {
                $filename=$Table->xmltable->getFilePath($oldvalues,$k);
                if (filesize($filename)!= filesize($_FILES[$k]['tmp_name']))
                {
                    // die ("$filename toupdate");
                    $toupdate=true;
                    break;
                }
            }
        }
    $newvalues['update']=time();
    foreach($Table->formvals as $f)
    {
        if (isset($newvalues[$f['name']]) && isset($Table->formvals[$f['name']]['frm_uppercase']))
        {
            if ($Table->formvals[$f['name']]['frm_uppercase']== "uppercase")
            {
                $_POST[$f['name']]=$newvalues[$f['name']]=strtoupper($newvalues[$f['name']]);
            }
            elseif ($Table->formvals[$f['name']]['frm_uppercase']== "lowercase")
            {
                $_POST[$f['name']]=$newvalues[$f['name']]=strtolower($newvalues[$f['name']]);
            }
        }
        if (isset($Table->formvals[$f['name']]['frm_onrowupdate']) && $Table->formvals[$f['name']]['frm_onrowupdate']!= "")
        {
            $dv=$Table->formvals[$f['name']]['frm_onrowupdate'];
            $fname=$f['name'];
            $rv="";
            eval("\$rv=$dv;");
            eval("\$newvalues"."['$fname'] = '$rv' ;");
        }
    }

    $errors=$Table->VerifyUpdate($newvalues,$pkold);
    if ($pkold!= $pk)
    {
        $newExists=$Table->xmltable->GetRecordByPrimaryKey($pk);
        if (isset($newExists[$Table->xmltable->primarykey]))
        {
            $newvalues[$Table->xmltable->primarykey]=$pkold;
            $errors[$Table->xmltable->primarykey]=array("title"=>$Table->formvals[$Table->xmltable->primarykey]['title'],"field"=>$Table->xmltable->primarykey,"error"=>FN_Translate("there is already an item with this value"));
        }
    }
    if (count($errors)== 0)
    {
        if (FN_IsAdmin())
        {
            if (!isset($_POST['userupdate']) || $_POST['userupdate']== "")
            {
                $_POST['userupdate']=$newvalues['userupdate']=$_FN['user'];
            }
        }
        else
            $newvalues['userupdate']=$_FN['user'];
        //-----verifica se sono abilitato all' aggiornamento ---------------
        if ($toupdate)
        {
            //--------------history-------------------------------------------->
            $newvalues['recordupdate']=xmldb_now();
            if ($config['enable_history'])
            {
                $_FILES_bk=$_FILES;
                $_FILES=array();
                $tv=FN_XmlTable($tablename."_versions");
                foreach($Table->xmltable->fields as $k=> $v)
                {
                    if (($v->type== "file" || $v->type== "image") && $oldvalues[$k]!= "")
                    {
                        $oldfile=$Table->xmltable->getFilePath($oldvalues,$k);
                        $_FILES[$k]['name']=$oldvalues[$k];
                        $_FILES[$k]['tmp_name']=$oldfile;
                    }
                }
                $bb=$tv->InsertRecord($oldvalues);
                $_FILES=$_FILES_bk;
            }
            //--------------history--------------------------------------------<
            $Table->UpdateRecord($newvalues,$pkold);
            FN_Log("{$_FN['mod']}",$_SERVER['REMOTE_ADDR']."||".$_FN['user']."||Table $tablename modified.");
            FN_Alert(FN_Translate("record updated"));
        }
    }
    if (isset($_FN['modparams'][$_FN['mod']]['editorparams']['table']['function_on_update']))
    {
        $function=$_FN['modparams'][$_FN['mod']]['editorparams']['table']['function_on_update'];
        if (function_exists($function))
        {
            $function($newvalues);
        }
    }

    return FNNAV_EditRecordForm($newvalues[$Table->xmltable->primarykey],$Table,$errors);
}

/**
 * insert record
 * @global array $_FN
 * @param type $Table 
 */
function FNNAV_InsertRecord($Table)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
//--config--<
    $html="";
//-----verifica se sono abilitato all' inserimento ---------------
    $username=$_FN['user'];
    if (!FNNAV_CanAddRecord())
        die(FN_Translate("you may not do that"));
    $newvalues=$Table->getbypost();
    $newvalues['insert']=time();
    $newvalues['update']=time();
    $newvalues['username']=$username;
    foreach($Table->formvals as $f)
    {
        if (isset($newvalues[$f['name']]) && isset($Table->formvals[$f['name']]['frm_uppercase']))
        {
            if ($Table->formvals[$f['name']]['frm_uppercase']== "uppercase")
            {
                $_POST[$f['name']]=$newvalues[$f['name']]=strtoupper($newvalues[$f['name']]);
            }
            elseif ($Table->formvals[$f['name']]['frm_uppercase']== "lowercase")
            {
                $_POST[$f['name']]=$newvalues[$f['name']]=strtolower($newvalues[$f['name']]);
            }
        }
        if ((isset($Table->formvals[$f['name']]['frm_onrowupdate']) && $Table->formvals[$f['name']]['frm_onrowupdate']!= ""))
        {
            $dv=$Table->formvals[$f['name']]['frm_onrowupdate'];
            $fname=$f['name'];
            $rv="";
            eval("\$rv=$dv;");
            eval("\$newvalues"."['$fname'] = '$rv' ;");
        }
    }
    //dprint_r($newvalues);
    //die();
    $errors=$Table->VerifyInsert($newvalues);

    if (count($errors)== 0)
    {
        $newvalues['recordupdate']=xmldb_now();
        $newvalues['recordinsert']=xmldb_now();
        $newvalues['userupdate']=$_FN['user'];
        $newvalues['username']=$_FN['user'];

        if (!empty($config['enable_permissions_edit_each_records']) && $config['enable_permissions_edit_each_records']== 1)
        {
            if ($config['permissions_records_edit_groups']!= "")
            {
                $allAllowedGroups=explode(",",$config['permissions_records_edit_groups']);
                $groupinsert=array();
                foreach($allAllowedGroups as $allAllowedGroup)
                {
                    if ($allAllowedGroup!= "" && FN_UserInGroup($_FN['user'],$allAllowedGroup))
                    {
                        $groupinsert[]=$allAllowedGroup;
                    }
                }
                $groupinsert=implode(",",$groupinsert);
                if (!FNNAV_IsAdmin())
                {
                    $newvalues['groupinsert']=$groupinsert;
                }
            }
        }
        $record=$Table->xmltable->InsertRecord($newvalues);
        $nrec=array();
        // se esistono i campi "visualizzato volte"
        if (isset($record['view']))
        {
            $nrec['view']=$record[$Table->xmltable->primarykey];
            $nrec[$Table->xmltable->primarykey]=$record[$Table->xmltable->primarykey];
            $record=$Table->xmltable->UpdateRecord($nrec);
        }
        //------ aggiorno la tabella degli utenti associati alla riga
        $users=FN_XmlTable("fieldusers");
        $r=array();
        $r['tablename']=$tablename;
        $r['username']=$username;
        $r['table_unirecid']=$record[$Table->xmltable->primarykey];
        $users->InsertRecord($r);

        if (isset($_FN['modparams'][$_FN['mod']]['editorparams']['table']['function_on_insert']))
        {
            $function=$_FN['modparams'][$_FN['mod']]['editorparams']['table']['function_on_insert'];
            if (function_exists($function))
            {
                $function($newvalues);
            }
        }

        FN_Log("{$_FN['mod']}",$_SERVER['REMOTE_ADDR']."||".$username."||Table $tablename record added.");
        $html.=FN_HtmlAlert(FN_Translate("the data were successfully inserted"));
        //----mail inserimento nuovo record -------->
        if (!empty($config['mailalert']))
        {
            $subject=FN_Translate("created new record in")." {$_FN['sectionvalues']['title']}";
            if (!empty($record['name']))
                $subject.=": ".$record['name'];
            $body="\n".FN_Translate("posted by")." ".$r['username'];
            $body.="\n\n".FN_Translate("to view go to the address").": ";
            $body.="\n".$_FN['siteurl']."/index.php?mod={$_FN['mod']}&op=view&id=".$record[$Table->xmltable->primarykey];
            $body.="\n\n".$_FN['sitename']."";
            FN_SendMail($config['mailalert'],$subject,$body,false);
        }
        //----mail inserimento nuovo record --------<
        $html.=FNNAV_EditRecordForm($record[$Table->xmltable->primarykey],$Table,$errors,true);
    }
    else
    {
        $html.=FNNAV_NewRecordForm($Table,$errors);
    }
    return $html;
}

function FNNAV_IsAdmin()
{
    if (FN_IsAdmin())
        return true;
    global $_FN;
    $config=FN_LoadConfig();
    if (!empty($config['groupadmin']) && FN_UserInGroup($_FN['user'],$config['groupadmin']))
        return true;
    return false;
}

/**
 * 
 * @global array $_FN
 * @param type $row
 * @param type $tablename
 * @param type $databasename
 * @param type $pathdatabase
 * @return type
 */
function FNNAV_GetFieldUser($row,$tablename,$databasename,$pathdatabase)
{
    global $_FN;
    $listusers=FN_XmlTable("fieldusers");
    $t=FN_XmlTable($tablename);
//restrizioni per la 'pseudoquery'
    $restr=array();
    $field['username']='-';
    $restr['table_unirecid']=$row[$t->primarykey];
    $restr['tablename']=$tablename;
    $listusers=FN_XmlTable("fieldusers");
    $field=$listusers->GetRecord($restr);
    return $field['username'];
}

/**
 * getFieldUserList
 * torna gli utenti abilitati a modificare un record
 * @param string $row
 * @param string $tablename
 * @param string $databasename
 * @param string $pathdatabase
 */
function FNNAV_GetFieldUserList($row,$tablename)
{
    static $userPerm=false;
    $t=FN_XmlTable($tablename);
    if (!$userPerm)
    {
        $listusers=FN_XmlTable("fieldusers");
        $userPerm=$listusers->GetRecords();
    }
    $ret=array();
    foreach($userPerm as $row_perm)
    {
        if ($row[$t->primarykey]== $row_perm['table_unirecid'] && $tablename== $row_perm['tablename'])
        {
            $ret[]=$row_perm;
        }
    }
    return $ret;
}

/**
 * isUser
 * torna true se l' utente corrente pu? modificare il record
 * @param string $row
 * @param string $tablename
 * @param string $databasename
 * @param string $pathdatabase */
function FNNAV_IsAdminRecord($row)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tablename=$config['tables'];
//--config--<
    if (FN_IsAdmin())
        return true;
    $user=$_FN['user'];
    if ($_FN['user']== "")
        return false;
    if (isset($row['username']) && $row['username']== $_FN['user'])
        return true;
    if (isset($row['user']) && $row['user']== $user)
        return true;
    if ($_FN['user']!= "" && $config['groupadmin']!= "" && FN_UserInGroup($_FN['user'],$config['groupadmin']))
    {
        return true;
    }
    //permessi per ogni record------------------------------------------------->
    if (empty($config['viewonlycreator']))
    {
        if (!empty($config['enable_permissions_edit_each_records']) && $config['enable_permissions_edit_each_records']== 1)
        {
            $record=$row;
            if (empty($record['groupinsert']))
            {
                return true;
            }
            else
            {
                $groups_can_insert=explode(",",$record['groupinsert'].",".$config['groupadmin']);
                foreach($groups_can_insert as $gr_can_insert)
                {
                    if ($gr_can_insert!= "" && FN_UserInGroup($_FN['user'],$gr_can_insert))
                    {
                        return true;
                    }
                }
                return false;
            }
        }
        else
        {
            /*
              if (empty($record['groupinsert']) )
              {

              return true;
              }
             * 
             */
        }
    }
    //permessi per ogni record-------------------------------------------------<	

    if (FNNAV_UserCanEditField($user,$row))
    {
        return true;
    }

    return false;
}

/**
 * canaddrecord
 * return true if user can add record
 */
function FNNAV_CanAddRecord()
{
    global $_FN;
    if (FN_IsAdmin())
        return true;

    $config=FN_LoadConfig();
    //dprint_r($config);
//include ("sections/" . $_FN['mod'] . "/config.php");
    if ($_FN['user']!= "" && $config['groupadmin']!= "" && FN_UserInGroup($_FN['user'],$config['groupadmin']))
        return true;
    if ($_FN['user']!= "" && $config['groupinsert']!= "" && FN_UserInGroup($_FN['user'],$config['groupinsert']))
        return true;
    if ($_FN['user']!= "" && $config['groupinsert']== "")
        return true;
    return false;
}

/**
 * canaddrecord
 * return true if user can view record
 *
 */
function FNNAV_CanViewRecords()
{
    global $_FN;
    if (FN_IsAdmin())
        return true;
    $config=FN_LoadConfig();
    if ($_FN['user']!= "" && $config['groupadmin']!= "" && FN_UserInGroup($_FN['user'],$config['groupadmin']))
        return true;
    if ($_FN['user']!= "" && $config['groupview']!= "" && FN_UserInGroup($_FN['user'],$config['groupview']))
        return true;
    if ($_FN['user']!= "" && $config['groupinsert']!= "" && FN_UserInGroup($_FN['user'],$config['groupinsert']))
        return true;
    if ($config['groupview']== "")
        return true;
    return false;
}

/**
 * makebr
 * 
 * trasforma i /n in <br />
 * @param $text
 *
 */
function FNNAV_MakeBr($text)
{
    global $_FN;
    return str_replace("\n","<br />",$text);
}

/**
 *
 * @global array $_FN
 * @param string $unirecid
 */
function FNNAV_UsersForm($unirecid)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
//--config--<
    $Table=FN_XmlTable($tablename);
    $row=$Table->GetRecordByPrimaryKey($unirecid);
    $pk=$Table->primarykey;
    $html="";
    $html.="<div class=\"fnfilesuserbar\" >";
    $html.=FNNAV_HtmlToolbar($config,$row);
    $html.="</div>";
    $titles=explode(",",$config['titlefield']);
    $t=array();
    foreach($titles as $tt)
    {
        $t[]=$row[$tt];
    }
    $title=implode(" ",$t);
    $html.="<h2>$title</h2>";
    $usertoadd=FN_GetParam("usertoadd",$_POST);
    $usertodel=FN_GetParam("usertodel",$_GET);
    if ($usertodel!= "")
    {
        $fieldusers=FN_XmlTable("fieldusers");
        $r=array();
        $r['tablename']=$tablename;
        $r['username']=$usertodel;
        $r['table_unirecid']=$unirecid;
        $old=$fieldusers->GetRecords($r);
        if (!isset($old[0]))
            $html.="error delete:".FN_Translate("this user not exists");
        $old=$old[0];
        $fieldusers->DelRecord($old[$fieldusers->primarykey]);
    }
    if ($usertoadd!= "")
    {
        if (FN_GetUser($usertoadd)== null)
        {
            $html.=FN_Translate("this user not exists");
        }
        else
        if (FNNAV_UserCanEditField($usertoadd,$row))
        {
            $html.=FN_Translate("this user is already enabled");
        }
        else
        {
            $fieldusers=FN_XmlTable("fieldusers");
            $r=array();
            $r['tablename']=$tablename;
            $r['username']=$usertoadd;
            $r['table_unirecid']=$unirecid;
            $fieldusers->InsertRecord($r);
            $rname=$row[$pk];
            if (isset($row['name']))
                $rname=$row['name'];
            else
                foreach($Table->fields as $gk=> $g)
                {
                    if (!isset($g->frm_show) || $g->frm_show!= 0)
                    {
                        $rname=$row[$gk];
                        break;
                    }
                }
            //dprint_r($Table->fields);
            $message=FN_Translate("you were added to the users allowed to edit this content")." \"".$rname."\" \n\n";
            $message.=FN_Translate("If you want to edit the content you have to login :")."\n".$_FN['siteurl']."index.php?mod=login\n";
            $message.=FN_Translate("and login as user").":\"$usertoadd\"\n";
            $message.=FN_Translate("then click on -user allowed to edit- and manage the permissions")."\n".$_FN['siteurl']."index.php?mod={$_FN['mod']}&op=edit&id=$unirecid\n";
            $user_record=FN_GetUser($usertoadd);
            $subject="[{$_FN['sitename']}] ".$rname;
            $to=FN_GetUser($usertoadd);
            FN_SendMail($to['email'],$subject,$message,false);
            FN_Log("{$_FN['mod']}",$_SERVER['REMOTE_ADDR']."||".$_FN['user']."||added user $usertoadd record: ".$rname." in table $tablename.");
        }
    }
    if (!FNNAV_IsAdminRecord($row))
    {
        return (FN_Translate("you may not do that"));
        return;
    }
    $link=FNNAV_MakeLink(array("op"=>"users","id"=>$row[$pk]));
    $html.="
	<form
		action=\"$link\"
		method=\"post\">
		<table>
			<tr>
				<td>";
    $html.=FN_Translate("add user");
    $html.=": </td>
			<td></td>
			<td><input type=\"text\" name=\"usertoadd\" /></td>
		</tr>
		<tr>
			<td colspan=\"2\"><input type=\"hidden\" name=\"$pk\"
			  value=\"$unirecid\" /> <input type=\"submit\" /></td>
		</tr>
	</table>
</form>
";
    $users=array();
    $users=FNNAV_GetFieldUserList($row,$tablename);
    if (is_array($users))
        foreach($users as $user)
        {
            $link=FNNAV_MakeLink(array("op"=>"users","id"=>$row[$pk],"usertodel"=>$user['username']));
            $html.="<br />".$user['username']."<input type=\"button\" value=\"".FN_Translate("delete")."\" onclick=\"check('$link')\" />";
        }
    $html.="<hr />";
    $link=FNNAV_MakeLink(array("op"=>"view","id"=>$row[$pk]));
    $html.="<br /><a href=\"$link\">".FN_Translate("next")."</a>"." <img style=\"vertical-align:middle\" src='".FN_FromTheme("images/right.png")."' alt='' border='0' />";
    return $html;
}

/**
 *
 * @global array $_FN
 * @param string $user
 * @param array $row
 * @return bool
 */
function FNNAV_UserCanEditField($user,$row)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
//--config--<
    if ($user== "")
        return false;
    $t=FN_XmlTable($tablename);
    $restr=array();
//    dprint_r($row);
    $restr['table_unirecid']=$row[$t->primarykey];
    $restr['tablename']=$tablename;
    $restr['username']=$user;
    $list_field=FNNAV_GetFieldUserList($row,$tablename,$_FN['database']);
    $unirecid=$row[$t->primarykey];
    if (is_array($list_field))
        foreach($list_field as $field)
        {

            if ($field['username']== $user && $field['table_unirecid']== $row[$t->primarykey] && $field['tablename']== $tablename)
                return true;
        }
    return false;
}

/**
 * @global array $_FN
 */
function FNNAV_WriteSitemap()
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
    $titlef=explode(",",$config['titlefield']);
    $titlef=$titlef[0];
//--config--<

    if ($config['generate_googlesitemap'])
    {
        $sBasePath=$url="http://".$_SERVER["HTTP_HOST"].DirName($_SERVER['PHP_SELF']);
        $Table=FN_XmlTable($tablename);
        $fieldstoread="$titlef|".$Table->primarykey;
        $data=$Table->GetRecords(false,false,false,false,false,$fieldstoread);
        $handle=fopen("sitemap-$tablename.xml","w");
        fwrite($handle,"<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.84\">\n");
        fwrite($handle,"<url>\n\t<loc>$sBasePath/index-$tablename.html</loc>\n</url>\n");
        foreach($data as $row)
        {
            $unirecid=$row[$Table->primarykey];
            fwrite($handle,"<url>\n\t<loc>$sBasePath/".FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;op=view&amp;id=$unirecid")."</loc>\n</url>\n");
        }
        fwrite($handle,"\n</urlset>");
        fclose($handle);
    }
    FNNAV_GenerateRSS();
}

/**
 *
 * @global array $_FN
 * @param string $unirecid
 */
function FNNAV_Request($unirecid)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
//--config--<
    $html="";
    if ($_FN['user']== "")
    {
        FN_JsRedirect(FN_RewriteLink("index.php?mod={$_FN['mod']}&op=view&id=$unirecid"));
        return "";
    }
    if (isset($_POST['message']))
    {
        $Table=FN_XmlForm($tablename);
        $row=$Table->xmltable->GetRecordByPrimaryKey($unirecid);
        if (!empty($row['username']))
        {
            $user_record=FN_GetUser($row['username']);
        }
        else
        {

            $user_record=array("username"=>"");
        }
        $Table=FN_XmlTable($tablename);
        $rname=$row[$Table->primarykey];
        if (isset($row['name']))
            $rname=$row['name'];
        else
            foreach($Table->fields as $gk=> $g)
            {
                if (!isset($g->frm_show) || $g->frm_show!= 0)
                {
                    $rname=$row[$gk];
                    break;
                }
            }
        $user=FN_GetUser($user_record['username']);

        $subject="[{$_FN['sitename']}] ".$rname;
        $message=$_FN['user']." ".FN_Translate("has requested to modify this content","aa")." \"".$rname."\"\n\n\n";
        $message.=FN_Translate("to allow editing do login","aa")." ".$_FN['siteurl']."index.php?mod=login\n";
        $message.=FN_Translate("and login as user","aa").": \"".$user_record['username']."\"\n\n\n";
        $message.=FN_Translate("go to edit this content or log in","aa")." :\n".$_FN['siteurl']."index.php?mod={$_FN['mod']}&op=edit&id=$unirecid\n";
        $message.=FN_Translate("then click on -user allowed to edit- and manage the permissions","aa")." "."\"{$_FN['user']}\"";
        $message.="\n\n----------------------\n";
        $message.="\n".FN_StripPostSlashes($_POST['message']);
        if (!empty($user['email']) && FN_SendMail($user['email'],$subject,$message))
        {
            $html.="<br />".FN_Translate("request sent")."<br />";
        }
        else
        {
            $html.="<br />".FN_Translate("you can not send your request, please contact the administrator of the website")."<br />";
        }
        FN_Log("{$_FN['mod']}",$_SERVER['REMOTE_ADDR']."||".$_FN['user']."||request ".$rname." in table $tablename.");
    }
    else
    {
        $html.=FN_Translate("the creator of the object will be contacted to request you to be allowed. You can add comments in the box below.")."<br />";
        $html.="<form method=\"post\" action=\"index.php?mod={$_FN['mod']}&amp;op=request&amp;id=$unirecid\">";
        $html.="<textarea name=\"message\" cols=\"60\" rows=\"5\"></textarea><br />";
        $html.="<input type=\"submit\"  name=\"send\" value=\"".FN_Translate("demand modification")."\" class=\"submit\" />";
        $link=FNNAV_MakeLink(array("op"=>null),"&");
        $html.="\n<input type=\"button\" onclick=\"window.location='$link'\" class=\"button\" value=\"".FN_Translate("cancel")."\" />";
        $html.="</form>";
    }
    $link=FNNAV_MakeLink(array("op"=>null),"&");
    $tit=FN_Translate("back");
    $html.="<br /><br /><button onclick=\"window.location='$link'\"><img border=\"0\" style=\"vertical-align:middle\" src=\"".FN_FromTheme("images/left.png")."\" alt=\"\">&nbsp;".FN_Translate("go to the contents list")."</button>";
    return $html;
}

/**
 *
 * @param string $id
 * @param bool $small
 * @param string $tablename
 * @return string 
 */
function FNNAV_HtmlRank($id,$small=true,$tablename="")
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    if ($tablename== "")
    {
        $tables=explode(",",$config['tables']);
        $tablename=$tables[0];
    }
//--config--<

    $n=0;
    $rank=FNNAV_GetRank($id,$n,$tablename);
    $html="";
    $s="";
    if ($small)
        $s="_small";
    $html.="<div style=\"display:inline;line-height:10px;\">";
    if ($rank>= 0)
        for($i=0; $i < 5; $i++)
        {
            if ($i < $rank)
            {
                $html.="<img style=\"\" alt=\"\" src=\"{$_FN['siteurl']}modules/navigator/star$s.png\" />";
            }
            else
            {
                $html.="<img style=\"\" alt=\"\" src=\"{$_FN['siteurl']}modules/navigator/star_gray$s.png\" />";
            }
        }
    else
        $html.="-";
    $html.="</div>";
    return $html;
}

/**
 * ricava il rank di una scheda
 *
 * @param int $id
 * @return int
 */
function FNNAV_GetRank($id,&$n,$tablename)
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    if ($tablename== "")
    {
        $tables=explode(",",$config['tables']);
        $tablename=$tables[0];
    }
//--config--<
    $table=FN_XmlTable($tablename."_ranks");
    $res=$table->GetRecords(array("unirecidrecord"=>"$id"));
    $total=0;
    if (!is_array($res))
        $res=array();
    $n=count($res);
    if ($n== 0)
        return -1;
    foreach($res as $r)
    {
        $total+=$r['rank'];
    }
    $m=round(($total / $n),0);
    return $m;
}

/**
 *
 * @global array $_FN
 * @param string $id
 * @param bool $small
 * @param string $tablename
 * @return string
 */
function FNNAV_HtmlRankEditable($id,$small,$tablename)
{
    global $_FN;
    $n=0;
    $s="";
    if ($small)
        $s="_small";
    $cookie=FN_GetParam("fnfiles_rank_{$tablename}_{$id}",$_COOKIE);
    if ($cookie== "true")
        return FN_Translate("rank")." : ".FNNAV_HtmlRank($id,$small,$tablename);
//$rank = pdit_get_rank ( $id, $n ,$tablename);
    $html="";
    $smalltxt="small=0";
    if ($small)
        $smalltxt="small=1";
    $html.="
<script  type=\"text/javascript\">
function set_rank(v)
{
	window.location=\"{$_FN['siteurl']}index.php?mod={$_FN['mod']}&op=view&id=$id&setrank=\"+v+\"&#___ranks\";
}
function select_star(c)
{
	var images = document.getElementById('pdit_rank').getElementsByTagName('img');
	for (var i in images)
	{
		if ((i) >= c)
			images[i].src='{$_FN['siteurl']}modules/navigator/star_gray$s.png';
		else
			images[i].src='{$_FN['siteurl']}modules/navigator/star$s.png';
	}
}
</script>

";
//$view = pdit_get_view ( $id, $tablename );
    $html.="<div style=\"height:20px;\">".FN_Translate("vote")." : ";
    $html.="<div style=\"display:inline\" id=\"pdit_rank\">";
    $votes=array(1=>FN_Translate("poor"),2=>FN_Translate("inadeguate"),3=>FN_Translate("adeguate"),4=>FN_Translate("good"),5=>FN_Translate("very good"));
    foreach($votes as $i=> $votes)
    {
        $html.="<img onmouseout=\"select_star(-1);document.getElementById('pdit_rank_desc').innerHTML='&nbsp;'\" onmouseover=\"select_star($i);document.getElementById('pdit_rank_desc').innerHTML='{$votes}'\" onclick=\"set_rank($i)\" style=\"vertical-align:middle;cursor:pointer;\" alt=\"\" src=\"{$_FN['siteurl']}modules/navigator/star_gray$s.png\" />";
    }
    $html.="<div style=\"margin-left:10px;display:inline\" id=\"pdit_rank_desc\" >&nbsp;</div>";
    $html.="</div>";
    $html.="</div>";
    return $html;
}

/**
 *
 * @global array $_FN
 * @global <type> $databasename
 * @param <type> $id
 * @param <type> $rank
 * @param <type> $tablename
 * @return <type> 
 */
function FNNAV_SetRank($id,$rank,$tablename)
{
    global $_FN;
    $rank=intval($rank);
    if ($rank > 5 || $rank < 0)
        return;
    $table=FN_XmlTable("{$tablename}_ranks");

    $table->InsertRecord(array("unirecidrecord"=>"$id","rank"=>$rank));
}

/**
 *
 * @param string $config
 * @param array $row
 * @return string
 */
function FNNAV_HtmlToolbar($config,$row)
{
    global $_FN;
    $tplfile=file_exists("sections/{$_FN['mod']}/viewitem.tp.html") ? "sections/{$_FN['mod']}/viewitem.tp.html" : FN_FromTheme("modules/navigator/viewitem.tp.html",false);
    $tplbasepath=dirname($tplfile)."/";
    $template=file_get_contents($tplfile);
    $tp_str=FN_TPL_GetHtmlPart("navigation bar",$template);
    $tp_str_options=FN_TPL_GetHtmlPart("options",$template);


    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
    $t=FN_XmlForm($tablename);
    $op=FN_GetParam("op",$_GET,"html");
    $unirecid=$row[$t->xmltable->primarykey];
    $results=FNNAV_GetResults($config);
    $results=$results[$tablename];
    $next=$prev="";
    $k=0;
    if (is_array($results))
        foreach($results as $k=> $item)
        {
            $id=$item[$t->xmltable->primarykey];
            if ($id== $unirecid)
            {
                $prev=isset($results[$k - 1]) ? $results[$k - 1][$t->xmltable->primarykey] : $results[count($results) - 1][$t->xmltable->primarykey];
                $next=isset($results[$k + 1]) ? $results[$k + 1][$t->xmltable->primarykey] : $results[0][$t->xmltable->primarykey];
                break;
            }
        }



    $linkusermodify=FNNAV_MakeLink(array("op"=>"users","id"=>$unirecid),"&");
    $linkmodify=FNNAV_MakeLink(array("op"=>"edit","id"=>$unirecid),"&");
    $linkprev=FNNAV_MakeLink(array("id"=>$prev),"&");
    $linkhistory=FNNAV_MakeLink(array("op"=>"history","id"=>$unirecid),"&");
    $linknext=FNNAV_MakeLink(array("id"=>$next),"&");
    $linklist=FNNAV_MakeLink(array("op"=>null),"&");
    $linkview=FNNAV_MakeLink(array("op"=>"view","id"=>$unirecid),"&");

    $vars['txt_rsults']=( $k + 1)."/".count($results);
    $vars['linkusermodify']=$linkusermodify;
    $vars['linkmodify']=$linkmodify;
    $vars['linklist']=$linklist;
    $vars['linkpreviouspage']=$linkprev;
    $vars['linknextpage']=$linknext;
    $vars['linkhistory']=$linkhistory;

    $tp_str=FN_TPL_ApplyTplString($tp_str,$vars);


    //-----next / prev / list buttons ----------------------------------------->
    $navigatebar_pages="";
    $htmlRecordpage=FN_TPL_GetHtmlPart("page",$tp_str);
    $vars['title']=FN_Translate("go to the contents list");
    $vars['link']=$linklist;
    $vars['image']=FN_FromTheme("images/up.png");
    $navigatebar_pages.=FN_TPL_ApplyTplString($htmlRecordpage,$vars);

    $vars['title']=FN_Translate("previous record");
    $vars['link']=$linkprev;
    $vars['image']=FN_FromTheme("images/left.png");
    $navigatebar_pages.=FN_TPL_ApplyTplString($htmlRecordpage,$vars);

    $vars['title']=FN_Translate("next record");
    $vars['image']=FN_FromTheme("images/right.png");
    $vars['link']=$linknext;
    $navigatebar_pages.=FN_TPL_ApplyTplString($htmlRecordpage,$vars);
    //-----next / prev / list buttons -----------------------------------------<
    //-----view/modify/history/users buttons ---------------------------------->
    $s=$op== "view" ? "class=\"nv_selected\"" : "";
    $navigatebar_options="";
    $htmlOption=FN_TPL_GetHtmlPart("option",$tp_str_options);
    $htmlOptionActive=FN_TPL_GetHtmlPart("optionactive",$tp_str_options);


    //view button
    $vars['title']=FN_Translate("view");
    $vars['image']=FN_FromTheme("images/mime/doc.png");
    $vars['link']=$linkview;
    if ($op== "view")
        $navigatebar_options.=FN_TPL_ApplyTplString($htmlOptionActive,$vars);
    else
        $navigatebar_options.=FN_TPL_ApplyTplString($htmlOption,$vars);
    //history button
    if ($config['enable_history'])
    {
        $vars['title']=FN_Translate("version history");
        $vars['image']=FN_FromTheme("images/read.png");
        $vars['link']=$linkhistory;
        if ($op== "history")
            $navigatebar_options.=FN_TPL_ApplyTplString($htmlOptionActive,$vars);
        else
            $navigatebar_options.=FN_TPL_ApplyTplString($htmlOption,$vars);
    }
    if (FNNAV_IsAdminRecord($row))
    {
        //edit button
        $vars['title']=FN_Translate("modify");
        $vars['image']=FN_FromTheme("images/modify.png");
        $vars['link']=$linkmodify;
        if ($op== "edit")
            $navigatebar_options.=FN_TPL_ApplyTplString($htmlOptionActive,$vars);
        else
            $navigatebar_options.=FN_TPL_ApplyTplString($htmlOption,$vars);

        //users button
        $vars['title']=FN_Translate("edit qualified users to modify");
        $vars['image']=FN_FromTheme("images/users.png");
        $vars['link']=$linkusermodify;
        if ($op== "users")
            $navigatebar_options.=FN_TPL_ApplyTplString($htmlOptionActive,$vars);
        else
            $navigatebar_options.=FN_TPL_ApplyTplString($htmlOption,$vars);
    }
    //-----view/modify/history/users buttons ----------------------------------<



    $tp_str=FN_TPL_ReplaceHtmlPart("pages",$navigatebar_pages,$tp_str);
    $tp_str=FN_TPL_ReplaceHtmlPart("options",$navigatebar_options,$tp_str);
    return $tp_str;
}

/**
 *
 * @global array $_FN
 * @param string $unirecid
 * @param string $tablename
 * @param bool $showbackbutton 
 */
function FNNAV_ViewRecordHistory($unirecid,$_tablename="")
{
    global $_FN;
    $version=FN_GetParam("version",$_GET);
    $config=FN_LoadConfig();
    $html="";
//--config--<
    $tables=explode(",",$config['tables']);
    if ($_tablename== "")
    {
        $tablename=$tables[0];
    }
    else
    {
        $tablename=$_tablename;
    }
    $t=FN_XmlForm($tablename);
    $Table=FN_XmlForm($tablename);
    $Table_history=FN_XmlForm($tablename."_versions");
    //del history------->
    $action=FN_GetParam("action",$_GET,"flat");
    if ($action== "delete")
    {
        $item = $t->xmltable->GetRecordByPrimarykey($unirecid);
        if (FNNAV_IsAdminRecord($item))
        {
            $Table_history->xmltable->DelRecord($version);
            $version="";
        }
    }
    //del history-------<

    $html.="<div class=\"fnfilesuserbar\" >";
    $html.=FNNAV_HtmlToolbar($config,$t->xmltable->GetRecordByPrimarykey($unirecid));
    $html.="</div><br />";
    $html.="<h2>".FN_Translate("previous versions").":</h2>";
    $res=FN_XMLQuery("SELECT * FROM {$tablename}_versions WHERE {$t->xmltable->primarykey} LIKE $unirecid ORDER BY recordupdate DESC");
    if (is_array($res))
    {
        foreach($res as $item)
        {
            $link_deleteversion=FNNAV_MakeLink(array("action"=>"delete","op"=>"history","id"=>$unirecid,"version"=>$item['idversions']),"&");
            $link_version=FNNAV_MakeLink(array("op"=>"history","id"=>$unirecid,"version"=>$item['idversions']),"&");

            if ($version== $item['idversions'])
            {
                $html.="<h3>".FN_GetDateTime($item['recordupdate'])." by {$item['userupdate']}</h3>";
                $html.=FNNAV_ViewRecordPage($item['idversions'],"{$tablename}_versions",false); // visualizza la pagina col record
                if (FNNAV_IsAdminRecord($item))
                    $html.="<div><a href=\"javascript:check('$link_deleteversion')\">".FN_Translate("delete this version")."</a></div>";
                $html.="<hr />";
            }
            else
            {
                $html.="<div>".FN_GetDateTime($item['recordupdate'])." by {$item['userupdate']} <a href=\"$link_version\">".FN_i18n("view")."</a>";
                if (FNNAV_IsAdminRecord($item))
                    $html.="&nbsp;<a href=\"javascript:check('$link_deleteversion')\">".FN_i18n("delete")."</a></div>";
            }
        }
    }
    else
        $html.=FN_Translate("no previous version is available");
    return $html;
}

/**
 *
 * @global array $_FN
 * @param string $unirecid
 * @param string $tablename
 * @param bool $showbackbutton 
 */
function FNNAV_ViewRecordPage($unirecid,$_tablename="",$shownavigatebar=true)
{
    global $_FN;

//--config-->
    $config=FN_LoadConfig();
//--config--<
    $tables=explode(",",$config['tables']);
    if ($_tablename== "")
    {
        $tablename=$tables[0];
    }
    else
    {
        $tablename=$_tablename;
    }

    $t=FN_XmlForm($tablename);
    $Table=FN_XmlForm($tablename);


    if (!FNNAV_CanViewRecord($unirecid,$tablename))
    {
        return "";
    }

    $forcelang=isset($_GET['forcelang']) ? $_GET['forcelang'] : $_FN['lang'];
    $rank=FN_GetParam("setrank",$_GET);
    $cookie=FN_GetParam("fnfiles_rank_{$tablename}_{$unirecid}",$_COOKIE);
    if ($config['enableranks']== 1 && $cookie!= "true" && isset($_GET['setrank']) && $rank>= 0 && $rank<= 5)
    {
        //die ($rank);
        FN_Alert(FN_Translate("thank you for voting"));
        FNNAV_SetRank($unirecid,$rank,$tablename);
        $ctime=time() + 999999999;
        setcookie("fnfiles_rank_{$tablename}_{$unirecid}","true",$ctime,$_FN['urlcookie']);
        $_COOKIE["fnfiles_rank_{$tablename}_{$unirecid}"]="true";
    }

    $row=$Table->xmltable->GetRecordByPrimaryKey($unirecid);

//-------statistiche---------------------->>
    if ($config['enable_statistics']== 1)
    {
        if (isset($row['view']) && $row['view']!= $row[$Table->xmltable->primarykey])
        {
            $Table2=FN_XmlTable($tablename);
            $ff=array();
            $ff['view']=$unirecid;
            $ff['unirecid']=$unirecid;
            //dprint_r($ff);
            $Table2->UpdateRecord($ff);
            $row=$Table2->GetRecordByPrimaryKey($unirecid);
        }
        if (!file_exists("{$_FN['datadir']}/{$_FN['database']}/$tablename"."_stat"))
        {
            $sfields=array();
            $sfields[0]['name']="unirecid";
            $sfields[0]['primarykey']="1";
            $sfields[1]['name']="view";
            createxmltable($_FN['database'],$tablename."_stat",$sfields,$_FN['datadir']);
        }
        $tbtmp=FN_XmlTable($tablename."_stat");

        $tmprow['unirecid']=$row[$t->xmltable->primarykey];
        if (($oldview=$tbtmp->GetRecordByPrimaryKey($row[$t->xmltable->primarykey]))== false)
        {
            $tmprow['view']=1;
            $rowtmp=$tbtmp->InsertRecord($tmprow);
        }
        else
        {
            $oldview['view'] ++;
            $rowtmp=$tbtmp->UpdateRecord($oldview); //aggiunge vista
            $Table2=FN_XmlTable($tablename);
            $row=$Table2->GetRecordByPrimaryKey($unirecid);
        }
    }
//-------statistiche----------------------<<
    $tablename=$Table->tablename;
    $unirecid=isset($row[$t->xmltable->primarykey]) ? $row[$t->xmltable->primarykey] : null;


    //--- template item ----->
    $tplfile=file_exists("sections/{$_FN['mod']}/viewitem.tp.html") ? "sections/{$_FN['mod']}/viewitem.tp.html" : FN_FromTheme("modules/navigator/viewitem.tp.html",false);
    $tplbasepath=dirname($tplfile)."/";
    $template=file_get_contents($tplfile);
    $tpvars=array();

    //--- template item -----<
//---------NAVIGATE BAR-------------------------------------------->
    $htmlNavigationbar="";
    if ($shownavigatebar== true)
    {
        $htmlNavigationbar.="<div class=\"fnfilesuserbar\" >";
        $htmlNavigationbar.=FNNAV_HtmlToolbar($config,$row);
        $htmlNavigationbar.="</div>";
        $tpvars['navigationbar']=$htmlNavigationbar;
        $template=FN_TPL_ReplaceHtmlPart("navigation bar",$htmlNavigationbar,$template);
    }
    else
    {
        $template=FN_TPL_ReplaceHtmlPart("navigation bar","",$template);
    }
//---------NAVIGATE BAR--------------------------------------------<
//
//------------------------------visualizzazione-------------------------------->
    $linklist=FNNAV_MakeLink(array("op"=>null,null=>null,"&amp;")); //link list
    $link=FNNAV_MakeLink(array("op"=>"view","id"=>"$unirecid","&amp;")); //link  to this page
    $htmlFooter="";
    ob_start();
    if ($shownavigatebar && file_exists("sections/{$_FN['mod']}/viewfooter.php"))
    {
        include ("sections/{$_FN['mod']}/viewfooter.php");
    }
    $htmlFooter=ob_get_clean();
    $htmlHeader="";
    ob_start();
    if ($shownavigatebar && file_exists("sections/{$_FN['mod']}/viewheader.php"))
    {
        include ("sections/{$_FN['mod']}/viewheader.php");
    }
    $htmlHeader=ob_get_clean();
    $tpvars['footer']=$htmlFooter;
    $tpvars['header']=$htmlHeader;

//------------------------------ INNER TABLES---------------------------------->
    ob_start();
    $oldvalues=$row;
    $htmlout="";
    if ($Table->innertables)
    {
        foreach($Table->innertables as $k=> $v)
        {
            $title=$v['tablename'];
            if (isset($v["frm_{$_FN['lang']}"]))
                $title=$v["frm_{$_FN['lang']}"];
            $params=array();
            $params['echo']=false;
            $params['path']=$Table->path;
            $params['enableedit']=true;
            $params['enablenew']=false;
            $params['enabledelete']=false;
            $params['enableview']=true;
            $tinner=explode(",",$v["linkfield"]);
            if (isset($tinner[1]) && $tinner[1]!= "" && isset($oldvalues[$tinner[0]]))
                $params['restr']=array($tinner[1]=>$oldvalues[$tinner[0]]);
            else
                $params['restr']=array($v["linkfield"]=>$oldvalues[$Table->xmltable->primarykey]);
            if (isset($v["tablename"]) && isset($oldvalues[$Table->xmltable->primarykey]) && file_exists("{$_FN['datadir']}/{$_FN['database']}/{$v["tablename"]}.php"))
            {
                $tmptable=FN_XmlForm($v["tablename"],$params);
                $allview=$tmptable->xmltable->getRecords($params['restr']);
                if (is_array($allview) && count($allview) > 0)
                {
                    $ft="<h3>{$title}:</h3>";
                    foreach($allview as $view)
                    {
                        if (FNNAV_CanViewRecord($view[$tmptable->xmltable->primarykey],$v["tablename"]))
                        {
                            echo $ft.FNNAV_ViewRecordPage($view[$tmptable->xmltable->primarykey],$v["tablename"],false);
                            $ft="";
                        }
                    }
                }
            }
        }
    }
    $innerTables=ob_get_clean();
    $tpvars['innertables']=$innerTables;
//------------------------------ INNER TABLES----------------------------------<
    $tpvars['htmlranks']="";
    if ($shownavigatebar && $config['enableranks']== 1)
        $tpvars['htmlranks']="<div class=\"fnfilesranks\" name=\"\"><a name=\"___ranks\" id=\"___ranks\"></a>".FNNAV_HtmlRankEditable($unirecid,false,$tablename)."<hr /><br /></div>";

    $template=FN_TPL_ApplyTplString($template,$tpvars);
    $Table->SetlayoutTemplateView($template);
    $htmlView=$Table->HtmlShowView($Table->GetRecordTranslatedByPrimarykey($unirecid));
    return $htmlView;

//------------------------------visualizzazione--------------------------------<
}

function FNNAV_CanEditRecord($id,$tablename)
{
    global $_FN;
    if (FN_IsAdmin())
        return true;
    $config=FN_LoadConfig();
    //----if inner table is in other section----------------------------------->
    if ($config['tables']!= $tablename)
    {
        foreach($_FN['sections'] as $section)
        {
            if ($section['type']== "navigator")
            {
                $configTmp=FN_LoadConfig("",$section['id']);
                if ($configTmp['tables']== $tablename)
                {
                    $config=$configTmp;
                    if (!FN_UserCanViewSection($section['id']))
                    {
                        return false;
                    }
                    break;
                }
            }
        }
    }
    //----if inner table is in other section-----------------------------------<
    return true;
}

/**
 * return true if user can view record
 * @global array $_FN
 * @param string $id
 * @param string $tablename
 * @return boolean
 */
function FNNAV_CanViewRecord($id,$tablename)
{
    global $_FN;
    if (FN_IsAdmin())
        return true;
    $config=FN_LoadConfig();
    //----if inner table is in other section----------------------------------->
    if ($config['tables']!= $tablename)
    {
        foreach($_FN['sections'] as $section)
        {
            if ($section['type']== "navigator")
            {
                $configTmp=FN_LoadConfig("",$section['id']);
                if ($configTmp['tables']== $tablename)
                {
                    $config=$configTmp;
                    if (!FN_UserCanViewSection($section['id']))
                    {
                        return false;
                    }
                    break;
                }
            }
        }
    }
    //----if inner table is in other section-----------------------------------<
    $table=FN_XmlTable($tablename);
    $record=$table->GetRecordByPrimaryKey($id);
    //--------visualizzazione solo per il creatore----------------------------->
    if (!empty($config['viewonlycreator']))
    {
        if ($_FN['user']== "" && $record['username']!= "")
        {
            return false;
        }
        elseif ($_FN['user']== $record['username'])
        {
            return true;
        }
    }

    if ($config['viewonlycreator'])
    {
        $listusers=FN_XmlTable("fieldusers");
        $list_field=FNNAV_GetFieldUserList($record,$tablename);
        if (is_array($list_field))
            foreach($list_field as $field)
            {
                if ($field['username']== $_FN['user'] && $field['table_unirecid']== $record[$table->primarykey] && $field['tablename']== $tablename)
                    return true;
            }
    }

    //--------visualizzazione solo per il creatore-----------------------------<
    //permessi per ogni record------------------------------------------------->
    else
    {
        if (!empty($config['enable_permissions_each_records']) && $config['enable_permissions_each_records']== 1)
        {
            if (empty($record['groupview']))
            {
                return true;
            }
            else
            {
                if ($_FN['user']== "")
                    return false;
                $uservalues=FN_GetUser($_FN['user']);
                $usergroups=explode(",",$uservalues['group']);
                $groupsview=explode(",",$record['groupview']);
                $groupinsert=explode(",",$config['groupinsert']);
                $groupadmin=explode(",",$config['groupadmin']);
                foreach($usergroups as $group)
                {
                    if (in_array($group,$groupsview) || in_array($group,$groupinsert) || in_array($group,$groupadmin))
                    {
                        return true;
                    }
                }
                return false;
            }
        }
        else
        {

            if (empty($record['groupview']) && empty($config['viewonlycreator']))
            {
                return true;
            }
        }
    }
    //permessi per ogni record-------------------------------------------------<
    return true;
}

//FNNAV_GenerateRSS();
/**
 * generete rss
 *
 */
function FNNAV_GenerateRSS()
{
    global $_FN;
//--config-->
    $config=FN_LoadConfig();
    if (!$config['enable_rss'])
        return;
//--config--<
    $tables=explode(",",$config['tables']);
    if (empty($_tablename))
    {
        $tablename=$tables[0];
    }
    else
    {
        $tablename=$_tablename;
    }
    $recordsperpage=10;
    $Table=FN_XmlForm($tablename);
    $DB=new XMLDatabase("fndatabase",$_FN['datadir']);
    $all=FNNAV_GetResults($config);
    foreach($_FN['listlanguages'] as $llang)
    {
        $idlang="";
        if ($llang!= $_FN['lang_default'])
        {
            $idlang="_{$llang}";
        }
        $locktop=array();
        $newstoprint=array();
        $curtime=FN_Time();

        if (is_array($all))
        {
            $i=1;
            foreach($all[$tablename] as $item)
            {
                $item=$Table->xmltable->GetRecordByPrimaryKey($item[$Table->xmltable->primarykey]);
                $titlename="";
                $titles=explode(",",$config['titlefield']);
                $s="";
                foreach($titles as $titleitem)
                {
                    if (isset($item[$titleitem]))
                    {
                        $titlename.="$s{$item[$titleitem]}";
                        $s=" ";
                    }
                    else
                    {
                        $titlename=each($item);
                        $titlename=$titlename[1];
                    }
                }
                $news_values['news_TITLE']=$titlename;
                $news_values['link_READ']=FN_RewriteLink("index.php?mod={$_FN['mod']}&op=view&id={$item[$Table->xmltable->primarykey]}");
                $news_values['news_SUMMARY']="$titlename";
                $news_values['date']=$item['recordupdate'];

                $newstoprint[]=$news_values;
                if ($i > $recordsperpage)
                    break;
                $i++;
            }
        }
        $body="<?xml version='1.0' encoding='".FN_i18n("_CHARSET",$llang)."'?>\n<rss version='2.0'>\n\t<channel>\n";
        // informazioni generali sul feed
        $body.="\t\t<title>{$_FN['sitename']}</title>\n\t\t<link>{$_FN['siteurl']}</link>\n\t\t<description><![CDATA['{$_FN['sitename']}' - {$_FN['sectionvalues']['title']} ]]></description>\n";
        $body.="\t\t<managingEditor>{$_FN['site_email_address']}</managingEditor>\n\t\t<generator>FlatNux RSS Generator - http://www.flatnux.sf.org</generator>\n";
        $body.="\t\t<lastBuildDate>".date("Y-m-d H:i:s")." GMT</lastBuildDate>\n";

        foreach($newstoprint as $news_values)
        {
            $body.="\t\t<item>\n";
            $body.="\t\t\t<title>{$news_values['news_TITLE']}</title>\n";
            $body.="\t\t\t<link>{$_FN['siteurl']}{$news_values['link_READ']}</link>\n\t\t\t<description><![CDATA[{$news_values['news_SUMMARY']}]]></description>\n";
            $body.="\t\t\t<pubDate>".date("Y-m-d H:i:s",strtotime($news_values['date']))." GMT</pubDate>\n";
            $body.="\t\t</item>\n";
        }
        $body.="\t</channel>\n</rss>";
        // scrittura del feed
        if (!file_exists($_FN['datadir']."/rss"))
            mkdir($_FN['datadir']."/rss");
        if (!file_exists($_FN['datadir']."/rss/{$tablename}"))
            mkdir($_FN['datadir']."/rss/{$tablename}");

        if (!file_exists($_FN['datadir']."/rss/{$tablename}/$llang"))
            mkdir($_FN['datadir']."/rss/$tablename/$llang");
        if (FN_IsAdmin())
        {
            // dprint_xml($body);
            // die();
        }
        FN_Write($body,"{$_FN['datadir']}/rss/$tablename/$llang/rss.xml");
    }
}

function FNNAV_AdminPerm()
{
    global $_FN;
    //--config-->
    $config=FN_LoadConfig();
    $tables=explode(",",$config['tables']);
    $tablename=$tables[0];
    $xmlform=FN_XmlForm($tablename);
    $op=FN_GetParam("op",$_GET);
    $results=FNNAV_GetResults($config);
    $query="SELECT * FROM $tablename";
    $results=FN_XMLQuery($query);
    $titlefield=explode(",",$config['titlefield']);
    $permissions_records_groups=explode(",",$config['permissions_records_groups']);
    $permissions_records_edit_groups=explode(",",$config['permissions_records_edit_groups']);
    $html="";
    if (!FN_IsAdmin())
        return "";
    if (isset($_POST['groups']))
    {
        foreach($_POST['groups'] as $k=> $v)
        {
            if (is_array($v))
            {
                $newgroups[$k]=implode(",",$v);
            }
        }
    }
    if (isset($_POST['editgroups']))
    {
        foreach($_POST['editgroups'] as $k=> $v)
        {
            if (is_array($v))
            {
                $neweditgroups[$k]=implode(",",$v);
            }
        }
    }
    //dprint_r($_POST);

    $html.="<script>
		
select_allck = function(el){
	var name = el.name.replace('s_','');
	var cklist = document.getElementsByTagName('input');
	for (var i in cklist)
	{
		if (cklist[i].type=='checkbox' && cklist[i].name.indexOf('['+name+']')>=0 && cklist[i].name.indexOf('tgroups')<=0)
		{
			if (el.checked)
			{
				cklist[i].checked = true;
			}
			else
				cklist[i].checked = false;
		}
	}
	//console.log(cklist);
}
select_allcke = function(el){
	var name = el.name.replace('se_','');
	var cklist = document.getElementsByTagName('input');
	for (var i in cklist)
	{
		if (cklist[i].type=='checkbox' && cklist[i].name.indexOf('['+name+']')>=0 && cklist[i].name.indexOf('tgroups')>=0)
		{
			if (el.checked)
			{
				cklist[i].checked = true;
			}
			else
				cklist[i].checked = false;
		}
	}
	//console.log(cklist);
}
</script>";
    //dprint_r($_POST);
    $pagelink=FN_RewriteLink("index.php?mod={$_FN['mod']}&amp;op=$op");
    $html.="<h3>".FN_Translate("manage permissions")."</h3>";
    $html.="<form method=\"post\" action=\"\">";
    $html.="<table style=\"border:1px solid\">";
    $cst=count($titlefield);
    $csg=count($permissions_records_groups);
    $csgw=count($permissions_records_edit_groups);

    $html.="<tr><td   style=\"border:1px solid\" colspan=\"$cst\"></td><td  style=\"border:1px solid\" colspan=\"$csg\">".FN_Translate("read")."</td><td  style=\"border:1px solid;background-color:#dadada;color:#000000\" colspan=\"$csgw\" >".FN_Translate("write")."</td>";
    $htmltitles="<tr>";
    foreach($titlefield as $t)
    {
        $htmltitles.="<td style=\"border:1px solid\" >";
        $htmltitles.=$t;
        $htmltitles.="</td>";
    }
    foreach($permissions_records_groups as $t)
    {
        $htmltitles.="<td style=\"border:1px  solid;text-align:center\">";
        $htmltitles.=$t;

        $htmltitles.="<br /><input type=\"checkbox\" name=\"s_$t\" onchange=\"select_allck(this);\" />";
        $htmltitles.="</td>";
    }
    foreach($permissions_records_edit_groups as $t)
    {
        $htmltitles.="<td style=\"border:1px  solid;text-align:center;background-color:#dadada;color:#000000\">";
        $htmltitles.=$t;

        $htmltitles.="<br /><input type=\"checkbox\" name=\"se_$t\" onchange=\"select_allcke(this);\" />";
        $htmltitles.="</td>";
    }




    $htmltitles.="</tr>";

    $i=0;
    $toupdate=false;
    $saveok=true;
    $html.=$htmltitles;
    //dprint_r($_POST);
    foreach($results as $values)
    {
        //if ($i > 1000)
        //	break;
        $toupdateitem=false;
        if (isset($_POST['oldgroups']))
        {
            $toupdate=true;

            //read
            if (!isset($newgroups[$values[$xmlform->xmltable->primarykey]]))
            {
                $newgroups[$values[$xmlform->xmltable->primarykey]]="";
            }
            if (isset($values['groupview']) && $values['groupview']!= $newgroups[$values[$xmlform->xmltable->primarykey]])
            {
                $toupdateitem=true;
                $values['groupview']=$newgroups[$values[$xmlform->xmltable->primarykey]];
            }
            //edit
            if (!isset($neweditgroups[$values[$xmlform->xmltable->primarykey]]))
            {
                $neweditgroups[$values[$xmlform->xmltable->primarykey]]="";
            }
            if (isset($values['groupinsert']) && $values['groupinsert']!= $neweditgroups[$values[$xmlform->xmltable->primarykey]])
            {
                $toupdateitem=true;
                $values['groupinsert']=$neweditgroups[$values[$xmlform->xmltable->primarykey]];
            }
        }
        if ($toupdateitem)
        {
            $res=$xmlform->xmltable->UpdateRecord($values);
            if (!is_array($res))
                $saveok=false;
        }
        $html.="<tr>";
        foreach($titlefield as $t)
        {
            $html.="<td style=\"border:1px  solid;\">";
            $html.=$values[$t];
            $html.="</td>";
        }
        $usergroups=explode(",",$values['groupview']);
        $usereditgroups=explode(",",$values['groupinsert']);
        //read
        foreach($permissions_records_groups as $t)
        {
            $html.="<td title=\"$t\" style=\"border:1px  solid;text-align:center\">";
            $html.="<input name=\"groups[{$values[$xmlform->xmltable->primarykey]}][$t]\" value=\"$t\" type=\"checkbox\" ";

            if (in_array($t,$usergroups))
            {
                $html.="checked=\"checked\"";
            }
            $html.=" />";
            $html.="</td>";
        }
        //modify
        foreach($permissions_records_edit_groups as $t)
        {
            $html.="<td title=\"$t\" style=\"border:1px  solid;text-align:center;background-color:#dadada;color:#000000\">";
            $html.="<input name=\"editgroups[{$values[$xmlform->xmltable->primarykey]}][$t]\" value=\"$t\" type=\"checkbox\" ";

            if (in_array($t,$usereditgroups))
            {
                $html.="checked=\"checked\"";
            }
            $html.=" />";
            $html.="</td>";
        }
        $html.="</tr>";
        $i++;
    }
    $html.="</table>";
    if ($toupdate)
    {
        if ($saveok)
            $html.=FN_HtmlAlert(FN_Translate("the data were successfully updated"));
        else
            $html.=FN_HtmlAlert(FN_Translate("error"));
    }
    $html.="<input name=\"oldgroups\" value=\"1\" type=\"hidden\" />";
    $l=FN_RewriteLink("index.php?mod={$_FN['mod']}","&");
    $html.="<button type=\"submit\">".FN_Translate("save")."</button>";
    $html.="<button type=\"reset\">".FN_Translate("reset")."</button>";
    $html.="<button onclick=\"window.location='$l'\" type=\"button\">".FN_Translate("go to the contents list")."</button>";
    $html.="</form>";
    return $html;
}

?>