<?php

$host = 'localhost';
$user = 'micro_user';
$pass = 'micro_pass123';
$db   = 'micro_store';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
