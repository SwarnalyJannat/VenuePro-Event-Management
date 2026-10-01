-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: venuepro
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
-- Table structure for table `booking_singular_items`
--

DROP TABLE IF EXISTS `booking_singular_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `booking_singular_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `singular_item_id` int(11) DEFAULT NULL,
  `item_name` varchar(150) NOT NULL,
  `emoji` varchar(20) DEFAULT '­ƒì¢´©Å',
  `dietary_tag` varchar(20) DEFAULT '',
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `item_status` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `singular_item_id` (`singular_item_id`),
  CONSTRAINT `booking_singular_items_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `booking_singular_items_ibfk_2` FOREIGN KEY (`singular_item_id`) REFERENCES `singular_menu_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `booking_singular_items`
--

LOCK TABLES `booking_singular_items` WRITE;
/*!40000 ALTER TABLE `booking_singular_items` DISABLE KEYS */;
INSERT INTO `booking_singular_items` VALUES (1,1,1,'Grilled Tiger Shrimp','­ƒìñ','GF',12.50,24,300.00,'Accepted','Glazed with lemon garlic butter'),(2,1,2,'Premium Coca-Cola (Glass Bottle)','­ƒÑñ','',3.50,60,210.00,'Accepted','Served chilled on ice'),(3,1,3,'Greek Yogurt Parfait','­ƒìÂ','Veg',7.00,15,105.00,'Pending','Gluten-free granola on top'),(4,1,8,'French Macarons Box/6 (Dessert)','­ƒ½É','Veg',14.00,10,140.00,'Pending','VIP table gift boxes'),(5,2,6,'Truffle Bruschetta','­ƒÑû','Veg',6.00,15,90.00,'Accepted','Passed during arrival reception'),(6,2,5,'San Pellegrino Sparkling Water','­ƒÆº','',4.50,20,50.00,'Accepted','Table bottles'),(7,3,7,'Artisan Cheese Board','­ƒºÇ','Veg',18.00,6,108.00,'Pending','Green room hospitality'),(8,3,8,'French Macarons Box/6 (Dessert)','­ƒ½É','Veg',14.00,12,168.00,'Pending','Late night dessert favor'),(9,4,1,'Grilled Tiger Shrimp','­ƒìñ','',12.50,20,250.00,'Pending',''),(10,4,2,'Premium Coca-Cola (Glass Bottle)','­ƒÑñ','',3.50,40,140.00,'Pending','');
/*!40000 ALTER TABLE `booking_singular_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_code` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `venue_id` int(11) NOT NULL,
  `package_id` int(11) DEFAULT NULL,
  `event_name` varchar(150) NOT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL DEFAULT '18:00:00',
  `end_time` time NOT NULL DEFAULT '22:00:00',
  `guest_count` int(11) NOT NULL DEFAULT 100,
  `duration_hours` int(11) DEFAULT 4,
  `venue_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `package_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `staffing_cost` decimal(10,2) NOT NULL DEFAULT 640.00,
  `addons_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL,
  `service_fee` decimal(10,2) NOT NULL,
  `tax_vat` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('paid','pending','refunded') DEFAULT 'paid',
  `payment_card_last4` varchar(4) DEFAULT '4242',
  `booking_status` enum('pending','confirmed','rejected','completed','cancelled','canceled') DEFAULT 'pending',
  `caterer_status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `caterer_rejection_reason` text DEFAULT NULL,
  `special_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_code` (`booking_code`),
  KEY `customer_id` (`customer_id`),
  KEY `venue_id` (`venue_id`),
  KEY `package_id` (`package_id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_booking_status` (`booking_status`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`id`),
  CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`package_id`) REFERENCES `catering_packages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES (1,'BK-9021',1,1,1,'Annual Gala Night','2026-10-14','18:00:00','22:00:00',320,4,2400.00,1200.00,640.00,755.00,4995.00,499.50,399.60,5894.10,'paid','4242','confirmed','accepted','Approved by Master Chef','VIP guest table requires extra floral arrangement and dietary accommodations.\n[Cancelled: test]\n[Cancelled: test again]\r\n[Admin Update: Verified by admin test]\r\n[Admin Update: Verified by admin test]','2026-10-01 15:38:31'),(2,'BK-8843',1,2,2,'TechCorp Global Executive Dinner','2026-11-04','18:30:00','22:30:00',150,4,2400.00,1850.00,640.00,140.00,5030.00,503.00,402.40,5935.40,'paid','4242','confirmed','accepted',NULL,'Projector and keynote podium required in the central atrium area.','2026-10-01 15:38:31'),(3,'BK-9011',1,3,3,'Luxe Media Q4 Launch Summit','2026-12-15','17:00:00','23:00:00',80,6,2450.00,2400.00,640.00,276.00,5766.00,576.60,461.28,6803.88,'paid','4242','confirmed','pending',NULL,'Rooftop cocktail party followed by 5-course tasting menu.','2026-10-01 15:38:31'),(4,'BK-8435',1,2,2,'Automated Test Gala 2026','2026-11-25','18:00:00','22:00:00',120,4,2400.00,1850.00,640.00,390.00,5280.00,528.00,422.40,6230.40,'paid','9876','confirmed','pending',NULL,'','2026-10-01 15:44:45'),(13,'BK-8767',1,5,1,'Test Gala Dinner','2026-11-30','18:00:00','22:00:00',50,4,3200.00,1200.00,640.00,0.00,5040.00,504.00,403.20,5947.20,'paid','4242','cancelled','pending',NULL,'Test booking\n[Cancelled: Automated test cancellation]','2026-10-01 17:18:41'),(14,'BK-8500',1,2,NULL,'Second Day Event','2027-03-30','10:00:00','14:00:00',30,4,2400.00,0.00,640.00,0.00,3040.00,304.00,243.20,3587.20,'paid','5555','confirmed','pending',NULL,'','2026-10-01 17:18:41');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caterer_orders`
--

DROP TABLE IF EXISTS `caterer_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caterer_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_code` varchar(50) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `caterer_id` int(11) NOT NULL,
  `covers_count` int(11) NOT NULL DEFAULT 100,
  `preparation_status` enum('Preparing','Delivering','Completed') DEFAULT 'Preparing',
  `package_decision` enum('Pending','Accepted','Rejected') DEFAULT 'Pending',
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_code` (`order_code`),
  KEY `booking_id` (`booking_id`),
  KEY `caterer_id` (`caterer_id`),
  KEY `idx_prep_status` (`preparation_status`),
  CONSTRAINT `caterer_orders_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `caterer_orders_ibfk_2` FOREIGN KEY (`caterer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caterer_orders`
--

LOCK TABLES `caterer_orders` WRITE;
/*!40000 ALTER TABLE `caterer_orders` DISABLE KEYS */;
INSERT INTO `caterer_orders` VALUES (1,'ORD-2045',1,2,320,'Preparing','Accepted','Approved by Master Chef','2026-10-01 15:38:31'),(2,'ORD-942',1,2,150,'Preparing','Pending',NULL,'2026-10-01 15:38:31'),(3,'ORD-938',2,2,200,'Delivering','Accepted',NULL,'2026-10-01 15:38:31'),(11,'ORD-3248',13,2,50,'Preparing','Pending',NULL,'2026-10-01 17:18:41');
/*!40000 ALTER TABLE `caterer_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caterer_profiles`
--

DROP TABLE IF EXISTS `caterer_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caterer_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(150) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `kitchen_address` text NOT NULL,
  `specialization` varchar(100) DEFAULT 'Fine Dining',
  `rating` decimal(2,1) DEFAULT 4.9,
  `review_count` int(11) DEFAULT 120,
  `approval_status` enum('approved','under_review','rejected') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `caterer_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caterer_profiles`
--

LOCK TABLES `caterer_profiles` WRITE;
/*!40000 ALTER TABLE `caterer_profiles` DISABLE KEYS */;
INSERT INTO `caterer_profiles` VALUES (1,2,'Artisan Grand Kitchen & Banquets','Alex Rivera','742 Evergreen Terrace, Culinary District, Metropolis','Fine Dining & Corporate Gala Banqueting',4.9,142,'approved','2026-10-01 15:38:31');
/*!40000 ALTER TABLE `caterer_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catering_packages`
--

DROP TABLE IF EXISTS `catering_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catering_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caterer_id` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `tier` enum('Gold','Platinum','Signature','Custom') DEFAULT 'Gold',
  `price_per_event` decimal(10,2) NOT NULL DEFAULT 1200.00,
  `price_per_guest` decimal(10,2) NOT NULL DEFAULT 24.00,
  `min_guests` int(11) DEFAULT 30,
  `max_capacity` int(11) DEFAULT 500,
  `description` text NOT NULL,
  `cuisine_type` varchar(100) DEFAULT 'Fine Dining',
  `service_style` varchar(100) DEFAULT 'Plated Banquet',
  `features` text DEFAULT NULL,
  `status` enum('published','draft','archived') DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `caterer_id` (`caterer_id`),
  CONSTRAINT `catering_packages_ibfk_1` FOREIGN KEY (`caterer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catering_packages`
--

LOCK TABLES `catering_packages` WRITE;
/*!40000 ALTER TABLE `catering_packages` DISABLE KEYS */;
INSERT INTO `catering_packages` VALUES (1,2,'Gold Package','Gold',1200.00,24.00,30,350,'Perfect for daytime corporate seminars, executive luncheons, and symposium sessions with plated courses.','Continental & Gourmet Fusion','Plated 2-Course Lunch','[\"2-Course Gourmet Plated Lunch\", \"Artisanal Coffee & Herbal Tea Lounge\", \"Passed Seasonal Finger Foods & Canap├®s\", \"Standard Banquet Table Settings & Linens\"]','published','2026-10-01 15:38:31'),(2,2,'Platinum Package','Platinum',1850.00,37.00,50,500,'Full gala dining experience with open bar mixology, table service, and sommelier curation for elite corporate dinners.','Modern European Fine Dining','Plated 3-Course Dinner & Open Bar','[\"3-Course Fine Dining Plated Dinner\", \"4-Hour Premium Open Bar & Wine Curation\", \"Executive Chef Signature Dessert Trio\", \"VIP Green Room Cocktail & Canapes Service\", \"Dedicated Captain & Maitre d Service\"]','published','2026-10-01 15:38:31'),(3,2,'Signature Package','Signature',2400.00,48.00,40,250,'Fixed 5-course private tasting banquet curated and orchestrated live by Master Chef Alex Rivera.','Avant-Garde Chef Tasting','5-Course Wine-Paired Banquet','[\"5-Course Wine-Paired Fine Dining Menu\", \"Executive Head Chef Live at Captain Table\", \"Midnight Savory Bites & Nightcap Station\", \"Artisanal Organic Farmstead Ingredients\"]','published','2026-10-01 15:38:31');
/*!40000 ALTER TABLE `catering_packages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `booking_id` (`booking_id`),
  KEY `idx_conversation` (`sender_id`,`receiver_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_ibfk_3` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES (1,3,1,1,'Hello Mahmud! I am Sarah Jenkins, your assigned Event Coordinator for the Annual Gala Night at Grand Emerald Ballroom. How can I assist you today?',1,'2026-10-01 04:15:00'),(2,1,3,1,'Hi Sarah! Thank you for reaching out. We have approximately 25 VIP attendees who will need front-row seating and dedicated parking spaces.',1,'2026-10-01 04:20:00'),(3,3,1,1,'Noted with pleasure! I have reserved 25 VIP spots with our valet team and designated Tables 1 through 3 at the front of the stage.',1,'2026-10-01 04:25:00'),(4,5,1,2,'Hello Mahmud, Marcus from AV here. We tested the 4K projector for your TechCorp dinner and everything is pristine.',0,'2026-10-01 08:00:00'),(5,1,3,NULL,'Automated test message: Is the podium mic confirmed?',1,'2026-10-01 15:44:45'),(6,1,3,NULL,'Hello from test!',0,'2026-10-01 17:03:47'),(7,1,3,NULL,'Hello from test!',0,'2026-10-01 17:05:23'),(8,1,3,NULL,'Hello from test!',0,'2026-10-01 17:18:41');
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_code` varchar(50) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `service_fee` decimal(10,2) NOT NULL,
  `vat_tax` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('paid','unpaid','overdue') DEFAULT 'paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_code` (`invoice_code`),
  KEY `booking_id` (`booking_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV-2026-0847',1,1,'2026-10-01','2026-10-14',4995.00,499.50,399.60,5894.10,'paid','2026-10-01 15:38:31'),(2,'INV-2026-0848',2,1,'2026-10-01','2026-11-04',5030.00,503.00,402.40,5935.40,'paid','2026-10-01 15:38:31'),(3,'INV-2026-8262',4,1,'2026-10-01','2026-11-25',5280.00,528.00,422.40,6230.40,'paid','2026-10-01 15:44:45'),(12,'INV-2026-6479',13,1,'2026-10-01','2026-11-30',5040.00,504.00,403.20,5947.20,'paid','2026-10-01 17:18:41'),(13,'INV-2026-5765',14,1,'2026-10-01','2027-03-30',3040.00,304.00,243.20,3587.20,'paid','2026-10-01 17:18:41');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
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
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('booking','order','system','chat','payment') DEFAULT 'booking',
  `link_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`,`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'Booking Confirmed (#BK-9021)','Your reservation for Grand Emerald Ballroom on Oct 14, 2026 has been successfully confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 15:38:31'),(2,1,'Staff Assigned to Your Event','Sarah Jenkins has been assigned as your Lead Event Coordinator.','system','customer-chat.php',0,'2026-10-01 15:38:31'),(3,1,'Payment Receipt Generated','Payment of $5,894.10 for #BK-9021 processed successfully.','payment','client-invoice.php',1,'2026-10-01 15:38:31'),(4,2,'New Catering Order #ORD-2045','Customer Mahmud placed an order for Gold Package (320 covers) + 4 add-on items.','order','caterer-order-details.php',0,'2026-10-01 15:38:31'),(5,3,'Event Shift Assigned: Grand Emerald','You are assigned as Lead Coordinator for Annual Gala Night on Oct 14.','system','staff-event-setup.php',0,'2026-10-01 15:38:31'),(6,4,'New Venue Booking (#BK-9021)','Customer Mahmud booked Grand Emerald Ballroom ($5,894.10).','booking','admin-booking-approval.php',0,'2026-10-01 15:38:31'),(7,1,'Booking Confirmed (BK-8435)','Your reservation for The Glass House - Grand Hall on 2026-11-25 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 15:44:45'),(8,2,'New Catering Order (BK-8435)','Customer Mahmud placed order for The Glass House - Grand Hall.','order','caterer-order-details.php',0,'2026-10-01 15:44:45'),(9,1,'Booking Confirmed (BK-8226)','Your reservation for Crystal Tech Pavilion on 2026-11-30 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 16:33:42'),(10,2,'New Catering Order (BK-8226)','Customer Mahmud placed order for Crystal Tech Pavilion.','order','caterer-order-details.php',0,'2026-10-01 16:33:42'),(11,1,'Booking Confirmed (BK-9471)','Your reservation for Crystal Tech Pavilion on 2026-12-01 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 16:33:42'),(12,2,'New Catering Order (BK-9471)','Customer Mahmud placed order for Crystal Tech Pavilion.','order','caterer-order-details.php',0,'2026-10-01 16:33:42'),(13,1,'Booking Cancelled (BK-8226)','Your booking for Test Gala Dinner has been cancelled. Reason: Automated test cancellation','booking','customer-live-progress.php',0,'2026-10-01 16:33:42'),(14,1,'Booking Cancelled (BK-8226)','Your booking for Test Gala Dinner has been cancelled. Reason: Cancelled by customer','booking','customer-live-progress.php',0,'2026-10-01 16:33:42'),(15,1,'Booking Cancelled (BK-9021)','Your booking for Annual Gala Night has been cancelled. Reason: test','booking','customer-live-progress.php',0,'2026-10-01 16:34:07'),(16,1,'Booking Cancelled (BK-9021)','Your booking for Annual Gala Night has been cancelled. Reason: test again','booking','customer-live-progress.php',0,'2026-10-01 16:34:07'),(17,1,'Booking Confirmed (BK-9693)','Your reservation for Grand Emerald Ballroom on 2026-12-30 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 16:35:43'),(18,2,'New Catering Order (BK-9693)','Customer Mahmud placed order for Grand Emerald Ballroom.','order','caterer-order-details.php',0,'2026-10-01 16:35:43'),(19,1,'Booking Cancelled (BK-9693)','Your booking for Cancel Test has been cancelled. Reason: First cancel','booking','customer-live-progress.php',0,'2026-10-01 16:35:43'),(20,1,'Booking Cancelled (BK-9693)','Your booking for Cancel Test has been cancelled. Reason: Second cancel','booking','customer-live-progress.php',0,'2026-10-01 16:35:43'),(21,1,'Booking Confirmed (BK-8606)','Your reservation for Crystal Tech Pavilion on 2026-11-30 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 16:38:41'),(22,2,'New Catering Order (BK-8606)','Customer Mahmud placed order for Crystal Tech Pavilion.','order','caterer-order-details.php',0,'2026-10-01 16:38:41'),(23,1,'Booking Confirmed (BK-8369)','Your reservation for Crystal Tech Pavilion on 2026-12-01 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 16:38:41'),(24,2,'New Catering Order (BK-8369)','Customer Mahmud placed order for Crystal Tech Pavilion.','order','caterer-order-details.php',0,'2026-10-01 16:38:41'),(25,1,'Booking Cancelled (BK-8606)','Your booking for Test Gala Dinner has been cancelled. Reason: Automated test cancellation','booking','customer-live-progress.php',0,'2026-10-01 16:38:41'),(26,1,'Booking Confirmed (BK-9512)','Your reservation for Crystal Tech Pavilion on 2026-11-30 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 17:03:46'),(27,2,'New Catering Order (BK-9512)','Customer Mahmud placed order for Crystal Tech Pavilion.','order','caterer-order-details.php',0,'2026-10-01 17:03:46'),(28,1,'Booking Cancelled (BK-9512)','Your booking for Test Gala Dinner has been cancelled. Reason: Automated test cancellation','booking','customer-live-progress.php',0,'2026-10-01 17:03:46'),(29,1,'Booking Confirmed (BK-8204)','Your reservation for Crystal Tech Pavilion on 2026-11-30 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 17:05:22'),(30,2,'New Catering Order (BK-8204)','Customer Mahmud placed order for Crystal Tech Pavilion.','order','caterer-order-details.php',0,'2026-10-01 17:05:22'),(31,1,'Booking Confirmed (BK-8513)','Your reservation for The Glass House - Grand Hall on 2027-03-30 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 17:05:22'),(32,2,'New Catering Order (BK-8513)','Customer Mahmud placed order for The Glass House - Grand Hall.','order','caterer-order-details.php',0,'2026-10-01 17:05:22'),(33,1,'Booking Cancelled (BK-8204)','Your booking for Test Gala Dinner has been cancelled. Reason: Automated test cancellation','booking','customer-live-progress.php',0,'2026-10-01 17:05:22'),(34,1,'Booking Confirmed (BK-8767)','Your reservation for Crystal Tech Pavilion on 2026-11-30 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 17:18:41'),(35,2,'New Catering Order (BK-8767)','Customer Mahmud placed order for Crystal Tech Pavilion.','order','caterer-order-details.php',0,'2026-10-01 17:18:41'),(36,1,'Booking Confirmed (BK-8500)','Your reservation for The Glass House - Grand Hall on 2027-03-30 has been confirmed.','booking','booking-status-timeline.php',0,'2026-10-01 17:18:41'),(37,2,'New Catering Order (BK-8500)','Customer Mahmud placed order for The Glass House - Grand Hall.','order','caterer-order-details.php',0,'2026-10-01 17:18:41'),(38,1,'Booking Cancelled (BK-8767)','Your booking for Test Gala Dinner has been cancelled. Reason: Automated test cancellation','booking','customer-live-progress.php',0,'2026-10-01 17:18:41');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `package_menu_items`
--

DROP TABLE IF EXISTS `package_menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `package_id` int(11) NOT NULL,
  `course` enum('Appetizers','Main Course','Desserts','Beverages') DEFAULT 'Main Course',
  `item_name` varchar(150) NOT NULL,
  `dietary_tag` varchar(20) DEFAULT '',
  `portion_note` varchar(255) DEFAULT NULL,
  `unit_value` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `package_menu_items_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `catering_packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `package_menu_items`
--

LOCK TABLES `package_menu_items` WRITE;
/*!40000 ALTER TABLE `package_menu_items` DISABLE KEYS */;
INSERT INTO `package_menu_items` VALUES (1,1,'Appetizers','Organic Garden Salad with Lemon Vinaigrette','Veg','Individual plated starter',6.00),(2,1,'Main Course','Pan-Seared Herb Chicken Breast & Roasted Baby Potatoes','GF','Main course poultry',16.00),(3,1,'Main Course','Vegetable Wellington with Spinach & Mushroom Reduction','Veg','Vegetarian alternative',14.00),(4,1,'Desserts','Artisanal Coffee, Tea & Mini Tartlet Station','Veg','Buffet dessert lounge',4.00),(5,2,'Appetizers','Lobster Roll Canap├® with Tarragon Mayo','','Passed luxury starter',12.00),(6,2,'Main Course','Grilled Norwegian Salmon Fillet with Lemon-Dill Beurre Blanc','GF','Seafood entr├®e',18.50),(7,2,'Main Course','Prime Beef Wellington Medallion with Mushroom Duxelles','','Signature entr├®e',24.00),(8,2,'Main Course','Wild Mushroom & Black Truffle Risotto','Veg','Vegetarian entr├®e',14.00),(9,2,'Desserts','Warm Chocolate Fondant with Madagascar Vanilla Cr├¿me','','Plated dessert',8.00),(10,2,'Beverages','Open Bar ÔÇö Premium Spirits, Wine & Champagne (4h)','','Full bar service window',15.00);
/*!40000 ALTER TABLE `package_menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `singular_menu_items`
--

DROP TABLE IF EXISTS `singular_menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `singular_menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caterer_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `item_key` varchar(50) NOT NULL,
  `category` varchar(50) DEFAULT 'Food',
  `emoji` varchar(20) DEFAULT '­ƒì¢´©Å',
  `image_url` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit_label` varchar(30) DEFAULT 'piece',
  `quantity_available` int(11) DEFAULT 100,
  `min_order_qty` int(11) DEFAULT 1,
  `description` text DEFAULT NULL,
  `dietary_tags` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `item_key` (`item_key`),
  KEY `caterer_id` (`caterer_id`),
  KEY `idx_category` (`category`),
  CONSTRAINT `singular_menu_items_ibfk_1` FOREIGN KEY (`caterer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `singular_menu_items`
--

LOCK TABLES `singular_menu_items` WRITE;
/*!40000 ALTER TABLE `singular_menu_items` DISABLE KEYS */;
INSERT INTO `singular_menu_items` VALUES (1,2,'Grilled Tiger Shrimp','shrimp','Seafood','­ƒìñ','../assets/item-shrimp.jpg',12.50,'piece',50,2,'Jumbo tiger shrimp flame-grilled with garlic butter, lemon zest and chopped flat parsley. Gluten-Free friendly.','[\"Gluten-Free\", \"High-Protein\"]','active','2026-10-01 15:38:31'),(2,2,'Premium Coca-Cola (Glass Bottle)','coke','Drinks','­ƒÑñ','../assets/item-coke.jpg',3.50,'bottle',120,1,'Chilled 330ml vintage glass contour bottle Coca-Cola served with ice bucket and fresh lime slices.','[\"Chilled\", \"Table-Service\"]','active','2026-10-01 15:38:31'),(3,2,'Greek Yogurt Parfait','yogurt','Dairy','­ƒìÂ','../assets/item-yogurt.jpg',7.00,'cup',30,1,'Thick strained Greek yogurt layered with wildflower honey, toasted granola, and seasonal organic berries.','[\"Vegetarian\", \"Gluten-Free\"]','active','2026-10-01 15:38:31'),(4,2,'Vanilla Panna Cotta Dessert','panna','Dessert','­ƒì«','../assets/item-panna.jpg',9.50,'serving',40,1,'Silky Piedmontese panna cotta infused with bourbon vanilla pod, topped with mixed berry coulis and fresh mint.','[\"Vegetarian\", \"Italian-Classic\"]','active','2026-10-01 15:38:31'),(5,2,'San Pellegrino Sparkling Water','water','Drinks','­ƒÆº','../assets/item-water.jpg',4.50,'bottle',30,1,'500ml glass bottle Italian sparkling natural mineral water with refined fine perlage for dining tables.','[\"Zero-Sugar\", \"Italian\"]','active','2026-10-01 15:38:31'),(6,2,'Truffle Bruschetta','bruschetta','Appetizer','­ƒÑû','../assets/item-bruschetta.jpg',6.00,'piece',30,2,'Charred artisan sourdough crostini brushed with white truffle oil, sun-ripened heritage tomatoes and torn basil.','[\"Vegetarian\", \"Vegan-Option\"]','active','2026-10-01 15:38:31'),(7,2,'Artisan Cheese Board','cheese','Appetizer','­ƒºÇ','../assets/item-cheese.jpg',18.00,'board',15,1,'Platter of aged French Brie, Dutch Gouda, and Spanish Manchego served with sea salt crackers, grapes, and honey.','[\"Vegetarian\", \"Serves-4\"]','active','2026-10-01 15:38:31'),(8,2,'French Macarons Box/6 (Dessert)','macarons','Dessert','­ƒ½É','../assets/item-macarons.jpg',14.00,'box',30,1,'Gift box of 6 handcrafted Parisian macarons: Sicilian pistachio, dark raspberry, salted butter caramel, Tahitian vanilla.','[\"Vegetarian\", \"Gluten-Free\"]','active','2026-10-01 15:38:31'),(9,2,'Matcha Green Tea Tartlet (Deluxe)','matcha-green-tea-tartlet-240','Dessert','🍽️',NULL,9.00,'piece',55,1,'Updated deluxe description.','[]','inactive','2026-10-01 15:44:45');
/*!40000 ALTER TABLE `singular_menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_policies`
--

DROP TABLE IF EXISTS `site_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_policies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_key` varchar(50) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` mediumtext NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `policy_key` (`policy_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `site_policies_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_policies`
--

LOCK TABLES `site_policies` WRITE;
/*!40000 ALTER TABLE `site_policies` DISABLE KEYS */;
INSERT INTO `site_policies` VALUES (1,'privacy_policy','Privacy Policy','Updated in automated test.',4,'2026-10-01 17:18:41'),(2,'terms_of_service','VenuePro Terms of Service','<h3>1. Agreement to Terms</h3><p>By accessing or utilizing the VenuePro Enterprise Platform, clients, venues, staff members, and catering partners agree to be bound by these Terms of Service.</p><h3>2. Booking & Cancellation Terms</h3><p>Reservations may be canceled with full refund up to 48 hours prior to scheduled event setup. Cancellations within 48 hours are subject to standard kitchen prep and staffing minimum allocations.</p><h3>3. Culinary & Health Standards</h3><p>All participating caterers must maintain valid commercial food sanitation certifications. Allergen declarations submitted through VenuePro are binding and must be strictly adhered to by kitchen stations.</p>',4,'2026-10-01 15:38:31'),(3,'contact_support','VenuePro Enterprise Support','<h3>Emergency Operational Concierge</h3><p>For live in-event logistics emergencies, dedicated coordinators can be contacted directly through the in-app chat or via our 24/7 priority enterprise support desk at <strong>support@venuepro.com</strong> or phone: <strong>+1 (800) 555-VENUE</strong>.</p><p>Standard response time for active booked events: under 5 minutes.</p>',4,'2026-10-01 15:38:31');
/*!40000 ALTER TABLE `site_policies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_assignments`
--

DROP TABLE IF EXISTS `staff_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `role_title` varchar(100) DEFAULT 'Event Coordinator',
  `shift_time` varchar(100) DEFAULT '16:00 - 23:00',
  `setup_status` enum('pending','in_progress','completed') DEFAULT 'in_progress',
  `checklist_json` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `staff_id` (`staff_id`),
  CONSTRAINT `staff_assignments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_assignments_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_assignments`
--

LOCK TABLES `staff_assignments` WRITE;
/*!40000 ALTER TABLE `staff_assignments` DISABLE KEYS */;
INSERT INTO `staff_assignments` VALUES (1,1,3,'Lead Event Coordinator','15:00 - 23:30','completed','[\"Banquet Tables Arranged \\u2713\",\"AV Tested \\u2713\",\"Catering Kitchen Briefed \\u2713\"]','2026-10-01 15:38:31'),(2,2,5,'AV & Technical Director','16:00 - 23:00','completed','[\"Projector & 4K Display Calibrated Ô£ô\", \"Wireless Microphones Battery Checked Ô£ô\", \"Keynote Stream Uplink Verified Ô£ô\"]','2026-10-01 15:38:31'),(3,3,6,'Guest Relations & Security Lead','15:30 - 23:30','pending','[\"Access Wristbands Prepared Ôùï\", \"Valet Parking Logistics Team Briefed Ôùï\", \"Coat Check Staff Deployed Ôùï\"]','2026-10-01 15:38:31'),(13,13,3,'Lead Event Coordinator','15:00 - 23:00','in_progress','[\"Banquet Tables & High-Tops Arranged \\u2713\",\"Stage & Audio-Visual Acoustics Tested \\u2713\",\"Catering Service Kitchen Station Briefed \\u25cb\",\"Emergency Exits & Security Briefed \\u25cb\"]','2026-10-01 17:18:41'),(14,14,3,'Lead Event Coordinator','15:00 - 23:00','in_progress','[\"Banquet Tables & High-Tops Arranged \\u2713\",\"Stage & Audio-Visual Acoustics Tested \\u2713\",\"Catering Service Kitchen Station Briefed \\u25cb\",\"Emergency Exits & Security Briefed \\u25cb\"]','2026-10-01 17:18:41');
/*!40000 ALTER TABLE `staff_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_profiles`
--

DROP TABLE IF EXISTS `staff_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `staff_code` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT 'Event Operations',
  `assigned_venues` text DEFAULT NULL,
  `active_status` enum('active','on_leave','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_profiles`
--

LOCK TABLES `staff_profiles` WRITE;
/*!40000 ALTER TABLE `staff_profiles` DISABLE KEYS */;
INSERT INTO `staff_profiles` VALUES (1,3,'STF-1042','Event Logistics & Guest Relations','Grand Emerald Ballroom, The Glass House','active','2026-10-01 15:38:31'),(2,5,'STF-1043','AV & Stage Production','Crystal Tech Pavilion, Skyline Vista Lounge','active','2026-10-01 15:38:31'),(3,6,'STF-1044','Security & Guest Operations','The Brick & Steel Gallery, Riverside Suite','active','2026-10-01 15:38:31');
/*!40000 ALTER TABLE `staff_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('customer','caterer','staff','admin') NOT NULL DEFAULT 'customer',
  `phone` varchar(30) DEFAULT NULL,
  `avatar_text` varchar(5) DEFAULT 'U',
  `avatar_bg` varchar(20) DEFAULT '#2563eb',
  `status` enum('active','pending','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Mahmud','customer@venuepro.com','$2y$10$.NsM26ClPgn6mWnHqy.4lOgoR.hfBg7ryD3vvjGTj1A9DjSoo99Eu','customer','+1 (555) 234-5678','M','#2563eb','active','2026-10-01 15:38:31'),(2,'Alex Rivera','caterer@venuepro.com','$2y$10$.NsM26ClPgn6mWnHqy.4lOgoR.hfBg7ryD3vvjGTj1A9DjSoo99Eu','caterer','+1 (555) 876-5432','AR','#059669','active','2026-10-01 15:38:31'),(3,'Sarah Jenkins','staff@venuepro.com','$2y$10$.NsM26ClPgn6mWnHqy.4lOgoR.hfBg7ryD3vvjGTj1A9DjSoo99Eu','staff','+1 (555) 345-6789','SJ','#0284c7','active','2026-10-01 15:38:31'),(4,'Alex Sterling','admin@venuepro.com','$2y$10$.NsM26ClPgn6mWnHqy.4lOgoR.hfBg7ryD3vvjGTj1A9DjSoo99Eu','admin','+1 (555) 987-6543','AS','#4f46e5','active','2026-10-01 15:38:31'),(5,'Marcus Vance','marcus.staff@venuepro.com','$2y$10$.NsM26ClPgn6mWnHqy.4lOgoR.hfBg7ryD3vvjGTj1A9DjSoo99Eu','staff','+1 (555) 456-7890','MV','#0891b2','active','2026-10-01 15:38:31'),(6,'Elena Rostova','elena.staff@venuepro.com','$2y$10$.NsM26ClPgn6mWnHqy.4lOgoR.hfBg7ryD3vvjGTj1A9DjSoo99Eu','staff','+1 (555) 567-8901','ER','#0d9488','active','2026-10-01 15:38:31'),(17,'Test Customer','testuser_246489@test.com','$2y$10$DbjezySAOYaGVgkB5g53BuqtwEeJpwM3Jb9gvGRj4hHiUIrV8LTWO','customer','','TE','#0284c7','active','2026-10-01 17:18:40'),(18,'<script>alert(\'XSS\')</script>','xss_92155@test.com','$2y$10$YbZwAN9TZBQzYtFae8LFQeYK9dhmI0k0ESMOwhhTfg12Ij9V5FgPO','customer','','<S','#4f46e5','active','2026-10-01 17:18:41');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venues`
--

DROP TABLE IF EXISTS `venues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `venues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `venue_type` varchar(50) NOT NULL DEFAULT 'Ballroom',
  `address` varchar(255) NOT NULL,
  `district` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `base_rate` decimal(10,2) NOT NULL DEFAULT 2400.00,
  `service_fee_pct` decimal(4,2) DEFAULT 10.00,
  `additional_hour_rate` decimal(10,2) DEFAULT 350.00,
  `rating` decimal(2,1) DEFAULT 4.9,
  `review_count` int(11) DEFAULT 124,
  `description` text DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT '../assets/venue-default.jpg',
  `badge` varchar(50) DEFAULT 'PREMIUM',
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_venue_type` (`venue_type`),
  KEY `idx_capacity` (`capacity`),
  KEY `idx_base_rate` (`base_rate`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venues`
--

LOCK TABLES `venues` WRITE;
/*!40000 ALTER TABLE `venues` DISABLE KEYS */;
INSERT INTO `venues` VALUES (1,'Grand Emerald Ballroom','grand-emerald-ballroom','Ballroom','100 Downtown Plaza, Level 4','Downtown Business District',500,2400.00,10.00,350.00,4.9,124,'A palatial crystal ballroom boasting high vaulted ceilings, bespoke crystal chandeliers, built-in presentation acoustic panels, and an adjoining private marble foyer ideal for cocktail receptions.','[\"WiFi / Ultra-Fast Fiber\", \"Bose Arena Sound System\", \"Full In-house Catering Prep\", \"Valet Parking (200 bays)\", \"Full ADA Compliance\", \"A/V Support Team On-site\", \"Elite Security Detail\", \"4 Breakout Suites\"]','../assets/venue-ballroom.jpg','PREMIUM','active','2026-10-01 15:38:31'),(2,'The Glass House - Grand Hall','the-glass-house','Garden Spaces','45 Botanica Boulevard','Emerald City North Waterfront',350,2400.00,10.00,300.00,4.9,98,'Floor-to-ceiling glass architecture overlooking botanical conservatory gardens and illuminated water reflection ponds. Ideal for luxury galas, weddings, and high-profile product showcases.','[\"Panoramic Garden Views\", \"Climate-Controlled Glass Solarium\", \"Integrated Mood Lighting\", \"Dedicated VIP Terrace\", \"Fiber WiFi\", \"Valet Parking\"]','../assets/venue-glasshouse.jpg','MOST POPULAR','active','2026-10-01 15:38:31'),(3,'Skyline Vista Lounge','skyline-vista-lounge','Rooftops','77 Tower Heights Way, 42nd Floor','North Waterfront District',150,2450.00,10.00,400.00,4.7,85,'A premier rooftop lounge panoramic skyline views, bespoke cocktail bar, lounge cabanas, and indoor climate-controlled penthouse.','[\"360-degree City Views\", \"Open Air Firepits\", \"State-of-the-Art DJ Booth\", \"Mixologist Bar Station\", \"Private VIP Elevator Access\"]','../assets/venue-rooftop.jpg','TOP RATED','active','2026-10-01 15:38:31'),(4,'The Brick & Steel Gallery','brick-and-steel-gallery','Industrial Lofts','12 Industrial Foundry Road','Arts & Design District',300,1800.00,10.00,250.00,4.8,64,'Exposed industrial brick, polished concrete floors, soaring steel truss ceilings, and natural industrial skylights.','[\"Exposed Brickwork\", \"Overhead Rigging Truss\", \"Heavy Equipment Loading Bay\", \"High-Speed WiFi\", \"Acoustic Wall Panels\"]','../assets/venue-loft.jpg','NEW','active','2026-10-01 15:38:31'),(5,'Crystal Tech Pavilion','crystal-tech-pavilion','Conference Centers','500 Innovation Boulevard','Innovation Science Park',400,3200.00,10.00,450.00,5.0,112,'Purpose-built for world-class keynotes, tech conferences, hackathons, and high-tech product launches.','[\"4K Video Wall 30ft\", \"Simultaneous Interpretation Booths\", \"Gigabit Uplink per Seat\", \"Green Room VIP Suites\"]','../assets/venue-tech.jpg','ENTERPRISE','active','2026-10-01 15:38:31'),(6,'The Legacy Hall','the-legacy-hall','Ballroom','88 Heritage Way','University Academic Quarter',200,950.00,10.00,150.00,4.6,47,'Timeless mahogany paneled hall with historic collegiate architecture, grand stone fireplace, and brass chandeliers.','[\"Grand Stone Fireplace\", \"Pipe Organ & Steinway Grand\", \"Courtyard Garden Access\", \"Dedicated Cloakroom\"]','../assets/venue-heritage.jpg','VALUE','active','2026-10-01 15:38:31'),(7,'Zen Courtyard Retreat','zen-courtyard-retreat','Garden Spaces','14 Bamboo Path','West Garden Hills',80,1500.00,10.00,200.00,4.9,39,'Tranquil Japanese-inspired outdoor garden pavilion with koi ponds, cedar pagodas, and bamboo perimeter privacy walls.','[\"Tranquil Water Features\", \"Cedar Pagodas\", \"Natural Stone Walkways\", \"Meditation Ambient Audio\"]','../assets/venue-zen.jpg','BOUTIQUE','active','2026-10-01 15:38:31');
/*!40000 ALTER TABLE `venues` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-01 23:18:56
