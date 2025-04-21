<?php
if (!isset($_SESSION)) session_start();
$user = $_SESSION['user'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="topnav">
  <div class="nav-left">
    <?php if ($user && $user['role'] === 'admin' && strpos($_SERVER['SCRIPT_NAME'], 'admin/') !== false): ?>
      <!-- Prikaz admin ikone v navigaciji za admin panel -->
      <span class="logo">⚙️ Admin</span>
    <?php else: ?>
      <!-- Standardna navigacija za uporabnike -->
      <span class="logo"><span class="emoji">💰</span> MyWallet</span>
    <?php endif; ?>
  </div>

  <?php if ($user): ?>
  <div class="nav-right">
    <!-- Pozdravno sporočilo -->
    <span class="welcome-msg">Dobrodošli, <?= htmlspecialchars($user['name']) ?>!</span>

    <!-- Dinamični prikaz gumbov glede na trenutno stran in vlogo uporabnika -->
    <?php if ($user['role'] === 'admin' && strpos($_SERVER['SCRIPT_NAME'], 'admin/') === false): ?>
      <a href="admin/dashboard.php" class="nav-button">⚙️ Admin</a>
    <?php elseif ($user['role'] === 'admin' && strpos($_SERVER['SCRIPT_NAME'], 'admin/') !== false): ?>
      <a href="../dashboard.php" class="nav-button">📊 Nadzorna plošča</a>
    <?php endif; ?>

    <?php if ($currentPage !== 'dashboard.php'): ?>
      <a href="<?= strpos($_SERVER['SCRIPT_NAME'], 'admin/') !== false ? '../dashboard.php' : 'dashboard.php' ?>" class="nav-button">📊 Nadzorna plošča</a>
    <?php endif; ?>

    <?php if ($currentPage !== 'profile.php'): ?>
      <a href="<?= strpos($_SERVER['SCRIPT_NAME'], 'admin/') !== false ? '../profile.php' : 'profile.php' ?>" class="nav-button">👤 Profil</a>
    <?php endif; ?>

    <!-- Gumb za odjavo -->
    <a href="<?= strpos($_SERVER['SCRIPT_NAME'], 'admin/') !== false ? '../logout.php' : 'logout.php' ?>" class="nav-button logout">🚪 Izpis</a>
  </div>
  <?php endif; ?>
</div>