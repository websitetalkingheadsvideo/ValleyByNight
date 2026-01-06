<?php
/**
 * Valley by Night - Caitiff Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Caitiff</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoBloodlineCaitiff.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Caitiff Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Caitiff</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Caitiff</h1>
                    <p class="lead">Clanless vampires, outcasts from Kindred society. Caitiff lack the traditional clan structure and often face discrimination, but they are free from clan curses and can develop any disciplines.</p>
                    
                    <p>The Caitiff are Kindred who have been Embraced but do not belong to any recognized clan. They are the clanless, the outcasts, the orphans of the night. Some are the result of weak bloodlines that have lost their clan identity over generations. Others are the product of Embraces gone wrong, where the sire's blood was too weak to pass on clan characteristics. Still others are simply anomalies—Kindred who should have been one clan but emerged as something else entirely.</p>
                    
                    <p>Caitiff culture is fragmented and individualistic. Without a clan structure to provide identity and support, each Caitiff must forge their own path. Some band together in loose coteries for mutual protection, while others remain isolated, moving from city to city in search of acceptance that never comes. They have no shared history, no common traditions, and no unified philosophy—only the shared experience of being rejected by Kindred society.</p>
                    
                    <p>What Caitiff lack in clan identity, they gain in freedom. They are not bound by clan curses, clan disciplines, or clan expectations. They can develop any disciplines they choose, creating unique combinations that no clan member could achieve. This freedom comes at a price: they are often viewed with suspicion, fear, or outright hatred by other Kindred, who see them as aberrations or threats to the natural order.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Various sourcebooks on clanless Kindred</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Caitiff in Phoenix</h2>
            
            <p>In Phoenix, the Caitiff have found a city that reflects their nature: the desert's isolation and the city's fragmentation after the Prince's murder create opportunities for those who have no clan to call home. The Caitiff are outcasts, but in a city where power structures are collapsing, being an outcast can be an advantage.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created both opportunity and danger for the Caitiff. As clanless outcasts, they are natural suspects, but they are also free from the political entanglements that bind other clans. The city's fragmentation means that traditional prejudices are being tested, and some Caitiff are finding acceptance—or at least tolerance—that they never had before.</p>
            
            <h3>Social and Political Standing</h3>
            <p>Caitiff are largely rejected by Kindred society. They are viewed with suspicion, fear, or outright hatred by other Kindred, who see them as aberrations or threats to the natural order. In Camarilla domains, they are often denied recognition, resources, and protection. In Phoenix, this position is more fluid than usual, as the city's fragmentation means that traditional hierarchies are being questioned.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The Caitiff's lack of clan identity creates both freedom and isolation. They are not bound by clan curses or clan expectations, but they also lack the support and protection that clans provide. Some Caitiff band together in loose coteries for mutual protection, while others remain isolated. In Phoenix, the city's fragmentation creates opportunities for Caitiff to prove their worth, but it also means that they are more vulnerable than ever.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/caitiff_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Caitiff NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Caitiff of Phoenix are led by those who understand that freedom comes at a price, and that price is isolation. Whether they're serving as lone wanderers, coterie members, or outcasts seeking acceptance, the Caitiff make survival their domain.</p>
                    
                    <p>Their featured members are masters of adaptation, using their freedom from clan restrictions to develop unique combinations of disciplines and abilities. They are the ones who have no clan to call home, who must forge their own path in a world that rejects them, and who ensure that even in the darkest nights, there is still someone who can survive without the support of a clan.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Forge Your Own Path</h2>
            <p>Are you willing to trade clan identity for freedom? The Caitiff offer a path of independence, adaptation, and survival. Whether you're a natural outcast or someone who believes that you don't need a clan to define you, the clanless welcome you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Caitiff characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Caitiff Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
