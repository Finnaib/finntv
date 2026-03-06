import re

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

usa_channels_by_group = {
    'Kids': [],
    'News': [],
    'Sports': [],
    'US Local': [],
    'Entertainment / Premium': [],
    'UK & Canada': [],
    'Others': []
}

for i in range(len(live_lines)):
    line = live_lines[i]
    if line.startswith('#EXTINF:'):
        group = None
        lower_line = line.lower()
        
        is_usa = False
        if (' usa ' in lower_line or '"usa"' in lower_line or ' us ' in lower_line or 'united states' in lower_line 
            or 'news' in lower_line or 'cnn' in lower_line or 'fox ' in lower_line 
            or 'cbs ' in lower_line or 'abc ' in lower_line or 'nbc ' in lower_line 
            or 'espn' in lower_line or 'tnt' in lower_line or 'hbo ' in lower_line 
            or 'amc ' in lower_line or 'starz' in lower_line or 'showtime' in lower_line
            or 'uk ' in lower_line or 'bbc ' in lower_line or 'itv' in lower_line 
            or 'sky ' in lower_line or 'canada' in lower_line or 'cbc ' in lower_line
            or 'sportsnet' in lower_line or 'tsn' in lower_line or 'discovery' in lower_line
            or 'history' in lower_line or 'local' in lower_line or 'pbs' in lower_line):
            is_usa = True
            
        if 'russia' in lower_line or 'arab' in lower_line or 'india' in lower_line: 
            is_usa = False

        if is_usa:
            if 'kid' in lower_line or 'disney' in lower_line or 'cartoon' in lower_line or 'nick' in lower_line or 'treehouse' in lower_line:
                group = 'Kids'
            elif 'news' in lower_line or 'cnn' in lower_line or 'fox news' in lower_line or 'msnbc' in lower_line:
                group = 'News'
            elif 'sport' in lower_line or 'espn' in lower_line or 'tsn' in lower_line or 'sky sport' in lower_line or 'bt sport' in lower_line:
                group = 'Sports'
            elif 'local' in lower_line or 'abc' in lower_line or 'cbs' in lower_line or 'nbc' in lower_line or 'pbs' in lower_line or 'cw' in lower_line:
                group = 'US Local'
            elif 'uk' in lower_line or 'canada' in lower_line or 'bbc' in lower_line or 'sky ' in lower_line or 'cbc' in lower_line or 'itv' in lower_line:
                group = 'UK & Canada'
            elif 'hbo' in lower_line or 'starz' in lower_line or 'amc' in lower_line or 'showtime' in lower_line or 'cinemax' in lower_line or 'movie' in lower_line:
                group = 'Entertainment / Premium'
            else:
                group = 'Others'
                
        if group:
            match = re.search(r',(.*?)$', line)
            channel_name = match.group(1).strip() if match else "Unknown"
            url = live_lines[i+1].strip() if i+1 < len(live_lines) else ""
            if url.startswith('http'):
                extinf = f'#EXTINF:-1 tvg-id="" tvg-name="{channel_name}" group-title="{group}",{channel_name}'
                usa_channels_by_group[group].append(extinf)
                usa_channels_by_group[group].append(url)
                usa_channels_by_group[group].append("")

with open('m3u/usa.m3u', 'w', encoding='utf-8') as f:
    f.write("#EXTM3U\n\n")
    for group, items in usa_channels_by_group.items():
        if len(items) > 0:
            f.write("################################################################################\n")
            f.write(f"# {group}\n")
            f.write("################################################################################\n\n")
            f.write('\n'.join(items) + '\n')

total = sum(len(v)//3 for v in usa_channels_by_group.values())
print(f"Extracted {total} USA/English channels from live.m3u to usa.m3u")
