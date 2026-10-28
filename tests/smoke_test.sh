#!/usr/bin/env bash
#
# OpenKIS smoke test: lint, clean install, every section and every export
# endpoint, map cache, control-center "Refresh map data", database indexes.
#
# It runs against a copy of this checkout served by a local web server
# (XAMPP by default) with its own MySQL database. Nothing in the repository
# is modified.
#
# Usage: tests/smoke_test.sh [-f] [-k]
#   -f   fresh install: wipe the test copy and its database, copy the code
#        again, run setup.sh and drive the installation wizard (needs sudo
#        when the web server owns the generated folders)
#   -k   keep going after a failed check (default: keep going; -k is implied)
#
# Settings (environment variables, defaults in brackets):
#   TEST_DIR   test copy served by the web server  [~/public_html/openkis_test]
#   TEST_URL   its URL, with trailing slash         [http://localhost/speleoalex/openkis_test/]
#   TEST_DB    database name                        [openkis_test]
#   DB_USER / DB_PASS / DB_HOST                     [root / "" / localhost]
#   PHP_BIN / MYSQL_BIN                             [/opt/lampp/bin/php, /opt/lampp/bin/mysql]
#   WEB_USER   web server user, used to run the CLI script with its rights [daemon]
#   FINIS_SRC  finis checkout to copy in the test copy [<repo>/finis]
#   ADMIN_USER / ADMIN_PASS / ADMIN_EMAIL           [admin / Admin12345! / admin@example.com]
#   PHP_ERROR_LOG  web server PHP error log to watch [/opt/lampp/logs/php_error_log]
#
set -u
REPO=$(cd "$(dirname "$0")/.." && pwd)
TEST_DIR=${TEST_DIR:-$HOME/public_html/openkis_test}
TEST_URL=${TEST_URL:-http://localhost/speleoalex/openkis_test/}
TEST_DB=${TEST_DB:-openkis_test}
DB_USER=${DB_USER:-root}; DB_PASS=${DB_PASS:-}; DB_HOST=${DB_HOST:-localhost}
PHP_BIN=${PHP_BIN:-$(command -v /opt/lampp/bin/php || command -v php)}
MYSQL_BIN=${MYSQL_BIN:-$(command -v /opt/lampp/bin/mysql || command -v mysql || command -v mariadb)}
WEB_USER=${WEB_USER:-daemon}
FINIS_SRC=${FINIS_SRC:-$REPO/finis}
ADMIN_USER=${ADMIN_USER:-admin}; ADMIN_PASS=${ADMIN_PASS:-Admin12345!}; ADMIN_EMAIL=${ADMIN_EMAIL:-admin@example.com}
PHP_ERROR_LOG=${PHP_ERROR_LOG:-/opt/lampp/logs/php_error_log}
FRESH=0
while getopts "fkh" opt; do
    case $opt in
        f) FRESH=1 ;;
        k) ;;
        h) sed -n '2,30p' "$0"; exit 0 ;;
        *) sed -n '2,30p' "$0"; exit 1 ;;
    esac
done

PASS=0; FAIL=0; FAILED=()
WORK=$(mktemp -d); trap 'rm -rf "$WORK"' EXIT
COOKIES=$WORK/cookies.txt
ANON=$WORK/anon.txt
PHP_ERRORS="Fatal error|Parse error|Warning:|Deprecated:|Notice:|Uncaught"

say()  { echo; echo "### $*"; }
ok()   { PASS=$((PASS + 1)); echo "  ok    $*"; }
fail() { FAIL=$((FAIL + 1)); FAILED+=("$*"); echo "  FAIL  $*"; }
check() { # check "description" command...
    local desc=$1; shift
    if "$@" >/dev/null 2>&1; then ok "$desc"; else fail "$desc"; fi
}
mysql_q() { "$MYSQL_BIN" -h "$DB_HOST" -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -N -s "$@"; }
mysql_file() { mysql_q "$1" < "$2"; } # errors in the file give a non-zero exit code (SOURCE does not)
# fetch OUTFILE [curl options...] URL  -> prints the HTTP status
fetch() {
    local out=$1; shift
    curl -s -o "$out" -w '%{http_code}' --max-time 300 "$@"
}
# page "description" cookiejar url [extra grep that must match]
page() {
    local desc=$1 jar=$2 url=$3 must=${4:-}
    local out=$WORK/page.html status
    status=$(fetch "$out" -b "$jar" -c "$jar" "$url")
    if [ "$status" != "200" ]; then fail "$desc: HTTP $status"; return 1; fi
    if grep -Eq "$PHP_ERRORS" "$out"; then
        fail "$desc: PHP error in output: $(grep -Eo "($PHP_ERRORS).{0,120}" "$out" | head -1)"; return 1
    fi
    if [ -n "$must" ] && ! grep -q -- "$must" "$out"; then fail "$desc: '$must' not found"; return 1; fi
    ok "$desc"
}
as_web_user() { # run a command with the web server user's rights (misc/ belongs to it)
    if [ "$(id -un)" = "$WEB_USER" ]; then "$@"; else sudo -n -u "$WEB_USER" "$@"; fi
}

echo "OpenKIS smoke test"
echo "repo:     $REPO"
echo "test dir: $TEST_DIR"
echo "url:      $TEST_URL"
echo "db:       $TEST_DB ($DB_USER@$DB_HOST)"
echo "php:      $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"
echo "finis:    $FINIS_SRC"
ERRLOG_START=0
[ -f "$PHP_ERROR_LOG" ] && ERRLOG_START=$(wc -l < "$PHP_ERROR_LOG")

# -------------------------------------------------------------- 1. lint
say "1. php -l on every PHP file of the repository and of finis"
lint_errors=0
while IFS= read -r f; do
    if ! "$PHP_BIN" -l "$f" >/dev/null 2>&1; then
        lint_errors=$((lint_errors + 1)); echo "  syntax error: $f"
    fi
done < <( (cd "$REPO" && git ls-files -z -- '*.php' | tr '\0' '\n' | grep -v '^finis/' | sed "s|^|$REPO/|") ;
          (cd "$FINIS_SRC" && git ls-files -z -- '*.php' | tr '\0' '\n' | sed "s|^|$FINIS_SRC/|") )
if [ $lint_errors = 0 ]; then ok "no syntax errors"; else fail "$lint_errors file(s) with syntax errors"; fi

# -------------------------------------------------------------- 2. fresh install
if [ $FRESH = 1 ]; then
    say "2. fresh install: wipe the test copy and the database"
    mkdir -p "$TEST_DIR"
    for d in misc .htaccess controlcenter images include languages modules themes/default extra/openkis_config.local.php; do
        [ -e "$TEST_DIR/$d" ] || continue
        rm -rf "$TEST_DIR/$d" 2>/dev/null || sudo -n rm -rf "$TEST_DIR/$d"
    done
    check "generated folders removed" test ! -e "$TEST_DIR/misc"
    check "database dropped" mysql_q -e "DROP DATABASE IF EXISTS \`$TEST_DB\`"

    say "2b. copy the code: git HEAD of the repository + HEAD of finis (uncommitted changes are not tested)"
    (cd "$REPO" && git archive --format=tar HEAD) | (cd "$TEST_DIR" && tar xf -)
    rm -rf "$TEST_DIR/finis"; mkdir -p "$TEST_DIR/finis"
    (cd "$FINIS_SRC" && git archive --format=tar HEAD) | (cd "$TEST_DIR/finis" && tar xf -)
    echo "  openkis $(cd "$REPO" && git rev-parse --short HEAD), finis $(cd "$FINIS_SRC" && git rev-parse --short HEAD)"
    # the repository tracks the OpenKIS control-center page: it must be there
    check "code copied" test -f "$TEST_DIR/controlcenter/sections/utilities/refresh_map_data/section.php"
    check "finis copied" test -f "$TEST_DIR/finis/src/include/finis.php"

    say "2c. setup.sh"
    if (cd "$TEST_DIR" && ./setup.sh -d "$TEST_DB" -u "$DB_USER" -p "$DB_PASS" -H "$DB_HOST" > "$WORK/setup.log" 2>&1); then
        ok "setup.sh"
    else
        fail "setup.sh (see below)"; cat "$WORK/setup.log"
    fi
    check "config file created" test -f "$TEST_DIR/extra/openkis_config.local.php"
    check "misc/firstinstall created" test -f "$TEST_DIR/misc/firstinstall"
    check "database created" mysql_q -e "USE \`$TEST_DB\`"

    say "2d. installation wizard"
    rm -f "$COOKIES"
    status=$(fetch "$WORK/w0.html" -b "$COOKIES" -c "$COOKIES" -L "${TEST_URL}index.php")
    if grep -q "fnapp=install" "$WORK/w0.html" || grep -q "Finis installer" "$WORK/w0.html"; then ok "first visit starts the wizard"; else fail "first visit does not start the wizard (HTTP $status)"; fi
    status=$(fetch "$WORK/w1.html" -b "$COOKIES" -c "$COOKIES" -d "lang=it" "${TEST_URL}index.php?fnapp=install&step=1")
    if grep -q "&checkmark;" "$WORK/w1.html" && grep -q 'type="submit"' "$WORK/w1.html"; then ok "step 1: permissions"; else fail "step 1: permissions check failed"; fi
    status=$(fetch "$WORK/w2a.html" -b "$COOKIES" -c "$COOKIES" -X POST "${TEST_URL}index.php?fnapp=install&step=2")
    [ "$status" = 200 ] && grep -q "conf_sitename" "$WORK/w2a.html" && ok "step 2: settings form" || fail "step 2: settings form (HTTP $status)"
    status=$(fetch "$WORK/w2b.html" -b "$COOKIES" -c "$COOKIES" \
        --data-urlencode "conf_languages=it" --data-urlencode "conf_theme=openkis" \
        --data-urlencode "conf_sitename=OpenKIS test" --data-urlencode "conf_site_title=OpenKIS test" \
        --data-urlencode "conf_site_subtitle=smoke test" --data-urlencode "conf_site_email_address=$ADMIN_EMAIL" \
        --data-urlencode "conf_home_section=map_caves" \
        "${TEST_URL}index.php?fnapp=install&step=2")
    theme=$(mysql_q "$TEST_DB" -e "SELECT varvalue FROM fn_settings WHERE varname='theme'" 2>/dev/null)
    home=$(mysql_q "$TEST_DB" -e "SELECT varvalue FROM fn_settings WHERE varname='home_section'" 2>/dev/null)
    [ "$theme" = "openkis" ] && [ "$home" = "map_caves" ] && ok "step 2: settings saved (theme=$theme, home=$home)" || fail "step 2: settings not saved (theme='$theme', home='$home')"
    status=$(fetch "$WORK/w3a.html" -b "$COOKIES" -c "$COOKIES" -X POST "${TEST_URL}index.php?fnapp=install&step=3")
    grep -q 'name="reg"' "$WORK/w3a.html" && ok "step 3: administrator form" || fail "step 3: administrator form (HTTP $status)"
    status=$(fetch "$WORK/w3b.html" -b "$COOKIES" -c "$COOKIES" \
        --data-urlencode "reg=reg" --data-urlencode "username=$ADMIN_USER" --data-urlencode "email=$ADMIN_EMAIL" \
        --data-urlencode "passwd=$ADMIN_PASS" --data-urlencode "name=Administrator" \
        "${TEST_URL}index.php?fnapp=install&step=3")
    users=$(mysql_q "$TEST_DB" -e "SELECT COUNT(*) FROM fn_users" 2>/dev/null)
    level=$(mysql_q "$TEST_DB" -e "SELECT level FROM fn_users WHERE username='$ADMIN_USER'" 2>/dev/null)
    [ "$users" = "1" ] && [ "$level" = "10" ] && ok "step 3: administrator created (level 10)" || { fail "step 3: administrator not created (users=$users level=$level)"; grep -Eo 'color:red[^<]*>[^<]*' "$WORK/w3b.html" | head -3; }
    check "misc/firstinstall removed: installation completed" test ! -e "$TEST_DIR/misc/firstinstall"
else
    say "2. existing install (use -f for a fresh one)"
fi

# -------------------------------------------------------------- 3. login
say "3. login as administrator"
rm -f "$COOKIES" "$ANON"
fetch "$WORK/l0.html" -b "$COOKIES" -c "$COOKIES" "${TEST_URL}index.php" >/dev/null
fetch "$WORK/l1.html" -b "$COOKIES" -c "$COOKIES" -L --data-urlencode "username=$ADMIN_USER" --data-urlencode "password=$ADMIN_PASS" "${TEST_URL}index.php?fnlogin=login" >/dev/null
status=$(fetch "$WORK/cc.html" -b "$COOKIES" -c "$COOKIES" "${TEST_URL}index.php?fnapp=controlcenter")
if [ "$status" = 200 ] && ! grep -q 'type="password"' "$WORK/cc.html"; then ok "control center opens for $ADMIN_USER"; else fail "login failed (HTTP $status)"; fi

# -------------------------------------------------------------- 4. demo data + indexes
say "4. demo data and database indexes"
if [ "$(mysql_q "$TEST_DB" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$TEST_DB' AND table_name='ctl_caves'")" != "1" ]; then
    # tables are created the first time a section is opened
    fetch /dev/null -b "$COOKIES" -c "$COOKIES" "${TEST_URL}index.php?mod=caves" >/dev/null
fi
check "ctl_caves table exists" test "$(mysql_q "$TEST_DB" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$TEST_DB' AND table_name='ctl_caves'")" = 1
if [ "$(mysql_q "$TEST_DB" -e "SELECT COUNT(*) FROM ctl_caves")" = "0" ]; then
    check "demo cave imported (extra/openkis/install/demo_cave.sql)" mysql_file "$TEST_DB" "$REPO/extra/openkis/install/demo_cave.sql"
else
    ok "ctl_caves already has records"
fi
DEMO_CODE=$(mysql_q "$TEST_DB" -e "SELECT code FROM ctl_caves ORDER BY id LIMIT 1")
echo "  demo cave code: $DEMO_CODE"
# the sections create their tables on first use: open them all before the indexes
for s in artificials springs glacial bibliography fauna fauna_surveys areas glaciers licenses; do
    [ -d "$REPO/sections/$s" ] && fetch /dev/null -b "$COOKIES" -c "$COOKIES" "${TEST_URL}index.php?mod=$s" >/dev/null
done
check "sql/openkis_indexes.sql, first run" mysql_file "$TEST_DB" "$REPO/sql/openkis_indexes.sql"
check "sql/openkis_indexes.sql, second run (idempotent)" mysql_file "$TEST_DB" "$REPO/sql/openkis_indexes.sql"
nidx=$(mysql_q "$TEST_DB" -e "SELECT COUNT(DISTINCT table_name, index_name) FROM information_schema.statistics WHERE table_schema='$TEST_DB' AND index_name LIKE 'okx_%'")
[ "${nidx:-0}" -ge 10 ] && ok "$nidx okx_ indexes present" || fail "only ${nidx:-0} okx_ indexes present"
check "procedure dropped after the run" test "$(mysql_q "$TEST_DB" -e "SELECT COUNT(*) FROM information_schema.routines WHERE routine_schema='$TEST_DB' AND routine_name='openkis_add_index'")" = 0

# -------------------------------------------------------------- 5. pages
say "5. every section, anonymous and as administrator"
for d in "$REPO"/sections/*/; do
    s=$(basename "$d")
    case $s in menu_*) continue ;; esac
    page "mod=$s (anonymous)" "$ANON" "${TEST_URL}index.php?mod=$s"
    page "mod=$s (admin)" "$COOKIES" "${TEST_URL}index.php?mod=$s"
done
page "caves: view demo cave" "$ANON" "${TEST_URL}index.php?mod=caves&op=view&id=$(mysql_q "$TEST_DB" -e "SELECT id FROM ctl_caves ORDER BY id LIMIT 1")" "$DEMO_CODE"
page "caves: new record form (admin)" "$COOKIES" "${TEST_URL}index.php?mod=caves&op=new" "form"
page "caves: search" "$ANON" "${TEST_URL}index.php?mod=caves&q=$DEMO_CODE" "$DEMO_CODE"
page "control center: home" "$COOKIES" "${TEST_URL}index.php?fnapp=controlcenter"
page "control center: settings" "$COOKIES" "${TEST_URL}index.php?fnapp=controlcenter&opt=settings"
page "control center: utilities" "$COOKIES" "${TEST_URL}index.php?fnapp=controlcenter&opt=utilities" "refresh_map_data"

# -------------------------------------------------------------- 6. endpoints and cache
say "6. map export endpoints and their cache"
CACHE_DIR=$TEST_DIR/misc/_cache
cache_files() { find "$CACHE_DIR" -type f 2>/dev/null | wc -l; }
endpoint() { # endpoint "desc" url must_contain
    local desc=$1 url=$2 must=$3 a=$WORK/e1 b=$WORK/e2 s1 s2 before after
    before=$(cache_files)
    s1=$(fetch "$a" "$url&nocache=1")
    s2=$(fetch "$b" "$url")
    after=$(cache_files)
    if [ "$s1" != 200 ] || [ "$s2" != 200 ]; then fail "$desc: HTTP $s1 / $s2"; return; fi
    if grep -aEq "$PHP_ERRORS" "$a"; then fail "$desc: PHP error: $(grep -aEo "($PHP_ERRORS).{0,100}" "$a" | head -1)"; return; fi
    if ! grep -aq -- "$must" "$a" && ! (gzip -dc "$a" 2>/dev/null | grep -aq -- "$must"); then fail "$desc: '$must' not in output"; return; fi
    ok "$desc"
    if cmp -s "$a" "$b" || [ "$(gzip -dc "$a" 2>/dev/null | md5sum)" = "$(gzip -dc "$b" 2>/dev/null | md5sum)" ]; then ok "$desc: cached call returns the same data"; else fail "$desc: cached call differs from fresh call"; fi
    [ "$after" -gt "$before" ] && ok "$desc: cache entry written ($before -> $after files)" || fail "$desc: no cache entry written"
}
endpoint "openkis_kml.php (caves)" "${TEST_URL}openkis_kml.php?mod=caves" "$DEMO_CODE"
endpoint "openkis_gpx.php (caves)" "${TEST_URL}openkis_gpx.php?mod=caves" "<wpt lat="
endpoint "openkis_json.php (caves)" "${TEST_URL}openkis_json.php?mod=caves" "$DEMO_CODE"
endpoint "openkis_markers.js.php (caves)" "${TEST_URL}openkis_markers.js.php?mod=caves" "$DEMO_CODE"
for m in artificials springs; do
    s=$(fetch "$WORK/e3" -b "$COOKIES" "${TEST_URL}openkis_kml.php?mod=$m"); [ "$s" = 200 ] && ! grep -aEq "$PHP_ERRORS" "$WORK/e3" && ok "openkis_kml.php (mod=$m, empty table, administrator)" || fail "openkis_kml.php mod=$m (administrator): HTTP $s"
done
# the exports follow the section access: springs is restricted to r_caves,w_caves on a clean install
s=$(fetch "$WORK/e3a" "${TEST_URL}openkis_kml.php?mod=springs"); [ "$s" = 403 ] && ok "openkis_kml.php mod=springs refused to anonymous visitors (restricted section)" || fail "openkis_kml.php mod=springs, anonymous: HTTP $s, expected 403"
s=$(fetch "$WORK/e3b" "${TEST_URL}openkis_map_access.js.php"); [ "$s" = 200 ] && grep -aq '"springs":""' "$WORK/e3b" && grep -aq '"caves":"all"' "$WORK/e3b" && ok "openkis_map_access.js.php: springs hidden, caves visible for anonymous visitors" || fail "openkis_map_access.js.php (HTTP $s)"
# the map proxy fetches only from the hosts of the map layers
s=$(fetch "$WORK/e3c" "${TEST_URL}openkis_proxy.php/http://example.com/"); [ "$s" = 403 ] && ok "openkis_proxy.php refuses a host that is not a map layer host" || fail "openkis_proxy.php/http://example.com/: HTTP $s, expected 403"
s=$(fetch "$WORK/e4" "${TEST_URL}openkis_kml.php?mod=caves&filter_code=NOSUCHCODE")
[ "$s" = 200 ] && ! grep -aq "$DEMO_CODE" "$WORK/e4" && ! (gzip -dc "$WORK/e4" 2>/dev/null | grep -aq "$DEMO_CODE") && ok "kml filter_code is part of the cache key (filtered call does not return the cached full export)" || fail "kml filter_code=NOSUCHCODE returned the demo cave or HTTP $s"
s=$(fetch "$WORK/e5" -b "$COOKIES" "${TEST_URL}openkis_API.php?mod=caves&fields=name,passwd")
[ "$s" = 200 ] && grep -aq "$DEMO_CODE" "$WORK/e5" && grep -aq '"latitude"' "$WORK/e5" && ! grep -aq '"name"' "$WORK/e5" && ok "openkis_API.php: fixed fields, request 'fields' ignored" || fail "openkis_API.php (HTTP $s)"
s=$(fetch "$WORK/e6" -b "$COOKIES" "${TEST_URL}openkis_API.php?op=near&mod=caves&lat=44.42&lon=8.98")
[ "$s" = 200 ] && grep -aq "methers" "$WORK/e6" && ok "openkis_API.php op=near" || fail "openkis_API.php op=near (HTTP $s)"

say "6b. cache invalidation when the table changes"
# edit directly in the database, then refresh from the command line as the web server user
mysql_q "$TEST_DB" -e "UPDATE ctl_caves SET name=CONCAT('Smoke test ', NOW()) WHERE code='$DEMO_CODE'"
newname=$(mysql_q "$TEST_DB" -e "SELECT name FROM ctl_caves WHERE code='$DEMO_CODE'")
s=$(fetch "$WORK/e7" "${TEST_URL}openkis_json.php?mod=caves")
! grep -aq "$newname" "$WORK/e7" && ok "direct DB edit not yet visible (served from cache, as documented)" || ok "direct DB edit already visible"
if (cd "$TEST_DIR" && as_web_user "$PHP_BIN" refresh-cache.php > "$WORK/refresh.log" 2>&1) && grep -q "ctl_caves" "$WORK/refresh.log" && ! grep -Eq "$PHP_ERRORS" "$WORK/refresh.log"; then
    ok "refresh-cache.php (CLI)"; sed 's/^/        /' "$WORK/refresh.log"
else
    fail "refresh-cache.php (CLI)"; sed 's/^/        /' "$WORK/refresh.log"
fi
s=$(fetch "$WORK/e8" "${TEST_URL}openkis_json.php?mod=caves")
grep -aq "$newname" "$WORK/e8" && ok "after the refresh the export shows the new data" || fail "export still stale after refresh-cache.php"
stamp=$(find "$TEST_DIR/misc/fndatabase" -path "*/ctl_caves/updated" 2>/dev/null | head -1)
[ -n "$stamp" ] && ok "ctl_caves stamp file written (${stamp#$TEST_DIR/})" || fail "no ctl_caves stamp file under misc/fndatabase"
# the control-center page
status=$(fetch "$WORK/r1.html" -b "$COOKIES" -c "$COOKIES" "${TEST_URL}index.php?fnapp=controlcenter&opt=utilities/refresh_map_data")
token=$(grep -o 'name="fn_csrf" value="[^"]*"' "$WORK/r1.html" | sed 's/.*value="//;s/"$//')
[ "$status" = 200 ] && [ -n "$token" ] && ok "control center: Refresh map data page with CSRF token" || fail "control center: Refresh map data page (HTTP $status)"
status=$(fetch "$WORK/r2.html" -b "$COOKIES" -c "$COOKIES" --data-urlencode "fn_csrf=$token" "${TEST_URL}index.php?fnapp=controlcenter&opt=utilities/refresh_map_data")
grep -qi "dati delle mappe aggiornati\|map data refreshed" "$WORK/r2.html" && ! grep -Eq "$PHP_ERRORS" "$WORK/r2.html" && ok "control center: refresh executed" || fail "control center: refresh not executed (HTTP $status)"
status=$(fetch "$WORK/r3.html" -b "$COOKIES" -c "$COOKIES" --data-urlencode "fn_csrf=wrong" "${TEST_URL}index.php?fnapp=controlcenter&opt=utilities/refresh_map_data")
! grep -qi "dati delle mappe aggiornati\|map data refreshed" "$WORK/r3.html" && ok "control center: wrong CSRF token refused" || fail "control center: wrong CSRF token accepted"
status=$(fetch "$WORK/r4.html" -b "$ANON" --data-urlencode "fn_csrf=$token" "${TEST_URL}index.php?fnapp=controlcenter&opt=utilities/refresh_map_data")
! grep -qi "dati delle mappe aggiornati\|map data refreshed" "$WORK/r4.html" && ok "control center: anonymous refresh refused" || fail "control center: anonymous refresh accepted"

# -------------------------------------------------------------- 7. server error log
say "6c. per-record edit permissions (tests/record_permissions_test.sh)"
if [ -x "$REPO/tests/record_permissions_test.sh" ]; then
    if TEST_DIR="$TEST_DIR" TEST_URL="$TEST_URL" TEST_DB="$TEST_DB" ADMIN_USER="$ADMIN_USER" ADMIN_PASS="$ADMIN_PASS" "$REPO/tests/record_permissions_test.sh" > "$WORK/perm.log" 2>&1; then
        ok "record permissions: $(tail -1 "$WORK/perm.log")"
    else
        fail "record permissions: $(tail -1 "$WORK/perm.log")"; grep "FAIL" "$WORK/perm.log" | head -20
    fi
else
    echo "  (tests/record_permissions_test.sh missing, skipped)"
fi

say "7. PHP errors logged by the web server during the test"
if [ -f "$PHP_ERROR_LOG" ]; then
    new=$(tail -n +"$((ERRLOG_START + 1))" "$PHP_ERROR_LOG" | grep -E "PHP (Fatal|Parse|Warning|Deprecated|Notice)" | sed 's/^\[[^]]*\] //' | sort | uniq -c | sort -rn)
    if [ -z "$new" ]; then ok "nothing new in $PHP_ERROR_LOG"; else fail "new PHP errors in $PHP_ERROR_LOG:"; echo "$new" | head -40; fi
else
    echo "  (no $PHP_ERROR_LOG, skipped)"
fi

# -------------------------------------------------------------- summary
echo
echo "=================================================="
echo "passed: $PASS   failed: $FAIL"
for f in "${FAILED[@]:-}"; do [ -n "$f" ] && echo "  - $f"; done
[ $FAIL = 0 ]
