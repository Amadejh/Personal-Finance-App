<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'partials/handle_forms.php';
redirect_if_not_logged_in();

$userId = $_SESSION['user']['id'];
$success = '';
$error = '';

// Pridobi podatke trenutnega uporabnika
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Obdelava obrazca za posodobitev profila
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['update_account'])) {
        // Uporabi transakcijo za zagotovitev konsistentnosti
        $conn->begin_transaction();
        try {
            // Pridobi in očisti vnosna polja
            $newName = trim($_POST['name']);
            $newLastname = trim($_POST['lastname']);
            $newGender = $_POST['gender'];
            $newEmail = trim($_POST['email']);
            $newPassword = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

            // Preveri, če email že obstaja pri drugem uporabniku
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->bind_param("si", $newEmail, $userId);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                throw new Exception("⚠️ Ta email je že v uporabi.");
            }

            // Različna SQL stavka glede na to, ali se posodablja geslo
            if ($newPassword) {
                $stmt = $conn->prepare("UPDATE users SET name=?, lastname=?, gender=?, email=?, password=? WHERE id=?");
                $stmt->bind_param("sssssi", $newName, $newLastname, $newGender, $newEmail, $newPassword, $userId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name=?, lastname=?, gender=?, email=? WHERE id=?");
                $stmt->bind_param("ssssi", $newName, $newLastname, $newGender, $newEmail, $userId);
            }

            if (!$stmt->execute()) {
                throw new Exception("❌ Napaka pri posodabljanju profila.");
            }

            // Dodatna logika za administratorje - dodajanje sredstev
            if (is_admin() && !empty($_POST['add_balance'])) {
              $addBalance = floatval(str_replace(',', '.', $_POST['add_balance']));
              
              if ($addBalance > 0) {
                  // Posodobi glavni račun
                  $stmt = $conn->prepare("UPDATE users SET main_balance = main_balance + ? WHERE id = ?");
                  $stmt->bind_param("di", $addBalance, $userId);
                  $stmt->execute();
                  $stmt->close();
          
                  // Zabeleži transakcijo v bazo
                  $type = 'nakazilo';
                  $category = 'Plača / Dohodek';
                  $description = 'Admin';
          
                  $logStmt = $conn->prepare("INSERT INTO transactions (user_id, type, category, amount, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                  $logStmt->bind_param("issds", $userId, $type, $category, $addBalance, $description);
                  $logStmt->execute();
                  $logStmt->close();
          
                  $_SESSION['popup'] = "✅ Dodano " . number_format($addBalance, 2) . "€ in zabeleženo kot transakcija.";
              }
          }
          
            // Zaključi transakcijo in posodobi podatke v seji
            $conn->commit();

            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $_SESSION['user'] = $stmt->get_result()->fetch_assoc();

            $_SESSION['popup'] = $_SESSION['popup'] ?? "✅ Spremembe uspešno shranjene.";
        } catch (Exception $e) {
            // V primeru napake razveljavi spremembe
            $conn->rollback();
            $_SESSION['popup'] = $e->getMessage();
        }

        header("Location: profile.php");
        exit;
    }

    // Brisanje uporabniškega računa
    if (isset($_POST['delete_account'])) {
        $conn->begin_transaction();
        try {
            // Najprej izbriši vse transakcije uporabnika
            $stmt = $conn->prepare("DELETE FROM transactions WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Nato izbriši uporabnika
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            $conn->commit();
            session_destroy();
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['popup'] = "❌ Napaka pri brisanju računa: " . $e->getMessage();
            header("Location: profile.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Moj profil</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="profile-page">

<?php include 'partials/navbar.php'; ?>

<div class="page-content">
<?php if (isset($_SESSION['popup'])): ?>
  <div id="popup" class="popup <?= $_SESSION['popup_type'] ?? 'success' ?>">
    <?= $_SESSION['popup']; unset($_SESSION['popup'], $_SESSION['popup_type']); ?>
  </div>
<?php endif; ?>

<div class="profile-panel">
  <h2 class="profile-title">👤 Moj profil</h2>

  <form method="post">
  <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <input type="hidden" name="update_account" value="1">

    <div class="form-row">
      <!-- Prvi stolpec: osnovni podatki -->
      <div class="profile-box">
        <label>Ime:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>

        <label>Priimek:</label>
        <input type="text" name="lastname" value="<?= htmlspecialchars($user['lastname']) ?>" required>
      </div>

      <!-- Drugi stolpec: kontaktni podatki in geslo -->
      <div class="profile-box">
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Novo geslo:</label>
        <input type="password" name="password" placeholder="Pusti prazno za obstoječe geslo">
      </div>
    </div>

    <div class="profile-box">
      <!-- Izbira spola -->
      <div class="gender-section">
        <div class="gender-label">
          <label>Spol:</label>
        </div>
        <div class="gender-group">
          <label><input type="radio" name="gender" value="male" <?= $user['gender'] === 'male' ? 'checked' : '' ?>> Moški</label>
          <label><input type="radio" name="gender" value="female" <?= $user['gender'] === 'female' ? 'checked' : '' ?>> Ženska</label>
          <label><input type="radio" name="gender" value="other" <?= $user['gender'] === 'other' ? 'checked' : '' ?>> Drugo</label>
        </div>
      </div>

      <!-- Admin: dodatna možnost za dodajanje sredstev -->
      <?php if (is_admin()): ?>
        <div class="profile-box">
          <label>💵 Dodaj sredstva v glavni račun:</label>
          <input type="number" name="add_balance" min="0" step="0.01" placeholder="Znesek v €">
        </div>
      <?php endif; ?>

      <!-- Gumbi za upravljanje profila -->
      <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 1.5rem;">
  <form method="post" style="margin: 0;">
  <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <input type="hidden" name="update_account" value="1">
    <button type="submit" class="btn-save">💾 Shrani spremembe</button>
  </form>

  <form method="post" onsubmit="return confirm('Ali si prepričan, da želiš izbrisati svoj račun? To dejanje je nepovratno.');" style="margin: 0;">
  <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
    <input type="hidden" name="delete_account" value="1">
    <button type="submit" class="btn-delete">🗑️ Izbriši račun</button>
  </form>
</div>

  </form>

</div>

<script src="assets/js/ui.js"></script>
</body>
</html>