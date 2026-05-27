<?php
$pdo = new PDO('mysql:host=localhost;dbname=draugiem', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);