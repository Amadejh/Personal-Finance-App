<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
  echo json_encode([]); exit;
}

$userId = $_SESSION['user']['id'];
$q = $_GET['q'] ?? '';
$q = '%' . $q . '%';

$stmt = $conn->prepare("
  SELECT DISTINCT description 
  FROM transactions 
  WHERE user_id = ? AND description LIKE ? 
  ORDER BY created_at DESC 
  LIMIT 10
");

$stmt->bind_param("is", $userId, $q);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
  if (!empty($row['description'])) {
    $suggestions[] = $row['description'];
  }
}

echo json_encode($suggestions);
