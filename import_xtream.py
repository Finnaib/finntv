import requests
import urllib3
import time
import json
import os
import base64
import re

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

def get_vercel_base_url():
    base_url = "https://finntv.vercel.app"
    if os.path.exists("config.php"):
        try:
            with open("config.php", "r", encoding="utf-8") as f:
                content = f.read()
                m = re.search(r"'base_url'\s*=>\s*'([^']+)'", content)
                if m:
                    base_url = m.group(1)
        except Exception:
            pass
    return base_url.rstrip('/')

def make_request(url, headers, use_proxy=False, timeout=120):
    if use_proxy:
        base_url = get_vercel_base_url()
        b64_url = base64.b64encode(url.encode('utf-8')).decode('utf-8')
        # Use dedicated xtream_proxy.php for API JSON calls (stream_proxy.php is for video streams)
        proxy_url = f"{base_url}/api/xtream_proxy.php?url={b64_url}"
        return requests.get(proxy_url, headers=headers, verify=False, timeout=timeout)
    else:
        return requests.get(url, headers=headers, verify=False, timeout=timeout)

def get_category_map(base_api, action, headers, use_proxy=False):
    for attempt in range(3):
        try:
            print(f"Fetching categories: {action} (Attempt {attempt+1})...")
            url = f"{base_api}&action={action}"
            r = make_request(url, headers=headers, use_proxy=use_proxy, timeout=120)
            if r.status_code != 200: 
                time.sleep(5)
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
            time.sleep(5)
    
    return {}

def fetch_and_append(base_api, host, username, password, action, filename, type_code, cat_map, headers, use_proxy=False):
    for attempt in range(3):
        print(f"\nFetching {filename} via {action} (Attempt {attempt+1})...")
        try:
            url = f"{base_api}&action={action}"
            r = make_request(url, headers=headers, use_proxy=use_proxy, timeout=120)
            r.raise_for_status()
            data = r.json()
            
            if not isinstance(data, list):
                if isinstance(data, dict):
                    # sometimes empty streams return dict like {"error": ...}
                    print(f"Warning: Expected list but got dict for {filename}. This may be an error stream.")
                    return
                print(f"Warning: Expected list but got {type(data)} for {filename}.")
                return
            elif len(data) == 1 and data[0].get('name') == 'شاهد الخطآ':
                 print(f"Warning: Account error/expired stream detected. Skipping this credential.")
                 return
            
            print(f"  Found {len(data)} items. Writing to file...")
            
            # Sort by category name then channel name for tidiness
            def sort_key(item):
                cid = item.get('category_id')
                cname = cat_map.get(cid, "Uncategorized")
                return (cname, item.get('name', ''))
            
            data.sort(key=sort_key)
            
            with open(f"m3u/{filename}", 'a', encoding='utf-8') as f:
                current_group = None
                
                for item in data:
                    name = item.get('name', 'Unknown')
                    
                    # Improved Logo Extraction
                    logo = item.get('stream_icon') or item.get('icon_url') or item.get('icon') or item.get('cover') or ""
                    
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
                    
            print(f"  Success! Appended to {filename}")
            return # Success, exit retry loop
            
        except Exception as e:
            print(f"  Error fetching {action} (Attempt {attempt+1}): {e}")
            if attempt < 2:
                time.sleep(5)
            else:
                print(f"  Failed to fetch {filename} after 3 attempts.")

def main():
    print("=== Xtream Codes Importer (API Mode) ===")
    
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
    else:
        print("Error: config must be a dictionary or list of dictionaries.")
        return

    headers = {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    }

    auth_success = False

    for i, cred in enumerate(credentials):
        host = cred.get("host", "").rstrip('/')
        username = cred.get("username", "")
        password = cred.get("password", "")
        
        if not host or not username or not password:
            print(f"Warning: Missing host, username, or password in config item {i}!")
            continue
            
        print(f"\n--- Processing credentials for: {username}@{host} ---")
        base_api = f"{host}/player_api.php?username={username}&password={password}"
        
        # Check connectivity and determine if proxy fallback is needed
        use_proxy = False
        print("Checking connection to provider...")
        try:
            r = requests.get(base_api, headers=headers, verify=False, timeout=10)
            if "tedata" in r.url or "megaplusredirection" in r.url or "tedata" in r.text or "megaplusredirection" in r.text:
                print("-> ISP redirection detected (Telecom Egypt). Activating Vercel Proxy fallback.")
                use_proxy = True
            elif r.status_code != 200:
                print(f"-> Direct connection status: {r.status_code}. Activating Vercel Proxy fallback.")
                use_proxy = True
            else:
                try:
                    r.json()
                except json.JSONDecodeError:
                    print("-> Direct connection returned non-JSON. Activating Vercel Proxy fallback.")
                    use_proxy = True
        except Exception as e:
            print(f"-> Direct connection failed: {e}. Activating Vercel Proxy fallback.")
            use_proxy = True

        if use_proxy:
            print(f"Routing traffic via Vercel proxy: {get_vercel_base_url()}/api/stream_proxy.php")
        else:
            print("Direct connection OK.")
        
        # Check auth
        try:
            r = make_request(base_api, headers=headers, use_proxy=use_proxy, timeout=30)
            if r.status_code != 200:
                print(f"Auth failed for {username}. Status code: {r.status_code}")
                continue
            
            try:
                res_json = r.json()
            except json.JSONDecodeError:
                print(f"Invalid API response from {host}")
                continue
                
            if "user_info" in res_json and "auth" in res_json["user_info"] and str(res_json["user_info"]["auth"]) == "0":
                print(f"Auth failed for {username}. Incorrect credentials.")
                continue
            if "user_info" in res_json and res_json["user_info"].get("status") != "Active":
                print(f"Account for {username} is not Active (status: {res_json['user_info'].get('status')})")
                continue
            
            if not auth_success:
                # Reset files first
                for fn in ["live.m3u", "vod.m3u", "series.m3u"]:
                    with open(f"m3u/{fn}", "w", encoding='utf-8') as file:
                        file.write("#EXTM3U\n")
                auth_success = True
                
        except Exception as e:
            print(f"Could not connect to {host} for {username}: {e}")
            continue

        # Fetch maps first
        live_cats = get_category_map(base_api, "get_live_categories", headers, use_proxy=use_proxy)
        time.sleep(1)
        vod_cats = get_category_map(base_api, "get_vod_categories", headers, use_proxy=use_proxy)
        time.sleep(1)
        series_cats = get_category_map(base_api, "get_series_categories", headers, use_proxy=use_proxy)
        time.sleep(1)

        # 1. LIVE
        fetch_and_append(base_api, host, username, password, "get_live_streams", "live.m3u", "live", live_cats, headers, use_proxy=use_proxy)
        
        # 2. VOD
        fetch_and_append(base_api, host, username, password, "get_vod_streams", "vod.m3u", "vod", vod_cats, headers, use_proxy=use_proxy)
        
        # 3. SERIES
        fetch_and_append(base_api, host, username, password, "get_series", "series.m3u", "series", series_cats, headers, use_proxy=use_proxy)
        
    print("\nAll imports finished.")

if __name__ == "__main__":
    main()
