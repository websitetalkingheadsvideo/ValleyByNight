<?php
/**
 * Admin Navigation Header
 * Bootstrap-based responsive navigation for admin pages
 * Automatically detects active page based on current script
 */

// Get current page filename to determine active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Admin Navigation -->
<nav class="admin-nav row g-2 g-md-3 mb-4" aria-label="Admin Navigation">
    <div class="col-12 col-sm-6 col-md-4 col-lg">
        <a href="admin_panel.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center <?php echo ($current_page === 'admin_panel.php') ? 'active' : ''; ?>">👥 Characters</a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-lg">
        <a href="admin_sire_childe.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center <?php echo ($current_page === 'admin_sire_childe.php' || $current_page === 'admin_sire_childe_enhanced.php') ? 'active' : ''; ?>">🧛 Sire/Childe</a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-lg">
        <a href="admin_items.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center <?php echo ($current_page === 'admin_items.php') ? 'active' : ''; ?>">⚔️ Items</a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-lg">
        <a href="admin_locations.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center <?php echo ($current_page === 'admin_locations.php') ? 'active' : ''; ?>">🏠 Locations</a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-lg">
        <a href="questionnaire_admin.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center <?php echo ($current_page === 'questionnaire_admin.php') ? 'active' : ''; ?>">📝 Questionnaire</a>
    </div>
    <div class="col-12 col-sm-6 col-md-4 col-lg">
        <a href="admin_npc_briefing.php" class="nav-btn btn btn-outline-danger btn-sm w-100 text-center <?php echo ($current_page === 'admin_npc_briefing.php') ? 'active' : ''; ?>">📋 NPC Briefing</a>
    </div>
</nav>

