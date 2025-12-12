import requests
import urllib3
urllib3.disable_warnings()

base = "http://mhav1.com:80/player_api.php?username=ht2990&password=742579548"

actions = ["get_live_categories", "get_vod_categories"]

for action in actions:
    url = f"{base}&action={action}"
    print(f"Testing {action}...")
    try:
        r = requests.get(url, verify=False, timeout=15)
        print(f"Status: {r.status_code}")
        if r.status_code == 200:
            data = r.json()
            print(f"Count: {len(data)}")
            if len(data) > 0:
                print(f"Sample: {data[0]}")
    except Exception as e:
        print(f"Error: {e}")
