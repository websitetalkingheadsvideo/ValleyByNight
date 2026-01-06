<?php
/**
 * Valley by Night - Tremere Clan Page
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include header with clans CSS
$extra_css = ['css/clans.css', 'css/character_view.css'];
include '../includes/header.php';
?>

<div class="page-content container py-4">
    <main id="main-content">
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Clans</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Tremere</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanTremere.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Tremere Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Tremere</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Tremere</h1>
                    <p class="lead">Blood sorcerers and scholars, the Tremere cannot create childer without permission. They are organized, hierarchical, and masters of Thaumaturgy, using blood magic to maintain their power.</p>
                    
                    <p>The Tremere began as mortal wizards who sought immortality through magic. In the 8th century, they discovered the secret of the Embrace and transformed themselves into vampires, but at a terrible cost: they became bound to their founder, Tremere, and lost the ability to create childer without permission. Since then, they have built a global organization based on hierarchy, knowledge, and blood magic.</p>
                    
                    <p>Tremere culture is built around the Pyramid—a strict hierarchy that extends from the lowest apprentice to the Council of Seven. Knowledge is power, and power is knowledge. The clan values discipline, obedience, and the pursuit of magical knowledge above all else. They organize into chantries—fortified locations where they study, practice Thaumaturgy, and maintain their power.</p>
                    
                    <p>The Tremere are masters of Thaumaturgy, a form of blood magic that allows them to manipulate reality through ritual and will. They use this power to maintain their position in Kindred society, to protect themselves from their many enemies, and to pursue their ultimate goal: understanding and controlling the nature of vampirism itself.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Tremere (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Tremere in Phoenix</h2>
            
            <p>In Phoenix, the Tremere maintain a chantry that serves as both a research facility and a fortress. The desert city's isolation has made their presence more important than ever, as they are one of the few clans with the magical knowledge and organizational structure to maintain order in the wake of the Prince's murder.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created both opportunity and danger for the Tremere. As masters of Thaumaturgy, they have the power to investigate the murder through magical means, but their strict hierarchy and need for permission to create childer makes them vulnerable. The clan must balance their desire for power with the reality that they are deeply unpopular among other Kindred.</p>
            
            <h3>Social and Political Standing</h3>
            <p>The Tremere are part of the Camarilla, but their position is tenuous. Other clans resent their magical power, their strict hierarchy, and their role in creating the Gargoyles. They are tolerated because their Thaumaturgy is useful, but they are never fully trusted. In Phoenix, this position is more important than ever as the city's Kindred society fragments.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The clan's strict hierarchy creates both strength and weakness. The Pyramid ensures discipline and organization, but it also means that individual Tremere have little freedom. The need for permission to create childer makes the clan vulnerable, as they cannot easily replace losses. However, their magical knowledge and organizational structure give them power that others cannot match.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/tremere_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Tremere NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Tremere of Phoenix are led by those who understand that knowledge is power, and power is knowledge. Whether they're serving as chantry leaders, magical researchers, or political advisors, the Tremere make Thaumaturgy their domain.</p>
                    
                    <p>Their featured members are masters of blood magic, using their knowledge of Thaumaturgy to maintain their position in Kindred society. They are the ones who investigate mysteries through magical means, who protect the clan through ritual and will, and who ensure that even in the darkest nights, there is still someone who understands the true nature of power.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Master the Art</h2>
            <p>Are you drawn to the pursuit of knowledge and power? The Tremere offer a path of magic, hierarchy, and scholarship. Whether you're a natural scholar or someone who believes that knowledge is the ultimate weapon, the clan of blood sorcerers welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Tremere characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Tremere Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
