<?php
declare(strict_types=1);
/** @var string $appName @var string $contact @var string $updated */
?>
<p class="eyebrow">Privacy notice</p>
<h1>How <?= e($appName) ?> handles personal information</h1>
<p class="legal-meta">Last updated <?= e($updated) ?></p>

<p>This notice explains what information the platform collects, why, who can see it and how long we keep it. We wrote it in plain language. The platform is used by schools and by people practising for exams, and the rules differ a little between the two, so each section says who it applies to.</p>

<h2>1. Who is responsible for the information</h2>
<ul>
    <li><strong>School accounts</strong> (students, teachers, administrators): the <em>school</em> decides what is collected and why, and is the data controller. <?= e($appName) ?> stores and processes it on the school's behalf and on the school's instructions.</li>
    <li><strong>Free practice without an account</strong>: <?= e($appName) ?> is responsible for the little information collected there.</li>
</ul>

<h2>2. What we collect</h2>
<h3>School users</h3>
<ul>
    <li>Names, sign-in details (a hashed password is stored, never the password itself), role, class and admission number.</li>
    <li>For students, a school may also record gender, date of birth, guardian name, phone and email, home address and a photo.</li>
    <li>Exam activity: answers, scores, start and finish times, and events during an exam such as leaving the exam window.</li>
    <li>If the school turns on identity checks for an exam: photographs taken with the device camera during the exam, and the result of comparing them with the student's photo.</li>
    <li>Records of actions taken in the school portal (who changed or opened what), to keep the school's data safe.</li>
</ul>
<h3>People using free practice</h3>
<ul>
    <li>Your answers and scores, so you can see progress, streaks and mistakes.</li>
    <li>A name and email address only if you choose to type them before a practice exam (so your result can be emailed).</li>
    <li>A random identifier stored in your browser, so your progress is remembered on that device.</li>
</ul>
<h3>Notifications</h3>
<ul>
    <li>If you turn on phone notifications, your browser gives us a notification address. We use it only to send you the alerts you asked for, such as announcements, exam notices, results and practice reminders. You can turn them off at any time in your browser or phone settings.</li>
</ul>

<h2>3. Why we use it</h2>
<p>To run exams and show results, to let schools manage students and records, to let you practise and track progress, to send the messages you asked for, to keep accounts secure, and to fix problems. We do not sell personal information and we do not show advertising.</p>

<h2>4. Cookies and similar storage</h2>
<p>We use only what the service needs: a sign-in session cookie, a security token that protects forms, the practice identifier described above, and small items in your browser such as your theme choice and saved name. There are no advertising or tracking cookies.</p>

<h2>5. Children</h2>
<p>Many school users are under 18. The school is responsible for having any permission it needs from parents or guardians, in particular for student photographs and for camera identity checks. Parents or guardians who have questions should speak to the school first.</p>

<h2>6. Who can see information</h2>
<ul>
    <li>Information belongs to the school it was entered for. People at one school cannot see another school's information; this is tested regularly.</li>
    <li>Within a school, administrators can see their school's records; teachers see the students in their classes; students see their own results.</li>
    <li>Service providers that help us run the platform: the hosting company, an email provider that delivers messages, and the browser makers' notification services (which receive only an encrypted alert for your device).</li>
    <li>We disclose information if the law requires it.</li>
</ul>

<h2>7. How long we keep it</h2>
<ul>
    <li>School records, results and class history are kept for as long as the school uses the platform, so a school can look up past years.</li>
    <li>Photographs taken for exam identity checks are deleted automatically after about 120 days.</li>
    <li>Password-reset links expire after one hour and are removed after a week.</li>
    <li>Free-practice progress is kept so you can continue; ask us to delete it whenever you like.</li>
</ul>

<h2>8. How we protect it</h2>
<p>Connections use HTTPS, passwords are stored hashed, two-step verification is available for staff, schools are kept apart in the system, sign-in attempts are limited, and important actions are logged.</p>

<h2>9. Your rights</h2>
<p>Under Nigeria's data protection law you may ask to see the information held about you, to correct it, to have it deleted where the law allows, to object to how it is used, and to complain to the Nigeria Data Protection Commission. For school records, please ask your school first; <?= $contact !== '' ? 'for free practice or anything else, write to <a href="mailto:' . e($contact) . '">' . e($contact) . '</a>' : 'for free practice or anything else, contact the platform operator through your school or the address shown on the website' ?>.</p>

<h2>10. Changes</h2>
<p>If we change this notice we will update the date above and, for important changes, tell school administrators.</p>
