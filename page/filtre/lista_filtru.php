<?php
require_once __DIR__ . '/../../include/conection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['actiune'] ?? '') === 'sterge') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        $conn->begin_transaction();

        try {
            $stmt1 = $conn->prepare("DELETE FROM filtre_cote WHERE filtru_id = ?");
            $stmt1->bind_param("i", $id);
            $stmt1->execute();
            $stmt1->close();

            $stmt2 = $conn->prepare("DELETE FROM filtre WHERE id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $stmt2->close();

            $conn->commit();
            $msg = "Filtrul a fost șters.";
        } catch (Throwable $e) {
            $conn->rollback();
            $msg = "Eroare la ștergere: " . $e->getMessage();
        }
    }
}

$sql = "
    SELECT
        f.id,
        f.denumire_filtru,
        f.identificator,
        f.activ,
        c.denumire AS categorie,
        fc.cota_1,
        fc.cota_x,
        fc.cota_2
    FROM filtre f
    LEFT JOIN categorii_filtre c ON c.id = f.categorie_id
    LEFT JOIN filtre_cote fc ON fc.filtru_id = f.id
    ORDER BY c.denumire ASC, f.identificator ASC, f.id ASC
";
$rez = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Listă filtre</title>
    <link rel="stylesheet" href="../../assets/sport.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f3f3f3; }
        .container { width: 96%; margin: 20px auto; background: white; padding: 20px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; vertical-align: middle; }
        th { background: #444; color: white; }
        .msg { margin: 10px 0; padding: 10px; background: #eef6ee; border: 1px solid #b8d8b8; border-radius: 6px; }
        .links a { margin-right: 12px; }
        .intern { font-size: 11px; color: #666; }
        .ident { font-weight: bold; color: #0a58ca; }
        .btn-link {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
            background: #0d6efd;
            color: white;
            font-size: 12px;
        }
        .btn-del {
            padding: 6px 10px;
            border: 0;
            border-radius: 6px;
            background: #dc3545;
            color: white;
            cursor: pointer;
            font-size: 12px;
        }
        .cat {
            background: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../include/menu.php'; ?>

<div class="container">
    <h1>Listă filtre</h1>

    <div class="links">
        <a href="categorii_filtre.php">Categorii</a>
        <a href="adauga_filtru.php">Adaugă filtru</a>
    </div>

    <?php if ($msg !== ''): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Identificator / Căutare</th>
            <th>Denumire internă</th>
            <th>Categorie</th>
            <th>1</th>
            <th>X</th>
            <th>2</th>
            <th>Activ</th>
            <th>Caută</th>
            <th>Șterge</th>
        </tr>

        <?php
        $lastCategorie = null;
        while ($row = $rez->fetch_assoc()):
            if ($lastCategorie !== $row['categorie']):
                $lastCategorie = $row['categorie'];
        ?>
            <tr class="cat">
                <td colspan="10"><?= htmlspecialchars((string)$lastCategorie) ?></td>
            </tr>
        <?php endif; ?>

            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td class="ident"><?= htmlspecialchars((string)$row['identificator']) ?></td>
                <td>
                    <?= htmlspecialchars((string)$row['denumire_filtru']) ?>
                    <div class="intern">nume intern unic</div>
                </td>
                <td><?= htmlspecialchars((string)$row['categorie']) ?></td>
                <td><?= $row['cota_1'] !== null ? htmlspecialchars((string)$row['cota_1']) : '-' ?></td>
                <td><?= $row['cota_x'] !== null ? htmlspecialchars((string)$row['cota_x']) : '-' ?></td>
                <td><?= $row['cota_2'] !== null ? htmlspecialchars((string)$row['cota_2']) : '-' ?></td>
                <td><?= (int)$row['activ'] ?></td>
                <td>
                    <a class="btn-link" href="cauta_filtru_exact.php?filtru_id=<?= (int)$row['id'] ?>">
                        Caută exact
                    </a>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('Sigur ștergi filtrul?');">
                        <input type="hidden" name="actiune" value="sterge">
                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                        <button type="submit" class="btn-del">Șterge</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
