<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_admin()) exit;

$q = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '';
$suggestions = [];

if ($q !== '') {
    $stmt = $conn->prepare("
        SELECT email, CONCAT(name, ' ', lastname, ' (', email, ')') AS label 
        FROM users 
        WHERE role != 'admin' 
          AND (email LIKE ? OR name LIKE ? OR lastname LIKE ?)
        LIMIT 5
    ");
    $stmt->bind_param("sss", $q, $q, $q);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $suggestions[] = [
            'label' => $row['label'],
            'value' => $row['email']  
        ];
    }
}

echo json_encode($suggestions);
