import re
import sys
sys.stdout.reconfigure(encoding='utf-8')

def check_m3u(path, label):
    try:
        with open(path, 'r', encoding='utf-8') as f:
            content = f.read()
    except FileNotFoundError:
        print(f'--- {label} --- MISSING')
        return
    lines = content.splitlines()
    total = sum(1 for l in lines if l.startswith('#EXTINF:'))
    sections = re.findall(r'^# (.+)$', content, re.M)
    names = re.findall(r'tvg-name="([^"]+)"', content)[:6]
    print(f'--- {label} ({path.split("/")[-1]}) ---')
    print(f'  Channels: {total}  Sections: {sections}')
    print(f'  First 6 channels: {names}')
    print()

check_m3u('m3u/india.m3u', 'India')
check_m3u('m3u/indonesia.m3u', 'Indonesia')
check_m3u('m3u/usa.m3u', 'USA')
check_m3u('m3u/asia.m3u', 'Asia')
check_m3u('m3u/sport.m3u', 'Sport')
