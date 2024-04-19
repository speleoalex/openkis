<?php

/*
 * Funzioni specifiche per il catasto speleologico piemontese
 */
global $_FN; //variabile globale condivisa da tutto il programma Flatnux
//questa riga di codiceassegna la funzione da richiamare dopo 
//l'inserimento di una grotta, la funzione riceve come parametro un array con i 
//dati appena inseriti:

$config=FN_LoadConfig("extra/openkis/config.php");
$_FN['default_latitude']=$config['default_latitude'];
$_FN['default_longitude']=$config['default_longitude'];
$_FN['default_zoom']=$config['default_zoom'];


