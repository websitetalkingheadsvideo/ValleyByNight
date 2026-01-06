<?php
/**
 * Valley by Night - Brujah Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Brujah</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanBrujah.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Brujah Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Brujah</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Brujah</h1>
                    <p class="lead">Passionate rebels and idealists, the Brujah are known for their fiery tempers and physical prowess. They are the clan of revolutionaries, activists, and warriors who fight for their ideals with every fiber of their being.</p>
                    
                    <p>In Valley by Night, the Brujah are a clan defined by rebellion, Carthage myth, and the tension between their violent rabble majority and a rare, powerful minority of philosopher-kings. Passion fuels everything they do—sometimes righteous, sometimes destructive. Their ideological fire makes them volatile neighbors, dangerous enemies, and unpredictable allies.</p>
                    
                    <p>The Brujah once stood as philosopher-kings and warrior-philosophers, guardians of an ancient utopian city—Carthage—where Kindred and mortals coexisted under Brujah ideals. Its destruction left a wound that still defines the clan. Over centuries, their intellectual tradition fractured, giving way to violent rebellion, street radicalism, and ideological schisms.</p>
                    
                    <p>Brujah culture is a spectrum: most of the clan thrives on aggression, anti-authoritarianism, and communal rage, while a small minority tries to uphold ancient traditions of rhetoric, philosophy, and radical political theory. Rants serve as cultural rituals—part debate hall, part brawl. Loyalty is situational but fierce; betrayal is remembered for centuries.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Brujah (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Brujah in Phoenix</h2>
            
            <p>Phoenix Brujah don't just burn with ideology—they <em>bake</em> in it. The desert city amplifies everything that makes them dangerous: the heat makes tempers shorter, the sprawl makes isolation feel like exile, and the endless asphalt reflects their rage back at them like a mirror. In Phoenix, a Brujah's passion doesn't simmer—it boils over, leaving scorch marks on everything they touch.</p>
            
            <p>The city's geography shapes them. The 24th Street corridor near the Arizona State Hospital isn't just Anarch territory—it's Brujah territory, a concrete proving ground where philosophy meets pavement. Here, the Rabble gather in parking lots and abandoned warehouses, their rants echoing off cinderblock walls while the desert wind carries their words into the night.</p>
            
            <h3>Current Situation</h3>
            <p>It's worth noting that the Prince himself is Brujah—a fact that adds complexity to the clan's position in the city. Even though the Anarchs are mostly Brujah, the Prince is also Brujah, creating an internal tension within the clan between those who support the Camarilla structure and those who rebel against it. Their Anarch sympathies make them natural enemies of the Camarilla structure, but they're also the movement's heart, its voice, and often its fist. The 24th Street cells are heavily Brujah-led, their rants serving as recruitment drives and ideological battlegrounds.</p>
            
            <h3>Social and Political Standing</h3>
            <p>Camarilla elders see Phoenix Brujah as a necessary evil—unstable, loud, and impossible to ignore. They're the clan you call when you need muscle, but never when you need subtlety. Anarchs view them differently: they're not just allies, they're the movement's core. When Brujah speak, crowds listen. When Brujah act, cities change.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The divide between old-world philosopher-rebels and new-world street Anarchs is sharper in Phoenix than in older cities. The rare Carthage Romantics arrive like missionaries, bringing the myth of Carthage to a city that's never heard of it. The Rabble—the 80%—are street fighters who know the system is broken and that someone needs to break it harder. This tension is constant, sometimes violent, and always immediate.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC: Dr. Aurelio Montfort</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/brujah_montfort.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Dr. Aurelio Montfort">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>Dr. Aurelio Montfort is one of the rare Brujah philosopher-elites—a historian of Carthage who speaks softly but can turn entire rooms with rhetoric. As an intellectual revolutionary and Carthage Romantic, he serves as an Anarch strategist, bridging the gap between ancient ideals and modern radical politics.</p>
                    
                    <p>Montfort represents the 20% of Brujah who maintain the clan's intellectual tradition. Unlike the Rabble who dominate Phoenix's streets, Montfort brings the weight of history and philosophy to every conversation. His presence in Phoenix is significant—he's not just preserving tradition, he's building a new one in a city hungry for something to believe in.</p>
                    
                    <p>Those who underestimate Montfort as a mere scholar do so at their peril. His words can inspire revolutions, and his understanding of both ancient Carthage and modern Phoenix politics makes him a dangerous strategist. In a city where power structures are shifting and tensions run high, Montfort's vision of a new utopia could reshape everything.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Join the Revolution</h2>
            <p>Are you ready to fight for what you believe in? The Brujah offer a path of passion, rebellion, and ideological fire. Whether you're a street revolutionary or a philosophical idealist, the clan of rebels welcomes those who refuse to accept the status quo.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Brujah characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Brujah Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
