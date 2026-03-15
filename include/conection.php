<?php

$DB_HOST = "localhost";
$DB_USER = "";
$DB_PASS = "";
$DB_NAME = "manage2";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die("Eroare conexiune DB: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
