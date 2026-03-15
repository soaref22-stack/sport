<?php
require_once __DIR__ . '/../../include/conection.php';

header('Content-Type: application/json; charset=utf-8');

$data_import = trim($_GET['data_import'] ?? '');

if ($data_import === '') {
    echo json_encode([
        'total' => 0,
        'cu_psf' => 0,
        'fara_psf' => 0,
        'pdf_oferta' => null,
        'pdf_psf' => null
    ]);
    exit;
}

$sqlZi = "SELECT id, pdf_oferta, pdf_psf FROM import_zile WHERE data_import = ?";
$stmtZi = $conn->prepare($sqlZi);
$stmtZi->bind_param("s", $data_import);
$stmtZi->execute();
$resZi = $stmtZi->get_result();
$zi = $resZi->fetch_assoc();
$stmtZi->close();

if (!$zi) {
    echo json_encode([
        'total' => 0,
        'cu_psf' => 0,
        'fara_psf' => 0,
        'pdf_oferta' => null,
        'pdf_psf' => null
    ]);
    exit;
}

$import_zi_id = (int)$zi['id'];

$sql = "
    SELECT
        COUNT(*) AS total,
        SUM(
            CASE
                WHEN psf1 IS NOT NULL AND psfx IS NOT NULL AND psf2 IS NOT NULL
                THEN 1 ELSE 0
            END
        ) AS cu_psf,
        SUM(
            CASE
                WHEN psf1 IS NULL OR psfx IS NULL OR psf2 IS NULL
                THEN 1 ELSE 0
            END
        ) AS fara_psf
    FROM import_oferte
    WHERE import_zi_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $import_zi_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

echo json_encode([
    'total' => (int)($row['total'] ?? 0),
    'cu_psf' => (int)($row['cu_psf'] ?? 0),
    'fara_psf' => (int)($row['fara_psf'] ?? 0),
    'pdf_oferta' => !empty($zi['pdf_oferta']) ? basename($zi['pdf_oferta']) : null,
    'pdf_psf' => !empty($zi['pdf_psf']) ? basename($zi['pdf_psf']) : null
]);
