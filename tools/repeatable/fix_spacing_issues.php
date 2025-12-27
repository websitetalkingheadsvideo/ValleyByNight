<?php
/**
 * Fix spacing issues in markdown files
 * - Adds spaces after punctuation (; : , .)
 * - Adds spaces before common words after punctuation
 * - Fixes specific concatenated phrases
 * - Detects and fixes concatenated words (2-4 words smashed together)
 * - Removes excessive spaces
 * - Preserves markdown formatting
 */

$books_dir = __DIR__ . '/../../reference/Books_md_ready';

if (!is_dir($books_dir)) {
    die("Directory not found: {$books_dir}\n");
}

// Load word detection function
require_once __DIR__ . '/detect_concatenated_words.php';

function fixSpacing(string $text): string {
    $lines = explode("\n", $text);
    $fixed_lines = [];
    
    foreach ($lines as $line) {
        // Skip markdown headers (lines starting with #)
        if (preg_match('/^#+\s/', $line)) {
            $fixed_lines[] = $line;
            continue;
        }
        
        // Skip empty lines
        if (trim($line) === '') {
            $fixed_lines[] = $line;
            continue;
        }
        
        $fixed = $line;
        
        // 1. Add space after punctuation if missing (but preserve existing spaces)
        // Only add space if there's a letter immediately after punctuation
        $fixed = preg_replace('/([;:,.])([a-zA-Z])/', '$1 $2', $fixed);
        
        // 2. Fix specific common concatenated phrases (be very specific)
        $specific_fixes = [
            // Common game phrases
            '/onasuccessfulattack/i' => 'on a successful attack',
            '/onasuccessful/i' => 'on a successful',
            '/takeanextraaction/i' => 'take an extra action',
            '/takeanextra/i' => 'take an extra',
            '/duringachallenge/i' => 'during a challenge',
            '/makeafollow-upattack/i' => 'make a follow-up attack',
            '/makeafollow-up/i' => 'make a follow-up',
            '/healoneHealth/i' => 'heal one Health',
            '/healone/i' => 'heal one',
            '/afterbecoming/i' => 'after becoming',
            '/powercertain/i' => 'power certain',
            '/aslisted/i' => 'as listed',
            '/givesthem/i' => 'gives them',
            '/the minto/i' => 'them into',
            '/the m /i' => 'them ',
            '/for m /i' => 'form ',
            '/for m\b/i' => 'form',
            '/onemonth/i' => 'one month',
            '/onceliving/i' => 'once living',
            '/the ir/i' => 'their',
            '/sufficientto:/i' => 'sufficient to:',
            '/sufficientto/i' => 'sufficient to',
            '/amortal/i' => 'a mortal',
            '/avampire/i' => 'a vampire',
            '/sustainavampire/i' => 'sustain a vampire',
            '/sustain a vampirefor/i' => 'sustain a vampire for',
            '/adda/i' => 'add a',
            '/oncepergame/i' => 'once per game',
            '/onceper/i' => 'once per',
            '/pergame/i' => 'per game',
            '/Attributecategory/i' => 'Attribute category',
            '/woundpenalties/i' => 'wound penalties',
            '/specialabilities/i' => 'special abilities',
            '/Health Levelof/i' => 'Health Level of',
            '/Levelof/i' => 'Level of',
            '/Corpus Levelof/i' => 'Corpus Level of',
            '/anotherform/i' => 'another form',
            '/another for m/i' => 'another form',
            '/anotherformimmediately/i' => 'another form immediately',
            '/Giftsaslisted/i' => 'Gifts as listed',
            '/Giftsaslistedin/i' => 'Gifts as listed in',
            '/powercertain/i' => 'power certain',
            '/powercertain Gifts/i' => 'power certain Gifts',
            '/enforcingone\'s/i' => 'enforcing one\'s',
            '/enforcingone/i' => 'enforcing one',
            // Additional common patterns
            '/throughthememory/i' => 'through the memory',
            '/holdonto/i' => 'hold onto',
            '/materialworld/i' => 'material world',
            '/powerin/i' => 'power in',
            '/canonly/i' => 'can only',
            '/wr a iths/i' => 'wraiths',
            '/wr a ithscanonly/i' => 'wraiths can only',
            '/wraithscanonly/i' => 'wraiths can only',
            '/wraithscan only/i' => 'wraiths can only',
            '/actionduring/i' => 'action during',
            '/afollow-upattack/i' => 'a follow-up attack',
            '/achallenge/i' => 'a challenge',
            '/ona successful/i' => 'on a successful',
            '/Giftsas listed/i' => 'Gifts as listed',
            '/incredibledestructive/i' => 'incredible destructive',
            // Historical/narrative patterns
            '/firesof/i' => 'fires of',
            '/changeand/i' => 'change and',
            '/wateredwith/i' => 'watered with',
            '/watered wit/i' => 'watered with',
            '/Hardestaadtathered/i' => 'Hardestaadt gathered',
            '/Hardestaadt athered/i' => 'Hardestaadt gathered',
            '/eldersin/i' => 'elders in',
            '/convocationand/i' => 'convocation and',
            '/proposedan/i' => 'proposed an',
            '/proposed an/i' => 'proposed an',
            '/muchspilled/i' => 'much spilled',
            '/spilledvitae/i' => 'spilled vitae',
            '/ofthe/i' => 'of the',
            '/oftheir/i' => 'of their',
            '/andthe/i' => 'and the',
            '/inthe/i' => 'in the',
            '/tothe/i' => 'to the',
            '/fromthe/i' => 'from the',
            '/withthe/i' => 'with the',
            '/bythe/i' => 'by the',
            '/forthe/i' => 'for the',
            '/onthe/i' => 'on the',
            '/overthe/i' => 'over the',
            '/throughthe/i' => 'through the',
            '/betweenthe/i' => 'between the',
            '/againstthe/i' => 'against the',
            '/withoutthe/i' => 'without the',
            '/withinthe/i' => 'within the',
            '/amongthe/i' => 'among the',
            '/acrossthe/i' => 'across the',
            '/duringthe/i' => 'during the',
            '/throughthe/i' => 'through the',
            '/thatthe/i' => 'that the',
            '/thisthe/i' => 'this the',
            '/whenthe/i' => 'when the',
            '/wherethe/i' => 'where the',
            '/whilethe/i' => 'while the',
            '/sincethe/i' => 'since the',
            '/untilthe/i' => 'until the',
            '/afterthe/i' => 'after the',
            '/beforethe/i' => 'before the',
            '/duringthe/i' => 'during the',
            '/andtheir/i' => 'and their',
            '/oftheir/i' => 'of their',
            '/withtheir/i' => 'with their',
            '/bytheir/i' => 'by their',
            '/fortheir/i' => 'for their',
            '/ontheir/i' => 'on their',
            '/overtheir/i' => 'over their',
            '/throughtheir/i' => 'through their',
            '/betweentheir/i' => 'between their',
            '/againsttheir/i' => 'against their',
            '/withouttheir/i' => 'without their',
            '/withintheir/i' => 'within their',
            '/amongtheir/i' => 'among their',
            '/acrosstheir/i' => 'across their',
            '/duringtheir/i' => 'during their',
            '/thattheir/i' => 'that their',
            '/whentheir/i' => 'when their',
            '/wheretheir/i' => 'where their',
            '/whiletheir/i' => 'while their',
            '/sincetheir/i' => 'since their',
            '/untiltheir/i' => 'until their',
            '/aftertheir/i' => 'after their',
            '/beforetheir/i' => 'before their',
            '/duringtheir/i' => 'during their',
            // More specific historical patterns
            '/In1381/i' => 'In 1381',
            '/In1435/i' => 'In 1435',
            '/In1493/i' => 'In 1493',
            '/abandof/i' => 'a band of',
            '/Englisheasants/i' => 'English peasants',
            '/rebelledagainst/i' => 'rebelled against',
            '/locallord/i' => 'local lord',
            '/attentionand/i' => 'attention and',
            '/aidof/i' => 'aid of',
            '/severalyoung/i' => 'several young',
            '/Thoughquickly/i' => 'Though quickly',
            '/putdown/i' => 'put down',
            '/leftitsmark/i' => 'left its mark',
            '/thoseof/i' => 'those of',
            '/whotookpart/i' => 'who took part',
            '/Frustratedin/i' => 'Frustrated in',
            '/risetopower/i' => 'rise to power',
            '/oftensuffocated/i' => 'often suffocated',
            '/undertheir/i' => 'under their',
            '/immortalelders/i' => 'immortal elders',
            '/irongrip/i' => 'iron grip',
            '/childer of Europe/i' => 'childer of Europe',
            '/kindled the/i' => 'kindled the',
            '/beginnings of/i' => 'beginnings of',
            '/own rebellion/i' => 'own rebellion',
            '/Camarilla\'shistory/i' => 'Camarilla\'s history',
            '/somany/i' => 'so many',
            '/partsof/i' => 'parts of',
            '/Kindred\'staleis/i' => 'Kindred\'s tale is',
            '/bloodyone/i' => 'bloody one',
            '/sinceitsearly/i' => 'since its early',
            '/daysasabulwark/i' => 'days as a bulwark',
            '/you thful/i' => 'youthful',
            '/early1400ssaw/i' => 'early 1400s saw',
            '/would igniteawildfire/i' => 'would ignite a wildfire',
            '/igniteawildfire/i' => 'ignite a wildfire',
            '/you ng/i' => 'young',
            '/childerrose/i' => 'childer rose',
            '/siresthroughout/i' => 'sires throughout',
            '/clearing the/i' => 'clearing the',
            '/avenues of/i' => 'avenues of',
            '/powerwithbloodandfire/i' => 'power with blood and fire',
            '/Warraged/i' => 'War raged',
            '/eldestof/i' => 'eldest of',
            '/Atthe/i' => 'At the',
            '/height of/i' => 'height of',
            '/rebelsdestroyed/i' => 'rebels destroyed',
            '/claimed tohave/i' => 'claimed to have',
            '/tohave/i' => 'to have',
            '/Bolsteredbythediablerie/i' => 'Bolstered by the diablerie',
            '/oftheirelders/i' => 'of their elders',
            '/rebelliousyouth/i' => 'rebellious youth',
            '/nowcalled/i' => 'now called',
            '/marched through/i' => 'marched through',
            '/laying was te/i' => 'laying waste',
            '/was te/i' => 'waste',
            '/work of/i' => 'work of',
            '/those lands/i' => 'those lands',
            '/ameans/i' => 'a means',
            '/tobreak/i' => 'to break',
            '/stranglehold of/i' => 'stranglehold of',
            '/blood bond/i' => 'blood bond',
            '/had beenfoundandsudenly/i' => 'had been found and suddenly',
            '/beenfoundandsudenly/i' => 'been found and suddenly',
            '/foundandsudenly/i' => 'found and suddenly',
            '/manyeonates/i' => 'many neonates',
            '/ancillaewere/i' => 'ancillae were',
            '/liping/i' => 'slipping',
            '/leashes elders/i' => 'leashes elders',
            '/had thought/i' => 'had thought',
            '/Eager for/i' => 'Eager for',
            '/opportunity to/i' => 'opportunity to',
            '/diablerize European/i' => 'diablerize European',
            '/joined the/i' => 'joined the',
            '/fighton the/i' => 'fight on the',
            '/anarch side/i' => 'anarch side',
            '/arrangement to/i' => 'arrangement to',
            '/deal with/i' => 'deal with',
            '/anarchmovement/i' => 'anarch movement',
            '/arrangementhe/i' => 'arrangement he',
            '/of fered/i' => 'offered',
            '/would crossblood/i' => 'would cross blood',
            '/crossblood/i' => 'cross blood',
            '/territorial lines/i' => 'territorial lines',
            '/todealwiththeissues/i' => 'to deal with the issues',
            '/dealwiththeissues/i' => 'deal with the issues',
            '/withtheissues/i' => 'with the issues',
            '/Kindredas/i' => 'Kindred as',
            '/awhole/i' => 'a whole',
            '/Truetoform/i' => 'True to form',
            '/mosteldersoffered/i' => 'most elders offered',
            '/littlemorethanskepticism/i' => 'little more than skepticism',
            '/morethanskepticism/i' => 'more than skepticism',
            '/left for/i' => 'left for',
            '/own have ns/i' => 'own havens',
            '/have ns/i' => 'havens',
            '/wait out/i' => 'wait out',
            '/anarch storms/i' => 'anarch storms',
            '/way they/i' => 'way they',
            '/weathered so/i' => 'weathered so',
            '/many trials/i' => 'many trials',
            '/centuries before/i' => 'centuries before',
            '/A few/i' => 'A few',
            '/though, remained/i' => 'though, remained',
            '/joined Hardestaadt/i' => 'joined Hardestaadt',
            '/his vision/i' => 'his vision',
            '/they were/i' => 'they were',
            '/the Founders/i' => 'the Founders',
            '/would lay/i' => 'would lay',
            '/groundworkforthenextfive/i' => 'groundwork for the next five',
            '/forthenextfive/i' => 'for the next five',
            '/nextfive/i' => 'next five',
            '/centuries of/i' => 'centuries of',
            '/Kindred society/i' => 'Kindred society',
            '/By the/i' => 'By the',
            '/middleof/i' => 'middle of',
            '/the15thcentury/i' => 'the 15th century',
            '/15thcentury/i' => '15th century',
            '/Founders had/i' => 'Founders had',
            '/persuaded enough/i' => 'persuaded enough',
            '/elders to/i' => 'elders to',
            '/join their/i' => 'join their',
            '/cause to/i' => 'cause to',
            '/put for th/i' => 'put forth',
            '/for th/i' => 'forth',
            '/significant resistance/i' => 'significant resistance',
            '/anarch rebellion/i' => 'anarch rebellion',
            '/Coteriesdrawnacrossclanlines/i' => 'Coteries drawn across clan lines',
            '/drawnacrossclanlines/i' => 'drawn across clan lines',
            '/acrossclanlines/i' => 'across clan lines',
            '/clanlines/i' => 'clan lines',
            '/boundbyasinglepupose/i' => 'bound by a single purpose',
            '/byasinglepupose/i' => 'by a single purpose',
            '/singlepupose/i' => 'single purpose',
            '/gathered across/i' => 'gathered across',
            '/knownworld/i' => 'known world',
            '/with their/i' => 'with their',
            '/aimsfinallyunited/i' => 'aims finally united',
            '/finallyunited/i' => 'finally united',
            '/heeldersofurope/i' => 'the elders of Europe',
            '/eldersofurope/i' => 'elders of Europe',
            '/ofurope/i' => 'of Europe',
            '/began toregain/i' => 'began to regain',
            '/toregain/i' => 'to regain',
            '/ground on/i' => 'ground on',
            '/their fractious/i' => 'their fractious',
            '/When coterieshand-picked/i' => 'When coteries hand-picked',
            '/coterieshand-picked/i' => 'coteries hand-picked',
            '/hand-picked bythe/i' => 'hand-picked by the',
            '/bythe/i' => 'by the',
            '/Founders and/i' => 'Founders and',
            '/theirintimatesfinallyreturned/i' => 'their intimates finally returned',
            '/intimatesfinallyreturned/i' => 'intimates finally returned',
            '/finallyreturned/i' => 'finally returned',
            '/returnedwith/i' => 'returned with',
            '/location of/i' => 'location of',
            '/hidden Assamite/i' => 'hidden Assamite',
            '/for tress/i' => 'fortress',
            '/of Alamut/i' => 'of Alamut',
            '/demise of/i' => 'demise of',
            '/revolt was/i' => 'revolt was',
            '/all but/i' => 'all but',
            '/assured. the/i' => 'assured. The',
            '/war ground/i' => 'war ground',
            '/stalemate of/i' => 'stalemate of',
            '/minor skirmishing/i' => 'minor skirmishing',
            '/Motivated by/i' => 'Motivated by',
            '/Inquisition, which/i' => 'Inquisition, which',
            '/raged across/i' => 'raged across',
            '/Europe ina/i' => 'Europe in a',
            '/ina/i' => 'in a',
            '/fiery backdroptothe/i' => 'fiery backdrop to the',
            '/backdroptothe/i' => 'backdrop to the',
            '/Anarch Revolt/i' => 'Anarch Revolt',
            '/newsect/i' => 'new sect',
            '/deemed the/i' => 'deemed the',
            '/long-ignored Masquerade/i' => 'long-ignored Masquerade',
            '/would become/i' => 'would become',
            '/centerpiece of/i' => 'centerpiece of',
            '/their or der/i' => 'their order',
            '/or der/i' => 'order',
            '/Nomore/i' => 'No more',
            '/would those/i' => 'would those',
            '/Bloodvisiblylordtheirowerovermortals/i' => 'Blood visibly lord their power over mortals',
            '/visiblylordtheirowerovermortals/i' => 'visibly lord their power over mortals',
            '/lordtheirowerovermortals/i' => 'lord their power over mortals',
            '/theirowerovermortals/i' => 'their power over mortals',
            '/owerovermortals/i' => 'power over mortals',
            '/overmortals/i' => 'over mortals',
            '/nsteadhe/i' => 'instead the',
            '/Kindredwouldactfom/i' => 'Kindred would act from',
            '/wouldactfom/i' => 'would act from',
            '/actfom/i' => 'act from',
            '/the shadows/i' => 'the shadows',
            '/enforcing the/i' => 'enforcing the',
            '/Traditions and/i' => 'Traditions and',
            '/protecting the/i' => 'protecting the',
            '/mselvesfrom/i' => 'themselves from',
            '/fires of/i' => 'fires of',
            '/mortal wrath/i' => 'mortal wrath',
            '/with a/i' => 'with a',
            '/charade that/i' => 'charade that',
            '/would come/i' => 'would come',
            '/to span/i' => 'to span',
            '/the globe/i' => 'the globe',
            '/Anarch Movementagreed/i' => 'Anarch Movement agreed',
            '/Movementagreed/i' => 'Movement agreed',
            '/toparley/i' => 'to parley',
            '/with the/i' => 'with the',
            '/Conventionof/i' => 'Convention of',
            '/Thornsconvenedinanabbey/i' => 'Thorns convened in an abbey',
            '/convenedinanabbey/i' => 'convened in an abbey',
            '/inanabbey/i' => 'in an abbey',
            '/in England/i' => 'in England',
            '/and the re/i' => 'and there',
            '/the re/i' => 'there',
            '/anarchsaccepted/i' => 'anarchs accepted',
            '/termsforsurrender/i' => 'terms for surrender',
            '/forsurrender/i' => 'for surrender',
            '/the treatyallowed/i' => 'the treaty allowed',
            '/treatyallowed/i' => 'treaty allowed',
            '/thoseanarchswhowished/i' => 'those anarchs who wished',
            '/anarchswhowished/i' => 'anarchs who wished',
            '/whowished/i' => 'who wished',
            '/tocomeintothefold/i' => 'to come into the fold',
            '/comeintothefold/i' => 'come into the fold',
            '/intothefold/i' => 'into the fold',
            '/of the/i' => 'of the',
            '/Camarilla todoso/i' => 'Camarilla to do so',
            '/todoso/i' => 'to do so',
            '/and leviedpunishment/i' => 'and levied punishment',
            '/leviedpunishment/i' => 'levied punishment',
            '/againstthe/i' => 'against the',
            '/Assamitesfortheirrole/i' => 'Assamites for their role',
            '/fortheirrole/i' => 'for their role',
            '/In this/i' => 'In this',
            '/treaty, the/i' => 'treaty, the',
            '/Camarillacameintoitsown/i' => 'Camarilla came into its own',
            '/cameintoitsown/i' => 'came into its own',
            '/intoitsown/i' => 'into its own',
            '/itsown/i' => 'its own',
            '/astheguidingsectof/i' => 'as the guiding sect of',
            '/theguidingsectof/i' => 'the guiding sect of',
            '/guidingsectof/i' => 'guiding sect of',
            '/Cainitelife/i' => 'Cainite life',
            '/Not all/i' => 'Not all',
            '/anarchs accepted/i' => 'anarchs accepted',
            '/Convention of/i' => 'Convention of',
            '/Many refused/i' => 'Many refused',
            '/to returntothesamestiflingorder/i' => 'to return to the same stifling order',
            '/returntothesamestiflingorder/i' => 'return to the same stifling order',
            '/tothesamestiflingorder/i' => 'to the same stifling order',
            '/thesamestiflingorder/i' => 'the same stifling order',
            '/samestiflingorder/i' => 'same stifling order',
            '/thathadcaused/i' => 'that had caused',
            '/hadcaused/i' => 'had caused',
            '/the mtorebel/i' => 'them to rebel',
            '/mtorebel/i' => 'm to rebel',
            '/inthefirstplace/i' => 'in the first place',
            '/firstplace/i' => 'first place',
            '/the yrejected/i' => 'they rejected',
            '/yrejected/i' => 'y rejected',
            '/the purportedpeace/i' => 'the purported peace',
            '/purportedpeace/i' => 'purported peace',
            '/and fled/i' => 'and fled',
            '/to Scandinavia/i' => 'to Scandinavia',
            '/tonurse/i' => 'to nurse',
            '/their wounds/i' => 'their wounds',
            '/and grudges/i' => 'and grudges',
            '/When they/i' => 'When they',
            '/finallyre-emerged/i' => 'finally re-emerged',
            '/re-emerged from/i' => 're-emerged from',
            '/their self-imposed/i' => 'their self-imposed',
            '/exile, the/i' => 'exile, the',
            '/yhadreformedintothesect/i' => 'y had reformed into the sect',
            '/hadreformedintothesect/i' => 'had reformed into the sect',
            '/reformedintothesect/i' => 'reformed into the sect',
            '/intothesect/i' => 'into the sect',
            '/thatwouldbethe/i' => 'that would be the',
            '/wouldbethe/i' => 'would be the',
            '/Camarilla\'sstaunchest/i' => 'Camarilla\'s staunchest',
            '/staunchest and/i' => 'staunchest and',
            '/most bloody/i' => 'most bloody',
            '/opposition: the/i' => 'opposition: the',
            // Fix remaining common patterns
            '/peasantsrebelled/i' => 'peasants rebelled',
            '/theirlocal/i' => 'their local',
            '/quicklyput/i' => 'quickly put',
            '/mortalrebellionleft/i' => 'mortal rebellion left',
            '/markonthose/i' => 'mark on those',
            '/Bloodwho/i' => 'Blood who',
            '/theirrise/i' => 'their rise',
            '/powerandoften/i' => 'power and often',
            '/suffocatedunder/i' => 'suffocated under',
            '/theirimmortal/i' => 'their immortal',
            '/change andwatered/i' => 'change and watered',
            '/andwatered/i' => 'and watered',
            '/withh /i' => 'with ',
            '/withh/i' => 'with',
            '/secthasgrown/i' => 'sect has grown',
            '/hasgrown/i' => 'has grown',
            '/bulwarkagainst/i' => 'bulwark against',
            '/Brujah By/i' => 'Brujah by',
            '/therebels/i' => 'the rebels',
            '/BolsteredBy/i' => 'Bolstered by',
            '/thediablerieof/i' => 'the diablerie of',
            '/theirelders/i' => 'their elders',
            '/therebellious/i' => 'the rebellious',
            '/forthe /i' => 'for the ',
            '/forthe/i' => 'for the',
            '/gatheredtheelders/i' => 'gathered the elders',
            '/inconvocation/i' => 'in convocation',
            '/andproposed/i' => 'and proposed',
            '/todealwith/i' => 'to deal with',
            '/theissues/i' => 'the issues',
            '/offeredlittle/i' => 'offered little',
            '/fortheir /i' => 'for their ',
            '/fortheir/i' => 'for their',
            '/groundworkforthenext/i' => 'groundwork for the next',
            '/linesbound/i' => 'lines bound',
            '/fin ally/i' => 'finally',
            '/therevolt/i' => 'the revolt',
            '/backdropto/i' => 'backdrop to',
            '/mortalsinstead/i' => 'mortals instead',
            '/the themselves/i' => 'themselves',
            '/Thornsconvenedin/i' => 'Thorns convened in',
            '/anabbeyin/i' => 'an abbey in',
            '/allowedthose/i' => 'allowed those',
            '/wishedtocomeinto/i' => 'wished to come into',
            '/thefold/i' => 'the fold',
            '/its ownas/i' => 'its own as',
            '/ownas/i' => 'own as',
            '/returnto/i' => 'return to',
            '/orderthat/i' => 'order that',
            '/rebelin/i' => 'rebel in',
            '/thefirst/i' => 'the first',
            '/Scandin avia/i' => 'Scandinavia',
            '/fin allyre-emerged/i' => 'finally re-emerged',
            '/the yhadreformedinto/i' => 'they had reformed into',
            '/yhadreformedinto/i' => 'y had reformed into',
            '/hadreformedinto/i' => 'had reformed into',
            '/thesectthat/i' => 'the sect that',
            '/sectthat/i' => 'sect that',
            '/aims fin ally/i' => 'aims finally',
            '/aimsfin ally/i' => 'aims finally',
            // Fix remaining minor issues
            '/elders\'iron/i' => 'elders\' iron',
            '/punishmentagainst/i' => 'punishment against',
            '/finallyre-emerged/i' => 'finally re-emerged',
            '/re-emerged/i' => 're-emerged',
        ];
        
        foreach ($specific_fixes as $pattern => $replacement) {
            $fixed = preg_replace($pattern, $replacement, $fixed);
        }
        
        // 3. Fix spacing around colons in common patterns (but be careful)
        // Only fix if there's no space after colon and it's followed by a capital letter
        $fixed = preg_replace('/([A-Za-z]+):([A-Z][a-z])/', '$1: $2', $fixed);
        
        // 4. Remove excessive spaces (3+ spaces -> 1 space, but preserve markdown)
        $fixed = preg_replace('/[ \t]{3,}/', ' ', $fixed);
        
        // 5. Fix double spaces (but not in markdown tables or code)
        $fixed = preg_replace('/(?<!\|)\s{2,}(?!\|)/', ' ', $fixed);
        
        // 6. Ensure space after semicolons and colons when followed by text
        $fixed = preg_replace('/([;:])([a-zA-Z])/', '$1 $2', $fixed);
        
        // 7. Detect and fix concatenated words (words longer than 7 chars that are actually 2-4 words)
        $word_list = getWordList();
        $fixed = preg_replace_callback('/\b([a-zA-Z]{8,})\b/', function($matches) use ($word_list) {
            $word = $matches[1];
            $result = detectConcatenatedWords($word, $word_list);
            if ($result !== false) {
                return implode(' ', $result);
            }
            return $word;
        }, $fixed);
        
        $fixed_lines[] = $fixed;
    }
    
    return implode("\n", $fixed_lines);
}

// Process all .md files (excluding .bak files)
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($books_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$processed = 0;
$errors = [];

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'md' && !str_ends_with($file->getFilename(), '.bak')) {
        $filepath = $file->getPathname();
        
        echo "Processing: " . basename($filepath) . "\n";
        
        try {
            $content = file_get_contents($filepath);
            if ($content === false) {
                $errors[] = "Failed to read: {$filepath}";
                continue;
            }
            
            $fixed_content = fixSpacing($content);
            
            // Only write if content changed
            if ($fixed_content !== $content) {
                if (file_put_contents($filepath, $fixed_content) === false) {
                    $errors[] = "Failed to write: {$filepath}";
                } else {
                    $processed++;
                    echo "  ✓ Fixed\n";
                }
            } else {
                echo "  - No changes\n";
            }
        } catch (Exception $e) {
            $errors[] = "Error processing {$filepath}: " . $e->getMessage();
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Files processed: {$processed}\n";
if (count($errors) > 0) {
    echo "Errors: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
} else {
    echo "No errors.\n";
}
