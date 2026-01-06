<?php
/**
 * Valley by Night - Clans Index
 * Landing page listing all active clans
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include header with clans CSS
$extra_css = ['css/clans.css', 'css/character_view.css'];
include '../includes/header.php';

// Define clans list with basic info
$clans = [
    [
        'name' => 'Assamite',
        'slug' => 'assamite',
        'description' => 'Middle Eastern assassins and warriors, masters of stealth and death. The Assamites are feared for their deadly precision and their complex relationship with the blood curse that drives them.',
        'blurb' => 'Silent shadows in the desert night, where honor and blood intertwine.'
    ],
    [
        'name' => 'Brujah',
        'slug' => 'brujah',
        'description' => 'Passionate rebels and idealists, the Brujah are known for their fiery tempers and physical prowess. They are the clan of revolutionaries, activists, and warriors who fight for their ideals with every fiber of their being.',
        'blurb' => 'Rebels with a cause, where passion meets philosophy and action speaks louder than words.'
    ],
    [
        'name' => 'Caitiff',
        'slug' => 'caitiff',
        'description' => 'Clanless vampires, outcasts from Kindred society. Caitiff lack the traditional clan structure and often face discrimination, but they are free from clan curses and can develop any disciplines.',
        'blurb' => 'Outcasts and free agents, forging their own path in a world of ancient bloodlines.'
    ],
    [
        'name' => 'Daughter of Cacophony',
        'slug' => 'daughter_of_cacophony',
        'description' => 'Musical sirens and performers, the Daughters of Cacophony use their voices as both weapon and art. They are rare, mysterious, and their songs can entrance or destroy.',
        'blurb' => 'Where melody becomes magic and every note carries the power to enchant or destroy.'
    ],
    [
        'name' => 'Followers of Set',
        'slug' => 'followers_of_set',
        'description' => 'Egyptian cultists and corruptors, the Followers of Set serve their dark god through temptation and corruption. They cannot enter holy ground but excel at manipulation and the Serpentis discipline.',
        'blurb' => 'Ancient darkness wrapped in modern temptation, where corruption is an art form.'
    ],
    [
        'name' => 'Gangrel',
        'slug' => 'gangrel',
        'description' => 'Nomadic shapeshifters and survivors, the Gangrel are closely attuned to the Beast and the natural world. They are self-reliant loners who often live on the fringes of Kindred society.',
        'blurb' => 'Wild souls who walk between the city and the wilderness, where the Beast is always close.'
    ],
    [
        'name' => 'Ghoul',
        'slug' => 'ghoul',
        'description' => 'Mortals bound to vampires through the consumption of vitae. Ghouls gain extended life and access to disciplines, serving their regnants in exchange for the blood that sustains them.',
        'blurb' => 'Bound by blood, serving the night in exchange for power and extended life.'
    ],
    [
        'name' => 'Giovanni',
        'slug' => 'giovanni',
        'description' => 'Necromancers and businessmen, death merchants who cannot create blood bonds. The Giovanni are a tightly-knit family clan that controls death itself through their unique discipline.',
        'blurb' => 'Masters of death and commerce, where family bonds are stronger than any blood tie.'
    ],
    [
        'name' => 'Malkavian',
        'slug' => 'malkavian',
        'description' => 'Seers and madmen with unique insights, all Malkavians suffer from some form of derangement. Their madness grants them prophetic visions and unpredictable behavior that makes them both feared and sought after.',
        'blurb' => 'Where madness meets prophecy, and the line between insight and insanity blurs.'
    ],
    [
        'name' => 'Nosferatu',
        'slug' => 'nosferatu',
        'description' => 'Information brokers and masters of stealth, the Nosferatu are hideously deformed and cannot pass as human. They excel at gathering secrets and operating from the shadows.',
        'blurb' => 'Hidden in the darkness, where information is currency and secrets are power.'
    ],
    [
        'name' => 'Ravnos',
        'slug' => 'ravnos',
        'description' => 'Illusionists and tricksters, nomadic wanderers who cannot resist challenges to their honor. The Ravnos are masters of Chimerstry, creating illusions so real they can harm.',
        'blurb' => 'Masters of illusion and deception, where nothing is as it seems and honor is everything.'
    ],
    [
        'name' => 'Toreador',
        'slug' => 'toreador',
        'description' => 'Artists and socialites, lovers of beauty who are prone to distraction by it. The Toreador are the clan of aesthetics, culture, and social grace, often serving as the face of Kindred society.',
        'blurb' => 'Where beauty is everything, and art becomes a way of life in the eternal night.'
    ],
    [
        'name' => 'Tremere',
        'slug' => 'tremere',
        'description' => 'Blood sorcerers and scholars, the Tremere cannot create childer without permission. They are organized, hierarchical, and masters of Thaumaturgy, using blood magic to maintain their power.',
        'blurb' => 'Where knowledge is power, and blood magic unlocks the secrets of the universe.'
    ],
    [
        'name' => 'Ventrue',
        'slug' => 'ventrue',
        'description' => 'Aristocrats and leaders of Kindred society, the Ventrue are the clan of princes and primogen. They are natural leaders who excel at politics, business, and maintaining the Masquerade.',
        'blurb' => 'Born to rule, where power and privilege shape the very structure of Kindred society.'
    ]
];
?>

<div class="page-content container py-4">
    <main id="main-content">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Clans of the Night</h1>
                <p class="lead mb-4">
                    Explore the thirteen clans of Kindred society, each with their own history, culture, and unique gifts. Choose your path carefully, for your clan will define your place in the eternal struggle.
                </p>
            </div>
        </div>

        <div class="row g-4 clans-index">
            <?php foreach ($clans as $clan): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card h-100">
                    <?php
                    // Map clan slugs to actual logo filenames
                    $logo_map = [
                        'assamite' => 'LogoClanAssamite.webp',
                        'brujah' => 'LogoClanBrujah.webp',
                        'caitiff' => 'LogoBloodlineCaitiff.webp',
                        'daughter_of_cacophony' => 'LogoBloodlineDaughtersofCacophony.webp',
                        'followers_of_set' => 'LogoClanFollowersofSet.webp',
                        'gangrel' => 'LogoClanGangrel.webp',
                        'ghoul' => 'Ghoul_Symbol.webp',
                        'giovanni' => 'LogoClanGiovanni.webp',
                        'malkavian' => 'LogoClanMalkavian.webp',
                        'nosferatu' => 'LogoClanNosferatu.webp',
                        'ravnos' => 'LogoClanRavnos.webp',
                        'toreador' => 'LogoClanToreador.webp',
                        'tremere' => 'LogoClanTremere.webp',
                        'ventrue' => 'LogoClanVentrue.webp'
                    ];
                    $logo_filename = $logo_map[$clan['slug']] ?? null;
                    if ($logo_filename):
                        $image_path = '../images/Clan Logos/' . $logo_filename;
                        $image_exists = file_exists($image_path);
                        if ($image_exists):
                    ?>
                    <div class="character-portrait-wrapper">
                        <div class="character-portrait-media">
                            <img src="<?php echo $image_path; ?>" class="character-portrait-image character-portrait-logo img-fluid" alt="<?php echo htmlspecialchars($clan['name']); ?> clan symbol">
                        </div>
                    </div>
                    <?php 
                        endif;
                    endif; 
                    ?>
                    <div class="card-body">
                        <h2 class="card-title">
                            <a href="<?php echo $clan['slug']; ?>.php" class="text-decoration-none">
                                <?php echo htmlspecialchars($clan['name']); ?>
                            </a>
                        </h2>
                        <p class="card-text"><?php echo htmlspecialchars($clan['description']); ?></p>
                        <p class="character-blurb"><?php echo htmlspecialchars($clan['blurb']); ?></p>
                        <a href="<?php echo $clan['slug']; ?>.php" class="btn btn-primary">Learn More</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
