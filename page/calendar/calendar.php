<?php
require_once __DIR__ . '/../../include/conection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$luna = isset($_GET['luna']) ? (int)$_GET['luna'] : (int)date('m');
$an   = isset($_GET['an']) ? (int)$_GET['an'] : (int)date('Y');
$dataSelectata = $_GET['data'] ?? '';

$f1 = isset($_GET['f1']) ? trim($_GET['f1']) : '';
$fx = isset($_GET['fx']) ? trim($_GET['fx']) : '';
$f2 = isset($_GET['f2']) ? trim($_GET['f2']) : '';
$marja = isset($_GET['marja']) ? (float)$_GET['marja'] : 0.03;

function getImportDatesForMonth(mysqli $conn, int $luna, int $an): array
{
    $start = sprintf('01.%02d.%04d', $luna, $an);
    $end   = sprintf('%02d.%02d.%04d', cal_days_in_month(CAL_GREGORIAN, $luna, $an), $luna, $an);

    $sql = "SELECT data_import FROM import_zile";
    $res = $conn->query($sql);

    $dates = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $d = trim($row['data_import']);
            if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $d, $m)) {
                $zi = (int)$m[1];
                $ll = (int)$m[2];
                $aa = (int)$m[3];
                if ($ll === $luna && $aa === $an) {
                    $dates[] = $d;
                }
            }
        }
    }

    return $dates;
}

function genereazaCalendar(int $luna, int $an, array $existingDates): void
{
    $numarZile = cal_days_in_month(CAL_GREGORIAN, $luna, $an);
    $primaZi = (int)date('N', mktime(0, 0, 0, $luna, 1, $an)); // 1=Luni

    echo "<table class='calendar'>";
    echo "<tr><th>L</th><th>Ma</th><th>Mi</th><th>J</th><th>V</th><th>S</th><th>D</th></tr><tr>";

    for ($i = 1; $i < $primaZi; $i++) {
        echo "<td>&nbsp;</td>";
    }

    for ($zi = 1; $zi <= $numarZile; $zi++) {
        $dataUrl = sprintf('%02d.%02d.%04d', $zi, $luna, $an);
        $hasContent = in_array($dataUrl, $existingDates, true);
        $class = $hasContent ? 'has-content' : '';

        echo "<td class='{$class}'>";
        echo "<a href='?luna={$luna}&an={$an}&data={$dataUrl}'>" . $zi . "</a>";
        echo "</td>";

        if ((($zi + $primaZi - 1) % 7) === 0 && $zi !== $numarZile) {
            echo "</tr><tr>";
        }
    }

    $cellsUsed = $numarZile + $primaZi - 1;
    $remaining = 7 - ($cellsUsed % 7);
    if ($remaining < 7) {
        for ($i = 0; $i < $remaining; $i++) {
            echo "<td>&nbsp;</td>";
        }
    }

    echo "</tr></table>";
}

$existingDates = getImportDatesForMonth($conn, $luna, $an);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Calendar Import Superbet</title>
    <link rel="stylesheet" href="../../assets/sport.css">
    <style>
        body { background:#505050; color:white; margin:0; font-family:Arial, sans-serif; }
        .container { width:95%; margin:20px auto; }
        .top-form, .filter-form {
            background:#3d3d3d; padding:15px; border-radius:8px; margin-bottom:20px;
        }
        .top-form select, .top-form button, .filter-form input, .filter-form select, .filter-form button {
            padding:6px 8px; margin-right:8px; background:#666; color:white; border:1px solid #888;
        }
        table { border-collapse:collapse; width:100%; background:#333; }
        th, td { border:1px solid #000; padding:8px; text-align:center; }
        th { background:#444; }
        a { text-decoration:none; color:#add8e6; display:block; }
        .calendar { width:90%; margin:20px auto; }
        .has-content { background:#4CAF50 !important; font-weight:bold; }
        .has-content a { color:white !important; }
        tr:nth-child(even) { background:#444; }
        .left { text-align:left; }
        .small { font-size:11px; color:#ddd; }
        .c1 { color:#ffeb3b; font-weight:bold; }
        .psf { color:#4caf50; font-weight:bold; }
        .title-box {
            background:#3b3b3b; padding:15px; border-radius:8px; margin-bottom:20px;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../include/menu.php'; ?>

<div class="container">
    <div class="title-box">
        <h1>📅 Calendar Import Superbet</h1>
        <div>Vezi ce există în <b>import_zile</b> și <b>import_oferte</b>, plus căutare manuală după cote.</div>
    </div>

    <form method="get" class="top-form">
        <label>Lună:</label>
        <select name="luna">
            <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= $i ?>" <?= $i === $luna ? 'selected' : '' ?>>
                    <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                </option>
            <?php endfor; ?>
        </select>

        <label>An:</label>
        <select name="an">
            <?php for ($i = 2024; $i <= 2030; $i++): ?>
                <option value="<?= $i ?>" <?= $i === $an ? 'selected' : '' ?>><?= $i ?></option>
            <?php endfor; ?>
        </select>

        <button type="submit">Afișează</button>
    </form>

    <?php genereazaCalendar($luna, $an, $existingDates); ?>

    <?php if ($dataSelectata): ?>
        <div class="title-box">
            <h2>📋 Oferte pentru <?= htmlspecialchars($dataSelectata) ?></h2>
        </div>

        <form method="get" class="filter-form">
            <input type="hidden" name="luna" value="<?= $luna ?>">
            <input type="hidden" name="an" value="<?= $an ?>">
            <input type="hidden" name="data" value="<?= htmlspecialchars($dataSelectata) ?>">

            <label>Cota 1:</label>
            <input type="text" name="f1" size="5" value="<?= htmlspecialchars($f1) ?>">

            <label>Cota X:</label>
            <input type="text" name="fx" size="5" value="<?= htmlspecialchars($fx) ?>">

            <label>Cota 2:</label>
            <input type="text" name="f2" size="5" value="<?= htmlspecialchars($f2) ?>">

            <label>Marjă:</label>
            <select name="marja">
                <option value="0.01" <?= $marja == 0.01 ? 'selected' : '' ?>>0.01</option>
                <option value="0.03" <?= $marja == 0.03 ? 'selected' : '' ?>>0.03</option>
                <option value="0.05" <?= $marja == 0.05 ? 'selected' : '' ?>>0.05</option>
                <option value="0.10" <?= $marja == 0.10 ? 'selected' : '' ?>>0.10</option>
            </select>

            <button type="submit">Filtrează</button>
        </form>

        <?php
        $sql = "
            SELECT 
                io.id,
                iz.data_import,
                io.id_off,
                io.match_id,
                io.match_key,
                io.incepe_text,
                io.data_meci,
                io.ora_meci,
                io.gazda,
                io.oaspeti,
                io.cota_1,
                io.cota_x,
                io.cota_2,
                io.psf1,
                io.psfx,
                io.psf2,
                io.created_at
            FROM import_oferte io
            INNER JOIN import_zile iz ON iz.id = io.import_zi_id
            WHERE iz.data_import = ?
        ";

        $types = "s";
        $params = [$dataSelectata];

        if ($f1 !== '' && is_numeric($f1)) {
            $sql .= " AND io.cota_1 BETWEEN ? AND ? ";
            $types .= "dd";
            $params[] = (float)$f1 - $marja;
            $params[] = (float)$f1 + $marja;
        }

        if ($fx !== '' && is_numeric($fx)) {
            $sql .= " AND io.cota_x BETWEEN ? AND ? ";
            $types .= "dd";
            $params[] = (float)$fx - $marja;
            $params[] = (float)$fx + $marja;
        }

        if ($f2 !== '' && is_numeric($f2)) {
            $sql .= " AND io.cota_2 BETWEEN ? AND ? ";
            $types .= "dd";
            $params[] = (float)$f2 - $marja;
            $params[] = (float)$f2 + $marja;
        }

        $sql .= " ORDER BY io.ora_meci ASC, io.gazda ASC ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0):
        ?>
            <table>
                <tr>
                    <th>#</th>
                    <th>Data Import</th>
                    <th>ID Off</th>
                    <th>Match ID</th>
                    <th>Ora</th>
                    <th>Meci</th>
                    <th>1</th>
                    <th>X</th>
                    <th>2</th>
                    <th>PSF 1</th>
                    <th>PSF X</th>
                    <th>PSF 2</th>
                    <th>Creat</th>
                </tr>
                <?php $nr = 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $nr++ ?></td>
                        <td><?= htmlspecialchars($row['data_import']) ?></td>
                        <td><?= htmlspecialchars((string)$row['id_off']) ?></td>
                        <td><?= $row['match_id'] ? htmlspecialchars($row['match_id']) : '-' ?></td>
                        <td><?= htmlspecialchars($row['ora_meci'] ?: $row['incepe_text']) ?></td>
                        <td class="left">
                            <b><?= htmlspecialchars($row['gazda']) ?></b> - <b><?= htmlspecialchars($row['oaspeti']) ?></b>
                            <?php if (!empty($row['match_key'])): ?>
                                <div class="small"><?= htmlspecialchars($row['match_key']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="c1"><?= $row['cota_1'] !== null ? htmlspecialchars((string)$row['cota_1']) : '-' ?></td>
                        <td><?= $row['cota_x'] !== null ? htmlspecialchars((string)$row['cota_x']) : '-' ?></td>
                        <td><?= $row['cota_2'] !== null ? htmlspecialchars((string)$row['cota_2']) : '-' ?></td>
                        <td class="psf"><?= $row['psf1'] !== null ? htmlspecialchars((string)$row['psf1']) : '-' ?></td>
                        <td class="psf"><?= $row['psfx'] !== null ? htmlspecialchars((string)$row['psfx']) : '-' ?></td>
                        <td class="psf"><?= $row['psf2'] !== null ? htmlspecialchars((string)$row['psf2']) : '-' ?></td>
                        <td class="small"><?= htmlspecialchars($row['created_at']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>Nu am găsit oferte pentru ziua selectată.</p>
        <?php
        endif;
        $stmt->close();
        ?>
    <?php endif; ?>
</div>
</body>
</html>
