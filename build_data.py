
import os
import json
import re

def parse_m3u(filepath):
    """
    Parses an M3U file and returns a list of stream dictionaries.
    Compatible with the PHP logic: extracts num (stream_id), name, logo, group, url.
    """
    streams = []
    if not os.path.exists(filepath):
        return streams

    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()

    current_entry = {}
    for line in lines:
        line = line.strip()
        if not line: continue
        if line.startswith("#EXTINF"):
            # Example: #EXTINF:-1 tvg-id="" tvg-name="Movie Name" tvg-logo="..." group-title="Action",Movie Name
            
            # Reset current entry
            current_entry = {}
            
            # Extract Name (last part after comma)
            try:
                name_part = line.rsplit(',', 1)[1].strip()
                current_entry['name'] = name_part
            except:
                current_entry['name'] = "Unknown"

            # Extract Logo
            logo_match = re.search(r'tvg-logo="([^"]+)"', line)
            current_entry['stream_icon'] = logo_match.group(1) if logo_match else ""

            # Extract Group
            group_match = re.search(r'group-title="([^"]+)"', line)
            current_entry['category_id'] = group_match.group(1) if group_match else "Uncategorized" # Using name as ID for simplicity like php
            
            # Extract tvg-name or tvg-id if needed, but primary Logic uses URL for ID in next step
            
        elif not line.startswith("#"):
            # This is the URL
            if current_entry:
                url = line
                current_entry['direct_source'] = url
                
                # Extract Stream ID / Num from URL
                # Formats: 
                # .../live/user/pass/123.ts
                # .../movie/user/pass/123.mp4
                try:
                    # Get the filename: 123.ts or 123.mp4
                    filename = url.split('/')[-1]
                    # Remove extension to get ID
                    stream_id = filename.rsplit('.', 1)[0]
                    current_entry['num'] = stream_id
                    current_entry['stream_id'] = stream_id
                except:
                    current_entry['num'] = "0"
                    current_entry['stream_id'] = "0"

                # Check container extension
                try:
                    current_entry['container_extension'] = filename.rsplit('.', 1)[1]
                except:
                     current_entry['container_extension'] = "ts"

                streams.append(current_entry)
                current_entry = {} # Reset

    return streams

def main():
    print("Building Data Cache (Python Version)...")
    
    base_dir = "m3u"
    
    # Initialize Data Structure
    data = {
        "live_streams": [],
        "vod_streams": [],
        "series": []
    }

    # 1. Parse LIVE streams (live.m3u + others)
    live_files = [f for f in os.listdir(base_dir) if f.endswith(".m3u") and f not in ["vod.m3u", "series.m3u", "series_test.m3u"]]
    
    print(f"Parsing {len(live_files)} Live M3U files...")
    for fname in live_files:
        path = os.path.join(base_dir, fname)
        streams = parse_m3u(path)
        data['live_streams'].extend(streams)

    # 2. Parse VOD
    print("Parsing VOD (vod.m3u)...")
    vod_path = os.path.join(base_dir, "vod.m3u")
    data['vod_streams'] = parse_m3u(vod_path)

    # 3. Parse Series
    print("Parsing Series (series.m3u)...")
    series_path = os.path.join(base_dir, "series.m3u")
    data['series'] = parse_m3u(series_path)

    # Stats
    print("Stats:")
    print(f"  Live:   {len(data['live_streams'])}")
    print(f"  VOD:    {len(data['vod_streams'])}")
    print(f"  Series: {len(data['series'])}")

    # Ensure output directory exists
    os.makedirs("data", exist_ok=True)

    # Save data.json
    json_path = os.path.join("data", "data.json")
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, separators=(',', ':')) # Minified
    
    size_mb = os.path.getsize(json_path) / (1024 * 1024)
    print(f"Saved data/data.json ({round(size_mb, 2)} MB)")

    # Build ID Map
    print("Building ID Map...")
    id_map = {}
    
    for s in data['live_streams']:
        if 'num' in s: id_map[str(s['num'])] = s.get('direct_source', '')
            
    for s in data['vod_streams']:
        if 'num' in s: id_map[str(s['num'])] = s.get('direct_source', '')
            
    for s in data['series']:
        if 'num' in s: id_map[str(s['num'])] = s.get('direct_source', '')

    # Save id_map.json (Root and Data dir for compatibility)
    id_map_path = "id_map.json"
    with open(id_map_path, 'w', encoding='utf-8') as f:
        json.dump(id_map, f, ensure_ascii=False, separators=(',', ':'))
        
    # Also save copy in data/ for good measure if needed logic expects it there
    shutil.copy(id_map_path, os.path.join("data", "id_map.json"))
        
    print("Saved id_map.json")
    print("Success.")

import shutil

if __name__ == "__main__":
    main()
