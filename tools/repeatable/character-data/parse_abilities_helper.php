<?php
/**
 * Parse abilities from character JSON.
 * Used by index.php (has_data + update) and sync_abilities_from_json.php.
 *
 * Reads from:
 * - abilities array (string "Name N" or object with name/category/level/specialization).
 * - specializations map (ability name => spec) to fill missing specialization.
 *
 * When abilities is empty but specializations is a non-empty object
 * ({"Ability": "spec", ...}), derives ability entries: name = key,
 * specialization = value, level = 1, category = ''.
 */

declare(strict_types=1);

/**
 * @return list<array{name: string, category: string, level: int, specialization: string}>
 */
function parse_abilities_from_character_json(array $json_data): array {
    $out = [];
    $abilities = $json_data['abilities'] ?? null;
    $specs_map = $json_data['specializations'] ?? [];
    if (!is_array($specs_map)) {
        $specs_map = [];
    }

    if (is_array($abilities) && count($abilities) > 0) {
        foreach ($abilities as $ability) {
            $name = '';
            $category = '';
            $level = 0;
            $specialization = '';

            if (is_string($ability)) {
                $s = trim($ability);
                if (preg_match('/^(.+?)\s+(\d+)(?:\s*\([^)]+\))?\s*$/', $s, $m)) {
                    $name = trim($m[1]);
                    $level = (int)$m[2];
                    if (preg_match('/\(([^)]+)\)/', $s, $sm)) {
                        $specialization = trim(preg_replace('/^Specialization:\s*/i', '', $sm[1]));
                    }
                } else {
                    $name = $s;
                }
                if ($name !== '' && $specialization === '' && isset($specs_map[$name])) {
                    $specialization = is_string($specs_map[$name]) ? trim($specs_map[$name]) : '';
                }
            } elseif (is_array($ability)) {
                $name = trim($ability['name'] ?? $ability['ability_name'] ?? '');
                $category = trim($ability['category'] ?? $ability['ability_category'] ?? '');
                $level = isset($ability['level']) ? (int)$ability['level'] : 0;
                $specialization = trim($ability['specialization'] ?? '');
                if ($name !== '' && $specialization === '' && isset($specs_map[$name])) {
                    $specialization = is_string($specs_map[$name]) ? trim($specs_map[$name]) : '';
                }
            }

            if ($name !== '') {
                $out[] = ['name' => $name, 'category' => $category, 'level' => $level, 'specialization' => $specialization];
            }
        }
        return $out;
    }

    $keys = array_keys($specs_map);
    $is_object_specs = count($specs_map) > 0 && $keys !== range(0, count($specs_map) - 1);
    if (!$is_object_specs) {
        return $out;
    }

    foreach ($specs_map as $ab_name => $spec_val) {
        $name = is_string($ab_name) ? trim($ab_name) : '';
        if ($name === '') {
            continue;
        }
        $specialization = is_string($spec_val) ? trim($spec_val) : '';
        $out[] = ['name' => $name, 'category' => '', 'level' => 1, 'specialization' => $specialization];
    }
    return $out;
}
