<div class="card wide">
    <h3>💸 Simuliraj transakcijo</h3>

    <form method="post">
        <input type="hidden" name="add_transaction" value="1">

        <label style="font-weight: bold; display: block;">Uporabnik:</label>
        <select name="user_id" required>
            <option value="">-- izberi uporabnika --</option>
            <?php
            $usersList = $conn->query("SELECT id, name, email FROM users WHERE role = 'user'");
            foreach ($usersList as $u): ?>
                <option value="<?= $u['id']; ?>"><?= htmlspecialchars($u['name']) . " (" . $u['email'] . ")"; ?></option>
            <?php endforeach; ?>
        </select><br><br>

        <label style="font-weight: bold; display: block;">Vrsta transakcije:</label>
        <select name="type" required>
            <option value="nakazilo">Nakazilo</option>
            <option value="dvig">Dvig</option>
            <option value="prenos">Prenos</option>
        </select><br><br>

        <label style="font-weight: bold; display: block;">Znesek (€):</label>
        <input type="number" name="amount" step="0.01" required><br><br>

        <label style="font-weight: bold; display: block;">Opis:</label>
        <input type="text" name="description" placeholder="Npr. simulacija plače"><br><br>

        <!-- ✅ NEW CATEGORY FIELD -->
        <label style="font-weight: bold; display: block;">Kategorija:</label>
        <select name="category" required>
            <option value="">-- izberi kategorijo --</option>
            <option value="Hrana in pijača">Hrana in pijača</option>
            <option value="Prevoz">Prevoz</option>
            <option value="Nakupljanje">Nakupi</option>
            <option value="Stanovanje in računi">Nastanitev in računi</option>
            <option value="Plača / Dohodek">Plača / Dohodek</option>
            <option value="Zabava in prosti čas">Zabava in prosti čas</option>
            <option value="Izobraževanje">Izobraževanje</option>
            <option value="Izobraževanje">Zdravje</option>
            <option value="Drugo">Drugo</option>
        </select><br><br>

        <button type="submit">✅ Dodaj transakcijo</button>
    </form>
</div>
