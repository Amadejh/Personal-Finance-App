<form method="POST">
  <input type="hidden" name="transfer_to_savings" value="1">

  <label for="transfer_amount">Znesek:</label>
  <input type="number" name="transfer_amount" step="0.01" placeholder="Vnesi znesek (€)" required>

  <label for="savings_account">Izberi varčevalni račun:</label>
  <select name="savings_account" required>
    <option value="">-- izberi račun --</option>
    <?php
    $stmt = $conn->prepare("SELECT id, name FROM savings_accounts WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()):
    ?>
      <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
    <?php endwhile; $stmt->close(); ?>
  </select>

  <button type="submit">💾 Prenesi</button>
</form>
