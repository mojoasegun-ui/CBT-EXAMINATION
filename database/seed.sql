-- Original practice questions for the anonymous simulator. No accounts are seeded.
INSERT INTO cbt_exam_types (slug, name, description, is_active) VALUES
('waec', 'WAEC', 'West African school-leaving examination practice.', 1),
('neco', 'NECO', 'National examination practice sets.', 1),
('jamb', 'JAMB', 'UTME-style practice sets.', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), is_active = 1;

INSERT INTO cbt_subjects (slug, name, icon, is_active) VALUES
('english', 'English Language', 'languages', 1),
('mathematics', 'Mathematics', 'calculator', 1),
('biology', 'Biology', 'leaf', 1),
('chemistry', 'Chemistry', 'flask', 1),
('physics', 'Physics', 'atom', 1),
('government', 'Government', 'landmark', 1),
('economics', 'Economics', 'chart', 1),
('literature', 'Literature', 'book', 1),
('crs', 'Christian Religious Studies', 'sparkles', 1),
('geography', 'Geography', 'globe', 1),
('agricultural-science', 'Agricultural Science', 'sprout', 1),
('animal-husbandry', 'Animal Husbandry', 'paw', 1),
('arabic', 'Arabic', 'languages', 1),
('basic-electricity', 'Basic Electricity', 'bolt', 1),
('building-construction', 'Building Construction', 'building', 1),
('business-studies', 'Business Studies', 'briefcase', 1),
('civic-education', 'Civic Education', 'landmark', 1),
('computer-studies', 'Computer Studies', 'monitor', 1),
('data-processing', 'Data Processing', 'database', 1),
('financial-accounting', 'Financial Accounting', 'coins', 1),
('food-and-nutrition', 'Food and Nutrition', 'utensils', 1),
('french', 'French', 'languages', 1),
('further-mathematics', 'Further Mathematics', 'sigma', 1),
('health-education', 'Health Education', 'heart', 1),
('islamic-studies', 'Islamic Studies', 'moon', 1),
('marketing', 'Marketing', 'megaphone', 1),
('office-practice', 'Office Practice', 'clipboard', 1),
('technical-drawing', 'Technical Drawing', 'ruler', 1),
('visual-arts', 'Visual Arts', 'palette', 1),
('woodwork', 'Woodwork', 'hammer', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), icon = VALUES(icon), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2020, 'WAEC Mathematics 2020 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'mathematics'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2021, 'WAEC Mathematics 2021 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'mathematics'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2022, 'WAEC Mathematics 2022 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'mathematics'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2023, 'WAEC Mathematics 2023 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'mathematics'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2024, 'WAEC English Language 2024 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'english'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2023, 'WAEC Biology 2023 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'biology'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2024, 'WAEC Chemistry 2024 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'chemistry'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2020, 'JAMB English Language 2020 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'english'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2021, 'JAMB English Language 2021 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'english'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2022, 'JAMB Mathematics 2022 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'mathematics'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2023, 'JAMB Mathematics 2023 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'mathematics'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2024, 'JAMB English Language 2024 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'english'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2021, 'JAMB Physics 2021 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'physics'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2023, 'JAMB Economics 2023 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'economics'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2020, 'NECO English Language 2020 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'english'
WHERE t.slug = 'neco'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2021, 'NECO Mathematics 2021 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'mathematics'
WHERE t.slug = 'neco'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2022, 'NECO Biology 2022 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'biology'
WHERE t.slug = 'neco'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2023, 'NECO Biology 2023 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'biology'
WHERE t.slug = 'neco'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2024, 'NECO Chemistry 2024 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'chemistry'
WHERE t.slug = 'neco'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2023, 'NECO Geography 2023 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'geography'
WHERE t.slug = 'neco'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, marks, sort_order)
SELECT e.id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option, q.explanation, 1.00, q.sort_order
FROM cbt_exams e
JOIN cbt_exam_types t ON t.id = e.exam_type_id
JOIN cbt_subjects s ON s.id = e.subject_id
JOIN (
	SELECT 'waec' exam_slug, 'mathematics' subject_slug, 1 sort_order, 'Simplify 3(2x - 4) + 5.' question_text, '6x - 12' option_a, '6x - 7' option_b, '6x - 17' option_c, '5x - 7' option_d, 'B' correct_option, 'Distribute 3 to get 6x - 12, then add 5 to obtain 6x - 7.' explanation
	UNION ALL SELECT 'waec', 'mathematics', 2, 'A rectangle has length 12 cm and width 5 cm. What is its area?', '17 cm^2', '34 cm^2', '60 cm^2', '120 cm^2', 'C', 'Area is length multiplied by width: 12 x 5 = 60 cm^2.'
	UNION ALL SELECT 'waec', 'mathematics', 3, 'Solve 4y + 3 = 19.', 'y = 3', 'y = 4', 'y = 5', 'y = 7', 'B', 'Subtract 3 to get 4y = 16, then divide by 4.'
	UNION ALL SELECT 'waec', 'mathematics', 4, 'What is the median of 2, 5, 7, 9, 12?', '5', '7', '8', '9', 'B', 'The ordered list has five values, so the middle value is 7.'
	UNION ALL SELECT 'waec', 'mathematics', 5, 'Express 0.375 as a fraction in its simplest form.', '3/4', '3/8', '5/8', '15/40', 'B', '0.375 = 375/1000; dividing numerator and denominator by 125 gives 3/8.'
	UNION ALL SELECT 'waec', 'mathematics', 6, 'A bag contains 3 red and 5 blue counters. What is the probability of choosing a red counter?', '3/5', '5/8', '3/8', '1/3', 'C', 'There are 3 red counters among 8 counters in total.'
	UNION ALL SELECT 'waec', 'mathematics', 7, 'Find the gradient of the line through (1, 2) and (5, 10).', '1', '2', '4', '8', 'B', 'Gradient = (10 - 2) / (5 - 1) = 8/4 = 2.'
	UNION ALL SELECT 'waec', 'mathematics', 8, 'A triangle has angles 48 degrees and 67 degrees. Find the third angle.', '55 degrees', '65 degrees', '75 degrees', '85 degrees', 'B', 'Angles in a triangle sum to 180 degrees; 180 - 48 - 67 = 65 degrees.'
	UNION ALL SELECT 'waec', 'mathematics', 9, 'Evaluate 2^3 x 2^2.', '16', '24', '32', '64', 'C', 'For equal bases, add exponents: 2^(3+2) = 2^5 = 32.'
	UNION ALL SELECT 'waec', 'mathematics', 10, 'A shirt costs NGN 8,000 and is discounted by 15%. What is the sale price?', 'NGN 6,400', 'NGN 6,800', 'NGN 7,200', 'NGN 7,850', 'B', '15% of NGN 8,000 is NGN 1,200; the sale price is NGN 6,800.'
	UNION ALL SELECT 'jamb', 'english', 1, 'Choose the word nearest in meaning to "concise".', 'Brief', 'Confusing', 'Detailed', 'Uncertain', 'A', 'Concise means expressed clearly in few words, or brief.'
	UNION ALL SELECT 'jamb', 'english', 2, 'Choose the correct completion: Neither the teacher nor the students ___ ready.', 'is', 'was', 'are', 'has', 'C', 'With neither/nor, the verb agrees with the nearer subject, "students".'
	UNION ALL SELECT 'jamb', 'english', 3, 'Which word is opposite in meaning to "scarce"?', 'Rare', 'Plentiful', 'Limited', 'Uncommon', 'B', 'Plentiful means available in large quantities, the opposite of scarce.'
	UNION ALL SELECT 'jamb', 'english', 4, 'Select the correctly punctuated sentence.', 'After lunch we returned, to class.', 'After lunch, we returned to class.', 'After, lunch we returned to class.', 'After lunch we, returned to class.', 'B', 'The introductory phrase is followed by a comma; the subject and verb stay together.'
	UNION ALL SELECT 'jamb', 'english', 5, 'In "The committee reached a unanimous decision," what does "unanimous" mean?', 'Made quickly', 'Made by everyone in agreement', 'Made in secret', 'Made after a debate', 'B', 'A unanimous decision is one supported by all members.'
	UNION ALL SELECT 'jamb', 'english', 6, 'Choose the correct word: She has lived in Abuja ___ 2020.', 'for', 'since', 'during', 'by', 'B', '"Since" introduces the starting point of a period that continues to the present.'
	UNION ALL SELECT 'jamb', 'english', 7, 'Which sentence is in the passive voice?', 'The team solved the problem.', 'The problem was solved by the team.', 'The team is solving the problem.', 'The team will solve the problem.', 'B', 'The subject "problem" receives the action in "was solved".'
	UNION ALL SELECT 'jamb', 'english', 8, 'Choose the correctly spelt word.', 'Accomodation', 'Acommodation', 'Accommodation', 'Accommondation', 'C', 'The standard spelling is accommodation, with double c and double m.'
	UNION ALL SELECT 'jamb', 'english', 9, 'What is the function of "although" in "Although it rained, the match continued"?', 'It introduces a contrast.', 'It gives a reason.', 'It states a result.', 'It gives an example.', 'A', 'Although introduces a contrast between the rain and the match continuing.'
	UNION ALL SELECT 'jamb', 'english', 10, 'Choose the correct reported form: Musa said, "I am tired."', 'Musa said that I am tired.', 'Musa said that he was tired.', 'Musa says that he tired.', 'Musa said he is tiring.', 'B', 'In reported speech, the pronoun changes to he and the present tense shifts to past.'
	UNION ALL SELECT 'neco', 'biology', 1, 'Which organelle is the main site of aerobic respiration in a cell?', 'Ribosome', 'Mitochondrion', 'Golgi apparatus', 'Vacuole', 'B', 'Mitochondria release energy through aerobic respiration.'
	UNION ALL SELECT 'neco', 'biology', 2, 'Which process produces glucose in green plants?', 'Transpiration', 'Respiration', 'Photosynthesis', 'Fermentation', 'C', 'Photosynthesis uses light energy, carbon dioxide, and water to produce glucose.'
	UNION ALL SELECT 'neco', 'biology', 3, 'What is the basic structural and functional unit of life?', 'Tissue', 'Organ', 'Cell', 'System', 'C', 'The cell is the smallest unit capable of carrying out life processes.'
	UNION ALL SELECT 'neco', 'biology', 4, 'Which blood component transports most oxygen?', 'Platelets', 'Red blood cells', 'Plasma', 'White blood cells', 'B', 'Haemoglobin in red blood cells binds and transports most oxygen.'
	UNION ALL SELECT 'neco', 'biology', 5, 'The transfer of pollen from anther to stigma is called what?', 'Fertilisation', 'Pollination', 'Germination', 'Dispersal', 'B', 'Pollination is the transfer of pollen grains to a flower stigma.'
	UNION ALL SELECT 'neco', 'biology', 6, 'Which organism is a primary consumer in a grassland food chain?', 'Grass', 'Grasshopper', 'Snake', 'Hawk', 'B', 'A grasshopper feeds on producers such as grass, so it is a primary consumer.'
	UNION ALL SELECT 'neco', 'biology', 7, 'Which part of the human kidney filters blood?', 'Nephron', 'Alveolus', 'Neuron', 'Villus', 'A', 'Each nephron filters blood and helps form urine.'
	UNION ALL SELECT 'neco', 'biology', 8, 'What is the term for an organism''s observable characteristics?', 'Genotype', 'Phenotype', 'Allele', 'Mutation', 'B', 'Phenotype describes observable traits produced by genes and environment.'
	UNION ALL SELECT 'neco', 'biology', 9, 'Which gas is released by plants during photosynthesis?', 'Nitrogen', 'Carbon dioxide', 'Oxygen', 'Methane', 'C', 'Oxygen is released when water molecules are split during photosynthesis.'
	UNION ALL SELECT 'neco', 'biology', 10, 'What is the main role of decomposers in an ecosystem?', 'Make sunlight', 'Recycle nutrients', 'Produce oxygen only', 'Control rainfall', 'B', 'Decomposers break down dead material and return nutrients to the ecosystem.'
	UNION ALL SELECT 'waec', 'mathematics', 11, 'A shop marks a school bag at NGN 12,500 and offers a discount of 20 percent. What amount does a buyer pay after the discount?', 'NGN 9,500', 'NGN 10,000', 'NGN 10,500', 'NGN 11,000', 'B', 'Twenty percent of NGN 12,500 is NGN 2,500; subtracting it gives NGN 10,000.'
	UNION ALL SELECT 'waec', 'mathematics', 12, 'A rectangular garden is 18 m long and 11 m wide. How many metres of fencing are needed to go once around it?', '29 m', '47 m', '58 m', '198 m', 'C', 'Perimeter is 2(length + width), so 2(18 + 11) = 58 m.'
	UNION ALL SELECT 'waec', 'mathematics', 13, 'Solve the equation 5x - 7 = 3x + 9 and select the value of x.', '6', '7', '8', '9', 'C', 'Subtract 3x and add 7 on both sides: 2x = 16, hence x = 8.'
	UNION ALL SELECT 'waec', 'mathematics', 14, 'The ratio of red counters to blue counters is 3:5. If there are 40 counters altogether, how many are blue?', '15', '20', '25', '30', 'C', 'There are 8 ratio parts; each part is 40/8 = 5, so blue counters total 5 x 5 = 25.'
	UNION ALL SELECT 'waec', 'mathematics', 15, 'A class has 50 learners and 18 percent are absent on a particular day. How many learners are present?', '39', '41', '43', '45', 'B', 'Eighteen percent of 50 is 9; subtracting 9 from 50 leaves 41 learners present.'
	UNION ALL SELECT 'waec', 'mathematics', 16, 'Five test scores are 6, 8, 9, 11 and 16. What is their arithmetic mean?', '9', '10', '11', '12', 'B', 'The total is 50; dividing by the five scores gives a mean of 10.'
	UNION ALL SELECT 'waec', 'mathematics', 17, 'A sequence begins 4, 9, 14, 19. If the same pattern continues, what is the next term?', '22', '23', '24', '25', 'C', 'The sequence increases by 5 each time, so the next term is 24.'
	UNION ALL SELECT 'waec', 'mathematics', 18, 'A trapezium has parallel sides of 8 cm and 14 cm and a perpendicular height of 6 cm. Find its area.', '44 cm^2', '60 cm^2', '66 cm^2', '84 cm^2', 'C', 'Area is half the sum of parallel sides times height: 0.5(8 + 14) x 6 = 66 cm^2.'
	UNION ALL SELECT 'waec', 'mathematics', 19, 'Solve simultaneously: x + y = 11 and x - y = 3. What is the value of x?', '4', '7', '8', '14', 'B', 'Adding the equations gives 2x = 14, so x = 7.'
	UNION ALL SELECT 'waec', 'mathematics', 20, 'A box contains 4 green and 6 yellow balls. One ball is chosen at random. What is the probability that it is green?', '1/5', '2/5', '3/5', '4/5', 'B', 'There are 4 favourable outcomes out of 10 equally likely balls, giving 4/10 = 2/5.'
	UNION ALL SELECT 'waec', 'mathematics', 21, 'A principal of NGN 30,000 is invested at 8 percent simple interest per year for 2 years. What is the interest earned?', 'NGN 2,400', 'NGN 4,800', 'NGN 6,000', 'NGN 34,800', 'B', 'Simple interest is PRT/100 = 30,000 x 8 x 2 / 100 = NGN 4,800.'
	UNION ALL SELECT 'waec', 'mathematics', 22, 'A bus travels 180 km in 3 hours at a constant speed. What is its average speed?', '45 km/h', '50 km/h', '60 km/h', '90 km/h', 'C', 'Average speed is distance divided by time: 180/3 = 60 km/h.'
	UNION ALL SELECT 'waec', 'mathematics', 23, 'Factorise the quadratic expression x^2 + 7x + 12 completely.', '(x + 2)(x + 6)', '(x + 3)(x + 4)', '(x - 3)(x - 4)', '(x + 1)(x + 12)', 'B', 'The numbers 3 and 4 multiply to 12 and add to 7, giving (x + 3)(x + 4).'
	UNION ALL SELECT 'waec', 'mathematics', 24, 'A recipe uses 3/4 cup of flour. A cook prepares half the recipe. How much flour is needed?', '1/4 cup', '3/8 cup', '1/2 cup', '2/3 cup', 'B', 'Half of 3/4 is 3/8 cup.'
	UNION ALL SELECT 'waec', 'mathematics', 25, 'Three interior angles of a quadrilateral are 82 degrees, 95 degrees and 103 degrees. Find the fourth angle.', '70 degrees', '80 degrees', '90 degrees', '100 degrees', 'B', 'The interior angles sum to 360 degrees; 360 - 82 - 95 - 103 = 80 degrees.'
	UNION ALL SELECT 'waec', 'mathematics', 26, 'A storage carton measures 12 cm by 8 cm by 5 cm. What is its volume?', '25 cm^3', '96 cm^3', '240 cm^3', '480 cm^3', 'D', 'Volume of a cuboid is length x width x height: 12 x 8 x 5 = 480 cm^3.'
	UNION ALL SELECT 'waec', 'mathematics', 27, 'A straight line passes through (0, 3) and (2, 7). What is its gradient?', '1', '2', '3', '4', 'B', 'Gradient is change in y divided by change in x: (7 - 3)/(2 - 0) = 2.'
	UNION ALL SELECT 'waec', 'mathematics', 28, 'The cost of 7 identical notebooks is NGN 2,100. At the same rate, what is the cost of 12 notebooks?', 'NGN 2,700', 'NGN 3,000', 'NGN 3,300', 'NGN 3,600', 'D', 'One notebook costs NGN 300, so 12 cost NGN 3,600.'
	UNION ALL SELECT 'waec', 'mathematics', 29, 'A map scale is 1 cm to 5 km. Two towns are 7.4 cm apart on the map. What is the actual distance?', '12.4 km', '25 km', '35 km', '37 km', 'D', 'Multiply the map distance by 5 km per centimetre: 7.4 x 5 = 37 km.'
	UNION ALL SELECT 'waec', 'mathematics', 30, 'Write 0.00056 in standard form.', '5.6 x 10^-3', '5.6 x 10^-4', '56 x 10^-5', '0.56 x 10^-3', 'B', 'Move the decimal four places right to get 5.6, so the power is 10^-4.'
	UNION ALL SELECT 'waec', 'mathematics', 31, 'Solve x^2 - 9 = 0 and choose the pair of roots.', 'x = 3 only', 'x = -3 only', 'x = 3 or x = -3', 'x = 9 or x = -9', 'C', 'x^2 = 9 has two roots, 3 and -3.'
	UNION ALL SELECT 'waec', 'mathematics', 32, 'A data set has values 1, 2 and 3 with frequencies 2, 3 and 5 respectively. What is the mean?', '2', '2.3', '2.5', '3', 'B', 'The weighted total is 1x2 + 2x3 + 3x5 = 23; total frequency is 10, giving 2.3.'
	UNION ALL SELECT 'waec', 'mathematics', 33, 'A sum of NGN 9,000 is shared in the ratio 2:3:4. What is the largest share?', 'NGN 2,000', 'NGN 3,000', 'NGN 4,000', 'NGN 5,000', 'C', 'There are 9 parts, each worth NGN 1,000; the largest share is 4 parts.'
	UNION ALL SELECT 'waec', 'mathematics', 34, 'A lesson begins at 10:35 and lasts 1 hour 48 minutes. At what time does it end?', '12:13', '12:23', '12:33', '12:43', 'B', 'Adding 1 hour gives 11:35; adding 48 minutes gives 12:23.'
	UNION ALL SELECT 'waec', 'mathematics', 35, 'A cylinder has radius 3 cm and height 10 cm. Using pi = 22/7, find its volume to 1 decimal place.', '94.3 cm^3', '188.6 cm^3', '282.9 cm^3', '314.2 cm^3', 'C', 'Volume is pi r^2 h = (22/7) x 9 x 10, approximately 282.9 cm^3.'
	UNION ALL SELECT 'waec', 'mathematics', 36, 'Find the gradient of a line perpendicular to a line with gradient 2.', '-2', '-1/2', '1/2', '2', 'B', 'Perpendicular non-vertical lines have gradients whose product is -1; the required gradient is -1/2.'
	UNION ALL SELECT 'waec', 'mathematics', 37, 'A phone valued at NGN 80,000 loses 10 percent of its value in one year. What is its value after that year?', 'NGN 70,000', 'NGN 72,000', 'NGN 74,000', 'NGN 78,000', 'B', 'Ten percent is NGN 8,000; subtracting this from NGN 80,000 gives NGN 72,000.'
	UNION ALL SELECT 'waec', 'mathematics', 38, 'Solve the inequality 3x + 2 < 14 for x.', 'x < 4', 'x > 4', 'x < 5', 'x > 5', 'A', 'Subtract 2 to get 3x < 12, then divide by 3 to get x < 4.'
	UNION ALL SELECT 'waec', 'mathematics', 39, 'In a right-angled triangle, the side opposite angle theta is 6 cm and the hypotenuse is 10 cm. Find sin theta.', '3/5', '4/5', '5/3', '5/4', 'A', 'Sine is opposite divided by hypotenuse: 6/10 = 3/5.'
	UNION ALL SELECT 'waec', 'mathematics', 40, 'If log base 10 of x equals 2, what is x?', '10', '20', '100', '1,000', 'C', 'log10(x) = 2 means x = 10^2 = 100.'
	UNION ALL SELECT 'jamb', 'english', 11, 'Choose the word nearest in meaning to "diligent" in the sentence: The diligent student revised every evening.', 'Careless', 'Hard-working', 'Impatient', 'Uncertain', 'B', 'Diligent means showing steady, careful effort.'
	UNION ALL SELECT 'jamb', 'english', 12, 'Choose the word opposite in meaning to "expand" as used in: The committee plans to expand the programme.', 'Enlarge', 'Extend', 'Reduce', 'Develop', 'C', 'Reduce is the opposite of expand.'
	UNION ALL SELECT 'jamb', 'english', 13, 'Select the correct completion: Each of the players ___ received a medal.', 'have', 'has', 'are', 'were', 'B', 'The subject "each" is singular and takes "has".'
	UNION ALL SELECT 'jamb', 'english', 14, 'Choose the correct completion: The information ___ useful to the new students.', 'are', 'were', 'is', 'have been', 'C', 'Information is an uncountable singular noun and takes "is".'
	UNION ALL SELECT 'jamb', 'english', 15, 'Which sentence uses the apostrophe correctly to show that the books belong to several teachers?', 'The teachers book are new.', 'The teacher''s books are new.', 'The teachers'' books are new.', 'The teachers''s books are new.', 'C', 'The plural possessive is formed by placing the apostrophe after the plural noun.'
	UNION ALL SELECT 'jamb', 'english', 16, 'Choose the sentence with correct subject-verb agreement.', 'The list of items are on the desk.', 'The list of items is on the desk.', 'The list of items were on the desk.', 'The list of items have been on the desk.', 'B', 'The head noun is "list", which is singular; the phrase "of items" does not change it.'
	UNION ALL SELECT 'jamb', 'english', 17, 'In the sentence "Because the road was flooded, the team arrived late," what does the opening clause express?', 'A contrast', 'A reason', 'A comparison', 'A purpose', 'B', 'The flooded road is the reason the team arrived late.'
	UNION ALL SELECT 'jamb', 'english', 18, 'Select the correctly spelt word.', 'Seperate', 'Separate', 'Seperete', 'Sepparate', 'B', 'The standard spelling is separate.'
	UNION ALL SELECT 'jamb', 'english', 19, 'Choose the word that best completes the sentence: The witness gave a ___ account of the event.', 'reliable', 'rely', 'reliably', 'reliability', 'A', 'An adjective is required before the noun "account"; "reliable" fits.'
	UNION ALL SELECT 'jamb', 'english', 20, 'Choose the correct reported form: "We will return tomorrow," the visitors said.', 'The visitors said they will return tomorrow.', 'The visitors said that they would return the next day.', 'The visitors say they returned tomorrow.', 'The visitors said that we return the next day.', 'B', 'Reported future shifts from will to would, and tomorrow becomes the next day.'
	UNION ALL SELECT 'jamb', 'english', 21, 'Which option is a complete sentence?', 'Although the rain stopped.', 'When the bus reached the station.', 'The candidates waited outside the hall.', 'After the long examination.', 'C', 'This option contains a subject and a complete predicate without a dependent clause.'
	UNION ALL SELECT 'jamb', 'english', 22, 'Choose the correct preposition: The principal congratulated the team ___ its performance.', 'for', 'with', 'at', 'by', 'A', 'The standard collocation is congratulate someone for an achievement.'
	UNION ALL SELECT 'jamb', 'english', 23, 'In "The evidence was compelling," what does "compelling" mean?', 'Difficult to find', 'Strong and convincing', 'Unrelated to the claim', 'Based on a guess', 'B', 'Compelling evidence is strong enough to persuade.'
	UNION ALL SELECT 'jamb', 'english', 24, 'Select the correctly punctuated direct speech.', 'Amina said "I have finished".', 'Amina said, "I have finished."', 'Amina said "I have finished.",', 'Amina, said, "I have finished".', 'B', 'A comma introduces the quotation and the full stop is placed inside the closing quotation mark.'
	UNION ALL SELECT 'jamb', 'english', 25, 'Choose the appropriate conjunction: The class practised daily, ___ its accuracy improved.', 'so', 'unless', 'although', 'whereas', 'A', '"So" introduces the result of practising daily.'
	UNION ALL SELECT 'jamb', 'english', 26, 'Which sentence uses "fewer" correctly?', 'There is fewer water in the tank.', 'The class made fewer errors this week.', 'She has fewer patience today.', 'We need fewer information.', 'B', 'Fewer is used with countable plural nouns such as errors.'
	UNION ALL SELECT 'jamb', 'english', 27, 'Choose the correct completion: If Chidi ___ earlier, he would have caught the first bus.', 'leaves', 'left', 'had left', 'will leave', 'C', 'The third conditional uses if + past perfect and would have + past participle.'
	UNION ALL SELECT 'jamb', 'english', 28, 'Choose the correct comparative form of "careful".', 'Carefuller', 'More careful', 'Most careful', 'Carefulest', 'B', 'For this multi-syllable adjective, the comparative is "more careful".'
	UNION ALL SELECT 'jamb', 'english', 29, 'What is the function of the semicolon in "The first trial failed; the second succeeded"?', 'It joins two closely related independent clauses.', 'It introduces a list.', 'It marks a question.', 'It replaces an apostrophe.', 'A', 'A semicolon can join two independent clauses that are closely connected in meaning.'
	UNION ALL SELECT 'jamb', 'english', 30, 'Choose the word nearest in meaning to "reluctant".', 'Unwilling', 'Excited', 'Certain', 'Carefree', 'A', 'Reluctant means hesitant or unwilling.'
	UNION ALL SELECT 'jamb', 'english', 31, 'Select the correct completion: The news ___ spread quickly through the town.', 'have', 'has', 'are', 'were', 'B', 'News is grammatically singular and takes "has".'
	UNION ALL SELECT 'jamb', 'english', 32, 'Which sentence is in the active voice?', 'The report was prepared by the group.', 'The group prepared the report.', 'The report is being prepared.', 'The report had been prepared.', 'B', 'The subject "group" performs the action in the active sentence.'
	UNION ALL SELECT 'jamb', 'english', 33, 'Choose the correctly spelt word.', 'Pronunciation', 'Pronounciation', 'Pronuncation', 'Pronounciaton', 'A', 'The correct spelling is pronunciation.'
	UNION ALL SELECT 'jamb', 'english', 34, 'Choose the correct completion: The students, together with their tutor, ___ attending the workshop.', 'is', 'was', 'are', 'has', 'C', 'The main subject "students" is plural; the intervening phrase does not change agreement.'
	UNION ALL SELECT 'jamb', 'english', 35, 'In "The manager postponed the meeting because the report was incomplete," which clause gives the reason?', 'The manager postponed the meeting.', 'The report was incomplete.', 'The meeting was postponed.', 'The manager was incomplete.', 'B', 'The clause introduced by "because" states the reason for postponement.'
	UNION ALL SELECT 'jamb', 'english', 36, 'Choose the appropriate word: The twins were born ___ the same day.', 'in', 'on', 'at', 'by', 'B', '"On" is used with a specific day or date.'
	UNION ALL SELECT 'jamb', 'english', 37, 'Choose the word nearest in meaning to "obstacle".', 'Advantage', 'Barrier', 'Reward', 'Direction', 'B', 'An obstacle is something that blocks progress, a barrier.'
	UNION ALL SELECT 'jamb', 'english', 38, 'Which sentence correctly uses "their"?', 'The school changed their timetable.', 'The students placed their books on the shelf.', 'Their going to the library now.', 'The book is over their.', 'B', '"Their" is a possessive determiner referring to the students.'
	UNION ALL SELECT 'jamb', 'english', 39, 'Choose the correct completion: No sooner had the bell rung ___ the candidates entered the hall.', 'when', 'than', 'while', 'then', 'B', 'The fixed construction is "no sooner ... than".'
	UNION ALL SELECT 'jamb', 'english', 40, 'Choose the word opposite in meaning to "transparent" when describing a decision process.', 'Clear', 'Open', 'Secretive', 'Simple', 'C', 'A secretive process is the opposite of a transparent one.'
	UNION ALL SELECT 'neco', 'biology', 11, 'A plant cell is placed in a concentrated salt solution and loses water. Which process explains the movement of water?', 'Diffusion', 'Osmosis', 'Active transport', 'Translocation', 'B', 'Water moves across a selectively permeable membrane from higher water potential to lower water potential by osmosis.'
	UNION ALL SELECT 'neco', 'biology', 12, 'Which tissue carries water and dissolved mineral salts from roots towards the leaves?', 'Phloem', 'Xylem', 'Epidermis', 'Cambium', 'B', 'Xylem vessels transport water and mineral salts from roots through the plant.'
	UNION ALL SELECT 'neco', 'biology', 13, 'Which organ produces bile, a substance that helps emulsify fats during digestion?', 'Pancreas', 'Liver', 'Stomach', 'Small intestine', 'B', 'The liver produces bile; the gall bladder stores and releases it.'
	UNION ALL SELECT 'neco', 'biology', 14, 'A heterozygous tall plant is crossed with a dwarf plant. If tallness is dominant, what proportion of offspring is expected to be tall?', 'One quarter', 'One half', 'Three quarters', 'All offspring', 'B', 'Tt crossed with tt gives half Tt tall offspring and half tt dwarf offspring.'
	UNION ALL SELECT 'neco', 'biology', 15, 'Which part of a flower receives pollen grains during pollination?', 'Anther', 'Stigma', 'Ovary', 'Filament', 'B', 'The stigma is the receptive surface for pollen grains.'
	UNION ALL SELECT 'neco', 'biology', 16, 'Which process releases energy from glucose in the cells of animals?', 'Photosynthesis', 'Respiration', 'Transpiration', 'Excretion', 'B', 'Cellular respiration releases usable energy from glucose.'
	UNION ALL SELECT 'neco', 'biology', 17, 'Which blood vessel carries blood away from the heart?', 'Vein', 'Artery', 'Capillary', 'Venule', 'B', 'Arteries carry blood away from the heart, irrespective of oxygen content.'
	UNION ALL SELECT 'neco', 'biology', 18, 'A population of insects increases after its main predator becomes scarce. Which ecological relationship has changed?', 'Predation', 'Commensalism', 'Pollination', 'Parasitism', 'A', 'Reduced predation can allow prey numbers to rise.'
	UNION ALL SELECT 'neco', 'biology', 19, 'Which structure in the human respiratory system is the main site of gaseous exchange?', 'Trachea', 'Bronchus', 'Alveolus', 'Larynx', 'C', 'Thin-walled alveoli provide a large surface area for oxygen and carbon dioxide exchange.'
	UNION ALL SELECT 'neco', 'biology', 20, 'Which nutrient is the primary immediate source of energy in a balanced diet?', 'Carbohydrate', 'Vitamin', 'Mineral salt', 'Water', 'A', 'Carbohydrates are commonly broken down to provide energy for cellular activities.'
	UNION ALL SELECT 'neco', 'biology', 21, 'What is the role of chlorophyll in a green leaf?', 'Absorb light energy', 'Transport sugars', 'Store mineral salts', 'Open the stomata', 'A', 'Chlorophyll absorbs light energy used in photosynthesis.'
	UNION ALL SELECT 'neco', 'biology', 22, 'Which plant response causes a shoot to bend towards a source of light?', 'Geotropism', 'Phototropism', 'Hydrotropism', 'Thigmotropism', 'B', 'Phototropism is directional growth in response to light.'
	UNION ALL SELECT 'neco', 'biology', 23, 'Which cells in the pancreas release insulin into the blood?', 'Beta cells', 'Red blood cells', 'Nerve cells', 'Goblet cells', 'A', 'Pancreatic beta cells produce insulin, which helps regulate blood glucose.'
	UNION ALL SELECT 'neco', 'biology', 24, 'Which feature of a fish helps it maintain balance and control movement in water?', 'Swim bladder only', 'Fins', 'Gill rakers', 'Scales', 'B', 'Fins provide steering, stability, and propulsion.'
	UNION ALL SELECT 'neco', 'biology', 25, 'Which microorganism is commonly used in baking to make dough rise?', 'Yeast', 'Amoeba', 'Plasmodium', 'Rhizobium', 'A', 'Yeast ferments sugars and releases carbon dioxide, which expands dough.'
	UNION ALL SELECT 'neco', 'biology', 26, 'Which term describes animals that maintain a relatively constant internal body temperature?', 'Ectothermic', 'Endothermic', 'Anaerobic', 'Aquatic', 'B', 'Endothermic animals generate and regulate body heat internally.'
	UNION ALL SELECT 'neco', 'biology', 27, 'What is the main function of root hairs in a plant?', 'Absorb water and mineral ions', 'Make seeds', 'Carry sugars to flowers', 'Prevent pollination', 'A', 'Root hairs increase surface area for absorption from the soil.'
	UNION ALL SELECT 'neco', 'biology', 28, 'Which structure prevents food from entering the trachea during swallowing?', 'Epiglottis', 'Diaphragm', 'Uvula', 'Bronchiole', 'A', 'The epiglottis folds over the airway during swallowing.'
	UNION ALL SELECT 'neco', 'biology', 29, 'In a food chain, what is the original source of energy for most ecosystems?', 'The Sun', 'Soil minerals', 'Decomposers', 'Wind', 'A', 'Producers capture solar energy and make it available to other trophic levels.'
	UNION ALL SELECT 'neco', 'biology', 30, 'Which process removes metabolic waste products from an organism?', 'Nutrition', 'Excretion', 'Assimilation', 'Reproduction', 'B', 'Excretion removes waste products formed by metabolism.'
	UNION ALL SELECT 'neco', 'biology', 31, 'Which part of the nephron selectively reabsorbs useful substances from the filtrate?', 'Renal tubule', 'Ureter', 'Bladder', 'Renal pelvis', 'A', 'The renal tubule reabsorbs water, glucose, and ions according to the body''s needs.'
	UNION ALL SELECT 'neco', 'biology', 32, 'Which type of reproduction usually produces offspring genetically similar to one parent?', 'Sexual reproduction', 'Asexual reproduction', 'Cross-pollination', 'Fertilisation', 'B', 'Asexual reproduction involves one parent and usually produces genetically similar offspring.'
	UNION ALL SELECT 'neco', 'biology', 33, 'Which gas is taken in by green plants for photosynthesis?', 'Oxygen', 'Carbon dioxide', 'Nitrogen', 'Hydrogen', 'B', 'Carbon dioxide is a raw material for photosynthesis.'
	UNION ALL SELECT 'neco', 'biology', 34, 'Which type of joint allows movement in many directions at the shoulder?', 'Hinge joint', 'Ball-and-socket joint', 'Pivot joint', 'Fixed joint', 'B', 'The shoulder''s ball-and-socket joint permits movement through a wide range of directions.'
	UNION ALL SELECT 'neco', 'biology', 35, 'Why are antibiotics ineffective against influenza viruses?', 'Viruses have no cell-wall structures targeted by many antibiotics', 'Viruses are larger than bacteria', 'Viruses contain chlorophyll', 'Antibiotics only work in cold climates', 'A', 'Antibiotics target bacterial structures or processes; viruses reproduce inside host cells.'
	UNION ALL SELECT 'neco', 'biology', 36, 'Which human cell part contains most of the genetic material?', 'Nucleus', 'Cell membrane', 'Ribosome', 'Lysosome', 'A', 'The nucleus contains chromosomes made of DNA in most human cells.'
	UNION ALL SELECT 'neco', 'biology', 37, 'What is the name for the gradual change in inherited characteristics of populations over generations?', 'Ecological succession', 'Evolution', 'Homeostasis', 'Fermentation', 'B', 'Evolution describes inherited population changes across generations.'
	UNION ALL SELECT 'neco', 'biology', 38, 'Which condition is required for seeds to germinate?', 'Water, oxygen and suitable temperature', 'Continuous bright light only', 'Carbon dioxide and salt', 'Complete submersion in water', 'A', 'Most seeds require moisture, oxygen, and a suitable temperature to germinate.'
	UNION ALL SELECT 'neco', 'biology', 39, 'Which organism is a decomposer in a terrestrial ecosystem?', 'Mushroom', 'Hawk', 'Grass', 'Goat', 'A', 'Fungi such as mushrooms break down dead organic material.'
	UNION ALL SELECT 'neco', 'biology', 40, 'What is the main benefit of vaccination to a population?', 'It can increase immunity and reduce transmission', 'It replaces all hygiene practices', 'It treats every disease after symptoms start', 'It prevents organisms from mutating', 'A', 'Vaccination can build immunity and reduce the spread of vaccine-preventable diseases.'
) q ON q.exam_slug = t.slug AND q.subject_slug = s.slug
WHERE e.year = CASE t.slug WHEN 'waec' THEN 2023 WHEN 'jamb' THEN 2024 WHEN 'neco' THEN 2023 END
ON DUPLICATE KEY UPDATE question_text = VALUES(question_text), option_a = VALUES(option_a), option_b = VALUES(option_b), option_c = VALUES(option_c), option_d = VALUES(option_d), correct_option = VALUES(correct_option), explanation = VALUES(explanation), marks = VALUES(marks);

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2022, 'WAEC Physics 2022 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'physics'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2024, 'WAEC Government 2024 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'government'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2020, 'WAEC Economics 2020 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'economics'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2022, 'JAMB Biology 2022 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'biology'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2023, 'JAMB Physics 2023 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'physics'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2024, 'JAMB Chemistry 2024 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'chemistry'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2022, 'JAMB Government 2022 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'government'
WHERE t.slug = 'jamb'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2024, 'NECO Government 2024 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'government'
WHERE t.slug = 'neco'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2021, 'NECO Physics 2021 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'physics'
WHERE t.slug = 'neco'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2022, 'NECO Chemistry 2022 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'chemistry'
WHERE t.slug = 'neco'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2024, 'NECO Economics 2024 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'economics'
WHERE t.slug = 'neco'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_exams (exam_type_id, subject_id, year, title, duration_minutes, total_questions, is_active)
SELECT t.id, s.id, 2023, 'WAEC Literature 2023 Practice', 45, 40, 1
FROM cbt_exam_types t JOIN cbt_subjects s ON s.slug = 'literature'
WHERE t.slug = 'waec'
ON DUPLICATE KEY UPDATE title = VALUES(title), duration_minutes = VALUES(duration_minutes), total_questions = VALUES(total_questions), is_active = 1;

INSERT INTO cbt_questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, explanation, marks, sort_order)
SELECT e.id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option, q.explanation, 1.00, q.sort_order
FROM cbt_exams e
JOIN cbt_exam_types t ON t.id = e.exam_type_id
JOIN cbt_subjects s ON s.id = e.subject_id
JOIN (
    SELECT 'waec' exam_slug, 'physics' subject_slug, 1 sort_order, 'Which quantity is measured in newton-metres?', 'Energy', 'Momentum', 'Work', 'Power', 'C', 'Work is defined as force times distance, so its SI unit is the newton-metre.'
    UNION ALL SELECT 'waec', 'physics', 2, 'A body moving at constant speed in a circle is said to have:', 'Zero acceleration', 'Uniform velocity', 'Centripetal acceleration', 'No force', 'C', 'A change in direction means acceleration is present even when the speed is constant.'
    UNION ALL SELECT 'waec', 'physics', 3, 'What is the SI unit of current?', 'Volt', 'Ohm', 'Ampere', 'Watt', 'C', 'Current is measured in amperes.'
    UNION ALL SELECT 'waec', 'physics', 4, 'A force of 20 N acts on an object over a distance of 5 m. How much work is done?', '4 J', '25 J', '100 J', '400 J', 'C', 'Work = force x distance = 20 x 5 = 100 J.'
    UNION ALL SELECT 'jamb', 'chemistry', 1, 'Which gas is produced when a metal reacts with dilute acid?', 'Oxygen', 'Hydrogen', 'Nitrogen', 'Carbon dioxide', 'B', 'Many metals release hydrogen gas when they react with dilute acids.'
    UNION ALL SELECT 'jamb', 'chemistry', 2, 'The pH of a neutral solution is:', '0', '7', '10', '14', 'B', 'Neutral solutions have a pH of 7.'
    UNION ALL SELECT 'jamb', 'chemistry', 3, 'Which of the following is a covalent compound?', 'NaCl', 'H2O', 'MgO', 'KBr', 'B', 'Water is made of covalent bonding between hydrogen and oxygen.'
    UNION ALL SELECT 'neco', 'geography', 1, 'Which line of latitude divides the earth into Northern and Southern Hemispheres?', 'Prime Meridian', 'Tropic of Cancer', 'Equator', 'Arctic Circle', 'C', 'The Equator is 0° latitude and divides Earth into hemispheres.'
    UNION ALL SELECT 'neco', 'geography', 2, 'Which type of rock is formed from cooling molten magma?', 'Sedimentary', 'Metamorphic', 'Igneous', 'Organic', 'C', 'Igneous rocks form when magma or lava cools and solidifies.'
    UNION ALL SELECT 'neco', 'geography', 3, 'Which weather element is measured by a hygrometer?', 'Temperature', 'Humidity', 'Wind speed', 'Pressure', 'B', 'A hygrometer measures moisture content in the air.'
    UNION ALL SELECT 'waec', 'government', 1, 'A constitution is primarily meant to:', 'Set taxation rules only', 'Provide the framework of government', 'Control all political parties', 'Limit public voting', 'B', 'The constitution defines how a state is governed and how powers are shared.'
    UNION ALL SELECT 'waec', 'government', 2, 'The principle of separation of powers is associated with:', 'Karl Marx', 'Montesquieu', 'Mao Zedong', 'Machiavelli', 'B', 'Montesquieu promoted the separation of governmental powers.'
    UNION ALL SELECT 'waec', 'government', 3, 'A democracy is a system in which:', 'The military rules the state', 'The people choose their leaders', 'Only the elite can vote', 'The court makes all laws', 'B', 'Democracy is government by the people, usually through elections.'
    UNION ALL SELECT 'waec', 'economics', 1, 'Scarcity means that resources are:', 'Unlimited', 'Very expensive', 'Limited in relation to demand', 'Always enough', 'C', 'Scarcity occurs when resources are insufficient for all wants.'
    UNION ALL SELECT 'waec', 'economics', 2, 'Demand is best described as the:', 'Willingness and ability to buy at various prices', 'Amount of goods produced', 'Cost of labour', 'Total exports', 'A', 'Demand is the quantity consumers are willing and able to buy at different prices.'
    UNION ALL SELECT 'waec', 'economics', 3, 'Inflation usually causes:', 'The purchasing power of money to fall', 'Average wages to rise uniformly', 'Exports to end', 'Unemployment to vanish', 'A', 'Inflation reduces the value of money over time.'
    UNION ALL SELECT 'waec', 'literature', 1, 'A poem with fourteen lines is usually called a:', 'Ballad', 'Sonnet', 'Epic', 'Ode', 'B', 'A sonnet is traditionally a poem of fourteen lines.'
    UNION ALL SELECT 'waec', 'literature', 2, 'The central idea or message in a literary work is called the:', 'Theme', 'Plot', 'Setting', 'Genre', 'A', 'Theme is the underlying message or idea.'
    UNION ALL SELECT 'waec', 'literature', 3, 'Which of these is a figure of speech?', 'Clause', 'Metaphor', 'Verb', 'Noun', 'B', 'Metaphor is a figure of speech used for comparison without like or as.'
    UNION ALL SELECT 'jamb', 'physics', 1, 'The force that resists the motion of one surface over another is called:', 'Tension', 'Friction', 'Gravity', 'Elasticity', 'B', 'Friction opposes relative motion between surfaces in contact.'
    UNION ALL SELECT 'jamb', 'biology', 1, 'Which part of the plant cell gives it a rigid shape?', 'Cell membrane', 'Cell wall', 'Nucleus', 'Chloroplast', 'B', 'The cell wall provides support and rigidity in plant cells.'
    UNION ALL SELECT 'jamb', 'biology', 2, 'Which blood cells help the body fight disease?', 'Red blood cells', 'White blood cells', 'Platelets', 'Plasma cells', 'B', 'White blood cells are central to the immune response.'
    UNION ALL SELECT 'jamb', 'economics', 1, 'A fall in the price of a good usually leads to:', 'Lower quantity demanded', 'Higher quantity demanded', 'No change in demand', 'Higher production cost', 'B', 'Demand usually rises when the price falls, all else equal.'
    UNION ALL SELECT 'jamb', 'economics', 2, 'A market is best described as a place where:', 'Only goods are produced', 'Buyers and sellers meet to exchange goods or services', 'Governments set all prices', 'Only services are traded', 'B', 'A market is any arrangement where buyers and sellers exchange items.'
    UNION ALL SELECT 'neco', 'economics', 1, 'What is a budget?', 'A plan of income and expenditure', 'An interest rate', 'A bank loan', 'A tax rate', 'A', 'A budget outlines expected income and spending.'
    UNION ALL SELECT 'neco', 'economics', 2, 'Which of these is a direct tax?', 'Customs duty', 'Value-added tax', 'Company income tax', 'Import levy', 'C', 'Company income tax is a direct tax on profits or income.'
    UNION ALL SELECT 'neco', 'geography', 3, 'Which of these is a renewable natural resource?', 'Coal', 'Petroleum', 'Forests', 'Natural gas', 'C', 'Forests can regenerate through sustainable management.'
    UNION ALL SELECT 'neco', 'government', 1, 'In a federal system, power is shared between:', 'The church and the state', 'The central and state governments', 'The judiciary and military', 'The legislature and media', 'B', 'Federalism divides power between national and subnational governments.'
    UNION ALL SELECT 'waec', 'physics', 5, 'What happens to the resistance of a conductor when its temperature increases?', 'It decreases', 'It stays constant', 'It increases', 'It becomes zero', 'C', 'Most metallic conductors increase in resistance as temperature rises.'
    UNION ALL SELECT 'waec', 'physics', 6, 'The image formed by a plane mirror is always:', 'Real and inverted', 'Virtual and laterally inverted', 'Real and upright', 'Virtual and magnified', 'B', 'Plane mirrors produce virtual, upright, laterally inverted images.'
    UNION ALL SELECT 'waec', 'government', 4, 'A political party is primarily meant to:', 'Offer legal advice', 'Win elections and influence government', 'Manage public schools', 'Control foreign trade', 'B', 'Political parties seek political power and public policy influence.'
    UNION ALL SELECT 'waec', 'economics', 4, 'Opportunity cost is the value of the:', 'Best alternative forgone', 'Total output produced', 'Cost of labor', 'Government subsidy', 'A', 'Opportunity cost is the next best option that is sacrificed.'
    UNION ALL SELECT 'waec', 'literature', 4, 'A narrative poem that tells a story is called a:', 'Ballad', 'Limerick', 'Sonnet', 'Elegy', 'A', 'Ballads are narrative poems often set to music.'
    UNION ALL SELECT 'waec', 'literature', 5, 'The voice or perspective from which a story is told is known as the:', 'Tone', 'Mood', 'Narrator', 'Setting', 'C', 'Narrator refers to the storyteller or point of view.'
    UNION ALL SELECT 'jamb', 'chemistry', 4, 'Which gas is most abundant in the atmosphere?', 'Oxygen', 'Carbon dioxide', 'Nitrogen', 'Hydrogen', 'C', 'Nitrogen makes up the largest proportion of the atmosphere.'
    UNION ALL SELECT 'jamb', 'biology', 3, 'Which process converts light energy into chemical energy in plants?', 'Respiration', 'Digestion', 'Photosynthesis', 'Excretion', 'C', 'Photosynthesis stores energy in carbohydrates using sunlight.'
    UNION ALL SELECT 'jamb', 'physics', 2, 'The unit of force is the:', 'Kilogram', 'Newton', 'Joule', 'Ampere', 'B', 'Force is measured in newtons.'
    UNION ALL SELECT 'jamb', 'physics', 3, 'What type of energy does an object possess because of its position?', 'Kinetic energy', 'Potential energy', 'Thermal energy', 'Sound energy', 'B', 'Potential energy depends on height or position relative to a reference point.'
    UNION ALL SELECT 'neco', 'government', 2, 'The legislature is primarily responsible for:', 'Enforcing laws', 'Making laws', 'Judging disputes', 'Running hospitals', 'B', 'The legislature creates and amends laws.'
    UNION ALL SELECT 'neco', 'government', 3, 'Which institution interprets the constitution?', 'Executive', 'Legislature', 'Judiciary', 'Police', 'C', 'The judiciary interprets laws and constitutional matters.'
    UNION ALL SELECT 'neco', 'geography', 4, 'A map scale of 1:50,000 means:', '1 cm on map equals 50,000 km on ground', '1 cm on map equals 500 m on ground', '1 cm on map equals 50,000 cm on ground', '1 cm on map equals 50,000 m on ground', 'C', 'A ratio scale of 1:50,000 means 1 unit on the map equals 50,000 of the same units on the ground.'
    UNION ALL SELECT 'neco', 'geography', 5, 'The greatest cause of soil erosion in many regions is:', 'Wind and water action', 'Sunshine alone', 'Building roads', 'No rainfall', 'A', 'Wind and flowing water are major natural agents of soil erosion.'
    UNION ALL SELECT 'waec', 'physics', 7, 'Light bends when it passes from one medium to another because of:', 'Reflection', 'Dispersion', 'Refraction', 'Diffraction', 'C', 'Refraction is the bending of light due to a change in speed.'
    UNION ALL SELECT 'waec', 'government', 5, 'The rule of law means that:', 'The government is above the law', 'Everyone is subject to the law', 'Only citizens obey the law', 'Courts do not matter', 'B', 'Rule of law means no one is above the law.'
    UNION ALL SELECT 'waec', 'economics', 5, 'What is a monopoly?', 'Many firms selling identical goods', 'Single seller controlling a market', 'A regulated market', 'Government-owned market only', 'B', 'A monopoly arises when one seller dominates supply in a market.'
    UNION ALL SELECT 'jamb', 'chemistry', 5, 'An atom is electrically neutral when:', 'It has more electrons than protons', 'It has more protons than electrons', 'The number of protons equals the number of electrons', 'The nucleus contains all the charges', 'C', 'In a neutral atom, the positive and negative charges balance.'
    UNION ALL SELECT 'neco', 'economics', 3, 'Which of the following is usually a feature of a developing economy?', 'High productivity', 'Low dependency ratio', 'Low literacy rate', 'Balance of payments surplus', 'C', 'Low literacy and poor infrastructure often characterize less-developed economies.'
    UNION ALL SELECT 'waec', 'literature', 6, 'The use of hints or clues about events yet to happen is called:', 'Foreshadowing', 'Dialogue', 'Soliloquy', 'Irony', 'A', 'Foreshadowing hints at future events.'
    UNION ALL SELECT 'waec', 'literature', 7, 'When a character speaks thoughts aloud alone, this is called:', 'Dialogue', 'Monologue', 'Soliloquy', 'Narration', 'C', 'A soliloquy is a speech made alone to reveal thoughts.'
    UNION ALL SELECT 'waec', 'government', 6, 'Which arm of government interprets laws?', 'Legislature', 'Executive', 'Judiciary', 'Military', 'C', 'The judiciary interprets laws and resolves disputes.'
    UNION ALL SELECT 'waec', 'economics', 6, 'The demand curve usually slopes:', 'Upward from left to right', 'Downward from left to right', 'Horizontally', 'Vertically', 'B', 'Demand generally slopes downward because lower prices attract more quantity demanded.'
    UNION ALL SELECT 'jamb', 'physics', 4, 'Which quantity is measured in watts?', 'Force', 'Pressure', 'Power', 'Momentum', 'C', 'Power is measured in watts.'
    UNION ALL SELECT 'jamb', 'chemistry', 6, 'Which statement is correct about acids?', 'They taste sweet', 'They turn blue litmus red', 'They are always neutral', 'They do not react with metals', 'B', 'Acids turn blue litmus paper red.'
    UNION ALL SELECT 'neco', 'government', 4, 'A citizen can participate in governance by:', 'Voting in elections', 'Ignoring laws', 'Refusing to pay taxes', 'Protesting only without voting', 'A', 'Voting is a key civic duty in democratic systems.'
    UNION ALL SELECT 'neco', 'geography', 6, 'A river that branches into many smaller channels in its lower course is called a:', 'Delta', 'Meander', 'Source', 'Tributary', 'A', 'A delta forms when a river splits into multiple channels near the sea.'
    UNION ALL SELECT 'waec', 'physics', 8, 'Which instrument is used to measure electric current?', 'Voltmeter', 'Ammeter', 'Barometer', 'Thermometer', 'B', 'An ammeter measures electric current.'
    UNION ALL SELECT 'jamb', 'economics', 3, 'What does GDP measure?', 'Government spending only', 'A country''s total output of goods and services', 'The number of banks', 'Tax revenue only', 'B', 'Gross Domestic Product estimates total output produced within a country.'
    UNION ALL SELECT 'waec', 'literature', 8, 'A story that teaches a moral lesson is often described as:', 'A tragedy', 'A fable', 'A satire', 'An ode', 'B', 'Fables often teach moral lessons through animals or simple characters.'
    UNION ALL SELECT 'waec', 'literature', 9, 'The arrangement of events in a story is called the:', 'Theme', 'Plot', 'Mood', 'Tone', 'B', 'Plot refers to the sequence of events.'
    UNION ALL SELECT 'waec', 'literature', 10, 'Which literary device repeats the same sound at the beginning of words?', 'Alliteration', 'Metaphor', 'Irony', 'Symbolism', 'A', 'Alliteration repeats initial consonant sounds.'
    UNION ALL SELECT 'waec', 'government', 7, 'Which of these is a function of the executive?', 'Making laws', 'Enforcing laws', 'Trying cases', 'Approving budgets only', 'B', 'The executive implements and enforces laws.'
    UNION ALL SELECT 'waec', 'economics', 7, 'An increase in supply usually causes the supply curve to shift:', 'Leftward', 'Rightward', 'Downward', 'Upward vertically', 'B', 'More supply at every price shifts the supply curve rightward.'
    UNION ALL SELECT 'jamb', 'chemistry', 7, 'Which of the following is a metal?', 'Sulphur', 'Carbon', 'Iron', 'Chlorine', 'C', 'Iron is a metal.'
    UNION ALL SELECT 'jamb', 'chemistry', 8, 'Which particle determines the identity of an element?', 'Electron', 'Neutron', 'Proton', 'Photon', 'C', 'The number of protons identifies an element.'
    UNION ALL SELECT 'waec', 'physics', 9, 'The ability of a body to return to its original shape after force is removed is called:', 'Elasticity', 'Plasticity', 'Inertia', 'Density', 'A', 'Elasticity describes recovery after deformation.'
    UNION ALL SELECT 'waec', 'physics', 10, 'Which type of lens is used to correct long-sightedness?', 'Concave lens', 'Convex lens', 'Flat lens', 'Prism glass', 'B', 'Convex lenses help correct hyperopia or long-sightedness.'
    UNION ALL SELECT 'waec', 'government', 8, 'A constitution can be changed by:', 'A referendum or legislative process', 'Changing the national anthem only', 'Removing all parties', 'Ignoring public opinion', 'A', 'Constitutions are amended through formal legal processes.'
    UNION ALL SELECT 'waec', 'economics', 8, 'Which factor does NOT directly affect demand?', 'Consumer income', 'Prices of related goods', 'Technology used in production', 'Tastes and preferences', 'C', 'Technology is more directly a supply-side factor.'
    UNION ALL SELECT 'jamb', 'physics', 5, 'If a car travels 60 km in 2 hours, what is its average speed?', '20 km/h', '30 km/h', '40 km/h', '60 km/h', 'B', 'Average speed = distance / time = 60/2 = 30 km/h.'
    UNION ALL SELECT 'jamb', 'biology', 4, 'What is the function of the xylem in plants?', 'Transport water and minerals', 'Make food', 'Store starch', 'Absorb sunlight', 'A', 'Xylem transports water and mineral salts from roots to other parts.'
    UNION ALL SELECT 'neco', 'government', 5, 'A ruling government is expected to be accountable to the:', 'Public', 'Army alone', 'Private firms only', 'Foreign investors only', 'A', 'Public accountability is essential in a responsive government.'
    UNION ALL SELECT 'neco', 'geography', 7, 'Which factor most influences the climate of a place near the Equator?', 'Altitude only', 'Latitude', 'Distance from the sea only', 'Time zone', 'B', 'Latitude strongly affects solar energy and climate.'
    UNION ALL SELECT 'waec', 'literature', 11, 'The mood created by a gloomy description is usually:', 'Humorous', 'Cheerful', 'Melancholic', 'Excited', 'C', 'Melancholic mood is associated with sadness or gloom.'
    UNION ALL SELECT 'waec', 'literature', 12, 'The comparison between two unlike things using like or as is called:', 'Metaphor', 'Simile', 'Irony', 'Hyperbole', 'B', 'Simile uses like or as to compare different things.'
    UNION ALL SELECT 'waec', 'government', 9, 'The electoral commission is usually responsible for:', 'Judging criminal cases', 'Conducting elections', 'Appointing judges', 'Enforcing tax laws', 'B', 'Electoral commissions manage fair electoral processes.'
    UNION ALL SELECT 'waec', 'economics', 9, 'A rise in income tax can reduce:', 'Disposable income', 'Productivity', 'Foreign trade', 'Savings', 'A', 'Higher income tax reduces the amount of income available to spend or save.'
    UNION ALL SELECT 'waec', 'government', 10, 'Which is a major feature of a representative democracy?', 'Citizens vote for representatives', 'The ruler is chosen by birth', 'Only the police vote', 'All laws are made by judges', 'A', 'Representative democracy elects people to govern on behalf of citizens.'
    UNION ALL SELECT 'waec', 'economics', 10, 'GDP per capita is most useful for comparing:', 'Average living standards between countries', 'The exchange rate only', 'Inflation alone', 'The total land area', 'A', 'GDP per capita helps compare approximate average output per person.'
    UNION ALL SELECT 'jamb', 'chemistry', 9, 'Which of the following is an example of a base?', 'Citric acid', 'Sodium hydroxide', 'Vinegar', 'Lemon juice', 'B', 'Sodium hydroxide is a strong alkali or base.'
    UNION ALL SELECT 'jamb', 'chemistry', 10, 'What is the main function of catalysts in chemistry?', 'To increase the amount of reactants', 'To speed up reactions without being used up', 'To change the products completely', 'To stop all reactions', 'B', 'Catalysts lower activation energy and accelerate reactions without being consumed.'
    UNION ALL SELECT 'neco', 'geography', 8, 'What is a weather station used for?', 'To measure and record weather data', 'To mine minerals', 'To build roads', 'To sell crops', 'A', 'Weather stations collect atmospheric data like temperature and rainfall.'
    UNION ALL SELECT 'waec', 'physics', 11, 'An object is said to be in equilibrium when:', 'It accelerates steadily', 'The net force is zero', 'Its speed is increasing', 'It is moving only upward', 'B', 'Equilibrium means no net force or net torque.'
    UNION ALL SELECT 'waec', 'economics', 11, 'The main function of money is to serve as a:', 'Store of value and medium of exchange', 'Tax system', 'Production factor', 'Trade restriction', 'A', 'Money facilitates exchange and stores purchasing power.'
    UNION ALL SELECT 'waec', 'government', 11, 'Citizenship of a country may be acquired by:', 'Birth or naturalization', 'Buying a passport only', 'Refusal to pay taxes', 'Ignoring electoral laws', 'A', 'Citizenship can come through birth, descent, or legal naturalization.'
    UNION ALL SELECT 'jamb', 'chemistry', 11, 'Which property is typical of metals?', 'Poor conductors of heat', 'Dull appearance', 'Good electrical conductivity', 'Low density', 'C', 'Metals are generally good conductors of electricity and heat.'
    UNION ALL SELECT 'jamb', 'biology', 5, 'Which structure transports food in plants?', 'Xylem', 'Phloem', 'Sclerenchyma', 'Root hair', 'B', 'Phloem transports sugars produced during photosynthesis.'
    UNION ALL SELECT 'waec', 'literature', 13, 'A poem expressing grief for the dead is an:', 'Ballad', 'Elegy', 'Epic', 'Limerick', 'B', 'An elegy is a poem of mourning.'
    UNION ALL SELECT 'waec', 'literature', 14, 'The overall emotional atmosphere of a text is its:', 'Theme', 'Tone', 'Mood', 'Plot', 'C', 'Mood is the emotional atmosphere created for the reader.'
    UNION ALL SELECT 'jamb', 'physics', 6, 'Energy cannot be created or destroyed; it can only be:', 'Changed from one form to another', 'Turned into matter completely', 'Removed from the universe', 'Stopped permanently', 'A', 'This is the principle of conservation of energy.'
    UNION ALL SELECT 'waec', 'government', 12, 'Participation in governance is important because it ensures:', 'One person rules forever', 'Citizens can influence decisions', 'The army chooses all leaders', 'Laws are ignored', 'B', 'Citizen participation ensures accountability and legitimacy.'
    UNION ALL SELECT 'waec', 'economics', 12, 'Which of these is a public good?', 'A private house', 'A school textbook', 'National defence', 'A car', 'C', 'National defence is a classic example of a public good.'
    UNION ALL SELECT 'waec', 'physics', 12, 'Which unit is used to measure pressure?', 'Newton', 'Joule', 'Pascal', 'Watt', 'C', 'Pressure is measured in pascals.'
    UNION ALL SELECT 'waec', 'government', 13, 'What is civic responsibility?', 'The duties citizens owe to the state', 'The right to avoid taxes', 'A law on courts only', 'Only voting once', 'A', 'Citizenship includes duties such as obeying laws and paying taxes.'
    UNION ALL SELECT 'waec', 'economics', 13, 'A business aims to maximize:', 'Profit', 'Losses', 'Taxes', 'Imports', 'A', 'Profit maximization is a common objective of business.'
    UNION ALL SELECT 'waec', 'literature', 15, 'The use of exaggeration for emphasis is:', 'Hyperbole', 'Irony', 'Personification', 'Metaphor', 'A', 'Hyperbole is deliberate exaggeration.'
    UNION ALL SELECT 'neco', 'geography', 9, 'A place with high rainfall and dense forest is most likely to be in a:', 'Desert zone', 'Tropical zone', 'Polar zone', 'Temperate grassland', 'B', 'Tropical zones often have high rainfall and lush forests.'
    UNION ALL SELECT 'neco', 'government', 6, 'Which body usually conducts elections?', 'Judiciary', 'Electoral commission', 'Police force', 'National assembly only', 'B', 'Electoral commissions supervise election management.'
    UNION ALL SELECT 'jamb', 'chemistry', 12, 'Which element is used to make stainless steel?', 'Copper', 'Iron', 'Silver', 'Gold', 'B', 'Iron is alloyed with other elements to form stainless steel.'
    UNION ALL SELECT 'jamb', 'biology', 6, 'Which process removes excess water from the body in animals?', 'Digestion', 'Excretion', 'Respiration', 'Reproduction', 'B', 'Excretion removes waste materials and excess water.'
    UNION ALL SELECT 'waec', 'physics', 13, 'When a current flows through a resistor, electrical energy is converted to:', 'Light only', 'Heat only', 'Chemical energy', 'Sound energy', 'B', 'Resistance converts electrical energy to heat.'
    UNION ALL SELECT 'waec', 'economics', 14, 'A country with a budget deficit spends:', 'More than it earns', 'Exactly its income', 'Less than it earns', 'Nothing', 'A', 'A deficit exists when expenditure exceeds revenue.'
    UNION ALL SELECT 'waec', 'government', 14, 'Which of the following is a major requirement for democratic governance?', 'Free and fair elections', 'Military control', 'No civic participation', 'One-party rule', 'A', 'Free and fair elections are fundamental to democracy.'
    UNION ALL SELECT 'waec', 'literature', 16, 'Which of these is a literary genre?', 'Noun', 'Poetry', 'Conjunction', 'Preposition', 'B', 'Poetry is a literary genre.'
    UNION ALL SELECT 'waec', 'economics', 15, 'A consumer surplus arises when:', 'The price paid is below willingness to pay', 'The price paid exceeds willingness to pay', 'Supply is zero', 'Demand is zero', 'A', 'Consumer surplus is the gap between what a buyer is willing to pay and what they actually pay.'
    UNION ALL SELECT 'waec', 'government', 15, 'A citizen should support the state by:', 'Paying taxes and obeying laws', 'Avoiding elections', 'Resisting all authority', 'Refusing community service', 'A', 'Good citizenship includes lawful participation and tax obligations.'
    UNION ALL SELECT 'jamb', 'physics', 7, 'A machine can increase:', 'Work done', 'Force exerted', 'Energy input', 'Power output', 'B', 'Machines can trade force for distance or speed, but not create energy.'
    UNION ALL SELECT 'jamb', 'biology', 7, 'Which organ filters blood in mammals?', 'Liver', 'Kidney', 'Lung', 'Heart', 'B', 'Kidneys filter blood and form urine.'
    UNION ALL SELECT 'waec', 'literature', 17, 'Which device gives human qualities to non-human things?', 'Personification', 'Alliteration', 'Metaphor', 'Onomatopoeia', 'A', 'Personification attributes human traits to non-human objects.'
    UNION ALL SELECT 'waec', 'literature', 18, 'The writer''s attitude toward the subject is the:', 'Theme', 'Tone', 'Plot', 'Character', 'B', 'Tone reflects the author''s attitude.'
    UNION ALL SELECT 'jamb', 'chemistry', 13, 'Which process is used to separate insoluble solids from liquids?', 'Distillation', 'Filtration', 'Evaporation', 'Sublimation', 'B', 'Filtration separates undissolved solids from a liquid.'
    UNION ALL SELECT 'jamb', 'economics', 4, 'Which of these is an example of a fixed cost?', 'Raw material cost', 'Wages based on output', 'Rent', 'Transport cost', 'C', 'Rent is often fixed over a period even if output changes.'
    UNION ALL SELECT 'waec', 'physics', 14, 'Which type of wave requires a material medium?', 'Sound wave', 'Light wave', 'Radio wave', 'X-ray', 'A', 'Sound waves need a material medium for propagation.'
    UNION ALL SELECT 'waec', 'economics', 16, 'Trade can benefit countries by allowing:', 'Specialization and access to different goods', 'Reduced competition', 'No international exchange', 'Higher taxes without gains', 'A', 'Trade allows specialization and wider access to goods and services.'
    UNION ALL SELECT 'waec', 'government', 16, 'The main purpose of a parliament is to:', 'Enforce court orders', 'Represent the people and make laws', 'Run schools', 'Operate businesses', 'B', 'Parliaments represent the people and enact laws.'
    UNION ALL SELECT 'waec', 'literature', 19, 'A detailed comparison between two things without using like or as is:', 'Simile', 'Metaphor', 'Onomatopoeia', 'Irony', 'B', 'A metaphor states one thing is another to create meaning.'
    UNION ALL SELECT 'waec', 'literature', 20, 'The sequence of events in a story is called the:', 'Setting', 'Characters', 'Plot', 'Narration', 'C', 'Plot is the arrangement of actions and events in a story.'
    UNION ALL SELECT 'waec', 'government', 17, 'Which state organ carries out the day-to-day administration of government?', 'Judiciary', 'Legislature', 'Executive', 'Electoral body', 'C', 'The executive handles routine administration and policy implementation.'
    UNION ALL SELECT 'waec', 'economics', 17, 'A company that has many buyers and sellers with identical products is in a:', 'Monopoly', 'Oligopoly', 'Perfect competition', 'Duopoly', 'C', 'Perfect competition has many buyers and sellers with homogeneous products.'
    UNION ALL SELECT 'jamb', 'biology', 8, 'Which system is responsible for movement in the body?', 'Digestive system', 'Skeletal and muscular systems', 'Respiratory system', 'Excretory system', 'B', 'The skeletal and muscular systems work together to enable movement.'
    UNION ALL SELECT 'waec', 'physics', 15, 'The device that converts chemical energy into electrical energy is:', 'Resistor', 'Cell', 'Transformer', 'Motor', 'B', 'A cell is a source of electrical energy from chemical reactions.'
    UNION ALL SELECT 'waec', 'government', 18, 'Good governance requires:', 'Transparency and accountability', 'Secrecy and fear', 'No elections', 'No public participation', 'A', 'Good governance is built on transparency, accountability, and responsiveness.'
    UNION ALL SELECT 'waec', 'economics', 18, 'Food, clothing, and shelter are examples of:', 'Wants', 'Needs', 'Services', 'Liabilities', 'B', 'Needs are essentials required for survival.'
    UNION ALL SELECT 'waec', 'literature', 21, 'A long narrative poem about heroes is an:', 'Epic', 'Limerick', 'Elegy', 'Ballad', 'A', 'Epic poems tell the story of heroic figures and events.'
    UNION ALL SELECT 'waec', 'literature', 22, 'When a reader understands the opposite of what a character says, this is:', 'Irony', 'Metaphor', 'Alliteration', 'Personification', 'A', 'Irony occurs when reality differs from expectation or appearance.'
    UNION ALL SELECT 'jamb', 'economics', 5, 'The basic economic problem is:', 'How to create unlimited wants', 'How to satisfy wants with limited resources', 'How to eliminate all taxes', 'How to reduce exports', 'B', 'Economics studies how to allocate limited resources to satisfy unlimited wants.'
    UNION ALL SELECT 'waec', 'economics', 19, 'A fall in interest rates usually encourages:', 'More borrowing and spending', 'Less borrowing', 'Lower output', 'Higher unemployment', 'A', 'Lower interest rates make borrowing cheaper and encourage spending.'
    UNION ALL SELECT 'waec', 'government', 19, 'The judiciary is independent to ensure:', 'Fairness and impartial justice', 'The executive acts without review', 'Parliament cannot debate', 'Fees are ignored', 'A', 'Judicial independence helps uphold fairness and the rule of law.'
    UNION ALL SELECT 'waec', 'literature', 23, 'Which term refers to the feeling of tension or suspense created in a story?', 'Mood', 'Tone', 'Suspense', 'Theme', 'C', 'Suspense is the tension that keeps readers interested.'
    UNION ALL SELECT 'waec', 'literature', 24, 'The final resolution of a story is called the:', 'Climax', 'Denouement', 'Exposition', 'Conflict', 'B', 'Denouement is the resolution after the climax.'
    UNION ALL SELECT 'waec', 'government', 20, 'The constitution protects the rights of citizens by:', 'Limiting arbitrary power', 'Allowing unlimited taxes', 'Removing courts', 'Suppressing protest', 'A', 'Constitutions constrain arbitrary rule and protect rights.'
    UNION ALL SELECT 'waec', 'economics', 20, 'A barter system requires:', 'Double coincidence of wants', 'A central bank', 'High taxes', 'A monopoly', 'A', 'Barter requires each party to want what the other has to offer.'
) q ON q.exam_slug = t.slug AND q.subject_slug = s.slug
WHERE e.year IN (2022, 2023, 2024)
ON DUPLICATE KEY UPDATE question_text = VALUES(question_text), option_a = VALUES(option_a), option_b = VALUES(option_b), option_c = VALUES(option_c), option_d = VALUES(option_d), correct_option = VALUES(correct_option), explanation = VALUES(explanation), marks = VALUES(marks);