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

say "6d. MCP servers with OAuth (index.php?fnapp=oauthserver)"
OA="${TEST_URL}index.php?fnapp=oauthserver&op="
REDIR=https://claude.ai/api/mcp/auth_callback
OAJAR=$WORK/oauth.txt
json_field() { grep -o "\"$1\":\"[^\"]*\"" "$2" | head -1 | cut -d'"' -f4; }
mcp_whoami() { # mcp_whoami url token -> HTTP status, answer in $WORK/mcp.json
    fetch "$WORK/mcp.json" -H "Authorization: Bearer $2" -H 'Content-Type: application/json' -d '{"jsonrpc":"2.0","id":1,"method":"tools/call","params":{"name":"whoami","arguments":{}}}' "$1"
}
token() { fetch "$WORK/token.json" "$@" "${OA}token"; }
s=$(fetch "$WORK/o1" "${OA}metadata"); [ "$s" = 200 ] && grep -q '"code_challenge_methods_supported":\["S256"\]' "$WORK/o1" && ok "authorization server metadata" || fail "authorization server metadata (HTTP $s)"
for u in "${TEST_URL}openkis_mcp.php" "${TEST_URL}index.php?fnapp=mcp"; do
    curl -s -D "$WORK/o2" -o /dev/null -X POST "$u"
    grep -qi '^WWW-Authenticate: Bearer resource_metadata="[^"]*op=prm' "$WORK/o2" && ok "401 with resource_metadata: $u" || fail "no resource_metadata in the 401 of $u"
done
fetch "$WORK/o3" -H 'Content-Type: application/json' -d "{\"client_name\":\"smoke test\",\"redirect_uris\":[\"$REDIR\"]}" "${OA}register" >/dev/null
CID=$(json_field client_id "$WORK/o3"); [ -n "$CID" ] && ok "dynamic client registration" || fail "dynamic client registration"
VER=$(head -c 48 /dev/urandom | base64 | tr '+/' '-_' | tr -d '=\n'); CH=$(printf %s "$VER" | openssl dgst -sha256 -binary | base64 | tr '+/' '-_' | tr -d '=\n')
AUTH="${OA}authorize&response_type=code&client_id=$CID&redirect_uri=$(printf %s "$REDIR" | sed 's/:/%3A/g;s#/#%2F#g')&code_challenge=$CH&code_challenge_method=S256&state=st1"
rm -f "$OAJAR"
fetch "$WORK/o4" -b "$OAJAR" -c "$OAJAR" "$AUTH" >/dev/null
action=$(grep -o '<form[^>]*name="login"[^>]*>' "$WORK/o4" | grep -o 'action="[^"]*"' | sed 's/action="//;s/"$//;s/&amp;/\&/g')
[ -n "$action" ] && ok "authorize: login form" || fail "authorize: no login form"
fetch "$WORK/o5" -b "$OAJAR" -c "$OAJAR" --data-urlencode "username=$ADMIN_USER" --data-urlencode "password=$ADMIN_PASS" "${TEST_URL}$action" >/dev/null
csrf=$(grep -o 'name="csrf" value="[0-9a-f]*"' "$WORK/o5" | sed 's/.*value="//;s/"$//')
[ -n "$csrf" ] && ok "authorize: consent page after the login" || fail "authorize: no consent page after the login"
loc=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$OAJAR" -d consent=allow -d csrf=wrong "$AUTH"); [ -z "$loc" ] && ok "authorize: wrong CSRF token refused" || fail "authorize: wrong CSRF token accepted"
loc=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$OAJAR" -d consent=allow -d "csrf=$csrf" "$AUTH")
code=$(printf %s "$loc" | grep -o 'code=[^&]*' | cut -d= -f2-)
[ -n "$code" ] && printf %s "$loc" | grep -q 'state=st1' && ok "authorize: redirect with code and state" || fail "authorize: no code ($loc)"
s=$(token --data-urlencode grant_type=authorization_code --data-urlencode "code=$code" --data-urlencode "client_id=$CID" --data-urlencode "redirect_uri=$REDIR" --data-urlencode "code_verifier=x$VER")
[ "$s" = 400 ] && ok "token: wrong PKCE verifier refused" || fail "token: wrong PKCE verifier, HTTP $s"
token --data-urlencode grant_type=authorization_code --data-urlencode "code=$code" --data-urlencode "client_id=$CID" --data-urlencode "redirect_uri=$REDIR" --data-urlencode "code_verifier=$VER" >/dev/null
AT=$(json_field access_token "$WORK/token.json"); RT=$(json_field refresh_token "$WORK/token.json")
[ -n "$AT" ] && [ -n "$RT" ] && ok "token: access and refresh token" || fail "token: $(cat "$WORK/token.json")"
s=$(token --data-urlencode grant_type=authorization_code --data-urlencode "code=$code" --data-urlencode "client_id=$CID" --data-urlencode "redirect_uri=$REDIR" --data-urlencode "code_verifier=$VER")
[ "$s" = 400 ] && ok "token: code used twice refused" || fail "token: code used twice, HTTP $s"
s=$(mcp_whoami "${TEST_URL}openkis_mcp.php" "$AT"); [ "$s" = 200 ] && grep -q "$ADMIN_USER,.*,admin," "$WORK/mcp.json" && ok "openkis_mcp.php whoami with the access token" || fail "openkis_mcp.php with the access token: HTTP $s"
s=$(mcp_whoami "${TEST_URL}index.php?fnapp=mcp" "$AT"); [ "$s" = 200 ] && grep -q "\"structuredContent\":{\"username\":\"$ADMIN_USER\"" "$WORK/mcp.json" && ok "finis mcp whoami with the access token" || fail "finis mcp with the access token: HTTP $s"
s=$(mcp_whoami "${TEST_URL}openkis_mcp.php" "$RT"); [ "$s" = 401 ] && ok "refresh token refused as access token" || fail "refresh token used as access token: HTTP $s"
token --data-urlencode grant_type=refresh_token --data-urlencode "refresh_token=$RT" --data-urlencode "client_id=$CID" >/dev/null
AT2=$(json_field access_token "$WORK/token.json"); [ -n "$AT2" ] && ok "token: refresh" || fail "token: refresh"
s=$(token --data-urlencode grant_type=refresh_token --data-urlencode "refresh_token=$RT" --data-urlencode "client_id=$CID"); [ "$s" = 400 ] && ok "token: refresh token used twice refused" || fail "token: refresh token used twice, HTTP $s"
key=$(mysql_q "$TEST_DB" -e "SELECT apikey FROM fn_users WHERE username='$ADMIN_USER'")
mysql_q "$TEST_DB" -e "UPDATE fn_users SET apikey='$(openssl rand -hex 24)' WHERE username='$ADMIN_USER'"
s=$(mcp_whoami "${TEST_URL}openkis_mcp.php" "$AT2"); [ "$s" = 401 ] && ok "a new API key revokes the OAuth tokens" || fail "access token still valid after a new API key: HTTP $s"
mysql_q "$TEST_DB" -e "UPDATE fn_users SET apikey='$key' WHERE username='$ADMIN_USER'"

say "6e. Karstlink export and links to Grottocenter (openkis_karstlink.php, section grottocenter)"
endpoint "openkis_karstlink.php (caves, CSV)" "${TEST_URL}openkis_karstlink.php?mod=caves" "$DEMO_CODE"
endpoint "openkis_karstlink.php (caves, JSON-LD)" "${TEST_URL}openkis_karstlink.php?mod=caves&format=jsonld" "karstlink:UndergroundCavity"
s=$(fetch "$WORK/k1" "${TEST_URL}openkis_karstlink.php?mod=caves&nocache=1")
[ "$s" = 200 ] && head -1 "$WORK/k1" | grep -q '^id,rdf:type,dct:rights/cc:attributionName,dct:rights/karstlink:licenseType,' && grep -aq "karstlink:UndergroundCavity" "$WORK/k1" && ok "Karstlink CSV: columns of the Grottocenter importer" || fail "Karstlink CSV header (HTTP $s)"
! grep -aq "Grotta dimostrativa per testare" "$WORK/k1" && ok "Karstlink CSV: description not exported by default" || fail "Karstlink CSV: description exported with export_texts = 0"
s=$(fetch "$WORK/k2" "${TEST_URL}openkis_karstlink.php?mod=caves&format=jsonld&nocache=1")
[ "$s" = 200 ] && grep -aq '"@context"' "$WORK/k2" && grep -aEq '"dwc:coordinatePrecision": ?3[,}]' "$WORK/k2" && ok "Karstlink JSON-LD: context, precision read from location_evaluation" || fail "Karstlink JSON-LD (HTTP $s)"
s=$(fetch "$WORK/k3" -b "$COOKIES" "${TEST_URL}openkis_karstlink.php?mod=springs"); [ "$s" = 404 ] && ok "openkis_karstlink.php refuses a section that is not a cave register" || fail "openkis_karstlink.php mod=springs: HTTP $s, expected 404"
page "grottocenter section (admin): export links and search form" "$COOKIES" "${TEST_URL}index.php?mod=grottocenter" "okgc_action"
s=$(fetch "$WORK/k4" -b "$ANON" "${TEST_URL}index.php?mod=grottocenter"); [ "$s" = 200 ] && ! grep -aq "okgc_action" "$WORK/k4" && ok "grottocenter section: no actions for anonymous visitors" || fail "grottocenter section, anonymous: HTTP $s or actions shown"
# a confirmed link, written directly (the search needs the Grottocenter API):
# card on the cave sheet and owl:sameAs in the export
DEMO_ID=$(mysql_q "$TEST_DB" -e "SELECT id FROM ctl_caves WHERE code='$DEMO_CODE'")
mysql_q "$TEST_DB" -e "INSERT INTO ctl_grottocenter (id,section,record_id,status,gc_entrance,gc_cave,gc_name) VALUES ('999999','caves','$DEMO_ID','confirmed','999999','999999','Smoke test entrance')"
page "cave sheet: Grottocenter card of a confirmed link" "$ANON" "${TEST_URL}index.php?mod=caves&op=view&id=$DEMO_ID" "grottocenter.org/ui/entrances/999999"
s=$(fetch "$WORK/k5" "${TEST_URL}openkis_karstlink.php?mod=caves&nocache=1"); [ "$s" = 200 ] && grep -aq "grottocenter.org/ui/entrances/999999" "$WORK/k5" && ok "Karstlink CSV: owl:sameAs of the confirmed link" || fail "Karstlink CSV: no owl:sameAs for the confirmed link (HTTP $s)"
mysql_q "$TEST_DB" -e "DELETE FROM ctl_grottocenter WHERE id='999999'"


say "6f. temperatures (section temperatures, openkis_temperatures.php)"
page "temperatures: list with the map and the UCC export links (admin)" "$COOKIES" "${TEST_URL}index.php?mod=temperatures" "openkis_temperatures.php?format=ucc"
page "temperatures: results as a list by default (grid/list switch)" "$ANON" "${TEST_URL}index.php?mod=temperatures" "openkis_view_temperatures"
page "tracings: results as a list by default (grid/list switch)" "$ANON" "${TEST_URL}index.php?mod=tracings" "openkis_view_tracings"
page "temperatures: new record form (admin)" "$COOKIES" "${TEST_URL}index.php?mod=temperatures&op=new" "name=\"temperature\""
# the id is computed by the framework (varchar, max + 1), so direct inserts give it
TID=$(mysql_q "$TEST_DB" -e "SELECT COALESCE(MAX(CAST(id AS UNSIGNED)),0)+1 FROM ctl_temperatures")
mysql_q "$TEST_DB" -e "INSERT INTO ctl_temperatures (id,name,type,medium,cave,position,temperature,year,month,day,instrument,bibliography,username) VALUES ($TID,'','spot','air','$DEMO_CODE','ingresso','7.2',1962,0,0,'termometro a mercurio','Smoke test, 1962','$ADMIN_USER')"
mysql_q "$TEST_DB" -e "INSERT INTO ctl_temperatures (id,name,type,medium,cave,position,t_mean,t_min,t_max,start_date,end_date,acq_interval,username) VALUES ($TID+1,'','monitoring','air','$DEMO_CODE','Sala Grande','8.5','8.1','8.9','2024-01-01','2024-12-31',30,'$ADMIN_USER')"
# direct SQL does not touch the table stamp (misc/fndatabase/.../ctl_temperatures/updated) the computed pass looks at
tstamp=$(find "$TEST_DIR/misc/fndatabase" -path "*/ctl_temperatures/updated" 2>/dev/null | head -1); [ -n "$tstamp" ] && sudo -n -u daemon touch "$tstamp" 2>/dev/null || sudo -n -u daemon touch "$TEST_DIR/misc/fndatabase/ctl_temperatures/updated" 2>/dev/null || touch "$TEST_DIR/misc/fndatabase/ctl_temperatures/updated"
page "temperatures: list after the inserts, computed name and date" "$ANON" "${TEST_URL}index.php?mod=temperatures" "$DEMO_CODE - ingresso - 1962"
page "cave sheet: temperatures card" "$ANON" "${TEST_URL}index.php?mod=caves&op=view&id=$DEMO_ID" "temperature_cave=$DEMO_CODE"
s=$(fetch "$WORK/t1" "${TEST_URL}openkis_temperatures.php"); [ "$s" = 200 ] && grep -aq '"type":"Point"' "$WORK/t1" && grep -aEq '"id":"cave:'"$DEMO_CODE"'".*"value":[0-9]+(\.[0-9]+)?' "$WORK/t1" && ok "openkis_temperatures.php: one point per cave, mean of the readings" || fail "openkis_temperatures.php GeoJSON (HTTP $s): $(head -c 300 "$WORK/t1")"
s=$(fetch "$WORK/t2" "${TEST_URL}openkis_temperatures.php?cave=NOSUCHCODE"); [ "$s" = 200 ] && grep -aq '"features":\[\]' "$WORK/t2" && ok "openkis_temperatures.php: unknown cave gives no feature" || fail "openkis_temperatures.php?cave=NOSUCHCODE (HTTP $s)"
s=$(fetch "$WORK/t3" "${TEST_URL}openkis_temperatures.php?format=ucc&type=spot"); [ "$s" = 200 ] && head -1 "$WORK/t3" | grep -q 'nome grotta";"num. Catasto";' && grep -aq ";ingresso;" "$WORK/t3" && ! grep -aq "Sala Grande" "$WORK/t3" && ok "UCC export: spot sheet with the spot records only" || fail "UCC spot export (HTTP $s)"
s=$(fetch "$WORK/t4" "${TEST_URL}openkis_temperatures.php?format=ucc&type=monitoring"); [ "$s" = 200 ] && head -1 "$WORK/t4" | grep -q 'inizio misurazione";"fine misurazione";' && grep -aq "Sala Grande" "$WORK/t4" && ! grep -aq ";ingresso;" "$WORK/t4" && ok "UCC export: monitoring sheet with the monitorings only" || fail "UCC monitoring export (HTTP $s)"

say "6g. tracings with free detection coordinates (openkis_tracings.php)"
RID=$(mysql_q "$TEST_DB" -e "SELECT COALESCE(MAX(CAST(id AS UNSIGNED)),0)+1 FROM ctl_tracings")
mysql_q "$TEST_DB" -e "INSERT INTO ctl_tracings (id,name,type,cave_injection,detection_latitude_txt,detection_longitude_txt,detection_coordinates_type,detection_elevation,result,username) VALUES ($RID,'','water','$DEMO_CODE','44.43','8.99','WGS84','310','positive','$ADMIN_USER')"
rstamp=$(find "$TEST_DIR/misc/fndatabase" -path "*/ctl_tracings/updated" 2>/dev/null | head -1); [ -n "$rstamp" ] && sudo -n -u daemon touch "$rstamp" 2>/dev/null || sudo -n -u daemon touch "$TEST_DIR/misc/fndatabase/ctl_tracings/updated" 2>/dev/null || touch "$TEST_DIR/misc/fndatabase/ctl_tracings/updated"
page "tracings: list after the insert, name with the detection coordinates" "$ANON" "${TEST_URL}index.php?mod=tracings" "$DEMO_CODE -> 44.43,8.99"
s=$(fetch "$WORK/r5" "${TEST_URL}openkis_tracings.php?id=$RID")
if [ "$s" = 200 ] && php -r '$j=json_decode(file_get_contents($argv[1]),true); $f=$j["features"][0]["geometry"]["coordinates"] ?? null; exit(($f && abs($f[1][0]-8.99)<0.00001 && abs($f[1][1]-44.43)<0.00001) ? 0 : 1);' "$WORK/r5"; then ok "openkis_tracings.php: the arrow ends at the free detection coordinates"; else fail "openkis_tracings.php?id=$RID (HTTP $s): $(head -c 300 "$WORK/r5")"; fi
page "tracings: sheet with the free detection point" "$ANON" "${TEST_URL}index.php?mod=tracings&op=view&id=$RID" "44.43, 8.99"

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
