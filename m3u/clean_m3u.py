import re
import glob
import os
from collections import Counter

def parse_m3u(file_path):
    channels = []
    with open(file_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
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

    return lines[0], channels

def clean_group_name(name):
    if " - " in name:
        parts = name.split(" - ")
        if re.match(r'^[a-zA-Z0-9\s]+$', parts[0]):
             return parts[0].strip()
    
    match = re.match(r'^([a-zA-Z0-9\s\-_\[\]\(\)\+]+)', name)
    if match:
        english_part = match.group(1).strip()
        english_part = re.sub(r'[\s\-]+$', '', english_part)
        
        if "Arabic SPORTS" in english_part: return "Arabic Sports"
        if "KSA AD Sport" in english_part: return "KSA AD Sport"
        if "ON  Sport" in english_part: return "ON Sport"
        
        if len(english_part) > 2:
            return english_part
            
    return name

def main():
    m3u_files = glob.glob("*.m3u")
    # Avoid processing script itself or output files if not m3u
    m3u_files = [f for f in m3u_files if f != "clean_m3u.py" and not f.endswith(".py")]
    
    print("Phase 1: Analyzing global group usage...")
    global_group_counts = Counter()
    
    # Store parsed data in memory
    all_files_data = {} 

    for input_file in m3u_files:
        try:
            header, channels = parse_m3u(input_file)
            cleaned_channels = []
            for ch in channels:
                raw_group = ch.get('group', 'Uncategorized')
                clean_name = clean_group_name(raw_group)
                
                # Manual overrides
                if "beIN" in clean_name and "Low" in clean_name: clean_name = "beIN Sports Low"
                if "beIN" in clean_name and "XTRA" in clean_name: clean_name = "beIN Xtra"
                if "beIN" in clean_name and "MAX" in clean_name: clean_name = "beIN Max"
                if "beIN" in clean_name and "Movies" in clean_name: clean_name = "beIN Movies"
                
                ch['clean_group'] = clean_name 
                global_group_counts[clean_name] += 1
                cleaned_channels.append(ch)
            
            all_files_data[input_file] = (header, cleaned_channels)
            print(f"Analyzed {input_file}: {len(channels)} channels.")
            
        except Exception as e:
            print(f"Error reading {input_file}: {e}")

    # Determine Top 100 Groups
    print(f"Found {len(global_group_counts)} distinct groups globally.")
    
    # Keep top 100
    top_100 = [group for group, count in global_group_counts.most_common(100)]
    top_100_set = set(top_100)
    
    print("Top 10 groups by frequency:", top_100[:10])
    
    print("Phase 2: Rewriting files with global limit (Top 100) and Visual Headers...")
    
    for input_file, (header, channels) in all_files_data.items():
        try:
            # Assign final groups and collect for sorting
            finalized_channels = []
            for ch in channels:
                group = ch['clean_group']
                if group not in top_100_set:
                    group = "Others"
                ch['final_group'] = group
                finalized_channels.append(ch)
            
            # Sort by Group Name to cluster them
            finalized_channels.sort(key=lambda x: x['final_group'])
            
            with open(input_file, 'w', encoding='utf-8') as f:
                f.write(header + '\n')
                
                current_group = None
                
                for ch in finalized_channels:
                    group = ch['final_group']
                    
                    # Check for group change to insert header
                    if group != current_group:
                        f.write('\n')
                        f.write('#' * 80 + '\n')
                        f.write(f'# {group}\n')
                        f.write('#' * 80 + '\n')
                        f.write('\n')
                        current_group = group

                    meta = ch['metadata']
                    # Replace group-title safely
                    if 'group-title="' in meta:
                        meta = re.sub(r'group-title="[^"]+"', f'group-title="{group}"', meta)
                    else:
                        # If missing, insert it
                        parts = meta.split(',')
                        if len(parts) > 1:
                            meta = parts[0] + f' group-title="{group}",' + ','.join(parts[1:])
                        else:
                            meta = meta + f' group-title="{group}"'

                    f.write(meta + '\n')
                    f.write(ch['url'] + '\n')
                    f.write('\n') # Add blank line for readability
            
            print(f"Rewrote {input_file} with headers")
            
        except Exception as e:
            print(f"Error writing {input_file}: {e}")
    
    print("Global cleanup complete.")

if __name__ == "__main__":
    main()
