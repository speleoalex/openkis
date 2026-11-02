# Personalizzazione Lombardia

Catasto Speleologico Lombardo (Federazione Speleologica Lombarda), produzione su
https://www.speleolombardia.it/catasto/. Si attiva con

```php
$_FN['openkis_custom'] = 'lombardia';
```

in `extra/openkis_config.local.php`, che include `lombardia.php`.

## File

| File | Contenuto |
|---|---|
| `lombardia.php` | funzioni della regione: numerazione, controllo provincia, codici FSLo, copia delle definizioni |
| `ctl_caves.php` | definizione della scheda grotte (campi, etichette, permessi) |
| `ctl_surveys.php` | definizione della scheda rilievi |
| `ctl_caves.0.php`, `ctl_caves.1.php` | versioni vecchie della definizione, non usate |
| `footer_map_caves.php` | statistiche sotto la mappa delle cavita' naturali (`sections/map_caves`) |
| `viewheader_cards_caves.php` | codice FSLo nell'intestazione della scheda grotta |

`InizializzaDB()` (chiamata a ogni richiesta) copia `ctl_caves.php` e `ctl_surveys.php` sopra
`misc/fndatabase/` quando il file di questa cartella e' piu' recente, poi ferma la pagina con
il messaggio `misc/fndatabase/ctl_caves.php updated` (basta ricaricarla). Quindi **la scheda si
modifica qui**, non in `misc/`: una modifica fatta solo in `misc/fndatabase/ctl_caves.php`
viene sovrascritta al primo aggiornamento di questo file. Fino al 2026-11-02 la definizione
attiva era quella di `misc/` (modificata online il 2026-04-27); ora questa cartella la contiene.

## Numero di catasto

`GeneraNumero()` e' agganciata all'inserimento di una grotta
(`$_FN['modparams']['caves']['editorparams']['table']['function_on_insert']`; FINIS la chiama
dopo il salvataggio, `FNDBVIEWCRUD::InsertRecord`).

- Se il campo `code` e' compilato, il numero resta quello scritto (es. `LO9295`, `LO1759.2`
  per il secondo ingresso).
- Se e' vuoto, assegna `LO` + un numero libero e aggiorna la scheda. Il form propone `LO` come
  valore iniziale (`frm_default`): per l'assegnazione automatica il campo va svuotato.
- Il numero si calcola sulla parte prima del punto (`LO1759.2` -> 1759).

Comportamento effettivo (verificato il 2026-11-02 su una copia dei dati: 7243 schede, numero
massimo 9558): l'array dei numeri usati contiene anche gli indici dei record (0 ... 7242), quindi
i numeri sotto il totale delle schede risultano sempre occupati. La funzione assegna il primo
numero libero **sopra** il totale (oggi `LO7596`) e non riassegna mai i 1794 numeri liberi
sotto 7243. **E' voluto** (confermato il 2026-11-02): i numeri bianchi non vanno riassegnati.
Il commento in `GeneraNumero()` lo dice: non "correggere" l'array.

`GeneraNumeroArtificials()` fa lo stesso per le cavita' artificiali (`ctl_artificials`).

## Codice FSLo

Campo `code_fslo` della scheda (non modificabile nel form): codice univoco della Federazione,
`LO` + provincia + numero (es. `LOBG1001`). Nella scheda compare nell'intestazione, come badge
`FSLo LOBG1001` accanto al numero di catasto (`viewheader_cards_caves.php` imposta
`$openkis_sheet_codes`, letto da `sections/caves/viewheader.php`), e non piu' nella card
"Altri dati". `GeneraCodiciFSLO()` lo
ricalcola per tutte le grotte, solo per gli amministratori: la sezione `aggiorna_codice_fslo`
(Strumenti) apre `genera_FSLO.php` nella radice del sito.

## Permessi per provincia

Gruppi dei curatori: `RW_BG` (Bergamo), `RW_BS` (Brescia), `RW_VA` (Varese),
`RW_CO_LC_SO_MI_PV` (Como, Lecco, Sondrio, Milano, Pavia).

Il campo `provincia` della scheda ha il validatore `cglombardia_checkprovincia()`: salvando una
grotta di una provincia diversa da quelle del proprio gruppo si riceve "Non puoi inserire dati
nella provincia di XX". Passano gli amministratori, chi e' nel gruppo `RW_<provincia>`, chi e' in
`RW_CO_LC_SO_MI_PV` per CO, LC, SO, MI, PV, e chi e' abilitato alla modifica della scheda che sta
salvando ("Gestisci gli utenti abilitati alla modifica", `FNDBVIEW::UserCanEditField`; la scheda
e' quella dei valori salvati, anche da API/MCP, o l'`id` della pagina). I gruppi si confrontano
per nome esatto (dal 2026-11-02; prima per sottostringa, e una sigla parziale come `C` o `O_`
passava per chi era in `RW_CO_LC_SO_MI_PV`). Le grotte di Monza e Brianza (`MB`), Cremona, Lodi
e Mantova non hanno un gruppo: le salvano solo amministratori e utenti abilitati sulla scheda.

## Ereditarieta' dei gruppi

Configurazione della sezione `caves` (tabella `fncf_caves`, pannello della sezione):

| Variabile | Valore |
|---|---|
| `enable_permissions_each_records` | 1 |
| `enable_permissions_edit_each_records` | 1 |
| `permissions_records_groups` | `RW_BG,RW_BS,RW_VA,RW_CO_LC_SO_MI_PV` |
| `permissions_records_edit_groups` | `RW_BG,RW_BS,RW_VA,RW_CO_LC_SO_MI_PV` |
| `groupinsert` | `catasto_inserimento` (chi puo' inserire) |
| `groupadmin` | `catasto_amministratori` |

Quando un utente che non e' amministratore inserisce una grotta, la scheda eredita nel campo
`groupinsert` i suoi gruppi tra quelli di `permissions_records_edit_groups`: una grotta inserita
da un curatore di Bergamo ha `groupinsert = RW_BG` e la modificano solo i curatori di Bergamo,
gli amministratori e `catasto_amministratori`. Il campo `groupinsert` lo cambiano solo gli
amministratori e `catasto_amministratori`. In FINIS la logica e' `FNDBVIEWCRUD::applyRecordPermissions()`, la stessa del
vecchio flatnux.

`groupview` limita la visualizzazione di una scheda ai gruppi indicati (nel 2026: 5 grotte).
Queste grotte non compaiono nella mappa pubblica (vedi sotto).

## Mappa pubblica

Chi non ha fatto l'accesso vede sulla mappa le grotte con `public_map = Y` (campo "Visibile
sulla mappa pubblica", predefinito Si), esclusi sempre i record con `groupview`
(`openkis_FilterPublicRecords()`), come faceva flatnux. Al passaggio a OpenKIS/FINIS il campo
e' stato valorizzato con `Y` per le grotte senza `groupview` e `N` per le altre. Le schede
restano riservate. Una grotta inserita da API/MCP senza il campo ha `public_map` vuoto e non
compare sulla mappa pubblica.

## Altri valori della regione

- Form: paese `Italy`, regione `LOMBARDIA`, nome in maiuscolo, tipo di coordinate originale
  modificabile solo dagli amministratori, rilievi della scheda visibili solo a `r_caves`.
- Mappa centrata su 45.62, 9.75, zoom 8 (`openkis_SetRegionMapCenter`), salvo valori diversi
  impostati in "Configurazione" (`extra_s_openkis`).
- Layer della mappa: `bs_map/conf/layers_lombardia.js`. I WMS della Regione Lombardia
  (CTR, DBTR toni di grigio, Ortofoto AGEA 2021) vanno chiesti in `EPSG:3857`: la mappa lavora
  in `EPSG:900913`, che quei server rifiutano (`InvalidCRS`). Il layer dei tracciamenti
  (`OPS_Map.addTracingsLayerFromUrl`) va chiamato qui: senza, la sezione `tracings` mostra la
  mappa vuota.

## Statistiche sotto la mappa

Sotto la mappa delle cavita' naturali (`map_caves`, che e' anche la home) `footer_map_caves.php`
mostra il riepilogo del catasto di `extra/openkis/cavestats/cavestats.inc.php`, lo stesso della
home del catasto ligure: grotte per provincia, rilievi, grotte con rilievo, foto, voci di
bibliografia, le 10 grotte piu' lunghe e piu' profonde, le ultime aggiornate. Chi non ha accesso
alla sezione grotte (anonimi) vede nelle classifiche solo le grotte della mappa pubblica, senza
link alla scheda. Le classifiche usano i valori delle singole schede: gli ingressi di un
complesso con valori diversi (es. Valle del Nose': LO2757, LO2021, LO2029) compaiono separati, e
lo sviluppo del complesso (`ctl_cavesystems`) non e' usato.

## Tema

`themes/openkis_lombardia`: logo della Federazione (`img/logo-fslo.png`, da
speleolombardia.wordpress.com, ritagliato sul cerchio con sfondo trasparente) e footer con i
crediti a OpenKIS e il link a GitHub.

## Sezioni del sito

Le sezioni solo lombarde non sono nel repository ma nella cartella del sito (`sections/`):
`Strumenti`, `reports`, `esportazioni`, `esportazione_excel`, `query`, `aggiorna_codice_fslo`,
`gruppi_speleologici`, `situazione_catasto`, `numeri_bianchi_e_avvisi`, `infocookie`, la `home`
e il testo della mappa (`map_caves/section.it.html`, Progetto Tu.Pa.Ca.).
