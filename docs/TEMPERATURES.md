# Underground temperatures (UCC project)

The section **Temperatures** (`index.php?mod=temperatures`, table `ctl_temperatures`) stores
the temperature measurements made in caves, after the data sheets of the **UCC project**,
*Underground Climate Change* ([progettoucc.it](https://www.progettoucc.it)): a national
citizen-science project of the Italian caving community, started at the end of 2023 and
now a commission of the Società Speleologica Italiana, that collects the historic and
current underground temperatures of Italy and installs standard monitoring stations.

## The UCC data sheets

The project collects the data in two Google sheets, one tab per region, with a fixed
structure that the regional contacts must not change:

- **Misure spot** (`UCC_misure spot T aria`, v3.2): one row per reading of the air
  temperature taken with a thermometer, mostly historic (from the 1920s on) and found in
  publications, register sheets and field notes.
- **Monitoraggi** (`UCC_monitoraggi T aria`, v1.2): one row per measurement point of a
  data logger series, with the statistics of the series (mean, minimum, maximum, mode,
  median, standard deviation) and who keeps the full data.

Both sheets identify the cave by name and register number, with WGS84 coordinates in
decimal degrees (5 decimals are enough), karst area, municipality and entrance elevation;
then the **position** of the point in the cave, its elevation and its distance from the
entrance (also approximate: 20 m on the elevation and 50 m on the distance are accepted;
0 for a measurement at the entrance), the number of entrances, the conditions at the point
(ventilation: not perceptible, weak, medium, strong; snow or ice; water: absent, wall
films, dripping, stream) and the instrument. Dates of the spot measurements are split into
**year, month and day**, because old readings often have an incomplete date; the hour is
`hh:mm`. Numbers must stay numeric (no units in the cells), the bibliography is at least
*author(s), year*, and everything else goes in the notes (the readings of the water
temperature, the outside temperature, ...). The last column is the initials of the
compiler.

The **monitoring stations** of the project follow the guidelines of its scientific
committee (*Criteri per l'installazione di una stazione termometrica sotterranea*, June
2024, revised 2025): data loggers with probes of resolution 0.01 °C and accuracy within
0.2 °C, measuring the **air** (probe hanging in the middle of the passage), the **rock**
(probe in a hole 40 cm deep, a second one at 80-100 cm when available) and the **water**
(dripping or stream), readings every 30 minutes, downloaded twice a year, in a place far
from the entrances and from the surface (50-100 m of rock), with a station outside.

## The record in OpenKIS

One record is **one measurement point**: a spot reading or a monitoring series. The cave
is a link to the register (`cave`, the cave code), so name, coordinates, municipality,
karst area and entrance elevation are not typed again and the record follows the
visibility of the cave (a measurement in a restricted cave is visible to the same groups,
field `groupview`). Free coordinates replace the cave entrance for a site that is not in
the register or for a point far from the entrance.

| Group | Field | UCC column | Notes |
| --- | --- | --- | --- |
| | `name` | | Computed when empty: *cave - position - date* |
| | `type` | sheet | `spot` or `monitoring` |
| | `medium` | (notes) | `air`, `water` or `rock`; the UCC sheets are for the air, the others are exported with the medium in the notes |
| Measurement point | `cave` | nome grotta, num. Catasto, Long., Lat., area carsica, comune, quota ingresso | From the register |
| | `position` | posizione | |
| | `measure_elevation` | quota misura | m a.s.l. |
| | `entrance_distance` | distanza ingresso | m along the cave |
| | `latitude_txt`, `longitude_txt`, `coordinates_type` | Long., Lat. | Only when the point is not the cave entrance; converted to WGS84 in `latitude`, `longitude` |
| Conditions | `ventilation` | ventilazione | none, weak, medium, strong |
| | `snow_ice` | neve/ghiaccio | absent, present, seasonal, persistent (the spot sheet wants si/no, the monitoring sheet assente/stagionale/persistente) |
| | `water` | acqua / idrologia | absent, films, dripping, stream, pool |
| | `instrument` | strumentazione | |
| | `accuracy`, `resolution` | accuratezza, risoluzione | °C |
| Spot measurement | `temperature` | temperatura | °C |
| | `temperature_external` | (notes) | Outside temperature, exported as *T esterna* in the notes |
| | `year`, `month`, `day`, `hour` | anno, mese, giorno, ora | Unknown parts left empty |
| Monitoring | `start_date`, `end_date` | inizio, fine misurazione | End empty while running (exported as *in atto*) |
| | `acq_interval` | intervallo acquisizione | minutes |
| | `samples` | (notes) | Number of readings |
| | `t_mean`, `t_min`, `t_max`, `t_median`, `t_mode`, `t_stddev` | T media, minima, massima, mediana, moda, deviazione standard | °C |
| | `data_holder` | detentore dati | |
| | `bibliography` | bibliografia | |
| | `file` | | Readings or report (zip for several files) |
| | `ucc` | | Ticked when the record is in the national sheet |
| | `notes` | note | |
| | `sort_date` | | Computed: date of the spot reading or start of the monitoring, for the list order |
| | `username` | compilatore | Who inserted the record |

The column *numero ingressi* of the sheets is left empty in the export (the register has
no such field): fill it in by hand when known.

## Map

`openkis_temperatures.php` returns the measurement points as GeoJSON: one point per cave
entrance (or per free coordinates) carrying the list of its measurements and the mean of
its air readings (spot temperatures and means of the monitorings), from which the map
colours the point: below 4 °C, 4-8, 8-12, 12-16, above 16 °C, grey without readings. The
layer *Temperature* of the map is switched on with `bs_map.htm?temperatures=1` (all the
points), `temperature=<id>` (one record) or `temperature_cave=<code>` (the measurements of
a cave), on a light base layer and with the caves layer off (both can be changed in the
layer switcher); the section page, the sheet of a measurement and the card *Temperatures*
of the cave sheet use these links. Only the records and caves visible to the user are returned.

## Export for the UCC sheets

`openkis_temperatures.php?format=ucc&type=spot` and `...&type=monitoring` download a CSV
(semicolon separated, UTF-8 with BOM) with the columns of the two UCC sheets, in their
order and with their Italian headers, so the rows can be pasted into the regional tab of
the national sheet. The links are in the footer of the map of the section page. The
export contains the records visible to the user; the field `ucc` records which ones have
already been sent, and the search option *in the UCC national database* lists the others.

## Sources

- UCC project site: <https://www.progettoucc.it> (documentation, stations, WebGIS)
- Società Speleologica Italiana, UCC commission: <https://speleo.it/site/underground-climate-change/>
- Data sheets (Google sheets of the project, access given by the coordinators):
  *UCC_misure spot T aria* v3.2 and *UCC_monitoraggi T aria* v1.2, by Leonardo Piccini
  (2023), with the notes of the project mailing list on the formats (May 2024)
- *Criteri per l'installazione di una stazione termometrica sotterranea*, scientific
  committee of the UCC project (L. Piccini, B. Vigna, F. Catalano, R. Bruschi), June 2024
