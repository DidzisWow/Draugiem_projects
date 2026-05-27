<?php
require 'db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();
if (!$student) { header('Location: index.php'); exit; }

$settings = $pdo->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1")->fetch();
$startDate = $settings['start_date'] ?? null;
$endDate   = $settings['end_date']   ?? null;

$stmt = $pdo->prepare("
    SELECT g.subject, g.grade_type, g.grade, g.grade_date
    FROM grades g
    WHERE g.student_id = ?
    AND g.grade_date BETWEEN ? AND ?
    ORDER BY g.subject, g.grade_type
");
$stmt->execute([$id, $startDate, $endDate]);
$grades = $stmt->fetchAll();

$subjectsRaw = $pdo->query("SELECT * FROM subjects")->fetchAll();
$subjectCategories = [];
foreach ($subjectsRaw as $s) {
    $subjectCategories[$s['name']] = $s['category'];
}

$subjectGrades = [];
foreach ($grades as $g) {
    $subject = $g['subject'];
    if ($g['grade_type'] === 'Galīgais vērtējums priekšmetā') {
        $subjectGrades[$subject] = ['grade' => $g['grade'], 'type' => $g['grade_type']];
    } elseif ($g['grade_type'] === 'II semestra vērtējums' && !isset($subjectGrades[$subject])) {
        $subjectGrades[$subject] = ['grade' => $g['grade'], 'type' => $g['grade_type']];
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name']) ?> — Vērtējumi</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f9fb; min-height: 100vh; color: #1a1a1a; }
    .container { max-width: 820px; margin: 0 auto; padding: 48px 24px; }
    .top-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; flex-wrap: wrap; gap: 12px; }
    .page-title { font-size: 26px; font-weight: 700; color: #111; }
    .page-subtitle { font-size: 13px; color: #888; margin-top: 4px; }
    .btn { display: inline-block; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.2s; text-decoration: none; }
    .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    .btn-blue { background: #e8f4fd; color: #1565c0; }
    .card { background: white; border-radius: 16px; padding: 28px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #efefef; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th { background: #f8f9fb; color: #888; padding: 10px 14px; text-align: left; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #efefef; }
    td { padding: 12px 14px; border-bottom: 1px solid #f5f5f5; color: #333; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #fafafa; }
    .badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .badge-vimp { background: #e8f4fd; color: #1565c0; }
    .badge-prof { background: #f3e5f5; color: #6a1b9a; }
    .grade-fail { color: #c62828; font-weight: 700; }
    .grade-ok { color: #2e7d32; font-weight: 700; }
    .type-label { font-size: 12px; color: #aaa; }
</style>
</head>
<body>
<div class="container">
    <div class="top-bar">
        <div>
            <div class="page-title"><?= htmlspecialchars($student['last_name'] . ' ' . $student['first_name']) ?></div>
            <div class="page-subtitle">
                <?= htmlspecialchars($student['class_group']) ?> &nbsp;·&nbsp;
                <?= htmlspecialchars($student['personal_code']) ?> &nbsp;·&nbsp;
                <?= htmlspecialchars($startDate) ?> → <?= htmlspecialchars($endDate) ?>
            </div>
        </div>
        <a href="javascript:history.back()" class="btn btn-blue">← Atpakaļ</a>
    </div>
    <div class="card">
        <?php if (empty($subjectGrades)): ?>
            <p style="font-size:14px;color:#888">Nav vērtējumu izvēlētajā periodā.</p>
        <?php else: ?>
        <table>
            <tr>
                <th>Mācību priekšmets</th>
                <th>Vērtējuma veids</th>
                <th>Vērtējums</th>
                <th>Kategorija</th>
            </tr>
            <?php foreach ($subjectGrades as $subject => $data):
                $cat = $subjectCategories[$subject] ?? 'VIMP';
                $minGrade = ($cat === 'PROF') ? 5.0 : 4.0;
                $fail = $data['grade'] < $minGrade;
            ?>
            <tr>
                <td><?= htmlspecialchars($subject) ?></td>
                <td><span class="type-label"><?= htmlspecialchars($data['type']) ?></span></td>
                <td class="<?= $fail ? 'grade-fail' : 'grade-ok' ?>"><?= $data['grade'] ?></td>
                <td><span class="badge <?= $cat === 'PROF' ? 'badge-prof' : 'badge-vimp' ?>"><?= $cat ?></span></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>