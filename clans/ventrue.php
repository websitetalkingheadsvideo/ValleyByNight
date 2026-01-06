<?php
/**
 * Valley by Night - Ventrue Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Ventrue</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanVentrue.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Ventrue Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(212, 176, 109, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Ventrue</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Ventrue</h1>
                    <p class="lead">Aristocrats and leaders of Kindred society, the Ventrue are the clan of princes and primogen. They are natural leaders who excel at politics, business, and maintaining the Masquerade.</p>
                    
                    <p>The Ventrue claim to be Caine's chosen, the first of the Third Generation and his closest advisor. They served as stewards of the First and Second Cities, leading the Kindred through the ages. The clan's history is one of conquest, achievement, and brilliant tactics. They spread with the Roman Empire, establishing domains throughout Europe and beyond.</p>
                    
                    <p>Ventrue culture is built around authority, tradition, and dignitas (personal honor and standing). They are organized into a strict hierarchy: peers (neonates), aediles (who assist with clan business), praetors (who oversee cities), strategoi (who oversee regions), and ephors (the secretive Directorate). The clan maintains elaborate protocols and etiquette, emphasizing respect, decorum, and proper behavior.</p>
                    
                    <p>Ventrue philosophy centers on leadership, responsibility, and the right to rule. They believe they are destined to lead, and they take that responsibility seriously. They rely on history, tradition, and precedent to guide their actions, believing that what worked before will work again. The clan's philosophy emphasizes noblesse oblige—with power comes responsibility.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Ventrue (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Ventrue in Phoenix</h2>
            
            <p>In Phoenix, the Ventrue face their greatest challenge: maintaining order in a city where the Prince is dead and the traditional power structure has collapsed. As the self-proclaimed leaders of the Camarilla, they are expected to restore stability, but the city's isolation and the Anarch presence make this a difficult task.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has left the Ventrue scrambling to maintain their position. Without a clear leader, the clan's hierarchy is being tested. Some Ventrue see this as an opportunity to prove their worth and claim the princedom. Others see it as a crisis that threatens everything they've built. The clan is divided between those who want to restore the old order and those who recognize that Phoenix may require a new approach.</p>
            
            <h3>Social and Political Standing</h3>
            <p>The Ventrue are the self-proclaimed leaders of the Camarilla, and they use their position to maintain their authority. They hold more leadership positions within the sect than any other clan, including many princes, primogen, seneschals, and sheriffs. In Phoenix, this position is more tenuous than usual, as the power vacuum has created opportunities for other clans to challenge Ventrue dominance.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The clan is divided between those who embrace the modern world and those who cling to tradition, but all agree that unity is essential for survival. The Ventrue must balance their desire for control with the reality that Phoenix is a city in flux. Their traditional allies—the Toreador and Malkavians—are still present, but their rivals—the Tremere and Brujah—are also making moves. The opportunity exists for a strong Ventrue leader to unite the city, but the risk of failure could destroy the clan's reputation.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/ventrue_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Ventrue NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(212, 176, 109, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Ventrue of Phoenix are led by those who understand that leadership is not just about power—it's about responsibility. Whether they're serving as primogen, seneschals, or simply influential members of the court, the Ventrue make maintaining order their primary concern.</p>
                    
                    <p>Their featured members are masters of politics and business, using their wealth, connections, and Dominate to shape the city's future. They are the ones who maintain the Masquerade through careful control of mortal institutions, who build networks of obligation and influence, and who ensure that even in chaos, there is still structure.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Claim Your Birthright</h2>
            <p>Are you born to lead? The Ventrue offer a path of authority, tradition, and responsibility. Whether you're a natural ruler or someone who believes in the power of structure and order, the clan of blue bloods welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Ventrue characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Ventrue Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
