<?php
/**
 * Valley by Night - Daughter of Cacophony Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Daughter of Cacophony</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoBloodlineDaughtersofCacophony.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Daughter of Cacophony Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Daughter of Cacophony</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Daughter of Cacophony</h1>
                    <p class="lead">Musical sirens and performers, the Daughters of Cacophony use their voices as both weapon and art. They are rare, mysterious, and their songs can entrance or destroy.</p>
                    
                    <p>The origins of the Daughters of Cacophony are shrouded in mystery. Even the Daughters themselves don't know the full truth, and the Mothers (the bloodline's elders) prefer to keep it that way, seeing how their childer react to confusion and chaos. Various theories circulate about their origins—some say they are descended from Toreador, others from Malkavians, still others from some unknown source.</p>
                    
                    <p>Daughters of Cacophony culture is built around music in all its forms. They are passionate, individualistic, and sometimes a little unbalanced. They form subcultures based on musical taste—opera lovers, goths, punks, ravers, jazz aficionados, folkies, metalheads, and more. These groups can be tight-knit and can squabble like any group of fans. The Daughters are loosely organized, with the Mothers serving as guides and mentors rather than rulers.</p>
                    
                    <p>Daughters believe that music is everything. Without it, there is nothing. They prioritize the emotional content of music over technical perfection, wanting their music to move people, to make them feel something, to change them. They believe that music can change things—people, moods, minds, even the world. They see music as honest expression, coming from the heart, and they reject anything that doesn't do something to the listener.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Bloodline Book: Daughters of Cacophony</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Daughter of Cacophony in Phoenix</h2>
            
            <p>In Phoenix, the Daughters of Cacophony have found a city that reflects their nature: the desert's isolation and the city's fragmentation after the Prince's murder create opportunities for those who can use music to influence and control. The Daughters are rare, but their presence is felt in the city's nightlife and music scenes.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created both opportunity and danger for the Daughters of Cacophony. As masters of music and influence, they are in demand for their ability to shape emotions and control crowds. They are largely apolitical, focusing on music rather than Kindred politics, but the city's fragmentation means that their services are more valuable than ever.</p>
            
            <h3>Social and Political Standing</h3>
            <p>Daughters are largely apolitical, focusing on music rather than Kindred politics. They are rarely considered a threat, and most clans see them as a curiosity or a potential tool. They make few enemies, but those they do make are usually dangerous. The Toreador and Brujah have more contact with them than other clans, due to their shared interest in music and art.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The Daughters have mixed relationships with other clans. The Toreador see them as both rivals and allies, sharing an interest in art but approaching it from different angles. The Brujah see them as potential allies in revolution, though many Daughters are less interested in political change than emotional change. The Tremere are interested in their Devotions, suspecting they have some form of Blood Sorcery. In Phoenix, this position is more important than ever as the city's Kindred society fragments and everyone seeks influence.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/daughter_of_cacophony_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Daughter of Cacophony NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Daughters of Cacophony in Phoenix are led by those who understand that music is power, and power is music. Whether they're serving as opera singers, punk guitarists, or DJs, the Daughters make performance their domain.</p>
                    
                    <p>Their featured members are masters of Melpominee, using their unique Discipline to create music that can entrance, inspire, or destroy. They are the ones who perform in the city's clubs and venues, who use their voices to shape emotions and control crowds, and who ensure that even in the darkest nights, there is still someone who can make you feel something through music.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Find Your Voice</h2>
            <p>Are you drawn to the power of music and performance? The Daughters of Cacophony offer a path of song, influence, and artistic expression. Whether you're a singer or a musician, the bloodline of sirens welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Daughter of Cacophony characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Daughter of Cacophony Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
