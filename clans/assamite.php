<?php
/**
 * Valley by Night - Assamite Clan Page
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
                        <li class="breadcrumb-item active" aria-current="page">Assamite</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Clan Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/LogoClanAssamite.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Assamite Clan Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Assamite</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Assamite</h1>
                    <p class="lead">Middle Eastern assassins and warriors, masters of stealth and death. The Assamites are feared for their deadly precision and their complex relationship with the blood curse that drives them.</p>
                    
                    <p>The Assamites trace their lineage to Haqim, a legendary judge and hunter of the Damned who established a fortress in the Middle East and gathered followers bound by strict codes of justice, loyalty, and blood. Over centuries, they positioned themselves as executioners of those Kindred who violated divine or clan law, earning a reputation as peerless assassins.</p>
                    
                    <p>Assamite culture is highly structured and steeped in ritual. They emphasize duty, hierarchy, and the refinement of one's chosen path, whether that is martial discipline, occult mastery, or intellectual and artistic excellence. Life is governed by codes, contracts, and oaths rather than whim. Many Assamites still orient themselves around a central homeland and fortress-temple, even if they now operate globally.</p>
                    
                    <p>At their core, Assamites believe in judgment and rectification: the world is broken, and they are tools—sometimes scalpels, sometimes scythes—for cutting out corruption. For Warriors, this is expressed through targeted violence and the elimination of the unworthy. For Sorcerers, it is the application of mystical law, curses, and complex rites. For Viziers, it is honing craft, governance, or diplomacy to a level that imposes order on chaos.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Clanbook: Assamite (Revised Edition)</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Clan in Phoenix -->
        <div class="clan-detail-section">
            <h2>Assamite in Phoenix</h2>
            
            <p>In Phoenix, the Assamites maintain a presence that reflects their nature: they operate in the shadows, taking contracts and enforcing their own codes of justice. The desert city's isolation and the fragmentation after the Prince's murder create opportunities for those who can provide services that other clans cannot—or will not.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created both opportunity and danger for the Assamites. As masters of assassination, they are natural suspects, but they are also in demand for their services. Princes and elders sometimes contract them covertly, despite prohibitions, to remove rivals or troublemakers. The clan's reputation as assassins means that their presence anywhere causes paranoia and subtle security shifts.</p>
            
            <h3>Social and Political Standing</h3>
            <p>In Camarilla domains, Assamites officially stand outside sect politics but are deeply entangled in practice. They are viewed with a mix of fear, respect, and prejudice. Ventrue and Tremere, in particular, remember historical assassination campaigns and wield political narratives about 'fanatic killers' to justify control measures. The Assamites, in turn, see most other clans as hypocrites who condemn murder while practicing it through proxies.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The clan is divided between traditionalists clinging to isolation and the old ways, reformers who want deeper engagement with global Kindred society, and extremists who revel in diablerie and blood addiction. Some act as enforcers for internal or regional codes, while others hire out as mercenaries in all but name, pushing the clan toward open conflict with established powers. In Phoenix, this tension is more immediate as the city's power structures fragment.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/assamite_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Assamite NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The Assamites of Phoenix are led by those who understand that judgment is a sacred act, and death is a tool of justice. Whether they're serving as warriors, sorcerers, or viziers, the Assamites make precision their domain.</p>
                    
                    <p>Their featured members are masters of their chosen paths, using their Quietus, Celerity, and Obfuscate to enforce their codes of justice. They are the ones who take contracts and fulfill them with deadly precision, who enforce clan law through violence or sorcery, and who ensure that even in the darkest nights, there is still someone who can deliver judgment.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Embrace the Path</h2>
            <p>Are you drawn to the path of judgment and precision? The Assamites offer a path of duty, honor, and deadly skill. Whether you're a warrior, sorcerer, or vizier, the clan of assassins welcomes you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Assamite characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create an Assamite Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
