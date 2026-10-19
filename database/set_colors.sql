-- ตั้งตัวเลือกสีให้สินค้าที่มีหลายสี (คั่นด้วย ,) รันใน phpMyAdmin ฐานข้อมูล superultrapoop
-- แก้ชื่อสี/ตัวเลือกภายหลังได้จาก Admin → แก้ไขสินค้า → ช่อง "สี / ตัวเลือก"
UPDATE products SET color='Pom,Nuts'                                   WHERE name='Pom the Puppy';
UPDATE products SET color='Angel Puppy น้ำตาลแดง,Chiffon ชมพู'          WHERE name='Angel puppy';
UPDATE products SET color='ขาว,ชมพู,ม่วง'                               WHERE name='Misty Cat';
UPDATE products SET color='ฟ้า,ชมพู,ม่วง'                               WHERE name='Angel dolphin';
UPDATE products SET color='กลิ่นขนมปัง,กลิ่นสตรอว์เบอร์รี,กลิ่นชานม'    WHERE name='Bagel';
UPDATE products SET color='วานิลลาฮันนี่,เบอร์รี่,ช็อกโกแลต'            WHERE name='Danish roll';
UPDATE products SET color='ชีสเค้กหน้าส้ม,ชีสเค้กขาว,ชาเขียว'           WHERE name='CHEESE CAKE Mini';
