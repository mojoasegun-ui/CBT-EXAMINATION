<?php
declare(strict_types=1);

/**
 * English Language (lexis and structure, oral English) and Biology practice questions, written against the
 * JAMB UTME syllabus topics. Original wording. The correct option's position is varied by the mc() helper.
 */
require_once __DIR__ . '/_helpers.php';

$eng = [];
$bio = [];
$n = 200;
$e = static function (string $t, string $d, string $b, string $ok, array $no, string $x) use (&$eng, &$n): void { $eng[] = mc($t, $d, $b, $ok, $no, $x, $n++); };
$b = static function (string $t, string $d, string $q, string $ok, array $no, string $x) use (&$bio, &$n): void { $bio[] = mc($t, $d, $q, $ok, $no, $x, $n++); };

// ---------------------------------------- English Language ----------------------------------------
$e('Concord', 'easy', 'Each of the boys ___ a textbook.', 'has', ['have', 'are', 'were'], '“Each” is singular, so it takes the singular verb “has”.');
$e('Concord', 'easy', 'The news from the village ___ encouraging.', 'is', ['are', 'were', 'have been'], '“News” is an uncountable noun and takes a singular verb.');
$e('Concord', 'easy', 'Mathematics ___ one of the compulsory subjects.', 'is', ['are', 'were', 'have'], 'Subject names ending in -s (mathematics, physics) are singular.');
$e('Concord', 'medium', 'One of the girls who ___ late was punished.', 'came', ['come', 'were coming', 'have came'], 'Past tense “came” agrees with the past punishment described.');
$e('Tenses', 'medium', 'By the time we reached the hall, the programme ___.', 'had started', ['has started', 'starts', 'was start'], 'The past perfect shows an earlier past action completed before another.');
$e('Tenses', 'easy', 'Chidi ___ in Suleja since 2019.', 'has lived', ['lives', 'lived', 'is living'], '“Since” with a point in time takes the present perfect.');
$e('Prepositions', 'easy', 'The examination is scheduled ___ Monday.', 'on', ['in', 'at', 'by'], 'Days of the week take “on”.');
$e('Prepositions', 'medium', 'The principal accused the boy ___ stealing the money.', 'of', ['for', 'with', 'on'], 'The verb “accuse” is followed by “of”.');
$e('Prepositions', 'easy', 'Amina is very fond ___ jollof rice.', 'of', ['on', 'at', 'with'], 'The adjective “fond” takes the preposition “of”.');
$e('Articles', 'easy', 'He is ___ honest man.', 'an', ['a', 'the', 'no article'], '“Honest” starts with a vowel sound (the h is silent), so “an” is used.');
$e('Articles', 'easy', '___ moon goes round the earth.', 'The', ['A', 'An', 'No article'], 'A unique object takes the definite article “the”.');
$e('Antonyms', 'easy', 'Choose the word opposite in meaning to “generous”.', 'stingy', ['kind', 'wealthy', 'polite'], 'Generous means giving freely; stingy means unwilling to give.');
$e('Antonyms', 'easy', 'Choose the word opposite in meaning to “ancient”.', 'modern', ['old', 'historic', 'rare'], 'Ancient means very old; modern means of the present time.');
$e('Synonyms', 'medium', 'Choose the word nearest in meaning to “abundant”.', 'plentiful', ['scarce', 'costly', 'tiny'], 'Abundant means existing in large quantities.');
$e('Synonyms', 'medium', 'Choose the word nearest in meaning to “diligent”.', 'hardworking', ['lazy', 'clever', 'careless'], 'A diligent person works carefully and steadily.');
$e('Synonyms', 'hard', 'The word “unanimous” means', 'in complete agreement', ['divided', 'secret', 'noisy'], 'A unanimous decision is supported by everyone.');
$e('Reported speech', 'medium', 'He said, “I am tired.” Reported: He said that he ___ tired.', 'was', ['is', 'will be', 'has been'], 'Present tense in direct speech moves back to the past in reported speech.');
$e('Reported speech', 'medium', 'The teacher said to me, “Close the door.” Reported: The teacher told me ___.', 'to close the door', ['close the door', 'that close the door', 'closing the door'], 'Commands are reported with “told + object + to-infinitive”.');
$e('Conditionals', 'medium', 'If it rains tomorrow, we ___ the match.', 'will cancel', ['would cancelled', 'cancelling', 'have cancel'], 'A real future condition takes “will” in the main clause.');
$e('Conditionals', 'hard', 'If I had known the answer, I ___ you.', 'would have told', ['will tell', 'would tell', 'told'], 'The third conditional: if + past perfect, would have + past participle.');
$e('Question tags', 'easy', 'Musa can swim, ___?', 'can’t he', ['can he', 'isn’t he', 'doesn’t he'], 'A positive statement takes a negative tag with the same auxiliary.');
$e('Figures of speech', 'easy', '“He is as brave as a lion” is an example of', 'simile', ['metaphor', 'irony', 'personification'], 'A simile compares two things using “as” or “like”.');
$e('Figures of speech', 'easy', '“Time is a thief” is an example of', 'metaphor', ['simile', 'hyperbole', 'alliteration'], 'A metaphor states that one thing is another, without “as” or “like”.');
$e('Figures of speech', 'medium', '“I have told you a thousand times” is an example of', 'hyperbole', ['irony', 'simile', 'oxymoron'], 'Hyperbole is deliberate exaggeration for emphasis.');
$e('Figures of speech', 'medium', '“Sally sells sea shells by the sea shore” is an example of', 'alliteration', ['assonance', 'onomatopoeia', 'paradox'], 'Alliteration is the repetition of the same initial consonant sound.');
$e('Idioms', 'medium', '“To bury the hatchet” means to', 'make peace', ['start a fight', 'hide something', 'dig a grave'], 'The idiom means to end a quarrel and become friends again.');
$e('Idioms', 'easy', '“To kick the bucket” means to', 'die', ['fail an exam', 'play football', 'be angry'], 'It is an informal idiom for dying.');
$e('Idioms', 'medium', '“A blessing in disguise” means something', 'good that first seemed bad', ['bad that first seemed good', 'very expensive', 'hidden from view'], 'It describes an apparent misfortune that turns out well.');
$e('Oral English', 'medium', 'Which word contains the vowel sound /iː/ as in “tree”?', 'seat', ['sit', 'set', 'sat'], '“Seat” has the long /iː/ sound; the others have short vowels.');
$e('Oral English', 'medium', 'Choose the word that rhymes with “light”.', 'fight', ['lit', 'late', 'lot'], '“Light” and “fight” both end in /aɪt/.');
$e('Oral English', 'medium', 'Which word begins with the consonant sound /ʃ/?', 'ship', ['chair', 'sit', 'think'], '“Sh” in “ship” represents /ʃ/.');
$e('Oral English', 'hard', 'Which word is stressed on the first syllable?', 'teacher', ['reply', 'police', 'deny'], 'TEA-cher is stressed on the first syllable; the others on the second.');
$e('Spelling', 'easy', 'Choose the correctly spelt word.', 'receive', ['recieve', 'receeve', 'recive'], 'The rule: “i before e, except after c”.');
$e('Spelling', 'medium', 'Choose the correctly spelt word.', 'necessary', ['neccessary', 'necesary', 'nessessary'], '“Necessary” has one c and two s.');
$e('Spelling', 'medium', 'Choose the correctly spelt word.', 'separate', ['seperate', 'separete', 'seprate'], 'Remember “a rat” in sep-a-rate.');
$e('Punctuation', 'easy', 'Which sentence is correctly punctuated?', 'It’s a lovely day.', ['Its a lovely day.', 'Its’ a lovely day.', 'It’s’ a lovely day.'], '“It’s” is the short form of “it is”; the apostrophe replaces the missing letter.');
$e('Parts of speech', 'easy', 'In “She sings beautifully”, the word “beautifully” is a/an', 'adverb', ['adjective', 'noun', 'verb'], 'It tells how she sings, so it modifies the verb: an adverb.');
$e('Pronouns', 'easy', 'The book on the table is ___.', 'mine', ['me', 'my', 'I'], '“Mine” is the possessive pronoun that stands alone.');
$e('Plurals', 'easy', 'What is the plural of “child”?', 'children', ['childs', 'childrens', 'childes'], '“Child” has the irregular plural “children”.');
$e('Plurals', 'medium', 'What is the plural of “phenomenon”?', 'phenomena', ['phenomenons', 'phenomenas', 'phenomenae'], 'Words of Greek origin ending in -on often form the plural with -a.');
$e('Sentence interpretation', 'medium', '“He is no longer a child” means he', 'has grown up', ['is a baby', 'dislikes children', 'is very short'], 'The sentence says childhood is over.');

// ---------------------------------------- Biology ----------------------------------------
$b('Cell biology', 'easy', 'The part of the cell where most energy (ATP) is released is the', 'mitochondrion', ['nucleus', 'ribosome', 'vacuole'], 'Aerobic respiration occurs mainly in mitochondria.');
$b('Cell biology', 'easy', 'Which organelle controls the activities of the cell?', 'nucleus', ['Golgi body', 'lysosome', 'vacuole'], 'The nucleus contains the chromosomes that direct cell activity.');
$b('Cell biology', 'easy', 'Photosynthesis takes place in the', 'chloroplast', ['mitochondrion', 'ribosome', 'nucleus'], 'Chloroplasts contain chlorophyll, which traps light energy.');
$b('Cell biology', 'medium', 'Protein synthesis takes place on the', 'ribosomes', ['lysosomes', 'vacuoles', 'centrioles'], 'Ribosomes assemble amino acids into proteins.');
$b('Cell biology', 'medium', 'The cell wall of a plant cell is made mainly of', 'cellulose', ['chitin', 'starch', 'protein'], 'Cellulose gives the plant cell wall its strength.');
$b('Cell biology', 'medium', 'Osmosis is the movement of water molecules through a semi-permeable membrane from a region of', 'high water potential to low water potential', ['low water potential to high water potential', 'high solute to low solute concentration', 'low pressure to high pressure'], 'Water moves down its water-potential gradient.');
$b('Cell biology', 'medium', 'Diffusion differs from active transport because diffusion', 'does not need energy from the cell', ['uses ATP', 'moves substances against a concentration gradient', 'requires carrier proteins only'], 'Diffusion is passive; active transport needs energy.');
$b('Nutrition', 'easy', 'The enzyme in saliva that digests starch is', 'salivary amylase', ['pepsin', 'lipase', 'trypsin'], 'Amylase begins starch digestion in the mouth.');
$b('Nutrition', 'easy', 'Protein digestion begins in the', 'stomach', ['mouth', 'duodenum', 'ileum'], 'Pepsin in the stomach starts breaking proteins down.');
$b('Nutrition', 'easy', 'Scurvy is caused by a lack of', 'vitamin C', ['vitamin A', 'vitamin D', 'vitamin K'], 'Vitamin C is found in citrus fruits.');
$b('Nutrition', 'easy', 'Rickets is caused by a lack of', 'vitamin D', ['vitamin C', 'iodine', 'iron'], 'Vitamin D helps the body absorb calcium for bones.');
$b('Nutrition', 'easy', 'Kwashiorkor is caused by a deficiency of', 'protein', ['starch', 'fat', 'vitamin A'], 'It is a protein-energy malnutrition disease in children.');
$b('Nutrition', 'medium', 'Most absorption of digested food takes place in the', 'ileum', ['stomach', 'colon', 'oesophagus'], 'The ileum has villi that give a large absorbing surface.');
$b('Respiration', 'medium', 'The products of aerobic respiration are', 'carbon dioxide and water', ['lactic acid and energy', 'ethanol and carbon dioxide', 'glucose and oxygen'], 'Glucose + oxygen → carbon dioxide + water + energy.');
$b('Respiration', 'medium', 'Anaerobic respiration in yeast produces', 'ethanol and carbon dioxide', ['lactic acid', 'water and carbon dioxide', 'glucose and oxygen'], 'This is alcoholic fermentation.');
$b('Transport', 'easy', 'Which blood cells carry oxygen?', 'red blood cells', ['white blood cells', 'platelets', 'plasma'], 'Haemoglobin in red cells binds oxygen.');
$b('Transport', 'easy', 'A blood vessel that carries blood away from the heart is called a/an', 'artery', ['vein', 'capillary', 'venule'], 'Arteries have thick, elastic walls to withstand pressure.');
$b('Transport', 'medium', 'Which chamber of the heart pumps blood to the whole body?', 'left ventricle', ['right ventricle', 'left atrium', 'right atrium'], 'The left ventricle has the thickest muscular wall.');
$b('Transport', 'medium', 'Blood clotting is helped by', 'platelets', ['red blood cells', 'lymphocytes', 'haemoglobin'], 'Platelets release substances that start the clotting process.');
$b('Excretion', 'easy', 'The functional unit of the kidney is the', 'nephron', ['neurone', 'alveolus', 'villus'], 'Each nephron filters blood and forms urine.');
$b('Excretion', 'medium', 'Urea is formed in the', 'liver', ['kidney', 'bladder', 'pancreas'], 'The liver deaminates amino acids and makes urea.');
$b('Coordination', 'medium', 'The part of the brain that controls balance and posture is the', 'cerebellum', ['cerebrum', 'medulla oblongata', 'hypothalamus'], 'The cerebellum coordinates movement and balance.');
$b('Coordination', 'easy', 'A reflex action is', 'an automatic response to a stimulus', ['a learned response', 'a slow voluntary action', 'a response controlled by the cerebrum'], 'Reflexes are rapid and do not involve conscious thought.');
$b('Coordination', 'easy', 'The hormone that lowers blood sugar level is', 'insulin', ['glucagon', 'adrenaline', 'thyroxine'], 'Insulin from the pancreas helps cells take up glucose.');
$b('Coordination', 'medium', 'The gland known as the “master gland” is the', 'pituitary', ['thyroid', 'adrenal', 'pancreas'], 'The pituitary controls many other endocrine glands.');
$b('Plants', 'easy', 'Water is absorbed by plants mainly through the', 'root hairs', ['stomata', 'lenticels', 'leaves'], 'Root hairs give a large surface area for absorption.');
$b('Plants', 'easy', 'The tissue that carries water and minerals upward in a plant is the', 'xylem', ['phloem', 'cambium', 'epidermis'], 'Xylem vessels transport water from the roots.');
$b('Plants', 'medium', 'The phloem transports', 'manufactured food (sugars)', ['water only', 'minerals only', 'oxygen'], 'Phloem carries sucrose from leaves to other parts.');
$b('Plants', 'easy', 'The raw materials for photosynthesis are', 'carbon dioxide and water', ['oxygen and glucose', 'nitrogen and water', 'glucose and carbon dioxide'], 'Light energy converts carbon dioxide and water into glucose and oxygen.');
$b('Plants', 'medium', 'Water vapour is lost from plant leaves mainly through the', 'stomata', ['roots', 'bark', 'flowers'], 'This loss of water vapour is called transpiration.');
$b('Reproduction', 'easy', 'The transfer of pollen grains from the anther to the stigma is', 'pollination', ['fertilisation', 'germination', 'dispersal'], 'Pollination must occur before fertilisation in flowering plants.');
$b('Reproduction', 'easy', 'The fusion of a male gamete with a female gamete is', 'fertilisation', ['pollination', 'germination', 'ovulation'], 'It forms a zygote.');
$b('Genetics', 'easy', 'Genes are located on the', 'chromosomes', ['ribosomes', 'mitochondria', 'cell wall'], 'Chromosomes are made of DNA, which carries genes.');
$b('Genetics', 'medium', 'A heterozygous individual has', 'two different alleles for a trait', ['two identical alleles for a trait', 'no alleles for the trait', 'only one chromosome'], 'For example Tt rather than TT or tt.');
$b('Genetics', 'easy', 'The sex chromosomes of a human male are', 'XY', ['XX', 'YY', 'XO'], 'Females are XX; males are XY.');
$b('Genetics', 'easy', 'Gregor Mendel carried out his experiments on', 'pea plants', ['maize', 'fruit flies', 'mice'], 'Mendel’s pea experiments established the basic laws of inheritance.');
$b('Evolution', 'easy', 'The theory of evolution by natural selection was proposed by', 'Charles Darwin', ['Gregor Mendel', 'Louis Pasteur', 'Robert Hooke'], 'Darwin published “On the Origin of Species” in 1859.');
$b('Ecology', 'medium', 'An organism that feeds on dead organic matter is a', 'saprophyte', ['parasite', 'producer', 'herbivore'], 'Saprophytes such as fungi break down dead material.');
$b('Ecology', 'medium', 'A relationship in which both organisms benefit is called', 'mutualism', ['parasitism', 'commensalism', 'predation'], 'An example is nitrogen-fixing bacteria in legume root nodules.');
$b('Ecology', 'easy', 'In a food chain, green plants are the', 'producers', ['primary consumers', 'decomposers', 'secondary consumers'], 'Plants make their own food by photosynthesis.');
$b('Ecology', 'easy', 'All the organisms of one species living in a particular area form a', 'population', ['community', 'ecosystem', 'biome'], 'A community is made of several populations.');
$b('Ecology', 'medium', 'Which gas is mainly responsible for the greenhouse effect?', 'carbon dioxide', ['oxygen', 'nitrogen', 'argon'], 'Carbon dioxide traps heat in the atmosphere.');
$b('Health and disease', 'easy', 'Malaria is transmitted by the female', 'Anopheles mosquito', ['Aedes mosquito', 'tsetse fly', 'housefly'], 'The Plasmodium parasite is carried by the female Anopheles mosquito.');
$b('Health and disease', 'medium', 'Cholera is caused by a', 'bacterium', ['virus', 'protozoan', 'fungus'], 'Vibrio cholerae spreads through contaminated water.');

return ['English Language' => $eng, 'Biology' => $bio];
