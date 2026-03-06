"""
rebuild_egypt.py - Rebuilds egypt.m3u using the updated group names from the new mhav1 feed.
"""
import re, sys
sys.stdout.reconfigure(encoding='utf-8')

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

# ── Updated group-title values for the MHV1 feed ──────────────────────────────
EGYPT_GROUP_MAP = {
    'Egypt - قنوات مصريه':   'Egypt',
    'OSN  HD او اس ان':     'OSN',
    'OSN  SD او اس ان':     'OSN',
    'MBC ام بى سى':        'MBC',
    'Rotana - روتانا':       'Rotana',
    'SHAHID VIP':            'Shahid',
    'Shahid Club شاهد':      'Shahid',
    'Shahid Live شاهد لايف': 'Shahid',
    'beIN SPORTS HD':        'beIN Sports HD',
    'beIN SPORTS 4K':        'beIN Sports 4K',
    'beIN SPORTS Ultra 4k':  'beIN Sports 4K',
    'beIN SPORTS Low':       'beIN Sports',
    'beIN SPORTS H265 SD':   'beIN Sports',
    'beIN H-265 -8M':       'beIN Sports',
    'beIN Sport Afc':        'beIN Sports AFC',
    'beIN XTRA-اكسترا':      'beIN Xtra',
    'beIN MAX':             'beIN Max',
    'beIN Movies - بي ان الترفهيه': 'beIN Movies',
    'Arabic SPORTS رياضه عربى': 'Arabic Sports',
    'Alkass قطر':          'Alkass Sports',
    'Alwan الوان رياضه':      'Alwan Sports',
    'ON  Sport اون اسبورت':   'ON Sport',
    'Watch it واتش ات':       'Watch IT',
    'NETFLIX Club نتفلكس':   'Netflix Club',
    'MH Club ام اتش':       'MH Club',
    'MH Stars ام اتش استارز': 'MH Club',
    'Adoro Club H265 اريدو': 'Adoro Club',
    'Kanz TV Club كنز- السبكى ': 'Kanz Club',
    'Islamic - اسلامي':      'Islamic',
    'Kids - اطفال':          'Kids',
    'News - اخبار':          'News',
    'Film Box فلم بوكس':      'Film Box',
    'Home Cinema هوم سينما':  'Home Cinema',
    'Thmanayah السعودية الرياضية': 'Thmanayah',
    'Starz Play-الدوري الايطالي': 'Starz Play',
    'WWE LIVE مصارعه':       'WWE',
}

SECTION_ORDER = [
    'Egypt', 'OSN', 'MBC', 'Rotana', 'Shahid', 'Watch IT', 'Netflix Club',
    'beIN Sports 4K', 'beIN Sports HD', 'beIN Sports', 'beIN Sports AFC', 'beIN Xtra', 'beIN Max',
    'beIN Movies', 'Arabic Sports', 'Alkass Sports', 'Alwan Sports', 'ON Sport', 'Thmanayah',
    'Starz Play', 'WWE', 'MH Club', 'Adoro Club', 'Kanz Club', 'Home Cinema', 'Film Box',
    'Islamic', 'Kids', 'News'
]

GROUP_LOOKUP = { k.lower(): v for k, v in EGYPT_GROUP_MAP.items() }

channels_by_section = {}
cur_extinf = None

for line in live_lines:
    if line.startswith('#EXTINF:'):
        cur_extinf = line
    elif line.startswith('http') and cur_extinf:
        url = line.strip()
        gm = re.search(r'group-title="([^"]*)"', cur_extinf)
        group = gm.group(1).strip() if gm else ''
        friendly = GROUP_LOOKUP.get(group.lower())
        if friendly:
            channels_by_section.setdefault(friendly, []).append((cur_extinf, url))
        cur_extinf = None

seen_urls = set()
output = ['#EXTM3U', '']
total = 0

for section in SECTION_ORDER:
    chans = channels_by_section.get(section, [])
    if not chans:
        continue
    output.append('#' * 80)
    output.append(f'# {section}')
    output.append('#' * 80)
    output.append('')
    for extinf, url in chans:
        if url in seen_urls: continue
        seen_urls.add(url)
        extinf_clean = re.sub(r'group-title="[^"]*"', f'group-title="{section}"', extinf)
        output.append(extinf_clean)
        output.append(url)
        output.append('')
        total += 1
    output.append('')

with open('m3u/egypt.m3u', 'w', encoding='utf-8') as f:
    f.write('\n'.join(output) + '\n')

print(f"DONE: egypt.m3u - {total} channels")
