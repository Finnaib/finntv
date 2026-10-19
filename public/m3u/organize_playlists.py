import re
import os
import shutil

# Paths
# Get the directory where this script is located (m3u/)
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
# BASE_DIR is the same directory
BASE_DIR = SCRIPT_DIR

VOD_FILE = os.path.join(BASE_DIR, "vod.m3u")
SERIES_FILE = os.path.join(BASE_DIR, "series.m3u")

def parse_m3u(filepath):
    entries = []
    header = "#EXTM3U" # Default
    if not os.path.exists(filepath):
        return header, entries
        
    with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()
    
    if lines and lines[0].startswith('#EXTM3U'):
        header = lines[0].strip()
    
    current_entry = {}
    for line in lines:
        line = line.strip()
        if not line: continue
        if line.startswith('#EXTM3U'): continue
        if line.startswith('#####') or line.startswith('#EXTGRP'): continue # Skip headers/groups
        
        if line.startswith('#EXTINF:'):
            current_entry = {'extinf': line}
            
            # Extract attributes
            # Try to get group-title
            group_match = re.search(r'group-title="([^"]+)"', line)
            if group_match:
                current_entry['group'] = group_match.group(1)
            else:
                current_entry['group'] = "Uncategorized"
                
            # Extract Title (last part after comma)
            try:
                current_entry['title'] = line.rsplit(',', 1)[1].strip()
            except:
                current_entry['title'] = "Unknown"
                
        elif not line.startswith('#'):
            # This is the URL
            if current_entry:
                current_entry['url'] = line
                entries.append(current_entry)
                current_entry = {} # Reset
            
    return header, entries

def classify_entry(entry):
    url = entry.get('url', '')
    title = entry.get('title', '')
    
    # Rule 1: Explicit paths
    if '/series/' in url: return 'SERIES'
    if '/movie/' in url: return 'VOD'
    
    # Rule 2: Title Heuristics
    if re.search(r'(?i)S\d+\s*E\d+|Season\s*\d+|Temporada\s*\d+|Capitulo\s*\d+|Episode\s*\d+', title):
        return 'SERIES'
        
    # Rule 3: Playlist files acting as Series containers
    is_playlist = re.search(r'(?i)\.(m3u|m3u8)$', url)
    if is_playlist:
        return 'SERIES'
        
    # Rule 4: File extension
    if re.search(r'(?i)\.(mp4|mkv|avi|mov|flv|wmv)$', url):
        return 'VOD'
        
    return 'SERIES' 

def save_m3u(entries, filepath, header="#EXTM3U"):
    # Sort by Group then Title
    entries.sort(key=lambda x: (x.get('group', 'Uncategorized'), x.get('title', '')))
    
    current_group = None
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(f"{header}\n")
        
        for entry in entries:
            group = entry.get('group', 'Uncategorized')
            
            # Add visual separator for new groups
            if group != current_group:
                f.write(f"\n##### [{group}] #####\n\n")
                current_group = group
            
            f.write(f"{entry['extinf']}\n")
            f.write(f"{entry['url']}\n")
            f.write("\n") # Add spacing between entries

def main():
    print("Reading playlists...")
    
    # 1. Special Handling for VOD & Series (Merge and Sort)
    print("Processing VOD and Series (Merging)...")
    _, vod_entries = parse_m3u(VOD_FILE)
    _, series_entries = parse_m3u(SERIES_FILE)
    
    all_vs_entries = vod_entries + series_entries
    
    new_vod = []
    new_series = []
    
    for entry in all_vs_entries:
        category = classify_entry(entry)
        if category == 'VOD':
            new_vod.append(entry)
        else:
            new_series.append(entry)
            
    print(f"  Classified: {len(new_vod)} Movies, {len(new_series)} Series.")
    
    # Backup VOD/Series
    if os.path.exists(VOD_FILE): shutil.copy(VOD_FILE, VOD_FILE + ".bak")
    if os.path.exists(SERIES_FILE): shutil.copy(SERIES_FILE, SERIES_FILE + ".bak")
    
    print("  Saving organized vod.m3u and series.m3u...")
    save_m3u(new_vod, VOD_FILE)
    save_m3u(new_series, SERIES_FILE)
    
    # 2. General Handling for All Other M3U Files (DISABLED)
    # The user wants to manually manage other playlists like india.m3u
    # So we skip reformatting them.
    """
    print("\nProcessing other playlists...")
    for filename in os.listdir(BASE_DIR):
        if not filename.lower().endswith(".m3u"):
            continue
            
        # Skip already processed special files
        if filename.lower() in ["vod.m3u", "series.m3u"]:
            continue
            
        filepath = os.path.join(BASE_DIR, filename)
        print(f"  Formatting {filename}...")
        
        # Read
        header_line, entries = parse_m3u(filepath)
        if not entries:
            print(f"    Skipping empty file: {filename}")
            continue
            
        print(f"    Found {len(entries)} entries.")
        
        # Backup
        shutil.copy(filepath, filepath + ".bak")
        
        # Save (This sorts and adds headers)
        save_m3u(entries, filepath, header=header_line)
        print(f"    Saved {filename}")
    """
        
    print("\nDone! VOD and Series organized. Other playlists left untouched.")

if __name__ == "__main__":
    main()
