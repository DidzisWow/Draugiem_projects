<?php
require 'db.php';

// Get settings
$settings = $pdo->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1")->fetch();
if (!$settings) {
    die('Nav saglabāti iestatījumi! <a href="index.php">Atpakaļ</a>');
}

$startDate = $settings['start_date'];
$endDate = $settings['end_date'];
$budget = $settings['monthly_budget'];

// Get all students
$students = $pdo->query("SELECT * FROM students")->fetchAll();

// Get scholarship table
$scholarshipTable = $pdo->query("SELECT * FROM scholarship_table ORDER BY grade_from")->fetchAll();

// Get subjects with categories
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
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Stipendiju rezultāti</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 40px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
        .summary { background: #f5f5f5; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .over { color: red; }
        .under { color: green; }
        button { padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .btn-blue { background: #2196F3; }
        a { text-decoration: none; }
        .fail { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Stipendiju rezultāti</h1>
    <a href="index.php"><button>← Atpakaļ</button></a>
    <a href="export.php"><button class="btn-blue"> Eksportēt uz Excel</button></a>

    <table>
        <tr>
            <th>Uzvārds</th>
            <th>Vārds</th>
            <th>Personas kods</th>
            <th>Grupa</th>
            <th>Vidējais vērtējums</th>
            <th>Stipendija (EUR)</th>
        </tr>
        <?php foreach ($results as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['last_name']) ?></td>
            <td><?= htmlspecialchars($r['first_name']) ?></td>
            <td><?= htmlspecialchars($r['personal_code']) ?></td>
            <td><?= htmlspecialchars($r['group']) ?></td>
            <td><?= $r['average'] ?></td>
            <td class="<?= $r['scholarship'] == 0 && $r['fail_count'] >= 2 ? 'fail' : '' ?>">
                <?= number_format($r['scholarship'], 2) ?> EUR
                <?= $r['fail_count'] >= 2 ? '(Nav tiesību)' : '' ?>
                <?= $r['fail_count'] == 1 ? '(1 nesekmīgs)' : '' ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="summary">
        <h2>Kopsavilkums</h2>
        <p>Kopējā stipendiju summa: <strong><?= number_format($totalScholarship, 2) ?> EUR</strong></p>
        <p>Mēneša budžets: <strong><?= number_format($budget, 2) ?> EUR</strong></p>
        <p class="<?= $difference < 0 ? 'over' : 'under' ?>">
            Starpība: <strong><?= number_format($difference, 2) ?> EUR</strong>
            <?= $difference < 0 ? '(Pārsniedz budžetu!)' : '(Iekļaujas budžetā)' ?>
        </p>
    </div>
</body>
</html>