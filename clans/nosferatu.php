<?php
/**
 * Valley by Night - Nosferatu Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Nosferatu</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanNosferatu.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Nosferatu Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Nosferatu</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Nosferatu</h1>
                    <p class="lead">Information brokers and masters of stealth, the Nosferatu are hideously deformed and cannot pass as human. They excel at gathering secrets and operating from the shadows.</p>
                    
                    <p>The Nosferatu trace their origins to a legendary hunter who was Embraced by Zillah, the youngest of Caine's three childer. This hunter, renamed Nosferatu, eventually rebelled against his sire and was cursed by Caine himself, along with all his descendants. The curse made them hideously deformed, forcing them to hide from the world of mortals.</p>
                    
                    <p>Nosferatu culture is built around survival in the face of their curse. They form tight-knit broods that gather in underground warrens, sewers, tunnels, and forgotten spaces. Information is their primary currency—they trade secrets, blackmail, and intelligence to maintain their position in Kindred society. They maintain extensive networks (both technological, like SchreckNET, and traditional) to gather and share information.</p>
                    
                    <p>Nosferatu philosophy is fundamentally pragmatic: survive, adapt, and know more than your enemies. They accept their curse as an unchangeable fact and focus on what they can control: information, territory, and mutual support. Many view themselves as the true watchers of Kindred society, seeing what others miss from their hidden vantage points.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Nosferatu (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Nosferatu in Phoenix</h2>
            
            <p>In Phoenix, the Nosferatu have built an extensive underground network that spans the city's infrastructure. The desert city's isolation has made their information-gathering abilities more valuable than ever, as they are often the only ones who know what's really happening beneath the surface.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created a crisis for the Nosferatu. As information brokers, they were deeply connected to the previous regime, providing intelligence and maintaining the city's infrastructure from below. Now they must navigate the power vacuum while maintaining their position as the city's eyes and ears. Their knowledge makes them valuable to all factions, but their appearance makes them dependent on others for certain tasks.</p>
            
            <h3>Social and Political Standing</h3>
            <p>In Camarilla domains, Nosferatu often serve as information brokers and unofficial spies for princes and primogen. They maintain the city's infrastructure from below and trade secrets for protection and resources. Their hideous appearance makes them dependent on others for certain tasks, but their knowledge makes them valuable. In Phoenix, this position is more important than ever as the city's Kindred society fragments.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The constant threat of the Nictuku creates a fatalistic undercurrent—they know they may be hunted at any time, so they live each night as if it might be their last. This paranoia, combined with their isolation, makes the Nosferatu both the most vulnerable clan and one of the most dangerous. Their knowledge of Phoenix's underground infrastructure and their extensive information networks give them power that others underestimate at their peril.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/nosferatu_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Nosferatu NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Nosferatu of Phoenix are led by those who understand that information is power, and power is survival. Whether they're serving as information brokers, maintaining the city's infrastructure, or organizing local broods, the Nosferatu make knowledge their domain.</p>
                    
                    <p>Their featured members are masters of the underground, using their appearance, their Animalism, and their extensive networks to gather and trade secrets. They are the ones who know what's really happening in Phoenix, who maintain the city's hidden infrastructure, and who ensure that even in the darkest nights, there is still someone watching.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Embrace the Shadows</h2>
            <p>Are you willing to trade beauty for knowledge? The Nosferatu offer a path of information, survival, and hidden power. Whether you're a natural information broker or someone who believes that knowledge is the ultimate currency, the clan of the hidden welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Nosferatu characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Nosferatu Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
