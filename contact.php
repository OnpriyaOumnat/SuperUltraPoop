<?php $pageTitle = t('contact'); require 'includes/header.php'; ?>
<div class="box"><h1><?= t('contact_title') ?></h1><p><?= t('phone_label') ?>: <?= h(setting('contact_phone')) ?></p><p><?= t('email_label') ?>: <?= h(setting('contact_email')) ?></p><p>LINE: <?= h(setting('contact_line')) ?></p></div>
<?php require 'includes/footer.php'; ?>
