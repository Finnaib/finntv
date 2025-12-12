import re

def main():
    input_file = 'vod.m3u'
    print(f"Reading {input_file}...")
    
    try:
        with open(input_file, 'r', encoding='utf-8') as f:
            lines = f.readlines()
            header = lines[0].strip() if lines else "#EXTM3U"
    except Exception as e:
        print(f"Error: {e}")
        return

    seen_urls = set()
    unique_channels = []
    
    current_channel = {}
    
    count = 0
    dupes = 0
    
    for line in lines:
        line = line.strip()
        if line.startswith('#EXTINF:'):
            current_channel['metadata'] = line
        elif line and not line.startswith('#'):
            url = line
            current_channel['url'] = url
            
            if url not in seen_urls:
                seen_urls.add(url)
                if 'metadata' in current_channel:
                    unique_channels.append(current_channel)
                    count += 1
            else:
                dupes += 1
            current_channel = {}

    print(f"Found {count} unique channels. Removed {dupes} duplicates.")
    
    with open(input_file, 'w', encoding='utf-8') as f:
        f.write(header + '\n')
        for ch in unique_channels:
            f.write(ch['metadata'] + '\n')
            f.write(ch['url'] + '\n')
            f.write('\n')
            
    print("Deduplication complete.")

if __name__ == "__main__":
    main()
