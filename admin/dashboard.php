<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once __DIR__ . '/partials/handle_forms.php';


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

<!-- 🔝 NAVIGACIJA -->
<?php include '../partials/navbar.php'; ?>


<!-- ✅ POPUP za uspeh/error -->
<?php include 'partials/popup.php'; ?>


<div class="page-content">
<div class="admin-container">

  <div class="admin-top-row">
    <div class="admin-card">
      <?php include 'partials/create_user_form.php'; ?>
    </div>

    <div class="admin-card">
      <?php include 'partials/simulate_transaction.php'; ?>
    </div>
  </div>

  <div class="admin-section">
    <?php include 'partials/user_list_ajax.php'; ?>
  </div>

</div>
</div>

<!-- ✅ Skripti -->
<script src="../assets/js/admin.js"></script>

</body>
</html>
