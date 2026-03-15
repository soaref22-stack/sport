<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

function find_match_by_id($conn, $match_id)
{
    if (!$match_id) return null;

    $sql = "SELECT * FROM meciuri_master WHERE match_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $match_id);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

function find_match_by_key($conn, $match_key)
{
    $sql = "SELECT * FROM meciuri_master WHERE match_key = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $match_key);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

function insert_match_master($conn, $data)
{
    $sql = "
    INSERT INTO meciuri_master
    (
        match_id,
        match_key,
        id_off,
        gazda,
        oaspeti,
        gazda_norm,
        oaspeti_norm,
        round_text,
        data_meci,
        ora_meci,
        cota_superbet_1,
        cota_superbet_x,
        cota_superbet_2
    )
    VALUES
    (?,?,?,?,?,?,?,?,?,?,?, ?,?)
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ssissssssddd",
        $data['match_id'],
        $data['match_key'],
        $data['id_off'],
        $data['gazda'],
        $data['oaspeti'],
        $data['gazda_norm'],
        $data['oaspeti_norm'],
        $data['round_text'],
        $data['data_meci'],
        $data['ora_meci'],
        $data['cota_1'],
        $data['cota_x'],
        $data['cota_2']
    );

    return $stmt->execute();
}

function update_match_master($conn, $id, $data)
{
    $sql = "
    UPDATE meciuri_master
    SET
        id_off = COALESCE(?, id_off),
        cota_superbet_1 = COALESCE(?, cota_superbet_1),
        cota_superbet_x = COALESCE(?, cota_superbet_x),
        cota_superbet_2 = COALESCE(?, cota_superbet_2)
    WHERE id = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "idddi",
        $data['id_off'],
        $data['cota_1'],
        $data['cota_x'],
        $data['cota_2'],
        $id
    );

    return $stmt->execute();
}

function save_or_update_match($conn, $data)
{
    $match = null;

    if (!empty($data['match_id'])) {
        $match = find_match_by_id($conn, $data['match_id']);
    }

    if (!$match) {
        $match = find_match_by_key($conn, $data['match_key']);
    }

    if ($match) {

        update_match_master($conn, $match['id'], $data);

        return [
            "status" => "updated",
            "id" => $match['id']
        ];
    }

    insert_match_master($conn, $data);

    return [
        "status" => "inserted"
    ];
}
