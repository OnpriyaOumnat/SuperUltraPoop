-- รันใน phpMyAdmin (เลือกฐานข้อมูล superultrapoop → แท็บ SQL) ครั้งเดียว
ALTER TABLE order_items ADD variant VARCHAR(60) DEFAULT NULL AFTER product_name;
-- ตัวอย่าง: สินค้าที่มีหลายสี ใส่ในช่อง color คั่นด้วย , (แก้ได้จากหลังบ้านด้วย)
UPDATE products SET color='Pom,Nuts' WHERE name='Pom the Puppy';
