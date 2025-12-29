#!/usr/bin/env python3
"""Test script to find the correct Envato API endpoint"""

import requests
from pathlib import Path

# Load API key from .env
ENV_FILE = Path(__file__).parent.parent / ".env"
api_key = None

with open(ENV_FILE, 'r') as f:
    for line in f:
        if line.startswith('ENVATO_API_KEY='):
            api_key = line.split('=', 1)[1].strip().strip('"\'')
            break

if not api_key:
    print("❌ API key not found in .env")
    exit(1)

headers = {'Authorization': f'Bearer {api_key}'}
base_url = 'https://api.envato.com'

# Test endpoints from API documentation
endpoints_to_test = [
    # Catalog endpoints
    ('/v1/market/catalog/item.json', {'term': 'katana'}),
    ('/v1/market/catalog/item.json', {'id': '12345'}),
    ('/v1/market/catalog/items.json', {'term': 'katana'}),
    ('/v1/market/catalog/items.json', {'term': 'katana'}),
    
    # Search endpoints
    ('/v1/market/catalog/search/item.json', {'term': 'katana'}),
    ('/v1/market/catalog/search/items.json', {'term': 'katana'}),
    ('/v1/discovery/search/search/item.json', {'term': 'katana'}),
    ('/v1/discovery/search/search/items.json', {'term': 'katana'}),
    
    # Alternative patterns
    ('/v1/market/search/item.json', {'term': 'katana'}),
    ('/v1/market/search/items.json', {'term': 'katana'}),
]

print("Testing Envato API endpoints...")
print("=" * 60)

for endpoint, params in endpoints_to_test:
    url = base_url + endpoint
    try:
        r = requests.get(url, headers=headers, params=params, timeout=10)
        status = r.status_code
        
        if status == 200:
            print(f"[SUCCESS] {endpoint}")
            print(f"   Params: {params}")
            print(f"   Response preview: {r.text[:200]}")
            print()
        elif status == 404:
            print(f"[404] {endpoint} (not found)")
        elif status == 403:
            print(f"[403] {endpoint} (forbidden - might be wrong endpoint)")
        elif status == 400:
            print(f"[400] {endpoint} (bad request - endpoint might exist)")
            print(f"   Response: {r.text[:200]}")
        else:
            print(f"[{status}] {endpoint}")
            print(f"   Response: {r.text[:200]}")
    except Exception as e:
        print(f"[ERROR] {endpoint} - {e}")

print("=" * 60)
print("Done testing endpoints")

