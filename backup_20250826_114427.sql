-- MySQL dump 10.13  Distrib 8.0.43, for Linux (aarch64)
--
-- Host: localhost    Database: cakephp_db
-- ------------------------------------------------------
-- Server version	8.0.43

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
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_factura` varchar(50) NOT NULL,
  `fecha` date NOT NULL,
  `cliente` varchar(255) NOT NULL,
  `email_cliente` varchar(255) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','pagada','cancelada') DEFAULT 'pendiente',
  `descripcion` text,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_factura` (`numero_factura`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'FAC-001','2025-08-25','Empresa ABC S.R.L','contacto@empresaabc.com',100.00,21.00,121.00,'pendiente','Servicios de consultorÃ­a - Agosto 2025','2025-08-25 16:32:44','2025-08-25 19:21:30'),(2,'FAC-002','2025-08-24','Cliente Beta Ltd.','admin@clientebeta.com',250.00,52.50,302.50,'pagada','Desarrollo web - Proyecto Beta','2025-08-25 16:32:44','2025-08-25 16:32:44'),(3,'FAC-003','2025-08-23','Startup Gamma','info@startupgamma.com',150.00,31.50,181.50,'pendiente','DiseÃ±o de logotipo y branding','2025-08-25 16:32:44','2025-08-25 16:32:44'),(4,'FAC-004','2025-08-22','CorporaciÃ³n Delta','ventas@delta.com',300.00,63.00,363.00,'cancelada','Proyecto cancelado por el cliente','2025-08-25 16:32:44','2025-08-25 16:32:44'),(5,'FAC-005','2025-08-21','Negocio Epsilon','info@epsilon.es',75.50,15.86,91.36,'pagada','Mantenimiento web mensual','2025-08-25 16:32:44','2025-08-25 16:32:44'),(6,'FAC-006','2025-08-25','Salva Roma','contacto@empresaroma.com',134.43,28.23,162.66,'pendiente','Bolo','2025-08-25 19:22:35','2025-08-25 19:22:35');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-26  9:44:28
