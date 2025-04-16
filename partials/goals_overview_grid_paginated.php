<?php
// paginacija setup
$goalsPerPage = 6;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $goalsPerPage;

$stmt = $conn->prepare("
    SELECT id, name, balance, goal_amount, monthly_amount
    FROM savings_accounts
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $userId, $goalsPerPage, $offset);
$stmt->execute();
$goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// prešteje cilje za paginacijo
$countStmt = $conn->prepare("SELECT COUNT(*) FROM savings_accounts WHERE user_id = ?");
$countStmt->bind_param("i", $userId);
$countStmt->execute();
$countStmt->bind_result($totalGoals);
$countStmt->fetch();
$countStmt->close();

$totalPages = ceil($totalGoals / $goalsPerPage);
?>

<div class="goals-grid">
  <?php foreach ($goals as $goal): ?>
    <?php
      $isCompleted = $goal['goal_amount'] > 0 && $goal['balance'] >= $goal['goal_amount'];
    ?>

    <?php if ($isCompleted): ?>
      <div class="goal-card completed-goal">
        <h4><?= htmlspecialchars($goal['name']) ?></h4>
        <p style="text-align:center; font-size: 1.2rem;">🎉 Cilj dosežen</p>
        <form method="post" onsubmit="return confirm('Ali res želiš izplačati prihranke?');" style="text-align:center;">
          <input type="hidden" name="claim_goal_id" value="<?= $goal['id'] ?>">
          <button type="submit" class="claim-button">izplačaj prihranke</button>
          <p><?= number_format($goal['balance'], 2) ?>€</p>

        </form>
      </div>
    <?php else: ?>
      <a href="specific_goal.php?id=<?= $goal['id'] ?>" class="goal-card">
        <h4><?= htmlspecialchars($goal['name']) ?></h4>
        <p><strong>Autosave:</strong>
        <br>
        <br>
          <span class="badge <?= $goal['monthly_amount'] > 0 ? 'badge-green' : 'badge-red' ?>">
            <?= $goal['monthly_amount'] > 0 ? '🟢 Nastavljeno' : '🔴 Onemogočeno' ?>
          </span>
        </p>
        <progress value="<?= $goal['balance'] ?>" max="<?= $goal['goal_amount'] ?>"></progress>
        <p><?= number_format($goal['balance'], 2) ?>€ od <?= number_format($goal['goal_amount'], 2) ?>€</p>
      </a>
    <?php endif; ?>
  <?php endforeach; ?>
      </div>

<!-- strani -->
<?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

