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
INSERT INTO `budgets` VALUES (1,1,'2026-09-H1',0.00,'2026-09-04 14:28:06','2026-09-04 14:28:06');
/*!40000 ALTER TABLE `budgets` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_transactions`
--

LOCK TABLES `budget_transactions` WRITE;
/*!40000 ALTER TABLE `budget_transactions` DISABLE KEYS */;
INSERT INTO `budget_transactions` VALUES (1,1,'2026-09-H1','adjustment',5000.00,'manual',NULL,6,'Initial test allocation','2026-09-04 14:28:06'),(2,1,'2026-09-H1','reservation',10.39,'requisition',1,6,NULL,'2026-09-04 14:28:12'),(3,1,'2026-09-H1','expense',15.00,'invoice',1,6,'Payment batch PB-2026-0001','2026-09-04 14:31:40'),(4,1,'2026-09-H1','release',15.00,'requisition',1,6,'Reservation closed by payment batch PB-2026-0001','2026-09-04 14:31:40'),(5,1,'2026-09-H1','reservation',11.95,'requisition',2,6,NULL,'2026-09-04 14:35:40'),(6,1,'2026-09-H1','reservation',-4.78,'purchase_order',2,5,'Adjusted for accepted supplier counter-proposal','2026-09-04 14:38:15'),(7,1,'2026-09-H1','reservation',127.80,'requisition',3,6,NULL,'2026-09-04 14:55:35'),(8,1,'2026-09-H1','expense',1023.90,'invoice',2,6,'Payment batch PB-2026-0002','2026-09-04 14:59:41'),(9,1,'2026-09-H1','release',127.80,'requisition',3,6,'Reservation closed by payment batch PB-2026-0002','2026-09-04 14:59:41'),(10,1,'2026-09-H1','reservation',41.56,'requisition',4,6,NULL,'2026-09-04 15:53:39'),(11,1,'2026-09-H1','reservation',20.78,'requisition',5,6,NULL,'2026-09-04 15:59:04'),(12,1,'2026-09-H1','reservation',5195.00,'requisition',6,6,'EEE','2026-09-04 16:04:26'),(13,1,'2026-09-H1','reservation',10.39,'requisition',7,6,'Test justification for double-click fix verification','2026-09-04 16:09:56');
/*!40000 ALTER TABLE `budget_transactions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-05  0:22:50
