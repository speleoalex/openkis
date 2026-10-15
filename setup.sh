#!/usr/bin/env bash
#
# OpenKIS local setup helper (Linux, tested with XAMPP /opt/lampp).
# Prepares a fresh checkout so that the web installation wizard can run:
#   - checks out the finis submodule
#   - creates extra/openkis_config.local.php from the example
#   - creates the MySQL database (if the mysql client is available)
#   - creates misc/firstinstall so the wizard starts on first visit
#   - copies the api_keys example files
#   - makes the needed folders writable by the web server
#
# Usage: ./setup.sh [options]
#   -d NAME   database name            (default: openkis)
#   -u USER   database user            (default: root)
#   -p PASS   database password        (default: empty)
#   -H HOST   database host            (default: localhost)
#   -r REGION regional customisation   (default: none; e.g. liguria)
#   -w USER   web server user: chown the writable folders to this user
#             instead of chmod a+w (e.g. -w www-data on a server)
#   -n        do not touch permissions
#   -h        help
#
# Never commit extra/openkis_config.local.php: it holds credentials.
set -e
cd "$(dirname "$0")"

DB_NAME=openkis; DB_USER=root; DB_PASS=""; DB_HOST=localhost; REGION=""; WEB_USER=""; NO_PERMS=0
while getopts "d:u:p:H:r:w:nh" opt; do
    case $opt in
        d) DB_NAME=$OPTARG ;;
        u) DB_USER=$OPTARG ;;
        p) DB_PASS=$OPTARG ;;
        H) DB_HOST=$OPTARG ;;
        r) REGION=$OPTARG ;;
        w) WEB_USER=$OPTARG ;;
        n) NO_PERMS=1 ;;
        h) sed -n '2,24p' "$0"; exit 0 ;;
        *) sed -n '2,24p' "$0"; exit 1 ;;
    esac
done

say() { echo "[setup] $*"; }

# 1. finis submodule
if [ ! -f finis/src/include/finis.php ]; then
    say "checking out the finis submodule"
    git submodule update --init --recursive
fi

# 2. PHP / mysql client (XAMPP first, then PATH)
PHP=$(command -v /opt/lampp/bin/php || command -v php || true)
MYSQL=$(command -v /opt/lampp/bin/mysql || command -v mysql || command -v mariadb || true)
if [ -n "$PHP" ]; then
    say "php: $("$PHP" -r 'echo PHP_VERSION;')"
    for ext in mysqli mbstring gd xml json curl; do
        "$PHP" -r "exit(extension_loaded('$ext') ? 0 : 1);" || say "WARNING: php extension '$ext' not loaded"
    done
else
    say "WARNING: php not found in PATH (XAMPP: /opt/lampp/bin/php)"
fi

# 3. local configuration
CONF=extra/openkis_config.local.php
if [ -f "$CONF" ]; then
    say "$CONF already exists, left untouched"
else
    say "creating $CONF"
    sed -e "s|'openkis'|'$DB_NAME'|" \
        -e "s|\['xmetadb_mysqlhost'\] = '[^']*'|['xmetadb_mysqlhost'] = '$DB_HOST'|" \
        -e "s|\['xmetadb_mysqlusername'\] = '[^']*'|['xmetadb_mysqlusername'] = '$DB_USER'|" \
        -e "s|\['xmetadb_mysqlpassword'\] = '[^']*'|['xmetadb_mysqlpassword'] = '$DB_PASS'|" \
        -e "s|\['openkis_custom'\] = '[^']*'|['openkis_custom'] = '$REGION'|" \
        extra/openkis_config.local.example.php > "$CONF"
fi
if [ -n "$REGION" ] && [ ! -f "extra/openkis/custom/$REGION/$REGION.php" ]; then
    say "WARNING: extra/openkis/custom/$REGION/$REGION.php not found"
fi

# 4. database
if [ -n "$MYSQL" ]; then
    say "creating database '$DB_NAME' if missing"
    PASSOPT=(); [ -n "$DB_PASS" ] && PASSOPT=("-p$DB_PASS")
    "$MYSQL" -h "$DB_HOST" -u "$DB_USER" "${PASSOPT[@]}" \
        -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
        || say "WARNING: could not create the database, create it by hand (it is also created on first run if the user has CREATE privilege)"
else
    say "mysql client not found: create the database '$DB_NAME' by hand if the db user cannot create it"
fi

# 5. api keys (optional map layers)
for f in bs_map/conf/api_keys.local.js themes/api_keys.local.js; do
    if [ ! -f "$f" ] && [ -f "${f%.js}.example.js" ]; then
        say "creating $f from example"
        cp "${f%.js}.example.js" "$f"
    fi
done

# 6. data dir + wizard trigger
if [ -d misc/fndatabase ]; then
    say "misc/fndatabase exists: installation already done, wizard not re-triggered"
else
    mkdir -p misc
    touch misc/firstinstall
    say "misc/firstinstall created: the wizard will start on the first visit"
fi

# 7. permissions (the framework writes .htaccess, misc/, sections/, cache and uploads)
if [ "$NO_PERMS" = 0 ]; then
    if [ -n "$WEB_USER" ]; then
        say "chown $WEB_USER on writable folders (may ask for sudo)"
        sudo chown -R "$WEB_USER" . 
    else
        say "chmod a+w on the project (development setup)"
        chmod -R a+w . 2>/dev/null || sudo chmod -R a+w .
    fi
fi

say "done. Open the site in the browser and follow the wizard:"
say "  - theme: openkis   - home section: map_caves (or home)"
say "  - the first registered user becomes the administrator"
say "admin area: index.php?fnapp=controlcenter"
