<?php
declare(strict_types=1);

/**
 * Physics and Chemistry calculation questions following the JAMB UTME syllabus areas
 * (Physics: motion, forces, work/energy/power, heat, waves, electricity, radioactivity;
 *  Chemistry: mole concept, stoichiometry, gas laws, solutions, pH). Answers are computed here.
 */
require_once __DIR__ . '/_helpers.php';

$phy = [];
$chm = [];
$n = 0;
$p = static function (array $row) use (&$phy): void { $phy[] = $row; };
$c = static function (array $row) use (&$chm): void { $chm[] = $row; };

// ================================ PHYSICS (take g = 10 m/s²) ================================
foreach ([[0, 4, 5], [5, 3, 4], [10, 2, 6], [0, 5, 6], [2, 6, 3], [8, 4, 5]] as [$u, $a, $t]) {
    $v = $u + $a * $t;
    $p(mc('Motion', 'easy', "A body starting with a velocity of {$u} m/s accelerates uniformly at {$a} m/s² for {$t} s. Find its final velocity.", $v, [$v + $a, $u * $t, $a * $t - $u, $v - $u], "v = u + at = {$u} + {$a} × {$t} = {$v} m/s.", $n++));
}
foreach ([[0, 2, 6], [0, 4, 5], [5, 2, 4], [10, 2, 3], [0, 3, 4], [4, 6, 2]] as [$u, $a, $t]) {
    $s = $u * $t + $a * $t * $t / 2;
    $p(mc('Motion', 'medium', "A car with initial velocity {$u} m/s accelerates uniformly at {$a} m/s² for {$t} s. Find the distance covered.", $s, [$u * $t + $a * $t, $a * $t * $t, $s + $u, $s / 2], "s = ut + ½at² = ({$u} × {$t}) + ½ × {$a} × {$t}² = " . qfmt($s) . ' m.', $n++));
}
foreach ([[2, 5], [4, 3], [10, 6], [5, 8], [3, 9], [20, 2]] as [$m, $a]) {
    $p(mc('Newton\'s laws', 'easy', "Find the force required to give a mass of {$m} kg an acceleration of {$a} m/s².", $m * $a, [$m + $a, $m / $a, $a * $a, $m * $a * 2], "F = ma = {$m} × {$a} = " . ($m * $a) . ' N.', $n++));
}
foreach ([[20, 5], [50, 3], [100, 4], [40, 6], [25, 8], [60, 2]] as [$F, $d]) {
    $p(mc('Work, energy and power', 'easy', "Calculate the work done when a force of {$F} N moves a body through {$d} m in its direction.", $F * $d, [$F + $d, $F / $d, $F * $d / 2, $F * $d * 2], "W = F × d = {$F} × {$d} = " . ($F * $d) . ' J.', $n++));
}
foreach ([[2, 4], [3, 6], [5, 2], [4, 5], [10, 3], [1, 8]] as [$m, $v]) {
    $p(mc('Work, energy and power', 'medium', "Find the kinetic energy of a {$m} kg body moving at {$v} m/s.", $m * $v * $v / 2, [$m * $v, $m * $v * $v, $m * $v / 2, $m * $v * $v / 4], "KE = ½mv² = ½ × {$m} × {$v}² = " . qfmt($m * $v * $v / 2) . ' J.', $n++));
}
foreach ([[2, 5], [10, 3], [5, 8], [4, 10], [20, 2], [3, 15]] as [$m, $h]) {
    $p(mc('Work, energy and power', 'medium', "Find the potential energy gained by a {$m} kg mass raised through {$h} m. (g = 10 m/s²)", $m * 10 * $h, [$m * $h, $m * 10 + $h, $m * 10 * $h / 2, $m * 100 * $h], "PE = mgh = {$m} × 10 × {$h} = " . ($m * 10 * $h) . ' J.', $n++));
}
foreach ([[600, 30], [1200, 40], [800, 20], [1500, 50], [900, 15], [2000, 25]] as [$W, $t]) {
    $p(mc('Work, energy and power', 'easy', "A machine does {$W} J of work in {$t} s. What is its power?", $W / $t, [$W * $t, $W - $t, $W / $t * 2, $t / $W], "P = W ÷ t = {$W} ÷ {$t} = " . qfmt($W / $t) . ' W.', $n++));
}
foreach ([[20, 40], [30, 75], [10, 80], [45, 60], [24, 30], [50, 100]] as [$out, $in]) {
    $eff = $out / $in * 100;
    $p(mc('Machines', 'medium', "A machine receives {$in} J of energy and does {$out} J of useful work. Find its efficiency.", $eff . '%', [($in / $out * 100) . '%', ($in - $out) . '%', ($eff / 2) . '%', ($eff + 10) . '%'], "Efficiency = useful output ÷ input × 100 = {$out}/{$in} × 100 = " . qfmt($eff) . '%.', $n++));
}
foreach ([[500, 250], [800, 200], [900, 300], [1000, 125], [400, 100], [600, 150]] as [$load, $effort]) {
    $p(mc('Machines', 'medium', "A lever lifts a load of {$load} N with an effort of {$effort} N. Find its mechanical advantage.", $load / $effort, [$effort / $load, $load - $effort, $load * $effort, $load / $effort + 1], "M.A. = load ÷ effort = {$load} ÷ {$effort} = " . qfmt($load / $effort) . '.', $n++));
}
foreach ([[5, 4], [20, 8], [100, 5], [12, 3], [60, 12], [8, 10]] as [$F, $A]) {
    $p(mc('Pressure', 'easy', "A force of {$F} N acts on an area of {$A} m². Find the pressure.", $F / $A, [$F * $A, $F - $A, $A / $F, $F / $A * 2], "P = F ÷ A = {$F} ÷ {$A} = " . qfmt($F / $A) . ' Pa.', $n++));
}
foreach ([[800, 0.5], [1000, 0.3], [600, 2], [1200, 0.25], [900, 0.4], [2700, 0.2]] as [$ρ, $V]) {
    $m = $ρ * $V;
    $p(mc('Density', 'medium', "A liquid of density {$ρ} kg/m³ fills a container of volume " . qfmt($V) . ' m³. Find the mass of the liquid.', $m, [$ρ / $V, $ρ + $V, $m * 2, $m / 2], "m = ρV = {$ρ} × " . qfmt($V) . ' = ' . qfmt($m) . ' kg.', $n++));
}
foreach ([[2, 4200, 10], [3, 4200, 20], [1, 4200, 5], [0.5, 4200, 40], [2, 900, 10], [4, 380, 25]] as [$m, $cap, $dt]) {
    $Q = $m * $cap * $dt;
    $p(mc('Heat energy', 'medium', "How much heat is needed to raise the temperature of " . qfmt($m) . " kg of a substance of specific heat capacity {$cap} J/(kg K) by {$dt} K?", $Q, [$m * $cap, $cap * $dt, $Q / 2, $Q * 2], "Q = mcΔθ = " . qfmt($m) . " × {$cap} × {$dt} = " . qfmt($Q) . ' J.', $n++));
}
foreach ([[50, 4], [20, 0.5], [100, 2], [10, 5], [340, 0.5], [25, 8]] as [$f, $λ]) {
    $v = $f * $λ;
    $p(mc('Waves', 'easy', "A wave has frequency {$f} Hz and wavelength " . qfmt($λ) . ' m. Find its speed.', $v, [$f / $λ, $f + $λ, $v * 2, $λ / $f], "v = fλ = {$f} × " . qfmt($λ) . ' = ' . qfmt($v) . ' m/s.', $n++));
}
foreach ([[0.5], [0.2], [0.25], [0.1], [0.04], [2]] as [$T]) {
    $f = 1 / $T;
    $p(mc('Waves', 'easy', 'A wave has a period of ' . qfmt($T) . ' s. Find its frequency.', $f, [$T, $f * 2, $f / 2, $f + 1], 'f = 1/T = 1 ÷ ' . qfmt($T) . ' = ' . qfmt($f) . ' Hz.', $n++));
}
foreach ([[2, 6], [3, 4], [5, 2], [1.5, 8], [4, 5], [10, 3]] as [$I, $R]) {
    $p(mc('Current electricity', 'easy', 'A current of ' . qfmt($I) . " A flows through a {$R} Ω resistor. Find the potential difference across it.", $I * $R, [$I + $R, $R / $I, $I * $R * 2, $I / $R], "V = IR = " . qfmt($I) . " × {$R} = " . qfmt($I * $R) . ' V.', $n++));
}
foreach ([[12, 4], [24, 8], [6, 2], [20, 5], [9, 3], [30, 10]] as [$V, $R]) {
    $p(mc('Current electricity', 'easy', "A {$V} V supply is connected across a {$R} Ω resistor. What current flows?", $V / $R, [$V * $R, $V - $R, $R / $V, $V / $R + 1], "I = V ÷ R = {$V} ÷ {$R} = " . qfmt($V / $R) . ' A.', $n++));
}
foreach ([[6, 3], [12, 4], [20, 5], [30, 6], [15, 10], [10, 40]] as [$R1, $R2]) {
    $par = $R1 * $R2 / ($R1 + $R2);
    $p(mc('Current electricity', 'medium', "Two resistors of {$R1} Ω and {$R2} Ω are connected in parallel. Find the effective resistance.", $par, [$R1 + $R2, ($R1 + $R2) / 2, $R1 * $R2, $par * 2], "1/R = 1/{$R1} + 1/{$R2}, so R = ({$R1} × {$R2}) ÷ (" . ($R1 + $R2) . ') = ' . qfmt($par) . ' Ω.', $n++));
}
foreach ([[3, 4, 5], [2, 6, 3], [10, 20, 30], [5, 5, 10], [1, 2, 3], [4, 8, 2]] as [$a, $b, $cc]) {
    $p(mc('Current electricity', 'easy', "Three resistors of {$a} Ω, {$b} Ω and {$cc} Ω are connected in series. Find the total resistance.", $a + $b + $cc, [($a + $b + $cc) / 3, $a * $b * $cc, $a + $b, ($a + $b + $cc) * 2], "In series the resistances add: {$a} + {$b} + {$cc} = " . ($a + $b + $cc) . ' Ω.', $n++));
}
foreach ([[220, 5], [12, 3], [240, 2], [110, 10], [6, 4], [220, 0.5]] as [$V, $I]) {
    $P = $V * $I;
    $p(mc('Electrical energy and power', 'medium', "An appliance on a {$V} V supply draws " . qfmt($I) . ' A. Find its power rating.', $P, [$V / $I, $V + $I, $P * 2, $P / 2], 'P = IV = ' . qfmt($I) . " × {$V} = " . qfmt($P) . ' W.', $n++));
}
foreach ([[2, 5], [1, 10], [3, 4], [0.5, 8], [4, 3], [2, 12]] as [$kw, $h]) {
    $units = $kw * $h;
    $p(mc('Electrical energy and power', 'medium', 'An electric heater rated ' . qfmt($kw) . " kW is used for {$h} hours. How many kilowatt-hours (units) of energy does it use?", $units, [$kw + $h, $kw / $h, $units * 2, $units / 2], 'Energy = power × time = ' . qfmt($kw) . " × {$h} = " . qfmt($units) . ' kWh.', $n++));
}
foreach ([[80, 3], [160, 4], [64, 2], [100, 5], [240, 3], [48, 4]] as [$N0, $halves]) {
    $left = $N0 / (2 ** $halves);
    $p(mc('Radioactivity', 'medium', "A radioactive sample contains {$N0} g of a nuclide. How much remains after {$halves} half-lives?", $left, [$N0 / $halves, $N0 / 2, $left * 2, $N0 - $halves], "Each half-life halves the amount: {$N0} ÷ 2^{$halves} = " . qfmt($left) . ' g.', $n++));
}
foreach ([[20, 4], [30, 6], [10, 2], [24, 3], [50, 5]] as [$F, $e]) {
    $k = $F / $e;
    $p(mc('Elasticity', 'medium', "A force of {$F} N stretches a spring by {$e} cm. What is the force constant of the spring in N/cm? (Hooke's law)", $k, [$F * $e, $F - $e, $k * 2, $e / $F], "k = F ÷ e = {$F} ÷ {$e} = " . qfmt($k) . ' N/cm.', $n++));
}
foreach ([[2, 5], [4, 3], [10, 6], [0.5, 20], [3, 8], [5, 4]] as [$m, $v]) {
    $p(mc('Momentum', 'easy', 'Find the momentum of a ' . qfmt($m) . " kg body moving at {$v} m/s.", $m * $v, [$m + $v, $v / $m, $m * $v * 2, $m * $v / 2], 'Momentum = mv = ' . qfmt($m) . " × {$v} = " . qfmt($m * $v) . ' kg m/s.', $n++));
}
foreach ([[40, 5], [60, 3], [100, 2], [20, 8], [30, 4]] as [$F, $d]) {
    $p(mc('Turning effect of forces', 'medium', "Find the moment of a {$F} N force whose line of action is {$d} m from the pivot.", $F * $d, [$F + $d, $F / $d, $F * $d * 2, $d / $F], "Moment = force × perpendicular distance = {$F} × {$d} = " . ($F * $d) . ' N m.', $n++));
}

// ================================ CHEMISTRY ================================
$mass = ['H' => 1, 'C' => 12, 'N' => 14, 'O' => 16, 'Na' => 23, 'Mg' => 24, 'Al' => 27, 'S' => 32, 'Cl' => 35.5, 'K' => 39, 'Ca' => 40, 'Cu' => 64];
$mm = static fn (array $f) => array_sum(array_map(static fn ($el, $k) => $GLOBALS['mass_tbl'][$el] * $k, array_keys($f), $f));
$GLOBALS['mass_tbl'] = $mass;
$compounds = [
    ['H₂O', ['H' => 2, 'O' => 1]], ['CO₂', ['C' => 1, 'O' => 2]], ['NaCl', ['Na' => 1, 'Cl' => 1]], ['CaCO₃', ['Ca' => 1, 'C' => 1, 'O' => 3]],
    ['H₂SO₄', ['H' => 2, 'S' => 1, 'O' => 4]], ['NaOH', ['Na' => 1, 'O' => 1, 'H' => 1]], ['NH₃', ['N' => 1, 'H' => 3]], ['CH₄', ['C' => 1, 'H' => 4]],
    ['Ca(OH)₂', ['Ca' => 1, 'O' => 2, 'H' => 2]], ['HCl', ['H' => 1, 'Cl' => 1]], ['KOH', ['K' => 1, 'O' => 1, 'H' => 1]], ['MgO', ['Mg' => 1, 'O' => 1]],
    ['Na₂CO₃', ['Na' => 2, 'C' => 1, 'O' => 3]], ['C₆H₁₂O₆', ['C' => 6, 'H' => 12, 'O' => 6]], ['CuSO₄', ['Cu' => 1, 'S' => 1, 'O' => 4]], ['Al₂O₃', ['Al' => 2, 'O' => 3]],
];
$key = '(H = 1, C = 12, N = 14, O = 16, Na = 23, Mg = 24, Al = 27, S = 32, Cl = 35.5, K = 39, Ca = 40, Cu = 64)';
foreach ($compounds as [$name, $f]) {
    $M = $mm($f);
    $c(mc('Mole concept', 'medium', "Calculate the molar mass of {$name}. {$key}", $M, [$M + 2, $M - 2, $M * 2, round($M / 2, 1)], "Add the atomic masses of every atom in {$name}: " . qfmt($M) . ' g/mol.', $n++));
}
foreach ([[0, 2], [1, 3], [3, 5], [4, 2], [7, 4], [9, 2], [10, 3], [12, 5]] as [$i, $k]) {
    [$name, $f] = $compounds[$i]; $M = $mm($f); $mass_g = $M * $k;
    $c(mc('Mole concept', 'medium', "How many moles are present in " . qfmt($mass_g) . " g of {$name}? {$key}", $k, [$k + 1, $k * 2, $k / 2, $mass_g / ($M + 1)], "n = mass ÷ molar mass = " . qfmt($mass_g) . ' ÷ ' . qfmt($M) . " = {$k} mol.", $n++));
}
foreach ([[0, 0.5], [1, 2], [2, 0.2], [4, 3], [6, 1.5], [10, 0.25]] as [$i, $nn]) {
    [$name, $f] = $compounds[$i]; $M = $mm($f);
    $c(mc('Mole concept', 'medium', "What is the mass of " . qfmt($nn) . " mol of {$name}? {$key}", $M * $nn, [$M + $nn, $M / $nn, $M * $nn * 2, $M * $nn / 2], "mass = n × M = " . qfmt($nn) . ' × ' . qfmt($M) . ' = ' . qfmt($M * $nn) . ' g.', $n++));
}
foreach ([0.5, 2, 0.25, 3, 1.5, 4] as $mol) {
    $c(mc('Gas volumes', 'medium', 'What volume does ' . qfmt($mol) . ' mol of any gas occupy at s.t.p.? (Molar volume = 22.4 dm³)', $mol * 22.4, [$mol * 22.4 * 2, $mol / 22.4, $mol * 24, $mol * 22.4 / 2], 'V = n × 22.4 = ' . qfmt($mol) . ' × 22.4 = ' . qfmt($mol * 22.4) . ' dm³.', $n++));
}
foreach ([11.2, 44.8, 5.6, 67.2, 22.4, 89.6] as $vol) {
    $c(mc('Gas volumes', 'medium', "How many moles of gas are present in " . qfmt($vol) . ' dm³ at s.t.p.? (Molar volume = 22.4 dm³)', $vol / 22.4, [$vol * 22.4, $vol / 22.4 * 2, $vol / 11.2 + 1, $vol / 44.8], 'n = V ÷ 22.4 = ' . qfmt($vol) . ' ÷ 22.4 = ' . qfmt($vol / 22.4) . ' mol.', $n++));
}
foreach ([[2, 0.5], [4, 2], [0.5, 0.25], [1, 0.5], [3, 1.5], [0.2, 0.1]] as [$mol, $V]) {
    $conc = $mol / $V;
    $c(mc('Solutions and concentration', 'medium', qfmt($mol) . ' mol of a solute is dissolved in ' . qfmt($V) . ' dm³ of solution. Find the concentration in mol/dm³.', $conc, [$mol * $V, $conc * 2, $conc / 2, $V / $mol], 'C = n ÷ V = ' . qfmt($mol) . ' ÷ ' . qfmt($V) . ' = ' . qfmt($conc) . ' mol/dm³.', $n++));
}
foreach ([[0, 2, 0.5], [1, 1, 0.5], [3, 0.5, 0.25], [2, 1, 1], [5, 0.2, 0.5]] as [$i, $conc, $V]) {
    [$name, $f] = $compounds[$i]; $M = $mm($f); $g = $conc * $V * $M;
    $c(mc('Solutions and concentration', 'hard', "What mass of {$name} is needed to prepare " . qfmt($V) . ' dm³ of ' . qfmt($conc) . " mol/dm³ solution? {$key}", $g, [$conc * $M, $V * $M, $g * 2, $g / 2], 'n = C × V = ' . qfmt($conc * $V) . ' mol; mass = n × M = ' . qfmt($conc * $V) . ' × ' . qfmt($M) . ' = ' . qfmt($g) . ' g.', $n++));
}
foreach ([[2, 25, 0.5], [1, 100, 0.25], [4, 50, 1], [0.5, 200, 0.2], [2, 100, 0.8], [1, 50, 0.1]] as [$C1, $V1, $C2]) {
    $V2 = $C1 * $V1 / $C2;
    $c(mc('Solutions and concentration', 'hard', qfmt($V1) . ' cm³ of ' . qfmt($C1) . ' mol/dm³ solution is diluted until its concentration is ' . qfmt($C2) . ' mol/dm³. What is the final volume?', $V2 . ' cm³', [($V1 / 2) . ' cm³', ($V1 + $V2) . ' cm³', ($V2 / 2) . ' cm³', ($V2 * 2) . ' cm³'], 'C₁V₁ = C₂V₂, so V₂ = (' . qfmt($C1) . ' × ' . qfmt($V1) . ') ÷ ' . qfmt($C2) . ' = ' . qfmt($V2) . ' cm³.', $n++));
}
foreach ([[2, 3], [1, 4], [3, 5], [4, 1], [5, 2], [6, 6]] as [$i, $k]) {
    $c(mc('pH and acidity', 'medium', "A solution has a hydrogen ion concentration of 1 × 10⁻{$k} mol/dm³. What is its pH?", $k, [14 - $k, $k + 1, $k - 1, -$k], "pH = −log[H⁺] = −log(10⁻{$k}) = {$k}.", $n++));
}
foreach ([3, 5, 9, 11, 12, 2] as $ph) {
    $c(mc('pH and acidity', 'medium', "A solution has a pH of {$ph}. It is", $ph < 7 ? 'acidic' : 'alkaline (basic)', [$ph < 7 ? 'alkaline (basic)' : 'acidic', 'neutral', 'a buffer only', 'saturated'], "pH below 7 is acidic, above 7 is alkaline; pH {$ph} is " . ($ph < 7 ? 'acidic' : 'alkaline') . '.', $n++));
}
foreach ([[1, 2], [2, 3], [3, 4], [0.5, 2], [2, 6], [4, 3]] as [$mol, $e]) {
    $part = $mol * 6.02;
    $c(mc('Mole concept', 'hard', 'How many molecules are there in ' . qfmt($mol) . ' mol of a gas? (Avogadro constant = 6.02 × 10²³ per mol)', qfmt($part) . ' × 10²³', [qfmt($mol + 6.02) . ' × 10²³', qfmt($mol / 6.02) . ' × 10²³', qfmt($part) . ' × 10²⁴', qfmt($part / 10) . ' × 10²³'], 'N = n × L = ' . qfmt($mol) . ' × 6.02 × 10²³ = ' . qfmt($part) . ' × 10²³.', $n++));
}
foreach ([[2, 4, 1], [4, 2, 4], [6, 3, 2], [1, 2, 3], [3, 6, 1.5], [8, 4, 2]] as [$P1, $V1, $P2]) {
    $V2 = $P1 * $V1 / $P2;
    $c(mc('Gas laws', 'medium', 'A gas occupies ' . qfmt($V1) . ' dm³ at ' . qfmt($P1) . ' atm. At constant temperature, what volume does it occupy at ' . qfmt($P2) . ' atm? (Boyle\'s law)', $V2, [$V1 * $P2 / $P1, $V1 + $P2, $V2 * 2, $V1 - $P1], 'P₁V₁ = P₂V₂, so V₂ = (' . qfmt($P1) . ' × ' . qfmt($V1) . ') ÷ ' . qfmt($P2) . ' = ' . qfmt($V2) . ' dm³.', $n++));
}
foreach ([[100, 27, 327], [200, 27, 177], [300, 27, 87], [50, 127, 527], [120, 27, -123], [80, 127, -73]] as [$V1, $t1, $t2]) {
    $T1 = $t1 + 273; $T2 = $t2 + 273; $V2 = $V1 * $T2 / $T1;
    $c(mc('Gas laws', 'hard', 'A gas occupies ' . $V1 . " cm³ at {$t1}°C. What volume does it occupy at {$t2}°C and the same pressure? (Charles' law)", $V2 . ' cm³', [($V1 * $t2 / $t1) . ' cm³', ($V1 * $T1 / $T2) . ' cm³', ($V1 * 2) . ' cm³', ($V1 + ($t2 - $t1)) . ' cm³'], "V₁/T₁ = V₂/T₂ with temperatures in kelvin: V₂ = {$V1} × {$T2} ÷ {$T1} = " . qfmt($V2) . ' cm³.', $n++));
}
foreach ([['H₂O', 'H', 2, 18, 11.1], ['CO₂', 'C', 1, 44, 27.3], ['NH₃', 'N', 1, 17, 82.4], ['CH₄', 'C', 1, 16, 75], ['MgO', 'Mg', 1, 40, 60], ['CaCO₃', 'Ca', 1, 100, 40]] as [$cmp, $el, $k, $M, $pct]) {
    $c(mc('Percentage composition', 'hard', "Find the percentage by mass of " . ($el === 'H' ? 'hydrogen' : ['C' => 'carbon', 'N' => 'nitrogen', 'Mg' => 'magnesium', 'Ca' => 'calcium'][$el]) . " in {$cmp}, to the nearest whole number or one decimal place. {$key}", qfmt($pct) . '%', [qfmt(100 - $pct) . '%', qfmt($pct / 2) . '%', qfmt(min(99, $pct + 10)) . '%', qfmt($pct * 2) . '%'], "% = (mass of element ÷ molar mass) × 100 = (" . qfmt($mass[$el] * $k) . ' ÷ ' . qfmt($M) . ') × 100 ≈ ' . qfmt($pct) . '%.', $n++));
}

return ['Physics' => $phy, 'Chemistry' => $chm];
