"""
rebuild_all_m3u.py
==================
Rebuilds india.m3u, indonesia.m3u, usa.m3u, asia.m3u, and sport.m3u
from scratch using the EXACT group-title values found in live.m3u.
This is far more accurate than keyword-matching on channel names.
"""

import re
import sys
sys.stdout.reconfigure(encoding='utf-8')

# ────────────────────────────────────────────────────────────────────────────
# Parse live.m3u into a list of (extinf_line, url_line, group_title, chan_name)
# ────────────────────────────────────────────────────────────────────────────
print("Reading live.m3u ...")
with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

catalog = []          # list of dicts
current_extinf = None

for line in live_lines:
    if line.startswith('#EXTINF:'):
        current_extinf = line
    elif line.startswith('http') and current_extinf:
        url = line.strip()
        gm = re.search(r'group-title="([^"]*)"', current_extinf)
        nm = re.search(r',(.+?)$', current_extinf)
        group = gm.group(1).strip() if gm else ''
        name  = nm.group(1).strip() if nm else ''
        catalog.append({
            'extinf': current_extinf,
            'url':    url,
            'group':  group,
            'name':   name,
            'group_lower': group.lower(),
            'name_lower':  name.lower(),
        })
        current_extinf = None
    elif not line.startswith('#'):
        current_extinf = None

print(f"  Total channels in live.m3u: {len(catalog)}")


# ────────────────────────────────────────────────────────────────────────────
# Helper: write an m3u file from a dict of {section: [(extinf, url), ...]}
# ────────────────────────────────────────────────────────────────────────────
def write_m3u(out_path, sections_ordered, channels_by_section):
    """Deduplicate by URL, write in section order."""
    seen_urls = set()
    total = 0
    lines = ['#EXTM3U', '']

    for section in sections_ordered:
        chans = channels_by_section.get(section, [])
        if not chans:
            continue
        # Normalise group-title in #EXTINF to the friendly section label
        deduped = []
        for extinf, url in chans:
            if url not in seen_urls:
                seen_urls.add(url)
                extinf_clean = re.sub(r'group-title="[^"]*"',
                                      f'group-title="{section}"', extinf)
                deduped.append((extinf_clean, url))

        if not deduped:
            continue
        lines.append('#' * 80)
        lines.append(f'# {section}')
        lines.append('#' * 80)
        lines.append('')
        for extinf, url in deduped:
            lines.append(extinf)
            lines.append(url)
            lines.append('')
        total += len(deduped)

    with open(out_path, 'w', encoding='utf-8') as f:
        f.write('\n'.join(lines) + '\n')

    label = out_path.split('/')[-1]
    print(f"\n{label}: {total} channels")
    for section in sections_ordered:
        chans = channels_by_section.get(section, [])
        if chans:
            print(f"   {section}: {len(chans)}")

    return total


# ════════════════════════════════════════════════════════════════════════════
# 1. INDIA  (india.m3u)
#    Groups: IN[ Kids ], IN[ Movies ], IN[ Prime ], IN[ Sport ]
#    Also pick up channels with name prefix IN: / Ind: / IN | for any that
#    the source labels with a different group
# ════════════════════════════════════════════════════════════════════════════
print("\n--- Building india.m3u ---")

INDIA_GROUPS = {
    'IN[ Kids ]':   'Kids',
    'IN[ Movies ]': 'Movies',
    'IN[ Prime ]':  'Entertainment',
    'IN[ Sport ]':  'Sports',
}

# Name-prefix fallback patterns (group-title not set to IN[...])
INDIA_NAME_RX = re.compile(
    r'^(IN:|IN\s+\||\bIN\b\s*-\s*|Ind:|india\s)',
    re.I
)

india = {}
for ch in catalog:
    gl = ch['group_lower']
    # Primary: exact group match
    for raw_group, friendly in INDIA_GROUPS.items():
        if raw_group.lower() == gl:
            india.setdefault(friendly, []).append((ch['extinf'], ch['url']))
            break
    else:
        # Fallback: channel name starts with an India prefix
        if INDIA_NAME_RX.match(ch['name']):
            india.setdefault('Entertainment', []).append((ch['extinf'], ch['url']))

write_m3u('m3u/india.m3u',
    ['Entertainment', 'Sports', 'Movies', 'Kids'],
    india)


# ════════════════════════════════════════════════════════════════════════════
# 2. INDONESIA  (indonesia.m3u)
#    No dedicated group in live.m3u — use channel name prefixes:
#    "ID:" / "IDN:" / "Indo:" / well-known Indonesian channel name keywords
# ════════════════════════════════════════════════════════════════════════════
print("\n--- Building indonesia.m3u ---")

# Known Indonesian channel names / keywords in the tvg-name
INDO_CHANNELS_RX = re.compile(
    r'(?:'
    r'^ID[N:]|'                          # prefix ID: or IDN:
    r'\bRCTI\b|\bSCTV\b|\bINDOSIAR\b|'  # major national channels
    r'\bTVRI\b|\bMNC\s*TV\b|\bANTV\b|'
    r'\bTrans\s*TV\b|\bTrans7\b|\bTV\s*One\b|\bTVone\b|'
    r'\bNet\.?\s*TV\b|\biNews\b|\bKompas\s*TV\b|'
    r'\bMetro\s*TV\b|\bRTV\b.*indo|'
    r'\bJakarta\b|\bIndonesia\b'
    r')',
    re.I
)

# Category buckets
INDO_SECTIONS = ['Hiburan', 'Berita', 'Olahraga', 'Anak', 'Lainnya']

def classify_indo(name):
    nl = name.lower()
    if any(k in nl for k in ['anak', 'kids', 'disney', 'cartoon', 'nick', 'spacetoon']):
        return 'Anak'
    if any(k in nl for k in ['berita', 'news', 'kompas', 'metro tv', 'inews', 'tvone', 'tv one']):
        return 'Berita'
    if any(k in nl for k in ['sport', 'bola', 'liga', 'sports']):
        return 'Olahraga'
    if any(k in nl for k in ['rcti', 'sctv', 'indosiar', 'trans tv', 'trans7', 'tvri',
                              'antv', 'mnc', 'net tv', 'net.', 'gtv', 'inews']):
        return 'Hiburan'
    return 'Lainnya'

indonesia = {}
for ch in catalog:
    if INDO_CHANNELS_RX.search(ch['name']):
        section = classify_indo(ch['name'])
        indonesia.setdefault(section, []).append((ch['extinf'], ch['url']))

write_m3u('m3u/indonesia.m3u', INDO_SECTIONS, indonesia)


# ════════════════════════════════════════════════════════════════════════════
# 3. USA  (usa.m3u)
#    Groups: US [*], UK [*], Canada, Canada Fr
#    Map to friendly section names
# ════════════════════════════════════════════════════════════════════════════
print("\n--- Building usa.m3u ---")

USA_GROUP_MAP = {
    'us [ news ]':       'US News',
    'us [ kids ]':       'US Kids',
    'us [ sport ]':      'US Sports',
    'us [ nba ]':        'US Sports',
    'us [ fox ]':        'US Entertainment',
    'us [ nbc ]':        'US Entertainment',
    'us [ prime ]':      'US Premium',
    'us [ movies ]':     'US Movies',
    'us [ docum ]':      'US Documentary',
    'us [ music ]':      'US Music',
    'uk [ bbc ]':        'UK Channels',
    'uk [ sky ]':        'UK Channels',
    'uk [ prime ]':      'UK Channels',
    'uk [ sports ]':     'UK Sports',
    'uk [ cartoon ]':    'UK Kids',
    'uk [ docum ]':      'UK Documentary',
    'canada':            'Canada',
    'canada fr':         'Canada',
}

USA_SECTIONS = [
    'US News', 'US Sports', 'US Entertainment', 'US Premium',
    'US Movies', 'US Documentary', 'US Music', 'US Kids',
    'UK Channels', 'UK Sports', 'UK Documentary', 'UK Kids',
    'Canada'
]

usa = {}
for ch in catalog:
    friendly = USA_GROUP_MAP.get(ch['group_lower'])
    if friendly:
        usa.setdefault(friendly, []).append((ch['extinf'], ch['url']))

write_m3u('m3u/usa.m3u', USA_SECTIONS, usa)


# ════════════════════════════════════════════════════════════════════════════
# 4. ASIA  (asia.m3u)
#    No dedicated group in live.m3u.
#    Use channel name prefix patterns for each country.
#    Known prefixes observed: KR|KR:, JP|JP:, TH|TH:, PH|PH:,
#    MY|MY:, SG|SG:, CN|CN:, TW|TW:, HK|HK:, VN|VN:
# ════════════════════════════════════════════════════════════════════════════
print("\n--- Building asia.m3u ---")

ASIA_PREFIX_MAP = {
    # (regex, section)
    re.compile(r'^(KR[:\s]|Korea\s)', re.I):                     'Korea',
    re.compile(r'^(JP[:\s]|Japan\s)', re.I):                     'Japan',
    re.compile(r'^(TH[:\s]|Thailand\s|Thai\s)', re.I):           'Thailand',
    re.compile(r'^(PH[:\s]|Philippines\s|Pinoy\s)', re.I):       'Philippines',
    re.compile(r'^(MY[:\s]|Malaysia\s)', re.I):                  'Malaysia',
    re.compile(r'^(SG[:\s]|Singapore\s)', re.I):                 'Singapore',
    re.compile(r'^(CN[:\s]|China\s|CCTV-|CGTN)', re.I):         'China',
    re.compile(r'^(TW[:\s]|Taiwan\s)', re.I):                    'Taiwan',
    re.compile(r'^(HK[:\s]|Hong\s*Kong\s)', re.I):              'Hong Kong',
    re.compile(r'^(VN[:\s]|Vietnam\s|VTV\d)', re.I):             'Vietnam',
}

ASIA_SECTIONS = [
    'Korea', 'Japan', 'China', 'Taiwan', 'Hong Kong',
    'Thailand', 'Philippines', 'Malaysia', 'Singapore', 'Vietnam'
]

asia = {}
for ch in catalog:
    for rx, section in ASIA_PREFIX_MAP.items():
        if rx.match(ch['name']):
            asia.setdefault(section, []).append((ch['extinf'], ch['url']))
            break

write_m3u('m3u/asia.m3u', ASIA_SECTIONS, asia)


# ════════════════════════════════════════════════════════════════════════════
# 5. SPORT  (sport.m3u)
#    Groups: Sports, Alwan Sport, Bein Sport [*], UAE Sports,
#            UK [ Sports ], US [ Sport ], US [ NBA ], IN[ Sport ],
#            Fr [ Sports ], Deutsche Sport, Polish Sports, Turkish Spor,
#            Egypt sports (ON Sport)
# ════════════════════════════════════════════════════════════════════════════
print("\n--- Building sport.m3u ---")

SPORT_GROUP_MAP = {
    'sports':               'General Sports',
    'alwan sport':          'Alwan Sport',
    'bein sport [ 2m ]':    'beIN Sport',
    'bein sport [ 4k ]':    'beIN Sport 4K',
    'bein sport [ en-fr ]': 'beIN Sport EN/FR',
    'bein sport [ hd ]':    'beIN Sport HD',
    'uae sports':           'UAE Sports',
    'uk [ sports ]':        'UK Sports',
    'us [ sport ]':         'US Sports',
    'us [ nba ]':           'US Sports',
    'in[ sport ]':          'India Sports',
    'fr [ sports ]':        'France Sports',
    'deutsche sport':       'Germany Sports',
    'poland sports':        'Poland Sports',
    'turkish spor':         'Turkish Sports',
    'bein [ ترفيهيه]':     'beIN Entertainment',
}

# Extra: ON Sport channels (Egyptian sports) — caught by channel name
ON_SPORT_RX = re.compile(r'\bON[\-\s]?Sport\b', re.I)

SPORT_SECTIONS = [
    'beIN Sport HD', 'beIN Sport', 'beIN Sport 4K', 'beIN Sport EN/FR',
    'General Sports', 'UAE Sports', 'UK Sports', 'US Sports',
    'France Sports', 'Germany Sports', 'India Sports', 'Turkish Sports',
    'Poland Sports', 'Alwan Sport', 'beIN Entertainment', 'ON Sport'
]

sport = {}
for ch in catalog:
    friendly = SPORT_GROUP_MAP.get(ch['group_lower'])
    if friendly:
        sport.setdefault(friendly, []).append((ch['extinf'], ch['url']))
    elif ON_SPORT_RX.search(ch['name']):
        sport.setdefault('ON Sport', []).append((ch['extinf'], ch['url']))

write_m3u('m3u/sport.m3u', SPORT_SECTIONS, sport)

print("\nAll done.")
