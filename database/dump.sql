
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `alumno_planes` WRITE;
/*!40000 ALTER TABLE `alumno_planes` DISABLE KEYS */;
/*!40000 ALTER TABLE `alumno_planes` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `alumnos` WRITE;
/*!40000 ALTER TABLE `alumnos` DISABLE KEYS */;
/*!40000 ALTER TABLE `alumnos` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `asistencias` WRITE;
/*!40000 ALTER TABLE `asistencias` DISABLE KEYS */;
/*!40000 ALTER TABLE `asistencias` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cajas_operativas` WRITE;
/*!40000 ALTER TABLE `cajas_operativas` DISABLE KEYS */;
INSERT INTO `cajas_operativas` (`id`, `usuario_operativo_id`, `apertura_at`, `cierre_at`, `estado`, `cerrada_por_admin`, `usuario_admin_cierre_id`, `usuario_admin_validacion_id`, `validada_at`, `motivo_rechazo`, `created_at`, `updated_at`) VALUES (1,1,'2026-02-03 01:35:31','2026-02-03 01:35:32','VALIDADA',0,NULL,1,'2026-02-03 01:35:49',NULL,'2026-02-03 04:35:31','2026-02-03 04:35:49');
/*!40000 ALTER TABLE `cajas_operativas` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cashflow_movimientos` WRITE;
/*!40000 ALTER TABLE `cashflow_movimientos` DISABLE KEYS */;
INSERT INTO `cashflow_movimientos` (`id`, `fecha`, `subrubro_id`, `tipo_caja_id`, `monto`, `observaciones`, `usuario_admin_id`, `referencia_tipo`, `referencia_id`, `created_at`, `updated_at`) VALUES (1,'2026-02-02',4,3,1500.00,'Rendimiento mensual MP',1,'SEED',NULL,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(2,'2026-02-02',6,2,45000.00,'Liquidación enero 2026',1,'SEED',NULL,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(3,'2026-02-02',9,2,80000.00,'Alquiler febrero 2026',1,'SEED',NULL,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(4,'2026-02-03',2,1,1000.00,'Test ingreso E1',1,'CAJA_OPERATIVA',1,'2026-02-03 04:35:49','2026-02-03 04:35:49'),(5,'2026-02-03',12,1,-300.00,'Test egreso E1',1,'CAJA_OPERATIVA',1,'2026-02-03 04:35:49','2026-02-03 04:35:49');
/*!40000 ALTER TABLE `cashflow_movimientos` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `clase_profesor` WRITE;
/*!40000 ALTER TABLE `clase_profesor` DISABLE KEYS */;
/*!40000 ALTER TABLE `clase_profesor` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `clases` WRITE;
/*!40000 ALTER TABLE `clases` DISABLE KEYS */;
/*!40000 ALTER TABLE `clases` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `deportes` WRITE;
/*!40000 ALTER TABLE `deportes` DISABLE KEYS */;
/*!40000 ALTER TABLE `deportes` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `deuda_cuotas` WRITE;
/*!40000 ALTER TABLE `deuda_cuotas` DISABLE KEYS */;
/*!40000 ALTER TABLE `deuda_cuotas` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `formas_pago` WRITE;
/*!40000 ALTER TABLE `formas_pago` DISABLE KEYS */;
INSERT INTO `formas_pago` (`id`, `nombre`, `activo`, `created_at`, `updated_at`) VALUES (1,'Efectivo',1,'2026-02-02 12:02:44','2026-02-02 12:02:44'),(2,'Débito',1,'2026-02-02 12:02:44','2026-02-02 12:02:44'),(3,'Crédito',1,'2026-02-02 12:02:44','2026-02-02 12:02:44'),(4,'Transferencia',1,'2026-02-02 12:02:44','2026-02-02 12:02:44');
/*!40000 ALTER TABLE `formas_pago` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `grupo_planes` WRITE;
/*!40000 ALTER TABLE `grupo_planes` DISABLE KEYS */;
/*!40000 ALTER TABLE `grupo_planes` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `grupos` WRITE;
/*!40000 ALTER TABLE `grupos` DISABLE KEYS */;
/*!40000 ALTER TABLE `grupos` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `liquidacion_detalles` WRITE;
/*!40000 ALTER TABLE `liquidacion_detalles` DISABLE KEYS */;
/*!40000 ALTER TABLE `liquidacion_detalles` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `liquidaciones` WRITE;
/*!40000 ALTER TABLE `liquidaciones` DISABLE KEYS */;
/*!40000 ALTER TABLE `liquidaciones` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_01_12_021412_create_deportes_table',1),(5,'2026_01_12_021419_create_grupos_table',1),(6,'2026_01_12_021505_create_alumnos_table',1),(7,'2026_01_12_023548_create_personal_access_tokens_table',1),(8,'2026_01_12_032445_remove_es_menor_from_alumnos_table',1),(9,'2026_01_12_034430_remove_horario_from_grupos_table',1),(10,'2026_01_12_034453_create_grupo_planes_table',1),(11,'2026_01_12_091852_create_reglas_primer_pago_table',1),(12,'2026_01_12_091900_create_formas_pago_table',1),(13,'2026_01_12_091912_create_pagos_table',1),(14,'2026_01_12_091959_create_alumno_planes_table',1),(15,'2026_01_25_115528_add_fecha_pago_to_pagos_table',1),(16,'2026_01_27_000001_create_profesores_table',1),(17,'2026_01_27_000002_create_clases_table',1),(18,'2026_01_27_000003_create_clase_profesor_table',1),(19,'2026_01_27_000004_create_asistencias_table',1),(20,'2026_01_28_000001_add_tipo_liquidacion_to_deportes_table',1),(21,'2026_01_28_000002_add_liquidacion_fields_to_profesores_table',1),(22,'2026_01_28_000003_add_liquidacion_fields_to_clases_table',1),(23,'2026_01_28_000004_create_liquidaciones_table',1),(24,'2026_01_28_000005_create_liquidacion_detalles_table',1),(25,'2026_02_01_000001_add_dni_to_alumnos_table',1),(26,'2026_02_01_000002_create_deuda_cuotas_table',1),(27,'2026_02_01_100001_create_rubros_table',1),(28,'2026_02_01_100002_create_subrubros_table',1),(29,'2026_02_01_100003_create_tipos_caja_table',1),(30,'2026_02_01_100004_create_cajas_operativas_table',1),(31,'2026_02_01_100005_create_movimientos_operativos_table',1),(32,'2026_02_01_100006_create_cashflow_movimientos_table',1),(33,'2026_02_01_100007_add_motivo_rechazo_to_cajas_operativas_table',1),(34,'2026_02_02_000001_add_tipo_caja_id_to_cashflow_movimientos_table',1),(35,'2026_02_02_000001_add_observaciones_to_deuda_cuotas_table',2),(36,'2026_02_02_000002_create_pago_deuda_cuota_table',2),(37,'2026_02_02_000003_add_es_reservado_sistema_to_subrubros_table',3),(38,'2026_02_03_000001_add_pago_fields_to_liquidaciones_table',4),(39,'2026_02_06_063616_add_rol_to_users_table',5);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `movimientos_operativos` WRITE;
/*!40000 ALTER TABLE `movimientos_operativos` DISABLE KEYS */;
INSERT INTO `movimientos_operativos` (`id`, `caja_operativa_id`, `fecha`, `tipo_caja_id`, `subrubro_id`, `monto`, `observaciones`, `usuario_id`, `created_at`, `updated_at`) VALUES (1,1,'2026-02-03',1,2,1000.00,'Test ingreso E1',1,'2026-02-03 04:35:32','2026-02-03 04:35:32'),(2,1,'2026-02-03',1,12,300.00,'Test egreso E1',1,'2026-02-03 04:35:32','2026-02-03 04:35:32');
/*!40000 ALTER TABLE `movimientos_operativos` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `pago_deuda_cuota` WRITE;
/*!40000 ALTER TABLE `pago_deuda_cuota` DISABLE KEYS */;
/*!40000 ALTER TABLE `pago_deuda_cuota` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alumno_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `regla_primer_pago_id` bigint(20) unsigned DEFAULT NULL,
  `mes` tinyint(3) unsigned NOT NULL,
  `anio` smallint(5) unsigned NOT NULL,
  `monto_base` decimal(10,2) NOT NULL,
  `porcentaje_aplicado` decimal(5,2) NOT NULL,
  `monto_final` decimal(10,2) NOT NULL,
  `forma_pago_id` bigint(20) unsigned NOT NULL,
  `fecha_pago` date NOT NULL,
  `observaciones` text DEFAULT NULL,
  `estado` enum('pagado','parcial','adeuda') NOT NULL DEFAULT 'pagado',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES (1,'App\\Models\\User',2,'auth-token','cfaffa0a4aed236efa48e6d93d79bfce8733540c57294bb47ff4422a7e04ee09','[\"*\"]',NULL,NULL,'2026-02-06 12:05:34','2026-02-06 12:05:34'),(2,'App\\Models\\User',2,'auth-token','f1853f7668053c52827f9154c00faf2d7a23f8a710faffed37e00a36c4731018','[\"*\"]','2026-02-06 12:05:51',NULL,'2026-02-06 12:05:49','2026-02-06 12:05:51'),(3,'App\\Models\\User',2,'auth-token','f023c45897aaf9c0883e12e427138a5d3a6f3f895b247d4146bfca503ef79154','[\"*\"]','2026-02-06 12:06:05',NULL,'2026-02-06 12:06:04','2026-02-06 12:06:05'),(4,'App\\Models\\User',2,'auth-token','b6df7ddf258a66ef83e00893724ff4974c10060a46da183b315b8aac6c6b2678','[\"*\"]','2026-02-06 12:06:19',NULL,'2026-02-06 12:06:18','2026-02-06 12:06:19'),(5,'App\\Models\\User',1,'auth-token','e492ae1e8d69e1c562583fdc06b77d771be737a82ac36fcde2c5101024dc4f67','[\"*\"]','2026-02-06 12:06:44',NULL,'2026-02-06 12:06:44','2026-02-06 12:06:44'),(6,'App\\Models\\User',2,'auth-token','01b3a5229549e5aabe7def5b236416e41263678a3a23d3c90aa0a62fe33afc13','[\"*\"]','2026-02-06 12:07:08',NULL,'2026-02-06 12:07:07','2026-02-06 12:07:08'),(7,'App\\Models\\User',2,'auth-token','a998aa9a42b5481cdfad2ca145adee3667fcb4d0f12ebfb1b18635da5321cebf','[\"*\"]','2026-02-06 12:08:43',NULL,'2026-02-06 12:08:43','2026-02-06 12:08:43'),(8,'App\\Models\\User',2,'auth-token','2433d474d2a9a55891706409485ba3af2518adc23116bcbd9532213f85d76269','[\"*\"]','2026-02-06 12:09:45',NULL,'2026-02-06 12:09:45','2026-02-06 12:09:45');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `profesores` WRITE;
/*!40000 ALTER TABLE `profesores` DISABLE KEYS */;
/*!40000 ALTER TABLE `profesores` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `reglas_primer_pago` WRITE;
/*!40000 ALTER TABLE `reglas_primer_pago` DISABLE KEYS */;
INSERT INTO `reglas_primer_pago` (`id`, `nombre`, `dia_desde`, `dia_hasta`, `porcentaje`, `activo`, `created_at`, `updated_at`) VALUES (1,'Primera quincena (1-15)',1,15,100.00,1,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(2,'Segunda quincena (16-23)',16,23,70.00,1,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(3,'Fin de mes (24-31)',24,31,40.00,1,'2026-02-02 12:02:45','2026-02-02 12:02:45');
/*!40000 ALTER TABLE `reglas_primer_pago` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rubros` WRITE;
/*!40000 ALTER TABLE `rubros` DISABLE KEYS */;
INSERT INTO `rubros` (`id`, `nombre`, `tipo`, `observacion`, `created_at`, `updated_at`) VALUES (1,'Cuotas','INGRESO','Cobro de cuotas mensuales de alumnos','2026-02-02 12:02:45','2026-02-02 12:02:45'),(2,'Ingresos Varios','INGRESO','Otros ingresos operativos (inscripciones, torneos, etc.)','2026-02-02 12:02:45','2026-02-02 12:02:45'),(3,'Intereses','INGRESO','Intereses generados por cuentas bancarias o plataformas de pago','2026-02-02 12:02:45','2026-02-02 12:02:45'),(4,'Sueldos','EGRESO','Pagos al personal docente y administrativo','2026-02-02 12:02:45','2026-02-02 12:02:45'),(5,'Servicios','EGRESO','Pagos de servicios (luz, agua, internet, alquiler)','2026-02-02 12:02:45','2026-02-02 12:02:45'),(6,'Gastos Operativos','EGRESO','Gastos menores del día a día (limpieza, librería, insumos)','2026-02-02 12:02:45','2026-02-02 12:02:45');
/*!40000 ALTER TABLE `rubros` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
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
  KEY `subrubros_rubro_id_foreign` (`rubro_id`),
  CONSTRAINT `subrubros_rubro_id_foreign` FOREIGN KEY (`rubro_id`) REFERENCES `rubros` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `subrubros` WRITE;
/*!40000 ALTER TABLE `subrubros` DISABLE KEYS */;
INSERT INTO `subrubros` (`id`, `rubro_id`, `nombre`, `permitido_para`, `afecta_caja`, `es_reservado_sistema`, `created_at`, `updated_at`) VALUES (1,1,'Cuota Mensual','OPERATIVO',1,1,'2026-02-02 12:02:45','2026-02-03 02:27:17'),(2,2,'Inscripción Torneo','OPERATIVO',1,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(3,2,'Venta de Indumentaria','OPERATIVO',1,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(4,3,'Intereses Mercado Pago','ADMIN',0,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(5,3,'Intereses Banco','ADMIN',0,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(6,4,'Sueldo Patín - Romina','ADMIN',0,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(7,4,'Sueldo Hockey - Lucas','ADMIN',0,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(8,4,'Sueldo Administrativo','ADMIN',0,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(9,5,'Alquiler','ADMIN',0,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(10,5,'Luz','ADMIN',0,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(11,5,'Internet','ADMIN',0,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(12,6,'Limpieza','OPERATIVO',1,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(13,6,'Librería','OPERATIVO',1,0,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(14,6,'Insumos Varios','OPERATIVO',1,0,'2026-02-02 12:02:45','2026-02-02 12:02:45');
/*!40000 ALTER TABLE `subrubros` ENABLE KEYS */;
UNLOCK TABLES;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `tipos_caja` WRITE;
/*!40000 ALTER TABLE `tipos_caja` DISABLE KEYS */;
INSERT INTO `tipos_caja` (`id`, `nombre`, `activo`, `created_at`, `updated_at`) VALUES (1,'Efectivo',1,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(2,'Banco',1,'2026-02-02 12:02:45','2026-02-02 12:02:45'),(3,'Mercado Pago',1,'2026-02-02 12:02:45','2026-02-02 12:02:45');
/*!40000 ALTER TABLE `tipos_caja` ENABLE KEYS */;
UNLOCK TABLES;
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

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `name`, `email`, `rol`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (1,'Test User','test@example.com','OPERATIVO','2026-02-02 12:02:44','$2y$12$WR/MQ7gi9AfFbdOgowRsU.cLhoRSJ9.cE6FKwIRP2iUbHHCnXgDam','uJt14MKmTk','2026-02-02 12:02:44','2026-02-02 12:02:44'),(2,'Admin Test','admin@test.com','ADMIN',NULL,'$2y$12$3g9tkGsS6WvgtTlteqr/MuJdR6PgrKBjfr7NcoCxzpzF5Gfy/9hSa',NULL,'2026-02-06 12:03:47','2026-02-06 12:03:47');
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

