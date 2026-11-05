<?php
declare(strict_types=1);

/**
 * Chemistry and Physics theory questions (non-calculation) written against the JAMB UTME syllabus topics.
 * Original wording; correct-option positions are varied by the mc() helper.
 */
require_once __DIR__ . '/_helpers.php';

$chm = [];
$phy = [];
$n = 600;
$C = static function (string $t, string $d, string $q, string $ok, array $no, string $x) use (&$chm, &$n): void { $chm[] = mc($t, $d, $q, $ok, $no, $x, $n++); };
$P = static function (string $t, string $d, string $q, string $ok, array $no, string $x) use (&$phy, &$n): void { $phy[] = mc($t, $d, $q, $ok, $no, $x, $n++); };

// ---------------------------------------- Chemistry ----------------------------------------
$C('Separation techniques', 'easy', 'Which method is used to separate a mixture of two miscible liquids with different boiling points?', 'fractional distillation', ['filtration', 'decantation', 'evaporation'], 'The liquid with the lower boiling point distils over first.');
$C('Separation techniques', 'easy', 'Sand can be separated from a mixture of sand and water by', 'filtration', ['distillation', 'sublimation', 'crystallisation'], 'The sand is trapped by the filter paper.');
$C('Atomic structure', 'easy', 'The atomic number of an element is the number of', 'protons in its nucleus', ['neutrons in its nucleus', 'protons and neutrons', 'electrons in the outer shell'], 'The atomic number identifies the element.');
$C('Atomic structure', 'medium', 'The mass number of an atom is the sum of its', 'protons and neutrons', ['protons and electrons', 'neutrons and electrons', 'electrons only'], 'Protons and neutrons are the particles in the nucleus.');
$C('Atomic structure', 'medium', 'Isotopes of an element have the same number of protons but different numbers of', 'neutrons', ['electrons', 'shells', 'ions'], 'Different neutron numbers give different mass numbers.');
$C('Atomic structure', 'medium', 'The electronic configuration of sodium (atomic number 11) is', '2, 8, 1', ['2, 8, 2', '2, 9', '8, 2, 1'], 'Sodium has one electron in its outer shell.');
$C('Periodic table', 'easy', 'Elements in Group 1 of the periodic table are known as the', 'alkali metals', ['halogens', 'noble gases', 'transition metals'], 'They react vigorously with water.');
$C('Periodic table', 'easy', 'Elements in Group 17 are known as the', 'halogens', ['alkali metals', 'noble gases', 'alkaline earth metals'], 'Fluorine, chlorine, bromine and iodine are halogens.');
$C('Periodic table', 'medium', 'The noble gases are unreactive because', 'their outer shells are completely filled', ['they have only one electron', 'they are very heavy', 'they are liquids'], 'A full outer shell is stable.');
$C('Periodic table', 'hard', 'The most electronegative element is', 'fluorine', ['oxygen', 'chlorine', 'nitrogen'], 'Fluorine attracts shared electrons most strongly.');
$C('Chemical bonding', 'easy', 'An ionic bond is formed by', 'the transfer of electrons from one atom to another', ['the sharing of electrons', 'the attraction of nuclei', 'the sharing of protons'], 'The resulting positive and negative ions attract.');
$C('Chemical bonding', 'easy', 'A covalent bond is formed by', 'the sharing of electrons between atoms', ['the transfer of electrons', 'the loss of protons', 'metallic attraction'], 'It usually forms between non-metals.');
$C('Chemical bonding', 'medium', 'Which of these compounds contains covalent bonds?', 'methane, CH₄', ['sodium chloride, NaCl', 'magnesium oxide, MgO', 'calcium fluoride, CaF₂'], 'Carbon and hydrogen are both non-metals and share electrons.');
$C('Acids, bases and salts', 'easy', 'An acid turns blue litmus paper', 'red', ['blue', 'green', 'colourless'], 'Acids give a red colour with blue litmus.');
$C('Acids, bases and salts', 'easy', 'Neutralisation is the reaction between', 'an acid and a base to form a salt and water', ['two acids', 'a metal and oxygen', 'two salts'], 'For example HCl + NaOH → NaCl + H₂O.');
$C('Acids, bases and salts', 'easy', 'The gas produced when zinc reacts with dilute hydrochloric acid is', 'hydrogen', ['oxygen', 'carbon dioxide', 'chlorine'], 'Active metals displace hydrogen from acids.');
$C('Acids, bases and salts', 'medium', 'Hardness of water is caused by dissolved salts of', 'calcium and magnesium', ['sodium and potassium', 'iron and copper only', 'chlorine and fluorine'], 'Hard water does not lather easily with soap.');
$C('Redox reactions', 'medium', 'Oxidation can be defined as', 'the loss of electrons', ['the gain of electrons', 'the gain of protons', 'the loss of neutrons'], 'Remember OIL RIG: oxidation is loss, reduction is gain.');
$C('Redox reactions', 'medium', 'The oxidation number of oxygen in water, H₂O, is', '−2', ['+2', '0', '−1'], 'Oxygen is normally −2 in compounds.');
$C('Electrolysis', 'medium', 'During electrolysis, reduction takes place at the', 'cathode', ['anode', 'electrolyte', 'salt bridge'], 'Positive ions gain electrons at the cathode.');
$C('Electrolysis', 'hard', 'In the electrolysis of concentrated brine with inert electrodes, the gas formed at the anode is', 'chlorine', ['hydrogen', 'oxygen', 'nitrogen'], 'Chloride ions are discharged at the anode; hydrogen forms at the cathode.');
$C('Rates of reaction', 'medium', 'A catalyst', 'speeds up a reaction without being used up', ['slows down a reaction', 'is used up in the reaction', 'changes the products formed'], 'It provides an alternative pathway with lower activation energy.');
$C('Rates of reaction', 'hard', 'Increasing temperature increases the rate of a reaction mainly because', 'more particles have enough energy to react on collision', ['the particles become smaller', 'the volume decreases', 'the catalyst melts'], 'Collisions are more frequent and more energetic.');
$C('Organic chemistry', 'medium', 'The general formula of the alkanes is', 'CₙH₂ₙ₊₂', ['CₙH₂ₙ', 'CₙH₂ₙ₋₂', 'CₙHₙ'], 'Methane CH₄ and ethane C₂H₆ fit this formula.');
$C('Organic chemistry', 'medium', 'The general formula of the alkenes is', 'CₙH₂ₙ', ['CₙH₂ₙ₊₂', 'CₙH₂ₙ₋₂', 'CₙH₂ₙ₊₁'], 'Alkenes have one carbon-carbon double bond.');
$C('Organic chemistry', 'medium', 'The functional group of alkanols (alcohols) is', '−OH', ['−COOH', '−CHO', '−NH₂'], 'It is the hydroxyl group.');
$C('Organic chemistry', 'medium', 'Ethene decolourises bromine water because it is', 'unsaturated', ['saturated', 'an acid', 'an alcohol'], 'The double bond reacts with bromine in an addition reaction.');
$C('Organic chemistry', 'easy', 'The fermentation of glucose by yeast produces', 'ethanol and carbon dioxide', ['methane and water', 'ethanoic acid only', 'hydrogen and oxygen'], 'This is how alcoholic drinks are made.');
$C('Environmental chemistry', 'easy', 'The gas that turns limewater milky is', 'carbon dioxide', ['oxygen', 'hydrogen', 'ammonia'], 'Calcium carbonate forms as a white precipitate.');
$C('Environmental chemistry', 'medium', 'Which gases are mainly responsible for acid rain?', 'sulphur dioxide and oxides of nitrogen', ['carbon monoxide and oxygen', 'hydrogen and helium', 'argon and neon'], 'They form sulphuric and nitric acids in rain water.');
$C('Metals', 'easy', 'Rusting of iron requires', 'water and oxygen', ['oxygen only', 'carbon dioxide only', 'nitrogen and water'], 'Both must be present for iron to rust.');
$C('Metals', 'medium', 'Coating iron with a layer of zinc to prevent rusting is called', 'galvanising', ['electroplating with gold', 'tempering', 'annealing'], 'Zinc corrodes in preference to iron.');
$C('Metals', 'medium', 'When limestone is strongly heated, the gas given off is', 'carbon dioxide', ['oxygen', 'hydrogen', 'sulphur dioxide'], 'CaCO₃ → CaO + CO₂.');

// ---------------------------------------- Physics ----------------------------------------
$P('Measurement', 'easy', 'Which of these is a vector quantity?', 'velocity', ['speed', 'mass', 'distance'], 'A vector has both magnitude and direction.');
$P('Measurement', 'easy', 'The SI unit of power is the', 'watt', ['joule', 'newton', 'pascal'], 'One watt is one joule per second.');
$P('Motion', 'medium', 'The slope of a velocity–time graph gives', 'acceleration', ['distance', 'speed', 'force'], 'Acceleration is the rate of change of velocity.');
$P('Motion', 'medium', 'The area under a velocity–time graph gives', 'the distance travelled', ['the acceleration', 'the force', 'the power'], 'Area equals velocity multiplied by time.');
$P('Newton’s laws', 'easy', 'Newton’s first law states that a body stays at rest or moves with uniform velocity unless it is acted on by', 'an external force', ['gravity only', 'friction only', 'its own weight'], 'This property is called inertia.');
$P('Newton’s laws', 'medium', 'The weight of a body is', 'the force of gravity acting on it', ['the amount of matter in it', 'its volume', 'its density'], 'Weight is measured in newtons; mass is measured in kilograms.');
$P('Forces', 'easy', 'Friction between two surfaces', 'opposes their relative motion', ['helps their relative motion', 'has no effect on motion', 'only acts in liquids'], 'It acts in the direction opposite to motion.');
$P('Forces', 'medium', 'Hooke’s law states that, within the elastic limit, the extension of a spring is proportional to the', 'applied force', ['length of the spring squared', 'mass of the spring', 'colour of the spring'], 'Doubling the load doubles the extension.');
$P('Heat', 'easy', 'Heat is transferred in liquids and gases mainly by', 'convection', ['conduction', 'radiation only', 'friction'], 'Warm fluid rises and cool fluid sinks.');
$P('Heat', 'easy', 'Which surface is the best absorber of heat radiation?', 'a dull black surface', ['a shiny silver surface', 'a white surface', 'a polished surface'], 'Dull black surfaces absorb and emit radiation best.');
$P('Heat', 'medium', 'The fixed points of the Celsius scale are the melting point of pure ice and', 'the boiling point of pure water at standard pressure', ['body temperature', 'room temperature', 'the freezing point of salt water'], 'They are 0°C and 100°C.');
$P('Light', 'easy', 'According to the law of reflection, the angle of incidence is equal to the angle of', 'reflection', ['refraction', 'diffraction', 'dispersion'], 'Both angles are measured from the normal.');
$P('Light', 'medium', 'Refraction of light is the bending of light as it passes', 'from one medium into another', ['through a vacuum only', 'along a straight wire', 'through a mirror'], 'It happens because light changes speed.');
$P('Light', 'easy', 'A converging lens is also called a', 'convex lens', ['concave lens', 'plane lens', 'diverging lens'], 'It is thicker in the middle than at the edges.');
$P('Light', 'medium', 'Short-sightedness (myopia) is corrected using a', 'concave lens', ['convex lens', 'plane mirror', 'prism'], 'A diverging lens moves the image back onto the retina.');
$P('Sound', 'easy', 'Sound cannot travel through', 'a vacuum', ['air', 'water', 'steel'], 'Sound needs a material medium.');
$P('Sound', 'medium', 'The pitch of a sound depends on its', 'frequency', ['amplitude', 'speed', 'wavelength squared'], 'Higher frequency gives higher pitch.');
$P('Sound', 'medium', 'The loudness of a sound depends on its', 'amplitude', ['frequency', 'wavelength', 'speed'], 'Larger amplitude gives a louder sound.');
$P('Sound', 'easy', 'An echo is', 'the reflection of sound', ['the refraction of sound', 'the absorption of sound', 'the diffraction of sound'], 'It is heard after the original sound.');
$P('Magnetism', 'medium', 'Magnetic field lines', 'leave the north pole and enter the south pole', ['leave the south pole and enter the north pole', 'cross each other', 'start and stop at the same point'], 'They never cross.');
$P('Electromagnetism', 'medium', 'A device that converts mechanical energy to electrical energy is a', 'generator (dynamo)', ['motor', 'transformer', 'cell'], 'It works by electromagnetic induction.');
$P('Electromagnetism', 'hard', 'A transformer works on the principle of', 'electromagnetic induction', ['electrolysis', 'thermionic emission', 'photoelectric emission'], 'A changing current in the primary coil induces a voltage in the secondary.');
$P('Electromagnetism', 'medium', 'A step-up transformer', 'increases voltage', ['decreases voltage', 'changes a.c. to d.c.', 'has fewer turns in the secondary coil'], 'It has more turns in the secondary than in the primary.');
$P('Domestic electricity', 'medium', 'A fuse should be connected in the', 'live wire', ['neutral wire', 'earth wire', 'any wire'], 'This disconnects the appliance if the current is too high.');
$P('Atomic physics', 'medium', 'An alpha particle carries a charge of', '+2 (positive)', ['−1 (negative)', 'zero', '+1'], 'It is a helium nucleus with two protons.');
$P('Waves', 'medium', 'Which of these electromagnetic waves has the shortest wavelength?', 'gamma rays', ['radio waves', 'infrared rays', 'visible light'], 'Gamma rays have the highest frequency and energy.');
$P('Waves', 'medium', 'Which of these is NOT an electromagnetic wave?', 'sound waves', ['X-rays', 'visible light', 'radio waves'], 'Sound is a mechanical wave that needs a medium.');

return ['Chemistry' => $chm, 'Physics' => $phy];
