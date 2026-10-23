<?php
$pageTitle = 'แดชบอร์ด';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
require __DIR__ . '/header.php';

$LOW_STOCK = 3;   // สต็อกเท่านี้หรือน้อยกว่า ถือว่าใกล้หมด
$count = fn($sql) => (int)$pdo->query($sql)->fetchColumn();

// งานที่ต้องลงมือทำวันนี้ (เรียงตามขั้นตอนของร้าน)
$tasks = [
    ['รอตรวจสอบการชำระเงิน', $count("SELECT COUNT(*) FROM orders WHERE status='Pending'"),    'orders.php?status=Pending',    '⏳'],
    ['ชำระแล้ว รอเตรียมสินค้า', $count("SELECT COUNT(*) FROM orders WHERE status='Paid'"),       'orders.php?status=Paid',       '🎀'],
    ['เตรียมแล้ว รอส่งพัสดุ',   $count("SELECT COUNT(*) FROM orders WHERE status='Processing'"), 'orders.php?status=Processing', '📦'],
    ['สินค้าใกล้หมด',          $count("SELECT COUNT(*) FROM products WHERE status='active' AND stock <= $LOW_STOCK"), 'products.php?low=1', '⚠️'],
];

$sales = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ('Paid','Processing','Shipped','Completed')")->fetchColumn();
$summary = [
    ['ยอดขายรวม',        money($sales),                                              'orders.php'],
    ['คำสั่งซื้อทั้งหมด', number_format($count("SELECT COUNT(*) FROM orders")),       'orders.php'],
    ['สินค้าทั้งหมด',     number_format($count("SELECT COUNT(*) FROM products")),     'products.php'],
    ['สมาชิก',           number_format($count("SELECT COUNT(*) FROM users")),        'customers.php'],
];
$recentOrders = $pdo->query("SELECT id, order_no, customer_name, total, status, created_at FROM orders ORDER BY id DESC LIMIT 8")->fetchAll();
?>
<section class="mb-5">
  <p class="font-num text-sm text-kawaii-ink/60"><?= date('d/m/Y') ?></p>
  <h2 class="mt-1 text-2xl font-bold">งานวันนี้</h2>
</section>

<section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
  <?php foreach ($tasks as [$label, $value, $href, $icon]): $hot = $value > 0; ?>
    <a href="<?= h($href) ?>" class="rounded-3xl border p-5 transition hover:-translate-y-0.5 <?= $hot ? 'border-kawaii-pink bg-white shadow-md shadow-kawaii-pink/20 ring-2 ring-kawaii-pink/15' : 'border-kawaii-pink-light bg-white/60' ?>">
      <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-semibold <?= $hot ? '' : 'text-kawaii-ink/50' ?>"><?= h($label) ?></p>
        <span class="text-xl <?= $hot ? '' : 'opacity-40' ?>"><?= $icon ?></span>
      </div>
      <?php if ($hot): ?>
        <p class="mt-3 font-num text-4xl font-bold tabular-nums text-kawaii-pink-deep"><?= number_format($value) ?></p>
        <p class="mt-2 text-xs font-semibold text-kawaii-pink-deep">ไปจัดการ →</p>
      <?php else: ?>
        <p class="mt-3 text-sm text-kawaii-ink/50">ไม่มีงานค้าง ✨</p>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</section>

<section class="mb-6 grid grid-cols-2 gap-px overflow-hidden rounded-3xl border border-kawaii-pink-light bg-kawaii-pink-light lg:grid-cols-4">
  <?php foreach ($summary as [$label, $value, $href]): ?>
    <a href="<?= h($href) ?>" class="bg-white px-5 py-4 transition hover:bg-kawaii-cream/60">
      <p class="text-xs text-kawaii-ink/60"><?= h($label) ?></p>
      <p class="mt-1 font-num text-xl font-bold tabular-nums"><?= h($value) ?></p>
    </a>
  <?php endforeach; ?>
</section>

<section class="mb-6 rounded-3xl border border-kawaii-pink-light bg-white p-5 shadow-sm sm:p-6">
  <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h2 class="text-lg font-bold">กราฟยอดขายรายเดือน</h2>
      <p id="salesPeriod" class="text-sm text-kawaii-ink/60">กำลังโหลดข้อมูล…</p>
    </div>
    <div class="flex items-center gap-2">
      <label for="monthFilter" class="text-xs font-semibold text-kawaii-ink/60">เลือกเดือน:</label>
      <input type="month" id="monthFilter" value="<?= date('Y-m') ?>" class="rounded-2xl border border-kawaii-pink-light bg-kawaii-cream/40 px-3 py-1.5 text-sm font-semibold outline-none focus:border-kawaii-pink">
    </div>
  </div>
  <div class="relative h-64">
    <canvas id="salesChart" aria-label="กราฟยอดขาย" role="img"></canvas>
  </div>
</section>

<section class="overflow-hidden rounded-3xl border border-kawaii-pink-light bg-white shadow-sm">
  <div class="flex flex-wrap items-center justify-between gap-3 border-b border-kawaii-pink-light px-5 py-4 sm:px-6">
    <div>
      <h2 class="text-lg font-bold">คำสั่งซื้อล่าสุด</h2>
      <p class="text-sm text-kawaii-ink/60">เข้ามาล่าสุด 8 รายการ</p>
    </div>
    <a href="orders.php" class="text-sm font-bold text-kawaii-pink-deep hover:underline">ดูคำสั่งซื้อทั้งหมด →</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[650px] text-left text-sm">
      <thead class="bg-kawaii-cream/70 text-kawaii-ink/70">
        <tr>
          <th class="px-5 py-3 font-semibold">เลขที่คำสั่งซื้อ</th>
          <th class="px-5 py-3 font-semibold">ลูกค้า</th>
          <th class="px-5 py-3 font-semibold">วันที่</th>
          <th class="px-5 py-3 text-right font-semibold">ยอดรวม</th>
          <th class="px-5 py-3 font-semibold">สถานะ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-kawaii-pink-light">
        <?php if (!$recentOrders): ?>
          <tr><td colspan="5" class="px-5 py-10 text-center text-kawaii-ink/60">ยังไม่มีคำสั่งซื้อ</td></tr>
        <?php else: foreach ($recentOrders as $order): ?>
          <tr class="hover:bg-kawaii-cream/40">
            <td class="px-5 py-4"><a class="font-num font-semibold tabular-nums text-kawaii-pink-deep hover:underline" href="order-detail.php?id=<?= (int)$order['id'] ?>"><?= h($order['order_no']) ?></a></td>
            <td class="px-5 py-4"><?= h($order['customer_name']) ?></td>
            <td class="px-5 py-4 whitespace-nowrap font-num tabular-nums"><?= h(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td>
            <td class="px-5 py-4 text-right whitespace-nowrap font-num font-semibold tabular-nums"><?= h(money($order['total'])) ?></td>
            <td class="px-5 py-4"><?= status_badge($order['status']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php
$extraScripts = '<script>
const salesCanvas = document.getElementById("salesChart");
const monthFilter = document.getElementById("monthFilter");
Chart.defaults.font.family = "Sarabun, Mali, sans-serif";

const salesChart = new Chart(salesCanvas, {
  type: "bar",
  data: {
    labels: [],
    datasets: [{
      label: "ยอดขาย (บาท)",
      data: [],
      backgroundColor: "rgba(255, 111, 165, 0.75)",
      hoverBackgroundColor: "#ff6fa5",
      borderRadius: 6
    }]
  },
  options: {
    maintainAspectRatio: false,
    plugins: { 
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => " ยอดขาย: ฿" + Number(ctx.parsed.y).toLocaleString(undefined, {minimumFractionDigits: 2})
        }
      }
    },
    scales: { 
      y: { 
        beginAtZero: true, 
        grid: { color: "rgba(255, 143, 184, 0.15)" },
        ticks: { callback: value => "฿" + Number(value).toLocaleString() } 
      },
      x: {
        grid: { display: false }
      }
    }
  }
});

async function refreshSalesChart() {
  if (document.hidden) return;
  const selectedMonth = monthFilter.value;
  try {
    const response = await fetch("sales-chart.php?month=" + encodeURIComponent(selectedMonth), { headers: { "Accept": "application/json" } });
    if (!response.ok) throw new Error("Sales chart request failed: " + response.status);
    const data = await response.json();
    if (!Array.isArray(data.labels) || !Array.isArray(data.values) || typeof data.period !== "string") {
      throw new Error("Sales chart response has an invalid format");
    }
    salesChart.data.labels = data.labels;
    salesChart.data.datasets[0].data = data.values;
    salesChart.update();
    document.getElementById("salesPeriod").textContent = data.period;
  } catch (error) {
    console.error("Unable to refresh dashboard sales chart.", error);
  }
}

monthFilter.addEventListener("change", refreshSalesChart);
refreshSalesChart();
window.setInterval(refreshSalesChart, 60000);
</script>';
require __DIR__ . '/footer.php';
