<?php
require_once __DIR__ . '/functions.php';
$q = $_GET['q'] ?? '';
$currentPage = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php');
$activeNav = [
    'index.php' => 'home',
    'products.php' => 'products',
    'product-detail.php' => 'products',
    'about.php' => 'about',
    'contact.php' => 'contact',
    'track-order.php' => 'track-order',
][$currentPage] ?? '';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'Home') ?> | <?= h(setting('store_name', 'SuperUltraPoop')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Mali:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../assets/css/style.css') ?: 1 ?>">
</head><body>
<?php $squishDemoPath = __DIR__ . '/../assets/images/SuperUltraPoop – Squish Demo.html'; ?>
<?php if (is_file($squishDemoPath)): ?>
<iframe class="site-background-demo" src="assets/images/SuperUltraPoop%20%E2%80%93%20Squish%20Demo.html?v=<?= @filemtime($squishDemoPath) ?: 1 ?>" title="" aria-hidden="true" tabindex="-1"></iframe>
<?php endif; ?>
<div class="topbar"><?= h(setting('announcement')) ?></div>
<header class="nav">
  <a class="logo" href="index.php">🧸 <?= h(setting('store_name', 'SuperUltraPoop')) ?></a>
  <nav>
    <a href="index.php" class="<?= $activeNav === 'home' ? 'active' : '' ?>" <?= $activeNav === 'home' ? 'aria-current="page"' : '' ?>><?= t('home') ?></a>
    <a id="productsNav" href="products.php" class="<?= $activeNav === 'products' ? 'active' : '' ?>" <?= $activeNav === 'products' ? 'aria-current="page"' : '' ?>><?= t('products') ?></a>
    <a href="about.php" class="<?= $activeNav === 'about' ? 'active' : '' ?>" <?= $activeNav === 'about' ? 'aria-current="page"' : '' ?>><?= t('about_us') ?></a>
    <a href="contact.php" class="<?= $activeNav === 'contact' ? 'active' : '' ?>" <?= $activeNav === 'contact' ? 'aria-current="page"' : '' ?>><?= t('contact') ?></a>
    <a href="track-order.php" class="<?= $activeNav === 'track-order' ? 'active' : '' ?>" <?= $activeNav === 'track-order' ? 'aria-current="page"' : '' ?>><?= t('track_order') ?></a>
  </nav>
  <form class="search" action="products.php"><input name="q" value="<?= h($q) ?>" placeholder="<?= t('search_placeholder') ?>"><button><i class="fa fa-search"></i></button></form>
  <div class="icons">
    <a href="<?= h(lang_url($lang === 'th' ? 'en' : 'th')) ?>" title="<?= $lang === 'th' ? 'Switch to English' : 'เปลี่ยนเป็นภาษาไทย' ?>" class="lang-toggle"><?= $lang === 'th' ? '🇬🇧 EN' : '🇹🇭 TH' ?></a>
    <a href="cart.php" title="<?= t('cart') ?>"><i class="fa fa-cart-shopping"></i><span class="count"><?= cart_count() ?></span></a>
    <?php if (!empty($_SESSION['user_id'])): ?>
      <a href="account.php" title="<?= t('account') ?>"><i class="fa fa-user"></i></a><a href="logout.php" title="<?= t('logout') ?>"><i class="fa fa-right-from-bracket"></i></a>
    <?php else: ?><a href="login.php" title="<?= t('login') ?>"><i class="fa fa-user"></i></a><?php endif; ?>
  </div>
</header>
<main class="wrap">
<?php if ($m = flash()): ?><div class="alert"><?= h($m) ?></div><?php endif; ?>
