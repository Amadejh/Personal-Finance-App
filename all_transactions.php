<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user'])) {
  header("Location: index.php");
  exit();
}

$userId = $_SESSION['user']['id'];
$search = $_GET['search'] ?? '';

// pridobi transakcije
$stmt = $conn->prepare("
  SELECT type, category, amount, description, created_at
  FROM transactions
  WHERE user_id = ? AND (created_at LIKE CONCAT('%', ?, '%') OR description LIKE CONCAT('%', ?, '%'))
  ORDER BY created_at DESC
");
$stmt->bind_param("iss", $userId, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

// skupaj zapravljeno in prihodek
$summaryStmt = $conn->prepare("
  SELECT 
    SUM(CASE WHEN type = 'dvig' THEN amount ELSE 0 END) AS total_spent,
    SUM(CASE WHEN type = 'nakazilo' THEN amount ELSE 0 END) AS total_income
  FROM transactions
  WHERE user_id = ?
");
$summaryStmt->bind_param("i", $userId);
$summaryStmt->execute();
$summaryResult = $summaryStmt->get_result()->fetch_assoc();
$totalSpent = $summaryResult['total_spent'] ?? 0;
$totalIncome = $summaryResult['total_income'] ?? 0;

// zadnjih 7 dni 
$chartStmt = $conn->prepare("
  SELECT category, SUM(amount) as total
  FROM transactions
  WHERE user_id = ? 
    AND type = 'dvig'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  GROUP BY category
");
$chartStmt->bind_param("i", $userId);
$chartStmt->execute();
$chartResult = $chartStmt->get_result();

$chartData = [];
while ($row = $chartResult->fetch_assoc()) {
  $chartData[] = [
    'x' => $row['category'],
    'y' => floatval($row['total']),
  ];
}

// Fallback če ni podatkov za graf
if (empty($chartData)) {
  $chartData = [['x' => 'Ni stroškov', 'y' => 1]];
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
  <meta charset="UTF-8">
  <title>Vse transakcije</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="dashboard-page">

<?php include 'partials/navbar.php'; ?>

<div class="page-content">

<main class="transactions-page">
  <div class="transactions-centered">

    <div class="card full-width transactions-card">
      <div class="transactions-header">
        <h2>📋 Vse transakcije</h2>
        <div class="summary-box">
          <p><strong>Vse transakcije:</strong> <?= $result->num_rows ?></p>
          <p><strong>Skupaj porabljeno:</strong> <?= number_format($totalSpent, 2) ?>€</p>
          <p><strong>Skupaj prejeto:</strong> <?= number_format($totalIncome, 2) ?>€</p>
        </div>
      </div>

      <form method="GET" class="form-container" style="position: relative;">
        <input 
          type="text" 
          name="search" 
          id="search-input" 
          class="search-input" 
          placeholder="Išči po opisu..." 
          value="<?= htmlspecialchars($search) ?>" 
          autocomplete="off"
        >
        <ul id="suggestion-list" class="transaction-suggestions animated-dropdown"></ul>
        <button type="submit" class="btn">🔍 Išči</button>
      </form>

      <ul style="line-height: 1.8; margin-top: 1rem;">
        <?php while ($row = $result->fetch_assoc()): ?>
          <li>
            <strong><?= ucfirst($row['type']) ?>:</strong>
            <?= number_format($row['amount'], 2) ?>€ –
            <?= htmlspecialchars($row['category']) ?>
            (<?= htmlspecialchars($row['description']) ?>)<br>
            <small style="color: var(--text-muted);">
              📅 <?= date("d.m.Y H:i", strtotime($row['created_at'])) ?>
            </small>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>

  </div>
</main>


</div>

<!-- ✅ Scripts -->
<script src="assets/js/apex-charts.js"></script>
<script src="assets/js/ui.js"></script>
</body>
</html>
