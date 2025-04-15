<?php if (!empty($_SESSION['popup'])): ?>
  <div id="popup" class="popup <?= $_SESSION['popup_type'] ?? 'success' ?>">
    <?= $_SESSION['popup'] ?>
  </div>
  <?php 
    unset($_SESSION['popup']); 
    unset($_SESSION['popup_type']); 
  ?>
<?php endif; ?>