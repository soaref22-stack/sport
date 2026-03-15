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
    die('Data import invalidă.');
}

$importZiId = find_or_create_import_zi($conn, $data_import);

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$pdfOfertaPath = null;
$pdfPsfPath = null;

if (isset($_FILES['pdf_oferta']) && $_FILES['pdf_oferta']['error'] === UPLOAD_ERR_OK) {
    $pdfOfertaName = 'oferta_' . str_replace('.', '-', $data_import) . '_' . time() . '.pdf';
    $pdfOfertaPath = $uploadDir . '/' . $pdfOfertaName;
    move_uploaded_file($_FILES['pdf_oferta']['tmp_name'], $pdfOfertaPath);
    $pdfOfertaPath = 'uploads/' . $pdfOfertaName;
}

if (isset($_FILES['pdf_psf']) && $_FILES['pdf_psf']['error'] === UPLOAD_ERR_OK) {
    $pdfPsfName = 'psf_' . str_replace('.', '-', $data_import) . '_' . time() . '.pdf';
    $pdfPsfPath = $uploadDir . '/' . $pdfPsfName;
    move_uploaded_file($_FILES['pdf_psf']['tmp_name'], $pdfPsfPath);
    $pdfPsfPath = 'uploads/' . $pdfPsfName;
}

if ($pdfOfertaPath !== null) {
    $sql = "UPDATE import_zile SET pdf_oferta = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $pdfOfertaPath, $importZiId);
    $stmt->execute();
    $stmt->close();
}

if ($pdfPsfPath !== null) {
    $sql = "UPDATE import_zile SET pdf_psf = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $pdfPsfPath, $importZiId);
    $stmt->execute();
    $stmt->close();
}

header("Location: adauga_oferta.php?uploaded=1");
exit;
