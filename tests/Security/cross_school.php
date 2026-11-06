<?php
declare(strict_types=1);

/**
 * Cross-school security test. Run against a LOCAL copy only (it deliberately attempts forbidden writes):
 *
 *   php tests/Security/cross_school.php
 *
 * It needs tests/Security/accounts.local.php (never committed) describing two schools and one account per role.
 * What it proves, in plain words:
 *   1. ROLE MATRIX   – each area (console / school admin / school staff / student) opens only for the right roles.
 *   2. READ ACCESS   – school X signed in, asking for school Y's records by id: every answer must be "not allowed/not found".
 *   3. WRITE ACCESS  – the same attempt with POST must change nothing in school Y (checked by hashing school Y's rows before/after).
 *   4. CONTROL       – the same accounts CAN open their own records (so a silent test failure cannot look like a pass).
 */
$root = dirname(__DIR__, 2);
$cfg = require __DIR__ . '/accounts.local.php';
$base = rtrim($cfg['base'], '/');

// ---------------------------------------------------------------- database (read-only helper)
$env = [];
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (preg_match('/^([A-Z_]+)=(.*)$/', trim($line), $m)) {
        $env[$m[1]] = trim($m[2], "\"'");
    }
}
$pdo = new PDO('mysql:host=' . ($env['DB_HOST'] ?? '127.0.0.1') . ';dbname=' . $env['DB_DATABASE'] . ';charset=utf8mb4', $env['DB_USERNAME'] ?? 'root', $env['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pre = $env['DB_PREFIX'] ?? 'cbt_';
$one = static function (string $sql, array $p = []) use ($pdo) {
    $s = $pdo->prepare($sql);
    $s->execute($p);
    $v = $s->fetchColumn();
    return $v === false ? null : $v;
};

// ---------------------------------------------------------------- http client
final class Client
{
    private string $jar;

    public function __construct(private readonly string $base)
    {
        $this->jar = tempnam(sys_get_temp_dir(), 'xs');
    }

    /** @return array{0:int,1:string,2:string} status, location, body */
    public function req(string $method, string $path, array $data = [], array $headers = []): array
    {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 60,
            CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar, CURLOPT_HTTPHEADER => $headers]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $raw = (string) curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $size = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $head = substr($raw, 0, $size);
        preg_match('/^Location:\s*(\S+)/mi', $head, $m);
        return [$status, $m[1] ?? '', substr($raw, $size)];
    }

    public function token(string $html): string
    {
        return preg_match('/name="csrf-token" content="([^"]+)"/', $html, $m) || preg_match('/name="_csrf_token" value="([^"]+)"/', $html, $m) ? $m[1] : '';
    }

    public function login(string $loginPath, array $fields): bool
    {
        [, , $page] = $this->req('GET', $loginPath);
        $fields['_csrf_token'] = $this->token($page);
        [$status, $loc] = $this->req('POST', $loginPath, $fields);
        return in_array($status, [302, 303], true) && !str_contains($loc, 'login');
    }
}

// ---------------------------------------------------------------- accounts
$A = (int) $cfg['schools']['A'];
$B = (int) $cfg['schools']['B'];
$people = [];
$mk = static function (string $name, string $loginPath, array $fields) use ($base, &$people): void {
    $c = new Client($base);
    $ok = $c->login($loginPath, $fields);
    echo sprintf("  sign-in %-9s %s\n", $name, $ok ? 'ok' : 'FAILED (its tests will be skipped)');
    $people[$name] = $ok ? $c : null;
};
echo "Signing in test accounts\n";
$mk('admin', '/login', ['email' => $cfg['admin']['email'], 'password' => $cfg['admin']['password']]);
$mk('teacher', '/login', ['email' => $cfg['teacher']['email'], 'password' => $cfg['teacher']['password']]);
$mk('student', '/student/login', ['school' => $cfg['student']['school'], 'admission_no' => $cfg['student']['admission'], 'password' => $cfg['student']['password']]);
$mk('owner', '/console/login', ['email' => $cfg['owner']['email'], 'password' => $cfg['owner']['password']]);
$people['anon'] = new Client($base);

// ---------------------------------------------------------------- routes (from the real route file)
$lines = file($root . '/routes/portal.php');
$routes = [];
foreach ($lines as $i => $text) {
    if (!preg_match('/\$router->(get|post)\(\'([^\']+)\'/', $text, $m)) {
        continue;
    }
    $n = $i + 1;
    [$method, $path] = [strtoupper($m[1]), $m[2]];
    $ctx = match (true) {
        $n >= 62 && $n <= 75 => 'bank',
        $n >= 77 && $n <= 107 => 'console',
        $n >= 112 && $n <= 157 => 'school',
        $n >= 158 && $n <= 222 => 'school-admin',
        $n >= 253 && $n <= 271 => 'student',
        default => null,
    };
    if ($ctx === null || str_contains($path, 'logout') || str_contains($path, 'stop-impersonating')) {
        continue;
    }
    if ($ctx === 'bank') {
        $routes[] = [$method, '/console' . $path, 'console'];
        $routes[] = [$method, '/school' . $path, 'school'];
        continue;
    }
    $prefix = $ctx === 'console' ? '/console' : ($ctx === 'student' ? '/student' : '/school');
    $routes[] = [$method, $prefix . ($path === '/' ? '' : $path), $ctx];
}
$allowed = ['console' => ['owner'], 'school' => ['admin', 'teacher'], 'school-admin' => ['admin'], 'student' => ['student']];

// ---------------------------------------------------------------- 1. role matrix
$fail = [];
$passes = 0;
echo "\n1. Role matrix (who may open which area)\n";
foreach ($routes as [$method, $path, $ctx]) {
    if ($method !== 'GET' || str_contains($path, '{') || str_contains($path, 'export') || str_contains($path, 'backups') || str_contains($path, 'slips') || str_contains($path, 'flyer')) {
        continue;
    }
    foreach (['anon', 'student', 'teacher', 'admin', 'owner'] as $role) {
        if (!in_array($role, $allowed[$ctx], true) && $people[$role] !== null) {
            [$status, $loc] = $people[$role]->req('GET', $path);
            if ($status === 200) {
                $fail[] = "ROLE   $role opened $path (should be blocked)";
            } else {
                $passes++;
            }
        }
    }
}
echo "   blocked correctly: $passes, failures so far: " . count($fail) . "\n";

// ---------------------------------------------------------------- id helpers for a target school
$ids = static function (int $school) use ($one, $pre): array {
    $exam = (int) $one("SELECT id FROM {$pre}school_exams WHERE school_id = ? ORDER BY id LIMIT 1", [$school]);
    $sitting = (int) $one("SELECT id FROM {$pre}exam_sittings WHERE school_id = ? ORDER BY id LIMIT 1", [$school]);
    $sittingExam = (int) $one("SELECT exam_id FROM {$pre}exam_sittings WHERE id = ?", [$sitting]);
    return [
        'exam' => $exam, 'sitting' => $sitting, 'sittingExam' => $sittingExam ?: $exam,
        'snap' => (int) $one("SELECT sn.id FROM {$pre}exam_snapshots sn JOIN {$pre}exam_sittings s ON s.id = sn.sitting_id WHERE s.school_id = ? LIMIT 1", [$school]),
        'paper' => (int) $one("SELECT p.id FROM {$pre}school_exam_papers p JOIN {$pre}school_exams e ON e.id = p.exam_id WHERE e.school_id = ? LIMIT 1", [$school]),
        'class' => (int) $one("SELECT id FROM {$pre}school_classes WHERE school_id = ? ORDER BY id LIMIT 1", [$school]),
        'subject' => (int) $one("SELECT id FROM {$pre}school_subjects WHERE school_id = ? ORDER BY id LIMIT 1", [$school]),
        'teacher' => (int) $one("SELECT id FROM {$pre}users WHERE school_id = ? AND role = 'teacher' ORDER BY id LIMIT 1", [$school]),
        'student' => (int) $one("SELECT id FROM {$pre}users WHERE school_id = ? AND role = 'student' ORDER BY id LIMIT 1", [$school]),
        'import' => (int) $one("SELECT id FROM {$pre}import_batches WHERE school_id = ? ORDER BY id LIMIT 1", [$school]),
        'department' => (int) $one("SELECT id FROM {$pre}departments WHERE school_id = ? ORDER BY id LIMIT 1", [$school]),
        'term' => (int) $one("SELECT id FROM {$pre}terms WHERE school_id = ? ORDER BY id LIMIT 1", [$school]),
        'bank' => (int) $one("SELECT id FROM {$pre}bank_questions WHERE school_id = ? ORDER BY id LIMIT 1", [$school]),
        'announcement' => (int) $one("SELECT id FROM {$pre}announcements WHERE school_id = ? ORDER BY id LIMIT 1", [$school]),
    ];
};
/** Fill a route pattern with a school's records; null when that school has no such record. */
$fill = static function (string $path, array $id): ?string {
    $map = ['/school/exams/{id}' => 'exam', '/school/exams/{id}/sittings/{sitting}' => null];
    $subs = [];
    $isStudentArea = str_starts_with($path, '/student/');
    if (preg_match('#/exams/\{id\}#', $path)) {
        $subs['{id}'] = $id['exam'];
    } elseif (preg_match('#/(classes)/\{id\}#', $path)) {
        $subs['{id}'] = $id['class'];
    } elseif (str_contains($path, '/subjects/{id}')) {
        $subs['{id}'] = $id['subject'];
    } elseif (str_contains($path, '/teachers/{id}')) {
        $subs['{id}'] = $id['teacher'];
    } elseif (str_contains($path, '/students/import/{id}')) {
        $subs['{id}'] = $id['import'];
    } elseif (str_contains($path, '/students/{id}')) {
        $subs['{id}'] = $id['student'];
    } elseif (str_contains($path, '/departments/{id}')) {
        $subs['{id}'] = $id['department'];
    } elseif (str_contains($path, '/terms/{id}')) {
        $subs['{id}'] = $id['term'];
    } elseif (str_contains($path, '/bank/{id}')) {
        $subs['{id}'] = $id['bank'];
    } elseif (str_contains($path, '/announcements/{id}')) {
        $subs['{id}'] = $id['announcement'];
    } elseif (str_contains($path, '/results/students/{student}')) {
        $subs['{student}'] = $id['student'];
    }
    if (str_contains($path, '{sitting}')) {
        $subs['{sitting}'] = $id['sitting'];
        $subs['{id}'] = $id['sittingExam'];
    }
    if (str_contains($path, '{snap}')) {
        $subs['{snap}'] = $id['snap'];
    }
    if (str_contains($path, '{paper}')) {
        $subs['{paper}'] = $id['paper'];
    }
    foreach ($subs as $k => $v) {
        if (!$v) {
            return null;
        }
        $path = str_replace($k, (string) $v, $path);
    }
    return str_contains($path, '{') ? null : $path;
};

// ---------------------------------------------------------------- B-scoped data fingerprint (write detector)
$fingerprint = static function (int $school) use ($pdo, $env, $pre): array {
    $pdo->exec('SET SESSION group_concat_max_len = 1073741824');
    $out = [];
    $tables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $pdo->quote($env['DB_DATABASE']) . " AND TABLE_NAME LIKE '{$pre}%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        if (preg_match('/(sessions|rate|throttle|cache|jobs|migrations|notifications|push_)/', $t) && !str_contains($t, 'academic')) {
            continue;
        }
        $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = " . $pdo->quote($env['DB_DATABASE']) . " AND TABLE_NAME = " . $pdo->quote($t) . " ORDER BY ORDINAL_POSITION")->fetchAll(PDO::FETCH_COLUMN);
        $concat = implode(',', array_map(static fn (string $c): string => "IFNULL(CAST(`{$c}` AS CHAR),'~')", $cols));
        $where = in_array('school_id', $cols, true) ? " WHERE school_id = {$school}" : '';
        $out[$t] = $pdo->query("SELECT COUNT(*), MD5(IFNULL(GROUP_CONCAT(MD5(CONCAT_WS('|',{$concat})) ORDER BY MD5(CONCAT_WS('|',{$concat})) SEPARATOR ''),'')) FROM `{$t}`{$where}")->fetch(PDO::FETCH_NUM);
    }
    return $out;
};

// ---------------------------------------------------------------- 2/3. cross-school reads and writes
$ctxFor = ['admin' => ['school', 'school-admin', 'bank'], 'teacher' => ['school', 'school-admin', 'bank'], 'student' => ['student']];
$attackers = array_map(static fn (array $x): array => [$x[0], $x[1], $x[2], $ctxFor[$x[0]]], $cfg['attacks']);
$victims = array_values(array_unique(array_merge(array_column($cfg['attacks'], 2), array_column($cfg['attacks'], 1))));
echo "\n2-4. Cross-school attempts\n";
// Harness self-test: the write detector must notice a change and must settle again after it is undone.
$probe = (int) $one("SELECT id FROM {$pre}school_subjects WHERE school_id = ? ORDER BY id LIMIT 1", [$victims[0]]);
$f1 = $fingerprint($victims[0]);
$pdo->exec("UPDATE {$pre}school_subjects SET name = CONCAT(name, '!') WHERE id = {$probe}");
$f2 = $fingerprint($victims[0]);
$pdo->exec("UPDATE {$pre}school_subjects SET name = LEFT(name, CHAR_LENGTH(name) - 1) WHERE id = {$probe}");
$f3 = $fingerprint($victims[0]);
if ($f1 === $f2 || $f1 !== $f3) {
    echo "ABORT: the write detector itself is not working; results would mean nothing.\n";
    exit(2);
}
echo "   write detector self-test: ok\n";
$writeBefore = [];
foreach ($victims as $v) {
    $writeBefore[$v] = $fingerprint($v);
}
$skipped = [];
$reads = 0;
$writes = 0;
$done = [];
$controlOk = 0;
$notes = [];
$controls = 0;
foreach ($attackers as [$role, $own, $target, $contexts]) {
    $who = $people[$role];
    if ($who === null) {
        echo "   skipped $role (not signed in)\n";
        continue;
    }
    $mine = $ids($own);
    $theirs = $ids($target);
    [, , $home] = $who->req('GET', $role === 'student' ? '/student' : '/school');
    $csrf = $who->token($home);
    foreach ($routes as [$method, $path, $ctx]) {
        if (!in_array($ctx, $contexts, true) || !str_contains($path, '{') || str_starts_with($path, '/console')) {
            continue;
        }
        $foreign = $fill($path, $theirs);
        if ($foreign === null) {
            $skipped[$role . '->' . $target][] = $method . ' ' . $path;
            continue;
        }
        $done[$role . '->' . $target][$method] = ($done[$role . '->' . $target][$method] ?? 0) + 1;
        if ($method === 'GET') {
            [$status] = $who->req('GET', $foreign);
            $reads++;
            if ($status === 200) {
                $fail[] = "READ   $role of school $own got 200 for school $target record: $foreign";
            }
            // control: the same page for the account's OWN record must open (admin/teacher/student role permitting)
            if (in_array($role, $allowed[$ctx] ?? [], true) && ($own_path = $fill($path, $mine)) !== null && $controls < 40 && $ctx !== 'school-admin' || ($role === 'admin' && ($own_path = $fill($path, $mine)) !== null && $controls < 40)) {
                [$cs] = $who->req('GET', $own_path);
                $controls++;
                if (in_array($cs, [200, 302, 303], true)) {
                    $controlOk++;
                } else {
                    // Some pages are correctly "not found" for an exam of the wrong kind (paper pages on a computer exam,
                    // a student's page for an exam of another class). Reported, but only a lack of working controls fails.
                    $notes[] = "control $role ($cs, business rule): $own_path";
                }
            }
        } else {
            $payload = ['_csrf_token' => $csrf, 'name' => 'HACKED-' . $role, 'title' => 'HACKED-' . $role, 'remarks' => 'HACKED', 'action' => 'cancel', 'status' => 'suspended', 'is_active' => '0', 'code' => 'HACKED', 'subject' => 'HACKED', 'password' => 'Hacked-12345-x'];
            $who->req('POST', $foreign, $payload, ['X-CSRF-Token: ' . $csrf]);
            $writes++;
        }
    }
}
// ---- Mixed-id attack: the attacker's OWN exam in the address, but ANOTHER school's sitting / paper / snapshot id.
$mixedTried = 0;
foreach ($cfg['attacks'] as [$role, $own, $target]) {
    if ($role !== 'admin' || $people['admin'] === null) {
        continue;
    }
    $who = $people['admin'];
    $mine = $ids($own);
    $theirs = $ids($target);
    if (!$mine['sitting'] || !$theirs['sitting']) {
        continue;
    }
    [, , $home] = $who->req('GET', '/school');
    $csrf = $who->token($home);
    $cases = [
        ['GET', "/school/exams/{$mine['sittingExam']}/sittings/{$theirs['sitting']}"],
        ['POST', "/school/exams/{$mine['sittingExam']}/sittings/{$theirs['sitting']}"],
        ['GET', "/school/exams/{$mine['sittingExam']}/paper/scans/{$theirs['sitting']}"],
        ['GET', "/school/exams/{$mine['sittingExam']}/sittings/{$theirs['sitting']}/snapshots/{$theirs['snap']}"],
        ['GET', "/school/exams/{$mine['sittingExam']}/sittings/{$mine['sitting']}/snapshots/{$theirs['snap']}"],
        ['GET', "/school/exams/{$mine['sittingExam']}/papers/{$theirs['paper']}/questions"],
        ['POST', "/school/exams/{$mine['sittingExam']}/papers/{$theirs['paper']}/questions"],
        ['POST', "/school/exams/{$mine['sittingExam']}/paper/theory"],
    ];
    foreach ($cases as [$method, $path]) {
        if (str_contains($path, '/0') || str_contains($path, '//')) {
            continue;
        }
        $mixedTried++;
        if ($method === 'GET') {
            [$status] = $who->req('GET', $path);
            if ($status === 200) {
                $fail[] = "MIXED  admin of school $own got 200 mixing its own exam with school $target ids: $path";
            }
        } else {
            $who->req('POST', $path, ['_csrf_token' => $csrf, 'action' => 'extend', 'minutes' => '30', 'status' => 'suspended', 'questions' => [$theirs['bank']], 'name' => 'HACKED'], ['X-CSRF-Token: ' . $csrf]);
        }
    }
}
echo "   mixed-id attempts: $mixedTried\n";
$writeAfter = [];
foreach ($victims as $v) {
    $writeAfter[$v] = $fingerprint($v);
}
foreach ($victims as $victim) {
    foreach ($writeBefore[$victim] as $t => $before) {
        if (($writeAfter[$victim][$t] ?? null) !== $before) {
            $fail[] = "WRITE  rows of school $victim changed in table $t during the cross-school attempts";
        }
    }
}
echo "   read attempts: $reads, write attempts: $writes, control checks: $controls\n";
foreach ($done as $pair => $n) {
    echo sprintf("   %-18s GET %d  POST %d\n", $pair, $n['GET'] ?? 0, $n['POST'] ?? 0);
}
foreach ($skipped as $pair => $list) {
    echo "   not testable for $pair (target has no such record): " . count($list) . ' route(s)' . "\n";
}

if ($controlOk < 20) {
    $fail[] = "CONTROL only $controlOk own-record pages opened; the test cannot be trusted";
}
echo "   control pages opened normally: $controlOk (inconclusive business-rule 404s: " . count($notes) . ")\n";
foreach ($skipped as $pair => $list) {
    echo "   not testable for $pair: " . implode(', ', array_unique($list)) . "\n";
}
// ---------------------------------------------------------------- 5. uploaded files (informational)
echo "\n5. Uploaded files reachable without signing in (informational)\n";
$files = array_filter([
    ...array_column($pdo->query("SELECT photo_path AS p FROM {$pre}student_profiles WHERE photo_path IS NOT NULL AND photo_path <> '' LIMIT 3")->fetchAll(PDO::FETCH_ASSOC), 'p'),
    ...array_column($pdo->query("SELECT logo_path AS p FROM {$pre}schools WHERE logo_path IS NOT NULL AND logo_path <> '' LIMIT 3")->fetchAll(PDO::FETCH_ASSOC), 'p'),
]);
foreach ($files as $f) {
    [$status] = $people['anon']->req('GET', '/' . ltrim((string) $f, '/'));
    echo sprintf("   %-60s anonymous request -> %d\n", substr((string) $f, 0, 60), $status);
}
if ($files === []) {
    echo "   (no uploaded photos or logos in this database)\n";
}

// ---------------------------------------------------------------- verdict
echo "\n==== RESULT ====\n";
if ($fail === []) {
    echo "PASS: no cross-school read or write succeeded, and role areas are closed to the wrong roles.\n";
    exit(0);
}
echo "FAIL: " . count($fail) . " problem(s):\n";
foreach (array_unique($fail) as $f) {
    echo " - $f\n";
}
exit(1);
