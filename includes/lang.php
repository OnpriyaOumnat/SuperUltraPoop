<?php
// ========== ระบบเปลี่ยนภาษา (Language Switcher) ==========
// รองรับ: ไทย (th) และ อังกฤษ (en)
// เปลี่ยนภาษาโดยใส่ ?lang=th หรือ ?lang=en ใน URL

if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'th';

$T = [
  'th' => [
    // ----- Navigation -----
    'home'            => 'หน้าแรก',
    'products'        => 'สินค้า',
    'about_us'        => 'เกี่ยวกับเรา',
    'contact'         => 'ติดต่อเรา',
    'track_order'     => 'ติดตามคำสั่งซื้อ',
    'search_placeholder' => 'ค้นหาสินค้า...',
    'cart'            => 'ตะกร้า',
    'account'         => 'บัญชี',
    'login'           => 'เข้าสู่ระบบ',
    'logout'          => 'ออกจากระบบ',
    'register'        => 'สมัครสมาชิก',

    // ----- Home -----
    'new_arrivals'    => 'สินค้ามาใหม่',
    'best_sellers'    => 'สินค้าขายดี',

    // ----- Products -----
    'select_category' => 'เลือกหมวดหมู่สินค้า',
    'all_products'    => 'สินค้าทั้งหมด',
    'sort_best'       => 'สินค้าขายดี',
    'sort_new'        => 'สินค้ามาใหม่',
    'sort_price_asc'  => 'ราคา: ต่ำ → สูง',
    'sort_price_desc' => 'ราคา: สูง → ต่ำ',
    'search_btn'      => 'ค้นหา',
    'showing_items'   => 'แสดง %d รายการ',
    'no_products'     => 'ไม่พบสินค้า 🥲',

    // ----- Product Card / Detail -----
    'view_details'    => 'ดูรายละเอียด',
    'add_to_cart'     => 'เพิ่มลงตะกร้า',
    'buy_now'         => 'ซื้อเลย',
    'sold_count'      => 'ขายแล้ว %s ชิ้น',
    'in_stock'        => 'คงเหลือ %d ชิ้น',
    'out_of_stock'    => 'สินค้าหมด',
    'sold_out'        => 'Sold Out',
    'new_arrival_badge' => 'New Arrival',
    'best_seller_badge' => 'Best Seller',
    'scent'           => 'กลิ่น',
    'color'           => 'สี',
    'select_variant'  => 'เลือกสี / ตัวเลือก',
    'related_products'=> 'สินค้าที่เกี่ยวข้อง',
    'back_to_products'=> '← กลับไปหน้าสินค้าทั้งหมด',

    // ----- Cart -----
    'cart_title'      => 'ตะกร้าสินค้า',
    'cart_empty'      => 'ตะกร้าว่าง',
    'browse_products' => 'เลือกซื้อสินค้า',
    'product'         => 'สินค้า',
    'price'           => 'ราคา',
    'quantity'        => 'จำนวน',
    'total'           => 'รวม',
    'delete'          => 'ลบ',
    'update'          => 'อัปเดต',
    'delete_confirm'  => 'ลบสินค้านี้?',
    'items_total'     => 'จำนวนรวม %d ชิ้น',
    'subtotal'        => 'ยอดรวม',
    'continue_shopping' => 'เลือกสินค้าต่อ',
    'checkout'        => 'Checkout',

    // ----- Checkout -----
    'checkout_title'  => 'สั่งซื้อสินค้า',
    'name'            => 'ชื่อ-นามสกุล',
    'phone'           => 'เบอร์โทรศัพท์',
    'address'         => 'ที่อยู่จัดส่ง',
    'address_hint'    => 'บ้านเลขที่ หมู่ ซอย ถนน',
    'address_example' => 'เช่น 99/9 หมู่ 5 ซอยสุขใจ ถนนพหลโยธิน',
    'province'        => 'จังหวัด',
    'district'        => 'อำเภอ/เขต',
    'subdistrict'     => 'ตำบล/แขวง',
    'postcode'        => 'รหัสไปรษณีย์',
    'loading_list'    => 'กำลังโหลดรายการ…',
    'select_province' => '— เลือกจังหวัดก่อน —',
    'select_district' => '— เลือกอำเภอก่อน —',
    'payment_method'  => 'วิธีชำระเงิน',
    'transfer'        => 'โอนเงิน',
    'cod'             => 'เก็บเงินปลายทาง',
    'confirm_order'   => 'ยืนยันคำสั่งซื้อ',
    'order_summary'   => 'สรุปรายการ',
    'product_total'   => 'ยอดสินค้า',
    'shipping_fee'    => 'ค่าจัดส่ง',
    'grand_total'     => 'รวมทั้งสิ้น',
    'payment_note'    => '* การโอนเงินต้องแจ้งยอดและเลขอ้างอิง แล้วรอร้านตรวจสอบก่อนยืนยันคำสั่งซื้อ',

    // ----- Login / Register -----
    'login_title'     => 'เข้าสู่ระบบ',
    'email'           => 'อีเมล',
    'password'        => 'รหัสผ่าน',
    'login_error'     => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง',
    'register_title'  => 'สมัครสมาชิก',
    'register_name'   => 'ชื่อ',
    'register_phone'  => 'เบอร์โทร',
    'register_pw_hint'=> 'รหัสผ่าน (8 ตัวขึ้นไป)',

    // ----- Account -----
    'my_account'      => 'บัญชีของฉัน',
    'order_history'   => 'ประวัติการสั่งซื้อ',
    'order_no'        => 'เลขที่',
    'order_date'      => 'วันที่',
    'status'          => 'สถานะ',

    // ----- Track Order -----
    'track_title'     => 'ติดตามคำสั่งซื้อ',
    'track_placeholder'=> 'เลขที่คำสั่งซื้อ เช่น SUP260101ABCD',
    'order_not_found' => 'ไม่พบคำสั่งซื้อนี้',
    'order_number'    => 'เลขที่',
    'order_date_label'=> 'วันที่',
    'order_status'    => 'สถานะคำสั่งซื้อ',
    'tracking_no'     => 'เลขพัสดุ',

    // ----- Order Success -----
    'order_success'   => '🎉 สั่งซื้อสำเร็จ',
    'order_no_label'  => 'เลขที่คำสั่งซื้อ',
    'keep_order_no'   => 'เก็บไว้ใช้ติดตามสถานะ',
    'payment_cod'     => 'เก็บเงินปลายทาง',
    'payment_transfer'=> 'โอนเงิน (ยืนยันแล้ว)',
    'payment_label'   => 'ชำระเงิน',

    // ----- Transfer Payment -----
    'pay_qr_title'    => 'ชำระเงินด้วย QR',
    'amount_to_pay'   => 'ยอดที่ต้องโอน',
    'qr_note'         => 'กรุณาตรวจสอบชื่อผู้รับและยอดเงินในแอปธนาคารก่อนยืนยันการโอน',
    'transfer_info'   => 'แจ้งข้อมูลการโอน',
    'transfer_cancelled'=> 'คำสั่งซื้อนี้ถูกยกเลิกหรือการชำระเงินไม่สำเร็จ กรุณาติดต่อร้าน',
    'transfer_submitted'=> 'แจ้งโอนแล้วเมื่อ %s — สถานะ: รอตรวจสอบจากร้าน',
    'transfer_amount_label' => 'ยอดที่แจ้งโอน',
    'view_slip'       => 'เปิดดูสลิป',
    'auto_check_note' => 'หน้านี้จะตรวจสอบสถานะให้อัตโนมัติ และไปหน้าสั่งซื้อสำเร็จเมื่อร้านยืนยันการรับเงิน',
    'after_transfer'  => 'หลังโอนเงินแล้ว กรอกยอดและแนบสลิปเพื่อให้ร้านตรวจสอบรายการได้',
    'transfer_amount' => 'ยอดเงินที่โอน (บาท)',
    'attach_slip'     => 'แนบสลิปโอนเงิน (JPG, PNG หรือ WEBP ไม่เกิน 2MB)',
    'submit_transfer' => 'แจ้งโอนเงิน',

    // ----- About / Contact -----
    'about_title'     => 'เกี่ยวกับเรา',
    'about_desc'      => '%s ร้านสกุชชี่นุ่มฟู บีบแล้วคืนตัวช้า ทั้งสกุชชี่ขนมปังเบเกอรี่และน้องสัตว์เทวดาสุดน่ารัก 🧸',
    'contact_title'   => 'ติดต่อเรา',
    'phone_label'     => 'โทร',
    'email_label'     => 'อีเมล',

    // ----- Footer -----
    'all_products'    => 'สินค้าทั้งหมด',

    // ----- Cart flash messages -----
    'added_to_cart'   => 'เพิ่มลงตะกร้าแล้ว 🧸',
    'stock_exceed'    => 'จำนวนสินค้าเกินสต็อก ปรับเป็น %d ชิ้น',
    'product_not_found'=> 'ไม่พบสินค้า',
    'cart_empty_msg'  => 'ตะกร้าว่าง',
  ],

  'en' => [
    // ----- Navigation -----
    'home'            => 'Home',
    'products'        => 'Products',
    'about_us'        => 'About Us',
    'contact'         => 'Contact',
    'track_order'     => 'Track Order',
    'search_placeholder' => 'Search products...',
    'cart'            => 'Cart',
    'account'         => 'Account',
    'login'           => 'Login',
    'logout'          => 'Logout',
    'register'        => 'Register',

    // ----- Home -----
    'new_arrivals'    => 'New Arrivals',
    'best_sellers'    => 'Best Sellers',

    // ----- Products -----
    'select_category' => 'Select Category',
    'all_products'    => 'All Products',
    'sort_best'       => 'Best Selling',
    'sort_new'        => 'New Arrivals',
    'sort_price_asc'  => 'Price: Low → High',
    'sort_price_desc' => 'Price: High → Low',
    'search_btn'      => 'Search',
    'showing_items'   => 'Showing %d items',
    'no_products'     => 'No products found 🥲',

    // ----- Product Card / Detail -----
    'view_details'    => 'View Details',
    'add_to_cart'     => 'Add to Cart',
    'buy_now'         => 'Buy Now',
    'sold_count'      => 'Sold %s pcs',
    'in_stock'        => '%d left in stock',
    'out_of_stock'    => 'Out of Stock',
    'sold_out'        => 'Sold Out',
    'new_arrival_badge' => 'New Arrival',
    'best_seller_badge' => 'Best Seller',
    'scent'           => 'Scent',
    'color'           => 'Color',
    'select_variant'  => 'Select Color / Variant',
    'related_products'=> 'Related Products',
    'back_to_products'=> '← Back to All Products',

    // ----- Cart -----
    'cart_title'      => 'Shopping Cart',
    'cart_empty'      => 'Your cart is empty',
    'browse_products' => 'Browse Products',
    'product'         => 'Product',
    'price'           => 'Price',
    'quantity'        => 'Qty',
    'total'           => 'Total',
    'delete'          => 'Remove',
    'update'          => 'Update',
    'delete_confirm'  => 'Remove this item?',
    'items_total'     => '%d items total',
    'subtotal'        => 'Subtotal',
    'continue_shopping' => 'Continue Shopping',
    'checkout'        => 'Checkout',

    // ----- Checkout -----
    'checkout_title'  => 'Place Order',
    'name'            => 'Full Name',
    'phone'           => 'Phone Number',
    'address'         => 'Shipping Address',
    'address_hint'    => 'House number, village, street',
    'address_example' => 'e.g. 99/9 Moo 5 Soi Sukjai, Phaholyothin Rd.',
    'province'        => 'Province',
    'district'        => 'District',
    'subdistrict'     => 'Sub-district',
    'postcode'        => 'Postal Code',
    'loading_list'    => 'Loading…',
    'select_province' => '— Select province first —',
    'select_district' => '— Select district first —',
    'payment_method'  => 'Payment Method',
    'transfer'        => 'Bank Transfer',
    'cod'             => 'Cash on Delivery',
    'confirm_order'   => 'Confirm Order',
    'order_summary'   => 'Order Summary',
    'product_total'   => 'Subtotal',
    'shipping_fee'    => 'Shipping',
    'grand_total'     => 'Grand Total',
    'payment_note'    => '* Bank transfer requires amount confirmation and reference. Please wait for shop verification.',

    // ----- Login / Register -----
    'login_title'     => 'Login',
    'email'           => 'Email',
    'password'        => 'Password',
    'login_error'     => 'Invalid email or password',
    'register_title'  => 'Register',
    'register_name'   => 'Name',
    'register_phone'  => 'Phone',
    'register_pw_hint'=> 'Password (min 8 characters)',

    // ----- Account -----
    'my_account'      => 'My Account',
    'order_history'   => 'Order History',
    'order_no'        => 'Order No.',
    'order_date'      => 'Date',
    'status'          => 'Status',

    // ----- Track Order -----
    'track_title'     => 'Track Order',
    'track_placeholder'=> 'Order number e.g. SUP260101ABCD',
    'order_not_found' => 'Order not found',
    'order_number'    => 'Order No.',
    'order_date_label'=> 'Date',
    'order_status'    => 'Order Status',
    'tracking_no'     => 'Tracking No.',

    // ----- Order Success -----
    'order_success'   => '🎉 Order Placed Successfully',
    'order_no_label'  => 'Order Number',
    'keep_order_no'   => 'Keep this number to track your order',
    'payment_cod'     => 'Cash on Delivery',
    'payment_transfer'=> 'Bank Transfer (Confirmed)',
    'payment_label'   => 'Payment',

    // ----- Transfer Payment -----
    'pay_qr_title'    => 'Pay via QR Code',
    'amount_to_pay'   => 'Amount to transfer',
    'qr_note'         => 'Please verify recipient name and amount in your banking app before confirming.',
    'transfer_info'   => 'Transfer Information',
    'transfer_cancelled'=> 'This order has been cancelled or payment failed. Please contact the shop.',
    'transfer_submitted'=> 'Submitted on %s — Status: Awaiting shop verification',
    'transfer_amount_label' => 'Amount transferred',
    'view_slip'       => 'View Slip',
    'auto_check_note' => 'This page will auto-check status and redirect to success page once the shop confirms payment.',
    'after_transfer'  => 'After transferring, enter the amount and attach the slip for the shop to verify.',
    'transfer_amount' => 'Transfer Amount (THB)',
    'attach_slip'     => 'Attach Transfer Slip (JPG, PNG or WEBP, max 2MB)',
    'submit_transfer' => 'Submit Transfer',

    // ----- About / Contact -----
    'about_title'     => 'About Us',
    'about_desc'      => '%s — Your go-to shop for soft, slow-rising squishies! From bakery squishies to adorable angel animals 🧸',
    'contact_title'   => 'Contact Us',
    'phone_label'     => 'Phone',
    'email_label'     => 'Email',

    // ----- Footer -----
    'all_products'    => 'All Products',

    // ----- Cart flash messages -----
    'added_to_cart'   => 'Added to cart 🧸',
    'stock_exceed'    => 'Quantity exceeds stock, adjusted to %d',
    'product_not_found'=> 'Product not found',
    'cart_empty_msg'  => 'Cart is empty',
  ],
];

/** ดึงข้อความตามภาษาปัจจุบัน */
function t($key) {
    global $T, $lang;
    return $T[$lang][$key] ?? $T['th'][$key] ?? $key;
}

/** ชื่อสินค้าตามภาษา: อังกฤษ = name, ไทย = name_th (ถ้ามี) */
function pname($p) {
    global $lang;
    if ($lang === 'en') return $p['name'] ?? '';
    return ($p['name_th'] ?? '') !== '' ? $p['name_th'] : ($p['name'] ?? '');
}

/** สร้าง URL สำหรับสลับภาษา (เก็บ query string อื่นๆ ไว้) */
function lang_url($targetLang) {
    $params = $_GET;
    $params['lang'] = $targetLang;
    return basename($_SERVER['SCRIPT_NAME']) . '?' . http_build_query($params);
}
