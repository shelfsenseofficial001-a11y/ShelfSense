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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisitions`
--

LOCK TABLES `requisitions` WRITE;
/*!40000 ALTER TABLE `requisitions` DISABLE KEYS */;
INSERT INTO `requisitions` VALUES (1,'REQ-2026-0001',5,1,1,'2026-09-H1','converted_to_po','2026-09-04',NULL,10.39,'',NULL,'2026-09-04 14:24:36','2026-09-04 14:28:12'),(2,'REQ-2026-0002',5,1,1,'2026-09-H1','converted_to_po','2026-09-04',NULL,11.95,'Counter-proposal test',NULL,'2026-09-04 14:34:44','2026-09-04 14:35:40'),(3,'REQ-2026-0003',5,1,1,'2026-09-H1','converted_to_po','2026-09-04',NULL,127.80,'3-way match test',NULL,'2026-09-04 14:54:38','2026-09-04 14:55:35'),(4,'REQ-2026-0004',5,1,1,'2026-09-H1','converted_to_po','2026-09-04','2026-10-01',41.56,'',NULL,'2026-09-04 15:50:24','2026-09-04 15:53:39'),(5,'REQ-2026-0005',5,1,1,'2026-09-H1','converted_to_po','2026-09-05',NULL,20.78,'Bug repro test',NULL,'2026-09-04 15:57:42','2026-09-04 15:59:04'),(6,'REQ-2026-0006',5,1,1,'2026-09-H1','converted_to_po','2026-09-05',NULL,5195.00,'Exceed test',NULL,'2026-09-04 16:00:33','2026-09-04 16:04:26'),(7,'REQ-2026-0007',5,1,1,'2026-09-H1','converted_to_po','2026-09-05',NULL,10.39,'Double-click fix test',NULL,'2026-09-04 16:08:28','2026-09-04 16:09:56'),(8,'REQ-2026-0008',5,1,1,'2026-09-H1','converted_to_po','2026-09-05',NULL,10.39,'Invoice-gate test',NULL,'2026-09-04 16:12:35','2026-09-04 16:38:22'),(9,'REQ-2026-0009',5,1,2,'2026-09-H1','converted_to_po','2026-09-04','2026-10-02',1338.75,'',NULL,'2026-09-04 16:21:59','2026-09-04 16:35:26');
/*!40000 ALTER TABLE `requisitions` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requisition_items`
--

LOCK TABLES `requisition_items` WRITE;
/*!40000 ALTER TABLE `requisition_items` DISABLE KEYS */;
INSERT INTO `requisition_items` VALUES (1,1,1,5,1,10.39,10.39,NULL,'2026-09-04 14:24:36'),(2,2,2,6,5,2.39,11.95,NULL,'2026-09-04 14:34:44'),(3,3,1,5,10,10.39,103.90,NULL,'2026-09-04 14:54:38'),(4,3,2,6,10,2.39,23.90,NULL,'2026-09-04 14:54:38'),(5,4,1,5,4,10.39,41.56,NULL,'2026-09-04 15:50:24'),(6,5,1,5,2,10.39,20.78,NULL,'2026-09-04 15:57:42'),(7,6,1,5,500,10.39,5195.00,NULL,'2026-09-04 16:00:33'),(8,7,1,5,1,10.39,10.39,NULL,'2026-09-04 16:08:28'),(9,8,1,5,1,10.39,10.39,NULL,'2026-09-04 16:12:35'),(10,9,5,16,45,21.00,945.00,NULL,'2026-09-04 16:21:59'),(11,9,7,18,35,11.25,393.75,NULL,'2026-09-04 16:21:59');
/*!40000 ALTER TABLE `requisition_items` ENABLE KEYS */;
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
  `status` enum('pending_dispatch','pending_confirmation','supplier_accepted','supplier_counter_proposed','confirmed','partially_received','received','cancelled','closed') NOT NULL DEFAULT 'pending_dispatch',
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `terms` varchar(50) DEFAULT 'Net 30',
  `dispatched_at` datetime DEFAULT NULL,
  `dispatched_via` enum('email','portal','both') DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_po_number` (`po_number`),
  KEY `idx_po_status` (`status`),
  KEY `idx_po_supplier` (`supplier_id`),
  KEY `fk_po_requisition` (`requisition_id`),
  KEY `fk_po_created_by` (`created_by`),
  CONSTRAINT `fk_po_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  CONSTRAINT `fk_po_requisition` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`),
  CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'PO-2026-0001',1,1,'closed','2026-09-04',NULL,10.39,0.00,10.39,'Net 30','2026-09-04 22:28:54','email',6,'2026-09-04 14:28:12','2026-09-04 14:33:11'),(2,'PO-2026-0002',2,1,'received','2026-09-04',NULL,7.17,0.00,7.17,'Net 30','2026-09-04 22:36:15','email',6,'2026-09-04 14:35:40','2026-09-04 16:12:15'),(3,'PO-2026-0003',3,1,'closed','2026-09-04',NULL,1003.90,0.00,1003.90,'Net 30','2026-09-04 22:56:10','email',6,'2026-09-04 14:55:35','2026-09-04 15:00:08'),(4,'PO-2026-0004',4,1,'pending_confirmation','2026-09-04','2026-10-01',41.56,0.00,41.56,'Net 30','2026-09-04 23:55:22','email',6,'2026-09-04 15:53:39','2026-09-04 15:55:22'),(5,'PO-2026-0005',5,1,'pending_confirmation','2026-09-04',NULL,20.78,0.00,20.78,'Net 30','2026-09-05 00:02:23','email',6,'2026-09-04 15:59:04','2026-09-04 16:04:04'),(6,'PO-2026-0006',6,1,'received','2026-09-04',NULL,5195.00,0.00,5195.00,'Net 30','2026-09-05 00:04:50','email',6,'2026-09-04 16:04:26','2026-09-04 16:08:28'),(7,'PO-2026-0007',7,1,'pending_dispatch','2026-09-04',NULL,10.39,0.00,10.39,'Net 30',NULL,NULL,6,'2026-09-04 16:09:56','2026-09-04 16:09:56'),(8,'PO-2026-0008',9,2,'pending_confirmation','2026-09-04','2026-10-02',1338.75,0.00,1338.75,'Net 30','2026-09-05 00:36:44','email',6,'2026-09-04 16:35:26','2026-09-04 16:36:44'),(9,'PO-2026-0009',8,1,'pending_dispatch','2026-09-04',NULL,10.39,0.00,10.39,'Net 30',NULL,NULL,6,'2026-09-04 16:38:22','2026-09-04 16:38:22');
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (1,1,1,5,1,10.39,10.39,1,'2026-09-04 14:28:12'),(2,2,2,6,3,2.39,7.17,3,'2026-09-04 14:35:40'),(3,3,1,5,10,98.00,980.00,10,'2026-09-04 14:55:35'),(4,3,2,6,10,2.39,23.90,4,'2026-09-04 14:55:35'),(5,4,1,5,4,10.39,41.56,0,'2026-09-04 15:53:39'),(6,5,1,5,2,10.39,20.78,0,'2026-09-04 15:59:04'),(7,6,1,5,500,10.39,5195.00,500,'2026-09-04 16:04:26'),(8,7,1,5,1,10.39,10.39,0,'2026-09-04 16:09:56'),(9,8,5,16,45,21.00,945.00,0,'2026-09-04 16:35:26'),(10,8,7,18,35,11.25,393.75,0,'2026-09-04 16:35:26'),(11,9,1,5,1,10.39,10.39,0,'2026-09-04 16:38:22');
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
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
INSERT INTO `po_counter_proposals` VALUES (1,2,12,'accepted','Only 3 units in stock right now',5,'2026-09-04 22:38:15','2026-09-04 14:37:40');
/*!40000 ALTER TABLE `po_counter_proposals` ENABLE KEYS */;
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
INSERT INTO `po_counter_proposal_items` VALUES (1,1,2,3,2.39,NULL,NULL);
/*!40000 ALTER TABLE `po_counter_proposal_items` ENABLE KEYS */;
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
INSERT INTO `goods_receipts` VALUES (1,1,5,'2026-09-04','completed',NULL,'2026-09-04 14:30:03'),(2,3,5,'2026-09-04','completed',NULL,'2026-09-04 14:57:13'),(3,6,5,'2026-09-04','completed',NULL,'2026-09-04 16:08:28'),(4,2,5,'2026-09-04','completed',NULL,'2026-09-04 16:12:15');
/*!40000 ALTER TABLE `goods_receipts` ENABLE KEYS */;
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
INSERT INTO `goods_receipt_items` VALUES (1,1,1,1,'good',NULL),(2,2,3,10,'good',NULL),(3,2,4,4,'good',NULL),(4,3,7,500,'good',NULL),(5,4,2,3,'good',NULL);
/*!40000 ALTER TABLE `goods_receipt_items` ENABLE KEYS */;
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
  `match_status` enum('pending','matched','price_hold','quantity_hold','approved','paid','rejected') NOT NULL DEFAULT 'pending',
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
INSERT INTO `invoices` VALUES (1,'INV-2026-0001',1,1,'2026-09-04','2026-10-04',15.00,0.00,15.00,'paid',NULL,'Test invoice with price variance','2026-09-04 14:30:33','2026-09-04 14:33:11'),(2,'INV-2026-0002',3,1,'2026-09-04','2026-10-04',1023.90,0.00,1023.90,'paid',NULL,'3-way match test: price hold on Book, quantity hold on Pen\n[OVERRIDE] Backorder acknowledged; approving payment for full billed qty per supplier agreement.','2026-09-04 14:57:46','2026-09-04 15:00:08'),(3,'INV-2026-0003',6,1,'2026-09-04','2026-10-04',5195.00,0.00,5195.00,'quantity_hold',NULL,'','2026-09-04 16:06:48','2026-09-04 16:06:48');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
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
INSERT INTO `invoice_items` VALUES (1,1,1,1,15.00,15.00,0,4.61,'ok'),(2,2,3,10,100.00,1000.00,0,2.00,'ok'),(3,2,4,10,2.39,23.90,6,0.00,'quantity_variance'),(4,3,7,500,10.39,5195.00,500,0.00,'quantity_variance');
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_batches`
--

LOCK TABLES `payment_batches` WRITE;
/*!40000 ALTER TABLE `payment_batches` DISABLE KEYS */;
INSERT INTO `payment_batches` VALUES (1,'PB-2026-0001',7,'disbursed',6,'2026-09-04 22:33:16',NULL,15.00,'2026-09-04 14:31:12'),(2,'PB-2026-0002',7,'disbursed',6,'2026-09-04 23:00:13',NULL,1023.90,'2026-09-04 14:59:10');
/*!40000 ALTER TABLE `payment_batches` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_batch_items`
--

LOCK TABLES `payment_batch_items` WRITE;
/*!40000 ALTER TABLE `payment_batch_items` DISABLE KEYS */;
INSERT INTO `payment_batch_items` VALUES (1,1,1,15.00),(2,2,2,1023.90);
/*!40000 ALTER TABLE `payment_batch_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `payment_batch_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` enum('bank_transfer','check','cash','other') NOT NULL DEFAULT 'bank_transfer',
  `reference_number` varchar(50) DEFAULT NULL,
  `paid_by` int(11) NOT NULL,
  `paid_at` datetime NOT NULL,
  `remittance_sent` tinyint(4) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_invoice_payment` (`invoice_id`),
  KEY `idx_pay_batch` (`payment_batch_id`),
  KEY `fk_pay_paid_by` (`paid_by`),
  CONSTRAINT `fk_pay_batch` FOREIGN KEY (`payment_batch_id`) REFERENCES `payment_batches` (`id`),
  CONSTRAINT `fk_pay_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `fk_pay_paid_by` FOREIGN KEY (`paid_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,1,15.00,'bank_transfer','TEST-REF-001',7,'2026-09-04 22:33:11',1,NULL),(2,2,2,1023.90,'bank_transfer','TEST-REF-002',7,'2026-09-04 23:00:08',1,NULL);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
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
INSERT INTO `budgets` VALUES (1,1,'2026-09-H1',0.00,'2026-09-04 16:32:58','2026-09-04 16:32:58');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_transactions`
--

LOCK TABLES `budget_transactions` WRITE;
/*!40000 ALTER TABLE `budget_transactions` DISABLE KEYS */;
INSERT INTO `budget_transactions` VALUES (1,1,'2026-09-H1','adjustment',10000.00,'manual',NULL,6,'Initial budget allocation for manual testing','2026-09-04 16:32:58'),(2,1,'2026-09-H1','reservation',1338.75,'requisition',9,6,NULL,'2026-09-04 16:35:26'),(3,1,'2026-09-H1','reservation',10.39,'requisition',8,6,NULL,'2026-09-04 16:38:22');
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

-- Dump completed on 2026-09-05  0:41:59
