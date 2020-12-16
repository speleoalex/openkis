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
    $TableFRM=FN_XmlForm("ctl_caves");
    $TableFRM->formvals['country']['frm_default']="ITALIA";
    $TableFRM->formvals['regione']['frm_default']="LOMBARDIA";
    $TableFRM->formvals['provincia']['frm_validator']="cglombardia_checkprovincia";
    $TableFRM1=FN_XmlForm("ctl_artificials");
    $TableFRM1->formvals['country']['frm_default']="ITALIA";
    $TableFRM1->formvals['regione']['frm_default']="LOMBARDIA";
    $TableFRM2=FN_XmlForm("ctl_springs");
    $TableFRM2->formvals['country']['frm_default']="ITALIA";
    $TableFRM2->formvals['regione']['frm_default']="LOMBARDIA";
    //<frm_validator>cglombardia_checkprovincia</frm_validator>

    $field=array();
    $field['name']='code_fslo';
    $field['frm_it']='Codice FSLo';
    $field['type']='string';
    $field['frm_size']='8';
    $field['frm_help_it']='Codice univoco della federazione Speleologica Lombarda. È composto LO+CODICE PROVINCIA+NUMERO+NUMERO INGRESSO AGGIUNTIVO';
    $field['frm_show']='0';
    $field['view_show']='1';
    addxmltablefield("fndatabase","ctl_caves",$field,"misc");
    
    $field=array();
    $field['name']='provincia';
    $field['type']='string';
    $field['frm_it']='Provincia';
    $field['frm_type']='provincia';
    $field['frm_validator']='cglombardia_checkprovincia';
    $field['frm_required']='1';
    addxmltablefield("fndatabase","ctl_caves",$field,"misc",false);
   
    
}
/**
 * 
 * @global array $_FN
 * @param type $prov
 * @return boolean
 */
function cglombardia_checkprovincia($prov)
{
    global $_FN;
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
        if (FNNAV_UserCanEditField($_FN['user'],$oldvalues))
        {
            return true;
        }
    }


    return "Non puoi inserire dati nella provincia di $prov";
}

InizializzaDB();
