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
// map centred on the region (unless changed in the admin section "configopenkis")
openkis_SetRegionMapCenter(42.00, 12.50, 6);

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
    $table=FN_XMDBTable("ctl_artificials");
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
        $numero=intval(str_replace("MA","",$tmp));
        $grotte_by_code[$numero]['num']=$numero;
        if ($numero > $max)
        {
            $max=$numero;
        }
    }
    $max++;
//se è in Piemonte aggiunge "PI", altrimenti "AO"
    $regione="MA";

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
    $table=FN_XMDBTable("ctl_caves");
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
        $numero=intval(str_replace("MA","",$tmp));
        $grotte_by_code[$numero]['num']=$numero;
        if ($numero > $max)
        {
            $max=$numero;
        }
    }
    $max++;
//se è in Piemonte aggiunge "PI", altrimenti "AO"
    if ($values['regione']!= "Marche")
    {
        $regione="MA";
    }
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
 * Country pre-filled in the insert forms. No default region: this is the
 * national register (it was MARCHE, copied from marche.php).
 */
function InizializzaDB()
{
    openkis_SetInsertDefaults(array("caves" => "ctl_caves", "artificials" => "ctl_artificials", "springs" => "ctl_springs"),
        array("country" => "ITALIA", "regione" => ""));
}

InizializzaDB();
