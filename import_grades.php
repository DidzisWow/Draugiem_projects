<?php
require 'vendor/autoload.php';
require 'db.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_FILES['grades_file']) || $_FILES['grades_file']['error'] !== 0) {
    die('Nav augšupielādēta datne! <a href="index.php">Atpakaļ</a>');
}

try {
    $file = $_FILES['grades_file']['tmp_name'];
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    // Delete old students and grades
    $pdo->exec("DELETE FROM grades");
    $pdo->exec("DELETE FROM students");

    $classGroup = $rows[0][0];
    $lastNames = $rows[1];
    $firstNames = $rows[2];
    $personalCodes = $rows[3];

    // Insert students - cols 5,6,7
    $studentIds = [];
    for ($col = 5; $col <= 7; $col++) {
        if (empty($firstNames[$col])) continue;

        $stmt = $pdo->prepare("INSERT INTO students (first_name, last_name, personal_code, class_group) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $firstNames[$col],
            $lastNames[$col] ?? '',
            $personalCodes[$col] ?? '',
            $classGroup
        ]);
        $studentIds[$col] = $pdo->lastInsertId();
    }

    // Read grades from row 4 onwards
    $currentSubject = '';
    for ($row = 4; $row < count($rows); $row++) {
        $rowData = $rows[$row];

        if (!empty($rowData[1])) {
            $currentSubject = $rowData[1];
        }

        $gradeType = $rowData[4] ?? '';
        $dateStr = $rowData[3] ?? '';

        if (!in_array($gradeType, ['II semestra vērtējums', 'Galīgais vērtējums priekšmetā'])) {
            continue;
        }

        $date = null;
        if (!empty($dateStr)) {
            $date = date('Y-m-d', strtotime($dateStr));
        }

        foreach ($studentIds as $col => $studentId) {
            $grade = $rowData[$col] ?? null;
            if ($grade === null || $grade === '') continue;

            // Handle nv as 0
            if (strtolower(trim($grade)) === 'nv') {
                $grade = 0;
            }

            if (!is_numeric($grade)) continue;

            $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject, grade_type, grade, grade_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$studentId, $currentSubject, $gradeType, $grade, $date]);
        }
    }

    header('Location: index.php?msg=grades_imported');
    exit;

} catch (Exception $e) {
    die('Kļūda: ' . $e->getMessage() . ' <a href="index.php">Atpakaļ</a>');
}