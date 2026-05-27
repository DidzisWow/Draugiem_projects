<?php
require 'vendor/autoload.php';
require 'db.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_FILES['subjects_file']) || $_FILES['subjects_file']['error'] !== 0) {
    die('Nav augšupielādēta datne! <a href="index.php">Atpakaļ</a>');
}

try {
    $file = $_FILES['subjects_file']['tmp_name'];
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    // Delete old subjects
    $pdo->exec("DELETE FROM subjects");

    foreach ($rows as $i => $row) {
        if ($i === 0) continue;
        if (empty($row[0])) continue;

        $stmt = $pdo->prepare("INSERT INTO subjects (name, category) VALUES (?, ?)");
        $stmt->execute([$row[0], $row[1]]);
    }

    header('Location: index.php?msg=subjects_imported');
    exit;

} catch (Exception $e) {
    die('Kļūda: ' . $e->getMessage() . ' <a href="index.php">Atpakaļ</a>');
}