<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Preveri, če je uporabnik admin - ščiti dostop do API-ja
if (!is_admin()) exit;

// Pridobi iskalni niz za predloge
$q = isset($_GET['q']) ? '%' . $_GET['q'] . '%' : '';
$suggestions = [];

if ($q !== '') {
    // Poišči predloge za uporabnike, ki ustrezajo iskalnemu nizu
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

    // Pripravi predloge v formatu za JSON
    while ($row = $res->fetch_assoc()) {
        $suggestions[] = [
            'label' => $row['label'],
            'value' => $row['email']  
        ];
    }
}

// Vrni predloge v JSON formatu
echo json_encode($suggestions);