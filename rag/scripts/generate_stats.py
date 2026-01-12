#!/usr/bin/env python3
"""
Generate chunking statistics and update reports.
"""
import json
import statistics
from pathlib import Path
from typing import Dict, List, Any
from collections import defaultdict

REPO_ROOT = Path(__file__).parent.parent.parent
RAG_DIR = REPO_ROOT / "rag"


def calculate_statistics(chunks_file: Path, book_index_file: Path) -> Dict[str, Any]:
    """Calculate chunking statistics."""
    print("Calculating statistics...")
    
    # Load chunks
    chunks = []
    with open(chunks_file, 'r', encoding='utf-8') as f:
        for line in f:
            chunk = json.loads(line)
            chunks.append(chunk)
    
    print(f"Processing {len(chunks)} chunks...")
    
    # Overall statistics
    total_chunks = len(chunks)
    token_counts = [chunk.get('token_count_estimate', 0) for chunk in chunks]
    
    stats = {
        'total_chunks': total_chunks,
        'token_statistics': {
            'min': min(token_counts) if token_counts else 0,
            'max': max(token_counts) if token_counts else 0,
            'mean': statistics.mean(token_counts) if token_counts else 0,
            'median': statistics.median(token_counts) if token_counts else 0,
            'stdev': statistics.stdev(token_counts) if len(token_counts) > 1 else 0,
        },
        'size_distribution': {
            'undersized_<50': sum(1 for tc in token_counts if tc < 50),
            'small_50-300': sum(1 for tc in token_counts if 50 <= tc < 300),
            'target_300-900': sum(1 for tc in token_counts if 300 <= tc <= 900),
            'large_900-1500': sum(1 for tc in token_counts if 900 < tc <= 1500),
            'oversized_>1500': sum(1 for tc in token_counts if tc > 1500),
        },
        'quality_flags': defaultdict(int),
        'tags_distribution': defaultdict(int),
        'books_statistics': {},
    }
    
    # Quality flags
    for chunk in chunks:
        for flag in chunk.get('quality_flags', []):
            stats['quality_flags'][flag] += 1
    
    # Tags distribution
    for chunk in chunks:
        for tag in chunk.get('tags', []):
            stats['tags_distribution'][tag] += 1
    
    # Per-book statistics
    book_stats = defaultdict(lambda: {
        'chunk_count': 0,
        'token_counts': [],
        'quality_flags': defaultdict(int),
        'terms_count': 0,
    })
    
    for chunk in chunks:
        book_id = chunk['source_book']
        book_stats[book_id]['chunk_count'] += 1
        book_stats[book_id]['token_counts'].append(chunk.get('token_count_estimate', 0))
        for flag in chunk.get('quality_flags', []):
            book_stats[book_id]['quality_flags'][flag] += 1
        book_stats[book_id]['terms_count'] = len(chunk.get('canonical_terms', []))
    
    # Calculate per-book statistics
    for book_id, book_stat in book_stats.items():
        token_counts_book = book_stat['token_counts']
        stats['books_statistics'][book_id] = {
            'chunk_count': book_stat['chunk_count'],
            'avg_chunk_size': statistics.mean(token_counts_book) if token_counts_book else 0,
            'min_chunk_size': min(token_counts_book) if token_counts_book else 0,
            'max_chunk_size': max(token_counts_book) if token_counts_book else 0,
            'quality_flags': dict(book_stat['quality_flags']),
            'terms_count': book_stat['terms_count'],
        }
    
    return stats


def generate_stats_report(stats: Dict[str, Any], book_index: List[Dict[str, Any]]) -> str:
    """Generate markdown statistics report."""
    report = "# Chunking Statistics Report\n\n"
    
    report += "## Overall Statistics\n\n"
    report += f"- **Total chunks**: {stats['total_chunks']:,}\n\n"
    
    report += "### Token Size Statistics\n\n"
    token_stats = stats['token_statistics']
    report += f"- **Minimum**: {token_stats['min']:.0f} tokens\n"
    report += f"- **Maximum**: {token_stats['max']:.0f} tokens\n"
    report += f"- **Mean**: {token_stats['mean']:.1f} tokens\n"
    report += f"- **Median**: {token_stats['median']:.1f} tokens\n"
    report += f"- **Standard Deviation**: {token_stats['stdev']:.1f} tokens\n\n"
    
    report += "### Size Distribution\n\n"
    size_dist = stats['size_distribution']
    report += f"- **Undersized (<50 tokens)**: {size_dist['undersized_<50']:,} ({size_dist['undersized_<50']/stats['total_chunks']*100:.1f}%)\n"
    report += f"- **Small (50-300 tokens)**: {size_dist['small_50-300']:,} ({size_dist['small_50-300']/stats['total_chunks']*100:.1f}%)\n"
    report += f"- **Target (300-900 tokens)**: {size_dist['target_300-900']:,} ({size_dist['target_300-900']/stats['total_chunks']*100:.1f}%)\n"
    report += f"- **Large (900-1500 tokens)**: {size_dist['large_900-1500']:,} ({size_dist['large_900-1500']/stats['total_chunks']*100:.1f}%)\n"
    report += f"- **Oversized (>1500 tokens)**: {size_dist['oversized_>1500']:,} ({size_dist['oversized_>1500']/stats['total_chunks']*100:.1f}%)\n\n"
    
    report += "### Quality Flags\n\n"
    for flag, count in sorted(stats['quality_flags'].items()):
        report += f"- **{flag}**: {count:,}\n"
    report += "\n"
    
    report += "### Tags Distribution\n\n"
    for tag, count in sorted(stats['tags_distribution'].items(), key=lambda x: -x[1]):
        report += f"- **{tag}**: {count:,}\n"
    report += "\n"
    
    report += "## Per-Book Statistics\n\n"
    report += "| Book ID | Chunks | Avg Size | Min Size | Max Size | Terms | Quality Flags |\n"
    report += "|---------|--------|----------|----------|----------|-------|---------------|\n"
    
    # Create book title lookup
    book_titles = {book['book_id']: book.get('title', book['book_id']) for book in book_index}
    
    for book_id in sorted(stats['books_statistics'].keys()):
        book_stat = stats['books_statistics'][book_id]
        title = book_titles.get(book_id, book_id)
        quality_flags_items = list(book_stat['quality_flags'].items())[:3]
        quality_flags_str = ', '.join(f"{k}:{v}" for k, v in quality_flags_items)
        if len(book_stat['quality_flags']) > 3:
            quality_flags_str += "..."
        
        report += f"| {book_id} | {book_stat['chunk_count']} | {book_stat['avg_chunk_size']:.0f} | {book_stat['min_chunk_size']:.0f} | {book_stat['max_chunk_size']:.0f} | {book_stat['terms_count']} | {quality_flags_str} |\n"
    
    report += "\n"
    
    # Outliers section
    report += "## Outliers\n\n"
    
    # Find books with unusual chunk counts
    chunk_counts = [book_stat['chunk_count'] for book_stat in stats['books_statistics'].values()]
    if chunk_counts:
        mean_chunks = statistics.mean(chunk_counts)
        stdev_chunks = statistics.stdev(chunk_counts) if len(chunk_counts) > 1 else 0
        
        report += "### Books with Unusual Chunk Counts\n\n"
        for book_id, book_stat in sorted(stats['books_statistics'].items(), key=lambda x: x[1]['chunk_count']):
            chunk_count = book_stat['chunk_count']
            if chunk_count > mean_chunks + 2 * stdev_chunks or chunk_count < mean_chunks - 2 * stdev_chunks:
                title = book_titles.get(book_id, book_id)
                report += f"- **{title}** ({book_id}): {chunk_count} chunks (mean: {mean_chunks:.1f}, stdev: {stdev_chunks:.1f})\n"
        report += "\n"
    
    return report


def main():
    """Generate statistics report."""
    print("Generating statistics...")
    
    chunks_file = RAG_DIR / "derived" / "chunks" / "chunks.jsonl"
    book_index_file = RAG_DIR / "index" / "book_index.json"
    reports_dir = RAG_DIR / "reports"
    
    # Load book index
    with open(book_index_file, 'r', encoding='utf-8') as f:
        book_index = json.load(f)
    
    # Calculate statistics
    stats = calculate_statistics(chunks_file, book_index_file)
    
    # Generate report
    report = generate_stats_report(stats, book_index)
    
    # Write report
    stats_file = reports_dir / "chunking_stats.md"
    with open(stats_file, 'w', encoding='utf-8') as f:
        f.write(report)
    
    print(f"Statistics report written to {stats_file}")


if __name__ == "__main__":
    main()
