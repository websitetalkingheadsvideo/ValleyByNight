<?php
/**
 * Valley by Night - Malkavian Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Malkavian</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanMalkavian.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Malkavian Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Malkavian</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Malkavian</h1>
                    <p class="lead">Seers and madmen with unique insights, all Malkavians suffer from some form of derangement. Their madness grants them prophetic visions and unpredictable behavior that makes them both feared and sought after.</p>
                    
                    <p>The Malkavians trace their origin to Malkav, one of the Third Generation, who was cursed with the Sight—a vision that pierced reality's illusions but shattered his mind. When Malkav was torn apart near Petra, his consciousness fragmented and spread through his childer, creating the Cobweb that links all Malkavians. The clan has always been fractured, with members scattered across cities, drawn to madness and prophecy.</p>
                    
                    <p>Malkavian culture is defined by the Cobweb—the psychic network connecting all members of the clan. They are creatures of cities, drawn to urban environments that pulse with life and madness. The clan has no unified hierarchy; each Malkavian follows their own vision, derangement, or obsession. They gather in loose networks, sharing insights through the Cobweb, but rarely organize formally.</p>
                    
                    <p>Malkavians believe that reality is a lie, a fragile construct that most Kindred and mortals accept without question. Their curse—the Sight—allows them to perceive the world's true angles, to see through illusions and recognize patterns others miss. They understand that madness is not a weakness but a different way of perceiving.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Malkavian (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Malkavian in Phoenix</h2>
            
            <p>In Phoenix, the Malkavians are drawn to the city's isolation and the chaos that follows the Prince's murder. The desert city's raw energy and the fragmentation of Kindred society create an environment where their visions and insights are more valuable—and more dangerous—than ever.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created a crisis that the Malkavians saw coming, though their warnings were dismissed as madness. Now, as the city fragments, their prophetic insights are suddenly in demand. Princes and primogen who once kept them at arm's length now seek their counsel, even as they fear the infectious nature of Malkavian madness.</p>
            
            <h3>Social and Political Standing</h3>
            <p>Malkavians are officially part of the Camarilla but are barely tolerated. Princes keep them close for their prophetic insights but fear their infectious madness and unpredictable behavior. They serve as advisors, seers, and sometimes scapegoats when things go wrong. In Phoenix, this position is more important than ever as the city's Kindred society fragments and everyone seeks answers.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The Cobweb—the psychic network connecting all Malkavians—allows them to share information across vast distances, making them excellent sources of plot hooks and warnings. Their derangements should be played seriously—not as comic relief but as different ways of perceiving truth. Malkavians can reveal that things are not as they seem, that patterns connect seemingly unrelated events, and that the Antediluvians are real and waking.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/malkavian_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Malkavian NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Malkavians of Phoenix are led by those who understand that madness is not a weakness—it's a different way of seeing. Whether they're serving as cryptic advisors, dangerous prophets, or tragic figures trapped by their visions, the Malkavians make insight their domain.</p>
                    
                    <p>Their featured members are masters of the Cobweb, using their connection to the psychic network to gather and share information. They are the ones who see patterns others miss, who speak uncomfortable truths, and who ensure that even in the darkest nights, there is still someone who sees through the illusions.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>See Through the Illusions</h2>
            <p>Are you willing to trade sanity for insight? The Malkavians offer a path of prophecy, truth, and madness. Whether you're a natural seer or someone who believes that reality is a lie, the clan of the mad welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Malkavian characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Malkavian Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
