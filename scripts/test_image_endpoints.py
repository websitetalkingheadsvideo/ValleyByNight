#!/usr/bin/env python3
"""Test script to find image-specific API endpoints"""

import requests
import json
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

# Test image/search related endpoints
endpoints_to_test = [
    # Image search endpoints
    ('/v1/market/images.json', {'term': 'katana'}),
    ('/v1/market/photos.json', {'term': 'katana'}),
    ('/v1/market/search/images.json', {'term': 'katana'}),
    ('/v1/market/search/photos.json', {'term': 'katana'}),
    ('/v1/discovery/search/search/image.json', {'term': 'katana'}),
    ('/v1/discovery/search/search/photo.json', {'term': 'katana'}),
    
    # Item details endpoints
    ('/v1/market/catalog/item.json', {'id': '12345'}),
    ('/v1/market/item.json', {'id': '12345'}),
    ('/v1/market/item/details.json', {'id': '12345'}),
    
    # Download endpoints (for purchased items)
    ('/v1/market/user/download-purchase.json', {'code': 'test'}),
    ('/v1/market/user/download.json', {'code': 'test'}),
]

print("Testing Image/Item/Download API endpoints...")
print("=" * 60)

for endpoint, params in endpoints_to_test:
    url = base_url + endpoint
    try:
        r = requests.get(url, headers=headers, params=params, timeout=10)
        status = r.status_code
        
        if status == 200:
            print(f"[SUCCESS] {endpoint}")
            print(f"   Params: {params}")
            try:
                data = r.json()
                print(f"   Response keys: {list(data.keys())[:5]}")
            except:
                print(f"   Response preview: {r.text[:200]}")
            print()
        elif status == 404:
            print(f"[404] {endpoint}")
        elif status == 403:
            print(f"[403] {endpoint} (forbidden)")
        elif status == 400:
            print(f"[400] {endpoint} (bad request - endpoint might exist)")
            try:
                error = r.json()
                print(f"   Error: {error}")
            except:
                print(f"   Response: {r.text[:200]}")
        else:
            print(f"[{status}] {endpoint}")
    except Exception as e:
        print(f"[ERROR] {endpoint} - {e}")

print("=" * 60)
print("Done testing endpoints")

