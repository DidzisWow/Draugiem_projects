<?php
require 'vendor/autoload.php';
require 'db.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== 0) {
    die('No file uploaded or upload error');
}

$file = $_FILES['excel_file']['tmp_name'];
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

// Delete old data first
$pdo->exec("DELETE FROM subjects");

// Insert new data
foreach ($rows as $i => $row) {
    if ($i === 0) continue;
    if (empty($row[0])) continue;

    $stmt = $pdo->prepare("INSERT INTO subjects (name, category) VALUES (?, ?)");
    $stmt->execute([$row[0], $row[1]]);
}

echo "Imported successfully!";