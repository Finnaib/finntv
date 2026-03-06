import re

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

asian_channels_by_group = {
    'China / Taiwan / HK': [],
    'Korea': [],
    'Japan': [],
    'Thailand': [],
    'Philippines': [],
    'Malaysia / Singapore': [],
    'Vietnam / Others': []
}

for i in range(len(live_lines)):
    line = live_lines[i]
    if line.startswith('#EXTINF:'):
        group = None
        lower_line = line.lower()
        
        is_asia = False
        if ('china' in lower_line or 'cctv-' in lower_line or 'cctv1' in lower_line 
            or 'taiwan' in lower_line or 'hong kong' in lower_line or ' tvb ' in lower_line 
            or 'korea' in lower_line or 'kbs ' in lower_line or 'sbs ' in lower_line 
            or 'mbc ' in lower_line or 'tvn ' in lower_line or 'jtbc' in lower_line
            or 'ytn ' in lower_line or 'chosun' in lower_line or 'japan' in lower_line
            or 'nhk ' in lower_line or 'fuji tv' in lower_line or 'wowow' in lower_line
            or 'jsports' in lower_line or 'thailand' in lower_line or 'thai ' in lower_line
            or 'gmm25' in lower_line or 'thairath' in lower_line or 'philippines' in lower_line
            or 'pinoy' in lower_line or 'gma ' in lower_line or 'abs-cbn' in lower_line
            or 'kapamilya' in lower_line or 'malaysia' in lower_line or ' astro ' in lower_line
            or ' rtm ' in lower_line or ' malay ' in lower_line or 'singapore' in lower_line
            or 'cna ' in lower_line or 'mewatch' in lower_line or 'vietnam' in lower_line
            or 'vtv' in lower_line):
            is_asia = True
            
        if 'starz' in lower_line or 'sbs transit' in lower_line or 'indiana' in lower_line or 'indonesia' in lower_line: 
            is_asia = False

        if is_asia:
            if 'china' in lower_line or 'cctv' in lower_line or 'taiwan' in lower_line or 'hong kong' in lower_line or 'tvb' in lower_line:
                group = 'China / Taiwan / HK'
            elif 'korea' in lower_line or 'kbs' in lower_line or 'sbs' in lower_line or 'mbc' in lower_line or 'tvn' in lower_line or 'jtbc' in lower_line or 'ytn' in lower_line or 'chosun' in lower_line:
                group = 'Korea'
            elif 'japan' in lower_line or 'nhk' in lower_line or 'fuji' in lower_line or 'wowow' in lower_line or 'jsports' in lower_line:
                group = 'Japan'
            elif 'thailand' in lower_line or 'thai' in lower_line or 'gmm25' in lower_line or 'thairath' in lower_line:
                group = 'Thailand'
            elif 'philippin' in lower_line or 'pinoy' in lower_line or 'gma' in lower_line or 'abs-cbn' in lower_line or 'kapamilya' in lower_line:
                group = 'Philippines'
            elif 'malaysia' in lower_line or 'astro' in lower_line or 'rtm' in lower_line or 'malay' in lower_line or 'singapore' in lower_line or 'cna' in lower_line or 'mewatch' in lower_line:
                group = 'Malaysia / Singapore'
            else:
                group = 'Vietnam / Others'
                
        if group:
            match = re.search(r',(.*?)$', line)
            channel_name = match.group(1).strip() if match else "Unknown"
            url = live_lines[i+1].strip() if i+1 < len(live_lines) else ""
            if url.startswith('http'):
                extinf = f'#EXTINF:-1 tvg-id="" tvg-name="{channel_name}" group-title="{group}",{channel_name}'
                asian_channels_by_group[group].append(extinf)
                asian_channels_by_group[group].append(url)
                asian_channels_by_group[group].append("")

with open('m3u/asia.m3u', 'w', encoding='utf-8') as f:
    f.write("#EXTM3U\n\n")
    for group, items in asian_channels_by_group.items():
        if len(items) > 0:
            f.write("################################################################################\n")
            f.write(f"# {group}\n")
            f.write("################################################################################\n\n")
            f.write('\n'.join(items) + '\n')

total = sum(len(v)//3 for v in asian_channels_by_group.values())
print(f"Extracted {total} Asian channels from live.m3u to asia.m3u")
