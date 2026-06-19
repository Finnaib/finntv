import requests
import json
import os
import sys

def safe(s):
    try:
        return str(s).encode(sys.stdout.encoding or 'utf-8', errors='replace').decode(sys.stdout.encoding or 'utf-8', errors='replace')
    except Exception:
        return repr(s)

def main():
    print("=== Fetching Sports & Facebook Channels from Xtream ===")
    
    config_path = "xtream_config.json"
    if not os.path.exists(config_path):
        print(f"Error: {config_path} not found!")
        return

    with open(config_path, 'r') as f:
        config = json.load(f)
        
    if isinstance(config, dict):
        credentials = [config]
    elif isinstance(config, list):
        credentials = config

    headers = {'User-Agent': 'Mozilla/5.0'}

    for cred in credentials:
        host = cred.get("host", "").rstrip('/')
        username = cred.get("username", "")
        password = cred.get("password", "")
        
        base_api = f"{host}/player_api.php?username={username}&password={password}"
        
        # 1. Fetch Categories
        print("Fetching live categories...")
        r_cats = requests.get(f"{base_api}&action=get_live_categories", headers=headers, verify=False, timeout=30)
        if r_cats.status_code != 200:
            print("Failed to fetch categories.")
            continue
            
        cat_map = {}
        target_cat_ids = set()
        
        for cat in r_cats.json():
            cid = str(cat.get('category_id', ''))
            cname = cat.get('category_name', '')
            cat_map[cid] = cname
            
            # Match keywords in category name
            cname_lower = cname.lower()
            keywords = [
                'sport', 'fifa', 'facebook', 'bein', 'wwe', 'ufc', 'boxing', 'nba', 'nfl', 'mlb', 'nhl', 
                'cricket', 'tennis', 'golf', 'rugby', 'racing', 'f1', 'sky', 'espn', 'fox', 'supersport', 
                'bt', 'sn', 'tsn', 'optus', 'dazn', 'football', 'soccer', 'wrestling'
            ]
            
            if any(k in cname_lower for k in keywords):
                target_cat_ids.add(cid)

        # 2. Fetch Live Streams
        print("Fetching live streams...")
        r_streams = requests.get(f"{base_api}&action=get_live_streams", headers=headers, verify=False, timeout=60)
        if r_streams.status_code != 200:
            print("Failed to fetch live streams.")
            continue

        streams = r_streams.json()
        target_streams = []
        
        for s in streams:
            cid = str(s.get('category_id', ''))
            name = s.get('name', '').lower()
            
            if cid in target_cat_ids or any(k in name for k in keywords):
                target_streams.append(s)

        # 3. Write to sport.m3u
        if target_streams:
            m3u_path = "m3u/sport.m3u"
            print(f"Found {len(target_streams)} matching channels. Appending to {m3u_path}...")
            
            with open(m3u_path, "a", encoding="utf-8") as f:
                f.write("\n\n##### [IMPORTED FROM XTREAM] #####\n\n")
                
                current_group = None
                for item in target_streams:
                    name = item.get('name', 'Unknown')
                    logo = item.get('stream_icon', '')
                    cid = str(item.get('category_id', ''))
                    cat_name = cat_map.get(cid, 'Sports')
                    stream_id = item.get('stream_id')
                    
                    if cat_name != current_group:
                        f.write(f"\n##### [{cat_name}] #####\n\n")
                        current_group = cat_name
                        
                    final_url = f"{host}/live/{username}/{password}/{stream_id}.ts"
                    f.write(f'#EXTINF:-1 tvg-id="" tvg-name="{name}" tvg-logo="{logo}" group-title="{cat_name}",{name}\n')
                    f.write(final_url + "\n\n")
            
            print("Successfully updated sport.m3u!")
        else:
            print("No matching sports or facebook channels found.")

if __name__ == "__main__":
    import urllib3
    urllib3.disable_warnings()
    main()
