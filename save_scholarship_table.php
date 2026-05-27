<?php
require 'db.php';

foreach ($_POST['amount'] as $id => $amount) {
    $stmt = $pdo->prepare("UPDATE scholarship_table SET amount = ? WHERE id = ?");
    $stmt->execute([$amount, $id]);
}

header('Location: index.php?msg=table_saved');
exit;