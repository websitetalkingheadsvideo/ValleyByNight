<?php
/**
 * Valley by Night - Phoenix Clanbook Viewer
 * Allows selection and viewing of Phoenix-localized clanbooks
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the base path for includes (go up 2 levels from reference/docs/ to root)
$base_path = dirname(dirname(__DIR__));

// Define clans with their display names and file slugs
$clans = [
    'brujah' => 'Brujah',
    'ventrue' => 'Ventrue',
    'toreador' => 'Toreador',
    'nosferatu' => 'Nosferatu',
    'malkavian' => 'Malkavian',
    'giovanni' => 'Giovanni',
    'tremere' => 'Tremere',
    'gangrel' => 'Gangrel',
    'assamite' => 'Assamite',
    'lasombra' => 'Lasombra',
    'ravnos' => 'Ravnos',
    'tzimisce' => 'Tzimisce',
    'samedi' => 'Samedi',
    'followers_of_set' => 'Followers of Set',
    'daughters_of_cacophony' => 'Daughters of Cacophony',
    'baali' => 'Baali',
    'cappadocian' => 'Cappadocian'
];

// Get selected clan from query parameter
$selected_clan = isset($_GET['clan']) ? $_GET['clan'] : '';
$clan_content = '';
$clan_file = '';

if ($selected_clan && isset($clans[$selected_clan])) {
    $clan_file = __DIR__ . '/clanbook-phoenix-' . $selected_clan . '.md';
    if (file_exists($clan_file)) {
        $clan_content = file_get_contents($clan_file);
    } else {
        $clan_content = "**Error:** Clanbook file not found.";
    }
}

// Markdown to HTML converter function
function markdownToHtml($markdown) {
    $lines = explode("\n", $markdown);
    $html = '';
    $in_list = false;
    $in_paragraph = false;
    $paragraph_text = '';
    
    foreach ($lines as $line) {
        $line = rtrim($line);
        
        // Horizontal rule
        if (preg_match('/^---+$/', $line)) {
            if ($in_paragraph) {
                $html .= '<p>' . processInlineMarkdown($paragraph_text) . '</p>';
                $paragraph_text = '';
                $in_paragraph = false;
            }
            if ($in_list) {
                $html .= '</ul>';
                $in_list = false;
            }
            $html .= '<hr>';
            continue;
        }
        
        // Headers
        if (preg_match('/^(#{1,4})\s+(.+)$/', $line, $matches)) {
            if ($in_paragraph) {
                $html .= '<p>' . processInlineMarkdown($paragraph_text) . '</p>';
                $paragraph_text = '';
                $in_paragraph = false;
            }
            if ($in_list) {
                $html .= '</ul>';
                $in_list = false;
            }
            $level = strlen($matches[1]);
            $text = $matches[2];
            $html .= "<h$level>" . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . "</h$level>\n";
            continue;
        }
        
        // List items
        if (preg_match('/^[\s]*- (.+)$/', $line, $matches)) {
            if ($in_paragraph) {
                $html .= '<p>' . processInlineMarkdown($paragraph_text) . '</p>';
                $paragraph_text = '';
                $in_paragraph = false;
            }
            if (!$in_list) {
                $html .= '<ul>';
                $in_list = true;
            }
            $html .= '<li>' . processInlineMarkdown($matches[1]) . '</li>';
            continue;
        } else {
            if ($in_list) {
                $html .= '</ul>';
                $in_list = false;
            }
        }
        
        // Empty line ends paragraph
        if ($line === '') {
            if ($in_paragraph && $paragraph_text !== '') {
                $html .= '<p>' . processInlineMarkdown($paragraph_text) . '</p>';
                $paragraph_text = '';
                $in_paragraph = false;
            }
            continue;
        }
        
        // Regular text line
        if ($paragraph_text !== '') {
            $paragraph_text .= ' ';
        }
        $paragraph_text .= $line;
        $in_paragraph = true;
    }
    
    // Close any open tags
    if ($in_paragraph && $paragraph_text !== '') {
        $html .= '<p>' . processInlineMarkdown($paragraph_text) . '</p>';
    }
    if ($in_list) {
        $html .= '</ul>';
    }
    
    return $html;
}

// Process inline markdown (bold, italic, links)
function processInlineMarkdown($text) {
    // Escape HTML first
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // Links [text](url)
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);
    
    // Bold **text**
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    
    // Italic *text* (but not if it's part of **text**)
    $text = preg_replace('/(?<!\*)\*([^\*]+?)\*(?!\*)/', '<em>$1</em>', $text);
    
    return $text;
}

// Convert content if selected
$html_content = '';
if ($clan_content) {
    $html_content = markdownToHtml($clan_content);
}

// Include header
$extra_css = ['css/dashboard.css'];
require_once $base_path . '/includes/header.php';
?>

<div class="page-content container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Phoenix Clanbooks</h1>
            <p class="lead mb-4">
                <em>Valley by Night — Phoenix, Arizona, 1994</em>
            </p>
            
            <!-- Clan Selection Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="d-flex gap-3 align-items-end">
                        <div class="flex-grow-1">
                            <label for="clan-select" class="form-label">Select Clan:</label>
                            <select name="clan" id="clan-select" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Choose a Clan --</option>
                                <?php foreach ($clans as $slug => $name): ?>
                                    <option value="<?php echo htmlspecialchars($slug); ?>" 
                                        <?php echo ($selected_clan === $slug) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">View Clanbook</button>
                    </form>
                </div>
            </div>
            
            <!-- Clanbook Content -->
            <?php if ($html_content): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="clanbook-content">
                            <?php echo $html_content; ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($selected_clan): ?>
                <div class="alert alert-warning">
                    <strong>Note:</strong> Please select a clan from the dropdown above to view its clanbook.
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <strong>Welcome!</strong> Select a clan from the dropdown above to view its Phoenix-localized clanbook.
                    Each clanbook contains setting-specific information, story hooks, and NPC seeds for Phoenix, Arizona in 1994.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.clanbook-content {
    line-height: 1.8;
    color: #e0e0e0;
}

.clanbook-content h1 {
    color: #c9a961;
    margin-top: 2rem;
    margin-bottom: 1rem;
    border-bottom: 2px solid #8B4513;
    padding-bottom: 0.5rem;
}

.clanbook-content h2 {
    color: #d4af37;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid #8B4513;
    padding-bottom: 0.25rem;
}

.clanbook-content h3 {
    color: #deb887;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
}

.clanbook-content h4 {
    color: #daa520;
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}

.clanbook-content p {
    margin-bottom: 1rem;
}

.clanbook-content ul {
    margin-left: 2rem;
    margin-bottom: 1rem;
}

.clanbook-content li {
    margin-bottom: 0.5rem;
}

.clanbook-content strong {
    color: #c9a961;
    font-weight: bold;
}

.clanbook-content em {
    font-style: italic;
    color: #daa520;
}

.clanbook-content hr {
    border: none;
    border-top: 1px solid #8B4513;
    margin: 2rem 0;
}

.clanbook-content a {
    color: #d4af37;
    text-decoration: underline;
}

.clanbook-content a:hover {
    color: #c9a961;
}
</style>

<?php
require_once $base_path . '/includes/footer.php';
?>

