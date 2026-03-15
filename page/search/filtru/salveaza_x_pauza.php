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

function data_completa(string $data): string
{
    $data = trim($data);

    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $data)) {
        return $data;
    }

    if (preg_match('/^\d{2}\.\d{2}$/', $data)) {
        return $data . '.' . date('Y');
    }

    return $data;
}

/*
|--------------------------------------------------------------------------
| LUAM FILTRUL
|--------------------------------------------------------------------------
*/
$sqlFiltru = "
    SELECT 
        f.id,
        f.identificator,
        f.denumire_filtru
    FROM filtre f
    WHERE f.id = ?
    LIMIT 1
";

$stmtFiltru = $conn->prepare($sqlFiltru);
if (!$stmtFiltru) {
    die('Eroare prepare filtru: ' . $conn->error);
}

$stmtFiltru->bind_param("i", $filtru_id);
$stmtFiltru->execute();
$resFiltru = $stmtFiltru->get_result();
$filtru = $resFiltru->fetch_assoc();
$stmtFiltru->close();

if (!$filtru) {
    die('Filtrul nu există.');
}

$identificator_filtru = trim((string)($filtru['identificator'] ?? ''));
if ($identificator_filtru === '') {
    $identificator_filtru = trim((string)($filtru['denumire_filtru'] ?? ''));
}

/*
|--------------------------------------------------------------------------
| INSERT IN rezultate_filtre
|--------------------------------------------------------------------------
*/
$table = 'rezultate_filtre';

$sqlInsert = "
    INSERT INTO $table (
        filtru_id,
        identificator_filtru,
        id_off,
        match_id,
        gazda,
        oaspeti,
        data_meci,
        ora_meci,
        cota_1,
        cota_x,
        cota_2,
        psf1,
        psfx,
        psf2
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
";

$stmtInsert = $conn->prepare($sqlInsert);
if (!$stmtInsert) {
    die('Eroare prepare INSERT rezultate_filtre: ' . $conn->error);
}

$salvate = 0;
$erori = 0;

foreach ($selectat as $rowKey) {
    $rowKey = (string)$rowKey;

    if (!isset($meci_data[$rowKey]) || !is_array($meci_data[$rowKey])) {
        $erori++;
        continue;
    }

    $row = $meci_data[$rowKey];

    $id_off   = isset($row['id_off']) && $row['id_off'] !== '' ? (int)$row['id_off'] : null;
    $match_id = isset($row['match_id']) && trim((string)$row['match_id']) !== '' ? trim((string)$row['match_id']) : null;

    $gazda   = trim((string)($row['gazda'] ?? ''));
    $oaspeti = trim((string)($row['oaspeti'] ?? ''));

    $data_meci_raw = trim((string)($row['data_meci'] ?? ''));
    if ($data_meci_raw === '') {
        $data_meci_raw = $zi_selectata;
    }
    $data_meci = data_completa($data_meci_raw);

    $ora_meci = trim((string)($row['ora_meci'] ?? ''));

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
        "isisssssdddddd",
        $filtru_id,
        $identificator_filtru,
        $id_off,
        $match_id,
        $gazda,
        $oaspeti,
        $data_meci,
        $ora_meci,
        $cota_1,
        $cota_x,
        $cota_2,
        $psf1,
        $psfx,
        $psf2
    );

    if ($stmtInsert->execute()) {
        $salvate++;
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
    '&erori=' . $erori
);
exit;
?>
