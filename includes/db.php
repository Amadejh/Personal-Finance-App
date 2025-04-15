<?php
$host = '88.200.86.10';
$db   = '2024_tb_04';
$user = '2024_TB_04';
$pass = 'de7mg57it';
$charset = 'utf8mb4';

// Using mysqli instead of PDO to match the rest of the application
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset($charset);
