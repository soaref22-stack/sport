<?php
require_once __DIR__ . '/../../../include/conection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rezultate.php');
    exit;
}

$filtru_id = isset($_POST['filtru_id']) ? (int)$_POST['filtru_id'] : 0;
$zi_selectata = trim($_POST['zi_selectata'] ?? date('d.m'));
$selectat = $_POST['selectat'] ?? [];
$meci_data = $_POST['meci_data'] ?? [];

if ($filtru_id <= 0) {
    die('Filtru invalid.');
}

if ($zi_selectata === '') {
    die('Ziua selectată lipsește.');
}

if (empty($selectat) || !is_array($selectat)) {
    header('Location: rezultate.php?filtru_id=' . $filtru_id . '&zi=' . urlencode($zi_selectata) . '&msg=nimic_selectat');
    exit;
}

/*
 * Luăm informațiile filtrului
 */
$sqlFiltru = "
    SELECT 
        f.id,
        f.identificator,
        c.denumire AS categorie_filtru
    FROM filtre f
    LEFT JOIN categorii_filtre c ON c.id = f.categorie_id
    WHERE f.id = ?
    LIMIT 1
";

$stmtFiltru = $conn->prepare($sqlFiltru);
if (!$stmtFiltru) {
    die('Eroare la prepare filtru: ' . $conn->error);
}

$stmtFiltru->bind_param("i", $filtru_id);
$stmtFiltru->execute();
$resFiltru = $stmtFiltru->get_result();
$filtru = $resFiltru->fetch_assoc();
$stmtFiltru->close();

if (!$filtru) {
    die('Filtrul nu există.');
}

$identificator_filtru = $filtru['identificator'] ?? null;
$categorie_filtru = $filtru['categorie_filtru'] ?? null;

/*
 * Insert/update în rezultate_filtre
 * UNIQUE (filtru_id, id_off, data_meci)
 */
$sqlInsert = "
    INSERT INTO rezultate_filtre (
        filtru_id,
        identificator_filtru,
        categorie_filtru,
        id_off,
        match_id,
        gazda,
        oaspeti,
        data_import,
        data_meci,
        ora_meci,
        cota_1,
        cota_x,
        cota_2,
        psf1,
        psfx,
        psf2,
        status_sursa,
        audit_verdict,
        audit_proc,
        activ
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE
        identificator_filtru = VALUES(identificator_filtru),
        categorie_filtru = VALUES(categorie_filtru),
        match_id = VALUES(match_id),
        gazda = VALUES(gazda),
        oaspeti = VALUES(oaspeti),
        data_import = VALUES(data_import),
        ora_meci = VALUES(ora_meci),
        cota_1 = VALUES(cota_1),
        cota_x = VALUES(cota_x),
        cota_2 = VALUES(cota_2),
        psf1 = VALUES(psf1),
        psfx = VALUES(psfx),
        psf2 = VALUES(psf2),
        status_sursa = VALUES(status_sursa),
        audit_verdict = VALUES(audit_verdict),
        audit_proc = VALUES(audit_proc),
        updated_at = CURRENT_TIMESTAMP
";

$stmtInsert = $conn->prepare($sqlInsert);

if (!$stmtInsert) {
    die('Eroare prepare INSERT: ' . $conn->error);
}

$salvate = 0;
$actualizate = 0;
$erori = 0;

foreach ($selectat as $id_off_selectat) {
    $id_off_key = (string)$id_off_selectat;

    if (!isset($meci_data[$id_off_key]) || !is_array($meci_data[$id_off_key])) {
        $erori++;
        continue;
    }

    $row = $meci_data[$id_off_key];

    $id_off = isset($row['id_off']) && $row['id_off'] !== '' ? (int)$row['id_off'] : null;
    $match_id = isset($row['match_id']) && trim((string)$row['match_id']) !== '' ? trim((string)$row['match_id']) : null;
    $gazda = trim((string)($row['gazda'] ?? ''));
    $oaspeti = trim((string)($row['oaspeti'] ?? ''));
    $data_import = trim((string)($row['data_import'] ?? ''));
    $data_meci = trim((string)($row['data_meci'] ?? ''));
    $ora_meci = trim((string)($row['ora_meci'] ?? ''));
    $status_sursa = trim((string)($row['status_sursa'] ?? 'superbet'));
    $audit_verdict = trim((string)($row['audit_verdict'] ?? ''));
    $audit_proc = trim((string)($row['audit_proc'] ?? ''));

    $cota_1 = ($row['cota_1'] !== '' && $row['cota_1'] !== null) ? (float)$row['cota_1'] : null;
    $cota_x = ($row['cota_x'] !== '' && $row['cota_x'] !== null) ? (float)$row['cota_x'] : null;
    $cota_2 = ($row['cota_2'] !== '' && $row['cota_2'] !== null) ? (float)$row['cota_2'] : null;

    $psf1 = ($row['psf1'] !== '' && $row['psf1'] !== null) ? (float)$row['psf1'] : null;
    $psfx = ($row['psfx'] !== '' && $row['psfx'] !== null) ? (float)$row['psfx'] : null;
    $psf2 = ($row['psf2'] !== '' && $row['psf2'] !== null) ? (float)$row['psf2'] : null;

    if ($gazda === '' || $oaspeti === '' || $data_meci === '') {
        $erori++;
        continue;
    }

    $stmtInsert->bind_param(
        "ississssssddddddsss",
        $filtru_id,
        $identificator_filtru,
        $categorie_filtru,
        $id_off,
        $match_id,
        $gazda,
        $oaspeti,
        $data_import,
        $data_meci,
        $ora_meci,
        $cota_1,
        $cota_x,
        $cota_2,
        $psf1,
        $psfx,
        $psf2,
        $status_sursa,
        $audit_verdict,
        $audit_proc
    );

    if ($stmtInsert->execute()) {
        if ($stmtInsert->affected_rows === 1) {
            $salvate++;
        } else {
            $actualizate++;
        }
    } else {
        $erori++;
    }
}

$stmtInsert->close();
$conn->close();

header(
    'Location: rezultate.php?filtru_id=' . $filtru_id .
    '&zi=' . urlencode($zi_selectata) .
    '&msg=salvat' .
    '&salvate=' . $salvate .
    '&actualizate=' . $actualizate .
    '&erori=' . $erori
);
exit;
?>
