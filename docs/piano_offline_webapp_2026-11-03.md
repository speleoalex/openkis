# Piano: OpenKIS installato come webapp consultabile offline

Data: 2026-11-03. Riguarda i temi installabili come app (`openkis_fslo` oggi; `openkis_grottocenter`,
`openkis_agsp` e gli altri che registrano `openkis_sw.js`).

## Obiettivo

Chi ha installato il catasto come app deve poter, senza rete (in grotta, in zona senza copertura):

- aprire l'app (home, menu, tema) senza la pagina "connessione non disponibile";
- consultare le schede delle grotte (e delle altre sezioni) già visitate o scaricate;
- cercare per codice, nome, sinonimo, comune e aprire la scheda;
- vedere la mappa con i marker delle grotte e le tessere già viste;
- vedere le miniature delle foto e i rilievi già visti.

Restano solo online: login, modifica, inserimento, controlcenter, esportazioni (KML, GPX, CSV),
CaveView 3D (salvo i modelli già aperti), Grottocenter, MCP.

## Scelta di fondo

**Service worker + Cache Storage che conserva le pagine generate dal server** (tema + frammento
della sezione), più un piccolo pacchetto JSON per ricerca e mappa. Niente seconda applicazione
JavaScript che ridisegna liste e schede: la resa resta in PHP (`openkis_sheet.inc.php`, dbview)
e offline si rivede esattamente la stessa pagina. Il pezzo riusato è il frammento `fnajax=1`
che finis già produce per la navigazione ajax delle liste (`include/methods/website.php:17`).

Alternativa scartata: app client-side con IndexedDB e rendering in JS (richiede riscrivere
schede e liste, due logiche da mantenere, temi e personalizzazioni regionali da replicare).

## Numeri di riferimento (copia locale Liguria, 2435 grotte, 1423 rilievi)

| Risorsa | Dimensione | Note |
| --- | --- | --- |
| Scheda grotta, frammento `fnajax=1` | 25 KB (6 KB gzip) | id 88, la più ricca |
| Scheda grotta, pagina intera | 45 KB (10 KB gzip) | tema compreso |
| Tutte le schede come frammenti | ~60 MB | 2435 x 25 KB, Cache Storage conserva il corpo in chiaro |
| `openkis_json.php?mod=caves&minimal=1` | 2,0 MB (174 KB gzip) | indice per ricerca offline |
| `openkis_markers.js.php?mod=caves` | 1,4 MB (109 KB gzip) | marker della mappa |
| Tema (`dist` + `assets`) | ~5 MB | bootstrap, jquery, font, css |
| `bs_map/js` + `bs_map/css` | ~7,7 MB | quasi tutto `ol.js` |
| Miniatura foto in lista (`fnapp=thumb`, 300x100) | ~10 KB | una per grotta con foto |

Un'installazione completa "tutte le schede + indice + mappa" pesa 80-100 MB per la Liguria,
di più per la Lombardia (proporzionale al numero di grotte). Accettabile su Android e su iOS
installato in home (le app installate sono esenti dalla cancellazione dopo 7 giorni di inattività
di Safari; i siti non installati no).

## Vincoli trovati nel codice

- `fnsid` viene impostato a **ogni** visitatore (`functions_sessions.inc.php:19`): il service
  worker non può distinguere un utente loggato da un anonimo dai cookie. Serve un header di
  risposta del server.
- Ogni risposta HTML esce con `Cache-Control: no-cache`: irrilevante per Cache Storage (lo
  governiamo noi), ma va lasciato così per il browser.
- Le pagine degli utenti loggati contengono toolbar di modifica, record riservati
  (`groupview`), campi riservati: non vanno mai conservate. Le pagine anonime sono le stesse
  per tutti e sono già filtrate da `openkis_FilterPublicRecords`.
- La scheda incorpora la mappa in un iframe `bs_map.htm?...` e carica
  `applications.techmakers.it/embedAI/summarise.js` (esterno): offline deve fallire in silenzio.
- `ctl_caves.recordupdate` esiste: permette il download incrementale (solo schede cambiate).
- Il sito può stare in una sottocartella (`catastogrotte.net/liguria`): il service worker è
  già in `{siteurl}openkis_sw.js`, scope = cartella del sito. Va bene.
- I provider di tessere (OpenTopoMap, MapTiler, Thunderforest) vietano il download massivo:
  si conservano solo le tessere viste. I WMS regionali passano da `openkis_proxy.php`
  (server nostro) e si possono conservare liberamente.

## Fase 0 - prerequisiti lato server (finis + openkis)

1. **Header `X-Finis-Visitor: public | private`** su ogni risposta HTML e `fnajax` (in
   `website.php`, prima dell'output): `private` se c'è un utente loggato o se la pagina è un
   form (`op=edit`, `op=new`, login, controlcenter). Il service worker conserva solo le risposte
   `public`. Costo: poche righe.
2. **`fnshell=1`** accanto a `fnajax=1` in `website.php`: la pagina intera con il tema ma con la
   sezione sostituita dal segnaposto `<!--fnsection-->`. Offline il service worker compone
   shell + frammento (una shell per tema/lingua, non una pagina intera per ogni scheda: si
   risparmia metà spazio e il tema cambia una volta sola). Il frammento porta il `<title>`
   della scheda per sostituirlo nella shell.
3. **`openkis_offline.php`** (nuovo, pubblico, solo anonimo):
   - `op=version`: hash della versione del codice (file del tema, di dbview, di
     `openkis_sheet.inc.php`, `openkis_sw.js`), stamp dati (massimo dei `<tabella>/updated`,
     già usato da `openkis_MapCacheStamp`), tema, lingue. Il worker cambia nome alla cache
     quando cambia la versione del codice.
   - `op=assets`: elenco delle risorse della shell da precaricare (css/js/img del tema, flag,
     icone `misc/openkis_icons`, `bs_map.htm` e i suoi js/css, `include/javascripts/*.js`),
     calcolato in PHP dal tema in uso: il worker resta generico.
   - `op=index&mod=caves` (e altre sezioni): JSON `[id, code, name, synonyms, municipality,
     area, lat, lon, recordupdate, hasphoto]` dei soli record pubblici, ~100-150 KB gzip.
     Serve per la ricerca offline e per il download incrementale. Stessa cache su file
     di `openkis_json.php` (`openkis_MapCacheGet`).
   - `op=page`: la pagina "Offline" dell'app (vedi fase 2), resa col tema.
4. Rendere offline-tolleranti i template: `async` sugli script esterni, nessun font da CDN,
   `custom.css?v=` già versionato (il worker aggiorna comunque con stale-while-revalidate).
5. Non obbligatorio ma utile: la cache HTML delle schede già in finis
   (`FNDBVIEWViewCache`) rende economico il download massivo dei frammenti da parte di molti
   utenti (fase 2); va abilitata sulle installazioni dove si offre il download.

## Fase 1 - service worker "conserva mentre navighi"

Riscrittura di `openkis_sw.js` (resta statico nella root; la parte variabile arriva da
`openkis_offline.php`).

- **Install**: legge `op=version` e `op=assets`, precarica la shell (`index.php?fnshell=1`),
  la home, la pagina offline e le risorse del tema/mappa in `openkis-shell-<versione>`.
  **Activate**: cancella le cache delle versioni precedenti.
- **Strategie per tipo di richiesta** (solo GET; POST, `fnapp=controlcenter`, `op=edit|new|
  delete`, `mod=login`, `openkis_mcp.php`, `openkis_API.php` sempre e solo rete):
  - navigazioni e frammenti `fnajax=1`: rete con timeout di 3 s, poi cache; la risposta si
    conserva solo con `X-Finis-Visitor: public`. Offline, per una scheda si serve la pagina
    intera se c'è, altrimenti shell + frammento; se manca tutto, la pagina offline con la
    ricerca locale.
  - risorse statiche (css, js, immagini del tema, `bs_map/*`, CaveView): stale-while-revalidate.
  - `fnapp=thumb`, `openkis_file.php` immagini: cache-first con tetto (es. 1000 voci, scarto
    delle più vecchie).
  - tessere mappa (`tile.opentopomap.org`, maptiler, thunderforest, arcgis) e WMS via
    `openkis_proxy.php`: cache-first con tetto (es. 3000 tessere, ~50 MB), solo quelle viste.
  - `openkis_markers.js.php`, `openkis_json.php`: rete poi cache, conservati.
- **Utente loggato**: il worker non scrive nulla; legge dalla cache solo offline (il contenuto
  è pubblico in ogni caso). Il logout non svuota la cache.
- **Pagina offline**: non più il testo fisso di oggi ma `op=page` precaricata (fase 2).
- Verifica: Playwright con `context.setOffline(true)` su `tests/` (apertura app, scheda
  visitata, scheda mai vista -> pagina offline con ricerca); Lighthouse "installable".
- Risultato di fase: tutto quello che l'utente ha visto online è rivedibile offline.

## Fase 2 - "Scarica per l'uso offline" e pagina Offline

Pagina dell'app `index.php?mod=offline` (sezione `sections/offline`, visibile nel menu solo
quando il service worker è attivo), stessa resa di `op=page` quando si è offline:

- **Stato**: versione dati, data dell'ultimo download, spazio usato
  (`navigator.storage.estimate()`), pulsante "Svuota".
- **Pacchetti scaricabili**, con dimensione stimata calcolata da `op=index` prima di partire:
  1. Ricerca e mappa (indice + marker + `bs_map.htm`): ~2,5 MB. Proposto all'installazione.
  2. Schede di una sezione (grotte, sorgenti, cavità artificiali, aree...): frammenti
     `fnajax=1`, concorrenza 4, barra di avanzamento, ripresa dopo interruzione.
  3. Miniature delle foto delle schede scaricate (`fnapp=thumb` 300x100, ~10 KB l'una).
  4. In seguito: schede di un'area o comune, rilievi a piena risoluzione delle grotte
     "preferite", tessere WMS regionali intorno alle grotte preferite (solo via proxy).
- **Incrementale**: confronto fra `op=index` (id + `recordupdate`) e l'elenco locale
  (IndexedDB `openkis_offline`: id, recordupdate, scaricato il) -> si scaricano solo le schede
  nuove o cambiate, si cancellano quelle sparite dall'indice (record cancellati o resi
  riservati). Aggiornamento automatico all'apertura dell'app se online e passate 24 h.
- **Background Fetch API** dove c'è (Chrome Android: il download continua con l'app chiusa),
  altrimenti ciclo in primo piano con avviso "tieni aperta l'app"; richiesta di
  `navigator.storage.persist()` dopo il primo download.
- Il download delle schede passa per la cache HTML di finis (fase 0.5): 2435 richieste
  diventano letture di file.

## Fase 3 - ricerca, liste e mappa offline

- **Ricerca**: `include/javascripts/openkis_offline.js` cerca nell'indice conservato
  (codice, nome, sinonimi, comune, area; normalizzazione accenti) e disegna le card con le
  stesse classi `dbview-cards`/`dbview-card` delle liste, link alla scheda, badge "offline"
  quando la scheda è in cache. Usata dalla pagina Offline e dal campo di ricerca del tema
  quando `navigator.onLine` è falso o la rete fallisce.
- **Liste**: le pagine di lista visitate (prima pagina, ricerche) sono conservate dalla
  fase 1; una lista non in cache ricade sulla ricerca locale della stessa sezione.
- **Mappa**: `bs_map.htm` e `ol.js` precaricati; i marker vengono da
  `openkis_markers.js.php` conservato; le tessere viste vengono dalla cache; senza tessere
  OpenLayers mostra i marker su sfondo vuoto (aggiungere uno sfondo grigio con reticolo per
  non sembrare rotta). Iframe della scheda: stesso meccanismo (`bs_map.htm?...&point=...`
  è una richiesta di navigazione del frame, il worker risponde con la copia in cache).
- **Scheda**: il rilievo in miniatura e le foto sono in cache se scaricate o già viste; i
  link "KML", "GMaps", "Grottocenter" restano ma falliscono offline (disattivarli con una
  classe `offline-only-online` gestita da `openkis_offline.js`).

## Fase 4 - rifiniture e rilascio

- Indicatore nella barra dell'app ("offline, dati del gg/mm") via `navigator.onLine` +
  messaggio dal worker; avviso "nuova versione, ricarica" quando cambia la cache.
- Voci di configurazione nel controlcenter del tema: sezioni scaricabili, tetto tessere e
  miniature, download automatico dell'indice all'installazione.
- Documentazione: capitolo "Uso offline" in `docs/MANUAL.md` / `MANUAL_IT.md` e screenshot.
- Rilascio: sviluppo e test sulla **Liguria** (copia locale `~/public_html/openkis_manual`,
  poi il mirror e la produzione `catastogrotte.net/liguria`, tema `openkis_liguria` con
  manifest e registrazione del worker da aggiungere come in `openkis_fslo`); poi Piemonte e il
  test Lombardia. Finis prima di openkis (`website.php` cambia). Ricordare che
  `openkis_sw.js` è in cache nei browser: la versione va cambiata a ogni deploy (il nome
  cache lo fa già da `op=version`).

## Stima

| Fase | Giorni | Dipende da |
| --- | --- | --- |
| 0 - header, `fnshell`, `openkis_offline.php` | 1,5 | - |
| 1 - service worker con strategie e pagina offline | 2 | 0 |
| 2 - pagina Offline e download incrementale | 2,5 | 0, 1 |
| 3 - ricerca locale, mappa, liste | 2 | 2 |
| 4 - rifiniture, manuale, rilascio | 1 | 3 |

Dopo la fase 1 l'app è già utile (si rivede quanto visto online); dopo la 2 si possono
scaricare tutte le schede; la 3 completa la consultazione.

## Decisioni (2026-11-03)

1. Approccio confermato: "pagine del server in cache" (service worker + Cache Storage),
   nessuna doppia resa, i temi regionali funzionano da soli.
2. Test sulla Liguria (copia locale, poi mirror e produzione).
3. Sezioni nel download: tutte quelle con `op=view` pubblico, grotte attive di default
   (sorgenti, artificiali, aree a scelta nella pagina Offline).
4. Miniature delle foto come pacchetto separato.
5. Tessere mappa: solo quelle viste; il pacchetto WMS regionale via proxy intorno alle
   grotte preferite resta in fase 2.4, da valutare dopo.

## Stato (2026-11-03): fasi 0-3 e manuale fatti, provati sulla copia locale Liguria

Codice (non committato):

- finis `include/methods/website.php`: header `X-Finis-Visitor` (`public` per un visitatore
  anonimo in GET, altrimenti `private`), `X-Finis-Section`, `X-Finis-Lang`; `fnshell=1`
  (tema con `<!--fnsection-->`, la sezione non viene generata, `theme.php`); il frammento
  `fnajax=1` esegue prima l'head della pagina e porta il `<title>` della scheda.
  `include/auth/local.php`: i form di login/logout non rimandano a `fnshell`/`fnajax`.
- openkis `extra/openkis/openkis_offline.inc.php` (versioni, sezioni, indici, configurazione
  del client), `extra/openkis/openkis_offline_hooks.php` (header `X-Finis-Visitor` su ogni
  risposta, compresi KML, file e miniature; script nell'head delle pagine di `index.php`),
  `openkis_offline.php` (`op=info`, `op=index`), `openkis_sw.js` (riscritto),
  `openkis_offline.js` (codice comune col worker, registrazione, etichetta offline, pagina
  Uso offline), `sections/offline/`, traduzioni in `extra/openkis/languages/it/lang.csv`.
- Temi: tolta la registrazione propria del worker da `openkis_agsp`, `openkis_default`,
  `openkis_fslo` (ora la fa `openkis_offline.js` per tutti i temi, con la versione
  nell'indirizzo); `openkis_liguria` installabile (manifest, meta, icone provvisorie
  ingrandite dal logo 64x64).
- Manuale: "Usare il catasto senza rete" (capitolo 1) e nota in Manutenzione.
- Smoke test: sezione 6h (header, shell, frammento, `op=info`, indice senza grotte riservate).

Differenze rispetto al piano:

- Niente `op=assets` né `op=page`: le risorse si ricavano dall'HTML delle pagine conservate,
  la pagina offline è la sezione `offline` conservata; la mappa si "riscalda" aprendo le
  pagine mappa in un iframe nascosto, così il worker conserva anche gli script e i livelli
  che carica da sola.
- Il worker non precarica nulla all'installazione (nessun carico per i visitatori occasionali):
  il nucleo (home, pagina offline, shell, indici, mappa) lo prepara la pagina Uso offline o
  l'app installata, una volta per versione o al giorno.
- Cache non versionate (`okis-core`, `okis-pages`, `okis-visited`, `okis-static`,
  `okis-data`, `okis-media`, `okis-images`, `okis-tiles`); la versione nell'indirizzo del
  worker serve ad aggiornare il worker e il nucleo.
- Aggiornamento automatico dell'app solo incrementale (`recordupdate`); dopo un deploy che
  cambia il codice delle schede compare "aggiornamenti disponibili" e la riscarica completa
  la fa il pulsante Aggiorna.

Prove (Playwright headless, proxy locale spento a comando per togliere davvero la rete: la
modalità offline di Playwright non ferma le richieste del service worker, e la cache HTTP
del browser può nascondere per qualche minuto una risorsa che il worker non serve):

| Prova | Risultato |
| --- | --- |
| Nucleo (home, pagina offline, 11 shell, indici, mappa riscaldata) | 13 s |
| Download complessi (7) / aree (43) / grotte (2302) | 0,5 s / 1,3 s / 198-221 s (locale, 2 richieste parallele) |
| Spazio usato dopo i tre download | 64 MB |
| Offline: home, scheda visitata, schede scaricate (ricomposte nel tema), lista grotte | aperte, con etichetta "Offline · copia del ..." |
| Offline: mappa grotte mai aperta online | 2252 elementi disegnati (KML conservato, script dal riscaldamento) |
| Offline: grotta non scaricata | pagina Uso offline con i dati dell'indice (comune, quota, coordinate) |
| Offline: ricerca "scovero" | LI86 Tana do Scovero, segnata "disponibile offline" |
| Download grotte con immagini | 199 s, 503 immagini; offline le schede le mostrano (la copia locale non aveva il collegamento a `openkis_file.php`, aggiunto) |
| Aggiornamento incrementale (aree) | 0 schede se nulla cambia, 1 se cambia un record, rimossa la scheda di un record sparito; con codice nuovo l'automatico resta incrementale, il pulsante riscarica tutto |
| Utente loggato (amministratore, grotta riservata LI3) | nessuna voce nuova in pagine, dati, immagini; header `private` su pagine e KML |
| `$_FN['openkis_offline'] = 0` | worker deregistrato e cache cancellate |
| Smoke test su installazione pulita | 193 superati, 0 falliti (12 nuovi in 6h) |

Da fare prima della produzione Liguria:

- `spark_liguria` (tema di produzione, fuori da git) non ha manifest: aggiungere
  `manifest.json`, icone e meta come in `openkis_liguria`; serve un logo in alta risoluzione
  per icone nitide.
- Deploy: prima finis condiviso (`website.php`, `theme.php`, `auth/local.php`), poi openkis.
- `openkis_offline_concurrency` = 1 o 2 su Aruba (9 connessioni MySQL per l'account).
- Possibile ottimizzazione: un endpoint che rende molte schede in una sola richiesta, per
  scaricare una sezione con meno avvii di PHP.

## TODO successivo: app parallela con dati in IndexedDB

In un secondo momento, separata da questo piano: un'applicazione **parallela al sito, con un
proprio URL** (es. `app/` nella root del sito o un sottodominio), che scarica i dati con
`openkis_json.php` / `openkis_offline.php?op=index` in IndexedDB, li sincronizza in modo
incrementale (`recordupdate`) e disegna liste, ricerca, schede e mappa in JavaScript. Punto di
partenza: gli endpoint JSON, l'indice e il meccanismo di versione costruiti qui (fase 0), la
ricerca locale di `openkis_offline.js` (fase 3) e le card `dbview-cards`. Da decidere allora:
inserimento offline con coda di sincronizzazione verso `openkis_insert.php` / MCP, login e
record riservati.
