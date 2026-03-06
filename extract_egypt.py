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

# Keep the direct CDN channels from the old list just in case
cdn_channels = [
    '#EXTINF:-1 tvg-name="Aghapy TV" tvg-logo="https://upload.wikimedia.org/wikipedia/en/e/eb/AghapyTV.jpg" tvg-id="AghapyTV.eg" group-title="Egypt",Aghapy TV',
    'https://5b622f07944df.streamlock.net/aghapy.tv/aghapy.smil/playlist.m3u8',
    '',
    '#EXTINF:-1 tvg-name="Al Ghad Plus" tvg-logo="https://upload.wikimedia.org/wikipedia/commons/0/06/AlGhad_TV.png" tvg-id="AlGhadPlus.eg" group-title="Egypt",Al Ghad Plus',
    'https://playlist.fasttvcdn.com/pl/ykvm3f2fhokwxqsurp9xcg/alghad-plus/playlist.m3u8',
    '',
    '#EXTINF:-1 tvg-name="Al Ghad TV" tvg-logo="https://upload.wikimedia.org/wikipedia/commons/0/06/AlGhad_TV.png" tvg-id="AlGhadTV.eg" group-title="Egypt",Al Ghad TV',
    'https://eazyvwqssi.erbvr.com/alghadtv/alghadtv.m3u8',
    '',
    '#EXTINF:-1 tvg-name="Al Qahera News" tvg-logo="https://upload.wikimedia.org/wikipedia/ar/b/b0/%D9%82%D9%86%D8%A7%D8%A9_%D8%A7%D9%84%D9%82%D8%A7%D9%87%D8%B1%D8%A9_%D8%A7%D9%84%D8%A5%D8%AE%D8%A8%D8%A7%D8%B1%D9%8A%D8%A9.png" tvg-id="AlQaheraNews.eg" group-title="Egypt",Al Qahera News',
    'https://bcovlive-a.akamaihd.net/d30cbb3350af4cb7a6e05b9eb1bfd850/eu-west-1/6057955906001/playlist.m3u8',
    '',
    '#EXTINF:-1 tvg-name="Alhayat TV" tvg-logo="https://upload.wikimedia.org/wikipedia/commons/8/8c/Al-Hayat_Media_Center_Logo_%28variant_2%29.svg" tvg-id="AlhayatTV.eg" group-title="Egypt",Alhayat TV',
    'https://cdn3.wowza.com/5/OE5HREpIcEkySlNT/alhayat-live/ngrp:livestream_all/playlist.m3u8',
    '',
    '#EXTINF:-1 tvg-name="Coptic TV" tvg-logo="https://upload.wikimedia.org/wikipedia/en/4/4c/Coptic_news.jpg" tvg-id="CopticTV.eg" group-title="Egypt",Coptic TV',
    'https://5aafcc5de91f1.streamlock.net/ctvchannel.tv/ctv.smil/playlist.m3u8',
    '',
    '#EXTINF:-1 tvg-name="Huda TV" tvg-logo="https://upload.wikimedia.org/wikipedia/commons/5/58/Logo_huda_%D8%AD%D8%AC%D9%85_%D9%83%D8%A8%D9%8A%D8%B1.gif" tvg-id="HudaTV.eg" group-title="Egypt",Huda TV',
    'https://cdn.bestream.io:19360/elfaro1/elfaro1.m3u8',
    '',
    '#EXTINF:-1 tvg-name="Koogi TV" tvg-logo="" tvg-id="KoogiTV.eg" group-title="Egypt",Koogi TV',
    'https://5d658d7e9f562.streamlock.net/koogi.tv/koogi.smil/playlist.m3u8',
    '',
    '#EXTINF:-1 tvg-name="MBC Masr 1" tvg-logo="https://upload.wikimedia.org/wikipedia/commons/7/7c/MBC_Masr_Logo.png" tvg-id="MBCMasr1.eg" group-title="Egypt",MBC Masr 1',
    'https://mbc1-enc.edgenextcdn.net/out/v1/d5036cabf11e45bf9d0db410ca135c18/index.m3u8',
    '',
    '#EXTINF:-1 tvg-name="MBC Masr 2" tvg-logo="https://upload.wikimedia.org/wikipedia/commons/5/53/MBC_Masr_2_Logo.svg" tvg-id="MBCMasr2.eg" group-title="Egypt",MBC Masr 2',
    'https://shls-masr2-ak.akamaized.net/out/v1/f683685242b549f48ea8a5171e3e993a/index.m3u8',
    '',
    '#EXTINF:-1 tvg-name="Rotana Cinema" tvg-logo="https://upload.wikimedia.org/wikipedia/commons/9/92/Rotana_Cinema_Egy.png" tvg-id="RotanaCinema.eg" group-title="Egypt",Rotana Cinema',
    'https://rotana.hibridcdn.net/rotana/cinemamasr_net-7Y83PP5adWixDF93/playlist.m3u8',
    '',
    '#EXTINF:-1 tvg-name="Watan TV" tvg-logo="" tvg-id="WatanTV.eg" group-title="Egypt",Watan TV',
    'https://rp.tactivemedia.com/watantv_source/live/playlist.m3u8',
    ''
]

with open('m3u/egypt.m3u', 'w', encoding='utf-8') as f:
    f.write(header)
    f.write('\n'.join(cdn_channels) + '\n')
    f.write('\n'.join(egypt_channels) + '\n')

print(f"Extracted {len(egypt_channels)//3} Egyptian channels from live.m3u to egypt.m3u")
