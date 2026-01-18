import requests
import json
import os

url = "http://localhost/player_api.php?username=finn&password=finn123&action=get_live_streams"
try:
    # Use 127.0.0.1 directly if localhost fails
    response = requests.get("http://127.0.0.1:80/player_api.php?username=finn&password=finn123&action=get_live_streams", timeout=10)
    print(f"Status Code: {response.status_code}")
    print(f"Size: {len(response.content)} bytes")
    
    data = response.json()
    print(f"Count: {len(data)}")
    if len(data) > 0:
        print("First Item Sample:")
        print(json.dumps(data[0], indent=2))
        
        # Check mandatory fields
        required = ['added', 'stream_id', 'num', 'category_id']
        missing = [f for f in required if f not in data[0]]
        if missing:
            print(f"MISSING FIELDS: {missing}")
        else:
            print("ALL MANDATORY FIELDS PRESENT")
except Exception as e:
    print(f"Error: {e}")
