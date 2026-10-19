<?php
// admin/includes/header.php
// Layout หลังบ้าน: <head> + Tailwind CDN + Sidebar ซ้าย + Top navbar (โปรไฟล์แอดมิน)
// วิธีใช้ในแต่ละหน้า:  $pageTitle = 'Products'; require 'includes/header.php'; ... require 'includes/footer.php';
require_once __DIR__ . '/../includes/functions.php';
admin_required();   // ยังไม่ล็อกอิน → เด้งไป login.php

$cur       = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'Dashboard';

// ชื่อแอดมินสำหรับแสดงที่มุมขวาบน
$stAdmin = $pdo->prepare("SELECT username FROM admins WHERE id = ?");
$stAdmin->execute([$_SESSION['admin_id']]);
$adminName = $stAdmin->fetchColumn() ?: 'admin';

// เมนู: [ลิงก์, ชื่อ, ไอคอน, ไฟล์ที่ถือว่า active]
$navItems = [
    ['index.php',      'แดชบอร์ด',  '🏠', ['index.php']],
    ['products.php',   'สินค้า',      '🧸', ['products.php', 'product-form.php']],
    ['orders.php',     'คำสั่งซื้อ',   '🧾', ['orders.php', 'order-detail.php']],
    ['customers.php',  'สมาชิก',     '👥', ['customers.php', 'member-detail.php']],
    ['create-admin.php', 'จัดการแอดมิน', '🔐', ['create-admin.php']],
];

// ป้ายสถานะคำสั่งซื้อ (ใช้ซ้ำในหลายหน้า)
if (!function_exists('status_badge')) {
    function status_badge($status) {
        $map = [
            'Pending'    => 'bg-amber-100 text-amber-700',
            'Paid'       => 'bg-emerald-100 text-emerald-700',
            'Processing' => 'bg-sky-100 text-sky-700',
            'Shipped'    => 'bg-violet-100 text-violet-700',
            'Completed'  => 'bg-green-100 text-green-700',
            'Cancelled'  => 'bg-rose-100 text-rose-700',
        ];
        $c = $map[$status] ?? 'bg-gray-100 text-gray-600';
        return '<span title="' . h(order_status_description($status)) . '" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ' . $c . '">' . h(order_status_label($status)) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?> | SuperUltraPoop Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Mali:wght@400;600;700&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // ธีม Kawaii Pastel
    tailwind.config = {
      theme: { extend: {
        colors: { kawaii: {
          pink: '#ff8fb8', 'pink-deep': '#ff6fa5', 'pink-light': '#ffe4ef',
          blue: '#cfe8ff', mint: '#c9f2e1', lilac: '#e3d9ff',
          cream: '#fff8ec', ink: '#6b4c5a'
        } },
        fontFamily: { sans: ['Mali', 'Tahoma', 'sans-serif'], num: ['Sarabun', 'Tahoma', 'sans-serif'] }
      } }
    };
  </script>
  <style>
    /* พิมพ์ใบแปะกล่อง: พิมพ์เฉพาะการ์ดที่อยู่จัดส่ง (#ship-label) */
    @media print {
      body * { visibility: hidden; }
      #ship-label, #ship-label * { visibility: visible; }
      #ship-label { position: absolute; left: 0; top: 0; width: 100%; border: 2px dashed #999; box-shadow: none; }
    }
  </style>
  <?= $extraHead ?? '' /* หน้าไหนต้องการ <script>/<style> เพิ่ม (เช่น Chart.js) ให้ตั้งตัวแปร $extraHead */ ?>
</head>
<body class="min-h-screen bg-kawaii-cream font-sans text-kawaii-ink">

  <!-- พื้นหลังมืดตอนเปิดเมนูบนมือถือ -->
  <div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-black/30 lg:hidden"></div>

  <!-- ===== Sidebar ===== -->
  <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-white shadow-xl transition-transform duration-200 lg:translate-x-0">
    <div class="flex items-center gap-3 border-b border-kawaii-pink-light px-5 py-5">
      <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-kawaii-pink-light text-2xl">🧸</span>
      <div class="leading-tight">
        <div class="text-lg font-bold text-kawaii-pink-deep">SuperUltraPoop</div>
        <div class="text-xs text-kawaii-ink/60">Admin Panel</div>
      </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto p-4">
      <?php foreach ($navItems as [$href, $label, $icon, $files]):
        $active = in_array($cur, $files, true); ?>
        <a href="<?= h($href) ?>"
           class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition
                  <?= $active ? 'bg-kawaii-pink text-white shadow-md shadow-kawaii-pink/40' : 'text-kawaii-ink hover:bg-kawaii-pink-light' ?>">
          <span class="text-lg"><?= $icon ?></span><?= h($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="space-y-1 border-t border-kawaii-pink-light p-4 text-sm">
      <a href="../index.php" target="_blank" class="flex items-center gap-3 rounded-2xl px-4 py-2.5 hover:bg-kawaii-blue/60">🛍️ ดูหน้าร้าน</a>
      <a href="logout.php" class="flex items-center gap-3 rounded-2xl px-4 py-2.5 text-rose-500 hover:bg-rose-50">🚪 ออกจากระบบ</a>
    </div>
  </aside>

  <!-- ===== พื้นที่เนื้อหา ===== -->
  <div class="flex min-h-screen flex-col lg:pl-64">

    <!-- Top navbar -->
    <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-kawaii-pink-light bg-white/85 px-4 py-3 backdrop-blur sm:px-6">
      <div class="flex items-center gap-3">
        <button id="sidebarToggle" type="button" aria-label="เปิดเมนู"
                class="rounded-xl bg-kawaii-pink-light px-3 py-2 text-lg lg:hidden">☰</button>
        <h1 class="text-xl font-bold sm:text-2xl"><?= h($pageTitle) ?></h1>
      </div>

      <div class="flex items-center gap-2 rounded-full bg-kawaii-mint/60 py-1.5 pl-1.5 pr-4">
        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-kawaii-pink text-sm font-bold uppercase text-white">
            <?= h(mb_substr($adminName, 0, 1)) ?>
        </span>
        <span class="hidden text-sm font-semibold sm:inline"><?= h($adminName) ?></span>
      </div>
    </header>

    <!-- เนื้อหาของแต่ละหน้า -->
    <main class="flex-1 p-4 sm:p-6">
      <?php if ($flashMsg = flash()): ?>
        <div class="mb-5 rounded-2xl bg-kawaii-blue/70 px-5 py-3 text-sm font-semibold"><?= h($flashMsg) ?></div>
      <?php endif; ?>
