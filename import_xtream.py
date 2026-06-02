import requests
import urllib3
import urllib.parse
import time
import json
import os
import base64
import re
import sys

# Fix Windows console Unicode errors (Arabic category names etc.)
try:
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')
except AttributeError:
    pass  # Python < 3.7

def safe(s):
    """Safely convert any string for printing on Windows console."""
    try:
        return str(s).encode(sys.stdout.encoding or 'utf-8', errors='replace').decode(sys.stdout.encoding or 'utf-8', errors='replace')
    except Exception:
        return repr(s)

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

def make_request(url, headers, use_proxy=False, timeout=120, allow_direct_fallback=True):
    """
    Fetch a URL, optionally through the Vercel proxy.
    If the proxy returns a 500 (e.g. Vercel timeout on large JSON), auto-falls back to direct.
    """
    if use_proxy:
        base_url = get_vercel_base_url()
        b64_url = base64.b64encode(url.encode('utf-8')).decode('utf-8')
        # URL-encode the base64 so = and + chars don't corrupt the query param
        encoded = urllib.parse.quote(b64_url, safe='')
        proxy_url = f"{base_url}/api/xtream_proxy.php?url={encoded}"
        r = requests.get(proxy_url, headers=headers, verify=False, timeout=timeout)
        if r.status_code != 200 and allow_direct_fallback:
            print(f"  -> Proxy returned {r.status_code}. Falling back to direct connection...")
            return requests.get(url, headers=headers, verify=False, timeout=timeout)
        return r
    else:
        return requests.get(url, headers=headers, verify=False, timeout=timeout)

def get_category_map(base_api, action, headers, use_proxy=False):
    for attempt in range(3):
        try:
            print(f"Fetching categories: {action} (Attempt {attempt+1})...")
            url = f"{base_api}&action={action}"
            r = make_request(url, headers=headers, use_proxy=use_proxy, timeout=120)
            if r.status_code != 200:
                print(f"  -> HTTP {r.status_code} for {action}")
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

def write_items(items, filename, type_code, cat_map, host, username, password):
    """Write a list of stream items to an M3U file."""
    if not items:
        return 0
    items.sort(key=lambda item: (cat_map.get(item.get('category_id'), 'Uncategorized'), item.get('name', '')))
    written = 0
    with open(f"m3u/{filename}", 'a', encoding='utf-8') as f:
        current_group = None
        for item in items:
            name       = item.get('name', 'Unknown')
            logo       = item.get('stream_icon') or item.get('icon_url') or item.get('icon') or item.get('cover') or ''
            cat_id     = item.get('category_id')
            cat_name   = cat_map.get(cat_id) or (f"Category {cat_id}" if cat_id else ("All Movies" if type_code == "vod" else "Uncategorized"))
            stream_id  = item.get('stream_id') or item.get('series_id')
            container  = item.get('container_extension', 'ts')

            if cat_name != current_group:
                f.write(f"\n##### [{cat_name}] #####\n\n")
                current_group = cat_name

            if type_code == "live":
                final_url = f"{host}/live/{username}/{password}/{stream_id}.ts"
            elif type_code == "vod":
                final_url = f"{host}/movie/{username}/{password}/{stream_id}.{container}"
            else:
                final_url = f"{host}/series/{username}/{password}/{stream_id}.{container}"

            f.write(f'#EXTINF:-1 tvg-id="" tvg-name="{name}" tvg-logo="{logo}" group-title="{cat_name}",{name}\n')
            f.write(final_url + "\n\n")
            written += 1
    return written


def fetch_and_append(base_api, host, username, password, action, filename, type_code, cat_map, headers, use_proxy=False):
    """
    Fetch streams from Xtream API and write to M3U file.
    Strategy:
      1. Try full fetch via proxy (fast, one request)
      2. If proxy 500 (too large), try direct connection
      3. If direct also blocked (ISP), fall back to per-category fetching through proxy
         - Each category is small enough to pass through Vercel
    """
    url = f"{base_api}&action={action}"

    # ── Attempt 1: Full fetch ────────────────────────────────────
    print(f"\nFetching {filename} via {action}...")
    full_data = None
    for attempt in range(2):
        try:
            r = make_request(url, headers=headers, use_proxy=use_proxy, timeout=180, allow_direct_fallback=True)
            if r.status_code == 200 and r.content:
                data = r.json()
                if isinstance(data, list) and len(data) > 0:
                    if len(data) == 1 and data[0].get('name') == 'شاهد الخطآ':
                        print("  Warning: Account error detected. Skipping.")
                        return
                    full_data = data
                    break
                elif isinstance(data, dict):
                    print(f"  Warning: Got dict response (may be error): {list(data.keys())[:3]}")
                    break
        except Exception as e:
            print(f"  Full fetch attempt {attempt+1} failed: {e}")
        time.sleep(3)

    if full_data is not None:
        print(f"  Found {len(full_data)} items. Writing to file...")
        n = write_items(full_data, filename, type_code, cat_map, host, username, password)
        print(f"  Success! Wrote {n} entries to {filename}")
        return

    # ── Attempt 2: Per-category fallback ─────────────────────────
    if not cat_map:
        print(f"  No categories available and full fetch failed. Skipping {filename}.")
        return

    print(f"  Full fetch failed. Switching to per-category mode ({len(cat_map)} categories)...")
    total_written = 0
    failed_cats   = 0
    empty_cats    = 0
    first_cat     = True  # debug first request

    for cat_id, cat_name in cat_map.items():
        cat_url = f"{url}&category_id={cat_id}"
        for attempt in range(2):
            try:
                r = make_request(cat_url, headers=headers, use_proxy=use_proxy,
                                 timeout=60, allow_direct_fallback=True)
                if first_cat:
                    print(f"  [debug] First cat request -> HTTP {r.status_code}, {len(r.content)} bytes")
                    first_cat = False
                if r.status_code == 200 and r.content:
                    cat_data = r.json()
                    if isinstance(cat_data, list) and cat_data:
                        n = write_items(cat_data, filename, type_code, cat_map, host, username, password)
                        total_written += n
                        print(f"  [{safe(cat_name)}]: {n} items")
                    elif isinstance(cat_data, list):
                        empty_cats += 1  # provider returned [] for this category
                    break
            except Exception as e:
                if attempt == 1:
                    failed_cats += 1
                    print(f"  [{safe(cat_name)}]: failed ({safe(e)})")
                else:
                    time.sleep(2)
        time.sleep(0.3)  # be nice to the server

    if total_written > 0:
        print(f"\n  Per-category complete: {total_written} total entries written to {filename} ({failed_cats} categories failed)")
    else:
        print(f"\n  Failed to fetch any data for {filename}. Check your connection or credentials.")



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
