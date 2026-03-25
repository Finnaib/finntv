import re
import urllib.request

def parse_m3u(content):
    channels = []
    lines = content.split('\n')
    current_channel = None
    
    for line in lines:
        line = line.strip()
        if line.startswith('#EXTINF:'):
            current_channel = {'info': line, 'url': ''}
        elif line.startswith('http') and current_channel:
            current_channel['url'] = line
            channels.append(current_channel)
            current_channel = None
    return channels

def is_blocked(channel):
    txt = (channel['info'] + channel['url']).lower()
    blocked_keywords = ['18+', 'nsfw', 'adult', 'xxx', 'porn', 'sex', 'ero', 'blue movie']
    for k in blocked_keywords:
        if k in txt:
            return True
    return False

# Read local file
try:
    with open(r'c:\Users\Finnaib\Desktop\finntv\m3u\india.m3u', 'r', encoding='utf-8') as f:
        local_content = f.read()
except:
    local_content = "#EXTM3U\n"

local_channels = parse_m3u(local_content)

# Fetch remote file
remote_url = "https://raw.githubusercontent.com/bugsfreeweb/LiveTVCollector/refs/heads/main/LiveTV/India/LiveTV.m3u"
try:
    with urllib.request.urlopen(remote_url) as response:
        remote_content = response.read().decode('utf-8')
except Exception as e:
    print(f"Error fetching remote M3U: {e}")
    remote_content = ""

remote_channels = parse_m3u(remote_content)

# Merge and Filter
all_channels = local_channels + remote_channels
filtered_channels = []
urls_seen = set()

for c in all_channels:
    if is_blocked(c):
        continue
    if c['url'] in urls_seen:
        continue
    urls_seen.add(c['url'])
    filtered_channels.append(c)

# Write output
with open(r'c:\Users\Finnaib\Desktop\finntv\m3u\india.m3u', 'w', encoding='utf-8') as f:
    f.write("#EXTM3U\n\n")
    for c in filtered_channels:
        f.write(c['info'] + '\n')
        f.write(c['url'] + '\n\n')

print(f"Merge complete. Total channels: {len(filtered_channels)}")
