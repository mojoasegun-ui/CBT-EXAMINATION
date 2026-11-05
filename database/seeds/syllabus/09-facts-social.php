<?php
declare(strict_types=1);

/** Geography, Economics, Government and Commerce recall questions from fact lists. Original wording. */
require_once __DIR__ . '/_helpers.php';

$states = [['Abia', 'Umuahia'], ['Adamawa', 'Yola'], ['Akwa Ibom', 'Uyo'], ['Anambra', 'Awka'], ['Bauchi', 'Bauchi'], ['Bayelsa', 'Yenagoa'], ['Benue', 'Makurdi'], ['Borno', 'Maiduguri'],
    ['Cross River', 'Calabar'], ['Delta', 'Asaba'], ['Ebonyi', 'Abakaliki'], ['Edo', 'Benin City'], ['Ekiti', 'Ado-Ekiti'], ['Enugu', 'Enugu'], ['Gombe', 'Gombe'], ['Imo', 'Owerri'],
    ['Jigawa', 'Dutse'], ['Kaduna', 'Kaduna'], ['Kano', 'Kano'], ['Katsina', 'Katsina'], ['Kebbi', 'Birnin Kebbi'], ['Kogi', 'Lokoja'], ['Kwara', 'Ilorin'], ['Lagos', 'Ikeja'],
    ['Nasarawa', 'Lafia'], ['Niger', 'Minna'], ['Ogun', 'Abeokuta'], ['Ondo', 'Akure'], ['Osun', 'Osogbo'], ['Oyo', 'Ibadan'], ['Plateau', 'Jos'], ['Rivers', 'Port Harcourt'],
    ['Sokoto', 'Sokoto'], ['Taraba', 'Jalingo'], ['Yobe', 'Damaturu'], ['Zamfara', 'Gusau']];
$africa = [['Nigeria', 'Abuja'], ['Ghana', 'Accra'], ['Kenya', 'Nairobi'], ['Egypt', 'Cairo'], ['Ethiopia', 'Addis Ababa'], ['South Africa', 'Pretoria'], ['Morocco', 'Rabat'], ['Algeria', 'Algiers'],
    ['Senegal', 'Dakar'], ['Mali', 'Bamako'], ['Niger Republic', 'Niamey'], ['Chad', 'N’Djamena'], ['Cameroon', 'Yaoundé'], ['Benin Republic', 'Porto-Novo'], ['Togo', 'Lomé'], ['Tanzania', 'Dodoma'],
    ['Uganda', 'Kampala'], ['Zambia', 'Lusaka'], ['Zimbabwe', 'Harare'], ['Angola', 'Luanda'], ['Sudan', 'Khartoum'], ['Libya', 'Tripoli'], ['Tunisia', 'Tunis'], ['Liberia', 'Monrovia'],
    ['Sierra Leone', 'Freetown'], ['Côte d’Ivoire', 'Yamoussoukro'], ['Gambia', 'Banjul'], ['Rwanda', 'Kigali'], ['Namibia', 'Windhoek'], ['Mozambique', 'Maputo']];
$landforms = [['a large body of fresh water surrounded by land', 'lake'], ['a narrow strip of land joining two larger land masses', 'isthmus'], ['land almost surrounded by water', 'peninsula'],
    ['a large flat-topped area of high land', 'plateau'], ['a low area between hills or mountains', 'valley'], ['the place where a river meets the sea', 'estuary'], ['the point where a river begins', 'source'],
    ['a fan-shaped area of deposits at a river mouth', 'delta'], ['a narrow sea inlet between two land masses', 'strait'], ['a ridge of sand built up by wind in a desert', 'dune'],
    ['a fertile spot with water in a desert', 'oasis'], ['a mass of ice that moves slowly down a valley', 'glacier'], ['a vent through which lava erupts', 'volcano'],
    ['a line joining places of equal height', 'contour'], ['the imaginary line at 0° latitude', 'equator']];

$geo = array_merge(
    pairq('Nigeria: states and capitals', 'easy', 'What is the capital of %s State?', 'Of which Nigerian state is %s the capital?', $states, '%2$s is the capital of %1$s State.'),
    pairq('Africa: countries and capitals', 'medium', 'What is the capital of %s?', 'Of which African country is %s the capital?', $africa, '%2$s is the capital of %1$s.'),
    pairq('Physical geography', 'medium', 'Which term describes %s?', null, $landforms, 'A %2$s is %1$s.'),
);

$econ = [['scarcity', 'limited resources set against unlimited wants'], ['opportunity cost', 'the next best alternative given up when a choice is made'], ['demand', 'the quantity of a good consumers are willing and able to buy at a given price'],
    ['supply', 'the quantity of a good producers are willing to sell at a given price'], ['inflation', 'a persistent rise in the general price level'], ['division of labour', 'splitting production into separate tasks done by different workers'],
    ['monopoly', 'a market with only one seller'], ['gross domestic product', 'the total value of goods and services produced in a country in a year'], ['specialisation', 'concentrating on producing one good or service'],
    ['fixed cost', 'a cost that does not change with the level of output'], ['elasticity of demand', 'the responsiveness of quantity demanded to a change in price'], ['barter', 'direct exchange of goods for goods'],
    ['public good', 'a good that benefits everyone and cannot be withheld from anyone'], ['tax', 'a compulsory payment made to the government'], ['budget', 'a plan of expected income and expenditure'],
    ['wage', 'payment for labour'], ['rent', 'payment for the use of land'], ['interest', 'payment for the use of capital'], ['profit', 'reward for the entrepreneur'],
    ['deflation', 'a persistent fall in the general price level']];
$econ = pairq('Basic economic concepts', 'medium', 'Which term means %s?', 'The term “%s” means', array_map(static fn ($p) => [$p[1], $p[0]], $econ), '“%2$s” is %1$s.');

$gov = [['The 1914 Amalgamation', 'Lord Lugard'], ['the first Governor-General of Nigeria', 'Lord Lugard'], ['the first Prime Minister of Nigeria', 'Abubakar Tafawa Balewa'],
    ['the first President of Nigeria', 'Nnamdi Azikiwe'], ['the first Executive President of Nigeria', 'Shehu Shagari'], ['the first Premier of the Western Region', 'Obafemi Awolowo'], ['the first Premier of the Northern Region', 'Ahmadu Bello']];
$gov = [['Nigeria’s independence from Britain', '1960'], ['Nigeria becoming a republic', '1963'], ['the amalgamation of Northern and Southern Nigeria', '1914'], ['the creation of Abuja as the capital', '1991'],
    ['the return to civilian rule in the Fourth Republic', '1999'], ['the Richards Constitution', '1946'], ['the Macpherson Constitution', '1951'], ['the Lyttleton Constitution', '1954'],
    ['the Clifford Constitution', '1922'], ['the Independence Constitution', '1960'], ['the Biafran civil war began', '1967'], ['the first military coup in Nigeria', '1966']];
$govA = pairq('Nigerian political history', 'medium', 'In which year was %s?', null, array_map(static fn ($p) => [$p[0], $p[1]], $gov), '%1$s: %2$s.');
$orgs = [['The United Nations', 'New York'], ['The African Union', 'Addis Ababa'], ['ECOWAS', 'Abuja'], ['OPEC', 'Vienna'], ['The Commonwealth Secretariat', 'London'], ['The International Court of Justice', 'The Hague'],
    ['The World Health Organisation', 'Geneva'], ['UNESCO', 'Paris'], ['The International Monetary Fund', 'Washington, D.C.']];
$govB = pairq('International organisations', 'medium', 'Where is the headquarters of %s?', 'Which organisation has its headquarters in %s?', $orgs, '%1$s is headquartered in %2$s.');
$arms = [['makes laws', 'the legislature'], ['carries out laws', 'the executive'], ['interprets laws', 'the judiciary']];
$govC = pairq('Arms of government', 'easy', 'Which arm of government %s?', null, array_map(static fn ($p) => [$p[0], $p[1]], $arms), 'It is %2$s that %1$s.');
$govTerms = [['democracy', 'rule by the people'], ['monarchy', 'rule by a king or queen'], ['oligarchy', 'rule by a few people'], ['autocracy', 'rule by one person with unlimited power'],
    ['sovereignty', 'the supreme power of a state to rule itself'], ['citizenship', 'full membership of a state with its rights and duties'], ['suffrage', 'the right to vote'],
    ['constitution', 'the fundamental law of a state'], ['federalism', 'a system where power is shared between central and regional governments'], ['bicameral legislature', 'a legislature with two chambers']];
$govD = pairq('Concepts in government', 'medium', 'What is the meaning of %s?', 'Which term means “%s”?', $govTerms, '%1$s means “%2$s”.');

$com = [['commerce', 'all the activities that help goods move from producers to consumers'], ['retail trade', 'selling goods in small quantities to final consumers'], ['wholesale trade', 'selling goods in large quantities to retailers'],
    ['insurance', 'protection against loss in return for a premium'], ['premium', 'the amount paid to an insurer'], ['bank draft', 'a bank’s order to pay a stated sum to a named person'],
    ['cheque', 'a written order to a bank to pay money from an account'], ['bill of lading', 'a document giving details of goods shipped'], ['invoice', 'a seller’s document listing goods sold and their prices'],
    ['warehouse', 'a building used to store goods'], ['advertising', 'making a product known to the public to encourage buying'], ['partnership', 'a business owned by two to twenty people'],
    ['sole proprietorship', 'a business owned and run by one person'], ['limited liability company', 'a company whose owners’ losses are limited to what they invested'],
    ['co-operative society', 'a business formed by members to help each other economically'], ['stock exchange', 'a market where company shares are bought and sold'], ['dividend', 'a share of company profit paid to shareholders'],
    ['credit note', 'a document sent by a seller to reduce a buyer’s debt'], ['entrepôt trade', 'importing goods to re-export them'], ['balance of trade', 'the difference between the value of exports and imports of goods']];
$com = pairq('Commerce terms', 'medium', 'Which term means %s?', 'The term “%s” means', array_map(static fn ($p) => [$p[1], $p[0]], $com), '“%2$s” is %1$s.');

return ['Geography' => $geo, 'Economics' => $econ, 'Government' => array_merge($govA, $govB, $govC, $govD), 'Commerce' => $com];
