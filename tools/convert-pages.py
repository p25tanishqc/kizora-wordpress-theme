#!/usr/bin/env python3
"""Convert the prototype's static pages (Next.js `out/` folder) into WordPress page content.

Usage: python3 tools/convert-pages.py <path-to-prototype-out> kizora/inc/content

What it does, per page:
  * keeps only the <main> markup, so the design classes stay exactly as in the prototype
  * replaces each product card with [kz_card slug="..."] so price, photo and link come live from WooCommerce
  * replaces kit prices and the free-delivery amount with shortcodes, so they never go stale
  * leaves links and image paths root-relative; the theme maps them at render time
"""
import json, re, sys
from pathlib import Path
from bs4 import BeautifulSoup, Comment

OUT, DEST = Path(sys.argv[1]), Path(sys.argv[2])
DEST.mkdir(parents=True, exist_ok=True)
products = json.load(open(DEST.parent / 'data' / 'products.json'))
kit_price = {f"₹{p['price']:,}".replace(',', ','): p['slug'] for p in products if p['kind'] == 'kit'}

PAGES = {
    'home': 'index.html', 'about': 'about/index.html', 'how-its-made': 'how-its-made/index.html',
    'faq': 'faq/index.html', 'contact': 'contact/index.html', 'shipping': 'shipping/index.html',
    'returns': 'returns/index.html', 'rituals': 'rituals/index.html',
}
for d in sorted((OUT / 'ingredients').iterdir()):
    if d.is_dir(): PAGES[f'ingredient-{d.name}'] = f'ingredients/{d.name}/index.html'
for d in sorted((OUT / 'journal').iterdir()):
    if d.is_dir(): PAGES[f'journal-{d.name}'] = f'journal/{d.name}/index.html'


def indian(n: int) -> str:
    s = str(n)
    if len(s) <= 3: return s
    head, tail = s[:-3], s[-3:]
    parts = []
    while len(head) > 2: parts.insert(0, head[-2:]); head = head[:-2]
    if head: parts.insert(0, head)
    return ','.join(parts) + ',' + tail


KIT_PRICES = {f"₹{indian(p['price'])}": p['slug'] for p in products if p['kind'] == 'kit'}

report = {}
manifest = {}
for key, rel in PAGES.items():
    soup = BeautifulSoup((OUT / rel).read_text(), 'html.parser')
    t = soup.find('title'); d = soup.find('meta', attrs={'name': 'description'}); h1 = soup.find('main').find('h1')
    manifest[key] = {'title': t.get_text(strip=True) if t else '', 'description': d['content'] if d else '', 'h1': h1.get_text(' ', strip=True) if h1 else ''}
    main = soup.find('main')
    for c in main.find_all(string=lambda t: isinstance(t, Comment)): c.extract()
    cards = 0
    for a in main.select('a.group.block'):
        m = re.match(r'^/products/([^/]+)/$', a.get('href', ''))
        if not m: continue
        a.replace_with(soup.new_string(f'[kz_card slug="{m.group(1)}"]'))
        cards += 1
    html = ''.join(str(x) for x in main.contents)
    # live prices for kits and the free-delivery threshold
    swaps = 0
    for txt, slug in KIT_PRICES.items():
        n = html.count(txt); swaps += n
        html = html.replace(txt, f'[kz_price slug="{slug}"]')
    n = html.count('₹799'); swaps += n
    html = html.replace('₹799', '[kz_free_delivery]')
    html = re.sub(r'\s+data-(?:nimg|precedence)="[^"]*"', '', html)
    (DEST / f'{key}.html').write_text(html.strip() + '\n')
    left = len(re.findall('₹', html))
    report[key] = {'bytes': len(html), 'cards': cards, 'price_shortcodes': swaps, 'rupee_left': left}

(DEST / 'manifest.json').write_text(json.dumps(manifest, indent=1, ensure_ascii=False))
for k, v in report.items(): print(k, v)
