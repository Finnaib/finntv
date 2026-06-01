"""
check_m3u.py - A simple script to verify M3U contents and counts.
"""
import re, sys
sys.stdout.reconfigure(encoding='utf-8')

def check_m3u(path, label):
    try:
        with open(f"m3u/{path}", 'r', encoding='utf-8') as f:
            content = f.read()
    except FileNotFoundError:
        print(f'--- {label} --- MISSING')
        return
    total = sum(1 for l in content.splitlines() if l.startswith('#EXTINF:'))
    
    # Simple logic to find sections starting with # 
    sections = re.findall(r'^# ([^#\n]+)$', content, re.M)
    sections = [s.strip() for s in sections if s.strip()]
    
    print(f'--- {label} ({path}) ---')
    print(f'  Channels: {total}')
    if sections:
        print(f'  Sections identified: {sections[:10]} {"..." if len(sections) > 10 else ""}')
    print()

check_m3u('egypt.m3u', 'Egypt')
check_m3u('india.m3u', 'India/Subcontinent')
check_m3u('world.m3u', 'World')
check_m3u('sport.m3u', 'Sport')
check_m3u('spanish.m3u', 'Spain')
check_m3u('asia.m3u', 'Asia')
