<?php
/**
 * Music Registry I/O Functions
 * Safe file operations with atomic writes and file locking
 */

declare(strict_types=1);

define('REGISTRY_PATH', __DIR__ . '/../../assets/music/music_registry.json');

/**
 * Load registry from disk
 * @return array Registry data
 * @throws Exception If file cannot be read or JSON is invalid
 */
function load_registry(): array {
    if (!file_exists(REGISTRY_PATH)) {
        throw new Exception("Registry file not found: " . REGISTRY_PATH);
    }
    
    $content = file_get_contents(REGISTRY_PATH);
    if ($content === false) {
        throw new Exception("Failed to read registry file");
    }
    
    $registry = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in registry: " . json_last_error_msg());
    }
    
    return $registry;
}

/**
 * Save registry to disk with atomic write and file locking
 * @param array $registry Registry data
 * @throws Exception If validation fails or file cannot be written
 */
function save_registry(array $registry): void {
    $errors = validate_registry($registry);
    if (!empty($errors)) {
        throw new Exception("Validation failed: " . implode("; ", $errors));
    }
    
    $json = json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new Exception("Failed to encode registry to JSON: " . json_last_error_msg());
    }
    
    $temp_file = REGISTRY_PATH . '.tmp';
    $fp = fopen($temp_file, 'c+');
    if ($fp === false) {
        throw new Exception("Failed to create temp file");
    }
    
    // Acquire exclusive lock
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new Exception("Failed to acquire file lock");
    }
    
    // Write to temp file
    ftruncate($fp, 0);
    fseek($fp, 0);
    $written = fwrite($fp, $json);
    fflush($fp);
    
    if ($written === false || $written !== strlen($json)) {
        flock($fp, LOCK_UN);
        fclose($fp);
        unlink($temp_file);
        throw new Exception("Failed to write registry data");
    }
    
    // Atomic rename
    if (!rename($temp_file, REGISTRY_PATH)) {
        flock($fp, LOCK_UN);
        fclose($fp);
        unlink($temp_file);
        throw new Exception("Failed to rename temp file to registry");
    }
    
    // Release lock and close
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * Validate registry structure and data integrity
 * @param array $registry Registry data
 * @return array List of error messages (empty if valid)
 */
function validate_registry(array $registry): array {
    $errors = [];
    
    // Check required top-level keys
    $required_keys = ['schema', 'enums', 'mix_profiles', 'assets', 'cues', 'bindings'];
    foreach ($required_keys as $key) {
        if (!isset($registry[$key])) {
            $errors[] = "Missing required key: {$key}";
        }
    }
    
    if (!empty($errors)) {
        return $errors;
    }
    
    // Validate enums structure
    if (!isset($registry['enums']['asset_source_type']) || !is_array($registry['enums']['asset_source_type'])) {
        $errors[] = "Invalid enums.asset_source_type";
    }
    if (!isset($registry['enums']['cue_role']) || !is_array($registry['enums']['cue_role'])) {
        $errors[] = "Invalid enums.cue_role";
    }
    if (!isset($registry['enums']['binding_type']) || !is_array($registry['enums']['binding_type'])) {
        $errors[] = "Invalid enums.binding_type";
    }
    if (!isset($registry['enums']['override_mode']) || !is_array($registry['enums']['override_mode'])) {
        $errors[] = "Invalid enums.override_mode";
    }
    
    // Validate assets
    if (!is_array($registry['assets'])) {
        $errors[] = "assets must be an array";
    } else {
        $asset_ids = [];
        foreach ($registry['assets'] as $idx => $asset) {
            if (!isset($asset['asset_id'])) {
                $errors[] = "Asset at index {$idx} missing asset_id";
                continue;
            }
            $asset_id = $asset['asset_id'];
            if (isset($asset_ids[$asset_id])) {
                $errors[] = "Duplicate asset_id: {$asset_id}";
            }
            $asset_ids[$asset_id] = true;
            
            if (!isset($asset['source']['type']) || !in_array($asset['source']['type'], $registry['enums']['asset_source_type'])) {
                $errors[] = "Asset {$asset_id} has invalid source.type";
            }
        }
    }
    
    // Validate cues
    if (!is_array($registry['cues'])) {
        $errors[] = "cues must be an array";
    } else {
        $cue_ids = [];
        $asset_ids_map = array_flip(array_column($registry['assets'] ?? [], 'asset_id'));
        
        foreach ($registry['cues'] as $idx => $cue) {
            if (!isset($cue['cue_id'])) {
                $errors[] = "Cue at index {$idx} missing cue_id";
                continue;
            }
            $cue_id = $cue['cue_id'];
            if (isset($cue_ids[$cue_id])) {
                $errors[] = "Duplicate cue_id: {$cue_id}";
            }
            $cue_ids[$cue_id] = true;
            
            if (!isset($cue['asset_ref'])) {
                $errors[] = "Cue {$cue_id} missing asset_ref";
            } elseif (!isset($asset_ids_map[$cue['asset_ref']])) {
                $errors[] = "Cue {$cue_id} references non-existent asset: {$cue['asset_ref']}";
            }
            
            if (!isset($cue['role']) || !in_array($cue['role'], $registry['enums']['cue_role'])) {
                $errors[] = "Cue {$cue_id} has invalid role";
            }
            
            if (isset($cue['override']['mode'])) {
                if (!in_array($cue['override']['mode'], $registry['enums']['override_mode'])) {
                    $errors[] = "Cue {$cue_id} has invalid override.mode";
                }
                if ($cue['override']['mode'] === 'exclusive') {
                    if (isset($cue['override']['exclusive_stop_mode']) && 
                        !in_array($cue['override']['exclusive_stop_mode'], $registry['enums']['exclusive_stop_mode'] ?? [])) {
                        $errors[] = "Cue {$cue_id} has invalid exclusive_stop_mode";
                    }
                    if (isset($cue['override']['exclusive_resume_mode']) && 
                        !in_array($cue['override']['exclusive_resume_mode'], $registry['enums']['exclusive_resume_mode'] ?? [])) {
                        $errors[] = "Cue {$cue_id} has invalid exclusive_resume_mode";
                    }
                }
            }
        }
    }
    
    // Validate bindings
    if (!is_array($registry['bindings'])) {
        $errors[] = "bindings must be an array";
    } else {
        $binding_ids = [];
        $cue_ids_map = array_flip(array_column($registry['cues'] ?? [], 'cue_id'));
        
        foreach ($registry['bindings'] as $idx => $binding) {
            if (!isset($binding['binding_id'])) {
                $errors[] = "Binding at index {$idx} missing binding_id";
                continue;
            }
            $binding_id = $binding['binding_id'];
            if (isset($binding_ids[$binding_id])) {
                $errors[] = "Duplicate binding_id: {$binding_id}";
            }
            $binding_ids[$binding_id] = true;
            
            if (!isset($binding['binding_type']) || !in_array($binding['binding_type'], $registry['enums']['binding_type'])) {
                $errors[] = "Binding {$binding_id} has invalid binding_type";
            }
            
            if (!isset($binding['play_cue_ref'])) {
                $errors[] = "Binding {$binding_id} missing play_cue_ref";
            } elseif (!isset($cue_ids_map[$binding['play_cue_ref']])) {
                $errors[] = "Binding {$binding_id} references non-existent cue: {$binding['play_cue_ref']}";
            }
        }
    }
    
    return $errors;
}

/**
 * Check if an ID is unique within a collection
 * @param array $registry Registry data
 * @param string $type Collection type: 'asset', 'cue', or 'binding'
 * @param string $id ID to check
 * @param string|null $exclude_id Optional ID to exclude from check (for updates)
 * @return bool True if unique
 */
function ensure_unique_id(array $registry, string $type, string $id, ?string $exclude_id = null): bool {
    $collection_key = $type === 'asset' ? 'assets' : ($type === 'cue' ? 'cues' : 'bindings');
    $id_key = $type === 'asset' ? 'asset_id' : ($type === 'cue' ? 'cue_id' : 'binding_id');
    
    if (!isset($registry[$collection_key]) || !is_array($registry[$collection_key])) {
        return true;
    }
    
    foreach ($registry[$collection_key] as $item) {
        if (!isset($item[$id_key])) {
            continue;
        }
        if ($item[$id_key] === $id && ($exclude_id === null || $item[$id_key] !== $exclude_id)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get registry file metadata
 * @return array File path and last modified time
 */
function get_registry_metadata(): array {
    $path = realpath(REGISTRY_PATH);
    $modified = file_exists(REGISTRY_PATH) ? filemtime(REGISTRY_PATH) : null;
    
    return [
        'path' => $path ?: REGISTRY_PATH,
        'modified' => $modified,
        'modified_formatted' => $modified ? date('Y-m-d H:i:s', $modified) : 'Never'
    ];
}
