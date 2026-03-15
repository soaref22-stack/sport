<?php
require_once __DIR__.'/../../include/conection.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function normalize_team_name(string $name): string {
    $name = mb_strtolower(trim($name), 'UTF-8');
    $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $name = preg_replace('/[^a-z0-9\s\-]/', '', $name);
    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
}

function build_match_key(string $data, string $ora, string $gazdaNorm, string $oaspetiNorm): string {
    return trim($data) . '|' . trim($ora) . '|' . trim($gazdaNorm) . '|' . trim($oaspetiNorm);
}

function csv_value(array $headers, array $row, array $aliases): ?string {
    foreach ($aliases as $alias) {
        $idx = array_search($alias, $headers, true);
        if ($idx !== false && isset($row[$idx])) {
            $value = trim((string)$row[$idx]);
            return $value === '' ? null : $value;
        }
    }
    return null;
}

function to_db_number($value) {
    if ($value === null) return null;
    $value = str_replace(',', '.', trim((string)$value));
    if ($value === '' || !is_numeric($value)) return null;
    return (float)$value;
}

function to_db_int($value) {
    if ($value === null) return null;
    $value = trim((string)$value);
    if ($value === '' || !is_numeric($value)) return null;
    return (int)$value;
}

$table = 'manage2.meciuri_master';

$todayFull = date('d.m.Y');
$todayShort = date('d.m');
?>
<!DOCTYPE HTML>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import CSV to Table</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .date-selector {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .date-fields {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
        }
        .date-fields select, .date-fields input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .quick-dates {
            margin-top: 10px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .quick-date-btn {
            padding: 5px 10px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .quick-date-btn:hover {
            background: #0b7dda;
        }
        .date-display {
            background: #e8f5e9;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-weight: bold;
            color: #d32f2f;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../include/menu.php'; ?>
<div class="container">
    <h2>Import CSV în meciuri_master</h2>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="date-selector">
            <h3>📅 Selectează data meciurilor</h3>

            <div class="date-fields">
                <div>
                    <label for="zi_select">Zi:</label>
                    <select name="zi_select" id="zi_select">
                        <?php for ($i = 1; $i <= 31; $i++):
                            $zi = str_pad((string)$i, 2, '0', STR_PAD_LEFT); ?>
                            <option value="<?= h($zi) ?>" <?= $zi === date('d') ? 'selected' : '' ?>><?= h($zi) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div>
                    <label for="luna_select">Lună:</label>
                    <select name="luna_select" id="luna_select">
                        <?php
                        $luni = [
                            '01' => 'Ian (01)', '02' => 'Feb (02)', '03' => 'Mar (03)',
                            '04' => 'Apr (04)', '05' => 'Mai (05)', '06' => 'Iun (06)',
                            '07' => 'Iul (07)', '08' => 'Aug (08)', '09' => 'Sep (09)',
                            '10' => 'Oct (10)', '11' => 'Nov (11)', '12' => 'Dec (12)'
                        ];
                        foreach ($luni as $num => $nume): ?>
                            <option value="<?= h($num) ?>" <?= $num === date('m') ? 'selected' : '' ?>><?= h($nume) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="an_select">An:</label>
                    <input type="number" name="an_select" id="an_select" value="<?= h(date('Y')) ?>" min="2024" max="2035" style="width:90px;">
                </div>
            </div>

            <div class="quick-dates">
                <button type="button" class="quick-date-btn" onclick="seteazaData('azi')">Azi</button>
                <button type="button" class="quick-date-btn" onclick="seteazaData('maine')">Mâine</button>
                <button type="button" class="quick-date-btn" onclick="seteazaData('poimaine')">Poimâine</button>
                <button type="button" class="quick-date-btn" onclick="seteazaData('ieri')">Ieri</button>
            </div>

            <div class="date-display">
                Data selectată: <span id="data_afisata"><?= h($todayFull) ?></span>
            </div>

            <input type="hidden" name="import_date_display" id="import_date_display" value="<?= h($todayShort) ?>">
            <input type="hidden" name="import_date_full" id="import_date_full" value="<?= h($todayFull) ?>">
        </div>

        <p><strong>Tabela țintă:</strong> manage2.meciuri_master</p>

        <input type="file" name="csv_file" accept=".csv" required>
        <br><br>
        <input type="submit" name="submit" value="Upload și Import CSV">
    </form>

<script>
function actualizeazaData() {
    const zi = document.getElementById('zi_select').value;
    const luna = document.getElementById('luna_select').value;
    const an = document.getElementById('an_select').value;
    document.getElementById('data_afisata').textContent = `${zi}.${luna}.${an}`;
    document.getElementById('import_date_display').value = `${zi}.${luna}`;
    document.getElementById('import_date_full').value = `${zi}.${luna}.${an}`;
}
function seteazaData(tip) {
    const d = new Date();
    if (tip === 'maine') d.setDate(d.getDate() + 1);
    if (tip === 'poimaine') d.setDate(d.getDate() + 2);
    if (tip === 'ieri') d.setDate(d.getDate() - 1);

    document.getElementById('zi_select').value = String(d.getDate()).padStart(2, '0');
    document.getElementById('luna_select').value = String(d.getMonth() + 1).padStart(2, '0');
    document.getElementById('an_select').value = d.getFullYear();
    actualizeazaData();
}
document.getElementById('zi_select').addEventListener('change', actualizeazaData);
document.getElementById('luna_select').addEventListener('change', actualizeazaData);
document.getElementById('an_select').addEventListener('input', actualizeazaData);
actualizeazaData();
</script>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $import_date_display = trim($_POST['import_date_display'] ?? '');
    $import_date_full = trim($_POST['import_date_full'] ?? '');

    echo "<div class='box info'>";
    echo "<h3>📋 Informații import</h3>";
    echo "<p><strong>Tabelă:</strong> " . h($table) . "</p>";
    echo "<p><strong>Data DD.MM:</strong> " . h($import_date_display) . "</p>";
    echo "<p><strong>Data DD.MM.YYYY:</strong> " . h($import_date_full) . "</p>";
    echo "</div>";

    if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $import_date_full)) {
        die("<div class='box err'>Format dată invalid.</div>");
    }

    $csv_file = $_FILES['csv_file']['tmp_name'] ?? '';
    if (!is_uploaded_file($csv_file)) {
        die("<div class='box err'>Fișierul CSV nu a fost încărcat.</div>");
    }

    $file = fopen($csv_file, 'r');
    if (!$file) {
        die("<div class='box err'>Nu s-a putut deschide fișierul CSV.</div>");
    }

    $headers = fgetcsv($file);
    if ($headers === false) {
        fclose($file);
        die("<div class='box err'>Nu s-au putut citi anteturile CSV.</div>");
    }

    $headers = array_map(static function($h) {
        return mb_strtolower(trim((string)$h), 'UTF-8');
    }, $headers);

    $sql = "INSERT INTO $table (
                match_id, match_key, id_off,
                gazda, oaspeti, gazda_norm, oaspeti_norm,
                round_text, data_meci, ora_meci,
                score, home_goals, away_goals, home_goals_1h, away_goals_1h,
                superbet_1, superbet_x, superbet_2,
                betano_1, betano_x, betano_2,
                offline_1, offline_x, offline_2,
                cota_psf_1, cota_psf_x, cota_psf_2,
                peste_2_5, sub_2_5, gg_da, gg_nu,
                foto, nota, tara, competitie, status_curent,
                are_flash, are_superbet, are_offline
            ) VALUES (
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?
            )
            ON DUPLICATE KEY UPDATE
                id_off = COALESCE(VALUES(id_off), id_off),
                gazda = COALESCE(NULLIF(VALUES(gazda), ''), gazda),
                oaspeti = COALESCE(NULLIF(VALUES(oaspeti), ''), oaspeti),
                gazda_norm = COALESCE(NULLIF(VALUES(gazda_norm), ''), gazda_norm),
                oaspeti_norm = COALESCE(NULLIF(VALUES(oaspeti_norm), ''), oaspeti_norm),
                round_text = COALESCE(NULLIF(VALUES(round_text), ''), round_text),
                data_meci = COALESCE(NULLIF(VALUES(data_meci), ''), data_meci),
                ora_meci = COALESCE(NULLIF(VALUES(ora_meci), ''), ora_meci),
                score = COALESCE(NULLIF(VALUES(score), ''), score),
                home_goals = COALESCE(VALUES(home_goals), home_goals),
                away_goals = COALESCE(VALUES(away_goals), away_goals),
                home_goals_1h = COALESCE(VALUES(home_goals_1h), home_goals_1h),
                away_goals_1h = COALESCE(VALUES(away_goals_1h), away_goals_1h),
                superbet_1 = COALESCE(VALUES(superbet_1), superbet_1),
                superbet_x = COALESCE(VALUES(superbet_x), superbet_x),
                superbet_2 = COALESCE(VALUES(superbet_2), superbet_2),
                betano_1 = COALESCE(VALUES(betano_1), betano_1),
                betano_x = COALESCE(VALUES(betano_x), betano_x),
                betano_2 = COALESCE(VALUES(betano_2), betano_2),
                offline_1 = COALESCE(VALUES(offline_1), offline_1),
		offline_x = COALESCE(VALUES(offline_x), offline_x),
		offline_2 = COALESCE(VALUES(offline_2), offline_2),
                cota_psf_1 = COALESCE(VALUES(cota_psf_1), cota_psf_1),
                cota_psf_x = COALESCE(VALUES(cota_psf_x), cota_psf_x),
                cota_psf_2 = COALESCE(VALUES(cota_psf_2), cota_psf_2),
                peste_2_5 = COALESCE(VALUES(peste_2_5), peste_2_5),
                sub_2_5 = COALESCE(VALUES(sub_2_5), sub_2_5),
                gg_da = COALESCE(VALUES(gg_da), gg_da),
                gg_nu = COALESCE(VALUES(gg_nu), gg_nu),
                foto = COALESCE(NULLIF(VALUES(foto), ''), foto),
                nota = COALESCE(NULLIF(VALUES(nota), ''), nota),
                tara = COALESCE(NULLIF(VALUES(tara), ''), tara),
                competitie = COALESCE(NULLIF(VALUES(competitie), ''), competitie),
                status_curent = COALESCE(NULLIF(VALUES(status_curent), ''), status_curent),
                are_flash = COALESCE(VALUES(are_flash), are_flash),
                are_superbet = COALESCE(VALUES(are_superbet), are_superbet),
                are_offline = COALESCE(VALUES(are_offline), are_offline)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        fclose($file);
        die("<div class='box err'>Eroare prepare SQL: " . h($conn->error) . "</div>");
    }

    $importedRows = 0;
    $failedRows = 0;
    $line = 1;

    echo "<div class='box info'><h4>📥 Procesare CSV</h4>";

    while (($row = fgetcsv($file, 0, ',')) !== false) {
        $match_id   = csv_value($headers, $row, ['match id', 'match_id']);
        $id_off     = to_db_int(csv_value($headers, $row, ['id_off']));
        $round_text = csv_value($headers, $row, ['round', 'round_text']);

        $csv_data   = csv_value($headers, $row, ['data', 'data_meci']);
        $data_meci  = $import_date_full ?: $csv_data ?: null;

        $ora_raw = csv_value($headers, $row, ['ora']);
        $ora_meci = null;
        if ($ora_raw !== null) {
            $ts = strtotime($ora_raw);
            $ora_meci = $ts !== false ? date('H:i', $ts) : $ora_raw;
        }

        $gazda      = csv_value($headers, $row, ['gazda']);
        $oaspeti    = csv_value($headers, $row, ['oaspeti']);
        $gazda_norm = normalize_team_name($gazda ?? '');
        $oaspeti_norm = normalize_team_name($oaspeti ?? '');
        $match_key  = build_match_key($data_meci ?? '', $ora_meci ?? '', $gazda_norm, $oaspeti_norm);

        $score          = csv_value($headers, $row, ['score']);
        $home_goals     = to_db_int(csv_value($headers, $row, ['home goals', 'home_goals']));
        $away_goals     = to_db_int(csv_value($headers, $row, ['away goals', 'away_goals']));
        $home_goals_1h  = to_db_int(csv_value($headers, $row, ['home_goals_1h']));
        $away_goals_1h  = to_db_int(csv_value($headers, $row, ['away_goals_1h']));

        $cota_1 = to_db_number(csv_value($headers, $row, ['cota 1', 'cota_1']));
        $cota_x = to_db_number(csv_value($headers, $row, ['cota x', 'cota_x']));
        $cota_2 = to_db_number(csv_value($headers, $row, ['cota 2', 'cota_2']));

        $cota_1_suplimentar = to_db_number(csv_value($headers, $row, ['cota_1_suplimentar', 'bet 1']));
        $cota_x_suplimentar = to_db_number(csv_value($headers, $row, ['cota_x_suplimentar', 'bet x']));
        $cota_2_suplimentar = to_db_number(csv_value($headers, $row, ['cota_2_suplimentar', 'bet 2']));
        
        $offline_1 = to_db_number(csv_value($headers, $row, ['offline 1', 'offline_1', 'off 1']));
$offline_x = to_db_number(csv_value($headers, $row, ['offline x', 'offline_x', 'off x']));
$offline_2 = to_db_number(csv_value($headers, $row, ['offline 2', 'offline_2', 'off 2']));

        $cota_psf_1 = to_db_number(csv_value($headers, $row, ['cota 1 2h', 'cota_1_2h', 'psf 1']));
        $cota_psf_x = to_db_number(csv_value($headers, $row, ['cota x 2h', 'cota_x_2h', 'psf x']));
        $cota_psf_2 = to_db_number(csv_value($headers, $row, ['cota 2 2h', 'cota_2_2h', 'psf 2']));

        $peste_2_5 = to_db_number(csv_value($headers, $row, ['p2.5', 'p2_5']));
        $sub_2_5   = to_db_number(csv_value($headers, $row, ['s2.5', 's2_5']));
        $gg_da     = to_db_number(csv_value($headers, $row, ['gg', 'da_gg']));
        $gg_nu     = to_db_number(csv_value($headers, $row, ['gg_2', 'nu_gg', 'ngg']));

        $foto          = csv_value($headers, $row, ['foto']);
        $nota          = csv_value($headers, $row, ['nota']);
        $tara          = csv_value($headers, $row, ['tara']);
        $competitie    = csv_value($headers, $row, ['competitie', 'competition']);
        $status_curent = csv_value($headers, $row, ['status', 'status_curent']);

        $are_flash     = $match_id ? 1 : 0;
        $are_superbet  = ($cota_1 !== null || $cota_x !== null || $cota_2 !== null) ? 1 : 0;
        $are_offline = ($offline_1 !== null || $offline_x !== null || $offline_2 !== null) ? 1 : 0;

        $types = "ssissssssssiiiiddddddddddddddddsssssiii";

$stmt->bind_param(
    $types,
    $match_id,
    $match_key,
    $id_off,
    $gazda,
    $oaspeti,
    $gazda_norm,
    $oaspeti_norm,
    $round_text,
    $data_meci,
    $ora_meci,
    $score,
    $home_goals,
    $away_goals,
    $home_goals_1h,
    $away_goals_1h,
    $cota_1,
    $cota_x,
    $cota_2,
    $cota_1_suplimentar,
    $cota_x_suplimentar,
    $cota_2_suplimentar,
    $offline_1,
    $offline_x,
    $offline_2,
    $cota_psf_1,
    $cota_psf_x,
    $cota_psf_2,
    $peste_2_5,
    $sub_2_5,
    $gg_da,
    $gg_nu,
    $foto,
    $nota,
    $tara,
    $competitie,
    $status_curent,
    $are_flash,
    $are_superbet,
    $are_offline
);

        if ($stmt->execute()) {
            $importedRows++;
            if ($line <= 5) {
                echo "<div class='line'>✓ " . h($gazda) . " - " . h($oaspeti) . "</div>";
            }
        } else {
            $failedRows++;
            echo "<div class='line' style='color:#dc3545;'>❌ Linia $line: " . h($stmt->error) . "</div>";
        }

        $line++;
    }

    echo "</div>";

    fclose($file);
    $stmt->close();

    echo "<div class='box ok'>";
    echo "<h3>✅ Import completat</h3>";
    echo "<p><strong>Rânduri importate:</strong> " . h($importedRows) . "</p>";
    echo "<p><strong>Rânduri eșuate:</strong> " . h($failedRows) . "</p>";
    echo "<p><strong>Data meciuri:</strong> " . h($import_date_full) . "</p>";
    echo "</div>";
}

$conn->close();
?>
</div>
</body>
</html>
