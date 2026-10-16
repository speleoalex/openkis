# OpenKIS: static analysis of response-time bottlenecks

Code read: `ravogdev/openkis` branch `docker-deploy` (c. 2026-10-16) with the
finis submodule at `63f7ba8`, MySQL/MariaDB driver (the Docker default:
`default_database_driver = mysql`). Nothing was run or measured: impact
ratings are estimates from the code paths, and grow with the number of
records (caves, versions, notifications).

## Summary

| # | Where | Problem | Impact | Fix effort |
|---|---|---|---|---|
| 1 | `finis/src/include/xmetadb/XMETADatabase.php:445` | The SQL-like `Query()` engine loads the **whole table** into PHP, sorts it in PHP and runs `eval()` once **per row** for the WHERE, even on MySQL | High, grows linearly with table size | Medium |
| 2 | `finis/src/include/functions.inc.php:608` ← `theme.php:185` | Every page view (guests included) runs the notifications query through #1, i.e. reads all of `fn_notifications` | High on busy sites, table only grows | Low |
| 3 | `sections/artificials/viewfooter.php:3` | `openkis_UpdateCoords("artificials", true)` forces a full recalculation of every artificial cavity's coordinates on **every view** of one record | High on the artificials section | Low |
| 4 | `finis/src/modules/dbview/FNDBVIEW.php:1011` | A record's history view reads the whole `*_versions` table through #1 (the biggest table: one full copy per edit) | High on history pages | Low |
| 5 | `XMETATable_mysql.php:233-300` | Tables are created with only a primary key; every field is `VARCHAR(255)`. No index on `code`, `codecave`, `codecaves`, `username`, `recorddeleted`… | Medium–high: every lookup is a full scan | Low (SQL `ALTER TABLE … ADD INDEX`) |
| 6 | `finis/src/include/xmetadb_frm.php:1956` | Showing foreign-key values does one query per record × per FK field × per shown field (N+1), on unindexed columns (#5) | Medium on lists/exports | Medium |
| 7 | `openkis_markers.js.php:30-35`, `openkis_json.php:30-35`, `openkis_kml.php:70-98` | Map endpoints always run the full cave query; markers/JSON compute a cache but never serve it (`PrintJs($cache)` is commented out); KML serves its cache only after the query has run, and `absolute=1` bypasses it | Medium: the map is the home page | Low |
| 8 | `extra/openkis.inc.php:57-72` | After any cave edit, the next map/KML/GPX request recalculates coordinates for all changed rows through #1 and writes them one `UpdateRecord` at a time; rows whose coordinates can't be resolved are redone every time | Medium, spikes after edits | Low–medium |
| 9 | `sections/caves/viewfooter.php:43`, `sections/artificials/viewfooter.php` | Bibliography for a cave is found with `codecaves LIKE '%,CODE,%'` (comma-separated codes in a text field): no index can help, full scan of bibliography on every cave view | Low–medium | Medium (needs a link table) |
| 10 | `sections/home/section.php:37` | The WISH home page loads every cave and computes the stats on each request, no cache | Medium for the WISH portal only | Low |
| 11 | `deploy/Dockerfile:21` | No APCu, so `FN_GetGlobalVarValue` caches go to files under `misc/_cache/`. (Opcache is installed and enabled: checked in the image on 2026-10-16.) | Low | Very low |
| 12 | `XMETATable_mysql.php:211, 327` and `XMETATable.php:205-249` | Per request, each table opened re-reads and regex-parses its XML descriptor, and runs `SHOW TABLES` + `DESCRIBE` (cached only for the request) | Low–medium: a page opens many tables | Medium |
| 13 | `XMETATable_mysql.php:1104` | New ids are computed with `SELECT MAX(CAST(id AS UNSIGNED)) … WHERE id NOT LIKE '%[a-z]%'` (ids are `VARCHAR`, no AUTO_INCREMENT): full scan on every insert | Low (inserts only) | Medium |

## Details

### 1. `Query()` reads everything into PHP

`XMETADatabase::Query()` (`XMETADatabase.php:89`) parses a SELECT string
itself; `getrecords()` then calls `GetRecords()` with **no WHERE and no
LIMIT** (`:445`), sorts the result in PHP (`:467`), and evaluates the
translated WHERE with `eval()` for each row (`:496`), after a regex safety
check that also runs per row. LIMIT is applied at the end. So a
`SELECT … WHERE username LIKE 'x' LIMIT 1` costs a full table read, a PHP sort
and N `eval()` calls, plus memory for the whole table.

Callers that go through it (`FN_XMETADBQuery`, `functions_xmetadb.inc.php:175`)
include the notifications (#2), record history (#4), FK text search
(`FNDBVIEW.php:404`), permissions admin (`:1239`), grouped counts when the
driver isn't native (`:743`), sitemap, login conditions and
`openkis_UpdateCoords` (#8).

The dbview list/search pages themselves are **not** affected on MySQL:
`FNDBVIEW::TableSupportsNativeSql()` (`FNDBVIEW.php:74`) sends their WHERE,
ORDER BY, LIMIT and COUNT(*) straight to MySQL.

Fix: in `FN_XMETADBQuery` (or `Query()`), when every table uses a native SQL
driver, run the query directly with `dbQuery()` on the real table name, the
same way `FNDBVIEW` does. Keep the PHP engine only for the file drivers.

### 2. Notifications on every page

`theme.php:185` calls `FN_GetNotificationsUndisplayed($_FN['user'])` for
every page, also for guests (empty user). It goes through #1, so every page
view reads the whole `fn_notifications` table, which only ever grows.
Fix: skip it for guests, use `GetRecords(array("username" => $user))` (native
WHERE; the commented-out line next to it already does this) and add an index
on `username`.

### 3. Forced coordinate refresh on the artificials page

`openkis_UpdateCoords($mod, $forceall = true)` ignores its own "nothing
changed" cache and recalculates every row; `sections/artificials/viewfooter.php`
calls it with `true` for each record shown. Fix: drop the `true` (or remove the
call: map/KML/GPX already refresh coordinates when needed).

### 4. History view

`FNDBVIEW.php:1011`: `SELECT * FROM {table}_versions WHERE id LIKE $id ORDER
BY recordupdate DESC` through #1 means the whole versions table is read and
sorted in PHP. Fix: `GetRecords(array($pk => $id), false, false,
"recordupdate:DESC")` on the versions table (native), plus an index on the PK
column of the versions tables.

### 5. No secondary indexes, everything is VARCHAR

`CREATE TABLE` (`XMETATable_mysql.php:233-300`) maps every field without a
type to `VARCHAR(255)` and adds only the primary key; there is no way to
declare an index in the XML descriptors. All filters are `LIKE '…'`. Without
indexes, each lookup by cave code, by `codecave` (photos, surveys), by
`username`, etc. scans the table. Numeric fields stored as text also sort
alphabetically (`'100' < '20'`), which is why some sorting is redone in PHP.

Quick win, no code change: `sql/openkis_indexes.sql` (see `INDEXES.md`) adds
the indexes for the most used and heaviest queries. The core of it, for example

```sql
ALTER TABLE ctl_caves        ADD INDEX (code), ADD INDEX (recorddeleted);
ALTER TABLE ctl_photos       ADD INDEX (codecave);
ALTER TABLE ctl_surveys      ADD INDEX (codecave);
ALTER TABLE ctl_caves_versions ADD INDEX (id);
ALTER TABLE fn_notifications ADD INDEX (username);
```

`LIKE 'value'` without wildcards can use these indexes (the tables use the
`utf8_bin` collation). `LIKE '%text%'` free-text search cannot; for that,
a FULLTEXT index on the searched fields would be the next step. Note that
`_` in a code is a LIKE wildcard, which also stops the index range from
being exact.

### 6. Foreign-key lookups (N+1)

`XMETAForm::GetRecordTranslated()` (`xmetadb_frm.php:1895`) resolves each
foreign key with `GetRecord()` per record, per FK field and per shown field
(`:1956`), on unindexed columns. A list or export of 500 caves with 3 FK
fields means 1,500+ queries. Fix: load each FK table's `(link, show)` pairs
once per request into an array and look them up there; indexes (#5) reduce
the cost meanwhile. The edit form also loads every row of each FK table to
fill its select boxes (`:672`), fine for small lookup tables only.

### 7. Map endpoints and their caches

- `openkis_markers.js.php`: builds `$cache` from `FN_GetGlobalVarValue("js$idresult")`
  but `PrintJs($cache)` is commented out (`:35`), so the marker script is
  rebuilt for every request, after running the full query. Same in
  `openkis_json.php:35`.
- `openkis_kml.php`: runs `GetResults()` (`:70`) before checking its cache
  (`:95`), so the cache only saves the XML rendering. `absolute=1` (used by
  every "download KML" button) skips the cache, and each item does a
  `file_exists()` for its icon (`:168`).
- All three call `openkis_UpdateCoords()` first (#8).

Fix: compute the cache key from the request parameters plus
`GetLastUpdateTime()` and return the cached output **before** running the
query; re-enable the commented `PrintJs`/`PrintKml` lines once the key is
safe. Send `Cache-Control`/`ETag` headers instead of `no-cache` so browsers
can reuse unchanged layers.

### 8. Coordinate recalculation

`openkis_UpdateCoords()` (`extra/openkis.inc.php:33`) is cheap when nothing
changed (a file timestamp check), but after any edit it runs
`SELECT … WHERE recordupdate > coordnatesupdated OR latitude LIKE '0' OR
latitude LIKE ''` through #1 (full read + eval per row) and updates rows one by
one. Every `UpdateRecord` touches the table's `updated` marker, which also
invalidates the KML cache. Rows that never get coordinates match
`latitude LIKE ''` forever and are recomputed on every refresh. Fix: native
query, recompute only on save (in the record's save hook) rather than on map
requests.

### 9. Comma-separated code lists

Bibliography items store linked caves as `codecaves = "A12,B7,…"`; the cave
page finds them with four `LIKE` patterns including `'%,CODE,%'`, which always
scans the whole bibliography table. A link table (bibliography id, cave code)
with an index would make it a lookup. Low priority while the bibliography is
small.

### 10. WISH home page

`sections/home/section.php:37` reads all caves (7 columns) and sorts them twice
(SQL and `FN_ArraySortByKey`) for the stats and top lists on each request.
Cache the computed block with `FN_SetGlobalVarValue` keyed on
`GetLastUpdateTime()`.

### 11. PHP caches

Corrected after checking the built image (2026-10-16): `php8.3-opcache` is
installed and `opcache.enable=1`, so PHP files are not recompiled on every
request. APCu is not installed: `FN_GetGlobalVarValue` then keeps its cache
in files under `misc/_cache/`. Adding `php8.3-apcu` to the Dockerfile would
make those lookups a memory read and would allow caching the per-request
schema work (#12) across requests.

### 12. Per-request schema work

Every table object re-reads its descriptor from `misc/fndatabase/*.php` and
parses it with regexes (`XMETATable.php:205-249`), then checks the MySQL table
with `SHOW TABLES` / `DESCRIBE` to sync columns. These are cached only in
`static` variables for the current request. With APCu (#11) these results
could be cached across requests and invalidated on the descriptor's mtime.

### 13. Id generation

`GetAutoincrement()` scans the table with `MAX(CAST(id AS UNSIGNED))` on each
insert because ids are `VARCHAR` without AUTO_INCREMENT. Only matters for bulk
imports (`sections/Imports`).

## Suggested order

1. Infrastructure, no code change: add APCu to the image (#11) and the
   indexes (#5).
2. One-line fixes: #3, #2 (guests + native query), #4.
3. Make `FN_XMETADBQuery` use the native SQL path on MySQL (#1). This fixes
   the root cause behind #2, #4 and #8 at once.
4. Map caches served before the query (#7), FK lookup cache (#6).

To confirm the ranking with numbers, enable the MariaDB slow query log
(`--slow_query_log=1 --long_query_time=0.2`) and use the framework's
`?debug` timer output (`FN_GetExecuteTimer`) on the main pages.
