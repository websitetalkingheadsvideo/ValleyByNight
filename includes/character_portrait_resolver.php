<?php
declare(strict_types=1);

/**
 * Character portrait resolution for admin/UI.
 * Single source of truth: portrait is resolved from DB fields + filesystem.
 * Checks upload_dir first, then reference_dir (e.g. reference/Characters/Images) with exact and display-name variants.
 *
 * @param string $upload_dir Directory where the app serves images (uploads/characters).
 * @param string|null $reference_dir Optional fallback directory (e.g. reference/Characters/Images) to find images not yet in uploads.
 * @return array{resolved_filename: string|null, attempted_reference: string|null, source_path: string|null} source_path set when file was found in reference_dir (caller may copy to upload_dir).
 */
function resolve_character_portrait(
    string $character_name,
    ?string $portrait_name,
    ?string $character_image,
    string $upload_dir,
    ?string $reference_dir = null
): array {
    $character_name = trim($character_name);
    $upload_dir = rtrim(str_replace('\\', '/', $upload_dir), '/') . '/';
    $reference_dir = $reference_dir !== null && $reference_dir !== '' ? rtrim(str_replace('\\', '/', $reference_dir), '/') . '/' : null;

    $normalize_image_name = static function ($value): string {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }
        $normalized = str_replace('\\', '/', $normalized);
        $parsed_path = parse_url($normalized, PHP_URL_PATH);
        if (is_string($parsed_path) && $parsed_path !== '') {
            $normalized = $parsed_path;
        }
        return trim((string) basename($normalized));
    };

    $extensions = ['png', 'webp', 'jpg', 'jpeg', 'jfif', 'gif'];

    $try_dirs = static function (string $candidate) use ($upload_dir, $reference_dir, $extensions): array {
        $try_one = static function (string $name, string $dir): ?string {
            return is_file($dir . $name) ? $name : null;
        };
        if ($try_one($candidate, $upload_dir) !== null) {
            return ['resolved_filename' => $candidate, 'attempted_reference' => $candidate, 'source_path' => null];
        }
        if ($reference_dir !== null) {
            if ($try_one($candidate, $reference_dir) !== null) {
                return ['resolved_filename' => $candidate, 'attempted_reference' => $candidate, 'source_path' => $reference_dir . $candidate];
            }
            $display = str_replace('_', ' ', $candidate);
            if ($display !== $candidate && $try_one($display, $reference_dir) !== null) {
                return ['resolved_filename' => $candidate, 'attempted_reference' => $candidate, 'source_path' => $reference_dir . $display];
            }
        }
        return [];
    };

    $try_dirs_with_extension_fallback = static function (string $candidate) use ($upload_dir, $reference_dir, $extensions, $try_dirs): array {
        $out = $try_dirs($candidate);
        if ($out !== []) {
            return $out;
        }
        $base = pathinfo($candidate, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
        foreach ($extensions as $e) {
            if ($e === $ext) {
                continue;
            }
            $alt = $base . '.' . $e;
            $out = $try_dirs($alt);
            if ($out !== []) {
                $out['resolved_filename'] = $alt;
                return $out;
            }
        }
        return [];
    };

    $resolved = null;
    $attempted = null;

    // 1) Explicit portrait_name if the referenced file exists (exact name or alternate extension).
    if ($portrait_name !== null && trim($portrait_name) !== '') {
        $candidate = $normalize_image_name($portrait_name);
        if ($candidate !== '') {
            $attempted = $candidate;
            $out = $try_dirs_with_extension_fallback($candidate);
            if ($out !== []) {
                return $out;
            }
        }
    }

    // 2) Character-name based file discovery.
    if ($character_name !== '') {
        $bases = [$character_name, str_replace(' ', '_', $character_name)];
        $extensions = ['png', 'webp', 'jpg', 'jpeg', 'jfif', 'gif'];
        foreach ($bases as $base_name) {
            foreach ($extensions as $extension) {
                $candidate = $base_name . '.' . $extension;
                if ($attempted === null) {
                    $attempted = $candidate;
                }
                $out = $try_dirs($candidate);
                if ($out !== []) {
                    return $out;
                }
            }
        }
    }

    // 3) character_image field (exact name or alternate extension).
    if ($character_image !== null && trim($character_image) !== '') {
        $candidate = $normalize_image_name($character_image);
        if ($candidate !== '') {
            if ($attempted === null) {
                $attempted = $candidate;
            }
            $out = $try_dirs_with_extension_fallback($candidate);
            if ($out !== []) {
                return $out;
            }
        }
    }

    return ['resolved_filename' => null, 'attempted_reference' => $attempted, 'source_path' => null];
}
