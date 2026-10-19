<?php
// ตั้งค่าฐานข้อมูลสำหรับ XAMPP (Localhost)
$dbHost = 'localhost';
$dbName = 'superultrapoop';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    exit('เชื่อมต่อฐานข้อมูลไม่ได้ ตรวจสอบว่าเปิด MySQL ใน XAMPP และนำเข้า superultrapoop.sql แล้ว');
}
