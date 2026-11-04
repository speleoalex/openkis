#!/usr/bin/env python3
"""Derives the custom.css of openkis_fslo and openkis_liguria from themes/openkis_agsp/custom.css:
same rules (layout, light and dark mode), other colour variables (light, dark, print) and a few
theme-specific lines. Change the rules in openkis_agsp, then run from the repository root:
    python3 docs/themes/derive_theme_css.py openkis_fslo
    python3 docs/themes/derive_theme_css.py openkis_liguria
and bump custom.css?v= in the template.tp.html of the three themes.
A block of the current output that starts with KEEP_MARKER (the record sheet rules of
openkis_fslo) is kept at the end of the new file."""
import os
import re
import sys

name = sys.argv[1]
src = "themes/openkis_agsp/custom.css"
out = "themes/%s/custom.css" % name
KEEP_MARKER = "/* ---------------------------------------------------------------------\n   Record sheets of caves"
s = open(src, encoding="utf-8").read()
kept = ""
if os.path.exists(out):
    cur = open(out, encoding="utf-8").read()
    if KEEP_MARKER in cur:
        kept = cur[cur.index(KEEP_MARKER):]

# light / dark palettes: every key must exist in the openkis_agsp blocks
P = {
    "openkis_fslo": {
        "comment": """/* =====================================================================
   openkis_fslo theme (Catasto Speleologico Lombardo, FSLo)
   Layout of openkis_grottocenter (fixed left drawer, top app bar, "paper"
   cards, footer with columns) in the green of the Federazione Speleologica
   Lombarda logo. Same rules as openkis_agsp (the light and dark mode
   included), other colour variables.
   Light and dark mode: every colour is a variable below; the dark values
   apply with <html data-theme="dark"> (set before the styles by the script
   at the top of template.tp.html: the choice made with the button of the
   app bar, otherwise the setting of the system) and, without JavaScript,
   with the dark setting of the system.
   ===================================================================== */
""",
        "light": {
            "--gc-brown": "#1b5e3b", "--gc-brown-dark": "#0f3d26", "--gc-brown-light": "#2e7d4f",
            "--gc-orange": "#00802f", "--gc-solid": "#1b5e3b", "--gc-orange-dark": "#006b27",
            "--gc-orange-light": "#a5d6a7", "--gc-beige": "#e8f5e9", "--gc-tint": "#e8f5e9",
            "--gc-tint-border": "rgba(27, 94, 59, .35)", "--gc-bg": "#f5f5f5",
            "--gc-header": "#1b5e3b", "--gc-dark": "#1b5e3b", "--gc-dark-deep": "#0f3d26",
            "--gc-footer": "#1b5e3b", "--gc-footer-deep": "#0f3d26", "--gc-secondary": "#1b5e3b",
            "--gc-secondary-dark": "#0f3d26", "--gc-table-head": "#1b5e3b", "--gc-code-badge": "#1b5e3b",
            "--gc-outline": "#1b5e3b", "--gc-outline-border": "rgba(27, 94, 59, .5)",
            "--gc-accent": "#00802f", "--gc-accent-dark": "#006b27", "--gc-accent-text": "#00802f",
            "--gc-accent-border": "rgba(0, 128, 47, .5)", "--gc-accent-tint": "rgba(0, 128, 47, .08)",
            "--gc-paper-2": "#fafafa", "--gc-hover-row": "#f1f8f2", "--gc-active-bg": "rgba(0, 128, 47, .12)",
            "--gc-focus": "rgba(0, 128, 47, .25)",
        },
        "dark": {
            "--gc-brown": "#8fd19e", "--gc-brown-dark": "#b9e5c3", "--gc-brown-light": "#4caf6a",
            "--gc-orange": "#8fd19e", "--gc-solid": "#2e7d4f", "--gc-orange-dark": "#8fd19e",
            "--gc-orange-light": "#a5d6a7", "--gc-beige": "#1d3326", "--gc-tint": "#1d3326",
            "--gc-tint-border": "rgba(143, 209, 158, .35)", "--gc-bg": "#0f1512",
            "--gc-header": "#1b5e3b", "--gc-dark": "#0b100d", "--gc-dark-deep": "#070a08",
            "--gc-footer": "#0b100d", "--gc-footer-deep": "#070a08", "--gc-secondary": "#2d6a47",
            "--gc-secondary-dark": "#357a53", "--gc-table-head": "#1f4a32", "--gc-code-badge": "#2d6a47",
            "--gc-outline": "#cfe3d5", "--gc-outline-border": "rgba(207, 227, 213, .4)",
            "--gc-accent": "#00802f", "--gc-accent-dark": "#0b963b", "--gc-accent-text": "#7ed895",
            "--gc-accent-border": "rgba(126, 216, 149, .55)", "--gc-accent-tint": "rgba(126, 216, 149, .12)",
            "--gc-paper": "#17201b", "--gc-paper-2": "#1c2620", "--gc-raised": "#202b24",
            "--gc-hover-row": "#202b24", "--gc-active-bg": "rgba(143, 209, 158, .16)", "--gc-media-bg": "#223029",
            "--gc-text": "#e4ede7", "--gc-text-2": "rgba(228, 237, 231, .8)", "--gc-muted": "rgba(228, 237, 231, .6)",
            "--gc-input-bg": "#0f1813", "--gc-input-readonly": "#1c2620", "--gc-focus": "rgba(143, 209, 158, .35)",
            "--gc-ico-filter": "brightness(1.6)",
        },
        "print": {"--gc-brown": "#1b5e3b", "--gc-brown-dark": "#0f3d26", "--gc-orange": "#00802f",
                  "--gc-orange-dark": "#006b27", "--gc-paper-2": "#fafafa", "--gc-tint": "#e8f5e9",
                  "--gc-table-head": "#1b5e3b"},
        "replace": [
            ("box-shadow: 0 0 0 .2rem rgba(194, 65, 12, .35);", "box-shadow: 0 0 0 .2rem rgba(0, 128, 47, .35);"),
            ("""    /* round badge with small lettering: larger than the FSLo logo */
    height: 150px;""", "    height: 100px;"),
            ("""    .gc-hero-logo {
        height: 100px;
    }""", """    .gc-hero-logo {
        height: 72px;
    }"""),
            ("   and cave codes: theme colours", "   and cave codes: theme greens"),
            ("    /* ten counters (Piemonte provinces and Valle d'Aosta) on one row */",
             "    /* twelve counters (Lombardia provinces) on one row */"),
        ],
    },
    "openkis_liguria": {
        "comment": """/* =====================================================================
   openkis_liguria theme (Catasto Speleologico Ligure)
   Layout of openkis_grottocenter (fixed left drawer, top app bar, "paper"
   cards, footer with columns) with the same rules as openkis_agsp (the
   light and dark mode included) in the colours of the Liguria register:
   the orange app bar of www.catastogrotte.net/liguria (#d67e36 with the
   yellow line below), a dark brown for the footer, the menu icons and the
   secondary buttons, a deeper orange for titles, links and main buttons.
   Light and dark mode: every colour is a variable below; the dark values
   apply with <html data-theme="dark"> (set before the styles by the script
   at the top of template.tp.html: the choice made with the button of the
   app bar, otherwise the setting of the system) and, without JavaScript,
   with the dark setting of the system.
   ===================================================================== */
""",
        "light": {
            "--gc-brown": "#8a4e17", "--gc-brown-dark": "#6b3a0f", "--gc-brown-light": "#e0a46a",
            "--gc-orange": "#a8521a", "--gc-solid": "#a8521a", "--gc-orange-dark": "#9a4a12",
            "--gc-orange-light": "#f6c08a", "--gc-beige": "#fdf3e7", "--gc-tint": "#fdf3e7",
            "--gc-tint-border": "rgba(214, 126, 54, .4)", "--gc-bg": "#f7f5f2",
            "--gc-header": "#d67e36", "--gc-dark": "#3d2a1c", "--gc-dark-deep": "#2a1c12",
            "--gc-footer": "#3d2a1c", "--gc-footer-deep": "#2a1c12", "--gc-secondary": "#3d2a1c",
            "--gc-secondary-dark": "#2a1c12", "--gc-table-head": "#3d2a1c", "--gc-code-badge": "#3d2a1c",
            "--gc-outline": "#3d2a1c", "--gc-outline-border": "rgba(61, 42, 28, .45)",
            "--gc-accent": "#c2410c", "--gc-accent-dark": "#9a3412", "--gc-accent-text": "#c2410c",
            "--gc-accent-border": "rgba(194, 65, 12, .5)", "--gc-accent-tint": "rgba(194, 65, 12, .08)",
            "--gc-paper-2": "#fbf8f5", "--gc-hover-row": "#fbf3ea", "--gc-active-bg": "rgba(214, 126, 54, .14)",
            "--gc-focus": "rgba(214, 126, 54, .3)",
        },
        "dark": {
            "--gc-brown": "#f2b27a", "--gc-brown-dark": "#f8d0aa", "--gc-brown-light": "#d67e36",
            "--gc-orange": "#f2b27a", "--gc-solid": "#a8521a", "--gc-orange-dark": "#f2b27a",
            "--gc-orange-light": "#f6c08a", "--gc-beige": "#3a2617", "--gc-tint": "#3a2617",
            "--gc-tint-border": "rgba(242, 178, 122, .35)", "--gc-bg": "#14110e",
            "--gc-header": "#b8662a", "--gc-dark": "#0d0a08", "--gc-dark-deep": "#080605",
            "--gc-footer": "#0d0a08", "--gc-footer-deep": "#080605", "--gc-secondary": "#5a3d27",
            "--gc-secondary-dark": "#6b4a30", "--gc-table-head": "#4a3220", "--gc-code-badge": "#5a3d27",
            "--gc-outline": "#e6d6c6", "--gc-outline-border": "rgba(230, 214, 198, .4)",
            "--gc-accent": "#c2410c", "--gc-accent-dark": "#d4500f", "--gc-accent-text": "#f0915a",
            "--gc-accent-border": "rgba(240, 145, 90, .55)", "--gc-accent-tint": "rgba(240, 145, 90, .12)",
            "--gc-paper": "#1e1915", "--gc-paper-2": "#241e19", "--gc-raised": "#29221c",
            "--gc-hover-row": "#29221c", "--gc-active-bg": "rgba(242, 178, 122, .16)", "--gc-media-bg": "#30281f",
            "--gc-text": "#efe7df", "--gc-text-2": "rgba(239, 231, 223, .8)", "--gc-muted": "rgba(239, 231, 223, .6)",
            "--gc-input-bg": "#17130f", "--gc-input-readonly": "#241e19", "--gc-focus": "rgba(242, 178, 122, .35)",
            "--gc-ico-filter": "none",
        },
        "print": {"--gc-brown": "#8a4e17", "--gc-brown-dark": "#6b3a0f", "--gc-orange": "#a8521a",
                  "--gc-orange-dark": "#9a4a12", "--gc-paper-2": "#fbf8f5", "--gc-tint": "#fdf3e7",
                  "--gc-table-head": "#3d2a1c"},
        "replace": [
            # the yellow line below the app bar of the Liguria site
            (""".gc-appbar {
    position: fixed;""", """.gc-appbar {
    border-bottom: 2px solid #fab941;
    position: fixed;"""),
            # the logo is a small drawing (64 px): not larger than 96 px
            ("""    /* round badge with small lettering: larger than the FSLo logo */
    height: 150px;""", "    height: 96px;"),
            ("""    .gc-hero-logo {
        height: 100px;
    }""", """    .gc-hero-logo {
        height: 72px;
    }"""),
            ("    /* ten counters (Piemonte provinces and Valle d'Aosta) on one row */",
             "    /* the four provinces and the total on one row */"),
            # white on the orange is 3:1: the search box darker than the bar, not lighter
            ("""    background: rgba(255, 255, 255, .15);
    border-radius: 4px;
    margin: 0 12px;""", """    background: rgba(0, 0, 0, .16);
    border-radius: 4px;
    margin: 0 12px;"""),
            (""".gc-search:focus-within {
    background: rgba(255, 255, 255, .25);""", """.gc-search:focus-within {
    background: rgba(0, 0, 0, .24);"""),
            (""".gc-search-input::placeholder {
    color: rgba(255, 255, 255, .7);""", """.gc-search-input::placeholder {
    color: rgba(255, 255, 255, .9);"""),
            # outlines of the app bar buttons: white, not half transparent
            ("""    border: 1px solid rgba(255, 255, 255, .5);
    border-radius: 4px;
    background: transparent;
    color: #f5f5f5;""", """    border: 1px solid rgba(255, 255, 255, .85);
    border-radius: 4px;
    background: transparent;
    color: #fff;"""),
        ],
    },
}[name]


def set_vars(block, values, strip_comments=True):
    for k, v in values.items():
        pat = re.compile(r"(^\s*" + re.escape(k) + r":\s*)([^;]+)(;)", re.M)
        block, n = pat.subn(lambda m: m.group(1) + v + m.group(3), block)
        if n != 1:
            sys.exit(f"{name}: {k} found {n} times in a block")
    if strip_comments:
        block = re.sub(r"^\s*/\*(?:(?!\*/).)*\*/\n", "", block, flags=re.M | re.S)
    return block


# header comment
i = s.index("/* =====")
j = s.index(":root {")
s = s[:i] + P["comment"] + "\n" + s[j:]

# light block: the first ":root {" ... "\n}\n"
i = s.index(":root {")
j = s.index("\n}\n", i) + 3
s = s[:i] + set_vars(s[i:j], P["light"]) + s[j:]

# dark block
i = s.index(':root[data-theme="dark"] {')
j = s.index("\n}\n", i) + 3
s = s[:i] + set_vars(s[i:j], P["dark"]) + s[j:]

# dark block for the system setting (inside @media)
i = s.index("@media (prefers-color-scheme: dark) {")
j = s.index("\n    }\n}\n", i) + 8
s = s[:i] + set_vars(s[i:j], P["dark"]) + s[j:]

# print block
i = s.index("    /* paper is white: dark text in both modes */")
j = s.index("\n    }\n", i) + 7
s = s[:i] + set_vars(s[i:j], P["print"], strip_comments=False) + s[j:]

for old, new in P["replace"]:
    n = s.count(old)
    if n != 1:
        sys.exit(f"{name}: {old[:60]!r} found {n} times")
    s = s.replace(old, new)

if kept:
    s = s.rstrip("\n") + "\n\n" + kept
open(out, "w", encoding="utf-8").write(s)
print("written", out)
