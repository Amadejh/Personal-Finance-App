<?php
require_once 'includes/db.php';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Ta email je že registriran.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, lastname, email, gender, password, role) VALUES (?, ?, ?, ?, ?, 'user')");
        $stmt->bind_param("sssss", $name, $lastname, $email, $gender, $password);

        if ($stmt->execute()) {
            $success = "Registracija uspešna! Zdaj se lahko prijaviš.";
        } else {
            $error = "Napaka pri registraciji.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Registracija</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
<div class="form-container">
    <h2>Registracija</h2>

    <?php if (!empty($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php elseif (!empty($success)): ?>
        <p class="success"><?= $success ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="name" placeholder="Ime" required>
        <input type="text" name="lastname" placeholder="Priimek" required>
        <input type="email" name="email" placeholder="Email" required>

        <label class="gender-title">Spol:</label>
        <div class="gender-group">
            <label><input type="radio" name="gender" value="male" required> Moški</label>
            <label><input type="radio" name="gender" value="female"> Ženska</label>
            <label><input type="radio" name="gender" value="other"> Drugo</label>
        </div>

        <input type="password" name="password" placeholder="Geslo" required>
        <button type="submit">Registriraj se</button>
    </form>

    <p>Že imaš račun? <a href="index.php">Prijavi se</a></p>
</div>
</body>
</html>
