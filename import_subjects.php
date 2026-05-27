<?php
require 'vendor/autoload.php';
require 'db.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_FILES['subjects_file']) || $_FILES['subjects_file']['error'] !== 0) {
    die('Nav augšupielādēta datne!');
}

$file = $_FILES['subjects_file']['tmp_name'];
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

// Delete old subjects
$pdo->exec("DELETE FROM subjects");

foreach ($rows as $i => $row) {
    if ($i === 0) continue; // skip header
    if (empty($row[0])) continue; // skip empty rows

    $stmt = $pdo->prepare("INSERT INTO subjects (name, category) VALUES (?, ?)");
    $stmt->execute([$row[0], $row[1]]);
}

header('Location: index.php?msg=subjects_imported');
exit;