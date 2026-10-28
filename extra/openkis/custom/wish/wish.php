<?php

/*
 * Funzioni specifiche per il catasto speleologico piemontese
 */
global $_FN; //variabile globale condivisa da tutto il programma Flatnux
//questa riga di codiceassegna la funzione da richiamare dopo 
//l'inserimento di una grotta, la funzione riceve come parametro un array con i 
//dati appena inseriti:

// map centred on the region (unless changed in the admin section "configopenkis")
openkis_SetRegionMapCenter(42.00, 12.50, 6);


