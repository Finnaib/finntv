import re
from collections import Counter

def parse_m3u(file_path):
    header = "#EXTM3U"
    channels = []
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            lines = f.readlines()
            if lines and lines[0].startswith("#EXTM3U"):
                header = lines[0].strip()
    except:
        return header, []

    current_channel = {}
    for line in lines:
        line = line.strip()
        if line.startswith('#EXTINF:'):
            current_channel['metadata'] = line
            match = re.search(r'group-title="([^"]+)"', line)
            current_channel['group'] = match.group(1) if match else "Uncategorized"
            
            # Extract name for analysis
            name_match = re.search(r'tvg-name="([^"]+)"', line)
            if not name_match:
                 name_match = re.search(r',([^,]+)$', line)
            current_channel['name'] = name_match.group(1) if name_match else ""
            
        elif line and not line.startswith('#'):
            current_channel['url'] = line
            if 'metadata' in current_channel:
                channels.append(current_channel)
            current_channel = {}
    return header, channels

def infer_category(channels_in_group):
    # Analyze text of all channels in this group
    text = " ".join([ch['name'].lower() for ch in channels_in_group])
    
    # Keywords
    if any(x in text for x in ['tamil', 'telugu', 'malayalam', 'hindi', 'kannada', 'punjabi', 'bollywood', 'indian', 'kerala', 'chennai', 'mumbai']):
        return "Indian Movies"
    if any(x in text for x in ['arabic', 'egypt', 'lebanon', 'syria', 'dubai', 'ksa', 'kuwait']):
        return "Arabic Movies"
    if any(x in text for x in ['usa', 'hollywood', 'netflix', 'hbo', 'disney', 'marvel']):
        return "English Movies"
    if any(x in text for x in ['french', 'france', 'paris']):
        return "French Movies"
        
    return None

def main():
    input_file = "vod.m3u"
    print(f"Processing {input_file}...")
    header, channels = parse_m3u(input_file)
    
    # Group channels by their CURRENT group
    grouped_channels = {}
    for ch in channels:
        g = ch['group']
        if g not in grouped_channels:
            grouped_channels[g] = []
        grouped_channels[g].append(ch)
        
    final_channels = []
    
    print("Inferring Categories...")
    for old_group, group_chans in grouped_channels.items():
        new_name = infer_category(group_chans)
        
        if new_name:
            print(f"  Renaming '{old_group}' -> '{new_name}' ({len(group_chans)} channels)")
            final_group = new_name
        else:
            print(f"  Keeping '{old_group}' (Could not infer region)")
            final_group = old_group
            
        # Update metadata
        for ch in group_chans:
            meta = ch['metadata']
            if 'group-title="' in meta:
                meta = re.sub(r'group-title="[^"]+"', f'group-title="{final_group}"', meta)
            else:
                 meta += f' group-title="{final_group}"'
            ch['metadata'] = meta
            
        final_channels.extend(group_chans)
            
    # Sort again by new group name
    final_channels.sort(key=lambda x: (x['metadata'], x['url'])) # Simple sort
    
    # Write back
    with open(input_file, 'w', encoding='utf-8') as f:
        f.write(header + '\n')
        curr = None
        for ch in final_channels:
            # Extract new group for headers
            match = re.search(r'group-title="([^"]+)"', ch['metadata'])
            grp = match.group(1) if match else "Uncategorized"
            
            if grp != curr:
                f.write('\n')
                f.write(f'##### [{grp}] #####\n')
                f.write('\n')
                curr = grp
                
            f.write(ch['metadata'] + '\n')
            f.write(ch['url'] + '\n')
            f.write('\n')
            
    print("Done.")

if __name__ == "__main__":
    main()
