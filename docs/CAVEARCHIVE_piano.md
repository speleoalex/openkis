# Integrazione CaveArchive / catasto — bozza di piano

Stato: bozza da discutere con Simon (chat 01/11 e 05/11/2026), non ancora confermata.
Riferimenti: repo CaveArchive https://github.com/SimonBeatriceLindner/CaveArchive
(clonato e analizzato il 15/11/2026), `docs/MCP_SERVER.md` per le chiavi utente.

## 1. Contesto

CaveArchive è un'app Android (Kotlin/Compose) per speleologi che usano TopoDroid:
sincronizza le cartelle di rilievo tra telefono e un archivio remoto, così i
compagni di spedizione ricevono aggiornamenti, giunzioni, punti fissi e DTM.

Osservato dal codice (novembre 2026, versione circa 1.2.1 in repository):

- il lato remoto è un semplice file store, dietro un'interfaccia di provider
  `RepositoryProvider` (autenticazione, list, download, upload, delete). Esistono
  due provider: OneDrive (MSAL/Graph) e FTP (username/password, Apache Commons
  Net). Simon sta per rilasciare la multi-provider con autenticazioni diverse:
  la porta per aggiungerne altri è già nello schema.
- il protocollo degli archivi vive in MainActivity/SyncWorker: nella root
  dell'archivio c'è un manifest versionato `cavesNNNNN.json` (contatore in
  esadecimale crescente, l'ultimo vince), la cartella `caves/` con i file
  sqlite di TopoDroid (`<nome>.sqlite`), e i DTM come `.asc` (zip) lato server.
- ogni upload: scarica l'ultimo manifest, incrementa il contatore, riscreve un
  nuovo `cavesNNNNN.json` con l'entry della grotta (nome, shotsFile, join,
  geopunti) e poi carica lo sqlite. La sincronizzazione scarica l'ultimo
  manifest e i file cambiati.
- la sincronizzazione in background attuale (`SyncWorker`) parla solo con
  OneDrive; la sync manuale già usa l'astrazione provider.

L'integrazione auspicata (da Simon): "i dati di rilievo digitale sul catasto e
la possibile sincronizzazione con CaveArchive", per catasto ligure/lombardo.
Con il codice in repository si può contribuire direttamente (PR contro il suo repo).

## 2. Obiettivo

Il catasto (Liguria, e in secondo tempo Lombardia / Piemonte) diventa un
archivio remoto di CaveArchive: i rilevatori sincronizzano i rilievi TopoDroid
tra loro tramite il server del catasto, e i file arrivano nel catasto collegati
alle schede grotta, con i permessi del catasto (grotte con accesso riservato,
rilievi non visibili fuori dai gruppi).

Doppio valore: la community di rilievo (che oggi tiene gli archivi su OneDrive
personali o FTP) porta i dati in catasto; il catasto riceve i rilievi digitali
in formato sorgente, non solo l'esportato (kml/pdf/dwg) che ha oggi in ctl_surveys.

## 3. Opzioni

### Opzione 0 — minima: catasto solo consumatore
Gli archivi restano OneDrive/FTP degli speleologi; un job di import catasto
scarica da lì e crea i record. Zero modifica all'app, ma richiede credenziali
dei servizi altrui, niente sync tra compagni via catasto, poco attrattivo.

### Opzione A — provider OpenKIS via MCP
CaveArchive implementa un provider `OpenKisRepositoryProvider` che parla con
`openkis_mcp.php` (JSON-RPC, chiavi utente). Riutilizza autenticazione e
permessi esistenti, ma il protocollo MCP è più pesante di un file store, il
manifest va gestito client-side sopra RPC e lega l'app al catasto. Un provider
"WebDAV" è più utile a Simon a lungo termine (riutilizzabile con altri server).

### Opzione B — consigliata: WebDAV generico + import catasto
Due pezzi indipendenti, accoppiati solo dalla cartella dell'archivio:

1. CaveArchive: terzo provider generico `WebDAVRepositoryProvider`
   (Basic/Digest o token), stesso stile del provider FTP esistente.
2. catasto: un endpoint WebDAV in finis/openkis che serve l'archivio
   (proprio sotto il dominio), autenticato con la chiave utente (già esiste:
   `fn_users.apikey`, hash + copia cifrata, pagine "MCP server");
3. lato catasto un job di import che legge l'archivio, mappa i file alle
   schede grotte (convenzione sul nome o tabella di registro) e crea o
   aggiorna i record in `ctl_surveys`.

Vantaggi: massimo riuso lato catasto (chiavi, permessi, pagine esistenti);
CaveArchive guadagna un provider generico riutilizzabile (interessa Simon per
l'app in sé); il server può serializzare le scritture del manifest (il
read-modify-write del manifest tra due utenti è una race anche su OneDrive/FTP;
il piano lo approfondisce se serve). TopoDroid non ha client WebDAV proprio
(verificato nel codice di marcocorvi/topodroid, novembre 2026: nessun client
dav/ftp/cloud, solo analytics e download del manuale), quindi non serve
direttamente: la stessa però l'endpoint resta usabile da qualsiasi client
WebDAV generico (es. app di sincronizzazione cartella come FolderSync),
che è il caso dei rilevatori senza CaveArchive.

Costo: scritture su entrambi i lati. Ma da questo lato è poco codice
(un endpoint dav + un job) e il grosso del protocollo è già nell'app.

### Opzione C — servizio esterno staccato dal catasto
Un piccolo server (es. WebDAV/Docker) dedicato agli speleologi, indipendente
dal catasto, che il catasto poi importa. Massima separazione ma un altro
sistema da gestire (hosting, credenziali, back-up) e nessuna integrazione con
i permessi del catasto. Da prendere in considerazione solo se Simon vuole un
servizio generale non vincolato a uno specifico catasto.

Consegna: opzione B, con opzione C come alternativa se Simon preferisce.

## 4. Modello dati lato catasto

- Archivio fisico: una cartella per archivio (es. una per utente/club) sotto
  il dominio, fuori dal web root o servita solo via endpoint autenticato
  (regola "protected files", come `openkis_file.php` per i file dei record).
- Autenticazione chiave utente: la pagina "MCP server" esiste già (mostra e
  genera le chiavi, le per gestire); per CaveArchive basta una chiave per
  utente e l'indirizzo dell'archivio.
- Mappatura file/grotte: il manifest di CaveArchive contiene solo il nome del
  rilievo; l'entry catasto (codecave) va ricavata o dal nome file
  (convenzione tipo `<codecave>_<nome>.sqlite`, da concordare) o da una
  tabella di registro (`ctl_cavearchive`: file, grotta, rilievo, ultima
  revisione, autore) riempita dagli speleologi tramite il foglio web.
- Import in `ctl_surveys`: il job di import crea o aggiorna i record del
  rilievo (filename, autore, data) e mette il file sqlite tra le versioni, con
  contatore di download come per i file di rilievo già presenti. Per l'import
  moderato si può usare il meccanismo delle proposte già esistente in dbview
  (`enable_proposals`) invece della scrittura diretta: gli speleologi
  propongono, i curator approvano.
- DTM: le cartelle `.asc`/zip sono grandi; serve una quota per archivio.

## 5. Fasi

| Fase | Contenuto | Dove | Dipendenze |
|------|-----------|------|------------|
| F0 | Call con Simon: conferma di protocollo, autenticazione, licenza | chat | - |
| F1 | Provider WebDAV in CaveArchive (e generalizzazione di SyncWorker, oggi solo OneDrive) | repo di Simon (PR) | F0 |
| F2 | Endpoint WebDAV in finis/openkis con chiavi utente e quote | finis + openkis | F0 |
| F3 | Mappatura file/grotte e job di import in `ctl_surveys` | openkis | F2 |
| F4 | UX del catasto: QR di configurazione (chiave+url) nella pagina MCP/l profilo, link dal foglio del rilievo ai file sincronizzati | openkis | F3 |

F1 e F2 sono indipendenti e sviluppabili in parallelo (F2 testabile con un
qualsiasi client WebDAV). F1 è nel repo di Simon: se lui la scrive, da questo
lato facciamo solo F2-F4.

Stima F2: endpoint con PROPFIND/GET/PUT/MKCOL sulle cartelle archivio + chiavi
in header, un file di 2-4 centinaia di righe in finis più l'hook in openkis.
Stima F3: job di import (uno-shot o cron) e tabella di registro, un paio di
centinaia di righe.

## 6. Domande per Simon

1. Il formato del manifest `cavesNNNNN.json` (contatore esadecimale in root
   dell'archivio) resta quello del codice attuale, o cambia con la nuova
   versione multi-provider?
2. Autenticazione per il nuovo provider: Basic su HTTPS basta, o preferisce
   OAuth (il catasto ha già un oauth server in finis) o token in header
   (com'è per le chiavi MCP)?
3. La race del manifest (due uploads simultanei) è accettata come per
   OneDrive/FTP, o il server deve garantire la sequenzialità? (lato server
   possiamo gestirlo, va solo concordato).
4. Dimensioni reali dei rilievi: quanto è grande in media lo sqlite di
   TopoDroid per una grotta, e i DTM? Serve per stabilire le quote e se
   l'hosting regge (Aruba ha limiti di caricamento PHP e di memoria).
5. Il nome del rilievo nel manifest è unico per archivio? Una convenzione
   `<codecave>_<nome>` andrebbe bene come collegamento catasto, o preferisce
   un campo metadato dedicato?.
6. La licenza del repo è ancora da inserire: con che licenza vuoi
   rilasciare/distribuire, e per la PR del provider WebDAV va bene?
7. TopoDroid non ha client WebDAV (verificato nel codice): siete d'accordo a
   non puntare su di esso direttamente, e a lasciare l'endpoint generico così
   che almeno le app di sync di cartella (FolderSync e simili) possano usarlo?
8. Background sync: l'attuale SyncWorker parla solo con OneDrive — nella
   versione multi-provider sarà generalizzato, da qui serve.

## 7. Rischi e note di sicurezza

- Hosting: su Aruba, da verifica reale: `upload_max_filesize`/`post_max_size`
  (rilievi grandi), `memory_quota` durante PUT, `mod_rewrite` per
  PROPFIND/PUT (dipende dall'hosting; su liguria è già forzato). Valutare un
  endpoint su una sola porta per non complicare.
- Path traversal: l'endpoint dav deve rifiutare `..`, symlink e nomi
  sospetti, e lasciare l'utente solo nella sua sotto-cartella.
- Credenziali: le chiavi esistono già con hash (fn_users.apikey + copia
  cifrata); nessuna chiave nel repo; HTTPS obbligatorio (il server oauth
  già lo esige).
- Permesso lato catasto: l'import deve rispettare il permesso di scrittura
  sulle schede grotte (r_/w_uncatalogued, gruppi): un rilievo mandato in
  catasto da un utente non può finire su una grotta che lui non può vedere.
- Back-up: la cartella dell'archivio finisce nel backup della piattaforma
  come il resto di misc/, ma le dimensioni del DTM vanno messe nel budget.
