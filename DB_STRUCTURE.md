# Column types: proposed improvements

**Status: proposal, not applied.** See also [INDEXES.md](INDEXES.md).

## Why almost everything is `VARCHAR(255)`

`finis/src/include/xmetadb/XMETATable_mysql.php` builds the MySQL tables from the field
descriptors:

| descriptor `type` | MySQL type |
|---|---|
| `text`, `html` | `TEXT` |
| `longtext` | `LONGTEXT` |
| `datetime` | `DATETIME` |
| `int` (`number` only when adding a column) | `INT` |
| `float`, `json` (only when adding a column) | `FLOAT`, `JSON` |
| anything else (`string`, `check`, `radio`, `select`, `image`, `file`, ...) | `VARCHAR(255)` |

- The `size` of the descriptor is ignored for strings: the length is always forced to 255.
- New columns added by `ALTER TABLE ... ADD` get `utf8_bin`, while many tables were
  converted to `utf8_general_ci`: the same table can mix collations.
- finis never modifies an existing column. A type changed by hand with `ALTER TABLE ...
  MODIFY` stays as it is, so the changes below can be made on a live database without
  touching finis. A **new** installation will still create `VARCHAR(255)` unless the
  descriptors (and the type mapping) are updated too.

How finis reads and writes values (relevant for the changes below):

- Insert and update write `NULL` when the value is `""` and the column is nullable
  (`Null = YES`); otherwise they write the quoted string.
- Every `NULL` read from MySQL is returned as `""` (`fix_null()`).

So a **nullable** numeric or date column round-trips an empty field as empty. A `NOT NULL`
numeric column would turn an empty field into `0`. With the empty `sql_mode` of the
production server this would happen silently (only a warning), and an empty elevation
would become "elevation 0". **Every column converted below must be `NULL DEFAULT NULL`.**

## What `VARCHAR(255)` costs today

- **Wrong order and filters on numbers.** Lengths, depths and elevations are strings, so
  `ORDER BY lenght_total` gives `100 < 20 < 3`. A range filter ("caves longer than
  1000 m") needs `+0` casts and can never use an index.
- **Truncation.** In Catasto Liguria (October 2026), one `ctl_bibliography.title` is
  exactly 255 characters and one `authors` value is 251: the first is almost certainly
  cut.
- **Index size.** A `VARCHAR(255)` utf8 key part takes 765 bytes. On MyISAM (1000 bytes
  per key) and on InnoDB without `innodb_large_prefix` (767 bytes), composite indexes
  need prefix lengths (`id(64),recordupdate(32)` in `sql/openkis_indexes.sql`).
- **Temporary tables.** MySQL 5.5–5.7 in-memory temporary tables (GROUP BY, DISTINCT,
  ORDER BY on non-indexed columns) store `VARCHAR` at full width: 765 bytes per column
  per row. Shorter columns keep more queries in memory.
- On disk the gain is small: `VARCHAR` only stores the actual length.

## Data checked (Catasto Liguria, production, 2026-10-20)

`ctl_caves`, 2,325 rows:

| column | max length | content |
|---|---|---|
| `code` | 8 | `LI1625` |
| `provincia` | 2 | `GE`, `SV`, ... |
| `areas` | 13 | comma separated area codes |
| `geologicalformation` | 4 | CARG code |
| `comune` / `localita` | 30 / 54 | |
| `name` / `synonyms` | 75 / 86 | |
| `groupview` | 43 | comma separated groups |
| `firstreference` / `changelog` | 194 / 166 | free text, keep 255 |
| `recordupdate`, `recordinsert`, `check_date` | 19 | all `YYYY-MM-DD HH:MM:SS` |
| `lenght_*`, `depth_*` | | all numeric, no decimal comma; up to 4800 m, up to 2 decimals (about 200 values per column with decimals) |
| `elevation*` | | all numeric; up to 1986 m; 30 values with decimals, up to 4 (values from the DTM) |
| `fn_users.username` | 26 | |
| `marine`, `closed`, `destroyed`, `tourist`, `archeological`, `hidden` | 1 | `Y` / `N` / `P` / empty |
| `check` | | **free text** (names of the people who checked): not a flag |

`ctl_bibliography.year`: 175 values are not a plain year (`1992-93`, `1969-1970`,
`2007 `): it must stay a string. The `ctl_surveys.date` values are all `YYYY` or
`YYYY-MM-DD`.

## Proposed types

| columns | now | proposed | gain |
|---|---|---|---|
| `recordupdate`, `recordinsert`, `coordnatesupdated`, `check_date` (all tables and `*_versions`) | `VARCHAR(255)` | `DATETIME NULL` | date ranges and sort on 5–8 bytes; no prefix in `okx_id_recordupdate` |
| `lenght_total`, `lenght_planimetric`, `lenght_extension`, `depth_positive`, `depth_negative`, `depth_total` | `VARCHAR(255)` | `DECIMAL(9,2) NULL` | numeric sort, range filters |
| `elevation`, `elevation_map`, `elevation_gps` | `VARCHAR(255)` | `DECIMAL(9,4) NULL`, or `DECIMAL(9,2)` after rounding the 30 DTM values (ask the curators) | numeric sort, range filters |
| `ctl_springs.latitude`, `longitude` | `VARCHAR(255)` | `DOUBLE NULL` (as in `ctl_caves`) | consistency |
| `code`, `codecave`, `codeartificial` and the other references to a code | `VARCHAR(255)` | `VARCHAR(32)` | full column indexes, smaller keys |
| `provincia`, `province` | `VARCHAR(255)` | `VARCHAR(8)` | |
| `areas`, `groupview`, `groupinsert` | `VARCHAR(255)` | keep `VARCHAR(255)` | lists, they can grow |
| `username`, `userupdate` | `VARCHAR(255)` | `VARCHAR(64)` (longest today: 26) | |
| `id` of the tables with a string key (`*_versions.id`, `ctl_artificials.id`, ...) | `VARCHAR(255)` | `VARCHAR(64)` | |
| yes/no fields (`marine`, `closed`, `destroyed`, `tourist`, `archeological`, `hidden`, `lake`, `environmentalrisk`) | `VARCHAR(255)` | `VARCHAR(1)` | |
| `ctl_bibliography.title`, `authors` | `VARCHAR(255)` | `TEXT` | no more truncation |
| `check` | `VARCHAR(255)` | keep: it holds names, not a flag | |

Not proposed:

- **No `utf8mb4`.** Index limits as above; also see the collation notes in the project
  instructions.
- **No `ENUM`.** Values come from `ctl_*` lookup tables or field options; an `ENUM`
  duplicates them in the schema.
- **Engine.** The data tables are already InnoDB. The MyISAM tables are configuration
  (`fn_*`, `fncf_*`, `themes_s_*`) plus `ctl_artificials_versions`, `fieldusers`,
  `ctl_fauna`, `ctl_licenses`, `ctl_coordinatestypes` and the `*_download_stat` tables.
  Converting `ctl_artificials_versions` and `fieldusers` to InnoDB (to match
  `ctl_caves_versions`) is safe and gives crash safety. Leave the rest.
- **Collation.** Keep the `utf8_general_ci` / `utf8_bin` mix as it is now. The
  `*_versions` tables are `utf8_bin`: a history search by name is case sensitive.
  Convert them only together with the main table.

## How to apply (when decided)

1. **Descriptors first.** Set the types in the field descriptors (`type="datetime"`,
   `type="int"` / `float`, `type="text"`). Otherwise new installations and new regions
   keep creating `VARCHAR(255)`. For the shorter strings finis needs a change: honour
   `size` for `VARCHAR` instead of forcing 255.
2. **Check that no value is lost.** Run the checks in the "Data checked" section again
   right before the change, and look for:
   - values longer than the new size;
   - non-numeric or comma-decimal numbers;
   - dates that are not `YYYY-MM-DD HH:MM:SS`.

   An `ALTER` in non-strict mode truncates or zeroes them **without an error**.
3. **Empty strings to `NULL`** before changing the type (otherwise they become `0` or
   `0000-00-00 00:00:00`):

   ```sql
   UPDATE ctl_caves SET elevation = NULL WHERE elevation = '';
   ```

4. **Change the main table and its `_versions` table together.** The history copies whole
   rows: the two must keep the same types.

   ```sql
   ALTER TABLE ctl_caves
     MODIFY `recordupdate` DATETIME NULL DEFAULT NULL,
     MODIFY `elevation` DECIMAL(9,4) NULL DEFAULT NULL,
     MODIFY `code` VARCHAR(32) NOT NULL DEFAULT '';
   ```

5. **Test on a copy:**
   - insert, edit and delete of a record;
   - the history view and restore;
   - list sorting and the search form (filters with `LIKE` on a numeric column still
     work, through an implicit cast);
   - the exports (KML, GPX, shapefile, JSON);
   - the MCP `update` tool.
6. **Code that compares dates as strings** (`recordupdate > '2026-01-01'`) keeps working.
   A `DECIMAL(9,2)` reads back as `120.00`, not `120`: lengths and elevations must be
   formatted where they are printed (`+0` in SQL or `(float)` in PHP), otherwise every
   card and export shows the trailing zeros.
