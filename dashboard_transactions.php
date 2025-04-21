<!-- transakcije na dashboardu -->

  <div class="flex-row">
    <!-- ➕ Nova transakcija -->
    <div class="card" style="flex: 1; margin-right: 1rem;">
      <h3>➕ Nova transakcija</h3>
      <form method="post">
      <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
        <input type="hidden" name="new_transaction" value="1">

        <label>Vrsta transakcije:</label>
        <select name="type" required>
          <option value="nakazilo">Nakazilo</option>
          <option value="dvig">Dvig</option>
          <option value="prenos">Prenos</option>
        </select>

        <label>Kategorija:</label>
        <select name="category" required>
            <option value="">-- izberi kategorijo --</option>
            <option value="Hrana in pijača">Hrana in pijača</option>
            <option value="Prevoz">Prevoz</option>
            <option value="Nakupi">Nakupi</option>
            <option value="Nastanitv in računi">Nastanitv in računi</option>
            <option value="Plača / Dohodek">Plača / Dohodek</option>
            <option value="Zabava in prosti čas">Zabava in prosti čas</option>
            <option value="Izobraževanje">Izobraževanje</option>
            <option value="Zdravje">Zdravje</option>
            <option value="Drugo">Drugo</option>
        </select>

        <label>Znesek (€):</label>
        <input type="number" name="amount" step="0.01" required>

        <label>Opis:</label>
        <input type="text" name="description" placeholder="Npr. malica, kino, račun...">

        <button type="submit" class="btn">✅ Dodaj transakcijo</button>
      </form>
    </div>

    <!-- 📋 Zadnje transakcije -->
<div class="card" style="flex: 1; min-width: 0;">
<h3 style="display: flex; justify-content: space-between; align-items: center;">
  📋 Zadnje transakcije
  <a href="all_transactions.php" style="font-size: 0.8rem; color: #90caf9; text-decoration: underline;">Pokaži vse</a>
</h3>

  <ul style="line-height: 1.6;">
    <?php
    $recent = $conn->prepare("SELECT type, category, amount, description, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $recent->bind_param("i", $userId);
    $recent->execute();
    $res = $recent->get_result();
    while ($row = $res->fetch_assoc()):    
      // za prikaz da prvo črko veliko tiskano
      $displayType = ucfirst($row['type']);
    ?>
      <li>
        <strong><?= $displayType ?>:</strong> 
        <?= number_format($row['amount'], 2) ?>€ – 
        <?= htmlspecialchars($row['category']) ?> 
        (<?= htmlspecialchars($row['description']) ?>)<br>
        <small style="color: #999;">📅 <?= date("d.m.Y H:i", strtotime($row['created_at'])) ?></small>
      </li>
    <?php endwhile; $recent->close(); ?>
  </ul>
</div>

  </div>
</content>