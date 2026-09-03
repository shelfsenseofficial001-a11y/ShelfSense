-- ShelfSense SQL Dump
-- Regenerated from live schema on Sep 04, 2026
-- Sync includes: applicant_skill_ratings (position-specific skills
-- self-assessment answers collected on the public Apply page).


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
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicant_skill_ratings`
--

LOCK TABLES `applicant_skill_ratings` WRITE;
/*!40000 ALTER TABLE `applicant_skill_ratings` DISABLE KEYS */;
INSERT INTO `applicant_skill_ratings` VALUES (25,15,'cash_handling',3,'2026-09-03 14:17:36'),(26,15,'card_digital_payments',4,'2026-09-03 14:17:36'),(27,15,'drawer_reconciliation',4,'2026-09-03 14:17:36'),(28,15,'register_speed_accuracy',3,'2026-09-03 14:17:36'),(29,15,'deescalating_customers',5,'2026-09-03 14:17:37'),(35,17,'cash_handling',4,'2026-09-03 14:36:45'),(36,17,'card_digital_payments',4,'2026-09-03 14:36:45'),(37,17,'drawer_reconciliation',5,'2026-09-03 14:36:45'),(38,17,'register_speed_accuracy',5,'2026-09-03 14:36:45'),(39,17,'deescalating_customers',5,'2026-09-03 14:36:45'),(40,18,'cash_handling',5,'2026-09-03 14:47:28'),(41,18,'card_digital_payments',5,'2026-09-03 14:47:28'),(42,18,'drawer_reconciliation',5,'2026-09-03 14:47:28'),(43,18,'register_speed_accuracy',5,'2026-09-03 14:47:28'),(44,18,'deescalating_customers',5,'2026-09-03 14:47:28'),(45,19,'cash_handling',5,'2026-09-03 15:23:06'),(46,19,'card_digital_payments',4,'2026-09-03 15:23:06'),(47,19,'drawer_reconciliation',5,'2026-09-03 15:23:07'),(48,19,'register_speed_accuracy',5,'2026-09-03 15:23:07'),(49,19,'deescalating_customers',5,'2026-09-03 15:23:07');
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
  `status` enum('pending','initial_scheduled','initial_passed','initial_failed','final_scheduled','final_passed','final_failed','screening','screening_success','screening_failed','contract_offered','contract_declined','hired') DEFAULT 'pending',
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicants`
--

LOCK TABLES `applicants` WRITE;
/*!40000 ALTER TABLE `applicants` DISABLE KEYS */;
INSERT INTO `applicants` VALUES (8,'Stephen','Frias','F','allen@sd.ph','09606824148',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Philippines','HR Staff',3,'uploads/resumes/eb18599e34bda8378b2645c12f4738ee.docx','screening','2026-08-31 03:59:28','2026-08-31 07:22:53'),(9,'Test','Vale','','rumbines.allen@ncst.edu.ph','09264550078',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Philippines','Employee',4,'uploads/resumes/621f41378d5095a2c60d0c7db0ef5521.docx','initial_scheduled','2026-09-02 07:11:51','2026-09-02 07:13:20'),(11,'Stephen','Frias','','stephenfrias04@gmail.com','09606824148','1999-07-03','Cavite','042100000','City of Imus','042109000','Bayan Luma VI','042109045','B4, LT 18','Narra Drv','Treelane 3-B Subdivision','4103','Philippines','Employee',4,'uploads/resumes/1c43842a768dbe484efe02b13f9e95f2.docx','pending','2026-09-03 03:24:39','2026-09-03 03:24:39'),(15,'gabbi','cecily','','gabbicecily@gmail.com','09840234432','1997-07-09','Abra','140100000','Bangued','140101000','Agtangao','140101001','1321321w2w4','3244314421344',NULL,'1412','Philippines','Employee',4,'uploads/resumes/1aa76d3db794d98e2df44099d4936ad4.pdf','initial_failed','2026-09-03 14:17:36','2026-09-03 15:20:32'),(17,'gab','ceci','','tristianoliquino@gmail.com','09840234432','2007-11-14','Abra','140100000','Bangued','140101000','Angad','140101002','fwfqwerrwerewrwe','rewrwerewr',NULL,'4108','Philippines','Employee',4,'uploads/resumes/0253d0fda1ebb730ec3ed489ded0c878.pdf','initial_failed','2026-09-03 14:36:45','2026-09-03 15:20:23'),(18,'gab','ceci','','tristiangabrieloliquino@gmail.com','09840234432','2007-11-14','Abra','140100000','Bangued','140101000','Agtangao','140101001','1321321w2w4','3244314421344',NULL,'1412','Philippines','Employee',4,'uploads/resumes/7098aa0bc2dfcb8d2e1cbf9e5e831bd6.pdf','initial_failed','2026-09-03 14:47:28','2026-09-03 15:20:15'),(19,'gab','ceci','','trainee@shelfsense.com','09840234432','2007-11-14','Abra','140100000','Bangued','140101000','Angad','140101002','1321321w2w4','3244314421344',NULL,'1412','Philippines','Employee',4,'uploads/resumes/448bee4c0be0375fda6191da2919199e.pdf','pending','2026-09-03 15:23:06','2026-09-03 15:23:06');
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
-- Table structure for table `budget_adjustments`
--

DROP TABLE IF EXISTS `budget_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budget_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) NOT NULL,
  `department` varchar(20) NOT NULL,
  `month_year` varchar(10) NOT NULL,
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
  CONSTRAINT `fk_budget_adjustments_budget` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`),
  CONSTRAINT `fk_budget_adjustments_user` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_adjustments`
--

LOCK TABLES `budget_adjustments` WRITE;
/*!40000 ALTER TABLE `budget_adjustments` DISABLE KEYS */;
INSERT INTO `budget_adjustments` VALUES (7,2,'hr','2026-08',50000.00,50009.59,9.59,0.00,0.00,6,'Revenue split for August 16-31, 2026 (+9.59)','2026-08-31 14:07:05'),(8,4,'general','2026-08',20000.00,20005.59,5.59,0.00,0.00,6,'Revenue split for August 16-31, 2026 (+5.59)','2026-08-31 14:07:05'),(9,1,'store','2026-08',10000.00,10000.80,0.80,112.33,0.00,6,'Revenue split for August 16-31, 2026 (+0.80)','2026-08-31 14:07:05'),(10,1,'store','2026-08',10000.80,10000.00,-0.80,140.28,0.00,6,NULL,'2026-08-31 21:20:47'),(11,2,'hr','2026-08',50009.59,50009.59,0.00,0.00,0.00,6,'Revenue split for August 1-15, 2026 (+0.00)','2026-08-31 21:26:12'),(12,1,'store','2026-08',10000.00,10000.00,0.00,140.28,0.00,6,'Revenue split for August 1-15, 2026 (+0.00)','2026-08-31 21:26:12'),(13,4,'general','2026-08',20005.59,20005.59,0.00,0.00,0.00,6,'Revenue split for August 1-15, 2026 (+0.00)','2026-08-31 21:26:12'),(14,100002,'finance','2026-09-H1',0.00,0.00,0.00,0.00,0.00,6,'Revenue split for September 1-14, 2026 (+0.00)','2026-08-31 21:57:31'),(15,100003,'hr','2026-09-H1',0.00,0.00,0.00,0.00,0.00,6,'Revenue split for September 1-14, 2026 (+0.00)','2026-08-31 21:57:31'),(16,100004,'store','2026-09-H1',0.00,0.00,0.00,0.00,0.00,6,'Revenue split for September 1-14, 2026 (+0.00)','2026-08-31 21:57:31'),(17,100005,'general','2026-09-H1',0.00,0.00,0.00,0.00,0.00,6,'Revenue split for September 1-14, 2026 (+0.00)','2026-08-31 21:57:31'),(18,100006,'finance','2026-08-H2',0.00,15000.00,15000.00,0.00,0.00,6,'Prototype test allocation for cutoff migration','2026-08-31 21:58:59'),(19,100002,'finance','2026-09-H1',0.00,20000.00,20000.00,0.00,0.00,6,NULL,'2026-09-01 14:08:15'),(20,100004,'store','2026-09-H1',0.00,20000.00,20000.00,0.00,0.00,6,NULL,'2026-09-01 14:09:08');
/*!40000 ALTER TABLE `budget_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(20) NOT NULL,
  `month_year` varchar(10) NOT NULL,
  `allocated_budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `used_budget` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_department_month` (`department`,`month_year`),
  KEY `idx_department` (`department`),
  KEY `idx_month_year` (`month_year`)
) ENGINE=InnoDB AUTO_INCREMENT=100007 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgets`
--

LOCK TABLES `budgets` WRITE;
/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
INSERT INTO `budgets` VALUES (1,'store','2026-08',10000.00,140.28,NULL,'2026-08-21 16:47:43','2026-08-31 21:20:47'),(2,'hr','2026-08',50009.59,0.00,NULL,'2026-08-21 16:47:43','2026-08-31 14:07:05'),(3,'finance','2026-08',30000.00,0.00,NULL,'2026-08-21 16:47:43','2026-08-24 16:56:15'),(4,'general','2026-08',20005.59,0.00,NULL,'2026-08-21 16:47:43','2026-08-31 14:07:05'),(100002,'finance','2026-09-H1',20000.00,0.00,NULL,'2026-08-31 21:57:31','2026-09-01 14:08:15'),(100003,'hr','2026-09-H1',0.00,0.00,NULL,'2026-08-31 21:57:31','2026-08-31 21:57:31'),(100004,'store','2026-09-H1',20000.00,555.35,NULL,'2026-08-31 21:57:31','2026-09-02 07:05:39'),(100005,'general','2026-09-H1',0.00,0.00,NULL,'2026-08-31 21:57:31','2026-08-31 21:57:31'),(100006,'finance','2026-08-H2',15000.00,0.00,NULL,'2026-08-31 21:58:59','2026-08-31 21:58:59');
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
INSERT INTO `categories` VALUES (1,'Books',NULL,1,'2026-08-20 17:09:05'),(2,'School Supplies',NULL,1,'2026-08-20 17:09:05');
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
  `contract_type` varchar(20) DEFAULT 'hired',
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `applicant_id` (`applicant_id`),
  KEY `offered_by` (`offered_by`),
  CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `contracts_ibfk_3` FOREIGN KEY (`offered_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts`
--

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
INSERT INTO `email_logs` VALUES (1,'rumbines.allen@ncst.edu.ph','Password Reset OTP - ShelfSense',NULL,'sent','2026-08-26 03:23:55'),(10,'rumbines.allen@ncst.edu.ph','ShelfSense SMTP Test',NULL,'sent','2026-08-31 03:18:58'),(11,'stephenfrias04@gmail.com','Application Received',NULL,'sent','2026-08-31 03:19:50'),(12,'stephenfrias04@gmail.com','Initial Interview Scheduled',NULL,'sent','2026-08-31 03:25:34'),(13,'stephenfrias04@gmail.com','Your Trainee Contract - ShelfSense',NULL,'sent','2026-08-31 03:34:41'),(14,'hr.staff2@shelfsense.com','New Trainee Assigned - ShelfSense',NULL,'sent','2026-08-31 03:34:45'),(15,'allen@sd.ph','Application Received',NULL,'sent','2026-08-31 03:59:33'),(16,'allen@sd.ph','Initial Interview Scheduled',NULL,'sent','2026-08-31 04:02:22'),(17,'stephenfrias04@gmail.com','Final Interview Scheduled',NULL,'sent','2026-08-31 06:16:13'),(18,'admin@shelfsense.com','Final Interview Scheduled - Stephen Frias',NULL,'sent','2026-08-31 06:16:19'),(19,'stephenfrias04@gmail.com','Your Employment Contract - ShelfSense',NULL,'sent','2026-08-31 06:35:48'),(20,'stephenfrias04@gmail.com','Congratulations! You\'re Hired!',NULL,'sent','2026-08-31 06:46:52'),(21,'stephenfrias04@gmail.com','Congratulations! You\'re Hired!',NULL,'sent','2026-08-31 06:48:58'),(22,'allen@sd.ph','Your Trainee Contract - ShelfSense',NULL,'sent','2026-08-31 07:21:57'),(23,'hr.staff2@shelfsense.com','New Trainee Assigned - ShelfSense',NULL,'sent','2026-08-31 07:22:03'),(24,'allen@sd.ph','Your Employment Contract - ShelfSense',NULL,'sent','2026-08-31 07:22:38'),(25,'rumbines.allen@ncst.edu.ph','Application Received',NULL,'sent','2026-09-02 07:11:58'),(26,'rumbines.allen@ncst.edu.ph','Initial Interview Scheduled',NULL,'sent','2026-09-02 07:13:27'),(27,'juan.delacruz.curltest3@example.com','Application Received',NULL,'sent','2026-09-03 02:59:52'),(28,'stephenfrias04@gmail.com','Application Received',NULL,'sent','2026-09-03 03:24:44'),(29,'curltester_1788443920@example.com','Application Received',NULL,'sent','2026-09-03 13:58:45'),(30,'chartverify_1788444298@example.com','Application Received',NULL,'sent','2026-09-03 14:05:03'),(31,'qonlyverify_1788444631@example.com','Application Received',NULL,'sent','2026-09-03 14:10:36'),(32,'gabbicecily@gmail.com','Application Received',NULL,'sent','2026-09-03 14:17:41'),(33,'sidepanel_1788445405@example.com','Application Received',NULL,'sent','2026-09-03 14:23:30'),(34,'tristianoliquino@gmail.com','Application Received',NULL,'sent','2026-09-03 14:36:50'),(35,'tristiangabrieloliquino@gmail.com','Application Received',NULL,'sent','2026-09-03 14:47:33'),(36,'tristiangabrieloliquino@gmail.com','Application Update',NULL,'sent','2026-09-03 15:20:19'),(37,'tristianoliquino@gmail.com','Application Update',NULL,'sent','2026-09-03 15:20:27'),(38,'gabbicecily@gmail.com','Application Update',NULL,'sent','2026-09-03 15:20:37'),(39,'trainee@shelfsense.com','Application Received',NULL,'sent','2026-09-03 15:23:11');
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
  `requisition_item_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_receipt_id` (`goods_receipt_id`),
  KEY `requisition_item_id` (`requisition_item_id`),
  CONSTRAINT `goods_receipt_items_ibfk_1` FOREIGN KEY (`goods_receipt_id`) REFERENCES `goods_receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `goods_receipt_items_ibfk_2` FOREIGN KEY (`requisition_item_id`) REFERENCES `store_requisition_items` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipt_items`
--

LOCK TABLES `goods_receipt_items` WRITE;
/*!40000 ALTER TABLE `goods_receipt_items` DISABLE KEYS */;
INSERT INTO `goods_receipt_items` VALUES (1,1,2,12,NULL),(2,2,3,15,NULL),(3,3,4,20,NULL),(4,4,10,10,NULL),(5,4,11,5,NULL);
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
  `requisition_id` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `receipt_date` date NOT NULL,
  `status` enum('draft','completed') DEFAULT 'completed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `requisition_id` (`requisition_id`),
  KEY `received_by` (`received_by`),
  CONSTRAINT `goods_receipts_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `store_requisitions` (`id`),
  CONSTRAINT `goods_receipts_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `goods_receipts`
--

LOCK TABLES `goods_receipts` WRITE;
/*!40000 ALTER TABLE `goods_receipts` DISABLE KEYS */;
INSERT INTO `goods_receipts` VALUES (1,2,5,'2026-08-24','completed',NULL,'2026-08-23 19:06:30'),(2,4,5,'2026-08-26','completed',NULL,'2026-08-26 03:15:28'),(3,5,5,'2026-08-26','completed',NULL,'2026-08-26 03:18:31'),(4,9,5,'2026-09-02','completed',NULL,'2026-09-02 07:07:35');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interviews`
--

LOCK TABLES `interviews` WRITE;
/*!40000 ALTER TABLE `interviews` DISABLE KEYS */;
INSERT INTO `interviews` VALUES (4,8,3,'initial','2026-09-01 02:00:00','https://meet.com','','','completed','passed','2026-08-31 04:02:18','2026-08-31 04:06:54'),(6,9,3,'initial','2026-09-03 02:00:00','https://meet.google.com/xxx-xxxx-xxx','',NULL,'scheduled','pending','2026-09-02 07:13:20','2026-09-02 07:13:20');
/*!40000 ALTER TABLE `interviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_postings`
--

DROP TABLE IF EXISTS `job_postings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_postings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `department_group` varchar(50) NOT NULL DEFAULT 'Front Department',
  `department` varchar(50) NOT NULL,
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
  `submitted_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `archived_at` datetime DEFAULT NULL,
  `reused_from_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `rejected_by` (`rejected_by`),
  KEY `reused_from_id` (`reused_from_id`),
  CONSTRAINT `job_postings_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `job_postings_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `job_postings_ibfk_3` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `job_postings_ibfk_4` FOREIGN KEY (`reused_from_id`) REFERENCES `job_postings` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_postings`
--

LOCK TABLES `job_postings` WRITE;
/*!40000 ALTER TABLE `job_postings` DISABLE KEYS */;
INSERT INTO `job_postings` VALUES (1,'Cashier','Front Department','Cashier','Main Store, Dasmarinas','Cashier','Full-Time','Responsible for processing daily sales transactions, maintaining accurate cash handling, providing excellent customer service, and ensuring the checkout area is clean and organized.','1+ year retail or cashier experience\nStrong communication and interpersonal skills\nAvailable for shifting schedules (opening/closing)\nBasic math and computer skills\nHigh school diploma or equivalent','Process daily sales transactions accurately\nProvide excellent customer service\nMaintain a clean and organized checkout area\nHandle cash, card, and digital payments\nAssist with inventory counts as needed',9900.00,10500.00,3,'2026-09-01','approved','2026-08-26 13:34:43',3,2,'2026-08-26 13:35:19',NULL,NULL,NULL,NULL,NULL,'2026-08-26 05:34:37','2026-08-26 06:20:01'),(2,'HR Staff','Human Resources Department','HR Staff','Headquarters','hr_staff','Full-Time','Support recruitment, employee records, and day-to-day HR operations.','HR-related degree or experience\nAttention to detail\nGood communication skills',NULL,NULL,NULL,2,'2026-10-26','approved','2026-08-26 14:17:06',3,2,'2026-08-26 14:17:10',NULL,NULL,NULL,NULL,NULL,'2026-08-26 06:17:06','2026-08-31 02:42:35'),(3,'HR Staff toy','Human Resources Department','HR Staff','asdasd','Hr staff','Full-Time','asdasd','asdasd','asdasd',1233.00,3211.00,20,'2026-09-04','closed','2026-08-31 10:45:00',3,2,'2026-08-31 10:50:54',NULL,NULL,NULL,NULL,NULL,'2026-08-31 02:45:00','2026-08-31 15:20:33'),(4,'Kelangan ng cashier','Front Department','Cashier','Dasma','mema','Full-Time','AKLsjdlkasdasd\nasdasds\nasd\nasd\na','ASd\nAsd\nasd\nAsd\nasd','asd\nasd\nasd\nasd',NULL,NULL,2,'2026-09-30','approved','2026-09-01 23:41:29',3,2,'2026-09-01 23:42:36',NULL,NULL,NULL,NULL,NULL,'2026-09-01 15:41:29','2026-09-01 15:42:36');
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
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,5,'invoice_received','Supplier has sent invoice for requisition #REQ-2026-0002. Please review and forward to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-23 17:03:58'),(2,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0002 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 17:05:18'),(3,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0002 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 17:05:18'),(4,5,'invoice_forwarded_success','Invoice for requisition #REQ-2026-0002 has been forwarded to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-23 17:05:18'),(5,6,'payment_request_pending','Payment request for requisition #REQ-2026-0002 is pending approval. Amount: ₱28.68','?page=finance_head_payment_requests',0,'2026-08-23 17:33:29'),(6,12,'payment_completed','Payment for requisition #REQ-2026-0002 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-23 17:34:35'),(7,7,'payment_request_approved','Payment request for requisition #REQ-2026-0002 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-23 17:34:35'),(8,5,'payment_approved','Payment for requisition #REQ-2026-0002 has been approved. The supplier will ship the goods.','?page=store_manager_requisitions',0,'2026-08-23 17:34:35'),(9,5,'invoice_received','Supplier has sent invoice for requisition #REQ-2026-0003. Please review and forward to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-23 19:09:52'),(10,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0003 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 19:11:10'),(11,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0003 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 19:11:11'),(12,5,'invoice_forwarded_success','Invoice for requisition #REQ-2026-0003 has been forwarded to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-23 19:11:11'),(13,6,'payment_request_pending','Payment request for requisition #REQ-2026-0003 is pending approval. Amount: ₱35.85','?page=finance_head_payment_requests',0,'2026-08-23 19:11:55'),(14,12,'payment_completed','Payment for requisition #REQ-2026-0003 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-24 17:22:48'),(15,7,'payment_request_approved','Payment request for requisition #REQ-2026-0003 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-24 17:22:48'),(16,5,'payment_approved','Payment for requisition #REQ-2026-0003 has been approved. The supplier will ship the goods.','?page=store_manager_requisitions',0,'2026-08-24 17:22:48'),(17,5,'invoice_received','Supplier has sent invoice for requisition #REQ-2026-0004. Please review and forward to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-26 03:12:43'),(18,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0004 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-26 03:13:09'),(19,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0004 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-26 03:13:09'),(20,5,'invoice_forwarded_success','Invoice for requisition #REQ-2026-0004 has been forwarded to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-26 03:13:09'),(21,6,'payment_request_pending','Payment request for requisition #REQ-2026-0004 is pending approval. Amount: ₱47.80','?page=finance_head_payment_requests',0,'2026-08-26 03:13:43'),(22,12,'payment_completed','Payment for requisition #REQ-2026-0004 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-26 03:14:21'),(23,7,'payment_request_approved','Payment request for requisition #REQ-2026-0004 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-26 03:14:21'),(24,5,'payment_approved','Payment for requisition #REQ-2026-0004 has been approved. The supplier will ship the goods.','?page=store_manager_requisitions',0,'2026-08-26 03:14:21'),(25,5,'goods_shipped','Supplier has shipped goods for requisition #REQ-2026-0004. Please receive the goods.','?page=store_manager_requisitions',0,'2026-08-26 03:14:58'),(35,2,'job_posting_submitted','Job posting \"Cashier\" is awaiting your review.','?page=hr_job_postings',0,'2026-08-26 05:34:43'),(36,3,'job_posting_approved','Your job posting \"Cashier\" was approved and is now public.','?page=hr_job_postings',0,'2026-08-26 05:35:19'),(46,2,'job_posting_submitted','A new job posting \"HR Staff\" is awaiting your review.','?page=hr_job_postings',0,'2026-08-26 06:17:06'),(47,3,'job_posting_approved','Your job posting \"HR Staff\" was approved and is now public.','?page=hr_job_postings',0,'2026-08-26 06:17:10'),(48,2,'job_posting_submitted','Job posting \"HR Staff toy\" is awaiting your review.','?page=hr_job_postings',0,'2026-08-31 02:45:00'),(49,3,'job_posting_approved','Your job posting \"HR Staff toy\" was approved and is now public.','?page=hr_job_postings',0,'2026-08-31 02:50:54'),(50,2,'new_application','New application from Stephen Frias for HR Staff position','?page=hr_applicants',0,'2026-08-31 03:19:50'),(51,3,'new_application','New application from Stephen Frias for HR Staff position','?page=hr_applicants',0,'2026-08-31 03:19:50'),(52,4,'new_application','New application from Stephen Frias for HR Staff position','?page=hr_applicants',0,'2026-08-31 03:19:50'),(53,3,'interview_scheduled','initial interview scheduled for Stephen Frias',NULL,0,'2026-08-31 03:25:29'),(54,3,'trainee_created','Trainee account created for Stephen Frias. Trainer Ana Reyes is locked.',NULL,0,'2026-08-31 03:34:37'),(55,14,'trainee_contract_ready','Your Trainee Contract is ready. Trainer: Ana Reyes. Salary: ₱4200. Hours: 10:00:00–15:00:00. Rest days: Saturday, Sunday.',NULL,0,'2026-08-31 03:34:37'),(56,4,'trainee_assigned','You have been assigned as trainer for Stephen Frias (HR Staff).',NULL,0,'2026-08-31 03:34:37'),(57,2,'new_application','New application from Stephen Frias for HR Staff toy position','?page=hr_applicants',0,'2026-08-31 03:59:33'),(58,3,'new_application','New application from Stephen Frias for HR Staff toy position','?page=hr_applicants',0,'2026-08-31 03:59:33'),(59,4,'new_application','New application from Stephen Frias for HR Staff toy position','?page=hr_applicants',0,'2026-08-31 03:59:33'),(60,3,'interview_scheduled','initial interview scheduled for Stephen Frias',NULL,0,'2026-08-31 04:02:18'),(61,3,'trainee_report_submitted','A week 1 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:04:12'),(62,4,'trainee_report_submitted','A week 1 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:04:12'),(63,3,'trainee_report_submitted','A week 1 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:17:58'),(64,4,'trainee_report_submitted','A week 1 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:17:58'),(65,3,'trainee_report_submitted','A week 2 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(66,4,'trainee_report_submitted','A week 2 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(67,3,'trainee_report_submitted','A week 3 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(68,4,'trainee_report_submitted','A week 3 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(69,3,'trainee_report_submitted','A week 4 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(70,4,'trainee_report_submitted','A week 4 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(71,3,'trainee_report_submitted','A week 5 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(72,4,'trainee_report_submitted','A week 5 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(73,3,'trainee_report_submitted','A week 6 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(74,4,'trainee_report_submitted','A week 6 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(75,3,'trainee_report_submitted','A week 7 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(76,4,'trainee_report_submitted','A week 7 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(77,3,'trainee_report_submitted','A week 8 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(78,4,'trainee_report_submitted','A week 8 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(79,3,'trainee_report_submitted','A week 9 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(80,4,'trainee_report_submitted','A week 9 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(81,3,'trainee_report_submitted','A week 10 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(82,4,'trainee_report_submitted','A week 10 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(83,3,'trainee_report_submitted','A week 11 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(84,4,'trainee_report_submitted','A week 11 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(85,3,'trainee_report_submitted','A week 12 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(86,4,'trainee_report_submitted','A week 12 report was submitted for review.','?page=hr_trainees',0,'2026-08-31 05:25:37'),(87,2,'trainee_report_forwarded','A trainee\'s week 2 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:27:16'),(88,2,'trainee_report_forwarded','A trainee\'s week 1 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:28:09'),(89,2,'trainee_report_forwarded','A trainee\'s week 3 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:30:09'),(90,2,'trainee_report_forwarded','A trainee\'s week 4 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:30:09'),(91,2,'trainee_report_forwarded','A trainee\'s week 5 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:30:09'),(92,2,'trainee_report_forwarded','A trainee\'s week 6 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:30:09'),(93,2,'trainee_report_forwarded','A trainee\'s week 7 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:30:09'),(94,2,'trainee_report_forwarded','A trainee\'s week 8 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:30:09'),(95,2,'trainee_report_forwarded','A trainee\'s week 9 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:30:09'),(96,2,'trainee_report_forwarded','A trainee\'s week 10 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:30:09'),(97,2,'trainee_report_forwarded','A trainee\'s week 11 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:30:09'),(98,2,'trainee_report_forwarded','A trainee\'s week 12 report was forwarded for your review.','?page=hr_trainees',0,'2026-08-31 05:30:09'),(99,1,'interview_scheduled','final interview updated for Stephen Frias',NULL,0,'2026-08-31 06:16:08'),(100,1,'final_interview_scheduled','Final Interview scheduled for Stephen Frias on September 1, 2026 02:00 AM.','?page=hr_interviews',0,'2026-08-31 06:16:13'),(101,14,'hired_contract_ready','Your Hired Contract is ready for your review. Please respond from your dashboard.','?page=dashboard',0,'2026-08-31 06:35:43'),(102,1,'hired_contract_offered','Hired Contract offered to Stephen Frias following the Final Interview.',NULL,0,'2026-08-31 06:35:48'),(103,2,'contract_accepted','Stephen Frias accepted their Hired Contract.','?page=hr_contracts',0,'2026-08-31 06:46:52'),(104,2,'contract_accepted','Stephen Frias accepted their Hired Contract.','?page=hr_contracts',0,'2026-08-31 06:48:58'),(105,1,'trainee_created','Trainee account created for Stephen Frias. Trainer Ana Reyes is locked.',NULL,0,'2026-08-31 07:21:52'),(106,15,'trainee_contract_ready','Your Trainee Contract is ready. Trainer: Ana Reyes. Salary: ₱3,900.00 – ₱4,500.00. Hours: 10:00:00–15:00:00. Rest days: Saturday, Sunday.',NULL,0,'2026-08-31 07:21:52'),(107,4,'trainee_assigned','You have been assigned as trainer for Stephen Frias (HR Staff).',NULL,0,'2026-08-31 07:21:52'),(108,15,'hired_contract_ready','Your Hired Contract is ready for your review. Please respond from your dashboard.','?page=dashboard',0,'2026-08-31 07:22:33'),(109,1,'hired_contract_offered','Hired Contract offered to Stephen Frias following the Final Interview.',NULL,0,'2026-08-31 07:22:38'),(110,9,'order_completed','Order #POS-20260831-0001 completed. Total: ₱25.98','?page=pos_orders&view=1',0,'2026-08-31 08:16:27'),(111,9,'order_completed','Order #POS-20260831-0001 completed. Total: ₱12.99','?page=pos_orders&view=2',0,'2026-08-31 08:27:50'),(112,9,'order_completed','Order #POS-20260831-0001 completed. Total: ₱5.98','?page=pos_orders&view=3',0,'2026-08-31 08:39:16'),(113,9,'order_completed','Order #POS-20260831-0001 completed. Total: ₱12.99','?page=pos_orders&view=4',0,'2026-08-31 08:41:31'),(114,9,'order_completed','Order #POS-20260831-0001 completed. Total: ₱12.99','?page=pos_orders&view=5',0,'2026-08-31 08:51:02'),(115,9,'order_completed','Order #POS-20260831-0001 completed. Total: ₱25.98','?page=pos_orders&view=6',0,'2026-08-31 09:03:11'),(116,9,'order_completed','Order #POS-20260831-0001 completed. Total: ₱12.99','?page=pos_orders&view=7',0,'2026-08-31 09:21:50'),(117,9,'order_completed','Order #POS-20260831-0002 completed. Total: ₱2.99','?page=pos_orders&view=8',0,'2026-08-31 13:34:40'),(118,5,'invoice_received','Supplier has sent invoice for requisition #REQ-2026-0005. Please review and forward to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-31 14:45:33'),(119,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0005 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-31 14:48:52'),(120,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0005 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-31 14:48:52'),(121,5,'invoice_forwarded_success','Invoice for requisition #REQ-2026-0005 has been forwarded to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-31 14:48:52'),(122,6,'payment_request_pending','Payment request for requisition #REQ-2026-0005 is pending approval. Amount: ₱27.95','?page=finance_head_payment_requests',0,'2026-08-31 14:52:30'),(123,12,'payment_completed','Payment for requisition #REQ-2026-0005 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-31 14:54:29'),(124,7,'payment_request_approved','Payment request for requisition #REQ-2026-0005 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-31 14:54:29'),(125,5,'payment_approved','Payment for requisition #REQ-2026-0005 has been approved. The supplier will ship the goods.','?page=store_manager_requisitions',0,'2026-08-31 14:54:29'),(126,5,'invoice_received','Supplier has sent invoice for requisition #REQ-2026-0007. Please review and forward to Finance Staff.','?page=store_manager_requisitions',0,'2026-09-01 14:02:17'),(127,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0007 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-09-01 14:04:14'),(128,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0007 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-09-01 14:04:14'),(129,5,'invoice_forwarded_success','Invoice for requisition #REQ-2026-0007 has been forwarded to Finance Staff.','?page=store_manager_requisitions',0,'2026-09-01 14:04:14'),(130,6,'payment_request_pending','Payment request for requisition #REQ-2026-0007 is pending approval. Amount: ₱439.50','?page=finance_head_payment_requests',0,'2026-09-01 14:10:25'),(131,12,'payment_completed','Payment for requisition #REQ-2026-0007 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-09-01 14:11:25'),(132,7,'payment_request_approved','Payment request for requisition #REQ-2026-0007 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-09-01 14:11:25'),(133,5,'payment_approved','Payment for requisition #REQ-2026-0007 has been approved. The supplier will ship the goods.','?page=store_manager_requisitions',0,'2026-09-01 14:11:25'),(134,2,'job_posting_submitted','Job posting \"Kelangan ng cashier\" is awaiting your review.','?page=hr_job_postings',0,'2026-09-01 15:41:29'),(135,3,'job_posting_approved','Your job posting \"Kelangan ng cashier\" was approved and is now public.','?page=hr_job_postings',0,'2026-09-01 15:42:36'),(136,9,'order_completed','Order #POS-20260902-0001 completed. Total: ₱14.95','?page=pos_orders&view=9',0,'2026-09-02 01:43:18'),(137,9,'order_completed','Order #POS-20260902-0002 completed. Total: ₱12.99','?page=pos_orders&view=10',0,'2026-09-02 06:47:39'),(138,5,'invoice_received','Supplier has sent invoice for requisition #REQ-2026-0008. Please review and forward to Finance Staff.','?page=store_manager_requisitions',0,'2026-09-02 06:59:55'),(139,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0008 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-09-02 07:03:06'),(140,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0008 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-09-02 07:03:06'),(141,5,'invoice_forwarded_success','Invoice for requisition #REQ-2026-0008 has been forwarded to Finance Staff.','?page=store_manager_requisitions',0,'2026-09-02 07:03:06'),(142,6,'payment_request_pending','Payment request for requisition #REQ-2026-0008 is pending approval. Amount: ₱115.85','?page=finance_head_payment_requests',0,'2026-09-02 07:04:29'),(143,12,'payment_completed','Payment for requisition #REQ-2026-0008 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-09-02 07:05:39'),(144,7,'payment_request_approved','Payment request for requisition #REQ-2026-0008 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-09-02 07:05:39'),(145,5,'payment_approved','Payment for requisition #REQ-2026-0008 has been approved. The supplier will ship the goods.','?page=store_manager_requisitions',0,'2026-09-02 07:05:39'),(146,5,'goods_shipped','Supplier has shipped goods for requisition #REQ-2026-0008. Please receive the goods.','?page=store_manager_requisitions',0,'2026-09-02 07:06:46'),(147,2,'new_application','New application from Test Vale for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-02 07:11:58'),(148,3,'new_application','New application from Test Vale for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-02 07:11:58'),(149,4,'new_application','New application from Test Vale for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-02 07:11:58'),(150,14,'new_application','New application from Test Vale for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-02 07:11:58'),(151,3,'interview_scheduled','initial interview scheduled for Test Vale',NULL,0,'2026-09-02 07:13:20'),(152,2,'new_application','New application from Juan Dela Cruz for HR Staff position','?page=hr_applicants',0,'2026-09-03 02:59:52'),(153,3,'new_application','New application from Juan Dela Cruz for HR Staff position','?page=hr_applicants',0,'2026-09-03 02:59:52'),(154,4,'new_application','New application from Juan Dela Cruz for HR Staff position','?page=hr_applicants',0,'2026-09-03 02:59:52'),(155,14,'new_application','New application from Juan Dela Cruz for HR Staff position','?page=hr_applicants',0,'2026-09-03 02:59:52'),(156,2,'new_application','New application from Stephen Frias for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 03:24:44'),(157,3,'new_application','New application from Stephen Frias for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 03:24:44'),(158,4,'new_application','New application from Stephen Frias for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 03:24:44'),(159,14,'new_application','New application from Stephen Frias for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 03:24:44'),(160,2,'new_application','New application from Curl Tester for HR Staff position','?page=hr_applicants',0,'2026-09-03 13:58:45'),(161,3,'new_application','New application from Curl Tester for HR Staff position','?page=hr_applicants',0,'2026-09-03 13:58:45'),(162,4,'new_application','New application from Curl Tester for HR Staff position','?page=hr_applicants',0,'2026-09-03 13:58:45'),(163,14,'new_application','New application from Curl Tester for HR Staff position','?page=hr_applicants',0,'2026-09-03 13:58:45'),(164,2,'new_application','New application from Chart Verify for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:05:03'),(165,3,'new_application','New application from Chart Verify for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:05:03'),(166,4,'new_application','New application from Chart Verify for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:05:03'),(167,14,'new_application','New application from Chart Verify for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:05:03'),(168,2,'new_application','New application from Q Verify for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:10:36'),(169,3,'new_application','New application from Q Verify for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:10:36'),(170,4,'new_application','New application from Q Verify for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:10:36'),(171,14,'new_application','New application from Q Verify for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:10:36'),(172,2,'new_application','New application from gabbi cecily for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:17:41'),(173,3,'new_application','New application from gabbi cecily for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:17:41'),(174,4,'new_application','New application from gabbi cecily for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:17:41'),(175,14,'new_application','New application from gabbi cecily for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:17:41'),(176,2,'new_application','New application from Side Panel for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:23:30'),(177,3,'new_application','New application from Side Panel for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:23:30'),(178,4,'new_application','New application from Side Panel for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:23:30'),(179,14,'new_application','New application from Side Panel for HR Staff position','?page=hr_applicants',0,'2026-09-03 14:23:30'),(180,2,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:36:50'),(181,3,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:36:50'),(182,4,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:36:50'),(183,14,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:36:50'),(184,2,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:47:33'),(185,3,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:47:33'),(186,4,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:47:33'),(187,14,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 14:47:33'),(188,2,'status_update','Applicant gab ceci moved to: Initial failed',NULL,0,'2026-09-03 15:20:19'),(189,2,'status_update','Applicant gab ceci moved to: Initial failed',NULL,0,'2026-09-03 15:20:27'),(190,2,'status_update','Applicant gabbi cecily moved to: Initial failed',NULL,0,'2026-09-03 15:20:37'),(191,2,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 15:23:11'),(192,3,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 15:23:11'),(193,4,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 15:23:11'),(194,14,'new_application','New application from gab ceci for Kelangan ng cashier position','?page=hr_applicants',0,'2026-09-03 15:23:11');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (7,7,1,1,12.99,12.99,'2026-08-31 09:21:50'),(8,8,2,1,2.99,2.99,'2026-08-31 13:34:40'),(9,9,2,5,2.99,14.95,'2026-09-02 01:43:18'),(10,10,1,1,12.99,12.99,'2026-09-02 06:47:39');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (7,'POS-20260831-0001',9,NULL,12.99,12.99,12.99,0.00,'cash','','','completed',NULL,NULL,NULL,'2026-08-31 09:21:50'),(8,'POS-20260831-0002',9,1,2.99,2.99,2.99,0.00,'cash','','','completed',NULL,NULL,NULL,'2026-08-31 13:34:40'),(9,'POS-20260902-0001',9,4,14.95,14.95,100.00,85.05,'cash','','','completed',NULL,NULL,NULL,'2026-09-02 01:43:18'),(10,'POS-20260902-0002',9,5,12.99,12.99,12.99,0.00,'cash','','','completed',NULL,NULL,NULL,'2026-09-02 06:47:39');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (5,4,'858060','2026-08-21 22:59:51',0,'2026-08-21 14:44:51'),(6,10,'907358','2026-08-26 11:38:50',0,'2026-08-26 03:23:50');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_requests`
--

DROP TABLE IF EXISTS `payment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_requests` (
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
  CONSTRAINT `payment_requests_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `store_requisitions` (`id`),
  CONSTRAINT `payment_requests_ibfk_2` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`),
  CONSTRAINT `payment_requests_ibfk_3` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `payment_requests_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_requests`
--

LOCK TABLES `payment_requests` WRITE;
/*!40000 ALTER TABLE `payment_requests` DISABLE KEYS */;
INSERT INTO `payment_requests` VALUES (6,4,2,7,'2026-08-23 19:11:55','approved',6,'2026-08-25 01:22:47',NULL,1,0,NULL,NULL,'','2026-08-23 19:11:55','2026-08-24 17:22:47',NULL),(7,5,3,7,'2026-08-26 03:13:43','approved',6,'2026-08-26 11:14:21',NULL,1,0,NULL,NULL,'','2026-08-26 03:13:43','2026-08-26 03:14:21',NULL),(8,6,4,7,'2026-08-31 14:52:30','approved',6,'2026-08-31 22:54:29',NULL,1,0,NULL,NULL,'','2026-08-31 14:52:30','2026-08-31 14:54:29',NULL),(9,8,5,7,'2026-09-01 14:10:25','approved',6,'2026-09-01 22:11:25',NULL,1,0,NULL,NULL,'','2026-09-01 14:10:25','2026-09-01 14:11:25',NULL),(10,9,6,7,'2026-09-02 07:04:29','approved',6,'2026-09-02 15:05:39',NULL,1,0,NULL,NULL,'','2026-09-02 07:04:29','2026-09-02 07:05:39',NULL);
/*!40000 ALTER TABLE `payment_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
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
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`supplier_invoice_id`) REFERENCES `supplier_invoices` (`id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (4,2,35.85,'bank_transfer','AUTO-20260824192247',6,'2026-08-25 01:22:47','Auto-recorded after Finance Head approval'),(5,3,47.80,'bank_transfer','AUTO-20260826051421',6,'2026-08-26 11:14:21','Auto-recorded after Finance Head approval'),(6,4,27.95,'bank_transfer','AUTO-20260831165429',6,'2026-08-31 22:54:29','Auto-recorded after Finance Head approval'),(7,5,439.50,'bank_transfer','AUTO-20260901161125',6,'2026-09-01 22:11:25','Auto-recorded after Finance Head approval'),(8,6,115.85,'bank_transfer','AUTO-20260902090539',6,'2026-09-02 15:05:39','Auto-recorded after Finance Head approval');
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
INSERT INTO `products` VALUES (1,'978-0-123-45678-9','Sample Book','A sample book for testing',1,12.99,8.00,18,5,NULL,1,'2026-08-20 17:09:05','2026-09-02 07:07:35'),(2,'BS-001','Sample Pen','A sample pen for testing',2,2.99,1.20,64,5,NULL,1,'2026-08-20 17:09:05','2026-09-02 07:07:35');
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
  CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `received_by` (`received_by`),
  CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `purchase_orders_ibfk_4` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
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
  `action` varchar(50) NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `entity_type_id` (`entity_type`,`entity_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `recruitment_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recruitment_logs`
--

LOCK TABLES `recruitment_logs` WRITE;
/*!40000 ALTER TABLE `recruitment_logs` DISABLE KEYS */;
INSERT INTO `recruitment_logs` VALUES (12,'job_posting',1,3,'hr_staff','created',NULL,'draft',NULL,NULL,'2026-08-26 05:34:37'),(13,'job_posting',1,3,'hr_staff','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-08-26 05:34:43'),(14,'job_posting',1,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-08-26 05:35:19'),(15,'job_posting',1,2,'hr_head','hr_head_overwrite','approved','approved',NULL,NULL,'2026-08-26 06:14:21'),(19,'job_posting',2,3,'hr_staff','created',NULL,'draft',NULL,NULL,'2026-08-26 06:17:06'),(20,'job_posting',2,3,'hr_staff','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-08-26 06:17:06'),(21,'job_posting',2,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-08-26 06:17:10'),(22,'job_posting',3,3,'hr_staff','created',NULL,'draft',NULL,NULL,'2026-08-31 02:45:00'),(23,'job_posting',3,3,'hr_staff','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-08-31 02:45:00'),(24,'job_posting',3,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-08-31 02:50:54'),(25,'applicant',7,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 2)','2026-08-31 03:19:50'),(26,'applicant',7,3,'hr_staff','initial_interview_scheduled','pending','initial_scheduled',NULL,NULL,'2026-08-31 03:25:29'),(27,'applicant',7,3,'hr_staff','trainee_contract_assigned','initial_passed','screening',NULL,'Trainer #4 assigned','2026-08-31 03:34:37'),(28,'applicant',8,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 3)','2026-08-31 03:59:33'),(29,'applicant',8,3,'hr_staff','initial_interview_scheduled','pending','initial_scheduled',NULL,NULL,'2026-08-31 04:02:18'),(30,'trainee_report',2,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 1','2026-08-31 05:04:12'),(31,'trainee_report',3,4,'hr_staff','submitted',NULL,'submitted',NULL,'trainee #2, week 1','2026-08-31 05:17:58'),(32,'trainee_report',4,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 2','2026-08-31 05:25:37'),(33,'trainee_report',5,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 3','2026-08-31 05:25:37'),(34,'trainee_report',6,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 4','2026-08-31 05:25:37'),(35,'trainee_report',7,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 5','2026-08-31 05:25:37'),(36,'trainee_report',8,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 6','2026-08-31 05:25:37'),(37,'trainee_report',9,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 7','2026-08-31 05:25:37'),(38,'trainee_report',10,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 8','2026-08-31 05:25:37'),(39,'trainee_report',11,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 9','2026-08-31 05:25:37'),(40,'trainee_report',12,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 10','2026-08-31 05:25:37'),(41,'trainee_report',13,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 11','2026-08-31 05:25:37'),(42,'trainee_report',14,1,'owner','submitted',NULL,'submitted',NULL,'trainee #2, week 12','2026-08-31 05:25:37'),(43,'trainee_report',4,4,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:27:16'),(44,'trainee_report',3,1,'owner','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:28:09'),(45,'trainee_report',5,3,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:30:09'),(46,'trainee_report',6,3,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:30:09'),(47,'trainee_report',7,3,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:30:09'),(48,'trainee_report',8,3,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:30:09'),(49,'trainee_report',9,3,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:30:09'),(50,'trainee_report',10,3,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:30:09'),(51,'trainee_report',11,3,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:30:09'),(52,'trainee_report',12,3,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:30:09'),(53,'trainee_report',13,3,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:30:09'),(54,'trainee_report',14,3,'hr_staff','forwarded_to_hr_head','submitted','forwarded',NULL,NULL,'2026-08-31 05:30:09'),(55,'trainee_report',14,2,'hr_head','hr_head_reviewed',NULL,'hr_reviewed',NULL,NULL,'2026-08-31 05:32:09'),(56,'applicant',7,1,'owner','trainee_contract_completed','screening','screening_success',NULL,NULL,'2026-08-31 06:15:16'),(57,'applicant',7,1,'owner','final_interview_scheduled','screening_success','final_scheduled',NULL,NULL,'2026-08-31 06:16:08'),(58,'applicant',7,2,'hr_head','hired_contract_offered','final_passed','contract_offered',NULL,'contract #2','2026-08-31 06:35:43'),(59,'applicant',7,14,'trainee','hired','contract_offered','hired',NULL,NULL,'2026-08-31 06:46:46'),(60,'applicant',7,14,'trainee','hired','contract_offered','hired',NULL,NULL,'2026-08-31 06:48:53'),(61,'applicant',8,1,'owner','trainee_contract_assigned','initial_passed','screening',NULL,'Trainer #4 assigned','2026-08-31 07:21:52'),(62,'applicant',8,1,'owner','hired_contract_offered','final_passed','contract_offered',NULL,'contract #4','2026-08-31 07:22:33'),(63,'job_posting',4,3,'hr_staff','created',NULL,'draft',NULL,NULL,'2026-09-01 15:41:29'),(64,'job_posting',4,3,'hr_staff','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-09-01 15:41:29'),(65,'job_posting',4,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-09-01 15:42:36'),(66,'applicant',9,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 4)','2026-09-02 07:11:58'),(67,'applicant',9,3,'hr_staff','initial_interview_scheduled','pending','initial_scheduled',NULL,NULL,'2026-09-02 07:13:20'),(68,'applicant',10,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 2)','2026-09-03 02:59:52'),(69,'applicant',11,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 4)','2026-09-03 03:24:44'),(70,'applicant',12,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 2)','2026-09-03 13:58:45'),(71,'applicant',13,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 2)','2026-09-03 14:05:03'),(72,'applicant',14,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 2)','2026-09-03 14:10:36'),(73,'applicant',15,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 4)','2026-09-03 14:17:41'),(74,'applicant',16,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 2)','2026-09-03 14:23:30'),(75,'applicant',17,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 4)','2026-09-03 14:36:50'),(76,'applicant',18,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 4)','2026-09-03 14:47:33'),(77,'applicant',18,2,'hr_head','reject_initial','pending','initial_failed',NULL,NULL,'2026-09-03 15:20:15'),(78,'applicant',17,2,'hr_head','reject_initial','pending','initial_failed',NULL,NULL,'2026-09-03 15:20:23'),(79,'applicant',15,2,'hr_head','reject_initial','pending','initial_failed',NULL,NULL,'2026-09-03 15:20:32'),(80,'applicant',19,NULL,NULL,'application_received',NULL,'pending',NULL,'source: public application form (job_posting_id 4)','2026-09-03 15:23:11');
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `register_allocations`
--

LOCK TABLES `register_allocations` WRITE;
/*!40000 ALTER TABLE `register_allocations` DISABLE KEYS */;
INSERT INTO `register_allocations` VALUES (1,1,9,5,3000.00,'cashed_out',2.99,0.00,3002.99,'2026-08-31 13:33:05','2026-08-31 13:35:09','','2026-08-31 13:33:05','2026-08-31 13:35:09'),(2,1,9,5,3000.00,'cashed_out',0.00,0.00,3000.00,'2026-08-31 13:38:19','2026-08-31 15:29:01','gggg','2026-08-31 13:38:19','2026-08-31 15:29:01'),(3,1,9,5,3000.00,'cashed_out',0.00,0.00,3000.00,'2026-08-31 23:45:19','2026-08-31 23:45:54','','2026-08-31 23:45:19','2026-08-31 23:45:54'),(4,1,9,5,3000.00,'cashed_out',14.95,0.00,3014.95,'2026-09-02 01:36:47','2026-09-02 01:45:53','','2026-09-02 01:36:47','2026-09-02 01:45:53'),(5,1,9,5,3000.00,'cashed_out',12.99,0.00,3012.99,'2026-09-02 06:43:08','2026-09-02 06:48:27','','2026-09-02 06:43:08','2026-09-02 06:48:27'),(6,1,9,5,3000.00,'cashed_out',0.00,0.00,3000.00,'2026-09-02 08:48:19','2026-09-02 08:52:04','','2026-09-02 08:48:19','2026-09-02 08:52:04'),(7,2,NULL,5,2000.00,'active',NULL,NULL,NULL,'2026-09-02 08:58:20',NULL,'','2026-09-02 08:58:20','2026-09-02 08:58:20'),(8,1,NULL,5,3000.00,'active',NULL,NULL,NULL,'2026-09-03 03:46:13',NULL,'','2026-09-03 03:46:13','2026-09-03 03:46:13');
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
INSERT INTO `registers` VALUES (1,5,'Main Register','POS-219','$2y$10$6l3AZqg0g51.F4aIEea5.OR6U/D29JjYlkr.eM1KbSZgRaafxkp62',1,'2026-09-02 06:44:49','open','2026-08-31 13:32:47','2026-09-03 03:46:13'),(2,5,'Register 2','POS-364','$2y$10$5IbBevN7PitkZg3VTXSJi.RbxGfrGH1LkJvEgst2LxOfMNWyJliS2',1,'2026-09-02 08:57:34','open','2026-09-02 08:57:34','2026-09-02 08:58:20');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rejection_reasons`
--

LOCK TABLES `rejection_reasons` WRITE;
/*!40000 ALTER TABLE `rejection_reasons` DISABLE KEYS */;
INSERT INTO `rejection_reasons` VALUES (1,18,2,'initial',NULL,'2026-09-03 15:20:15'),(2,17,2,'initial',NULL,'2026-09-03 15:20:23'),(3,15,2,'initial',NULL,'2026-09-03 15:20:32');
/*!40000 ALTER TABLE `rejection_reasons` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revenue_split_rules`
--

LOCK TABLES `revenue_split_rules` WRITE;
/*!40000 ALTER TABLE `revenue_split_rules` DISABLE KEYS */;
INSERT INTO `revenue_split_rules` VALUES (1,'store',5.00,0,6,'2026-08-31 13:56:47','2026-08-31 14:09:17'),(2,'hr',60.00,0,6,'2026-08-31 13:56:47','2026-08-31 14:09:00'),(3,'general',0.00,1,6,'2026-08-31 13:56:47','2026-08-31 14:09:00'),(10,'finance',10.00,0,NULL,'2026-08-31 21:47:12','2026-08-31 21:47:12');
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
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revenue_split_shares`
--

LOCK TABLES `revenue_split_shares` WRITE;
/*!40000 ALTER TABLE `revenue_split_shares` DISABLE KEYS */;
INSERT INTO `revenue_split_shares` VALUES (3,2,'hr',60.00,9.59),(4,2,'store',5.00,0.80),(5,2,'general',NULL,5.59),(24,3,'hr',60.00,0.00),(25,3,'store',5.00,0.00),(26,3,'general',NULL,0.00),(30,6,'hr',60.00,0.00),(31,6,'store',5.00,0.00),(32,6,'general',NULL,0.00),(42,4,'hr',60.00,9.59),(43,4,'store',5.00,0.80),(44,4,'general',NULL,5.59),(45,7,'hr',60.00,0.00),(46,7,'store',5.00,0.00),(47,7,'general',NULL,0.00),(48,5,'finance',10.00,0.00),(49,5,'hr',60.00,0.00),(50,5,'store',5.00,0.00),(51,5,'general',NULL,0.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `revenue_splits`
--

LOCK TABLES `revenue_splits` WRITE;
/*!40000 ALTER TABLE `revenue_splits` DISABLE KEYS */;
INSERT INTO `revenue_splits` VALUES (2,'2026-08-16','2026-08-31','August 16-31, 2026','2026-08',15.98,'applied',6,'2026-08-31 14:06:18',6,'2026-08-31 14:07:05'),(3,'2026-08-01','2026-08-15','August 1-15, 2026','2026-08',0.00,'applied',6,'2026-08-31 21:26:01',6,'2026-08-31 21:26:12'),(4,'2026-08-16','2026-08-31','August 16-31, 2026','2026-08',15.98,'draft',6,'2026-08-31 21:34:57',NULL,NULL),(5,'2026-09-01','2026-09-14','September 1-14, 2026','2026-09-H1',0.00,'applied',6,'2026-08-31 21:57:01',6,'2026-08-31 21:57:31'),(6,'2026-09-15','2026-09-30','September 15-30, 2026','2026-09',0.00,'draft',6,'2026-08-31 21:26:25',NULL,NULL),(7,'2026-08-01','2026-08-15','August 1-15, 2026','2026-08',0.00,'draft',6,'2026-08-31 21:34:59',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `schedules`
--

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
INSERT INTO `schedules` VALUES (50,15,'monday','10:00:00','15:00:00',0,'2026-08-31 07:21:52','2026-08-31 07:21:52'),(51,15,'tuesday','10:00:00','15:00:00',0,'2026-08-31 07:21:52','2026-08-31 07:21:52'),(52,15,'wednesday','10:00:00','15:00:00',0,'2026-08-31 07:21:52','2026-08-31 07:21:52'),(53,15,'thursday','10:00:00','15:00:00',0,'2026-08-31 07:21:52','2026-08-31 07:21:52'),(54,15,'friday','10:00:00','15:00:00',0,'2026-08-31 07:21:52','2026-08-31 07:21:52'),(55,15,'saturday','00:00:00','00:00:00',1,'2026-08-31 07:21:52','2026-08-31 07:21:52'),(56,15,'sunday','00:00:00','00:00:00',1,'2026-08-31 07:21:52','2026-08-31 07:21:52'),(79,14,'monday','08:00:00','17:00:00',0,'2026-09-02 05:08:21','2026-09-02 05:08:21'),(80,14,'tuesday','08:00:00','17:00:00',0,'2026-09-02 05:08:21','2026-09-02 05:08:21'),(81,14,'wednesday','00:00:00','00:00:00',1,'2026-09-02 05:08:21','2026-09-02 05:08:21'),(82,14,'thursday','08:00:00','17:00:00',0,'2026-09-02 05:08:21','2026-09-02 05:08:21'),(83,14,'friday','08:00:00','17:00:00',0,'2026-09-02 05:08:21','2026-09-02 05:08:21'),(84,14,'saturday','08:00:00','17:00:00',0,'2026-09-02 05:08:21','2026-09-02 05:08:21'),(85,14,'sunday','00:00:00','00:00:00',1,'2026-09-02 05:08:21','2026-09-02 05:08:21');
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_requisition_items`
--

DROP TABLE IF EXISTS `store_requisition_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_requisition_items` (
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
  CONSTRAINT `store_requisition_items_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `store_requisitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `store_requisition_items_ibfk_2` FOREIGN KEY (`store_product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `store_requisition_items_ibfk_3` FOREIGN KEY (`supplier_product_id`) REFERENCES `supplier_products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_requisition_items`
--

LOCK TABLES `store_requisition_items` WRITE;
/*!40000 ALTER TABLE `store_requisition_items` DISABLE KEYS */;
INSERT INTO `store_requisition_items` VALUES (1,1,1,5,15,10.39,155.85,0,NULL,'2026-08-23 05:20:15'),(2,2,2,6,12,2.39,28.68,12,NULL,'2026-08-23 15:50:15'),(3,4,2,6,15,2.39,35.85,15,NULL,'2026-08-23 19:08:42'),(4,5,2,6,20,2.39,47.80,20,NULL,'2026-08-26 03:11:36'),(5,6,1,5,2,10.39,20.78,0,NULL,'2026-08-31 14:43:18'),(6,6,2,6,3,2.39,7.17,0,NULL,'2026-08-31 14:43:18'),(7,7,2,6,1,2.39,2.39,0,NULL,'2026-08-31 22:03:07'),(8,8,1,5,40,10.39,415.60,0,NULL,'2026-09-01 13:56:55'),(9,8,2,6,10,2.39,23.90,0,NULL,'2026-09-01 13:56:55'),(10,9,1,5,10,10.39,103.90,10,NULL,'2026-09-02 06:58:07'),(11,9,2,6,5,2.39,11.95,5,NULL,'2026-09-02 06:58:07');
/*!40000 ALTER TABLE `store_requisition_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_requisitions`
--

DROP TABLE IF EXISTS `store_requisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_requisitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requisition_number` varchar(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `department` varchar(20) NOT NULL DEFAULT 'store',
  `status` enum('draft','pending_supplier','sent_to_supplier','supplier_processed','awaiting_finance_staff','awaiting_finance','finance_approved','finance_rejected','paid','shipped','completed','partial_received') DEFAULT 'draft',
  `order_date` date NOT NULL,
  `budget_month_year` varchar(10) NOT NULL,
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
  CONSTRAINT `store_requisitions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `store_requisitions_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_requisitions`
--

LOCK TABLES `store_requisitions` WRITE;
/*!40000 ALTER TABLE `store_requisitions` DISABLE KEYS */;
INSERT INTO `store_requisitions` VALUES (1,'REQ-2026-0001',5,1,'store','pending_supplier','2026-08-23','','2026-08-25',155.85,0.00,155.85,'','2026-08-23 05:20:15','2026-08-23 05:20:15'),(2,'REQ-2026-0002',5,1,'store','completed','2026-08-23','2026-08','0000-00-00',28.68,0.00,28.68,'','2026-08-23 15:50:15','2026-08-23 19:06:30'),(4,'REQ-2026-0003',5,1,'store','completed','2026-08-24','2026-08','2026-08-25',35.85,0.00,35.85,'','2026-08-23 19:08:41','2026-08-26 03:15:28'),(5,'REQ-2026-0004',5,1,'store','completed','2026-08-26','2026-08','2026-09-23',47.80,0.00,47.80,'[SHIPPED]','2026-08-26 03:11:35','2026-08-26 03:18:31'),(6,'REQ-2026-0005',5,1,'store','paid','2026-08-31','2026-08','2026-09-07',27.95,0.00,27.95,'Budget consumption test - resupply','2026-08-31 14:43:18','2026-08-31 14:54:29'),(7,'REQ-2026-0006',5,1,'store','pending_supplier','2026-09-01','2026-09-H1','2026-09-10',2.39,0.00,2.39,'','2026-08-31 22:03:07','2026-08-31 22:03:07'),(8,'REQ-2026-0007',5,1,'store','paid','2026-09-01','2026-09-H1','2026-09-15',439.50,0.00,439.50,'Please po master','2026-09-01 13:56:55','2026-09-01 14:11:25'),(9,'REQ-2026-0008',5,1,'store','completed','2026-09-02','2026-09-H1','2026-09-07',115.85,0.00,115.85,'test\n[SHIPPED]','2026-09-02 06:58:07','2026-09-02 07:07:35');
/*!40000 ALTER TABLE `store_requisitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_invoices`
--

DROP TABLE IF EXISTS `supplier_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_invoices` (
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
  CONSTRAINT `supplier_invoices_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `store_requisitions` (`id`),
  CONSTRAINT `supplier_invoices_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `supplier_invoices_ibfk_3` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_invoices`
--

LOCK TABLES `supplier_invoices` WRITE;
/*!40000 ALTER TABLE `supplier_invoices` DISABLE KEYS */;
INSERT INTO `supplier_invoices` VALUES (1,'INV-2026-0001',2,1,'2026-08-23',28.68,0.00,28.68,'2026-08-26','pending',0,0,'test',NULL,NULL,'2026-08-23 17:03:58','2026-08-23 19:04:19'),(2,'INV-2026-0002',4,1,'2026-08-23',35.85,0.00,35.85,'2026-09-01','paid',0,0,'teast',6,'2026-08-25 01:22:47','2026-08-23 19:09:51','2026-08-24 17:22:47'),(3,'INV-2026-0003',5,1,'2026-08-26',47.80,0.00,47.80,'2026-10-08','paid',0,0,'',6,'2026-08-26 11:14:21','2026-08-26 03:12:43','2026-08-26 03:14:21'),(4,'INV-2026-0004',6,1,'2026-08-31',27.95,0.00,27.95,'2026-09-07','paid',0,0,'',6,'2026-08-31 22:54:29','2026-08-31 14:45:33','2026-08-31 14:54:29'),(5,'INV-2026-0005',8,1,'2026-09-01',439.50,0.00,439.50,'2026-09-15','paid',0,0,'Okay po',6,'2026-09-01 22:11:25','2026-09-01 14:02:17','2026-09-01 14:11:25'),(6,'INV-2026-0006',9,1,'2026-09-02',115.85,0.00,115.85,'2026-09-02','paid',0,0,'ge',6,'2026-09-02 15:05:39','2026-09-02 06:59:55','2026-09-02 07:05:39');
/*!40000 ALTER TABLE `supplier_invoices` ENABLE KEYS */;
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
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `supplier_products_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_products`
--

LOCK TABLES `supplier_products` WRITE;
/*!40000 ALTER TABLE `supplier_products` DISABLE KEYS */;
INSERT INTO `supplier_products` VALUES (5,1,'Sample Book','A sample book for testing',10.39,1,'2026-08-21 12:54:24','2026-08-21 12:54:24'),(6,1,'Sample Pen','A sample pen for testing',2.39,1,'2026-08-21 12:54:24','2026-08-21 12:54:24');
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Sample Supplier Inc.','John Supplier','supplier@shelfsense.com','09123456789',NULL,NULL,NULL,1,'2026-08-20 17:09:05','2026-08-21 12:54:24'),(2,'Sample Supplier Inc.','Supplier Contact','supplier@shelfsense.com','09123456789','123 Supplier St, City',NULL,NULL,1,'2026-08-21 03:04:10','2026-08-21 03:04:10');
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
  `department` varchar(50) NOT NULL,
  `trainer_id` int(11) NOT NULL,
  `report_content` text NOT NULL,
  `performance_rating` enum('excellent','good','satisfactory','needs_improvement','poor') DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `improvements` text DEFAULT NULL,
  `attendance_notes` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `status` enum('submitted','forwarded','hr_reviewed') NOT NULL DEFAULT 'submitted',
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewer_role` varchar(50) DEFAULT NULL,
  `reviewer_observation` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `forwarded_at` datetime DEFAULT NULL,
  `hr_head_id` int(11) DEFAULT NULL,
  `hr_head_notes` text DEFAULT NULL,
  `hr_head_reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_trainee_week` (`trainee_id`,`week_number`),
  KEY `trainer_id` (`trainer_id`),
  KEY `reviewer_id` (`reviewer_id`),
  KEY `hr_head_id` (`hr_head_id`),
  CONSTRAINT `trainee_reports_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trainee_reports_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `trainee_reports_ibfk_3` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `trainee_reports_ibfk_4` FOREIGN KEY (`hr_head_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  `trainee_salary` decimal(10,2) DEFAULT NULL,
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
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `applicant_id` (`applicant_id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `trainees_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trainees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `trainees_ibfk_3` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainees`
--

LOCK TABLES `trainees` WRITE;
/*!40000 ALTER TABLE `trainees` DISABLE KEYS */;
INSERT INTO `trainees` VALUES (3,8,15,4,'HR Staff','2026-08-31','2026-12-01','10:00:00','15:00:00',NULL,'active',3900.00,4500.00,'2026-08-31 07:21:52',NULL,NULL,NULL,'pending',0,NULL,NULL,NULL,NULL,NULL,'2026-08-31 07:21:52','2026-08-31 07:21:52');
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
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unassigned_at` datetime DEFAULT NULL,
  `reason` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trainee_id` (`trainee_id`),
  KEY `trainer_id` (`trainer_id`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `trainer_assignments_ibfk_1` FOREIGN KEY (`trainee_id`) REFERENCES `trainees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trainer_assignments_ibfk_2` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `trainer_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainer_assignments`
--

LOCK TABLES `trainer_assignments` WRITE;
/*!40000 ALTER TABLE `trainer_assignments` DISABLE KEYS */;
INSERT INTO `trainer_assignments` VALUES (3,3,4,1,'2026-08-31 07:21:52',NULL,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_dashboard_layouts`
--

LOCK TABLES `user_dashboard_layouts` WRITE;
/*!40000 ALTER TABLE `user_dashboard_layouts` DISABLE KEYS */;
INSERT INTO `user_dashboard_layouts` VALUES (1,2,'hr_dashboard','{\"stats\":[\"stat_total\",\"stat_scheduled\",\"stat_pending\",\"stat_hired\"],\"content\":[\"table_interviews\",\"chart_monthly\",\"chart_pipeline\",\"table_applicants\",\"table_trainees\"]}','2026-09-03 14:03:24'),(4,3,'hr_dashboard','{\"stats\":[\"stat_total\",\"stat_scheduled\",\"stat_pending\",\"stat_hired\"],\"content\":[\"chart_monthly\",\"chart_pipeline\",\"table_applicants\",\"table_interviews\",\"table_trainees\"]}','2026-08-31 08:40:58'),(7,1,'hr_dashboard','{\"stats\":[\"stat_hired\",\"stat_total\",\"stat_pending\",\"stat_scheduled\"],\"content\":[\"table_interviews\",\"table_trainees\",\"table_applicants\",\"chart_monthly\",\"chart_pipeline\"]}','2026-08-31 14:54:58'),(22,5,'store_manager_dashboard','{\"stats\":[\"stat_total\",\"stat_finance\",\"stat_pending\",\"stat_lowstock\"],\"content\":[\"table_finance\",\"table_history\",\"chart_trend\",\"table_lowstock\",\"chart_status\",\"table_mine\",\"list_recent\",\"panel_insights\"]}','2026-09-02 16:34:47');
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `show_dashboard_tour` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `employee_number` (`employee_number`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SA-001','Super','Admin',NULL,'admin@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','owner',5,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-09-02 08:59:06',1),(2,'HH-001','Maria','Santos',NULL,'hr.head@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','hr_head',4,1,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-09-03 14:02:27',0),(3,'HS-001','Juan','Dela Cruz',NULL,'hr.staff@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','hr_staff',1,1,0,1,1,NULL,'uploads/avatars/user_3_pending_1788312423.png','pending',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-09-02 01:27:03',1),(4,'HS-002','Ana','Reyes',NULL,'hr.staff2@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','hr_staff',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-31 07:21:52',1),(5,'SM-001','Store','Manager',NULL,'store.manager@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','store_manager',4,0,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-09-02 08:59:06',1),(6,'FH-001','Finance','Head',NULL,'finance.head@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','finance_head',4,0,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-31 21:59:43',1),(7,'FS-001','Finance','Staff',NULL,'finance.staff@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','finance_staff',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-31 22:01:07',1),(8,'FS-002','Sarah','Williams',NULL,'finance.staff2@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','finance_staff',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-20 17:09:05',1),(9,'CA-001','Cashier','Test',NULL,'employee@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','employee',1,1,0,1,1,NULL,'uploads/avatars/user_9_pending_1788168266.jpg','pending',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-09-02 06:51:01',1),(10,'CA-002','John','Doe',NULL,'rumbines.allen@ncst.edu.ph','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','employee',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-26 03:23:27',1),(11,'TR-001','Trainee','User',NULL,'trainee@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','trainee',0,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-20 17:09:05',1),(12,'SUP-001','Sample','Supplier',NULL,'supplier@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','supplier',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-21 03:04:09','2026-08-31 14:55:12',1),(14,'HS-331','Stephen','Frias','F','stephenfrias04@gmail.com','$2y$10$5T5O2tMN1/Drfh187J.TduF2nek8TjU2K0/3aPVGVMxO4c8tOVBgO','hr_staff',1,1,0,1,1,NULL,NULL,'none',NULL,'2026-08-31 06:48:53',15.00,15.00,5.00,0.00,'2026-08-31 03:31:51','2026-08-31 06:48:53',1),(15,'TR-643','Stephen','Frias','F','allen@sd.ph','$2y$10$ACKPEiPCihZXZ8qI/0p7kutLXth3dTMJWxiLV8SfeFJc..YdNEjDK','trainee',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-31 07:21:52','2026-08-31 07:21:52',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-04  0:37:31
