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

// ✅ Calculate total balance from transactions and update user's main_balance
$balanceQuery = $conn->prepare("
    SELECT SUM(CASE 
        WHEN type = 'nakazilo' THEN amount 
        WHEN type = 'dvig' THEN -amount
        WHEN type = 'prenos' THEN -amount 
        ELSE 0 
    END) AS calculated_balance
    FROM transactions
    WHERE user_id = ?
");
$balanceQuery->bind_param("i", $userId);
$balanceQuery->execute();
$balanceResult = $balanceQuery->get_result();
$balanceData = $balanceResult->fetch_assoc();
$calculatedBalance = $balanceData['calculated_balance'] ?? 0;

// Update user's main_balance with the calculated balance
$updateBalance = $conn->prepare("UPDATE users SET main_balance = ? WHERE id = ?");
$updateBalance->bind_param("di", $calculatedBalance, $userId);
$updateBalance->execute();
$updateBalance->close();

// ✅ Get current main_balance DIRECTLY from database
$stmt = $conn->prepare("SELECT main_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$main_balance = $userData['main_balance'];
$stmt->close();

// ✅ Monthly Income & Expenses Summary
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$monthlyStmt = $conn->prepare("
    SELECT
        SUM(CASE 
            WHEN type = 'nakazilo' AND (description IS NULL OR description NOT IN ('Zaključen cilj', 'Transfer from goal')) 
            THEN amount ELSE 0 END) AS nakazila,
        SUM(CASE 
            WHEN type = 'dvig' AND (description IS NULL OR description NOT IN ('Prenos v cilj', 'Transfer to savings')) 
            THEN amount ELSE 0 END) AS stroski,
        SUM(CASE 
            WHEN type = 'prenos' THEN amount ELSE 0 END) AS prenosi
    FROM transactions
    WHERE user_id = ? AND created_at BETWEEN ? AND ?
");

$monthlyStmt->bind_param("iss", $userId, $startOfMonth, $endOfMonth);
$monthlyStmt->execute();
$monthlyResult = $monthlyStmt->get_result();
$monthlyData = $monthlyResult->fetch_assoc();
$monthlyIncome = $monthlyData['nakazila'] ?? 0;
$monthlyExpenses = $monthlyData['stroski'] ?? 0;
$monthlyTransfers = $monthlyData['prenosi'] ?? 0;
$monthlyStmt->close();


// ✅ Cash Flow Data for Chart
$cashFlowData = ['months' => [], 'nakazila' => [], 'dvigi' => [], 'prenosi' => []];

$flowChart = $conn->prepare("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS month,
           SUM(CASE WHEN type = 'nakazilo' AND description != 'Zaključen cilj' THEN amount ELSE 0 END) AS nakazila,
           SUM(CASE WHEN type = 'dvig' THEN amount ELSE 0 END) AS dvigi,
           SUM(CASE WHEN type = 'prenos' THEN amount ELSE 0 END) AS prenosi
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
    $cashFlowData['prenosi'][] = (float)$row['prenosi'];
}
$flowChart->close();

// ✅ Savings Breakdown
$totalSavings = 0;
$savingsLabels = [];
$savingsValues = [];

// Check if savings_accounts table exists and has data
$savingsTableExistsQuery = $conn->query("SHOW TABLES LIKE 'savings_accounts'");
$savingsTableExists = $savingsTableExistsQuery->num_rows > 0;

if ($savingsTableExists) {
    $savingsQuery = $conn->prepare("SELECT name, balance FROM savings_accounts WHERE user_id = ?");
    $savingsQuery->bind_param("i", $userId);
    $savingsQuery->execute();
    $savingsResult = $savingsQuery->get_result();
    
    if ($savingsResult->num_rows > 0) {
        while ($s = $savingsResult->fetch_assoc()) {
            $savingsLabels[] = $s['name'];
            $savingsValues[] = (float)$s['balance'];
            $totalSavings += (float)$s['balance'];
        }
      } else {
        // No savings found for user — display nothing
        $savingsLabels[] = 'Ni ciljev';
        $savingsValues[] = 0;
    } }   

// ✅ Calculate wallet and savings distribution
$walletVsSavingsLabels = ['Glavni račun', 'Varčevanje'];
$totalNetWorth = $main_balance + $totalSavings;

// Make sure wallet amount is not negative for chart display
$walletAmount = max(0, floatval($main_balance));
$walletVsSavingsRaw = [$walletAmount, floatval($totalSavings)];

// ✅ Spending Breakdown
$spendingData = ['categories' => [], 'amounts' => []];

$spendingStmt = $conn->prepare("
    SELECT COALESCE(category, 'Drugo') as category, SUM(amount) AS total
    FROM transactions
    WHERE user_id = ?
      AND type = 'dvig'
      AND (description IS NULL OR description NOT IN ('Prenos v cilj', 'Transfer to savings'))
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY category
    ORDER BY total DESC
    LIMIT 10
");
$spendingStmt->bind_param("i", $userId);
$spendingStmt->execute();
$spendingResult = $spendingStmt->get_result();

if ($spendingResult->num_rows > 0) {
    while ($row = $spendingResult->fetch_assoc()) {
        $spendingData['categories'][] = $row['category'];
        $spendingData['amounts'][] = (float)$row['total'];
    }
} else {
    $spendingData['categories'][] = 'Ni podatkov o stroških';
    $spendingData['amounts'][] = 0;
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
        <p><?= number_format($totalNetWorth, 2) ?>€</p>
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
// Prepare the data for our external chart script
const chartData = {
  walletVsSavings: {
    labels: <?= json_encode($walletVsSavingsLabels) ?>,
    values: <?= json_encode($walletVsSavingsRaw) ?>
  },
  savingsBreakdown: {
    labels: <?= json_encode($savingsLabels) ?>,
    values: <?= json_encode($savingsValues) ?>
  },
  spending: {
    categories: <?= json_encode($spendingData['categories']) ?>,
    amounts: <?= json_encode($spendingData['amounts']) ?>
  },
  cashFlow: {
    months: <?= json_encode($cashFlowData['months']) ?>,
    income: <?= json_encode($cashFlowData['nakazila']) ?>,
    expenses: <?= json_encode($cashFlowData['dvigi']) ?>,
    transfers: <?= json_encode($cashFlowData['prenosi']) ?>
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
