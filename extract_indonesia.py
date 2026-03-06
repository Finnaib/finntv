import re

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

indo_channels_by_group = {
    'ANAK': [],
    'BERITA': [],
    'BOLA / SPORTS': [],
    'AGAMA': [],
    'HIBURAN / TV NASIONAL': [],
    'CCTV inDo': [],
    'Luar Negeri / Lainnya': []
}

for i in range(len(live_lines)):
    line = live_lines[i]
    if line.startswith('#EXTINF:'):
        group = None
        lower_line = line.lower()
        
        is_indo = False
        if ('indonesia' in lower_line or 'indo ' in lower_line or 'jawa ' in lower_line 
            or 'bali ' in lower_line or 'sunda ' in lower_line or 'trans tv' in lower_line 
            or 'trans7' in lower_line or 'rcti' in lower_line or 'sctv' in lower_line 
            or 'indosiar' in lower_line or 'antv' in lower_line or 'mnc ' in lower_line
            or 'rtv' in lower_line or 'kompas' in lower_line or 'tvri' in lower_line
            or 'tvone' in lower_line or 'metro tv' in lower_line or 'inews' in lower_line
            or 'net.' in lower_line or 'net tv' in lower_line or 'jakarta' in lower_line
            or 'cctv indo' in lower_line or 'cctv_indo' in lower_line or 'malioboro' in lower_line
            or 'atcs' in lower_line or 'jogja' in lower_line):
            is_indo = True
            
        if 'window' in lower_line or 'hindu' in lower_line:
            is_indo = False

        if is_indo:
            if 'anak' in lower_line or 'kids' in lower_line or 'cartoon' in lower_line or 'disney' in lower_line or 'nick' in lower_line:
                group = 'ANAK'
            elif 'berita' in lower_line or 'news' in lower_line or 'tvone' in lower_line or 'metro tv' in lower_line or 'inews' in lower_line or 'kompas' in lower_line:
                group = 'BERITA'
            elif 'bola' in lower_line or 'sport' in lower_line or 'arena' in lower_line or 'premier' in lower_line:
                group = 'BOLA / SPORTS'
            elif 'agama' in lower_line or 'religi' in lower_line or 'islam' in lower_line or 'rodja' in lower_line or 'dakwah' in lower_line:
                group = 'AGAMA'
            elif 'cctv' in lower_line or 'malioboro' in lower_line or 'atcs' in lower_line or 'jogja' in lower_line:
                group = 'CCTV inDo'
            elif 'trans' in lower_line or 'rcti' in lower_line or 'sctv' in lower_line or 'indosiar' in lower_line or 'mnc' in lower_line or 'antv' in lower_line or 'tvri' in lower_line or 'hiburan' in lower_line:
                group = 'HIBURAN / TV NASIONAL'
            else:
                group = 'Luar Negeri / Lainnya'
                
        if group:
            match = re.search(r',(.*?)$', line)
            channel_name = match.group(1).strip() if match else "Unknown"
            url = live_lines[i+1].strip() if i+1 < len(live_lines) else ""
            if url.startswith('http'):
                extinf = f'#EXTINF:-1 tvg-id="" tvg-name="{channel_name}" group-title="{group}",{channel_name}'
                indo_channels_by_group[group].append(extinf)
                indo_channels_by_group[group].append(url)
                indo_channels_by_group[group].append("")

with open('m3u/indonesia.m3u', 'w', encoding='utf-8') as f:
    f.write("#EXTM3U\n\n")
    for group, items in indo_channels_by_group.items():
        if len(items) > 0:
            f.write("################################################################################\n")
            f.write(f"# {group}\n")
            f.write("################################################################################\n\n")
            f.write('\n'.join(items) + '\n')

total = sum(len(v)//3 for v in indo_channels_by_group.values())
print(f"Extracted {total} Indonesian channels from live.m3u to indonesia.m3u")
