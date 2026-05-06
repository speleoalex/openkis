<?php

global $_FN;
$vars = array();



// Recupera tutte le regioni dalla tabella "ctl_wishregioni"
$table_regioni = FN_XMDBTable("ctl_wishregioni");
$regioni = $table_regioni->GetRecords(/* array("status"=>1) */);

// Per ogni regione, aggiunge il percorso dell'immagine se presente
foreach ($regioni as $k => $v)
{
    $regioni[$k]['urlimg'] = false;
    if (!empty($v['photo1']))
    {
        $regioni[$k]['urlimg'] = $table_regioni->getFilePath($v, "photo1");
    }
}
$vars['regioni'] = $regioni;
/*
echo "<br>Vars regioni caricate=" . count($regioni) . "<br>";
echo "<br>Vars=";
print_r($vars);
die("<br>Fine debug<br>");
*/
// Recupera tutte le grotte dalla tabella "ctl_caves"
$table = FN_XMDBTable("ctl_caves");
$all = $table->GetRecords(
    false, false, false, "code", false, 
    "id|code|regione|name|synonyms|lenght_total|depth_total|id_regione"
);
// Ordina le grotte per codice
$all = FN_ArraySortByKey($all, "code");

// Inizializza array per statistiche sulle grotte
$MaxLenght = false;
$MaxDepht = false;
$by_lenght = array();
$by_depht = array();
$regioni_censite = array();

// Cicla tutte le grotte per trovare le più lunghe e profonde
foreach ($all as $k => $item)
{
    // Crea il link alla pagina della grotta
    $item['url_wish'] = FN_RewriteLink("index.php?mod=caves&op=view&&id={$item['id']}");
    // Salva l'id_regione per ogni regione censita
    $regioni_censite[$item['regione']] = $item['id_regione'];

    // Converte lunghezza e profondità in interi
    $lenght_total = intval($item['lenght_total']);
    $depth_total = intval($item['depth_total']);
    // Salva la grotta più lunga per ogni valore unico di lunghezza
    if (empty($by_lenght[$lenght_total]))
        $by_lenght[$lenght_total] = $item;
    // Salva la grotta più profonda per ogni valore unico di profondità
    if (empty($by_depht[$depth_total]))
        $by_depht[$depth_total] = $item;
}

// Ordina le grotte per lunghezza e profondità in ordine decrescente
krsort($by_lenght);
krsort($by_depht);

// Seleziona le 10 grotte più lunghe
$vars['grotte_grandi'] = array();
$i = 0;
foreach ($by_lenght as $item)
{
    if ($i > 10)
        break;
    $vars['grotte_grandi'][$i] = $item;
    $i++;
}

// Seleziona le 10 grotte più profonde
$vars['grotte_profonde'] = array();
$i = 0;
foreach ($by_depht as $item)
{
    if ($i > 10)
        break;
    $vars['grotte_profonde'][$i] = $item;
    $i++;
}

// Recupera i dati delle regioni censite e aggiunge alcune informazioni
$table_ctl_wishregioni = FN_XMDBTable("ctl_wishregioni");
ksort($regioni_censite);
foreach ($regioni_censite as $k => $regione_censita)
{
    $regioni_censite[$k] = $table_ctl_wishregioni->GetRecord(array("id" => $regione_censita));
    $regioni_censite[$k]['nome_regione'] = $k;
    $regioni_censite[$k]['title'] = isset($regioni_censite[$k]['title']) ? $regioni_censite[$k]['title'] : "";
}

// Aggiorna nuovamente le immagini delle regioni (ridondante, ma presente nel codice originale)
foreach ($regioni as $k => $v)
{
    $regioni[$k]['urlimg'] = false;
    if (!empty($v['photo1']))
    {
        $regioni[$k]['urlimg'] = $table_regioni->getFilePath($v, "photo1");
    }
}
$vars['regioni'] = $regioni;
$vars['regioni_censite'] = $regioni_censite;
$vars['numero_ingressi'] = count($all);

// Mappa stato regioni italiane per visualizzazione
// verde = con grotte e dati completi
// giallo = con grotte ma coordinate con errore randomico (url_data_caves vuoto)
// rosso = senza grotte nel sistema

// Nomi delle regioni come appaiono nell'SVG
$regioni_svg = array(
    'Abruzzo', 'Basilicata', 'Calabria', 'Campania', 'Emilia-Romagna',
    'Friuli Venezia Giulia', 'Lazio', 'Liguria', 'Lombardia', 'Marche',
    'Molise', 'Piemonte', 'Puglia', 'Sardegna', 'Sicilia', 'Toscana',
    'Trentino Alto Adige', 'Umbria', 'Valle d\'Aosta', 'Veneto'
);

// Funzione per normalizzare i nomi delle regioni
function normalizza_nome_regione($nome) {
    // Converti da MAIUSCOLO a Prima Lettera Maiuscola
    $nome = mb_convert_case(mb_strtolower($nome, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

    // Mappa speciale per alcune regioni
    $mappa = array(
        'Emilia Romagna' => 'Emilia-Romagna',
        'Valle D\'aosta' => 'Valle d\'Aosta',
        'Valle D\'Aosta' => 'Valle d\'Aosta',
    );

    return isset($mappa[$nome]) ? $mappa[$nome] : $nome;
}

$mappa_regioni_stato = array();
foreach ($regioni_svg as $regione_nome) {
    $mappa_regioni_stato[$regione_nome] = 'rosso'; // Default: nessuna grotta
}

// Aggiorna lo stato in base ai dati reali
foreach ($regioni_censite as $nome_db => $regione_data) {
    if (is_array($regione_data)) {
        // Normalizza il nome per corrispondere all'SVG
        $nome_svg = normalizza_nome_regione($nome_db);

        // Trova i dati della regione dall'array completo
        $random_error_coords = '';
        $no_position = '';
        foreach ($regioni as $r) {
            // Confronto case-insensitive perché ctl_caves ha nomi MAIUSCOLI e ctl_wishregioni ha nomi Title Case
            if (strcasecmp($r['regione'], $nome_db) == 0) {
                $random_error_coords = $r['random_error_coords'] ?? '';
                $no_position = $r['no_position'] ?? '';
                break;
            }
        }

        // Logica:
        // - Arancione: no_position = 1 (nessuna coordinata fornita)
        // - Giallo: random_error_coords > 0 (coordinate approssimate)
        // - Verde: coordinate precise
        if (intval($no_position) == 1) {
            $mappa_regioni_stato[$nome_svg] = 'arancione'; // Nessuna posizione
        } else if (intval($random_error_coords) > 0) {
            $mappa_regioni_stato[$nome_svg] = 'giallo'; // Coordinate approssimate
        } else {
            $mappa_regioni_stato[$nome_svg] = 'verde'; // Coordinate precise
        }
    }
}

// La Valle d'Aosta fa parte del catasto Piemonte, quindi deve avere lo stesso stato
if (isset($mappa_regioni_stato['Piemonte'])) {
    $mappa_regioni_stato['Valle d\'Aosta'] = $mappa_regioni_stato['Piemonte'];
}

$vars['mappa_regioni_stato'] = $mappa_regioni_stato;
$vars['mappa_regioni_stato_json'] = json_encode($mappa_regioni_stato);

// $SECTION contiene il dizionario che il sistema passa a section.it.html
$SECTION=&$vars;

