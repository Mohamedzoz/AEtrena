-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2026 at 12:11 PM
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
-- Database: `aeterna_erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `stage_reference_tasks`
--

CREATE TABLE `stage_reference_tasks` (
  `id` int(11) NOT NULL,
  `stage` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stage_reference_tasks`
--

INSERT INTO `stage_reference_tasks` (`id`, `stage`, `task_name`) VALUES
(1, 1, 'رفع المقاسات'),
(2, 1, 'تصوير الموقع'),
(3, 1, 'مقاسات وأنواع الاجهزة الكهربائية المبدأية'),
(4, 1, 'متابعة تنفيذ تعديلات الجوب اوردر اللازمة في الموقع'),
(5, 1, 'رسم الرفع الموقعي 2d و 3d'),
(6, 2, 'الموافقة على التصميم مع العميل'),
(7, 2, 'اختيار الخامات بالأكواد والألوان والصور'),
(8, 2, 'تأكيد التكلفة النهائية'),
(9, 3, 'توقيع العقد والمرفقات'),
(10, 3, 'تحصيل الدفعة الأولى'),
(11, 4, 'الحصول على المقاسات والانواع النهائية للاجهزة من العميل'),
(12, 4, 'إعداد الـ Job Order ومراجعة المواصفات والتوقيع من المدير'),
(13, 4, 'التوقيع من العميل'),
(14, 4, 'تحصيل الدفعة الثانية'),
(15, 4, 'تبليغ مهندس التنفيذ بمتابعة تعديلات الموقع مع المسؤول'),
(16, 5, 'تحصيل الدفعة الثالثة'),
(17, 5, 'استلام المطبخ على ارض المصنع'),
(18, 5, 'متابعة وانهاء تعاملات الموردين الخارجين'),
(19, 5, 'توثيق تنفيذ المطبخ مع العميل في حالة طلبه'),
(20, 6, 'تنسيق موع التسليم بين العميل والمورد'),
(21, 6, 'توقيع محضر استلام من الموردين'),
(22, 6, 'توقيع محضر استلام في الموقع من العميل'),
(23, 7, 'تركيب المنتج النهائي'),
(24, 7, 'تشغيل المنتح وتسليمه للعميل'),
(25, 7, 'توقيع محضر استلام نهائي من العميل'),
(26, 8, 'جمع تقييم العميل'),
(27, 8, 'أرشفة صور المشروع النهائية');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `stage_reference_tasks`
--
ALTER TABLE `stage_reference_tasks`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `stage_reference_tasks`
--
ALTER TABLE `stage_reference_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
