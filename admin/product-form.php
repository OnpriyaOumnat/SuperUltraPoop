<?php
require_once __DIR__ . '/../includes/functions.php';
admin_required();

$id = (int)($_GET['id'] ?? 0);
$product = [
    'name' => '',
    'name_th' => '',
    'description' => '',
    'price' => '',
    'stock' => 0,
    'category_id' => 0,
    'scent' => '',
    'color' => '',
    'is_new' => 0,
    'is_best' => 0,
    'status' => 'active',
    'image' => '',
];
if ($id > 0) {
    $findProduct = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $findProduct->execute([$id]);
    $product = $findProduct->fetch();
    if (!$product) {
        http_response_code(404);
        exit('ไม่พบสินค้า');
    }
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
$categoryIds = array_map('intval', array_column($categories, 'id'));
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'name_th' => trim($_POST['name_th'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT),
        'stock' => filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT),
        'category_id' => (int)($_POST['category_id'] ?? 0),
        'scent' => trim($_POST['scent'] ?? ''),
        'color' => trim($_POST['color'] ?? ''),
        'is_new' => isset($_POST['is_new']) ? 1 : 0,
        'is_best' => isset($_POST['is_best']) ? 1 : 0,
        'status' => ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'active',
        'image' => $product['image'],
    ];

    if ($data['name'] === '') {
        $errors[] = 'กรอกชื่อสินค้า';
    }
    if ($data['price'] === false || $data['price'] <= 0) {
        $errors[] = 'ราคาต้องเป็นตัวเลขที่มากกว่า 0';
    }
    if ($data['stock'] === false || $data['stock'] < 0) {
        $errors[] = 'สต็อกต้องเป็นจำนวนเต็มตั้งแต่ 0 ขึ้นไป';
    }
    if (!in_array($data['category_id'], $categoryIds, true)) {
        $errors[] = 'เลือกหมวดหมู่สินค้าที่ถูกต้อง';
    }

    if (!$errors && isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'อัปโหลดรูปไม่สำเร็จ กรุณาลองอีกครั้ง';
        } else {
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['image']['tmp_name']);
            if (!isset($allowedTypes[$mime])) {
                $errors[] = 'รองรับเฉพาะไฟล์ JPG, PNG และ WEBP';
            } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'ไฟล์รูปต้องมีขนาดไม่เกิน 2MB';
            } else {
                $uploadDirectory = __DIR__ . '/../uploads/products';
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    $errors[] = 'ไม่สามารถเตรียมโฟลเดอร์สำหรับบันทึกรูปได้';
                } else {
                    $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDirectory . '/' . $filename)) {
                        $data['image'] = 'uploads/products/' . $filename;
                    } else {
                        $errors[] = 'บันทึกรูปสินค้าไม่สำเร็จ';
                    }
                }
            }
        }
    }

    if (!$errors) {
        if ($id > 0) {
            $data['id'] = $id;
            $save = $pdo->prepare('UPDATE products SET category_id=:category_id,name=:name,name_th=:name_th,description=:description,price=:price,stock=:stock,image=:image,scent=:scent,color=:color,is_new=:is_new,is_best=:is_best,status=:status WHERE id=:id');
            $save->execute($data);
            flash('แก้ไขสินค้าแล้ว');
        } else {
            $save = $pdo->prepare('INSERT INTO products (category_id,name,name_th,description,price,stock,image,scent,color,is_new,is_best,status) VALUES (:category_id,:name,:name_th,:description,:price,:stock,:image,:scent,:color,:is_new,:is_best,:status)');
            $save->execute($data);
            flash('เพิ่มสินค้าแล้ว');
        }
        redirect('products.php');
    }
    $product = array_merge($product, $data);
}

$pageTitle = $id > 0 ? 'แก้ไขสินค้า' : 'เพิ่มสินค้า';
require __DIR__ . '/header.php';
?>
<section class="mx-auto max-w-3xl">
  <div class="mb-5">
    <a href="products.php" class="text-sm font-semibold text-kawaii-pink-deep hover:underline">← กลับไปหน้าสินค้าทั้งหมด</a>
    <h2 class="mt-2 text-2xl font-bold"><?= $id > 0 ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' ?></h2>
  </div>
  <?php foreach ($errors as $error): ?>
    <p class="mb-3 rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700" role="alert"><?= h($error) ?></p>
  <?php endforeach; ?>
  <form method="post" enctype="multipart/form-data" class="space-y-5 rounded-3xl border border-kawaii-pink-light bg-white p-5 shadow-sm sm:p-7">
    <?= csrf_field() ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="name" class="mb-1 block text-sm font-semibold">ชื่อสินค้า *</label>
        <input id="name" name="name" required value="<?= h($product['name']) ?>" class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink">
      </div>
      <div>
        <label for="name_th" class="mb-1 block text-sm font-semibold">ชื่อภาษาไทย</label>
        <input id="name_th" name="name_th" value="<?= h($product['name_th']) ?>" class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink">
      </div>
      <div>
        <label for="category_id" class="mb-1 block text-sm font-semibold">หมวดหมู่ *</label>
        <select id="category_id" name="category_id" required class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink">
          <option value="">เลือกหมวดหมู่</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= (int)$category['id'] ?>" <?= (int)$product['category_id'] === (int)$category['id'] ? 'selected' : '' ?>><?= h($category['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="price" class="mb-1 block text-sm font-semibold">ราคา (บาท) *</label>
        <input id="price" type="number" step="0.01" min="0.01" name="price" required value="<?= h($product['price']) ?>" class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink">
      </div>
      <div>
        <label for="stock" class="mb-1 block text-sm font-semibold">สต็อก *</label>
        <input id="stock" type="number" min="0" step="1" name="stock" required value="<?= h($product['stock']) ?>" class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink">
      </div>
      <div>
        <label for="status" class="mb-1 block text-sm font-semibold">สถานะ</label>
        <select id="status" name="status" class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink">
          <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
      <div>
        <label for="scent" class="mb-1 block text-sm font-semibold">กลิ่น</label>
        <input id="scent" name="scent" value="<?= h($product['scent']) ?>" class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink">
      </div>
      <div>
        <label for="color" class="mb-1 block text-sm font-semibold">สี / ตัวเลือก</label>
        <input id="color" name="color" value="<?= h($product['color']) ?>" placeholder="คั่นแต่ละตัวเลือกด้วย , เช่น Pom,Nuts" class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink">
      </div>
    </div>
    <div>
      <label for="description" class="mb-1 block text-sm font-semibold">รายละเอียดสินค้า</label>
      <textarea id="description" name="description" rows="4" class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3 outline-none focus:border-kawaii-pink"><?= h($product['description']) ?></textarea>
    </div>
    <div>
      <label for="image" class="mb-1 block text-sm font-semibold">รูปสินค้า (JPG, PNG หรือ WEBP ไม่เกิน 2MB)</label>
      <?php if ($product['image'] !== ''): ?>
        <img src="../<?= h(img($product)) ?>" alt="<?= h($product['name']) ?>" class="mb-3 h-24 w-24 rounded-2xl object-cover">
      <?php endif; ?>
      <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="w-full rounded-2xl border border-kawaii-pink-light px-4 py-3">
    </div>
    <div class="flex flex-wrap gap-x-6 gap-y-3">
      <label class="inline-flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_new" value="1" <?= $product['is_new'] ? 'checked' : '' ?> class="h-4 w-4 accent-pink-500"> New Arrival</label>
      <label class="inline-flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_best" value="1" <?= $product['is_best'] ? 'checked' : '' ?> class="h-4 w-4 accent-pink-500"> Best Seller</label>
    </div>
    <div class="flex flex-wrap gap-3 border-t border-kawaii-pink-light pt-5">
      <button class="rounded-2xl bg-kawaii-pink px-6 py-3 font-bold text-white shadow-md shadow-kawaii-pink/30 transition hover:bg-kawaii-pink-deep">บันทึกสินค้า</button>
      <a href="products.php" class="rounded-2xl border border-kawaii-pink-light px-6 py-3 font-semibold hover:bg-kawaii-cream">ยกเลิก</a>
    </div>
  </form>
</section>
<?php require __DIR__ . '/footer.php'; ?>
