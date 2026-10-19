-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 17, 2026 at 02:56 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `superultrapoop`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'onpriya', '$2y$10$pfWLEmqONHo625K2kK7Q7e.FfBBoE20hDx/BUK5ImPI..Jmy3t7cy', '2026-10-16 12:54:00'),
(2, 'mildmint', '$2y$10$jl2xngv9w/YhG1Mg6NSxuOmJ7bOert5olcM67Z0ZAKNUvEjZmrozC', '2026-10-16 12:55:57');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Bakery', 'สกุชชี่รูปขนมปังและเบเกอรี่'),
(2, 'Angelanimal', 'สกุชชี่รูปสัตว์และตัวละครน่ารัก');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_no` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(120) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `province` varchar(80) NOT NULL,
  `district` varchar(80) NOT NULL,
  `subdistrict` varchar(80) NOT NULL DEFAULT '',
  `postcode` varchar(10) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `payment_method` enum('transfer','cod') NOT NULL,
  `status` enum('Pending','Paid','Processing','Shipped','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `tracking_no` varchar(60) DEFAULT NULL,
  `stock_restored` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_no`, `user_id`, `customer_name`, `phone`, `address`, `province`, `district`, `postcode`, `subtotal`, `shipping`, `total`, `payment_method`, `status`, `tracking_no`, `stock_restored`, `created_at`) VALUES
(6, 'SUP26101648D7', 4, 'นวรัตน์ ศรีเมือง', '0824246878', 'ประสงค์ก้าวหน้า 2 Dormitory, Room 99/8, Moo 1,', 'ปทุมธานี', 'klog6', '12120', 606.00, 40.00, 646.00, 'transfer', 'Pending', NULL, 0, '2026-10-16 14:26:30');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(160) NOT NULL,
  `variant` varchar(60) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `variant`, `price`, `qty`) VALUES
(10, 6, 16, 'Chubby Fluffy Hamster', 'ขาวมุก', 257.00, 1),
(11, 6, 3, 'I Love Strawberry alen', 'แดง', 349.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `method` enum('transfer','cod') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transfer_amount` decimal(10,2) DEFAULT NULL,
  `transfer_reference` varchar(100) DEFAULT NULL,
  `transfer_slip` varchar(255) DEFAULT NULL,
  `transfer_submitted_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `is_demo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `method`, `amount`, `transfer_amount`, `transfer_reference`, `transfer_submitted_at`, `paid_at`, `status`, `is_demo`, `created_at`) VALUES
(6, 6, 'transfer', 646.00, NULL, NULL, NULL, NULL, 'pending', 1, '2026-10-16 14:26:30');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `name_th` varchar(160) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `scent` varchar(80) DEFAULT NULL,
  `color` varchar(255) DEFAULT NULL,
  `is_new` tinyint(1) NOT NULL DEFAULT 0,
  `is_best` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `name_th`, `description`, `price`, `stock`, `image`, `scent`, `color`, `is_new`, `is_best`, `status`, `created_at`) VALUES
(1, 1, 'Melon Bread Queen', 'สกุชชี่เมลอนปัง', 'เมลอนปังลายตารางสุดน่ารัก นุ่มฟู คืนตัวช้า', 259.00, 9, 'image/Melon Bread Queen.jpg', 'เมลอน', 'เขียวอ่อน', 0, 1, 'active', '2026-10-13 14:27:09'),
(2, 1, 'I Love Peach Queen', 'สกุชชี่ลูกพีช', 'ลูกพีชสีชมพูพาสเทล บีบเพลินมือ', 349.00, 14, 'image/I Love Peach Queen.jpg', 'พีช', 'ชมพู', 0, 1, 'active', '2026-10-13 14:27:09'),
(3, 1, 'I Love Strawberry alen', 'สกุชชี่สตรอว์เบอร์รี', 'สตรอว์เบอร์รีฟูนุ่ม หอมหวาน', 349.00, 15, 'image/I Love Strawberry alen.jpg', 'สตรอว์เบอร์รี', 'แดง', 0, 1, 'active', '2026-10-13 14:27:09'),
(4, 1, 'Bagel', 'สกุชชี่เบเกิลยักษ์', 'เบเกิลชิ้นใหญ่ นุ่มหนึบ', 299.00, 11, 'image/Bagel.jpg', 'ขนมปังอบ', 'กลิ่นขนมปัง,กลิ่นสตรอว์เบอร์รี,กลิ่นชานม', 0, 0, 'active', '2026-10-13 14:27:09'),
(5, 1, 'Danish roll', 'สกุชชี่เดนิชโรลยักษ์', 'เดนิชโรลชิ้นใหญ่ เนื้อนุ่มช้า', 299.00, 10, 'image/Danish roll.jpg', 'เนย', 'วานิลลาฮันนี่,เบอร์รี่,ช็อกโกแลต', 0, 0, 'active', '2026-10-13 14:27:09'),
(6, 1, 'Baguette', 'สกุชชี่บาแก็ตยักษ์', 'บาแก็ตยาวสุดนุ่ม กอดได้', 329.00, 10, 'image/Baguette.jpg', 'ขนมปังอบ', 'น้ำตาลทอง', 0, 0, 'active', '2026-10-13 14:27:09'),
(7, 1, 'Manttaiko baguette', 'เมนไทโกะ บาแก็ตใหญ่ สกุชชี่', 'บาแก็ตเมนไทโกะชิ้นใหญ่', 349.00, 7, 'image/Mentaiko  baguette.jpg', 'เมนไทโกะ', 'ครีมส้ม', 1, 0, 'active', '2026-10-13 14:27:09'),
(8, 1, 'Boule de campagne', 'สกุชชี่ขนมปังฝรั่งเศส', 'ขนมปังก้อนกลมสไตล์ฝรั่งเศส', 289.00, 9, 'image/BOULE DE CAMPAGNE.jpg', 'ขนมปังอบ', 'น้ำตาลอ่อน', 0, 0, 'active', '2026-10-13 14:27:09'),
(9, 1, 'Cheese cake mini', 'สกุชชี่ชีสเค้กจิ๋ว', 'ชีสเค้กชิ้นจิ๋ว พกพาง่าย', 199.00, 16, 'image/CHEESE CAKE Mini.jpg', 'ชีส', 'ชีสเค้กหน้าส้ม,ชีสเค้กขาว,ชาเขียว', 0, 0, 'active', '2026-10-13 14:27:09'),
(10, 2, 'Angel dolphin', 'สกุชชี่โลมาเทวดา', 'โลมาน้อยพร้อมวงแหวนเทวดา', 279.00, 14, 'image/Angel dolphin.jpg', 'ทะเลอ่อน', 'ฟ้า,ชมพู,ม่วง', 0, 0, 'active', '2026-10-13 14:27:09'),
(11, 2, 'Angel Bunny', 'สกุชชี่กระต่ายเทวดา', 'กระต่ายหูยาวสุดนุ่ม', 279.00, 14, 'image/Angel Bunny.jpg', 'วานิลลา', 'ชมพู', 1, 0, 'active', '2026-10-13 14:27:09'),
(12, 2, 'Misty Cat', 'สกุชชี่แมว', 'แมวน้อยตัวนุ่ม ใบหน้าใจดี', 299.00, 12, 'image/Misty Cat.jpg', 'มิลค์', 'ขาว,ชมพู,ม่วง', 0, 1, 'active', '2026-10-13 14:27:09'),
(13, 2, 'Pom the Puppy', 'สกุชชี่น้องหมาปอมีปอม', 'น้องหมาปอมฟูฟ่อง', 269.00, 11, 'image/Pom the Puppy.jpg', 'คุกกี้', 'Pom,Nuts', 0, 0, 'active', '2026-10-13 14:27:09'),
(14, 2, 'Angel puppy', 'สกุชชี่น้องหมาเทวดา', 'น้องหมาเทวดาตัวนุ่ม', 279.00, 12, 'image/Angel puppy.jpg', 'วานิลลา', 'Angel Puppy น้ำตาลแดง,Chiffon ชมพู', 0, 0, 'active', '2026-10-13 14:27:09'),
(15, 2, 'Poo-caca-bobo queen', 'สกุชชี่อุนจิ', 'อุนจิน่ารักสุดฮา บีบแล้วคืนตัวช้า', 259.00, 2, 'image/POO-CACA-BOO Queen.jpg', 'ช็อกโกแลต', 'สีน้ำตาล', 0, 0, 'inactive', '2026-10-13 14:27:09'),
(16, 2, 'Chubby Fluffy Hamster', 'แฮมสเตอร์คินคุมะ', 'แฮมสเตอร์ตัวอ้วนขนฟู', 257.00, 10, 'uploads/products/f328444b56fd9f0d54eca1f2d3416d56.jpg', 'ขนมปังเนย', 'ขาวมุก,เหลืองทอง', 1, 0, 'active', '2026-10-16 13:55:15'),
(17, 1, 'Birthday cake squishy set', 'สกุชชี่เค้กวันเกิด', 'ชุดสกุชชี่เค้กวันเกิด', 1599.00, 7, 'uploads/products/00a93360af35641b21f2053d40082e9c.jpg', 'สตอเบอร์รี่,ครีมชีส', 'สตอเบอร์รี่,บัตเตอร์', 1, 0, 'active', '2026-10-16 14:06:46');

-- --------------------------------------------------------

--
-- Table structure for table `store_settings`
--

CREATE TABLE `store_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `store_settings`
--

INSERT INTO `store_settings` (`setting_key`, `setting_value`) VALUES
('announcement', 'นุ่มฟู บีบเพลิน คืนตัวช้า'),
('contact_email', 'hello@superultrapoop.test'),
('contact_line', '@superultrapoop'),
('contact_phone', '08x-xxx-xxxx'),
('shipping_fee', '40'),
('store_name', 'SuperUltraPoop');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `created_at`) VALUES
(4, 'นวรัตน์ ศรีเมือง', 'Navarat@gmail.com', '0824246878', '$2y$10$J6TtMJcP3sl9BwH1gSjfFuvoPDR6Az8kymApU/aHv0V2BwqZ3tOW2', '2026-10-16 14:25:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `fk_ord_user` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_oi_order` (`order_id`),
  ADD KEY `fk_oi_prod` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pay_order` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prod_cat` (`category_id`);

--
-- Indexes for table `store_settings`
--
ALTER TABLE `store_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_ord_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_oi_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
