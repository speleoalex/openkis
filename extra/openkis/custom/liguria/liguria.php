<?php

/*
 * Funzioni specifiche per il catasto speleologico piemontese
 */
global $_FN; //variabile globale condivisa da tutto il programma Flatnux
//questa riga di codiceassegna la funzione da richiamare dopo 
//l'inserimento di una grotta, la funzione riceve come parametro un array con i 
//dati appena inseriti:
$_FN['modparams']["caves"]['editorparams']['table']['function_on_insert'] = "GeneraNumero";
$_FN['modparams']["artificials"]['editorparams']['table']['function_on_insert'] = "GeneraNumeroArtificials";
// map centred on the region (unless changed in the admin section "configopenkis")
openkis_SetRegionMapCenter(44.25, 8.70, 9);

/**
 * 
 * @param string $values
 * @return string
 */
function GeneraNumeroArtificials($values)
{
//se il numero non è già impostato non faccio nulla
    if (!empty($values['code']) && $values['code'] != "")
    {
        return $values;
    }

//inizializza la variabile oggetto che punta alla tabella
    $table = FN_XMDBTable("ctl_artificials");
//recupera i campi "id" e "code" di tutte le grotte (code corrisponde al numero di catasto) 
    $grotte = $table->GetRecords(false, false, false, false, false, "id|code");
    $max = 0; //variabile che conterrà il primo numero libero
    $grotte_by_code = array(); //array che contiene le grotte per codice
    foreach ($grotte as $k => $grotta)
    {
        $grotte_by_code[$k] = $grotta;
//elimino a destra del "." esempio PI1210.2
        $tmp = explode(".", $grotta['code']);
        $tmp = $tmp[0];
//prendo solo il numero
        $numero = intval(str_replace("LI", "", str_replace("AO", "", $grotta['code'])));
        $grotte_by_code[$numero]['num'] = $numero;
        if ($numero > $max)
        {
            $max = $numero;
        }
    }
    $max++;
//se è in Piemonte aggiunge "PI", altrimenti "AO"
    $regione = "LI";

//cerca il primo numero libero
    ksort($grotte_by_code);
    for ($i = 1;
    ; $i++)
    {
        if (empty($grotte_by_code[$i]))
        {
            $max = $i;
            break;
        }
    }
    $values['code'] = $regione . $max;
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
    if ($values['code'] != "")
    {
        return $values;
    }

//inizializza la variabile oggetto che punta alla tabella
    $table = FN_XMDBTable("ctl_caves");
//recupera i campi "id" e "code" di tutte le grotte (code corrisponde al numero di catasto) 
    $grotte = $table->GetRecords(false, false, false, false, false, "id|code");
    $max = 0; //variabile che conterrà il primo numero libero
    $grotte_by_code = array(); //array che contiene le grotte per codice
    foreach ($grotte as $k => $grotta)
    {
        $grotte_by_code[$k] = $grotta;
//elimino a destra del "." esempio PI1210.2
        $tmp = explode(".", $grotta['code']);
        $tmp = $tmp[0];
//prendo solo il numero
        $numero = intval(str_replace("LI", "", str_replace("AO", "", $grotta['code'])));
        $grotte_by_code[$numero]['num'] = $numero;
        if ($numero > $max)
        {
            $max = $numero;
        }
    }
    $max++;
//se è in Piemonte aggiunge "PI", altrimenti "AO"
    if ($values['regione'] != "Liguria")
    {
        $regione = "LI";
    }
//cerca il primo numero libero
    ksort($grotte_by_code);
    for ($i = 1;
    ; $i++)
    {
        if (empty($grotte_by_code[$i]))
        {
            $max = $i;
            break;
        }
    }
    $values['code'] = $regione . $max;
    return $table->UpdateRecord($values);
}

/**
 * Country and region pre-filled in the insert forms, set only on the pages of
 * these sections (see openkis_SetInsertDefaults()).
 */
function InizializzaDB()
{
    openkis_SetInsertDefaults(array("caves" => "ctl_caves"),
        array("country" => "ITALIA", "regione" => "LIGURIA"));
}
/**
 * 
 * @global array $_FN
 * @param string $iconparams
 * @param type $itemvalues
 * @param type $mod
 * @return type
 */
function openkis_GetIcon_custom($iconparams, $itemvalues, $mod)
{
    global $_FN;
    //custom field
    if (!empty($itemvalues['xxx']))
    {
        $iconparams['xxx'] = "xxx";
    }
    $name = implode("_", $iconparams);
    $image = "{$_FN['datadir']}/openkis_icons/{$name}.png";
    if (!file_exists($image))
    {
        openkis_MakeIcon($iconparams);
    }
    return $image;
}

InizializzaDB();
