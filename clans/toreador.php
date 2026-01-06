<?php
/**
 * Valley by Night - Toreador Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Toreador</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanToreador.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Toreador Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(220, 20, 60, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Toreador</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Toreador</h1>
                    <p class="lead">Artists and socialites, lovers of beauty who are prone to distraction by it. The Toreador are the clan of aesthetics, culture, and social grace, often serving as the face of Kindred society.</p>
                    
                    <p>The Toreador trace their roots to artists, poets, performers, and patrons throughout human history. Their origin myths vary—some claim descent from a muse-touched progenitor, others from a cruelly beautiful predator of the first cities—but all versions bind the clan to the evolution of culture. They attach themselves to courts, salons, theaters, studios, and protest movements, shaping tastes from behind the scenes.</p>
                    
                    <p>Toreador culture is built around aesthetics, social dominance, and gatekeeping. They organize themselves into salons, circles, and coteries that revolve around galleries, clubs, or particular creative scenes. Status is measured not just in boons and titles, but in who attends your parties, who wears your designs, who quotes your work, and whose reputation you can elevate or destroy.</p>
                    
                    <p>At their best, Toreador believe that beauty and meaning are worth preserving in a brutal, unchanging world. They frame themselves as guardians of culture, using their immortality to curate what is truly timeless. At their worst, this curatorial instinct calcifies into elitism and cruelty: if something is ugly or unsophisticated by their standards, it is treated as disposable.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Toreador (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Toreador in Phoenix</h2>
            
            <p>In Phoenix, the Toreador have found a city that reflects their dual nature: the polished surface of high society and the raw energy of underground art scenes. The desert city's isolation has created a unique Toreador community that bridges both worlds, making them essential to maintaining the Masquerade through culture and media.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has left the Toreador in a delicate position. As the clan most embedded in Elysium and court life, they were deeply connected to the previous regime. Now they must navigate the power vacuum while maintaining their role as social arbiters and cultural gatekeepers. Their ability to shape opinion and create consensus makes them valuable to all factions.</p>
            
            <h3>Social and Political Standing</h3>
            <p>In Camarilla domains, Toreador are often deeply embedded in Elysium and court life. They host gatherings, curate neutral ground, and subtly steer trends in Kindred fashion and opinion. Harpies are frequently Toreador, as the clan's knack for gossip and reputation management makes them ideal social arbiters. In Phoenix, this role is more important than ever as the city's Kindred society fragments.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The clan is divided between traditionalists who insist that true art lives in opera houses and symphonies, and modernists who embrace graffiti, performance art, and digital media. Some Toreador focus on mortal high society and fashion, others on underground subcultures and fringe scenes. This divide creates both tension and opportunity as Phoenix's art scene evolves in the wake of political upheaval.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/toreador_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Toreador NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(220, 20, 60, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Toreador of Phoenix are led by those who understand that beauty is power, and power is beauty. Whether they're hosting exclusive gallery openings that double as political gatherings, or curating underground scenes that shape mortal culture, the Toreador make Phoenix's nightlife their domain.</p>
                    
                    <p>Their featured members are masters of the social game, using their charm, their art, and their connections to maintain influence in a city where power structures are shifting. They are the ones who make Elysium feel like home, who turn political gatherings into cultural events, and who ensure that even in the darkest nights, there is still beauty to be found.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Embrace Beauty</h2>
            <p>Are you drawn to the eternal pursuit of beauty and meaning? The Toreador offer a path of aesthetics, culture, and social grace. Whether you're an artist, a patron, or simply someone who believes that beauty matters, the clan of roses welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Toreador characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Toreador Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
