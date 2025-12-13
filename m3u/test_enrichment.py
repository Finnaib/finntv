
import re

GENRE_MAP = {
    "animation": "https://EXAMPLE_LOGO.png"
}

def clean_name(name):
    clean = re.sub(r'\(.*?\)', '', name)
    clean = re.sub(r'\s+', ' ', clean)
    return clean.strip().lower()

def test():
    line = '#EXTINF:-1 group-title="Animation",Test Movie'
    print(f"Original: {line}")
    
    found_logo = None
    
    group_match = re.search(r'group-title="([^"]+)"', line)
    group_name = group_match.group(1) if group_match else ""
    print(f"Group: '{group_name}'")
    
    if group_name:
        cg = clean_name(group_name)
        print(f"Clean Group: '{cg}'")
        
        for genre, logo_url in GENRE_MAP.items():
            if genre in cg:
                found_logo = logo_url
                print(f"MATCH: {genre} -> {logo_url}")
                break
                
    if found_logo:
        if '#EXTINF:-1 ' in line:
            line = line.replace('#EXTINF:-1 ', f'#EXTINF:-1 tvg-logo="{found_logo}" ')
        else:
             # Fallback if no space?
             line = line.replace('#EXTINF:-1', f'#EXTINF:-1 tvg-logo="{found_logo}"')
             
        print(f"Modified: {line}")
    else:
        print("NO MATCH FOUND")

if __name__ == "__main__":
    test()
