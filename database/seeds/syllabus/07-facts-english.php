<?php
declare(strict_types=1);

/** English Language (JAMB "Lexis and structure"): vocabulary, antonyms, plurals, one-word substitution. Original lists. */
require_once __DIR__ . '/_helpers.php';

$syn = [['abandon', 'desert'], ['abolish', 'end'], ['accurate', 'correct'], ['adequate', 'enough'], ['ambiguous', 'unclear'], ['ample', 'plenty'], ['arrogant', 'proud'], ['assist', 'help'],
    ['barren', 'infertile'], ['benevolent', 'kind'], ['blunt', 'dull'], ['bravery', 'courage'], ['brittle', 'fragile'], ['cautious', 'careful'], ['conceal', 'hide'], ['conquer', 'defeat'],
    ['courteous', 'polite'], ['crucial', 'vital'], ['deceive', 'mislead'], ['delicate', 'dainty'], ['depart', 'leave'], ['diminish', 'reduce'], ['eager', 'keen'], ['enormous', 'huge'],
    ['essential', 'necessary'], ['exhaust', 'drain'], ['fatal', 'deadly'], ['feeble', 'weak'], ['fierce', 'ferocious'], ['frugal', 'thrifty'], ['furious', 'angry'], ['gloomy', 'dismal'],
    ['hasty', 'hurried'], ['hostile', 'unfriendly'], ['humble', 'modest'], ['idle', 'lazy'], ['immense', 'vast'], ['inquire', 'ask'], ['jovial', 'cheerful'], ['lethargic', 'sluggish'],
    ['lucid', 'clear'], ['mandatory', 'compulsory'], ['meagre', 'scanty'], ['merciful', 'lenient'], ['obstinate', 'stubborn'], ['opponent', 'rival'], ['peril', 'danger'], ['prudent', 'wise'],
    ['rebuke', 'scold'], ['reluctant', 'unwilling'], ['remote', 'distant'], ['reside', 'live'], ['scarce', 'rare'], ['sever', 'cut'], ['shrewd', 'clever'], ['sincere', 'genuine'],
    ['tranquil', 'calm'], ['vacant', 'empty'], ['vigorous', 'energetic'], ['weary', 'tired']];
$ant = [['accept', 'reject'], ['admit', 'deny'], ['advance', 'retreat'], ['ascend', 'descend'], ['arrive', 'depart'], ['beautiful', 'ugly'], ['bright', 'dull'], ['cheap', 'expensive'],
    ['clean', 'dirty'], ['common', 'rare'], ['compulsory', 'optional'], ['create', 'destroy'], ['crooked', 'straight'], ['dangerous', 'safe'], ['deep', 'shallow'], ['difficult', 'easy'],
    ['diligent', 'lazy'], ['early', 'late'], ['empty', 'full'], ['fast', 'slow'], ['fertile', 'barren'], ['generous', 'stingy'], ['heavy', 'light'], ['hostile', 'friendly'], ['humble', 'proud'],
    ['inferior', 'superior'], ['major', 'minor'], ['mature', 'immature'], ['noisy', 'quiet'], ['permanent', 'temporary'], ['polite', 'rude'], ['poor', 'wealthy'], ['present', 'absent'],
    ['public', 'private'], ['rough', 'smooth'], ['sharp', 'blunt'], ['strong', 'weak'], ['tight', 'loose'], ['victory', 'defeat'], ['wide', 'narrow'], ['young', 'old'], ['zenith', 'nadir']];
$plural = [['child', 'children'], ['man', 'men'], ['woman', 'women'], ['foot', 'feet'], ['tooth', 'teeth'], ['mouse', 'mice'], ['goose', 'geese'], ['ox', 'oxen'], ['person', 'people'],
    ['criterion', 'criteria'], ['phenomenon', 'phenomena'], ['bacterium', 'bacteria'], ['datum', 'data'], ['crisis', 'crises'], ['analysis', 'analyses'], ['thesis', 'theses'],
    ['nucleus', 'nuclei'], ['radius', 'radii'], ['stimulus', 'stimuli'], ['formula', 'formulae'], ['appendix', 'appendices'], ['leaf', 'leaves'], ['knife', 'knives'], ['wolf', 'wolves'],
    ['life', 'lives'], ['half', 'halves'], ['thief', 'thieves'], ['loaf', 'loaves'], ['wife', 'wives'], ['hero', 'heroes'], ['potato', 'potatoes'], ['tomato', 'tomatoes']];
$oneWord = [['one who studies the stars', 'astronomer'], ['a person who cannot read or write', 'illiterate'], ['a place where bees are kept', 'apiary'], ['a person who walks on foot', 'pedestrian'],
    ['a person who is new to a task', 'novice'], ['a book of words and their meanings', 'dictionary'], ['a person who eats no meat', 'vegetarian'], ['a person who speaks two languages', 'bilingual'],
    ['the killing of a king', 'regicide'], ['one who can use both hands equally well', 'ambidextrous'], ['a speech made without preparation', 'impromptu'], ['an official count of the population', 'census'],
    ['a person who pays rent for a house', 'tenant'], ['the owner of a house let to others', 'landlord'], ['a fear of high places', 'acrophobia'], ['a place where aeroplanes land and take off', 'aerodrome'],
    ['one who makes bread', 'baker'], ['one who treats teeth', 'dentist'], ['a collection of poems', 'anthology'], ['a person who gives speeches in public', 'orator'],
    ['a person who survives a disaster', 'survivor'], ['one who lives in a foreign land by choice', 'expatriate'], ['a government by the people', 'democracy'], ['a person who sells flowers', 'florist'],
    ['a person who writes about a country’s past', 'historian']];

return ['English Language' => array_merge(
    pairq('Synonyms', 'medium', 'Choose the word nearest in meaning to “%s”.', 'Choose the word nearest in meaning to “%s”.', $syn, '“%1$s” and “%2$s” have nearly the same meaning.'),
    pairq('Antonyms', 'medium', 'Choose the word opposite in meaning to “%s”.', 'Choose the word opposite in meaning to “%s”.', $ant, '“%1$s” and “%2$s” are opposites.'),
    pairq('Plurals', 'easy', 'What is the plural of “%s”?', 'What is the singular of “%s”?', $plural, 'The plural of “%1$s” is “%2$s”.'),
    pairq('One-word substitution', 'medium', 'Which one word means “%s”?', 'What does “%s” mean?', array_map(static fn ($p) => [$p[0], $p[1]], $oneWord), '“%2$s” means “%1$s”.'),
)];
