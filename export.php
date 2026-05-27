<?php
require 'vendor/autoload.php';
require 'db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Get settings
$settings = $pdo->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1")->fetch();
$startDate = $settings['start_date'];
$endDate = $settings['end_date'];
$budget = $settings['monthly_budget'];

// Get scholarship table
$scholarshipTable = $pdo->query("SELECT * FROM scholarship_table ORDER BY grade_from")->fetchAll();

// Get subjects
$subjectsRaw = $pdo->query("SELECT * FROM subjects")->fetchAll();
$subjectCategories = [];
foreach ($subjectsRaw as $s) {
    $subjectCategories[$s['name']] = $s['category'];
}

// Get all students
$students = $pdo->query("SELECT * FROM students")->fetchAll();

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
        'scholarship' => $scholarship
    ];
}

// Build Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Stipendijas');

// Header row styling
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];

// Headers
$headers = ['Uzvārds', 'Vārds', 'Personas kods', 'Grupa', 'Vidējais vērtējums', 'Stipendija (EUR)'];
foreach ($headers as $i => $header) {
    $col = chr(65 + $i);
    $sheet->setCellValue($col . '1', $header);
    $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Data rows
foreach ($results as $i => $r) {
    $row = $i + 2;
    $sheet->setCellValue('A' . $row, $r['last_name']);
    $sheet->setCellValue('B' . $row, $r['first_name']);
    $sheet->setCellValue('C' . $row, $r['personal_code']);
    $sheet->setCellValue('D' . $row, $r['group']);
    $sheet->setCellValue('E' . $row, $r['average']);
    $sheet->setCellValue('F' . $row, $r['scholarship']);
}

// Summary rows
$summaryRow = count($results) + 3;
$sheet->setCellValue('E' . $summaryRow, 'Kopā:');
$sheet->setCellValue('F' . $summaryRow, $totalScholarship);
$sheet->getStyle('E' . $summaryRow . ':F' . $summaryRow)->getFont()->setBold(true);

$summaryRow++;
$sheet->setCellValue('E' . $summaryRow, 'Budžets:');
$sheet->setCellValue('F' . $summaryRow, $budget);

$summaryRow++;
$sheet->setCellValue('E' . $summaryRow, 'Starpība:');
$sheet->setCellValue('F' . $summaryRow, $budget - $totalScholarship);

// Download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="stipendijas.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;