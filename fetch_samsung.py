import os
import urllib.request

def parse_m3u_from_url(url):
    channels = []
    print(f"Fetching {url}...")
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req) as response:
            lines = response.read().decode('utf-8').splitlines()
    except Exception as e:
        print(f"Failed to fetch samsung m3u: {e}")
        return channels

    current_extinf = None
    for line in lines:
        line = line.strip()
        if line.startswith('#EXTINF:'):
            current_extinf = line
        elif line and not line.startswith('#'):
            if current_extinf:
                channels.append({
                    'extinf': current_extinf,
                    'url': line
                })
                current_extinf = None
    return channels

def append_to_m3u(file_path, channels):
    if not channels:
        return
    
    existing_urls = set()
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#'):
                    existing_urls.add(line)
    
    with open(file_path, 'a', encoding='utf-8') as f:
        if not os.path.exists(file_path) or os.path.getsize(file_path) == 0:
            f.write('#EXTM3U\n')
            
        f.write("\n" + "#" * 80 + "\n")
        f.write(f"# EXTERNAL: SAMSUNG TV PLUS\n")
        f.write("#" * 80 + "\n\n")

        for ch in channels:
            if ch['url'] not in existing_urls:
                extinf = ch['extinf']
                f.write(f"{extinf}\n{ch['url']}\n\n")

def main():
    url = 'https://raw.githubusercontent.com/Paradise-91/ParaTV/refs/heads/main/playlists/samsungtvplus/main/samsungtvplus.m3u'
    channels = parse_m3u_from_url(url)

    if not channels:
        return

    south_korea = []
    india = []
    spain = []
    sports = []
    other_categories = []

    for ch in channels:
        extinf = ch['extinf']
        
        group_title = ''
        if 'group-title="' in extinf:
            group_title = extinf.split('group-title="')[1].split('"')[0].lower()
        
        category = ''
        name_part = extinf.split(',')[-1] if ',' in extinf else ''
        if '[' in name_part and ']' in name_part:
            category = name_part.split('[')[1].split(']')[0].lower()
        
        added = False
        if 'south korea' in group_title or 'korea' in group_title:
            south_korea.append(ch)
            added = True
        elif 'india' in group_title:
            india.append(ch)
            added = True
        elif 'spain' in group_title:
            spain.append(ch)
            added = True
            
        if 'sport' in category or 'deporte' in category or 'sports' in group_title:
            sports.append(ch)
            added = True
            
        if not added:
            other_categories.append(ch)

    print(f"Found {len(south_korea)} South Korea channels")
    print(f"Found {len(india)} India channels")
    print(f"Found {len(spain)} Spain channels")
    print(f"Found {len(sports)} Sports channels")
    print(f"Found {len(other_categories)} other channels")

    # Assuming this script is run from the root finntv directory
    append_to_m3u('m3u/asia.m3u', south_korea)
    append_to_m3u('m3u/india.m3u', india)
    append_to_m3u('m3u/spanish.m3u', spain)
    append_to_m3u('m3u/sport.m3u', sports)
    append_to_m3u('m3u/world.m3u', other_categories)

    print("Samsung TV Plus channels added successfully!")

if __name__ == '__main__':
    main()
