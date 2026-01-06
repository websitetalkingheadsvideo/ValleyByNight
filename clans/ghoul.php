<?php
/**
 * Valley by Night - Ghoul Page
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
                        <li class="breadcrumb-item active" aria-current="page">Ghoul</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Section 1: Overview -->
        <div class="clan-detail-section">
            <div class="row">
                <div class="col-md-3 text-center mb-4">
                    <?php
                    $logo_path = '../images/Clan Logos/Ghoul_Symbol.webp';
                    $logo_exists = file_exists($logo_path);
                    if ($logo_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $logo_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="Ghoul Symbol">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="clan-logo d-flex align-items-center justify-content-center" style="width: 200px; height: 200px; margin: 0 auto; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--muted-gold); border-radius: 0.75rem;">
                        <h2 class="text-center mb-0">Ghoul</h2>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="mb-4">Ghoul</h1>
                    <p class="lead">Mortals bound to vampires through the consumption of vitae. Ghouls gain extended life and access to disciplines, serving their regnants in exchange for the blood that sustains them.</p>
                    
                    <p>Ghouls are mortals who have consumed vampire blood (vitae) and been bound to their regnant—the vampire who provides the blood. Through this bond, ghouls gain extended life, slowed aging, and access to a single discipline at the first level. They are not vampires, but they are no longer fully human either. They exist in a liminal space between mortal and Kindred, serving their regnants in exchange for the blood that sustains them.</p>
                    
                    <p>Ghoul culture is defined by their relationship with their regnants. Some ghouls are loyal servants, bound by love, duty, or the blood bond itself. Others are prisoners, forced into servitude through coercion or manipulation. Most exist somewhere in between, bound by the blood bond's powerful influence but maintaining some degree of autonomy. Ghouls often form their own communities, sharing information and support with others who understand their unique position.</p>
                    
                    <p>The blood bond creates a powerful psychological and emotional connection between ghoul and regnant. With each taste of vitae, the bond grows stronger, making the ghoul more devoted and dependent. This bond can be broken only by extended separation from the regnant, but even then, the psychological effects can linger for years. Some ghouls spend decades or even centuries in service, their extended lives a gift and a curse.</p>
                    
                    <div class="clan-sources">
                        <p><strong>Sources:</strong></p>
                        <p>Laws of the Night Revised</p>
                        <p>Various sourcebooks on ghouls and retainers</p>
                    </div>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 2: Ghouls in Phoenix -->
        <div class="clan-detail-section">
            <h2>Ghouls in Phoenix</h2>
            
            <p>In Phoenix, ghouls serve a vital role in Kindred society. They are the eyes and ears of their regnants, operating in the daylight hours when vampires cannot. They maintain businesses, manage resources, and interact with mortals in ways that Kindred cannot. The city's fragmentation after the Prince's murder has made their services more valuable than ever.</p>
            
            <h3>Current Situation</h3>
            <p>The Prince's murder has created both opportunity and danger for ghouls. As servants of Kindred, they are caught in the middle of the power struggle that follows. Some ghouls have found themselves serving new regnants as power shifts. Others have been abandoned or killed as their regnants fall. The city's fragmentation means that ghouls must be more careful than ever, navigating a landscape where allegiances are uncertain.</p>
            
            <h3>Social and Political Standing</h3>
            <p>Ghouls occupy a unique position in Kindred society. They are not Kindred, so they are not bound by the Traditions in the same way. They can operate during the day, interact with mortals freely, and maintain resources that vampires cannot. However, they are also dependent on their regnants for vitae, making them vulnerable to manipulation and control. In Phoenix, this position is more important than ever as the city's Kindred society fragments.</p>
            
            <h3>Notable Tensions and Opportunities</h3>
            <p>The blood bond creates both loyalty and dependency. Some ghouls are genuinely devoted to their regnants, while others serve out of necessity or fear. The bond can be broken, but doing so requires extended separation and can leave the ghoul vulnerable. In Phoenix, the city's fragmentation creates opportunities for ghouls to break free or find new regnants, but it also means that they are more vulnerable than ever.</p>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 3: Featured NPC -->
        <div class="clan-detail-section">
            <h2>Featured NPC</h2>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <?php
                    $npc_image = '../uploads/characters/ghoul_featured.jpg';
                    $npc_image_exists = file_exists($npc_image);
                    if ($npc_image_exists):
                    ?>
                    <img src="<?php echo $npc_image; ?>" class="clan-npc-image img-fluid" alt="Featured Ghoul NPC">
                    <?php else: ?>
                    <div class="clan-npc-image d-flex align-items-center justify-content-center" style="height: 300px; background: rgba(139, 0, 0, 0.3); border: 2px solid var(--blood-red); border-radius: 0.75rem;">
                        <p class="text-center mb-0">Portrait Unavailable</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <p>The ghouls of Phoenix are led by those who understand that service is power, and power is service. Whether they're serving as loyal retainers, independent operators, or those seeking to break free, ghouls make survival their domain.</p>
                    
                    <p>Their featured members are masters of adaptation, using their extended lives and access to disciplines to serve their regnants while maintaining some degree of autonomy. They are the ones who operate in the daylight hours, who manage resources and businesses, and who ensure that even in the darkest nights, there is still someone who can bridge the gap between mortal and Kindred worlds.</p>
                </div>
            </div>
        </div>

        <hr class="gothic-separator" aria-hidden="true">

        <!-- Section 4: Call to Action -->
        <div class="clan-cta">
            <h2>Serve the Night</h2>
            <p>Are you willing to trade mortality for extended life and power? Ghouls offer a path of service, loyalty, and the bridge between mortal and Kindred worlds. Whether you're a loyal retainer or someone seeking to break free, the bound welcome you.</p>
            <p class="note"><strong>Note:</strong> Advanced clans are not available at game launch. Ghoul characters are available for creation.</p>
            <a href="../lotn_char_create.php" class="btn btn-primary btn-lg">Create a Ghoul Character</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
