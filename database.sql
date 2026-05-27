-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: draugiem
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `grade_type` varchar(100) DEFAULT NULL,
  `grade` decimal(4,2) DEFAULT NULL,
  `grade_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grades`
--

LOCK TABLES `grades` WRITE;
/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
INSERT INTO `grades` VALUES (1,12,'Matemātika I','II semestra vērtējums',3.00,'2026-05-18'),(2,13,'Matemātika I','II semestra vērtējums',8.00,'2026-05-18'),(3,14,'Matemātika I','II semestra vērtējums',4.00,'2026-05-18'),(4,12,'Programmas izstrādes process','Galīgais vērtējums priekšmetā',4.00,'2026-01-14'),(5,13,'Programmas izstrādes process','Galīgais vērtējums priekšmetā',7.00,'2026-01-14'),(6,14,'Programmas izstrādes process','Galīgais vērtējums priekšmetā',10.00,'2026-01-14'),(7,12,'Programmas koda rakstīšana (Kodēšana)','II semestra vērtējums',8.00,'2026-05-13'),(8,13,'Programmas koda rakstīšana (Kodēšana)','II semestra vērtējums',7.00,'2026-05-13'),(9,14,'Programmas koda rakstīšana (Kodēšana)','II semestra vērtējums',10.00,'2026-05-13');
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scholarship_table`
--

DROP TABLE IF EXISTS `scholarship_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scholarship_table` (
  `id` int NOT NULL AUTO_INCREMENT,
  `grade_from` decimal(4,2) DEFAULT NULL,
  `grade_to` decimal(4,2) DEFAULT NULL,
  `amount` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scholarship_table`
--

LOCK TABLES `scholarship_table` WRITE;
/*!40000 ALTER TABLE `scholarship_table` DISABLE KEYS */;
INSERT INTO `scholarship_table` VALUES (1,4.00,5.00,20.00),(2,5.00,6.00,30.00),(3,6.00,7.00,40.00),(4,7.00,8.00,50.00),(5,8.00,9.00,60.00),(6,9.00,10.00,70.00);
/*!40000 ALTER TABLE `scholarship_table` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `monthly_budget` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (2,'2026-01-05','2026-06-30',500.00);
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `personal_code` varchar(50) DEFAULT NULL,
  `class_group` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (12,'Edgars','Īlens','747474-22222','IPb24'),(13,'Aldis','Kārkliņš','747474-33338','IPb24'),(14,'Silvija','Varizeja','747474-44444','IPb24');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `category` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (61,'EIKT pamatprocesi un darbu veidi','PROF'),(62,'EIKT nozares tehnisko darbu pamatiemaņas','PROF'),(63,'Algoritmēšanas un programmēšanas pamati','PROF'),(64,'Preču un pakalpojumu izvēle EIKT infrastruktūras izveidei','PROF'),(65,'Programmas koda rakstīšana (Kodēšana)','PROF'),(66,'Programmas izstrādes process','PROF'),(67,'Datu bāzu tehnoloģijas','PROF'),(68,'Programmu uzturēšana','PROF'),(69,'Programmu projektēšana','PROF'),(70,'Matemātikas speciālās nodaļas','PROF'),(71,'Programmu testēšana','PROF'),(72,'Latviešu valoda I un Literatūra I','VIMP'),(73,'Matemātika I','VIMP'),(74,'Angļu valoda I','VIMP'),(75,'Sports','VIMP');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-27 11:00:01
