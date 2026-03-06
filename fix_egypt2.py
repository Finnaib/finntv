import re
import json

def normalize(name):
    n = name.lower()
    n = re.sub(r'\[.*?\]|\(.*?\)|\{.*?\}', '', n)
    n = re.sub(r'\b(hd|sd|fhd|4k|low|plus|\+|tv|channel|eg|ar|egypt)\b', ' ', n)
    n = re.sub(r'[^a-z0-9]', ' ', n)
    return re.sub(r'\s+', ' ', n).strip()

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

catalog = []
current_name = None
for line in live_lines:
    if line.startswith('#EXTINF:'):
        matches = re.findall(r',(.*?)$', line)
        if matches:
            current_name = matches[0].strip()
    elif line.startswith('http') and current_name:
        catalog.append({'name': current_name, 'url': line.strip(), 'norm': normalize(current_name)})
        current_name = None

with open('m3u/egypt.m3u', 'r', encoding='utf-8') as f:
    egypt_lines = f.read().splitlines()

manual_map = {
    # Sports & beIN
    "bein_max_1_low": next((c for c in catalog if 'bein sports max' in c['norm'] and '1' in c['norm']), None),
    "bein_max_2_low": next((c for c in catalog if 'bein sports max' in c['norm'] and '2' in c['norm']), None),
    "bein_max_3_low": next((c for c in catalog if 'bein sports max' in c['norm'] and '3' in c['norm']), None),
    "bein_max_4_low": next((c for c in catalog if 'bein sports max' in c['norm'] and '4' in c['norm']), None),
    "bein_sports_1xtra_low": next((c for c in catalog if 'bein sports xtra' in c['norm'] and '1' in c['norm']), None),
    "bein_sports_2xtra_low": next((c for c in catalog if 'bein sports xtra' in c['norm'] and '2' in c['norm']), None),
    "bein_sports_3xtra_low": next((c for c in catalog if 'bein sports xtra' in c['norm'] and '3' in c['norm']), None),
    "bein_sports_1english_low": next((c for c in catalog if 'bein sports english' in c['norm'] and '1' in c['norm']), None),
    "bein baby": next((c for c in catalog if 'bein sports' not in c['norm'] and 'baby' in c['norm']), None),
    "bein_baraem": next((c for c in catalog if 'baraem' in c['norm']), None),
    "shasha sport 1": next((c for c in catalog if 'shasha sport 1' in c['norm']), None),
    "shasha sport 2": next((c for c in catalog if 'shasha sport 2' in c['norm']), None),
    "shasha sport 3": next((c for c in catalog if 'shasha sport 3' in c['norm']), None),
    "starzplay 1 480p": next((c for c in catalog if 'starzplay 1' in c['norm']), None),
    "starzplay 2 480p": next((c for c in catalog if 'starzplay 2' in c['norm']), None),
    "starzplay 3 1080p": next((c for c in catalog if 'starzplay 3' in c['norm']), None),
    "ad sport asia 1": next((c for c in catalog if 'ad sport asia 1' in c['norm'] or 'abu dhabi asia 1' in c['norm']), None),
    "ad sport asia 2": next((c for c in catalog if 'ad sport asia 2' in c['norm'] or 'abu dhabi asia 2' in c['norm']), None),
    "on sport fm": next((c for c in catalog if 'on sport fm' in c['norm']), None),
    
    # Egypt local & Misc
    "misr el bald": next((c for c in catalog if 'misr el balad' in c['norm'] or 'masr el balad' in c['norm']), None),
    "panorama foot": next((c for c in catalog if 'panorama food' in c['norm'] or 'panorama foo' in c['norm']), None),
    "m classic": next((c for c in catalog if 'm classic' in c['norm']), None),
    "hareem elsultan": next((c for c in catalog if 'hareem elsultan' in c['norm'] or 'harem' in c['norm']), None),
    "m cinema": next((c for c in catalog if 'm cinema' in c['norm']), None),
    "ksa sports1": next((c for c in catalog if 'ksa sports 1' in c['norm']), None),
    "ksa sports2": next((c for c in catalog if 'ksa sports 2' in c['norm']), None),
    "mbc panorama": next((c for c in catalog if 'mbc panorama' in c['norm']), None),
    "mbc wanash": next((c for c in catalog if 'mbc wanash' in c['norm']), None),
    "mh search": next((c for c in catalog if 'mh tv' in c['norm'] or 'search' in c['norm']), None),
    "almashhad": next((c for c in catalog if 'al mashhad' in c['norm']), None),
    "rotana khalijia": next((c for c in catalog if 'rotana khalijia' in c['norm'] or 'rotana khaligia' in c['norm']), None),
    "shahid mbc drama": next((c for c in catalog if 'shahid mbc drama' in c['norm'] or ('shahid' in c['norm'] and 'mbc' in c['norm'])), None),
    "shahid bollywood": next((c for c in catalog if 'shahid bollywood' in c['norm']), None),
    "shahid masrah misr": next((c for c in catalog if 'shahid masrah misr' in c['norm']), None),
    "shahid mbc5": next((c for c in catalog if 'shahid mbc5' in c['norm']), None),
    "shahid bab al hara": next((c for c in catalog if 'shahid bab al hara' in c['norm'] or 'bab al hara' in c['norm']), None),
    "shahid movies": next((c for c in catalog if 'shahid movies' in c['norm'] and 'thriller' not in c['norm'] and 'action' not in c['norm']), None),
    "shahid movies thriller": next((c for c in catalog if 'shahid movies thriller' in c['norm']), None),
    "shahid movies action": next((c for c in catalog if 'shahid movies action' in c['norm']), None),
    "shahid topchef": next((c for c in catalog if 'shahid topchef' in c['norm'] or 'top chef' in c['norm']), None),
    "shahid turkish drama": next((c for c in catalog if 'shahid turkish drama' in c['norm'] or 'turkish' in c['norm']), None),
    "shahid alhufra": next((c for c in catalog if 'shahid alhufra' in c['norm'] or 'alhufra' in c['norm']), None),
    "shahid al leaba": next((c for c in catalog if 'shahid al leaba' in c['norm'] or 'leaba' in c['norm']), None),
    "mbc masr2 1080": next((c for c in catalog if 'mbc masr 2' in c['norm']), None),
    "alikhbariya": next((c for c in catalog if 'al ekhbariya' in c['norm'] or 'alikhbariya' in c['norm']), None),
    "el leba": next((c for c in catalog if 'el leba' in c['norm'] or 'leaba' in c['norm']), None),
    "masrah masr": next((c for c in catalog if 'masrah masr' in c['norm']), None),
    "bab al hara": next((c for c in catalog if 'bab al hara' in c['norm']), None),
    
    # Defaults
    "bein": next((c for c in catalog if 'bein sports 1' in c['norm']), None),
    "mbc": next((c for c in catalog if 'mbc 1' in c['norm']), None),
    "osn": next((c for c in catalog if 'osn movies' in c['norm']), None)
}

def is_acceptable_match(norm_target, norm_candidate):
    if not norm_target or not norm_candidate: return False
    if norm_target == norm_candidate: return True
    parts_t = norm_target.split()
    parts_c = set(norm_candidate.split())
    if len(parts_t) > 0 and all(p in parts_c for p in parts_t):
        return True
    return False

updated_lines = []
current_egypt_name = None

matched = 0
not_found = 0

for line in egypt_lines:
    if line.startswith('#EXTINF:'):
        matches = re.findall(r',(.*?)$', line)
        if matches:
            current_egypt_name = matches[0].strip()
        updated_lines.append(line)
        
    elif line.startswith('http') and 'aromatv' in line:
        url_kept = line
        if current_egypt_name:
            norm_name = normalize(current_egypt_name)
            best_match = None
            
            # Exact
            for c in catalog:
                if c['name'].lower() == current_egypt_name.lower(): best_match = c; break
            if not best_match:
                for c in catalog:
                    if c['norm'] == norm_name: best_match = c; break
            if not best_match:
                for c in catalog:
                    if is_acceptable_match(norm_name, c['norm']): best_match = c; break
                    
            if not best_match and norm_name in manual_map and manual_map[norm_name]:
                best_match = manual_map[norm_name]
                
            # If still missing, check if it's a specific family
            if not best_match:
                if 'bein' in norm_name: best_match = manual_map["bein"]
                elif 'shahid' in norm_name: best_match = manual_map["mbc"]
                elif 'mbc' in norm_name: best_match = manual_map["mbc"]
                elif 'rotana' in norm_name: best_match = manual_map["mbc"]

            if best_match:
                url_kept = best_match['url']
                matched += 1
            else:
                not_found += 1
                
        updated_lines.append(url_kept)
        current_egypt_name = None
        
    else:
        updated_lines.append(line)
        current_egypt_name = None

with open('m3u/egypt.m3u', 'w', encoding='utf-8') as f:
    f.write('\n'.join(updated_lines) + '\n')

print(f"Matched: {matched}, Default/Missing kept old URL: {not_found}")
