# OpenKIS: database indexes

`sql/openkis_indexes.sql` adds 41 secondary indexes (prefix `okx_`) to the
MariaDB/MySQL tables. The finis MySQL driver creates every table with a
primary key only, so every other lookup is a full table scan.

Optional: OpenKIS works without them, they only make the pages faster on
sites with many records.

## Apply

Requires MySQL 5.7+ or MariaDB 10.2+ and a user with the `CREATE ROUTINE`
privilege (the script creates a temporary stored procedure), e.g. root:

```bash
mariadb -u root -p openkis < sql/openkis_indexes.sql
# or
mysql -u root -p openkis < sql/openkis_indexes.sql
```

The script is idempotent: an index is created only when its table and columns
exist, no column is a TEXT/BLOB and the index is not there yet; anything else
is skipped silently. OpenKIS creates tables on first use, so run it again after
new sections have been opened, or after each upgrade. The framework only ever
adds columns (`ALTER TABLE … ADD`), so it never drops these indexes. Building
them takes seconds on tens of thousands of rows; on very large tables run it
outside busy hours.

To remove them: `ALTER TABLE <table> DROP INDEX okx_<name>`.

## How the queries were chosen

From a static read of the code (`finis` + OpenKIS), the queries that reach
MySQL directly (`GetRecords()`/`GetRecord()` with filters and the dbview
module's native path) and filter or sort on a column other than the primary
key, weighted by how often they run:

| Indexes | Query pattern | Where it comes from | How often |
|---|---|---|---|
| `code` on `ctl_caves`, `ctl_artificials`, `ctl_glacial` | `WHERE code LIKE 'X'` | map / KML / GPX / JSON `filter_code`, cave links, every FK that points to a cave (photos, surveys, fauna, springs, bibliography) | Very high |
| `codecave` / `codeartificial` / `codeglacial` on photos, surveys, attachments, fauna, springs | `WHERE codecave LIKE 'X'` | cave view footers (`sections/*/viewfooter.php`), `openkis_photos_server.php`, dbview navigation by cave | Every cave page |
| `recordupdate` on the main tables | `WHERE recorddeleted <> '1' ORDER BY recordupdate DESC LIMIT a,b` | default list order of the caves, artificials, glacial, springs and bibliography sections | Every list page |
| `regione`, `provincia`, `comune`, `areas`, `glaciers`, `year`, `province`, `zone`, `username` | `WHERE field LIKE 'value'` and per-value counts | dbview `navigate_groups` filters configured in the sections | Navigation clicks |
| `(id, recordupdate)` on the `*_versions` tables | `WHERE id LIKE 'X' ORDER BY recordupdate DESC` | record history, `openkis_kml_history.php` | History views; these are the largest tables |
| `code` on `ctl_areas`, `ctl_glaciers`; `name` on `ctl_licenses`; `scientific_name` on `ctl_fauna`, `name` on `ctl_faunacave` | `WHERE link_field LIKE 'X'` | foreign-key value lookups done per record and per field in lists, views and exports | Many per page |
| `(username, displayed)` on `fn_notifications` | `WHERE username LIKE 'u' AND displayed <> 1` | notifications, on every page | Every page |
| `(username, varname)` on `fn_userssessions` | `WHERE username = 'u' AND varname = 'v'` | per-user variables for logged-in users | Every logged-in page |
| `email` on `fn_users` | `WHERE email LIKE 'x'` | login / password recovery by e-mail | Logins |

Primary keys already cover the other FK targets (`ctl_coordinatestypes`,
`ctl_geologicalformations`, `ctl_art_types`, `ctl_art_categories`,
`fn_groups`) and `fn_users.username`.

## Checked

On MariaDB 11.4 with the schema the driver generates from the table
descriptors (`VARCHAR(255)`, `utf8` / `utf8_bin`) and sample data (20,000
caves, 100,000 versions, 40,000 photos, 50,000 notifications): the script
ran three times without errors (also with a table missing), and `EXPLAIN`
shows the indexes used:

| Query | Without the indexes | With the indexes (EXPLAIN) |
|---|---|---|
| cave by code | full scan, 20,000 rows | `okx_code`, 1 row |
| photos of a cave | full scan, 40,000 rows | `okx_codecave`, 2 rows |
| caves list, newest first, 20 per page | full scan + filesort | `okx_recordupdate`, reads 20 rows |
| history of one cave | full scan, 100,000 rows | `okx_id_recordupdate`, 5 rows |
| a user's notifications | full scan, 50,000 rows | `okx_username_displayed`, 167 rows |

`LIKE 'value'` without wildcards uses the index because the tables use the
binary `utf8_bin` collation. `_` inside a value is a LIKE wildcard and
shortens the usable prefix, which still beats a scan.

## What indexes cannot fix

- Queries that go through finis's own `Query()` engine
  (`FN_XMETADBQuery`) read the whole table into PHP whatever the indexes, so
  the notifications and history indexes only pay off where those calls use
  the native path. The same goes for `openkis_UpdateCoords`.
- Free-text search (`LIKE '%text%'`), permission filters on `groupview`
  (`LIKE '%group'`) and the comma-separated code lists in the bibliography
  (`codecaves LIKE '%,X,%'`) can't use a normal index; FULLTEXT indexes or a
  link table would be needed.
- `COUNT(*) … WHERE recorddeleted <> '1'` for the list pagination still reads
  all rows; it is cheap compared with the old full scan + sort.

To see what is left on a live site, enable the slow query log
(`--slow_query_log=1 --long_query_time=0.2 --log_queries_not_using_indexes=1`).
