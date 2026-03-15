<?php
require_once __DIR__ . '/../../../include/conection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = $_GET['msg'] ?? '';
$salvate = isset($_GET['salvate']) ? (int)$_GET['salvate'] : 0;
$actualizate = isset($_GET['actualizate']) ? (int)$_GET['actualizate'] : 0;
$erori = isset($_GET['erori']) ? (int)$_GET['erori'] : 0;

$filtru_id = isset($_GET['filtru_id']) ? (int)$_GET['filtru_id'] : 0;
$marja = isset($_GET['marja']) && $_GET['marja'] !== '' ? (float)$_GET['marja'] : 0.03;
$data_import = trim($_GET['data_import'] ?? '');

$filtre = [];
$zile = [];
$filtruSelectat = null;
$rezultate = [];

/*
|--------------------------------------------------------------------------
| ZILE IMPORT
|--------------------------------------------------------------------------
*/
$resZile = $conn->query("SELECT data_import FROM import_zile ORDER BY id DESC");
if ($resZile) {
    while ($row = $resZile->fetch_assoc()) {
        $zile[] = $row['data_import'];
    }
}

/*
|--------------------------------------------------------------------------
| DATA IMPLICITA
|--------------------------------------------------------------------------
*/
if ($data_import === '' && !empty($zile)) {
    $data_import = $zile[0];
}

/*
|--------------------------------------------------------------------------
| FILTRE
|--------------------------------------------------------------------------
*/
$sqlFiltre = "
    SELECT 
        f.id,
        f.identificator,
        f.denumire_filtru,
        c.denumire AS categorie,
        fc.cota_1,
        fc.cota_x,
        fc.cota_2
    FROM filtre f
    LEFT JOIN categorii_filtre c ON c.id = f.categorie_id
    LEFT JOIN filtre_cote fc ON fc.filtru_id = f.id
    WHERE f.activ = 1
    ORDER BY c.ordine ASC, f.id DESC
";
$resFiltre = $conn->query($sqlFiltre);
if ($resFiltre) {
    while ($row = $resFiltre->fetch_assoc()) {
        $filtre[] = $row;
    }
}

/*
|--------------------------------------------------------------------------
| FILTRU SELECTAT + REZULTATE
|--------------------------------------------------------------------------
*/
if ($filtru_id > 0 && $data_import !== '') {
    $sqlF = "
        SELECT 
            f.id,
            f.identificator,
            f.denumire_filtru,
            c.denumire AS categorie,
            fc.cota_1,
            fc.cota_x,
            fc.cota_2
        FROM filtre f
        LEFT JOIN categorii_filtre c ON c.id = f.categorie_id
        LEFT JOIN filtre_cote fc ON fc.filtru_id = f.id
        WHERE f.id = ?
        LIMIT 1
    ";
    $stmtF = $conn->prepare($sqlF);
    $stmtF->bind_param("i", $filtru_id);
    $stmtF->execute();
    $resF = $stmtF->get_result();
    $filtruSelectat = $resF->fetch_assoc();
    $stmtF->close();

    if ($filtruSelectat) {
        $c1 = (float)$filtruSelectat['cota_1'];
        $cx = (float)$filtruSelectat['cota_x'];
        $c2 = (float)$filtruSelectat['cota_2'];

        $min1 = $c1 - $marja;
        $max1 = $c1 + $marja;
        $minx = $cx - $marja;
        $maxx = $cx + $marja;
        $min2 = $c2 - $marja;
        $max2 = $c2 + $marja;

        $sql = "
            SELECT
                io.id,
                io.id_off,
                io.match_id,
                io.gazda,
                io.oaspeti,
                io.incepe_text,
                io.data_meci,
                io.ora_meci,
                io.cota_1,
                io.cota_x,
                io.cota_2,
                io.psf1,
                io.psfx,
                io.psf2,
                iz.data_import
            FROM import_oferte io
            INNER JOIN import_zile iz ON iz.id = io.import_zi_id
            WHERE iz.data_import = ?
              AND io.cota_1 BETWEEN ? AND ?
              AND io.cota_x BETWEEN ? AND ?
              AND io.cota_2 BETWEEN ? AND ?
            ORDER BY io.data_meci ASC, io.ora_meci ASC, io.gazda ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sdddddd",
            $data_import,
            $min1, $max1,
            $minx, $maxx,
            $min2, $max2
        );
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $rezultate[] = $row;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Căutare Meciuri după Dată și Cote</title>
    <link rel="stylesheet" href="../../../assets/sport.css">
    <link rel="stylesheet" href="cauta_cota.css">
    <style>
        .success-message {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            padding: 10px;
            margin: 12px 0;
            border-radius: 6px;
        }
        .warning-message {
            background: #fff3cd;
            border: 1px solid #ffe69c;
            padding: 10px;
            margin: 12px 0;
            border-radius: 6px;
        }
        .small {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        .left {
            text-align: left;
        }
        .cota {
            font-weight: bold;
        }
        .psf {
            color: #2e8b57;
            font-weight: bold;
        }
        .action-buttons {
            margin: 15px 0;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../../include/menu.php'; ?>

<div class="container">
    <h1>Căutare Meciuri după Dată și Cote</h1>
    <p>Caută în oferta Superbet importată în <b>import_oferte</b>, folosind cotele dintr-un filtru.</p>

    <?php if ($msg === 'salvat'): ?>
        <div class="success-message">
            Salvate: <b><?= $salvate ?></b> |
            Actualizate: <b><?= $actualizate ?></b> |
            Erori: <b><?= $erori ?></b>
        </div>
    <?php endif; ?>

    <?php if ($msg === 'nimic_selectat'): ?>
        <div class="warning-message">
            Nu ai selectat niciun meci.
        </div>
    <?php endif; ?>

    <div class="form-box">
        <form method="get">
            <label>Data import:</label>
            <input list="lista_zile" name="data_import" value="<?= htmlspecialchars($data_import) ?>" placeholder="ex: 14.03.2026" required>
            <datalist id="lista_zile">
                <?php foreach ($zile as $zi): ?>
                    <option value="<?= htmlspecialchars($zi) ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <label>Filtru:</label>
            <select name="filtru_id" required>
                <option value="">-- alege filtru --</option>
                <?php foreach ($filtre as $f): ?>
                    <option value="<?= (int)$f['id'] ?>" <?= $filtru_id === (int)$f['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($f['categorie'] ?? '-') . ' | ' . ($f['identificator'] ?: $f['denumire_filtru']) . ' | ' . $f['cota_1'] . ' / ' . $f['cota_x'] . ' / ' . $f['cota_2']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Marjă:</label>
            <select name="marja">
                <option value="0.01" <?= $marja == 0.01 ? 'selected' : '' ?>>0.01</option>
                <option value="0.03" <?= $marja == 0.03 ? 'selected' : '' ?>>0.03</option>
                <option value="0.05" <?= $marja == 0.05 ? 'selected' : '' ?>>0.05</option>
                <option value="0.10" <?= $marja == 0.10 ? 'selected' : '' ?>>0.10</option>
            </select>

            <button type="submit" class="btn">Caută</button>
        </form>
    </div>

    <?php if ($filtruSelectat): ?>
        <div class="info-box">
            <b>Filtru selectat:</b><br>
            Categorie: <b><?= htmlspecialchars($filtruSelectat['categorie'] ?? '-') ?></b><br>
            Denumire internă: <b><?= htmlspecialchars($filtruSelectat['denumire_filtru']) ?></b><br>
            Identificator: <b><?= htmlspecialchars($filtruSelectat['identificator'] ?? '') ?></b><br>
            Cote:
            <span class="cota"><?= htmlspecialchars((string)$filtruSelectat['cota_1']) ?></span> /
            <span class="cota"><?= htmlspecialchars((string)$filtruSelectat['cota_x']) ?></span> /
            <span class="cota"><?= htmlspecialchars((string)$filtruSelectat['cota_2']) ?></span>
            <br>
            Marjă folosită: <b><?= htmlspecialchars((string)$marja) ?></b>
        </div>
    <?php endif; ?>

    <?php if ($data_import !== '' && $filtru_id > 0): ?>
        <h2>Rezultate găsite: <?= count($rezultate) ?></h2>

        <?php if (!empty($rezultate)): ?>
            <form method="post" action="salveaza_x_pauza.php" id="salvareForm">
    <input type="hidden" name="filtru_id" value="<?= (int)$filtru_id ?>">
    <input type="hidden" name="zi_selectata" value="<?= htmlspecialchars($data_import) ?>">
    <input type="hidden" name="data_import" value="<?= htmlspecialchars($data_import) ?>">
    <input type="hidden" name="marja" value="<?= htmlspecialchars((string)$marja) ?>">

                <div class="action-buttons">
                    <button type="submit" class="btn" id="submitBtn">Salvează selecția în rezultate</button>
                    <label>
                        <input type="checkbox" id="select-all"> Selectează tot
                    </label>
                    <button type="button" class="btn btn-blue" id="deselect-all">Deselectează tot</button>
                    <button type="button" class="btn btn-blue" id="invert-selection">Inversează selecția</button>
                </div>

                <table>
                    <tr>
                        <th class="checkbox-col">Sel.</th>
                        <th>#</th>
                        <th>ID Off</th>
                        <th>Match ID</th>
                        <th>Data meci</th>
                        <th>Ora</th>
                        <th>Meci</th>
                        <th>1</th>
                        <th>X</th>
                        <th>2</th>
                        <th>PSF 1</th>
                        <th>PSF X</th>
                        <th>PSF 2</th>
                    </tr>
                    <?php foreach ($rezultate as $i => $row): ?>
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
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][ora_meci]" value="<?= htmlspecialchars((string)($row['ora_meci'] ?: $row['incepe_text'])) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][cota_1]" value="<?= htmlspecialchars((string)$row['cota_1']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][cota_x]" value="<?= htmlspecialchars((string)$row['cota_x']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][cota_2]" value="<?= htmlspecialchars((string)$row['cota_2']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][psf1]" value="<?= htmlspecialchars((string)$row['psf1']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][psfx]" value="<?= htmlspecialchars((string)$row['psfx']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][psf2]" value="<?= htmlspecialchars((string)$row['psf2']) ?>">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][status_sursa]" value="superbet">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][audit_verdict]" value="">
                            <input type="hidden" name="meci_data[<?= $rowKey ?>][audit_proc]" value="">
                            

                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars((string)$row['id_off']) ?></td>
                            <td><?= $row['match_id'] ? htmlspecialchars($row['match_id']) : '-' ?></td>
                            <td><?= htmlspecialchars($row['data_meci'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($row['ora_meci'] ?: $row['incepe_text']) ?></td>
                            <td class="left">
                                <b><?= htmlspecialchars($row['gazda']) ?></b> - <b><?= htmlspecialchars($row['oaspeti']) ?></b>
                                <div class="small">Import: <?= htmlspecialchars($row['data_import']) ?></div>
                            </td>
                            <td class="cota"><?= $row['cota_1'] !== null ? htmlspecialchars((string)$row['cota_1']) : '-' ?></td>
                            <td class="cota"><?= $row['cota_x'] !== null ? htmlspecialchars((string)$row['cota_x']) : '-' ?></td>
                            <td class="cota"><?= $row['cota_2'] !== null ? htmlspecialchars((string)$row['cota_2']) : '-' ?></td>
                            <td class="psf"><?= $row['psf1'] !== null ? htmlspecialchars((string)$row['psf1']) : '-' ?></td>
                            <td class="psf"><?= $row['psfx'] !== null ? htmlspecialchars((string)$row['psfx']) : '-' ?></td>
                            <td class="psf"><?= $row['psf2'] !== null ? htmlspecialchars((string)$row['psf2']) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </form>

            <script>
            document.getElementById('select-all')?.addEventListener('change', function () {
                document.querySelectorAll('.meci-checkbox').forEach(cb => {
                    cb.checked = this.checked;
                });
            });

            document.getElementById('deselect-all')?.addEventListener('click', function () {
                document.querySelectorAll('.meci-checkbox').forEach(cb => {
                    cb.checked = false;
                });
                const all = document.getElementById('select-all');
                if (all) all.checked = false;
            });

            document.getElementById('invert-selection')?.addEventListener('click', function () {
                document.querySelectorAll('.meci-checkbox').forEach(cb => {
                    cb.checked = !cb.checked;
                });
                const allChecked = Array.from(document.querySelectorAll('.meci-checkbox')).every(cb => cb.checked);
                const all = document.getElementById('select-all');
                if (all) all.checked = allChecked;
            });

            document.getElementById('salvareForm')?.addEventListener('submit', function(e) {
                const selected = document.querySelectorAll('.meci-checkbox:checked').length;
                if (selected === 0) {
                    e.preventDefault();
                    alert('Nu ai selectat niciun meci!');
                    return;
                }

                const confirmare = confirm(`Ești sigur că vrei să salvezi ${selected} meci(uri) selectate?`);
                if (!confirmare) {
                    e.preventDefault();
                    return;
                }

                const btn = document.getElementById('submitBtn');
                btn.disabled = true;
                btn.innerHTML = 'Se salvează... ⏳';
            });
            </script>
        <?php else: ?>
            <p>Nu s-au găsit meciuri pentru filtrul selectat în ziua aleasă.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
