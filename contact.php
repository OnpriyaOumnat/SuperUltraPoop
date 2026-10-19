<?php $pageTitle = 'Contact'; require 'includes/header.php'; ?>
<div class="box"><h1>Contact Us</h1><p>โทร: <?= h(setting('contact_phone')) ?></p><p>อีเมล: <?= h(setting('contact_email')) ?></p><p>LINE: <?= h(setting('contact_line')) ?></p></div>
<?php require 'includes/footer.php'; ?>
