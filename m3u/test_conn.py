import requests
import urllib3
urllib3.disable_warnings()

url = "http://mhav1.com:80/player_api.php?username=ht2990&password=742579548"

print(f"Connecting to {url}...")
try:
    r = requests.get(url, verify=False, stream=True, timeout=10)
    print(f"Status Code: {r.status_code}")
    try:
        print(r.json())
    except:
        print(r.text[:500])
        
except Exception as e:
    print(f"Error: {e}")
