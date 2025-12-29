#!/usr/bin/env python3
"""Test photo search with different parameters"""

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

# Test different photo-related searches
test_params = [
    {'term': 'sword', 'classification': 'photo'},
    {'term': 'sword', 'category': 'photos'},
    {'term': 'sword'},  # No filter
]

print("Testing Photo Search Parameters...")
print("=" * 60)

for params in test_params:
    r = requests.get('https://api.envato.com/v1/discovery/search/search/item.json', 
                    headers=headers, params=params, timeout=10)
    data = r.json()
    
    print(f"\nParams: {params}")
    print(f"Total hits: {data.get('total_hits', 0)}")
    print(f"Matches: {len(data.get('matches', []))}")
    
    if data.get('matches'):
        for match in data.get('matches', [])[:2]:
            print(f"  - {match.get('name')} | {match.get('site')} | {match.get('classification', 'N/A')}")

print("\n" + "=" * 60)
print("Checking available categories/classifications...")

# Get aggregations to see available categories
r = requests.get('https://api.envato.com/v1/discovery/search/search/item.json', 
                headers=headers, params={'term': 'photo', 'page_size': 1}, timeout=10)
data = r.json()

if 'aggregations' in data:
    print("\nAvailable categories:")
    if 'category' in data['aggregations']:
        for cat in data['aggregations']['category'][:10]:
            print(f"  - {cat.get('key')}: {cat.get('count')} items")

