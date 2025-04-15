<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'partials/handle_forms.php';
require_once 'includes/savings_automation.php';

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

// Get current user data
$userId = $_SESSION['user']['id'];
run_savings_automation($userId);

// ✅ Get current main_balance DIRECTLY from database (DO NOT RECALCULATE)
$stmt = $conn->prepare("SELECT main_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($main_balance);
$stmt->fetch();
$stmt->close();

// ✅ Calculate transaction-based balance FOR DISPLAY/ANALYTICS ONLY
$transactionBalance = 0;
$transactions = $conn->prepare("SELECT type, amount FROM transactions WHERE user_id = ? ORDER BY created_at ASC");
$transactions->bind_param("i", $userId);
$transactions->execute();
$res = $transactions->get_result();
while ($r = $res->fetch_assoc()) {
    $transactionBalance += $r['type'] === 'nakazilo' ? $r['amount'] : -$r['amount'];
}
$transactions->close();

// ✅ Monthly Income & Expenses Summary
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$monthlyStmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN type = 'nakazilo' THEN amount ELSE 0 END) AS income,
        SUM(CASE WHEN type = 'dvig' THEN amount ELSE 0 END) AS expenses
    FROM transactions
    WHERE user_id = ? AND created_at BETWEEN ? AND ?
");
$monthlyStmt->bind_param("iss", $userId, $startOfMonth, $endOfMonth);
$monthlyStmt->execute();
$monthlyStmt->bind_result($monthlyIncome, $monthlyExpenses);
$monthlyStmt->fetch();
$monthlyStmt->close();

// ✅ Cash Flow Data for Chart
$cashFlowData = ['months' => [], 'nakazila' => [], 'dvigi' => []];

$flowChart = $conn->prepare("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS month,
           SUM(CASE WHEN type = 'nakazilo' THEN amount ELSE 0 END) AS nakazila,
           SUM(CASE WHEN type = 'dvig' THEN amount ELSE 0 END) AS dvigi
    FROM transactions
    WHERE user_id = ?
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY YEAR(created_at), MONTH(created_at)
");
$flowChart->bind_param("i", $userId);
$flowChart->execute();
$res = $flowChart->get_result();

while ($row = $res->fetch_assoc()) {
    $cashFlowData['months'][] = $row['month'];
    $cashFlowData['nakazila'][] = (float)$row['nakazila'];
    $cashFlowData['dvigi'][] = (float)$row['dvigi'];
}
$flowChart->close();

// ✅ Savings Breakdown
$totalSavings = 0;
$savingsLabels = [];
$savingsValues = [];
$savingsQuery = $conn->prepare("SELECT name, balance FROM savings_accounts WHERE user_id = ?");
$savingsQuery->bind_param("i", $userId);
$savingsQuery->execute();
$savingsResult = $savingsQuery->get_result();
while ($s = $savingsResult->fetch_assoc()) {
    $savingsLabels[] = $s['name'];
    $savingsValues[] = (float)$s['balance'];
    $totalSavings += (float)$s['balance'];
}
$savingsQuery->close();

$walletVsSavingsLabels = ['Main Wallet', 'Savings'];
$totalNetWorth = $main_balance + $totalSavings;

$walletPercent = $totalNetWorth > 0 ? ($main_balance / $totalNetWorth) * 100 : 0;
$savingsPercent = $totalNetWorth > 0 ? ($totalSavings / $totalNetWorth) * 100 : 0;

$adjustedWallet = max(0, floatval($main_balance) - floatval($totalSavings));
$walletVsSavingsRaw = [$adjustedWallet, floatval($totalSavings)];

// ✅ Spending Breakdown
$spendingData = ['categories' => [], 'amounts' => []];

$spendingStmt = $conn->prepare("
    SELECT category, SUM(amount) AS total
    FROM transactions
    WHERE user_id = ? AND type = 'dvig'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY category
    ORDER BY total DESC
    LIMIT 10
");
$spendingStmt->bind_param("i", $userId);
$spendingStmt->execute();
$spendingResult = $spendingStmt->get_result();

while ($row = $spendingResult->fetch_assoc()) {
    $spendingData['categories'][] = $row['category'];
    $spendingData['amounts'][] = (float)$row['total'];
}
$spendingStmt->close();
?>

<!DOCTYPE html>
<html lang="sl">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="dashboard-page">

<?php include 'partials/navbar.php'; ?>

<div class="page-content">
<?php if (isset($_SESSION['popup'])): ?>
  <div id="popup" class="popup success">
    <?= $_SESSION['popup']; unset($_SESSION['popup']); ?>
  </div>
<?php endif; ?>

<main class="analytics-dashboard">

<div class="dashboard-grid">
  <div class="left-column">
    <div class="summary-cards">
      <div class="card">
        <h3>Skupno dobroimetje</h3>
        <p><?= number_format($main_balance, 2) ?>€</p>
      </div>
      <div class="card">
        <h3>Mesečni prihodki</h3>
        <p><?= number_format($monthlyIncome ?? 0, 2) ?>€</p>
      </div>
      <div class="card">
        <h3>Mesečni stroški</h3>
        <p><?= number_format($monthlyExpenses ?? 0, 2) ?>€</p>
      </div>
    </div>

    <div class="donut-charts">
      <div class="card chart-card">
        <h3>🏦 Razporeditev dobroimetja </h3>
        <div id="walletVsSavingsChart"></div>
      </div>
      <div class="card chart-card">
        <h3>🎯 Razporeditev ciljev</h3>
        <div id="savingsBreakdownChart"></div>
      </div>
    </div>
  </div>

  <div class="right-column">
    <div class="card chart-card tall">
      <div style="display: flex; justify-content: space-between; align-items: center;">
      <h3 style="text-align: center; margin: 0 auto; width: fit-content;">
        🎯 Analiza stroškov <span style="font-weight: normal; font-size: 0.9rem;">(zadnjih 6 mesecev)</span>
      </h3>

      </div>
      <div id="spendingChart" class="chart-container" style="margin-top: 1rem;"></div>
    </div>
  </div>
</div>

<div class="flex_row">
  <?php include 'dashboard_transactions.php'; ?>
</div>

<div class="flex-row">
  <div class="card">
    <h3>➕ Ustvari nov Cilj</h3>
    <?php include 'partials/create_goal_form.php'; ?>
    <hr>
    <div class="card">
      <h3>💸 Ročni prenos</h3>
      <?php include 'partials/manual_transfer.php'; ?>
    </div>
  </div>

  <div class="card" style="flex: 1; min-width: 0;">
    <h3>🎯 Trenutni cilji</h3>
    <?php include 'partials/goals_overview_grid_paginated.php'; ?>
  </div>
</div>

</main>
</div>

<script>
const chartData = {
  walletVsSavings: {
    labels: <?= json_encode($walletVsSavingsLabels ?? []) ?>,
    values: <?= json_encode($walletVsSavingsRaw ?? []) ?>
  },
  savingsBreakdown: {
    labels: <?= json_encode($savingsLabels ?? []) ?>,
    values: <?= json_encode($savingsValues ?? []) ?>
  },
  spending: {
    categories: <?= json_encode($spendingData['categories'] ?? []) ?>,
    amounts: <?= json_encode($spendingData['amounts'] ?? []) ?>
  }
};
</script>

<script src="assets/js/apex-charts.js"></script>
<script src="assets/js/ui.js"></script>

<style>
.transaction-balance {
  color: #666;
  font-size: 0.8rem;
  display: block;
  margin-top: 0.25rem;
}
</style>

</body>
</html> 