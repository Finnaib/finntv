
import os
import json
import re
import zlib
import time

def crc32_id(string):
    """Generates a positive integer ID from a string using CRC32."""
    return str(zlib.crc32(string.encode('utf-8')) & 0xffffffff)

def parse_m3u_with_categories(filepath, stream_type='live'):
    """
    Parses an M3U file and returns:
    1. List of streams
    2. Dictionary of categories {name: id}
    """
    streams = []
    categories = {} # name -> id
    
    if not os.path.exists(filepath):
        return streams, categories

    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()

    current_entry = {}
    
    # Track existing stream IDs to prevent duplicates if needed
    
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

            # 2. Extract Logo (Robust Regex)
            logo_match = re.search(r'tvg-logo=["\']([^"\']*)["\']', line)
            current_entry['stream_icon'] = logo_match.group(1) if logo_match else ""

            # 3. Extract Group (Category)
            group_match = re.search(r'group-title="([^"]+)"', line)
            group_name = group_match.group(1) if group_match else "Uncategorized"
            current_entry['group_title'] = group_name # Temp storage

            # 4. Generate Category ID (Numeric)
            # Prefix with type to ensure unique IDs across types if needed, 
            # though players usually query types separately.
            failed_safe_group = f"{stream_type}_{group_name}"
            cat_id = crc32_id(failed_safe_group)
            
            # Add to categories dict
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
                    current_entry['num'] = crc32_id(url) # Fallback
                    current_entry['stream_id'] = current_entry['num']

                # Extension
                try:
                    current_entry['container_extension'] = filename.rsplit('.', 1)[1]
                except:
                     current_entry['container_extension'] = "ts"
                
                # Default Properties
                if stream_type == 'live':
                     current_entry['stream_type'] = 'live'
                     current_entry['epg_channel_id'] = ""
                     current_entry['tv_archive'] = 0
                     current_entry['tv_archive_duration'] = 0
                elif stream_type == 'movie':
                     current_entry['stream_type'] = 'movie'
                     current_entry['rating'] = "5.0"
                     current_entry['added'] = str(int(time.time()))
                     # DO NOT overwrite container_extension if already parsed
                     if 'container_extension' not in current_entry:
                         current_entry['container_extension'] = "mp4"
                elif stream_type == 'series':
                     current_entry['cover'] = current_entry.get('stream_icon', '')
                     current_entry['series_id'] = current_entry['num']
                     
                streams.append(current_entry)
                current_entry = {} 

    return streams, categories

def main():
    print("Building Data Cache (Python Improved)...")
    
    base_dir = "m3u"
    
    data = {
        "live_streams": [],
        "live_categories": [],
        "vod_streams": [],
        "vod_categories": [],
        "series": [],
        "series_categories": []
    }
    
    # --- Helper to format categories list ---
    def format_categories(cat_dict):
        result = []
        for name, cid in cat_dict.items():
            result.append({
                "category_id": cid,
                "category_name": name,
                "parent_id": 0
            })
        return result

    # 1. LIVE
    print("Parsing Live (Strict: live.m3u only)...")
    
    # STRICT MODE: Only load live.m3u (from Xtream import), ignore custom files like asia.m3u
    target_live = "live.m3u"
    live_path = os.path.join(base_dir, target_live)
    
    all_live_cats = {}
    
    if os.path.exists(live_path):
        streams, cats = parse_m3u_with_categories(live_path, 'live')
        data['live_streams'].extend(streams)
        all_live_cats.update(cats)
    else:
        print(f"Warning: {target_live} not found. Live streams will be empty.")
    
    data['live_categories'] = format_categories(all_live_cats)

    # 2. VOD
    print("Parsing VOD...")
    vod_path = os.path.join(base_dir, "vod.m3u")
    if os.path.exists(vod_path):
        streams, cats = parse_m3u_with_categories(vod_path, 'movie')
        data['vod_streams'] = streams
        data['vod_categories'] = format_categories(cats)

    # 3. SERIES
    print("Parsing Series...")
    series_path = os.path.join(base_dir, "series.m3u")
    if os.path.exists(series_path):
         streams, cats = parse_m3u_with_categories(series_path, 'series')
         data['series'] = streams
         data['series_categories'] = format_categories(cats)

    # Stats
    print("Stats:")
    print(f"  Live Streams: {len(data['live_streams'])} | Categories: {len(data['live_categories'])}")
    print(f"  VOD Streams:  {len(data['vod_streams'])} | Categories: {len(data['vod_categories'])}")
    print(f"  Series:       {len(data['series'])} | Categories: {len(data['series_categories'])}")

    # Save
    os.makedirs("data", exist_ok=True)
    json_path = os.path.join("data", "data.json")
    
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, separators=(',', ':'))
    
    size_mb = os.path.getsize(json_path) / (1024 * 1024)
    print(f"Saved data/data.json ({round(size_mb, 2)} MB)")

    # Build ID Map (Prefixed to prevent collisions)
    print("Building ID Map...")
    id_map = {}
    for s in data['live_streams']:
        if 'num' in s: id_map[f"live_{s['num']}"] = s.get('direct_source', '')
    for s in data['vod_streams']:
        if 'num' in s: id_map[f"movie_{s['num']}"] = s.get('direct_source', '')
    for s in data['series']:
        if 'num' in s: id_map[f"series_{s['num']}"] = s.get('direct_source', '')
            
    id_map_path = "id_map.json"
    with open(id_map_path, 'w', encoding='utf-8') as f:
        json.dump(id_map, f, ensure_ascii=False, separators=(',', ':'))
        
    import shutil
    shutil.copy(id_map_path, os.path.join("data", "id_map.json"))
        
    print("Success.")

if __name__ == "__main__":
    main()
