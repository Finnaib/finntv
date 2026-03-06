"""Check actual channel name patterns for Asian and Indonesian channels in live.m3u"""
import re, sys
sys.stdout.reconfigure(encoding='utf-8')

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    content = f.read()

# Find all EXTINF lines
extinfs = re.findall(r'#EXTINF:[^\n]+', content)

# Filter by keywords that suggest Asian origin
asia_keywords = [
    'korea', 'kbs', 'sbs', 'tvn', 'jtbc', 'ytn',
    'japan', 'nhk', 'fuji', 'wowow',
    'china', 'cctv', 'cgtn', 'taiwan', 'hong kong', 'tvb',
    'thailand', 'thai',
    'philippines', 'pinoy', 'gma', 'abs-cbn', 'kapamilya',
    'malaysia', 'astro', 'singapore', 'cna',
    'vietnam', 'vtv'
]

indo_keywords = [
    'rcti', 'sctv', 'indosiar', 'tvri', 'trans tv', 'trans7', 'tvone',
    'metro tv', 'kompas', 'inews', 'antv', 'mnc tv', 'net tv', 'jakarta',
    'indonesia', 'id:'
]

print("=== ASIA channels (first 40 matches) ===")
count = 0
for line in extinfs:
    lower = line.lower()
    if any(k in lower for k in asia_keywords):
        name = re.search(r',(.+)$', line)
        group = re.search(r'group-title="([^"]*)"', line)
        if name:
            print(f"  [{group.group(1) if group else '?'}] {name.group(1)}")
            count += 1
            if count >= 40:
                break

print(f"\nTotal shown: {count}")
print()
print("=== INDONESIA channels (all matches) ===")
count = 0
for line in extinfs:
    lower = line.lower()
    if any(k in lower for k in indo_keywords):
        name = re.search(r',(.+)$', line)
        group = re.search(r'group-title="([^"]*)"', line)
        if name:
            print(f"  [{group.group(1) if group else '?'}] {name.group(1)}")
            count += 1
print(f"Total: {count}")
