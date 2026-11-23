#!/usr/bin/env python3
"""PDF of the short offline manual (docs/manuale_offline_it.html).

Print layout only: A4, backgrounds, page numbers. The screenshots come from
docs/manual/it/offline_*.png, taken with docs/manual/screenshots.py and
shots_offline.json on a local copy of the Liguria register.

usage: python3 build_manuale_offline.py [output.pdf]
"""
import os
import sys
from playwright.sync_api import sync_playwright

HERE = os.path.dirname(os.path.abspath(__file__))
DOCS = os.path.dirname(HERE)
SRC = os.path.join(DOCS, "manuale_offline_it.html")
OUT = sys.argv[1] if len(sys.argv) > 1 else os.path.join(DOCS, "manuale_offline_it.pdf")

FOOTER = ('<div style="font:8pt Arial;color:#777;width:100%;padding:0 16mm;'
          'display:flex;justify-content:space-between;">'
          '<span>Catasto Speleologico Ligure</span>'
          '<span><span class="pageNumber"></span> / <span class="totalPages"></span></span></div>')

with sync_playwright() as p:
    browser = p.chromium.launch()
    page = browser.new_page()
    page.goto("file://" + SRC, wait_until="load")
    page.emulate_media(media="print")
    page.pdf(path=OUT, format="A4", print_background=True,
             display_header_footer=True, header_template="<div></div>", footer_template=FOOTER,
             margin={"top": "14mm", "bottom": "18mm", "left": "16mm", "right": "16mm"})
    browser.close()
print(OUT)
