<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Stipendiju aprēķins</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f8f9fb;
            min-height: 100vh;
            color: #1a1a1a;
        }
        .container { max-width: 960px; margin: 0 auto; padding: 48px 24px; }

        .page-title {
            font-size: 32px;
            font-weight: 700;
            color: #111;
            margin-bottom: 6px;
        }
        .page-subtitle {
            font-size: 14px;
            color: #888;
            margin-bottom: 36px;
        }

        .alert {
            background: #e8f4fd;
            color: #1565c0;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 28px;
            border-left: 4px solid #90caf9;
            font-size: 14px;
        }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .grid-full { grid-column: 1 / -1; }

        .card {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            border: 1px solid #efefef;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        .card-num {
            background: #e8f4fd;
            color: #1565c0;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .card-title {
            font-size: 15px;
            font-weight: 600;
            color: #111;
        }

        .info-box {
            background: #f8f9fb;
            border: 1px solid #e8edf2;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            color: #444;
            margin-bottom: 16px;
        }
        .info-box strong { color: #111; }

        .badge {
            display: inline-block;
            background: #e8f4fd;
            color: #1565c0;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            margin-top: 14px;
        }
        label:first-of-type { margin-top: 0; }

        input[type="date"],
        input[type="number"],
        input[type="file"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            color: #111;
            background: #fafafa;
            transition: border 0.2s;
        }
        input[type="file"] { padding: 8px; }
        input:focus {
            outline: none;
            border-color: #90caf9;
            background: white;
        }

        .btn {
            display: inline-block;
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-top: 16px;
            transition: all 0.2s;
            letter-spacing: 0.2px;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-dark { background: #111; color: white; }
        .btn-red { background: #fdecea; color: #c62828; }
        .btn-full { width: 100%; text-align: center; padding: 14px; font-size: 15px; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th {
            background: #f8f9fb;
            color: #888;
            padding: 10px 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #f5f5f5;
        }
        tr:last-child td { border-bottom: none; }
    </style>
</head>
<body>
<div class="container">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
        <div class="page-title">Stipendiju aprēķins</div>
        <a href="reset.php" class="btn btn-red" onclick="return confirm('Vai tiešām dzēst visus datus?')">🗑 Notīrīt datus</a>
    </div>
    <div class="page-subtitle">Ievadiet datus un aprēķiniet stipendijas</div>

    <?php
    require 'db.php';

    $messages = [
        'subjects_imported' => '✓ Priekšmeti veiksmīgi augšupielādēti!',
        'grades_imported'   => '✓ Vērtējumi veiksmīgi augšupielādēti!',
        'settings_saved'    => '✓ Iestatījumi saglabāti!',
        'table_saved'       => '✓ Stipendijas tabula saglabāta!',
        'reset'             => '✓ Visi dati veiksmīgi dzēsti!'
    ];

    if (isset($_GET['msg']) && isset($messages[$_GET['msg']])): ?>
        <div class="alert"><?= $messages[$_GET['msg']] ?></div>
    <?php endif; ?>

    <div class="grid">

        <!-- Section 1: Subjects -->
        <div class="card">
            <div class="card-header">
                <div class="card-num">1</div>
                <div class="card-title">Mācību priekšmeti</div>
            </div>
            <?php
            $subjectCount = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
            if ($subjectCount > 0): ?>
                <div class="info-box"> Datubāzē: <strong><?= $subjectCount ?></strong> priekšmeti</div>
                <?php
                $vimp = $pdo->query("SELECT name FROM subjects WHERE category='VIMP' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
                $prof = $pdo->query("SELECT name FROM subjects WHERE category='PROF' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
                if ($vimp || $prof): ?>
                <div style="margin-top:14px">
                    <?php if ($vimp): ?>
                    <div style="margin-bottom:10px">
                        <span style="font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.5px">VIMP</span>
                        <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:6px">
                            <?php foreach ($vimp as $name): ?>
                            <span style="background:#e8f4fd;color:#1565c0;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600"><?= htmlspecialchars($name) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($prof): ?>
                    <div>
                        <span style="font-size:11px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.5px">PROF</span>
                        <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:6px">
                            <?php foreach ($prof as $name): ?>
                            <span style="background:#f3e5f5;color:#6a1b9a;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600"><?= htmlspecialchars($name) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            <form action="import_subjects.php" method="POST" enctype="multipart/form-data">
                <label>Excel datne (.xlsx)</label>
                <input type="file" name="subjects_file" accept=".xlsx" required>
                <button type="submit" class="btn btn-dark">Augšupielādēt</button>
            </form>
        </div>

        <!-- Section 2: Settings -->
        <div class="card">
            <div class="card-header">
                <div class="card-num">2</div>
                <div class="card-title">Iestatījumi</div>
            </div>
            <?php
            $settings = $pdo->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1")->fetch();
            if ($settings):
            $start = new DateTime($settings['start_date']);
            $end = new DateTime($settings['end_date']);

            // Calculate total days between start and end date
            $days = $start->diff($end)->days;

            if ($days > 180) {
            $semester = 'Gada vērtējums';
            } else {
            $month = (int)$start->format('m');
            $semester = in_array($month, [9, 10, 11, 12, ]) ? '1. semestris' : '2. semestris';
               }
            ?>
                <div class="info-box">
                    <?= $settings['start_date'] ?> → <?= $settings['end_date'] ?> &nbsp;|&nbsp;
                    <?= number_format($settings['monthly_budget'], 2) ?> EUR &nbsp;|&nbsp;
                    <span class="badge"><?= $semester ?></span>
                </div>
            <?php endif; ?>
            <form action="save_settings.php" method="POST">
                <label>Sākuma datums</label>
                <input type="date" name="start_date" value="<?= $settings['start_date'] ?? '' ?>" required>
                <label>Beigu datums</label>
                <input type="date" name="end_date" value="<?= $settings['end_date'] ?? '' ?>" required>
                <label>Mēneša budžets (EUR)</label>
                <input type="number" step="0.01" name="budget" value="<?= $settings['monthly_budget'] ?? '' ?>" required>
                <button type="submit" class="btn btn-dark">Saglabāt</button>
            </form>
        </div>

        <!-- Section 3: Scholarship table -->
        <div class="card">
            <div class="card-header">
                <div class="card-num">3</div>
                <div class="card-title">Stipendijas apjoma tabula</div>
            </div>
            <form action="save_scholarship_table.php" method="POST">
                <table>
                    <tr><th>No</th><th>Līdz</th><th>Summa (EUR)</th></tr>
                    <?php
                    $rows = $pdo->query("SELECT * FROM scholarship_table ORDER BY grade_from")->fetchAll();
                    foreach ($rows as $row): ?>
                    <tr>
                        <td><?= $row['grade_from'] ?></td>
                        <td><?= $row['grade_to'] ?></td>
                        <td><input type="number" step="0.01" name="amount[<?= $row['id'] ?>]" value="<?= $row['amount'] ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <button type="submit" class="btn btn-dark">Saglabāt tabulu</button>
            </form>
        </div>

        <!-- Section 4: Upload grades -->
        <div class="card">
            <div class="card-header">
                <div class="card-num">4</div>
                <div class="card-title">Augšupielādēt vērtējumus</div>
            </div>
            <?php
            $studentCount = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
            if ($studentCount > 0): ?>
                <div class="info-box"> Datubāzē: <strong><?= $studentCount ?></strong> studenti</div>
            <?php endif; ?>
            <form action="import_grades.php" method="POST" enctype="multipart/form-data">
                <label>E-klases Excel datne (.xlsx)</label>
                <input type="file" name="grades_file" accept=".xlsx" required>
                <button type="submit" class="btn btn-dark">Augšupielādēt</button>
            </form>
        </div>

        <!-- Section 5: Calculate -->
        <div class="card grid-full">
            <div class="card-header">
                <div class="card-num">5</div>
                <div class="card-title">Aprēķināt stipendijas</div>
            </div>
            <form action="calculate.php" method="POST">
                <button type="submit" class="btn btn-dark btn-full">Aprēķināt stipendijas →</button>
            </form>
        </div>

    </div>
</div>
</body>
</html>