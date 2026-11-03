# Temperature sotterranee (progetto UCC)

La sezione **Temperature** (`index.php?mod=temperatures`, tabella `ctl_temperatures`)
archivia le misure di temperatura fatte in grotta secondo le schede del **progetto UCC**,
*Underground Climate Change* ([progettoucc.it](https://www.progettoucc.it)): progetto
nazionale di citizen science della speleologia italiana, avviato a fine 2023 e oggi
commissione della Società Speleologica Italiana, che raccoglie le temperature sotterranee
storiche e attuali d'Italia e installa stazioni di monitoraggio standard.

## Le schede UCC

Il progetto raccoglie i dati in due fogli Google, una scheda per regione, con una struttura
fissa che i referenti regionali non devono modificare:

- **Misure spot** (`UCC_misure spot T aria`, v3.3): una riga per ogni lettura della
  temperatura dell'aria fatta con un termometro, per lo più storica (dagli anni '20 in poi)
  e recuperata da pubblicazioni, schede catastali e appunti di campagna.
- **Monitoraggi** (`UCC_monitoraggi T aria`, v1.2): una riga per ogni punto di misura di
  una serie registrata da un data logger, con le statistiche della serie (media, minima,
  massima, moda, mediana, deviazione standard) e chi conserva i dati completi.

Entrambi i fogli identificano la grotta con nome e numero di catasto, coordinate WGS84 in
gradi decimali (bastano 5 decimali), area carsica, comune e quota dell'ingresso; poi la
**posizione** del punto nella grotta, la sua quota e la distanza dall'ingresso (anche
approssimate: sono accettati 20 m sulla quota e 50 m sulla distanza; 0 per una misura
all'ingresso), il numero di ingressi, le condizioni nel punto (ventilazione: non
percepibile, debole, media, intensa; neve o ghiaccio; acqua: assente, veli parietali,
stillicidio, torrente) e lo strumento. Le date delle misure spot sono divise in **anno,
mese e giorno**, perché le letture storiche hanno spesso una data incompleta; l'ora è
`hh:mm`. I numeri devono restare numerici (niente unità nelle celle), la bibliografia è
almeno *autore/i, anno*, e tutto il resto va nelle note (le letture della temperatura
dell'acqua, la temperatura esterna, ...). L'ultima colonna è la sigla del compilatore.

Le **stazioni di monitoraggio** del progetto seguono le linee guida del suo comitato
scientifico (*Criteri per l'installazione di una stazione termometrica sotterranea*, giugno
2024, riviste nel 2025): data logger con sonde di risoluzione 0,01 °C e accuratezza entro
0,2 °C, che misurano l'**aria** (sonda sospesa al centro dell'ambiente), la **roccia**
(sonda in un foro profondo 40 cm, una seconda a 80-100 cm se disponibile) e l'**acqua**
(stillicidio o torrente), letture ogni 30 minuti, scaricate due volte l'anno, in un luogo
lontano dagli ingressi e dalla superficie (50-100 m di roccia), con una stazione esterna.

## Il record in OpenKIS

Un record è **un punto di misura**: una lettura spot o una serie di monitoraggio. La
grotta è un collegamento al catasto (`cave`, il codice della grotta), così nome,
coordinate, comune, area carsica e quota dell'ingresso non si riscrivono e il record segue
la visibilità della grotta (una misura in una grotta riservata è visibile agli stessi
gruppi, campo `groupview`). Le coordinate libere sostituiscono l'ingresso della grotta per
un sito non a catasto o per un punto lontano dall'ingresso.

| Gruppo | Campo | Colonna UCC | Note |
| --- | --- | --- | --- |
| | `name` | | Calcolato se vuoto: *grotta - posizione - data* |
| | `type` | foglio | `spot` o `monitoring` |
| | `medium` | (note) | `air`, `water` o `rock`; i fogli UCC sono per l'aria, gli altri mezzi sono esportati con il mezzo nelle note |
| Punto di misura | `cave` | nome grotta, num. Catasto, Long., Lat., area carsica, comune, quota ingresso | Dal catasto |
| | `position` | posizione | |
| | `measure_elevation` | quota misura | m s.l.m. |
| | `entrance_distance` | distanza ingresso | m lungo la grotta |
| | `latitude_txt`, `longitude_txt`, `coordinates_type` | Long., Lat. | Solo se il punto non è l'ingresso della grotta; convertite in WGS84 in `latitude`, `longitude` |
| Condizioni | `ventilation` | ventilazione | none, weak, medium, strong |
| | `snow_ice` | neve/ghiaccio | absent, present, seasonal, persistent (il foglio spot vuole si/no, quello dei monitoraggi assente/stagionale/persistente) |
| | `water` | acqua / idrologia | absent, films, dripping, stream, pool |
| | `instrument` | strumentazione | |
| | `accuracy`, `resolution` | accuratezza, risoluzione | °C |
| Misura spot | `temperature` | temperatura | °C |
| | `temperature_external` | (note) | Temperatura esterna, esportata come *T esterna* nelle note |
| | `year`, `month`, `day`, `hour` | anno, mese, giorno, ora | Le parti ignote restano vuote |
| Monitoraggio | `start_date`, `end_date` | inizio, fine misurazione | Fine vuota finché è in corso (esportata come *in atto*) |
| | `acq_interval` | intervallo acquisizione | minuti |
| | `samples` | (note) | Numero di letture |
| | `t_mean`, `t_min`, `t_max`, `t_median`, `t_mode`, `t_stddev` | T media, minima, massima, mediana, moda, deviazione standard | °C |
| | `data_holder` | detentore dati | |
| | `bibliography` | bibliografia | |
| | `file` | | Letture o relazione (zip per più file) |
| | `ucc` | | Spuntato quando il record è nel foglio nazionale |
| | `notes` | note | |
| | `sort_date` | | Calcolata: data della lettura spot o inizio del monitoraggio, per l'ordine dell'elenco |
| | `username` | compilatore | Chi ha inserito il record |

La colonna *numero ingressi* dei fogli resta vuota nell'esportazione (il catasto non ha
questo campo): compilarla a mano quando è nota.

## Mappa

`openkis_temperatures.php` restituisce i punti di misura in GeoJSON: un punto per ingresso
di grotta (o per coordinate libere) con l'elenco delle sue misure e la media delle letture
dell'aria (temperature spot e medie dei monitoraggi), da cui la mappa colora il punto:
sotto 4 °C, 4-8, 8-12, 12-16, sopra 16 °C, grigio senza letture. Il livello *Temperature*
della mappa si accende con `bs_map.htm?temperatures=1` (tutti i punti),
`temperature=<id>` (un record) o `temperature_cave=<codice>` (le misure di una grotta), con
sfondo chiaro e livello delle grotte spento (si possono cambiare dal selettore dei livelli); la
pagina della sezione, la scheda di una misura e il riquadro *Temperature* della scheda
della grotta usano questi link. Sono restituiti solo i record e le grotte visibili
all'utente.

## Esportazione per i fogli UCC

`openkis_temperatures.php?format=ucc&type=spot` e `...&type=monitoring` scaricano un CSV
(separatore punto e virgola, UTF-8 con BOM) con le colonne dei due fogli UCC, nel loro
ordine e con le intestazioni italiane, così le righe si incollano nella scheda regionale
del foglio nazionale. I link sono nel piè di pagina della mappa della sezione.
L'esportazione contiene i record visibili all'utente; il campo `ucc` segna quelli già
inviati e l'opzione di ricerca *nella banca dati nazionale UCC* elenca gli altri.
`openkis_temperatures.php?format=ods` scarica le due schede in un unico foglio di calcolo
OpenDocument (`Format_UCC_<data>.ods`, fogli `MISURE_SPOT` e `MONITORAGGI`): il formato del
file `Format_UCC.ods` che il referente ligure del progetto distribuisce per raccogliere i dati
(dicembre 2025), le cui colonne sono quelle dei fogli nazionali *UCC_misure spot T aria* v3.3
e *UCC_monitoraggi T aria* v1.2 (novembre 2026). Numeri, date e ore sono celle tipizzate.

## Fonti

- Sito del progetto UCC: <https://www.progettoucc.it> (documentazione, stazioni, WebGIS)
- Società Speleologica Italiana, commissione UCC: <https://speleo.it/site/underground-climate-change/>
- Schede dati (fogli Google del progetto, accesso dato dai coordinatori):
  *UCC_misure spot T aria* v3.3 e *UCC_monitoraggi T aria* v1.2, di Leonardo Piccini
  (2023), con le indicazioni sui formati date in lista UCC (maggio 2024)
- *Criteri per l'installazione di una stazione termometrica sotterranea*, comitato
  scientifico del progetto UCC (L. Piccini, B. Vigna, F. Catalano, R. Bruschi), giugno 2024
