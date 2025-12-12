import re
from collections import Counter

def main():
    try:
        with open('vod.m3u', 'r', encoding='utf-8') as f:
            content = f.read()
            groups = re.findall(r'group-title="([^"]+)"', content)
            c = Counter(groups)
            for g, count in c.most_common():
                print(f"{count}: {g}")
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    main()
