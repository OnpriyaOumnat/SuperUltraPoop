</main>
<footer class="footer">
  <div><b>🧸 <?= h(setting('store_name')) ?></b><br><?= h(setting('announcement')) ?></div>
  <div>โทร: <?= h(setting('contact_phone')) ?><br>อีเมล: <?= h(setting('contact_email')) ?><br>LINE: <?= h(setting('contact_line')) ?></div>
  <div><a href="products.php">สินค้าทั้งหมด</a><br><a href="track-order.php">ติดตามคำสั่งซื้อ</a><br><a href="admin/login.php">Admin</a></div>
</footer>
<script src="assets/js/script.js?v=<?= @filemtime(__DIR__ . '/../assets/js/script.js') ?: 1 ?>"></script>
</body></html>
