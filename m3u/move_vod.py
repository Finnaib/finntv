import re
import glob
import os

def parse_m3u(file_path):
    channels = []
    header = "#EXTM3U"
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            lines = f.readlines()
            if lines and lines[0].startswith("#EXTM3U"):
                header = lines[0].strip()
    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return header, []

    current_channel = {}
    for line in lines:
        line = line.strip()
        if line.startswith('#EXTINF:'):
            current_channel['metadata'] = line
            match = re.search(r'group-title="([^"]+)"', line)
            if match:
                current_channel['group'] = match.group(1)
            else:
                current_channel['group'] = "Uncategorized"
        elif line and not line.startswith('#'):
            current_channel['url'] = line
            if 'metadata' in current_channel:
                channels.append(current_channel)
            current_channel = {}
    return header, channels

def is_vod_group(group_name):
    g_lower = group_name.lower()
    # Explicitly exclude beIN from VOD check
    if 'bein' in g_lower:
        return False
        
    keywords = ['movie', 'cinema', 'vod', 'film', '4k']
    for kw in keywords:
        if kw in g_lower:
            return True
    return False

def main():
    m3u_files = glob.glob("*.m3u")
    # Exclude script itself
    m3u_files = [f for f in m3u_files if not f.endswith(".py")]
    
    vod_channels = []
    
    # Files to process for extraction (excluding vod.m3u itself initially to avoid self-loop issues, though we will rewrite it)
    files_to_process = [f for f in m3u_files if f != 'vod.m3u']
    
    print("Scanning files for VOD content...")
    
    for input_file in files_to_process:
        print(f"Processing {input_file}...")
        header, channels = parse_m3u(input_file)
        
        kept_channels = []
        moved_count = 0
        
        for ch in channels:
            group = ch.get('group', '')
            if is_vod_group(group):
                vod_channels.append(ch)
                moved_count += 1
            else:
                kept_channels.append(ch)
        
        if moved_count > 0:
            print(f"  Moves {moved_count} channels to VOD list.")
            # Rewrite the file strictly without VOD channels
            with open(input_file, 'w', encoding='utf-8') as f:
                f.write(header + '\n')
                for ch in kept_channels:
                    f.write(ch['metadata'] + '\n')
                    f.write(ch['url'] + '\n')
                    f.write('\n')
        else:
            print("  No VOD channels found.")

    # Now handle vod.m3u inclusion (if it had existing content we want to keep it or just overwrite/append? 
    # User said "put in my vod.m3u only", implying it should contain ALL VOD.
    # Previous vod.m3u might have been empty. Let's read it to be safe, but mostly we are effectively rebuilding it.
    
    print(f"Total VOD channels collected: {len(vod_channels)}")
    
    if vod_channels:
        # Load existing vod.m3u to preserve anything that was already there? 
        # Actually user said "vod.m3u is empty now". So we can overwrite.
        
        with open('vod.m3u', 'w', encoding='utf-8') as f:
            f.write("#EXTM3U\n")
            for ch in vod_channels:
                f.write(ch['metadata'] + '\n')
                f.write(ch['url'] + '\n')
                f.write('\n')
        print("Updated vod.m3u successfully.")

if __name__ == "__main__":
    main()
