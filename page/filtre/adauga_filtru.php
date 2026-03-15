<?php
require_once __DIR__ . '/../../include/conection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = '';

$categorii = $conn->query("SELECT id, denumire FROM categorii_filtre ORDER BY ordine ASC, denumire ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categorie_id = isset($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : 0;
    $identificator = trim($_POST['identificator'] ?? '');

    if ($categorie_id > 0) {
        $denumire_filtru = 'FILT_' . date('YmdHis') . '_' . rand(100, 999);

        $sql = "INSERT INTO filtre (categorie_id, denumire_filtru, identificator, activ)
                VALUES (?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $categorie_id, $denumire_filtru, $identificator);

        if ($stmt->execute()) {
            $filtru_id = (int)$stmt->insert_id;
            $stmt->close();

            $cota_1 = ($_POST['cota_1'] !== '') ? (float)$_POST['cota_1'] : null;
            $cota_x = ($_POST['cota_x'] !== '') ? (float)$_POST['cota_x'] : null;
            $cota_2 = ($_POST['cota_2'] !== '') ? (float)$_POST['cota_2'] : null;

            $sql2 = "INSERT INTO filtre_cote (filtru_id, cota_1, cota_x, cota_2, activ)
                     VALUES (?, ?, ?, ?, 1)";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("iddd", $filtru_id, $cota_1, $cota_x, $cota_2);

            if ($stmt2->execute()) {
                $msg = "Filtrul a fost adăugat.";
            } else {
                $msg = "Filtrul a fost creat, dar nu s-au salvat cotele: " . $stmt2->error;
            }
            $stmt2->close();
        } else {
            $msg = "Eroare la adăugare filtru: " . $stmt->error;
            $stmt->close();
        }
    } else {
        $msg = "Selectează o categorie.";
    }

    $categorii = $conn->query("SELECT id, denumire FROM categorii_filtre ORDER BY ordine ASC, denumire ASC");
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Adaugă filtru</title>
    <link rel="stylesheet" href="../../assets/sport.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f3f3f3; }
        .container { width: 95%; margin: 20px auto; background: white; padding: 20px; border-radius: 10px; }
        .box { background: #f8f8f8; padding: 15px; border-radius: 8px; max-width: 700px; }
        input, select { padding: 8px; margin: 8px 0; width: 100%; }
        button { padding: 10px 16px; cursor: pointer; }
        .msg { margin: 10px 0; padding: 10px; background: #eef6ee; border: 1px solid #b8d8b8; border-radius: 6px; }
        .links a { margin-right: 12px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../include/menu.php'; ?>

<div class="container">
    <h1>Adaugă filtru</h1>

    <div class="links">
        <a href="categorii_filtre.php">Categorii</a>
        <a href="lista_filtru.php">Listă filtre</a>
    </div>

    <?php if ($msg !== ''): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="box">
        <form method="post">
            <label>Categorie</label>
            <select name="categorie_id" required>
                <option value="">-- alege --</option>
                <?php while ($cat = $categorii->fetch_assoc()): ?>
                    <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['denumire']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Identificator (scrii ce vrei)</label>
            <input type="text" name="identificator" placeholder="ex: 2P_Pauza1_2P1.5">

            <label>Cota 1</label>
            <input type="text" name="cota_1" placeholder="ex: 3.10">

            <label>Cota X</label>
            <input type="text" name="cota_x" placeholder="ex: 3.00">

            <label>Cota 2</label>
            <input type="text" name="cota_2" placeholder="ex: 2.25">

            <button type="submit">Salvează filtru</button>
        </form>
    </div>
</div>
</body>
</html>
