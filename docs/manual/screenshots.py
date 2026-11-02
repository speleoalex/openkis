#!/usr/bin/env python3
"""Screenshots of the manual (docs/MANUAL.md, docs/MANUAL_IT.md).

They were taken on a local copy of the Liguria cave register running the current
code: a site with the Liguria data (`$_FN['openkis_custom'] = "liguria"`, theme
openkis_liguria, languages it,en), an administrator and an enabled non-admin user
(groups r_caves, w_caves, mcp_users), the Grottocenter search already run and the
links of LI202 and LI32 confirmed. Cave LI202 (id 204) is the example.

usage:
  BASE=http://localhost/openkis_copy/ MANUAL_ADMIN=user:password MANUAL_EDITOR=user:password \
  python3 screenshots.py shots.json outdir it|en [name,name...]
then compress with: pngquant --force --skip-if-larger --quality=70-92 --ext .png outdir/*.png

shots.json: list of {"name", "url", "login": true (admin) | "editor", "full": bool, "w", "h",
                     "wait": ms, "clip": selector, "clip_union": [selectors], "hide": [selectors],
                     "actions": [{"click"|"fill"|"type"|"press"|"hover"|"select"|"js"|"scroll"|"goto",
                                  "frame", "pos", "value", "wait"}],
                     "crop": [x, y, w, h], "nolang": bool}
The site URL is replaced by https://catasto.example.org/ and API keys are masked.
"""
import json, sys, os, re
from playwright.sync_api import sync_playwright

BASE = os.environ["BASE"]
USERS = {True: os.environ.get("MANUAL_ADMIN", ":").split(":", 1),
         "editor": os.environ.get("MANUAL_EDITOR", ":").split(":", 1)}
HIDE = ["div:has(> #fn_adminonoff)"]
# local URL -> example domain, personal API keys masked
SCRUB = r"""() => {
  const re = new RegExp(BASE_RE, 'g'), to = 'https://catasto.example.org/';
  const key = /\b[0-9a-f]{40,}\b/g;
  const fix = t => t.replace(re, to).replace(key, m => m.slice(0, 6) + '\u2022'.repeat(18));
  const w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  let n; while ((n = w.nextNode())) { const v = fix(n.nodeValue); if (v !== n.nodeValue) n.nodeValue = v; }
  document.querySelectorAll('input,textarea').forEach(i => { if (i.type !== 'file') { const v = fix(i.value); if (v !== i.value) i.value = v; } });
}"""


def target(page, a):
    return page.frame_locator(a["frame"]) if a.get("frame") else page


def run_action(page, a):
    t = target(page, a)
    if "click" in a:
        kw = {"position": {"x": a["pos"][0], "y": a["pos"][1]}} if a.get("pos") else {}
        t.locator(a["click"]).first.click(timeout=a.get("timeout", 15000), **kw)
    if "fill" in a:
        t.locator(a["fill"]).first.fill(a["value"])
    if "type" in a:
        t.locator(a["type"]).first.press_sequentially(a["value"], delay=80)
    if "press" in a:
        t.locator(a["press"]).first.press(a["key"])
    if "hover" in a:
        t.locator(a["hover"]).first.hover()
    if "select" in a:
        t.locator(a["select"]).first.select_option(a["value"])
    if "js" in a:
        page.evaluate(a["js"].replace("$LANG", LANG))
    if "scroll" in a:
        page.locator(a["scroll"]).first.scroll_into_view_if_needed()
    if "goto" in a:
        page.goto(BASE + a["goto"], wait_until="networkidle")
    page.wait_for_timeout(a.get("wait", 1200))


def main():
    shots = json.load(open(sys.argv[1]))
    outdir = sys.argv[2]
    global LANG
    lang = LANG = sys.argv[3] if len(sys.argv) > 3 else "it"
    only = sys.argv[4].split(",") if len(sys.argv) > 4 else None
    os.makedirs(outdir, exist_ok=True)
    with sync_playwright() as p:
        b = p.chromium.launch()
        ctx = {}
        for s in shots:
            if only and s["name"] not in only:
                continue
            key = (s.get("login") or False, s.get("w", 1366), s.get("h", 800))
            if key not in ctx:
                c = b.new_context(viewport={"width": key[1], "height": key[2]},
                                  locale="it-IT" if lang == "it" else "en-GB",
                                  device_scale_factor=1)
                pg = c.new_page()
                pg.goto(BASE + "index.php?lang=" + lang)
                if key[0]:
                    pg.request.post(BASE + "index.php?mod=login&fnlogin=login",
                                    form={"username": USERS[key[0]][0], "password": USERS[key[0]][1]})
                ctx[key] = pg
            page = ctx[key]
            url = s["url"]
            if not url.startswith("http"):
                url = BASE + url
            if not s.get("nolang"):
                url += ("&" if "?" in url else "?") + "lang=" + lang
            try:
                page.goto(url, wait_until="networkidle", timeout=60000)
            except Exception as e:
                print("timeout", s["name"], e)
            page.wait_for_timeout(s.get("wait", 800))
            for sel in s.get("hide", []) + HIDE:
                page.add_style_tag(content=sel + "{display:none !important}")
            try:
                page.evaluate(SCRUB.replace("BASE_RE", json.dumps(re.escape(BASE.rstrip("/")) + "/?")))
            except Exception as e:
                print("scrub failed", s["name"], str(e)[:200])
            try:
                for a in s.get("actions", []):
                    run_action(page, a)
            except Exception as e:
                print("action failed", s["name"], str(e)[:300])
            out = os.path.join(outdir, s["name"] + ".png")
            if s.get("clip_union"):
                boxes = []
                for sel in s["clip_union"]:
                    bb = page.locator(sel).first.bounding_box()
                    sy = page.evaluate("window.scrollY")
                    boxes.append((bb["x"], bb["y"] + sy, bb["x"] + bb["width"], bb["y"] + sy + bb["height"]))
                pad = s.get("pad", 8)
                x0 = max(0, min(b_[0] for b_ in boxes) - pad); y0 = max(0, min(b_[1] for b_ in boxes) - pad)
                x1 = max(b_[2] for b_ in boxes) + pad; y1 = max(b_[3] for b_ in boxes) + pad
                page.screenshot(path=out, full_page=True, clip={"x": x0, "y": y0, "width": x1 - x0, "height": y1 - y0})
            elif s.get("clip"):
                page.locator(s["clip"]).first.screenshot(path=out)
            else:
                page.screenshot(path=out, full_page=s.get("full", False))
            if s.get("crop"):
                from PIL import Image
                x, y, w, h = s["crop"]
                im = Image.open(out)
                im.crop((x, y, min(im.size[0], x + w), min(im.size[1], y + h))).save(out)
            print("ok", out)
        b.close()


main()
