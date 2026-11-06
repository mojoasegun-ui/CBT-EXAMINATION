<?php
declare(strict_types=1);
/** @var string $appName @var string $contact @var string $updated */
?>
<p class="eyebrow">Terms of use</p>
<h1>Using <?= e($appName) ?></h1>
<p class="legal-meta">Last updated <?= e($updated) ?></p>

<p>By signing in or using the practice pages you agree to these terms. They are short on purpose.</p>

<h2>1. Who may use it</h2>
<p>School accounts are created by a school and may be used only by the person they were made for. Keep your password private. If you think someone else knows it, change it or ask your school administrator to reset it. Free practice is open to everyone.</p>

<h2>2. Using it properly</h2>
<ul>
    <li>Do not try to cheat in an exam, share exam questions, or use someone else's account.</li>
    <li>Do not try to break into the platform, look at other people's information, overload it, or damage it.</li>
    <li>Do not upload anything unlawful or offensive, and only upload photos you are allowed to share.</li>
</ul>
<p>We may suspend accounts that break these rules, and schools may take their own action.</p>

<h2>3. Schools</h2>
<p>A school is responsible for the information it enters, for choosing who gets accounts, and for any permission it needs from parents or guardians, including for photographs and camera identity checks. A school can export its records and ask for them to be removed when it stops using the platform.</p>

<h2>4. Practice questions</h2>
<p>The practice question sets are original questions written to follow the exam syllabus. They are <strong>not</strong> the real past papers of JAMB, WAEC or NECO, and the year on a practice series is only a label. Practising here helps you prepare but cannot guarantee any exam result.</p>

<h2>5. Availability</h2>
<p>We work to keep the service running and your answers safe, but we cannot promise it will never be interrupted. Plan important exams with your school's own arrangements for problems.</p>

<h2>6. Responsibility</h2>
<p>To the extent the law allows, we are not responsible for losses that come from interruptions, from mistakes in information a school entered, or from using practice material instead of official resources. Nothing here limits rights you have under Nigerian law that cannot be limited.</p>

<h2>7. Your information</h2>
<p>How information is handled is described in the <a href="<?= e(url('/privacy')) ?>">privacy notice</a>.</p>

<h2>8. Changes and law</h2>
<p>We may update these terms and will change the date above. They are governed by the laws of the Federal Republic of Nigeria.</p>

<h2>9. Contact</h2>
<p><?= $contact !== '' ? 'Questions: <a href="mailto:' . e($contact) . '">' . e($contact) . '</a>.' : 'Questions: please contact your school or the platform operator through the website.' ?></p>
