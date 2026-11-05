<?php
declare(strict_types=1);

/**
 * Economics, Government, Commerce and Geography practice questions written against the JAMB UTME
 * syllabus topics. Original wording; correct-option positions are varied by the mc() helper.
 */
require_once __DIR__ . '/_helpers.php';

$eco = [];
$gov = [];
$com = [];
$geo = [];
$n = 400;
$mk = static fn (array &$bank) => static function (string $t, string $d, string $q, string $ok, array $no, string $x) use (&$bank, &$n): void { $bank[] = mc($t, $d, $q, $ok, $no, $x, $n++); };
$E = $mk($eco);
$G = $mk($gov);
$C = $mk($com);
$O = $mk($geo);

// ---------------------------------------- Economics ----------------------------------------
$E('Basic concepts', 'easy', 'The basic economic problem facing every society is', 'scarcity', ['inflation', 'unemployment', 'poverty'], 'Wants are unlimited but resources are limited, so choices must be made.');
$E('Basic concepts', 'easy', 'The next best alternative given up when a choice is made is the', 'opportunity cost', ['marginal cost', 'fixed cost', 'sunk cost'], 'Opportunity cost is the value of the best forgone alternative.');
$E('Demand and supply', 'easy', 'According to the law of demand, when the price of a good rises, the quantity demanded', 'falls', ['rises', 'stays the same', 'doubles'], 'Price and quantity demanded move in opposite directions, other things being equal.');
$E('Demand and supply', 'medium', 'Which of these would shift the demand curve of a normal good to the right?', 'an increase in consumers’ income', ['a rise in the price of the good', 'a fall in consumers’ income', 'a fall in population'], 'Higher income increases demand at every price.');
$E('Demand and supply', 'easy', 'Bread and butter are examples of', 'complementary goods', ['substitutes', 'inferior goods', 'free goods'], 'Complements are used together.');
$E('Demand and supply', 'medium', 'At the equilibrium price', 'quantity demanded equals quantity supplied', ['demand exceeds supply', 'supply exceeds demand', 'the price is zero'], 'The market clears: there is neither shortage nor surplus.');
$E('Demand and supply', 'hard', 'A price ceiling fixed below the equilibrium price will cause', 'a shortage', ['a surplus', 'a fall in demand', 'an increase in supply'], 'At the lower price, quantity demanded exceeds quantity supplied.');
$E('Elasticity', 'medium', 'If the price elasticity of demand for a good is greater than one, demand is said to be', 'elastic', ['inelastic', 'unitary', 'perfectly inelastic'], 'Quantity demanded changes by a larger proportion than price.');
$E('Production', 'easy', 'The reward for the factor of production called land is', 'rent', ['wages', 'interest', 'profit'], 'Labour earns wages, capital earns interest, the entrepreneur earns profit.');
$E('Production', 'easy', 'The reward to the entrepreneur is', 'profit', ['rent', 'wages', 'interest'], 'The entrepreneur organises production and bears the risk.');
$E('Production', 'medium', 'Division of labour leads mainly to', 'specialisation and higher output', ['higher unemployment only', 'lower efficiency', 'less trade'], 'Workers become skilled at particular tasks.');
$E('Costs', 'medium', 'Fixed costs are costs that', 'do not change with the level of output', ['vary directly with output', 'are paid only when goods are sold', 'fall as output rises'], 'Rent on a factory is a fixed cost.');
$E('Costs', 'medium', 'Average cost is obtained by', 'dividing total cost by the quantity produced', ['multiplying price by output', 'subtracting fixed from variable cost', 'dividing output by total cost'], 'AC = TC ÷ Q.');
$E('Market structures', 'easy', 'A market with a single seller is a', 'monopoly', ['oligopoly', 'perfect competition', 'duopoly'], 'The monopolist is the only producer of the good.');
$E('Market structures', 'medium', 'A market dominated by a few large firms is called an', 'oligopoly', ['monopoly', 'perfect market', 'monopsony'], 'Firms in an oligopoly are interdependent.');
$E('Money and banking', 'easy', 'Which institution regulates the supply of money in Nigeria?', 'The Central Bank of Nigeria', ['The Federal Ministry of Finance', 'The NNPC', 'The Nigeria Deposit Insurance Corporation'], 'The CBN conducts monetary policy.');
$E('Money and banking', 'easy', 'A sustained rise in the general price level is called', 'inflation', ['deflation', 'devaluation', 'depression'], 'Inflation reduces the purchasing power of money.');
$E('Money and banking', 'medium', 'Devaluation means', 'a deliberate reduction in the value of a currency against foreign currencies', ['a general fall in prices', 'an increase in the value of the currency', 'a rise in interest rates'], 'It is an official policy decision, unlike depreciation caused by market forces.');
$E('Money and banking', 'easy', 'Which of these is a function of money?', 'a medium of exchange', ['a source of rent', 'a factor of production', 'a means of production'], 'Money lets people trade without barter.');
$E('National income', 'easy', 'GDP stands for', 'Gross Domestic Product', ['General Domestic Price', 'Gross Domestic Profit', 'Government Domestic Product'], 'It measures the total value of goods and services produced in a country in a year.');
$E('National income', 'hard', 'Which of these is NOT a method of measuring national income?', 'the tax method', ['the output method', 'the income method', 'the expenditure method'], 'The three approaches are output, income and expenditure.');
$E('Public finance', 'medium', 'A tax whose burden is borne directly by the person on whom it is imposed is a', 'direct tax', ['indirect tax', 'regressive tax only', 'customs duty'], 'Income tax is a direct tax.');
$E('Public finance', 'medium', 'Value Added Tax (VAT) is an example of an', 'indirect tax', ['direct tax', 'income tax', 'property tax'], 'The tax is passed on to consumers through prices.');
$E('Public finance', 'medium', 'A progressive tax is one that', 'takes a higher percentage from higher incomes', ['takes the same percentage from all incomes', 'takes a lower percentage from higher incomes', 'is paid only by companies'], 'The rate rises as income rises.');
$E('International trade', 'medium', 'The theory of comparative cost advantage was developed by', 'David Ricardo', ['Adam Smith', 'John Keynes', 'Thomas Malthus'], 'Ricardo showed that countries gain by specialising even if one is better at everything.');
$E('International trade', 'easy', 'A tariff is', 'a tax on imported goods', ['a tax on exports only', 'a subsidy to farmers', 'a ban on imports'], 'Tariffs protect local industries and raise revenue.');
$E('International trade', 'medium', 'The balance of payments is a record of', 'a country’s transactions with the rest of the world', ['a country’s budget', 'its internal trade only', 'the banks’ assets'], 'It covers trade in goods, services and capital flows.');
$E('International organisations', 'easy', 'ECOWAS stands for', 'Economic Community of West African States', ['Economic Council of West African States', 'East and Central West African States', 'Economic Community of Western Allied States'], 'ECOWAS was formed in 1975 to promote regional integration.');
$E('International organisations', 'easy', 'OPEC stands for', 'Organisation of Petroleum Exporting Countries', ['Organisation of Petroleum and Energy Companies', 'Oil Producers and Exporters Council', 'Organisation of Private Energy Corporations'], 'Nigeria has been a member since 1971.');
$E('Population', 'medium', 'The optimum population of a country is the population that', 'gives the highest output per head', ['is the largest possible', 'has no unemployment', 'is spread evenly'], 'Below or above it, output per head is lower.');
$E('Population', 'easy', 'The birth rate minus the death rate gives the', 'natural rate of population increase', ['migration rate', 'dependency ratio', 'life expectancy'], 'Migration is not part of this calculation.');
$E('Development', 'hard', 'Economic development differs from economic growth in that development', 'also involves improved living standards and structural change', ['concerns only a rise in GDP', 'is measured by population size', 'ignores health and education'], 'Growth is a quantity increase; development includes quality of life.');
$E('Labour', 'medium', 'Unemployment caused by a change in the structure of the economy is called', 'structural unemployment', ['frictional unemployment', 'seasonal unemployment', 'cyclical unemployment'], 'Workers’ skills no longer match the jobs available.');
$E('Labour', 'easy', 'A worker temporarily between two jobs is', 'frictionally unemployed', ['structurally unemployed', 'seasonally unemployed', 'disguised unemployed'], 'Frictional unemployment is short-term job search.');
$E('Business organisation', 'medium', 'In a limited liability company, the liability of shareholders is limited to', 'the amount they invested in shares', ['their total personal wealth', 'the company’s annual profit', 'nothing at all'], 'Creditors cannot claim shareholders’ personal property.');
$E('Business organisation', 'easy', 'A cooperative society is', 'a voluntary association of people for mutual benefit', ['a business owned by one person', 'a government corporation', 'a foreign company'], 'Members share in its benefits.');
$E('Business organisation', 'medium', 'Shares of public limited companies are bought and sold on the', 'stock exchange', ['commodity market', 'foreign exchange market', 'money market'], 'The stock exchange is the market for securities.');

// ---------------------------------------- Government ----------------------------------------
$G('Basic concepts', 'easy', 'The supreme power of a state to make and enforce laws is called', 'sovereignty', ['legitimacy', 'authority', 'influence'], 'Sovereignty means a state has final authority within its territory.');
$G('Basic concepts', 'medium', 'A state differs from a nation in that a state must have', 'a defined territory and a government', ['a common language', 'a shared religion', 'a single ethnic group'], 'A nation is a people with a shared identity; a state is a political organisation.');
$G('Basic concepts', 'easy', 'Government by the people, through their elected representatives, is called', 'democracy', ['oligarchy', 'monarchy', 'autocracy'], 'In a democracy, power ultimately belongs to the people.');
$G('Basic concepts', 'easy', 'Rule by a small group of people is known as', 'oligarchy', ['democracy', 'republic', 'federation'], '“Oligarchy” comes from the Greek for “rule by few”.');
$G('Basic concepts', 'medium', 'Power that is accepted by the people as rightful is described as', 'legitimate', ['coercive', 'illegal', 'arbitrary'], 'Legitimacy is the people’s acceptance of the right to rule.');
$G('Constitution', 'medium', 'A constitution that is difficult to amend is described as', 'rigid', ['flexible', 'unwritten', 'unitary'], 'Rigid constitutions need special procedures to be changed.');
$G('Constitution', 'medium', 'Which of these countries has an unwritten constitution?', 'the United Kingdom', ['Nigeria', 'the United States', 'Ghana'], 'The UK constitution is drawn from many sources, not one document.');
$G('Constitution', 'medium', 'The doctrine of separation of powers is associated with', 'Montesquieu', ['Rousseau', 'Karl Marx', 'Aristotle'], 'He proposed dividing power among legislature, executive and judiciary.');
$G('Constitution', 'easy', 'The rule of law means that', 'everyone is equal before the law', ['the president is above the law', 'only the court makes laws', 'soldiers make laws'], 'No person is above the law.');
$G('Constitution', 'medium', 'Fundamental human rights are contained in which chapter of the 1999 Nigerian Constitution?', 'Chapter IV', ['Chapter II', 'Chapter III', 'Chapter V'], 'Chapter IV sets out rights such as life and liberty.');
$G('Systems of government', 'medium', 'In a federal system, power is', 'shared between a central government and regional governments', ['held only by the central government', 'held only by the regions', 'shared by the army'], 'Each level has its own area of authority.');
$G('Systems of government', 'medium', 'In a parliamentary system, the executive is drawn from the', 'legislature', ['judiciary', 'civil service', 'military'], 'The prime minister and ministers are normally members of parliament.');
$G('Systems of government', 'easy', 'The head of government in a presidential system is the', 'president', ['prime minister', 'governor-general', 'speaker'], 'The president is both head of state and head of government.');
$G('Arms of government', 'easy', 'The main function of the legislature is to', 'make laws', ['interpret laws', 'enforce laws', 'collect taxes'], 'The executive enforces and the judiciary interprets laws.');
$G('Arms of government', 'easy', 'The judiciary is responsible for', 'interpreting the laws', ['making the laws', 'enforcing the laws', 'conducting elections'], 'Courts settle disputes about the meaning and application of the law.');
$G('Arms of government', 'medium', 'The Nigerian Senate has', '109 members', ['36 members', '100 members', '360 members'], 'Three senators for each of the 36 states plus one for the Federal Capital Territory.');
$G('Electoral systems', 'easy', 'The right of all adult citizens to vote is called', 'universal adult suffrage', ['plebiscite', 'referendum', 'franchise tax'], 'Suffrage means the right to vote.');
$G('Electoral systems', 'medium', 'In the simple majority (first-past-the-post) system, the winner is the candidate with', 'the highest number of votes', ['more than half of all votes', 'a two-thirds majority', 'the most states'], 'Winning needs only more votes than any other candidate.');
$G('Electoral systems', 'easy', 'The body that conducts general elections in Nigeria is the', 'Independent National Electoral Commission (INEC)', ['Code of Conduct Bureau', 'National Assembly', 'Federal Judicial Service Commission'], 'INEC registers voters and organises elections.');
$G('Political parties', 'easy', 'A group that tries to influence government policy without seeking to form the government is a', 'pressure group', ['political party', 'secret society', 'caucus'], 'Examples include trade unions and professional associations.');
$G('Political parties', 'easy', 'The main aim of a political party is to', 'win control of government', ['sell goods', 'make laws for the courts', 'train soldiers'], 'Parties compete in elections to form the government.');
$G('Public service', 'medium', 'The civil service is best described as', 'a permanent body of non-political government employees', ['a group of elected officials', 'a private army', 'a pressure group'], 'It continues to serve whichever party is in power.');
$G('Local government', 'easy', 'The level of government closest to the people is the', 'local government', ['state government', 'federal government', 'national assembly'], 'It provides basic services at community level.');
$G('Pre-colonial rule', 'medium', 'The head of a Hausa-Fulani emirate was the', 'Emir', ['Alaafin', 'Oba', 'Obi'], 'Emirs ruled under the Sultan of Sokoto.');
$G('Pre-colonial rule', 'medium', 'The traditional ruler of the Old Oyo Empire was the', 'Alaafin', ['Emir', 'Obi', 'Oni'], 'The Alaafin of Oyo ruled with the Oyo Mesi council.');
$G('Pre-colonial rule', 'hard', 'The traditional political system of most Igbo communities was', 'decentralised, based on village groups and age grades', ['a strong centralised kingdom', 'ruled by one emir', 'a military empire'], 'There was no single king; decisions were made by councils and elders.');
$G('Colonial rule', 'medium', 'Indirect rule in Nigeria was introduced by', 'Lord Lugard', ['Sir Hugh Clifford', 'Sir Arthur Richards', 'Sir John Macpherson'], 'It governed through traditional rulers.');
$G('Colonial rule', 'easy', 'The Northern and Southern Protectorates were amalgamated in', '1914', ['1900', '1922', '1960'], 'Lord Lugard carried out the amalgamation.');
$G('Colonial rule', 'hard', 'The Clifford Constitution of 1922 introduced', 'the elective principle', ['regional governments', 'internal self-government', 'a federal system'], 'Four seats (three in Lagos, one in Calabar) were opened to elected members.');
$G('Colonial rule', 'hard', 'The constitution that formally introduced federalism in Nigeria was the', 'Lyttelton Constitution of 1954', ['Clifford Constitution of 1922', 'Richards Constitution of 1946', 'Macpherson Constitution of 1951'], 'It created a federal structure with regional governments.');
$G('Independence', 'easy', 'Nigeria became independent on', '1 October 1960', ['1 January 1914', '1 October 1963', '15 January 1966'], 'The country became a republic in 1963.');
$G('Independence', 'easy', 'Nigeria became a republic in', '1963', ['1960', '1966', '1979'], 'Dr Nnamdi Azikiwe became the first President.');
$G('Independence', 'easy', 'The first President of Nigeria was', 'Nnamdi Azikiwe', ['Abubakar Tafawa Balewa', 'Obafemi Awolowo', 'Ahmadu Bello'], 'Tafawa Balewa was the first Prime Minister.');
$G('International organisations', 'easy', 'The headquarters of the United Nations is in', 'New York', ['Geneva', 'London', 'Addis Ababa'], 'The UN has offices in several cities, but its headquarters is in New York.');
$G('International organisations', 'medium', 'The African Union replaced the', 'Organisation of African Unity', ['League of Nations', 'Commonwealth', 'Non-Aligned Movement'], 'The AU was launched in 2002.');
$G('International organisations', 'medium', 'The UN organ with five permanent members is the', 'Security Council', ['General Assembly', 'International Court of Justice', 'Secretariat'], 'The five permanent members hold veto power.');
$G('International organisations', 'easy', 'ECOWAS was established in', '1975', ['1960', '1963', '1980'], 'The Treaty of Lagos created ECOWAS in 1975.');

// ---------------------------------------- Commerce ----------------------------------------
$C('Trade', 'easy', 'Trade carried on within the boundaries of one country is called', 'home (domestic) trade', ['foreign trade', 'entrepôt trade', 'barter'], 'Trade between countries is foreign trade.');
$C('Trade', 'easy', 'Goods sent out of a country for sale abroad are called', 'exports', ['imports', 're-exports only', 'invisibles'], 'Imports are goods brought in.');
$C('Trade', 'medium', 'A tax charged on imported goods is called', 'import duty', ['income tax', 'excise duty', 'value added tax'], 'Import duty is a type of customs duty.');
$C('Distribution', 'easy', 'The usual chain of distribution runs from the producer to the', 'wholesaler, then the retailer, then the consumer', ['retailer, then the wholesaler', 'consumer directly then the retailer', 'agent only'], 'Each middleman performs a function in moving goods.');
$C('Distribution', 'easy', 'A wholesaler mainly', 'sells goods in large quantities to retailers', ['sells goods to final consumers', 'manufactures goods', 'transports goods only'], 'Wholesalers break bulk for retailers.');
$C('Distribution', 'medium', 'Warehouses help to create', 'time utility', ['form utility', 'place utility', 'possession utility'], 'Storing goods until needed makes them available at the right time.');
$C('Business units', 'easy', 'A business owned and run by one person is a', 'sole proprietorship', ['partnership', 'public company', 'cooperative society'], 'The owner bears all profits and losses.');
$C('Business units', 'medium', 'The minimum number of persons needed to form a partnership is', 'two', ['one', 'five', 'seven'], 'A partnership is an association of two or more persons.');
$C('Business units', 'medium', 'A company’s capital divided into units of equal value is called', 'shares', ['debentures', 'bonds', 'bills'], 'Shareholders own the company.');
$C('Business units', 'easy', 'Shareholders receive a part of the company’s profit called', 'dividend', ['interest', 'commission', 'rent'], 'Dividends are paid out of profits.');
$C('Business units', 'hard', 'The memorandum of association of a company states its', 'objects and relationship with the outside world', ['internal rules of management', 'list of its debts', 'dividend policy'], 'The articles of association state the internal rules.');
$C('Documents', 'easy', 'A document sent by a seller to a buyer listing goods supplied and their prices is the', 'invoice', ['receipt', 'cheque', 'waybill'], 'The invoice is the bill for the goods.');
$C('Documents', 'easy', 'A document that acknowledges that money has been received is a', 'receipt', ['invoice', 'debit note', 'quotation'], 'A receipt is proof of payment.');
$C('Documents', 'medium', 'A credit note is sent by a seller to a buyer when', 'the buyer returns goods or was overcharged', ['the buyer owes more money', 'the goods are ordered', 'the goods are delivered'], 'It reduces the amount owed by the buyer.');
$C('Documents', 'hard', 'The document issued by a shipping company acknowledging goods placed on board is the', 'bill of lading', ['invoice', 'consular invoice', 'certificate of origin'], 'It is evidence of the contract of carriage.');
$C('Banking', 'easy', 'A cheque is', 'a written order to a bank to pay a stated sum', ['a loan agreement', 'a bank statement', 'a share certificate'], 'The drawer instructs the bank to pay the payee.');
$C('Banking', 'medium', 'An account that allows withdrawals by cheque and normally pays no interest is a', 'current account', ['savings account', 'fixed deposit account', 'loan account'], 'It is designed for frequent transactions.');
$C('Banking', 'medium', 'An overdraft allows a customer to', 'withdraw more than the balance in the account', ['deposit foreign currency', 'buy shares', 'close the account'], 'It is a short-term borrowing facility.');
$C('Insurance', 'easy', 'The amount paid to an insurance company for cover is the', 'premium', ['claim', 'dividend', 'commission'], 'The insurer takes the premium in exchange for assuming the risk.');
$C('Insurance', 'medium', 'The principle that the insured must disclose all material facts is', 'utmost good faith', ['indemnity', 'subrogation', 'contribution'], 'Failure to disclose can make the policy void.');
$C('Insurance', 'medium', 'The principle of indemnity means that the insured', 'is restored to the same position as before the loss, not better', ['makes a profit from the loss', 'pays no premium', 'chooses the insurer’s losses'], 'Insurance must not be a source of gain.');
$C('Insurance', 'hard', 'Insurable interest means the insured', 'would suffer a financial loss if the subject matter were damaged', ['owns the insurance company', 'works for the insurer', 'pays no premium'], 'Without it the contract is a wager.');
$C('Transport', 'medium', 'The cheapest means of transporting bulky goods over a long distance is', 'water transport', ['air transport', 'road transport', 'pipeline for solids'], 'Ships carry very large loads at low cost per tonne.');
$C('Transport', 'easy', 'The fastest means of transport is', 'air transport', ['rail', 'water', 'road'], 'It is also the most expensive for bulky goods.');
$C('Advertising', 'easy', 'The main purpose of advertising is to', 'inform and persuade people to buy', ['reduce production cost', 'collect taxes', 'keep records'], 'Advertising increases awareness and demand.');
$C('Trade finance', 'medium', 'In a hire purchase agreement, ownership of the goods passes to the buyer', 'when the last instalment is paid', ['when the first instalment is paid', 'when the goods are delivered', 'on signing the agreement'], 'Until then the goods belong to the seller.');
$C('Trade finance', 'medium', 'A reduction in list price given by a seller to a trader is a', 'trade discount', ['cash discount', 'bonus', 'rebate on tax'], 'It is deducted before the invoice is prepared.');
$C('Co-operatives', 'medium', 'Profits of a consumer co-operative society are usually shared among members as', 'dividends based on purchases', ['salaries', 'rent', 'taxes'], 'Members benefit according to their patronage.');
$C('Stock exchange', 'easy', 'The stock exchange deals mainly in', 'shares and bonds', ['perishable foods', 'second-hand cars', 'raw cotton only'], 'It is the market for company and government securities.');
$C('Communication', 'easy', 'Which of these is a means of telecommunication?', 'a mobile telephone', ['a courier van', 'a railway', 'a pipeline'], 'Telecommunication uses electrical or electronic signals.');

// ---------------------------------------- Geography ----------------------------------------
$O('The earth', 'easy', 'The earth rotates on its axis from', 'west to east', ['east to west', 'north to south', 'south to north'], 'This is why the sun appears to rise in the east.');
$O('The earth', 'easy', 'One complete rotation of the earth takes about', '24 hours', ['12 hours', '30 days', '365 days'], 'A rotation gives day and night.');
$O('The earth', 'easy', 'The earth takes about ___ to complete one revolution round the sun.', '365¼ days', ['24 hours', '30 days', '12 months only exactly'], 'The extra quarter day is why we have a leap year every four years.');
$O('The earth', 'medium', 'The seasons are caused mainly by', 'the tilt of the earth’s axis as it revolves round the sun', ['the earth’s rotation', 'the moon’s orbit', 'the distance from the equator only'], 'Different parts receive different amounts of sunlight during the year.');
$O('The earth', 'easy', 'The latitude of the Equator is', '0°', ['23½°', '66½°', '90°'], 'The Equator divides the earth into northern and southern hemispheres.');
$O('The earth', 'easy', 'The Prime Meridian (0° longitude) passes through', 'Greenwich', ['Lagos', 'Paris', 'Cairo'], 'Greenwich near London is the reference for longitude and time zones.');
$O('Rocks', 'medium', 'Igneous rocks are formed from', 'cooled and solidified magma or lava', ['compressed sediments', 'the remains of animals', 'rocks changed by heat and pressure'], 'Granite and basalt are examples.');
$O('Rocks', 'medium', 'Limestone is an example of a/an', 'sedimentary rock', ['igneous rock', 'metamorphic rock', 'volcanic rock'], 'It forms from sediments, often the remains of marine animals.');
$O('Rocks', 'hard', 'Marble is formed when limestone is changed by', 'heat and pressure', ['cooling magma', 'wind erosion only', 'river deposition'], 'Rocks changed by heat and pressure are metamorphic.');
$O('Weathering and erosion', 'easy', 'The breaking up of rocks in place by weather is called', 'weathering', ['erosion', 'deposition', 'transportation'], 'Erosion involves transport by moving agents.');
$O('Weathering and erosion', 'medium', 'The wearing away and removal of land material by running water, wind or ice is', 'erosion', ['weathering', 'metamorphism', 'folding'], 'Erosion includes transport and deposition.');
$O('Rivers', 'medium', 'A delta is formed when a river', 'deposits its sediment at its mouth', ['cuts a V-shaped valley', 'flows over a waterfall', 'forms a lake'], 'The Niger Delta is a large example.');
$O('Rivers', 'easy', 'A river bend that forms in the middle or lower course is a', 'meander', ['waterfall', 'tributary', 'estuary'], 'Meanders form through erosion on the outer bank and deposition on the inner bank.');
$O('Rivers', 'easy', 'The River Niger and River Benue meet at', 'Lokoja', ['Kano', 'Jos', 'Enugu'], 'Lokoja, in Kogi State, is the confluence.');
$O('Africa', 'easy', 'The longest river in Africa is the', 'Nile', ['Niger', 'Congo', 'Zambezi'], 'The Nile flows northward to the Mediterranean.');
$O('Africa', 'easy', 'The highest mountain in Africa is', 'Mount Kilimanjaro', ['Mount Kenya', 'Mount Cameroon', 'the Atlas Mountains'], 'It is in Tanzania.');
$O('Climate', 'medium', 'The Harmattan is a', 'dry, dusty wind blowing from the Sahara', ['wet wind from the Atlantic', 'cold ocean current', 'strong hurricane'], 'It occurs in West Africa between about November and March.');
$O('Climate', 'easy', 'The instrument used to measure rainfall is the', 'rain gauge', ['barometer', 'thermometer', 'anemometer'], 'A barometer measures air pressure.');
$O('Climate', 'easy', 'A barometer measures', 'atmospheric pressure', ['temperature', 'humidity', 'wind speed'], 'Falling pressure often signals wet weather.');
$O('Vegetation', 'medium', 'Savanna vegetation is characterised by', 'tall grasses with scattered trees', ['dense evergreen forest', 'bare sand', 'ice and moss'], 'Savanna has a distinct wet and dry season.');
$O('Vegetation', 'medium', 'The tropical rainforest is characterised by', 'dense evergreen trees growing in layers', ['short grass only', 'cactus plants', 'conifer trees'], 'Heavy rain and heat throughout the year support it.');
$O('Map work', 'medium', 'On a map of scale 1 : 50 000, 1 cm represents', '500 m on the ground', ['50 m', '5 km', '50 km'], '50 000 cm = 500 m.');
$O('Map work', 'easy', 'A line on a map joining places of equal height is a', 'contour line', ['meridian', 'isobar', 'grid line'], 'Close contour lines show steep slopes.');
$O('Nigeria', 'medium', 'Tin is mined mainly on the', 'Jos Plateau', ['Niger Delta', 'Obudu Plateau', 'Lagos lagoon'], 'Jos Plateau historically produced tin (cassiterite).');
$O('Nigeria', 'easy', 'Coal in Nigeria is found at', 'Enugu', ['Kano', 'Sokoto', 'Lagos'], 'Enugu was a major coal-mining centre.');
$O('Nigeria', 'easy', 'Cocoa in Nigeria is grown mainly in the', 'south-west', ['north-east', 'north-west', 'far north'], 'Ondo, Ogun, Oyo and Osun are major producers.');
$O('Nigeria', 'medium', 'Groundnut in Nigeria is grown mainly in the', 'northern savanna', ['coastal swamp', 'rainforest belt', 'Niger Delta'], 'The savanna’s dry season suits groundnut harvesting.');
$O('Population', 'easy', 'Population density is the', 'number of people per unit area', ['total number of people in a country', 'number of births per year', 'number of towns in a region'], 'It is usually measured as people per square kilometre.');
$O('Population', 'easy', 'The movement of people from villages to cities is', 'rural–urban migration', ['emigration', 'urbanisation only', 'immigration'], 'It is common in many developing countries.');

return ['Economics' => $eco, 'Government' => $gov, 'Commerce' => $com, 'Geography' => $geo];
