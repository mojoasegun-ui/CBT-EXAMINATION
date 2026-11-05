# Public CBT Practice Simulator

The simulator is anonymous and session-scoped. It has no login, signup, learner profile, or admin UI. The session contains a random 256-bit practice token; each `cbt_attempts` row stores only that token for ownership and resumption. Attempt routes use a session-bound HMAC token (`attempt-id-signature`) instead of exposing a guessable ID by itself. A hyphen separator also ensures the PHP built-in server treats the URL as an application route, not a dotted static filename.

## Setup

1. Create a fresh MySQL 8+ or compatible MariaDB database, for example `cbt_examcore`.
2. Import `database/schema.sql`, then `database/seed.sql` using the cPanel MySQL tools or `mysql` CLI. Neither script alters host-school tables. The schema uses only `cbt_` tables and `IF NOT EXISTS`; the seed uses upserts.
3. Set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_HOST`, and `DB_PREFIX=cbt_` in the protected environment configuration.
4. Ensure `storage/sessions`, `storage/cache`, and `storage/logs` are writable by PHP.
5. Open `/`. Choose an exam type, subject, and available year, then start.

Seed questions are original practice material written for this simulator, not official or reproduced examination papers. The current seed contains three practice sets: WAEC Mathematics 2023, JAMB English Language 2024, and NECO Biology 2023. Each has 40 questions and a 45-minute limit. The subject catalog currently contains 30 entries; only those three subjects have question sets ready, and the remaining catalog cards are labeled as coming soon.

## Request Flow

- `GET /` renders exam types and the current session's recent attempts.
- `GET /api/exams?exam_type=waec` returns subjects available for the selected type.
- `GET /api/exams?exam_type=waec&subject=mathematics` returns the matching exam/year choices.
- `POST /start` validates the exam ID and creates a fresh session-owned attempt.
- `GET /exam/{attempt-token}` verifies the HMAC and session ownership, resumes saved choices, and sends only question text, option text, and IDs to the browser. The runner query intentionally omits `correct_option` and `explanation`.
- `POST /exam/{attempt-token}/answer` validates CSRF, option allowlist, attempt/session ownership, question membership, and server-side expiry before upserting an answer. The rotated CSRF value is returned in `X-CSRF-Token` for the next serialized autosave.
- `POST /exam/{attempt-token}/submit` grades on the server in a transaction, persists per-question correctness, and redirects to results.
- `GET /results/{attempt-token}` verifies session ownership and only then returns correct answers, explanations, score, and the breakdown.

All POST routes retain the application's global CSRF middleware and rate limiter. No authentication or admin middleware is attached. Answers and scores are not written to application logs. Session ownership is checked on every read/write; the attempt's exam membership is checked before each answer write.

## Timing and Scoring

The server stores `started_at` in UTC. Remaining time is calculated as `started_at + duration_minutes - current server time` when the exam is opened, when an answer is saved, and when it is submitted. The browser only displays the server-supplied initial remaining seconds and triggers a convenient submit request at zero; it cannot extend an attempt, accept answers after expiry, or determine the score. If a browser remains open after expiry, the save endpoint auto-submits and returns the result URL; normal form submission is also rechecked server-side.

The pass mark is currently 50% in `AttemptService` (`$passMarkPercent`). Change that constructor default through the application service binding when a different product rule is approved. Question `marks` determine the total and score; seeded questions each carry one mark.

## Adding Practice Exams

1. Add an exam type or subject row to `cbt_exam_types` / `cbt_subjects` with a unique lowercase slug.
2. Add an active exam row to `cbt_exams`, linking the type and subject, with its year, duration, and expected question count.
3. Add exactly that many questions to `cbt_questions`. Keep `correct_option` to A-D, write a learner-facing explanation, and use unique `sort_order` values per exam.
4. Verify the selector API returns the new entry, an attempt runner response contains no `correct_option`, and a completed attempt grades/reviews correctly. Use original or institution-authorized question material.

Do not change table identifiers supplied by users; model table names are internal and values are bound as PDO parameters. Any future import or administration tool needs its own authorization, validation, provenance, and audit review.

## Limitations

This is a practice simulator, not an official exam provider. It does not claim endorsement by WAEC, NECO, or JAMB. The current 30-entry catalog is not a complete official subject list; NECO notes that 38 subjects are usually examined in SSCE Internal, and JAMB's current subject/brochure system is maintained through IBASS. The three original practice sets are not curriculum-complete or psychometrically validated. More subjects should only be marked available after their own reviewed question banks are added. There is no global attempt cleanup job; anonymous attempts remain in the database until an authorized retention policy is implemented. No fake proctoring or identity tracking is performed. Database import and the complete HTTP lifecycle must be verified against the target MySQL/MariaDB version before production use.

## Subject Research

- NECO's official SSCE Internal page states that 38 subjects are usually examined and groups them into compulsory, science/mathematics, trade, business, and humanities categories: [NECO SSCE Internal](https://neco.gov.ng/exams/ssce-internal).
- JAMB links candidates to its official Integrated Brochure & Syllabus System (IBASS) for current subject and syllabus information: [JAMB IBASS](https://ibass.jamb.gov.ng/brochure).
- WAEC Nigeria's public site is the source for WAEC announcements and candidate information: [WAEC Nigeria](https://www.waecnigeria.org/).

These pages informed the initial broad catalog only. Exam offerings can vary by board, year, and candidate combination; confirm each new subject against the current official brochure/syllabus before adding a practice set.
