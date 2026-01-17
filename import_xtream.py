import requests
import urllib3
import time

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

def main():
    print("=== Xtream Codes Importer (API Mode) ===")
    
    # Load credentials from config file
    import json
    import os
    
    config_path = "xtream_config.json"
    if not os.path.exists(config_path):
        print(f"Error: {config_path} not found!")
        return

    with open(config_path, 'r') as f:
        config = json.load(f)
        
    host = config.get("host", "")
    username = config.get("username", "")
    password = config.get("password", "")
    
    if not host or not username or not password:
        print("Error: Missing host, username, or password in config!")
        return
    
    base_api = f"{host}/player_api.php?username={username}&password={password}"
    
    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    }

    def get_category_map(action):
        for attempt in range(3):
            try:
                print(f"Fetching categories: {action} (Attempt {attempt+1})...")
                url = f"{base_api}&action={action}"
                r = requests.get(url, headers=headers, verify=False, timeout=60)
                if r.status_code != 200: 
                    time.sleep(2)
                    continue
                    
                data = r.json()
                # Map category_id -> category_name
                mapping = {}
                for cat in data:
                    cid = cat.get('category_id')
                    cname = cat.get('category_name', 'Unknown')
                    if cid:
                        mapping[cid] = cname
                return mapping
            except Exception as e:
                print(f"  Error fetching categories: {e}")
                time.sleep(2)
        
        return {}

    # Fetch maps first
    live_cats = get_category_map("get_live_categories")
    vod_cats = get_category_map("get_vod_categories")
    series_cats = get_category_map("get_series_categories")

    def fetch_and_save(action, filename, type_code, cat_map):
        print(f"\nFetching {filename} via {action}...")
        try:
            url = f"{base_api}&action={action}"
            r = requests.get(url, headers=headers, verify=False, timeout=60)
            r.raise_for_status()
            data = r.json()
            
            if not isinstance(data, list):
                print(f"Warning: Expected list but got {type(data)} for {filename}")
                return

            print(f"  Found {len(data)} items. Writing to file...")
            
            # Sort by category name then channel name for tidiness
            def sort_key(item):
                cid = item.get('category_id')
                cname = cat_map.get(cid, "Uncategorized")
                return (cname, item.get('name', ''))
            
            data.sort(key=sort_key)
            
            with open(f"m3u/{filename}", 'w', encoding='utf-8') as f:
                f.write("#EXTM3U\n")
                
                current_group = None
                
                for item in data:
                    name = item.get('name', 'Unknown')
                    logo = item.get('stream_icon', '')
                    
                    # Resolve Category Name
                    cat_id = item.get('category_id') # Some use category_id
                    
                    if cat_id in cat_map:
                        cat_name = cat_map[cat_id]
                    elif cat_id:
                        # Fallback to ID if name missing
                        cat_name = f"Category {cat_id}"
                    else:
                        cat_name = "All Movies" if type_code == "vod" else "Uncategorized"
                    
                    # Add Visual Headers for new groups
                    if cat_name != current_group:
                        f.write(f"\n##### [{cat_name}] #####\n\n")
                        current_group = cat_name
                    
                    stream_id = item.get('stream_id') or item.get('series_id')
                    container = item.get('container_extension', 'ts')
                    
                    final_url = ""
                    if type_code == "live":
                        final_url = f"{host}/live/{username}/{password}/{stream_id}.ts"
                    elif type_code == "vod":
                        final_url = f"{host}/movie/{username}/{password}/{stream_id}.{container}"
                    elif type_code == "series":
                        final_url = f"{host}/series/{username}/{password}/{stream_id}.{container}"
                        
                    meta = f'#EXTINF:-1 tvg-id="" tvg-name="{name}" tvg-logo="{logo}" group-title="{cat_name}",{name}'
                    f.write(meta + "\n")
                    f.write(final_url + "\n")
                    f.write("\n")
                    
            print(f"  Success! Saved {filename}")
            
        except Exception as e:
            print(f"  Error fetching {action}: {e}")

    # 1. LIVE
    fetch_and_save("get_live_streams", "live.m3u", "live", live_cats)
    
    # 2. VOD
    fetch_and_save("get_vod_streams", "vod.m3u", "vod", vod_cats)
    
    # 3. SERIES
    fetch_and_save("get_series", "series.m3u", "series", series_cats)
    
    print("\nAll imports finished.")

if __name__ == "__main__":
    main()
