<?php
require_once __DIR__ . '/../../include/conection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actiune = $_POST['actiune'] ?? '';

    if ($actiune === 'adauga') {
        $cod = trim($_POST['cod'] ?? '');
        $denumire = trim($_POST['denumire'] ?? '');

        if ($cod !== '' && $denumire !== '') {
            $sqlMax = "SELECT COALESCE(MAX(ordine), 0) + 1 AS next_ordine FROM categorii_filtre";
            $resMax = $conn->query($sqlMax);
            $rowMax = $resMax->fetch_assoc();
            $ordine = (int)$rowMax['next_ordine'];

            $sql = "INSERT INTO categorii_filtre (cod, denumire, ordine) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $cod, $denumire, $ordine);

            if ($stmt->execute()) {
                $msg = "Categoria a fost adăugată.";
            } else {
                $msg = "Eroare la adăugare: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $msg = "Completează cod și denumire.";
        }
    }

    if ($actiune === 'sterge') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id > 0) {
            $sql = "DELETE FROM categorii_filtre WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $msg = "Categoria a fost ștearsă.";
            } else {
                $msg = "Nu se poate șterge. Probabil este folosită în filtre.";
            }
            $stmt->close();
        }
    }
}

$categorii = $conn->query("SELECT * FROM categorii_filtre ORDER BY ordine ASC, denumire ASC");
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Categorii filtre</title>
    <link rel="stylesheet" href="../../assets/sport.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f3f3f3; }
        .container { width: 95%; margin: 20px auto; background: white; padding: 20px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background: #444; color: white; }
        .form-box { background: #f8f8f8; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        input { padding: 8px; margin-right: 10px; }
        button { padding: 8px 14px; cursor: pointer; }
        .msg { margin: 10px 0; padding: 10px; background: #eef6ee; border: 1px solid #b8d8b8; border-radius: 6px; }
        .links a { margin-right: 12px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../include/menu.php'; ?>

<div class="container">
    <h1>Categorii filtre</h1>

    <div class="links">
        <a href="adauga_filtru.php">Adaugă filtru</a>
        <a href="lista_filtru.php">Listă filtre</a>
    </div>

    <?php if ($msg !== ''): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="form-box">
        <h3>Adaugă categorie</h3>
        <form method="post">
            <input type="hidden" name="actiune" value="adauga">
            <input type="text" name="cod" placeholder="cod ex: favorit" required>
            <input type="text" name="denumire" placeholder="denumire ex: Favorit" required>
            <button type="submit">Salvează</button>
        </form>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Cod</th>
            <th>Denumire</th>
            <th>Ordine</th>
            <th>Activ</th>
            <th>Șterge</th>
        </tr>
        <?php while ($row = $categorii->fetch_assoc()): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= htmlspecialchars($row['cod']) ?></td>
                <td><?= htmlspecialchars($row['denumire']) ?></td>
                <td><?= (int)$row['ordine'] ?></td>
                <td><?= isset($row['activ']) ? (int)$row['activ'] : 1 ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Sigur ștergi categoria?');">
                        <input type="hidden" name="actiune" value="sterge">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button type="submit">Șterge</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
