<?php
declare(strict_types=1);

function normalize_team_name($team)
{
    $team = strtolower(trim($team));

    $replace = [
        ' fotbal' => '',
        ' football' => '',
        ' fc' => '',
        '.' => '',
        ',' => '',
        '-' => ' ',
        '_' => ' '
    ];

    $team = strtr($team, $replace);

    return trim($team);
}

function build_match_key($data, $ora, $gazda, $oaspeti)
{
    $gazda = normalize_team_name($gazda);
    $oaspeti = normalize_team_name($oaspeti);

    return $data . '|' . $ora . '|' . $gazda . '|' . $oaspeti;
}

function get_categorie_cota($cota)
{
    if ($cota <= 1.40) return 'sport1';
    if ($cota <= 1.50) return 'sport2';
    if ($cota <= 1.60) return 'sport3';
    if ($cota <= 1.70) return 'sport4';
    if ($cota <= 1.80) return 'sport5';
    if ($cota <= 1.90) return 'sport6';
    if ($cota <= 2.00) return 'sport7';
    if ($cota <= 2.10) return 'sport8';
    if ($cota <= 2.20) return 'sport9';
    if ($cota <= 2.30) return 'sport10';
    if ($cota <= 2.40) return 'sport11';

    return 'sport12';
}
