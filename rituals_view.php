<?php
/**
 * rituals_view.php
 * 
 * Displays 10 random rituals from the rituals_master table
 */

require_once __DIR__ . '/includes/connect.php';

// Query 10 random rituals
$query = "SELECT name, type, level, description, system_text, requirements, ingredients, source 
          FROM rituals_master 
          ORDER BY RAND() 
          LIMIT 10";

$result = $conn->query($query);
$rituals = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rituals[] = $row;
    }
}

// Set up page-specific CSS
$extra_css = ['css/rituals_view.css'];

// Include header
include 'includes/header.php';
?>

<main class="main-wrapper">
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-light mb-3">Random Rituals</h1>
                <p class="text-light-50 mb-4">Displaying 10 random rituals from the database</p>
            </div>
        </div>

        <?php if (empty($rituals)): ?>
            <div class="alert alert-warning">
                <strong>No rituals found.</strong> The rituals_master table appears to be empty.
            </div>
        <?php else: ?>
            <div class="rituals-container">
                <?php foreach ($rituals as $index => $ritual): ?>
                    <div class="ritual-card card mb-4">
                        <div class="card-header bg-dark border-danger">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0 text-light">
                                    <?php echo htmlspecialchars($ritual['name']); ?>
                                </h3>
                                <div class="ritual-badges">
                                    <span class="badge bg-danger me-1"><?php echo htmlspecialchars($ritual['type']); ?></span>
                                    <span class="badge bg-secondary">Level <?php echo (int)$ritual['level']; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body bg-dark text-light">
                            <?php if (!empty($ritual['description'])): ?>
                                <div class="ritual-field mb-3">
                                    <h5 class="field-label text-danger">Description</h5>
                                    <p class="field-value"><?php echo nl2br(htmlspecialchars($ritual['description'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($ritual['system_text'])): ?>
                                <div class="ritual-field mb-3">
                                    <h5 class="field-label text-danger">System Text</h5>
                                    <div class="field-value"><?php echo nl2br(htmlspecialchars($ritual['system_text'])); ?></div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($ritual['requirements'])): ?>
                                <div class="ritual-field mb-3">
                                    <h5 class="field-label text-danger">Requirements</h5>
                                    <p class="field-value"><?php echo nl2br(htmlspecialchars($ritual['requirements'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($ritual['ingredients'])): ?>
                                <div class="ritual-field mb-3">
                                    <h5 class="field-label text-danger">Ingredients</h5>
                                    <p class="field-value"><?php echo nl2br(htmlspecialchars($ritual['ingredients'])); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($ritual['source'])): ?>
                                <div class="ritual-field">
                                    <h5 class="field-label text-danger">Source</h5>
                                    <p class="field-value opacity-75 small"><?php echo htmlspecialchars($ritual['source']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row mt-4">
                <div class="col-12 text-center">
                    <a href="rituals_view.php" class="btn btn-danger">Refresh - Get 10 New Random Rituals</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

