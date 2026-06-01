import os
import re

def clean_text(text):
    # Regex to match emojis and some special non-ascii characters
    # We will just strip anything that is not ASCII to be safe, except for standard M3U characters.
    # Wait, M3U can contain foreign languages (Arabic, Hindi). We only want to strip emojis.
    
    # Common emoji ranges
    emoji_pattern = re.compile(
        u"(\ud83d[\ude00-\ude4f])|"  # emoticons
        u"(\ud83c[\udf00-\uffff])|"  # symbols & pictographs (1 of 2)
        u"(\ud83d[\u0000-\uddff])|"  # symbols & pictographs (2 of 2)
        u"(\ud83d[\ude80-\udeff])|"  # transport & map symbols
        u"(\ud83c[\udde0-\uddff])|"  # flags (iOS)
        u"[\u2600-\u26FF\u2700-\u27BF]|"
        u"[\U00010000-\U0010ffff]",
        flags=re.UNICODE)
    
    return emoji_pattern.sub(r'', text)

m3u_dir = 'm3u'
for filename in os.listdir(m3u_dir):
    if filename.endswith('.m3u'):
        filepath = os.path.join(m3u_dir, filename)
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            lines = f.readlines()
        
        with open(filepath, 'w', encoding='utf-8') as f:
            for line in lines:
                f.write(clean_text(line))
                
print("Emojis removed from all M3U files.")
