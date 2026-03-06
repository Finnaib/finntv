import re

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

egypt_channels = []
in_egypt_group = False

for i in range(len(live_lines)):
    line = live_lines[i]
    if line.startswith('#EXTINF:'):
        # We check both explicit group-title="EGYPT and channels that start with "Eg|" or similar formats common to Egyptian feeds
        if 'group-title="EGYPT' in line or ',Eg|' in line or 'group-title="Nilesat' in line:
            in_egypt_group = True
            
        if in_egypt_group:
            # We want to format it nicely like the old one, but keep the new ids/urls
            # Grab the channel name
            match = re.search(r',(.*?)$', line)
            if match:
                channel_name = match.group(1).strip()
            else:
                channel_name = "Unknown"
                
            # Grab the url (usually the next line)
            url = live_lines[i+1].strip() if i+1 < len(live_lines) else ""
            
            # Make sure it's valid
            if url.startswith('http'):
                # Format it as clean as possible
                extinf = f'#EXTINF:-1 tvg-id="" tvg-name="{channel_name}" group-title="Egypt",{channel_name}'
                egypt_channels.append(extinf)
                egypt_channels.append(url)
                egypt_channels.append("") # empty line spacing
            
            in_egypt_group = False

header = "#EXTM3U\n\n"
header += "################################################################################\n"
header += "# Egypt\n"
header += "################################################################################\n\n"

with open('m3u/egypt.m3u', 'w', encoding='utf-8') as f:
    f.write(header)
    f.write('\n'.join(egypt_channels) + '\n')

print(f"Extracted {len(egypt_channels)//3} Egyptian channels from live.m3u to egypt.m3u")
