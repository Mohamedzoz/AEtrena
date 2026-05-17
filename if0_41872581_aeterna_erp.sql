-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql113.infinityfree.com
-- Generation Time: May 09, 2026 at 06:30 PM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41872581_aeterna_erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('sign_in','sign_out') NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `is_inside_location` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `action`, `latitude`, `longitude`, `is_inside_location`, `created_at`) VALUES
(9, 8, 'sign_out', '30.06511002', '31.27226869', 0, '2026-05-09 17:45:00'),
(10, 8, 'sign_in', '30.02881020', '31.10744550', 0, '2026-05-09 18:01:45');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `social_media` varchar(255) DEFAULT NULL,
  `lead_source` varchar(100) DEFAULT 'Direct',
  `username` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `document_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `customer_code`, `name`, `phone`, `address`, `social_media`, `lead_source`, `username`, `password_hash`, `document_url`, `created_at`) VALUES
(1, 'CUST-1001', 'Mohamed Elsayed', '01117442068', 'Building Maghraby Plaza 94', '', 'Direct', NULL, NULL, NULL, '2026-05-09 09:33:56'),
(2, 'CUST-1002', 'شركة النيل للتطوير', '01002233445', 'شارع التسعين، التجمع الخامس', '@nile_dev', 'Facebook', NULL, NULL, NULL, '2026-05-09 11:12:53'),
(3, 'CUST-1003', 'فيلا مدحت القاضي', '01223344556', 'كومباوند سوديك، الشيخ زايد', '', 'Recommendation', NULL, NULL, NULL, '2026-05-09 11:12:53'),
(4, 'CUST-1004', 'مطعم وكافيه أوريكا', '01556677889', 'المعادي، شارع 9', '@oreka_cafe', 'Instagram', NULL, NULL, NULL, '2026-05-09 11:12:53'),
(5, 'CUST-1005', 'مجموعة الفطيم العقارية', '01011223344', 'كايرو فيستيفال سيتي', '@alfuttaim', 'Direct', NULL, NULL, NULL, '2026-05-09 11:16:52'),
(6, 'CUST-1006', 'دكتور وائل غنيم', '01144556677', 'القطامية هايتس - فيلا 45', '', 'Recommendation', NULL, NULL, NULL, '2026-05-09 11:16:52'),
(7, 'CUST-1007', 'مكتبة الشروق', '01288990011', 'وسط البلد - شارع طلعت حرب', '@shorouk', 'Facebook', NULL, NULL, NULL, '2026-05-09 11:16:52'),
(8, 'CUST-1008', 'شركة اوراسكوم للانشاءات', '01599887766', 'نايل سيتي تاورز', '', 'Direct', NULL, NULL, NULL, '2026-05-09 11:16:52'),
(9, 'CUST-1009', 'المهندس شريف فوزي', '01055443322', 'الرحاب - المرحلة التاسعة', '', 'Instagram', NULL, NULL, NULL, '2026-05-09 11:16:52'),
(10, 'CUST-1010', 'فندق ماريوت الزمالك', '01122334455', 'الزمالك - شارع سراي الجزيرة', '@marriott', 'Direct', NULL, NULL, NULL, '2026-05-09 11:16:52'),
(11, 'CUST-1011', 'كافيه ومطعم سيلانترو', '01099887755', 'مصر الجديدة - ميدان تريومف', '@cilantro', 'Facebook', NULL, NULL, NULL, '2026-05-09 11:16:52'),
(12, 'CUST-12', 'عباس الضبع', '01117442068', 'Building Maghraby Plaza 94', '', 'Direct', 'abaas', '$2y$10$z/MGGO1zKJNX8fHJVJB4vOhbhIeRyQWtb1.f8aZ2ce6qdRqVKGhJi', NULL, '2026-05-09 11:59:37');

-- --------------------------------------------------------

--
-- Table structure for table `customer_files`
--

CREATE TABLE `customer_files` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_files`
--

INSERT INTO `customer_files` (`id`, `customer_id`, `file_name`, `file_path`, `file_type`, `uploaded_by`, `uploaded_at`) VALUES
(1, 1, 'Contract_Final.pdf', 'uploads/customers/1/contract.pdf', 'pdf', 1, '2026-05-09 11:12:53'),
(2, 2, 'Site_Plan.jpg', 'uploads/customers/2/plan.jpg', 'image', 5, '2026-05-09 11:12:53'),
(3, 12, 'logoAefavico.png', 'uploads/customers/عباس الضبع/General_Files/1778328667_logoAefavico.png', 'image/png', 8, '2026-05-09 12:11:07'),
(4, 12, 'logoAefavico.png', 'uploads/customers/عباس الضبع/General_Files/1778328699_logoAefavico.png', 'image/png', 8, '2026-05-09 12:11:39');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `category_id`, `amount`, `description`, `expense_date`, `created_at`, `created_by`) VALUES
(1, 1, '10.00', 'شاي', '2026-05-09', '2026-05-09 09:39:45', NULL),
(2, 1, '10.00', 'شاي', '2026-05-09', '2026-05-09 09:41:40', 1),
(3, 1, '10.00', 'شاي', '2026-05-09', '2026-05-09 09:41:46', 1),
(4, 1, '10.00', 'شاي', '2026-05-09', '2026-05-09 09:42:34', 1),
(5, 3, '20000.00', 'انتقالات', '2026-05-09', '2026-05-09 09:43:38', 1),
(6, 4, '7000.00', 'ضرائب الجيزة', '2026-05-09', '2026-05-09 09:50:38', 1),
(7, 2, '1200.00', 'فاتورة إنترنت المكتب لشهر مايو', '2026-05-08', '2026-05-09 11:12:53', 4),
(8, 1, '250.00', 'أدوات كتابية وأوراق طباعة', '2026-05-09', '2026-05-09 11:12:53', 1),
(9, 3, '500.00', 'بنزين سيارة مهندس الموقع', '2026-05-09', '2026-05-09 11:12:53', 6),
(10, 2, '4500.00', 'فاتورة الكهرباء - شهر ابريل', '2026-05-01', '2026-05-09 11:16:52', 12),
(11, 1, '850.00', 'طلبية نسكافيه وشاي وسكر للمكتب', '2026-05-02', '2026-05-09 11:16:52', 11),
(12, 3, '1200.00', 'انتقالات فريق المهندسين لموقع العاصمة', '2026-05-03', '2026-05-09 11:16:52', 10),
(13, 4, '15000.00', 'ضريبة القيمة المضافة - الربع الأول', '2026-05-04', '2026-05-09 11:16:52', 12),
(14, 1, '300.00', 'تصليح ماكينة القهوة', '2026-05-05', '2026-05-09 11:16:52', 11),
(15, 2, '600.00', 'اشتراك الانترنت فايبر للمكتب', '2026-05-06', '2026-05-09 11:16:52', 1),
(16, 3, '200.00', 'توصيل عينات رخام لموقع القطامية', '2026-05-07', '2026-05-09 11:16:52', 13);

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`) VALUES
(1, 'نثريات وبوفيه'),
(2, 'فواتير (كهرباء وانترنت)'),
(3, 'انتقالات ومواصلات'),
(4, 'ضرائب');

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('Leave','Permission','Occasion') NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `is_vat_included` tinyint(1) DEFAULT 0,
  `vat_amount` decimal(15,2) DEFAULT 0.00,
  `type` enum('Down Payment','Milestone','Final') NOT NULL,
  `status` enum('Pending','Paid','Late') DEFAULT 'Paid',
  `due_date` date DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `project_id`, `amount`, `is_vat_included`, `vat_amount`, `type`, `status`, `due_date`, `paid_at`, `created_by`) VALUES
(1, 1, '100000.00', 1, '14000.00', 'Down Payment', 'Paid', '2026-05-01', '2026-05-09 11:12:53', 4),
(2, 2, '300000.00', 1, '42000.00', 'Down Payment', 'Paid', '2026-05-05', '2026-05-09 11:12:53', 4),
(3, 1, '50000.00', 0, '0.00', 'Milestone', 'Pending', '2026-06-01', '2026-05-09 11:12:53', 4),
(4, 5, '500000.00', 1, '70000.00', 'Down Payment', 'Paid', '2026-05-01', '2026-05-09 11:16:52', 12),
(5, 6, '200000.00', 1, '28000.00', 'Down Payment', 'Paid', '2026-05-02', '2026-05-09 11:16:52', 12),
(6, 7, '150000.00', 0, '0.00', 'Milestone', 'Late', '2026-04-30', '2026-05-09 11:16:52', 12),
(7, 11, '100000.00', 1, '14000.00', 'Down Payment', 'Paid', '2026-05-05', '2026-05-09 11:16:52', 12),
(8, 9, '50000.00', 0, '0.00', 'Final', 'Pending', '2026-05-20', '2026-05-09 11:16:52', 12),
(9, 12, '5000.00', 0, '0.00', 'Milestone', 'Paid', NULL, '2026-05-09 12:26:34', 8),
(10, 12, '200.00', 0, '0.00', 'Milestone', 'Paid', '2026-05-14', '2026-05-09 12:26:55', 8),
(11, 13, '3000.00', 0, '0.00', 'Down Payment', 'Paid', NULL, '2026-05-09 17:04:58', 8);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `stage` int(11) DEFAULT 1 COMMENT '1: معاينة, 2: تصميم مبدئي, 3: تعاقد, 4: رسومات تنفيذية, 5: تصنيع, 6: توريد, 7: تركيب وتسليم, 8: فيدباك',
  `total_value` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `customer_id`, `title`, `stage`, `total_value`, `created_at`) VALUES
(1, 1, 'تشطيب شقة محمد السيد', 3, '450000.00', '2026-05-09 11:12:53'),
(2, 2, 'تجهيز مقر شركة النيل', 5, '1200000.00', '2026-05-09 11:12:53'),
(3, 3, 'لاندسكيب فيلا الشيخ زايد', 1, '150000.00', '2026-05-09 11:12:53'),
(4, 4, 'ديكورات مطعم أوريكا', 2, '600000.00', '2026-05-09 11:12:53'),
(5, 5, 'تصميم لاندسكيب المرحلة الرابعة', 2, '2500000.00', '2026-05-09 11:16:52'),
(6, 6, 'تشطيب داخلي مودرن - فيلا القطامية', 4, '850000.00', '2026-05-09 11:16:52'),
(7, 7, 'تجديد فرع مكتبة الشروق بالتجمع', 6, '450000.00', '2026-05-09 11:16:52'),
(8, 10, 'ديكورات قاعة المؤتمرات الرئيسية', 1, '1200000.00', '2026-05-09 11:16:52'),
(9, 11, 'تجهيز مطبخ فرع تريومف الجديد', 5, '350000.00', '2026-05-09 11:16:52'),
(10, 8, 'رسومات تنفيذية لمشروع العاصمة', 3, '150000.00', '2026-05-09 11:16:52'),
(11, 9, 'نظام سمارت هوم متكامل - شقة الرحاب', 7, '280000.00', '2026-05-09 11:16:52'),
(12, 12, 'dressing room', 4, '14000.00', '2026-05-09 12:24:51'),
(13, 12, 'مطبخ مكتب التجمع ', 2, '25350.00', '2026-05-09 17:03:47');

-- --------------------------------------------------------

--
-- Table structure for table `project_tasks`
--

CREATE TABLE `project_tasks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `stage` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_tasks`
--

INSERT INTO `project_tasks` (`id`, `project_id`, `stage`, `task_name`, `is_completed`, `completed_by`, `completed_at`) VALUES
(1, 1, 1, 'المعاينة الأولية ومقاسات الشقة', 1, 6, '2026-05-09 11:12:53'),
(2, 1, 2, 'تصميم المطبخ والحمامات 3D', 1, 5, '2026-05-09 11:12:53'),
(3, 2, 5, 'شراء وحدات الإضاءة المركزية', 0, NULL, NULL),
(4, 4, 2, 'رسم اللوجو وتوزيع الإضاءة', 0, NULL, NULL),
(5, 5, 2, 'رسم المساقط الأفقية للاندسكيب', 1, 8, '2026-05-09 11:16:52'),
(6, 6, 4, 'اعتماد عينات السيراميك والبورسلين', 1, 14, '2026-05-09 11:16:52'),
(7, 11, 7, 'تركيب وبرمجة الحساسات الذكية', 0, NULL, NULL),
(8, 7, 6, 'تركيب الأرفف والاضاءة الداخلية', 0, NULL, NULL),
(9, 12, 1, 'رفع المقاسات', 1, 8, '2026-05-09 12:25:05'),
(10, 12, 1, 'تصوير الموقع', 1, 8, '2026-05-09 12:25:07'),
(11, 12, 2, 'مراجعة متطلبات المرحلة', 1, 8, '2026-05-09 12:25:17'),
(12, 12, 3, 'مراجعة متطلبات المرحلة', 1, 8, '2026-05-09 12:25:59'),
(13, 12, 4, 'مراجعة متطلبات المرحلة', 0, NULL, NULL),
(14, 13, 1, 'رفع المقاسات', 1, 8, '2026-05-09 17:04:07'),
(15, 13, 1, 'تصوير الموقع', 1, 8, '2026-05-09 17:04:12'),
(16, 13, 2, 'مراجعة متطلبات المرحلة', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `site_reports`
--

CREATE TABLE `site_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `report_text` text NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_reports`
--

INSERT INTO `site_reports` (`id`, `user_id`, `project_id`, `report_text`, `photo_path`, `latitude`, `longitude`, `created_at`) VALUES
(1, 6, 1, 'تم الانتهاء من أعمال المحارة في الريسبشن وجاري العمل في غرف النوم.', 'uploads/reports/site1.jpg', '30.04440000', '31.23570000', '2026-05-09 11:12:53'),
(2, 6, 2, 'استلام خامات السباكة ومطابقتها للمواصفات.', 'uploads/reports/site2.jpg', '30.05000000', '31.24000000', '2026-05-09 11:12:53'),
(3, 8, 12, 'تم تركيب القطعه', 'uploads/reports/1778330430_image.jpg', '30.11410172', '31.19923789', '2026-05-09 12:40:30'),
(4, 1, 12, 'تركيب الوحده', 'uploads/reports/1778343186_logoAE.png', '30.11317600', '31.19899600', '2026-05-09 16:13:07'),
(5, 1, 12, 'تركيب الوحده', 'uploads/reports/1778343254_logoAE.png', '30.11317600', '31.19899600', '2026-05-09 16:14:14'),
(6, 1, 12, 'تركيب الوحده', 'uploads/reports/1778343289_logoAE.png', '30.11317600', '31.19899600', '2026-05-09 16:14:50'),
(7, 1, 12, 'تركيب الوحده', 'uploads/reports/1778343392_logoAE.png', '30.11317600', '31.19899600', '2026-05-09 16:16:33'),
(8, 1, 12, 'تركيب الوحده', 'uploads/reports/1778343423_logoAE.png', '30.11317600', '31.19899600', '2026-05-09 16:17:04');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 3, 'تسجيل الخروج', 'قام Zoz بتسجيل الخروج من النظام.', '::1', '2026-05-09 11:59:57'),
(2, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '::1', '2026-05-09 12:00:53'),
(3, NULL, 'دخول عميل', 'قام العميل عباس الضبع بتسجيل الدخول للنظام.', '::1', '2026-05-09 12:06:01'),
(4, NULL, 'دخول عميل', 'قام العميل عباس الضبع بتسجيل الدخول للنظام.', '::1', '2026-05-09 12:06:41'),
(5, 8, 'تحديث الإعدادات', 'قام المدير بتحديث إعدادات النظام الرئيسية.', '::1', '2026-05-09 12:07:40'),
(6, 8, 'تحديث الإعدادات', 'قام المدير بتحديث إعدادات النظام الرئيسية.', '::1', '2026-05-09 12:08:02'),
(7, 8, 'تحديث الإعدادات', 'قام المدير بتحديث إعدادات النظام الرئيسية.', '::1', '2026-05-09 12:09:57'),
(8, 8, 'تحديث الإعدادات', 'قام المدير بتحديث إعدادات النظام الرئيسية.', '::1', '2026-05-09 12:11:54'),
(9, 2, 'تسجيل الدخول', 'قام محمود إبراهيم بتسجيل الدخول للنظام.', '::1', '2026-05-09 12:15:04'),
(10, 2, 'تسجيل الخروج', 'قام محمود إبراهيم بتسجيل الخروج من النظام.', '::1', '2026-05-09 12:16:41'),
(11, NULL, 'دخول عميل', 'قام العميل عباس الضبع بتسجيل الدخول للنظام.', '::1', '2026-05-09 12:16:51'),
(12, 2, 'تسجيل الدخول', 'قام محمود إبراهيم بتسجيل الدخول للنظام.', '::1', '2026-05-09 12:21:39'),
(13, 2, 'تسجيل الخروج', 'قام محمود إبراهيم بتسجيل الخروج من النظام.', '::1', '2026-05-09 12:25:33'),
(14, NULL, 'دخول عميل', 'قام العميل عباس الضبع بتسجيل الدخول للنظام.', '::1', '2026-05-09 12:25:43'),
(15, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '::1', '2026-05-09 12:29:08'),
(16, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '::1', '2026-05-09 12:29:11'),
(17, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '::1', '2026-05-09 12:31:37'),
(18, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '::1', '2026-05-09 12:31:43'),
(19, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 12:35:25'),
(20, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 12:35:31'),
(21, NULL, 'دخول عميل', 'قام العميل عباس الضبع بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 12:35:45'),
(22, NULL, 'دخول عميل', 'قام العميل عباس الضبع بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 12:37:46'),
(23, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 12:38:35'),
(24, 8, 'تحديث الإعدادات', 'قام المدير بتحديث إعدادات النظام الرئيسية.', '154.184.23.212', '2026-05-09 12:58:59'),
(25, 5, 'تسجيل الدخول', 'قام نورهان علي بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 13:08:05'),
(26, 5, 'تسجيل الخروج', 'قام نورهان علي بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 13:12:49'),
(27, 1, 'تسجيل الدخول', 'قام محمد السيد بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 13:13:06'),
(28, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 15:10:08'),
(29, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 15:17:12'),
(30, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 15:51:40'),
(31, 1, 'تسجيل الدخول', 'قام محمد السيد بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:02:19'),
(32, 1, 'تسجيل الخروج', 'قام محمد السيد بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 16:02:44'),
(33, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:02:48'),
(34, 1, 'تسجيل الدخول', 'قام محمد السيد بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:05:08'),
(35, 1, 'تسجيل الخروج', 'قام محمد السيد بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 16:08:13'),
(36, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:08:16'),
(37, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 16:08:21'),
(38, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 16:10:18'),
(39, 1, 'تسجيل الدخول', 'قام محمد السيد بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:10:25'),
(40, 1, 'تسجيل الدخول', 'قام محمد السيد بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:11:27'),
(41, 1, 'حذف من attendance', 'تم حذف سجل رقم 1 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:22'),
(42, 1, 'حذف سجل حضور', 'تم حذف سجل حضور/انصراف #1 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:22'),
(43, 1, 'حذف من attendance', 'تم حذف سجل رقم 2 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:26'),
(44, 1, 'حذف سجل حضور', 'تم حذف سجل حضور/انصراف #2 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:26'),
(45, 1, 'حذف من attendance', 'تم حذف سجل رقم 3 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:30'),
(46, 1, 'حذف سجل حضور', 'تم حذف سجل حضور/انصراف #3 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:30'),
(47, 1, 'حذف من attendance', 'تم حذف سجل رقم 4 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:41'),
(48, 1, 'حذف سجل حضور', 'تم حذف سجل حضور/انصراف #4 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:41'),
(49, 1, 'حذف من attendance', 'تم حذف سجل رقم 8 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:44'),
(50, 1, 'حذف سجل حضور', 'تم حذف سجل حضور/انصراف #8 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:44'),
(51, 1, 'حذف من attendance', 'تم حذف سجل رقم 7 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:47'),
(52, 1, 'حذف سجل حضور', 'تم حذف سجل حضور/انصراف #7 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:47'),
(53, 1, 'حذف من attendance', 'تم حذف سجل رقم 6 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:49'),
(54, 1, 'حذف سجل حضور', 'تم حذف سجل حضور/انصراف #6 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:49'),
(55, 1, 'حذف من attendance', 'تم حذف سجل رقم 5 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:52'),
(56, 1, 'حذف سجل حضور', 'تم حذف سجل حضور/انصراف #5 ونقله لسلة المهملات.', '197.60.22.156', '2026-05-09 16:15:52'),
(57, 1, 'نسخة احتياطية', 'تم إنشاء نسخة احتياطية كاملة: aeterna_backup_2026-05-09_12-23-42.zip (30 ملف)', '197.60.22.156', '2026-05-09 16:23:43'),
(58, 1, 'تسجيل الخروج', 'قام محمد السيد بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 16:30:45'),
(59, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:30:49'),
(60, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 16:35:53'),
(61, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:35:58'),
(62, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 16:39:14'),
(63, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:39:29'),
(64, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 16:43:37'),
(65, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:43:48'),
(66, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 16:47:42'),
(67, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 16:47:53'),
(68, 1, 'تحديث الإعدادات', 'قام المدير بتحديث إعدادات النظام الرئيسية.', '197.60.22.156', '2026-05-09 16:52:50'),
(69, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 17:00:29'),
(70, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 17:00:34'),
(71, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 17:01:50'),
(72, NULL, 'دخول عميل', 'قام العميل عباس الضبع بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 17:02:17'),
(73, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 17:05:49'),
(74, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 17:05:53'),
(75, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 17:05:59'),
(76, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 17:11:53'),
(77, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 17:12:05'),
(78, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '102.56.219.131', '2026-05-09 17:43:04'),
(79, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '102.56.219.131', '2026-05-09 17:45:30'),
(80, 8, 'تسجيل خروج تلقائي', 'تم تسجيل الخروج تلقائياً بعد 15 دقيقة من عدم النشاط.', '154.184.23.212', '2026-05-09 17:50:52'),
(81, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '154.184.23.212', '2026-05-09 17:50:58'),
(82, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '196.219.39.50', '2026-05-09 18:00:29'),
(83, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '156.208.255.121', '2026-05-09 18:01:32'),
(84, 8, 'تسجيل خروج تلقائي', 'تم تسجيل الخروج تلقائياً بعد 15 دقيقة من عدم النشاط.', '154.184.224.57', '2026-05-09 19:57:51'),
(85, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '196.159.65.1', '2026-05-09 21:43:38'),
(86, 1, 'تسجيل خروج تلقائي', 'تم تسجيل الخروج تلقائياً بعد 15 دقيقة من عدم النشاط.', '197.60.22.156', '2026-05-09 22:13:47'),
(87, 8, 'تسجيل الدخول', 'قام Mohamed Elsayed  بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 22:14:43'),
(88, 8, 'تسجيل الخروج', 'قام Mohamed Elsayed  بتسجيل الخروج من النظام.', '197.60.22.156', '2026-05-09 22:19:21'),
(89, 1, 'تسجيل الدخول', 'قام محمد السيد بتسجيل الدخول للنظام.', '197.60.22.156', '2026-05-09 22:21:02'),
(90, 1, 'تحديث الإعدادات', 'قام المدير بتحديث إعدادات النظام الرئيسية.', '197.60.22.156', '2026-05-09 22:29:33');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('company_address', 'Down Town Mall, No. S3, 1st Floor, 5th Settlement, New Cairo, Cairo, Egypt'),
('company_email', 'info@aeternacabinetry.com'),
('company_phone', '+201090001055'),
('currency', 'EGP'),
('facebook_link', 'https://www.facebook.com/AeternaCabinetry'),
('favicon_path', 'uploads/settings/favicon.png'),
('gps_mandatory', '1'),
('hotline', ''),
('logo_path', 'uploads/settings/logo.png'),
('primary_font', 'Cairo'),
('projects_mandatory_checklist', '1'),
('session_timeout', '15'),
('system_name', 'Aeterna'),
('tax_rate', '14'),
('whatsapp_number', '+201090001055'),
('workplace_lat', '31.432434'),
('workplace_lng', '30.00123'),
('workplace_radius', '50');

-- --------------------------------------------------------

--
-- Table structure for table `trash`
--

CREATE TABLE `trash` (
  `id` int(11) NOT NULL,
  `table_name` varchar(100) NOT NULL,
  `original_id` int(11) NOT NULL,
  `data_json` text NOT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trash`
--

INSERT INTO `trash` (`id`, `table_name`, `original_id`, `data_json`, `deleted_by`, `deleted_at`) VALUES
(1, 'attendance', 1, '{\"id\":1,\"user_id\":8,\"action\":\"sign_in\",\"latitude\":\"30.11317600\",\"longitude\":\"31.19899600\",\"is_inside_location\":1,\"created_at\":\"2026-05-09 05:10:10\"}', 1, '2026-05-09 16:15:22'),
(2, 'attendance', 2, '{\"id\":2,\"user_id\":2,\"action\":\"sign_in\",\"latitude\":\"30.11317600\",\"longitude\":\"31.19899600\",\"is_inside_location\":1,\"created_at\":\"2026-05-09 05:22:19\"}', 1, '2026-05-09 16:15:26'),
(3, 'attendance', 3, '{\"id\":3,\"user_id\":8,\"action\":\"sign_in\",\"latitude\":\"30.11310053\",\"longitude\":\"31.19937877\",\"is_inside_location\":1,\"created_at\":\"2026-05-09 05:57:19\"}', 1, '2026-05-09 16:15:30'),
(4, 'attendance', 4, '{\"id\":4,\"user_id\":8,\"action\":\"sign_in\",\"latitude\":\"30.11310053\",\"longitude\":\"31.19937877\",\"is_inside_location\":1,\"created_at\":\"2026-05-09 05:57:29\"}', 1, '2026-05-09 16:15:41'),
(5, 'attendance', 8, '{\"id\":8,\"user_id\":1,\"action\":\"sign_in\",\"latitude\":\"30.11317600\",\"longitude\":\"31.19899600\",\"is_inside_location\":1,\"created_at\":\"2026-05-09 09:12:30\"}', 1, '2026-05-09 16:15:44'),
(6, 'attendance', 7, '{\"id\":7,\"user_id\":5,\"action\":\"sign_in\",\"latitude\":\"30.11413130\",\"longitude\":\"31.19932270\",\"is_inside_location\":0,\"created_at\":\"2026-05-09 06:08:42\"}', 1, '2026-05-09 16:15:47'),
(7, 'attendance', 6, '{\"id\":6,\"user_id\":8,\"action\":\"sign_out\",\"latitude\":\"30.11198726\",\"longitude\":\"31.19950779\",\"is_inside_location\":0,\"created_at\":\"2026-05-09 05:59:58\"}', 1, '2026-05-09 16:15:49'),
(8, 'attendance', 5, '{\"id\":5,\"user_id\":8,\"action\":\"sign_out\",\"latitude\":\"30.11296947\",\"longitude\":\"31.19930282\",\"is_inside_location\":1,\"created_at\":\"2026-05-09 05:58:06\"}', 1, '2026-05-09 16:15:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Manager','Accountant','Senior Design','Junior Design','Secretary','Site Manager','Site Engineer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_code`, `name`, `email`, `username`, `password_hash`, `role`, `created_at`) VALUES
(1, 'EMP-1001', 'محمد السيد', 'mohamed.e.abdelrehem@te.eg', 'Admin', '$2y$10$Xzr50Dz0LiRX84uReqKvTO.9PdbgX3rqN5TTOsZ63BNPi5VvN7JdG', 'Admin', '2026-05-09 11:53:20'),
(2, 'EMP-1002', 'محمود إبراهيم', 'mahmoud.ibrahim@aeterna.com', 'mahmoud_acc', '$2y$10$26lsdE315TTpNVTxj70QG.WXRn6c7WK7D77s6JTsA1idx33ySTiZu', 'Accountant', '2026-05-09 11:53:20'),
(3, 'EMP-1003', 'إيمان يوسف', 'eman.youssef@aeterna.com', 'eman_senior', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Senior Design', '2026-05-09 11:53:20'),
(4, 'EMP-1004', 'أحمد كمال', 'ahmed.kamal@aeterna.com', 'ahmed_junior', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Junior Design', '2026-05-09 11:53:20'),
(5, 'EMP-1005', 'نورهان علي', 'nourhan.ali@aeterna.com', 'nourhan_sec', '$2y$10$M.NV2XzIh1.4myAlpDjKQ.oFuxl6u3KLBVRSO4Dquw9j3eFbyXj22', 'Secretary', '2026-05-09 11:53:20'),
(6, 'EMP-1006', 'ياسر جلال', 'yasser.galal@aeterna.com', 'yasser_site', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Site Manager', '2026-05-09 11:53:20'),
(7, 'EMP-1007', 'هاني فوزي', 'hani.fawzy@aeterna.com', 'hani_eng', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Site Engineer', '2026-05-09 11:53:20'),
(8, 'EMP-8', 'Mohamed Elsayed ', '', 'Zoz', '$2y$10$qkBSp2mYPGoDL7fx7K3q9uJecpgARav3.5gSOvkOj.IMExt1Pre0m', 'Manager', '2026-05-09 11:54:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `customer_files`
--
ALTER TABLE `customer_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `project_tasks`
--
ALTER TABLE `project_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `completed_by` (`completed_by`);

--
-- Indexes for table `site_reports`
--
ALTER TABLE `site_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `trash`
--
ALTER TABLE `trash`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deleted_by` (`deleted_by`);

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
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `customer_files`
--
ALTER TABLE `customer_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `project_tasks`
--
ALTER TABLE `project_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `site_reports`
--
ALTER TABLE `site_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `trash`
--
ALTER TABLE `trash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_files`
--
ALTER TABLE `customer_files`
  ADD CONSTRAINT `customer_files_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_files_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `site_reports`
--
ALTER TABLE `site_reports`
  ADD CONSTRAINT `site_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `site_reports_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
