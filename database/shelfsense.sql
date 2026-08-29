-- ShelfSense SQL Dump
-- Regenerated from live schema on Aug 26, 2026
-- Sync includes: job_postings (location, employment_type, responsibilities,
-- slots columns), applicants.job_posting_id (links an application to its
-- job posting for slot-consumption tracking), trainer_assignments,
-- trainee_reports, and recruitment_logs tables.



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
  CONSTRAINT `applicants_ibfk_job_posting` FOREIGN KEY (`job_posting_id`) REFERENCES `job_postings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `applicants` WRITE;
/*!40000 ALTER TABLE `applicants` DISABLE KEYS */;
/*!40000 ALTER TABLE `applicants` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `attendance_monthly_summaries` WRITE;
/*!40000 ALTER TABLE `attendance_monthly_summaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_monthly_summaries` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `attendance_weekly_summaries` WRITE;
/*!40000 ALTER TABLE `attendance_weekly_summaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_weekly_summaries` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `budget_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budget_adjustments` (
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
  CONSTRAINT `fk_budget_adjustments_budget` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`),
  CONSTRAINT `fk_budget_adjustments_user` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `budget_adjustments` WRITE;
/*!40000 ALTER TABLE `budget_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `budget_adjustments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `budgets` (
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

LOCK TABLES `budgets` WRITE;
/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
INSERT INTO `budgets` VALUES (1,'store','2026-08',10000.00,112.33,NULL,'2026-08-21 16:47:43','2026-08-26 03:14:21'),(2,'hr','2026-08',50000.00,0.00,NULL,'2026-08-21 16:47:43','2026-08-21 16:47:43'),(3,'finance','2026-08',30000.00,0.00,NULL,'2026-08-21 16:47:43','2026-08-24 16:56:15'),(4,'general','2026-08',20000.00,0.00,NULL,'2026-08-21 16:47:43','2026-08-21 16:47:43');
/*!40000 ALTER TABLE `budgets` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `cash_reconciliation` WRITE;
/*!40000 ALTER TABLE `cash_reconciliation` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_reconciliation` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Books',NULL,1,'2026-08-20 17:09:05'),(2,'School Supplies',NULL,1,'2026-08-20 17:09:05'),(3,'Merchandise',NULL,1,'2026-08-20 17:09:05'),(4,'Beverages',NULL,1,'2026-08-20 17:09:05'),(5,'Snacks',NULL,1,'2026-08-20 17:09:05');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `applicant_id` (`applicant_id`),
  KEY `offered_by` (`offered_by`),
  CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `contracts_ibfk_3` FOREIGN KEY (`offered_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
INSERT INTO `email_logs` VALUES (1,'rumbines.allen@ncst.edu.ph','Password Reset OTP - ShelfSense',NULL,'sent','2026-08-26 03:23:55');
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `goods_receipt_items` WRITE;
/*!40000 ALTER TABLE `goods_receipt_items` DISABLE KEYS */;
INSERT INTO `goods_receipt_items` VALUES (1,1,2,12,NULL),(2,2,3,15,NULL),(3,3,4,20,NULL);
/*!40000 ALTER TABLE `goods_receipt_items` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `goods_receipts` WRITE;
/*!40000 ALTER TABLE `goods_receipts` DISABLE KEYS */;
INSERT INTO `goods_receipts` VALUES (1,2,5,'2026-08-24','completed',NULL,'2026-08-23 19:06:30'),(2,4,5,'2026-08-26','completed',NULL,'2026-08-26 03:15:28'),(3,5,5,'2026-08-26','completed',NULL,'2026-08-26 03:18:31');
/*!40000 ALTER TABLE `goods_receipts` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `interviews` WRITE;
/*!40000 ALTER TABLE `interviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `interviews` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `job_postings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_postings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `job_postings` WRITE;
/*!40000 ALTER TABLE `job_postings` DISABLE KEYS */;
INSERT INTO `job_postings` VALUES (1,'Cashier','Cashier','Main Store, Dasmarinas','Cashier','Full-Time','Responsible for processing daily sales transactions, maintaining accurate cash handling, providing excellent customer service, and ensuring the checkout area is clean and organized.','1+ year retail or cashier experience\nStrong communication and interpersonal skills\nAvailable for shifting schedules (opening/closing)\nBasic math and computer skills\nHigh school diploma or equivalent','Process daily sales transactions accurately\nProvide excellent customer service\nMaintain a clean and organized checkout area\nHandle cash, card, and digital payments\nAssist with inventory counts as needed',9900.00,10500.00,3,'2026-09-01','approved','2026-08-26 13:34:43',3,2,'2026-08-26 13:35:19',NULL,NULL,NULL,NULL,NULL,'2026-08-26 05:34:37','2026-08-26 06:20:01'),(2,'HR Staff','HR Staff','Headquarters','hr_staff','Full-Time','Support recruitment, employee records, and day-to-day HR operations.','HR-related degree or experience\nAttention to detail\nGood communication skills',NULL,NULL,NULL,2,'2026-10-26','approved','2026-08-26 14:17:06',3,2,'2026-08-26 14:17:10',NULL,NULL,NULL,NULL,NULL,'2026-08-26 06:17:06','2026-08-26 06:20:01');
/*!40000 ALTER TABLE `job_postings` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `leaves` WRITE;
/*!40000 ALTER TABLE `leaves` DISABLE KEYS */;
/*!40000 ALTER TABLE `leaves` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,5,'invoice_received','Supplier has sent invoice for requisition #REQ-2026-0002. Please review and forward to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-23 17:03:58'),(2,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0002 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 17:05:18'),(3,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0002 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 17:05:18'),(4,5,'invoice_forwarded_success','Invoice for requisition #REQ-2026-0002 has been forwarded to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-23 17:05:18'),(5,6,'payment_request_pending','Payment request for requisition #REQ-2026-0002 is pending approval. Amount: ₱28.68','?page=finance_head_payment_requests',0,'2026-08-23 17:33:29'),(6,12,'payment_completed','Payment for requisition #REQ-2026-0002 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-23 17:34:35'),(7,7,'payment_request_approved','Payment request for requisition #REQ-2026-0002 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-23 17:34:35'),(8,5,'payment_approved','Payment for requisition #REQ-2026-0002 has been approved. The supplier will ship the goods.','?page=store_manager_requisitions',0,'2026-08-23 17:34:35'),(9,5,'invoice_received','Supplier has sent invoice for requisition #REQ-2026-0003. Please review and forward to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-23 19:09:52'),(10,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0003 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 19:11:10'),(11,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0003 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-23 19:11:11'),(12,5,'invoice_forwarded_success','Invoice for requisition #REQ-2026-0003 has been forwarded to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-23 19:11:11'),(13,6,'payment_request_pending','Payment request for requisition #REQ-2026-0003 is pending approval. Amount: ₱35.85','?page=finance_head_payment_requests',0,'2026-08-23 19:11:55'),(14,12,'payment_completed','Payment for requisition #REQ-2026-0003 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-24 17:22:48'),(15,7,'payment_request_approved','Payment request for requisition #REQ-2026-0003 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-24 17:22:48'),(16,5,'payment_approved','Payment for requisition #REQ-2026-0003 has been approved. The supplier will ship the goods.','?page=store_manager_requisitions',0,'2026-08-24 17:22:48'),(17,5,'invoice_received','Supplier has sent invoice for requisition #REQ-2026-0004. Please review and forward to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-26 03:12:43'),(18,7,'invoice_forwarded','Invoice for requisition #REQ-2026-0004 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-26 03:13:09'),(19,8,'invoice_forwarded','Invoice for requisition #REQ-2026-0004 has been forwarded. Supplier: Sample Supplier Inc.','?page=finance_staff_requisitions',0,'2026-08-26 03:13:09'),(20,5,'invoice_forwarded_success','Invoice for requisition #REQ-2026-0004 has been forwarded to Finance Staff.','?page=store_manager_requisitions',0,'2026-08-26 03:13:09'),(21,6,'payment_request_pending','Payment request for requisition #REQ-2026-0004 is pending approval. Amount: ₱47.80','?page=finance_head_payment_requests',0,'2026-08-26 03:13:43'),(22,12,'payment_completed','Payment for requisition #REQ-2026-0004 has been completed. Please ship the goods.','?page=supplier_requisitions',0,'2026-08-26 03:14:21'),(23,7,'payment_request_approved','Payment request for requisition #REQ-2026-0004 has been approved and recorded.','?page=finance_staff_payment_requests',0,'2026-08-26 03:14:21'),(24,5,'payment_approved','Payment for requisition #REQ-2026-0004 has been approved. The supplier will ship the goods.','?page=store_manager_requisitions',0,'2026-08-26 03:14:21'),(25,5,'goods_shipped','Supplier has shipped goods for requisition #REQ-2026-0004. Please receive the goods.','?page=store_manager_requisitions',0,'2026-08-26 03:14:58'),(35,2,'job_posting_submitted','Job posting \"Cashier\" is awaiting your review.','?page=hr_job_postings',0,'2026-08-26 05:34:43'),(36,3,'job_posting_approved','Your job posting \"Cashier\" was approved and is now public.','?page=hr_job_postings',0,'2026-08-26 05:35:19'),(46,2,'job_posting_submitted','A new job posting \"HR Staff\" is awaiting your review.','?page=hr_job_postings',0,'2026-08-26 06:17:06'),(47,3,'job_posting_approved','Your job posting \"HR Staff\" was approved and is now public.','?page=hr_job_postings',0,'2026-08-26 06:17:10');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `cashier_id` (`cashier_id`),
  KEY `voided_by` (`voided_by`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`voided_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (5,4,'858060','2026-08-21 22:59:51',0,'2026-08-21 14:44:51'),(6,10,'907358','2026-08-26 11:38:50',0,'2026-08-26 03:23:50');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payment_requests` WRITE;
/*!40000 ALTER TABLE `payment_requests` DISABLE KEYS */;
INSERT INTO `payment_requests` VALUES (6,4,2,7,'2026-08-23 19:11:55','approved',6,'2026-08-25 01:22:47',NULL,1,0,NULL,NULL,'','2026-08-23 19:11:55','2026-08-24 17:22:47',NULL),(7,5,3,7,'2026-08-26 03:13:43','approved',6,'2026-08-26 11:14:21',NULL,1,0,NULL,NULL,'','2026-08-26 03:13:43','2026-08-26 03:14:21',NULL);
/*!40000 ALTER TABLE `payment_requests` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (4,2,35.85,'bank_transfer','AUTO-20260824192247',6,'2026-08-25 01:22:47','Auto-recorded after Finance Head approval'),(5,3,47.80,'bank_transfer','AUTO-20260826051421',6,'2026-08-26 11:14:21','Auto-recorded after Finance Head approval');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `payroll_approval_logs` WRITE;
/*!40000 ALTER TABLE `payroll_approval_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_approval_logs` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `payroll_cycles` WRITE;
/*!40000 ALTER TABLE `payroll_cycles` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_cycles` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `payroll_entries` WRITE;
/*!40000 ALTER TABLE `payroll_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_entries` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `pos_override_requests` WRITE;
/*!40000 ALTER TABLE `pos_override_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `pos_override_requests` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'978-0-123-45678-9','Sample Book','A sample book for testing',1,12.99,8.00,10,5,NULL,1,'2026-08-20 17:09:05','2026-08-23 02:49:37'),(2,'BS-001','Sample Pen','A sample pen for testing',2,2.99,1.20,67,5,NULL,1,'2026-08-20 17:09:05','2026-08-26 03:18:31');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `recruitment_logs` WRITE;
/*!40000 ALTER TABLE `recruitment_logs` DISABLE KEYS */;
INSERT INTO `recruitment_logs` VALUES (12,'job_posting',1,3,'hr_staff','created',NULL,'draft',NULL,NULL,'2026-08-26 05:34:37'),(13,'job_posting',1,3,'hr_staff','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-08-26 05:34:43'),(14,'job_posting',1,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-08-26 05:35:19'),(15,'job_posting',1,2,'hr_head','hr_head_overwrite','approved','approved',NULL,NULL,'2026-08-26 06:14:21'),(19,'job_posting',2,3,'hr_staff','created',NULL,'draft',NULL,NULL,'2026-08-26 06:17:06'),(20,'job_posting',2,3,'hr_staff','submitted_for_approval','draft','pending_approval',NULL,NULL,'2026-08-26 06:17:06'),(21,'job_posting',2,2,'hr_head','approved','pending_approval','approved',NULL,NULL,'2026-08-26 06:17:10');
/*!40000 ALTER TABLE `recruitment_logs` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `rejection_reasons` WRITE;
/*!40000 ALTER TABLE `rejection_reasons` DISABLE KEYS */;
/*!40000 ALTER TABLE `rejection_reasons` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `schedules` WRITE;
/*!40000 ALTER TABLE `schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `schedules` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `store_requisition_items` WRITE;
/*!40000 ALTER TABLE `store_requisition_items` DISABLE KEYS */;
INSERT INTO `store_requisition_items` VALUES (1,1,1,5,15,10.39,155.85,0,NULL,'2026-08-23 05:20:15'),(2,2,2,6,12,2.39,28.68,12,NULL,'2026-08-23 15:50:15'),(3,4,2,6,15,2.39,35.85,15,NULL,'2026-08-23 19:08:42'),(4,5,2,6,20,2.39,47.80,20,NULL,'2026-08-26 03:11:36');
/*!40000 ALTER TABLE `store_requisition_items` ENABLE KEYS */;
UNLOCK TABLES;
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
  CONSTRAINT `store_requisitions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `store_requisitions_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `store_requisitions` WRITE;
/*!40000 ALTER TABLE `store_requisitions` DISABLE KEYS */;
INSERT INTO `store_requisitions` VALUES (1,'REQ-2026-0001',5,1,'store','pending_supplier','2026-08-23','','2026-08-25',155.85,0.00,155.85,'','2026-08-23 05:20:15','2026-08-23 05:20:15'),(2,'REQ-2026-0002',5,1,'store','completed','2026-08-23','2026-08','0000-00-00',28.68,0.00,28.68,'','2026-08-23 15:50:15','2026-08-23 19:06:30'),(4,'REQ-2026-0003',5,1,'store','completed','2026-08-24','2026-08','2026-08-25',35.85,0.00,35.85,'','2026-08-23 19:08:41','2026-08-26 03:15:28'),(5,'REQ-2026-0004',5,1,'store','completed','2026-08-26','2026-08','2026-09-23',47.80,0.00,47.80,'[SHIPPED]','2026-08-26 03:11:35','2026-08-26 03:18:31');
/*!40000 ALTER TABLE `store_requisitions` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `supplier_invoices` WRITE;
/*!40000 ALTER TABLE `supplier_invoices` DISABLE KEYS */;
INSERT INTO `supplier_invoices` VALUES (1,'INV-2026-0001',2,1,'2026-08-23',28.68,0.00,28.68,'2026-08-26','pending',0,0,'test',NULL,NULL,'2026-08-23 17:03:58','2026-08-23 19:04:19'),(2,'INV-2026-0002',4,1,'2026-08-23',35.85,0.00,35.85,'2026-09-01','paid',0,0,'teast',6,'2026-08-25 01:22:47','2026-08-23 19:09:51','2026-08-24 17:22:47'),(3,'INV-2026-0003',5,1,'2026-08-26',47.80,0.00,47.80,'2026-10-08','paid',0,0,'',6,'2026-08-26 11:14:21','2026-08-26 03:12:43','2026-08-26 03:14:21');
/*!40000 ALTER TABLE `supplier_invoices` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `supplier_products` WRITE;
/*!40000 ALTER TABLE `supplier_products` DISABLE KEYS */;
INSERT INTO `supplier_products` VALUES (5,1,'Sample Book','A sample book for testing',10.39,1,'2026-08-21 12:54:24','2026-08-21 12:54:24'),(6,1,'Sample Pen','A sample pen for testing',2.39,1,'2026-08-21 12:54:24','2026-08-21 12:54:24');
/*!40000 ALTER TABLE `supplier_products` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Sample Supplier Inc.','John Supplier','supplier@shelfsense.com','09123456789',NULL,NULL,NULL,1,'2026-08-20 17:09:05','2026-08-21 12:54:24'),(2,'Sample Supplier Inc.','Supplier Contact','supplier@shelfsense.com','09123456789','123 Supplier St, City',NULL,NULL,1,'2026-08-21 03:04:10','2026-08-21 03:04:10');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `trainee_reports` WRITE;
/*!40000 ALTER TABLE `trainee_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainee_reports` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `trainee_weekly_reports` WRITE;
/*!40000 ALTER TABLE `trainee_weekly_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainee_weekly_reports` ENABLE KEYS */;
UNLOCK TABLES;
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `applicant_id` (`applicant_id`),
  KEY `trainer_id` (`trainer_id`),
  CONSTRAINT `trainees_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trainees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `trainees_ibfk_3` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `trainees` WRITE;
/*!40000 ALTER TABLE `trainees` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainees` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `trainer_assignments` WRITE;
/*!40000 ALTER TABLE `trainer_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainer_assignments` ENABLE KEYS */;
UNLOCK TABLES;
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
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `employee_number` (`employee_number`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'SA-001','Super','Admin',NULL,'admin@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','owner',5,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-20 17:09:05'),(2,'HH-001','Maria','Santos',NULL,'hr.head@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','hr_head',4,1,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-26 06:20:26'),(3,'HS-001','Juan','Dela Cruz',NULL,'hr.staff@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','hr_staff',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-26 06:20:26'),(4,'HS-002','Ana','Reyes',NULL,'hr.staff2@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','hr_staff',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-26 05:18:46'),(5,'SM-001','Store','Manager',NULL,'store.manager@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','store_manager',4,0,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-26 05:18:46'),(6,'FH-001','Finance','Head',NULL,'finance.head@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','finance_head',4,0,1,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-20 17:09:05'),(7,'FS-001','Finance','Staff',NULL,'finance.staff@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','finance_staff',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-20 17:09:05'),(8,'FS-002','Sarah','Williams',NULL,'finance.staff2@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','finance_staff',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-20 17:09:05'),(9,'CA-001','Cashier','Test',NULL,'employee@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','employee',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-26 05:18:46'),(10,'CA-002','John','Doe',NULL,'rumbines.allen@ncst.edu.ph','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','employee',1,1,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-26 03:23:27'),(11,'TR-001','Trainee','User',NULL,'trainee@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','trainee',0,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-20 17:09:05','2026-08-20 17:09:05'),(12,'SUP-001','Sample','Supplier',NULL,'supplier@shelfsense.com','$2y$10$JNrnPP.TfsAws1o1AwAYJ.Gim25c6QgPhyQFcMJOJLHAtUE3NyHTu','supplier',1,0,0,1,1,NULL,NULL,'none',NULL,NULL,15.00,15.00,5.00,0.00,'2026-08-21 03:04:09','2026-08-21 03:07:17');
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

