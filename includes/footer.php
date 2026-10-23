</main>
<footer class="footer">
  <div><b>🧸 <?= h(setting('store_name')) ?></b><br><?= h(setting('announcement')) ?></div>
  <div><?= t('phone_label') ?>: <?= h(setting('contact_phone')) ?><br><?= t('email_label') ?>: <?= h(setting('contact_email')) ?><br>LINE: <?= h(setting('contact_line')) ?></div>
  <div><a href="products.php"><?= t('all_products') ?></a><br><a href="track-order.php"><?= t('track_order') ?></a><br><a href="admin/login.php">Admin</a></div>
</footer>
<script src="assets/js/script.js?v=<?= @filemtime(__DIR__ . '/../assets/js/script.js') ?: 1 ?>"></script>
</body></html>
