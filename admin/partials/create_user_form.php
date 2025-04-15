<div class="card wide">
    <h3>➕ Ustvari novega uporabnika</h3>
    <form method="post">
        <input type="hidden" name="create_user" value="1">

        <label style="font-weight: bold; display: block;">Ime:</label>
        <input type="text" name="new_name" required>

        <label style="font-weight: bold; display: block;">Priimek:</label>
        <input type="text" name="new_lastname" required>

        <label style="font-weight: bold; display: block;">Email:</label>
        <input type="email" name="new_email" required>

        <label style="font-weight: bold; display: block;">Spol:</label>
        <div class="gender-group" style="text-align: center; margin-bottom: 15px;">
            <label><input type="radio" name="new_gender" value="male" required> Moški</label>
            <label style="margin-left: 15px;"><input type="radio" name="new_gender" value="female"> Ženska</label>
            <label style="margin-left: 15px;"><input type="radio" name="new_gender" value="other"> Drugo</label>
        </div>
        <br>
        <label style="font-weight: bold; display: block;">Geslo:</label>
        <input type="password" name="new_password" required>

        <label style="font-weight: bold; display: block;">"Vloga:</label>
        <select name="new_role" required>
            <option value="user">Uporabnik</option>
            <option value="admin">Admin</option>
        </select><br><br>

        <button type="submit">➕ Ustvari uporabnika</button>
    </form>
</div>
