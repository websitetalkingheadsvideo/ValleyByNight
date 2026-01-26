<?php
/**
 * pre_game_primer.php
 * 
 * Pre-Game History Primer - What your character knows before Session 1
 */

declare(strict_types=1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set up page-specific CSS
$extra_css = ['css/glossary.css'];

// Include header
require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-wrapper">
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-light mb-3">Pre-Game History Primer</h1>
                <p class="text-light mb-4">What your character knows before their first night in Phoenix's Kindred society.</p>
            </div>
        </div>

        <div class="primer-content">
            <!-- The Setting -->
            <div class="card mb-4 bg-dark border-danger">
                <div class="card-header bg-dark border-danger">
                    <h2 class="text-light mb-0">The Setting</h2>
                </div>
                <div class="card-body text-light">
                    <p><strong>Phoenix, Arizona - October 1994</strong></p>
                    <p>You are a neonate Kindred, newly Embraced or recently arrived in Phoenix. Tonight is the night you will be formally introduced to Kindred society at a Camarilla gathering at Hawthorn Estate, the domain's primary Elysium.</p>
                    <p>The city sprawls across the Sonoran Desert, a neon-lit maze of strip malls, gated communities, and endless asphalt. In 1994, Phoenix is still growing, still building, still defining itself. For Kindred, it's a city of opportunity and danger—new enough that power structures are still forming, old enough that ancient grudges have taken root.</p>
                </div>
            </div>

            <!-- Recent History -->
            <div class="card mb-4 bg-dark border-danger">
                <div class="card-header bg-dark border-danger">
                    <h2 class="text-light mb-0">Recent History</h2>
                </div>
                <div class="card-body text-light">
                    <h3 style="color: var(--muted-gold);">Prince Solomon Reaves (1973-1994)</h3>
                    <p>Prince Solomon Reaves, a Brujah, has ruled Phoenix for over 20 years. He assumed the Princedom in 1973 after the mysterious disappearance of his sire, Prince Elijah Crenshaw. Under Solomon's rule, Phoenix became a stable Camarilla domain, though tensions with Anarchs and other factions simmered beneath the surface.</p>
                    <p>In 1989, Prince Reaves brought in Roland Cross, a legendary Toreador Sheriff with over a century of experience enforcing Camarilla law across the Southwest. This move suggested the Prince was aware of growing threats or preparing for conflict.</p>

                    <h3 class="mt-4" style="color: var(--muted-gold);">The Current Crisis</h3>
                    <p><strong>Tonight, at Hawthorn Estate, the Prince will be assassinated.</strong></p>
                    <p>This is what you know: Tonight is your formal introduction to Kindred society. You've been told to attend a gathering at Hawthorn Estate, where you'll be presented to the court and formally recognized as a member of the Camarilla. What you don't know—what no one knows yet—is that before the night ends, Prince Solomon Reaves will be dead, and Phoenix will be plunged into chaos.</p>
                </div>
            </div>

            <!-- Power Structure -->
            <div class="card mb-4 bg-dark border-danger">
                <div class="card-header bg-dark border-danger">
                    <h2 class="text-light mb-0">Power Structure</h2>
                </div>
                <div class="card-body text-light">
                    <h3 style="color: var(--muted-gold);">The Court</h3>
                    <ul>
                        <li><strong>Prince Solomon Reaves</strong> (Brujah) - Ruler of Phoenix since 1973</li>
                        <li><strong>Cordelia Fairchild</strong> (Toreador, Harpy) - Has controlled social standing in Phoenix since the 1950s. Her word determines who can participate in Kindred society.</li>
                        <li><strong>Roland Cross</strong> (Toreador, Sheriff) - Enforces Camarilla law. Arrived in 1989. Legendary for his precision and lethality.</li>
                        <li><strong>The Primogen Council</strong> - Representatives from each major clan. You know they exist, but their internal politics are opaque to a neonate.</li>
                    </ul>

                    <h3 class="mt-4" style="color: var(--muted-gold);">Power Centers</h3>
                    <ul>
                        <li><strong>Hawthorn Estate</strong> - Elysium, located in Northern Scottsdale. Where major Camarilla gatherings are held.</li>
                        <li><strong>24th Street Corridor</strong> - Anarch territory, but they steer clear of the Arizona State Hospital grounds (controlled by a powerful Malkavian elder).</li>
                        <li><strong>Scottsdale (Camelback Mountain area)</strong> - Giovanni family stronghold.</li>
                        <li><strong>Mesa</strong> - Setite temple operates here (disguised as a nightclub). Also home to a mage-controlled skyscraper (becomes important later).</li>
                        <li><strong>Guadalupe, AZ</strong> - Rumored Sabbat pack presence. Creates constant background paranoia.</li>
                    </ul>
                </div>
            </div>

            <!-- Key Figures -->
            <div class="card mb-4 bg-dark border-danger">
                <div class="card-header bg-dark border-danger">
                    <h2 class="text-light mb-0">Key Figures You've Heard Of</h2>
                </div>
                <div class="card-body text-light">
                    <p>As a neonate, you've heard names and rumors. These are the figures whose reputations precede them:</p>

                    <h3 style="color: var(--muted-gold);">Camarilla</h3>
                    <ul>
                        <li><strong>Cordelia Fairchild</strong> - The Harpy. Her social judgments are absolute. Cross her at your peril.</li>
                        <li><strong>Roland Cross</strong> - The Sheriff. Never misses. Never wastes a bullet. His presence means order—or death.</li>
                        <li><strong>Misfortune</strong> (Malkavian, Primogen) - Collects boons like currency. Knows everyone's debts.</li>
                        <li><strong>James Whitmore</strong> (Tremere, Regent) - Runs the local chantry. Magic and business combined.</li>
                        <li><strong>Alistaire</strong> (Nosferatu, Primogen) - Information broker. Lives in Mesa storm drains (or so everyone says).</li>
                    </ul>

                    <h3 class="mt-4" style="color: var(--muted-gold);">Anarchs</h3>
                    <ul>
                        <li><strong>Piston</strong> (Brujah) - Hell's Angel enforcer. Runs operations in Surprise. Dangerous and unpredictable.</li>
                        <li><strong>Bayside Bob</strong> (Toreador) - Owns The Bali Hai, a tiki bar on Camelback Road. De facto Anarch gathering place.</li>
                    </ul>

                    <h3 class="mt-4" style="color: var(--muted-gold);">Other Factions</h3>
                    <ul>
                        <li><strong>The Hospital Elder</strong> (Malkavian) - Controls Arizona State Hospital grounds. Even Anarchs respect this boundary. No one knows their name.</li>
                        <li><strong>Giovanni</strong> - Present in Scottsdale. Wealthy, neutral, and dangerous.</li>
                        <li><strong>Setites</strong> - Operating in Mesa. Temple disguised as a nightclub. Stay away unless you want trouble.</li>
                    </ul>
                </div>
            </div>

            <!-- What You Don't Know -->
            <div class="card mb-4 bg-dark border-danger">
                <div class="card-header bg-dark border-danger">
                    <h2 class="text-light mb-0">What You Don't Know (Yet)</h2>
                </div>
                <div class="card-body text-light">
                    <p>As a neonate, there are many things you don't know:</p>
                    <ul>
                        <li>The full extent of clan politics and internal conflicts</li>
                        <li>The details of the 1973 disappearance of Prince Elijah Crenshaw</li>
                        <li>The true nature of the Nosferatu's information network (Shrecknet)</li>
                        <li>The specific plots and schemes of various Primogen</li>
                        <li>Who will try to fill the power vacuum after the Prince's death</li>
                        <li>What tonight's gathering will truly become</li>
                    </ul>
                    <p class="mt-3"><strong>That's what makes tonight dangerous—and exciting.</strong></p>
                </div>
            </div>

            <!-- Your First Night -->
            <div class="card mb-4 bg-dark border-danger">
                <div class="card-header bg-dark border-danger">
                    <h2 class="text-light mb-0">Your First Night</h2>
                </div>
                <div class="card-body text-light">
                    <p>Tonight, you will:</p>
                    <ol>
                        <li>Arrive at Hawthorn Estate for your formal introduction</li>
                        <li>Be presented to the court and recognized as a member of the Camarilla</li>
                        <li>Witness the assassination of Prince Solomon Reaves</li>
                        <li>Find yourself in a city without a Prince, where every faction is scrambling for power</li>
                        <li>Make choices that will determine your survival and your place in Phoenix's Kindred society</li>
                    </ol>
                    <p class="mt-3"><strong>The chronicle begins with the Prince's death. Your story begins with how you respond.</strong></p>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card mb-4 bg-dark border-danger">
                <div class="card-header bg-dark border-danger">
                    <h2 class="text-light mb-0">Canon Timeline</h2>
                </div>
                <div class="card-body text-light">
                    <ul>
                        <li><strong>1973:</strong> Prince Elijah Crenshaw disappears. Solomon Reaves assumes the Princedom.</li>
                        <li><strong>1981:</strong> Bayside Bob arrives in Phoenix, establishes The Bali Hai.</li>
                        <li><strong>1989:</strong> Roland Cross arrives as Sheriff. Betty (Nosferatu) is Embraced.</li>
                        <strong>Early 1990s:</strong> Piston arrives in Phoenix, establishes Anarch operations in Surprise.</li>
                        <li><strong>December 11, 1993:</strong> Sarah Hansen claims Mill Avenue, forms art coterie with Julien Roche and Harold Ashby.</li>
                        <li><strong>October 8, 1994:</strong> Game start. Prince Solomon Reaves is assassinated at Hawthorn Estate.</li>
                    </ul>
                    <p class="mt-3"><strong>Elysium Schedule:</strong> 2nd and 4th Saturdays of each month at Hawthorn Estate.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
