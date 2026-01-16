
import requests
import json
import urllib3
urllib3.disable_warnings()

LOGOS_URL = "https://iptv-org.github.io/api/logos.json"
CHECK_TERMS = ["animation", "movies", "cinema", "action", "amazon", "netflix", "series", "kids", "comedy", "drama"]

def check_matches():
    print("Downloading DB...")
    r = requests.get(LOGOS_URL, verify=False)
    data = r.json()
    
    print(f"DB size: {len(data)}")
    
    for term in CHECK_TERMS:
        print(f"\nScanning for '{term}'...")
        matches = [x for x in data if term in x.get('name', '').lower()]
        for m in matches[:3]: # Show top 3
            print(f"  Match: {m['name']} -> {m['logo']}")

if __name__ == "__main__":
    check_matches()
