<?php
if (!isset($_SESSION)) session_start();
$user = $_SESSION['user'] ?? null;
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="topnav">
  <div class="nav-left">
    <?php if ($user && $user['role'] === 'admin' && strpos($_SERVER['SCRIPT_NAME'], 'admin/') !== false): ?>
      <span class="logo">⚙️ Admin</span>
    <?php else: ?>
      <span class="logo"><span class="emoji">💰</span> MyWallet</span>
    <?php endif; ?>
  </div>

  <?php if ($user): ?>
  <div class="nav-right">
    <span class="welcome-msg">Dobrodošli, <?= htmlspecialchars($user['name']) ?>!</span>

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

    <a href="<?= strpos($_SERVER['SCRIPT_NAME'], 'admin/') !== false ? '../logout.php' : 'logout.php' ?>" class="nav-button logout">🚪 Izpis</a>
  </div>
  <?php endif; ?>
</div>

