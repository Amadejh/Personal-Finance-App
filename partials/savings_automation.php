<div class="card wide">
  <h3>📅 Samodejno varčevanje</h3>

  <form method="post">
    <input type="hidden" name="setup_automation">
    <input type="hidden" name="savings_account_id" value="<?= $account['id'] ?>">


    <label>Izberi račun:</label>
    <select name="savings_account_id" required>
      <option value="">-- izberi račun --</option>
      <?php
      $userId = $_SESSION['user']['id'];
      $result = $conn->query("SELECT id, name FROM savings_accounts WHERE user_id = $userId");
      while ($acc = $result->fetch_assoc()):
      ?>
        <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['name']) ?></option>
      <?php endwhile; ?>
    </select>

    <label>Trajanje (v mesecih):</label>
    <input type="number" name="duration_months" min="1" required>

    <button type="submit">⚙️ Nastavi avtomatski prenos</button>
  </form>
</div>
