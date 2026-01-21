import re

m3u_file = r'c:\Users\Finnaib\Downloads\Compressed\finntv-main\m3u\indonesia.m3u'
groups = set()

with open(m3u_file, 'r', encoding='utf-8') as f:
    for line in f:
        match = re.search(r'group-title="([^"]*)"', line)
        if match:
            groups.add(match.group(1))

print("Unique Groups Found:")
for g in sorted(groups):
    print(f"- {g}")
