import re

def natural_keys(text):
    '''
    alist.sort(key=natural_keys) sorts in human order
    http://nedbatchelder.com/blog/200712/human_sorting.html
    '''
    return [ int(c) if c.isdigit() else c for c in re.split(r'(\d+)', text) ]

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

def main():
    input_file = 'vod.m3u'
    print(f"Reading {input_file}...")
    header, channels = parse_m3u(input_file)
    
    # Sort by Group (Natural Sort) then by Name
    print("Sorting channels...")
    channels.sort(key=lambda x: (natural_keys(x.get('group', '')), x['metadata']))
    
    print(f"Writing sorted {input_file}...")
    with open(input_file, 'w', encoding='utf-8') as f:
        f.write(header + '\n')
        
        current_group = None
        for ch in channels:
            group = ch.get('group', 'Uncategorized')
            
            # Add visual separator for new groups
            if group != current_group:
                f.write('\n')
                f.write(f'##### [{group}] #####\n')
                f.write('\n')
                current_group = group
            
            f.write(ch['metadata'] + '\n')
            f.write(ch['url'] + '\n')
            f.write('\n') # Blank line
            
    print("Sort complete.")

if __name__ == "__main__":
    main()
