# OpenKIS deploy environment (Docker)

Docker Compose setup for OpenKIS (Opensource Karst Information System).
This fork ([ravogdev/openkis](https://github.com/ravogdev/openkis)) uses the
[ravogdev/finis](https://github.com/ravogdev/finis) fork as submodule, with the
PHP 8 fixes listed below. Tested end to end on 2026-10-15.

## What the application needs

| Item | Detail |
|---|---|
| Language / runtime | PHP only (no Composer, no Node or Python at run time). PHP 7.4+, runs on **8.3** with the patches below |
| PHP extensions | mysqli, gd, exif, curl, mbstring, xml, zip |
| Web server | Apache with `mod_rewrite` and `AllowOverride All` (the framework writes its own `.htaccess`) |
| Database | MySQL 5.7+ / MariaDB 10+, utf8mb4. Tables are created automatically from `extra/openkis/install/misc/fndatabase/ctl_*.php`; no SQL dump needed |
| Framework | [finis](https://github.com/speleoalex/finis), a git submodule in `finis/` |
| Writable paths | The whole web root: the framework writes `misc/` (data, uploads, cache), `.htaccess`, `sections/`, `include/`, `images/`, `languages/`, `modules/`, `themes/default/`, `controlcenter/` |
| Install | Web wizard on the first visit (language, permission check, site settings, admin account) |
| Secrets | `extra/openkis_config.local.php` (DB credentials, MCP API key), `bs_map/conf/api_keys.local.js`, `themes/api_keys.local.js` (optional map keys) |

## What is in this folder (`deploy/`)

```
Dockerfile             Ubuntu 24.04 + Apache 2.4 + PHP 8.3 (mod_php) + extensions
docker-compose.yml     web (OpenKIS, image robydoc/openkis-web:latest) + db (MariaDB 11.4),
                       two named volumes
docker-compose.build.yml  optional override: build the image from this checkout
.env.example           all settings; copy to .env
backup.sh              dumps the database and misc/ into ./backups/
docker/entrypoint.sh   installs/upgrades code in the volume, writes the config from .env
docker/apache-openkis.conf, docker/php.ini
patches/               optional extra patches applied at build (empty)
```

## Quick start

```bash
git clone https://github.com/ravogdev/openkis.git
cd openkis/deploy
cp .env.example .env          # set OPENKIS_DB_PASSWORD and MARIADB_ROOT_PASSWORD
docker compose up -d          # pulls robydoc/openkis-web:latest from Docker Hub
```

`robydoc/openkis-web:latest` is built from this `deploy/Dockerfile` with this
repository and its finis submodule. Set `OPENKIS_IMAGE` in `.env` to pin
another tag (e.g. `robydoc/openkis-web:2026-10-15`).

### Building the image from this checkout

```bash
git submodule update --init --recursive    # or clone with --recurse-submodules
docker compose -f docker-compose.yml -f docker-compose.build.yml up -d --build
```

The result is tagged `openkis-web:local`.

Open <http://localhost:8080/> and follow the wizard:

1. language;
2. permission check (should be all green);
3. site settings: theme **`openkis`**, home section **`map_caves`** (or `caves`);
4. administrator account (the first user becomes admin, level 10).

Admin area: `http://localhost:8080/index.php?fnapp=controlcenter`.

Optional demo cave:

```bash
set -a; . ./.env; set +a
docker compose exec -T web cat /usr/src/openkis/extra/openkis/install/demo_cave.sql \
  | docker compose exec -T db mariadb -u"$OPENKIS_DB_USER" -p"$OPENKIS_DB_PASSWORD" "$OPENKIS_DB_NAME"
```

(Open the *Caves* section once before importing, so the `ctl_caves` table exists.)

## A site for a single regional cave register

Example for Lombardia; the regional customisations are `liguria`,
`lombardia`, `marche`, `piemonte`, `umbria` (plus `italia` and `wish`).

1. In `.env` (copied from `.env.example`) set the passwords and:
   ```bash
   COMPOSE_PROJECT_NAME=openkis_lombardia   # own containers and volumes
   OPENKIS_HTTP_PORT=8084
   OPENKIS_DB_NAME=openkis_lombardia
   OPENKIS_CUSTOM=lombardia                 # regional rules, map centre
   OPENKIS_HOME_SECTION=map_caves           # landing page: the regional cave map
   OPENKIS_ENABLE_PROXY=1                   # Lombardia only: its IGM map layer
   ```
2. `docker compose up -d`, then open `http://<server>:8084/` and run the
   wizard as in the quick start. The first user registered is the admin.
3. Sign in from the site's login with that user; the admin area is
   `http://<server>:8084/index.php?fnapp=controlcenter`.

Without `OPENKIS_HOME_SECTION` the landing page is the home section chosen in
the wizard (step 3) or later in the control center (*Settings*); the default
`home` is the WISH national portal, which is still reachable at
`index.php?mod=home`.

To run several regions on one server, give each its own env file with a
different `COMPOSE_PROJECT_NAME`, port and database and start each one with
`docker compose --env-file <file> up -d`. The `https` profile below binds
ports 80/443, so only one of them can use it; several public sites on one
server need one reverse proxy in front of all of them.

## Configuration (.env)

| Variable | Meaning |
|---|---|
| `OPENKIS_HTTP_PORT` | host port (default 8080) |
| `OPENKIS_DB_NAME`, `OPENKIS_DB_USER`, `OPENKIS_DB_PASSWORD`, `MARIADB_ROOT_PASSWORD` | database |
| `OPENKIS_CUSTOM` | regional customisation: `example`, `italia`, `liguria`, `lombardia`, `marche`, `piemonte`, `umbria`, `wish`; empty = generic |
| `OPENKIS_HOME_SECTION` | landing page, a folder in `sections/`: e.g. `map_caves` (cave map centred on the region) or `caves` instead of the WISH portal `home`; empty = the home section set in the control center |
| `OPENKIS_MCP_APIKEY`, `OPENKIS_MCP_USER`, `OPENKIS_MCP_READONLY` | MCP API (`openkis_mcp.php`), off while the key is empty |
| `THUNDERFOREST_API_KEY`, `MAPTILER_API_KEY`, `ALGOLIA_DOCSEARCH_API_KEY` | optional map/search keys |
| `OPENKIS_DISPLAY_ERRORS` | `off` (default). `on` shows PHP errors in pages; development only, warnings corrupt the KML/JSON/GPX exports |
| `TZ` | time zone for PHP and MariaDB |
| `OPENKIS_DB_INDEXES`, `OPENKIS_DB_INDEXES_INTERVAL` | `1` (default) applies `sql/openkis_indexes.sql` at start and every `3600` s; `0` = off / only at start (see `INDEXES.md`) |
| `COMPOSE_PROFILES`, `OPENKIS_DOMAIN` | `https` + a domain turn on the Caddy HTTPS front end; empty = off (see below) |
| `OPENKIS_DATA_DIR`, `OPENKIS_DB_DATA_DIR` | host folders for the web root and the MariaDB data; empty = named volumes (see below) |

`extra/openkis_config.local.php` is rewritten from `.env` on every start. To
manage it by hand, remove its "generated by the openkis container entrypoint"
line; it is then left alone.

## How code and data are stored

- Volume `openkis_www` → `/var/www/html`: code **and** everything the framework
  writes. The image keeps a clean copy in `/usr/src/openkis`; on the first start
  and whenever the image version changes, the entrypoint copies it into the
  volume with `rsync` (no `--delete`), so data, uploads and files generated by
  the admin area are kept.
- Volume `openkis_db` → MariaDB data.

### Data in host folders instead of volumes

Set `OPENKIS_DATA_DIR` and/or `OPENKIS_DB_DATA_DIR` in `.env` to a host path
(absolute, or `./relative` to this folder) and that folder is bind-mounted in
place of the named volume; leave them empty to keep the volumes. Example with a
dedicated disk mounted at `/srv/openkis`:

```bash
OPENKIS_DATA_DIR=/srv/openkis/www
OPENKIS_DB_DATA_DIR=/srv/openkis/db
```

Empty folders are filled on the first start (the entrypoint copies the code,
MariaDB initialises its data) and the containers set the ownership (www-data
uid 33, mysql uid 999); do not `chown` them afterwards. Uploads are then
directly in `<OPENKIS_DATA_DIR>/misc/`. Keep using `backup.sh` for the
database: copying the MariaDB folder while it runs can give a broken copy.

To move an existing installation from the volumes, stop it and copy once:

```bash
docker compose down
sudo mkdir -p /srv/openkis/www /srv/openkis/db
sudo cp -a /var/lib/docker/volumes/openkis_openkis_www/_data/. /srv/openkis/www/
sudo cp -a /var/lib/docker/volumes/openkis_openkis_db/_data/.  /srv/openkis/db/
docker compose up -d
```

### Upgrading

```bash
docker compose pull           # newer robydoc/openkis-web:latest
docker compose up -d          # the log shows "installing code version ..."
```

With a local build: `git pull --recurse-submodules`, then
`docker compose -f docker-compose.yml -f docker-compose.build.yml up -d --build`.

New table fields are applied automatically by the framework. For installations
older than 2024-03-25 see `extra/openkis/update.md` upstream.

### Map data after editing the database directly

The map layers (KML, GPX, JSON, markers) are cached and refreshed by
themselves when the data are changed through OpenKIS. After changing the caves
data directly in the database, refresh them from Control center -> Utilities ->
Refresh map data, or with:

```bash
docker compose exec -u www-data web php /var/www/html/refresh-cache.php
```

Otherwise they are rebuilt within an hour
(`$_FN['openkis_map_cache_maxage']`, seconds; 0 disables the cache).

### Backup and restore

```bash
./backup.sh                                    # -> backups/<timestamp>/{db.sql.gz,files.tar.gz}

# restore
set -a; . ./.env; set +a
gunzip -c backups/<ts>/db.sql.gz | docker compose exec -T db mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$OPENKIS_DB_NAME"
docker compose exec -T web tar -C /var/www/html -xzf - < backups/<ts>/files.tar.gz
docker compose exec web chown -R www-data:www-data /var/www/html/misc
```

## PHP 8.3 fixes in this fork

The finis submodule and OpenKIS were written for older PHP. These fixes are
committed in the forks:

| Where | Problem on PHP 8 | Effect without it |
|---|---|---|
| finis: `src/include/html2pdf4` | The bundled TCPDF 5.0.002 (2010) uses `$str{$i}`, `each()` and `create_function()`, all removed in PHP 8 | Fatal error on any PDF with a barcode, form fields, justified text, page moves (used by the Liguria cave sheets) |
| finis: `src/include/finis.php` | finis only accepted a home section in `finis/src/sections/`; OpenKIS sections live in the app | The site root showed "you don't have permission to view this page" whatever home section the wizard saved |
| openkis: `openkis_gpx.php` | The bare word `UTF_8` was passed as charset | GPX export died with "Undefined constant" |

Python and Node in finis (`modules/python3`, `modules/nodejs`) are optional
section types; no OpenKIS section uses them, so the image does not install
them. `sections/home/kml_to_svg.py` is a one-off developer script with a
hard-coded path, not used at run time.

## HTTPS with Caddy

The compose file has an optional `caddy` service (profile `https`) that
terminates TLS in front of OpenKIS, with a free Let's Encrypt certificate that
it gets and renews by itself. It is off unless you turn it on in `.env`:

```bash
COMPOSE_PROFILES=https
OPENKIS_DOMAIN=kis.example.org      # DNS A/AAAA record pointing at this server
OPENKIS_HTTP_PORT=127.0.0.1:8080    # keep plain HTTP off the internet
```

Open ports 80 and 443 on the firewall (80 is needed for the certificate
challenge and redirects to HTTPS), then `docker compose up -d`. Caddy's config
is `docker/Caddyfile`; certificates are kept in the `caddy_data` volume. `OPENKIS_CADDY_HTTP_PORT` / `OPENKIS_CADDY_HTTPS_PORT` change the host ports if
80/443 are taken.

### Public server: free Let's Encrypt certificate

Nothing to configure in Caddy: for a real domain it requests the certificate
from Let's Encrypt and renews it before it expires. What it needs:

1. a DNS **A** (and/or **AAAA**) record for `OPENKIS_DOMAIN` pointing at the
   server's public IP;
2. ports **80 and 443 open** to the internet: Let's Encrypt checks port 80 (or
   443) to prove you control the domain;
3. `docker compose up -d`, then `docker compose logs -f caddy` until it reports
   `certificate obtained successfully`.

If it fails, check the DNS record (`dig +short kis.example.org`) and the
firewall, then `docker compose restart caddy`. Do not delete the `caddy_data`
volume: repeated requests for the same domain hit Let's Encrypt's rate limits.

### Local machine: self-signed certificate for https://localhost

With `OPENKIS_DOMAIN=localhost` (or empty) Caddy creates its own local
certificate authority and issues a certificate for `localhost` by itself:

```bash
COMPOSE_PROFILES=https
OPENKIS_DOMAIN=localhost
OPENKIS_CADDY_HTTPS_PORT=8443       # optional, if 443 is busy
```

Open `https://localhost/` (or `https://localhost:8443/`). The browser warns that
the certificate is not trusted; accept it once, or trust Caddy's root
certificate to remove the warning:

```bash
docker compose cp caddy:/data/caddy/pki/authorities/local/root.crt ./caddy-root.crt
```

- **Windows**: double-click `caddy-root.crt` → Install Certificate → Local
  Machine → "Trusted Root Certification Authorities".
- **macOS**: `sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain caddy-root.crt`
- **Linux**: `sudo cp caddy-root.crt /usr/local/share/ca-certificates/ && sudo update-ca-certificates`
- **Firefox** keeps its own list: Settings → Privacy & Security → Certificates →
  View Certificates → Authorities → Import.

The root certificate lives in the `caddy_data` volume and stays the same across
restarts; trust it only on your own machines.

## Production notes

- Serve it over HTTPS: use the `https` profile above, or another TLS reverse
  proxy in front of port 80; Apache honours `X-Forwarded-Proto: https`. Set the site URL in the control center if
  links come out as `http://`.
- Change both database passwords; MariaDB is not published on the host.
- Apache denies `openkis_config.local.php`, `config.vars.local.php`,
  the `.php` files in `misc/fndatabase/` (PHP is also switched off there, so an
  upload can never run), `.git`, plus the unauthenticated maintenance scripts
  `clear_cache.php` (deletes files from a path in the URL), `openkis_las.php`
  (reads any `*.las` path or URL) and `finis/{utils,tests,examples}/`. Run
  `clear_cache.php` with `docker compose exec web php clear_cache.php` instead.
- `openkis_proxy.php` is an open HTTP proxy (SSRF). It is blocked unless
  `OPENKIS_ENABLE_PROXY=1`; only the Lombardia IGM map layer needs it.
- The finis query layer still evaluates `WHERE` clauses with PHP `eval()`.
  The `php8-compat` finis branch strips `` ` $ { } `` from them before
  evaluation, which closes the known code-execution path through a cave's
  fauna field; replacing `eval()` altogether is still advisable.
- Outgoing e-mail (registration confirmation) uses PHP `mail()`, which has no
  MTA in the container. Disable "registration by mail confirmation" in the
  control center or add an SMTP relay (e.g. `msmtp-mta`) to the image.

## Tested

In a clean container: image build, stack start, the four wizard steps (theme
`openkis`, home `map_caves`, admin user), login, the Caves, Map, Springs,
Artificials, Settings and control center pages with no PHP or JS errors,
demo cave import, JSON/KML/GPX/Shapefile exports, Liguria PDF cave sheet
(`OPENKIS_CUSTOM=liguria`), MCP endpoint (401 without a key), image upgrade on
an existing volume, `down`/`up` with data kept, `backup.sh`.

Not tested: a real hostname with TLS, e-mail, photo/survey uploads through the
UI, the other regional customisations.
