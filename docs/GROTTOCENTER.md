# Grottocenter

[Grottocenter](https://grottocenter.org) is the collaborative database of the caves of the
world. It already holds many Italian caves, often imported years ago from the regional
registers: same coordinates, but no cave code and names that are sometimes truncated.
OpenKIS links its caves to the same caves on Grottocenter and exports them in the
Karstlink format, which Grottocenter can import.

Nothing is written on Grottocenter: OpenKIS only reads its public API, no account needed.

Italian version: [GROTTOCENTER_IT.md](GROTTOCENTER_IT.md).

## Karstlink export

`openkis_karstlink.php` exports the caves in
[Karstlink](https://ontology.uis-speleo.org/howto/), the data exchange standard of the UIS:

```text
https://<site>/openkis_karstlink.php?mod=caves                  CSV
https://<site>/openkis_karstlink.php?mod=caves&format=jsonld    JSON-LD
```

- Same records as the map exports: the ones the visitor may see (section access,
  per-record `groupview`, `public_map`), with valid coordinates (not 0, in range: an
  import that lost the decimal point, e.g. longitude `17297`, is left out). The dbview filters of the
  section work (`filter_code`, `q`, ...). Only the sections whose table is `ctl_caves`
  (404 otherwise).
- The CSV has the columns read by the Grottocenter importer, which differ in a few names
  from the how-to page: `dct:rights/karstlink:licenseType`, `karstlink:verticalExtend`,
  `karstlink:extendBelowEntrance`, `karstlink:extendAboveEntrance`.
- `id` is the record id (Grottocenter wants a number): together with the source name
  (`dct:rights/cc:attributionName`) it lets Grottocenter recognise the records already
  imported. The record URL (`index.php?mod=caves&op=view&id=N`) is the attribution URL and
  the JSON-LD `@id`.
- `gn:alternateName`: the cave code first, then `alias` and `synonyms`, separated by ` | `.
- Coordinates: WGS84 computed by OpenKIS; altitude from `elevation`, `elevation_map`,
  `elevation_gps`; `dwc:coordinatePrecision` read from `location_evaluation` when it says
  so (e.g. "GPS, precisione 4m").
- Lengths and depths rounded to metres, depths without sign. `karstlink:isTouristic` from
  `tourist`, `karstlink:hasRules` from `closed`.
- `owl:sameAs`: the Grottocenter entrance of a confirmed link (see below).
- Cached like the other map exports (`openkis_MapCacheKey()`); the settings and the links
  are part of the cache key.

## Links with Grottocenter

**Settings > Grottocenter** (section `grottocenter`, table `ctl_grottocenter`), reserved
to the administrators of the caves section (`groupadmin`, e.g. `w_caves`) and of the site.

1. **Search the links**: reads the Grottocenter entrances around the caves
   (`GET /geoloc/entrances`, tiles of 1x1 degree cached in `misc/_cache/grottocenter/` for
   `cache_hours`) and proposes for each cave the most likely entrance.
2. Review the proposals: confirm, choose another candidate, enter the id of an entrance
   by hand (read from `GET /entrances/{id}`; a warning when it is farther than
   `max_distance`), or mark the cave as not on Grottocenter. **Confirm the proposals with
   a score of at least** confirms the ones above `auto_confirm_score` all together.
3. The confirmed links appear on the cave sheet ("Grottocenter" card, also with the
   regional sheets) and in the export as `owl:sameAs`.

A new search keeps the links confirmed or marked as missing and replaces the other
proposals; an entrance already confirmed for a cave is not proposed again.

### Score

For each cave the entrances within `max_distance` are scored, 0-100%:

- **names**: the words that tell the cave apart (no generic cave words, also the dialect
  ones such as *arma*, *buranco*, *bus*, no articles), compared by common words and by
  text similarity, which tolerates truncated and misspelt names. Each synonym and each
  bracketed part of the Grottocenter name is compared. The cave code inside the
  Grottocenter name (e.g. `[LI1878]`) counts as same name. Names made of numbers and
  initials only ("FESSURA N. 1") count half, and so do names with different numbered
  designations ("BUCA -B39- DI CARCARAIA" and "POZZO -B9- DI CARCARAIA").
- **distance**: `exp(-d / 60 m)`, 0.97 at 2 m, 0.61 at 30 m, 0.19 at 100 m.
- score = `1 - (1 - 0.95 * names) * (1 - 0.9 * distance)`: either one alone can give a
  high score, a same name hundreds of metres away or a truncated name a few metres away.
  The entrances marked as duplicates on Grottocenter (*doublon*) lose 10%.

The pairs are then taken best first: each cave gets the best entrance not taken by a
better pair. Below 40% the cave gets "no match", its candidates are still listed.

On the national register WISH (44,595 caves with coordinates, 66 tiles, 34,957
Grottocenter entrances) the search proposed 31,753 links, 31,202 of them at 90% or more,
in 23 s with the tiles already downloaded (peak 270 MB). Friuli Venezia Giulia,
Lombardia, Sardegna, Lazio and Campania are almost all on Grottocenter, at the same
coordinates; Veneto, Sicilia and Emilia-Romagna almost not at all.

### Settings

| Setting | Default | |
| --- | --- | --- |
| `source_name` | site name | Karstlink `attributionName`; do not change it after the first import on Grottocenter |
| `license` | not set | CC-BY, CC-BY-SA, ODbL, ODC-By: required by Grottocenter to import |
| `export_texts` | no | also export `description`/`itinerary` with their authors: check the rights first |
| `country_code` | IT | when `country` is not recognised |
| `max_distance` | 500 m | candidates farther away are ignored |
| `auto_confirm_score` | 90% | threshold of the confirmation all together |
| `cache_hours` | 24 | life of the downloaded tiles |
| `api_url` | `https://api.grottocenter.org/api/v1` | |

The settings change the public export: only the site administrators edit them.

### Limits of the Grottocenter API

- `/geoloc/entrances` accepts at most 35000 km² per request (a tile of 1 degree is
  smaller), anonymous clients 200 requests every 10 minutes: a region needs a few tiles,
  the whole of Italy a few tens.
- If a tile cannot be read, its caves are left as they were and the error is shown.

### Memory

The export is written row by row. On a register as large as WISH (46,000 caves) its peak
is still about 450 MB (CSV) and 490 MB (JSON-LD), with `memory_limit` 512M: almost all of
it is spent by dbview (`FNDBVIEW::GetResults()`), whose form loads every record of the
tables linked by a foreign key (`caveslinks` -> `ctl_caves`) with all their columns. The
WISH map cache pays the same cost. Loading only the columns of the options in
`xmetadb_frm.php` (finis) would remove it.

## Not done yet

Sending the caves missing on Grottocenter (`POST /entrances/check-rows` and
`/entrances/import-rows`) needs a Grottocenter account allowed to import, the agreement of
the Grottocenter team and a license for the data. The Karstlink rows of the export are
already in the format of that importer. Known issue of the importer to check before
that: it reads `gn:alternateName` as an object (`row['gn:alternateName'].name` in
`CSVImportQueueService`).
