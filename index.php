<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Stipendiju aprēķins</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; }
        h1 { color: #333; }
        .section { background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        input, button { padding: 8px 12px; margin: 5px; border-radius: 4px; border: 1px solid #ccc; }
        button { background: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background: #45a049; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <h1>Stipendiju aprēķins</h1>

    <!-- Section 1: Upload subjects Excel -->
    <div class="section">
        <h2>1. Mācību priekšmeti</h2>
        <form action="import_subjects.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="subjects_file" accept=".xlsx" required>
            <button type="submit">Augšupielādēt priekšmetus</button>
        </form>
    </div>

    <!-- Section 2: Settings -->
    <div class="section">
        <h2>2. Iestatījumi</h2>
        <form action="save_settings.php" method="POST">
            <label>Sākuma datums: <input type="date" name="start_date" required></label>
            <label>Beigu datums: <input type="date" name="end_date" required></label>
            <label>Mēneša budžets (EUR): <input type="number" step="0.01" name="budget" required></label>
            <button type="submit">Saglabāt</button>
        </form>
    </div>

    <!-- Section 3: Scholarship table -->
    <div class="section">
        <h2>3. Stipendijas apjoma tabula</h2>
        <?php
        require 'db.php';
        $rows = $pdo->query("SELECT * FROM scholarship_table ORDER BY grade_from")->fetchAll();
        ?>
        <form action="save_scholarship_table.php" method="POST">
            <table>
                <tr><th>No</th><th>Līdz</th><th>Summa (EUR)</th></tr>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= $row['grade_from'] ?></td>
                    <td><?= $row['grade_to'] ?></td>
                    <td><input type="number" step="0.01" name="amount[<?= $row['id'] ?>]" value="<?= $row['amount'] ?>"></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <button type="submit">Saglabāt tabulu</button>
        </form>
    </div>

    <!-- Section 4: Upload grades -->
    <div class="section">
        <h2>4. Augšupielādēt vērtējumus</h2>
        <form action="import_grades.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="grades_file" accept=".xlsx" required>
            <button type="submit">Augšupielādēt vērtējumus</button>
        </form>
    </div>

    <!-- Section 5: Calculate -->
    <div class="section">
        <h2>5. Aprēķināt stipendijas</h2>
        <form action="calculate.php" method="POST">
            <button type="submit">Aprēķināt</button>
        </form>
    </div>
</body>
</html>