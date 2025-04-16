<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'partials/handle_forms.php';
redirect_if_not_logged_in();

$userId = $_SESSION['user']['id'];
$goalId = $_GET['id'] ?? null;

if (!$goalId) {
    header("Location: dashboard.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM savings_accounts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $goalId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$goal = $result->fetch_assoc();
$stmt->close();

if (!$goal) {
    echo "❌ Goal not found or access denied.";
    exit;
}

// redirect samo ce je cilj ze claimed
if ($goal['balance'] >= $goal['goal_amount']) {
  // ce ni ga oznaci kot complete in ugasni autosave
  if (!$goal['is_claimed']) {
      $stmt = $conn->prepare("UPDATE savings_accounts SET monthly_amount = 0 WHERE id = ?");
      $stmt->bind_param("i", $goalId);
      $stmt->execute();
      $stmt->close();
  }

  //redirect razen ce je claimable
  if (!isset($_GET['claim_view'])) {
      header("Location: dashboard.php");
      exit;
  }
}


$progress = $goal['goal_amount'] > 0 ? min($goal['goal_amount'], $goal['balance']) : 0;
?>
<!DOCTYPE html>
<html lang="sl">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($goal['name']) ?> - Goal</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="dashboard-page">

<!-- top navbar -->
<?php include 'partials/navbar.php'; ?>

<!-- main content -->

<div class="page-content">
<main class="goal-detail-page">
  <?php if (isset($_SESSION['popup'])): ?>
    <div id="popup" class="popup success"><?= $_SESSION['popup']; unset($_SESSION['popup']); ?></div>
  <?php endif; ?>

  <div class="card goal-detail">
    <h2>🎯 <?= htmlspecialchars($goal['name']) ?></h2>

    <p><strong>Napredek:</strong> <?= number_format($goal['balance'], 2) ?>€ of <?= number_format($goal['goal_amount'], 2) ?>€</p>
    <div class="progress-bar">
    <div class="fill" style="width: <?= min(100, ($goal['goal_amount'] > 0 ? ($goal['balance'] / $goal['goal_amount']) * 100 : 0)) ?>%"></div>

    </div>

    <p><strong>Autosave:</strong>
      <?php if ($goal['monthly_amount'] > 0): ?>
        <span class="badge badge-green">🟢 Nastavljeno</span>
        <br>
        <br><small>€<?= number_format($goal['monthly_amount'], 2) ?> / mesec &bull; <?= $goal['duration_months'] ?> mesecev</small>
      <?php else: ?>
        <span class="badge badge-red">🔴 Onemogočeno</span>
      <?php endif; ?>
    </p>

<!-- forms za dodajanje novih sredstev -->
<div class="flex-row" style="gap: 2rem; margin-top: 2rem;">
  <!-- form za rocni premik -->
  <div style="flex: 1;">
    <form method="post" class="goal-form">
      <h4>💸 Dodaj sredstva</h4>
      <input type="hidden" name="transfer_to_savings" value="1">
      <input type="hidden" name="savings_account" value="<?= $goal['id'] ?>">
      <input type="number" name="transfer_amount" min="0.01" step="0.01" placeholder="Znesek €" required>
      <button type="submit">💰 Dodaj</button>
    </form>
  </div>

  <!-- autosave-->
  <div style="flex: 1;">
    <form method="post" class="goal-form">
      <h4>⚙️ Auto Save</h4>
      <input type="hidden" name="setup_automation" value="1">
      <input type="hidden" name="savings_account_id" value="<?= $goal['id'] ?>">
      <input type="number" name="duration_months" min="1" max="120" placeholder="Mesecev" required>
      <button type="submit">💾 Shrani</button>
    </form>

    <?php if ($goal['monthly_amount'] > 0): ?>
      <!-- stop autosave -->
      <form method="post" style="margin-top: 1rem;">
        <input type="hidden" name="stop_automation" value="1">
        <input type="hidden" name="savings_account_id" value="<?= $goal['id'] ?>">
        <button type="submit" class="danger">⏹️ Ustavi avtomatsko varčevanje</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<hr>


    <!-- briši cilj -->
    <form method="post" action="dashboard.php" onsubmit="return confirm('Ali res želiš izbrisati ta cilj?');">
      <input type="hidden" name="delete_savings_account_id" value="<?= $goal['id'] ?>">
      <button type="submit" class="danger">🗑️ Izbriši cilj</button>
    </form>
  </div>
</main>

</div>
<script src="assets/js/ui.js"></script>


</body>
</html>