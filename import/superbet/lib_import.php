<?php

function normalize_team_name(string $name): string
{
    $name = trim($name);
    $name = mb_strtolower($name, 'UTF-8');

    $replace = [
        'ă' => 'a', 'â' => 'a', 'î' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
        '.' => ' ', ',' => ' ', '-' => ' ', '_' => ' ', '/' => ' '
    ];
    $name = strtr($name, $replace);

    $name = preg_replace('/\s+/', ' ', $name);
    return trim($name);
}

function parse_incepe_text(?string $incepeText): array
{
    $incepeText = trim((string)$incepeText);

    if ($incepeText === '') {
        return ['data_meci' => null, 'ora_meci' => null];
    }

    // Exemple:
    // 13.03. 17:30
    // 13.03 17:30
    // 13.03.2026 17:30

    $data_meci = null;
    $ora_meci = null;

    if (preg_match('/(\d{2}\.\d{2}(?:\.\d{4})?)\s*\.?\s*(\d{2}:\d{2})/u', $incepeText, $m)) {
        $data_meci = $m[1];
        $ora_meci = $m[2];
    }

    return [
        'data_meci' => $data_meci,
        'ora_meci' => $ora_meci,
    ];
}

function get_existing_import_dates(mysqli $conn): array
{
    $dates = [];

    $sql = "SELECT data_import FROM import_zile ORDER BY data_import DESC";
    $res = $conn->query($sql);

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $dates[] = $row['data_import'];
        }
    }

    return $dates;
}

function find_or_create_import_zi(mysqli $conn, string $dataImport): int
{
    $sql = "SELECT id FROM import_zile WHERE data_import = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $dataImport);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row && isset($row['id'])) {
        return (int)$row['id'];
    }

    $sqlInsert = "INSERT INTO import_zile (data_import) VALUES (?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("s", $dataImport);
    $stmtInsert->execute();
    $newId = (int)$stmtInsert->insert_id;
    $stmtInsert->close();

    return $newId;
}
