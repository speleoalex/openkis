# Piano: scheda OpenKIS veloce come (e più di) catastogrotte-piemonte.net

Data: 2026-11-03. Confronto di partenza (Carsena di Piaggia Bella, id 88 in entrambi i siti):

| Misura | Sito ufficiale (Laravel) | OpenKIS prod (Aruba) | OpenKIS locale |
| --- | --- | --- | --- |
| TTFB scheda | 0,45 s | 2,0-2,8 s (Liguria 3,4 s) | 1,3-1,8 s |
| TTFB scheda piccola (id 1) | - | 2,0 s | 0,67 s |
| TTFB lista | 0,3 s | 0,8 s | 0,36 s |
| Peso scheda nel browser | 57 MB | ~5 MB | - |

OpenKIS perde solo sul tempo di generazione lato server. Il peso della pagina è già
10 volte migliore (thumbnail, mappa in iframe, KML solo su richiesta).

## Diagnosi (log di tutte le query di una richiesta, locale)

Scheda id 88: **601 query**, 0,8 s di tempo MySQL, 565.000 righe esaminate.

1. **12 caricamenti completi di `ctl_caves`** (3239 righe x 75 colonne, testi compresi,
   ~36 MB di RAM e 20 ms ciascuno in locale). Li fa `XMETADBForm::LoadOptions()`
   (finis `xmetadb_frm.php:651`, chiamata dal costruttore alla riga 593) per ogni campo
   con `foreignkey` + `fk_show_field`: `caveslinks` (grotte), `codecaves` e simili
   (bibliografia, 4 campi), `cave_injection`/`cave_detection` (tracciamenti),
   `codecave` (temperature, fauna, allegati, rilievi). In visualizzazione le opzioni
   non servono a nulla: il valore tradotto viene da `GetRecord()` singolo
   (`GetRecordTranslated`, riga 1989). È il costo fisso che rende lenta anche una
   grotta senza rilievi (0,67 s locale).
2. **156 query per i 39 rilievi**: le inner tables di dbview rendono ogni rilievo con
   `ViewRecordPage()` ricorsivo (`CanViewRecord` due volte, `GetRecordByPrimaryKey`,
   `GetRecordTranslatedByPrimarykey`, un template per record).
3. **Scritture durante una GET**: la card tracciamenti esegue 2 `UPDATE ctl_tracings`
   (ricalcolo coordinate di rilevamento) a ogni visualizzazione, e legge
   `SELECT code,groupview FROM ctl_caves` (3239 righe) per i permessi.
4. In produzione il MySQL è su un altro host (89.46.68.157): le 601 query pagano
   ciascuna la latenza di rete, da cui il secondo in più rispetto al locale, e il
   limite di 9 connessioni condiviso fra Liguria e Piemonte amplifica ogni richiesta
   lenta.
5. Il rilievo 136 (PNG 4,2 MB) è servito a piena dimensione: la thumbnail non è stata
   generata (GD senza memoria sufficiente per il PNG), e il template ricade sul file
   originale.

## Fase 1 - finis: opzioni foreign key snelle e pigre (il guadagno maggiore)

File: `finis/src/include/xmetadb_frm.php` (`LoadOptions`, costruttore riga 591-595).

- 1a. In `LoadOptions()` passare a `GetRecords()` la lista dei campi necessari:
  chiave primaria, `fk_link_field`, `fk_show_field` (tutti i campi dell'elenco),
  `frm_show_image`, più le varianti `_<lang>`. Misurato: da 21 ms/36 MB a 3 ms per
  caricamento. Il parametro `$fields` esiste già in tutti i driver (`XMETATable_mysql.php:402`).
- 1b. Caricamento pigro: nel costruttore non chiamare `LoadOptions()`; mettere in
  `$tmp['options']` un segnaposto e risolverlo al primo uso reale (metodo
  `GetOptions($fieldname)` chiamato da `HtmlForm`, `HtmlShowView` per i campi select,
  `GetRecordTranslated` riga 2002, `xmetadbfrm_field_multicave::show`). Censire con
  `grep -n "\['options'\]" finis/src/include/*.php extra/openkis/fields/*.php
  finis/src/modules/dbview/*.php` ogni lettore delle opzioni prima di cambiare.
  Se il censimento è troppo largo, fermarsi a 1a: da solo toglie il 90% del costo.
- 1c. `ViewRecordPage()` riga 1269-1270: non ripetere `CanViewRecord` per i record
  delle inner tables (già verificato nel ciclo).
- Verifica: `tests/smoke_test.sh -f` con `FINIS_SRC`; form di modifica di grotta,
  bibliografia, tracciamento (i multicave devono ancora mostrare l'elenco completo);
  conteggio query con lo slow log a `long_query_time=0` (atteso: da 601 a ~200).

## Fase 2 - openkis: card dei rilievi senza N+1 e niente scritture in lettura

- 2a. Rilievi come card della scheda (`sections/caves/viewfooter.php` +
  `openkis_sheet.inc.php`): una sola `GetResults` su `ctl_surveys` con i campi
  `id,name,photo1,date,author,license,priority,file*`, griglia di thumbnail con link
  alla scheda del rilievo (stessa resa dell'attuale inner table, con `loading="lazy"`).
  Togliere `ctl_surveys` dalle `innertables` di `ctl_caves` (definizione in
  `extra/openkis/install/misc/fndatabase/ctl_caves.php` e nelle installazioni:
  ricetta in [[openkis-tracings]] per aggiornare `misc/fndatabase`). Atteso: 156 query -> 1.
- 2b. `openkis_tracings.inc.php`: le coordinate di rilevamento si ricalcolano al
  salvataggio (come fatto per gli artificiali in 0c95b6c), non nella card; i permessi
  per codice con `code IN (...)` invece di tutta la tabella.
- 2c. Controllare allo stesso modo le card temperature, fauna, bibliografia
  (`SELECT bibliography_id ... 141 righe` va bene) e la card Grottocenter.
- Obiettivo di fase: scheda id 88 sotto 0,3 s in locale, sotto 1 s in produzione
  senza alcuna cache.

## Fase 3 - cache HTML della scheda (solo visitatori anonimi)

Vincoli (decisi il 2026-11-03): lo storage è illimitato, il DB no, quindi cache su
file; i file non devono crescere all'infinito e devono aggiornarsi o sparire da soli
quando le pagine cambiano. Il problema del passato era una cache che cresceva con il
traffico (una voce per ogni URL richiesta). Qui il numero di file è limitato dai
dati, non dalle richieste:

- **Un file per record e lingua, nome deterministico, sempre sovrascritto**:
  `misc/_cache/pages/<sezione>/<lingua>/<id div 1000>/<id>.html` (es.
  `pages/caves/it/0/88.html`, `pages/caves/it/1/1375.html`). La chiave non contiene
  nulla preso dalla richiesta oltre a sezione, id e lingua (niente query string, user
  agent, tema: il tema entra nella chiave solo se l'installazione ne usa più di uno).
  Massimo teorico: record x lingue (Piemonte: 3239 grotte x 1-2 lingue), con
  sottocartelle da 1000 file al massimo, così `ls`, backup FTP e `FN_RemoveDir`
  restano veloci. Una voce aggiornata sostituisce la precedente: il numero di file
  non cresce con le visite né con le modifiche.
- **Scrittura atomica**: file temporaneo nella stessa cartella + `rename()`, mai un
  file a metà sotto carico; HTML in chiaro (lo spazio non è un problema, e si evita
  il costo di gzip a ogni lettura).
- **Aggiornamento automatico**: alla lettura si confronta il `filemtime` del file con
  lo stamp (massimo dei `<tabella>/updated` delle tabelle coinvolte, confronto `>=`
  per la granularità al secondo) e con la versione del codice; se il file è più
  vecchio si rigenera e si sovrascrive. Più la scadenza di sicurezza a 24 h.
- **Cancellazione immediata del singolo record**: nel salvataggio e nella
  cancellazione da dbview (hook `$_FN['dbview_view_cache']['invalidate']`) si fa
  `unlink` dei file di quel record in tutte le lingue, così la scheda appena
  modificata è fresca anche nello stesso secondo dello stamp.
- **Autopulizia**: (a) a ogni scrittura, una volta ogni 100 (contatore in un file
  `pages/<sezione>/.sweep`), si passa una sola sottocartella a rotazione e si
  cancellano i file più vecchi di 30 giorni (record cancellati, lingue non più usate:
  nessuno li richiede, nessuno li sovrascrive); (b) `refresh-cache.php` e la pagina
  del controlcenter "Refresh map data" fanno il giro completo: cancellano i file dei
  record che non esistono più e quelli oltre i 30 giorni, e riportano il conteggio;
  (c) tetto di sicurezza: se il conteggio di una sezione supera record x lingue x 2,
  si cancella tutta la cartella della sezione (si ricostruisce da sola). Nessun
  file viene mai creato per una richiesta che non corrisponde a un record esistente
  e visibile (id inesistenti, parametri extra, utenti loggati: nessuna scrittura).
- `FN_ClearCache()` cancella anche `pages/`: si ricostruisce alla prima visita.
- **Cosa si mette in cache**: il solo HTML restituito da `ViewRecordPage()` del
  record principale (header, card, footer, inner tables, toolbar), non la pagina
  intera: tema, menu e stato di login restano dinamici.
- **Quando**: `op=view`, GET senza altri parametri, utente non loggato (nessun cookie
  di sessione finis), record pubblico (`groupview` vuoto), sezione con
  `enable_view_cache=1` nel config dbview. Gli utenti loggati non passano mai dalla
  cache (toolbar modifica, permessi per record, campi riservati).
- **Chiave**: sezione, id, lingua, tema, `openkis_custom`, versione del codice
  (hash di `viewheader.php`+`viewfooter.php`+`openkis_sheet.inc.php`, o un numero
  da incrementare a ogni deploy).
- **Validità**: la voce porta lo `stamp` = max dei `GetLastUpdateTime()` delle tabelle
  coinvolte (`ctl_caves`, `ctl_surveys`, `ctl_bibliography`, `ctl_bibliography_links`,
  `ctl_tracings`, `ctl_temperatures`, `ctl_faunacave`/`ctl_fauna_surveys`,
  `ctl_attachments`, `ctl_photos`, `ctl_grottocenter`, `ctl_coordinatestypes`,
  `ctl_areas`, `ctl_cavesystems`). I file `<tabella>/updated` esistono già (toccati da
  `XMETATable::...` riga 522 a ogni scrittura, uno per tabella, numero fisso) e sono
  lo stesso meccanismo della cache delle esportazioni mappa (`openkis_MapCacheStamp`).
  Alla lettura: se uno stamp è più recente della voce, o la voce ha più di 24 h, si
  rigenera. Invalidazione grossolana (una modifica a una grotta invalida tutte le
  schede grotte) ma sempre corretta e senza liste da mantenere; dopo le fasi 1-2 la
  rigenerazione costa poco.
- **Dove**: hook generico in finis `FNDBVIEW::ViewRecordPage()` (lettura/scrittura
  tramite `$_FN['dbview_view_cache']` = callable get/set), implementazione in
  `extra/openkis.inc.php` accanto alla cache mappa, voce "svuota cache schede" nel
  controlcenter insieme a "Refresh map data" e in `refresh-cache.php`.
- Attenzione: `enable_statistics` fa un `UPDATE` del contatore visite a ogni vista
  (riga 1144-1173): con la cache va saltato o mantenuto come unica query.

## Fase 4 - lista e dettagli minori

- Lista grotte (0,8 s prod): rimisurare dopo la fase 1 (il form delle grotte carica
  una volta tutta la tabella per `caveslinks`); poi slow log sulla lista.
- Thumbnail mancanti: in `xmetadb_create_thumb()` (`xmetadb_core.php:368`) stimare la
  memoria (`larghezza*altezza*5` byte) prima di `imagecreatefrompng`; se non basta
  usare `convert` di ImageMagick se disponibile, altrimenti scrivere una thumbnail
  segnaposto, così il template non ricade mai sull'originale da 4 MB. Rigenerare le
  thumb mancanti con un giro una tantum (`find misc/fndatabase -path '*photo1/*'
  -not -path '*thumbs*'` senza il corrispondente `.jpg` in `thumbs/`).
- `loading="lazy"` sulle immagini dei rilievi e delle foto nelle card.
- Export KML 3,4 s: era un miss della cache mappa (valida fino al cambio tabella);
  verificare che in produzione la cache venga scritta (permessi di `misc/_cache`).

## Liguria (2026-11-03, 20:11): fasi 1-3 in produzione

Resoconto `assistance/2026-11-03_prestazioni_schede.md` nella copia locale di www.catastogrotte.net. I file
con altre modifiche più recenti in git (non in produzione) hanno ricevuto solo la differenza dei commit
prestazioni. La cache serve anche i frammenti `fnajax=1` (finis 52fa3d4: download offline).

| Schede grotte | prima | primo accesso | dalla cache | frammento |
| --- | --- | --- | --- | --- |
| produzione Liguria | 1,4-3,5 s | 0,7-2,3 s | 0,31-0,50 s | 0,24-0,32 s |

HTML di 10 schede identico a prima, dopo aver corretto l'ordine dei rilievi (openkis 10b61ce: gli id erano
confrontati come testo, 1232 prima di 2; la copia del Piemonte ha ancora il codice precedente). Restano due
difetti preesistenti, non toccati: il campo "Dati pre importazione" mostra un numero e `{_` al posto della prima
graffa del JSON (motore dei template), e il titolo di una grotta riservata compare a un anonimo nel `<title>` e
in `og:title` (finis `modules/dbview/header.php` in produzione è ae0af7f, la correzione è 7f0fe8b).

## Stato finale (2026-11-03, 11:30): fasi 1a, 2a, 2b, 2c e 3 fatte e in produzione

Commit: openkis 0632cc7 + 5061664, finis b8cb447 + f4bd89b (submodule). Deploy su
catastogrotte.net/piemonte con curl + rinomina, backup in
`assistance/2026-11-03_backup_pre_deploy_perf_scheda/`.

| Scheda id 88 | inizio | dopo fasi 1-2 | con cache (fase 3) |
| --- | --- | --- | --- |
| query | 601 | 196 | 147 (miss) / 88 (hit, solo bootstrap) |
| locale | 1,3-1,8 s | 0,20 s | 0,05 s |
| produzione | 2,3-3,4 s | 1,1-1,4 s | 0,35-0,52 s (la home fa 0,4-0,6 s) |

Fase 2b: la card tracciamenti riscriveva due record a ogni vista (tolleranza 0,00001 su
colonne FLOAT a 6 cifre significative), toccando lo stamp di `ctl_tracings` a ogni
richiesta: corretto (tolleranza 0,0001), altrimenti la cache sarebbe stata sempre scaduta.
Fase 2c: `SET NAMES` una volta per connessione (`DESCRIBE` era già in cache per richiesta).
Fase 3 come progettata (trait `FNDBVIEWViewCache`, default in `extra/openkis_config.inc.php`,
`$_FN['dbview_view_cache'] = 0` nel config locale per spegnerla). Resta da fare la fase 4
(lista, thumbnail dei PNG grandi, verifica cache KML in produzione).

Nota per il deploy di finis in produzione Piemonte: `FNDBVIEW.php` lì è la versione del
2026-11-02 più i soli blocchi della cache (non ha la toolbar da template 56f8ec1 né la
navigazione ajax); chi carica `FNDBVIEW.php` di HEAD deve includere i blocchi `ViewCache`
(sono in HEAD dal commit f4bd89b).

## Stato intermedio (2026-11-03, mattina)

Fasi 1a, 2a e la parte "x12 per card" fatte e in produzione su catastogrotte.net/piemonte
(openkis `sheets: cave sheet with a third of the queries`, finis
`xmetadb_frm: foreign key options read only the columns they are built from`):

| Scheda id 88 | prima | dopo |
| --- | --- | --- |
| query | 601 | 196 |
| locale | 1,3-1,8 s | 0,20 s |
| produzione | 2,3-3,4 s | 1,1-1,4 s |
| memoria PHP | 76 MB | 46 MB |

HTML identico a prima (schede grotte, sorgenti, artificiali, form di modifica di grotte,
tracciamenti e bibliografia confrontati byte per byte), smoke test 177/177.
Delle 196 query rimaste, 44 sono `SET NAMES` e altrettante `DESCRIBE`: una per ogni
oggetto tabella istanziato nella richiesta (candidato per un passo 2c in finis: struttura
delle tabelle in cache per richiesta). In produzione il resto del tempo è la latenza per
query verso il MySQL remoto: da qui in poi conta la fase 3.

Lezioni del deploy su Aruba (valide anche per Liguria):

- `lftp put` ha troncato due volte `xmetadb_frm.php` (92 KB) a 16 e 48 KB: il sito ha
  risposto 500 per un minuto. `curl -k -u utente:password -T file ftp://host/percorso.new`
  carica tutto; poi `mv` con lftp (atomico) e verifica con download + `cmp`.
- lftp conserva la data del file locale: opcache ricompila solo se cambia la data, quindi
  un file ricaricato con la stessa data può non essere mai letto (il file troncato del
  ripristino è stato servito dalla cache finché non l'ho sostituito). Con curl la data è
  quella del caricamento.
- Backup dei file sostituiti in `assistance/2026-11-03_backup_pre_deploy_perf_scheda/`.

## Ordine, verifica e deploy

1. Fase 1a (mezza giornata, finis) -> smoke test -> misura: è il passo con il
   rapporto costo/beneficio migliore.
2. Fase 2a+2b (una giornata, openkis) -> smoke test -> misura.
3. Fase 1b e 1c solo se la scheda resta sopra 0,5 s locale.
4. Fase 3 (una giornata, finis + openkis) solo dopo aver rimisurato in produzione:
   con MySQL remoto anche 200 query costano; la cache porta la scheda pubblica a una
   query.
5. Fase 4 in coda.

Misure da ripetere a ogni passo, locale e produzione: TTFB di `id=88`, `id=1`, lista;
numero di query con lo slow log (`SET GLOBAL slow_query_log=1, long_query_time=0`,
parser in `scratchpad/piem`, da rifare in `tools/`). Deploy: prima Piemonte
(catastogrotte.net/piemonte, copia di test), poi Liguria con la procedura file per
file con backup (vedi [[catastogrotte-production]]); cambi alla definizione delle
tabelle (inner tables) con la ricetta `misc/fndatabase` + `_versions`.
