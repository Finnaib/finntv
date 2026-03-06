import re
import subprocess
import os

# Put egypt.m3u back to remote HEAD so we have the original names & old aromatv URLs to replace.
subprocess.run(['git', 'checkout', 'HEAD', '--', 'm3u/egypt.m3u'])

with open('m3u/live.m3u', 'r', encoding='utf-8') as f:
    live_lines = f.read().splitlines()

# Build a searchable catalog from live.m3u
catalog = []
current_name = None
for line in live_lines:
    if line.startswith('#EXTINF:'):
        matches = re.findall(r',(.*?)$', line)
        if matches:
            current_name = matches[0].strip()
    elif line.startswith('http') and current_name:
        catalog.append({'name': current_name, 'url': line.strip()})
        current_name = None

def normalize(name):
    n = name.lower()
    n = re.sub(r'\[.*?\]|\(.*?\)|\{.*?\}', '', n)
    n = re.sub(r'\b(hd|sd|fhd|4k|low|plus|\+|tv|channel|eg|ar|egypt)\b', ' ', n)
    n = re.sub(r'[^a-z0-9]', ' ', n)
    # Strip spaces
    n = re.sub(r'\s+', ' ', n).strip()
    return n

for c in catalog:
    c['norm'] = normalize(c['name'])
    
# Add some fuzzy algorithms
def is_acceptable_match(norm_target, norm_candidate):
    if not norm_target or not norm_candidate: return False
    # Exact normal
    if norm_target == norm_candidate: return True
    # Subset matching checking bounds so `bein 1` doesn't match `bein 11`
    parts_t = norm_target.split()
    parts_c = set(norm_candidate.split())
    # All parts of target must be in candidate
    if len(parts_t) > 0 and all(p in parts_c for p in parts_t):
        return True
    return False

with open('m3u/egypt.m3u', 'r', encoding='utf-8') as f:
    egypt_lines = f.read().splitlines()

updated_lines = []
current_egypt_name = None
matched_count = 0
unmatched_count = 0
direct_cdn = 0

for line in egypt_lines:
    if line.startswith('#EXTINF:'):
        matches = re.findall(r',(.*?)$', line)
        if matches:
            current_egypt_name = matches[0].strip()
        updated_lines.append(line)
        
    elif line.startswith('http'):
        if 'aromatv.co' not in line:
            updated_lines.append(line)
            direct_cdn += 1
            current_egypt_name = None
            continue
            
        if current_egypt_name:
            norm_name = normalize(current_egypt_name)
            
            best_match = None
            
            # 1. Exact match
            for c in catalog:
                if c['name'].lower() == current_egypt_name.lower():
                    best_match = c
                    break
            
            # 2. Exact Normalized
            if not best_match:
                for c in catalog:
                    if c['norm'] == norm_name:
                        best_match = c
                        break
            
            # 3. Subset Normalized
            if not best_match:
                for c in catalog:
                    if is_acceptable_match(norm_name, c['norm']):
                        best_match = c
                        break
                        
            # 4. Keyword fallback if long enough (dangerous, but we restrict it)
            if not best_match and len(norm_name) > 5:
                # E.g., 'zamalik sport' -> look for 'zamalik'
                if 'zamalik' in norm_name:
                    best_match = next((c for c in catalog if 'zamalek' in c['norm'] or 'zamalik' in c['norm']), None)

            if best_match:
                updated_lines.append(best_match['url'])
                print(f"Matched: '{current_egypt_name}' -> '{best_match['name']}' ({best_match['url'].split('/')[-1]})")
                matched_count += 1
            else:
                updated_lines.append(line)  # Keep old one
                print(f"NOT FOUND: '{current_egypt_name}' (norm: {norm_name})")
                unmatched_count += 1
                
        else:
            updated_lines.append(line)
        current_egypt_name = None
    else:
        updated_lines.append(line)

with open('m3u/egypt.m3u', 'w', encoding='utf-8') as f:
    f.write('\n'.join(updated_lines) + '\n')

print("-------------------------------------------------")
print(f"Total processed: {len(egypt_lines)}")
print(f"Matched channels: {matched_count}")
print(f"Unmatched channels: {unmatched_count} (left with old URLs)")
print(f"Direct CDN channels: {direct_cdn}")
