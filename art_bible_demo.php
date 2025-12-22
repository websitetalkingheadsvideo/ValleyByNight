<?php
/**
 * Art Bible Demo Page
 * Test page for Art Bible color and typography changes
 */

// Define version constant
require_once __DIR__ . '/includes/version.php';

// Start session
session_start();

// Include database connection (optional, for demo)
require_once 'includes/connect.php';

// Check for authentication bypass
require_once 'includes/auth_bypass.php';

// Check if user is logged in (or bypass is enabled)
if (!isset($_SESSION['user_id']) && !isAuthBypassEnabled()) {
    header('Location: login.php');
    exit;
}

// If bypass is enabled, set up guest session
if (isAuthBypassEnabled() && !isset($_SESSION['user_id'])) {
    setupBypassSession();
}

// Get user information
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? 'Guest';

// Include header
include 'includes/header.php';
?>

<style>
/* Demo-specific styles for testing */
.demo-section {
    margin-bottom: 3rem;
    padding: 2rem;
    background: rgba(26, 15, 15, 0.3);
    border: 2px solid rgba(139, 0, 0, 0.3);
    border-radius: 8px;
}

/* Ensure modals have blood-red radial gradient background */
.modal-content.vbn-modal-content,
.modal.show .modal-content {
    background: radial-gradient(circle at center, #8B0000 0%, #820000 40%, #1a0f0f 100%) !important;
}

.demo-section h2 {
    font-family: var(--font-brand-sc), 'IM Fell English SC', serif; /* Small Caps variant */
    color: var(--text-light);
    margin-bottom: 1.5rem;
    border-bottom: 2px solid var(--blood-red);
    padding-bottom: 0.5rem;
}

.demo-section h3 {
    font-family: var(--font-brand-sc), 'IM Fell English SC', serif; /* Small Caps variant */
    color: var(--muted-gold);
    margin-top: 1.5rem;
    margin-bottom: 1rem;
}

/* Test teal moonlight in various contexts */
.teal-accent-border {
    border-left: 4px solid var(--teal-moonlight);
    padding-left: 1rem;
    background: rgba(11, 60, 73, 0.1);
}

.teal-badge {
    background: var(--teal-moonlight);
    color: var(--text-light);
    padding: 4px 12px;
    border-radius: 4px;
    display: inline-block;
    font-size: 0.9em;
    font-weight: 600;
}

.teal-link {
    color: var(--teal-moonlight);
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: all 0.3s ease;
}

.teal-link:hover {
    color: #0d5a6b;
    border-bottom-color: var(--teal-moonlight);
}

.teal-gradient-bg {
    background: linear-gradient(135deg, rgba(11, 60, 73, 0.2) 0%, rgba(26, 15, 15, 0.4) 100%);
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid rgba(11, 60, 73, 0.4);
}

/* Gold border variants for testing */
.card-gold {
    border: 2px solid var(--muted-gold) !important;
}

.card-gold:hover {
    border-color: #e5c77d !important;
    box-shadow: 0 6px 25px rgba(212, 176, 109, 0.3) !important;
}

.btn-gold {
    background: transparent;
    border: 2px solid var(--muted-gold);
    color: var(--muted-gold);
    padding: 10px 20px;
    border-radius: 5px;
    font-family: var(--font-title), 'Libre Baskerville', serif;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-gold:hover {
    background: var(--muted-gold);
    color: var(--bg-dark);
    box-shadow: 0 5px 20px rgba(212, 176, 109, 0.4);
}

.btn-red-gold {
    background: linear-gradient(135deg, var(--blood-red) 0%, #600000 100%);
    border: 2px solid var(--muted-gold);
    color: var(--text-light);
    padding: 10px 20px;
    border-radius: 5px;
    font-family: var(--font-title), 'Libre Baskerville', serif;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.btn-red-gold:hover {
    background: linear-gradient(135deg, #b30000 0%, var(--blood-red) 100%);
    border-color: #e5c77d;
    color: var(--text-light);
    box-shadow: 0 5px 20px rgba(139, 0, 0, 0.4), 0 0 10px rgba(212, 176, 109, 0.3);
    transform: translateY(-2px);
}

/* Art Bible Danger Button - Dark red, shadowed, brighter red on hover */
.btn-danger-bible {
    background: linear-gradient(135deg, #660000 0%, #4a0000 100%);
    border: 2px solid #770000;
    color: var(--text-light);
    padding: 10px 20px;
    border-radius: 5px;
    font-family: var(--font-title), 'Libre Baskerville', serif;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5), 0 2px 8px rgba(102, 0, 0, 0.4);
}

.btn-danger-bible:hover {
    background: linear-gradient(135deg, #8B0000 0%, #660000 100%);
    border-color: #990000;
    color: var(--text-light);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6), 0 3px 10px rgba(139, 0, 0, 0.5);
    transform: translateY(-2px);
}

.table-gold-header th,
thead.table-gold-header th {
    background: linear-gradient(135deg, var(--muted-gold) 0%, #b89a5a 100%) !important;
    color: var(--bg-dark) !important;
    border-color: rgba(212, 176, 109, 0.3) !important;
}

.table-red-gradient {
    background: radial-gradient(circle at center, rgba(139, 0, 0, 0.4) 0%, rgba(139, 0, 0, 0.2) 40%, rgba(26, 15, 15, 0.6) 100%) !important;
}

.table-red-gradient thead th {
    background: linear-gradient(135deg, #8B0000 0%, #600000 100%) !important;
    color: var(--text-light) !important;
}

.table-red-gradient tbody tr {
    background: rgba(139, 0, 0, 0.1) !important;
}

.table-red-gradient tbody tr:hover {
    background: rgba(139, 0, 0, 0.2) !important;
}

.form-gold-border input,
.form-gold-border select,
.form-gold-border textarea {
    border-color: var(--muted-gold) !important;
}

.form-gold-border input:focus,
.form-gold-border select:focus,
.form-gold-border textarea:focus {
    border-color: var(--muted-gold) !important;
    box-shadow: 0 0 10px rgba(212, 176, 109, 0.3) !important;
}

.color-swatch {
    display: inline-block;
    width: 60px;
    height: 60px;
    border-radius: 4px;
    border: 2px solid rgba(139, 0, 0, 0.3);
    margin: 0.5rem;
    vertical-align: middle;
}

.color-label {
    display: inline-block;
    margin-left: 0.5rem;
    vertical-align: middle;
    color: var(--text-mid);
}

/* Art Bible Navbar Styles */
.nav-link-bible {
    color: #f5e6d3; /* Parchment text */
    text-decoration: none;
    padding: 10px 15px;
    position: relative;
    transition: all 0.3s ease;
    font-family: var(--font-title), 'Libre Baskerville', serif;
}

/* Gold hover lines - Art Bible spec */
.nav-link-bible:hover {
    color: #d4b06d; /* Muted gold */
}

.nav-link-bible:hover::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 15px;
    right: 15px;
    height: 2px;
    background: #d4b06d; /* Gold line */
}

/* Red underline for active state - Art Bible spec */
.nav-link-bible.active {
    color: #f5e6d3;
}

.nav-link-bible.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 15px;
    right: 15px;
    height: 2px;
    background: #8B0000; /* Blood red underline */
}

/* Current Implementation Navbar Styles */
.nav-link-current {
    color: #f5e6d3;
    text-decoration: none;
    padding: 10px 15px;
    position: relative;
    transition: all 0.3s ease;
    font-family: var(--font-title), 'Libre Baskerville', serif;
    text-shadow: 
        2px 2px 4px rgba(0, 0, 0, 0.8),
        0 0 10px rgba(139, 0, 0, 0.3);
}

/* Current hover - RED glow, not gold lines */
.nav-link-current:hover {
    color: #ffffff;
    text-shadow: 
        2px 2px 4px rgba(0, 0, 0, 0.8),
        0 0 15px rgba(139, 0, 0, 0.6); /* Red glow */
}

/* Current active - Bootstrap button style (background fill) */
.nav-link-current.active {
    background: rgba(139, 0, 0, 0.3);
    border-radius: 4px;
    color: #f5e6d3;
}

/* Art Bible Sidebar Styles */
.sidebar-link-bible:hover {
    background: rgba(212, 176, 109, 0.15);
    box-shadow: 0 0 10px rgba(212, 176, 109, 0.3); /* Soft gold glow */
    color: #d4b06d; /* Gold text on hover */
}

/* Current Sidebar Styles */
.sidebar-link-current:hover {
    background: rgba(139, 0, 0, 0.2);
    box-shadow: 0 0 8px rgba(139, 0, 0, 0.4); /* Red glow */
    color: #ffffff;
}
</style>

<div class="page-content container py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4" style="font-family: var(--font-brand-sc), 'IM Fell English SC', serif;">
                Art Bible Demo & Test Page
            </h1>
            <p class="lead" style="color: var(--text-mid);">
                Testing color palette changes, typography variants, and component styling
            </p>
        </div>
    </div>

    <!-- Color Palette Section -->
    <div class="demo-section">
        <h2>Color Palette</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <h3>Primary Colors</h3>
                <div>
                    <span class="color-swatch" style="background: #0d0606;"></span>
                    <span class="color-label">Gothic Black: #0d0606</span>
                </div>
                <div>
                    <span class="color-swatch" style="background: #1a0f0f;"></span>
                    <span class="color-label">Dusk Brown-Black: #1a0f0f</span>
                </div>
                <div>
                    <span class="color-swatch" style="background: #8B0000;"></span>
                    <span class="color-label">Blood Red: #8B0000</span>
                </div>
                <div>
                    <span class="color-swatch" style="background: #d4b06d;"></span>
                    <span class="color-label">Muted Gold: #d4b06d (now text-mid)</span>
                </div>
                <div>
                    <span class="color-swatch" style="background: #f5e6d3;"></span>
                    <span class="color-label">Parchment Light: #f5e6d3</span>
                </div>
            </div>
            <div class="col-md-6">
                <h3>New Colors</h3>
                <div>
                    <span class="color-swatch" style="background: #0B3C49;"></span>
                    <span class="color-label">Teal Moonlight: #0B3C49</span>
                </div>
                <div class="mt-3">
                    <h4>Teal Moonlight Usage Suggestions:</h4>
                    <ul style="color: var(--text-mid);">
                        <li>Accent borders for special sections</li>
                        <li>Link hover states</li>
                        <li>Badge backgrounds for special types</li>
                        <li>Gradient backgrounds for info panels</li>
                        <li>Secondary accent in modals</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Typography Section -->
    <div class="demo-section">
        <h2>Typography - Small Caps Headers</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <h3>Regular Headers (Current)</h3>
                <h1 style="font-family: var(--font-brand), 'IM Fell English', serif;">Heading 1 - Regular</h1>
                <h2 style="font-family: var(--font-brand), 'IM Fell English', serif;">Heading 2 - Regular</h2>
                <h3 style="font-family: var(--font-brand), 'IM Fell English', serif;">Heading 3 - Regular</h3>
            </div>
            <div class="col-md-6">
                <h3>Small Caps Headers (Art Bible)</h3>
                <h1 style="font-family: var(--font-brand-sc), 'IM Fell English SC', serif;">Heading 1 - Small Caps</h1>
                <h2 style="font-family: var(--font-brand-sc), 'IM Fell English SC', serif;">Heading 2 - Small Caps</h2>
                <h3 style="font-family: var(--font-brand-sc), 'IM Fell English SC', serif;">Heading 3 - Small Caps</h3>
            </div>
        </div>
        <div class="mt-4">
            <p style="color: var(--text-mid);">
                Body text using <strong>muted gold (#d4b06d)</strong> as text-mid color. 
                This provides better contrast and matches the Art Bible specification.
            </p>
        </div>
    </div>

    <!-- Card Components Section -->
    <div class="demo-section">
        <h2>Card Components</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Red Border Card (Default)</h4>
                    </div>
                    <div class="card-body">
                        <p class="card-text" style="color: var(--text-mid);">
                            Default card with red border styling.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card active">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Gold Border Card (Active)</h4>
                    </div>
                    <div class="card-body">
                        <p class="card-text" style="color: var(--text-mid);">
                            Active card with gold border (Art Bible spec). Use <code>.active</code> or <code>.card-active</code> class.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-gold">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Gold Border (Test)</h4>
                    </div>
                    <div class="card-body">
                        <p class="card-text" style="color: var(--text-mid);">
                            Test gold border card for comparison.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <p style="color: var(--text-mid);">
                <strong>Usage:</strong> Add <code>.active</code> or <code>.card-active</code> class to cards to apply gold border styling.
            </p>
        </div>
    </div>

    <!-- Border Radius Comparison Section -->
    <div class="demo-section">
        <h2>Card Border Radius Comparison</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card" style="border-radius: 8px;">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Current Border Radius</h4>
                    </div>
                    <div class="card-body">
                        <p class="card-text" style="color: var(--text-mid);">
                            <strong>8px (0.5rem)</strong> - Current implementation
                        </p>
                        <p class="card-text" style="color: var(--text-mid); font-size: 0.9em;">
                            This is the current border radius used throughout the site.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card" style="border-radius: 0.75rem;">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Art Bible Border Radius</h4>
                    </div>
                    <div class="card-body">
                        <p class="card-text" style="color: var(--text-mid);">
                            <strong>0.75rem (12px)</strong> - Art Bible specification
                        </p>
                        <p class="card-text" style="color: var(--text-mid); font-size: 0.9em;">
                            Art Bible specifies 0.75rem - 1rem (12px - 16px) for card border radius.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card" style="border-radius: 1rem;">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Art Bible (1rem variant)</h4>
                    </div>
                    <div class="card-body">
                        <p class="card-text" style="color: var(--text-mid);">
                            <strong>1rem (16px)</strong> - Art Bible upper range
                        </p>
                        <p class="card-text" style="color: var(--text-mid); font-size: 0.9em;">
                            Alternative within the Art Bible specification range.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-2">
            <div class="col-md-6">
                <div class="card active" style="border-radius: 0.75rem;">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Gold Border + 0.75rem</h4>
                    </div>
                    <div class="card-body">
                        <p class="card-text" style="color: var(--text-mid);">
                            <strong>0.75rem with gold border</strong> - Combined
                        </p>
                        <p class="card-text" style="color: var(--text-mid); font-size: 0.9em;">
                            Active card with Art Bible border radius and gold border.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card active" style="border-radius: 1rem;">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Gold Border + 1rem</h4>
                    </div>
                    <div class="card-body">
                        <p class="card-text" style="color: var(--text-mid);">
                            <strong>1rem with gold border</strong> - Combined
                        </p>
                        <p class="card-text" style="color: var(--text-mid); font-size: 0.9em;">
                            Active card with 1rem border radius and gold border.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Button Components Section -->
    <div class="demo-section">
        <h2>Button Components</h2>
        <div class="d-flex flex-wrap gap-3 mb-4">
            <button class="btn btn-primary">Primary (Red)</button>
            <button class="btn btn-secondary">Secondary (Gold - Art Bible)</button>
            <a href="#" class="btn-red-gold">Red BG + Gold Border</a>
            <button class="btn btn-outline-primary">Outline Primary</button>
        </div>
        <p style="color: var(--text-mid);">
            Secondary buttons now use transparent background with gold border (Art Bible spec).
            The "Red BG + Gold Border" button combines blood red background with muted gold border.
        </p>
    </div>

    <!-- Danger Button Comparison Section -->
    <div class="demo-section">
        <h2>Danger Button Comparison</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <h3>Current Implementation</h3>
                <div class="d-flex flex-wrap gap-3 mb-3">
                    <button class="btn btn-danger">Danger Button</button>
                </div>
                <div style="background: rgba(26, 15, 15, 0.5); padding: 1rem; border-radius: 5px; border: 1px solid rgba(139, 0, 0, 0.3);">
                    <p style="color: var(--text-mid); margin: 0; font-size: 0.9em;">
                        <strong>Current Style:</strong> Uses Bootstrap's danger button with custom gradient styling.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <h3>Art Bible Specification</h3>
                <div class="d-flex flex-wrap gap-3 mb-3">
                    <button class="btn btn-danger-bible">Danger Button (Bible)</button>
                </div>
                <div style="background: rgba(26, 15, 15, 0.5); padding: 1rem; border-radius: 5px; border: 1px solid rgba(139, 0, 0, 0.3);">
                    <p style="color: var(--text-mid); margin: 0; font-size: 0.9em;">
                        <strong>Art Bible Spec:</strong> Dark red, shadowed. Hover: brighter red.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Components Section -->
    <div class="demo-section">
        <h2>Table Components</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <h3>Red Header (Current)</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Clan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Character One</td>
                                <td>Ventrue</td>
                                <td>Active</td>
                            </tr>
                            <tr>
                                <td>Character Two</td>
                                <td>Toreador</td>
                                <td>Active</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <h3>Gold Header (Art Bible)</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead class="table-gold-header">
                            <tr>
                                <th>Name</th>
                                <th>Clan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Character One</td>
                                <td>Ventrue</td>
                                <td>Active</td>
                            </tr>
                            <tr>
                                <td>Character Two</td>
                                <td>Toreador</td>
                                <td>Active</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <h3>Blood-Red Gradient Background</h3>
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-red-gradient">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Clan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Character One</td>
                                <td>Ventrue</td>
                                <td>Active</td>
                            </tr>
                            <tr>
                                <td>Character Two</td>
                                <td>Toreador</td>
                                <td>Active</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Components Section -->
    <div class="demo-section">
        <h2>Form Components</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <h3>Red Borders (Current)</h3>
                <div class="mb-3">
                    <label for="test-input-red" class="form-label">Test Input</label>
                    <input type="text" id="test-input-red" class="form-control" placeholder="Red border input">
                </div>
                <div class="mb-3">
                    <label for="test-select-red" class="form-label">Test Select</label>
                    <select id="test-select-red" class="form-select">
                        <option>Red border select</option>
                        <option>Option 2</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <h3>Gold Borders (Art Bible)</h3>
                <div class="form-gold-border">
                    <div class="mb-3">
                        <label for="test-input-gold" class="form-label">Test Input</label>
                        <input type="text" id="test-input-gold" class="form-control" placeholder="Gold border input">
                    </div>
                    <div class="mb-3">
                        <label for="test-select-gold" class="form-label">Test Select</label>
                        <select id="test-select-gold" class="form-select">
                            <option>Gold border select</option>
                            <option>Option 2</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teal Moonlight Info Panels Section -->
    <div class="demo-section">
        <h2>Teal Moonlight Info Panels</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="info-panel-teal">
                    <h4>Info Panel with Border & Gradient</h4>
                    <p style="color: var(--text-mid);">
                        Use <code>.info-panel-teal</code> class for info panels with both teal border and gradient background.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-panel-teal-border">
                    <div class="p-3">
                        <h4>Info Panel with Border Only</h4>
                        <p style="color: var(--text-mid);">
                            Use <code>.info-panel-teal-border</code> class for teal border only.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-panel-teal-gradient">
                    <h4>Info Panel with Gradient Only</h4>
                    <p style="color: var(--text-mid);">
                        Use <code>.info-panel-teal-gradient</code> class for gradient background only.
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="teal-accent-border">
                    <h4>Accent Border (Legacy)</h4>
                    <p style="color: var(--text-mid);">
                        Teal moonlight used as a left border accent for special information sections.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Code/Monospace Section -->
    <div class="demo-section">
        <h2>Code & Monospace Typography</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <h3>Inline Code</h3>
                <p style="color: var(--text-mid);">
                    Use <code>inline code</code> with the <code>&lt;code&gt;</code> tag. Font: Source Code Pro.
                </p>
                <pre style="background: rgba(26, 15, 15, 0.6); padding: 1rem; border-radius: 5px; border: 1px solid rgba(139, 0, 0, 0.3);"><code>// Code block example
function example() {
    return "Source Code Pro font";
}</code></pre>
            </div>
            <div class="col-md-6">
                <h3>Code Block</h3>
                <p style="color: var(--text-mid);">
                    Use <code>&lt;pre&gt;&lt;code&gt;</code> for code blocks. Font: Source Code Pro.
                </p>
                <pre style="background: rgba(26, 15, 15, 0.6); padding: 1rem; border-radius: 5px; border: 1px solid rgba(139, 0, 0, 0.3); color: var(--text-mid);"><code>const example = {
    font: 'Source Code Pro',
    type: 'monospace'
};</code></pre>
            </div>
        </div>
    </div>

    <!-- Navbar/Navigation Examples Section -->
    <div class="demo-section">
        <h2>Navbar/Navigation Examples</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <h3>Art Bible Specification</h3>
                <nav class="navbar-bible" style="background: #1a0f0f; padding: 15px 30px; border-radius: 8px; margin-bottom: 1rem;">
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#" class="nav-link-bible">Home</a>
                        <a href="#" class="nav-link-bible active">Characters</a>
                        <a href="#" class="nav-link-bible">Locations</a>
                        <a href="#" class="nav-link-bible">Items</a>
                    </div>
                </nav>
                <div style="background: rgba(26, 15, 15, 0.5); padding: 1rem; border-radius: 5px; border: 1px solid rgba(139, 0, 0, 0.3);">
                    <p style="color: var(--text-mid); margin: 0; font-size: 0.9em;">
                        <strong>Art Bible Spec:</strong><br>
                        • Background: Solid #1a0f0f<br>
                        • Hover: Gold text (#d4b06d) + gold underline line<br>
                        • Active: Parchment text with red underline (2px)
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <h3>Current Implementation</h3>
                <nav class="navbar-current" style="background: linear-gradient(180deg, #1a0f0f 0%, #0d0606 100%); padding: 15px 30px; border-radius: 8px; border-bottom: 2px solid #8B0000; margin-bottom: 1rem; box-shadow: 0 4px 15px rgba(139, 0, 0, 0.3);">
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#" class="nav-link-current">Home</a>
                        <a href="#" class="nav-link-current active">Characters</a>
                        <a href="#" class="nav-link-current">Locations</a>
                        <a href="#" class="nav-link-current">Items</a>
                    </div>
                </nav>
                <div style="background: rgba(26, 15, 15, 0.5); padding: 1rem; border-radius: 5px; border: 1px solid rgba(139, 0, 0, 0.3);">
                    <p style="color: var(--text-mid); margin: 0; font-size: 0.9em;">
                        <strong>Current Implementation:</strong><br>
                        • Background: Gradient (#1a0f0f → #0d0606) with red bottom border<br>
                        • Hover: Red text shadow glow (not gold lines)<br>
                        • Active: Bootstrap button active state (background fill, not red underline)
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Examples Section -->
    <div class="demo-section">
        <h2>Sidebar Examples</h2>
        <div class="row g-4">
            <div class="col-md-6">
                <h3>Art Bible Specification</h3>
                <aside class="sidebar-bible" style="background: rgba(26, 15, 15, 0.8); padding: 1.5rem; border-radius: 8px; border: 2px solid rgba(139, 0, 0, 0.3); min-height: 300px;">
                    <h4 class="sidebar-heading-bible" style="color: var(--muted-gold); font-family: var(--font-brand), 'IM Fell English', serif; margin-bottom: 1rem; border-bottom: 1px solid rgba(212, 176, 109, 0.3); padding-bottom: 0.5rem;">
                        Navigation
                    </h4>
                    <nav class="sidebar-nav-bible">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 0.75rem;">
                                <a href="#" class="sidebar-link-bible" style="color: #f5e6d3; text-decoration: none; display: block; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.3s ease;">
                                    Dashboard
                                </a>
                            </li>
                            <li style="margin-bottom: 0.75rem;">
                                <a href="#" class="sidebar-link-bible" style="color: #f5e6d3; text-decoration: none; display: block; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.3s ease;">
                                    Characters
                                </a>
                            </li>
                            <li style="margin-bottom: 0.75rem;">
                                <a href="#" class="sidebar-link-bible" style="color: #f5e6d3; text-decoration: none; display: block; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.3s ease;">
                                    Locations
                                </a>
                            </li>
                            <li style="margin-bottom: 0.75rem;">
                                <a href="#" class="sidebar-link-bible" style="color: #f5e6d3; text-decoration: none; display: block; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.3s ease;">
                                    Items
                                </a>
                            </li>
                        </ul>
                    </nav>
                </aside>
                <div style="background: rgba(26, 15, 15, 0.5); padding: 1rem; border-radius: 5px; border: 1px solid rgba(139, 0, 0, 0.3); margin-top: 1rem;">
                    <p style="color: var(--text-mid); margin: 0; font-size: 0.9em;">
                        <strong>Art Bible Spec:</strong><br>
                        • Dark panel background<br>
                        • Gold headings (#d4b06d)<br>
                        • Parchment text (#f5e6d3)<br>
                        • Soft glow on hover
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <h3>Current Implementation</h3>
                <aside class="sidebar-current" style="background: rgba(26, 15, 15, 0.6); padding: 1.5rem; border-radius: 8px; border: 2px solid rgba(139, 0, 0, 0.4); min-height: 300px;">
                    <h4 class="sidebar-heading-current" style="color: var(--text-light); font-family: var(--font-brand), 'IM Fell English', serif; margin-bottom: 1rem; border-bottom: 1px solid rgba(139, 0, 0, 0.3); padding-bottom: 0.5rem;">
                        Navigation
                    </h4>
                    <nav class="sidebar-nav-current">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 0.75rem;">
                                <a href="#" class="sidebar-link-current" style="color: #f5e6d3; text-decoration: none; display: block; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.3s ease;">
                                    Dashboard
                                </a>
                            </li>
                            <li style="margin-bottom: 0.75rem;">
                                <a href="#" class="sidebar-link-current" style="color: #f5e6d3; text-decoration: none; display: block; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.3s ease;">
                                    Characters
                                </a>
                            </li>
                            <li style="margin-bottom: 0.75rem;">
                                <a href="#" class="sidebar-link-current" style="color: #f5e6d3; text-decoration: none; display: block; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.3s ease;">
                                    Locations
                                </a>
                            </li>
                            <li style="margin-bottom: 0.75rem;">
                                <a href="#" class="sidebar-link-current" style="color: #f5e6d3; text-decoration: none; display: block; padding: 0.5rem 0.75rem; border-radius: 4px; transition: all 0.3s ease;">
                                    Items
                                </a>
                            </li>
                        </ul>
                    </nav>
                </aside>
                <div style="background: rgba(26, 15, 15, 0.5); padding: 1rem; border-radius: 5px; border: 1px solid rgba(139, 0, 0, 0.3); margin-top: 1rem;">
                    <p style="color: var(--text-mid); margin: 0; font-size: 0.9em;">
                        <strong>Current Implementation:</strong><br>
                        • Dark panel background (similar)<br>
                        • Parchment headings (not gold)<br>
                        • Parchment text (#f5e6d3)<br>
                        • Red hover effect (not soft glow)<br>
                        <em>Note: Sidebars are not currently implemented in the codebase</em>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Example Section -->
    <div class="demo-section">
        <h2>Modal Examples</h2>
        <div class="d-flex gap-3">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#redModal">
                Red Border Modal
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#goldModal">
                Gold Border Modal (Test)
            </button>
        </div>
    </div>

    <!-- Red Border Modal -->
    <div class="modal fade" id="redModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content vbn-modal-content">
                <div class="modal-header vbn-modal-header">
                    <h5 class="modal-title vbn-modal-title">Red Border Modal (Current)</h5>
                    <button type="button" class="btn-close btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body vbn-modal-body">
                    <p style="color: var(--text-mid);">This modal uses the current red border styling.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Gold Border Modal -->
    <div class="modal fade" id="goldModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content vbn-modal-content" style="border: 3px solid var(--muted-gold);">
                <div class="modal-header vbn-modal-header">
                    <h5 class="modal-title vbn-modal-title">Gold Border Modal (Art Bible)</h5>
                    <button type="button" class="btn-close btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body vbn-modal-body">
                    <p style="color: var(--text-mid);">This modal uses gold border as specified in Art Bible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>


