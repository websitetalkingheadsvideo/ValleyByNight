<?php
/**
 * Valley by Night - Followers of Set Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Followers of Set</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanFollowersofSet.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Followers of Set Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Followers of Set</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Followers of Set</h1>
                    <p class="lead">Egyptian cultists and corruptors, the Followers of Set serve their dark god through temptation and corruption. They cannot enter holy ground but excel at manipulation and the Serpentis discipline.</p>
                    
                    <p>The Followers of Set trace their origins to the worship of the god Set in ancient Egypt, recasting him not merely as a desert storm god but as a primordial liberator and adversary of stagnant order. Over centuries they insert themselves into pharaonic cults, rival priesthoods, Hellenistic mystery religions, and later heresies and occult societies.</p>
                    
                    <p>Setite culture is a hybrid of religious order, criminal syndicate, and occult university. The clan organizes itself around temples, cults, and networks of mortal devotees, each built on carefully tailored temptations: drugs, sex, forbidden knowledge, political power, spiritual comfort. Internally, they speak in the language of doctrine, revelations, and paths, but are pragmatic about methods—whatever leads a target closer to Set is acceptable.</p>
                    
                    <p>The central Setite belief is that existence is bondage: to fear, guilt, institutions, false gods, and comforting lies. Set, in their theology, is the principle that shatters these chains, even at terrible personal cost. The Path of Typhon and related doctrines teach that corruption and degradation can be tools of revelation: by stripping away illusions through vice, trauma, and ecstatic experiences, the initiate confronts what they truly are.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Followers of Set (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Followers of Set in Phoenix</h2>
            
            <p>In Phoenix, the Followers of Set have found a city that reflects their nature: the desert's harsh environment and the city's fragmentation after the Prince's murder create opportunities for those who can provide services that other clans cannot—or will not. The Setites operate behind the scenes, running clubs, cults, and criminal enterprises that generate leverage and influence.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created both opportunity and danger for the Followers of Set. As masters of corruption and manipulation, they are natural suspects, but they are also in demand for their services. They prefer operating behind the scenes, bribing officials, supplying vices, and trading information. In Phoenix, this position is more important than ever as the city's Kindred society fragments and everyone seeks leverage.</p>
            
            <h3>Social and Political Standing</h3>
            <p>Among the Damned, Setites officially present as independents, but in practice they infiltrate every sect. They prefer operating behind the scenes, bribing officials, supplying vices, and trading information. In Camarilla domains, they often occupy gray-market roles: supplying illegal services to respected elders, running mortal fronts that everyone uses but no one publicly acknowledges.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>Other clans generally distrust or hate the Followers of Set, associating them with addiction, betrayal, and cultic manipulation. Ventrue and Tremere tend to treat them as dangerous but occasionally useful specialists. Setites reciprocate with a mixture of amusement and contempt: they consider most other Kindred to be hypocrites in denial about their own predatory nature. Nonetheless, they are very willing to forge alliances—short-term, conditional, and always hedged with leverage.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/setite_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Follower of Set NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Followers of Set in Phoenix are led by those who understand that corruption is an art form, and temptation is a tool of liberation. Whether they're serving as cult leaders, vice merchants, or scholarly heretics, the Setites make corruption their domain.</p>
                    
                    <p>Their featured members are masters of manipulation, using their Presence, Serpentis, and Obfuscate to control situations and people. They are the ones who run the clubs and cults that everyone uses but no one acknowledges, who supply the vices that others crave, and who ensure that even in the darkest nights, there is still someone who can offer exactly what you need—for a price.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Break the Chains</h2>
            <p>Are you drawn to the path of liberation through corruption? The Followers of Set offer a path of temptation, manipulation, and spiritual revelation. Whether you're a cult leader or a vice merchant, the clan of serpents welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Followers of Set characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Follower of Set Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
