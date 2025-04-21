<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once __DIR__ . '/partials/handle_forms.php';

// Preveri, če je uporabnik admin - če ni, ga preusmeri
if (!is_admin()) {
    header("Location: ../index.php");
    exit;
}

$success = '';
$error = '';
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="admin-page">

<!-- 🔝 Navigacijska vrstica -->
<?php include '../partials/navbar.php'; ?>

<!-- ✅ Popup okno za obvestila o uspehu/napaki -->
<?php include 'partials/popup.php'; ?>

<div class="page-content">
<div class="admin-container">

  <!-- Zgornja vrstica z obrazci za ustvarjanje uporabnikov in simulacijo transakcij -->
  <div class="admin-top-row">
    <div class="admin-card">
      <?php include 'partials/create_user_form.php'; ?>
    </div>

    <div class="admin-card">
      <?php include 'partials/simulate_transaction.php'; ?>
    </div>
  </div>

  <!-- Seznam uporabnikov z AJAX iskanjem -->
  <div class="admin-section">
    <?php include 'partials/user_list_ajax.php'; ?>
  </div>

</div>
</div>

<!-- ✅ JavaScript funkcije za admin panel -->
<script src="../assets/js/admin.js"></script>

</body>
</html>