-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: gestion_wings
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
-- Table structure for table `alumno_planes`
--

DROP TABLE IF EXISTS `alumno_planes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alumno_planes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alumno_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `fecha_desde` date NOT NULL,
  `fecha_hasta` date DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_alumno_plan_activo` (`alumno_id`,`activo`),
  KEY `alumno_planes_plan_id_foreign` (`plan_id`),
  CONSTRAINT `alumno_planes_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alumno_planes_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `grupo_planes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alumno_planes`
--

LOCK TABLES `alumno_planes` WRITE;
/*!40000 ALTER TABLE `alumno_planes` DISABLE KEYS */;
INSERT INTO `alumno_planes` VALUES (1,1,2,'2026-01-05',NULL,1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(2,2,5,'2026-01-18',NULL,1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(3,3,1,'2026-01-27',NULL,1,'2026-02-11 17:47:42','2026-02-11 17:47:42');
/*!40000 ALTER TABLE `alumno_planes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alumnos`
--

DROP TABLE IF EXISTS `alumnos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alumnos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(255) NOT NULL,
  `dni` varchar(255) DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `celular` varchar(255) NOT NULL,
  `nombre_tutor` varchar(255) DEFAULT NULL,
  `telefono_tutor` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `deporte_id` bigint(20) unsigned NOT NULL,
  `grupo_id` bigint(20) unsigned NOT NULL,
  `fecha_alta` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alumnos_dni_deporte_unique` (`dni`,`deporte_id`),
  KEY `alumnos_deporte_id_foreign` (`deporte_id`),
  KEY `alumnos_grupo_id_foreign` (`grupo_id`),
  CONSTRAINT `alumnos_deporte_id_foreign` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alumnos_grupo_id_foreign` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alumnos`
--

LOCK TABLES `alumnos` WRITE;
/*!40000 ALTER TABLE `alumnos` DISABLE KEYS */;
INSERT INTO `alumnos` VALUES (1,'Juan','Pérez',NULL,'2001-02-11','555-0001',NULL,NULL,'juan@example.com',1,1,'2026-01-05',1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(2,'María','González',NULL,'2006-02-11','555-0002',NULL,NULL,'maria@example.com',1,2,'2026-01-18',1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(3,'Carlos','Rodríguez',NULL,'2011-02-11','555-0003','Roberto Rodríguez','555-0004',NULL,1,1,'2026-01-27',1,'2026-02-11 17:47:42','2026-02-11 17:47:42');
/*!40000 ALTER TABLE `alumnos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alumnos_revision_cobranza`
--

DROP TABLE IF EXISTS `alumnos_revision_cobranza`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alumnos_revision_cobranza` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alumno_id` bigint(20) unsigned NOT NULL,
  `periodo_objetivo` varchar(7) NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `estado_revision` enum('PENDIENTE','RESUELTO') NOT NULL DEFAULT 'PENDIENTE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `revision_alumno_periodo_unique` (`alumno_id`,`periodo_objetivo`),
  CONSTRAINT `alumnos_revision_cobranza_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alumnos_revision_cobranza`
--

LOCK TABLES `alumnos_revision_cobranza` WRITE;
/*!40000 ALTER TABLE `alumnos_revision_cobranza` DISABLE KEYS */;
INSERT INTO `alumnos_revision_cobranza` VALUES (1,1,'2026-04','SIN_ASISTENCIAS_NI_PAGO_PERIODO_ANTERIOR','PENDIENTE','2026-02-11 18:29:34','2026-02-11 18:29:34'),(2,3,'2026-04','SIN_ASISTENCIAS_NI_PAGO_PERIODO_ANTERIOR','PENDIENTE','2026-02-11 18:29:34','2026-02-11 18:29:34');
/*!40000 ALTER TABLE `alumnos_revision_cobranza` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asistencia_excesos`
--

DROP TABLE IF EXISTS `asistencia_excesos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asistencia_excesos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asistencia_id` bigint(20) unsigned NOT NULL,
  `alumno_id` bigint(20) unsigned NOT NULL,
  `fecha_clase` date NOT NULL,
  `motivo` enum('EXTRA','RECUPERA') NOT NULL,
  `detalle` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asistencia_excesos_asistencia_id_unique` (`asistencia_id`),
  KEY `asistencia_excesos_alumno_id_index` (`alumno_id`),
  CONSTRAINT `asistencia_excesos_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asistencia_excesos_asistencia_id_foreign` FOREIGN KEY (`asistencia_id`) REFERENCES `asistencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asistencia_excesos`
--

LOCK TABLES `asistencia_excesos` WRITE;
/*!40000 ALTER TABLE `asistencia_excesos` DISABLE KEYS */;
/*!40000 ALTER TABLE `asistencia_excesos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asistencias`
--

DROP TABLE IF EXISTS `asistencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asistencias` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clase_id` bigint(20) unsigned NOT NULL,
  `alumno_id` bigint(20) unsigned NOT NULL,
  `presente` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asistencia_clase_alumno_unique` (`clase_id`,`alumno_id`),
  KEY `asistencias_alumno_id_foreign` (`alumno_id`),
  CONSTRAINT `asistencias_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `asistencias_clase_id_foreign` FOREIGN KEY (`clase_id`) REFERENCES `clases` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asistencias`
--

LOCK TABLES `asistencias` WRITE;
/*!40000 ALTER TABLE `asistencias` DISABLE KEYS */;
/*!40000 ALTER TABLE `asistencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cajas_operativas`
--

DROP TABLE IF EXISTS `cajas_operativas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cajas_operativas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_operativo_id` bigint(20) unsigned NOT NULL,
  `apertura_at` datetime NOT NULL,
  `cierre_at` datetime DEFAULT NULL,
  `estado` enum('ABIERTA','CERRADA','VALIDADA','RECHAZADA') NOT NULL DEFAULT 'ABIERTA',
  `cerrada_por_admin` tinyint(1) NOT NULL DEFAULT 0,
  `usuario_admin_cierre_id` bigint(20) unsigned DEFAULT NULL,
  `usuario_admin_validacion_id` bigint(20) unsigned DEFAULT NULL,
  `validada_at` datetime DEFAULT NULL,
  `motivo_rechazo` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cajas_operativas_usuario_operativo_id_foreign` (`usuario_operativo_id`),
  KEY `cajas_operativas_usuario_admin_cierre_id_foreign` (`usuario_admin_cierre_id`),
  KEY `cajas_operativas_usuario_admin_validacion_id_foreign` (`usuario_admin_validacion_id`),
  CONSTRAINT `cajas_operativas_usuario_admin_cierre_id_foreign` FOREIGN KEY (`usuario_admin_cierre_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cajas_operativas_usuario_admin_validacion_id_foreign` FOREIGN KEY (`usuario_admin_validacion_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cajas_operativas_usuario_operativo_id_foreign` FOREIGN KEY (`usuario_operativo_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cajas_operativas`
--

LOCK TABLES `cajas_operativas` WRITE;
/*!40000 ALTER TABLE `cajas_operativas` DISABLE KEYS */;
/*!40000 ALTER TABLE `cajas_operativas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashflow_movimientos`
--

DROP TABLE IF EXISTS `cashflow_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cashflow_movimientos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `subrubro_id` bigint(20) unsigned NOT NULL,
  `tipo_caja_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `usuario_admin_id` bigint(20) unsigned NOT NULL,
  `referencia_tipo` varchar(255) DEFAULT NULL,
  `referencia_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cashflow_movimientos_subrubro_id_foreign` (`subrubro_id`),
  KEY `cashflow_movimientos_usuario_admin_id_foreign` (`usuario_admin_id`),
  KEY `cashflow_movimientos_referencia_tipo_referencia_id_index` (`referencia_tipo`,`referencia_id`),
  KEY `cashflow_movimientos_tipo_caja_id_foreign` (`tipo_caja_id`),
  CONSTRAINT `cashflow_movimientos_subrubro_id_foreign` FOREIGN KEY (`subrubro_id`) REFERENCES `subrubros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cashflow_movimientos_tipo_caja_id_foreign` FOREIGN KEY (`tipo_caja_id`) REFERENCES `tipos_caja` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cashflow_movimientos_usuario_admin_id_foreign` FOREIGN KEY (`usuario_admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashflow_movimientos`
--

LOCK TABLES `cashflow_movimientos` WRITE;
/*!40000 ALTER TABLE `cashflow_movimientos` DISABLE KEYS */;
/*!40000 ALTER TABLE `cashflow_movimientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clase_profesor`
--

DROP TABLE IF EXISTS `clase_profesor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clase_profesor` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `clase_id` bigint(20) unsigned NOT NULL,
  `profesor_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clase_profesor_unique` (`clase_id`,`profesor_id`),
  KEY `clase_profesor_profesor_id_foreign` (`profesor_id`),
  CONSTRAINT `clase_profesor_clase_id_foreign` FOREIGN KEY (`clase_id`) REFERENCES `clases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clase_profesor_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clase_profesor`
--

LOCK TABLES `clase_profesor` WRITE;
/*!40000 ALTER TABLE `clase_profesor` DISABLE KEYS */;
/*!40000 ALTER TABLE `clase_profesor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clases`
--

DROP TABLE IF EXISTS `clases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clases` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grupo_id` bigint(20) unsigned NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `validada_para_liquidacion` tinyint(1) NOT NULL DEFAULT 0,
  `cancelada` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clases_grupo_id_foreign` (`grupo_id`),
  KEY `clases_fecha_horario_index` (`fecha`,`hora_inicio`,`hora_fin`),
  CONSTRAINT `clases_grupo_id_foreign` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clases`
--

LOCK TABLES `clases` WRITE;
/*!40000 ALTER TABLE `clases` DISABLE KEYS */;
/*!40000 ALTER TABLE `clases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deportes`
--

DROP TABLE IF EXISTS `deportes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deportes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `tipo_liquidacion` enum('HORA','COMISION') NOT NULL DEFAULT 'HORA',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deportes`
--

LOCK TABLES `deportes` WRITE;
/*!40000 ALTER TABLE `deportes` DISABLE KEYS */;
INSERT INTO `deportes` VALUES (1,'Fútbol','HORA',1,'2026-02-11 17:47:42','2026-02-11 17:47:42');
/*!40000 ALTER TABLE `deportes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deuda_cuotas`
--

DROP TABLE IF EXISTS `deuda_cuotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deuda_cuotas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alumno_id` bigint(20) unsigned NOT NULL,
  `periodo` varchar(7) NOT NULL,
  `monto_original` decimal(10,2) NOT NULL,
  `monto_pagado` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('PENDIENTE','PAGADA','CONDONADA','AJUSTADA') NOT NULL DEFAULT 'PENDIENTE',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deuda_cuotas_alumno_periodo_unique` (`alumno_id`,`periodo`),
  KEY `deuda_cuotas_alumno_id_periodo_index` (`alumno_id`,`periodo`),
  CONSTRAINT `deuda_cuotas_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deuda_cuotas`
--

LOCK TABLES `deuda_cuotas` WRITE;
/*!40000 ALTER TABLE `deuda_cuotas` DISABLE KEYS */;
INSERT INTO `deuda_cuotas` VALUES (1,1,'2026-02',28000.00,0.00,'PENDIENTE',NULL,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(2,1,'2026-03',28000.00,0.00,'PENDIENTE',NULL,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(3,2,'2026-02',28000.00,0.00,'PENDIENTE',NULL,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(4,2,'2026-03',28000.00,0.00,'PENDIENTE',NULL,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(5,3,'2026-02',28000.00,0.00,'PENDIENTE',NULL,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(6,3,'2026-03',28000.00,0.00,'PENDIENTE',NULL,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(11,2,'2026-04',500.00,0.00,'PENDIENTE',NULL,'2026-02-11 18:29:34','2026-02-11 18:29:34');
/*!40000 ALTER TABLE `deuda_cuotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `formas_pago`
--

DROP TABLE IF EXISTS `formas_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formas_pago` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `formas_pago`
--

LOCK TABLES `formas_pago` WRITE;
/*!40000 ALTER TABLE `formas_pago` DISABLE KEYS */;
INSERT INTO `formas_pago` VALUES (1,'Efectivo',1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(2,'Débito',1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(3,'Crédito',1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(4,'Transferencia',1,'2026-02-11 17:47:42','2026-02-11 17:47:42');
/*!40000 ALTER TABLE `formas_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupo_planes`
--

DROP TABLE IF EXISTS `grupo_planes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grupo_planes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `grupo_id` bigint(20) unsigned NOT NULL,
  `clases_por_semana` int(10) unsigned NOT NULL,
  `precio_mensual` decimal(10,2) unsigned NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_grupo_clases_activo` (`grupo_id`,`clases_por_semana`,`activo`),
  CONSTRAINT `grupo_planes_grupo_id_foreign` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupo_planes`
--

LOCK TABLES `grupo_planes` WRITE;
/*!40000 ALTER TABLE `grupo_planes` DISABLE KEYS */;
INSERT INTO `grupo_planes` VALUES (1,1,2,300.00,1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(2,1,3,400.00,1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(3,1,5,600.00,1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(4,2,2,400.00,1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(5,2,3,500.00,1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(6,2,5,800.00,1,'2026-02-11 17:47:42','2026-02-11 17:47:42');
/*!40000 ALTER TABLE `grupo_planes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupos`
--

DROP TABLE IF EXISTS `grupos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grupos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `deporte_id` bigint(20) unsigned NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grupos_deporte_id_foreign` (`deporte_id`),
  CONSTRAINT `grupos_deporte_id_foreign` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupos`
--

LOCK TABLES `grupos` WRITE;
/*!40000 ALTER TABLE `grupos` DISABLE KEYS */;
INSERT INTO `grupos` VALUES (1,'Fútbol Principiantes',1,1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(2,'Fútbol Avanzados',1,1,'2026-02-11 17:47:42','2026-02-11 17:47:42');
/*!40000 ALTER TABLE `grupos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `liquidacion_detalles`
--

DROP TABLE IF EXISTS `liquidacion_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `liquidacion_detalles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `liquidacion_id` bigint(20) unsigned NOT NULL,
  `tipo_referencia` varchar(20) NOT NULL,
  `referencia_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `liquidacion_detalles_liquidacion_id_index` (`liquidacion_id`),
  KEY `liquidacion_detalles_tipo_referencia_referencia_id_index` (`tipo_referencia`,`referencia_id`),
  CONSTRAINT `liquidacion_detalles_liquidacion_id_foreign` FOREIGN KEY (`liquidacion_id`) REFERENCES `liquidaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `liquidacion_detalles`
--

LOCK TABLES `liquidacion_detalles` WRITE;
/*!40000 ALTER TABLE `liquidacion_detalles` DISABLE KEYS */;
/*!40000 ALTER TABLE `liquidacion_detalles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `liquidaciones`
--

DROP TABLE IF EXISTS `liquidaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `liquidaciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `profesor_id` bigint(20) unsigned NOT NULL,
  `mes` tinyint(3) unsigned NOT NULL,
  `anio` smallint(5) unsigned NOT NULL,
  `tipo` enum('HORA','COMISION') NOT NULL,
  `total_calculado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado` enum('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA',
  `estado_pago` enum('PENDIENTE','PAGADA') NOT NULL DEFAULT 'PENDIENTE',
  `pagada_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pagada_por_admin_id` bigint(20) unsigned DEFAULT NULL,
  `pagada_fecha` date DEFAULT NULL,
  `pagada_tipo_caja_id` bigint(20) unsigned DEFAULT NULL,
  `pagada_subrubro_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `liquidaciones_profesor_id_mes_anio_unique` (`profesor_id`,`mes`,`anio`),
  KEY `liquidaciones_mes_anio_index` (`mes`,`anio`),
  KEY `liquidaciones_estado_index` (`estado`),
  KEY `liquidaciones_pagada_por_admin_id_foreign` (`pagada_por_admin_id`),
  KEY `liquidaciones_pagada_tipo_caja_id_foreign` (`pagada_tipo_caja_id`),
  KEY `liquidaciones_pagada_subrubro_id_foreign` (`pagada_subrubro_id`),
  CONSTRAINT `liquidaciones_pagada_por_admin_id_foreign` FOREIGN KEY (`pagada_por_admin_id`) REFERENCES `users` (`id`),
  CONSTRAINT `liquidaciones_pagada_subrubro_id_foreign` FOREIGN KEY (`pagada_subrubro_id`) REFERENCES `subrubros` (`id`),
  CONSTRAINT `liquidaciones_pagada_tipo_caja_id_foreign` FOREIGN KEY (`pagada_tipo_caja_id`) REFERENCES `tipos_caja` (`id`),
  CONSTRAINT `liquidaciones_profesor_id_foreign` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `liquidaciones`
--

LOCK TABLES `liquidaciones` WRITE;
/*!40000 ALTER TABLE `liquidaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `liquidaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_01_12_021412_create_deportes_table',1),(5,'2026_01_12_021419_create_grupos_table',1),(6,'2026_01_12_021505_create_alumnos_table',1),(7,'2026_01_12_023548_create_personal_access_tokens_table',1),(8,'2026_01_12_032445_remove_es_menor_from_alumnos_table',1),(9,'2026_01_12_034430_remove_horario_from_grupos_table',1),(10,'2026_01_12_034453_create_grupo_planes_table',1),(11,'2026_01_12_091852_create_reglas_primer_pago_table',1),(12,'2026_01_12_091900_create_formas_pago_table',1),(13,'2026_01_12_091912_create_pagos_table',1),(14,'2026_01_12_091959_create_alumno_planes_table',1),(15,'2026_01_25_115528_add_fecha_pago_to_pagos_table',1),(16,'2026_01_27_000001_create_profesores_table',1),(17,'2026_01_27_000002_create_clases_table',1),(18,'2026_01_27_000003_create_clase_profesor_table',1),(19,'2026_01_27_000004_create_asistencias_table',1),(20,'2026_01_28_000001_add_tipo_liquidacion_to_deportes_table',1),(21,'2026_01_28_000002_add_liquidacion_fields_to_profesores_table',1),(22,'2026_01_28_000003_add_liquidacion_fields_to_clases_table',1),(23,'2026_01_28_000004_create_liquidaciones_table',1),(24,'2026_01_28_000005_create_liquidacion_detalles_table',1),(25,'2026_02_01_000001_add_dni_to_alumnos_table',1),(26,'2026_02_01_000002_create_deuda_cuotas_table',1),(27,'2026_02_01_100001_create_rubros_table',1),(28,'2026_02_01_100002_create_subrubros_table',1),(29,'2026_02_01_100003_create_tipos_caja_table',1),(30,'2026_02_01_100004_create_cajas_operativas_table',1),(31,'2026_02_01_100005_create_movimientos_operativos_table',1),(32,'2026_02_01_100006_create_cashflow_movimientos_table',1),(33,'2026_02_01_100007_add_motivo_rechazo_to_cajas_operativas_table',1),(34,'2026_02_02_000001_add_observaciones_to_deuda_cuotas_table',1),(35,'2026_02_02_000001_add_tipo_caja_id_to_cashflow_movimientos_table',1),(36,'2026_02_02_000002_create_pago_deuda_cuota_table',1),(37,'2026_02_02_000003_add_es_reservado_sistema_to_subrubros_table',1),(38,'2026_02_03_000001_add_pago_fields_to_liquidaciones_table',1),(39,'2026_02_06_063616_add_rol_to_users_table',1),(40,'2026_02_11_000001_fix_pagos_nullable_for_cuota_flow',2),(41,'2026_02_11_000002_add_unique_nombre_to_subrubros_table',2),(42,'2026_02_12_000001_create_alumnos_revision_cobranza_table',2),(43,'2026_02_12_000002_create_asistencia_excesos_table',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `movimientos_operativos`
--

DROP TABLE IF EXISTS `movimientos_operativos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_operativos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `caja_operativa_id` bigint(20) unsigned NOT NULL,
  `fecha` date DEFAULT NULL,
  `tipo_caja_id` bigint(20) unsigned NOT NULL,
  `subrubro_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `usuario_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `movimientos_operativos_caja_operativa_id_foreign` (`caja_operativa_id`),
  KEY `movimientos_operativos_tipo_caja_id_foreign` (`tipo_caja_id`),
  KEY `movimientos_operativos_subrubro_id_foreign` (`subrubro_id`),
  KEY `movimientos_operativos_usuario_id_foreign` (`usuario_id`),
  CONSTRAINT `movimientos_operativos_caja_operativa_id_foreign` FOREIGN KEY (`caja_operativa_id`) REFERENCES `cajas_operativas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_operativos_subrubro_id_foreign` FOREIGN KEY (`subrubro_id`) REFERENCES `subrubros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_operativos_tipo_caja_id_foreign` FOREIGN KEY (`tipo_caja_id`) REFERENCES `tipos_caja` (`id`) ON DELETE CASCADE,
  CONSTRAINT `movimientos_operativos_usuario_id_foreign` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `movimientos_operativos`
--

LOCK TABLES `movimientos_operativos` WRITE;
/*!40000 ALTER TABLE `movimientos_operativos` DISABLE KEYS */;
/*!40000 ALTER TABLE `movimientos_operativos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pago_deuda_cuota`
--

DROP TABLE IF EXISTS `pago_deuda_cuota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pago_deuda_cuota` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pago_id` bigint(20) unsigned NOT NULL,
  `deuda_cuota_id` bigint(20) unsigned NOT NULL,
  `monto_aplicado` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pago_deuda_cuota_unique` (`pago_id`,`deuda_cuota_id`),
  KEY `pago_deuda_cuota_deuda_cuota_id_foreign` (`deuda_cuota_id`),
  CONSTRAINT `pago_deuda_cuota_deuda_cuota_id_foreign` FOREIGN KEY (`deuda_cuota_id`) REFERENCES `deuda_cuotas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pago_deuda_cuota_pago_id_foreign` FOREIGN KEY (`pago_id`) REFERENCES `pagos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pago_deuda_cuota`
--

LOCK TABLES `pago_deuda_cuota` WRITE;
/*!40000 ALTER TABLE `pago_deuda_cuota` DISABLE KEYS */;
/*!40000 ALTER TABLE `pago_deuda_cuota` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alumno_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned DEFAULT NULL,
  `regla_primer_pago_id` bigint(20) unsigned DEFAULT NULL,
  `mes` tinyint(3) unsigned NOT NULL,
  `anio` smallint(5) unsigned NOT NULL,
  `monto_base` decimal(10,2) NOT NULL,
  `porcentaje_aplicado` decimal(5,2) NOT NULL,
  `monto_final` decimal(10,2) NOT NULL,
  `forma_pago_id` bigint(20) unsigned DEFAULT NULL,
  `fecha_pago` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('pagado','parcial','adeuda','COMPLETADO') NOT NULL DEFAULT 'pagado',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagos_alumno_id_foreign` (`alumno_id`),
  KEY `pagos_plan_id_foreign` (`plan_id`),
  KEY `pagos_regla_primer_pago_id_foreign` (`regla_primer_pago_id`),
  KEY `pagos_forma_pago_id_foreign` (`forma_pago_id`),
  CONSTRAINT `pagos_alumno_id_foreign` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pagos_forma_pago_id_foreign` FOREIGN KEY (`forma_pago_id`) REFERENCES `formas_pago` (`id`),
  CONSTRAINT `pagos_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `grupo_planes` (`id`),
  CONSTRAINT `pagos_regla_primer_pago_id_foreign` FOREIGN KEY (`regla_primer_pago_id`) REFERENCES `reglas_primer_pago` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
INSERT INTO `pagos` VALUES (1,1,NULL,NULL,8,2025,20000.00,100.00,20000.00,NULL,'2025-08-15',NULL,'COMPLETADO','2026-02-11 18:29:34','2026-02-11 18:29:34'),(2,2,NULL,NULL,3,2026,25000.00,100.00,25000.00,NULL,'2026-03-10',NULL,'COMPLETADO','2026-02-11 18:29:34','2026-02-11 18:29:34');
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',1,'test','2f7f4f8c0c39a24b94997bc00f1dbd20ab82d8b6cf284a068a35be07752bd7e2','[\"*\"]',NULL,NULL,'2026-02-11 22:12:27','2026-02-11 22:12:27'),(2,'App\\Models\\User',2,'test','87120abf769768dc39b9e501cbe5da3c7ec9a498dae51f9683909ae0aa3cb863','[\"*\"]',NULL,NULL,'2026-02-11 22:12:27','2026-02-11 22:12:27'),(3,'App\\Models\\User',1,'auth-token','440c9e251c5036566129544357c1a8df8194eefd1c13097953a12f87dcc3ad42','[\"*\"]',NULL,NULL,'2026-02-11 22:13:13','2026-02-11 22:13:13');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `profesores`
--

DROP TABLE IF EXISTS `profesores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profesores` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deporte_id` bigint(20) unsigned DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `apellido` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(255) DEFAULT NULL,
  `valor_hora` decimal(10,2) DEFAULT NULL,
  `porcentaje_comision` decimal(5,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `profesores_deporte_id_foreign` (`deporte_id`),
  CONSTRAINT `profesores_deporte_id_foreign` FOREIGN KEY (`deporte_id`) REFERENCES `deportes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profesores`
--

LOCK TABLES `profesores` WRITE;
/*!40000 ALTER TABLE `profesores` DISABLE KEYS */;
/*!40000 ALTER TABLE `profesores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reglas_primer_pago`
--

DROP TABLE IF EXISTS `reglas_primer_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reglas_primer_pago` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `dia_desde` tinyint(3) unsigned NOT NULL,
  `dia_hasta` tinyint(3) unsigned NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reglas_primer_pago`
--

LOCK TABLES `reglas_primer_pago` WRITE;
/*!40000 ALTER TABLE `reglas_primer_pago` DISABLE KEYS */;
INSERT INTO `reglas_primer_pago` VALUES (1,'Primera quincena (1-15)',1,15,100.00,1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(2,'Segunda quincena (16-23)',16,23,70.00,1,'2026-02-11 17:47:42','2026-02-11 17:47:42'),(3,'Fin de mes (24-31)',24,31,40.00,1,'2026-02-11 17:47:42','2026-02-11 17:47:42');
/*!40000 ALTER TABLE `reglas_primer_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rubros`
--

DROP TABLE IF EXISTS `rubros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rubros` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `tipo` enum('INGRESO','EGRESO') NOT NULL,
  `observacion` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rubros`
--

LOCK TABLES `rubros` WRITE;
/*!40000 ALTER TABLE `rubros` DISABLE KEYS */;
INSERT INTO `rubros` VALUES (1,'Cuotas','INGRESO','Cobro de cuotas mensuales de alumnos','2026-02-11 17:47:48','2026-02-11 17:47:48'),(2,'Ingresos Varios','INGRESO','Otros ingresos operativos (inscripciones, torneos, etc.)','2026-02-11 17:47:48','2026-02-11 17:47:48'),(3,'Intereses','INGRESO','Intereses generados por cuentas bancarias o plataformas de pago','2026-02-11 17:47:48','2026-02-11 17:47:48'),(4,'Sueldos','EGRESO','Pagos al personal docente y administrativo','2026-02-11 17:47:48','2026-02-11 17:47:48'),(5,'Servicios','EGRESO','Pagos de servicios (luz, agua, internet, alquiler)','2026-02-11 17:47:48','2026-02-11 17:47:48'),(6,'Gastos Operativos','EGRESO','Gastos menores del día a día (limpieza, librería, insumos)','2026-02-11 17:47:48','2026-02-11 17:47:48');
/*!40000 ALTER TABLE `rubros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('1NI5smLhZUu3YhLLWQMqv1jID2WOdhM4pFz53an7',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVWp0ZnhjeGlobTRwUFdFdE11emVHYUZ5MGhnY0ZCRDY1WlAxak5sUCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9hZG1pbi9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6MTU6ImFkbWluLmRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837151),('2wY6IK73bPsQZCt2TrpLCfigAWr3R19EL68GtoMQ',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoidXZIVFU2ZERTY3pENEdWTUhBTHpaMmZ3NlhkWmFKVVBaUEVwOFA0NyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837121),('4cHPZYjclU7EkGF1bLsw8X1hjzYD6OmphD3jyerY',NULL,'127.0.0.1','curl/8.14.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoia2lUYW1mbmpOWjQ1dVNkOWthOE52Wk9FdzhoVUlRT25uTzdYVnFjVyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyNToiaHR0cDovL2dlc3Rpb24td2luZ3MvY2FqYSI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjI1OiJodHRwOi8vZ2VzdGlvbi13aW5ncy9jYWphIjtzOjU6InJvdXRlIjtzOjE0OiJvcGVyYXRpdm8uY2FqYSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770838949),('A3aK2yZTDrlW86cKAntG4NiXloG8mnFLnPbBbjJC',NULL,'127.0.0.1','curl/8.14.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiYXVWVzNkVEwyZUZkUzhVQWFwdkZzN2dZZEZOeXBJTGdXUlA1ZkRUZyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozNjoiaHR0cDovL2dlc3Rpb24td2luZ3MvYWRtaW4vZGFzaGJvYXJkIjt9czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzY6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2FkbWluL2Rhc2hib2FyZCI7czo1OiJyb3V0ZSI7czoxNToiYWRtaW4uZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1770838948),('con66Oi0SZdTNfpWWLzu9hlPRyQjwyyB28RheLCT',2,'127.0.0.1','curl/8.14.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOE9hNTIwMVpQbjVPTGtPRkRZYUFRYk11SHZETFpHY0tDQ2hob3pXYSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2FsdW1ub3MvY3JlYXRlIjtzOjU6InJvdXRlIjtzOjE4OiJ3ZWIuYWx1bW5vcy5jcmVhdGUiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=',1770844630),('EmG8J54708kB4jd2S39W1JDSl518t5pp3vAo0tzy',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoicG83UHdOZTRHY1d5WVBFdWpMWWVqZjlyTXBLU3p6OXphcXFmUnJJcCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9jYWphIjtzOjU6InJvdXRlIjtzOjE0OiJvcGVyYXRpdm8uY2FqYSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837153),('GcI47Iq6IGYotwkSzRZgGOLElxnhc9IuCqBWA0x7',1,'127.0.0.1','curl/8.14.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMUQwSlNFTGdNWnRaRG1CSVJDNUpUcTlrbUt2c2JtSExyMkVtMXZOdiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1770838990),('IvsOI8oLhwLQGQdfwwO4WA2ApjwfHn8pg2ZrD3Cl',NULL,'127.0.0.1','curl/8.14.1','YTo1OntzOjY6Il90b2tlbiI7czo0MDoidUVVUG4zeHVteUhGMVRVRmxEYUtVWjg4TTFYUldiSWJEc2w1N2tWSSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6Mjp7aTowO3M6NjoiZXJyb3JzIjtpOjE7czoxMDoiX29sZF9pbnB1dCI7fXM6MzoibmV3IjthOjA6e319czo2OiJlcnJvcnMiO086MzE6IklsbHVtaW5hdGVcU3VwcG9ydFxWaWV3RXJyb3JCYWciOjE6e3M6NzoiACoAYmFncyI7YToxOntzOjc6ImRlZmF1bHQiO086Mjk6IklsbHVtaW5hdGVcU3VwcG9ydFxNZXNzYWdlQmFnIjoyOntzOjExOiIAKgBtZXNzYWdlcyI7YToxOntzOjU6ImVtYWlsIjthOjE6e2k6MDtzOjQ0OiJMYXMgY3JlZGVuY2lhbGVzIGluZ3Jlc2FkYXMgbm8gc29uIHbDoWxpZGFzLiI7fX1zOjk6IgAqAGZvcm1hdCI7czo4OiI6bWVzc2FnZSI7fX19czoxMDoiX29sZF9pbnB1dCI7YToxOntzOjU6ImVtYWlsIjtzOjE1OiJhZG1pbkB3aW5ncy5jb20iO319',1770839034),('iYIeLEwP9F02DkRnIgG8zWmxIpYDnFDeHSAor1W7',1,'127.0.0.1','curl/8.14.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoidmxYV2Jqa0RxUUVqOWtDalBleG9VTjdTNXFXMEh6ZDFuMXpta2xzbCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2NhamEiO3M6NToicm91dGUiO3M6MTQ6Im9wZXJhdGl2by5jYWphIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1770839007),('JR0MuxzXNbjtQdSH19w8ZAgpBe6dtxXwy6mOw2wl',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTWFCOFFUYTdMZ3lSb3drT1dRVTBpd216Z1hOUXFnMVViTWYxSEd5biI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1770838946),('LO3SVijL7GCUxyQ2fmRdBUfjnqVFv7PZjmKSd25F',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoidHJ6QWJMNUYxM3NmTG1Ub2g0S1BTN1F3d1lkR3NOamk3WDN5Znl1OSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9sb2dpbiI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837116),('lxZ8Q8ALjtqVyPhPChukDWII16Zyos0k2DNPa27h',1,'127.0.0.1','curl/8.14.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZTg4cFJIVFhwenFNUG9TT3hOOEttSEVmYWI1aDhpcHdSMG9jazdPbiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2FsdW1ub3M/c2VhcmNoPXRlc3QiO3M6NToicm91dGUiO3M6MTc6IndlYi5hbHVtbm9zLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1770844648),('mfwTG4d5uXAX4ysbo0gWC8j3xiUTZeFYU4KqMKDX',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiemtQNWpDUHhndnRwYkxzb2ljZXB2YWtLb0JDV0VTaWh2S2hpSHg0QyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9hZG1pbi9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6MTU6ImFkbWluLmRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837126),('mOM4Sc3ppWxvwNS4VjPfwIApiDTVedCeqWP33tTW',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiN0M2YXNPQTc0ejFudXIyOXprRmdFT3lDMTA5UWlZZ3cxREU0OTVTUCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1770838561),('N5yzPPri8dhzICugy5vEvjbL41QYv0YOarWKkdtz',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMzdoWjNpUWpRMWN5SUhiNWxpVVFEUlZiVXFyRHFQakkxcnBmMW04VSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1770838524),('nhGwOEoxzzropihuy2rbJJbLgSyNqkdTs6mupv0i',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiclJ2WEphckhGczNBdllOVU1pVmdpNk9jOHB0aFRIVnpiSFI2Ykp6NyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMSI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1770837125),('OFf0FOvwxtSj9cRWAUFrR0Stq2dYxNqXuxW5LP9O',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoibkJlN0FBcm5uOHlxTFE2S1Zyc0YwelhVcWdJTWJTbm1PSFVoYjV6ZyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9jYWphIjtzOjU6InJvdXRlIjtzOjE0OiJvcGVyYXRpdm8uY2FqYSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837184),('pQsnQjHU22Ch2vxnu3qtD0htuyodpYrJl8op487i',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYkI1YnhWQ0Z6b1VFaUJWWUlvZzd3Q3pqRXRrenFyUExiSVRPNDByTCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9jYWphIjtzOjU6InJvdXRlIjtzOjE0OiJvcGVyYXRpdm8uY2FqYSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837153),('qbSNuxJ6CR0QkPisKPZEdyaU7roB24w5GRWwQKDD',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoicDI3dGFoQlFxZWFHYm9VNlpyUnJKR1JzSm5qdmtUdU54MDBjWUxGZSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9hZG1pbi9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6MTU6ImFkbWluLmRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837183),('SBxEF63e0W7jx0nh634FizFYJoLNeKOCRWWqJTqy',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZTNZck9KV3FDTm5vTkxvSUtWZWpvdzN5S1kyczJKMFM2bGx5VW84QyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9hZG1pbi9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6MTU6ImFkbWluLmRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837188),('Sfa4q995pz5PIafdson2XJuZG9iPw99MtKYo6Aus',2,'127.0.0.1','curl/8.14.1','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNUNYNDRCdXJtTWpNRnlzVlZLdGtRbWUxUTQwaDhUcjRpREFmNlBQQiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2NhamEiO3M6NToicm91dGUiO3M6MTQ6Im9wZXJhdGl2by5jYWphIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6Mjt9',1770839021),('UrsmksJxnsMPZphY3YXH7LctKw0vN3RFERjHlPhT',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiblROMG1rQWhrbk5ldE9jeW1uVnk5OU9QN0R3YzdFb0FmRXZXdk5GbyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9jYWphIjtzOjU6InJvdXRlIjtzOjE0OiJvcGVyYXRpdm8uY2FqYSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837187),('X6j1J3xcsDUqvNDj1UomGQbLVSd93n2bvuSYAHm6',1,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36','YTo0OntzOjY6Il90b2tlbiI7czo0MDoicGRIcXVuM3J4VWNHWlhMUUljc1p0d2wyV2RZZEJYRVlPTHJ4REJwSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2FsdW1ub3MvMSI7czo1OiJyb3V0ZSI7czoxNjoid2ViLmFsdW1ub3Muc2hvdyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==',1770845901),('Xbdhzmjh2huWTv138joROOrGnKve66kDE4CwcV8h',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVGlwcjU5eGZ0N0xvaGt4VnliTDlwcUJLcllmZDBXT1pDWHNCUGIyaiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1770838947),('y0FKgK2J6pZpiZW9BS5Ddxyrwx2B64pSG82JYQwu',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYWdEMkIwNVJ5M2Q1a29XZmJDRUFoVVh6MDJWNFdVdjhVdG1adWdjUiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mzc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9hZG1pbi9kYXNoYm9hcmQiO3M6NToicm91dGUiO3M6MTU6ImFkbWluLmRhc2hib2FyZCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837155),('yOiDqXWEo3YjJTd6fUe0MbWvtEIpN1qnWOWteDb0',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoid0dWVlYyckwzSXhOVUZWU3lRaXNhUTFCcmh1Q2dmYThmSnNuSGxTUyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly9nZXN0aW9uLXdpbmdzL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1770838946),('z9ucf08mhVOTt0WyjkZlZEiK4CbKO07RTk3pHtmY',NULL,'127.0.0.1','curl/8.14.1','YTozOntzOjY6Il90b2tlbiI7czo0MDoidEhUWGdpekRhZkhKWmVrR3J2Wk5aZnRBcGlEV2d6eHkweG9yMlVuUyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMS9jYWphIjtzOjU6InJvdXRlIjtzOjE0OiJvcGVyYXRpdm8uY2FqYSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1770837127);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subrubros`
--

DROP TABLE IF EXISTS `subrubros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subrubros` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rubro_id` bigint(20) unsigned NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `permitido_para` enum('ADMIN','OPERATIVO') NOT NULL,
  `afecta_caja` tinyint(1) NOT NULL DEFAULT 1,
  `es_reservado_sistema` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subrubros_nombre_unique` (`nombre`),
  KEY `subrubros_rubro_id_foreign` (`rubro_id`),
  CONSTRAINT `subrubros_rubro_id_foreign` FOREIGN KEY (`rubro_id`) REFERENCES `rubros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subrubros`
--

LOCK TABLES `subrubros` WRITE;
/*!40000 ALTER TABLE `subrubros` DISABLE KEYS */;
INSERT INTO `subrubros` VALUES (1,1,'Cuota Mensual','OPERATIVO',1,1,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(2,2,'Inscripción Torneo','OPERATIVO',1,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(3,2,'Venta de Indumentaria','OPERATIVO',1,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(4,3,'Intereses Mercado Pago','ADMIN',0,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(5,3,'Intereses Banco','ADMIN',0,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(6,4,'Sueldo Patín - Romina','ADMIN',0,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(7,4,'Sueldo Hockey - Lucas','ADMIN',0,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(8,4,'Sueldo Administrativo','ADMIN',0,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(9,5,'Alquiler','ADMIN',0,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(10,5,'Luz','ADMIN',0,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(11,5,'Internet','ADMIN',0,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(12,6,'Limpieza','OPERATIVO',1,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(13,6,'Librería','OPERATIVO',1,0,'2026-02-11 17:47:48','2026-02-11 17:47:48'),(14,6,'Insumos Varios','OPERATIVO',1,0,'2026-02-11 17:47:48','2026-02-11 17:47:48');
/*!40000 ALTER TABLE `subrubros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_caja`
--

DROP TABLE IF EXISTS `tipos_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_caja` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_caja`
--

LOCK TABLES `tipos_caja` WRITE;
/*!40000 ALTER TABLE `tipos_caja` DISABLE KEYS */;
/*!40000 ALTER TABLE `tipos_caja` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `rol` enum('ADMIN','OPERATIVO') NOT NULL DEFAULT 'OPERATIVO',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Test','admin@wings.com','ADMIN',NULL,'$2y$12$0Cjs7PhavxwbhgigME3J/.j6DjxtEV9ZiN3DtgTj3SRUzE2rBzPM2',NULL,'2026-02-11 22:12:27','2026-02-11 22:12:27'),(2,'Operativo Test','operativo@wings.com','OPERATIVO',NULL,'$2y$12$wv2F.7zji1xv3eyREQfdYOKIK3Z8zDWLTKZErKiVmVA3miNOV6kMS',NULL,'2026-02-11 22:12:27','2026-02-11 22:12:27');
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

-- Dump completed on 2026-02-11 20:36:26
