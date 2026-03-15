<?php
require_once __DIR__ . '/../../../include/conection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rezultate.php');
    exit;
}

$filtru_id    = isset($_POST['filtru_id']) ? (int)$_POST['filtru_id'] : 0;
$zi_selectata = trim($_POST['zi_selectata'] ?? '');
$selectat     = $_POST['selectat'] ?? [];
$meci_data    = $_POST['meci_data'] ?? [];

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

function zi_scurta_la_data_completa(string $zi_scurta): string
{
    $zi_scurta = trim($zi_scurta);

    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $zi_scurta)) {
        return $zi_scurta;
    }

    if (preg_match('/^\d{2}\.\d{2}$/', $zi_scurta)) {
        return $zi_scurta . '.' . date('Y');
    }

    return $zi_scurta;
}

$table = 'manage2.meciuri_master';

/*
|--------------------------------------------------------------------------
| INSERT / UPDATE IN MASTER
|--------------------------------------------------------------------------
| UNIC pe match_id, deci folosim ON DUPLICATE KEY UPDATE pe match_id
*/
$sqlInsert = "
    INSERT INTO $table (
        match_id,
        id_off,
        `round`,
        data_meci,
        ora_meci,
        gazda,
        oaspeti,
        superbet_1,
        superbet_x,
        superbet_2,
        cota_psf_1,
        cota_psf_x,
        cota_psf_2,
        status
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
    ON DUPLICATE KEY UPDATE
        id_off = COALESCE(VALUES(id_off), id_off),
        `round` = COALESCE(NULLIF(VALUES(`round`), ''), `round`),
        data_meci = COALESCE(NULLIF(VALUES(data_meci), ''), data_meci),
        ora_meci = COALESCE(NULLIF(VALUES(ora_meci), ''), ora_meci),
        gazda = COALESCE(NULLIF(VALUES(gazda), ''), gazda),
        oaspeti = COALESCE(NULLIF(VALUES(oaspeti), ''), oaspeti),
        superbet_1 = COALESCE(VALUES(superbet_1), superbet_1),
        superbet_x = COALESCE(VALUES(superbet_x), superbet_x),
        superbet_2 = COALESCE(VALUES(superbet_2), superbet_2),
        cota_psf_1 = COALESCE(VALUES(cota_psf_1), cota_psf_1),
        cota_psf_x = COALESCE(VALUES(cota_psf_x), cota_psf_x),
        cota_psf_2 = COALESCE(VALUES(cota_psf_2), cota_psf_2),
        status = COALESCE(NULLIF(VALUES(status), ''), status)
";

$stmtInsert = $conn->prepare($sqlInsert);

if (!$stmtInsert) {
    die('Eroare prepare INSERT master: ' . $conn->error);
}

$salvate = 0;
$actualizate = 0;
$erori = 0;

foreach ($selectat as $rowKey) {
    $rowKey = (string)$rowKey;

    if (!isset($meci_data[$rowKey]) || !is_array($meci_data[$rowKey])) {
        $erori++;
        continue;
    }

    $row = $meci_data[$rowKey];

    $id_off = (isset($row['id_off']) && $row['id_off'] !== '') ? (int)$row['id_off'] : null;
    $match_id = (isset($row['match_id']) && trim((string)$row['match_id']) !== '') ? trim((string)$row['match_id']) : null;

    $gazda   = trim((string)($row['gazda'] ?? ''));
    $oaspeti = trim((string)($row['oaspeti'] ?? ''));

    $data_meci_raw = trim((string)($row['data_meci'] ?? ''));
    if ($data_meci_raw === '') {
        $data_meci_raw = $zi_selectata;
    }
    $data_meci = zi_scurta_la_data_completa($data_meci_raw);

    $ora_meci = trim((string)($row['ora_meci'] ?? ''));
    $round    = trim((string)($row['round'] ?? ''));
    $status   = trim((string)($row['status'] ?? ''));

    $superbet_1 = (isset($row['cota_1']) && $row['cota_1'] !== '' && $row['cota_1'] !== null) ? (float)$row['cota_1'] : null;
    $superbet_x = (isset($row['cota_x']) && $row['cota_x'] !== '' && $row['cota_x'] !== null) ? (float)$row['cota_x'] : null;
    $superbet_2 = (isset($row['cota_2']) && $row['cota_2'] !== '' && $row['cota_2'] !== null) ? (float)$row['cota_2'] : null;

    $cota_psf_1 = (isset($row['psf1']) && $row['psf1'] !== '' && $row['psf1'] !== null) ? (float)$row['psf1'] : null;
    $cota_psf_x = (isset($row['psfx']) && $row['psfx'] !== '' && $row['psfx'] !== null) ? (float)$row['psfx'] : null;
    $cota_psf_2 = (isset($row['psf2']) && $row['psf2'] !== '' && $row['psf2'] !== null) ? (float)$row['psf2'] : null;

    if ($gazda === '' || $oaspeti === '' || $data_meci === '') {
        $erori++;
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Dacă nu ai match_id, nu putem folosi cheia unică pe match_id
    |--------------------------------------------------------------------------
    */
    if ($match_id === null || $match_id === '') {
        $erori++;
        continue;
    }

    $stmtInsert->bind_param(
        "sisssssdddddds",
        $match_id,
        $id_off,
        $round,
        $data_meci,
        $ora_meci,
        $gazda,
        $oaspeti,
        $superbet_1,
        $superbet_x,
        $superbet_2,
        $cota_psf_1,
        $cota_psf_x,
        $cota_psf_2,
        $status
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

