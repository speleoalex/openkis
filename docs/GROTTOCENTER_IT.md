# Grottocenter

[Grottocenter](https://grottocenter.org) è il database collaborativo delle grotte del
mondo. Contiene già molte grotte italiane, spesso importate anni fa dai catasti regionali:
stesse coordinate, ma senza numero di catasto e con nomi a volte troncati. OpenKIS collega
le sue grotte alle stesse grotte su Grottocenter e le esporta nel formato Karstlink, che
Grottocenter può importare.

Su Grottocenter non viene scritto nulla: OpenKIS legge solo la sua API pubblica, senza
account.

Versione inglese: [GROTTOCENTER.md](GROTTOCENTER.md).

## Esportazione Karstlink

`openkis_karstlink.php` esporta le grotte in
[Karstlink](https://ontology.uis-speleo.org/howto/), lo standard di scambio dati dell'UIS:

```text
https://<sito>/openkis_karstlink.php?mod=caves                  CSV
https://<sito>/openkis_karstlink.php?mod=caves&format=jsonld    JSON-LD
```

- Stessi record delle esportazioni delle mappe: quelli che il visitatore può vedere
  (accesso alla sezione, `groupview` del singolo record, `public_map`), con coordinate
  valide (non 0, nei limiti: un import che ha perso la virgola, es. longitudine `17297`,
  resta fuori).
  I filtri dbview della sezione funzionano (`filter_code`, `q`, ...). Solo le sezioni con
  tabella `ctl_caves` (altrimenti 404).
- Il CSV ha le colonne lette dall'importatore di Grottocenter, che in alcuni nomi
  differiscono dalla pagina how-to: `dct:rights/karstlink:licenseType`,
  `karstlink:verticalExtend`, `karstlink:extendBelowEntrance`,
  `karstlink:extendAboveEntrance`.
- `id` è l'id della scheda (Grottocenter vuole un numero): insieme al nome della fonte
  (`dct:rights/cc:attributionName`) permette a Grottocenter di riconoscere i record già
  importati. L'URL della scheda (`index.php?mod=caves&op=view&id=N`) è l'attribution URL e
  l'`@id` del JSON-LD.
- `gn:alternateName`: prima il numero di catasto, poi `alias` e `synonyms`, separati da
  ` | `.
- Coordinate: WGS84 calcolate da OpenKIS; quota da `elevation`, `elevation_map`,
  `elevation_gps`; `dwc:coordinatePrecision` letta da `location_evaluation` quando la
  riporta (es. "GPS, precisione 4m").
- Sviluppo e dislivelli arrotondati al metro, dislivelli senza segno.
  `karstlink:isTouristic` da `tourist`, `karstlink:hasRules` da `closed`.
- `owl:sameAs`: l'ingresso di Grottocenter di un collegamento confermato (vedi sotto).
- Cache come le altre esportazioni delle mappe (`openkis_MapCacheKey()`); impostazioni e
  collegamenti fanno parte della chiave.

## Collegamenti con Grottocenter

**Configurazione > Grottocenter** (sezione `grottocenter`, tabella `ctl_grottocenter`),
riservata agli amministratori della sezione grotte (`groupadmin`, es. `w_caves`) e del
sito.

1. **Cerca i collegamenti**: legge gli ingressi di Grottocenter attorno alle grotte
   (`GET /geoloc/entrances`, riquadri di 1x1 gradi conservati in
   `misc/_cache/grottocenter/` per `cache_hours`) e propone per ogni grotta l'ingresso più
   probabile.
2. Verifica le proposte: conferma, scegli un altro candidato, inserisci a mano l'id di un
   ingresso (letto da `GET /entrances/{id}`; un avviso se è oltre `max_distance`), oppure
   segna la grotta come assente su Grottocenter. **Conferma le proposte con punteggio di
   almeno** conferma insieme quelle sopra `auto_confirm_score`.
3. I collegamenti confermati compaiono nella scheda della grotta (riquadro
   "Grottocenter", anche con le schede regionali) e nell'esportazione come `owl:sameAs`.

Una nuova ricerca mantiene i collegamenti confermati o segnati come assenti e sostituisce
le altre proposte; un ingresso già confermato per una grotta non viene riproposto.

### Punteggio

Per ogni grotta vengono valutati gli ingressi entro `max_distance`, 0-100%:

- **nomi**: le parole che distinguono la grotta (niente parole generiche di grotta, anche
  dialettali come *arma*, *buranco*, *bus*, niente articoli), confrontate per parole
  comuni e per somiglianza del testo, che tollera nomi troncati o scritti male. Si
  confrontano ogni sinonimo e ogni parte tra parentesi quadre del nome su Grottocenter.
  Il numero di catasto dentro il nome su Grottocenter (es. `[LI1878]`) vale come nome
  uguale. I nomi fatti solo di numeri e iniziali ("FESSURA N. 1") valgono la metà, e così
  i nomi con sigle numerate diverse ("BUCA -B39- DI CARCARAIA" e "POZZO -B9- DI
  CARCARAIA").
- **distanza**: `exp(-d / 60 m)`, 0,97 a 2 m, 0,61 a 30 m, 0,19 a 100 m.
- punteggio = `1 - (1 - 0,95 * nomi) * (1 - 0,9 * distanza)`: basta uno dei due per un
  punteggio alto, lo stesso nome a centinaia di metri o un nome troncato a pochi metri.
  Gli ingressi segnati come duplicati su Grottocenter (*doublon*) perdono il 10%.

Le coppie vengono poi prese dalla migliore: ogni grotta riceve il miglior ingresso non
preso da una coppia migliore. Sotto il 40% la grotta va in "nessuna corrispondenza", i
suoi candidati restano elencati.

Sul catasto nazionale WISH (44.595 grotte con coordinate, 66 riquadri, 34.957 ingressi di
Grottocenter) la ricerca ha proposto 31.753 collegamenti, 31.202 al 90% o più, in 23 s con
i riquadri già scaricati (picco 270 MB). Friuli Venezia Giulia, Lombardia, Sardegna,
Lazio e Campania sono quasi tutte su Grottocenter, alle stesse coordinate; Veneto,
Sicilia ed Emilia-Romagna quasi per niente.

### Impostazioni

| Impostazione | Predefinito | |
| --- | --- | --- |
| `source_name` | nome del sito | `attributionName` di Karstlink; non cambiarlo dopo il primo import su Grottocenter |
| `license` | non impostata | CC-BY, CC-BY-SA, ODbL, ODC-By: richiesta da Grottocenter per importare |
| `export_texts` | no | esporta anche `description`/`itinerary` con i loro autori: prima verificare i diritti |
| `country_code` | IT | quando `country` non è riconosciuto |
| `max_distance` | 500 m | i candidati più lontani vengono ignorati |
| `auto_confirm_score` | 90% | soglia della conferma in blocco |
| `cache_hours` | 24 | durata dei riquadri scaricati |
| `api_url` | `https://api.grottocenter.org/api/v1` | |

Le impostazioni cambiano l'esportazione pubblica: le modificano solo gli amministratori
del sito.

### Limiti dell'API di Grottocenter

- `/geoloc/entrances` accetta al massimo 35000 km² per richiesta (un riquadro di 1 grado è
  più piccolo), i client anonimi 200 richieste ogni 10 minuti: una regione richiede pochi
  riquadri, tutta l'Italia qualche decina.
- Se un riquadro non si può leggere, le sue grotte restano com'erano e l'errore viene
  mostrato.

### Memoria

L'esportazione viene scritta riga per riga. Su un catasto grande come WISH (46.000
grotte) il picco resta comunque di circa 450 MB (CSV) e 490 MB (JSON-LD), con
`memory_limit` 512M: quasi tutto lo consuma dbview (`FNDBVIEW::GetResults()`), il cui form
carica tutti i record delle tabelle collegate da una chiave esterna (`caveslinks` ->
`ctl_caves`) con tutte le colonne. La cache della mappa di WISH paga lo stesso costo.
Caricare solo le colonne delle opzioni in `xmetadb_frm.php` (finis) lo eliminerebbe.

## Non ancora fatto

L'invio a Grottocenter delle grotte mancanti (`POST /entrances/check-rows` e
`/entrances/import-rows`) richiede un account Grottocenter abilitato all'import, l'accordo
del team di Grottocenter e una licenza per i dati. Le righe Karstlink dell'esportazione
sono già nel formato di quell'importatore. Problema noto dell'importatore da verificare
prima: legge `gn:alternateName` come oggetto (`row['gn:alternateName'].name` in
`CSVImportQueueService`).
