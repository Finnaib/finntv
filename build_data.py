import os
import json
import re
import zlib
import time

def crc32_id(string):
    """Generates a positive integer ID from a string using CRC32."""
    return str(zlib.crc32(string.encode('utf-8')) & 0xffffffff)

def parse_m3u(filepath, stream_type='live', id_map=None):
    """
    Parses an M3U file and returns:
    1. List of streams (optimized)
    2. Dictionary of categories {name: id}
    """
    streams = []
    categories = {} # name -> id
    
    if id_map is None: id_map = {}

    if not os.path.exists(filepath):
        return streams, categories

    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()

    current_entry = {}
    
    for line in lines:
        line = line.strip()
        if not line: continue
        
        if line.startswith("#EXTINF"):
            current_entry = {}
            
            # 1. Extract Name
            try:
                name_part = line.rsplit(',', 1)[1].strip()
                current_entry['name'] = name_part
            except:
                current_entry['name'] = "Unknown"

            # 2. Extract Logo
            logo_match = re.search(r'tvg-logo=["\']([^"\']*)["\']', line)
            current_entry['stream_icon'] = logo_match.group(1) if logo_match else ""

            # 3. Extract Group (Category)
            group_match = re.search(r'group-title="([^"]+)"', line)
            group_name = group_match.group(1) if group_match else "Uncategorized"
            current_entry['group_title'] = group_name

            # 4. Generate Category ID
            failed_safe_group = f"{stream_type}_{group_name}"
            cat_id = crc32_id(failed_safe_group)
            
            if group_name not in categories:
                categories[group_name] = cat_id
            
            current_entry['category_id'] = cat_id

        elif not line.startswith("#"):
            if current_entry:
                url = line
                current_entry['direct_source'] = url
                
                # Extract Stream ID / Num from URL
                try:
                    filename = url.split('/')[-1]
                    stream_id = filename.rsplit('.', 1)[0]
                    current_entry['num'] = stream_id
                    current_entry['stream_id'] = stream_id
                except:
                    current_entry['num'] = crc32_id(url)
                    current_entry['stream_id'] = current_entry['num']

                # Extension
                try:
                    current_entry['container_extension'] = filename.rsplit('.', 1)[1]
                except:
                     current_entry['container_extension'] = "ts"
                
                # Type Specifics
                if stream_type == 'live':
                     current_entry['stream_type'] = 'live'
                     current_entry['epg_channel_id'] = ""
                elif stream_type == 'movie':
                     current_entry['stream_type'] = 'movie'
                     current_entry['rating'] = "5.0"
                     current_entry['added'] = str(int(time.time()))
                     if 'container_extension' not in current_entry:
                         current_entry['container_extension'] = "mp4"
                elif stream_type == 'series':
                     current_entry['cover'] = current_entry.get('stream_icon', '')
                     current_entry['series_id'] = current_entry['num']
                     
                # POPULATE ID MAP while we have full data
                cid = current_entry['num']
                id_map[str(cid)] = url
                id_map[f"{stream_type}_{cid}"] = url

                # Optimization: Strip fields for data.json
                exclude = ['direct_source', 'group_title', 'uniq_id']
                entry_copy = {k:v for k,v in current_entry.items() if k not in exclude}
                streams.append(entry_copy)
                current_entry = {} 

    return streams, categories

def main():
    print("Building Data Cache (Python Improved)...")
    base_dir = "m3u"
    
    data = {
        'live_streams': [], 'live_categories': [],
        'vod_streams': [], 'vod_categories': [],
        'series': [], 'series_categories': []
    }
    id_map = {}

    def format_categories(cat_dict):
        return [{"category_id": cid, "category_name": name, "parent_id": 0} 
                for name, cid in cat_dict.items()]

    # 1. LIVE
    print("Parsing Live...")
    live_path = os.path.join(base_dir, "live.m3u")
    if os.path.exists(live_path):
        s, c = parse_m3u(live_path, 'live', id_map)
        data['live_streams'] = s
        data['live_categories'] = format_categories(c)

    # 2. VOD
    print("Parsing VOD...")
    vod_path = os.path.join(base_dir, "vod.m3u")
    if os.path.exists(vod_path):
        s, c = parse_m3u(vod_path, 'movie', id_map)
        data['vod_streams'] = s
        data['vod_categories'] = format_categories(c)

    # 3. SERIES
    print("Parsing Series...")
    series_path = os.path.join(base_dir, "series.m3u")
    if os.path.exists(series_path):
        s, c = parse_m3u(series_path, 'series', id_map)
        data['series'] = s
        data['series_categories'] = format_categories(c)

    # Stats
    print(f"Stats: Live:{len(data['live_streams'])} VOD:{len(data['vod_streams'])} Series:{len(data['series'])}")

    # Save
    os.makedirs("data", exist_ok=True)
    with open(os.path.join("data", "data.json"), 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, separators=(',', ':'))
    
    with open("id_map.json", 'w', encoding='utf-8') as f:
        json.dump(id_map, f, ensure_ascii=False, separators=(',', ':'))
    
    import shutil
    shutil.copy("id_map.json", os.path.join("data", "id_map.json"))
    print("Success.")

if __name__ == "__main__":
    main()
