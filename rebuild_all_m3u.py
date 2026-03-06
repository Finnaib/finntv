"""
rebuild_all_m3u.py - Rebuilds india, usa, sport M3U files using the updated group names from the new mhav1 feed.
"""
import re, sys
sys.stdout.reconfigure(encoding='utf-8')

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

catalog = []
cur_extinf = None
for line in live_lines:
    if line.startswith('#EXTINF:'):
        cur_extinf = line
    elif line.startswith('http') and cur_extinf:
        gm = re.search(r'group-title="([^"]*)"', cur_extinf)
        nm = re.search(r',(.+?)$', cur_extinf)
        catalog.append({
            'extinf': cur_extinf,
            'url':    line.strip(),
            'group':  (gm.group(1).strip() if gm else ''),
            'name':   (nm.group(1).strip() if nm else ''),
        })
        cur_extinf = None

def write_m3u(out_path, sections_ordered, channels_by_section):
    seen_urls = set()
    total = 0
    lines = ['#EXTM3U', '']
    for section in sections_ordered:
        chans = channels_by_section.get(section, [])
        if not chans: continue
        deduped = []
        for extinf, url in chans:
            if url not in seen_urls:
                seen_urls.add(url)
                ec = re.sub(r'group-title="[^"]*"', f'group-title="{section}"', extinf)
                deduped.append((ec, url))
        if not deduped: continue
        lines += ['#' * 80, f'# {section}', '#' * 80, '']
        for ec, url in deduped:
            lines += [ec, url, '']
        total += len(deduped)
    with open(out_path, 'w', encoding='utf-8') as f:
        f.write('\n'.join(lines) + '\n')
    print(f"DONE: {out_path.split('/')[-1]} - {total} channels")

# ════════════════════════════════════════════════════════════════════════════
# 1. INDIA.M3U - Includes India, Pakistan, Bangladesh
# ════════════════════════════════════════════════════════════════════════════
india = {}
# India group contains mostly Indian + some Bangla
for ch in catalog:
    gl = ch['group'].lower()
    nl = ch['name'].lower()
    
    if 'pakistani' in gl:
        india.setdefault('Pakistan', []).append((ch['extinf'], ch['url']))
    elif 'india' in gl:
        # Check if Bangla
        if 'bangla' in nl or ch['name'].startswith('BD:'):
            india.setdefault('Bangladesh', []).append((ch['extinf'], ch['url']))
        else:
            india.setdefault('India', []).append((ch['extinf'], ch['url']))
    # Include Afghan just in case
    elif 'afghan' in gl:
        india.setdefault('Afghanistan', []).append((ch['extinf'], ch['url']))

write_m3u('m3u/india.m3u', ['India', 'Pakistan', 'Bangladesh', 'Afghanistan'], india)

# ════════════════════════════════════════════════════════════════════════════
# 2. USA.M3U - Updated group names
# ════════════════════════════════════════════════════════════════════════════
usa = {}
USA_SECTIONS = {
    'u s a':      'USA',
    'u k':        'UK',
    'canada':     'Canada',
    'france فرنسى': 'France',
    'germany':    'Germany',
    'italya':     'Italy',
    'sweden':     'Sweden',
    'norway':     'Norway',
    'denmark':    'Denmark',
    'netherland': 'Netherlands',
    'switzerland': 'Switzerland',
    'espan':       'Spain',
    'portugal':    'Portugal',
    'greek':       'Greece',
    'exyu':        'EXYU',
    'russia':      'Russia',
    'polish':      'Poland',
    'brazil':      'Brazil',
    'philipine':   'Philippines'
}

for ch in catalog:
    gl = ch['group'].lower()
    friendly = USA_SECTIONS.get(gl)
    if friendly:
        usa.setdefault(friendly, []).append((ch['extinf'], ch['url']))

write_m3u('m3u/usa.m3u', sorted(USA_SECTIONS.values()), usa)

# ════════════════════════════════════════════════════════════════════════════
# 3. SPORT.M3U
# ════════════════════════════════════════════════════════════════════════════
sport = {}
SPORT_GROUP_MAP = {
    'beIN SPORTS HD':       'beIN Sports HD',
    'beIN SPORTS 4K':       'beIN Sports 4K',
    'beIN SPORTS Ultra 4k': 'beIN Sports 4K',
    'beIN Sports Afc':      'beIN Sports AFC',
    'Arabic SPORTS رياضه عربى': 'Arabic Sports',
    'ON  Sport اون اسبورت':  'ON Sport',
    'KSA AD Sport السعوديه رياضه': 'KSA Sports',
    'Alkass قطر':          'Alkass Sports',
    'Alwan الوان رياضه':      'Alwan Sports',
    'Live Sport':           'Live Sports',
    'Live Sport Pro':       'Live Sports',
    'beIN XTRA-اكسترا':      'beIN Xtra',
    'beIN MAX':             'beIN Max',
}

for ch in catalog:
    friendly = SPORT_GROUP_MAP.get(ch['group'])
    if friendly:
        sport.setdefault(friendly, []).append((ch['extinf'], ch['url']))

write_m3u('m3u/sport.m3u', sorted(SPORT_GROUP_MAP.values()), sport)
