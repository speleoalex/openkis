<?php

/*
 * Funzioni specifiche per il catasto speleologico piemontese
 */
global $_FN; //variabile globale condivisa da tutto il programma Flatnux
//questa riga di codiceassegna la funzione da richiamare dopo 
//l'inserimento di una grotta, la funzione riceve come parametro un array con i 
//dati appena inseriti:
$_FN['modparams']["caves"]['editorparams']['table']['function_on_insert']="GeneraNumero";
$_FN['modparams']["artificials"]['editorparams']['table']['function_on_insert']="GeneraNumeroArtificials";
$config=FN_LoadConfig("extra/openkis/config.php");
$_FN['default_latitude']=$config['default_latitude'];
$_FN['default_longitude']=$config['default_longitude'];
$_FN['default_zoom']=$config['default_zoom'];


/**
 * 
 * @param string $values
 * @return string
 */
function GeneraNumeroArtificials($values)
{
//se il numero non è già impostato non faccio nulla
    if (!empty($values['code']) && $values['code']!= "")
    {
        return $values;
    }

//inizializza la variabile oggetto che punta alla tabella
    $table=FN_XmlTable("ctl_artificials");
//recupera i campi "id" e "code" di tutte le grotte (code corrisponde al numero di catasto) 
    $grotte=$table->GetRecords(false,false,false,false,false,"id|code");
    $max=0; //variabile che conterrà il primo numero libero
    $grotte_by_code=array(); //array che contiene le grotte per codice
    foreach($grotte as $k=> $grotta)
    {
        $grotte_by_code[$k]=$grotta;
//elimino a destra del "." esempio PI1210.2
        $tmp=explode(".",$grotta['code']);
        $tmp=$tmp[0];
//prendo solo il numero
        $numero=intval(str_replace("LO",""));
        $grotte_by_code[$numero]['num']=$numero;
        if ($numero > $max)
        {
            $max=$numero;
        }
    }
    $max++;
//se è in Piemonte aggiunge "PI", altrimenti "AO"
    $regione="LO";

//cerca il primo numero libero
    ksort($grotte_by_code);
    for($i=1;
    ; $i++)
    {
        if (empty($grotte_by_code[$i]))
        {
            $max=$i;
            break;
        }
    }
    $values['code']=$regione.$max;
    return $table->UpdateRecord($values);
}

/**
 * 
 * @param string $values
 * @return string
 */
function GeneraNumero($values)
{
//se il numero non è già impostato non faccio nulla
    if ($values['code']!= "")
    {
        return $values;
    }
//inizializza la variabile oggetto che punta alla tabella
    $table=FN_XmlTable("ctl_caves");
//recupera i campi "id" e "code" di tutte le grotte (code corrisponde al numero di catasto) 
    $grotte=$table->GetRecords(false,false,false,false,false,"id|code");
    $max=0; //variabile che conterrà il primo numero libero
    $grotte_by_code=array(); //array che contiene le grotte per codice
    foreach($grotte as $k=> $grotta)
    {
        $grotte_by_code[$k]=$grotta;
//elimino a destra del "." esempio PI1210.2
        $tmp=explode(".",$grotta['code']);
        $tmp=$tmp[0];
//prendo solo il numero
        $numero=intval(str_replace("LO","",str_replace("AO","",$grotta['code'])));
        $grotte_by_code[$numero]['num']=$numero;
        if ($numero > $max)
        {
            $max=$numero;
        }
    }
    $max++;

    $regione="LO";

//cerca il primo numero libero
    ksort($grotte_by_code);
    for($i=1;
    ; $i++)
    {
        if (empty($grotte_by_code[$i]))
        {
            $max=$i;
            break;
        }
    }
    $values['code']=$regione.$max;
    return $table->UpdateRecord($values);
}

/**
 * 
 */
function InizializzaDB()
{

    if (!file_exists("misc/fndatabase/ctl_caves.php") || filemtime(__DIR__."/ctl_caves.php") > filemtime("misc/fndatabase/ctl_caves.php") )
    {
        file_put_contents("misc/fndatabase/ctl_caves.php", file_get_contents(__DIR__."/ctl_caves.php"));
        die("misc/fndatabase/ctl_caves.php updated");
    }
    else
    {

        //    dprint_r(filemtime("misc/fndatabase/ctl_caves.php") );
        //    dprint_r(filemtime(__DIR__."/ctl_caves.php") );
    }

    //LONTRA
    if (!file_exists("misc/fndatabase/ctl_surveys.php") || filemtime(__DIR__."/ctl_surveys.php") > filemtime("misc/fndatabase/ctl_surveys.php") )
    {
        file_put_contents("misc/fndatabase/ctl_surveys.php", file_get_contents(__DIR__."/ctl_surveys.php"));
        die("misc/fndatabase/ctl_surveys.php updated");
    }
    else
    {

        //    dprint_r(filemtime("misc/fndatabase/ctl_surveys.php") );
        //    dprint_r(filemtime(__DIR__."/ctl_surveys.php") );
    }


}



/**
 * 
 * @global array $_FN
 * @param type $prov
 * @return boolean
 */

//echo '<script>console.log("Lombardia"); </script>';
//wh_log("Lombardia");
//wh_log($group);

function cglombardia_checkprovincia($prov)
{
    global $_FN;
    //echo "<script>console.log('".$_FN."');</script>";
    syslog(LOG_WARNING, "User #14 is logged from two different places.");
    if (FN_IsAdmin())
    {
        return true;
    }
    $group="RW_CO_LC_SO_MI_PV";
    if (false!== stripos($_FN['uservalues']['group'],"RW_$prov"))
    {
        return true;
    }
    if (false!== stripos("CO_LC_SO_MI_PV","$prov") && FN_UserInGroup($_FN['user'],"RW_CO_LC_SO_MI_PV"))
    { 
        return true;
    }

    $table=FN_XmlTable("ctl_caves");
    $id=FN_GetParam("id",$_GET);
    if ($id)
    {
        $oldvalues=$table->GetRecordByPk($id);
        //dprint_r($oldvalues);
        //dprint_r($_FN['user'],$oldvalues);
        if (FNNAV_UserCanEditField($_FN['user'],$oldvalues))
        { 
            return true;
        }
    }


    return "Non puoi inserire dati nella provincia di $prov";
}


/**
 * funzione che genera i codici FSLO, legge tutte le grotte e aggiorna il campo "code_fslo"
 * 
 * @global array $_FN
 */
function GeneraCodiciFSLO($silent=false)
{
    global $_FN;
    $table =FN_XmlTable("ctl_caves");
    $all=$table->GetRecords();
    if (FN_IsAdmin())
    {
        echo "Aggiornamento codici FSLO:<br />";
        foreach($all as $item)
        {
            $newitem=array();
            $newitem['id']=$item['id'];
            $codenum = str_replace("LO","",($item['code']));
            $codice="LO{$item['provincia']}{$codenum}";
            if ($item['code']!= $codice)
            {
                $newitem['code_fslo']=$codice;
                $table->UpdateRecord($newitem);
                if (!$silent)
                    echo "<span style=\"color:red\">Aggiorno $codice</span><br />";
                //die();
            }
            else
            {
                if (!$silent)
                    echo "OK $codice<br />";
            }
        }
    }
    else
    {
        if (!$silent)
            echo "funzione riservata agli amministratori";
    }
}


InizializzaDB();
