<?php
/**
 * Grottocenter integration (section "grottocenter", openkis_karstlink.php)
 *
 * do not modify this file: it contains only the default values, the
 * administrators change them in the section page (Settings), the values are
 * stored in misc/fndatabase/fncf_grottocenter
 */
//[it]Nome della fonte: il catasto che pubblica i dati, vuoto = nome del sito. Grottocenter lo usa insieme all'id della scheda per riconoscere i record già importati: non cambiarlo dopo il primo import
//[i18n]Source name: the cave register that publishes the data, empty = site name. Grottocenter uses it with the record id to recognise the records already imported: do not change it after the first import
$config['source_name'] = "";
//[it]Licenza dei dati esportati {=non impostata,CC-BY=CC BY,CC-BY-SA=CC BY-SA,ODbL=ODbL,ODC-By=ODC-By}
//[i18n]License of the exported data {=not set,CC-BY=CC BY,CC-BY-SA=CC BY-SA,ODbL=ODbL,ODC-By=ODC-By}
$config['license'] = "";
//[it]Esporta anche descrizione e itinerario (testi degli autori: verificare i diritti) {1=si,0=no}
//[i18n]Export the description and the itinerary too (texts of the authors: check the rights) {1=yes,0=no}
$config['export_texts'] = "0";
//[it]Codice ISO del paese quando il campo paese della scheda non è riconosciuto
//[i18n]ISO country code used when the country of a record is not recognised
$config['country_code'] = "IT";
//[it]Distanza massima tra una grotta e un ingresso di Grottocenter candidato (metri)
//[i18n]Maximum distance between a cave and a candidate Grottocenter entrance (metres)
$config['max_distance'] = "500";
//[it]Punteggio minimo per la conferma in blocco delle proposte (percentuale)
//[i18n]Minimum score to confirm the proposals all together (percentage)
$config['auto_confirm_score'] = "90";
//[it]Ore di validità dei dati di Grottocenter scaricati
//[i18n]Hours the downloaded Grottocenter data are kept
$config['cache_hours'] = "24";
//[it]Indirizzo dell’API di Grottocenter
//[i18n]Grottocenter API address
$config['api_url'] = "https://api.grottocenter.org/api/v1";
?>
