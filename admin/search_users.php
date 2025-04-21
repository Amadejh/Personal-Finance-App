<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (!is_admin()) exit;

$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : '%';
$limit = 5;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;


// Podatki
$stmt = $conn->prepare("SELECT * FROM users WHERE role != 'admin' AND email LIKE ? LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $search, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Skupno število
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role != 'admin' AND email LIKE ?");
$countStmt->bind_param("s", $search);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);

while ($row = $result->fetch_assoc()):
?>
<div class="user-card">
<form method="post" class="edit-form" style="margin-bottom: 20px;">
<input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <input type="hidden" name="edit_user_id" value="<?= $row['id']; ?>">

    <label>Ime:</label>
    <input type="text" name="edit_name" value="<?= htmlspecialchars($row['name']); ?>" required>

    <label>Priimek:</label>
    <input type="text" name="edit_lastname" value="<?= htmlspecialchars($row['lastname']); ?>" required>

    <label>Email:</label>
    <input type="email" name="edit_email" value="<?= htmlspecialchars($row['email']); ?>" required>

    <label>Novo geslo:</label>
    <input type="password" name="edit_password" placeholder="Pusti prazno za obstoječe geslo">

    <label>Vloga:</label>
    <select name="edit_role" required>
        <option value="user" <?= $row['role'] === 'user' ? 'selected' : ''; ?>>Uporabnik</option>
        <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
    </select>

    <div class="button-group">
    <button type="submit" name="update_user">💾 Shrani spremembe</button>
    <button type="submit" name="delete_user" onclick="return confirm('Ali si prepričan, da želiš izbrisati tega uporabnika?')" style="background-color: #c0392b; color: white;">🗑️ Izbriši uporabnika</button>
    </div>
</form>
<hr>

</div>
<?php endwhile; ?>


