<?php
/**
 * Helper Functions for Music Registry Admin
 */

declare(strict_types=1);

/**
 * Generate a slug-like ID from a title
 * @param string $title Title to convert
 * @param string $prefix Optional prefix (e.g., 'asset', 'cue')
 * @return string Generated ID
 */
function generate_id_from_title(string $title, string $prefix = ''): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/\s+/', '_', $slug);
    $slug = preg_replace('/_+/', '_', $slug);
    $slug = trim($slug, '_');
    
    if ($prefix) {
        $slug = $prefix . '_' . $slug;
    }
    
    return $slug ?: 'untitled';
}

/**
 * Sanitize string for HTML output
 * @param string $value Value to sanitize
 * @return string Escaped string
 */
function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate file path to prevent directory traversal
 * @param string $path File path to validate
 * @param string $base_dir Base directory (project root)
 * @return bool True if valid
 */
function validate_file_path(string $path, string $base_dir = ''): bool {
    if (empty($base_dir)) {
        $base_dir = realpath(__DIR__ . '/../..') ?: __DIR__ . '/../..';
    }
    
    // Reject absolute paths outside project
    $real_path = realpath($base_dir . '/' . ltrim($path, '/'));
    if ($real_path === false) {
        return false;
    }
    
    $real_base = realpath($base_dir);
    if ($real_base === false) {
        return false;
    }
    
    // Ensure path is within base directory
    return strpos($real_path, $real_base) === 0;
}

/**
 * Parse comma-separated tags into array
 * @param string $tags Comma-separated tags
 * @return array Array of trimmed, non-empty tags
 */
function parse_tags(string $tags): array {
    if (empty(trim($tags))) {
        return [];
    }
    
    $tag_array = explode(',', $tags);
    $tag_array = array_map('trim', $tag_array);
    $tag_array = array_filter($tag_array, function($tag) {
        return !empty($tag);
    });
    
    return array_values($tag_array);
}

/**
 * Format tags array for display
 * @param array $tags Tags array
 * @return string Comma-separated string
 */
function format_tags(array $tags): string {
    return implode(', ', $tags);
}

/**
 * Pretty print JSON for preview
 * @param mixed $data Data to encode
 * @return string Pretty JSON string
 */
function json_preview($data): string {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return $json !== false ? $json : '{}';
}

/**
 * Get all asset IDs for dropdown
 * @param array $registry Registry data
 * @return array Array of [id => title] pairs
 */
function get_asset_options(array $registry): array {
    $options = [];
    if (isset($registry['assets']) && is_array($registry['assets'])) {
        foreach ($registry['assets'] as $asset) {
            if (isset($asset['asset_id']) && isset($asset['title'])) {
                $options[$asset['asset_id']] = $asset['title'];
            }
        }
    }
    return $options;
}

/**
 * Get all cue IDs for dropdown
 * @param array $registry Registry data
 * @return array Array of [id => label] pairs
 */
function get_cue_options(array $registry): array {
    $options = [];
    if (isset($registry['cues']) && is_array($registry['cues'])) {
        foreach ($registry['cues'] as $cue) {
            if (isset($cue['cue_id']) && isset($cue['asset_ref'])) {
                $asset_title = 'Unknown';
                if (isset($registry['assets']) && is_array($registry['assets'])) {
                    foreach ($registry['assets'] as $asset) {
                        if (isset($asset['asset_id']) && $asset['asset_id'] === $cue['asset_ref']) {
                            $asset_title = $asset['title'] ?? 'Unknown';
                            break;
                        }
                    }
                }
                $role = $cue['role'] ?? 'unknown';
                $options[$cue['cue_id']] = "{$cue['cue_id']} ({$role}) - {$asset_title}";
            }
        }
    }
    return $options;
}

/**
 * Find relationships: which cues use an asset
 * @param array $registry Registry data
 * @param string $asset_id Asset ID
 * @return array Array of cue IDs
 */
function find_cues_using_asset(array $registry, string $asset_id): array {
    $cue_ids = [];
    if (isset($registry['cues']) && is_array($registry['cues'])) {
        foreach ($registry['cues'] as $cue) {
            if (isset($cue['asset_ref']) && $cue['asset_ref'] === $asset_id) {
                $cue_ids[] = $cue['cue_id'] ?? null;
            }
        }
    }
    return array_filter($cue_ids);
}

/**
 * Find relationships: which bindings use a cue
 * @param array $registry Registry data
 * @param string $cue_id Cue ID
 * @return array Array of binding IDs
 */
function find_bindings_using_cue(array $registry, string $cue_id): array {
    $binding_ids = [];
    if (isset($registry['bindings']) && is_array($registry['bindings'])) {
        foreach ($registry['bindings'] as $binding) {
            if (isset($binding['play_cue_ref']) && $binding['play_cue_ref'] === $cue_id) {
                $binding_ids[] = $binding['binding_id'] ?? null;
            }
        }
    }
    return array_filter($binding_ids);
}
