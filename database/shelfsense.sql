-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: shelfsense
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `applicant_skill_ratings`
--

DROP TABLE IF EXISTS `applicant_skill_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applicant_skill_ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `skill_key` varchar(100) NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_applicant_skill` (`applicant_id`,`skill_key`),
  CONSTRAINT `fk_skill_rating_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicant_skill_ratings`
--

LOCK TABLES `applicant_skill_ratings` WRITE;
/*!40000 ALTER TABLE `applicant_skill_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `applicant_skill_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applicants`
--

DROP TABLE IF EXISTS `applicants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applicants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `province_code` varchar(20) DEFAULT NULL,
  `city_municipality` varchar(150) DEFAULT NULL,
  `city_municipality_code` varchar(20) DEFAULT NULL,
  `barangay` varchar(150) DEFAULT NULL,
  `barangay_code` varchar(20) DEFAULT NULL,
  `house_block_lot` varchar(255) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `subdivision` varchar(255) DEFAULT NULL,
  `postal_code` varchar(4) DEFAULT NULL,
  `country` varchar(50) NOT NULL DEFAULT 'Philippines',
  `target_role` varchar(50) NOT NULL,
  `job_posting_id` int(11) DEFAULT NULL,
  `resume_path` varchar(255) NOT NULL,
  `status` enum('pending','initial_scheduled','initial_passed','initial_failed','final_scheduled','final_passed','final_failed','screening','screening_success','screening_failed','contract_offered','contract_declined','hired','withdrawn') DEFAULT 'pending',
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_email` (`email`),
  KEY `idx_target_role` (`target_role`),
  KEY `job_posting_id` (`job_posting_id`),
  KEY `idx_province_code` (`province_code`),
  KEY `idx_city_municipality_code` (`city_municipality_code`),
  CONSTRAINT `applicants_ibfk_job_posting` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicants`
--

LOCK TABLES `applicants` WRITE;
/*!40000 ALTER TABLE `applicants` DISABLE KEYS */;
INSERT INTO `applicants` VALUES (1,'test','test','test','test@gmail.com','09264550078',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Philippines','Employee',1,'uploads/resumes/cd59030f7be000ae2bf73bc363643ee9.docx','initial_passed','2026-08-30 09:55:42','2026-08-30 12:16:45');
/*!40000 ALTER TABLE `applicants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_date` (`user_id`,`date`),
  KEY `recorded_by` (`recorded_by`),
  KEY `verified_by` (`verified_by`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_monthly_summaries`
--

DROP TABLE IF EXISTS `attendance_monthly_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_monthly_summaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `month_year` (`month_year`),
  KEY `sent_by` (`sent_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `attendance_monthly_summaries_ibfk_1` FOREIGN KEY (`sent_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_monthly_summaries_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_monthly_summaries`
--

LOCK TABLES `attendance_monthly_summaries` WRITE;
/*!40000 ALTER TABLE `attendance_monthly_summaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_monthly_summaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_weekly_summaries`
--

DROP TABLE IF EXISTS `attendance_weekly_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_weekly_summaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_week` (`user_id`,`week_start_date`),
  KEY `sent_by` (`sent_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `attendance_weekly_summaries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_weekly_summaries_ibfk_2` FOREIGN KEY (`sent_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_weekly_summaries_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_weekly_summaries`
--

LOCK TABLES `attendance_weekly_summaries` WRITE;
/*!40000 ALTER TABLE `attendance_weekly_summaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_weekly_summaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budget_transactions`
--

DROP TABLE IF EXISTS `budget_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budget_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `period_key` varchar(10) NOT NULL,
  `type` enum('allocation','reservation','release','expense','adjustment') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reference_type` enum('requisition','purchase_order','invoice','manual') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bt_department_period` (`department_id`,`period_key`),
  KEY `idx_bt_type` (`type`),
  KEY `idx_bt_reference` (`reference_type`,`reference_id`),
  KEY `idx_bt_created_by` (`created_by`),
  CONSTRAINT `fk_bt_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `fk_bt_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_transactions`
--

LOCK TABLES `budget_transactions` WRITE;
/*!40000 ALTER TABLE `budget_transactions` DISABLE KEYS */;
INSERT INTO `budget_transactions` VALUES (1,1,'2026-09-H1','reservation',12.78,'requisition',1,6,'Test approval - no budget allocated yet in dev environment','2026-09-06 11:39:39'),(2,1,'2026-09-H1','expense',12.78,'purchase_order',1,6,'PO payment request #1','2026-09-06 11:50:14'),(3,1,'2026-09-H1','release',12.78,'requisition',1,6,'Reservation closed by PO payment request #1','2026-09-06 11:50:14'),(4,1,'2026-09-H1','reservation',31.17,'requisition',3,6,'Second test run - no budget allocated in dev','2026-09-06 12:03:28'),(5,1,'2026-09-H1','reservation',-10.39,'requisition',3,5,'Adjusted for accepted supplier counter-proposal','2026-09-06 12:09:29'),(6,1,'2026-09-H1','expense',20.78,'purchase_order',3,6,'PO payment request #2','2026-09-06 12:20:30'),(7,1,'2026-09-H1','release',20.78,'requisition',3,6,'Reservation closed by PO payment request #2','2026-09-06 12:20:30'),(8,1,'2026-09-H1','adjustment',20000.00,'manual',NULL,6,'','2026-09-06 14:48:15'),(9,1,'2026-09-H1','reservation',9.95,'requisition',4,6,NULL,'2026-09-06 14:49:07'),(10,1,'2026-09-H1','expense',9.95,'purchase_order',4,6,'PO payment request #3','2026-09-06 15:13:45'),(11,1,'2026-09-H1','release',9.95,'requisition',4,6,'Reservation closed by PO payment request #3','2026-09-06 15:13:45');
/*!40000 ALTER TABLE `budget_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `period_key` varchar(10) NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_department_period` (`department_id`,`period_key`),
  KEY `idx_period_key` (`period_key`),
  CONSTRAINT `fk_budgets_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgets`
--

LOCK TABLES `budgets` WRITE;
/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
INSERT INTO `budgets` VALUES (1,1,'2026-09-H1',0.00,'2026-09-06 11:39:39','2026-09-06 11:39:39');
/*!40000 ALTER TABLE `budgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_reconciliation`
--

DROP TABLE IF EXISTS `cash_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_manager_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `expected_cash` decimal(10,2) NOT NULL,
  `actual_cash` decimal(10,2) NOT NULL,
  `difference` decimal(10,2) GENERATED ALWAYS AS (`expected_cash` - `actual_cash`) STORED,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `store_manager_id` (`store_manager_id`),
  CONSTRAINT `cash_reconciliation_ibfk_1` FOREIGN KEY (`store_manager_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_reconciliation`
--

LOCK TABLES `cash_reconciliation` WRITE;
/*!40000 ALTER TABLE `cash_reconciliation` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_reconciliation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Books',NULL,1,'2026-08-20 17:09:05'),(2,'School Supplies',NULL,1,'2026-08-20 17:09:05'),(3,'Merchandise',NULL,1,'2026-08-20 17:09:05'),(4,'Beverages',NULL,1,'2026-08-20 17:09:05'),(5,'Snacks',NULL,1,'2026-08-20 17:09:05');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts`
--

DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `contract_type` enum('trainee','hired') NOT NULL DEFAULT 'hired',
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
  `response_notes` text DEFAULT NULL,
  `rest_days` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `applicant_id` (`applicant_id`),
  KEY `offered_by` (`offered_by`),
  CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `contracts_ibfk_3` FOREIGN KEY (`offered_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts`
--

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_department_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'store','STORE',1,'2026-09-06 11:20:01');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `status` enum('sent','failed') DEFAULT 'sent',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
INSERT INTO `email_logs` VALUES (1,'test.trainee@example.com','Final Interview Scheduled',NULL,'failed','2026-08-25 20:09:49'),(2,'test.trainee@example.com','Contract Offered',NULL,'failed','2026-08-25 20:10:08'),(3,'test.trainee@example.com','Congratulations! You\'re Hired!',NULL,'failed','2026-08-25 20:10:17'),(4,'test@gmail.com','Application Received',NULL,'sent','2026-08-30 09:55:47'),(5,'test@gmail.com','Initial Interview Scheduled',NULL,'sent','2026-08-30 10:01:04'),(8,'qa_finalpass_temp@shelfsense.test','Your Employment Contract - ShelfSense',NULL,'sent','2026-08-30 12:09:09'),(9,'qa_salary_temp@shelfsense.test','Your Trainee Contract - ShelfSense',NULL,'sent','2026-08-30 12:21:15'),(10,'employee@shelfsense.com','New Trainee Assigned - ShelfSense',NULL,'sent','2026-08-30 12:21:18'),(11,'supplier@shelfsense.com','New Purchase Order PO-2026-0001 - ShelfSense',NULL,'failed','2026-09-06 11:40:44'),(12,'supplier@shelfsense.com','Payment Sent - PO PO-2026-0001',NULL,'failed','2026-09-06 11:50:14'),(13,'supplier@shelfsense.com','New Purchase Order PO-2026-0003 - ShelfSense',NULL,'failed','2026-09-06 12:04:53'),(14,'supplier@shelfsense.com','Payment Sent - PO PO-2026-0003',NULL,'failed','2026-09-06 12:20:31'),(15,'supplier@shelfsense.com','New Purchase Order PO-2026-0004 - ShelfSense',NULL,'failed','2026-09-06 14:50:15'),(16,'supplier@shelfsense.com','Payment Sent - PO PO-2026-0004',NULL,'failed','2026-09-06 15:13:45');
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_receipt_items`
--

DROP TABLE IF EXISTS `goods_receipt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goods_receipt_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `condition` enum('good','damaged','missing') NOT NULL DEFAULT 'good',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gri_receipt` (`goods_receipt_id`),
  KEY `idx_gri_po_item` (`po_item_id`),
  CONSTRAINT `fk_gri_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`),
  CONSTRAINT `fk_gri_receipt` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipt_items`
--

LOCK TABLES `goods_receipt_items` WRITE;
/*!40000 ALTER TABLE `goods_receipt_items` DISABLE KEYS */;
INSERT INTO `goods_receipt_items` VALUES (1,1,1,1,'good',NULL),(2,1,2,1,'good',NULL),(3,2,4,1,'good',NULL),(4,3,4,1,'damaged',NULL),(5,4,5,5,'good',NULL);
/*!40000 ALTER TABLE `goods_receipt_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `goods_receipts`
--

DROP TABLE IF EXISTS `goods_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `goods_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `status` enum('completed') NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gr_po` (`po_id`),
  KEY `fk_gr_received_by` (`received_by`),
  CONSTRAINT `fk_gr_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `fk_gr_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipts`
--

LOCK TABLES `goods_receipts` WRITE;
/*!40000 ALTER TABLE `goods_receipts` DISABLE KEYS */;
INSERT INTO `goods_receipts` VALUES (1,1,5,'2026-09-06','completed',NULL,'2026-09-06 11:45:29'),(2,3,5,'2026-09-06','completed',NULL,'2026-09-06 12:13:51'),(3,3,5,'2026-09-06','completed',NULL,'2026-09-06 12:14:37'),(4,4,5,'2026-09-06','completed',NULL,'2026-09-06 15:07:41');
/*!40000 ALTER TABLE `goods_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interviews`
--

DROP TABLE IF EXISTS `interviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `applicant_id` (`applicant_id`),
  KEY `hr_user_id` (`hr_user_id`),
  CONSTRAINT `interviews_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interviews_ibfk_2` FOREIGN KEY (`hr_user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interviews`
--

LOCK TABLES `interviews` WRITE;
/*!40000 ALTER TABLE `interviews` DISABLE KEYS */;
INSERT INTO `interviews` VALUES (1,1,3,'initial','2026-09-05 18:00:00','https://meet.google.com/xxx-xxx-xxx','','','completed','passed','2026-08-30 10:01:00','2026-08-30 12:16:45');
/*!40000 ALTER TABLE `interviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `billed_quantity` int(11) NOT NULL,
  `billed_unit_price` decimal(10,2) NOT NULL,
  `billed_total` decimal(10,2) NOT NULL,
  `quantity_variance` int(11) NOT NULL DEFAULT 0,
  `price_variance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `variance_flag` enum('ok','price_variance','quantity_variance') NOT NULL DEFAULT 'ok',
  PRIMARY KEY (`id`),
  KEY `idx_ii_invoice` (`invoice_id`),
  KEY `idx_ii_po_item` (`po_item_id`),
  CONSTRAINT `fk_ii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ii_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
INSERT INTO `invoice_items` VALUES (1,1,1,1,2.39,2.39,0,0.00,'ok'),(2,1,2,1,10.39,10.39,0,0.00,'ok'),(3,2,4,2,13.00,26.00,0,2.61,'ok'),(4,3,5,5,1.99,9.95,0,0.00,'ok');
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `po_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `match_status` enum('pending','matched','price_hold','quantity_hold','approved','reconciled','rejected') NOT NULL DEFAULT 'pending',
  `file_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_invoice_number` (`invoice_number`),
  KEY `idx_inv_po` (`po_id`),
  KEY `idx_inv_supplier` (`supplier_id`),
  KEY `idx_inv_match_status` (`match_status`),
  CONSTRAINT `fk_inv_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `fk_inv_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV-2026-0001',1,1,'2026-09-06','2026-10-06',12.78,0.00,12.78,'reconciled',NULL,'','2026-09-06 11:46:42','2026-09-06 11:46:42'),(2,'INV-2026-0002',3,1,'2026-09-06','2026-10-06',26.00,0.00,26.00,'reconciled',NULL,'','2026-09-06 12:16:28','2026-09-06 12:16:28'),(3,'INV-2026-0003',4,1,'2026-09-06','2026-10-06',9.95,0.00,9.95,'reconciled',NULL,'','2026-09-06 15:12:35','2026-09-06 15:12:35');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_postings`
--

DROP TABLE IF EXISTS `job_postings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_postings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reused_from_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL,
  `department_group` enum('Front Department','Human Resources Department','Finance Department') NOT NULL DEFAULT 'Front Department',
  `location` varchar(150) DEFAULT NULL,
  `role` varchar(50) NOT NULL,
  `employment_type` varchar(30) NOT NULL DEFAULT 'Full-Time',
  `description` text NOT NULL,
  `requirements` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `salary_range_min` decimal(10,2) DEFAULT NULL,
  `salary_range_max` decimal(10,2) DEFAULT NULL,
  `slots` int(11) DEFAULT NULL,
  `open_until` date NOT NULL,
  `status` enum('draft','pending_approval','approved','rejected','closed','archived') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `fk_job_postings_rejected_by` (`rejected_by`),
  KEY `fk_job_postings_reused_from` (`reused_from_id`),
  CONSTRAINT `fk_job_postings_rejected_by` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_job_postings_reused_from` FOREIGN KEY (`reused_from_id`) REFERENCES `job_postings` (`id`),
  CONSTRAINT `job_postings_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `job_postings_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_postings`
--

LOCK TABLES `job_postings` WRITE;
/*!40000 ALTER TABLE `job_postings` DISABLE KEYS */;
INSERT INTO `job_postings` VALUES (1,NULL,'TestJob','Cashier','Front Department','asdasd','Cashier','Full-Time','test','test','test',123213.00,123321.00,3,'2026-09-03','approved',2,'2026-08-30 17:43:36',2,'2026-08-30 17:43:40',NULL,NULL,NULL,NULL,'2026-08-30 09:43:36','2026-08-30 09:43:40'),(2,NULL,'TestJob2','HR Staff','Human Resources Department','asdasd1','Cashier','Full-Time','test','test','test',123213.00,123321.00,32,'2026-09-04','approved',2,'2026-08-30 17:44:57',2,'2026-08-30 17:45:00',NULL,NULL,NULL,NULL,'2026-08-30 09:44:57','2026-08-30 11:30:57');
/*!40000 ALTER TABLE `job_postings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leave_balances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_days` decimal(5,2) DEFAULT 5.00,
  `used_days` decimal(5,2) DEFAULT 0.00,
  `remaining_days` decimal(5,2) GENERATED ALWAYS AS (`total_days` - `used_days`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_year` (`user_id`,`year`),
  CONSTRAINT `leave_balances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leaves`
--

DROP TABLE IF EXISTS `leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leaves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('sick','vacation','emergency','maternity','other') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `leaves_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `leaves_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leaves`
--

LOCK TABLES `leaves` WRITE;
/*!40000 ALTER TABLE `leaves` DISABLE KEYS */;
/*!40000 ALTER TABLE `leaves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_budget_adjustments`
--

DROP TABLE IF EXISTS `legacy_budget_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_budget_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) NOT NULL,
  `department` varchar(20) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `previous_allocated` decimal(12,2) NOT NULL,
  `new_allocated` decimal(12,2) NOT NULL,
  `adjustment_amount` decimal(12,2) NOT NULL,
  `used_at_adjustment` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reserved_at_adjustment` decimal(12,2) NOT NULL DEFAULT 0.00,
  `adjusted_by` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_department_month` (`department`,`month_year`),
  KEY `idx_adjusted_by` (`adjusted_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_budget_adjustments_budget` (`budget_id`),
  CONSTRAINT `fk_budget_adjustments_budget` FOREIGN KEY (`budget_id`) REFERENCES `legacy_budgets` (`id`),
  CONSTRAINT `fk_budget_adjustments_user` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_budget_adjustments`
--

LOCK TABLES `legacy_budget_adjustments` WRITE;
/*!40000 ALTER TABLE `legacy_budget_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `legacy_budget_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_budgets`
--

DROP TABLE IF EXISTS `legacy_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(20) NOT NULL,
  `month_year` varchar(7) NOT NULL,
  `allocated_budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `used_budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_department_month` (`department`,`month_year`),
  KEY `idx_department` (`department`),
  KEY `idx_month_year` (`month_year`)
) ENGINE=InnoDB AUTO_INCREMENT=100002 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_budgets`
--

LOCK TABLES `legacy_budgets` WRITE;
/*!40000 ALTER TABLE `legacy_budgets` DISABLE KEYS */;
INSERT INTO `legacy_budgets` VALUES (1,'store','2026-08',10000.00,64.53,NULL,'2026-08-21 16:47:43','2026-08-24 17:22:47'),(2,'hr','2026-08',50000.00,0.00,NULL,'2026-08-21 16:47:43','2026-08-21 16:47:43'),(3,'finance','2026-08',30000.00,0.00,NULL,'2026-08-21 16:47:43','2026-08-24 16:56:15'),(4,'general','2026-08',20000.00,0.00,NULL,'2026-08-21 16:47:43','2026-08-21 16:47:43');
/*!40000 ALTER TABLE `legacy_budgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_goods_receipt_items`
--

DROP TABLE IF EXISTS `legacy_goods_receipt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_goods_receipt_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` int(11) NOT NULL,
  `requisition_item_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_receipt_id` (`goods_receipt_id`),
  KEY `requisition_item_id` (`requisition_item_id`),
  CONSTRAINT `legacy_goods_receipt_items_ibfk_1` FOREIGN KEY (`goods_receipt_id`) REFERENCES `legacy_goods_receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `legacy_goods_receipt_items_ibfk_2` FOREIGN KEY (`requisition_item_id`) REFERENCES `legacy_store_requisition_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_goods_receipt_items`
--

LOCK TABLES `legacy_goods_receipt_items` WRITE;
/*!40000 ALTER TABLE `legacy_goods_receipt_items` DISABLE KEYS */;
INSERT INTO `legacy_goods_receipt_items` VALUES (1,1,2,12,NULL);
/*!40000 ALTER TABLE `legacy_goods_receipt_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_goods_receipts`
--

DROP TABLE IF EXISTS `legacy_goods_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_goods_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `status` enum('draft','completed') DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `received_by` (`received_by`),
  CONSTRAINT `legacy_goods_receipts_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `legacy_store_requisitions` (`id`),
  CONSTRAINT `legacy_goods_receipts_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_goods_receipts`
--

LOCK TABLES `legacy_goods_receipts` WRITE;
/*!40000 ALTER TABLE `legacy_goods_receipts` DISABLE KEYS */;
INSERT INTO `legacy_goods_receipts` VALUES (1,2,5,'2026-08-24','completed',NULL,'2026-08-23 19:06:30');
/*!40000 ALTER TABLE `legacy_goods_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_payment_requests`
--

DROP TABLE IF EXISTS `legacy_payment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_payment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `approval_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active_requisition_lock` int(11) GENERATED ALWAYS AS (case when `status` = 'pending' then `requisition_id` else NULL end) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_active_requisition` (`active_requisition_lock`),
  KEY `supplier_invoice_id` (`supplier_invoice_id`),
  KEY `requested_by` (`requested_by`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_status` (`status`),
  KEY `idx_requisition` (`requisition_id`),
  KEY `idx_payment_requests_status` (`status`),
  CONSTRAINT `legacy_payment_requests_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `legacy_store_requisitions` (`id`),
  CONSTRAINT `legacy_payment_requests_ibfk_2` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `legacy_supplier_invoices` (`id`),
  CONSTRAINT `legacy_payment_requests_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `legacy_payment_requests_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_payment_requests`
--

LOCK TABLES `legacy_payment_requests` WRITE;
/*!40000 ALTER TABLE `legacy_payment_requests` DISABLE KEYS */;
INSERT INTO `legacy_payment_requests` VALUES (6,4,2,7,'2026-08-23 19:11:55','approved',6,'2026-08-25 01:22:47',NULL,1,0,NULL,NULL,'','2026-08-23 19:11:55','2026-08-24 17:22:47',NULL);
/*!40000 ALTER TABLE `legacy_payment_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_payments`
--

DROP TABLE IF EXISTS `legacy_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('bank_transfer','check','cash','other') NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `paid_by` int(11) NOT NULL,
  `paid_at` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_invoice_payment` (`supplier_invoice_id`),
  KEY `paid_by` (`paid_by`),
  CONSTRAINT `legacy_payments_ibfk_1` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `legacy_supplier_invoices` (`id`),
  CONSTRAINT `legacy_payments_ibfk_2` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_payments`
--

LOCK TABLES `legacy_payments` WRITE;
/*!40000 ALTER TABLE `legacy_payments` DISABLE KEYS */;
INSERT INTO `legacy_payments` VALUES (4,2,35.85,'bank_transfer','AUTO-20260824192247',6,'2026-08-25 01:22:47','Auto-recorded after Finance Head approval');
/*!40000 ALTER TABLE `legacy_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_purchase_order_items`
--

DROP TABLE IF EXISTS `legacy_purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_purchase_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `received_quantity` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `po_id` (`po_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `legacy_purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `legacy_purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `legacy_purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_purchase_order_items`
--

LOCK TABLES `legacy_purchase_order_items` WRITE;
/*!40000 ALTER TABLE `legacy_purchase_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `legacy_purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_purchase_orders`
--

DROP TABLE IF EXISTS `legacy_purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_number` varchar(20) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `status` enum('pending_budget_check','budget_rejected','pending_fh_approval','pending_dispatch','pending_confirmation','supplier_accepted','supplier_counter_proposed','confirmed','pending_payment','paid','shipped','partially_received','received','cancelled','closed') NOT NULL DEFAULT 'pending_budget_check',
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `received_by` (`received_by`),
  CONSTRAINT `legacy_purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `legacy_purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `legacy_purchase_orders_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `legacy_purchase_orders_ibfk_4` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_purchase_orders`
--

LOCK TABLES `legacy_purchase_orders` WRITE;
/*!40000 ALTER TABLE `legacy_purchase_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `legacy_purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_store_requisition_items`
--

DROP TABLE IF EXISTS `legacy_store_requisition_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_store_requisition_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `store_product_id` int(11) NOT NULL,
  `supplier_product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `received_quantity` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `store_product_id` (`store_product_id`),
  KEY `supplier_product_id` (`supplier_product_id`),
  CONSTRAINT `legacy_store_requisition_items_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `legacy_store_requisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `legacy_store_requisition_items_ibfk_2` FOREIGN KEY (`store_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `legacy_store_requisition_items_ibfk_3` FOREIGN KEY (`supplier_product_id`) REFERENCES `supplier_products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_store_requisition_items`
--

LOCK TABLES `legacy_store_requisition_items` WRITE;
/*!40000 ALTER TABLE `legacy_store_requisition_items` DISABLE KEYS */;
INSERT INTO `legacy_store_requisition_items` VALUES (1,1,1,5,15,10.39,155.85,0,NULL,'2026-08-23 05:20:15'),(2,2,2,6,12,2.39,28.68,12,NULL,'2026-08-23 15:50:15'),(3,4,2,6,15,2.39,35.85,0,NULL,'2026-08-23 19:08:42');
/*!40000 ALTER TABLE `legacy_store_requisition_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_store_requisitions`
--

DROP TABLE IF EXISTS `legacy_store_requisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_store_requisitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `requisition_number` (`requisition_number`),
  KEY `created_by` (`created_by`),
  KEY `supplier_id` (`supplier_id`),
  KEY `idx_requisition_status` (`status`),
  KEY `idx_requisition_department` (`department`),
  KEY `idx_requisition_budget_month` (`budget_month_year`),
  CONSTRAINT `legacy_store_requisitions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `legacy_store_requisitions_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_store_requisitions`
--

LOCK TABLES `legacy_store_requisitions` WRITE;
/*!40000 ALTER TABLE `legacy_store_requisitions` DISABLE KEYS */;
INSERT INTO `legacy_store_requisitions` VALUES (1,'REQ-2026-0001',5,1,'store','pending_supplier','2026-08-23','','2026-08-25',155.85,0.00,155.85,'','2026-08-23 05:20:15','2026-08-23 05:20:15'),(2,'REQ-2026-0002',5,1,'store','completed','2026-08-23','2026-08','0000-00-00',28.68,0.00,28.68,'','2026-08-23 15:50:15','2026-08-23 19:06:30'),(4,'REQ-2026-0003',5,1,'store','paid','2026-08-24','2026-08','2026-08-25',35.85,0.00,35.85,'','2026-08-23 19:08:41','2026-08-24 17:22:47');
/*!40000 ALTER TABLE `legacy_store_requisitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legacy_supplier_invoices`
--

DROP TABLE IF EXISTS `legacy_supplier_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_supplier_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `requisition_id` (`requisition_id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `paid_by` (`paid_by`),
  CONSTRAINT `legacy_supplier_invoices_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `legacy_store_requisitions` (`id`),
  CONSTRAINT `legacy_supplier_invoices_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `legacy_supplier_invoices_ibfk_3` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legacy_supplier_invoices`
--

LOCK TABLES `legacy_supplier_invoices` WRITE;
/*!40000 ALTER TABLE `legacy_supplier_invoices` DISABLE KEYS */;
INSERT INTO `legacy_supplier_invoices` VALUES (1,'INV-2026-0001',2,1,'2026-08-23',28.68,0.00,28.68,'2026-08-26','pending',0,0,'test',NULL,NULL,'2026-08-23 17:03:58','2026-08-23 19:04:19'),(2,'INV-2026-0002',4,1,'2026-08-23',35.85,0.00,35.85,'2026-09-01','paid',0,0,'teast',6,'2026-08-25 01:22:47','2026-08-23 19:09:51','2026-08-24 17:22:47');
/*!40000 ALTER TABLE `legacy_supplier_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (2,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0002 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 17:05:18'),(3,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0002 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 17:05:18'),(5,6,'payment_request_pending','Payment request for requisition #REQ-2026-0002 is pending approval. Amount: ₱28.68','?page=finance_head_payment_requests',0,'2026-08-23 17:33:29'),(6,12,'payment_completed','Payment for requisition #REQ-2026-0002 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-23 17:34:35'),(7,7,'payment_request_approved','Payment request for requisition #REQ-2026-0002 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-23 17:34:35'),(10,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0003 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 19:11:10'),(11,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0003 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 19:11:11'),(13,6,'payment_request_pending','Payment request for requisition #REQ-2026-0003 is pending approval. Amount: ₱35.85','?page=finance_head_payment_requests',0,'2026-08-23 19:11:55'),(14,12,'payment_completed','Payment for requisition #REQ-2026-0003 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-24 17:22:48'),(15,7,'payment_request_approved','Payment request for requisition #REQ-2026-0003 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-24 17:22:48'),(34,3,'new_application','New application from test test for TestJob position','?page=hr_applicants',0,'2026-08-30 09:55:47'),(35,4,'new_application','New application from test test for TestJob position','?page=hr_applicants',0,'2026-08-30 09:55:47'),(36,3,'interview_scheduled','initial interview scheduled for test test',NULL,0,'2026-08-30 10:01:00'),(51,7,'po_pending_budget_check','New Purchase Order PO-2026-0001 (₱12.78) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 11:37:16'),(52,8,'po_pending_budget_check','New Purchase Order PO-2026-0001 (₱12.78) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 11:37:16'),(53,6,'po_pending_approval','Purchase Order PO-2026-0001 passed budget check and needs your approval.','?page=finance_head_requisitions',0,'2026-09-06 11:38:38'),(54,7,'po_pending_dispatch','Purchase Order PO-2026-0001 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 11:39:40'),(55,8,'po_pending_dispatch','Purchase Order PO-2026-0001 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 11:39:40'),(56,5,'po_approved','Purchase Order PO-2026-0001 (requisition #REQ-2026-0001) was approved by Finance Head.','?page=store_manager_requisitions',0,'2026-09-06 11:39:40'),(57,12,'po_received','New Purchase Order PO-2026-0001 has been sent to you. Please review and confirm.','?page=supplier_requisitions',0,'2026-09-06 11:40:44'),(58,5,'po_confirmed','Supplier confirmed Purchase Order PO-2026-0001 for requisition #REQ-2026-0001.','?page=store_manager_requisitions',0,'2026-09-06 11:41:51'),(59,5,'po_shipped','PO PO-2026-0001 has been shipped by the supplier.','?page=store_manager_requisitions',0,'2026-09-06 11:43:17'),(60,7,'goods_received','Goods fully received for PO PO-2026-0001 (requisition #REQ-2026-0001).','?page=finance_staff_payment_requests',0,'2026-09-06 11:45:29'),(61,8,'goods_received','Goods fully received for PO PO-2026-0001 (requisition #REQ-2026-0001).','?page=finance_staff_payment_requests',0,'2026-09-06 11:45:29'),(62,7,'invoice_reconciled','Invoice INV-2026-0001 for PO PO-2026-0001 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 11:46:42'),(63,8,'invoice_reconciled','Invoice INV-2026-0001 for PO PO-2026-0001 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 11:46:42'),(64,6,'po_payment_requested','Payment requested for PO PO-2026-0001 (₱12.78).','?page=finance_head_payment_requests',0,'2026-09-06 11:48:47'),(65,12,'po_payment_received','Payment for PO PO-2026-0001 has been sent.','?page=supplier_requisitions',0,'2026-09-06 11:50:14'),(66,7,'po_payment_approved','Payment request for PO PO-2026-0001 was approved and disbursed.','?page=finance_staff_payment_requests',0,'2026-09-06 11:50:14'),(67,7,'po_pending_budget_check','New Purchase Order PO-2026-0002 (₱3.98) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 11:54:53'),(68,8,'po_pending_budget_check','New Purchase Order PO-2026-0002 (₱3.98) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 11:54:53'),(69,6,'po_pending_approval','Purchase Order PO-2026-0002 passed budget check and needs your approval.','?page=finance_head_requisitions',0,'2026-09-06 11:56:19'),(70,5,'po_rejected','Purchase Order PO-2026-0002 was rejected by Finance Head. Reason: Testing the reject path for the report','?page=store_manager_requisitions',0,'2026-09-06 11:58:05'),(71,7,'po_pending_budget_check','New Purchase Order PO-2026-0003 (₱31.17) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 12:00:05'),(72,8,'po_pending_budget_check','New Purchase Order PO-2026-0003 (₱31.17) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 12:00:05'),(73,6,'po_pending_approval','Purchase Order PO-2026-0003 passed budget check and needs your approval.','?page=finance_head_requisitions',0,'2026-09-06 12:01:35'),(74,7,'po_pending_dispatch','Purchase Order PO-2026-0003 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 12:03:28'),(75,8,'po_pending_dispatch','Purchase Order PO-2026-0003 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 12:03:28'),(76,5,'po_approved','Purchase Order PO-2026-0003 (requisition #REQ-2026-0003) was approved by Finance Head.','?page=store_manager_requisitions',0,'2026-09-06 12:03:28'),(77,12,'po_received','New Purchase Order PO-2026-0003 has been sent to you. Please review and confirm.','?page=supplier_requisitions',0,'2026-09-06 12:04:53'),(78,5,'po_counter_proposed','Supplier proposed changes to Purchase Order PO-2026-0003 for requisition #REQ-2026-0003.','?page=store_manager_requisitions',0,'2026-09-06 12:06:44'),(79,12,'po_counter_accepted','Your counter-proposal for PO PO-2026-0003 was accepted.','?page=supplier_requisitions',0,'2026-09-06 12:09:29'),(80,5,'po_shipped','PO PO-2026-0003 has been shipped by the supplier.','?page=store_manager_requisitions',0,'2026-09-06 12:11:32'),(81,7,'goods_received','Goods partially received for PO PO-2026-0003 (requisition #REQ-2026-0003).','?page=finance_staff_payment_requests',0,'2026-09-06 12:13:52'),(82,8,'goods_received','Goods partially received for PO PO-2026-0003 (requisition #REQ-2026-0003).','?page=finance_staff_payment_requests',0,'2026-09-06 12:13:52'),(83,7,'goods_received','Goods fully received for PO PO-2026-0003 (requisition #REQ-2026-0003).','?page=finance_staff_payment_requests',0,'2026-09-06 12:14:37'),(84,8,'goods_received','Goods fully received for PO PO-2026-0003 (requisition #REQ-2026-0003).','?page=finance_staff_payment_requests',0,'2026-09-06 12:14:37'),(85,7,'invoice_reconciled','Invoice INV-2026-0002 for PO PO-2026-0003 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 12:16:29'),(86,8,'invoice_reconciled','Invoice INV-2026-0002 for PO PO-2026-0003 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 12:16:29'),(87,6,'po_payment_requested','Payment requested for PO PO-2026-0003 (₱20.78).','?page=finance_head_payment_requests',0,'2026-09-06 12:18:35'),(88,12,'po_payment_received','Payment for PO PO-2026-0003 has been sent.','?page=supplier_requisitions',0,'2026-09-06 12:20:31'),(89,7,'po_payment_approved','Payment request for PO PO-2026-0003 was approved and disbursed.','?page=finance_staff_payment_requests',0,'2026-09-06 12:20:31'),(90,7,'po_pending_budget_check','New Purchase Order PO-2026-0004 (₱9.95) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 14:41:42'),(91,8,'po_pending_budget_check','New Purchase Order PO-2026-0004 (₱9.95) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 14:41:42'),(92,6,'po_pending_approval','Purchase Order PO-2026-0004 passed budget check and needs your approval.','?page=finance_head_requisitions',0,'2026-09-06 14:48:46'),(93,7,'po_pending_dispatch','Purchase Order PO-2026-0004 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 14:49:07'),(94,8,'po_pending_dispatch','Purchase Order PO-2026-0004 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 14:49:07'),(95,5,'po_approved','Purchase Order PO-2026-0004 (requisition #REQ-2026-0004) was approved by Finance Head.','?page=store_manager_requisitions',0,'2026-09-06 14:49:07'),(96,12,'po_received','New Purchase Order PO-2026-0004 has been sent to you. Please review and confirm.','?page=supplier_requisitions',0,'2026-09-06 14:50:15'),(97,5,'po_confirmed','Supplier confirmed Purchase Order PO-2026-0004 for requisition #REQ-2026-0004.','?page=store_manager_requisitions',0,'2026-09-06 15:01:49'),(98,5,'po_shipped','PO PO-2026-0004 has been shipped by the supplier.','?page=store_manager_requisitions',0,'2026-09-06 15:02:16'),(99,7,'goods_received','Goods fully received for PO PO-2026-0004 (requisition #REQ-2026-0004).','?page=finance_staff_payment_requests',0,'2026-09-06 15:07:42'),(100,8,'goods_received','Goods fully received for PO PO-2026-0004 (requisition #REQ-2026-0004).','?page=finance_staff_payment_requests',0,'2026-09-06 15:07:42'),(101,7,'invoice_reconciled','Invoice INV-2026-0003 for PO PO-2026-0004 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 15:12:35'),(102,8,'invoice_reconciled','Invoice INV-2026-0003 for PO PO-2026-0004 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 15:12:35'),(103,6,'po_payment_requested','Payment requested for PO PO-2026-0004 (₱9.95).','?page=finance_head_payment_requests',1,'2026-09-06 15:13:13'),(104,12,'po_payment_received','Payment for PO PO-2026-0004 has been sent.','?page=supplier_requisitions',0,'2026-09-06 15:13:45'),(105,7,'po_payment_approved','Payment request for PO PO-2026-0004 was approved and disbursed.','?page=finance_staff_payment_requests',0,'2026-09-06 15:13:45'),(106,7,'po_pending_budget_check','New Purchase Order PO-2026-0005 (₱31.17) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 15:17:41'),(107,8,'po_pending_budget_check','New Purchase Order PO-2026-0005 (₱31.17) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 15:17:41');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `register_allocation_id` int(11) DEFAULT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `cashier_id` (`cashier_id`),
  KEY `voided_by` (`voided_by`),
  KEY `register_allocation_id` (`register_allocation_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`voided_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`register_allocation_id`) REFERENCES `register_allocations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `otp` (`otp`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (4,10,'259722','2026-08-21 22:58:59',0,'2026-08-21 14:43:59'),(5,4,'858060','2026-08-21 22:59:51',0,'2026-08-21 14:44:51');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_batch_items`
--

DROP TABLE IF EXISTS `payment_batch_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_batch_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_batch_invoice` (`batch_id`,`invoice_id`),
  KEY `idx_pbi_invoice` (`invoice_id`),
  CONSTRAINT `fk_pbi_batch` FOREIGN KEY (`batch_id`) REFERENCES `payment_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pbi_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_batch_items`
--

LOCK TABLES `payment_batch_items` WRITE;
/*!40000 ALTER TABLE `payment_batch_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_batch_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_batches`
--

DROP TABLE IF EXISTS `payment_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_batches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_number` varchar(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('pending_approval','approved','rejected','disbursed') NOT NULL DEFAULT 'pending_approval',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_batch_number` (`batch_number`),
  KEY `idx_pb_status` (`status`),
  KEY `fk_pb_created_by` (`created_by`),
  KEY `fk_pb_approved_by` (`approved_by`),
  CONSTRAINT `fk_pb_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_pb_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_batches`
--

LOCK TABLES `payment_batches` WRITE;
/*!40000 ALTER TABLE `payment_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) DEFAULT NULL,
  `po_id` int(11) DEFAULT NULL,
  `payment_request_id` int(11) DEFAULT NULL,
  `payment_batch_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` enum('bank_transfer','check','cash','other') NOT NULL DEFAULT 'bank_transfer',
  `reference_number` varchar(50) DEFAULT NULL,
  `paid_by` int(11) NOT NULL,
  `paid_at` datetime NOT NULL,
  `remittance_sent` tinyint(4) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pay_batch` (`payment_batch_id`),
  KEY `fk_pay_paid_by` (`paid_by`),
  KEY `idx_pay_invoice` (`invoice_id`),
  KEY `idx_pay_po` (`po_id`),
  KEY `idx_pay_payment_request` (`payment_request_id`),
  CONSTRAINT `fk_pay_batch` FOREIGN KEY (`payment_batch_id`) REFERENCES `payment_batches` (`id`),
  CONSTRAINT `fk_pay_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `fk_pay_paid_by` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_pay_payment_request` FOREIGN KEY (`payment_request_id`) REFERENCES `po_payment_requests` (`id`),
  CONSTRAINT `fk_pay_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,NULL,1,1,NULL,12.78,'bank_transfer','PO-PAY-20260906135014',6,'2026-09-06 19:50:14',1,NULL),(2,NULL,3,2,NULL,20.78,'bank_transfer','PO-PAY-20260906142030',6,'2026-09-06 20:20:30',1,NULL),(3,NULL,4,3,NULL,9.95,'bank_transfer','PO-PAY-20260906171345',6,'2026-09-06 23:13:45',1,NULL);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_approval_logs`
--

DROP TABLE IF EXISTS `payroll_approval_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_approval_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_cycle_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `action_by` int(11) NOT NULL,
  `action_at` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payroll_cycle_id` (`payroll_cycle_id`),
  KEY `action_by` (`action_by`),
  CONSTRAINT `payroll_approval_logs_ibfk_1` FOREIGN KEY (`payroll_cycle_id`) REFERENCES `payroll_cycles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_approval_logs_ibfk_2` FOREIGN KEY (`action_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_approval_logs`
--

LOCK TABLES `payroll_approval_logs` WRITE;
/*!40000 ALTER TABLE `payroll_approval_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_approval_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_cycles`
--

DROP TABLE IF EXISTS `payroll_cycles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_cycles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `approved_by` (`approved_by`),
  KEY `verified_by` (`verified_by`),
  KEY `processed_by` (`processed_by`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `payroll_cycles_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_cycles_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_cycles_ibfk_3` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `payroll_cycles_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_cycles`
--

LOCK TABLES `payroll_cycles` WRITE;
/*!40000 ALTER TABLE `payroll_cycles` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_cycles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_entries`
--

DROP TABLE IF EXISTS `payroll_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cycle_user` (`payroll_cycle_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `payroll_entries_ibfk_1` FOREIGN KEY (`payroll_cycle_id`) REFERENCES `payroll_cycles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payroll_entries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_entries`
--

LOCK TABLES `payroll_entries` WRITE;
/*!40000 ALTER TABLE `payroll_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_counter_proposal_items`
--

DROP TABLE IF EXISTS `po_counter_proposal_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_counter_proposal_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposal_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `proposed_quantity` int(11) DEFAULT NULL,
  `proposed_unit_price` decimal(10,2) DEFAULT NULL,
  `proposed_delivery_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pcpi_proposal` (`proposal_id`),
  KEY `idx_pcpi_po_item` (`po_item_id`),
  CONSTRAINT `fk_pcpi_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pcpi_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `po_counter_proposals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_counter_proposal_items`
--

LOCK TABLES `po_counter_proposal_items` WRITE;
/*!40000 ALTER TABLE `po_counter_proposal_items` DISABLE KEYS */;
INSERT INTO `po_counter_proposal_items` VALUES (1,1,4,2,NULL,NULL,NULL);
/*!40000 ALTER TABLE `po_counter_proposal_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_counter_proposals`
--

DROP TABLE IF EXISTS `po_counter_proposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_counter_proposals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `proposed_by` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `reason` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pcp_po` (`po_id`),
  KEY `fk_pcp_proposed_by` (`proposed_by`),
  KEY `fk_pcp_responded_by` (`responded_by`),
  CONSTRAINT `fk_pcp_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pcp_proposed_by` FOREIGN KEY (`proposed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_pcp_responded_by` FOREIGN KEY (`responded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_counter_proposals`
--

LOCK TABLES `po_counter_proposals` WRITE;
/*!40000 ALTER TABLE `po_counter_proposals` DISABLE KEYS */;
INSERT INTO `po_counter_proposals` VALUES (1,3,12,'accepted','Only 2 in stock right now',5,'2026-09-06 20:09:29','2026-09-06 12:06:44');
/*!40000 ALTER TABLE `po_counter_proposals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_events`
--

DROP TABLE IF EXISTS `po_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `visibility` enum('all','not_supplier','not_store_manager') NOT NULL DEFAULT 'all',
  `actor_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_po_events_po` (`po_id`,`created_at`),
  KEY `fk_po_events_actor` (`actor_user_id`),
  CONSTRAINT `fk_po_events_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_po_events_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_events`
--

LOCK TABLES `po_events` WRITE;
/*!40000 ALTER TABLE `po_events` DISABLE KEYS */;
INSERT INTO `po_events` VALUES (1,1,'po_created','Purchase Order PO-2026-0001 created by Store Manager from requisition #REQ-2026-0001, supplier: Sample Supplier Inc..','all',5,'2026-09-06 11:37:16'),(2,1,'po_approved','Purchase Order PO-2026-0001 approved by Finance Head. Justification: Test approval - no budget allocated yet in dev environment','all',6,'2026-09-06 11:39:39'),(3,1,'po_dispatched','Purchase Order dispatched to supplier by Finance Staff.','all',7,'2026-09-06 11:40:44'),(4,1,'po_confirmed','Supplier accepted the purchase order as-is.','all',12,'2026-09-06 11:41:51'),(5,1,'shipped','Supplier marked the order as shipped.','all',12,'2026-09-06 11:43:16'),(6,1,'goods_received','Goods fully received by Store Manager.','not_supplier',5,'2026-09-06 11:45:29'),(7,1,'invoice_submitted','Supplier submitted invoice INV-2026-0001. Reconciliation result: reconciled.','not_store_manager',12,'2026-09-06 11:46:42'),(8,1,'payment_requested','Payment of ₱12.78 requested by Finance Staff.','all',7,'2026-09-06 11:48:46'),(9,1,'payment_approved','Payment of ₱12.78 approved by Finance Head.','all',6,'2026-09-06 11:50:14'),(10,2,'po_created','Purchase Order PO-2026-0002 created by Store Manager from requisition #REQ-2026-0002, supplier: Sample Supplier Inc..','all',5,'2026-09-06 11:54:53'),(11,2,'po_rejected','Purchase Order PO-2026-0002 rejected by Finance Head. Reason: Testing the reject path for the report','all',6,'2026-09-06 11:58:05'),(12,3,'po_created','Purchase Order PO-2026-0003 created by Store Manager from requisition #REQ-2026-0003, supplier: Sample Supplier Inc..','all',5,'2026-09-06 12:00:05'),(13,3,'po_approved','Purchase Order PO-2026-0003 approved by Finance Head. Justification: Second test run - no budget allocated in dev','all',6,'2026-09-06 12:03:28'),(14,3,'po_dispatched','Purchase Order dispatched to supplier by Finance Staff.','all',7,'2026-09-06 12:04:53'),(15,3,'po_counter_proposed','Supplier proposed a quantity/date change. Reason: Only 2 in stock right now','all',12,'2026-09-06 12:06:44'),(16,3,'counter_accepted','Store Manager accepted the supplier\'s quantity change.','all',5,'2026-09-06 12:09:29'),(17,3,'shipped','Supplier marked the order as shipped.','all',12,'2026-09-06 12:11:32'),(18,3,'goods_received','Goods partially received by Store Manager.','not_supplier',5,'2026-09-06 12:13:52'),(19,3,'goods_received','Goods fully received by Store Manager.','not_supplier',5,'2026-09-06 12:14:37'),(20,3,'invoice_submitted','Supplier submitted invoice INV-2026-0002. Reconciliation result: reconciled.','not_store_manager',12,'2026-09-06 12:16:28'),(21,3,'payment_requested','Payment of ₱20.78 requested by Finance Staff.','all',7,'2026-09-06 12:18:35'),(22,3,'payment_approved','Payment of ₱20.78 approved by Finance Head.','all',6,'2026-09-06 12:20:30'),(23,4,'po_created','Purchase Order PO-2026-0004 created by Store Manager from requisition #REQ-2026-0004, supplier: Sample Supplier Inc..','all',5,'2026-09-06 14:41:42'),(24,4,'po_approved','Purchase Order PO-2026-0004 approved by Finance Head.','all',6,'2026-09-06 14:49:07'),(25,4,'po_dispatched','Purchase Order dispatched to supplier by Finance Staff.','all',7,'2026-09-06 14:50:15'),(26,4,'po_confirmed','Supplier accepted the purchase order as-is.','all',12,'2026-09-06 15:01:49'),(27,4,'shipped','Supplier marked the order as shipped.','all',12,'2026-09-06 15:02:16'),(28,4,'goods_received','Goods fully received by Store Manager.','not_supplier',5,'2026-09-06 15:07:42'),(29,4,'invoice_submitted','Supplier submitted invoice INV-2026-0003. Reconciliation result: reconciled.','not_store_manager',12,'2026-09-06 15:12:35'),(30,4,'payment_requested','Payment of ₱9.95 requested by Finance Staff.','all',7,'2026-09-06 15:13:13'),(31,4,'payment_approved','Payment of ₱9.95 approved by Finance Head.','all',6,'2026-09-06 15:13:45'),(32,5,'po_created','Purchase Order PO-2026-0005 created by Store Manager from requisition #REQ-2026-0005, supplier: Sample Supplier Inc..','all',5,'2026-09-06 15:17:41');
/*!40000 ALTER TABLE `po_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_payment_requests`
--

DROP TABLE IF EXISTS `po_payment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_payment_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ppr_po` (`po_id`),
  KEY `idx_ppr_status` (`status`),
  KEY `fk_ppr_requested_by` (`requested_by`),
  KEY `fk_ppr_approved_by` (`approved_by`),
  CONSTRAINT `fk_ppr_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_ppr_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `fk_ppr_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_payment_requests`
--

LOCK TABLES `po_payment_requests` WRITE;
/*!40000 ALTER TABLE `po_payment_requests` DISABLE KEYS */;
INSERT INTO `po_payment_requests` VALUES (1,1,7,12.78,'approved',6,'2026-09-06 19:50:14',NULL,NULL,'2026-09-06 11:48:46'),(2,3,7,20.78,'approved',6,'2026-09-06 20:20:30',NULL,NULL,'2026-09-06 12:18:35'),(3,4,7,9.95,'approved',6,'2026-09-06 23:13:45',NULL,NULL,'2026-09-06 15:13:13');
/*!40000 ALTER TABLE `po_payment_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pos_override_requests`
--

DROP TABLE IF EXISTS `pos_override_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pos_override_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cashier_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `type` enum('price_override','void') NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cashier_id` (`cashier_id`),
  KEY `order_id` (`order_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `pos_override_requests_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `pos_override_requests_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `pos_override_requests_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pos_override_requests`
--

LOCK TABLES `pos_override_requests` WRITE;
/*!40000 ALTER TABLE `pos_override_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_override_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'978-0-123-45678-9','Sample Book','A sample book for testing',1,12.99,8.00,12,5,NULL,1,'2026-08-20 17:09:05','2026-09-06 12:13:51'),(2,'BS-001','Sample Pen','A sample pen for testing',2,2.99,1.20,38,5,NULL,1,'2026-08-20 17:09:05','2026-09-06 15:07:42');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `store_product_id` int(11) NOT NULL,
  `supplier_product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `received_quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_poi_po` (`po_id`),
  KEY `idx_poi_store_product` (`store_product_id`),
  KEY `idx_poi_supplier_product` (`supplier_product_id`),
  CONSTRAINT `fk_poi_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_poi_store_product` FOREIGN KEY (`store_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_poi_supplier_product` FOREIGN KEY (`supplier_product_id`) REFERENCES `supplier_products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (1,1,2,6,1,2.39,2.39,1,'2026-09-06 11:37:16'),(2,1,1,5,1,10.39,10.39,1,'2026-09-06 11:37:16'),(3,2,2,9,2,1.99,3.98,0,'2026-09-06 11:54:53'),(4,3,1,5,2,10.39,20.78,2,'2026-09-06 12:00:05'),(5,4,2,9,5,1.99,9.95,5,'2026-09-06 14:41:42'),(6,5,1,5,3,10.39,31.17,0,'2026-09-06 15:17:41');
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_number` varchar(20) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `status` enum('pending_budget_check','budget_rejected','pending_fh_approval','pending_dispatch','pending_confirmation','supplier_accepted','supplier_counter_proposed','confirmed','pending_payment','paid','shipped','partially_received','received','cancelled','closed') NOT NULL DEFAULT 'pending_budget_check',
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `terms` varchar(50) DEFAULT 'Net 30',
  `dispatched_at` datetime DEFAULT NULL,
  `dispatched_via` enum('email','portal','both') DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `budget_rejected_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_po_number` (`po_number`),
  KEY `idx_po_status` (`status`),
  KEY `idx_po_supplier` (`supplier_id`),
  KEY `fk_po_requisition` (`requisition_id`),
  KEY `fk_po_created_by` (`created_by`),
  KEY `fk_po_approved_by` (`approved_by`),
  CONSTRAINT `fk_po_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_po_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_po_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`),
  CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'PO-2026-0001',1,1,'paid','2026-09-06',NULL,12.78,0.00,12.78,'Net 30','2026-09-06 19:40:44','email',5,6,'2026-09-06 13:39:39',NULL,NULL,'2026-09-06 11:37:16','2026-09-06 11:50:14'),(2,'PO-2026-0002',2,1,'cancelled','2026-09-06',NULL,3.98,0.00,3.98,'Net 30',NULL,NULL,5,NULL,NULL,'Testing the reject path for the report',NULL,'2026-09-06 11:54:53','2026-09-06 14:59:15'),(3,'PO-2026-0003',3,1,'paid','2026-09-06',NULL,20.78,0.00,20.78,'Net 30','2026-09-06 20:04:53','email',5,6,'2026-09-06 14:03:28',NULL,NULL,'2026-09-06 12:00:05','2026-09-06 12:20:30'),(4,'PO-2026-0004',4,1,'paid','2026-09-06','2026-09-21',9.95,0.00,9.95,'Net 30','2026-09-06 22:50:15','email',5,6,'2026-09-06 16:49:07',NULL,NULL,'2026-09-06 14:41:42','2026-09-06 15:13:45'),(5,'PO-2026-0005',5,1,'cancelled','2026-09-06',NULL,31.17,0.00,31.17,'Net 30',NULL,NULL,5,NULL,NULL,NULL,NULL,'2026-09-06 15:17:41','2026-09-06 15:18:35');
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recruitment_logs`
--

DROP TABLE IF EXISTS `recruitment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recruitment_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(30) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` varchar(30) DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `previous_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_recruitment_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recruitment_logs`
--

LOCK TABLES `recruitment_logs` WRITE;
/*!40000 ALTER TABLE `recruitment_logs` DISABLE KEYS */;
INSERT INTO `recruitment_logs` VALUES (18,'job_posting',1,2,'hr_head','created',NULL,'draft',NULL,NULL,'2026-08-30 09:43:36'),(19,'job_posting',1,2,'hr_head','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-08-30 09:43:36'),(20,'job_posting',1,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-08-30 09:43:40'),(21,'job_posting',2,2,'hr_head','created',NULL,'draft',NULL,NULL,'2026-08-30 09:44:57'),(22,'job_posting',2,2,'hr_head','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-08-30 09:44:57'),(23,'job_posting',2,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-08-30 09:45:00'),(24,'applicant',1,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 1)','2026-08-30 09:55:47'),(25,'applicant',1,3,'hr_staff','initial_interview_scheduled','pending','initial_scheduled',NULL,NULL,'2026-08-30 10:01:00');
/*!40000 ALTER TABLE `recruitment_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `register_allocations`
--

DROP TABLE IF EXISTS `register_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `register_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `register_id` int(11) NOT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `allocated_by` int(11) NOT NULL,
  `initial_budget` decimal(10,2) NOT NULL,
  `status` enum('active','cashed_out') NOT NULL DEFAULT 'active',
  `cash_sales` decimal(10,2) DEFAULT NULL,
  `online_sales` decimal(10,2) DEFAULT NULL,
  `total_pulled` decimal(10,2) DEFAULT NULL,
  `opened_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cashed_out_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `register_id` (`register_id`),
  KEY `cashier_id` (`cashier_id`),
  KEY `allocated_by` (`allocated_by`),
  CONSTRAINT `register_allocations_ibfk_1` FOREIGN KEY (`register_id`) REFERENCES `registers` (`id`),
  CONSTRAINT `register_allocations_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `register_allocations_ibfk_3` FOREIGN KEY (`allocated_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `register_allocations`
--

LOCK TABLES `register_allocations` WRITE;
/*!40000 ALTER TABLE `register_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `register_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registers`
--

DROP TABLE IF EXISTS `registers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_manager_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL DEFAULT 'Main Register',
  `pos_id` varchar(20) DEFAULT NULL,
  `pin_hash` varchar(255) DEFAULT NULL,
  `pos_created_by` int(11) DEFAULT NULL,
  `pos_created_at` timestamp NULL DEFAULT NULL,
  `status` enum('closed','open') NOT NULL DEFAULT 'closed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `pos_id` (`pos_id`),
  KEY `registers_pos_created_by_fk` (`pos_created_by`),
  KEY `idx_registers_store_manager_id` (`store_manager_id`),
  CONSTRAINT `registers_ibfk_1` FOREIGN KEY (`store_manager_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `registers_pos_created_by_fk` FOREIGN KEY (`pos_created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registers`
--

LOCK TABLES `registers` WRITE;
/*!40000 ALTER TABLE `registers` DISABLE KEYS */;
INSERT INTO `registers` VALUES (1,5,'Register 1','POS-001','$2y$10$w5/l7tcOzklSnbfJLMgXjO.sgImsSigAAsF2JsVi7CfA0iNBBMqry',1,'2026-09-08 03:03:57','closed','2026-09-08 03:01:02','2026-09-08 03:03:57'),(2,5,'Register 2','POS-002','$2y$10$w5/l7tcOzklSnbfJLMgXjO.sgImsSigAAsF2JsVi7CfA0iNBBMqry',1,'2026-09-08 03:03:57','closed','2026-09-08 03:01:02','2026-09-08 03:03:57');
/*!40000 ALTER TABLE `registers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rejection_reasons`
--

DROP TABLE IF EXISTS `rejection_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rejection_reasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `hr_user_id` int(11) NOT NULL,
  `stage` enum('initial','final','screening','contract') NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `applicant_id` (`applicant_id`),
  KEY `hr_user_id` (`hr_user_id`),
  CONSTRAINT `rejection_reasons_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rejection_reasons_ibfk_2` FOREIGN KEY (`hr_user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rejection_reasons`
--

LOCK TABLES `rejection_reasons` WRITE;
/*!40000 ALTER TABLE `rejection_reasons` DISABLE KEYS */;
/*!40000 ALTER TABLE `rejection_reasons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requisition_items`
--

DROP TABLE IF EXISTS `requisition_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requisition_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `store_product_id` int(11) NOT NULL,
  `supplier_product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `estimated_unit_price` decimal(10,2) NOT NULL,
  `estimated_total` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ri_requisition` (`requisition_id`),
  KEY `idx_ri_store_product` (`store_product_id`),
  KEY `idx_ri_supplier_product` (`supplier_product_id`),
  CONSTRAINT `fk_ri_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ri_store_product` FOREIGN KEY (`store_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_ri_supplier_product` FOREIGN KEY (`supplier_product_id`) REFERENCES `supplier_products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisition_items`
--

LOCK TABLES `requisition_items` WRITE;
/*!40000 ALTER TABLE `requisition_items` DISABLE KEYS */;
INSERT INTO `requisition_items` VALUES (1,1,2,6,1,2.39,2.39,NULL,'2026-09-06 11:37:16'),(2,1,1,5,1,10.39,10.39,NULL,'2026-09-06 11:37:16'),(3,2,2,9,2,1.99,3.98,NULL,'2026-09-06 11:54:53'),(4,3,1,5,3,10.39,31.17,NULL,'2026-09-06 12:00:05'),(5,4,2,9,5,1.99,9.95,NULL,'2026-09-06 14:41:42'),(6,5,1,5,3,10.39,31.17,NULL,'2026-09-06 15:17:40');
/*!40000 ALTER TABLE `requisition_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requisitions`
--

DROP TABLE IF EXISTS `requisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requisitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_number` varchar(20) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `preferred_supplier_id` int(11) NOT NULL,
  `period_key` varchar(10) NOT NULL,
  `status` enum('draft','pending_budget_check','budget_rejected','pending_finance_head','approved','rejected','converted_to_po','cancelled') NOT NULL DEFAULT 'pending_budget_check',
  `order_date` date NOT NULL,
  `needed_by_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `rejected_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_requisition_number` (`requisition_number`),
  KEY `idx_req_status` (`status`),
  KEY `idx_req_department_period` (`department_id`,`period_key`),
  KEY `fk_req_requested_by` (`requested_by`),
  KEY `fk_req_supplier` (`preferred_supplier_id`),
  CONSTRAINT `fk_req_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  CONSTRAINT `fk_req_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_req_supplier` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisitions`
--

LOCK TABLES `requisitions` WRITE;
/*!40000 ALTER TABLE `requisitions` DISABLE KEYS */;
INSERT INTO `requisitions` VALUES (1,'REQ-2026-0001',5,1,1,'2026-09-H1','converted_to_po','2026-09-06',NULL,12.78,'',NULL,'2026-09-06 11:37:16','2026-09-06 11:37:16'),(2,'REQ-2026-0002',5,1,1,'2026-09-H1','cancelled','2026-09-06',NULL,3.98,'','Testing the reject path for the report','2026-09-06 11:54:53','2026-09-06 14:59:15'),(3,'REQ-2026-0003',5,1,1,'2026-09-H1','converted_to_po','2026-09-06',NULL,31.17,'',NULL,'2026-09-06 12:00:05','2026-09-06 12:00:05'),(4,'REQ-2026-0004',5,1,1,'2026-09-H1','converted_to_po','2026-09-06','2026-09-21',9.95,'',NULL,'2026-09-06 14:41:42','2026-09-06 14:59:15'),(5,'REQ-2026-0005',5,1,1,'2026-09-H1','cancelled','2026-09-06',NULL,31.17,'',NULL,'2026-09-06 15:17:40','2026-09-06 15:18:35');
/*!40000 ALTER TABLE `requisitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revenue_split_rules`
--

DROP TABLE IF EXISTS `revenue_split_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revenue_split_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(20) NOT NULL,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_remainder` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `department` (`department`),
  KEY `revenue_split_rules_ibfk_1` (`updated_by`),
  CONSTRAINT `revenue_split_rules_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revenue_split_rules`
--

LOCK TABLES `revenue_split_rules` WRITE;
/*!40000 ALTER TABLE `revenue_split_rules` DISABLE KEYS */;
INSERT INTO `revenue_split_rules` VALUES (1,'store',5.00,0,NULL,'2026-09-08 02:52:04','2026-09-08 02:52:04'),(2,'hr',60.00,0,NULL,'2026-09-08 02:52:04','2026-09-08 02:52:04'),(3,'general',0.00,1,NULL,'2026-09-08 02:52:04','2026-09-08 02:52:04'),(4,'finance',10.00,0,NULL,'2026-09-08 02:52:19','2026-09-08 02:52:19');
/*!40000 ALTER TABLE `revenue_split_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revenue_split_shares`
--

DROP TABLE IF EXISTS `revenue_split_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revenue_split_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `revenue_split_id` int(11) NOT NULL,
  `department` varchar(20) NOT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `revenue_split_id` (`revenue_split_id`),
  CONSTRAINT `revenue_split_shares_ibfk_1` FOREIGN KEY (`revenue_split_id`) REFERENCES `revenue_splits` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revenue_split_shares`
--

LOCK TABLES `revenue_split_shares` WRITE;
/*!40000 ALTER TABLE `revenue_split_shares` DISABLE KEYS */;
/*!40000 ALTER TABLE `revenue_split_shares` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `revenue_splits`
--

DROP TABLE IF EXISTS `revenue_splits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `revenue_splits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `period_label` varchar(50) NOT NULL,
  `budget_period` varchar(10) NOT NULL,
  `total_revenue` decimal(12,2) NOT NULL,
  `status` enum('draft','applied') NOT NULL DEFAULT 'draft',
  `computed_by` int(11) NOT NULL,
  `computed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `applied_by` int(11) DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `period_range` (`period_start`,`period_end`),
  KEY `computed_by` (`computed_by`),
  KEY `applied_by` (`applied_by`),
  CONSTRAINT `revenue_splits_ibfk_1` FOREIGN KEY (`computed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `revenue_splits_ibfk_2` FOREIGN KEY (`applied_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revenue_splits`
--

LOCK TABLES `revenue_splits` WRITE;
/*!40000 ALTER TABLE `revenue_splits` DISABLE KEYS */;
/*!40000 ALTER TABLE `revenue_splits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `schedules`
--

DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `is_rest_day` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_day` (`user_id`,`day_of_week`),
  CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_products`
--

DROP TABLE IF EXISTS `supplier_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) NOT NULL,
  `store_product_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `idx_sp_store_product` (`store_product_id`),
  CONSTRAINT `fk_sp_store_product` FOREIGN KEY (`store_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `supplier_products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_products`
--

LOCK TABLES `supplier_products` WRITE;
/*!40000 ALTER TABLE `supplier_products` DISABLE KEYS */;
INSERT INTO `supplier_products` VALUES (5,1,1,'Sample Book','A sample book for testing',10.39,100,1,'2026-08-21 12:54:24','2026-09-06 11:22:04'),(6,1,2,'Sample Pen','A sample pen for testing',2.39,100,1,'2026-08-21 12:54:24','2026-09-06 11:22:04'),(9,1,2,'Sample Pen','Test dup supplier pen',1.99,50,0,'2026-09-06 11:34:00','2026-09-06 15:00:48'),(10,3,1,'Sample Book','Northgate listing',9.99,80,1,'2026-09-06 15:00:17','2026-09-06 15:00:17'),(11,3,2,'Sample Pen','Northgate listing',2.15,200,1,'2026-09-06 15:00:17','2026-09-06 15:00:17');
/*!40000 ALTER TABLE `supplier_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Sample Supplier Inc.','John Supplier','supplier@shelfsense.com','09123456789',NULL,NULL,NULL,1,'2026-08-20 17:09:05','2026-08-21 12:54:24'),(3,'Northgate Office Supplies','Jamie Cruz','supplier2@shelfsense.com','09171234567','Dasmarinas, Cavite',NULL,NULL,1,'2026-09-06 15:00:17','2026-09-06 15:00:17');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainee_reports`
--

DROP TABLE IF EXISTS `trainee_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainee_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `month_number` int(11) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `department` varchar(20) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `report_content` text NOT NULL,
  `performance_rating` varchar(20) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `improvements` text DEFAULT NULL,
  `attendance_notes` text DEFAULT NULL,
  `recommendation` varchar(30) DEFAULT NULL,
  `status` enum('submitted','reviewed','forwarded','hr_reviewed') NOT NULL DEFAULT 'submitted',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewer_role` varchar(20) DEFAULT NULL,
  `reviewer_observation` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `forwarded_at` datetime DEFAULT NULL,
  `hr_head_id` int(11) DEFAULT NULL,
  `hr_head_notes` text DEFAULT NULL,
  `hr_head_reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_trainee_week` (`trainee_id`,`week_number`),
  KEY `idx_trainee` (`trainee_id`),
  KEY `idx_status` (`status`),
  KEY `fk_trainee_reports_trainer` (`trainer_id`),
  KEY `fk_trainee_reports_reviewer` (`reviewer_id`),
  KEY `fk_trainee_reports_hrhead` (`hr_head_id`),
  CONSTRAINT `fk_trainee_reports_hrhead` FOREIGN KEY (`hr_head_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_trainee_reports_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_trainee_reports_trainee` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trainee_reports_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainee_reports`
--

LOCK TABLES `trainee_reports` WRITE;
/*!40000 ALTER TABLE `trainee_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainee_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainee_weekly_reports`
--

DROP TABLE IF EXISTS `trainee_weekly_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainee_weekly_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `submitted_by` (`submitted_by`),
  KEY `trainer_reviewed_by` (`trainer_reviewed_by`),
  KEY `department_head_reviewed_by` (`department_head_reviewed_by`),
  CONSTRAINT `trainee_weekly_reports_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`),
  CONSTRAINT `trainee_weekly_reports_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `trainee_weekly_reports_ibfk_3` FOREIGN KEY (`trainer_reviewed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `trainee_weekly_reports_ibfk_4` FOREIGN KEY (`department_head_reviewed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainee_weekly_reports`
--

LOCK TABLES `trainee_weekly_reports` WRITE;
/*!40000 ALTER TABLE `trainee_weekly_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainee_weekly_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainees`
--

DROP TABLE IF EXISTS `trainees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `trainee_salary` decimal(10,2) DEFAULT NULL,
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
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `applicant_id` (`applicant_id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `trainees_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trainees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `trainees_ibfk_3` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainees`
--

LOCK TABLES `trainees` WRITE;
/*!40000 ALTER TABLE `trainees` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainer_assignments`
--

DROP TABLE IF EXISTS `trainer_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainer_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` int(11) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unassigned_at` datetime DEFAULT NULL,
  `reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_trainee` (`trainee_id`),
  KEY `fk_trainer_assignments_trainer` (`trainer_id`),
  KEY `fk_trainer_assignments_by` (`assigned_by`),
  CONSTRAINT `fk_trainer_assignments_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_trainer_assignments_trainee` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trainer_assignments_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainer_assignments`
--

LOCK TABLES `trainer_assignments` WRITE;
/*!40000 ALTER TABLE `trainer_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainer_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_dashboard_layouts`
--

DROP TABLE IF EXISTS `user_dashboard_layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_dashboard_layouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `dashboard_key` varchar(50) NOT NULL,
  `widget_order` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_dashboard` (`user_id`,`dashboard_key`),
  CONSTRAINT `fk_user_dashboard_layouts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_dashboard_layouts`
--

LOCK TABLES `user_dashboard_layouts` WRITE;
/*!40000 ALTER TABLE `user_dashboard_layouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_dashboard_layouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `pending_profile_pic` varchar(255) DEFAULT NULL,
  `pending_profile_pic_status` enum('none','pending','rejected') NOT NULL DEFAULT 'none',
  `pending_profile_pic_reason` varchar(255) DEFAULT NULL,
  `hired_date` timestamp NULL DEFAULT NULL,
  `sick_leave_balance` decimal(5,2) DEFAULT 15.00,
  `vacation_leave_balance` decimal(5,2) DEFAULT 15.00,
  `emergency_leave_balance` decimal(5,2) DEFAULT 5.00,
  `other_leave_balance` decimal(5,2) DEFAULT 0.00,
  `maternity_leave_balance` decimal(5,2) DEFAULT 60.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `show_dashboard_tour` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `employee_number` (`employee_number`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SA-001','Stephen','Frias',NULL,'stephenfrias4@gmail.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','owner',5,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-08 04:07:01',1),(2,'HH-001','Maria','Santos',NULL,'hr.head@shelfsense.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','hr_head',4,1,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-08 03:51:28',1),(3,'HS-001','Juan','Dela Cruz',NULL,'hr.staff@shelfsense.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','hr_staff',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-08 03:51:28',1),(4,'HS-002','Ana','Reyes',NULL,'stephenfrias04@gmail.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','hr_staff',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-08 04:07:01',1),(5,'SM-001','Store','Manager',NULL,'store.manager@shelfsense.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','store_manager',4,0,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-06 11:33:39',1),(6,'FH-001','Finance','Head',NULL,'finance.head@shelfsense.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','finance_head',4,0,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-06 11:33:39',1),(7,'FS-001','Finance','Staff',NULL,'finance.staff@shelfsense.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','finance_staff',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-06 11:33:39',1),(8,'FS-002','Sarah','Williams',NULL,'finance.staff2@shelfsense.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','finance_staff',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-08 04:07:01',1),(9,'CA-001','Cashier','Test',NULL,'employee@shelfsense.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','employee',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-08 04:07:01',1),(10,'CA-002','John','Doe',NULL,'rumbines.allen@ncst.edu.ph','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','employee',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-08 04:07:01',1),(11,'TR-001','Trainee','User',NULL,'trainee@shelfsense.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','trainee',0,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-09-08 04:07:01',1),(12,'SUP-001','Sample','Supplier',NULL,'supplier@shelfsense.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','supplier',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-21 03:04:09','2026-09-06 11:33:52',1),(13,'SUP-002','Northgate','Supplies',NULL,'supplier2@shelfsense.com','$2y$10$ai3l/XJb5tdOU2Be7frVS.Tz5nS8DadjTkOJD6UuYHHB2E2KUKk6W','supplier',1,0,0,1,0,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-09-06 15:00:17','2026-09-06 15:00:17',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `variance_tolerance_settings`
--

DROP TABLE IF EXISTS `variance_tolerance_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `variance_tolerance_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `price_tolerance_percent` decimal(5,2) NOT NULL DEFAULT 2.00,
  `price_tolerance_amount` decimal(10,2) NOT NULL DEFAULT 50.00,
  `quantity_tolerance_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_variance_settings_user` (`updated_by`),
  CONSTRAINT `fk_variance_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `variance_tolerance_settings`
--

LOCK TABLES `variance_tolerance_settings` WRITE;
/*!40000 ALTER TABLE `variance_tolerance_settings` DISABLE KEYS */;
INSERT INTO `variance_tolerance_settings` VALUES (1,2.00,50.00,0.00,NULL,'2026-09-06 11:20:01');
/*!40000 ALTER TABLE `variance_tolerance_settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-08 12:08:31
