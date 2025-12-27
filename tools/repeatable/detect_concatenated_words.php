<?php
/**
 * Function to detect if a long word is actually multiple words concatenated
 * Tests words longer than 7 letters to see if they can be split into 2-4 valid words
 */

// Get word list function
function getWordList(): array {
    // Common English words list (most frequent words)
    $common_words = [
        // Articles and pronouns
        'the', 'a', 'an', 'and', 'or', 'but', 'if', 'when', 'where', 'while', 'that', 'this', 'these', 'those',
        'he', 'she', 'it', 'they', 'we', 'you', 'i', 'me', 'him', 'her', 'us', 'them', 'his', 'her', 'its', 'their', 'our', 'your', 'my',
        
        // Prepositions
        'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'into', 'onto', 'upon', 'over', 'under', 'through', 'between', 'against', 'without', 'within', 'among', 'across', 'during', 'since', 'until', 'after', 'before', 'during',
        
        // Common verbs
        'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'can', 'may', 'might',
        'get', 'got', 'give', 'gave', 'take', 'took', 'make', 'made', 'go', 'went', 'come', 'came', 'see', 'saw', 'know', 'knew', 'think', 'thought', 'say', 'said',
        'find', 'found', 'use', 'used', 'work', 'worked', 'call', 'called', 'try', 'tried', 'ask', 'asked', 'need', 'needed', 'want', 'wanted', 'tell', 'told',
        'become', 'became', 'leave', 'left', 'put', 'let', 'help', 'helped', 'begin', 'began', 'show', 'showed', 'hear', 'heard', 'play', 'played', 'run', 'ran',
        'move', 'moved', 'live', 'lived', 'believe', 'believed', 'bring', 'brought', 'happen', 'happened', 'write', 'wrote', 'sit', 'sat', 'stand', 'stood', 'lose', 'lost',
        'pay', 'paid', 'meet', 'met', 'include', 'included', 'continue', 'continued', 'set', 'lead', 'led', 'understand', 'understood', 'watch', 'watched', 'follow', 'followed',
        'stop', 'stopped', 'create', 'created', 'speak', 'spoke', 'read', 'read', 'allow', 'allowed', 'add', 'added', 'spend', 'spent', 'grow', 'grew', 'open', 'opened',
        'walk', 'walked', 'win', 'won', 'offer', 'offered', 'remember', 'remembered', 'love', 'loved', 'consider', 'considered', 'appear', 'appeared', 'buy', 'bought',
        'wait', 'waited', 'serve', 'served', 'die', 'died', 'send', 'sent', 'build', 'built', 'stay', 'stayed', 'fall', 'fell', 'cut', 'cut', 'reach', 'reached',
        'kill', 'killed', 'raise', 'raised', 'pass', 'passed', 'sell', 'sold', 'decide', 'decided', 'return', 'returned', 'explain', 'explained', 'develop', 'developed',
        'carry', 'carried', 'break', 'broke', 'receive', 'received', 'agree', 'agreed', 'support', 'supported', 'hit', 'hit', 'produce', 'produced', 'eat', 'ate',
        'cover', 'covered', 'catch', 'caught', 'draw', 'drew', 'choose', 'chose',
        
        // Common nouns
        'time', 'year', 'people', 'way', 'day', 'man', 'thing', 'woman', 'life', 'child', 'world', 'school', 'state', 'family', 'student', 'group', 'country', 'problem', 'hand', 'part',
        'place', 'case', 'week', 'company', 'system', 'program', 'question', 'work', 'government', 'number', 'night', 'point', 'home', 'water', 'room', 'mother', 'area', 'money',
        'story', 'fact', 'month', 'lot', 'right', 'study', 'book', 'eye', 'job', 'word', 'business', 'issue', 'side', 'kind', 'head', 'house', 'service', 'friend', 'father', 'power',
        'hour', 'game', 'line', 'end', 'member', 'law', 'car', 'city', 'community', 'name', 'president', 'team', 'minute', 'idea', 'kid', 'body', 'information', 'back', 'parent',
        'face', 'others', 'level', 'office', 'door', 'health', 'person', 'art', 'war', 'history', 'party', 'result', 'change', 'morning', 'reason', 'research', 'girl', 'guy',
        'moment', 'air', 'teacher', 'force', 'education',
        
        // Adjectives
        'good', 'new', 'first', 'last', 'long', 'great', 'little', 'own', 'other', 'old', 'right', 'big', 'high', 'different', 'small', 'large', 'next', 'early', 'young', 'important',
        'few', 'public', 'bad', 'same', 'able', 'human', 'local', 'late', 'hard', 'major', 'better', 'economic', 'strong', 'possible', 'whole', 'free', 'military', 'true', 'federal',
        'international', 'full', 'special', 'easy', 'clear', 'recent', 'certain', 'personal', 'open', 'red', 'difficult', 'available', 'likely', 'national', 'political', 'real', 'best',
        'left', 'sure', 'black', 'white', 'past', 'ready', 'general', 'financial', 'medical', 'wrong', 'private', 'past', 'foreign', 'fine', 'common', 'poor', 'natural', 'significant',
        'similar', 'hot', 'dead', 'central', 'happy', 'serious', 'ready', 'simple', 'legal', 'fair', 'beautiful', 'entire', 'civil', 'primary', 'careful', 'concerned', 'recent',
        'willing', 'nice', 'wonderful', 'impossible', 'terrible', 'normal', 'healthy', 'correct', 'clever', 'complete', 'proud', 'afraid', 'brave', 'satisfied', 'delighted',
        
        // Adverbs
        'not', 'up', 'so', 'out', 'just', 'now', 'how', 'then', 'more', 'also', 'here', 'well', 'only', 'very', 'even', 'back', 'there', 'down', 'still', 'in', 'as', 'too', 'when',
        'never', 'really', 'most', 'on', 'why', 'about', 'over', 'again', 'where', 'off', 'away', 'however', 'always', 'today', 'far', 'quite', 'rather', 'almost', 'enough',
        'probably', 'perhaps', 'maybe', 'already', 'soon', 'once', 'twice', 'often', 'sometimes', 'usually', 'always', 'never', 'hardly', 'nearly', 'quite', 'very', 'too', 'so',
        
        // Game-specific common terms
        'blood', 'vampire', 'kindred', 'elder', 'sire', 'childe', 'clan', 'sect', 'coterie', 'prince', 'primogen', 'sheriff', 'scourge', 'harpy', 'kine', 'mortal', 'ghoul',
        'discipline', 'trait', 'challenge', 'action', 'rage', 'pathos', 'glamour', 'willpower', 'health', 'corpus', 'damage', 'heal', 'power', 'gift', 'arcanoi', 'cantrip',
        'anarch', 'sabbat', 'camarilla', 'masquerade', 'tradition', 'elders', 'childer', 'neonate', 'ancilla', 'antediluvian', 'caine', 'generation', 'embrace', 'diablerie',
        'havens', 'elysium', 'domain', 'hunting', 'feeding', 'vitae', 'humanity', 'path', 'road', 'frenzy', 'roetschreck', 'torpor', 'final', 'death', 'stake', 'fire', 'fires', 'sunlight',
        
        // Additional common words needed for detection
        'change', 'watered', 'gathered', 'proposed', 'successful', 'extra', 'during', 'convocation', 'rebellious', 'spilled', 'grown', 'bulwark', 'youthful', 'ignite', 'wildfire',
        'fires', 'fire', 'elders', 'an', 'on', 'a', 'take', 'challenge',
        'throughout', 'assaulted', 'inspired', 'insolence', 'rose', 'sires', 'continent', 'clearing', 'avenues', 'raged', 'eldest', 'height', 'madness', 'rebels', 'destroyed',
        'lasombra', 'claimed', 'bolstered', 'diablerie', 'rebellious', 'youth', 'called', 'marched', 'eastern', 'laying', 'waste', 'stranglehold', 'bond', 'found', 'suddenly',
        'neonates', 'ancillae', 'slipping', 'leashes', 'thought', 'secure', 'eager', 'opportunity', 'diablerize', 'european', 'joined', 'fight', 'arrangement', 'offered', 'cross',
        'territorial', 'lines', 'issues', 'whole', 'true', 'form', 'skepticism', 'havens', 'storms', 'weathered', 'trials', 'centuries', 'remained', 'vision', 'founders', 'groundwork',
        'next', 'five', 'middle', 'fifteenth', 'persuaded', 'enough', 'cause', 'forth', 'significant', 'resistance', 'rebellion', 'drawn', 'across', 'bound', 'single', 'purpose',
        'known', 'aims', 'finally', 'united', 'began', 'regain', 'ground', 'fractious', 'hand-picked', 'intimates', 'returned', 'location', 'hidden', 'assamite', 'fortress', 'alamut',
        'demise', 'revolt', 'assured', 'stalemate', 'minor', 'skirmishing', 'motivated', 'inquisition', 'fiery', 'backdrop', 'deemed', 'long-ignored', 'centerpiece', 'order',
        'visibly', 'lord', 'shadows', 'enforcing', 'traditions', 'protecting', 'themselves', 'wrath', 'charade', 'span', 'globe', 'movement', 'agreed', 'parley', 'convention',
        'thorns', 'convened', 'abbey', 'england', 'there', 'accepted', 'terms', 'surrender', 'treaty', 'allowed', 'wished', 'come', 'fold', 'levied', 'punishment', 'role',
        'came', 'guiding', 'cainite', 'refused', 'return', 'same', 'stifling', 'caused', 'rebel', 'first', 'place', 'rejected', 'purported', 'peace', 'fled', 'scandinavia',
        'nurse', 'wounds', 'grudges', 're-emerged', 'self-imposed', 'exile', 'reformed', 'staunchest', 'bloody', 'opposition',
        'smoldering', 'glares', 'glare',
    ];
    
    // Convert to lowercase for case-insensitive matching
    $common_words = array_map('strtolower', $common_words);
    $common_words = array_unique($common_words);
    
    return $common_words;
}

// Global word list for backward compatibility
$common_words = getWordList();

/**
 * Check if a word can be split into valid word parts
 * @param string $word The word to test
 * @param array $word_list List of valid words to check against
 * @return array|false Returns array of word parts if valid split found, false otherwise
 */
function detectConcatenatedWords(string $word, array $word_list): array|false {
    $word = strtolower($word);
    $len = strlen($word);
    
    // Only test words 7 characters or longer (to catch words like "firesof" = 7 chars)
    if ($len < 7) {
        return false;
    }
    
    // Try splitting into 2 words (allow 2-char words for second part)
    for ($i = 2; $i <= $len - 2; $i++) {
        $part1 = substr($word, 0, $i);
        $part2 = substr($word, $i);
        
        // First part should be at least 3 chars (unless it's a common 2-char word)
        if ($i < 3 && !in_array($part1, $word_list)) continue;
        
        if (in_array($part1, $word_list) && in_array($part2, $word_list)) {
            return [$part1, $part2];
        }
    }
    
    // Try splitting into 3 words (allow 1-char words like "a", "i")
    for ($i = 1; $i <= $len - 3; $i++) {
        $part1 = substr($word, 0, $i);
        if ($i < 2 && !in_array($part1, $word_list)) continue;
        if (!in_array($part1, $word_list)) continue;
        
        for ($j = $i + 1; $j <= $len - 2; $j++) {
            $part2 = substr($word, $i, $j - $i);
            $part3 = substr($word, $j);
            
            if (in_array($part2, $word_list) && in_array($part3, $word_list)) {
                return [$part1, $part2, $part3];
            }
        }
    }
    
    // Try splitting into 4 words (allow 1-char words like "a", "i")
    for ($i = 1; $i <= $len - 4; $i++) {
        $part1 = substr($word, 0, $i);
        if ($i < 2 && !in_array($part1, $word_list)) continue;
        if (!in_array($part1, $word_list)) continue;
        
        for ($j = $i + 1; $j <= $len - 3; $j++) {
            $part2 = substr($word, $i, $j - $i);
            if (!in_array($part2, $word_list)) continue;
            
            for ($k = $j + 1; $k <= $len - 2; $k++) {
                $part3 = substr($word, $j, $k - $j);
                $part4 = substr($word, $k);
                
                if (in_array($part3, $word_list) && in_array($part4, $word_list)) {
                    return [$part1, $part2, $part3, $part4];
                }
            }
        }
    }
    
    return false;
}

/**
 * Test the function with some examples
 */
function testDetectConcatenatedWords() {
    global $common_words;
    
    $test_words = [
        'firesof' => ['fires', 'of'],
        'changeand' => ['change', 'and'],
        'wateredwith' => ['watered', 'with'],
        'gatheredthe' => ['gathered', 'the'],
        'eldersin' => ['elders', 'in'],
        'convocationand' => ['convocation', 'and'],
        'proposedan' => ['proposed', 'an'],
        'onasuccessful' => ['on', 'a', 'successful'],
        'takeanextra' => ['take', 'an', 'extra'],
        'duringachallenge' => ['during', 'a', 'challenge'],
        'vampire' => false, // Should not split (valid single word)
        'discipline' => false, // Should not split (valid single word)
        'government' => false, // Should not split (valid single word)
    ];
    
    echo "Testing detectConcatenatedWords function:\n\n";
    
    foreach ($test_words as $word => $expected) {
        $result = detectConcatenatedWords($word, $common_words);
        $status = ($result === $expected || (is_array($result) && is_array($expected) && $result == $expected)) ? '✓' : '✗';
        $result_str = is_array($result) ? implode(' + ', $result) : 'false';
        $expected_str = is_array($expected) ? implode(' + ', $expected) : 'false';
        echo "{$status} '{$word}': {$result_str} (expected: {$expected_str})\n";
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    testDetectConcatenatedWords();
}
