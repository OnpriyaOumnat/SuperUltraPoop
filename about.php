<?php $pageTitle = t('about_title'); require 'includes/header.php'; ?>
<div class="box"><h1><?= t('about_title') ?></h1><p><?= sprintf(t('about_desc'), h(setting('store_name'))) ?></p></div>
<?php require 'includes/footer.php'; ?>
