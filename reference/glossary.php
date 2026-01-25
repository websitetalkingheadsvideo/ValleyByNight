<?php
/**
 * glossary.php
 * 
 * Displays the World of Darkness Glossary with search functionality
 */

declare(strict_types=1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Path to the glossary markdown file
$glossary_file = __DIR__ . '/Books_summaries/WoD_Glossary.md';

// Read and parse the markdown file
$glossary_content = '';
$glossary_data = [];

if (file_exists($glossary_file)) {
    $markdown = file_get_contents($glossary_file);
    
    // Parse markdown into structured data
    $lines = explode("\n", $markdown);
    $current_section = '';
    $current_term = '';
    $current_content = [];
    $in_content = false;
    $skip_until_section = true; // Skip title and intro
    
    foreach ($lines as $line) {
        $line = rtrim($line);
        
        // Skip the main title (#) and intro text until first section
        if ($skip_until_section) {
            if (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                $skip_until_section = false;
                $current_section = $matches[1];
            }
            continue;
        }
        
        // Skip horizontal rules (---)
        if (preg_match('/^---+$/', $line)) {
            continue;
        }
        
        // Main section headers (##)
        if (preg_match('/^##\s+(.+)$/', $line, $matches)) {
            // Save previous term if exists
            if (!empty($current_term) && !empty($current_content)) {
                $glossary_data[] = [
                    'section' => $current_section,
                    'term' => $current_term,
                    'content' => trim(implode("\n", $current_content))
                ];
            }
            $current_section = $matches[1];
            $current_term = '';
            $current_content = [];
            $in_content = false;
            continue;
        }
        
        // Term headers (###)
        if (preg_match('/^###\s+(.+)$/', $line, $matches)) {
            // Save previous term if exists
            if (!empty($current_term) && !empty($current_content)) {
                $glossary_data[] = [
                    'section' => $current_section,
                    'term' => $current_term,
                    'content' => trim(implode("\n", $current_content))
                ];
            }
            $current_term = $matches[1];
            $current_content = [];
            $in_content = true;
            continue;
        }
        
        // Collect content for current term
        if ($in_content && !empty($current_term)) {
            $current_content[] = $line;
        }
    }
    
    // Add the last term
    if (!empty($current_term) && !empty($current_content)) {
        $glossary_data[] = [
            'section' => $current_section,
            'term' => $current_term,
            'content' => trim(implode("\n", $current_content))
        ];
    }
}

// Set up page-specific CSS and JS
$extra_css = ['css/glossary.css'];
$extra_js = ['js/glossary_search.js'];

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-wrapper">
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-light mb-3">World of Darkness Glossary</h1>
                <p class="text-light mb-4">Definitions of World of Darkness terms based on their usage throughout the source material.</p>
                
                <!-- Search Box -->
                <div class="glossary-search mb-4">
                    <div class="input-group">
                        <input type="text" 
                               id="glossary-search-input" 
                               class="form-control" 
                               placeholder="Search terms, definitions, or sections..." 
                               aria-label="Search glossary">
                        <button class="btn btn-danger" type="button" id="glossary-search-clear" style="display: none;">
                            Clear
                        </button>
                    </div>
                    <div id="glossary-search-results" class="mt-2 text-light"></div>
                </div>
            </div>
        </div>

        <?php if (empty($glossary_data)): ?>
            <div class="alert alert-warning">
                <strong>No glossary data found.</strong> The glossary file could not be loaded or parsed.
            </div>
        <?php else: ?>
            <div id="glossary-content" class="glossary-container">
                <?php
                $current_section = '';
                foreach ($glossary_data as $index => $entry):
                    // Start new section
                    if ($entry['section'] !== $current_section):
                        if ($current_section !== ''):
                            echo '</div>'; // Close previous section
                        endif;
                        $current_section = $entry['section'];
                        echo '<div class="glossary-section mb-5" data-section="' . htmlspecialchars(strtolower($current_section), ENT_QUOTES, 'UTF-8') . '">';
                        echo '<h2 class="text-danger mb-4">' . htmlspecialchars($current_section) . '</h2>';
                    endif;
                    
                    // Display term
                    $term_id = 'term-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($entry['term']));
                    $searchable_text = strtolower($entry['term'] . ' ' . $entry['section'] . ' ' . $entry['content']);
                    ?>
                    <div class="glossary-term card mb-3" 
                         data-term="<?php echo htmlspecialchars(strtolower($entry['term']), ENT_QUOTES, 'UTF-8'); ?>"
                         data-searchable="<?php echo htmlspecialchars($searchable_text, ENT_QUOTES, 'UTF-8'); ?>"
                         id="<?php echo htmlspecialchars($term_id, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="card-header bg-dark border-danger">
                            <h3 class="card-title mb-0 text-light">
                                <?php echo htmlspecialchars($entry['term']); ?>
                            </h3>
                        </div>
                        <div class="card-body bg-dark text-light">
                            <?php
                            // Parse and display content (convert markdown-like formatting to HTML)
                            $content = $entry['content'];
                            
                            // Split content into paragraphs (double newlines)
                            $paragraphs = preg_split('/\n\s*\n/', $content);
                            $processed_content = [];
                            
                            foreach ($paragraphs as $para) {
                                $para = trim($para);
                                if (empty($para)) {
                                    continue;
                                }
                                
                                // Check if this is a "From" citation
                                if (preg_match('/^\*\*From\s+"([^"]+)"\s*\(([^)]+)\):\*\*/', $para, $matches)) {
                                    $processed_content[] = '<div class="citation"><strong>From "' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '" (' . htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8') . '):</strong></div>';
                                    $para = preg_replace('/^\*\*From\s+"[^"]+"\s*\([^)]+\):\*\*\s*/', '', $para);
                                } elseif (preg_match('/^\*\*From\s+"([^"]+)":\*\*/', $para, $matches)) {
                                    $processed_content[] = '<div class="citation"><strong>From "' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '":</strong></div>';
                                    $para = preg_replace('/^\*\*From\s+"[^"]+":\*\*\s*/', '', $para);
                                } elseif (preg_match('/^\*\*From\s+([^:]+):\*\*/', $para, $matches)) {
                                    $processed_content[] = '<div class="citation"><strong>From ' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . ':</strong></div>';
                                    $para = preg_replace('/^\*\*From\s+[^:]+:\*\*\s*/', '', $para);
                                }
                                
                                // Check if paragraph contains list items
                                $lines = explode("\n", $para);
                                $in_list = false;
                                $list_items = [];
                                
                                foreach ($lines as $line) {
                                    $line = trim($line);
                                    if (empty($line)) {
                                        continue;
                                    }
                                    
                                    // Check for list item
                                    if (preg_match('/^-\s+(.+)$/', $line, $matches)) {
                                        if (!$in_list) {
                                            $in_list = true;
                                        }
                                        $list_items[] = '<li>' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</li>';
                                    } else {
                                        // End list if we were in one
                                        if ($in_list && !empty($list_items)) {
                                            $processed_content[] = '<ul>' . implode('', $list_items) . '</ul>';
                                            $list_items = [];
                                            $in_list = false;
                                        }
                                        
                                        // Process regular line (convert **bold** to <strong>)
                                        $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', htmlspecialchars($line, ENT_QUOTES, 'UTF-8'));
                                        $processed_content[] = '<p>' . $line . '</p>';
                                    }
                                }
                                
                                // Close any remaining list
                                if ($in_list && !empty($list_items)) {
                                    $processed_content[] = '<ul>' . implode('', $list_items) . '</ul>';
                                }
                            }
                            
                            echo '<div class="glossary-definition">' . implode('', $processed_content) . '</div>';
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div> <!-- Close last section -->
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
