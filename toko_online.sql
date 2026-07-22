-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 22 Jul 2026 pada 11.10
-- Versi server: 10.4.22-MariaDB
-- Versi PHP: 8.1.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toko_online`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$ftjJapyf3s2JKf39DV6KeOTWREOdzNK75uuSoL1Ltv3jcjzRObnFG', '2026-05-31 12:58:11'),
(2, 'admin2', '$2y$10$b/fJTUUzQqYuelB9ho2sfulPpQHiEi6FCRCSJqnvVbFT1vmZI/NAe', '2026-05-31 15:51:29'),
(3, 'admin3', 'admin123', '2026-07-05 16:06:44');

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`id`, `nama`, `created_at`) VALUES
(1, 'Elektronik', '2026-05-31 12:58:11'),
(2, 'Pakaian', '2026-05-31 12:58:11'),
(3, 'Makanan & Minuman', '2026-05-31 12:58:11'),
(4, 'Aksesoris', '2026-05-31 12:58:11'),
(5, 'Sparepart', '2026-07-12 16:47:25');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `kode_pesanan` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_penerima` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_hp` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat_pengiriman` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metode_pengiriman` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_pengiriman` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ongkir` decimal(12,2) NOT NULL DEFAULT 0.00,
  `metode_pembayaran` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COD',
  `catatan` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtotal_produk` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_bayar` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu Konfirmasi',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id_order`, `id_user`, `kode_pesanan`, `nama_penerima`, `no_hp`, `alamat_pengiriman`, `metode_pengiriman`, `label_pengiriman`, `ongkir`, `metode_pembayaran`, `catatan`, `subtotal_produk`, `total_bayar`, `status`, `created_at`) VALUES
(1, 9, 'INV260705607969', 'sule', '085176922041', 'Depok', 'reguler', 'Kurir Reguler (JNE / J&T / SiCepat)', '15000.00', 'COD', 'Rumah kotak hijau', '255000.00', '270000.00', 'Menunggu Konfirmasi', '2026-07-05 16:03:50'),
(2, 10, 'INV2607053C6A5E', 'woi', '085176922041', 'asdasd', 'reguler', 'Kurir Reguler (JNE / J&T / SiCepat)', '15000.00', 'COD', '', '55000.00', '70000.00', 'Menunggu Konfirmasi', '2026-07-05 16:37:39'),
(3, 10, 'INV26070543004E', 'woi', '085176922041', 'asdasd', 'instan', 'Instan / Same Day', '25000.00', 'COD', '', '12000000.00', '12025000.00', 'Selesai', '2026-07-05 16:41:40'),
(4, 9, 'INV26070570CFA8', 'sule', '085176922041', 'dsad', 'reguler', 'Kurir Reguler (JNE / J&T / SiCepat)', '15000.00', 'COD', '', '110000.00', '125000.00', 'Dikemas', '2026-07-05 19:36:07'),
(5, 9, 'INV260705CC4C5F', 'sule', '085176922041', 'dsad', 'reguler', 'Kurir Reguler (JNE / J&T / SiCepat)', '15000.00', 'COD', 'Rumah kotak hijau', '500000.00', '515000.00', 'Selesai', '2026-07-05 20:05:16'),
(6, 9, 'INV260711A1B70B', 'Abijar', '085176922041', 'Bogor', 'kargo', 'Kargo (disarankan)', '10000.00', 'COD', '', '12955000.00', '12965000.00', 'Dibatalkan', '2026-07-11 14:10:02'),
(7, 9, 'INV260712E55FD2', 'uyyi', '08862818791', 'dsad', 'reguler', 'Kurir Reguler (JNE / J&T / SiCepat)', '15000.00', 'COD', '', '12045000.00', '12060000.00', 'Dikemas', '2026-07-12 14:03:26'),
(8, 9, 'INV260713AD346E', 'sule', '085176922041', 'dsad', 'reguler', 'Kurir Reguler (JNE / J&T / SiCepat)', '15000.00', 'COD', '', '14500000.00', '14515000.00', 'Menunggu Konfirmasi', '2026-07-13 19:11:38'),
(9, 9, 'INV260715D31F24', 'sule', '085176922041', 'dsad', 'instan', 'Instan / Same Day', '25000.00', 'COD', '', '2500000.00', '2525000.00', 'Menunggu Konfirmasi', '2026-07-16 01:48:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id_item` int(11) NOT NULL,
  `id_order` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `nama_produk` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga_satuan` decimal(12,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id_item`, `id_order`, `id_produk`, `nama_produk`, `harga_satuan`, `qty`, `subtotal`) VALUES
(1, 1, 3, 'Kaos Polos Premium', '85000.00', 3, '255000.00'),
(2, 2, 5, 'Kopi Arabika 250g', '55000.00', 1, '55000.00'),
(3, 3, 7, 'Laptop ASUS', '12000000.00', 1, '12000000.00'),
(4, 4, 5, 'Kopi Arabika 250g', '55000.00', 2, '110000.00'),
(5, 5, 4, 'Hoodie Oversized', '250000.00', 2, '500000.00'),
(6, 6, 1, 'Earphone Bluetooth X1', '150000.00', 2, '300000.00'),
(7, 6, 4, 'Hoodie Oversized', '250000.00', 2, '500000.00'),
(8, 6, 5, 'Kopi Arabika 250g', '55000.00', 2, '110000.00'),
(9, 6, 6, 'EDIFIER W800BT PRO Hi-Res Audio ANC Headphone - Hitam', '45000.00', 1, '45000.00'),
(10, 6, 7, 'Laptop ASUS', '12000000.00', 1, '12000000.00'),
(11, 7, 6, 'EDIFIER W800BT PRO Hi-Res Audio ANC Headphone - Hitam', '45000.00', 1, '45000.00'),
(12, 7, 7, 'Laptop ASUS', '12000000.00', 1, '12000000.00'),
(13, 8, 7, 'Laptop ASUS', '12000000.00', 1, '12000000.00'),
(14, 8, 9, 'Xiaomi Redmi Note 14', '2500000.00', 1, '2500000.00'),
(15, 9, 9, 'Xiaomi Redmi Note 14', '2500000.00', 1, '2500000.00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `gambar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `category_id`, `nama`, `harga`, `stok`, `gambar`, `deskripsi`, `created_at`) VALUES
(1, 1, 'Earphone Bluetooth X1', '150000.00', 18, 'prod_6a1bdb4ca5d7e.jpg', 'Earphone wireless dengan bass yang kuat dan baterai tahan lama.', '2026-05-31 12:58:11'),
(2, 1, 'Powerbank 10000mAh', '200000.00', 9, 'prod_6a1bdb3d5f482.jpg', 'Powerbank slim kapasitas 10000mAh, cocok untuk perjalanan.', '2026-05-31 12:58:11'),
(3, 2, 'Kaos Polos Premium', '85000.00', 47, 'prod_6a1bdb2ef009b.jpg', 'Kaos berbahan cotton combed 30s, adem dan nyaman dipakai.', '2026-05-31 12:58:11'),
(4, 2, 'Hoodie Oversized', '250000.00', 26, 'prod_6a1bdabfeb352.jpg', 'Hoodie tebal unisex dengan desain minimalis.', '2026-05-31 12:58:11'),
(5, 3, 'Kopi Arabika 250g', '55000.00', 35, 'prod_6a1bda7b1e3d9.jpg', 'Kopi arabika single origin, sangrai medium.', '2026-05-31 12:58:11'),
(6, 4, 'EDIFIER W800BT PRO Hi-Res Audio ANC Headphone - Hitam', '45000.00', 58, 'prod_6a1bd9d6d154d.jpeg', 'Kondisi: Baru\r\nBerat Satuan: 1 kg\r\nMin. Beli: 1 Buah\r\nKategori: Headphone\r\nEtalase: HEADPHONE\r\nEDIFIER W800BT PRO Hi-Res Audio ANC Headphone\r\n\r\n\r\n\r\nHybrid ANC technology isolates unwanted noise.\r\nΦ40mm dynamic driver with titanium coated diaphragm for high-fidelity audio.\r\nHi-Res audio certification via USB-C wired connection.\r\nMultipoint connection for seamless switching between devices.\r\nBuilt-in mic with AI noise cancellation for clear calls.\r\nLightweight, fully foldable design for daily use and travel.\r\nSkin-friendly, super soft ear cushions for all-day comfort.\r\n45 hours of playback, 10-minute charge provides 5 hours of use.\r\n\r\n\r\nImmerse in Pure Sound, Hear Every Detail\r\nThe 40mm dynamic driver provides an expansive soundstage and powerful bass, while the titanium-coated diaphragm ensures clear and transparent mid-high frequencies, making every listening session pure and enjoyable.\r\n\r\n\r\nAdvanced Noise Cancellation with Multiple Modes\r\nAdvanced hybrid active noise cancellation technology combined with the upgraded full-fit over-ear design, ensures excellent passive noise reduction and achieves a noise reduction depth of up to -44dB, letting you immerse yourself in a world of music anytime, anywhere.\r\n\r\n\r\nDesigned for All-Day Comfort\r\nIntroducing our newly designed multi-direction adaptive structure, engineered to perfectly fit various face shapes and ear contours. Combined with a lightweight body pressure-relief headband, and soft earpads, it offers an unparalleled and comfortable wearing experience.\r\n\r\n\r\nClear Hands-Free Calls, Anytime, Anywhere\r\nIntegrated Al call noise reduction algorithms and a high-sensitivity microphone effectively recognize background noise, preserving human voices for clear hands-free calls.\r\n\r\n\r\nFaster and More Stable Connectivity\r\nUtilizes the latest Bluetooth version 5.4 technology for faster transmission speeds, stronger anti-interference capabilities, and lower power consumption.\r\n\r\n\r\nUltra-Low Latency\r\nGaming Experience With a low latency of 0.06 seconds and game sound effects, it achieves audio-visual synchronization, providing you an immersive gaming experience.\r\n\r\n\r\n45H Battery Life with Fast Charging Support\r\nWith a built-in large-capacity battery and a low-power consumption chipset, it offers up to 45 hours of continuous music playback with ANC off, ensuring prolonged convenience.\r\n\r\n\r\nSeamless Multipoint Connection\r\nIt is capable of connecting to two Bluetooth audio devices simultaneously, making it incredibly convenient for both work and enjoying music at the same time.\r\n\r\n\r\nEnjoy Wired Connection Reliability\r\nIt supports USB-C connection for music, gaming, and more, allowing you to enjoy higher quality audio effortlessly.', '2026-05-31 12:58:11'),
(7, 1, 'Laptop ASUS', '12000000.00', 46, 'prod_6a1bdd8bae7da.jpg', '', '2026-05-31 14:04:43'),
(9, 1, 'Xiaomi Redmi Note 14', '2500000.00', 20, 'prod_6a6087cfb28e2.jpg', 'Xiamoi Redmi Note 14 OFFICIAL STOT\\RE', '2026-07-12 16:46:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id_user`, `nama`, `alamat`, `username`, `password`) VALUES
(1, 'rakha', 'london', 'rakha', '$2y$10$qMDe'),
(2, 'alien', 'Cijantung', 'Alien', '$2y$10$ZvNZ'),
(3, 'jeki', 'Cilodong', 'Jeki', 'Jeki123'),
(5, 'Ghani', 'london', 'ghani', '$2y$10$faRP'),
(6, 'Lionel messi', 'london', 'messi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(7, 'jaka', 'cilodong', 'Zaka', '$2y$10$4DphpVXQT05hqty9m7AkTOP7FlN2pPuwk8VbD0c8M7gWPYAtb3fFa'),
(8, 'ijat', 'depok', 'ijat', '$2y$10$e9OxFs6QcFBZrla1c4.TY.bpkIqrSb2kqm1hCqBMFV5ie1uPF5dmG'),
(9, 'sule', 'dsad', 'p', '$2y$10$rMr4nVXgcmXV3LiF91U1L.hF8lPhT1O63YaAt7gWvEC8kuUmBeqo.'),
(10, 'woi', 'asdasd', 'woi', '$2y$10$ftY9wJK4aoUrVylkjnybtO3NBpy9UQGCLpQchbcCRFe7r9bcO/5HC'),
(11, 'ijat', 'Bogor', 'ijat2', '$2y$10$wujZvArpNkyNnYfR6xP3bOL330jYvThr6hmUt3Ap89VtRXBvIZ9qm'),
(12, 'ojay alghifari', 'kp. tipar', 'ojay', '$2y$10$WR1DYWiIdU0jrOVYmsC84e0RZfCgPv1WZu.E6vnxbsNyyKjnzgzmS'),
(13, 'ojay alghifari', 'kp. tipar', 'ojayalghifari', '$2y$10$qa98EfWrtL58TKp8gbh9eeMahzJbODfhO/bllx22avHReG/fEighy');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id_order`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id_item`),
  ADD KEY `id_order` (`id_order`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id_item` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`id_order`) REFERENCES `orders` (`id_order`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `products` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
