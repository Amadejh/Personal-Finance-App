<?php
session_start();
require_once "includes/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["email"], $_POST["password"])) {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION["user"] = $user;
            header("Location: " . ($user["role"] === "admin"
                ? "/personal-banking-app/admin/dashboard.php"
                : "/personal-banking-app/dashboard.php"));
            exit;
        } else {
            $error = "Napačen email ali geslo.";
        }
    } else {
        $error = "Napačen email ali geslo.";
    }
}
?>


<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Prijava</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">

<pre>
<?/*php
// 🔍 Debug incoming form data
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    var_dump($_POST);
}*/
?>
</pre>

<div class="form-container">
    <h2>Prijava</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="post">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Geslo" required><br>
        <button type="submit">Prijavi se</button>
    </form>
    <p>Še nimaš računa? <a href="register.php">Registriraj se</a></p>
</div>
</body>
</html>
