<?php
require_once __DIR__ . '/../../../include/conection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$filtru_id = isset($_GET['filtru_id']) ? (int)$_GET['filtru_id'] : 0;
$zi_selectata = trim($_GET['zi'] ?? date('d.m'));
$msg = $_GET['msg'] ?? '';

$rezultate_pe_zile = [];

/*
|--------------------------------------------------------------------------
| STERGE TOATA ZIUA
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_day'])) {
    $filtru_id_post = isset($_POST['filtru_id']) ? (int)$_POST['filtru_id'] : 0;
    $zi_de_sters = trim($_POST['zi_selectata'] ?? '');

    if ($filtru_id_post > 0 && $zi_de_sters !== '') {
        $stmtDel = $conn->prepare("
            DELETE FROM rezultate_filtre
            WHERE filtru_id = ?
              AND LEFT(data_meci, 5) = ?
        ");

        if ($stmtDel) {
            $stmtDel->bind_param("is", $filtru_id_post, $zi_de_sters);
            $stmtDel->execute();
            $stmtDel->close();
        }
    }

    header('Location: rezultate.php?filtru_id=' . $filtru_id_post . '&msg=zi_stearsa');
    exit;
}

if ($filtru_id > 0) {
    $sqlRez = "
    SELECT
        rf.id,
        rf.filtru_id,
        rf.identificator_filtru,
        rf.categorie_filtru,
        rf.id_off,
        rf.match_id,
        rf.gazda,
        rf.oaspeti,
        rf.data_import,
        rf.data_meci,
        rf.ora_meci,
        rf.cota_1,
        rf.cota_x,
        rf.cota_2,
        rf.psf1,
        rf.psfx,
        rf.psf2,
        rf.status_sursa,
        rf.audit_verdict,
        rf.audit_proc,
        rf.created_at,
        mm.id AS master_id
    FROM rezultate_filtre rf
    LEFT JOIN manage2.meciuri_master mm
        ON mm.id_off = rf.id_off
       AND (
            CONVERT(mm.data_meci USING utf8mb4) COLLATE utf8mb4_unicode_ci =
            CONVERT(rf.data_meci USING utf8mb4) COLLATE utf8mb4_unicode_ci
            OR
            CONVERT(mm.data_meci USING utf8mb4) COLLATE utf8mb4_unicode_ci =
            CONVERT(CONCAT(rf.data_meci, '.', YEAR(CURDATE())) USING utf8mb4) COLLATE utf8mb4_unicode_ci
            OR
            CONVERT(LEFT(mm.data_meci, 5) USING utf8mb4) COLLATE utf8mb4_unicode_ci =
            CONVERT(LEFT(rf.data_meci, 5) USING utf8mb4) COLLATE utf8mb4_unicode_ci
       )
    WHERE rf.filtru_id = ?
    ORDER BY rf.data_meci ASC, rf.ora_meci ASC, rf.gazda ASC
";
    $stmtRez = $conn->prepare($sqlRez);
    $stmtRez->bind_param("i", $filtru_id);
    $stmtRez->execute();
    $resRez = $stmtRez->get_result();

    while ($row = $resRez->fetch_assoc()) {
        $data_raw = trim((string)($row['data_meci'] ?? ''));

        if ($data_raw !== '') {
            $data_key = substr($data_raw, 0, 5); // dd.mm
        } else {
            $data_key = 'Fără dată';
        }

        $rezultate_pe_zile[$data_key][] = $row;
    }
    $stmtRez->close();

    uksort($rezultate_pe_zile, function ($a, $b) {
        if ($a === 'Fără dată') return 1;
        if ($b === 'Fără dată') return -1;

        $an = date('Y');
        $dA = DateTime::createFromFormat('d.m.Y', "$a.$an");
        $dB = DateTime::createFromFormat('d.m.Y', "$b.$an");

        if ($dA && $dB) {
            return $dA->getTimestamp() <=> $dB->getTimestamp();
        }

        return strcmp($a, $b);
    });
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Rezultate Grupate pe Zile</title>
    <link rel="stylesheet" href="../../../assets/sport.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f3f3f3; }
        .container { width: 95%; margin: 20px auto; background: white; padding: 20px; border-radius: 10px; }

        table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #007bff; color: white; position: sticky; top: 0; }

        .left { text-align: left; }
        .small { color: #666; font-size: 11px; }
        .cota { font-weight: bold; color: #0b63c9; }
        .psf { color: #2e8b57; font-weight: bold; }

        .calendar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
            justify-content: center;
        }

        .day-box {
            background: #f8f9fa;
            border: 1px solid #007bff;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            min-width: 80px;
        }

        .day-box a {
            font-weight: bold;
            text-decoration: none;
            color: #007bff;
        }

        .selected-day {
            background: #e7f1ff !important;
            border-width: 2px !important;
        }

        .toolbar {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #b8daff;
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .toolbar button,
        .toolbar input {
            padding: 8px;
        }

        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-delete-day {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .disponibil-ok {
            color: #28a745;
            font-weight: bold;
        }

        .in-master {
            color: #198754;
            font-weight: bold;
        }

        .not-in-master {
            color: #dc3545;
            font-weight: bold;
        }

        .msg {
            padding: 10px 14px;
            margin-bottom: 15px;
            border-radius: 6px;
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../../include/menu.php'; ?>

<div class="container">
    <h1>📊 Rezultate Grupate pe Zile</h1>

    <?php if ($msg === 'zi_stearsa'): ?>
        <div class="msg">Ziua a fost ștearsă.</div>
    <?php endif; ?>

    <?php if ($msg === 'salvat'): ?>
        <div class="msg">Meciurile selectate au fost trimise în master.</div>
    <?php endif; ?>

    <?php if (!empty($rezultate_pe_zile)): ?>
        <div class="calendar">
            <?php foreach ($rezultate_pe_zile as $data => $meciuri): ?>
                <div class="day-box <?= ($zi_selectata === $data ? 'selected-day' : '') ?>">
                    <a href="?filtru_id=<?= $filtru_id ?>&zi=<?= urlencode($data) ?>">
                        <?= htmlspecialchars($data) ?>
                    </a>
                    <p><?= count($meciuri) ?> meciuri</p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($zi_selectata) && isset($rezultate_pe_zile[$zi_selectata])): ?>

        <form method="POST" style="margin-bottom:15px;" onsubmit="return confirm('Sigur vrei să ștergi toată ziua <?= htmlspecialchars($zi_selectata) ?>?');">
            <input type="hidden" name="filtru_id" value="<?= $filtru_id ?>">
            <input type="hidden" name="zi_selectata" value="<?= htmlspecialchars($zi_selectata) ?>">
            <button type="submit" name="delete_day" class="btn-delete-day">Șterge ziua</button>
        </form>

        <form action="salveaza_in_master.php" method="POST">
            <input type="hidden" name="filtru_id" value="<?= $filtru_id ?>">
            <input type="hidden" name="zi_selectata" value="<?= htmlspecialchars($zi_selectata) ?>">

            <div class="toolbar">
                <label><input type="checkbox" id="select-all"> Selectează tot</label>
                <button type="submit" class="btn-save">💾 Salvează Selectate</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Sel.</th>
                        <th>Status</th>
                        <th>ID Off</th>
                        <th>Match ID</th>
                        <th>Data</th>
                        <th>Ora</th>
                        <th>Gazda</th>
                        <th>Oaspeți</th>
                        <th>1 / X / 2</th>
                        <th>PSF 1 / X / 2</th>
                        <th>Import</th>
                        <th>Creat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rezultate_pe_zile[$zi_selectata] as $row): ?>
                        <?php $rowKey = (int)$row['id']; ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selectat[]" value="<?= $rowKey ?>" class="meci-checkbox">
                            </td>

                            <input type="hidden" name="meci_data[<?= $rowKey ?>][id_off]" value="<?= htmlspecialchars((string)$row['id_off']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][match_id]" value="<?= htmlspecialchars((string)$row['match_id']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][gazda]" value="<?= htmlspecialchars((string)$row['gazda']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][oaspeti]" value="<?= htmlspecialchars((string)$row['oaspeti']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][data_import]" value="<?= htmlspecialchars((string)$row['data_import']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][data_meci]" value="<?= htmlspecialchars((string)$row['data_meci']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][ora_meci]" value="<?= htmlspecialchars((string)$row['ora_meci']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][cota_1]" value="<?= htmlspecialchars((string)$row['cota_1']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][cota_x]" value="<?= htmlspecialchars((string)$row['cota_x']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][cota_2]" value="<?= htmlspecialchars((string)$row['cota_2']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][psf1]" value="<?= htmlspecialchars((string)$row['psf1']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][psfx]" value="<?= htmlspecialchars((string)$row['psfx']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][psf2]" value="<?= htmlspecialchars((string)$row['psf2']) ?>">

                            <td>
    <?php if (!empty($row['master_id'])): ?>
        <span class="in-master">în master</span><br>
        <span class="small">
            <?= htmlspecialchars((string)($row['master_status_sursa'] ?? '-')) ?>
            <?php if (!empty($row['master_status_bilet'])): ?>
                | <?= htmlspecialchars((string)$row['master_status_bilet']) ?>
            <?php endif; ?>
            <?php if (!empty($row['master_identificator_filtru'])): ?>
                | <?= htmlspecialchars((string)$row['master_identificator_filtru']) ?>
            <?php endif; ?>
        </span>
    <?php else: ?>
        <span class="not-in-master">nu</span>
    <?php endif; ?>
</td>

                            <td><?= htmlspecialchars((string)$row['id_off']) ?></td>
                            <td><?= $row['match_id'] ? htmlspecialchars($row['match_id']) : '-' ?></td>
                            <td><?= htmlspecialchars($row['data_meci'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($row['ora_meci'] ?: '-') ?></td>
                            <td class="left"><b><?= htmlspecialchars($row['gazda']) ?></b></td>
                            <td class="left"><b><?= htmlspecialchars($row['oaspeti']) ?></b></td>
                            <td class="cota">
                                <?= $row['cota_1'] !== null ? htmlspecialchars((string)$row['cota_1']) : '-' ?>
                                |
                                <?= $row['cota_x'] !== null ? htmlspecialchars((string)$row['cota_x']) : '-' ?>
                                |
                                <?= $row['cota_2'] !== null ? htmlspecialchars((string)$row['cota_2']) : '-' ?>
                            </td>
                            <td class="psf">
                                <?= $row['psf1'] !== null ? htmlspecialchars((string)$row['psf1']) : '-' ?>
                                |
                                <?= $row['psfx'] !== null ? htmlspecialchars((string)$row['psfx']) : '-' ?>
                                |
                                <?= $row['psf2'] !== null ? htmlspecialchars((string)$row['psf2']) : '-' ?>
                            </td>
                            <td><?= htmlspecialchars($row['data_import'] ?: '-') ?></td>
                            <td class="small"><?= htmlspecialchars($row['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    <?php elseif ($filtru_id > 0 && !empty($rezultate_pe_zile)): ?>
        <p>Selectează o zi de sus ca să vezi meciurile.</p>
    <?php else: ?>
        <p>Nu există rezultate pentru acest filtru.</p>
    <?php endif; ?>
</div>

<script>
document.getElementById('select-all')?.addEventListener('change', function () {
    document.querySelectorAll('.meci-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>
</body>
</html>
