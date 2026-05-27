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
            $subjectGrades[$subject] = $g['grade'];
        } elseif ($g['grade_type'] === 'II semestra vērtējums' && !isset($subjectGrades[$subject])) {
            $subjectGrades[$subject] = $g['grade'];
        }
    }

    if (empty($subjectGrades)) continue;

    $failCount = 0;
    foreach ($subjectGrades as $subject => $grade) {
        $category = $subjectCategories[$subject] ?? 'VIMP';
        $minGrade = ($category === 'PROF') ? 5.0 : 4.0;
        if ($grade < $minGrade) $failCount++;
    }

    $avg = array_sum($subjectGrades) / count($subjectGrades);
    $scholarship = 0;

    if ($failCount >= 2) {
        $scholarship = 0;
    } elseif ($failCount === 1) {
        $scholarship = 15.00;
    } else {
        foreach ($scholarshipTable as $row) {
            if ($avg >= $row['grade_from'] && $avg < $row['grade_to']) {
                $scholarship = $row['amount'];
                break;
            }
        }
    }

    $totalScholarship += $scholarship;
    $results[] = [
        'last_name' => $student['last_name'],
        'first_name' => $student['first_name'],
        'personal_code' => $student['personal_code'],
        'group' => $student['class_group'],
        'average' => round($avg, 2),
        'scholarship' => $scholarship,
        'fail_count' => $failCount
    ];
}

$difference = $budget - $totalScholarship;
$month = (int)(new DateTime($startDate))->format('m');
$semester = ($month >= 9 || $month <= 1) ? '1. semestris' : '2. semestris';
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
            margin-bottom: 28px;
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

        .card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            border: 1px solid #efefef;
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
        }
        td {
            padding: 13px 16px;
            border-bottom: 1px solid #f5f5f5;
            color: #333;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-blue { background: #e8f4fd; color: #1565c0; }
        .badge-red { background: #ffebee; color: #c62828; }
        .badge-gray { background: #f5f5f5; color: #888; }
        .badge-green { background: #e8f5e9; color: #2e7d32; }

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
            <a href="export.php" class="btn btn-dark">📥 Eksportēt</a>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-label">Kopējā summa</div>
            <div class="summary-value"><?= number_format($totalScholarship, 2) ?> €</div>
            <div class="summary-sub"><?= count($results) ?> studenti</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Mēneša budžets</div>
            <div class="summary-value"><?= number_format($budget, 2) ?> €</div>
            <div class="summary-sub">Pieejamie līdzekļi</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Starpība</div>
            <div class="summary-value <?= $difference < 0 ? 'over' : 'under' ?>">
                <?= number_format($difference, 2) ?> €
            </div>
            <div class="summary-sub"><?= $difference < 0 ? 'Pārsniedz budžetu!' : 'Iekļaujas budžetā' ?></div>
        </div>
    </div>

    <!-- Results table -->
    <div class="card">
        <table>
            <tr>
                <th>Uzvārds</th>
                <th>Vārds</th>
                <th>Personas kods</th>
                <th>Grupa</th>
                <th>Vid. vērtējums</th>
                <th>Stipendija</th>
            </tr>
            <?php foreach ($results as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['last_name']) ?></strong></td>
                <td><?= htmlspecialchars($r['first_name']) ?></td>
                <td style="color:#888;font-size:13px"><?= htmlspecialchars($r['personal_code']) ?></td>
                <td><span class="badge badge-blue"><?= htmlspecialchars($r['group']) ?></span></td>
                <td><strong><?= $r['average'] ?></strong></td>
                <td>
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
        </table>
    </div>

</div>
</body>
</html>