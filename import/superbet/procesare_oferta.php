<?php
require_once __DIR__ . '/../../include/conection.php';
require_once __DIR__ . '/lib_import.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adauga_oferta.php");
    exit;
}

$data_import = trim($_POST['data_import'] ?? '');

if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $data_import)) {
    die("Data import invalidă. Format corect: DD.MM.YYYY");
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    die("Nu ai încărcat CSV-ul corect.");
}

$tmpFile = $_FILES['csv_file']['tmp_name'];
$importZiId = find_or_create_import_zi($conn, $data_import);

$total = 0;
$inserted = 0;
$updated = 0;
$skipped = 0;

if (($handle = fopen($tmpFile, "r")) === false) {
    die("Nu pot deschide fișierul CSV.");
}

// sari peste header
fgetcsv($handle, 2000, ",", '"');

$sqlCheck = "SELECT id FROM import_oferte WHERE import_zi_id = ? AND id_off = ?";
$stmtCheck = $conn->prepare($sqlCheck);

$sqlInsert = "INSERT INTO import_oferte (
    import_zi_id, id_off, gazda, oaspeti, gazda_norm, oaspeti_norm,
    incepe_text, data_meci, ora_meci,
    cota_1, cota_x, cota_2,
    psf1, psfx, psf2
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmtInsert = $conn->prepare($sqlInsert);

$sqlUpdate = "UPDATE import_oferte SET
    gazda = ?, oaspeti = ?, gazda_norm = ?, oaspeti_norm = ?,
    incepe_text = ?, data_meci = ?, ora_meci = ?,
    cota_1 = ?, cota_x = ?, cota_2 = ?,
    psf1 = ?, psfx = ?, psf2 = ?
    WHERE import_zi_id = ? AND id_off = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);

while (($row = fgetcsv($handle, 2000, ",")) !== false) {
    $total++;

    if (count($row) < 7) {
        $skipped++;
        continue;
    }

    $id_off   = isset($row[0]) ? (int)trim($row[0]) : 0;
    $gazda    = trim($row[1] ?? '');
    $oaspeti  = trim($row[2] ?? '');
    $incepe   = trim($row[3] ?? '');

    $cota_1   = (isset($row[4]) && $row[4] !== '' && is_numeric($row[4])) ? (float)$row[4] : null;
    $cota_x   = (isset($row[5]) && $row[5] !== '' && is_numeric($row[5])) ? (float)$row[5] : null;
    $cota_2   = (isset($row[6]) && $row[6] !== '' && is_numeric($row[6])) ? (float)$row[6] : null;

    $psf1     = (isset($row[7]) && $row[7] !== '' && is_numeric($row[7])) ? (float)$row[7] : null;
    $psfx     = (isset($row[8]) && $row[8] !== '' && is_numeric($row[8])) ? (float)$row[8] : null;
    $psf2     = (isset($row[9]) && $row[9] !== '' && is_numeric($row[9])) ? (float)$row[9] : null;

    if ($id_off <= 0 || $gazda === '' || $oaspeti === '') {
        $skipped++;
        continue;
    }

    $gazda_norm = normalize_team_name($gazda);
    $oaspeti_norm = normalize_team_name($oaspeti);

    $parsed = parse_incepe_text($incepe);
    $data_meci = $parsed['data_meci'];
    $ora_meci = $parsed['ora_meci'];

    $stmtCheck->bind_param("ii", $importZiId, $id_off);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $exists = $resCheck->fetch_assoc();

    if ($exists) {
        $stmtUpdate->bind_param(
            "sssssssddddddii",
            $gazda, $oaspeti, $gazda_norm, $oaspeti_norm,
            $incepe, $data_meci, $ora_meci,
            $cota_1, $cota_x, $cota_2,
            $psf1, $psfx, $psf2,
            $importZiId, $id_off
        );
        $stmtUpdate->execute();
        $updated++;
    } else {
        $stmtInsert->bind_param(
            "iisssssssdddddd",
            $importZiId, $id_off, $gazda, $oaspeti, $gazda_norm, $oaspeti_norm,
            $incepe, $data_meci, $ora_meci,
            $cota_1, $cota_x, $cota_2,
            $psf1, $psfx, $psf2
        );
        $stmtInsert->execute();
        $inserted++;
    }
}

fclose($handle);

$stmtCheck->close();
$stmtInsert->close();
$stmtUpdate->close();

header("Location: adauga_oferta.php?ok=1&data_import=" . urlencode($data_import) . "&total=$total&inserted=$inserted&updated=$updated&skipped=$skipped");
exit;
