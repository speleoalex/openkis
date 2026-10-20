# OpenKIS

**Opensource Karst Information System** - A PHP/MySQL web application for managing speleological and karst geological data (caves, springs, glaciers, artificial cavities, fauna surveys).

## Features

- GIS mapping with coordinate system transformations (Proj4)
- 3D cave visualization (CaveView.js)
- Multi-region support with customizable themes
- Data export: KML, GPX, JSON, Shapefile
- REST API endpoints
- MCP server (`openkis_mcp.php`) for AI assistants, with personal API keys and
  read only access by default

## Requirements

- PHP 7.4+ (8.x recommended) with the `mysqli`, `mbstring`, `gd`, `xml`, `curl` extensions
- MySQL 5.7+ / MariaDB 10+
- Apache (with `mod_rewrite`, `AllowOverride All`) or Nginx
- `git` to clone the repository (the Finis framework is a git submodule)

## Installation

### 1. Get the code

```bash
git clone --recurse-submodules https://github.com/speleoalex/openkis.git
cd openkis
# if you cloned without --recurse-submodules:
git submodule update --init --recursive
```

The folder must be served by the web server (e.g. `/var/www/html/openkis`, or a
folder under `htdocs` / `public_html` with XAMPP).

### 2. Quick setup (Linux, XAMPP or any LAMP)

```bash
./setup.sh -d openkis -u root -p ''          # development, XAMPP defaults
./setup.sh -d openkis -u dbuser -p secret -w www-data   # server: files owned by the web user
./setup.sh -h                                # all options
```

The script checks out the submodule, writes `extra/openkis_config.local.php`,
creates the database (when the `mysql` client is available), copies the
`api_keys.local.js` examples, creates `misc/firstinstall` so that the wizard
starts, and makes the folders writable. Then go to step 4.

### 3. Manual setup (what the script does)

1. Copy `extra/openkis_config.local.example.php` to `extra/openkis_config.local.php`
   and set the database connection (the file is git-ignored, never commit it):

   ```php
   $_FN['default_database_driver'] = 'mysql';
   $_FN['xmetadb_mysqlhost'] = 'localhost';
   $_FN['xmetadb_mysqldatabase'] = 'openkis';
   $_FN['xmetadb_mysqlusername'] = 'root';
   $_FN['xmetadb_mysqlpassword'] = '';
   $_FN['openkis_custom'] = '';   // optional: liguria, piemonte, lombardia, ...
   ```

2. Create the database (skip if the DB user has the `CREATE` privilege, e.g.
   XAMPP `root`: it is created on the first request):

   ```sql
   CREATE DATABASE openkis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Make the project writable by the web server user. The framework writes
   `.htaccess`, `misc/` (data, cache, uploads), `sections/`, `include/`,
   `images/`, `languages/`, `modules/`, `themes/default/` in the project root:

   ```bash
   sudo chown -R www-data:www-data .      # server (Apache user: www-data, apache, daemon on XAMPP)
   chmod -R a+w .                         # or, for a local development copy
   ```

4. Do **not** create the `misc/` folder by hand: the framework creates it and
   starts the wizard on the first visit. If `misc/` already exists, run
   `touch misc/firstinstall` to start the wizard.

### 4. Run the wizard

Open `http://localhost/openkis/` (or your site URL). The wizard runs in 4 steps:

1. **Language** of the admin interface.
2. **Permission check** (fix the folder permissions if something is red).
3. **Site settings**: site name, e-mail, languages. Choose theme **`openkis`**
   and home section **`map_caves`** (map of caves) or `caves` (list). The
   `home` section is the WISH portal home page and needs the `regioni` data.
4. **Administrator account**: the first registered user becomes the
   administrator (level 10).

The OpenKIS tables (`ctl_caves`, `ctl_springs`, `ctl_surveys`, ...) are created
automatically from the definitions in `extra/openkis/install/misc/fndatabase/`
the first time each section is opened. No SQL dump is needed.

### 5. After the installation

- **Admin area**: `index.php?fnapp=controlcenter` (login with the account
  created in the wizard). Map defaults (centre, zoom) are in the
  `configopenkis` section; the regional customisation can also set them.
- **Demo data** (one sample cave):

  ```bash
  mysql -u root openkis < extra/openkis/install/demo_cave.sql
  ```

- **Database indexes** (optional, faster pages with many records):
  `mysql -u root -p openkis < sql/openkis_indexes.sql`, see `INDEXES.md`.
- **Map API keys** (optional layers like Google, Bing, regional WMS): edit
  `bs_map/conf/api_keys.local.js` and `themes/api_keys.local.js`
  (git-ignored, copied from the `*.example.js` files by `setup.sh`).
- **Groups**: `r_caves`/`w_caves`, `r_artificials`/`w_artificials`,
  `r_fauna`/`w_fauna` (read / write) are created automatically; assign users
  to the `w_*` groups to let them insert and edit records.
- **Regional customisation**: set `$_FN['openkis_custom']` to a folder name
  in `extra/openkis/custom/` (automatic cave numbering, PDF sheets, map
  centre). Themes `openkis_liguria`, `openkis_lombardia`, ... extend the base
  `openkis` theme.

- **MCP server** (optional): AI assistants (Claude, ChatGPT, ...) can query and,
  if allowed, edit the database through `openkis_mcp.php`. An administrator
  enables the users in **Configuration > MCP server** (group `mcp_users`);
  enabled users generate their key on the same page. Keys are read only unless
  the administrator allows the write tools (group `mcp_write`). Connect with:
  `claude mcp add --transport http openkis https://<site>/openkis_mcp.php --header "X-API-Key: <key>"`.
  See [docs/MCP_SERVER.md](docs/MCP_SERVER.md).

### XAMPP notes (Linux, `/opt/lampp`)

- Apache runs as user `daemon`; `setup.sh` without `-w` uses `chmod -R a+w`,
  which is fine for a local copy. Files created by the web server belong to
  `daemon`: use `sudo` to delete them (`sudo rm -rf misc` to start over).
- PHP and the client are in `/opt/lampp/bin/php` and `/opt/lampp/bin/mysql`;
  the PHP error log is `/opt/lampp/logs/php_error_log`.
- To reinstall from scratch: `sudo rm -rf misc`, drop the database, run
  `./setup.sh` again.

## Testing

`tests/smoke_test.sh` checks a copy of the code served by a local web server:
`php -l` on every file, optionally a clean install (`setup.sh` + wizard),
every section as anonymous and administrator, the KML/GPX/JSON/markers
exports and their cache, the control-center "Refresh map data" page,
`refresh-cache.php`, `sql/openkis_indexes.sql` and the PHP error log.

```bash
# existing test copy (defaults: ~/public_html/openkis_test, DB openkis_test, XAMPP root without password)
tests/smoke_test.sh
# wipe the test copy and its database, install from scratch, then run every check
tests/smoke_test.sh -f
```

`tests/smoke_test.sh -h` lists the environment variables (`TEST_DIR`,
`TEST_URL`, `TEST_DB`, `DB_USER`, `DB_PASS`, `FINIS_SRC`, ...). It copies the
git `HEAD` of the repository and of `finis`, so commit before testing.

## Upgrading

```bash
git pull --recurse-submodules
```

New fields added to the table definitions are applied automatically
(`ALTER TABLE ... ADD`). If you upgrade an installation older than
2024-03-25, also run the SQL in `extra/openkis/update.md`.

## Support the Project

[![Donate with PayPal](paypal.png)](https://www.paypal.com/donate/?business=TKQWLKGENEP7L&no_recurring=0&item_name=Openkis+project%3A%0AOpensource+Karst+Information+System%0A&currency_code=EUR)
