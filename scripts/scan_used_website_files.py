import os
import re
from typing import Dict, Iterable, List, Set, Tuple


ROOT: str = r"G:\VbN"

# Directory basenames that must never be touched (any depth in tree)
PROTECTED_DIR_BASENAMES: Set[str] = {
    "Prompts",
    ".cursor",
    ".taskmaster",
    ".git",
    "Added to Database",
    "Books",
    "images",
    "game-lore",
}

# Entry-point PHP files that represent real site pages.
# These are treated as initial seeds; everything else is discovered via references.
ENTRYPOINT_FILES: List[str] = [
    "index.php",
    "login.php",
    "register.php",
    "account.php",
    "questionnaire.php",
    "chat.php",
    "lotn_char_create.php",
]


def discover_protected_dirs(root: str) -> Set[str]:
    """
    Walk the tree and return absolute paths of all directories whose basename
    matches a protected name.
    """
    protected: Set[str] = set()
    for dirpath, dirnames, _ in os.walk(root):
        basename: str = os.path.basename(dirpath)
        if basename in PROTECTED_DIR_BASENAMES:
            protected.add(os.path.normpath(dirpath))
    return protected


def is_under_any(path: str, dirs: Set[str]) -> bool:
    """
    Check whether 'path' is at or under any directory in 'dirs'.
    """
    norm_path: str = os.path.normpath(path)
    for base in dirs:
        # Ensure we match full path segments
        if norm_path == base or norm_path.startswith(base + os.sep):
            return True
    return False


def build_entrypoint_paths(root: str) -> List[str]:
    """
    Build a list of absolute paths for configured entrypoint files that exist.
    """
    paths: List[str] = []
    for name in ENTRYPOINT_FILES:
        candidate: str = os.path.normpath(os.path.join(root, name))
        if os.path.isfile(candidate):
            paths.append(candidate)
    return paths


def extract_php_includes(contents: str) -> List[str]:
    """
    Extract paths from PHP include/require statements.
    """
    pattern = re.compile(
        r"""(?:(?:include|include_once|require|require_once)\s*\(?\s*['"]([^'"]+)['"])""",
        re.IGNORECASE,
    )
    return [match.group(1) for match in pattern.finditer(contents)]


def extract_html_attr_paths(contents: str) -> List[str]:
    """
    Extract local href/src/action attribute paths from HTML.
    """
    pattern = re.compile(
        r"""(?:href|src|action)\s*=\s*['"]([^"'#]+)['"]""",
        re.IGNORECASE,
    )
    matches: List[str] = []
    for match in pattern.finditer(contents):
        value: str = match.group(1).strip()
        if not value:
            continue
        # Ignore full external URLs
        if value.startswith("http://") or value.startswith("https://") or value.startswith("//"):
            continue
        matches.append(value)
    return matches


def extract_js_paths(contents: str) -> List[str]:
    """
    Extract local paths from common JS patterns (fetch, $.ajax, axios).
    """
    paths: List[str] = []

    fetch_pattern = re.compile(r"""fetch\(\s*['"]([^'"]+)['"]""", re.IGNORECASE)
    ajax_pattern = re.compile(r"""url\s*:\s*['"]([^'"]+)['"]""", re.IGNORECASE)
    axios_pattern = re.compile(r"""axios\.(?:get|post|put|delete)\(\s*['"]([^'"]+)['"]""", re.IGNORECASE)

    for pattern in (fetch_pattern, ajax_pattern, axios_pattern):
        for match in pattern.finditer(contents):
            value: str = match.group(1).strip()
            if not value:
                continue
            if value.startswith("http://") or value.startswith("https://") or value.startswith("//"):
                continue
            paths.append(value)

    return paths


def extract_css_url_paths(contents: str) -> List[str]:
    """
    Extract url(...) references from CSS content.
    """
    pattern = re.compile(r"""url\(\s*['"]?([^'")]+)['"]?\s*\)""", re.IGNORECASE)
    matches: List[str] = []
    for match in pattern.finditer(contents):
        value: str = match.group(1).strip()
        if not value:
            continue
        if value.startswith("http://") or value.startswith("https://") or value.startswith("//"):
            continue
        matches.append(value)
    return matches


def resolve_path(current_file: str, ref: str, root: str) -> str:
    """
    Resolve a referenced path (relative or root-relative) to an absolute
    path under ROOT. Returns an empty string if it cannot be resolved inside root.
    """
    # Strip query and fragment
    ref_clean: str = ref.split("?", 1)[0].split("#", 1)[0]
    if not ref_clean:
        return ""

    # Ignore external schemes
    if ref_clean.startswith("http://") or ref_clean.startswith("https://") or ref_clean.startswith("//"):
        return ""

    if ref_clean.startswith("/"):
        abs_path: str = os.path.join(root, ref_clean.lstrip("/"))
    else:
        base_dir: str = os.path.dirname(current_file)
        abs_path = os.path.join(base_dir, ref_clean)

    abs_path = os.path.normpath(abs_path)

    if not abs_path.startswith(os.path.normpath(root) + os.sep) and abs_path != os.path.normpath(root):
        return ""

    if os.path.exists(abs_path):
        return abs_path

    return ""


def read_text_file(path: str) -> str:
    """
    Read text file contents using utf-8 with fallback to latin-1.
    """
    try:
        with open(path, "r", encoding="utf-8") as f:
            return f.read()
    except UnicodeDecodeError:
        with open(path, "r", encoding="latin-1") as f:
            return f.read()


def collect_references_for_file(path: str, root: str) -> List[str]:
    """
    Parse a single file (PHP/HTML/JS/CSS) and return a list of referenced paths.
    """
    contents: str = read_text_file(path)
    refs: List[str] = []

    lower: str = contents.lower()

    ext: str = os.path.splitext(path)[1].lower()

    if ext in {".php", ".html", ".htm", ".js"}:
        refs.extend(extract_php_includes(contents))
        refs.extend(extract_html_attr_paths(contents))
        refs.extend(extract_js_paths(contents))

    if ext == ".css":
        refs.extend(extract_css_url_paths(contents))

    resolved: List[str] = []
    for ref in refs:
        target: str = resolve_path(path, ref, root)
        if target:
            resolved.append(target)

    return resolved


def build_used_file_set(root: str, protected_dirs: Set[str]) -> Set[str]:
    """
    Build the set of files that are actually used by the website based on
    static reference analysis starting from entrypoint files.
    """
    used: Set[str] = set()
    queue: List[str] = []

    entrypoints: List[str] = build_entrypoint_paths(root)
    for ep in entrypoints:
        used.add(os.path.normpath(ep))
        queue.append(os.path.normpath(ep))

    # Always keep this script and the manifest itself
    this_script: str = os.path.normpath(os.path.join(root, "scripts", "scan_used_website_files.py"))
    manifest_path: str = os.path.normpath(os.path.join(root, "do_not_delete.txt"))
    used.add(this_script)
    used.add(manifest_path)

    visited: Set[str] = set()

    while queue:
        current: str = queue.pop()
        if current in visited:
            continue
        visited.add(current)

        # Never traverse into protected dirs, but still treat the file as used
        if is_under_any(current, protected_dirs):
            continue

        ext: str = os.path.splitext(current)[1].lower()
        if ext not in {".php", ".html", ".htm", ".js", ".css"}:
            continue

        try:
            refs: List[str] = collect_references_for_file(current, root)
        except (OSError, UnicodeDecodeError):
            continue

        for target in refs:
            if not target:
                continue
            norm_target: str = os.path.normpath(target)
            if not os.path.isfile(norm_target):
                continue
            if is_under_any(norm_target, protected_dirs):
                # Protected dirs are always kept, no need to queue for parsing
                used.add(norm_target)
                continue
            if norm_target not in used:
                used.add(norm_target)
                # Only parse certain types further
                target_ext: str = os.path.splitext(norm_target)[1].lower()
                if target_ext in {".php", ".html", ".htm", ".js", ".css"}:
                    queue.append(norm_target)

    return used


def collect_all_protected_files(root: str, protected_dirs: Set[str]) -> Set[str]:
    """
    Collect all files located under any protected directory.
    """
    files: Set[str] = set()
    for dirpath, dirnames, filenames in os.walk(root):
        if is_under_any(dirpath, protected_dirs):
            for filename in filenames:
                full_path: str = os.path.normpath(os.path.join(dirpath, filename))
                files.add(full_path)
            # Do not descend further manually; is_under_any will keep matching
    return files


def write_manifest(root: str, used_files: Set[str], protected_dirs: Set[str]) -> str:
    """
    Write do_not_delete.txt manifest listing all used files and everything
    under protected directories.
    """
    manifest_path: str = os.path.normpath(os.path.join(root, "do_not_delete.txt"))

    all_protected_files: Set[str] = collect_all_protected_files(root, protected_dirs)
    combined: Set[str] = set(used_files)
    combined.update(all_protected_files)

    lines: List[str] = []
    lines.append("# DO NOT DELETE: files listed in this manifest are required by the website")
    lines.append("# Generated by scripts/scan_used_website_files.py")
    lines.append("")

    for path in sorted(combined):
        lines.append(path)

    contents: str = "\n".join(lines) + "\n"

    with open(manifest_path, "w", encoding="utf-8") as f:
        f.write(contents)

    return manifest_path


def compute_delete_candidates(root: str, protected_dirs: Set[str], keep_files: Set[str]) -> List[str]:
    """
    Compute list of files that should be deleted:
    - Not under any protected directory
    - Not present in keep_files
    """
    candidates: List[str] = []
    norm_root: str = os.path.normpath(root)

    for dirpath, dirnames, filenames in os.walk(root):
        # Skip protected dirs entirely
        dir_basename: str = os.path.basename(dirpath)
        if dir_basename in PROTECTED_DIR_BASENAMES:
            dirnames[:] = []
            continue

        if is_under_any(dirpath, protected_dirs):
            continue

        for filename in filenames:
            full_path: str = os.path.normpath(os.path.join(dirpath, filename))
            if full_path in keep_files:
                continue
            candidates.append(full_path)

    return candidates


def delete_files(paths: Iterable[str]) -> None:
    """
    Delete all files in the provided iterable. Errors are not ignored.
    """
    for path in paths:
        if not os.path.isfile(path):
            continue
        os.remove(path)


def remove_empty_directories(root: str, protected_dirs: Set[str]) -> None:
    """
    Remove directories that become empty after deletion, excluding protected dirs.
    """
    for dirpath, dirnames, filenames in os.walk(root, topdown=False):
        if is_under_any(dirpath, protected_dirs):
            continue
        if dirnames or filenames:
            continue
        try:
            os.rmdir(dirpath)
        except OSError:
            # Directory not empty or cannot be removed; skip
            continue


def main() -> None:
    norm_root: str = os.path.normpath(ROOT)

    if not os.path.isdir(norm_root):
        raise RuntimeError(f"Root directory does not exist: {norm_root}")

    protected_dirs: Set[str] = discover_protected_dirs(norm_root)

    used_files: Set[str] = build_used_file_set(norm_root, protected_dirs)

    manifest_path: str = write_manifest(norm_root, used_files, protected_dirs)

    # Recompute keep set including everything in manifest (which already includes protected files)
    keep_files: Set[str] = set()
    with open(manifest_path, "r", encoding="utf-8") as f:
        for line in f:
            stripped: str = line.strip()
            if not stripped or stripped.startswith("#"):
                continue
            keep_files.add(os.path.normpath(stripped))

    delete_candidates: List[str] = compute_delete_candidates(norm_root, protected_dirs, keep_files)

    delete_files(delete_candidates)
    remove_empty_directories(norm_root, protected_dirs)


if __name__ == "__main__":
    main()


