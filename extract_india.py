import re

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

indian_channels_by_group = {
    'Bangladesh': [],
    'Kids': [],
    'News': [],
    'India': [],
    'Uncategorized': []
}

in_group = None

for i in range(len(live_lines)):
    line = live_lines[i]
    if line.startswith('#EXTINF:'):
        # Determine group
        group = None
        lower_line = line.lower()
        
        # 1. Keywords to grab for the regions
        # Only grab it if it sounds like it belongs to Indian/Pak/BD subcontinent
        is_subcontinent = False
        if ('india' in lower_line or 'hindi' in lower_line or 'bangla' in lower_line 
            or 'gujarati' in lower_line or 'tamil' in lower_line or 'telugu' in lower_line 
            or 'kannada' in lower_line or 'malayalam' in lower_line or 'marathi' in lower_line
            or 'pakistan' in lower_line or 'urdu' in lower_line or 'punjabi' in lower_line
            or 'star' in lower_line or 'zee ' in lower_line or 'sony ' in lower_line 
            or 'colors ' in lower_line or 'sun ' in lower_line or 'jalsha' in lower_line
            or 'asianet' in lower_line or 'sports18' in lower_line):
            is_subcontinent = True
            
        # Exception: filter out some false positives if necessary, e.g., 'star' in 'starz' or western channels
        if 'starz' in lower_line or 'star trek' in lower_line: 
            is_subcontinent = False

        if is_subcontinent:
            if 'bangla' in lower_line or 'bd ' in lower_line or 'btv' in lower_line or 'somoy' in lower_line:
                group = 'Bangladesh'
            elif 'kids' in lower_line or 'disney' in lower_line or 'nick' in lower_line or 'cartoon' in lower_line or 'hungama' in lower_line or 'pogo' in lower_line:
                group = 'Kids'
            elif 'news' in lower_line or 'aaj tak' in lower_line or 'ndtv' in lower_line or 'abp' in lower_line or 'geo' in lower_line or 'ary' in lower_line:
                group = 'News'
            else:
                group = 'India'
                
        if group:
            match = re.search(r',(.*?)$', line)
            channel_name = match.group(1).strip() if match else "Unknown"
            url = live_lines[i+1].strip() if i+1 < len(live_lines) else ""
            if url.startswith('http'):
                extinf = f'#EXTINF:-1 tvg-id="" tvg-name="{channel_name}" group-title="{group}",{channel_name}'
                indian_channels_by_group[group].append(extinf)
                indian_channels_by_group[group].append(url)
                indian_channels_by_group[group].append("")



with open('m3u/india.m3u', 'w', encoding='utf-8') as f:
    f.write("#EXTM3U\n\n")
    for group, items in indian_channels_by_group.items():
        if len(items) > 0:
            f.write("################################################################################\n")
            f.write(f"# {group}\n")
            f.write("################################################################################\n\n")
            f.write('\n'.join(items) + '\n')

total = sum(len(v)//3 for v in indian_channels_by_group.values())
print(f"Extracted {total} Indian/Subcontinent channels from live.m3u to india.m3u")
