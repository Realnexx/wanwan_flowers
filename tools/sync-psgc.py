"""Refresh the local address snapshot from PSGC Cloud's documented v2 API."""
import concurrent.futures
import datetime
import json
from pathlib import Path
import urllib.request

BASE = 'https://psgc.cloud/api/v2/'
def fetch(path):
    with urllib.request.urlopen(BASE + path, timeout=40) as response:
        return json.load(response)['data']

regions = fetch('regions')
provinces = fetch('provinces')
cities = fetch('cities-municipalities')
region_names = {r['name']: r['code'] for r in regions}
province_names = {(p['region'], p['name']): p['code'] for p in provinces}
result = {'source': BASE, 'retrieved_at': datetime.datetime.now(datetime.timezone.utc).isoformat(),
          'regions': [], 'provinces': [], 'cities': [], 'barangays': []}
for row in regions:
    result['regions'].append({'code': row['code'], 'name': row['name']})
for row in provinces:
    result['provinces'].append({'code': row['code'], 'name': row['name'], 'region': region_names[row['region']]})
for row in cities:
    result['cities'].append({'code': row['code'], 'name': row['name'],
        'region': region_names[row['region']], 'province': province_names.get((row['region'], row['province']), ''),
        'zip': row.get('zip_code', ''), 'type': row.get('type', '')})
city_codes = {c['code'] for c in cities}
submunicipalities = {c['code'] for c in cities if c.get('type') == 'SubMun'}
with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
    collections = pool.map(fetch, ['regions/' + r['code'] + '/barangays' for r in regions])
    seen = set()
    for rows in collections:
        for row in rows:
            city = row['code'][:7] + '000'
            if city not in city_codes:
                raise ValueError('Unknown barangay parent: ' + row['code'])
            if city in submunicipalities:
                city = city[:5] + '00000'
            if row['code'] in seen:
                raise ValueError('Duplicate barangay: ' + row['code'])
            seen.add(row['code'])
            result['barangays'].append({'code': row['code'], 'name': row['name'], 'city': city})
result['cities'] = [c for c in result['cities'] if c['code'] not in submunicipalities]
assert len(result['barangays']) > 40000, 'Incomplete source response; snapshot preserved'
target = Path(__file__).resolve().parent.parent / 'data' / 'psgc.json'
temporary = target.with_suffix('.tmp')
temporary.write_text(json.dumps(result, ensure_ascii=False, separators=(',', ':')), encoding='utf-8')
temporary.replace(target)
print('Saved PSGC snapshot:', {k: len(result[k]) for k in ['regions', 'provinces', 'cities', 'barangays']})
