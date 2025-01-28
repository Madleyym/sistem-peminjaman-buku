-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2025 at 02:38 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
-- Table structure for table `activity_logs`
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
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `action_type`, `ip_address`, `created_at`) VALUES
(1, 101, 'Login', '', '192.168.1.1', '2025-01-27 03:00:00'),
(2, 102, 'View Profile', '', '192.168.1.2', '2025-01-27 03:05:00'),
(3, 103, 'Edit Profile', '', '192.168.1.3', '2025-01-27 03:10:00'),
(4, 101, 'Logout', '', '192.168.1.1', '2025-01-27 03:15:00'),
(5, 104, 'Delete Account', '', '192.168.1.4', '2025-01-27 03:20:00'),
(6, 105, 'Upload File', '', '192.168.1.5', '2025-01-27 03:25:00'),
(7, 106, 'Download Report', '', '192.168.1.6', '2025-01-27 03:30:00'),
(8, 107, 'Reset Password', '', '192.168.1.7', '2025-01-27 03:35:00');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
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
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `email`, `last_login`, `created_at`, `created_by`, `nik`, `name`) VALUES
(7, 'admin', '$2y$10$xR6erGJECO2J2zqGL6vJ/ucjCut6YmqtbjzfFvOdga4jIXzEUDEtW', 'admin@gmail.com', '2025-01-28 06:21:31', '2025-01-27 10:45:20', NULL, '1111111111111111', 'Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `books`
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
  `shelf_location` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `publisher`, `year_published`, `isbn`, `category`, `total_quantity`, `available_quantity`, `cover_image`, `description`, `created_at`, `shelf_location`) VALUES
(9, 'Artificial Intelligence: A Modern Approach', 'Stuart Russell', 'Pearson', '2001', '978-0136042594', NULL, 5, 5, '6797897037c8f_pepe.jpg', 'The standard textbook for artificial intelligence.', '2025-01-24 10:17:09', '12'),
(10, 'Cracking the Coding Interview', 'Gayle Laakmann McDowell', 'CareerCup', '2015', '978-0984782857', '2', 12, 12, '6798323320b89_index.jpeg', '189 programming questions and solutions.', '2025-01-24 10:17:09', '12'),
(11, 'The Mythical Man-Month', 'Frederick P. Brooks Jr.', 'Addison-Wesley', '1995', '978-0201835953', NULL, 3, 2, 'mythical_man_month.jpg', 'Essays on software engineering.', '2025-01-24 10:17:09', ''),
(12, 'Deep Learning', 'Ian Goodfellow', 'MIT Press', '2016', '978-0262035613', NULL, 9, 8, 'deep_learning.jpg', 'An introduction to deep learning.', '2025-01-24 10:17:09', ''),
(13, 'Python Crash Course', 'Eric Matthes', 'No Starch Press', '2019', '978-1593279288', NULL, 8, 8, 'python_crash_course.jpg', 'A hands-on project-based introduction to programming in Python.', '2025-01-24 10:17:09', ''),
(14, 'Head First Design Patterns', 'Eric Freeman', 'O\'Reilly Media', '2004', '978-0596007126', NULL, 5, 5, 'head_first_design_patterns.jpg', 'A brain-friendly guide to design patterns.', '2025-01-24 10:17:09', ''),
(15, 'Learning SQL', 'Alan Beaulieu', 'O\'Reilly Media', '2009', '978-0596520833', NULL, 7, 6, 'learning_sql.jpg', 'Master the fundamentals of SQL programming.', '2025-01-24 10:17:09', ''),
(16, 'JavaScript: The Good Parts', 'Douglas Crockford', 'O\'Reilly Media', '2008', '978-0596517741', NULL, 6, 6, 'javascript_good_parts.jpg', 'Unearth the best features of JavaScript.', '2025-01-24 10:17:09', ''),
(17, 'Computer Networking: A Top-Down Approach', 'James F. Kurose', 'Pearson', '2020', '978-0135928608', NULL, 5, 4, 'computer_networking.jpg', 'A comprehensive textbook on computer networking.', '2025-01-24 10:17:09', ''),
(18, 'The Art of Computer Programming', 'Donald E. Knuth', 'Addison-Wesley', '1997', '978-0201896831', NULL, 3, 3, 'art_of_programming.jpg', 'A classic work on algorithms and programming.', '2025-01-24 10:17:09', ''),
(19, 'Data Structures and Algorithm Analysis', 'Mark Allen Weiss', 'Pearson', '2009', '978-0132847378', NULL, 2, 1, '6797895ce0023_pepe.jpg', 'A comprehensive guide to data structures and algorithms.', '2025-01-24 10:17:09', '2'),
(20, 'Refactoring', 'Martin Fowler', 'Addison-Wesley', '2018', '978-0134757590', NULL, 6, 5, 'refactoring.jpg', 'Improving the design of existing code.', '2025-01-24 10:17:09', ''),
(21, 'Code Complete', 'Steve McConnell', 'Microsoft Press', '2004', '978-0735619678', NULL, 10, 9, 'code_complete.jpg', 'A practical handbook of software construction.', '2025-01-24 10:17:09', ''),
(22, 'Structure and Interpretation of Computer Programs', 'Harold Abelson', 'MIT Press', '1996', '978-0262510875', NULL, 5, 4, 'sicp.jpg', 'A foundational book in computer science.', '2025-01-24 10:17:09', '');

-- --------------------------------------------------------

--
-- Table structure for table `borrowings`
--

CREATE TABLE `borrowings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date NOT NULL,
  `return_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Fiksi', 'Kategori buku fiksi seperti novel dan cerita pendek.', '2025-01-26 13:41:29', '2025-01-26 13:41:29'),
(2, 'Non-Fiksi', 'Kategori buku non-fiksi yang berisi pengetahuan nyata.', '2025-01-26 13:41:29', '2025-01-26 13:41:29'),
(3, 'Ilmiah', 'Kategori buku yang berfokus pada ilmu pengetahuan dan teknologi.', '2025-01-26 13:41:29', '2025-01-26 13:41:29');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `created_at`) VALUES
(1, 'Tryanda Anggita Suwito', '2203010343@unper.ac.id', 'aku ajsdjaiohdhuciasdbfcuisddf', '2025-01-25 04:59:13'),
(2, 'Tryanda Anggita Suwitoa', '2203010343@unper.ac.id', 'adadadadadadaadaa', '2025-01-25 05:10:49');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
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
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `user_id`, `book_id`, `loan_date`, `due_date`, `return_date`, `status`, `fine_amount`, `created_at`) VALUES
(25, 8, 9, '2025-01-27', '2025-02-10', NULL, 'active', 0.00, '2025-01-27 07:56:29'),
(27, 9, 10, '2025-01-28', '2025-02-11', NULL, 'active', 0.00, '2025-01-27 19:13:28'),
(28, 9, 12, '2025-01-28', '2025-02-11', NULL, 'active', 0.00, '2025-01-27 19:35:23'),
(29, 9, 11, '2025-01-28', '2025-02-11', NULL, 'active', 0.00, '2025-01-27 19:35:25'),
(30, 9, 15, '2025-01-28', '2025-02-11', NULL, 'active', 0.00, '2025-01-27 19:35:33'),
(31, 9, 17, '2025-01-28', '2025-02-11', NULL, 'active', 0.00, '2025-01-27 19:35:43'),
(32, 9, 22, '2025-01-28', '2025-02-11', NULL, 'active', 0.00, '2025-01-27 19:35:47'),
(33, 9, 21, '2025-01-28', '2025-02-11', NULL, 'active', 0.00, '2025-01-27 19:35:49'),
(34, 9, 20, '2025-01-28', '2025-02-11', NULL, 'active', 0.00, '2025-01-27 19:35:52'),
(35, 9, 19, '2025-01-28', '2025-02-11', NULL, 'active', 0.00, '2025-01-27 19:39:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone_number`, `address`, `profile_image`, `status`, `created_at`) VALUES
(8, 'Qian.xyz', 'kades@gmail.com', '$2y$10$OWv5Q1UPRt5PpDOjnnotGeeYfU3pD4KTb.dW/AEionqwOwCNqs1Za', '081395000225', 'Jl Pembela Tanah Air 177, Kota Tasikmalaya, Jawa Barat 46115', 'default_profile.png', 'active', '2025-01-26 06:23:41'),
(9, 'Tryanda Anggita Suwito', 'tryandaasu@gmail.com', '$2y$10$jeoIJ1jOulXKoIlDtapY.OeWcT5E.UFU6lFxv3EAg5SG6K04LfyJO', '081395000225', 'Jl Pembela Tanah Air 177, Kota Tasikmalaya, Jawa Barat 46115', 'default_profile.png', 'active', '2025-01-27 06:35:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`);

--
-- Indexes for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `borrowings`
--
ALTER TABLE `borrowings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `borrowings`
--
ALTER TABLE `borrowings`
  ADD CONSTRAINT `borrowings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `borrowings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
