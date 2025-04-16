<?php
$host = '88.200.86.10';
$db   = '2024_tb_04';
$user = '2024_TB_04';
$pass = 'de7mg57it';
$charset = 'utf8mb4';


$conn = new mysqli($host, $user, $pass, $db);

//preveri povezavo
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Nastavi znakovno kodiranje
$conn->set_charset($charset);
