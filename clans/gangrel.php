<?php
/**
 * Valley by Night - Gangrel Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Gangrel</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanGangrel.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Gangrel Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Gangrel</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Gangrel</h1>
                    <p class="lead">Nomadic shapeshifters and survivors, the Gangrel are closely attuned to the Beast and the natural world. They are self-reliant loners who often live on the fringes of Kindred society.</p>
                    
                    <p>The Gangrel trace their origins to Ennoia, one of the Antediluvians, though their early history is shrouded in myth and legend. They have always been wanderers, existing on the fringes of both mortal and Kindred society. Throughout history, they've adapted to every environment—from the steppes of Central Asia to the forests of Europe, from the cities of the New World to the wilderness of every continent.</p>
                    
                    <p>Gangrel culture is built on survival, strength, and independence. They have no formal hierarchy—respect is earned through demonstrated capability, not age or position. The Embrace is traditionally harsh: childer are abandoned immediately after the transformation, left to survive or die on their own. Those who survive are considered worthy; those who don't are forgotten.</p>
                    
                    <p>Gangrel philosophy centers on survival, freedom, and acceptance of the Beast. They believe in doing what's necessary, not what's polite. The world is dangerous, and only the strong and adaptable survive. They don't fight against their nature—they embrace it, even as it transforms them. Every frenzy leaves its mark, and Gangrel accept this as the price of their power.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Gangrel (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Gangrel in Phoenix</h2>
            
            <p>In Phoenix, the Gangrel have found a city that reflects their nature: the desert wilderness that surrounds the city provides territory for those who prefer isolation, while the city itself offers opportunities for those who can adapt. The desert's harsh environment and the city's fragmentation after the Prince's murder create an environment where Gangrel survival skills are more valuable than ever.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created both opportunity and danger for the Gangrel. As independents who left the Camarilla, they are free from sect politics, but they also lack the protection that sects provide. The city's fragmentation means that territory is up for grabs, but it also means that conflict is more likely. Gangrel must rely on their survival skills and their ability to adapt.</p>
            
            <h3>Social and Political Standing</h3>
            <p>Gangrel politics are minimal and direct. They don't play the games of other clans—respect is earned through strength and action, not manipulation. In Phoenix, remaining Gangrel are viewed with suspicion by those who stayed in the Camarilla, their loyalty questioned. Many have lost territory, status, and hunting grounds as other clans take advantage of their weakened position.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The clan's departure from the Camarilla has strained relations with all sects, leaving them isolated but independent. The desert surrounding Phoenix provides territory for those who prefer isolation, while the city itself offers opportunities for those who can adapt. The Gangrel's animal features—marks of their connection to the Beast—make them more monstrous over time, but they accept this as the price of their power.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/gangrel_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Gangrel NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Gangrel of Phoenix are led by those who understand that survival is everything, and everything is survival. Whether they're serving as territorial guardians, lone wanderers, or pack members, the Gangrel make strength their domain.</p>
                    
                    <p>Their featured members are masters of the wild, using their Animalism, Fortitude, and Protean to survive in the desert and the city. They are the ones who claim territory and defend it, who adapt to any environment, and who ensure that even in the darkest nights, there is still someone who can survive anything.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Embrace the Wild</h2>
            <p>Are you willing to trade civilization for freedom? The Gangrel offer a path of survival, strength, and independence. Whether you're a natural wanderer or someone who believes that the strong survive, the clan of shapeshifters welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Gangrel characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Gangrel Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
