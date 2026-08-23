-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 23, 2026 at 04:41 PM
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
-- Database: `shelfsense`
--

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `target_role` varchar(50) NOT NULL,
  `resume_path` varchar(255) NOT NULL,
  `status` enum('pending','initial_scheduled','initial_passed','initial_failed','final_scheduled','final_passed','final_failed','screening','screening_success','screening_failed','contract_offered','contract_declined','hired') DEFAULT 'pending',
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `overtime_hours` decimal(4,2) DEFAULT 0.00,
  `status` enum('present','late','absent','leave_paid','leave_unpaid','holiday_no_work','holiday_work','rest_day') NOT NULL DEFAULT 'absent',
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_monthly_summaries`
--

CREATE TABLE `attendance_monthly_summaries` (
  `id` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `total_employees` int(11) DEFAULT 0,
  `total_weeks` int(11) DEFAULT 4,
  `overall_status` enum('draft','in_progress','sent','approved','rejected','locked') DEFAULT 'draft',
  `sent_by` int(11) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_weekly_summaries`
--

CREATE TABLE `attendance_weekly_summaries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `week_number` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `total_days` int(11) DEFAULT 0,
  `present_days` int(11) DEFAULT 0,
  `late_days` int(11) DEFAULT 0,
  `absent_days` int(11) DEFAULT 0,
  `leave_paid_days` int(11) DEFAULT 0,
  `leave_unpaid_days` int(11) DEFAULT 0,
  `rest_days` int(11) DEFAULT 0,
  `holiday_days` int(11) DEFAULT 0,
  `total_overtime_hours` decimal(5,2) DEFAULT 0.00,
  `status` enum('draft','complete','sent','approved','locked') DEFAULT 'draft',
  `dtr_image_path` varchar(255) DEFAULT NULL,
  `sent_by` int(11) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `department` varchar(20) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `allocated_budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `used_budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`id`, `department`, `month_year`, `allocated_budget`, `used_budget`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'store', '2026-08', 10000.00, 0.00, NULL, '2026-08-21 16:47:43', '2026-08-22 09:48:26'),
(2, 'hr', '2026-08', 50000.00, 0.00, NULL, '2026-08-21 16:47:43', '2026-08-21 16:47:43'),
(3, 'finance', '2026-08', 30000.00, 0.00, NULL, '2026-08-21 16:47:43', '2026-08-21 16:47:43'),
(4, 'general', '2026-08', 20000.00, 0.00, NULL, '2026-08-21 16:47:43', '2026-08-21 16:47:43');

-- --------------------------------------------------------

--
-- Table structure for table `cash_reconciliation`
--

CREATE TABLE `cash_reconciliation` (
  `id` int(11) NOT NULL,
  `store_manager_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `expected_cash` decimal(10,2) NOT NULL,
  `actual_cash` decimal(10,2) NOT NULL,
  `difference` decimal(10,2) GENERATED ALWAYS AS (`expected_cash` - `actual_cash`) STORED,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Books', NULL, 1, '2026-08-20 17:09:05'),
(2, 'School Supplies', NULL, 1, '2026-08-20 17:09:05'),
(3, 'Merchandise', NULL, 1, '2026-08-20 17:09:05'),
(4, 'Beverages', NULL, 1, '2026-08-20 17:09:05'),
(5, 'Snacks', NULL, 1, '2026-08-20 17:09:05');

-- --------------------------------------------------------

--
-- Table structure for table `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shift` enum('opening','closing','midshift') NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `job_details` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  `salary_range_min` decimal(10,2) DEFAULT NULL,
  `salary_range_max` decimal(10,2) DEFAULT NULL,
  `decision_deadline` date DEFAULT NULL,
  `offered_by` int(11) DEFAULT NULL,
  `offered_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `rest_days` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient_email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `status` enum('sent','failed') DEFAULT 'sent',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipts`
--

CREATE TABLE `goods_receipts` (
  `id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `status` enum('draft','completed') DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `goods_receipt_items`
--

CREATE TABLE `goods_receipt_items` (
  `id` int(11) NOT NULL,
  `goods_receipt_id` int(11) NOT NULL,
  `requisition_item_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interviews`
--

CREATE TABLE `interviews` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `hr_user_id` int(11) NOT NULL,
  `interview_type` enum('initial','final','contract') NOT NULL,
  `scheduled_date` datetime NOT NULL,
  `gmeet_link` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `result` enum('passed','failed','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL,
  `role` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `salary_range_min` decimal(10,2) DEFAULT NULL,
  `salary_range_max` decimal(10,2) DEFAULT NULL,
  `open_until` date NOT NULL,
  `status` enum('draft','pending_approval','approved','closed') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

CREATE TABLE `leaves` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('sick','vacation','emergency','other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_days` decimal(5,2) DEFAULT 5.00,
  `used_days` decimal(5,2) DEFAULT 0.00,
  `remaining_days` decimal(5,2) GENERATED ALWAYS AS (`total_days` - `used_days`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `change_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` enum('cash','card','gcash','paymaya','other') NOT NULL,
  `payment_reference` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('completed','voided') DEFAULT 'completed',
  `void_reason` varchar(255) DEFAULT NULL,
  `voided_by` int(11) DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `otp`, `expires_at`, `used`, `created_at`) VALUES
(4, 10, '259722', '2026-08-21 22:58:59', 0, '2026-08-21 14:43:59'),
(5, 4, '858060', '2026-08-21 22:59:51', 0, '2026-08-21 14:44:51');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `supplier_invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('bank_transfer','check','cash','other') NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `paid_by` int(11) NOT NULL,
  `paid_at` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_requests`
--

CREATE TABLE `payment_requests` (
  `id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `supplier_invoice_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `budget_checked` tinyint(4) DEFAULT 0,
  `budget_exceeded` tinyint(4) DEFAULT 0,
  `budget_exceeded_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_approval_logs`
--

CREATE TABLE `payroll_approval_logs` (
  `id` int(11) NOT NULL,
  `payroll_cycle_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `action_by` int(11) NOT NULL,
  `action_at` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_cycles`
--

CREATE TABLE `payroll_cycles` (
  `id` int(11) NOT NULL,
  `cycle_name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `payment_date` date NOT NULL,
  `total_employees` int(11) DEFAULT 0,
  `total_gross` decimal(12,2) DEFAULT 0.00,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `total_net` decimal(12,2) DEFAULT 0.00,
  `status` enum('draft','pending_approval','approved','verified','processed','cancelled') DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_entries`
--

CREATE TABLE `payroll_entries` (
  `id` int(11) NOT NULL,
  `payroll_cycle_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_working_days` int(11) DEFAULT 0,
  `attended_days` int(11) DEFAULT 0,
  `absent_days` int(11) DEFAULT 0,
  `total_overtime_hours` decimal(5,2) DEFAULT 0.00,
  `total_holiday_work_hours` decimal(5,2) DEFAULT 0.00,
  `late_minutes` int(11) DEFAULT 0,
  `monthly_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `daily_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `regular_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `overtime_pay` decimal(10,2) DEFAULT 0.00,
  `holiday_pay` decimal(10,2) DEFAULT 0.00,
  `late_deduction` decimal(10,2) DEFAULT 0.00,
  `absent_deduction` decimal(10,2) DEFAULT 0.00,
  `unpaid_leave_deduction` decimal(10,2) DEFAULT 0.00,
  `other_deductions` decimal(10,2) DEFAULT 0.00,
  `gross_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('pending','paid','hold') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pos_override_requests`
--

CREATE TABLE `pos_override_requests` (
  `id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `type` enum('price_override','void') NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `barcode` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) DEFAULT 5,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `barcode`, `name`, `description`, `category_id`, `price`, `cost`, `stock_quantity`, `reorder_level`, `image_path`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '978-0-123-45678-9', 'Sample Book', 'A sample book for testing', 1, 12.99, 8.00, 10, 5, NULL, 1, '2026-08-20 17:09:05', '2026-08-23 02:49:37'),
(2, 'BS-001', 'Sample Pen', 'A sample pen for testing', 2, 2.99, 1.20, 20, 5, NULL, 1, '2026-08-20 17:09:05', '2026-08-23 02:49:37');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(20) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `status` enum('draft','pending','approved','received','cancelled') DEFAULT 'draft',
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `received_quantity` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rejection_reasons`
--

CREATE TABLE `rejection_reasons` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `hr_user_id` int(11) NOT NULL,
  `stage` enum('initial','final','screening','contract') NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `is_rest_day` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_requisitions`
--

CREATE TABLE `store_requisitions` (
  `id` int(11) NOT NULL,
  `requisition_number` varchar(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `department` varchar(20) NOT NULL DEFAULT 'store',
  `status` enum('draft','pending_supplier','sent_to_supplier','supplier_processed','awaiting_finance_staff','awaiting_finance','finance_approved','finance_rejected','paid','shipped','completed','partial_received') DEFAULT 'draft',
  `order_date` date NOT NULL,
  `budget_month_year` varchar(7) NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_requisitions`
--

INSERT INTO `store_requisitions` (`id`, `requisition_number`, `created_by`, `supplier_id`, `department`, `status`, `order_date`, `budget_month_year`, `expected_delivery`, `subtotal`, `tax`, `total`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'REQ-2026-0001', 5, 1, 'store', 'pending_supplier', '2026-08-23', '', '2026-08-25', 155.85, 0.00, 155.85, '', '2026-08-23 05:20:15', '2026-08-23 05:20:15');

-- --------------------------------------------------------

--
-- Table structure for table `store_requisition_items`
--

CREATE TABLE `store_requisition_items` (
  `id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `store_product_id` int(11) NOT NULL,
  `supplier_product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `received_quantity` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_requisition_items`
--

INSERT INTO `store_requisition_items` (`id`, `requisition_id`, `store_product_id`, `supplier_product_id`, `quantity`, `unit_price`, `total`, `received_quantity`, `notes`, `created_at`) VALUES
(1, 1, 1, 5, 15, 10.39, 155.85, 0, NULL, '2026-08-23 05:20:15');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `company_name`, `contact_person`, `email`, `phone`, `address`, `tax_id`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Sample Supplier Inc.', 'John Supplier', 'supplier@shelfsense.com', '09123456789', NULL, NULL, NULL, 1, '2026-08-20 17:09:05', '2026-08-21 12:54:24'),
(2, 'Sample Supplier Inc.', 'Supplier Contact', 'supplier@shelfsense.com', '09123456789', '123 Supplier St, City', NULL, NULL, 1, '2026-08-21 03:04:10', '2026-08-21 03:04:10');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_invoices`
--

CREATE TABLE `supplier_invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `invoice_date` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','verified','paid','rejected') DEFAULT 'pending',
  `po_match` tinyint(4) DEFAULT 0,
  `gr_match` tinyint(4) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `paid_by` int(11) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_products`
--

CREATE TABLE `supplier_products` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_products`
--

INSERT INTO `supplier_products` (`id`, `supplier_id`, `name`, `description`, `price`, `is_active`, `created_at`, `updated_at`) VALUES
(5, 1, 'Sample Book', 'A sample book for testing', 10.39, 1, '2026-08-21 12:54:24', '2026-08-21 12:54:24'),
(6, 1, 'Sample Pen', 'A sample pen for testing', 2.39, 1, '2026-08-21 12:54:24', '2026-08-21 12:54:24');

-- --------------------------------------------------------

--
-- Table structure for table `trainees`
--

CREATE TABLE `trainees` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `trainer_id` int(11) DEFAULT NULL,
  `target_role` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `schedule_start` time DEFAULT '10:00:00',
  `schedule_end` time DEFAULT '15:00:00',
  `status` enum('active','completed','terminated') DEFAULT 'active',
  `trainee_salary_min` decimal(10,2) DEFAULT 3900.00,
  `trainee_salary_max` decimal(10,2) DEFAULT 4500.00,
  `trainee_salary_set_at` timestamp NULL DEFAULT NULL,
  `report_1` text DEFAULT NULL,
  `report_2` text DEFAULT NULL,
  `report_3` text DEFAULT NULL,
  `reports_status` enum('pending','reviewed','completed') DEFAULT 'pending',
  `eligible_for_contract` tinyint(4) DEFAULT 0,
  `decision_deadline` date DEFAULT NULL,
  `contract_offered_date` date DEFAULT NULL,
  `trainer_released_at` datetime DEFAULT NULL,
  `training_completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trainee_weekly_reports`
--

CREATE TABLE `trainee_weekly_reports` (
  `id` int(11) NOT NULL,
  `trainee_id` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `report_text` text NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `trainer_reviewed_by` int(11) DEFAULT NULL,
  `trainer_notes` text DEFAULT NULL,
  `department_head_reviewed_by` int(11) DEFAULT NULL,
  `department_head_notes` text DEFAULT NULL,
  `status` enum('submitted','trainer_reviewed','head_reviewed','completed') DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `employee_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','hr_head','hr_staff','employee','finance_head','finance_staff','trainee','store_manager','supplier') NOT NULL,
  `permission_level` int(11) DEFAULT 1,
  `can_train` tinyint(4) DEFAULT 0,
  `is_supervising` tinyint(4) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `is_first_login` tinyint(4) DEFAULT 1,
  `profile_pic` varchar(255) DEFAULT NULL,
  `hired_date` timestamp NULL DEFAULT NULL,
  `sick_leave_balance` decimal(5,2) DEFAULT 15.00,
  `vacation_leave_balance` decimal(5,2) DEFAULT 15.00,
  `emergency_leave_balance` decimal(5,2) DEFAULT 5.00,
  `other_leave_balance` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `employee_number`, `first_name`, `last_name`, `middle_name`, `email`, `password`, `role`, `permission_level`, `can_train`, `is_supervising`, `is_active`, `is_first_login`, `profile_pic`, `hired_date`, `sick_leave_balance`, `vacation_leave_balance`, `emergency_leave_balance`, `other_leave_balance`, `created_at`, `updated_at`) VALUES
(1, 'SA-001', 'Super', 'Admin', NULL, 'admin@shelfsense.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'owner', 5, 0, 0, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-20 17:09:05'),
(2, 'HH-001', 'Maria', 'Santos', NULL, 'hr.head@shelfsense.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'hr_head', 4, 1, 1, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-20 17:09:05'),
(3, 'HS-001', 'Juan', 'Dela Cruz', NULL, 'hr.staff@shelfsense.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'hr_staff', 1, 1, 0, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-20 17:09:05'),
(4, 'HS-002', 'Ana', 'Reyes', NULL, 'stephenfrias04@gmail.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'hr_staff', 1, 1, 0, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-21 14:44:43'),
(5, 'SM-001', 'Store', 'Manager', NULL, 'store.manager@shelfsense.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'store_manager', 4, 0, 1, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-20 17:09:05'),
(6, 'FH-001', 'Finance', 'Head', NULL, 'finance.head@shelfsense.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'finance_head', 4, 0, 1, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-20 17:09:05'),
(7, 'FS-001', 'Finance', 'Staff', NULL, 'finance.staff@shelfsense.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'finance_staff', 1, 0, 0, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-20 17:09:05'),
(8, 'FS-002', 'Sarah', 'Williams', NULL, 'finance.staff2@shelfsense.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'finance_staff', 1, 0, 0, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-20 17:09:05'),
(9, 'CA-001', 'Cashier', 'Test', NULL, 'employee@shelfsense.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'employee', 1, 1, 0, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-21 01:51:10'),
(10, 'CA-002', 'John', 'Doe', NULL, 'rumbines.allen@ncst.edu.ph', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'employee', 1, 1, 0, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-21 14:43:48'),
(11, 'TR-001', 'Trainee', 'User', NULL, 'trainee@shelfsense.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'trainee', 0, 0, 0, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-20 17:09:05', '2026-08-20 17:09:05'),
(12, 'SUP-001', 'Sample', 'Supplier', NULL, 'supplier@shelfsense.com', '$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu', 'supplier', 1, 0, 0, 1, 1, NULL, NULL, 15.00, 15.00, 5.00, 0.00, '2026-08-21 03:04:09', '2026-08-21 03:07:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_target_role` (`target_role`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_date` (`user_id`,`date`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `attendance_monthly_summaries`
--
ALTER TABLE `attendance_monthly_summaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `month_year` (`month_year`),
  ADD KEY `sent_by` (`sent_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `attendance_weekly_summaries`
--
ALTER TABLE `attendance_weekly_summaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_week` (`user_id`,`week_start_date`),
  ADD KEY `sent_by` (`sent_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_department_month` (`department`,`month_year`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_month_year` (`month_year`);

--
-- Indexes for table `cash_reconciliation`
--
ALTER TABLE `cash_reconciliation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `store_manager_id` (`store_manager_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `offered_by` (`offered_by`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requisition_id` (`requisition_id`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `goods_receipt_id` (`goods_receipt_id`),
  ADD KEY `requisition_item_id` (`requisition_item_id`);

--
-- Indexes for table `interviews`
--
ALTER TABLE `interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `hr_user_id` (`hr_user_id`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_year` (`user_id`,`year`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `cashier_id` (`cashier_id`),
  ADD KEY `voided_by` (`voided_by`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `otp` (`otp`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_invoice_id` (`supplier_invoice_id`),
  ADD KEY `paid_by` (`paid_by`);

--
-- Indexes for table `payment_requests`
--
ALTER TABLE `payment_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_invoice_id` (`supplier_invoice_id`),
  ADD KEY `requested_by` (`requested_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_requisition` (`requisition_id`),
  ADD KEY `idx_payment_requests_status` (`status`);

--
-- Indexes for table `payroll_approval_logs`
--
ALTER TABLE `payroll_approval_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payroll_cycle_id` (`payroll_cycle_id`),
  ADD KEY `action_by` (`action_by`);

--
-- Indexes for table `payroll_cycles`
--
ALTER TABLE `payroll_cycles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `processed_by` (`processed_by`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `payroll_entries`
--
ALTER TABLE `payroll_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cycle_user` (`payroll_cycle_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pos_override_requests`
--
ALTER TABLE `pos_override_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cashier_id` (`cashier_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `rejection_reasons`
--
ALTER TABLE `rejection_reasons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `hr_user_id` (`hr_user_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_day` (`user_id`,`day_of_week`);

--
-- Indexes for table `store_requisitions`
--
ALTER TABLE `store_requisitions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `requisition_number` (`requisition_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `idx_requisition_status` (`status`),
  ADD KEY `idx_requisition_department` (`department`),
  ADD KEY `idx_requisition_budget_month` (`budget_month_year`);

--
-- Indexes for table `store_requisition_items`
--
ALTER TABLE `store_requisition_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requisition_id` (`requisition_id`),
  ADD KEY `store_product_id` (`store_product_id`),
  ADD KEY `supplier_product_id` (`supplier_product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `requisition_id` (`requisition_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `paid_by` (`paid_by`);

--
-- Indexes for table `supplier_products`
--
ALTER TABLE `supplier_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `trainees`
--
ALTER TABLE `trainees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- Indexes for table `trainee_weekly_reports`
--
ALTER TABLE `trainee_weekly_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trainee_id` (`trainee_id`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `trainer_reviewed_by` (`trainer_reviewed_by`),
  ADD KEY `department_head_reviewed_by` (`department_head_reviewed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_monthly_summaries`
--
ALTER TABLE `attendance_monthly_summaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_weekly_summaries`
--
ALTER TABLE `attendance_weekly_summaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100002;

--
-- AUTO_INCREMENT for table `cash_reconciliation`
--
ALTER TABLE `cash_reconciliation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interviews`
--
ALTER TABLE `interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_requests`
--
ALTER TABLE `payment_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_approval_logs`
--
ALTER TABLE `payroll_approval_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_cycles`
--
ALTER TABLE `payroll_cycles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_entries`
--
ALTER TABLE `payroll_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pos_override_requests`
--
ALTER TABLE `pos_override_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rejection_reasons`
--
ALTER TABLE `rejection_reasons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_requisitions`
--
ALTER TABLE `store_requisitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `store_requisition_items`
--
ALTER TABLE `store_requisition_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_products`
--
ALTER TABLE `supplier_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `trainees`
--
ALTER TABLE `trainees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trainee_weekly_reports`
--
ALTER TABLE `trainee_weekly_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_monthly_summaries`
--
ALTER TABLE `attendance_monthly_summaries`
  ADD CONSTRAINT `attendance_monthly_summaries_ibfk_1` FOREIGN KEY (`sent_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_monthly_summaries_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `attendance_weekly_summaries`
--
ALTER TABLE `attendance_weekly_summaries`
  ADD CONSTRAINT `attendance_weekly_summaries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_weekly_summaries_ibfk_2` FOREIGN KEY (`sent_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `attendance_weekly_summaries_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `cash_reconciliation`
--
ALTER TABLE `cash_reconciliation`
  ADD CONSTRAINT `cash_reconciliation_ibfk_1` FOREIGN KEY (`store_manager_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `contracts_ibfk_3` FOREIGN KEY (`offered_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `goods_receipts`
--
ALTER TABLE `goods_receipts`
  ADD CONSTRAINT `goods_receipts_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `store_requisitions` (`id`),
  ADD CONSTRAINT `goods_receipts_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `goods_receipt_items`
--
ALTER TABLE `goods_receipt_items`
  ADD CONSTRAINT `goods_receipt_items_ibfk_1` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `goods_receipt_items_ibfk_2` FOREIGN KEY (`requisition_item_id`) REFERENCES `store_requisition_items` (`id`);

--
-- Constraints for table `interviews`
--
ALTER TABLE `interviews`
  ADD CONSTRAINT `interviews_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `interviews_ibfk_2` FOREIGN KEY (`hr_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD CONSTRAINT `job_postings_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `job_postings_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `leaves`
--
ALTER TABLE `leaves`
  ADD CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leaves_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD CONSTRAINT `leave_balances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`voided_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payment_requests`
--
ALTER TABLE `payment_requests`
  ADD CONSTRAINT `payment_requests_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `store_requisitions` (`id`),
  ADD CONSTRAINT `payment_requests_ibfk_2` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`),
  ADD CONSTRAINT `payment_requests_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `payment_requests_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payroll_approval_logs`
--
ALTER TABLE `payroll_approval_logs`
  ADD CONSTRAINT `payroll_approval_logs_ibfk_1` FOREIGN KEY (`payroll_cycle_id`) REFERENCES `payroll_cycles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_approval_logs_ibfk_2` FOREIGN KEY (`action_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_cycles`
--
ALTER TABLE `payroll_cycles`
  ADD CONSTRAINT `payroll_cycles_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_cycles_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_cycles_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payroll_cycles_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll_entries`
--
ALTER TABLE `payroll_entries`
  ADD CONSTRAINT `payroll_entries_ibfk_1` FOREIGN KEY (`payroll_cycle_id`) REFERENCES `payroll_cycles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payroll_entries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `pos_override_requests`
--
ALTER TABLE `pos_override_requests`
  ADD CONSTRAINT `pos_override_requests_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `pos_override_requests_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `pos_override_requests_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `purchase_orders_ibfk_4` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `rejection_reasons`
--
ALTER TABLE `rejection_reasons`
  ADD CONSTRAINT `rejection_reasons_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rejection_reasons_ibfk_2` FOREIGN KEY (`hr_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `store_requisitions`
--
ALTER TABLE `store_requisitions`
  ADD CONSTRAINT `store_requisitions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `store_requisitions_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `store_requisition_items`
--
ALTER TABLE `store_requisition_items`
  ADD CONSTRAINT `store_requisition_items_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `store_requisitions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `store_requisition_items_ibfk_2` FOREIGN KEY (`store_product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `store_requisition_items_ibfk_3` FOREIGN KEY (`supplier_product_id`) REFERENCES `supplier_products` (`id`);

--
-- Constraints for table `supplier_invoices`
--
ALTER TABLE `supplier_invoices`
  ADD CONSTRAINT `supplier_invoices_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `store_requisitions` (`id`),
  ADD CONSTRAINT `supplier_invoices_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `supplier_invoices_ibfk_3` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `supplier_products`
--
ALTER TABLE `supplier_products`
  ADD CONSTRAINT `supplier_products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `trainees`
--
ALTER TABLE `trainees`
  ADD CONSTRAINT `trainees_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trainees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `trainees_ibfk_3` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `trainee_weekly_reports`
--
ALTER TABLE `trainee_weekly_reports`
  ADD CONSTRAINT `trainee_weekly_reports_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`),
  ADD CONSTRAINT `trainee_weekly_reports_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `trainee_weekly_reports_ibfk_3` FOREIGN KEY (`trainer_reviewed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `trainee_weekly_reports_ibfk_4` FOREIGN KEY (`department_head_reviewed_by`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
