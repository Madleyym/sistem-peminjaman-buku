-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 01 Feb 2025 pada 03.47
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `peminjaman_buku`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `action_type` enum('login','book_loan','book_return','profile_update') DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `action_type`, `ip_address`, `created_at`) VALUES
(1, 101, 'Login', '', '192.168.1.1', '2025-01-27 03:00:00'),
(2, 102, 'View Profile', '', '192.168.1.2', '2025-01-27 03:05:00'),
(3, 103, 'Edit Profile', '', '192.168.1.3', '2025-01-27 03:10:00'),
(4, 101, 'Logout', '', '192.168.1.1', '2025-01-27 03:15:00'),
(5, 104, 'Delete Account', '', '192.168.1.4', '2025-01-27 03:20:00'),
(6, 105, 'Upload File', '', '192.168.1.5', '2025-01-27 03:25:00'),
(7, 106, 'Download Report', '', '192.168.1.6', '2025-01-27 03:30:00'),
(8, 107, 'Reset Password', '', '192.168.1.7', '2025-01-27 03:35:00'),
(9, 9, 'Membatalkan peminjaman buku \"asaas\"', '', '::1', '2025-01-31 05:11:50'),
(10, 9, 'Membatalkan peminjaman buku \"asaas\"', '', '127.0.0.1', '2025-01-31 08:24:03'),
(11, 10, 'Membatalkan peminjaman buku \"asaas\"', '', '::1', '2025-01-31 09:36:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(100) DEFAULT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `email`, `last_login`, `created_at`, `created_by`, `nik`, `name`) VALUES
(7, 'Administrator', '$2y$10$xR6erGJECO2J2zqGL6vJ/ucjCut6YmqtbjzfFvOdga4jIXzEUDEtW', 'admin@gmail.com', '2025-02-01 01:33:39', '2025-01-27 10:45:20', NULL, '1111111111111111', 'Administrator');

-- --------------------------------------------------------

--
-- Struktur dari tabel `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `year_published` year(4) DEFAULT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `total_quantity` int(11) NOT NULL DEFAULT 0,
  `available_quantity` int(11) NOT NULL DEFAULT 0,
  `cover_image` varchar(255) DEFAULT 'default_book.jpg',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shelf_location` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `publisher`, `year_published`, `isbn`, `category`, `total_quantity`, `available_quantity`, `cover_image`, `description`, `created_at`, `shelf_location`, `updated_at`) VALUES
(26, 'Pemrograman Web dengan PHP dan MySQL', 'Budi Raharjo', 'Informatika', '2000', '978-123-456', 'Classic', 11, 11, '679b15ae58855_pepe.jpg', 'Buku ini membahas dasar-dasar pemrograman web menggunakan PHP dan MySQL, dilengkapi dengan studi kasus dan praktik langsung. Sangat cocok bagi pemula yang ingin memahami pengembangan web secara sistematis.', '2025-01-29 05:30:40', '3', '2025-01-31 07:16:00'),
(29, 'asa', 'asas', 'asa', '2019', '978-0262033877', 'Business', 11, 10, '679b074e42cea_pepe.jpg', 'sads', '2025-01-29 05:45:38', '11', '2025-01-31 07:16:00'),
(30, 'Atomic Habits', 'James Clear', 'Penguin', '2018', '9780735211292', 'Business', 10, 10, '679b04735b300_pepe.jpg', 'A book about building good habits.', '2025-01-30 04:36:45', '12', '2025-01-31 07:16:00'),
(31, 'The Power of Habit', 'Charles Duhigg', 'Random House', '2011', '9780812981605', '', 8, 8, '679b083373d9b_pepe.jpg', 'How habits shape our lives.', '2025-01-30 04:36:45', 'A2', '2025-01-31 07:16:00'),
(32, 'Deep Work', 'Cal Newport', 'Grand Central Publishing', '2016', '9781455586691', '', 7, 7, '679b16243550a_logo.png', 'Focused success in a distracted world.', '2025-01-30 04:36:45', 'A3', '2025-01-31 07:16:00'),
(34, 'Rich Dad Poor Dad', 'Robert Kiyosaki', 'Plata Publishing', '1997', '9781612680194', '', 12, 12, '679b27f79479e_pepe.jpg', 'Lessons on money and wealth.', '2025-01-30 04:36:45', 'B2', '2025-01-31 07:16:00'),
(35, 'The Lean Startup', 'Eric Ries', 'Crown Business', '2011', '9780307887893', 'Business', 6, 4, 'covers/lean_startup.jpg', 'How to build a startup effectively.', '2025-01-30 04:36:45', 'B3', '2025-01-31 07:16:00'),
(36, 'Zero to One', 'Peter Thiel', 'Crown Business', '2014', '9780804139298', 'Entrepreneurship', 5, 3, 'covers/zero_to_one.jpg', 'Building the future.', '2025-01-30 04:36:45', 'B4', '2025-01-31 07:16:00'),
(37, 'Thinking, Fast and Slow', 'Daniel Kahneman', 'Farrar, Straus and Giroux', '2011', '9780374533557', 'Psychology', 8, 6, 'covers/thinking_fast_and_slow.jpg', 'Two systems of thinking.', '2025-01-30 04:36:45', 'C1', '2025-01-31 07:16:00'),
(38, 'Sapiens', 'Yuval Noah Harari', 'Harper', '2011', '9780062316097', 'History', 7, 5, 'covers/sapiens.jpg', 'A brief history of humankind.', '2025-01-30 04:36:45', 'C2', '2025-01-31 07:16:00'),
(39, 'Homo Deus', 'Yuval Noah Harari', 'Harper', '2015', '9780062464316', 'Future Studies', 6, 4, 'covers/homo_deus.jpg', 'The future of humankind.', '2025-01-30 04:36:45', 'C3', '2025-01-31 07:16:00'),
(40, 'The Subtle Art of Not Giving a F*ck', 'Mark Manson', 'HarperOne', '2016', '9780062457714', 'Self-Improvement', 9, 7, 'covers/subtle_art.jpg', 'A counterintuitive approach to living.', '2025-01-30 04:36:45', 'C4', '2025-01-31 07:16:00'),
(41, 'The Psychology of Money', 'Morgan Housel', 'Harriman House', '2020', '9780857197689', 'Finance', 10, 7, 'covers/psychology_of_money.jpg', 'Timeless lessons on wealth.', '2025-01-30 04:36:45', 'D1', '2025-01-31 15:40:00'),
(42, 'Can’t Hurt Me', 'David Goggins', 'Lioncrest Publishing', '2018', '9781544512280', 'Motivation', 7, 5, 'covers/cant_hurt_me.jpg', 'Mastering your mind.', '2025-01-30 04:36:45', 'D2', '2025-01-31 07:16:00'),
(43, 'The Alchemist', 'Paulo Coelho', 'HarperOne', '1988', '9780062315007', 'Fiction', 8, 6, 'covers/alchemist.jpg', 'A story about following dreams.', '2025-01-30 04:36:45', 'D3', '2025-01-31 07:16:00'),
(44, '1984', 'George Orwell', 'Secker & Warburg', '1949', '9780451524935', 'Dystopian', 6, 4, 'covers/1984.jpg', 'A dystopian society under surveillance.', '2025-01-30 04:36:45', 'E1', '2025-01-31 07:16:00'),
(45, 'Brave New World', 'Aldous Huxley', 'Chatto & Windus', '1932', '9780060850523', 'Dystopian', 5, 3, 'covers/brave_new_world.jpg', 'A future world of genetic engineering.', '2025-01-30 04:36:45', 'E2', '2025-01-31 07:16:00'),
(46, 'The Catcher in the Rye', 'J.D. Salinger', 'Little, Brown and Company', '1951', '9780316769488', 'Fiction', 7, 4, 'covers/catcher_in_the_rye.jpg', 'A coming-of-age novel.', '2025-01-30 04:36:45', 'E3', '2025-01-31 07:40:48'),
(47, 'To Kill a Mockingbird', 'Harper Lee', 'J.B. Lippincott & Co.', '1960', '9780061120084', 'Fiction', 8, 6, 'covers/to_kill_a_mockingbird.jpg', 'A novel about racial injustice.', '2025-01-30 04:36:45', 'E4', '2025-01-31 07:16:00'),
(48, 'The Great Gatsby', 'F. Scott Fitzgerald', 'Charles Scribner’s Sons', '1925', '9780743273565', 'Fiction', 9, 7, 'covers/great_gatsby.jpg', 'A novel of wealth and decadence.', '2025-01-30 04:36:45', 'F1', '2025-01-31 07:16:00'),
(49, 'Moby-Dick', 'Herman Melville', 'Harper & Brothers', '2019', '9781503280786', 'Classic', 5, 5, '679b07fed203e_pepe.jpg', 'The story of Captain Ahab’s quest.', '2025-01-30 04:36:45', 'F2', '2025-01-31 07:16:00'),
(50, 'asaas', 'asaas', 'asasad', '2025', '9780132350884', 'Business', 12, 7, '679b18e463847_pepe.jpg', 'ds', '2025-01-30 06:08:50', '12', '2025-01-31 17:13:45'),
(51, 'DUREN', 'DUREN', 'DUREN', '2025', '9780132350885', '', 19, 16, '679b194f08bee_pepe.jpg', 'aefvsadfsa', '2025-01-30 06:16:12', '1233', '2025-01-31 14:23:53'),
(52, 'MANUK', 'MANUK', 'MANUK', '2025', '978123456', '', 100, 100, 'default_book.jpg', 'sdddfsgdsf', '2025-01-30 06:22:55', '123', '2025-01-31 07:16:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` datetime NOT NULL,
  `due_date` datetime NOT NULL,
  `return_date` datetime DEFAULT NULL,
  `status` enum('pending','approved','rejected','borrowed','returned','overdue') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `borrowings`
--

INSERT INTO `borrowings` (`id`, `user_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 9, 50, '2025-01-31 08:16:53', '2025-02-07 08:16:53', NULL, '', '\nDibatalkan oleh pengguna pada 2025-01-31 12:11:50', '0000-00-00 00:00:00', '2025-01-31 12:11:50'),
(2, 9, 50, '2025-01-31 08:19:20', '2025-02-07 08:19:20', NULL, '', '\nDibatalkan oleh pengguna pada 2025-01-31 15:24:03', '0000-00-00 00:00:00', '2025-01-31 15:24:03'),
(3, 9, 46, '2025-01-31 08:40:48', '2025-02-07 08:40:48', NULL, 'pending', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(4, 9, 51, '2025-01-31 09:18:07', '2025-02-07 09:18:07', NULL, 'pending', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(5, 10, 50, '2025-01-31 09:49:07', '2025-02-07 09:49:07', NULL, '', '\nDibatalkan oleh pengguna pada 2025-01-31 16:36:07', '0000-00-00 00:00:00', '2025-01-31 16:36:07'),
(6, 9, 50, '2025-01-31 11:00:58', '2025-02-07 11:00:58', NULL, 'pending', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(7, 9, 51, '2025-01-31 11:12:53', '2025-02-07 11:12:53', NULL, 'pending', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(8, 9, 50, '2025-01-31 11:45:04', '2025-02-07 11:45:04', NULL, 'pending', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(9, 9, 51, '2025-01-31 15:23:53', '2025-02-07 15:23:53', NULL, 'pending', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(10, 10, 50, '2025-01-31 16:35:58', '2025-02-07 16:35:58', NULL, 'pending', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(11, 10, 41, '2025-01-31 16:40:00', '2025-02-07 16:40:00', NULL, 'pending', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(12, 9, 50, '2025-01-31 17:42:55', '2025-02-07 17:42:55', NULL, 'pending', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(13, 9, 50, '2025-01-31 18:13:45', '2025-02-07 18:13:45', NULL, 'pending', NULL, '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `created_at`) VALUES
(1, 'Tryanda Anggita Suwito', '2203010343@unper.ac.id', 'aku ajsdjaiohdhuciasdbfcuisddf', '2025-01-25 04:59:13'),
(2, 'Tryanda Anggita Suwitoa', '2203010343@unper.ac.id', 'adadadadadadaadaa', '2025-01-25 05:10:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `fines`
--

CREATE TABLE `fines` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL,
  `processed_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `loan_date` date NOT NULL,
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('active','returned','overdue') DEFAULT 'active',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `book_id`, `loan_date`, `due_date`, `return_date`, `status`, `fine_amount`, `created_at`) VALUES
(36, 9, 29, '2025-01-30', '2025-02-13', NULL, 'active', 0.00, '2025-01-30 10:28:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `loan_logs`
--

CREATE TABLE `loan_logs` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `action` enum('borrowed','returned','extended') DEFAULT NULL,
  `action_by` varchar(50) DEFAULT NULL,
  `action_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `status` enum('read','unread') NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `related_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 9, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 1, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(2, 9, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 2, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(3, 9, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 3, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(4, 9, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 4, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(5, 10, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 5, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(6, 9, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 6, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(7, 9, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 7, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(8, 9, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 8, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(9, 9, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 9, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(10, 10, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 10, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(11, 10, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 11, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(12, 9, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 12, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(13, 9, 'borrow_request', 'Permintaan peminjaman buku sedang diproses', 13, 'unread', '0000-00-00 00:00:00', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('staff','supervisor') NOT NULL DEFAULT 'staff',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `login_count` int(11) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `staff`
--

INSERT INTO `staff` (`id`, `name`, `username`, `email`, `password`, `role`, `is_active`, `login_count`, `last_login`, `created_at`, `created_by`, `updated_at`, `updated_by`, `deleted_at`, `deleted_by`) VALUES
(1, 'Tryanda Anggita Suwito', 'petugas', 'petugas@gmail.com', '$2y$10$WlDI.hRV85rIwGToGMRQJ.rNqc.gdbKSBvsR9tfNKm0P6ydV/rzb.', 'staff', 1, 10, '2025-02-01 00:46:01', '2025-01-31 18:52:14', 7, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default_profile.png',
  `status` enum('active','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('admin','staff','users') NOT NULL DEFAULT 'users'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone_number`, `address`, `profile_image`, `status`, `created_at`, `updated_at`, `role`) VALUES
(8, 'Qian.xyz', 'kades@gmail.com', '$2y$10$OWv5Q1UPRt5PpDOjnnotGeeYfU3pD4KTb.dW/AEionqwOwCNqs1Za', '081395000225', 'Jl Pembela Tanah Air 177, Kota Tasikmalaya, Jawa Barat 46115', 'default_profile.png', 'active', '2025-01-26 06:23:41', '2025-01-31 07:16:00', 'users'),
(9, 'Tryanda Anggita Suwito', 'tryandaasu@gmail.com', '$2y$10$jeoIJ1jOulXKoIlDtapY.OeWcT5E.UFU6lFxv3EAg5SG6K04LfyJO', '081395000225', 'Jl Pembela Tanah Air 177, Kota Tasikmalaya, Jawa Barat 46115', 'default_profile.png', 'active', '2025-01-27 06:35:41', '2025-01-31 07:16:00', 'users'),
(13, 'Muhamad Agisna Revaldo', 'agisrifaldo75@gmail.com', '$2y$10$6rsj8L7.fSWBIkQJ7kuXLuFLy0kUvW8Q/wllrg7xs/RgKvOrynBli', '082115730185', 'Kp. Cikuya, RT.023, RW.003\r\nLuyubakti, Puspahiang\r\n46471', 'default_profile.png', 'active', '2025-01-31 14:25:04', '2025-01-31 14:25:04', 'users');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indeks untuk tabel `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indeks untuk tabel `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indeks untuk tabel `loan_logs`
--
ALTER TABLE `loan_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `deleted_by` (`deleted_by`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT untuk tabel `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT untuk tabel `loan_logs`
--
ALTER TABLE `loan_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- Ketidakleluasaan untuk tabel `fines`
--
ALTER TABLE `fines`
  ADD CONSTRAINT `fines_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fines_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `loan_logs`
--
ALTER TABLE `loan_logs`
  ADD CONSTRAINT `loan_logs_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
