<?php

/*
 * Funzioni specifiche per il Catasto Speleologico Lombardo (FSLo), caricate con
 * $_FN['openkis_custom'] = "lombardia": numero di catasto LO<n> assegnato alle nuove
 * grotte e cavita' artificiali, codici FSLo (LO<provincia><n>), controllo della
 * provincia di una grotta rispetto ai gruppi dei curatori (RW_<provincia>),
 * definizioni delle tabelle ctl_caves e ctl_surveys di questa cartella.
 */
global $_FN; //variabile globale condivisa da tutto il programma Finis
//questa riga di codiceassegna la funzione da richiamare dopo 
//l'inserimento di una grotta, la funzione riceve come parametro un array con i 
//dati appena inseriti:
$_FN['modparams']["caves"]['editorparams']['table']['function_on_insert']="GeneraNumero";
// FSLo code recomputed at every save (the provincia may change)
$_FN['modparams']["caves"]['editorparams']['table']['function_on_update']="lombardia_UpdateCodeFslo";
$_FN['modparams']["artificials"]['editorparams']['table']['function_on_insert']="GeneraNumeroArtificials";
// map centred on the region (unless changed in the admin section "configopenkis")
openkis_SetRegionMapCenter(45.62, 9.75, 8);
// "Uso offline" in the menu only for the registered users (the page stays public,
// openkis_OfflineMenuOnlyForUsers() in extra/openkis/openkis_offline.inc.php)
$_FN['openkis_offline_menu_users_only'] = 1;


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
        $numero=intval(str_replace("LO","",$tmp));
        $grotte_by_code[$numero]['num']=$numero;
        if ($numero > $max)
        {
            $max=$numero;
        }
    }
    $max++;
//prefisso del catasto lombardo
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
        lombardia_UpdateCodeFslo($values);
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
//le chiavi 0..(numero di schede - 1) restano occupate: il numero assegnato e' il primo
//libero sopra il totale delle schede, i numeri liberi sotto (numeri bianchi) non vengono
//riassegnati. Voluto (confermato 2026-11-02): non cambiare.
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
    $values = $table->UpdateRecord($values);
    lombardia_UpdateCodeFslo($values);
    return $values;
}

/**
 * FSLo code of a cave: LO + provincia + cadastre number (LO1759.2 in LC -> LOLC1759.2),
 * "" without provincia.
 *
 * @param array $cave record of ctl_caves with code and provincia
 * @return string
 */
function lombardia_CodeFslo($cave)
{
    $provincia = isset($cave['provincia']) ? trim($cave['provincia']) : "";
    if ($provincia == "" || empty($cave['code']))
    {
        return "";
    }
    return "LO" . $provincia . str_replace("LO", "", $cave['code']);
}

/**
 * Save callback of the caves section (insert, through GeneraNumero, and update): writes
 * code_fslo from the stored code and provincia. Reads the stored record because the form
 * values may lack fields; writes through the table, so no history version is added.
 *
 * @param array $record the saved record (only its id is used)
 */
function lombardia_UpdateCodeFslo($record)
{
    if (empty($record['id']))
    {
        return;
    }
    $table = FN_XMDBTable("ctl_caves");
    $cave = $table->GetRecordByPrimaryKey($record['id']);
    if (!is_array($cave))
    {
        return;
    }
    $codefslo = lombardia_CodeFslo($cave);
    if ($codefslo != "" && $cave['code_fslo'] !== $codefslo)
    {
        $table->UpdateRecord(array("id" => $cave['id'], "code_fslo" => $codefslo));
    }
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
 * Validatore della provincia di una grotta (frm_validator di ctl_caves.provincia,
 * chiamato da Finis solo se la provincia non e' vuota): i curatori salvano solo le
 * grotte delle province del proprio gruppo, RW_<provincia> (RW_BG, RW_BS, RW_VA) o
 * RW_CO_LC_SO_MI_PV per quelle cinque province; gli utenti abilitati alla modifica
 * di una singola scheda ("Gestisci gli utenti abilitati alla modifica") possono
 * salvarla qualunque sia la provincia.
 *
 * @global array $_FN
 * @param string $prov provincia da salvare
 * @param bool $update true se si salva una scheda esistente
 * @param array $newvalues valori da salvare
 * @return bool|string true, oppure il messaggio di errore mostrato nel modulo
 */
function cglombardia_checkprovincia($prov, $update = false, $newvalues = array())
{
    global $_FN;
    if (FN_IsAdmin())
    {
        return true;
    }
    $prov = strtoupper(trim((string) $prov));
    if ($_FN['user'] != "" && $prov != "")
    {
        if (FN_UserInGroup($_FN['user'], "RW_$prov"))
        {
            return true;
        }
        if (in_array($prov, array("CO", "LC", "SO", "MI", "PV")) && FN_UserInGroup($_FN['user'], "RW_CO_LC_SO_MI_PV"))
        {
            return true;
        }
    }
    // utente abilitato alla modifica della scheda che si sta salvando
    $id = ($update && !empty($newvalues['id'])) ? $newvalues['id'] : FN_GetParam("id", $_GET);
    if ($_FN['user'] != "" && $id != "")
    {
        $oldvalues = FN_XMDBTable("ctl_caves")->GetRecordByPk($id);
        if (is_array($oldvalues))
        {
            require_once "{$_FN['src_finis']}/modules/dbview/FNDBVIEW.php";
            $cavesview = new FNDBVIEW(FN_LoadConfig("modules/dbview/config.php", "caves"));
            if ($cavesview->UserCanEditField($_FN['user'], $oldvalues))
            {
                return true;
            }
        }
    }
    return "Non puoi inserire dati nella provincia di " . htmlspecialchars($prov);
}


/**
 * Fills ctl_caves.code_fslo with LO<provincia><number> (LO1759.2 in LC -> LOLC1759.2) and
 * prints the codes it changed, for the caves saved before lombardia_UpdateCodeFslo() existed.
 * Only the caves whose code differs are written: comparing with "code" instead of "code_fslo"
 * rewrote all the caves at every run. Caves without provincia are listed and left untouched.
 * Only administrators.
 */
function GeneraCodiciFSLO()
{
    if (!FN_IsAdmin())
    {
        echo "Funzione riservata agli amministratori.";
        return;
    }
    $table = FN_XMDBTable("ctl_caves");
    $all = $table->GetRecords(false, false, false, false, false, array("id", "code", "provincia", "code_fslo"));
    $updated = array();
    $noprovincia = array();
    foreach ($all as $item)
    {
        $code = htmlspecialchars($item['code']);
        $codefslo = lombardia_CodeFslo($item);
        if ($codefslo == "")
        {
            $noprovincia[] = $code;
            continue;
        }
        if ($item['code_fslo'] !== $codefslo)
        {
            $table->UpdateRecord(array("id" => $item['id'], "code_fslo" => $codefslo));
            $updated[] = "$code: " . htmlspecialchars($item['code_fslo']) . " &rarr; " . htmlspecialchars($codefslo);
        }
    }
    echo "<p>Grotte: " . count($all) . ", codici FSLo aggiornati: " . count($updated) . "</p>";
    if ($updated)
    {
        echo "<p>" . implode("<br />", $updated) . "</p>";
    }
    if ($noprovincia)
    {
        echo "<p>Senza provincia, codice non generato: " . implode(", ", $noprovincia) . "</p>";
    }
}


InizializzaDB();
