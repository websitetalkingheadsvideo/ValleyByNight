<?php
/**
 * Valley by Night - Ravnos Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Ravnos</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanRavnos.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Ravnos Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Ravnos</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Ravnos</h1>
                    <p class="lead">Illusionists and tricksters, nomadic wanderers who cannot resist challenges to their honor. The Ravnos are masters of Chimerstry, creating illusions so real they can harm.</p>
                    
                    <p>The Ravnos trace their origins to India, where according to the Karavalanisha Vrana (Wounds of the Night's Sword), they were created to hunt down the asuratizayya (countless demons)—fallen angels who had betrayed their duty. Their progenitor, Zapathasura (the Accursed Monster), was created by the gods to be a warrior against these demons.</p>
                    
                    <p>Ravnos culture is built around individualism, freedom, and the Path of Paradox. They are wanderers who rarely stay in one place too long, valuing their independence above all else. Many Ravnos travel with nomadic groups (historically the Rroma), but they maintain their own separate identity. The clan is organized into jati (castes/lineages): Brahman (prophets and seers), Kshatriyas (warriors), Vaisyas (merchants and those who interact with mortals), and Chandalas (outcasts).</p>
                    
                    <p>Ravnos philosophy centers on the Path of Paradox (mayaparisatya), which teaches that each Ravnos must find their svadharma (true purpose) by transcending their curse. The path views the Embrace as locking the soul outside the cycle of samsara (reincarnation), requiring the Ravnos to reestablish their dharma as one of the Kindred. By understanding and penetrating maya (illusion), a Ravnos can transcend the curses of undeath.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Ravnos (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Ravnos in Phoenix</h2>
            
            <p>In Phoenix, the Ravnos have found a city that reflects their nature: the desert's isolation and the city's fragmentation after the Prince's murder create opportunities for those who can adapt and move on. The Ravnos are wanderers who rarely stay in one place too long, and Phoenix is just another stop on their endless journey.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created both opportunity and danger for the Ravnos. As masters of illusion and deception, they are natural suspects, but they are also in demand for their services. They may claim membership in either sect for convenience, but they rarely commit fully. In Phoenix, this position is more important than ever as the city's Kindred society fragments and everyone seeks advantage.</p>
            
            <h3>Social and Political Standing</h3>
            <p>Ravnos are largely independent, rejecting both Camarilla and Sabbat structures. In Camarilla domains, they're often tolerated as long as they don't cause trouble, but many princes are quick to expel them. Most other clans view Ravnos with suspicion or outright hostility due to their reputation for crime and deception. Since the Week of Nightmares, many clans view the Ravnos as a dying or dead clan, which has only increased their isolation.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The clan has a long-standing feud with the Gangrel, dating back to conflicts over herds when Ravnos traveled with the Rroma through Europe. They're often mistaken for or confused with Malkavians due to their trickster reputation. Their compulsion to commit crimes creates constant tension and moral conflict, but it also makes them unpredictable and dangerous.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/ravnos_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Ravnos NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Ravnos of Phoenix are led by those who understand that illusion is reality, and reality is illusion. Whether they're serving as wanderers, tricksters, or path seekers, the Ravnos make deception their domain.</p>
                    
                    <p>Their featured members are masters of Chimerstry, using their ability to create illusions so real they can harm to manipulate situations and people. They are the ones who never stay in one place too long, who use their compulsion to commit crimes as both curse and tool, and who ensure that even in the darkest nights, there is still someone who can make you question what is real.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Find Your Path</h2>
            <p>Are you drawn to the path of illusion and freedom? The Ravnos offer a path of deception, travel, and the search for true purpose. Whether you're a wanderer or a trickster, the clan of illusionists welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Ravnos characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Ravnos Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
