<?php
require 'db.php';

$start = $_POST['start_date'];
$end = $_POST['end_date'];
$budget = $_POST['budget'];

// Delete old settings and save new
$pdo->exec("DELETE FROM settings");
$stmt = $pdo->prepare("INSERT INTO settings (start_date, end_date, monthly_budget) VALUES (?, ?, ?)");
$stmt->execute([$start, $end, $budget]);

header('Location: index.php?msg=settings_saved');
exit;