# Piano: pulsante "Scarica per l'uso offline" (tutto il sito con un tocco)

Data: 2026-11-05. Continua `piano_offline_webapp_2026-11-03.md` (uso offline in produzione su
Liguria, Piemonte e test Lombardia dal 03/11/2026).

## Richiesta

Oggi, senza rete, si rivedono solo le pagine già visitate oppure le sezioni scaricate una per una
dalla pagina **Uso offline** (menu, posizione 900). Si vuole un modo per **scaricare tutto il
sito con un solo comando**, con un pulsante nel menu laterale **al posto di "Contribuisci"**
(che oggi porta al login, già raggiungibile dal menu utente in alto a destra).

## Cosa c'è già (da riusare, non da rifare)

- `openkis_offline.js`: `OKIS.prepareCore()` (home, pagina offline, 12 shell del tema, indici,
  mappa e livelli), `OKIS.downloadSection(section, {images, full, incremental, progress})`
  (frammenti `fnajax=1` + immagini della scheda, incrementale per `recordupdate`, metadati in
  `okis-meta/section-<mod>-<lang>.json` salvati ogni 25 schede), `OKIS.updateAll()` (aggiornamento
  automatico all'apertura dell'app installata, una volta al giorno), ricerca offline, badge.
- `openkis_sw.js`: ricompone shell + frammento; ignora le richieste senza cookie dei download.
- `openkis_offline.php`: `op=info` (sezioni, conteggi, indirizzi), `op=index&mod=` (indice dei
  record pubblici con `recordupdate`, in cache su file).
- finis: header `X-Finis-Visitor/Section/Lang`, `fnshell=1`, `fnajax=1` con `<title>`; cache delle
  schede su file (`FNDBVIEWViewCache`, solo per grotte, artificiali, sorgenti, glaciali in Liguria,
  invalidata per **tabella** a ogni scrittura).
- Tema `openkis_liguria` (in produzione dal 04/11, stesso template di `openkis_agsp` e
  `openkis_fslo`): blocco `gc-contribute` nel menu laterale, opzione `show_contribute_button`
  in `config.php`, etichetta "Contribute/Contribuisci" in `languages/it/lang.csv`.

## Numeri misurati il 05/11/2026 sulla produzione Liguria

Sezioni scaricabili da anonimo e schede: grotte 2302, rilievi 1313, bibliografia 1328,
artificiali 173, tracciamenti 66, fauna 44, aree 43, complessi 7 = **5276 schede**.

| Misura | Valore |
| --- | --- |
| Frammento grotta, dalla cache delle schede | 0,25-0,6 s (0,7-1,3 s a cache fredda) |
| Frammento rilievo / bibliografia (senza cache) | 0,3-0,55 s |
| Peso di un frammento grotta | 20-80 KB in chiaro (5-10 KB gzip); rilievi e bibliografia 9-15 KB |
| Immagine di scheda (`openkis_file.php`, miniatura) | 16-27 KB, 0,18-0,25 s (carica finis) |
| Immagini totali (foto 483 + rilievi 1423 + artificiali) | ~2000, ~40 MB |
| Tutto il sito, oggi, 2 richieste in parallelo | 5276 x 0,45 s / 2 = **~20 minuti** con l'app aperta, più ~4 minuti di immagini |
| Spazio sul dispositivo | ~70 MB grotte + ~30 MB altre sezioni + ~40 MB immagini + ~10 MB nucleo = **~150 MB** |
| Modifiche alle grotte (ultimi 90 giorni) | 12 giorni distinti, a raffiche (434 record il 20/10) |

Il limite è il **tempo per scheda** (bootstrap di finis + andata e ritorno su Aruba), non la
banda: 5276 frammenti pesano in tutto ~25 MB compressi, cioè 1-2 minuti di trasferimento.
La concorrenza resta 2 (`$_FN['openkis_offline_concurrency']`: 9 connessioni MySQL in tutto).

## Metodi valutati

| | Metodo | Tempo per tutto il sito | Impatto | Giudizio |
| --- | --- | --- | --- | --- |
| A | **Solo client**: pulsante che chiama in sequenza il codice esistente (nucleo + tutte le sezioni + immagini), lavoro ripreso automaticamente a ogni pagina | ~20-25 min (Liguria) | solo `openkis_offline.js` + tema | necessario comunque: è l'interfaccia; da solo resta lento |
| B | **Pacchetti dal server**: i frammenti già generati vengono conservati su file e `op=bundle` ne restituisce 100 per richiesta, compressi; il client chiede solo quelli che gli mancano | **1-2 min** a server caldo; come A per le schede mai generate (che si scaldano per tutti) | +1 riga in finis (`website.php`), ~150 righe PHP, ~100 righe JS | **consigliato**, si innesta dentro `downloadSection` |
| C | Archivio statico per sezione costruito dal server (cron o pulsante admin) | 1-2 min | serve un costruttore a pezzi (PHP ~30-60 s per richiesta su Aruba, niente CLI), lock, spazio su server; file da rifare a ogni modifica | scartato: B dà lo stesso risultato senza costruttore |
| D | Download dentro il service worker (`postMessage`) | come A | il browser chiude il worker dopo pochi minuti di lavoro: non regge 20 min | scartato |
| E | Background Fetch API (continua ad app chiusa) | come A/B | solo Chrome/Edge Android, niente iOS e Firefox | rimandato: con B il download dura 1-2 min e non serve |
| F | Schede compresse in Cache Storage (`CompressionStream` nel client, `DecompressionStream` nel worker) | — | spazio ~100 MB -> ~35 MB; +40 righe JS; Safari >= 16.4 | fase facoltativa, da decidere dopo una prova su iPhone |

Scelta: **A + B**, in quest'ordine. A è utile da subito e B accelera A senza cambiarne l'interfaccia.

## Fase 1 - pulsante nel menu e lavoro "scarica tutto" ripreso tra le pagine

Solo client: `openkis_offline.js`, i tre temi `openkis_liguria` / `openkis_agsp` / `openkis_fslo`,
traduzioni. Nessuna modifica a finis né a `openkis_offline.php`.

1. **Tema**: nel template, accanto al blocco `gc-contribute`, un segnaposto
   `<div class="gc-offline" id="okis-sidebar" hidden></div>` governato da una nuova opzione
   `show_offline_button {1=yes,0=no}` in `config.php` (le opzioni del tema arrivano al template
   come variabili). Liguria: `show_offline_button=1`, `show_contribute_button=0`; gli altri temi
   restano com'erano (si cambia dal pannello di controllo). CSS: stesso stile del pulsante
   arancio, barra di avanzamento sottile sotto, versione a sola icona quando il menu è
   ripiegato (`body.gc-collapsed`, come per `.gc-plus`).
2. **Il pulsante è disegnato da `openkis_offline.js`** (`OKIS.sidebarButton(el)`), così la
   logica sta in un posto solo e vale per ogni tema che mette il segnaposto. Stati:
   - browser senza service worker o pagina non sicura: segnaposto nascosto;
   - mai scaricato: "Scarica per l'uso offline" (icona download); al tocco una finestra
     di conferma con stima di schede, MB e tempo (dai conteggi di `op=info` e dalle medie sopra),
     casella "anche le immagini" (preselezionata), avviso "si scarica solo il contenuto
     pubblico" se l'utente è loggato, e un consiglio a installare l'app se non lo è;
   - in corso: barra e testo "1234 / 5276 schede", tocco = pausa;
   - in pausa: "Riprendi (1234 / 5276)";
   - completo: "Disponibile offline · 05/11 18:30"; se `pages` del nucleo è cambiato o sono
     passate 24 h: "Aggiorna la copia offline"; tocco = pagina Uso offline (o aggiornamento);
   - senza rete: "Offline · copia del 05/11", tocco = pagina Uso offline (ricerca).
3. **Lavoro persistente** `okis-meta/job-<lang>.json` = `{state: running|paused|done, images,
   started, finished, sections: {mod: {done, total}}}` scritto con `OKIS.writeJson`.
   `OKIS.downloadAll()` = `prepareCore(force)` + `downloadSection` per ogni sezione con
   `count > 0` (grotte per prime, poi artificiali, aree, complessi, rilievi, bibliografia...).
   **Ripresa**: in `start()` di ogni pagina, se `state === running`, si è online e nessun'altra
   scheda del browser sta già lavorando (Web Locks `navigator.locks.request('okis-download',
   {ifAvailable: true})`, altrimenti un battito `heartbeat` nel job aggiornato ogni 10 s e
   considerato morto dopo 30 s), il download continua da dove era: navigare non lo interrompe.
   Serve una modifica a `downloadSection`: all'inizio di un download completo (codice nuovo o
   immagini chieste per la prima volta) azzera `meta.records` e scrive subito `meta.pages`
   e `meta.images`, così una ripresa è incrementale rispetto a quanto già fatto invece di
   ricominciare (oggi `meta.pages` è scritto solo alla fine).
4. **Avanzamento fra le schede del browser**: `BroadcastChannel('okis')` con ripiego su
   lettura del job ogni 5 s; la pagina Uso offline mostra lo stesso lavoro e il pulsante
   "Scarica tutto" in cima alle sezioni (stesso `downloadAll`).
5. Alla fine: `navigator.storage.persist()`, `state = done`; da lì `updateAll()` (già esistente)
   tiene tutto aggiornato una volta al giorno, immagini comprese se erano state scaricate.
6. Testi nuovi in `extra/openkis/languages/it/lang.csv` (~12 voci: "download for offline use",
   "downloading", "pause", "resume", "available offline", "update the offline copy",
   "estimated size", "also the images", "about %s minutes"...).

Risultato di fase: un tocco scarica tutto; in Liguria ~25 minuti con le immagini, ripresi
automaticamente se l'app viene chiusa e riaperta.

## Fase 2 - pacchetti dal server (`op=bundle`): da 20 minuti a 1-2

Idea: il server conserva i frammenti pubblici che ha già generato e li consegna a pacchetti;
il client non cambia interfaccia, solo `downloadSection` prova prima il pacchetto e scarica
una per una solo le schede che il server non ha.

1. **Deposito dei frammenti** (openkis, `extra/openkis/openkis_offline.inc.php`):
   `misc/_cache/offline/<mod>/<tema>/<lingua>/<id div 1000>/<id>.html`, prima riga di
   intestazione JSON `{"pages": "<versione codice schede>", "updated": "<recordupdate>",
   "time": <unix>}` e poi il frammento com'è uscito dal server. Scritto solo quando: GET
   anonimo, `op=view&fnajax=1` con i soli parametri mod/op/id/lang/fnajax, risposta 200
   `public`, record presente fra quelli pubblici della sezione (stessa regola dell'indice:
   `public_map = Y` e `groupview` vuoto dove esistono, altrimenti solo `groupview` vuoto).
   Scrittura su file temporaneo e `rename` come `ViewCacheSet`. Vale per **tutte** le sezioni
   (anche rilievi e bibliografia, che non hanno la cache delle schede di finis).
2. **Aggancio in finis** (1 riga + commento in `include/methods/website.php`): dopo aver
   costruito `$str` del frammento, `if (!empty($_FN['fnajax_output_hook'])) call_user_func(...,
   $str)`. Un buffer di output aperto da openkis non va bene: `website.php` apre e chiude i
   propri (`ob_get_clean` a riga 80) e chiuderebbe il nostro.
3. **Validità per record, non per tabella**: un frammento è buono se `pages` è la versione
   corrente (`openkis_OfflinePagesVersion()`), `updated` è uguale al `recordupdate` dell'indice
   e ha meno di 30 giorni (`$_FN['openkis_offline_bundle_keep']`). È la stessa regola con cui
   il client decide cosa riscaricare, quindi client e server sono coerenti; le modifiche a
   raffica di una tabella non buttano via i frammenti delle altre schede (la cache di finis sì,
   ed è giusto così per chi naviga online). Compromesso noto, già accettato dal client: una
   scheda che cita dati di un altro record (grotta collegata, bibliografia) può restare
   indietro finché il record o il codice non cambiano, o fino a 30 giorni.
4. **`openkis_offline.php?op=bundle&mod=caves&lang=it&ids=88,90,...`** (max 100 id per
   richiesta, GET da anonimo come gli altri op; già nella lista NEVER del worker): legge
   l'indice (in cache) per sapere quali id sono pubblici e il loro `recordupdate`, legge i file,
   risponde JSON gzip `{"records": [{"id": "88", "updated": "...", "date": "...", "html": "..."},
   {"id": "90", "missing": true}, ...]}`. Costo: un bootstrap di finis + 100 letture di file:
   ~0,5-1 s per 100 schede. Niente rendering nel ciclo: nessun problema di rientranza di finis
   né di `max_execution_time`.
5. **Client** (`downloadSection`): costruito `todo`, lo divide in blocchi di 100, chiede il
   pacchetto, salva ogni frammento con `pagesCache.put(fragmentKey, new Response(html,
   {headers: {'Content-Type': 'text/html; charset=utf-8', 'X-Finis-Section': mod,
   'X-Finis-Lang': lang, 'Date': date}}))` (il worker legge sezione e lingua dagli header per
   scegliere la shell), aggiorna `meta.records`; gli id `missing` passano al ciclo attuale
   una per una (e così si scaldano sul server per tutti). Se `op=bundle` risponde 404
   (openkis vecchio) si usa solo il ciclo attuale: le due versioni convivono.
6. **Pulizia**: `FN_ClearCache()` / `refresh-cache.php` svuotano anche `_cache/offline`;
   una scansione casuale (1 scrittura su 100) cancella i file più vecchi di 30 giorni, come
   `ViewCacheSweep`. Spazio su server: ~5276 x 30 KB = ~160 MB per tema e lingua (solo it in
   Liguria). Se pesa troppo, i file si salvano compressi con `gzencode` a livello 6 (~35 MB) e
   `op=bundle` li decomprime in memoria prima di metterli nel JSON (100 x 30 KB = 3 MB per
   richiesta, accettabile).
7. **Immagini**: restano una per URL (`openkis_file.php`, ~0,2 s), ~3-4 minuti per ~2000
   immagini: è il nuovo collo di bottiglia dopo la fase 2. Se si vuole scendere sotto i 2 minuti
   complessivi: `op=bundle&images=1` allega le miniature in base64 leggendo i file con lo stesso
   codice di `openkis_file.php` (fase 2b, da decidere dopo aver misurato).
8. Smoke test (`tests/smoke_test.sh` 6h): dopo una richiesta anonima di un frammento esiste il
   file del deposito; `op=bundle` lo restituisce con `updated` uguale all'indice; un id non
   pubblico (LI3) risulta `missing` anche se chiesto; un utente loggato non scrive file.

Risultato di fase: a server caldo tutto il sito in 1-2 minuti (più le immagini); il primo
dispositivo dopo un deploy del codice delle schede o dopo una modifica paga solo le schede
cambiate.

## Fase 3 - rifiniture (facoltative, da decidere dopo le prove)

- F: frammenti compressi nel Cache Storage (spazio dimezzato o meglio) se la prova su iPhone
  mostra limiti di spazio.
- Scaldamento del deposito dal pannello di controllo ("prepara i pacchetti offline": la pagina
  Uso offline di un amministratore lancia un `downloadAll` senza salvare nulla in locale, così
  il server genera i frammenti mancanti una volta per tutti).
- Ordine dei risultati della ricerca offline per sezione (punto già aperto nel piano del 03/11).
- Manuale: aggiornare "Usare il catasto senza rete" (`docs/MANUAL_IT.md`, `docs/MANUAL.md`)
  con il pulsante del menu e la stima di spazio.

## Deploy e verifiche

- Sviluppo e prova sulla copia locale Liguria (`~/public_html/speleo/catastogrotte/www.catastogrotte.net/liguria`,
  symlink a `git/openkis`), poi produzione Liguria, poi Piemonte e test Lombardia come il 03/11.
- Ordine in produzione: finis `website.php` (l'aggancio è inerte finché nessuno lo usa),
  `openkis_offline.inc.php` + `openkis_offline.php` (deposito e bundle), `openkis_offline.js`
  (il worker lo ricarica per la versione nell'indirizzo), tema (template, config, css, lang).
  Prima di ogni deploy copia dei file sostituiti in `db_backup/<data>_offline_scarica_tutto/`.
- Prove (Playwright con proxy spento a comando, come il 03/11): download completo da pulsante
  con tempi e MB; chiusura dell'app a metà e ripresa; due schede del browser (una sola lavora);
  utente loggato (nessun file nel deposito, nessuna scheda riservata nel pacchetto);
  aggiornamento dopo la modifica di un record (solo quello riscaricato); offline tutto apribile;
  iPhone reale: installazione, 150 MB conservati dopo 24 h.
- Resoconto in `assistance/<data>_offline_scarica_tutto.md` e `TODO.md`.

## Stima

| Fase | Giorni |
| --- | --- |
| 1 - pulsante, conferma, lavoro ripreso, tre temi, traduzioni, prove | 1,5 |
| 2 - deposito, aggancio finis, `op=bundle`, client, smoke test, prove | 1,5 |
| Deploy Liguria + Piemonte + Lombardia, manuale, resoconti | 0,5 |

## Da decidere

1. Immagini comprese nel "scarica tutto" (proposta: sì, con casella preselezionata; ~40 MB e
   ~4 minuti in più) oppure solo schede.
2. Pulsante attivo di default anche su Piemonte (`openkis_agsp`) e Lombardia (`openkis_fslo`), o
   solo Liguria per ora (proposta: solo Liguria, gli altri dal pannello di controllo).
3. Validità per record dei pacchetti (30 giorni) e il compromesso sulle schede che citano altri
   record: va bene o si preferisce la regola per tabella (più lenta dopo ogni modifica)?
4. Fase 2b (immagini nel pacchetto) e fase 3 F (compressione) solo dopo aver misurato.
