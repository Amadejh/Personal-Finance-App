<form method="POST">
  <label for="goal_name">Ime cilja</label>
  <input type="text" name="goal_name" id="goal_name" required>

  <label for="goal_amount">Ciljna vsota (€)</label>
  <input type="number" name="goal_amount" id="goal_amount" step="0.01" required>

  <button type="submit" name="create_savings_account">Ustvari</button>
</form>
