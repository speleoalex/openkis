#!/bin/bash
# Functional test of the dbview per-record edit permissions on the local test install
# (see tests/smoke_test.sh for the install itself). Scenario: with
# enable_permissions_edit_each_records=1 a writer who is not in the record's edit
# groups must not open the edit form, save the record or change its enabled users;
# the same writer may edit a record without edit groups, the admin may edit everything,
# and with the flag off the old behaviour (anyone who can add may edit) is kept.
# Usage: tests/record_permissions_test.sh   (env: TEST_DIR TEST_URL TEST_DB ADMIN_USER ADMIN_PASS)
set -u
TEST_DIR=${TEST_DIR:-$HOME/public_html/openkis_test}
TEST_URL=${TEST_URL:-http://localhost/speleoalex/openkis_test/}
TEST_DB=${TEST_DB:-openkis_test}
ADMIN_USER=${ADMIN_USER:-admin}
ADMIN_PASS=${ADMIN_PASS:-Admin12345!}
MYSQL=${MYSQL:-/opt/lampp/bin/mysql}
PHP=${PHP:-/opt/lampp/bin/php}
WEB_USER=${WEB_USER:-daemon}
WRITER=writer2
WRITER_PASS='Writer12345!'
WORK=$(mktemp -d); chmod 755 "$WORK"   # readable by the web user for the CLI script
trap 'rm -rf "$WORK"' EXIT
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
refused() { grep -qi "non puoi\|you may not do that" "$1"; }
# POST the edit form of record $3 as $1 (jar) with name=$2; prints the response status
save_record() { # jar newname id
    local jar=$1 newname=$2 id=$3
    fetch "$WORK/form.html" "$jar" "${TEST_URL}index.php?mod=caves&op=edit&id=$id" >/dev/null
    python3 - "$WORK/form.html" "$newname" > "$WORK/post.txt" <<'PY'
# re-post every field of the edit form (the only other form on the page is the search box "q")
import sys, html
from html.parser import HTMLParser
from urllib.parse import urlencode
src = open(sys.argv[1], encoding="utf-8", errors="replace").read()
newname = sys.argv[2]
class P(HTMLParser):
    def __init__(self):
        super().__init__(); self.fields = []; self.sel = None; self.ta = None
    def handle_starttag(self, t, a):
        a = dict(a)
        if t == "input":
            ty = (a.get("type") or "text").lower(); n = a.get("name")
            if not n or n == "q": return
            if ty in ("checkbox", "radio"):
                if "checked" in a: self.fields.append((n, a.get("value", "on")))
            elif ty == "submit":
                if n == "xmetadbsave": self.fields.append((n, a.get("value", "1")))
            elif ty not in ("button", "file", "reset", "image"):
                self.fields.append((n, a.get("value") or ""))
        elif t == "select": self.sel = a.get("name"); self.selected = False; self.first = None
        elif t == "option" and self.sel:
            v = a.get("value", "")
            if self.first is None: self.first = v
            if "selected" in a: self.fields.append((self.sel, v)); self.selected = True
        elif t == "textarea": self.ta = a.get("name"); self.tabuf = ""
    def handle_endtag(self, t):
        if t == "select" and self.sel:
            if not self.selected and self.first is not None and not self.sel.endswith("[]"): self.fields.append((self.sel, self.first))
            self.sel = None
        elif t == "textarea" and self.ta:
            self.fields.append((self.ta, html.unescape(self.tabuf))); self.ta = None
    def handle_data(self, d):
        if self.ta is not None: self.tabuf += d
p = P(); p.feed(src)
fields = [(k, v) for k, v in p.fields if k != "name"] + [("name", newname)]
print(urlencode(fields), end="")
PY
    fetch "$WORK/save.html" "$jar" -X POST --data-binary "@$WORK/post.txt" "${TEST_URL}index.php?mod=caves&op=updaterecord&id=$id"
}

say "0. setup"
ID=$(q "SELECT id FROM ctl_caves ORDER BY id LIMIT 1")
[ -n "$ID" ] || { echo "no cave in $TEST_DB: run tests/smoke_test.sh -f first"; exit 1; }
ORIG_NAME=$(q "SELECT name FROM ctl_caves WHERE id='$ID'")
ORIG_FLAG=$(q "SELECT varvalue FROM fncf_caves WHERE varname='enable_permissions_edit_each_records'")
ORIG_GI=$(q "SELECT groupinsert FROM ctl_caves WHERE id='$ID'")
echo "record $ID '$ORIG_NAME' (groupinsert='$ORIG_GI'), flag=$ORIG_FLAG"
cat > "$WORK/adduser.php" <<PHP
<?php
chdir("$TEST_DIR");
require_once "loadfinis.php";
if (FN_GetUser("$WRITER") == null) {
    \$u = array("username" => "$WRITER", "email" => "$WRITER@example.invalid", "name" => "Writer", "surname" => "Two", "level" => "1", "group" => "w_caves", "active" => "1");
    echo FN_AddUser(\$u, "$WRITER_PASS") ? "created\n" : "ERROR creating user\n";
} else {
    echo "exists\n";
}
PHP
# finis resolves the application directory from the script location: run it from the site root
cp "$WORK/adduser.php" "$TEST_DIR/_test_adduser.php"
r=$(cd "$TEST_DIR" && sudo -n -u "$WEB_USER" "$PHP" _test_adduser.php 2>&1 | tail -1)
rm -f "$TEST_DIR/_test_adduser.php"
echo "user $WRITER: $r"
[ -n "$(q "SELECT username FROM fn_users WHERE username='$WRITER' AND \`group\`='w_caves'")" ] && ok "writer user exists, groups=w_caves only" || fail "writer user ($r)"
# admin may always edit: enabling the users page as admin also creates the fieldusers table
login "$WORK/admin.jar" "$ADMIN_USER" "$ADMIN_PASS" && ok "admin login" || fail "admin login"
login "$WORK/writer.jar" "$WRITER" "$WRITER_PASS" && ok "writer login" || fail "writer login"

say "1. flag on, record reserved to r_caves: writer must be refused"
q "UPDATE fncf_caves SET varvalue='1' WHERE varname='enable_permissions_edit_each_records'"
q "UPDATE ctl_caves SET groupinsert='r_caves' WHERE id='$ID'"
s=$(fetch "$WORK/e.html" "$WORK/writer.jar" "${TEST_URL}index.php?mod=caves&op=edit&id=$ID")
refused "$WORK/e.html" && ! grep -q 'name="xmetadbsave"' "$WORK/e.html" && ok "edit form refused (HTTP $s)" || fail "edit form shown to the writer (HTTP $s)"
printf 'xmetadbsave=Salva&_xmetadbform_pk_id=%s&id=%s&code=DEMO1&name=HACKED' "$ID" "$ID" > "$WORK/post.txt"
s=$(fetch "$WORK/save.html" "$WORK/writer.jar" -X POST --data-binary "@$WORK/post.txt" "${TEST_URL}index.php?mod=caves&op=updaterecord&id=$ID")
n=$(q "SELECT name FROM ctl_caves WHERE id='$ID'")
refused "$WORK/save.html" && [ "$n" = "$ORIG_NAME" ] && ok "save refused, record unchanged (HTTP $s)" || fail "save: refused=$(refused "$WORK/save.html" && echo y || echo n) name='$n' (HTTP $s)"
s=$(fetch "$WORK/u.html" "$WORK/writer.jar" -X POST -d "usertoadd=$WRITER" "${TEST_URL}index.php?mod=caves&op=users&id=$ID")
fu=$(q "SELECT COUNT(*) FROM fieldusers WHERE username='$WRITER'" 2>/dev/null || echo 0)
refused "$WORK/u.html" && [ "$fu" = 0 ] && ok "users page refused, no enabled-user row added (HTTP $s)" || fail "users page: refused=$(refused "$WORK/u.html" && echo y || echo n) rows=$fu (HTTP $s)"
s=$(fetch "$WORK/v.html" "$WORK/writer.jar" "${TEST_URL}index.php?mod=caves&op=view&id=$ID")
[ "$s" = 200 ] && grep -q "$ORIG_NAME" "$WORK/v.html" && ok "writer may still view the record" || fail "writer view (HTTP $s)"

say "2. admin may edit the reserved record"
s=$(fetch "$WORK/e.html" "$WORK/admin.jar" "${TEST_URL}index.php?mod=caves&op=edit&id=$ID")
grep -q 'name="xmetadbsave"' "$WORK/e.html" && ok "admin edit form (HTTP $s)" || fail "admin edit form (HTTP $s)"
s=$(fetch "$WORK/u.html" "$WORK/admin.jar" "${TEST_URL}index.php?mod=caves&op=users&id=$ID")
[ "$s" = 200 ] && ! refused "$WORK/u.html" && ok "admin users page (HTTP $s)" || fail "admin users page (HTTP $s)"

say "3. admin enables the writer on the record: writer may edit it"
s=$(fetch "$WORK/u.html" "$WORK/admin.jar" -X POST -d "usertoadd=$WRITER" "${TEST_URL}index.php?mod=caves&op=users&id=$ID")
fu=$(q "SELECT COUNT(*) FROM fieldusers WHERE username='$WRITER'" 2>/dev/null || echo 0)
[ "$fu" = 1 ] && ok "enabled-user row added by admin" || fail "enabled-user row (rows=$fu HTTP $s)"
s=$(fetch "$WORK/e.html" "$WORK/writer.jar" "${TEST_URL}index.php?mod=caves&op=edit&id=$ID")
grep -q 'name="xmetadbsave"' "$WORK/e.html" && ok "enabled writer gets the edit form" || fail "enabled writer edit form (HTTP $s)"
s=$(save_record "$WORK/writer.jar" "$ORIG_NAME enabled" "$ID")
n=$(q "SELECT name FROM ctl_caves WHERE id='$ID'")
[ "$n" = "$ORIG_NAME enabled" ] && ok "enabled writer saved the record (HTTP $s)" || fail "enabled writer save: name='$n' (HTTP $s)"
s=$(fetch "$WORK/u.html" "$WORK/admin.jar" "${TEST_URL}index.php?mod=caves&op=users&id=$ID&usertodel=$WRITER")
fu=$(q "SELECT COUNT(*) FROM fieldusers WHERE username='$WRITER'")
[ "$fu" = 0 ] && ok "admin removed the enabled user" || fail "remove enabled user (rows=$fu HTTP $s)"
s=$(fetch "$WORK/e.html" "$WORK/writer.jar" "${TEST_URL}index.php?mod=caves&op=edit&id=$ID")
refused "$WORK/e.html" && ok "writer refused again after removal" || fail "writer still allowed after removal (HTTP $s)"

say "4. flag on, record without edit groups: writer may edit"
q "UPDATE ctl_caves SET groupinsert='' WHERE id='$ID'"
s=$(fetch "$WORK/e.html" "$WORK/writer.jar" "${TEST_URL}index.php?mod=caves&op=edit&id=$ID")
grep -q 'name="xmetadbsave"' "$WORK/e.html" && ok "edit form shown (HTTP $s)" || fail "edit form (HTTP $s)"
s=$(save_record "$WORK/writer.jar" "$ORIG_NAME open" "$ID")
n=$(q "SELECT name FROM ctl_caves WHERE id='$ID'")
[ "$n" = "$ORIG_NAME open" ] && ok "record saved (HTTP $s)" || fail "save: name='$n' (HTTP $s)"

say "5. flag off, record reserved: old behaviour, writer who can add may edit"
q "UPDATE fncf_caves SET varvalue='0' WHERE varname='enable_permissions_edit_each_records'"
q "UPDATE ctl_caves SET groupinsert='r_caves' WHERE id='$ID'"
s=$(fetch "$WORK/e.html" "$WORK/writer.jar" "${TEST_URL}index.php?mod=caves&op=edit&id=$ID")
grep -q 'name="xmetadbsave"' "$WORK/e.html" && ok "edit form shown (HTTP $s)" || fail "edit form (HTTP $s)"
s=$(save_record "$WORK/writer.jar" "$ORIG_NAME" "$ID")
n=$(q "SELECT name FROM ctl_caves WHERE id='$ID'")
[ "$n" = "$ORIG_NAME" ] && ok "record saved, name restored (HTTP $s)" || fail "save: name='$n' (HTTP $s)"

say "6. anonymous"
s=$(fetch "$WORK/a.html" "$WORK/anon.jar" -X POST -d "usertoadd=$WRITER" "${TEST_URL}index.php?mod=caves&op=users&id=$ID")
fu=$(q "SELECT COUNT(*) FROM fieldusers WHERE username='$WRITER'")
[ "$fu" = 0 ] && ok "anonymous cannot add enabled users (HTTP $s)" || fail "anonymous added a row (HTTP $s)"

say "cleanup"
q "UPDATE fncf_caves SET varvalue='$ORIG_FLAG' WHERE varname='enable_permissions_edit_each_records'"
q "UPDATE ctl_caves SET groupinsert='$ORIG_GI', name='$ORIG_NAME' WHERE id='$ID'"
echo
echo "passed: $PASS  failed: $FAIL"
[ "$FAIL" = 0 ]
