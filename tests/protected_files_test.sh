#!/bin/bash
# Functional test of the protected files and of the records of the restricted caves on the
# local test install (see tests/smoke_test.sh for the install itself). Scenario: a public
# cave and a cave restricted to the group hidden_caves, each with an entrance photo and a
# survey with a file. The files answer only through openkis_file.php and only to who may
# view the record (the anonymous visitor gets the public cave's files, a member of
# hidden_caves and the admin also the restricted ones); the direct path in misc/fndatabase
# is denied; the survey of the restricted cave is left out of the survey list and its page
# is refused; the maintenance scripts refuse the visitors.
# Usage: tests/protected_files_test.sh   (env: TEST_DIR TEST_URL TEST_DB ADMIN_USER ADMIN_PASS)
set -u
TEST_DIR=${TEST_DIR:-$HOME/public_html/openkis_test}
TEST_URL=${TEST_URL:-http://localhost/speleoalex/openkis_test/}
TEST_DB=${TEST_DB:-openkis_test}
ADMIN_USER=${ADMIN_USER:-admin}
ADMIN_PASS=${ADMIN_PASS:-Admin12345!}
MYSQL=${MYSQL:-/opt/lampp/bin/mysql}
PHP=${PHP:-/opt/lampp/bin/php}
WEB_USER=${WEB_USER:-daemon}
READER=hiddenreader
READER_PASS='Reader12345!'
WORK=$(mktemp -d); chmod 755 "$WORK"
trap 'rm -rf "$WORK"; rm -f "$TEST_DIR/_test_files.php"' EXIT
PASS=0; FAIL=0
ok()   { echo "  [ok]   $1"; PASS=$((PASS+1)); }
fail() { echo "  [FAIL] $1"; FAIL=$((FAIL+1)); }
say()  { echo; echo "== $1"; }
q()    { $MYSQL -u root -N -s "$TEST_DB" -e "$1"; }
fetch() { # out jar args...
    local out=$1 jar=$2; shift 2
    curl -s -o "$out" -w "%{http_code}" -b "$jar" -c "$jar" "$@"
}
login() { # jar user pass
    rm -f "$1"
    fetch /dev/null "$1" -L "${TEST_URL}index.php" >/dev/null
    fetch "$WORK/login.html" "$1" -L --data-urlencode "username=$2" --data-urlencode "password=$3" "${TEST_URL}index.php?fnlogin=login" >/dev/null
    fetch "$WORK/home.html" "$1" "${TEST_URL}index.php?mod=caves" >/dev/null
    grep -q 'fnlogin=logout' "$WORK/home.html"
}
# runs a PHP snippet in the site root as the web user (finis finds the site from the script path)
site_php() { # file
    cp "$1" "$TEST_DIR/_test_files.php"
    (cd "$TEST_DIR" && sudo -n -u "$WEB_USER" "$PHP" _test_files.php 2>&1)
    rm -f "$TEST_DIR/_test_files.php"
}

say "setup"
# the survey archive public, as in Liguria (the default install shows it to the registered users)
ORIG_LEVEL=$(q "SELECT level FROM fn_sections WHERE id='survey'")
q "UPDATE fn_sections SET level='' WHERE id='survey'"
cat > "$WORK/setup.php" <<PHP
<?php
chdir("$TEST_DIR");
require_once "loadfinis.php";
if (FN_GetUser("$READER") == null) {
    \$u = array("username" => "$READER", "email" => "$READER@example.invalid", "name" => "Hidden", "surname" => "Reader", "level" => "1", "group" => "hidden_caves", "active" => "1");
    FN_AddUser(\$u, "$READER_PASS");
}
\$png = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==");
function put(\$table, \$id, \$field, \$name, \$content) {
    \$dir = "misc/fndatabase/\$table/\$id/\$field";
    @mkdir(\$dir, 0777, true);
    file_put_contents("\$dir/\$name", \$content);
}
\$caves = FN_XMDBTable("ctl_caves");
\$surveys = FN_XMDBTable("ctl_surveys");
\$out = array();
foreach (array("TFPUB" => "", "TFRES" => "hidden_caves") as \$code => \$groups) {
    foreach ((array) \$caves->GetRecords(array("code" => \$code)) as \$old)
        \$caves->DelRecord(\$old['id']);
    foreach ((array) \$surveys->GetRecords(array("codecave" => \$code)) as \$old)
        \$surveys->DelRecord(\$old['id']);
    \$c = \$caves->InsertRecord(array("code" => \$code, "name" => "Protected files \$code", "photo1" => "entrance.png", "groupview" => \$groups, "public_map" => "Y"));
    put("ctl_caves", \$c['id'], "photo1", "entrance.png", \$png);
    \$s = \$surveys->InsertRecord(array("codecave" => \$code, "name" => "Survey \$code", "file" => "survey_\$code.txt", "groupview" => ""));
    put("ctl_surveys", \$s['id'], "file", "survey_\$code.txt", "survey of \$code");
    // the links relative to the site (from the command line siteurl is not the web one)
    \$rel = function (\$url) { return preg_replace('#^.*?(openkis_file\\.php\\?|misc/)#', '\$1', (string) \$url); };
    \$out[] = \$code . "=" . \$c['id'] . "," . \$s['id'] . ","
        . \$rel(\$caves->getFileUrl(\$c, "photo1")) . "," . \$rel(\$surveys->getFileUrl(\$s, "file"));
}
echo implode("\n", \$out) . "\n";
PHP
site_php "$WORK/setup.php" > "$WORK/setup.txt"
cat "$WORK/setup.txt" | grep -v "^TF" | head -5
line_pub=$(grep '^TFPUB=' "$WORK/setup.txt"); line_res=$(grep '^TFRES=' "$WORK/setup.txt")
IFS=, read -r PUB_CAVE PUB_SURVEY PUB_PHOTO_URL PUB_FILE_URL <<< "${line_pub#TFPUB=}"
IFS=, read -r RES_CAVE RES_SURVEY RES_PHOTO_URL RES_FILE_URL <<< "${line_res#TFRES=}"
PUB_PHOTO_URL=$TEST_URL$PUB_PHOTO_URL; PUB_FILE_URL=$TEST_URL$PUB_FILE_URL; RES_PHOTO_URL=$TEST_URL$RES_PHOTO_URL; RES_FILE_URL=$TEST_URL$RES_FILE_URL
[ -n "$RES_SURVEY" ] && ok "records created (public cave $PUB_CAVE, restricted cave $RES_CAVE)" || { fail "setup: $(cat "$WORK/setup.txt" | head -3)"; echo "passed: $PASS  failed: $FAIL"; exit 1; }
case "$RES_FILE_URL" in *openkis_file.php?f=*) ok "file links go through openkis_file.php" ;; *) fail "file link is $RES_FILE_URL" ;; esac
case "$RES_FILE_URL" in *ctl_surveys*|*survey_TFRES*) fail "the link shows the storage path: $RES_FILE_URL" ;; *) ok "the link does not show table or file name" ;; esac
login "$WORK/admin.jar" "$ADMIN_USER" "$ADMIN_PASS" && ok "admin login" || fail "admin login"
login "$WORK/reader.jar" "$READER" "$READER_PASS" && ok "hidden_caves reader login" || fail "hidden_caves reader login"
: > "$WORK/anon.jar"

say "direct paths in the data folder"
fetch /dev/null "$WORK/anon.jar" "${TEST_URL}index.php" > /dev/null   # the first request writes the folder guards
[ -f "$TEST_DIR/misc/fndatabase/ctl_surveys/.htaccess" ] && ok "ctl_surveys folder has the deny .htaccess" || fail "no .htaccess in misc/fndatabase/ctl_surveys"
s=$(fetch /dev/null "$WORK/anon.jar" "${TEST_URL}misc/fndatabase/ctl_surveys/$RES_SURVEY/file/survey_TFRES.txt")
[ "$s" = 403 ] && ok "restricted survey file by path: 403" || fail "restricted survey file by path: $s"
s=$(fetch /dev/null "$WORK/admin.jar" "${TEST_URL}misc/fndatabase/ctl_caves/$PUB_CAVE/photo1/entrance.png")
[ "$s" = 403 ] && ok "public photo by path: 403 also for the admin" || fail "public photo by path: $s"

say "openkis_file.php"
check_file() { # label jar url expected
    local s; s=$(fetch "$WORK/f.out" "$2" "$3")
    [ "$s" = "$4" ] && ok "$1: $s" || fail "$1: $s (expected $4)"
}
check_file "anonymous, public cave photo" "$WORK/anon.jar" "$PUB_PHOTO_URL" 200
check_file "anonymous, public cave survey file" "$WORK/anon.jar" "$PUB_FILE_URL" 200
grep -q "survey of TFPUB" "$WORK/f.out" && ok "the file content is served" || fail "unexpected content: $(head -c 80 "$WORK/f.out")"
check_file "anonymous, restricted cave photo" "$WORK/anon.jar" "$RES_PHOTO_URL" 403
check_file "anonymous, survey of the restricted cave (survey without groups)" "$WORK/anon.jar" "$RES_FILE_URL" 403
check_file "hidden_caves reader, restricted cave photo" "$WORK/reader.jar" "$RES_PHOTO_URL" 200
check_file "hidden_caves reader, survey of the restricted cave" "$WORK/reader.jar" "$RES_FILE_URL" 200
check_file "admin, survey of the restricted cave" "$WORK/admin.jar" "$RES_FILE_URL" 200
tampered=$(echo "$RES_FILE_URL" | sed 's/f=\(.....\)./f=\1A/')
check_file "altered token" "$WORK/admin.jar" "$tampered" 404
s=$(curl -s -D - -o /dev/null -b "$WORK/admin.jar" "$RES_FILE_URL" | tr -d '\r' | grep -i '^x-content-type-options:\|^content-disposition:' | wc -l)
[ "$s" = 2 ] && ok "nosniff and content-disposition headers" || fail "headers: $s of 2"

say "thumbnails by record (list cards)"
check_file "anonymous, public cave thumbnail" "$WORK/anon.jar" "${TEST_URL}index.php?fnapp=thumb&format=png&h=50&w=50&t=ctl_caves&i=$PUB_CAVE&c=photo1" 200
cp "$WORK/f.out" "$WORK/thumb_record.png"
fetch "$WORK/thumb_icon.png" "$WORK/anon.jar" "${TEST_URL}index.php?fnapp=thumb&format=png&h=50&w=50&f=images/none.png" > /dev/null
! cmp -s "$WORK/thumb_record.png" "$WORK/thumb_icon.png" && ok "the thumbnail by record is the photo, not the generic icon" || fail "the thumbnail by record is the generic icon (record not found)"
check_file "anonymous, restricted cave thumbnail" "$WORK/anon.jar" "${TEST_URL}index.php?fnapp=thumb&format=png&h=50&w=50&t=ctl_caves&i=$RES_CAVE&c=photo1" 403
check_file "hidden_caves reader, restricted cave thumbnail" "$WORK/reader.jar" "${TEST_URL}index.php?fnapp=thumb&format=png&h=50&w=50&t=ctl_caves&i=$RES_CAVE&c=photo1" 200
check_file "thumbnail of a raw path in the data folder" "$WORK/anon.jar" "${TEST_URL}index.php?fnapp=thumb&format=png&h=50&w=50&f=misc/fndatabase/ctl_caves/$RES_CAVE/photo1/entrance.png" 403
check_file "thumbnail from another database folder" "$WORK/anon.jar" "${TEST_URL}index.php?fnapp=thumb&format=png&h=50&w=50&d=other&t=ctl_caves&i=$PUB_CAVE&c=photo1" 403

say "pages"
fetch "$WORK/clist.html" "$WORK/anon.jar" "${TEST_URL}index.php?mod=caves&q=TFPUB" > /dev/null
grep -q "fnapp=thumb[^\"']*t=ctl_caves[^\"']*i=$PUB_CAVE" "$WORK/clist.html" && ok "caves list: the card thumbnail is asked by record" || fail "caves list: no thumbnail by record"
! grep -q "misc/fndatabase/ctl_caves" "$WORK/clist.html" && ok "caves list: no data folder path" || fail "caves list: data folder path in the page"
fetch "$WORK/csheet.html" "$WORK/anon.jar" "${TEST_URL}index.php?mod=caves&op=view&id=$PUB_CAVE" > /dev/null
grep -q "openkis_file.php?f=" "$WORK/csheet.html" && ok "cave sheet: the photo through openkis_file.php" || fail "cave sheet: no openkis_file.php link"
! grep -q "misc/fndatabase/ctl_" "$WORK/csheet.html" && ok "cave sheet: no data folder path" || fail "cave sheet: data folder path in the page"

say "survey records of the restricted cave"
fetch "$WORK/list_anon.html" "$WORK/anon.jar" "${TEST_URL}index.php?mod=survey&q=Survey" > /dev/null
grep -q "Survey TFPUB" "$WORK/list_anon.html" && ok "anonymous: the public cave's survey is listed" || fail "anonymous: public survey missing from the list"
! grep -q "Survey TFRES" "$WORK/list_anon.html" && ok "anonymous: the restricted cave's survey is not listed" || fail "anonymous: restricted cave's survey listed"
fetch "$WORK/list_reader.html" "$WORK/reader.jar" "${TEST_URL}index.php?mod=survey&q=Survey" > /dev/null
grep -q "Survey TFRES" "$WORK/list_reader.html" && ok "hidden_caves reader: the restricted cave's survey is listed" || fail "hidden_caves reader: restricted cave's survey missing"
fetch "$WORK/view_anon.html" "$WORK/anon.jar" "${TEST_URL}index.php?mod=survey&op=view&id=$RES_SURVEY" > /dev/null
! grep -q "Survey TFRES" "$WORK/view_anon.html" && ok "anonymous: the restricted cave's survey page shows nothing of it" || fail "anonymous: restricted survey page shows the record"
fetch "$WORK/view_reader.html" "$WORK/reader.jar" "${TEST_URL}index.php?mod=survey&op=view&id=$RES_SURVEY" > /dev/null
grep -q "Survey TFRES" "$WORK/view_reader.html" && ok "hidden_caves reader: the restricted cave's survey page" || fail "hidden_caves reader: restricted survey page empty"
fetch "$WORK/cave_anon.html" "$WORK/anon.jar" "${TEST_URL}index.php?mod=caves&op=view&id=$RES_CAVE" > /dev/null
! grep -q "Protected files TFRES" "$WORK/cave_anon.html" && ok "anonymous: no title of the restricted cave in its page" || fail "anonymous: the restricted cave's title is in its page"

say "viewers and maintenance scripts"
fetch "$WORK/cv.html" "$WORK/anon.jar" "${TEST_URL}cave_viewer.php?f=%27%29%3Balert%281%29%3B%2F%2F" > /dev/null
! grep -q "loadCave('');alert(1)" "$WORK/cv.html" && grep -q 'loadCave("' "$WORK/cv.html" && ok "cave_viewer.php: the f parameter is escaped" || fail "cave_viewer.php: f printed as it is"
fetch "$WORK/cv2.html" "$WORK/anon.jar" "${TEST_URL}openkis_cave_viewer.php?f=%27%29%3Balert%281%29%3B%2F%2F" > /dev/null
! grep -q "loadCave('');alert(1)" "$WORK/cv2.html" && ok "openkis_cave_viewer.php: the f parameter is escaped" || fail "openkis_cave_viewer.php: f printed as it is"
check_file "clear_cache.php, anonymous" "$WORK/anon.jar" "${TEST_URL}clear_cache.php?run=1&path=./misc/_cache" 403
check_file "clear_cache.php, admin" "$WORK/admin.jar" "${TEST_URL}clear_cache.php" 200
check_file "openkis_update_sections.php, anonymous" "$WORK/anon.jar" "${TEST_URL}openkis_update_sections.php" 403
check_file "openkis_las.php, a file outside the records" "$WORK/anon.jar" "${TEST_URL}openkis_las.php?f=/etc/hosts%23.las" 404
check_file "openkis_las.php, a remote url" "$WORK/anon.jar" "${TEST_URL}openkis_las.php?f=http://example.com/x.las" 404

say "cleanup"
cat > "$WORK/cleanup.php" <<PHP
<?php
chdir("$TEST_DIR");
require_once "loadfinis.php";
foreach (array("ctl_caves" => "code", "ctl_surveys" => "codecave") as \$table => \$field) {
    \$t = FN_XMDBTable(\$table);
    foreach (array("TFPUB", "TFRES") as \$code)
        foreach ((array) \$t->GetRecords(array(\$field => \$code)) as \$r)
            \$t->DelRecord(\$r['id']);
}
echo "done\n";
PHP
site_php "$WORK/cleanup.php" | tail -1
q "UPDATE fn_sections SET level='$ORIG_LEVEL' WHERE id='survey'"
echo
echo "passed: $PASS  failed: $FAIL"
[ "$FAIL" = 0 ]
