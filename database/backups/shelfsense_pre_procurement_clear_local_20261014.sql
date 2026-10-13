-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: shelfsense
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicant_skill_ratings`
--

LOCK TABLES `applicant_skill_ratings` WRITE;
/*!40000 ALTER TABLE `applicant_skill_ratings` DISABLE KEYS */;
INSERT INTO `applicant_skill_ratings` VALUES (6,4,'cash_handling',3,'2026-10-11 02:39:01'),(7,4,'card_digital_payments',3,'2026-10-11 02:39:01'),(8,4,'drawer_reconciliation',3,'2026-10-11 02:39:01'),(9,4,'register_speed_accuracy',3,'2026-10-11 02:39:01'),(10,4,'deescalating_customers',3,'2026-10-11 02:39:02'),(11,5,'cash_handling',4,'2026-10-11 02:42:08'),(12,5,'card_digital_payments',4,'2026-10-11 02:42:08'),(13,5,'drawer_reconciliation',4,'2026-10-11 02:42:08'),(14,5,'register_speed_accuracy',4,'2026-10-11 02:42:09'),(15,5,'deescalating_customers',4,'2026-10-11 02:42:09');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicants`
--

LOCK TABLES `applicants` WRITE;
/*!40000 ALTER TABLE `applicants` DISABLE KEYS */;
INSERT INTO `applicants` VALUES (1,'test','test','test','test@gmail.com','09264550078',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Philippines','Employee',1,'uploads/resumes/cd59030f7be000ae2bf73bc363643ee9.docx','screening','2026-08-30 09:55:42','2026-10-11 11:49:18'),(2,'Maya','Cruz',NULL,'maya.cruz.trainee@shelfsense.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Philippines','Employee',NULL,'seed/no_resume.pdf','hired','2026-09-08 04:20:46','2026-09-09 19:21:45'),(4,'Allen','Rumbines','','allenrumbines06@gmail.com','09264550078','2006-05-20','Agusan Del Norte','160200000','Carmen','160204000','Cahayagan','160204001','123123','123123','asdasd','1231','Philippines','Employee',4,'uploads/resumes/7aa0d2ab885f046d6face2af267b59af.pdf','pending','2026-10-11 02:39:01','2026-10-11 02:54:14'),(5,'Allen','Rumbines','','rumbines.allen@ncst.edu.ph','09264550078','2002-10-17','Cavite','042100000','Alfonso','042101000','Barangay II (Pob.)','042101003','123123124','123123123','asdasasdasdd','2132','Philippines','Employee',4,'uploads/resumes/75635c0e59ca8a7780d1dd037f4446c8.pdf','pending','2026-10-11 02:42:08','2026-10-11 02:42:08');
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
  `verification_method` enum('manual','face') NOT NULL DEFAULT 'manual',
  `verification_photo` varchar(255) DEFAULT NULL,
  `match_distance` decimal(6,4) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (3,9,'2026-09-09','18:30:02','18:33:33',0.00,'present',NULL,NULL,9,'face',NULL,0.0000,NULL,'2026-09-09 16:30:02','2026-09-09 16:33:33'),(5,9,'2026-10-11','03:53:19','03:59:48',0.00,'present',NULL,NULL,9,'manual',NULL,NULL,NULL,'2026-10-11 01:53:20','2026-10-11 01:59:48');
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_transactions`
--

LOCK TABLES `budget_transactions` WRITE;
/*!40000 ALTER TABLE `budget_transactions` DISABLE KEYS */;
INSERT INTO `budget_transactions` VALUES (1,1,'2026-10-H1','adjustment',100000.00,'manual',NULL,6,'','2026-10-13 14:14:35'),(2,1,'2026-10-H1','reservation',6000.00,'requisition',1,6,NULL,'2026-10-13 14:17:12'),(3,1,'2026-10-H1','reservation',300.00,'requisition',2,6,NULL,'2026-10-13 15:15:51'),(4,1,'2026-10-H1','reservation',26000.00,'requisition',3,6,NULL,'2026-10-13 15:56:27');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgets`
--

LOCK TABLES `budgets` WRITE;
/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
INSERT INTO `budgets` VALUES (1,1,'2026-09-H1',0.00,'2026-09-06 11:39:39','2026-09-06 11:39:39'),(5,1,'2026-10-H1',0.00,'2026-10-13 14:14:35','2026-10-13 14:14:35');
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
-- Table structure for table `catalog_overrides`
--

DROP TABLE IF EXISTS `catalog_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catalog_overrides` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `discount_type` enum('percent','fixed') DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `catalog_overrides_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catalog_overrides_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catalog_overrides`
--

LOCK TABLES `catalog_overrides` WRITE;
/*!40000 ALTER TABLE `catalog_overrides` DISABLE KEYS */;
/*!40000 ALTER TABLE `catalog_overrides` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Books',NULL,1,'2026-08-20 17:09:05'),(2,'School Supplies',NULL,1,'2026-08-20 17:09:05'),(3,'Merchandise',NULL,1,'2026-08-20 17:09:05'),(4,'Beverages',NULL,1,'2026-08-20 17:09:05'),(5,'Snacks',NULL,1,'2026-08-20 17:09:05'),(6,'Papers','Bond paper, construction paper, and other paper stock',1,'2026-10-11 02:01:21'),(7,'Pens','Ballpoint, gel, and other pens',1,'2026-10-11 02:01:21'),(8,'Folders','Document folders and envelopes',1,'2026-10-11 02:01:21'),(9,'Notebooks','Notebooks, pads, and composition books',1,'2026-10-11 02:01:21'),(10,'Pencils','Wooden and mechanical pencils',1,'2026-10-11 02:01:21'),(11,'Sharpeners','Pencil sharpeners',1,'2026-10-11 02:01:21'),(12,'Erasers','Erasers',1,'2026-10-11 02:01:21'),(13,'Rulers','Rulers and other measuring tools',1,'2026-10-11 02:01:21'),(14,'Scissors','Scissors',1,'2026-10-11 02:01:21'),(15,'Markers','Permanent and whiteboard markers',1,'2026-10-11 02:01:21'),(16,'Highlighters','Highlighters',1,'2026-10-11 02:01:21'),(17,'Glues','Glue sticks and liquid glue',1,'2026-10-11 02:01:21'),(18,'Tapes','Adhesive and packing tape',1,'2026-10-11 02:01:21');
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
  `shift` enum('opening','closing','midshift') DEFAULT NULL,
  `schedule_start` time DEFAULT NULL,
  `schedule_end` time DEFAULT NULL,
  `salary` decimal(10,2) NOT NULL,
  `job_details` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `status` enum('pending','accepted','declined') DEFAULT 'pending',
  `salary_range_min` decimal(10,2) DEFAULT NULL,
  `salary_range_max` decimal(10,2) DEFAULT NULL,
  `decision_deadline` date DEFAULT NULL,
  `response_token` varchar(64) DEFAULT NULL,
  `offered_by` int(11) DEFAULT NULL,
  `offered_at` timestamp NULL DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `declined_at` timestamp NULL DEFAULT NULL,
  `response_notes` text DEFAULT NULL,
  `rest_days` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_contract_type` (`user_id`,`contract_type`),
  UNIQUE KEY `response_token` (`response_token`),
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
-- Table structure for table `deal_items`
--

DROP TABLE IF EXISTS `deal_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deal_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deal_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_deal_product` (`deal_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `deal_items_ibfk_1` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deal_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deal_items`
--

LOCK TABLES `deal_items` WRITE;
/*!40000 ALTER TABLE `deal_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `deal_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deals`
--

DROP TABLE IF EXISTS `deals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `deals_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deals`
--

LOCK TABLES `deals` WRITE;
/*!40000 ALTER TABLE `deals` DISABLE KEYS */;
/*!40000 ALTER TABLE `deals` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
INSERT INTO `email_logs` VALUES (1,'test.trainee@example.com','Final Interview Scheduled',NULL,'failed','2026-08-25 20:09:49'),(2,'test.trainee@example.com','Contract Offered',NULL,'failed','2026-08-25 20:10:08'),(3,'test.trainee@example.com','Congratulations! You\'re Hired!',NULL,'failed','2026-08-25 20:10:17'),(4,'test@gmail.com','Application Received',NULL,'sent','2026-08-30 09:55:47'),(5,'test@gmail.com','Initial Interview Scheduled',NULL,'sent','2026-08-30 10:01:04'),(8,'qa_finalpass_temp@shelfsense.test','Your Employment Contract - ShelfSense',NULL,'sent','2026-08-30 12:09:09'),(9,'qa_salary_temp@shelfsense.test','Your Trainee Contract - ShelfSense',NULL,'sent','2026-08-30 12:21:15'),(10,'employee@shelfsense.com','New Trainee Assigned - ShelfSense',NULL,'sent','2026-08-30 12:21:18'),(11,'supplier@shelfsense.com','New Purchase Order PO-2026-0001 - ShelfSense',NULL,'failed','2026-09-06 11:40:44'),(12,'supplier@shelfsense.com','Payment Sent - PO PO-2026-0001',NULL,'failed','2026-09-06 11:50:14'),(13,'supplier@shelfsense.com','New Purchase Order PO-2026-0003 - ShelfSense',NULL,'failed','2026-09-06 12:04:53'),(14,'supplier@shelfsense.com','Payment Sent - PO PO-2026-0003',NULL,'failed','2026-09-06 12:20:31'),(15,'supplier@shelfsense.com','New Purchase Order PO-2026-0004 - ShelfSense',NULL,'failed','2026-09-06 14:50:15'),(16,'supplier@shelfsense.com','Payment Sent - PO PO-2026-0004',NULL,'failed','2026-09-06 15:13:45'),(19,'test.allen@example.com','Application Received',NULL,'sent','2026-10-11 02:28:39'),(20,'allenrumbines06@gmail.com','Application Received',NULL,'sent','2026-10-11 02:39:06'),(21,'rumbines.allen@ncst.edu.ph','Application Received',NULL,'sent','2026-10-11 02:42:13'),(22,'allenrumbines06@gmail.com','Initial Interview Scheduled',NULL,'sent','2026-10-11 02:53:57'),(23,'test@gmail.com','Your Trainee Contract - ShelfSense',NULL,'sent','2026-10-11 03:51:33'),(24,'employee@shelfsense.com','New Trainee Assigned - ShelfSense',NULL,'sent','2026-10-11 03:51:37'),(25,'test@gmail.com','Your Trainee Contract - ShelfSense',NULL,'sent','2026-10-11 08:37:11'),(26,'rumbines.allen@ncst.edu.ph','New Trainee Assigned - ShelfSense',NULL,'sent','2026-10-11 08:37:15'),(27,'test@gmail.com','Your Trainee Contract - ShelfSense',NULL,'sent','2026-10-11 11:23:21'),(28,'rumbines.allen@ncst.edu.ph','New Trainee Assigned - ShelfSense',NULL,'sent','2026-10-11 11:23:25'),(29,'test@gmail.com','Your Trainee Contract - ShelfSense',NULL,'sent','2026-10-11 11:25:20'),(30,'rumbines.allen@ncst.edu.ph','New Trainee Assigned - ShelfSense',NULL,'sent','2026-10-11 11:25:24'),(31,'test@gmail.com','Your Trainee Contract - ShelfSense',NULL,'sent','2026-10-11 11:26:27'),(32,'rumbines.allen@ncst.edu.ph','New Trainee Assigned - ShelfSense',NULL,'sent','2026-10-11 11:26:31'),(33,'test@gmail.com','Your Trainee Contract - ShelfSense',NULL,'sent','2026-10-11 11:49:22'),(34,'rumbines.allen@ncst.edu.ph','New Trainee Assigned - ShelfSense',NULL,'sent','2026-10-11 11:49:26'),(35,'supplier2@shelfsense.com','New Purchase Order PO-2026-0001 - ShelfSense',NULL,'failed','2026-10-13 14:17:45'),(36,'supplier2@shelfsense.com','New Purchase Order PO-2026-0002 - ShelfSense',NULL,'failed','2026-10-13 15:16:29'),(37,'supplier2@shelfsense.com','New Purchase Order PO-2026-0003 - ShelfSense',NULL,'failed','2026-10-13 15:57:02');
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `face_enrollments`
--

DROP TABLE IF EXISTS `face_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `face_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `descriptors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Array of 128-float face descriptor arrays, one per captured angle' CHECK (json_valid(`descriptors`)),
  `consent_at` datetime NOT NULL COMMENT 'When the employee accepted the biometric-data notice for this enrollment',
  `enrolled_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_face_enrollment_user` (`user_id`),
  CONSTRAINT `face_enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `face_enrollments`
--

LOCK TABLES `face_enrollments` WRITE;
/*!40000 ALTER TABLE `face_enrollments` DISABLE KEYS */;
/*!40000 ALTER TABLE `face_enrollments` ENABLE KEYS */;
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
  `condition` enum('good','damaged','missing','excess') NOT NULL DEFAULT 'good',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gri_receipt` (`goods_receipt_id`),
  KEY `idx_gri_po_item` (`po_item_id`),
  CONSTRAINT `fk_gri_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`),
  CONSTRAINT `fk_gri_receipt` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipt_items`
--

LOCK TABLES `goods_receipt_items` WRITE;
/*!40000 ALTER TABLE `goods_receipt_items` DISABLE KEYS */;
INSERT INTO `goods_receipt_items` VALUES (1,1,1,100,'good',NULL),(2,2,2,5,'good',NULL);
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
  `gr_number` varchar(30) NOT NULL,
  `po_id` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `status` enum('completed') NOT NULL DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gr_number` (`gr_number`),
  KEY `idx_gr_po` (`po_id`),
  KEY `fk_gr_received_by` (`received_by`),
  CONSTRAINT `fk_gr_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `fk_gr_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipts`
--

LOCK TABLES `goods_receipts` WRITE;
/*!40000 ALTER TABLE `goods_receipts` DISABLE KEYS */;
INSERT INTO `goods_receipts` VALUES (1,'GR-2026-0001',1,5,'2026-10-13','completed',NULL,'2026-10-13 15:02:11'),(2,'GR-2026-0002',2,5,'2026-10-13','completed',NULL,'2026-10-13 15:21:47');
/*!40000 ALTER TABLE `goods_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `holidays`
--

DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `holiday_date` date NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('regular','special_non_working') NOT NULL DEFAULT 'special_non_working',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `holiday_date` (`holiday_date`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `holidays_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `holidays`
--

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES (1,'2026-01-01','New Year\'s Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(2,'2026-02-25','EDSA People Power Anniversary','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(3,'2026-04-01','Maundy Thursday','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(4,'2026-04-02','Good Friday','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(5,'2026-04-03','Black Saturday','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(6,'2026-04-09','Araw ng Kagitingan','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(7,'2026-05-01','Labor Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(8,'2026-06-12','Independence Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(9,'2026-08-21','Ninoy Aquino Day','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(10,'2026-08-31','National Heroes Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(11,'2026-11-01','All Saints\' Day','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(12,'2026-11-30','Bonifacio Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(13,'2026-12-08','Feast of the Immaculate Conception','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(14,'2026-12-24','Christmas Eve','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(15,'2026-12-25','Christmas Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(16,'2026-12-30','Rizal Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(17,'2026-12-31','Last Day of the Year','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(18,'2027-01-01','New Year\'s Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(19,'2027-02-25','EDSA People Power Anniversary','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(20,'2027-03-24','Maundy Thursday','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(21,'2027-03-25','Good Friday','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(22,'2027-03-26','Black Saturday','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(23,'2027-04-09','Araw ng Kagitingan','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(24,'2027-05-01','Labor Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(25,'2027-06-12','Independence Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(26,'2027-08-21','Ninoy Aquino Day','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(27,'2027-08-30','National Heroes Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(28,'2027-11-01','All Saints\' Day','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(29,'2027-11-30','Bonifacio Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(30,'2027-12-08','Feast of the Immaculate Conception','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(31,'2027-12-24','Christmas Eve','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(32,'2027-12-25','Christmas Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(33,'2027-12-30','Rizal Day','regular',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08'),(34,'2027-12-31','Last Day of the Year','special_non_working',NULL,'2026-09-13 05:30:08','2026-09-13 05:30:08');
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_items`
--

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
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
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_invoice_number` (`invoice_number`),
  UNIQUE KEY `uniq_invoice_po` (`po_id`),
  KEY `idx_inv_po` (`po_id`),
  KEY `idx_inv_supplier` (`supplier_id`),
  KEY `idx_inv_match_status` (`match_status`),
  CONSTRAINT `fk_inv_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  CONSTRAINT `fk_inv_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_posting_messages`
--

DROP TABLE IF EXISTS `job_posting_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_posting_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_posting_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `action` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_jpm_posting` (`job_posting_id`),
  KEY `fk_jpm_author` (`author_id`),
  CONSTRAINT `fk_jpm_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jpm_posting` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_posting_messages`
--

LOCK TABLES `job_posting_messages` WRITE;
/*!40000 ALTER TABLE `job_posting_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_posting_messages` ENABLE KEYS */;
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
  `open_until` date DEFAULT NULL,
  `status` enum('draft','pending_approval','approved','rejected','closed','archived') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approval_message` text DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_postings`
--

LOCK TABLES `job_postings` WRITE;
/*!40000 ALTER TABLE `job_postings` DISABLE KEYS */;
INSERT INTO `job_postings` VALUES (1,NULL,'TestJob','Cashier','Front Department','asdasd','Cashier','Full-Time','test','test','test',123213.00,123321.00,3,'2026-09-03','approved',2,'2026-08-30 17:43:36',2,'2026-08-30 17:43:40',NULL,NULL,NULL,NULL,NULL,'2026-08-30 09:43:36','2026-08-30 09:43:40'),(2,NULL,'TestJob2','HR Staff','Human Resources Department','asdasd1','Cashier','Full-Time','test','test','test',123213.00,123321.00,32,'2026-09-04','approved',2,'2026-08-30 17:44:57',2,'2026-08-30 17:45:00',NULL,NULL,NULL,NULL,NULL,'2026-08-30 09:44:57','2026-08-30 11:30:57'),(3,NULL,'Test Cashier Position','','Front Department',NULL,'test-cashier-position-1789797520547','Full-Time','',NULL,NULL,NULL,NULL,NULL,NULL,'draft',18,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-19 05:58:40','2026-10-11 02:42:47'),(4,NULL,'TestJob123','Cashier','Front Department','asdasd','testjob123-1791684335353','Full-Time','testasdasdasdasdasdasdasdasdasjbfahsbfaksbfjasbfkj\nasdasdasdasdas\nasdasdasdasdasd\n# scasdw3sadwasd\nasdasdasdasd','eee','asdasd',1400.00,2000.00,20,'2027-04-11','approved',3,'2026-10-11 10:05:35',2,'2026-10-11 10:07:10',NULL,NULL,NULL,NULL,NULL,'2026-10-11 02:05:35','2026-10-11 02:07:10');
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
) ENGINE=InnoDB AUTO_INCREMENT=192 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (2,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0002 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 17:05:18'),(3,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0002 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 17:05:18'),(5,6,'payment_request_pending','Payment request for requisition #REQ-2026-0002 is pending approval. Amount: ₱28.68','?page=finance_head_payment_requests',0,'2026-08-23 17:33:29'),(6,12,'payment_completed','Payment for requisition #REQ-2026-0002 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-23 17:34:35'),(7,7,'payment_request_approved','Payment request for requisition #REQ-2026-0002 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-23 17:34:35'),(10,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0003 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 19:11:10'),(11,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0003 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 19:11:11'),(13,6,'payment_request_pending','Payment request for requisition #REQ-2026-0003 is pending approval. Amount: ₱35.85','?page=finance_head_payment_requests',0,'2026-08-23 19:11:55'),(14,12,'payment_completed','Payment for requisition #REQ-2026-0003 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-24 17:22:48'),(15,7,'payment_request_approved','Payment request for requisition #REQ-2026-0003 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-24 17:22:48'),(34,3,'new_application','New application from test test for TestJob position','?page=hr_applicants',0,'2026-08-30 09:55:47'),(35,4,'new_application','New application from test test for TestJob position','?page=hr_applicants',0,'2026-08-30 09:55:47'),(36,3,'interview_scheduled','initial interview scheduled for test test',NULL,0,'2026-08-30 10:01:00'),(51,7,'po_pending_budget_check','New Purchase Order PO-2026-0001 (₱12.78) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 11:37:16'),(52,8,'po_pending_budget_check','New Purchase Order PO-2026-0001 (₱12.78) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 11:37:16'),(53,6,'po_pending_approval','Purchase Order PO-2026-0001 passed budget check and needs your approval.','?page=finance_head_requisitions',0,'2026-09-06 11:38:38'),(54,7,'po_pending_dispatch','Purchase Order PO-2026-0001 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 11:39:40'),(55,8,'po_pending_dispatch','Purchase Order PO-2026-0001 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 11:39:40'),(56,5,'po_approved','Purchase Order PO-2026-0001 (requisition #REQ-2026-0001) was approved by Finance Head.','?page=store_manager_requisitions',0,'2026-09-06 11:39:40'),(57,12,'po_received','New Purchase Order PO-2026-0001 has been sent to you. Please review and confirm.','?page=supplier_requisitions',0,'2026-09-06 11:40:44'),(58,5,'po_confirmed','Supplier confirmed Purchase Order PO-2026-0001 for requisition #REQ-2026-0001.','?page=store_manager_requisitions',0,'2026-09-06 11:41:51'),(59,5,'po_shipped','PO PO-2026-0001 has been shipped by the supplier.','?page=store_manager_requisitions',0,'2026-09-06 11:43:17'),(60,7,'goods_received','Goods fully received for PO PO-2026-0001 (requisition #REQ-2026-0001).','?page=finance_staff_payment_requests',0,'2026-09-06 11:45:29'),(61,8,'goods_received','Goods fully received for PO PO-2026-0001 (requisition #REQ-2026-0001).','?page=finance_staff_payment_requests',0,'2026-09-06 11:45:29'),(62,7,'invoice_reconciled','Invoice INV-2026-0001 for PO PO-2026-0001 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 11:46:42'),(63,8,'invoice_reconciled','Invoice INV-2026-0001 for PO PO-2026-0001 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 11:46:42'),(64,6,'po_payment_requested','Payment requested for PO PO-2026-0001 (₱12.78).','?page=finance_head_payment_requests',0,'2026-09-06 11:48:47'),(65,12,'po_payment_received','Payment for PO PO-2026-0001 has been sent.','?page=supplier_requisitions',0,'2026-09-06 11:50:14'),(66,7,'po_payment_approved','Payment request for PO PO-2026-0001 was approved and disbursed.','?page=finance_staff_payment_requests',0,'2026-09-06 11:50:14'),(67,7,'po_pending_budget_check','New Purchase Order PO-2026-0002 (₱3.98) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 11:54:53'),(68,8,'po_pending_budget_check','New Purchase Order PO-2026-0002 (₱3.98) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 11:54:53'),(69,6,'po_pending_approval','Purchase Order PO-2026-0002 passed budget check and needs your approval.','?page=finance_head_requisitions',0,'2026-09-06 11:56:19'),(70,5,'po_rejected','Purchase Order PO-2026-0002 was rejected by Finance Head. Reason: Testing the reject path for the report','?page=store_manager_requisitions',0,'2026-09-06 11:58:05'),(71,7,'po_pending_budget_check','New Purchase Order PO-2026-0003 (₱31.17) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 12:00:05'),(72,8,'po_pending_budget_check','New Purchase Order PO-2026-0003 (₱31.17) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 12:00:05'),(73,6,'po_pending_approval','Purchase Order PO-2026-0003 passed budget check and needs your approval.','?page=finance_head_requisitions',0,'2026-09-06 12:01:35'),(74,7,'po_pending_dispatch','Purchase Order PO-2026-0003 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 12:03:28'),(75,8,'po_pending_dispatch','Purchase Order PO-2026-0003 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 12:03:28'),(76,5,'po_approved','Purchase Order PO-2026-0003 (requisition #REQ-2026-0003) was approved by Finance Head.','?page=store_manager_requisitions',0,'2026-09-06 12:03:28'),(77,12,'po_received','New Purchase Order PO-2026-0003 has been sent to you. Please review and confirm.','?page=supplier_requisitions',0,'2026-09-06 12:04:53'),(78,5,'po_counter_proposed','Supplier proposed changes to Purchase Order PO-2026-0003 for requisition #REQ-2026-0003.','?page=store_manager_requisitions',0,'2026-09-06 12:06:44'),(79,12,'po_counter_accepted','Your counter-proposal for PO PO-2026-0003 was accepted.','?page=supplier_requisitions',0,'2026-09-06 12:09:29'),(80,5,'po_shipped','PO PO-2026-0003 has been shipped by the supplier.','?page=store_manager_requisitions',0,'2026-09-06 12:11:32'),(81,7,'goods_received','Goods partially received for PO PO-2026-0003 (requisition #REQ-2026-0003).','?page=finance_staff_payment_requests',0,'2026-09-06 12:13:52'),(82,8,'goods_received','Goods partially received for PO PO-2026-0003 (requisition #REQ-2026-0003).','?page=finance_staff_payment_requests',0,'2026-09-06 12:13:52'),(83,7,'goods_received','Goods fully received for PO PO-2026-0003 (requisition #REQ-2026-0003).','?page=finance_staff_payment_requests',0,'2026-09-06 12:14:37'),(84,8,'goods_received','Goods fully received for PO PO-2026-0003 (requisition #REQ-2026-0003).','?page=finance_staff_payment_requests',0,'2026-09-06 12:14:37'),(85,7,'invoice_reconciled','Invoice INV-2026-0002 for PO PO-2026-0003 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 12:16:29'),(86,8,'invoice_reconciled','Invoice INV-2026-0002 for PO PO-2026-0003 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 12:16:29'),(87,6,'po_payment_requested','Payment requested for PO PO-2026-0003 (₱20.78).','?page=finance_head_payment_requests',0,'2026-09-06 12:18:35'),(88,12,'po_payment_received','Payment for PO PO-2026-0003 has been sent.','?page=supplier_requisitions',0,'2026-09-06 12:20:31'),(89,7,'po_payment_approved','Payment request for PO PO-2026-0003 was approved and disbursed.','?page=finance_staff_payment_requests',0,'2026-09-06 12:20:31'),(90,7,'po_pending_budget_check','New Purchase Order PO-2026-0004 (₱9.95) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 14:41:42'),(91,8,'po_pending_budget_check','New Purchase Order PO-2026-0004 (₱9.95) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 14:41:42'),(92,6,'po_pending_approval','Purchase Order PO-2026-0004 passed budget check and needs your approval.','?page=finance_head_requisitions',0,'2026-09-06 14:48:46'),(93,7,'po_pending_dispatch','Purchase Order PO-2026-0004 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 14:49:07'),(94,8,'po_pending_dispatch','Purchase Order PO-2026-0004 was approved and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-09-06 14:49:07'),(95,5,'po_approved','Purchase Order PO-2026-0004 (requisition #REQ-2026-0004) was approved by Finance Head.','?page=store_manager_requisitions',0,'2026-09-06 14:49:07'),(96,12,'po_received','New Purchase Order PO-2026-0004 has been sent to you. Please review and confirm.','?page=supplier_requisitions',0,'2026-09-06 14:50:15'),(97,5,'po_confirmed','Supplier confirmed Purchase Order PO-2026-0004 for requisition #REQ-2026-0004.','?page=store_manager_requisitions',0,'2026-09-06 15:01:49'),(98,5,'po_shipped','PO PO-2026-0004 has been shipped by the supplier.','?page=store_manager_requisitions',0,'2026-09-06 15:02:16'),(99,7,'goods_received','Goods fully received for PO PO-2026-0004 (requisition #REQ-2026-0004).','?page=finance_staff_payment_requests',0,'2026-09-06 15:07:42'),(100,8,'goods_received','Goods fully received for PO PO-2026-0004 (requisition #REQ-2026-0004).','?page=finance_staff_payment_requests',0,'2026-09-06 15:07:42'),(101,7,'invoice_reconciled','Invoice INV-2026-0003 for PO PO-2026-0004 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 15:12:35'),(102,8,'invoice_reconciled','Invoice INV-2026-0003 for PO PO-2026-0004 matched cleanly and is reconciled.','?page=finance_staff_payment_requests',0,'2026-09-06 15:12:35'),(103,6,'po_payment_requested','Payment requested for PO PO-2026-0004 (₱9.95).','?page=finance_head_payment_requests',1,'2026-09-06 15:13:13'),(104,12,'po_payment_received','Payment for PO PO-2026-0004 has been sent.','?page=supplier_requisitions',0,'2026-09-06 15:13:45'),(105,7,'po_payment_approved','Payment request for PO PO-2026-0004 was approved and disbursed.','?page=finance_staff_payment_requests',0,'2026-09-06 15:13:45'),(106,7,'po_pending_budget_check','New Purchase Order PO-2026-0005 (₱31.17) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 15:17:41'),(107,8,'po_pending_budget_check','New Purchase Order PO-2026-0005 (₱31.17) needs a budget check.','?page=finance_staff_requisitions',0,'2026-09-06 15:17:41'),(108,9,'order_completed','Order #POS-20260909-0001 completed. Total: ₱2.99','?page=pos_orders&view=1',0,'2026-09-09 16:36:23'),(109,12,'po_payment_received','Payment for PO PO-TEST-SIM has been sent.','?page=supplier_requisitions',0,'2026-09-09 17:07:35'),(110,6,'po_payment_approved','Payment request for PO PO-TEST-SIM was approved and disbursed.','?page=finance_staff_payment_requests',0,'2026-09-09 17:07:35'),(111,12,'po_payment_received','Payment for PO PO-TEST-SIM2 has been sent.','?page=supplier_requisitions',0,'2026-09-09 17:20:26'),(112,7,'po_payment_approved','Payment request for PO PO-TEST-SIM2 was approved and disbursed.','?page=finance_staff_payment_requests',0,'2026-09-09 17:20:26'),(113,7,'po_payment_rejected','Payment request for PO PO-TEST-SIM3 was rejected. Reason: Testing rejection visibility for Finance Staff','?page=finance_staff_payment_requests',0,'2026-09-09 17:21:03'),(121,2,'job_posting_submitted','Job posting \"TestJob123\" is awaiting your review.','?page=hr_job_posting_approvals&posting_id=4',0,'2026-10-11 02:05:35'),(122,3,'job_posting_approved','Your job posting \"TestJob123\" was approved and is now public.','?page=hr_job_postings&posting_id=4',0,'2026-10-11 02:07:10'),(123,2,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=3',0,'2026-10-11 02:28:40'),(124,3,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=3',0,'2026-10-11 02:28:40'),(125,4,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=3',0,'2026-10-11 02:28:40'),(126,18,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=3',0,'2026-10-11 02:28:40'),(127,2,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=4',0,'2026-10-11 02:39:06'),(128,3,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=4',0,'2026-10-11 02:39:06'),(129,4,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=4',0,'2026-10-11 02:39:06'),(130,18,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=4',0,'2026-10-11 02:39:06'),(131,2,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=5',0,'2026-10-11 02:42:13'),(132,3,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=5',0,'2026-10-11 02:42:13'),(133,4,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=5',0,'2026-10-11 02:42:13'),(134,18,'new_application','New application from Allen Rumbines for TestJob123 position','?page=hr_applicants&applicant_id=5',0,'2026-10-11 02:42:13'),(135,18,'interview_scheduled','initial interview scheduled for Allen Rumbines',NULL,0,'2026-10-11 02:53:53'),(136,3,'trainee_created','Trainee account created for test test. Trainer Cashier Test is locked.',NULL,0,'2026-10-11 03:51:28'),(138,9,'trainee_assigned','You have been assigned as trainer for test test (Employee).',NULL,0,'2026-10-11 03:51:28'),(139,5,'trainee_report_submitted','A week 1 report was submitted for review.','?page=trainee_report',0,'2026-10-11 07:06:14'),(140,17,'trainee_report_submitted','A week 1 report was submitted for review.','?page=trainee_report',0,'2026-10-11 07:06:15'),(141,2,'trainee_report_forwarded','A trainee\'s week 1 report was forwarded for your review.','?page=trainee_report',0,'2026-10-11 07:07:32'),(142,5,'trainee_report_submitted','A week 1 report was submitted for review.','?page=trainee_report',0,'2026-10-11 08:02:10'),(143,17,'trainee_report_submitted','A week 1 report was submitted for review.','?page=trainee_report',0,'2026-10-11 08:02:10'),(144,2,'trainee_created','Trainee account created for test test. Trainer John Doe is locked.',NULL,0,'2026-10-11 08:37:04'),(146,10,'trainee_assigned','You have been assigned as trainer for test test (Employee).',NULL,0,'2026-10-11 08:37:04'),(147,2,'trainee_created','Trainee account created for test test. Trainer John Doe is locked.',NULL,0,'2026-10-11 11:23:10'),(149,10,'trainee_assigned','You have been assigned as trainer for test test (Employee).',NULL,0,'2026-10-11 11:23:11'),(150,2,'trainee_created','Trainee account created for test test. Trainer John Doe is locked.',NULL,0,'2026-10-11 11:25:16'),(152,10,'trainee_assigned','You have been assigned as trainer for test test (Employee).',NULL,0,'2026-10-11 11:25:16'),(153,2,'trainee_created','Trainee account created for test test. Trainer John Doe is locked.',NULL,0,'2026-10-11 11:26:22'),(155,10,'trainee_assigned','You have been assigned as trainer for test test (Employee).',NULL,0,'2026-10-11 11:26:22'),(156,3,'trainee_created','Trainee account created for test test. Trainer John Doe is locked.',NULL,0,'2026-10-11 11:49:18'),(157,28,'trainee_contract_ready','Your Trainee Contract is ready. Trainer: John Doe. Salary: ₱3,900.00 – ₱4,500.00. Hours: 10:00:00–15:00:00. Rest days: Saturday, Sunday.',NULL,0,'2026-10-11 11:49:18'),(158,10,'trainee_assigned','You have been assigned as trainer for test test (Employee).',NULL,0,'2026-10-11 11:49:18'),(159,5,'trainee_report_submitted','A week 1 report was submitted for review.','?page=trainee_report',0,'2026-10-11 11:52:38'),(160,17,'trainee_report_submitted','A week 1 report was submitted for review.','?page=trainee_report',0,'2026-10-11 11:52:38'),(161,2,'trainee_report_screened','A trainee\'s week 1 report passed HR Staff screening and needs your final review.','?page=trainee_report',0,'2026-10-11 12:32:39'),(162,7,'requisition_pending_budget_check','New Requisition REQ-2026-0001 (₱6,000.00) needs a budget check.','?page=finance_staff_requisitions',0,'2026-10-13 14:13:09'),(163,8,'requisition_pending_budget_check','New Requisition REQ-2026-0001 (₱6,000.00) needs a budget check.','?page=finance_staff_requisitions',0,'2026-10-13 14:13:09'),(164,6,'requisition_pending_approval','Requisition REQ-2026-0001 passed budget check and needs your review.','?page=finance_head_requisitions',0,'2026-10-13 14:16:26'),(165,7,'po_pending_dispatch','Purchase Order PO-2026-0001 was created and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-10-13 14:17:13'),(166,8,'po_pending_dispatch','Purchase Order PO-2026-0001 was created and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-10-13 14:17:13'),(167,5,'requisition_approved','Requisition REQ-2026-0001 was approved by Finance Head. Purchase Order PO-2026-0001 created.','?page=store_manager_requisitions',0,'2026-10-13 14:17:13'),(168,13,'po_received','New Purchase Order PO-2026-0001 has been sent to you. Please review and confirm.','?page=supplier_requisitions',0,'2026-10-13 14:17:45'),(169,5,'po_confirmed','Supplier confirmed Purchase Order PO-2026-0001 for requisition #REQ-2026-0001.','?page=store_manager_requisitions',0,'2026-10-13 14:25:48'),(170,5,'po_shipped','PO PO-2026-0001 has been shipped by the supplier.','?page=store_manager_requisitions',0,'2026-10-13 14:30:52'),(171,7,'goods_received','Goods fully received for PO PO-2026-0001 (requisition #REQ-2026-0001).','?page=finance_staff_payment_requests',0,'2026-10-13 15:02:12'),(172,8,'goods_received','Goods fully received for PO PO-2026-0001 (requisition #REQ-2026-0001).','?page=finance_staff_payment_requests',0,'2026-10-13 15:02:12'),(173,7,'requisition_pending_budget_check','New Requisition REQ-2026-0002 (₱300.00) needs a budget check.','?page=finance_staff_requisitions',0,'2026-10-13 15:15:25'),(174,8,'requisition_pending_budget_check','New Requisition REQ-2026-0002 (₱300.00) needs a budget check.','?page=finance_staff_requisitions',0,'2026-10-13 15:15:25'),(175,6,'requisition_pending_approval','Requisition REQ-2026-0002 passed budget check and needs your review.','?page=finance_head_requisitions',0,'2026-10-13 15:15:39'),(176,7,'po_pending_dispatch','Purchase Order PO-2026-0002 was created and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-10-13 15:15:51'),(177,8,'po_pending_dispatch','Purchase Order PO-2026-0002 was created and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-10-13 15:15:51'),(178,5,'requisition_approved','Requisition REQ-2026-0002 was approved by Finance Head. Purchase Order PO-2026-0002 created.','?page=store_manager_requisitions',0,'2026-10-13 15:15:51'),(179,13,'po_received','New Purchase Order PO-2026-0002 has been sent to you. Please review and confirm.','?page=supplier_requisitions',0,'2026-10-13 15:16:29'),(180,5,'po_confirmed','Supplier confirmed Purchase Order PO-2026-0002 for requisition #REQ-2026-0002.','?page=store_manager_requisitions',0,'2026-10-13 15:17:27'),(181,5,'po_shipped','PO PO-2026-0002 has been shipped by the supplier.','?page=store_manager_requisitions',0,'2026-10-13 15:17:51'),(182,7,'goods_received','Goods fully received for PO PO-2026-0002 (requisition #REQ-2026-0002).','?page=finance_staff_payment_requests',0,'2026-10-13 15:21:48'),(183,8,'goods_received','Goods fully received for PO PO-2026-0002 (requisition #REQ-2026-0002).','?page=finance_staff_payment_requests',0,'2026-10-13 15:21:48'),(184,7,'requisition_pending_budget_check','New Requisition REQ-2026-0003 (₱26,000.00) needs a budget check.','?page=finance_staff_requisitions',0,'2026-10-13 15:55:48'),(185,8,'requisition_pending_budget_check','New Requisition REQ-2026-0003 (₱26,000.00) needs a budget check.','?page=finance_staff_requisitions',0,'2026-10-13 15:55:48'),(186,6,'requisition_pending_approval','Requisition REQ-2026-0003 passed budget check and needs your review.','?page=finance_head_requisitions',0,'2026-10-13 15:56:07'),(187,7,'po_pending_dispatch','Purchase Order PO-2026-0003 was created and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-10-13 15:56:27'),(188,8,'po_pending_dispatch','Purchase Order PO-2026-0003 was created and is ready to dispatch to the supplier.','?page=finance_staff_payment_requests',0,'2026-10-13 15:56:27'),(189,5,'requisition_approved','Requisition REQ-2026-0003 was approved by Finance Head. Purchase Order PO-2026-0003 created.','?page=store_manager_requisitions',0,'2026-10-13 15:56:27'),(190,13,'po_received','New Purchase Order PO-2026-0003 has been sent to you. Please review and confirm.','?page=supplier_requisitions',0,'2026-10-13 15:57:02'),(191,5,'po_counter_proposed','Supplier proposed changes to Purchase Order PO-2026-0003 for requisition #REQ-2026-0003.','?page=store_manager_requisitions',0,'2026-10-13 15:57:45');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_deal_items`
--

DROP TABLE IF EXISTS `order_deal_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_deal_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `deal_id` int(11) DEFAULT NULL,
  `deal_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `deal_id` (`deal_id`),
  CONSTRAINT `order_deal_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_deal_items_ibfk_2` FOREIGN KEY (`deal_id`) REFERENCES `deals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_deal_items`
--

LOCK TABLES `order_deal_items` WRITE;
/*!40000 ALTER TABLE `order_deal_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_deal_items` ENABLE KEYS */;
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
  `original_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pwd_senior_discount` tinyint(1) NOT NULL DEFAULT 0,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  `method` enum('bank_transfer','check','cash','paymongo_simulated','other') NOT NULL DEFAULT 'bank_transfer',
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
INSERT INTO `po_counter_proposal_items` VALUES (1,1,3,70,NULL,NULL,NULL);
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
INSERT INTO `po_counter_proposals` VALUES (1,3,13,'pending','no stock',NULL,NULL,'2026-10-13 15:57:45');
/*!40000 ALTER TABLE `po_counter_proposals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_discrepancies`
--

DROP TABLE IF EXISTS `po_discrepancies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_discrepancies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `goods_receipt_item_id` int(11) NOT NULL,
  `type` enum('missing','damaged') NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('open','action_taken','resolved') NOT NULL DEFAULT 'open',
  `new_delivery_date` date DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `rma_number` varchar(50) DEFAULT NULL,
  `resolved_goods_receipt_item_id` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_disc_po` (`po_id`),
  KEY `idx_disc_status` (`status`),
  KEY `fk_disc_po_item` (`po_item_id`),
  KEY `fk_disc_gr_item` (`goods_receipt_item_id`),
  KEY `fk_disc_resolved_gr_item` (`resolved_goods_receipt_item_id`),
  CONSTRAINT `fk_disc_gr_item` FOREIGN KEY (`goods_receipt_item_id`) REFERENCES `goods_receipt_items` (`id`),
  CONSTRAINT `fk_disc_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_disc_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`),
  CONSTRAINT `fk_disc_resolved_gr_item` FOREIGN KEY (`resolved_goods_receipt_item_id`) REFERENCES `goods_receipt_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_discrepancies`
--

LOCK TABLES `po_discrepancies` WRITE;
/*!40000 ALTER TABLE `po_discrepancies` DISABLE KEYS */;
/*!40000 ALTER TABLE `po_discrepancies` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_events`
--

LOCK TABLES `po_events` WRITE;
/*!40000 ALTER TABLE `po_events` DISABLE KEYS */;
INSERT INTO `po_events` VALUES (1,1,'po_created','Purchase Order PO-2026-0001 created from requisition REQ-2026-0001 by Finance Head approval, supplier: Northgate Office Supplies.','all',6,'2026-10-13 14:17:13'),(2,1,'po_dispatched','Purchase Order dispatched to supplier by Finance Staff.','all',7,'2026-10-13 14:17:45'),(3,1,'po_confirmed','Supplier accepted the purchase order as-is.','all',13,'2026-10-13 14:25:48'),(4,1,'shipped','Supplier marked the order as shipped.','all',13,'2026-10-13 14:30:52'),(5,1,'goods_received','Goods fully received by Store Manager.','not_supplier',5,'2026-10-13 15:02:11'),(6,2,'po_created','Purchase Order PO-2026-0002 created from requisition REQ-2026-0002 by Finance Head approval, supplier: Northgate Office Supplies.','all',6,'2026-10-13 15:15:51'),(7,2,'po_dispatched','Purchase Order dispatched to supplier by Finance Staff.','all',7,'2026-10-13 15:16:29'),(8,2,'po_confirmed','Supplier accepted the purchase order as-is.','all',13,'2026-10-13 15:17:27'),(9,2,'shipped','Supplier marked the order as shipped.','all',13,'2026-10-13 15:17:51'),(10,2,'goods_received','Goods fully received by Store Manager.','not_supplier',5,'2026-10-13 15:21:47'),(11,3,'po_created','Purchase Order PO-2026-0003 created from requisition REQ-2026-0003 by Finance Head approval, supplier: Northgate Office Supplies.','all',6,'2026-10-13 15:56:27'),(12,3,'po_dispatched','Purchase Order dispatched to supplier by Finance Staff.','all',7,'2026-10-13 15:57:02'),(13,3,'po_counter_proposed','Supplier proposed a quantity/date change. Reason: no stock','all',13,'2026-10-13 15:57:45');
/*!40000 ALTER TABLE `po_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `po_excess_items`
--

DROP TABLE IF EXISTS `po_excess_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `po_excess_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `po_item_id` int(11) NOT NULL,
  `goods_receipt_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('open','accepted','returned') NOT NULL DEFAULT 'open',
  `accepted_unit_price` decimal(10,2) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_excess_po` (`po_id`),
  KEY `idx_excess_status` (`status`),
  KEY `fk_excess_po_item` (`po_item_id`),
  KEY `fk_excess_gr_item` (`goods_receipt_item_id`),
  KEY `fk_excess_resolved_by` (`resolved_by`),
  CONSTRAINT `fk_excess_gr_item` FOREIGN KEY (`goods_receipt_item_id`) REFERENCES `goods_receipt_items` (`id`),
  CONSTRAINT `fk_excess_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_excess_po_item` FOREIGN KEY (`po_item_id`) REFERENCES `purchase_order_items` (`id`),
  CONSTRAINT `fk_excess_resolved_by` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_excess_items`
--

LOCK TABLES `po_excess_items` WRITE;
/*!40000 ALTER TABLE `po_excess_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `po_excess_items` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `po_payment_requests`
--

LOCK TABLES `po_payment_requests` WRITE;
/*!40000 ALTER TABLE `po_payment_requests` DISABLE KEYS */;
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
-- Table structure for table `product_proposals`
--

DROP TABLE IF EXISTS `product_proposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_proposals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proposed_by` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `proposed_name` varchar(100) NOT NULL,
  `proposed_barcode` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `proposed_price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending_supplier','pending_owner','approved','rejected') NOT NULL DEFAULT 'pending_supplier',
  `supplier_product_name` varchar(100) DEFAULT NULL,
  `supplier_price` decimal(10,2) DEFAULT NULL,
  `supplier_quantity` int(11) DEFAULT NULL,
  `supplier_responded_by` int(11) DEFAULT NULL,
  `supplier_responded_at` timestamp NULL DEFAULT NULL,
  `supplier_decline_reason` text DEFAULT NULL,
  `owner_decided_by` int(11) DEFAULT NULL,
  `owner_decided_at` timestamp NULL DEFAULT NULL,
  `owner_reject_reason` text DEFAULT NULL,
  `resulting_product_id` int(11) DEFAULT NULL,
  `resulting_supplier_product_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `proposed_by` (`proposed_by`),
  KEY `supplier_id` (`supplier_id`),
  KEY `category_id` (`category_id`),
  KEY `supplier_responded_by` (`supplier_responded_by`),
  KEY `owner_decided_by` (`owner_decided_by`),
  KEY `resulting_product_id` (`resulting_product_id`),
  KEY `status` (`status`),
  CONSTRAINT `pp_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pp_owner_decided_by_fk` FOREIGN KEY (`owner_decided_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `pp_proposed_by_fk` FOREIGN KEY (`proposed_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `pp_resulting_product_fk` FOREIGN KEY (`resulting_product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pp_supplier_fk` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `pp_supplier_responded_by_fk` FOREIGN KEY (`supplier_responded_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_proposals`
--

LOCK TABLES `product_proposals` WRITE;
/*!40000 ALTER TABLE `product_proposals` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_proposals` ENABLE KEYS */;
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
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'978-0-123-45678-9','Sample Book','A sample book for testing',1,12.99,8.00,0.00,'percent',12,5,NULL,1,'2026-08-20 17:09:05','2026-09-19 08:44:21'),(2,'BS-001','Sample Pen','A sample pen for testing',2,2.99,1.20,0.00,'percent',38,5,NULL,1,'2026-08-20 17:09:05','2026-09-09 16:38:18'),(4,'STA-PPR-001','Bond Paper (Stack of 500)','Short bond paper, substance 20, 500 sheets per stack.',6,220.00,165.00,0.00,'percent',30,8,NULL,1,'2026-10-11 02:01:21','2026-10-11 02:01:21'),(5,'STA-PEN-001','Ballpoint Pen (Box of 12)','Black ballpoint pens, medium tip, 12 pens per box.',7,96.00,60.00,0.00,'percent',145,10,NULL,1,'2026-10-11 02:01:21','2026-10-13 15:21:47'),(6,'STA-FLD-001','Long Folder (Pack of 20)','Long (legal-size) paper folders, assorted colors, 20 per pack.',8,150.00,105.00,0.00,'percent',25,6,NULL,1,'2026-10-11 02:01:21','2026-10-11 02:01:21'),(7,'STA-NTB-001','Spiral Notebook (Pack of 10)','80-leaf spiral notebooks, 10 per pack.',9,340.00,260.00,0.00,'percent',20,5,NULL,1,'2026-10-11 02:01:21','2026-10-11 02:01:21'),(8,'STA-PNC-001','Wooden Pencil (Box of 12)','No. 2 wooden pencils, 12 per box.',10,60.00,36.00,0.00,'percent',35,10,NULL,1,'2026-10-11 02:01:21','2026-10-11 02:01:21');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (1,1,5,14,100,60.00,6000.00,100,'2026-10-13 14:17:12'),(2,2,5,14,5,60.00,300.00,5,'2026-10-13 15:15:51'),(3,3,7,16,100,260.00,26000.00,0,'2026-10-13 15:56:27');
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
  `status` enum('pending_budget_check','budget_rejected','pending_fh_approval','pending_dispatch','pending_confirmation','supplier_accepted','supplier_counter_proposed','confirmed','pending_payment','paid','shipped','partially_received','received','cancelled','closed','discrepancy_logged') NOT NULL DEFAULT 'pending_budget_check',
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'PO-2026-0001',1,3,'received','2026-10-13',NULL,6000.00,0.00,6000.00,'Net 30','2026-10-13 22:17:45','email',6,6,'2026-10-13 16:17:12',NULL,NULL,'2026-10-13 14:17:12','2026-10-13 15:02:11'),(2,'PO-2026-0002',2,3,'received','2026-10-13','2026-10-31',300.00,0.00,300.00,'Net 30','2026-10-13 23:16:29','email',6,6,'2026-10-13 17:15:51',NULL,NULL,'2026-10-13 15:15:51','2026-10-13 15:21:47'),(3,'PO-2026-0003',3,3,'supplier_counter_proposed','2026-10-13','2026-10-22',26000.00,0.00,26000.00,'Net 30','2026-10-13 23:57:02','email',6,6,'2026-10-13 17:56:27',NULL,NULL,'2026-10-13 15:56:27','2026-10-13 15:57:45');
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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recruitment_logs`
--

LOCK TABLES `recruitment_logs` WRITE;
/*!40000 ALTER TABLE `recruitment_logs` DISABLE KEYS */;
INSERT INTO `recruitment_logs` VALUES (18,'job_posting',1,2,'hr_head','created',NULL,'draft',NULL,NULL,'2026-08-30 09:43:36'),(19,'job_posting',1,2,'hr_head','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-08-30 09:43:36'),(20,'job_posting',1,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-08-30 09:43:40'),(21,'job_posting',2,2,'hr_head','created',NULL,'draft',NULL,NULL,'2026-08-30 09:44:57'),(22,'job_posting',2,2,'hr_head','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-08-30 09:44:57'),(23,'job_posting',2,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-08-30 09:45:00'),(24,'applicant',1,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 1)','2026-08-30 09:55:47'),(25,'applicant',1,3,'hr_staff','initial_interview_scheduled','pending','initial_scheduled',NULL,NULL,'2026-08-30 10:01:00'),(34,'job_posting',3,18,'hr_staff','created',NULL,'draft',NULL,NULL,'2026-09-19 05:58:41'),(35,'job_posting',4,3,'hr_staff','created',NULL,'draft',NULL,NULL,'2026-10-11 02:05:35'),(36,'job_posting',4,3,'hr_staff','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-10-11 02:05:35'),(37,'job_posting',4,2,'hr_head','hr_head_edit','pending_approval','pending_approval',NULL,NULL,'2026-10-11 02:06:48'),(38,'job_posting',4,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-10-11 02:07:10'),(39,'applicant',3,3,'hr_staff','application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 4)','2026-10-11 02:28:39'),(40,'applicant',4,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 4)','2026-10-11 02:39:06'),(41,'applicant',5,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 4)','2026-10-11 02:42:13'),(42,'applicant',4,18,'hr_staff','initial_interview_scheduled','pending','initial_scheduled',NULL,NULL,'2026-10-11 02:53:53'),(43,'applicant',1,3,'hr_staff','trainee_contract_assigned','initial_passed','screening',NULL,'Trainer #9 assigned','2026-10-11 03:51:28'),(44,'trainee_report',1,9,'employee','submitted',NULL,'submitted',NULL,'trainee #1, week 1','2026-10-11 07:06:14'),(45,'trainee_report',1,5,'store_manager','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-10-11 07:07:32'),(46,'trainee_report',1,2,'hr_head','hr_head_reviewed',NULL,'hr_reviewed',NULL,NULL,'2026-10-11 07:08:43'),(47,'trainee_report',2,9,'employee','submitted',NULL,'submitted',NULL,'trainee #1, week 1','2026-10-11 08:02:10'),(48,'applicant',1,2,'hr_head','trainee_contract_assigned','initial_passed','screening',NULL,'Trainer #10 assigned','2026-10-11 08:37:04'),(49,'applicant',1,2,'hr_head','trainee_contract_assigned','initial_passed','screening',NULL,'Trainer #10 assigned','2026-10-11 11:23:10'),(50,'applicant',1,2,'hr_head','trainee_contract_assigned','initial_passed','screening',NULL,'Trainer #10 assigned','2026-10-11 11:25:16'),(51,'applicant',1,2,'hr_head','trainee_contract_assigned','initial_passed','screening',NULL,'Trainer #10 assigned','2026-10-11 11:26:22'),(52,'applicant',1,3,'hr_staff','trainee_contract_assigned','initial_passed','screening',NULL,'Trainer #10 assigned','2026-10-11 11:49:18'),(53,'trainee_report',3,10,'employee','submitted',NULL,'submitted',NULL,'trainee #7, week 1','2026-10-11 11:52:37'),(54,'trainee_report',3,3,'hr_staff','hr_staff_screened','forwarded','hr_staff_reviewed',NULL,NULL,'2026-10-11 12:32:39'),(55,'trainee_report',3,2,'hr_head','hr_head_reviewed',NULL,'hr_reviewed',NULL,NULL,'2026-10-11 12:33:42');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registers`
--

LOCK TABLES `registers` WRITE;
/*!40000 ALTER TABLE `registers` DISABLE KEYS */;
INSERT INTO `registers` VALUES (1,5,'Register 1','POS-001','$2y$10$w5/l7tcOzklSnbfJLMgXjO.sgImsSigAAsF2JsVi7CfA0iNBBMqry',1,'2026-09-08 03:03:57','closed','2026-09-08 03:01:02','2026-09-08 07:36:00'),(2,5,'Register 2','POS-002','$2y$10$w5/l7tcOzklSnbfJLMgXjO.sgImsSigAAsF2JsVi7CfA0iNBBMqry',1,'2026-09-08 03:03:57','closed','2026-09-08 03:01:02','2026-09-08 03:03:57');
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
-- Table structure for table `requisition_events`
--

DROP TABLE IF EXISTS `requisition_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requisition_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `event_type` varchar(50) NOT NULL,
  `message` text DEFAULT NULL,
  `actor_user_id` int(11) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_req_events_requisition` (`requisition_id`),
  KEY `fk_req_events_actor` (`actor_user_id`),
  CONSTRAINT `fk_req_events_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_req_events_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisition_events`
--

LOCK TABLES `requisition_events` WRITE;
/*!40000 ALTER TABLE `requisition_events` DISABLE KEYS */;
INSERT INTO `requisition_events` VALUES (1,1,'requisition_submitted','Requisition REQ-2026-0001 submitted. Suggested supplier: Northgate Office Supplies. Note: Good',5,'store_manager','2026-10-13 14:13:09'),(2,1,'budget_check_passed','Passed budget check by Finance Staff.',7,'finance_staff','2026-10-13 14:16:26'),(3,1,'requisition_approved','Approved by Finance Head. Purchase Order PO-2026-0001 created, supplier: Northgate Office Supplies.',6,'finance_head','2026-10-13 14:17:13'),(4,2,'requisition_submitted','Requisition REQ-2026-0002 submitted. Suggested supplier: Northgate Office Supplies.',5,'store_manager','2026-10-13 15:15:25'),(5,2,'budget_check_passed','Passed budget check by Finance Staff.',7,'finance_staff','2026-10-13 15:15:39'),(6,2,'requisition_approved','Approved by Finance Head. Purchase Order PO-2026-0002 created, supplier: Northgate Office Supplies.',6,'finance_head','2026-10-13 15:15:51'),(7,3,'requisition_submitted','Requisition REQ-2026-0003 submitted. Suggested supplier: Northgate Office Supplies.',5,'store_manager','2026-10-13 15:55:48'),(8,3,'budget_check_passed','Passed budget check by Finance Staff.',7,'finance_staff','2026-10-13 15:56:07'),(9,3,'requisition_approved','Approved by Finance Head. Purchase Order PO-2026-0003 created, supplier: Northgate Office Supplies.',6,'finance_head','2026-10-13 15:56:27');
/*!40000 ALTER TABLE `requisition_events` ENABLE KEYS */;
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
  `supplier_product_id` int(11) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisition_items`
--

LOCK TABLES `requisition_items` WRITE;
/*!40000 ALTER TABLE `requisition_items` DISABLE KEYS */;
INSERT INTO `requisition_items` VALUES (1,1,5,14,100,60.00,6000.00,NULL,'2026-10-13 14:13:09'),(2,2,5,14,5,60.00,300.00,NULL,'2026-10-13 15:15:25'),(3,3,7,16,100,260.00,26000.00,NULL,'2026-10-13 15:55:48');
/*!40000 ALTER TABLE `requisition_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requisition_quote_items`
--

DROP TABLE IF EXISTS `requisition_quote_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requisition_quote_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_id` int(11) NOT NULL,
  `requisition_item_id` int(11) NOT NULL,
  `offered_quantity` int(11) NOT NULL,
  `offered_unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quote_item` (`quote_id`,`requisition_item_id`),
  KEY `fk_qi_req_item` (`requisition_item_id`),
  CONSTRAINT `fk_qi_quote` FOREIGN KEY (`quote_id`) REFERENCES `requisition_quotes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qi_req_item` FOREIGN KEY (`requisition_item_id`) REFERENCES `requisition_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisition_quote_items`
--

LOCK TABLES `requisition_quote_items` WRITE;
/*!40000 ALTER TABLE `requisition_quote_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `requisition_quote_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requisition_quote_requests`
--

DROP TABLE IF EXISTS `requisition_quote_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requisition_quote_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_qr_requisition` (`requisition_id`),
  KEY `fk_qr_created_by` (`created_by`),
  CONSTRAINT `fk_qr_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_qr_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisition_quote_requests`
--

LOCK TABLES `requisition_quote_requests` WRITE;
/*!40000 ALTER TABLE `requisition_quote_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `requisition_quote_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requisition_quotes`
--

DROP TABLE IF EXISTS `requisition_quotes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requisition_quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote_request_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `status` enum('invited','responded','selected','sm_rejected','declined') NOT NULL DEFAULT 'invited',
  `notes` text DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quote_request_supplier` (`quote_request_id`,`supplier_id`),
  KEY `idx_quotes_supplier` (`supplier_id`),
  CONSTRAINT `fk_quotes_request` FOREIGN KEY (`quote_request_id`) REFERENCES `requisition_quote_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quotes_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisition_quotes`
--

LOCK TABLES `requisition_quotes` WRITE;
/*!40000 ALTER TABLE `requisition_quotes` DISABLE KEYS */;
/*!40000 ALTER TABLE `requisition_quotes` ENABLE KEYS */;
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
  `preferred_supplier_id` int(11) DEFAULT NULL,
  `period_key` varchar(10) NOT NULL,
  `status` enum('draft','pending_budget_check','budget_rejected','pending_supplier_quotes','pending_sm_quote_confirmation','pending_finance_head','revision_requested','approved','rejected','converted_to_po','cancelled') NOT NULL DEFAULT 'pending_budget_check',
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisitions`
--

LOCK TABLES `requisitions` WRITE;
/*!40000 ALTER TABLE `requisitions` DISABLE KEYS */;
INSERT INTO `requisitions` VALUES (1,'REQ-2026-0001',5,1,3,'2026-10-H1','converted_to_po','2026-10-13',NULL,6000.00,'',NULL,'2026-10-13 14:13:09','2026-10-13 14:17:12'),(2,'REQ-2026-0002',5,1,3,'2026-10-H1','converted_to_po','2026-10-13','2026-10-31',300.00,'',NULL,'2026-10-13 15:15:25','2026-10-13 15:15:51'),(3,'REQ-2026-0003',5,1,3,'2026-10-H1','converted_to_po','2026-10-13','2026-10-22',26000.00,'',NULL,'2026-10-13 15:55:48','2026-10-13 15:56:27');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
-- Table structure for table `schedule_overrides`
--

DROP TABLE IF EXISTS `schedule_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedule_overrides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `period_key` varchar(10) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `swap_with_day` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `is_rest_day` tinyint(1) NOT NULL DEFAULT 0,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_period_day` (`user_id`,`period_key`,`day_of_week`),
  KEY `idx_period_key` (`period_key`),
  KEY `schedule_overrides_ibfk_2` (`changed_by`),
  CONSTRAINT `schedule_overrides_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `schedule_overrides_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedule_overrides`
--

LOCK TABLES `schedule_overrides` WRITE;
/*!40000 ALTER TABLE `schedule_overrides` DISABLE KEYS */;
/*!40000 ALTER TABLE `schedule_overrides` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (108,28,'monday','10:00:00','15:00:00',0,'2026-10-11 11:49:17','2026-10-11 11:49:17'),(109,28,'tuesday','10:00:00','15:00:00',0,'2026-10-11 11:49:17','2026-10-11 11:49:17'),(110,28,'wednesday','10:00:00','15:00:00',0,'2026-10-11 11:49:18','2026-10-11 11:49:18'),(111,28,'thursday','10:00:00','15:00:00',0,'2026-10-11 11:49:18','2026-10-11 11:49:18'),(112,28,'friday','10:00:00','15:00:00',0,'2026-10-11 11:49:18','2026-10-11 11:49:18'),(113,28,'saturday','00:00:00','00:00:00',1,'2026-10-11 11:49:18','2026-10-11 11:49:18'),(114,28,'sunday','00:00:00','00:00:00',1,'2026-10-11 11:49:18','2026-10-11 11:49:18');
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
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_products`
--

LOCK TABLES `supplier_products` WRITE;
/*!40000 ALTER TABLE `supplier_products` DISABLE KEYS */;
INSERT INTO `supplier_products` VALUES (5,1,1,'Sample Book','A sample book for testing',10.39,100,1,'2026-08-21 12:54:24','2026-09-06 11:22:04'),(6,1,2,'Sample Pen','A sample pen for testing',2.39,100,1,'2026-08-21 12:54:24','2026-09-06 11:22:04'),(9,1,2,'Sample Pen','Test dup supplier pen',1.99,50,0,'2026-09-06 11:34:00','2026-09-06 15:00:48'),(10,3,1,'Sample Book','Northgate listing',9.99,80,1,'2026-09-06 15:00:17','2026-09-06 15:00:17'),(11,3,2,'Sample Pen','Northgate listing',2.15,200,1,'2026-09-06 15:00:17','2026-09-06 15:00:17'),(13,1,4,'Bond Paper (Stack of 500)','Short bond paper, substance 20, 500 sheets per stack.',165.00,120,1,'2026-10-11 02:01:21','2026-10-11 02:01:21'),(14,3,5,'Ballpoint Pen (Box of 12)','Black ballpoint pens, medium tip, 12 pens per box.',60.00,150,1,'2026-10-11 02:01:21','2026-10-11 02:01:21'),(15,1,6,'Long Folder (Pack of 20)','Long (legal-size) paper folders, assorted colors, 20 per pack.',105.00,90,1,'2026-10-11 02:01:21','2026-10-11 02:01:21'),(16,3,7,'Spiral Notebook (Pack of 10)','80-leaf spiral notebooks, 10 per pack.',260.00,70,1,'2026-10-11 02:01:21','2026-10-11 02:01:21'),(17,1,8,'Wooden Pencil (Box of 12)','No. 2 wooden pencils, 12 per box.',36.00,130,1,'2026-10-11 02:01:21','2026-10-11 02:01:21');
/*!40000 ALTER TABLE `supplier_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_ratings`
--

DROP TABLE IF EXISTS `supplier_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `rated_by` int(11) NOT NULL,
  `on_time` tinyint(1) NOT NULL DEFAULT 1,
  `fill_rate_percent` decimal(5,2) NOT NULL DEFAULT 100.00,
  `quality_rating` tinyint(4) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_rating_per_po` (`po_id`),
  KEY `idx_ratings_supplier` (`supplier_id`),
  KEY `fk_ratings_rated_by` (`rated_by`),
  CONSTRAINT `fk_ratings_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ratings_rated_by` FOREIGN KEY (`rated_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_ratings_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `chk_quality_rating` CHECK (`quality_rating` between 1 and 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_ratings`
--

LOCK TABLES `supplier_ratings` WRITE;
/*!40000 ALTER TABLE `supplier_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_suppliers_user_id` (`user_id`),
  CONSTRAINT `fk_suppliers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,12,'Sample Supplier Inc.','John Supplier','supplier@shelfsense.com','09123456789',NULL,NULL,NULL,1,'2026-08-20 17:09:05','2026-10-13 14:04:59'),(3,13,'Northgate Office Supplies','Jamie Cruz','supplier2@shelfsense.com','09171234567','Dasmarinas, Cavite',NULL,NULL,1,'2026-09-06 15:00:17','2026-10-13 14:04:59');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `setting_key` varchar(64) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`),
  KEY `fk_system_settings_updated_by` (`updated_by`),
  CONSTRAINT `fk_system_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES ('test_mode','1',1,'2026-09-19 12:26:08');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trainee_daily_logs`
--

DROP TABLE IF EXISTS `trainee_daily_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trainee_daily_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_id` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `day_name` varchar(10) NOT NULL,
  `content` text NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_trainee_date` (`trainee_id`,`log_date`),
  KEY `idx_tdl_trainee_week` (`trainee_id`,`week_number`),
  KEY `fk_tdl_trainer` (`trainer_id`),
  CONSTRAINT `fk_tdl_trainee` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tdl_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainee_daily_logs`
--

LOCK TABLES `trainee_daily_logs` WRITE;
/*!40000 ALTER TABLE `trainee_daily_logs` DISABLE KEYS */;
INSERT INTO `trainee_daily_logs` VALUES (8,7,1,'2026-10-12','monday','asdasdasd',10,'2026-10-11 11:52:18','2026-10-11 11:52:18'),(9,7,1,'2026-10-13','tuesday','asdasd',10,'2026-10-11 11:52:21','2026-10-11 11:52:21'),(10,7,1,'2026-10-14','wednesday','asdasd',10,'2026-10-11 11:52:23','2026-10-11 11:52:23'),(11,7,1,'2026-10-15','thursday','asdasdasd',10,'2026-10-11 11:52:26','2026-10-11 11:52:26'),(12,7,1,'2026-10-16','friday','asdasdasdasd',10,'2026-10-11 11:52:28','2026-10-11 11:52:28'),(13,7,2,'2026-10-19','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:46','2026-10-11 11:55:46'),(14,7,2,'2026-10-20','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:46','2026-10-11 11:55:46'),(15,7,2,'2026-10-21','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:46','2026-10-11 11:55:46'),(16,7,2,'2026-10-22','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:46','2026-10-11 11:55:46'),(17,7,2,'2026-10-23','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:46','2026-10-11 11:55:46'),(18,7,3,'2026-10-26','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(19,7,3,'2026-10-27','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(20,7,3,'2026-10-28','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(21,7,3,'2026-10-29','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(22,7,3,'2026-10-30','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(23,7,4,'2026-11-02','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(24,7,4,'2026-11-03','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(25,7,4,'2026-11-04','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(26,7,4,'2026-11-05','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(27,7,4,'2026-11-06','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(28,7,5,'2026-11-09','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(29,7,5,'2026-11-10','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(30,7,5,'2026-11-11','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(31,7,5,'2026-11-12','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(32,7,5,'2026-11-13','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(33,7,6,'2026-11-16','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(34,7,6,'2026-11-17','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(35,7,6,'2026-11-18','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(36,7,6,'2026-11-19','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(37,7,6,'2026-11-20','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(38,7,7,'2026-11-23','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(39,7,7,'2026-11-24','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(40,7,7,'2026-11-25','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(41,7,7,'2026-11-26','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(42,7,7,'2026-11-27','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:47','2026-10-11 11:55:47'),(43,7,8,'2026-11-30','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(44,7,8,'2026-12-01','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(45,7,8,'2026-12-02','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(46,7,8,'2026-12-03','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(47,7,8,'2026-12-04','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(48,7,9,'2026-12-07','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(49,7,9,'2026-12-08','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(50,7,9,'2026-12-09','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(51,7,9,'2026-12-10','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(52,7,9,'2026-12-11','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(53,7,10,'2026-12-14','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(54,7,10,'2026-12-15','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(55,7,10,'2026-12-16','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(56,7,10,'2026-12-17','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(57,7,10,'2026-12-18','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(58,7,11,'2026-12-21','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(59,7,11,'2026-12-22','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(60,7,11,'2026-12-23','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(61,7,11,'2026-12-24','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(62,7,11,'2026-12-25','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(63,7,12,'2026-12-28','monday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(64,7,12,'2026-12-29','tuesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(65,7,12,'2026-12-30','wednesday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(66,7,12,'2026-12-31','thursday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48'),(67,7,12,'2027-01-01','friday','Continued on-the-job training tasks and shadowing.',10,'2026-10-11 11:55:48','2026-10-11 11:55:48');
/*!40000 ALTER TABLE `trainee_daily_logs` ENABLE KEYS */;
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
  `status` enum('submitted','forwarded','hr_staff_reviewed','hr_reviewed') NOT NULL DEFAULT 'submitted',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewer_role` varchar(20) DEFAULT NULL,
  `reviewer_observation` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `forwarded_at` datetime DEFAULT NULL,
  `hr_staff_id` int(11) DEFAULT NULL,
  `hr_staff_notes` text DEFAULT NULL,
  `hr_staff_reviewed_at` datetime DEFAULT NULL,
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
  KEY `fk_trainee_reports_hr_staff` (`hr_staff_id`),
  CONSTRAINT `fk_trainee_reports_hr_staff` FOREIGN KEY (`hr_staff_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_trainee_reports_hrhead` FOREIGN KEY (`hr_head_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_trainee_reports_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_trainee_reports_trainee` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_trainee_reports_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainee_reports`
--

LOCK TABLES `trainee_reports` WRITE;
/*!40000 ALTER TABLE `trainee_reports` DISABLE KEYS */;
INSERT INTO `trainee_reports` VALUES (3,7,1,1,'2026-10-11','2026-10-17','store',10,'Monday: asdasdasd\nTuesday: asdasd\nWednesday: asdasd\nThursday: asdasdasd\nFriday: asdasdasdasd','good','Technical / Job Knowledge','Speed & Productivity','ad123sad','eee','hr_reviewed','2026-10-11 11:52:37',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:16','2026-10-11 19:55:16',3,NULL,'2026-10-11 20:32:39',2,NULL,'2026-10-11 20:33:42','2026-10-11 11:52:37'),(4,7,2,1,'2026-10-18','2026-10-24','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:46',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:47','2026-10-11 19:55:47',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:46'),(5,7,3,1,'2026-10-25','2026-10-31','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:47',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:47','2026-10-11 19:55:47',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:47'),(6,7,4,1,'2026-11-01','2026-11-07','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:47',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:47','2026-10-11 19:55:47',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:47'),(7,7,5,2,'2026-11-08','2026-11-14','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:47',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:47','2026-10-11 19:55:47',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:47'),(8,7,6,2,'2026-11-15','2026-11-21','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:47',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:47','2026-10-11 19:55:47',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:47'),(9,7,7,2,'2026-11-22','2026-11-28','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:47',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:48','2026-10-11 19:55:48',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:47'),(10,7,8,2,'2026-11-29','2026-12-05','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:48',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:48','2026-10-11 19:55:48',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:48'),(11,7,9,3,'2026-12-06','2026-12-12','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:48',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:48','2026-10-11 19:55:48',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:48'),(12,7,10,3,'2026-12-13','2026-12-19','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:48',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:48','2026-10-11 19:55:48',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:48'),(13,7,11,3,'2026-12-20','2026-12-26','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:48',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:48','2026-10-11 19:55:48',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:48'),(14,7,12,3,'2026-12-27','2027-01-02','store',10,'Monday: Continued on-the-job training tasks and shadowing.\nTuesday: Continued on-the-job training tasks and shadowing.\nWednesday: Continued on-the-job training tasks and shadowing.\nThursday: Continued on-the-job training tasks and shadowing.\nFriday: Continued on-the-job training tasks and shadowing.','good','Reliable and eager to learn.','Could improve speed on register tasks.','No absences this week.',NULL,'forwarded','2026-10-11 11:55:48',5,'store_manager','Trainee is progressing well.','2026-10-11 19:55:48','2026-10-11 19:55:48',NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:55:48');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainees`
--

LOCK TABLES `trainees` WRITE;
/*!40000 ALTER TABLE `trainees` DISABLE KEYS */;
INSERT INTO `trainees` VALUES (1,2,14,9,'Employee','2026-09-08','2026-12-08','10:00:00','15:00:00','active',3900.00,4500.00,NULL,NULL,NULL,NULL,NULL,'pending',0,NULL,NULL,NULL,NULL,NULL,'2026-09-08 04:20:46','2026-09-09 19:21:45'),(7,1,28,10,'Employee','2026-10-11','2027-01-11','10:00:00','15:00:00','active',3900.00,4500.00,NULL,'2026-10-11 11:49:17',NULL,NULL,NULL,'pending',0,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:49:17','2026-10-11 12:25:32');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainer_assignments`
--

LOCK TABLES `trainer_assignments` WRITE;
/*!40000 ALTER TABLE `trainer_assignments` DISABLE KEYS */;
INSERT INTO `trainer_assignments` VALUES (8,7,10,3,'2026-10-11 11:49:17',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SA-001','Stephen','Frias',NULL,'stephenfrias4@gmail.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','owner',5,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 01:52:21',1),(2,'HH-001','Maria','Santos',NULL,'hr.head@shelfsense.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','hr_head',4,1,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 01:52:21',1),(3,'HS-001','Juan','Dela Cruz',NULL,'hr.staff@shelfsense.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','hr_staff',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 01:52:21',1),(4,'HS-002','Ana','Reyes',NULL,'stephenfrias04@gmail.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','hr_staff',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 01:52:21',1),(5,'SM-001','Store','Manager',NULL,'store.manager@shelfsense.com','$2y$10$.hAOXn9rgMF1ibYWjqwNAeKqKzn48K6bL3R1EEkgC7X6AcuDK2De.','store_manager',4,0,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 07:06:28',1),(6,'FH-001','Finance','Head',NULL,'finance.head@shelfsense.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','finance_head',4,0,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 01:52:21',1),(7,'FS-001','Finance','Staff',NULL,'finance.staff@shelfsense.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','finance_staff',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 01:52:21',1),(8,'FS-002','Sarah','Williams',NULL,'finance.staff2@shelfsense.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','finance_staff',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 01:52:21',1),(9,'CA-001','Cashier','Test',NULL,'employee@shelfsense.com','$2y$10$vplT8tV6VOxgYbWQee78hucZ8OHJW.5z9ip4RuLAaXXsWRr2cDnCm','employee',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 07:05:13',1),(10,'CA-002','John','Doe',NULL,'rumbines.allen@ncst.edu.ph','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','employee',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 11:49:17',1),(11,'TR-001','Trainee','User',NULL,'trainee@shelfsense.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','trainee',0,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-20 17:09:05','2026-10-11 01:52:21',1),(12,'SUP-001','Sample','Supplier',NULL,'supplier@shelfsense.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','supplier',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-08-21 03:04:09','2026-10-11 01:52:21',1),(13,'SUP-002','Northgate','Supplies',NULL,'supplier2@shelfsense.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','supplier',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-09-06 15:00:17','2026-10-11 01:52:21',1),(14,'TR-002','Maya','Cruz',NULL,'maya.cruz.trainee@shelfsense.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','trainee',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-09-08 04:20:46','2026-10-11 01:52:21',1),(17,'SM-002','Store','Manager Two',NULL,'sm2@shelfsense.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','store_manager',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-09-13 02:07:56','2026-10-11 01:52:21',1),(18,'HS-003','Staff','TestAccount',NULL,'hs-003-test@example.com','$2y$10$aE8L22trQwJCIpDX29v.tO/G.1y1Z1g21pl.iUmtlr6MUeNNIES4K','hr_staff',1,1,0,1,1,NULL,NULL,'none',NULL,'2026-09-12 16:00:00',15.00,15.00,5.00,0.00,60.00,'2026-09-13 03:10:12','2026-10-11 01:52:21',0),(28,'TR-305','test','test','test','test@gmail.com','$2y$10$RSh3WsZbS/qerptY.xlYUuEjWHdWZVX5vAnm5o/pv8693eFU5tlKS','trainee',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,60.00,'2026-10-11 11:49:17','2026-10-11 11:49:17',1);
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

-- Dump completed on 2026-10-14  1:24:38
