"""List all unique group-title values in live.m3u"""
import re, sys
sys.stdout.reconfigure(encoding='utf-8')

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    content = f.read()

groups = sorted(set(re.findall(r'group-title="([^"]+)"', content)))
for g in groups:
    print(g)
print(f"\nTotal unique groups: {len(groups)}")
