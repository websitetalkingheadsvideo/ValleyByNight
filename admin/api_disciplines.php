<?php
/**
 * Disciplines API
 * Returns discipline powers data for character creation
 */
session_start();
header('Content-Type: application/json');

// Check authentication (optional - disciplines are public data)
// Uncomment if you want to restrict access
// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit();
// }

$action = isset($_GET['action']) ? $_GET['action'] : 'all';

// Discipline powers data (matches lotn_char_create.php)
$disciplinePowers = [
    'Animalism' => [
        ['level' => 1, 'name' => 'Feral Whispers', 'description' => 'Communicate with animals'],
        ['level' => 2, 'name' => 'Animal Succulence', 'description' => 'Feed from animals'],
        ['level' => 3, 'name' => 'Quell the Beast', 'description' => 'Calm frenzied vampires'],
        ['level' => 4, 'name' => 'Subsume the Spirit', 'description' => 'Possess animals'],
        ['level' => 5, 'name' => 'Animal Dominion', 'description' => 'Command all animals in area']
    ],
    'Auspex' => [
        ['level' => 1, 'name' => 'Heightened Senses', 'description' => 'Enhanced perception'],
        ['level' => 2, 'name' => 'Aura Perception', 'description' => 'See emotional auras'],
        ['level' => 3, 'name' => 'The Spirit\'s Touch', 'description' => 'Read objects\' history'],
        ['level' => 4, 'name' => 'Telepathy', 'description' => 'Read minds'],
        ['level' => 5, 'name' => 'Psychic Projection', 'description' => 'Astral projection']
    ],
    'Celerity' => [
        ['level' => 1, 'name' => 'Quickness', 'description' => 'The vampire can move and react at superhuman speeds, allowing them to perform actions much faster than normal.'],
        ['level' => 2, 'name' => 'Sprint', 'description' => 'The vampire can achieve incredible bursts of speed over short distances.'],
        ['level' => 3, 'name' => 'Enhanced Reflexes', 'description' => 'The vampire\'s reaction time becomes so fast they can dodge bullets and catch arrows in flight.'],
        ['level' => 4, 'name' => 'Blur', 'description' => 'The vampire moves so fast they become a blur, making them nearly impossible to target.'],
        ['level' => 5, 'name' => 'Accelerated Movement', 'description' => 'The vampire can maintain superhuman speed for extended periods.']
    ],
    'Dominate' => [
        ['level' => 1, 'name' => 'Cloud Memory', 'description' => 'Erase recent memories'],
        ['level' => 2, 'name' => 'Mesmerize', 'description' => 'Compel simple actions'],
        ['level' => 3, 'name' => 'The Forgetful Mind', 'description' => 'Implant false memories'],
        ['level' => 4, 'name' => 'Mass Manipulation', 'description' => 'Affect multiple targets'],
        ['level' => 5, 'name' => 'Possession', 'description' => 'Take control of body']
    ],
    'Fortitude' => [
        ['level' => 1, 'name' => 'Resilience', 'description' => 'Resist physical damage'],
        ['level' => 2, 'name' => 'Unswayable Mind', 'description' => 'Resist mental influence'],
        ['level' => 3, 'name' => 'Toughness', 'description' => 'Ignore wound penalties'],
        ['level' => 4, 'name' => 'Defy Bane', 'description' => 'Resist supernatural effects'],
        ['level' => 5, 'name' => 'Fortify the Inner Facade', 'description' => 'Become immune to damage']
    ],
    'Obfuscate' => [
        ['level' => 1, 'name' => 'Cloak of Shadows', 'description' => 'Hide in darkness'],
        ['level' => 2, 'name' => 'Silence of Death', 'description' => 'Move without sound'],
        ['level' => 3, 'name' => 'Mask of a Thousand Faces', 'description' => 'Change appearance'],
        ['level' => 4, 'name' => 'Vanish', 'description' => 'Become completely invisible'],
        ['level' => 5, 'name' => 'Cloak the Gathering', 'description' => 'Hide groups of people']
    ],
    'Potence' => [
        ['level' => 1, 'name' => 'Lethal Body', 'description' => 'Enhanced physical strength'],
        ['level' => 2, 'name' => 'Prowess', 'description' => 'Devastating physical attacks'],
        ['level' => 3, 'name' => 'Brutal Feed', 'description' => 'Feed through violence'],
        ['level' => 4, 'name' => 'Spark of Rage', 'description' => 'Cause frenzy in others'],
        ['level' => 5, 'name' => 'Earthshock', 'description' => 'Create earthquakes']
    ],
    'Presence' => [
        ['level' => 1, 'name' => 'Awe', 'description' => 'Inspire admiration'],
        ['level' => 2, 'name' => 'Dread Gaze', 'description' => 'Cause fear'],
        ['level' => 3, 'name' => 'Entrancement', 'description' => 'Create devoted followers'],
        ['level' => 4, 'name' => 'Summon', 'description' => 'Compel others to come'],
        ['level' => 5, 'name' => 'Majesty', 'description' => 'Become untouchable']
    ],
    'Protean' => [
        ['level' => 1, 'name' => 'Eyes of the Beast', 'description' => 'Enhanced night vision'],
        ['level' => 2, 'name' => 'Shape of the Beast', 'description' => 'Transform into animal'],
        ['level' => 3, 'name' => 'Mist Form', 'description' => 'Become mist'],
        ['level' => 4, 'name' => 'Form of the Ancient', 'description' => 'Become giant bat'],
        ['level' => 5, 'name' => 'Earth Meld', 'description' => 'Merge with earth']
    ],
    'Vicissitude' => [
        ['level' => 1, 'name' => 'Malleable Visage', 'description' => 'Change facial features'],
        ['level' => 2, 'name' => 'Fleshcraft', 'description' => 'Modify body structure'],
        ['level' => 3, 'name' => 'Bonecraft', 'description' => 'Manipulate bones'],
        ['level' => 4, 'name' => 'Horrid Form', 'description' => 'Take monstrous shape'],
        ['level' => 5, 'name' => 'Metamorphosis', 'description' => 'Complete body transformation']
    ],
    'Dementation' => [
        ['level' => 1, 'name' => 'Confusion', 'description' => 'Cause mental disorientation'],
        ['level' => 2, 'name' => 'The Haunting', 'description' => 'Create hallucinations'],
        ['level' => 3, 'name' => 'Nightmare', 'description' => 'Induce terrifying dreams'],
        ['level' => 4, 'name' => 'Total Insanity', 'description' => 'Drive target completely mad'],
        ['level' => 5, 'name' => 'The Beast Within', 'description' => 'Unleash inner monster']
    ],
    'Thaumaturgy' => [
        ['level' => 1, 'name' => 'A Taste for Blood', 'description' => 'Sense blood and vitae'],
        ['level' => 2, 'name' => 'Blood Rage', 'description' => 'Cause frenzy in others'],
        ['level' => 3, 'name' => 'The Blood Bond', 'description' => 'Create blood bonds'],
        ['level' => 4, 'name' => 'Blood of Acid', 'description' => 'Corrupt blood'],
        ['level' => 5, 'name' => 'Cauldron of Blood', 'description' => 'Mass blood manipulation']
    ],
    'Necromancy' => [
        ['level' => 1, 'name' => 'Speak with the Dead', 'description' => 'Communicate with spirits'],
        ['level' => 2, 'name' => 'Summon Soul', 'description' => 'Call forth spirits'],
        ['level' => 3, 'name' => 'Compel Soul', 'description' => 'Force spirit obedience'],
        ['level' => 4, 'name' => 'Reanimate Corpse', 'description' => 'Raise the dead'],
        ['level' => 5, 'name' => 'Soul Stealing', 'description' => 'Capture souls']
    ],
    'Quietus' => [
        ['level' => 1, 'name' => 'Silence of Death', 'description' => 'Move without sound'],
        ['level' => 2, 'name' => 'Touch of Death', 'description' => 'Poisonous touch'],
        ['level' => 3, 'name' => 'Baal\'s Caress', 'description' => 'Lethal blood attack'],
        ['level' => 4, 'name' => 'Blood of the Lamb', 'description' => 'Corrupt blood'],
        ['level' => 5, 'name' => 'The Killing Word', 'description' => 'Death by command']
    ],
    'Serpentis' => [
        ['level' => 1, 'name' => 'Eyes of the Serpent', 'description' => 'Hypnotic gaze'],
        ['level' => 2, 'name' => 'Tongue of the Asp', 'description' => 'Venomous bite'],
        ['level' => 3, 'name' => 'Form of the Cobra', 'description' => 'Transform into snake'],
        ['level' => 4, 'name' => 'The Serpent\'s Kiss', 'description' => 'Paralyzing venom'],
        ['level' => 5, 'name' => 'The Serpent\'s Embrace', 'description' => 'Complete serpent form']
    ],
    'Obtenebration' => [
        ['level' => 1, 'name' => 'Shroud of Night', 'description' => 'Create darkness'],
        ['level' => 2, 'name' => 'Arms of the Abyss', 'description' => 'Shadow tentacles'],
        ['level' => 3, 'name' => 'Shadow Form', 'description' => 'Become living shadow'],
        ['level' => 4, 'name' => 'Summon the Abyss', 'description' => 'Call forth darkness'],
        ['level' => 5, 'name' => 'Black Metamorphosis', 'description' => 'Become shadow demon']
    ],
    'Chimerstry' => [
        ['level' => 1, 'name' => 'Ignis Fatuus', 'description' => 'Create false lights'],
        ['level' => 2, 'name' => 'Fata Morgana', 'description' => 'Create illusions'],
        ['level' => 3, 'name' => 'Permanency', 'description' => 'Make illusions real'],
        ['level' => 4, 'name' => 'Horrid Reality', 'description' => 'Create nightmare illusions'],
        ['level' => 5, 'name' => 'Reality\'s Curtain', 'description' => 'Alter reality itself']
    ],
    'Daimoinon' => [
        ['level' => 1, 'name' => 'Summon Demon', 'description' => 'Call forth minor demons'],
        ['level' => 2, 'name' => 'Bind Demon', 'description' => 'Control summoned demons'],
        ['level' => 3, 'name' => 'Demon\'s Kiss', 'description' => 'Gain demonic powers'],
        ['level' => 4, 'name' => 'Hell\'s Gate', 'description' => 'Open portal to Hell'],
        ['level' => 5, 'name' => 'Infernal Mastery', 'description' => 'Command all demons']
    ],
    'Melpominee' => [
        ['level' => 1, 'name' => 'The Tragic Muse', 'description' => 'Inspire artistic genius'],
        ['level' => 2, 'name' => 'The Tragic Flaw', 'description' => 'Reveal fatal weaknesses'],
        ['level' => 3, 'name' => 'The Tragic Hero', 'description' => 'Create doomed champions'],
        ['level' => 4, 'name' => 'The Tragic End', 'description' => 'Ensure dramatic deaths'],
        ['level' => 5, 'name' => 'The Tragic Cycle', 'description' => 'Control fate itself']
    ],
    'Valeren' => [
        ['level' => 1, 'name' => 'The Healing Touch', 'description' => 'Heal others'],
        ['level' => 2, 'name' => 'The Warrior\'s Resolve', 'description' => 'Enhance combat abilities'],
        ['level' => 3, 'name' => 'The Martyr\'s Blessing', 'description' => 'Absorb others\' pain'],
        ['level' => 4, 'name' => 'The Saint\'s Grace', 'description' => 'Become immune to harm'],
        ['level' => 5, 'name' => 'The Messiah\'s Return', 'description' => 'Resurrect the dead']
    ],
    'Mortis' => [
        ['level' => 1, 'name' => 'Speak with the Dead', 'description' => 'Communicate with corpses'],
        ['level' => 2, 'name' => 'Animate Corpse', 'description' => 'Raise the dead'],
        ['level' => 3, 'name' => 'Bone Craft', 'description' => 'Manipulate bones'],
        ['level' => 4, 'name' => 'Soul Stealing', 'description' => 'Capture souls'],
        ['level' => 5, 'name' => 'Death\'s Embrace', 'description' => 'Become death itself']
    ]
];

// Return response in format expected by DataManager
$response = [
    'success' => true,
    'data' => [
        'disciplinePowers' => $disciplinePowers
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>

