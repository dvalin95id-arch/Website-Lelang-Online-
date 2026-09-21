-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2026 at 01:55 PM
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
-- Database: `lelang_online`
--

-- --------------------------------------------------------

--
-- Table structure for table `auctions`
--

CREATE TABLE `auctions` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `start_price` decimal(15,2) NOT NULL,
  `current_price` decimal(15,2) NOT NULL,
  `winner_user_id` int(11) DEFAULT NULL,
  `winner_bid` decimal(15,2) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('aktif','selesai') DEFAULT 'aktif',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `winner_at` datetime DEFAULT NULL,
  `winner_status` enum('belum','ditentukan') DEFAULT 'belum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auctions`
--

INSERT INTO `auctions` (`id`, `title`, `description`, `image`, `start_price`, `current_price`, `winner_user_id`, `winner_bid`, `start_time`, `end_time`, `status`, `created_by`, `created_at`, `winner_at`, `winner_status`) VALUES
(1, 'iPhone 13 128GB Second', 'iPhone 13 second kondisi baik, kamera normal, Face ID normal dan siap digunakan.', 'https://images.unsplash.com/photo-1592286927505-2fd8e6f1c2a3?auto=format&fit=crop&w=800&q=80', 5000000.00, 5000000.00, NULL, NULL, '2026-09-11 08:05:54', '2026-09-18 08:05:54', 'aktif', NULL, '2026-09-11 01:05:54', NULL, 'belum'),
(2, 'Samsung Galaxy S22', 'Samsung Galaxy S22 second dengan kondisi baik. Layar, kamera dan fingerprint normal.', 'https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?auto=format&fit=crop&w=800&q=80', 4000000.00, 9999999999999.99, NULL, NULL, '2026-09-11 08:05:54', '2026-09-16 08:05:54', 'aktif', NULL, '2026-09-11 01:05:54', NULL, 'belum'),
(3, 'iPhone 11 64GB', 'iPhone 11 second dengan kondisi masih bagus dan cocok untuk penggunaan sehari-hari.', 'https://images.unsplash.com/photo-1574755393849-623942496936?auto=format&fit=crop&w=800&q=80', 3000000.00, 3000000.00, NULL, NULL, '2026-09-11 08:05:54', '2026-09-21 08:05:54', 'aktif', NULL, '2026-09-11 01:05:54', NULL, 'belum'),
(4, 'Xiaomi Redmi Note 12', 'Redmi Note 12 second, performa normal dan baterai masih cukup baik.', 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?auto=format&fit=crop&w=800&q=80', 1800000.00, 18230000.00, 4, 18230000.00, '2026-09-11 08:05:54', '2026-09-19 08:05:54', 'selesai', NULL, '2026-09-11 01:05:54', NULL, 'belum');

-- --------------------------------------------------------

--
-- Table structure for table `bids`
--

CREATE TABLE `bids` (
  `id` int(11) NOT NULL,
  `auction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bid_amount` decimal(15,2) NOT NULL,
  `bid_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bids`
--

INSERT INTO `bids` (`id`, `auction_id`, `user_id`, `bid_amount`, `bid_time`, `created_at`) VALUES
(1, 2, 1, 100000000.00, '2026-09-12 04:51:07', '2026-09-12 05:06:05'),
(2, 2, 1, 9999999999999.99, '2026-09-12 04:51:19', '2026-09-12 05:06:05'),
(3, 4, 4, 18200000.00, '2026-09-12 05:42:09', '2026-09-12 05:42:09'),
(4, 4, 4, 18220000.00, '2026-09-13 04:31:10', '2026-09-13 04:31:10'),
(5, 4, 4, 18230000.00, '2026-09-13 05:12:47', '2026-09-13 05:12:47'),
(6, 2, 4, 9999999999999.99, '2026-09-14 09:48:59', '2026-09-14 09:48:59');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `auction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'padli', 'user', 'padli@gmail.com', '$2y$10$HHrr86KKjwqtiP.AQtIjA.qhal.77OsFHTpXm7SYgeunfcYn20YKe', 'user', '2026-09-12 04:45:14'),
(2, 'Administrator', 'admin', 'admin@lelangkita.com', '$2y$10$7Ftb/fXW2xNMYq/Y.HHT2O7xGrgtwVkcZseJkdpEo5FBpiJj/7fvS', 'admin', '2026-09-12 05:17:52'),
(4, 'pawbertt', 'padli', 'banana@gmail.com', '$2y$10$MUvZ..j7xHXeKUco7wk84.Tkdw1Og5EJEhANHSp19opHpXTqPAm/K', 'user', '2026-09-12 05:41:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auctions`
--
ALTER TABLE `auctions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `bids`
--
ALTER TABLE `bids`
  ADD PRIMARY KEY (`id`),
  ADD KEY `auction_id` (`auction_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auctions`
--
ALTER TABLE `auctions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bids`
--
ALTER TABLE `bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `auctions`
--
ALTER TABLE `auctions`
  ADD CONSTRAINT `auctions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bids`
--
ALTER TABLE `bids`
  ADD CONSTRAINT `bids_ibfk_1` FOREIGN KEY (`auction_id`) REFERENCES `auctions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bids_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
