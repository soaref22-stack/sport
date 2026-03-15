<?php
session_start();
require_once __DIR__ . '/../../include/conection.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$table = "manage2.meciuri_master";
$selected_day = $_GET['zi'] ?? null;

$messages = $_SESSION['sportx_messages'] ?? [];
unset($_SESSION['sportx_messages']);

/*
|--------------------------------------------------------------------------
| MAPARE FORMULAR -> BAZA DE DATE
|--------------------------------------------------------------------------
*/
$map = [
    'match_id'      => 'match_id',
    'id_off'        => 'id_off',
    'ora'           => 'ora_meci',
    'gazda'         => 'gazda',
    'oaspeti'       => 'oaspeti',
    'score'         => 'score',
    'status'        => 'status_curent',

    'home_goals'    => 'home_goals',
    'away_goals'    => 'away_goals',
    'home_goals_1h' => 'home_goals_1h',
    'away_goals_1h' => 'away_goals_1h',

    // Superbet
    'superbet_1'    => 'superbet_1',
    'superbet_x'    => 'superbet_x',
    'superbet_2'    => 'superbet_2',

    // Betano
    'betano_1'      => 'betano_1',
    'betano_x'      => 'betano_x',
    'betano_2'      => 'betano_2',
    
    // Offline
    'ofline1'      => 'offline_1',
    'oflinex'      => 'offline_x',
    'ofline2'      => 'offline_2',


    // Totale / GG
    'p2_5'          => 'peste_2_5',
    's2_5'          => 'sub_2_5',
    'da_gg'         => 'gg_da',
    'nu_gg'         => 'gg_nu',

    'nota'          => 'nota',
    'tara'          => 'tara',
    'competitie'    => 'competitie',
    'round_text'    => 'round_text',
    'foto'          => 'foto',
    
    // PSF
    'cota_psf_1'       => 'cota_psf_1',
    'cota_psf_x'       => 'cota_psf_x',
    'cota_psf_2'       => 'cota_psf_2',
];

/*
|--------------------------------------------------------------------------
| HELPERI
|--------------------------------------------------------------------------
*/
function parseDecimalOrNull($value): ?float
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    return (float) str_replace(',', '.', $value);
}

function parseIntOrNull($value): ?int
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    return (int) $value;
}

function categorieDinCotaMinima(?float $valoare): ?string
{
    if ($valoare === null) {
        return null;
    }

    if ($valoare >= 1.40 && $valoare < 1.50) return 'sport1';
    if ($valoare >= 1.50 && $valoare < 1.60) return 'sport2';
    if ($valoare >= 1.60 && $valoare < 1.70) return 'sport3';
    if ($valoare >= 1.70 && $valoare < 1.80) return 'sport4';
    if ($valoare >= 1.80 && $valoare < 1.90) return 'sport5';
    if ($valoare >= 1.90 && $valoare < 2.00) return 'sport6';
    if ($valoare >= 2.00 && $valoare < 2.10) return 'sport7';
    if ($valoare >= 2.10 && $valoare < 2.20) return 'sport8';
    if ($valoare >= 2.20 && $valoare < 2.30) return 'sport9';
    if ($valoare >= 2.30 && $valoare < 2.40) return 'sport10';
    if ($valoare >= 2.40 && $valoare < 2.50) return 'sport11';
    if ($valoare >= 2.50 && $valoare <= 2.70) return 'sport12';

    return null;
}

/*
|--------------------------------------------------------------------------
| UPDATE RAND
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $update_count = 0;
    $error_count = 0;

    foreach ($_POST['update'] as $id => $clicked) {
        $id = (int)$id;
        if ($id <= 0) {
            continue;
        }

        $sql_parts = [];
        $params = [];
        $types = '';

        foreach ($map as $post_field => $db_field) {
            if (!isset($_POST[$post_field][$id])) {
                continue;
            }

            $raw = $_POST[$post_field][$id];
            $val = trim((string)$raw);

            if ($val === '') {
                $sql_parts[] = "`$db_field` = NULL";
                continue;
            }

            $sql_parts[] = "`$db_field` = ?";

            if (in_array($db_field, [
    'superbet_1', 'superbet_x', 'superbet_2',
    'betano_1', 'betano_x', 'betano_2',
    'offline_1', 'offline_x', 'offline_2',
    'cota_psf_1', 'cota_psf_x', 'cota_psf_2',
    'peste_2_5', 'sub_2_5', 'gg_da', 'gg_nu',
    'valoare_cota_curenta'
], true)) {
    $params[] = parseDecimalOrNull($val);
    $types .= 'd';
} elseif (in_array($db_field, [
                'id_off', 'home_goals', 'away_goals', 'home_goals_1h', 'away_goals_1h',
                'are_flash', 'are_superbet', 'are_offline'
            ], true)) {
                $params[] = parseIntOrNull($val);
                $types .= 'i';
            } else {
                $params[] = $val;
                $types .= 's';
            }
        }

        if (empty($sql_parts)) {
            continue;
        }

        $sql = "UPDATE $table SET " . implode(', ', $sql_parts) . " WHERE id = ?";
        $params[] = $id;
        $types .= 'i';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error_count++;
            continue;
        }

        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $update_count++;
        } else {
            $error_count++;
        }

        $stmt->close();
    }

    $_SESSION['sportx_messages'] = [
        "Update: Salvate {$update_count} | Erori {$error_count}"
    ];

    header("Location: " . $_SERVER['PHP_SELF'] . ($selected_day ? "?zi=" . urlencode($selected_day) : ""));
    exit;
}
/*
|--------------------------------------------------------------------------
| STERGE RAND
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $delete_count = 0;
    $error_count = 0;

    foreach ($_POST['delete'] as $id => $clicked) {
        $id = (int)$id;
        if ($id <= 0) {
            continue;
        }

        $stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
        if (!$stmt) {
            $error_count++;
            continue;
        }

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $delete_count++;
        } else {
            $error_count++;
        }

        $stmt->close();
    }

    $_SESSION['sportx_messages'] = [
        "Șterse {$delete_count} | Erori {$error_count}"
    ];

    header("Location: " . $_SERVER['PHP_SELF'] . ($selected_day ? "?zi=" . urlencode($selected_day) : ""));
    exit;
}
/*
|--------------------------------------------------------------------------
| MUTA AUTOMAT DUPA COTA MINIMA SUPERBET
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['muta_toate'])) {
    $moved_count = 0;
    $skip_count = 0;
    $error_count = 0;

    if (!empty($selected_day)) {
        $stmt = $conn->prepare("
            SELECT id, superbet_1, superbet_2, score
            FROM $table
            WHERE data_meci = ?
              AND (categorie_cota_curenta IS NULL OR categorie_cota_curenta = '')
            ORDER BY STR_TO_DATE(ora_meci, '%H:%i') ASC, gazda ASC, oaspeti ASC
        ");

        if ($stmt) {
            $stmt->bind_param("s", $selected_day);
            $stmt->execute();
            $result_move = $stmt->get_result();

            while ($row = $result_move->fetch_assoc()) {
                $id = (int)$row['id'];
                $score = trim((string)($row['score'] ?? ''));

                // muta doar daca are score
                if ($score === '') {
                    $skip_count++;
                    continue;
                }

                $v1 = parseDecimalOrNull($row['superbet_1'] ?? '');
                $v2 = parseDecimalOrNull($row['superbet_2'] ?? '');

                $cote = array_filter([$v1, $v2], static function ($v) {
                    return $v !== null && $v > 0;
                });

                if (empty($cote)) {
                    $skip_count++;
                    continue;
                }

                $valoare_reala = min($cote);

                // plafonare la 2.50
                $valoare = ($valoare_reala > 2.50) ? 2.50 : $valoare_reala;

                $categorie = categorieDinCotaMinima($valoare);

                if ($categorie === null) {
                    $skip_count++;
                    continue;
                }

                $stmt_update = $conn->prepare("
                    UPDATE $table
                    SET categorie_cota_curenta = ?, valoare_cota_curenta = ?
                    WHERE id = ?
                ");

                if (!$stmt_update) {
                    $error_count++;
                    continue;
                }

                $stmt_update->bind_param("sdi", $categorie, $valoare, $id);

                if ($stmt_update->execute()) {
                    $moved_count++;
                } else {
                    $error_count++;
                }

                $stmt_update->close();
            }

            $stmt->close();
        } else {
            $error_count++;
        }
    }

    $_SESSION['sportx_messages'] = [
        "Mutate {$moved_count} | Sărite {$skip_count} | Erori {$error_count}"
    ];

    header("Location: " . $_SERVER['PHP_SELF'] . ($selected_day ? "?zi=" . urlencode($selected_day) : ""));
    exit;
}

/*
|--------------------------------------------------------------------------
| ZILE DISPONIBILE
|--------------------------------------------------------------------------
*/
$today = date('d.m.Y');

$days_sql = "
    SELECT DISTINCT display_date
    FROM (
        SELECT
            CASE
                WHEN data_meci IS NULL OR TRIM(data_meci) = '' THEN ?
                ELSE data_meci
            END AS display_date
        FROM $table
    ) t
    ORDER BY
        CASE WHEN display_date = 'no_date' THEN 1 ELSE 0 END,
        STR_TO_DATE(display_date, '%d.%m.%Y') DESC
";

$days_stmt = $conn->prepare($days_sql);
$days_stmt->bind_param("s", $today);
$days_stmt->execute();
$days_result = $days_stmt->get_result();

$available_days = [];
while ($day_row = $days_result->fetch_assoc()) {
    $available_days[] = $day_row['display_date'];
}
$days_stmt->close();

/*
|--------------------------------------------------------------------------
| MECIURI PE ZI
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT *,
        CASE
            WHEN data_meci IS NULL OR TRIM(data_meci) = '' THEN ?
            ELSE data_meci
        END AS display_date
    FROM $table
";

$params = [$today];
$types = "s";

if ($selected_day) {
    $sql .= " HAVING display_date = ?";
    $params[] = $selected_day;
    $types .= "s";
}

$sql .= "
    ORDER BY
        STR_TO_DATE(display_date, '%d.%m.%Y') ASC,
        STR_TO_DATE(ora_meci, '%H:%i') ASC,
        gazda ASC,
        oaspeti ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$matches_by_day = [];
$total_matches = 0;

while ($row = $result->fetch_assoc()) {
    if (!empty($row['categorie_cota_curenta'])) {
        continue;
    }

    $day = $row['display_date'];

    if (!isset($matches_by_day[$day])) {
        $matches_by_day[$day] = [];
    }

    $matches_by_day[$day][] = $row;
    $total_matches++;
}
$stmt->close();
?>
