<?php
require_once __DIR__ . '/../includes/db.php';
$userId = $_SESSION['user']['id'];

// Get main balance
$stmt = $conn->prepare("SELECT main_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($main_balance);
$stmt->fetch();
$stmt->close();

// Fetch all savings accounts
$savings = [];
$savingsStmt = $conn->prepare("SELECT id, name, balance, goal_amount FROM savings_accounts WHERE user_id = ?");
$savingsStmt->bind_param("i", $userId);
$savingsStmt->execute();
$savings = $savingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$savingsStmt->close();
?>

<div class="card wide">
    <h3>💳 Moji Računi</h3>
    <p><strong>Glavni račun:</strong> €<?= number_format($main_balance, 2) ?></p>

    <form method="post" class="flex-row">
        <input type="hidden" name="transfer_to_savings">
        <label>Znesek:</label>
        <input type="number" name="transfer_amount" step="0.01" min="0" required>

        <label>Varčevalni račun:</label>
        <select name="target_savings_id" required>
            <option value="">-- Izberi račun --</option>
            <?php foreach ($savings as $acc): ?>
                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">💸 Prenesi</button>
    </form>

    <hr>

    <h3>📈 Varčevalni račun:</h3>
    <form method="post">
        <input type="hidden" name="create_savings_account" value="1">
        <input type="text" name="goal_name" placeholder="Ime cilja (npr. Dopust)" required>
        <input type="number" name="goal_amount" step="0.01" placeholder="Ciljna vsota (€)" required>
        <button type="submit">➕ Ustvari</button>
    </form>

    <hr>

    <h4>📦 Varčevalni računi</h4>
    <?php foreach ($savings as $acc): ?>
        <div class="card light">
            <p><strong><?= htmlspecialchars($acc['name']) ?>:</strong> €<?= number_format($acc['balance'], 2) ?></p>
            <progress value="<?= $acc['balance'] ?>" max="<?= $acc['goal_amount'] ?>"></progress>
            <span>
                <?= $acc['goal_amount'] > 0
                    ? round($acc['balance'] / $acc['goal_amount'] * 100, 1)
                    : 0 ?>% od €<?= number_format($acc['goal_amount'], 2) ?>
            </span>

            <!-- Edit -->
            <form method="post" style="margin-top: 10px;">
                <input type="hidden" name="edit_savings_account_id" value="<?= $acc['id'] ?>">
                <input type="text" name="new_goal_name" value="<?= htmlspecialchars($acc['name']) ?>" required>
                <input type="number" name="new_goal_amount" step="0.01" value="<?= $acc['goal_amount'] ?>" required>
                <button type="submit">✏️ Posodobi</button>
            </form>

            <!-- Delete -->
            <form method="post" onsubmit="return confirm('Ali res želiš izbrisati ta račun?');" style="margin-top: 5px;">
                <input type="hidden" name="delete_savings_account_id" value="<?= $acc['id'] ?>">
                <button type="submit" class="danger">🗑️ Izbriši</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
