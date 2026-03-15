<?php
require_once __DIR__ . '/../../include/conection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adauga_oferta.php");
    exit;
}

$data_import = trim($_POST['data_import'] ?? '');

if (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $data_import)) {
    die('Data import invalidă.');
}

$sql = "SELECT id, pdf_oferta, pdf_psf FROM import_zile WHERE data_import = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $data_import);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: adauga_oferta.php");
    exit;
}

$importZiId = (int)$row['id'];

if (!empty($row['pdf_oferta'])) {
    $full = __DIR__ . '/' . $row['pdf_oferta'];
    if (file_exists($full)) {
        unlink($full);
    }
}

if (!empty($row['pdf_psf'])) {
    $full = __DIR__ . '/' . $row['pdf_psf'];
    if (file_exists($full)) {
        unlink($full);
    }
}

$sqlDelete = "DELETE FROM import_zile WHERE id = ?";
$stmtDelete = $conn->prepare($sqlDelete);
$stmtDelete->bind_param("i", $importZiId);
$stmtDelete->execute();
$stmtDelete->close();

header("Location: adauga_oferta.php?deleted=1");
exit;
