<?php
/**
 * Valley by Night - Giovanni Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Giovanni</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanGiovanni.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Giovanni Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Giovanni</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Giovanni</h1>
                    <p class="lead">Necromancers and businessmen, death merchants who cannot create blood bonds. The Giovanni are a tightly-knit family clan that controls death itself through their unique discipline.</p>
                    
                    <p>The Giovanni began as a mortal merchant family in ancient Rome, practicing ancestor worship and death magic (nigrimancy). In 1005, Augustus Giovanni was Embraced by the Cappadocian Antediluvian, who sought to learn the Giovanni's secrets of communicating with the dead. Augustus and his family had other plans: they learned from the Cappadocians, grew powerful, and in 1444, Augustus diablerized the Cappadocian founder, elevating himself to Third Generation and destroying the Cappadocian clan.</p>
                    
                    <p>Giovanni culture is built on family, wealth, and necromantic power. They are a true family business, with intense loyalty mixed with suffocating control. Family members are expected to serve the clan's interests above their own. The clan values 'single-blooded' childer—those born from incestuous unions within the family—believing them superior to 'double-blooded' outsiders.</p>
                    
                    <p>Giovanni philosophy centers on wealth, power, and family legacy. They believe that money is the ultimate tool—it buys influence, protection, and silence. They see death as a resource to be managed, and the dead as sources of information and power. The clan's ultimate goal is the Endless Night, when the barrier between the living and the dead will be torn down, giving the Giovanni ultimate power.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Giovanni (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Giovanni in Phoenix</h2>
            
            <p>In Phoenix, the Giovanni maintain a presence that reflects their nature: they operate in the shadows, using their wealth and necromantic knowledge to influence events without direct involvement. The desert city's isolation and the fragmentation after the Prince's murder create opportunities for those who can provide services that other clans cannot.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created both opportunity and danger for the Giovanni. As masters of necromancy, they have the power to investigate the murder through communication with the dead, but their isolation from Kindred politics makes them vulnerable. The clan must balance their desire for power with the reality that they are deeply unpopular among other Kindred.</p>
            
            <h3>Social and Political Standing</h3>
            <p>The Giovanni operate outside both the Camarilla and Sabbat, bound by the Promise of 1528 to remain apolitical. This isolation protects them from sect conflicts but also means they have no allies when trouble comes. They use their wealth and necromantic knowledge to influence events without direct involvement. Other Kindred resent them for their isolation, their wealth, and their role in destroying the Cappadocians.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The clan's family structure creates opportunities for loyalty conflicts, generational disputes, and the tension between personal ambition and family duty. Their necromantic abilities make them valuable sources of information but also dangerous enemies. The Giovanni can provide services that other clans cannot, but always for a price. In Phoenix, this position is more important than ever as the city's Kindred society fragments and everyone seeks answers.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/giovanni_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Giovanni NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Giovanni of Phoenix are led by those who understand that wealth is power, and power is wealth. Whether they're serving as business magnates, necromancers, or family enforcers, the Giovanni make death their domain.</p>
                    
                    <p>Their featured members are masters of necromancy and business, using their knowledge of death and their financial resources to maintain their position in Kindred society. They are the ones who communicate with the dead, who manage the family's wealth, and who ensure that even in the darkest nights, there is still someone who understands the true value of death.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Join the Family</h2>
            <p>Are you drawn to the power of death and wealth? The Giovanni offer a path of necromancy, business, and family loyalty. Whether you're a natural businessman or someone who believes that death is a resource, the clan of death merchants welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Giovanni characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Giovanni Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
