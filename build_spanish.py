"""
build_spanish.py
================
Fetches Spanish channels from two sources and builds spanish.m3u.
No strict filtering - takes all channels from Spain sources.
"""
import requests, re, sys
sys.stdout.reconfigure(encoding='utf-8')

SPAIN_SOURCES = [
    'https://iptv-org.github.io/iptv/countries/es.m3u',
    'https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/main/LiveTV/Spain/LiveTV.m3u'
]

BLOCK_KEYWORDS = ['xxx', 'adult', 'porn', '18+', 'erotic', 'brazers', 'redlight', 'playboy', 'penthouse', 'hustler']

seen = set()
output = ['#EXTM3U', '']

for url in SPAIN_SOURCES:
    try:
        print(f"Fetching: {url}")
        r = requests.get(url, timeout=20)
        r.raise_for_status()
        lines = r.text.replace('\r', '').splitlines()
        cur = None
        count = 0
        for line in lines:
            if line.startswith('#EXTINF:'):
                cur = line
            elif line.startswith('http') and cur:
                url_key = line.strip().lower()
                # Only block adult content
                name_match = re.search(r',(.+?)$', cur)
                name = name_match.group(1).strip().lower() if name_match else ''
                is_adult = any(k in name for k in BLOCK_KEYWORDS)
                
                if not is_adult and url_key not in seen:
                    seen.add(url_key)
                    # Normalize group title to Spain
                    extinf = cur
                    if 'group-title' not in extinf:
                        extinf = extinf.replace('#EXTINF:-1', '#EXTINF:-1 group-title="Spain"')
                    else:
                        extinf = re.sub(r'group-title="[^"]*"', 'group-title="Spain"', extinf)
                    output.append(extinf)
                    output.append(line.strip())
                    output.append('')
                    count += 1
                cur = None
        print(f"  Added {count} channels from this source.")
    except Exception as e:
        print(f"  Error: {e}")

with open('m3u/spanish.m3u', 'w', encoding='utf-8') as f:
    f.write('\n'.join(output))

print(f"\nDone! spanish.m3u has {len(seen)} unique channels.")
