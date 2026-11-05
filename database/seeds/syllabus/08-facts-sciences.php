<?php
declare(strict_types=1);

/** Biology, Chemistry and Physics recall questions built from fact lists (JAMB syllabus topics). Original wording. */
require_once __DIR__ . '/_helpers.php';

// ================================ Biology ================================
$organelles = [['nucleus', 'control the activities of the cell'], ['mitochondrion', 'release energy during respiration'], ['ribosome', 'make proteins'], ['chloroplast', 'carry out photosynthesis'],
    ['cell membrane', 'control what enters and leaves the cell'], ['cell wall', 'give a plant cell strength and shape'], ['vacuole', 'store water and dissolved substances'],
    ['lysosome', 'digest worn-out cell parts'], ['Golgi body', 'package and secrete substances'], ['cytoplasm', 'hold the organelles where most cell reactions occur']];
$deficiency = [['vitamin A', 'night blindness'], ['thiamine (vitamin B₁)', 'beriberi'], ['vitamin C', 'scurvy'], ['vitamin D', 'rickets'], ['vitamin K', 'poor blood clotting'], ['iron', 'anaemia'],
    ['iodine', 'goitre'], ['protein', 'kwashiorkor']];
$agents = [['malaria', 'Plasmodium'], ['cholera', 'Vibrio cholerae'], ['tuberculosis', 'Mycobacterium tuberculosis'], ['typhoid fever', 'Salmonella typhi'], ['sleeping sickness', 'Trypanosoma'],
    ['ringworm', 'a fungus'], ['polio', 'the poliovirus'], ['AIDS', 'the human immunodeficiency virus'], ['measles', 'the measles virus'], ['bilharzia (schistosomiasis)', 'Schistosoma'],
    ['river blindness', 'Onchocerca volvulus'], ['elephantiasis', 'Wuchereria bancrofti']];
$hormones = [['insulin', 'pancreas'], ['thyroxine', 'thyroid gland'], ['adrenaline', 'adrenal gland'], ['oestrogen', 'ovary'], ['testosterone', 'testis'], ['growth hormone', 'pituitary gland'], ['melatonin', 'pineal gland']];
$organs = [['heart', 'pump blood round the body'], ['lungs', 'exchange gases with the blood'], ['kidney', 'remove urea and excess water from the blood'], ['liver', 'make urea and store glycogen'],
    ['stomach', 'begin the digestion of protein'], ['small intestine', 'absorb digested food'], ['large intestine', 'absorb water from undigested food'], ['skin', 'help regulate body temperature'],
    ['brain', 'coordinate the activities of the body'], ['bladder', 'store urine'], ['gall bladder', 'store bile']];
$plantParts = [['root', 'absorb water and mineral salts'], ['stem', 'support the plant and conduct materials'], ['leaf', 'make food by photosynthesis'], ['flower', 'carry out sexual reproduction'],
    ['xylem', 'transport water and mineral salts upward'], ['phloem', 'transport manufactured food'], ['stomata', 'allow gaseous exchange and loss of water vapour'], ['root cap', 'protect the root tip'],
    ['cambium', 'increase the thickness of the stem'], ['fruit', 'protect seeds and help their dispersal']];
$classes = [['lizard', 'Reptilia'], ['frog', 'Amphibia'], ['tilapia', 'Pisces'], ['fowl', 'Aves'], ['goat', 'Mammalia'], ['housefly', 'Insecta'], ['spider', 'Arachnida'], ['crab', 'Crustacea'],
    ['earthworm', 'Annelida'], ['millipede', 'Myriapoda'], ['snail', 'Mollusca'], ['tapeworm', 'Platyhelminthes']];
$ecology = [['habitat', 'the place where an organism lives'], ['population', 'all the organisms of one species living in an area'], ['community', 'all the populations living together in an area'],
    ['ecosystem', 'a community together with its non-living environment'], ['producer', 'an organism that makes its own food'], ['consumer', 'an organism that feeds on other organisms'],
    ['decomposer', 'an organism that breaks down dead matter'], ['food chain', 'the transfer of food energy from one organism to another'], ['niche', 'the role of an organism in its habitat'],
    ['symbiosis', 'two different organisms living together'], ['parasite', 'an organism that lives on or in another and harms it']];

$bio = array_merge(
    pairq('Cell biology', 'easy', 'What is the function of the %s?', 'Which part of the cell is described as follows: it helps to %s?', $organelles, 'The %1$s helps to %2$s.'),
    pairq('Nutrition', 'medium', 'A deficiency of %s causes', '%s is caused by a deficiency of', $deficiency, 'A lack of %1$s leads to %2$s.'),
    pairq('Health and disease', 'medium', 'The cause of %s is', '%s causes', $agents, '%1$s is caused by %2$s.'),
    pairq('Coordination', 'medium', 'The hormone %s is produced by the', 'Which hormone is produced by the %s?', $hormones, '%1$s comes from the %2$s.'),
    pairq('Human organs', 'easy', 'The main function of the %s is to', 'Which organ is mainly used to %s?', $organs, 'The %1$s’s main job is to %2$s.'),
    pairq('Plants', 'easy', 'The function of the %s in a plant is to', 'Which part of a plant is mainly used to %s?', $plantParts, 'The %1$s helps the plant to %2$s.'),
    pairq('Classification', 'medium', 'To which group (class or phylum) does the %s belong?', 'Which animal belongs to %s?', $classes, 'A %1$s belongs to %2$s.'),
    pairq('Ecology', 'medium', 'The term “%s” means', 'Which term means: %s?', $ecology, '%1$s: %2$s.'),
);

// ================================ Chemistry ================================
$symbols = [['hydrogen', 'H'], ['helium', 'He'], ['lithium', 'Li'], ['carbon', 'C'], ['nitrogen', 'N'], ['oxygen', 'O'], ['fluorine', 'F'], ['neon', 'Ne'], ['sodium', 'Na'], ['magnesium', 'Mg'],
    ['aluminium', 'Al'], ['silicon', 'Si'], ['phosphorus', 'P'], ['sulphur', 'S'], ['chlorine', 'Cl'], ['potassium', 'K'], ['calcium', 'Ca'], ['iron', 'Fe'], ['copper', 'Cu'], ['zinc', 'Zn'],
    ['silver', 'Ag'], ['gold', 'Au'], ['mercury', 'Hg'], ['lead', 'Pb'], ['tin', 'Sn'], ['bromine', 'Br'], ['iodine', 'I'], ['argon', 'Ar'], ['barium', 'Ba'], ['manganese', 'Mn']];
$formulas = [['water', 'H₂O'], ['carbon dioxide', 'CO₂'], ['methane', 'CH₄'], ['ammonia', 'NH₃'], ['sodium chloride', 'NaCl'], ['calcium carbonate', 'CaCO₃'], ['sulphuric acid', 'H₂SO₄'],
    ['hydrochloric acid', 'HCl'], ['sodium hydroxide', 'NaOH'], ['nitric acid', 'HNO₃'], ['glucose', 'C₆H₁₂O₆'], ['ethanol', 'C₂H₅OH'], ['hydrogen peroxide', 'H₂O₂'], ['calcium hydroxide', 'Ca(OH)₂'],
    ['magnesium oxide', 'MgO'], ['copper(II) sulphate', 'CuSO₄']];
$apparatus = [['burette', 'deliver measured volumes of liquid in a titration'], ['pipette', 'measure a fixed volume of liquid accurately'], ['conical flask', 'hold the solution being titrated'],
    ['measuring cylinder', 'measure approximate volumes of liquids'], ['Bunsen burner', 'provide heat'], ['filter funnel', 'separate an insoluble solid from a liquid'], ['condenser', 'cool vapour back to liquid in distillation'],
    ['evaporating dish', 'evaporate a solution to dryness'], ['thermometer', 'measure temperature'], ['balance', 'measure mass']];
$gases = [['hydrogen', 'burns with a squeaky pop'], ['oxygen', 'relights a glowing splint'], ['carbon dioxide', 'turns lime water milky'], ['ammonia', 'turns damp red litmus paper blue'],
    ['chlorine', 'bleaches damp litmus paper'], ['sulphur dioxide', 'turns acidified potassium dichromate(VI) green']];
$groups = [['Group 1 elements', 'alkali metals'], ['Group 2 elements', 'alkaline earth metals'], ['Group 17 elements', 'halogens'], ['Group 18 elements', 'noble gases']];

$chm = array_merge(
    pairq('Atoms and the periodic table', 'easy', 'What is the chemical symbol for %s?', 'Which element has the symbol %s?', $symbols, 'The symbol for %1$s is %2$s.'),
    pairq('Chemical formulae', 'medium', 'The chemical formula of %s is', 'Which substance has the formula %s?', $formulas, 'The formula of %1$s is %2$s.'),
    pairq('Laboratory apparatus', 'easy', 'The %s is used to', 'Which apparatus is used to %s?', $apparatus, 'The %1$s is used to %2$s.'),
    pairq('Tests for gases', 'medium', 'Which description fits %s gas? It', 'Which gas is identified because it %s?', $gases, '%1$s: it %2$s.'),
    pairq('Periodic table', 'easy', '%s in the periodic table are called the', 'The %s belong to', $groups, '%1$s are the %2$s.'),
);

// ================================ Physics ================================
$units = [['force', 'newton'], ['pressure', 'pascal'], ['energy', 'joule'], ['power', 'watt'], ['frequency', 'hertz'], ['electric charge', 'coulomb'], ['potential difference', 'volt'],
    ['electrical resistance', 'ohm'], ['electric current', 'ampere'], ['capacitance', 'farad'], ['mass', 'kilogram'], ['thermodynamic temperature', 'kelvin'], ['length', 'metre'], ['time', 'second']];
$instruments = [['ammeter', 'electric current'], ['voltmeter', 'potential difference'], ['barometer', 'atmospheric pressure'], ['hydrometer', 'the density of liquids'], ['thermometer', 'temperature'],
    ['anemometer', 'wind speed'], ['hygrometer', 'humidity'], ['stopwatch', 'short time intervals'], ['micrometer screw gauge', 'very small thicknesses and diameters'], ['spring balance', 'weight (force)']];
$devices = [['transformer', 'change the size of an alternating voltage'], ['dynamo', 'convert mechanical energy to electrical energy'], ['electric motor', 'convert electrical energy to mechanical energy'],
    ['periscope', 'see over obstacles using plane mirrors'], ['hydraulic press', 'transmit pressure through a liquid'], ['lightning conductor', 'protect a building from lightning'],
    ['fuse', 'break a circuit when the current is too large']];

$phy = array_merge(
    pairq('Units and measurement', 'easy', 'The SI unit of %s is the', 'The %s is the SI unit of', $units, 'The SI unit of %1$s is the %2$s.'),
    pairq('Measuring instruments', 'easy', 'The %s is used to measure', 'Which instrument measures %s?', $instruments, 'The %1$s measures %2$s.'),
    pairq('Devices and applications', 'medium', 'The %s is used to', 'Which device is used to %s?', $devices, 'The %1$s is used to %2$s.'),
);

return ['Biology' => $bio, 'Chemistry' => $chm, 'Physics' => $phy];
