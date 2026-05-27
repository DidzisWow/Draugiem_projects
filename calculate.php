<?php
require 'db.php';

$settings = $pdo->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1")->fetch();
if (!$settings) {
    die('Nav saglabāti iestatījumi! <a href="index.php">Atpakaļ</a>');
}

$startDate = $settings['start_date'];
$endDate = $settings['end_date'];
$budget = $settings['monthly_budget'];

$students = $pdo->query("SELECT * FROM students")->fetchAll();
$scholarshipTable = $pdo->query("SELECT * FROM scholarship_table ORDER BY grade_from")->fetchAll();

$subjectsRaw = $pdo->query("SELECT * FROM subjects")->fetchAll();
$subjectCategories = [];
foreach ($subjectsRaw as $s) {
    $subjectCategories[$s['name']] = $s['category'];
}

$results = [];
$totalScholarship = 0;
$allStudentGrades = [];

foreach ($students as $student) {
    $stmt = $pdo->prepare("
        SELECT g.subject, g.grade_type, g.grade
        FROM grades g
        WHERE g.student_id = ?
        AND g.grade_date BETWEEN ? AND ?
        ORDER BY g.subject, g.grade_type
    ");
    $stmt->execute([$student['id'], $startDate, $endDate]);
    $grades = $stmt->fetchAll();

    $subjectGrades = [];
    foreach ($grades as $g) {
        $subject = $g['subject'];
        if ($g['grade_type'] === 'Galīgais vērtējums priekšmetā') {
            $subjectGrades[$subject] = ['grade' => $g['grade'], 'type' => $g['grade_type']];
        } elseif ($g['grade_type'] === 'II semestra vērtējums' && !isset($subjectGrades[$subject])) {
            $subjectGrades[$subject] = ['grade' => $g['grade'], 'type' => $g['grade_type']];
        }
    }

    if (empty($subjectGrades)) continue;

    $failCount = 0;
    foreach ($subjectGrades as $subject => $data) {
        $category = $subjectCategories[$subject] ?? 'VIMP';
        $minGrade = ($category === 'PROF') ? 5.0 : 4.0;
        if ($data['grade'] < $minGrade) $failCount++;
    }

    $grades_only = array_map(fn($d) => $d['grade'], $subjectGrades);
    $avg = array_sum($grades_only) / count($grades_only);
    $scholarship = 0;

    if ($failCount >= 2) {
        $scholarship = 0;
    } elseif ($failCount === 1) {
        $scholarship = 15.00;
    } else {
        foreach ($scholarshipTable as $i => $row) {
            $isLast = $i === count($scholarshipTable) - 1;
            if ($avg >= $row['grade_from'] && ($isLast ? $avg <= $row['grade_to'] : $avg < $row['grade_to'])) {
                $scholarship = $row['amount'];
                break;
            }
        }
    }

    $totalScholarship += $scholarship;
    $results[] = [
        'id' => $student['id'],
        'last_name' => $student['last_name'],
        'first_name' => $student['first_name'],
        'personal_code' => $student['personal_code'],
        'group' => $student['class_group'],
        'average' => round($avg, 2),
        'scholarship' => $scholarship,
        'fail_count' => $failCount
    ];
    $allStudentGrades[$student['id']] = $subjectGrades;
}

$difference = $budget - $totalScholarship;
$month = (int)(new DateTime($startDate))->format('m');
$semester = ($month >= 9) ? '1. semestris' : '2. semestris';

// Stats
$zeroCount = count(array_filter($results, fn($r) => $r['scholarship'] == 0 && $r['fail_count'] >= 2));
$lowCount  = count(array_filter($results, fn($r) => $r['fail_count'] === 1));
$fullCount = count(array_filter($results, fn($r) => $r['fail_count'] === 0));
$maxScholarship = $results ? max(array_column($results, 'scholarship')) : 0;
$maxAvg = $results ? max(array_column($results, 'average')) : 0;
$minAvg = $results ? min(array_column($results, 'average')) : 0;

$scholarshipTableJson = json_encode($scholarshipTable);
$subjectCategoriesJson = json_encode($subjectCategories);
$allStudentGradesJson = json_encode($allStudentGrades);
$budget_js = $budget;
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Stipendiju rezultāti</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8f9fb;
            min-height: 100vh;
            color: #1a1a1a;
        }
        .container { max-width: 1000px; margin: 0 auto; padding: 48px 24px; }

        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .page-title { font-size: 28px; font-weight: 700; color: #111; }
        .page-subtitle { font-size: 13px; color: #888; margin-top: 4px; }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-dark { background: #111; color: white; }
        .btn-blue { background: #e8f4fd; color: #1565c0; }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }
        .summary-card {
            background: white;
            border-radius: 14px;
            padding: 20px 24px;
            border: 1px solid #efefef;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .summary-label {
            font-size: 12px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .summary-value {
            font-size: 24px;
            font-weight: 700;
            color: #111;
        }
        .summary-value.over { color: #c62828; }
        .summary-value.under { color: #2e7d32; }
        .summary-sub {
            font-size: 12px;
            color: #aaa;
            margin-top: 4px;
        }

        /* Stats bar */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 14px 16px;
            border: 1px solid #efefef;
            text-align: center;
        }
        .stat-label { font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 6px; }
        .stat-value { font-size: 20px; font-weight: 700; color: #111; }
        .stat-value.red { color: #c62828; }
        .stat-value.orange { color: #e65100; }
        .stat-value.green { color: #2e7d32; }

        .card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            border: 1px solid #efefef;
            margin-bottom: 24px;
        }
        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: #111;
            margin-bottom: 20px;
        }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th {
            background: #f8f9fb;
            color: #888;
            padding: 12px 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #efefef;
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        th:hover { color: #111; }
        th .sort-arrow { margin-left: 4px; opacity: 0.4; }
        th.sorted .sort-arrow { opacity: 1; color: #1565c0; }

        td {
            padding: 13px 16px;
            border-bottom: 1px solid #f5f5f5;
            color: #333;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { filter: brightness(0.97); }

        /* Row coloring */
        tr.row-zero td { background: #fff5f5; }
        tr.row-low td { background: #fff8f0; }
        tr.row-ok td { background: #f5fbf5; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-blue { background: #e8f4fd; color: #1565c0; }
        .badge-vimp { background: #e8f4fd; color: #1565c0; }
        .badge-prof { background: #f3e5f5; color: #6a1b9a; }

        .amount { font-weight: 700; font-size: 15px; }
        .amount-zero { color: #c62828; }
        .amount-low { color: #e65100; }
        .amount-ok { color: #2e7d32; }

        .fail-note {
            font-size: 11px;
            color: #c62828;
            display: block;
            margin-top: 2px;
        }
        .student-link {
            color: #111;
            text-decoration: none;
            font-weight: 600;
        }
        .student-link:hover { color: #1565c0; text-decoration: underline; }

        .group-student {
            border: 1px solid #efefef;
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
        }
        .group-student-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            background: #f8f9fb;
            border-bottom: 1px solid #efefef;
            flex-wrap: wrap;
            gap: 8px;
        }
        .group-student-name { font-weight: 700; font-size: 14px; color: #111; }
        .group-student-meta { font-size: 12px; color: #888; margin-top: 2px; }
        .group-subject-row {
            display: flex;
            align-items: center;
            padding: 10px 18px;
            border-bottom: 1px solid #f5f5f5;
            gap: 12px;
        }
        .group-subject-row:last-child { border-bottom: none; }
        .group-subject-row.excluded { opacity: 0.4; text-decoration: line-through; }
        .group-subject-name { flex: 1; font-size: 13px; color: #333; }
        .group-subject-type { font-size: 11px; color: #aaa; min-width: 200px; }
        .group-subject-grade { font-weight: 700; font-size: 14px; min-width: 50px; }
        .grade-fail-color { color: #c62828; }
        .grade-ok-color { color: #2e7d32; }
        .group-subject-cat { min-width: 60px; }
        input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: #1565c0; }
    </style>
</head>
<body>
<div class="container">

    <div class="top-bar">
        <div>
            <div class="page-title">Stipendiju rezultāti</div>
            <div class="page-subtitle">
                <?= $startDate ?> → <?= $endDate ?> &nbsp;·&nbsp;
                <span style="color:#1565c0"><?= $semester ?></span>
            </div>
        </div>
        <div style="display:flex;gap:10px">
            <a href="index.php" class="btn btn-blue">← Atpakaļ</a>
            <a href="export.php" class="btn btn-dark"> Eksportēt</a>
        </div>
    </div>

    <!-- Budget summary -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">Kopējā summa</div>
            <div class="summary-value" id="total-sum"><?= number_format($totalScholarship, 2) ?> €</div>
            <div class="summary-sub"><?= count($results) ?> studenti</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Mēneša budžets</div>
            <div class="summary-value"><?= number_format($budget, 2) ?> €</div>
            <div class="summary-sub">Pieejamie līdzekļi</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Starpība</div>
            <div class="summary-value" id="difference-val"><?= number_format($difference, 2) ?> €</div>
            <div class="summary-sub" id="difference-note"><?= $difference < 0 ? 'Pārsniedz budžetu!' : 'Iekļaujas budžetā' ?></div>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Bez stipendijas</div>
            <div class="stat-value red"><?= $zeroCount ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">15 € stipendija</div>
            <div class="stat-value orange"><?= $lowCount ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pilna stipendija</div>
            <div class="stat-value green"><?= $fullCount ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Augstākā stipendija</div>
            <div class="stat-value"><?= number_format($maxScholarship, 2) ?> €</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Augstākais vid.</div>
            <div class="stat-value green"><?= $maxAvg ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Zemākais vid.</div>
            <div class="stat-value red"><?= $minAvg ?></div>
        </div>
    </div>

    <!-- Main results table -->
    <div class="card">
        <table id="results-table">
            <thead>
            <tr>
                <th onclick="sortTable(0)">Uzvārds <span class="sort-arrow">↕</span></th>
                <th onclick="sortTable(1)">Vārds <span class="sort-arrow">↕</span></th>
                <th>Personas kods</th>
                <th>Grupa</th>
                <th onclick="sortTable(4)">Vid. vērtējums <span class="sort-arrow">↕</span></th>
                <th onclick="sortTable(5)">Stipendija <span class="sort-arrow">↕</span></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($results as $r):
                if ($r['fail_count'] >= 2) $rowClass = 'row-zero';
                elseif ($r['fail_count'] === 1) $rowClass = 'row-low';
                else $rowClass = 'row-ok';
            ?>
            <tr class="<?= $rowClass ?>">
                <td><a href="student.php?id=<?= $r['id'] ?>" class="student-link"><?= htmlspecialchars($r['last_name']) ?></a></td>
                <td><?= htmlspecialchars($r['first_name']) ?></td>
                <td style="color:#888;font-size:13px"><?= htmlspecialchars($r['personal_code']) ?></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($r['group']) ?></span></td>
                <td><strong id="avg-<?= $r['id'] ?>"><?= $r['average'] ?></strong></td>
                <td id="scholarship-cell-<?= $r['id'] ?>">
                    <?php if ($r['fail_count'] >= 2): ?>
                        <span class="amount amount-zero">0.00 €</span>
                        <span class="fail-note">Nav tiesību (<?= $r['fail_count'] ?> nesekmīgi)</span>
                    <?php elseif ($r['fail_count'] === 1): ?>
                        <span class="amount amount-low">15.00 €</span>
                        <span class="fail-note">1 nesekmīgs priekšmets</span>
                    <?php else: ?>
                        <span class="amount amount-ok"><?= number_format($r['scholarship'], 2) ?> €</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Group view with checkboxes -->
    <div class="card">
        <div class="card-title">Grupas skats — vērtējumu pārvaldība</div>
        <?php foreach ($results as $r):
            $grades = $allStudentGrades[$r['id']] ?? [];
        ?>
        <div class="group-student">
            <div class="group-student-header">
                <div>
                    <div class="group-student-name"><?= htmlspecialchars($r['last_name'] . ' ' . $r['first_name']) ?></div>
                    <div class="group-student-meta"><?= htmlspecialchars($r['group']) ?> · <?= htmlspecialchars($r['personal_code']) ?></div>
                </div>
                <div>
                    <span style="font-size:15px;font-weight:700" id="gs-<?= $r['id'] ?>"><?= number_format($r['scholarship'], 2) ?> €</span>
                    <span style="font-size:12px;color:#888;margin-left:6px">Vid.: <span id="ga-<?= $r['id'] ?>"><?= $r['average'] ?></span></span>
                </div>
            </div>
            <?php foreach ($grades as $subject => $data):
                $cat = $subjectCategories[$subject] ?? 'VIMP';
                $minGrade = ($cat === 'PROF') ? 5.0 : 4.0;
                $fail = $data['grade'] < $minGrade;
                $safeId = 'cb_' . $r['id'] . '_' . preg_replace('/[^a-zA-Z0-9]/u', '_', $subject);
            ?>
            <div class="group-subject-row" id="row_<?= $safeId ?>">
                <input type="checkbox" id="<?= $safeId ?>"
                    checked
                    data-student="<?= $r['id'] ?>"
                    data-subject="<?= htmlspecialchars($subject, ENT_QUOTES) ?>"
                    onchange="recalculate(<?= $r['id'] ?>)">
                <span class="group-subject-name"><?= htmlspecialchars($subject) ?></span>
                <span class="group-subject-type"><?= htmlspecialchars($data['type']) ?></span>
                <span class="group-subject-grade <?= $fail ? 'grade-fail-color' : 'grade-ok-color' ?>"><?= $data['grade'] ?></span>
                <span class="group-subject-cat">
                    <span class="badge <?= $cat === 'PROF' ? 'badge-prof' : 'badge-vimp' ?>"><?= $cat ?></span>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

</div>
<script>
const scholarshipTable = <?= $scholarshipTableJson ?>;
const subjectCategories = <?= $subjectCategoriesJson ?>;
const allGrades = <?= $allStudentGradesJson ?>;
const budget = <?= $budget_js ?>;

// Sorting
let sortDir = {};
function sortTable(col) {
    const table = document.getElementById('results-table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const ths = table.querySelectorAll('th');

    sortDir[col] = !sortDir[col];

    rows.sort((a, b) => {
        let aVal = a.cells[col].innerText.replace(' €', '').replace(',', '.').trim();
        let bVal = b.cells[col].innerText.replace(' €', '').replace(',', '.').trim();
        const aNum = parseFloat(aVal);
        const bNum = parseFloat(bVal);
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return sortDir[col] ? aNum - bNum : bNum - aNum;
        }
        return sortDir[col] ? aVal.localeCompare(bVal, 'lv') : bVal.localeCompare(aVal, 'lv');
    });

    ths.forEach(th => th.classList.remove('sorted'));
    ths[col].classList.add('sorted');
    ths[col].querySelector('.sort-arrow').textContent = sortDir[col] ? '↑' : '↓';

    rows.forEach(r => tbody.appendChild(r));
}

function calcScholarship(avg, failCount) {
    if (failCount >= 2) return { amount: 0, failCount };
    if (failCount === 1) return { amount: 15.00, failCount };
    for (let i = 0; i < scholarshipTable.length; i++) {
        const row = scholarshipTable[i];
        const isLast = i === scholarshipTable.length - 1;
        if (avg >= row.grade_from && (isLast ? avg <= row.grade_to : avg < row.grade_to)) {
            return { amount: parseFloat(row.amount), failCount };
        }
    }
    return { amount: 0, failCount };
}

function recalculate(studentId) {
    const grades = allGrades[studentId];
    if (!grades) return;

    const activeGrades = {};
    for (const subject in grades) {
        const safeId = 'cb_' + studentId + '_' + subject.replace(/[^a-zA-Z0-9]/g, '_');
        const cb = document.getElementById(safeId);
        const row = document.getElementById('row_' + safeId);
        if (cb && cb.checked) {
            activeGrades[subject] = grades[subject];
            if (row) row.classList.remove('excluded');
        } else {
            if (row) row.classList.add('excluded');
        }
    }

    if (Object.keys(activeGrades).length === 0) {
        document.getElementById('gs-' + studentId).textContent = '0.00 €';
        document.getElementById('ga-' + studentId).textContent = '0.00';
        document.getElementById('avg-' + studentId).textContent = '0.00';
        updateSummaryRow(studentId, 0, 0, 0);
        updateTotals();
        return;
    }

    let failCount = 0;
    let sum = 0;
    let count = 0;
    for (const subject in activeGrades) {
        const grade = parseFloat(activeGrades[subject].grade);
        const cat = subjectCategories[subject] ?? 'VIMP';
        const minGrade = cat === 'PROF' ? 5.0 : 4.0;
        if (grade < minGrade) failCount++;
        sum += grade;
        count++;
    }

    const avg = Math.round((sum / count) * 100) / 100;
    const result = calcScholarship(avg, failCount);

    document.getElementById('gs-' + studentId).textContent = result.amount.toFixed(2) + ' €';
    document.getElementById('ga-' + studentId).textContent = avg.toFixed(2);
    document.getElementById('avg-' + studentId).textContent = avg.toFixed(2);

    updateSummaryRow(studentId, result.amount, avg, failCount);
    updateTotals();
}

function updateSummaryRow(studentId, amount, avg, failCount) {
    const cell = document.getElementById('scholarship-cell-' + studentId);
    if (!cell) return;
    const row = cell.closest('tr');
    row.className = failCount >= 2 ? 'row-zero' : failCount === 1 ? 'row-low' : 'row-ok';
    if (failCount >= 2) {
        cell.innerHTML = '<span class="amount amount-zero">0.00 €</span><span class="fail-note">Nav tiesību (' + failCount + ' nesekmīgi)</span>';
    } else if (failCount === 1) {
        cell.innerHTML = '<span class="amount amount-low">15.00 €</span><span class="fail-note">1 nesekmīgs priekšmets</span>';
    } else {
        cell.innerHTML = '<span class="amount amount-ok">' + amount.toFixed(2) + ' €</span>';
    }
}

function updateTotals() {
    let total = 0;
    document.querySelectorAll('[id^="gs-"]').forEach(el => {
        total += parseFloat(el.textContent) || 0;
    });
    document.getElementById('total-sum').textContent = total.toFixed(2) + ' €';
    const diff = budget - total;
    const diffEl = document.getElementById('difference-val');
    const noteEl = document.getElementById('difference-note');
    diffEl.textContent = diff.toFixed(2) + ' €';
    diffEl.className = 'summary-value ' + (diff < 0 ? 'over' : 'under');
    noteEl.textContent = diff < 0 ? 'Pārsniedz budžetu!' : 'Iekļaujas budžetā';
}
</script>
</body>
</html>