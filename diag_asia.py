"""Show first N lines of output for find_asia_indo"""
import re, sys
sys.stdout.reconfigure(encoding='utf-8')

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    content = f.read()

extinfs = re.findall(r'#EXTINF:[^\n]+', content)

asia_keywords = [
    'korea', 'kbs', 'sbs ', 'tvn', 'jtbc', 'ytn',
    'japan', 'nhk', 'fuji', 'wowow',
    'china', 'cctv', 'cgtn', 'taiwan', 'hong kong', 'tvb',
    'thailand', 'thai',
    'philippines', 'pinoy', 'gma', 'abs-cbn', 'kapamilya',
    'malaysia', 'astro', 'singapore', 'cna',
    'vietnam', 'vtv'
]

print("=== ASIA channels (first 60 matches) ===")
count = 0
for line in extinfs:
    lower = line.lower()
    if any(k in lower for k in asia_keywords):
        name = re.search(r',(.+)$', line)
        group = re.search(r'group-title="([^"]*)"', line)
        if name:
            print(f"  G=[{group.group(1) if group else '?'}]  N={name.group(1)[:70]}")
            count += 1
            if count >= 60:
                print("  ...")
                break
print(f"Shown: {count}")
