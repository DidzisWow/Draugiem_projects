<?php
require 'db.php';

$pdo->exec("DELETE FROM grades");
$pdo->exec("DELETE FROM students");
$pdo->exec("DELETE FROM subjects");

header('Location: index.php?msg=reset');
exit;