"""
rebuild_egypt.py - Rebuilds egypt.m3u from scratch using only genuinely Egyptian channels from live.m3u.
Looks for channels matching Egyptian patterns: EG:, group-title="EGYPT", group-title="Egypt",
group-title="Nilesat", Ch:Aghapy, OSN (Middle East), beIN (Arabic), ON Sport, Nile channels, etc.
"""
import re

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

# -----------------------------------------------------------------------
# Groups and name patterns that count as "Egypt / Arab" channels
# -----------------------------------------------------------------------
INCLUDE_GROUPS = {
    'egypt', 'eg', 'nilesat', 'arabic sports', 'bein sports',
    'bein movies', 'osn', 'mbc egypt', 'rotana', 'on sport'
}

# Prefixes found on channel names that identify them as Egyptian/Arab
EG_PREFIXES = (
    'eg:', 'ch:aghapy', 'ch:aghapy ', 'nile ', 'egon', 'eg ',
)

# group-title values in live.m3u that are clearly Egypt/Arab
INCLUDE_GROUP_PATTERNS = [
    re.compile(r'^egypt$', re.I),
    re.compile(r'^EGYPT\s', re.I),
    re.compile(r'^nilesat', re.I),
    re.compile(r'^arabic\s*sports$', re.I),
    re.compile(r'^bein\s*sports$', re.I),
    re.compile(r'^bein\s*movies$', re.I),
    re.compile(r'^OSN$', re.I),
    re.compile(r'^mbc\s*egypt$', re.I),
    re.compile(r'^on\s*sport', re.I),
]

# Channel name patterns that flag a channel as Egyptian regardless of group
EG_CHANNEL_PATTERNS = [
    # Egyptian channel name prefixes (EG:, Ch:Aghapy, etc.)
    re.compile(r'^EG:', re.I),
    re.compile(r'^Ch:Aghapy', re.I),
    # Nile Egypt channels
    re.compile(r'\bNile\s+(Cinema|Comedy|Culture|Drama|Education|Family|Life|News|Sport|TV|Zaman)\b', re.I),
    # ON Sport (Egyptian sports)
    re.compile(r'\bON[\-\s]?Sport\b', re.I),
    # Maspero Zaman (Egyptian state TV)
    re.compile(r'\bMaspero\b', re.I),
    # beIN – keep all beIN channels
    re.compile(r'\bbeIN\b', re.I),
    # OSN – keep all OSN channels
    re.compile(r'\bOSN\b', re.I),
    # MBC channels
    re.compile(r'\bMBC\b', re.I),
]

def group_is_egypt(group_title: str) -> bool:
    for pat in INCLUDE_GROUP_PATTERNS:
        if pat.search(group_title):
            return True
    return False

def name_is_egypt(channel_name: str) -> bool:
    for pat in EG_CHANNEL_PATTERNS:
        if pat.search(channel_name):
            return True
    return False

# -----------------------------------------------------------------------
# Track which section headers we've seen so we can label them neatly
# -----------------------------------------------------------------------
SECTION_MAP = {
    # group_title keyword -> friendly section name
    'egypt': 'Egypt',
    'nilesat': 'Egypt',
    'on sport': 'Egypt',
    'mbc egypt': 'MBC',
    'bein sports': 'beIN Sports',
    'bein movies': 'beIN Movies',
    'arabic sports': 'Arabic Sports',
    'osn': 'OSN',
    'rotana': 'Rotana',
    'mbc': 'MBC',
}

def friendly_section(group_title: str) -> str:
    lower = group_title.lower()
    for key, friendly in SECTION_MAP.items():
        if key in lower:
            return friendly
    return group_title

# -----------------------------------------------------------------------
# Parse live.m3u and collect matching channels
# -----------------------------------------------------------------------
channels_by_section = {}  # section_name -> list of (extinf_line, url_line)
current_extinf = None

for i, line in enumerate(live_lines):
    if line.startswith('#EXTINF:'):
        current_extinf = line
    elif line.startswith('http') and current_extinf:
        url = line.strip()
        # Parse group-title and channel name from #EXTINF line
        group_match = re.search(r'group-title="([^"]*)"', current_extinf)
        name_match = re.search(r',(.+?)$', current_extinf)
        group_title = group_match.group(1).strip() if group_match else ''
        channel_name = name_match.group(1).strip() if name_match else ''

        # Decide whether to include this channel
        include = False
        section = None

        if group_is_egypt(group_title):
            include = True
            section = friendly_section(group_title)
        elif name_is_egypt(channel_name):
            include = True
            # Determine section from channel name
            if re.search(r'\bbeIN\b', channel_name, re.I):
                section = 'beIN Sports'
            elif re.search(r'\bOSN\b', channel_name, re.I):
                section = 'OSN'
            elif re.search(r'\bMBC\b', channel_name, re.I):
                section = 'MBC'
            else:
                section = 'Egypt'

        if include and section:
            if section not in channels_by_section:
                channels_by_section[section] = []
            channels_by_section[section].append((current_extinf, url))

        current_extinf = None
    elif not line.startswith('#'):
        current_extinf = None

# -----------------------------------------------------------------------
# Sort sections and deduplicate by URL (keep first occurrence)
# -----------------------------------------------------------------------
SECTION_ORDER = [
    'Egypt', 'Arabic Sports', 'beIN Sports', 'beIN Movies',
    'OSN', 'MBC', 'Rotana'
]

# Add any extra sections we may have caught
for s in list(channels_by_section.keys()):
    if s not in SECTION_ORDER:
        SECTION_ORDER.append(s)

def dedup(channel_list):
    seen_urls = set()
    result = []
    for extinf, url in channel_list:
        if url not in seen_urls:
            seen_urls.add(url)
            result.append((extinf, url))
    return result

# -----------------------------------------------------------------------
# Build output
# -----------------------------------------------------------------------
output_lines = ['#EXTM3U', '']

total = 0
for section in SECTION_ORDER:
    if section not in channels_by_section:
        continue
    chans = dedup(channels_by_section[section])
    if not chans:
        continue
    # Section header
    output_lines.append('#' * 80)
    output_lines.append(f'# {section}')
    output_lines.append('#' * 80)
    output_lines.append('')
    for extinf, url in chans:
        # Normalise the group-title to match the section name
        extinf_clean = re.sub(r'group-title="[^"]*"',
                              f'group-title="{section}"', extinf)
        output_lines.append(extinf_clean)
        output_lines.append(url)
        output_lines.append('')
        total += 1
    output_lines.append('')

with open('m3u/egypt.m3u', 'w', encoding='utf-8') as f:
    f.write('\n'.join(output_lines) + '\n')

print(f"\nDONE: egypt.m3u rebuilt with {total} channels across {len([s for s in SECTION_ORDER if s in channels_by_section])} sections.")
for section in SECTION_ORDER:
    if section in channels_by_section:
        n = len(dedup(channels_by_section[section]))
        print(f"   {section}: {n} channels")
