#!/usr/bin/env python3
"""Test image URL extraction from search results"""

import requests
import json
from pathlib import Path

# Load API key
ENV_FILE = Path(__file__).parent.parent / ".env"
api_key = None

with open(ENV_FILE, 'r') as f:
    for line in f:
        if line.startswith('ENVATO_API_KEY='):
            api_key = line.split('=', 1)[1].strip().strip('"\'')
            break

headers = {'Authorization': f'Bearer {api_key}'}

# Test search for different item types
test_queries = [
    ('sword', None),
    ('katana', None),
    ('weapon', None),
]

print("Testing image URL extraction from search results...")
print("=" * 60)

for query, site in test_queries:
    params = {'term': query, 'page_size': 2}
    if site:
        params['site'] = site
    
    r = requests.get('https://api.envato.com/v1/discovery/search/search/item.json', 
                    headers=headers, params=params, timeout=10)
    data = r.json()
    
    print(f"\nQuery: '{query}' (site: {site or 'all'})")
    print(f"Total hits: {data.get('total_hits', 0)}")
    
    for match in data.get('matches', [])[:2]:
        print(f"\n  Item: {match.get('name')}")
        print(f"  Site: {match.get('site')}")
        print(f"  Classification: {match.get('classification', 'N/A')}")
        
        # Check for image URLs
        image_sources = []
        
        if 'previews' in match:
            previews = match['previews']
            for key, value in previews.items():
                if isinstance(value, dict):
                    if 'icon_url' in value:
                        image_sources.append(f"previews.{key}.icon_url")
                    if 'url' in value:
                        image_sources.append(f"previews.{key}.url")
                elif isinstance(value, str):
                    image_sources.append(f"previews.{key}")
        
        if 'image_urls' in match:
            image_sources.append("image_urls")
        
        if image_sources:
            print(f"  Image sources found: {', '.join(image_sources)}")
        else:
            print(f"  No image sources found")
            print(f"  Available keys: {[k for k in match.keys() if 'image' in k.lower() or 'preview' in k.lower() or 'url' in k.lower()]}")

print("\n" + "=" * 60)

