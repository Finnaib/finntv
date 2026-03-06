import re

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

egypt_channels_by_group = {
    'Arabic Sports': [],
    'beIN MAX': [],
    'beIN Movies': [],
    'beIN Sports': [],
    'MBC': [],
    'Rotana': [],
    'Shahid Live': [],
    'Kids': [],
    'Egypt': [],
    'Others': []
}

in_group = None

for i in range(len(live_lines)):
    line = live_lines[i]
    if line.startswith('#EXTINF:'):
        # Determine group
        group = None
        
        # Check explicit tags or names
        lower_line = line.lower()
        
        if 'group-title="arabic sport' in lower_line or 'shasha sport' in lower_line or ('ksa' in lower_line and 'sport' in lower_line) or 'ad sport' in lower_line or 'ssc' in lower_line or 'abu dhabi sports' in lower_line:
            group = 'Arabic Sports'
        elif 'bein sports max' in lower_line or 'bein max' in lower_line:
            group = 'beIN MAX'
        elif ('bein' in lower_line and 'movies' in lower_line) or 'fatafeat' in lower_line or 'bein_star' in lower_line or 'jeem' in lower_line or 'hgtv' in lower_line:
            group = 'beIN Movies'
        elif 'bein sport' in lower_line or 'bein' in lower_line and ('xtra' in lower_line or 'english' in lower_line):
            group = 'beIN Sports'
        elif 'mbc' in lower_line and 'persia' not in lower_line and 'iraq' not in lower_line:
            group = 'MBC'
        elif 'rotana' in lower_line or 'lbc' in lower_line:
            group = 'Rotana'
        elif 'shahid' in lower_line:
            group = 'Shahid Live'
        elif 'spacetoon' in lower_line or 'cn arabic' in lower_line or 'mbc 3' in lower_line or 'rotana kids' in lower_line or 'baby' in lower_line or 'baraem' in lower_line:
            group = 'Kids'
        elif 'group-title="egypt' in lower_line or 'group-title="eg|' in lower_line or 'eg|' in lower_line or 'nilesat' in lower_line or 'osn' in lower_line or 'al arabiya' in lower_line or 'al hadath' in lower_line or 'alghad' in lower_line or 'aghapy' in lower_line or 'on time ' in lower_line or 'on sport' in lower_line or 'al ahly' in lower_line or 'zamalek' in lower_line:
            group = 'Egypt'
            
        if group:
            match = re.search(r',(.*?)$', line)
            channel_name = match.group(1).strip() if match else "Unknown"
            url = live_lines[i+1].strip() if i+1 < len(live_lines) else ""
            if url.startswith('http'):
                extinf = f'#EXTINF:-1 tvg-id="" tvg-name="{channel_name}" group-title="{group}",{channel_name}'
                egypt_channels_by_group[group].append(extinf)
                egypt_channels_by_group[group].append(url)
                egypt_channels_by_group[group].append("")



with open('m3u/egypt.m3u', 'w', encoding='utf-8') as f:
    f.write("#EXTM3U\n\n")
    for group, items in egypt_channels_by_group.items():
        if len(items) > 0:
            f.write("################################################################################\n")
            f.write(f"# {group}\n")
            f.write("################################################################################\n\n")
            f.write('\n'.join(items) + '\n')

total = sum(len(v)//3 for v in egypt_channels_by_group.values())
print(f"Extracted {total} famous/Egypt channels from live.m3u to egypt.m3u")
