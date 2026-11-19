-- MySQL dump 10.13  Distrib 8.0.26, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: pronee_doctor
-- ------------------------------------------------------
-- Server version	8.0.26

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned DEFAULT NULL,
  `organization_id` bigint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `phone` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Longitude` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'طول جغرافیایی\\\\n',
  `Latitude` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'عرض جغرافیایی\\\\n',
  `Altitude` int DEFAULT NULL COMMENT 'ارتفاع جغرافیایی',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `addresses_id_doctor_id_unique` (`id`,`doctor_id`),
  KEY `addresses_doctor_id_foreign` (`doctor_id`),
  KEY `addresses_city_id_index` (`city_id`),
  KEY `addresses_organization_id_is_active_index` (`organization_id`,`is_active`),
  CONSTRAINT `addresses_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `addresses_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `addresses_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `appointment_payments`
--

DROP TABLE IF EXISTS `appointment_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointment_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint unsigned NOT NULL,
  `amount_rial` bigint unsigned NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gateway_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_num` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `terminal_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sep',
  `gateway_result_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gateway_result_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `gateway_verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointment_payments_payment_uuid_unique` (`payment_uuid`),
  UNIQUE KEY `appointment_payments_gateway_ref_unique` (`gateway_name`,`ref_num`),
  KEY `appointment_payments_appointment_id_status_index` (`appointment_id`,`status`),
  KEY `appointment_payments_gateway_token_index` (`gateway_token`),
  KEY `appointment_payments_gateway_reference_index` (`ref_num`),
  CONSTRAINT `appointment_payments_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `visit_reason_id` bigint unsigned DEFAULT NULL,
  `surgery_type_id` bigint unsigned DEFAULT NULL,
  `surgery_date` date DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `doctor_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned DEFAULT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `time_slot_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `patient_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_national_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed','visited') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `visit_number` bigint unsigned DEFAULT NULL,
  `fee_rial` bigint unsigned NOT NULL DEFAULT '5000',
  `reservation_expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `appointments_visit_number_unique` (`visit_number`),
  KEY `appointments_clinic_id_foreign` (`clinic_id`),
  KEY `appointments_user_id_foreign` (`user_id`),
  KEY `appointments_doctor_id_appointment_date_index` (`doctor_id`,`appointment_date`),
  KEY `appointments_time_slot_id_status_index` (`time_slot_id`,`status`),
  KEY `appointments_visit_reason_id_foreign` (`visit_reason_id`),
  KEY `appointments_surgery_type_id_foreign` (`surgery_type_id`),
  KEY `appointments_clinic_doctor_fk` (`clinic_id`,`doctor_id`),
  KEY `appointments_slot_clinic_doctor_fk` (`time_slot_id`,`clinic_id`,`doctor_id`),
  KEY `appointments_visit_reason_doctor_fk` (`visit_reason_id`,`doctor_id`),
  KEY `appointments_surgery_type_doctor_index` (`surgery_type_id`,`doctor_id`),
  KEY `appointments_patient_id_foreign` (`patient_id`),
  KEY `appointments_doctor_patient_date_index` (`doctor_id`,`patient_id`,`appointment_date`),
  CONSTRAINT `appointments_clinic_doctor_fk` FOREIGN KEY (`clinic_id`, `doctor_id`) REFERENCES `addresses` (`id`, `doctor_id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_slot_clinic_doctor_fk` FOREIGN KEY (`time_slot_id`, `clinic_id`, `doctor_id`) REFERENCES `time_slots` (`id`, `clinic_id`, `doctor_id`) ON DELETE CASCADE,
  CONSTRAINT `appointments_surgery_type_doctor_fk` FOREIGN KEY (`surgery_type_id`, `doctor_id`) REFERENCES `surgery_types` (`id`, `doctor_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `appointments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `appointments_visit_reason_doctor_fk` FOREIGN KEY (`visit_reason_id`, `doctor_id`) REFERENCES `visit_reasons` (`id`, `doctor_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cities_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_brain_waitlist_entries`
--

DROP TABLE IF EXISTS `clinic_brain_waitlist_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_brain_waitlist_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned DEFAULT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `preferred_from` date NOT NULL,
  `preferred_until` date NOT NULL,
  `preferred_period` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'any',
  `contact_consent` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'waiting',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `offered_slot_id` bigint unsigned DEFAULT NULL,
  `offered_at` datetime DEFAULT NULL,
  `fulfilled_appointment_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_brain_waitlist_entries_clinic_id_foreign` (`clinic_id`),
  KEY `clinic_brain_waitlist_entries_created_by_foreign` (`created_by`),
  KEY `clinic_brain_waitlist_scope_idx` (`doctor_id`,`status`,`preferred_from`),
  KEY `clinic_brain_waitlist_patient_idx` (`patient_id`,`status`),
  KEY `clinic_brain_waitlist_entries_fulfilled_appointment_id_foreign` (`fulfilled_appointment_id`),
  KEY `waitlist_offer_slot_status_idx` (`offered_slot_id`,`status`),
  CONSTRAINT `clinic_brain_waitlist_entries_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_brain_waitlist_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_brain_waitlist_entries_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_brain_waitlist_entries_fulfilled_appointment_id_foreign` FOREIGN KEY (`fulfilled_appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_brain_waitlist_entries_offered_slot_id_foreign` FOREIGN KEY (`offered_slot_id`) REFERENCES `time_slots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_brain_waitlist_entries_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_economic_scenarios`
--

DROP TABLE IF EXISTS `clinic_economic_scenarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_economic_scenarios` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `scenario_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inputs` json NOT NULL,
  `results` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_economic_scenarios_clinic_id_foreign` (`clinic_id`),
  KEY `clinic_economic_scenarios_created_by_foreign` (`created_by`),
  KEY `ces_doctor_type_created_idx` (`doctor_id`,`scenario_type`,`created_at`),
  CONSTRAINT `clinic_economic_scenarios_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_economic_scenarios_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_economic_scenarios_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_expenses`
--

DROP TABLE IF EXISTS `clinic_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `category` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` bigint unsigned NOT NULL,
  `expense_date` date NOT NULL,
  `payment_method` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_expenses_clinic_id_foreign` (`clinic_id`),
  KEY `clinic_expenses_recorded_by_foreign` (`recorded_by`),
  KEY `clinic_expenses_doctor_id_clinic_id_expense_date_index` (`doctor_id`,`clinic_id`,`expense_date`),
  CONSTRAINT `clinic_expenses_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_expenses_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_expenses_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_financial_accounts`
--

DROP TABLE IF EXISTS `clinic_financial_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_financial_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned DEFAULT NULL,
  `clinic_id` bigint unsigned DEFAULT NULL,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope_key` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_financial_accounts_scope_key_unique` (`scope_key`),
  UNIQUE KEY `finance_account_scope_code_unique` (`organization_id`,`clinic_id`,`code`),
  KEY `clinic_financial_accounts_clinic_id_foreign` (`clinic_id`),
  CONSTRAINT `clinic_financial_accounts_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_financial_accounts_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_financial_ledger`
--

DROP TABLE IF EXISTS `clinic_financial_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_financial_ledger` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned DEFAULT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned DEFAULT NULL,
  `treatment_plan_id` bigint unsigned DEFAULT NULL,
  `installment_payment_id` bigint unsigned DEFAULT NULL,
  `expense_id` bigint unsigned DEFAULT NULL,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `entry_type` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `debit` bigint unsigned NOT NULL DEFAULT '0',
  `credit` bigint unsigned NOT NULL DEFAULT '0',
  `occurred_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_financial_ledger_clinic_id_foreign` (`clinic_id`),
  KEY `clinic_financial_ledger_treatment_plan_id_foreign` (`treatment_plan_id`),
  KEY `clinic_financial_ledger_installment_payment_id_foreign` (`installment_payment_id`),
  KEY `clinic_financial_ledger_expense_id_foreign` (`expense_id`),
  KEY `clinic_financial_ledger_recorded_by_foreign` (`recorded_by`),
  KEY `clinic_financial_ledger_doctor_id_clinic_id_occurred_at_index` (`doctor_id`,`clinic_id`,`occurred_at`),
  KEY `clinic_financial_ledger_patient_id_occurred_at_index` (`patient_id`,`occurred_at`),
  KEY `clinic_financial_ledger_organization_id_foreign` (`organization_id`),
  CONSTRAINT `clinic_financial_ledger_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_financial_ledger_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_financial_ledger_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `clinic_expenses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_financial_ledger_installment_payment_id_foreign` FOREIGN KEY (`installment_payment_id`) REFERENCES `patient_installment_payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_financial_ledger_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_financial_ledger_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_financial_ledger_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_financial_ledger_treatment_plan_id_foreign` FOREIGN KEY (`treatment_plan_id`) REFERENCES `patient_treatment_plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_health_memberships`
--

DROP TABLE IF EXISTS `clinic_health_memberships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_health_memberships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `program_id` bigint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `consent_at` timestamp NOT NULL,
  `consent_recorded_by` bigint unsigned NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_checkup_at` datetime DEFAULT NULL,
  `last_reminded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `health_membership_patient_program_unique` (`patient_id`,`program_id`),
  KEY `clinic_health_memberships_program_id_foreign` (`program_id`),
  KEY `clinic_health_memberships_consent_recorded_by_foreign` (`consent_recorded_by`),
  KEY `clinic_health_memberships_doctor_id_status_enrolled_at_index` (`doctor_id`,`status`,`enrolled_at`),
  CONSTRAINT `clinic_health_memberships_consent_recorded_by_foreign` FOREIGN KEY (`consent_recorded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_health_memberships_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_health_memberships_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_health_memberships_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `clinic_health_programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_health_points`
--

DROP TABLE IF EXISTS `clinic_health_points`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_health_points` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `membership_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `points` smallint unsigned NOT NULL,
  `activity_key` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `awarded_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `health_points_source_unique` (`membership_id`,`source_key`),
  KEY `clinic_health_points_doctor_id_foreign` (`doctor_id`),
  KEY `clinic_health_points_awarded_by_foreign` (`awarded_by`),
  KEY `clinic_health_points_patient_id_created_at_index` (`patient_id`,`created_at`),
  CONSTRAINT `clinic_health_points_awarded_by_foreign` FOREIGN KEY (`awarded_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_health_points_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_health_points_membership_id_foreign` FOREIGN KEY (`membership_id`) REFERENCES `clinic_health_memberships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_health_points_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_health_programs`
--

DROP TABLE IF EXISTS `clinic_health_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_health_programs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned DEFAULT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `interval_days` smallint unsigned NOT NULL DEFAULT '365',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_health_programs_clinic_id_foreign` (`clinic_id`),
  KEY `clinic_health_programs_doctor_id_is_active_category_index` (`doctor_id`,`is_active`,`category`),
  CONSTRAINT `clinic_health_programs_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_health_programs_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_inventory_items`
--

DROP TABLE IF EXISTS `clinic_inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_inventory_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned DEFAULT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `sku` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'عدد',
  `minimum_quantity` decimal(12,3) unsigned NOT NULL DEFAULT '0.000',
  `current_quantity` decimal(12,3) unsigned NOT NULL DEFAULT '0.000',
  `average_unit_cost` bigint unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lead_time_days` smallint unsigned NOT NULL DEFAULT '7',
  `safety_stock_days` smallint unsigned NOT NULL DEFAULT '7',
  `preferred_supplier` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_inventory_items_clinic_id_sku_unique` (`clinic_id`,`sku`),
  KEY `clinic_inventory_items_organization_id_foreign` (`organization_id`),
  KEY `clinic_inventory_items_clinic_id_current_quantity_index` (`clinic_id`,`current_quantity`),
  KEY `inventory_forecast_scope_idx` (`clinic_id`,`is_active`,`current_quantity`),
  CONSTRAINT `clinic_inventory_items_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_inventory_items_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_inventory_movements`
--

DROP TABLE IF EXISTS `clinic_inventory_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_inventory_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `inventory_item_id` bigint unsigned NOT NULL,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `movement_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_cost` bigint unsigned NOT NULL DEFAULT '0',
  `reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `occurred_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_inventory_movements_recorded_by_foreign` (`recorded_by`),
  KEY `clinic_inventory_movements_inventory_item_id_occurred_at_index` (`inventory_item_id`,`occurred_at`),
  CONSTRAINT `clinic_inventory_movements_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `clinic_inventory_items` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_inventory_movements_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_journal_entries`
--

DROP TABLE IF EXISTS `clinic_journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_journal_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned DEFAULT NULL,
  `clinic_id` bigint unsigned DEFAULT NULL,
  `doctor_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `source_type` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(240) COLLATE utf8mb4_unicode_ci NOT NULL,
  `occurred_at` datetime NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'posted',
  `idempotency_key` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_journal_entries_idempotency_key_unique` (`idempotency_key`),
  KEY `clinic_journal_entries_doctor_id_foreign` (`doctor_id`),
  KEY `clinic_journal_entries_created_by_foreign` (`created_by`),
  KEY `clinic_journal_entries_organization_id_occurred_at_status_index` (`organization_id`,`occurred_at`,`status`),
  KEY `clinic_journal_entries_clinic_id_occurred_at_index` (`clinic_id`,`occurred_at`),
  CONSTRAINT `clinic_journal_entries_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_journal_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_journal_entries_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_journal_entries_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_journal_lines`
--

DROP TABLE IF EXISTS `clinic_journal_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_journal_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `debit` bigint unsigned NOT NULL DEFAULT '0',
  `credit` bigint unsigned NOT NULL DEFAULT '0',
  `memo` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_journal_lines_journal_entry_id_foreign` (`journal_entry_id`),
  KEY `clinic_journal_lines_account_id_journal_entry_id_index` (`account_id`,`journal_entry_id`),
  CONSTRAINT `clinic_journal_lines_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `clinic_financial_accounts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_journal_lines_journal_entry_id_foreign` FOREIGN KEY (`journal_entry_id`) REFERENCES `clinic_journal_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_marketing_campaigns`
--

DROP TABLE IF EXISTS `clinic_marketing_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_marketing_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'social',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `budget_rial` bigint unsigned DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_marketing_campaigns_code_unique` (`code`),
  KEY `clinic_marketing_campaigns_created_by_foreign` (`created_by`),
  KEY `marketing_campaign_doctor_status_idx` (`doctor_id`,`status`,`created_at`),
  KEY `marketing_campaign_clinic_status_idx` (`clinic_id`,`status`),
  CONSTRAINT `clinic_marketing_campaigns_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_marketing_campaigns_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_marketing_campaigns_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_marketing_events`
--

DROP TABLE IF EXISTS `clinic_marketing_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_marketing_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint unsigned NOT NULL,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `occurred_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_marketing_events_appointment_id_unique` (`appointment_id`),
  KEY `marketing_events_campaign_type_date_idx` (`campaign_id`,`event_type`,`occurred_at`),
  CONSTRAINT `clinic_marketing_events_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_marketing_events_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `clinic_marketing_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_organization_clinics`
--

DROP TABLE IF EXISTS `clinic_organization_clinics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_organization_clinics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `branch_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_organization_clinics_organization_id_clinic_id_unique` (`organization_id`,`clinic_id`),
  KEY `clinic_organization_clinics_clinic_id_foreign` (`clinic_id`),
  KEY `clinic_organization_clinics_organization_id_status_index` (`organization_id`,`status`),
  CONSTRAINT `clinic_organization_clinics_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_organization_clinics_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_organization_doctors`
--

DROP TABLE IF EXISTS `clinic_organization_doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_organization_doctors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `role` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'practitioner',
  `revenue_share_basis_points` smallint unsigned NOT NULL DEFAULT '7000',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `joined_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_organization_doctors_organization_id_doctor_id_unique` (`organization_id`,`doctor_id`),
  KEY `clinic_organization_doctors_doctor_id_status_index` (`doctor_id`,`status`),
  CONSTRAINT `clinic_organization_doctors_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_organization_doctors_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_organization_members`
--

DROP TABLE IF EXISTS `clinic_organization_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_organization_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `permissions` json DEFAULT NULL,
  `clinic_ids` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_organization_members_organization_id_user_id_unique` (`organization_id`,`user_id`),
  KEY `clinic_organization_members_user_id_is_active_index` (`user_id`,`is_active`),
  CONSTRAINT `clinic_organization_members_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_organization_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_organizations`
--

DROP TABLE IF EXISTS `clinic_organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_organizations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_user_id` bigint unsigned NOT NULL,
  `name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `legal_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_number` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Tehran',
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_organizations_slug_unique` (`slug`),
  KEY `clinic_organizations_owner_user_id_status_index` (`owner_user_id`,`status`),
  CONSTRAINT `clinic_organizations_owner_user_id_foreign` FOREIGN KEY (`owner_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_professional_partnerships`
--

DROP TABLE IF EXISTS `clinic_professional_partnerships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_professional_partnerships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `provider_id` bigint unsigned NOT NULL,
  `created_by_user_id` bigint unsigned NOT NULL,
  `model` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_fee_rial` bigint unsigned NOT NULL DEFAULT '0',
  `professional_share_bps` int unsigned NOT NULL DEFAULT '7000',
  `clinic_share_bps` int unsigned NOT NULL DEFAULT '3000',
  `fixed_fee_rial` bigint unsigned NOT NULL DEFAULT '0',
  `periodic_fee_rial` bigint unsigned NOT NULL DEFAULT '0',
  `periodic_cycle` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_direction` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'clinic_to_professional',
  `starts_at` date NOT NULL,
  `ends_at` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proposed',
  `accepted_at` timestamp NULL DEFAULT NULL,
  `terms` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_professional_partnerships_clinic_id_foreign` (`clinic_id`),
  KEY `clinic_professional_partnerships_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `cpp_doctor_clinic_status` (`doctor_id`,`clinic_id`,`status`),
  KEY `cpp_provider_status` (`provider_id`,`status`),
  CONSTRAINT `clinic_professional_partnerships_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_professional_partnerships_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_professional_partnerships_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_professional_partnerships_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `medical_network_providers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_professional_sessions`
--

DROP TABLE IF EXISTS `clinic_professional_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_professional_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `partnership_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `provider_id` bigint unsigned NOT NULL,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `created_by_user_id` bigint unsigned NOT NULL,
  `service_name` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_date` date NOT NULL,
  `gross_amount_rial` bigint unsigned NOT NULL,
  `adjustment_amount_rial` bigint unsigned NOT NULL DEFAULT '0',
  `professional_amount_rial` bigint unsigned NOT NULL,
  `clinic_amount_rial` bigint unsigned NOT NULL,
  `settlement_amount_rial` bigint unsigned NOT NULL DEFAULT '0',
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `payer_confirmed_at` timestamp NULL DEFAULT NULL,
  `payer_confirmed_by_user_id` bigint unsigned DEFAULT NULL,
  `payer_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receiver_confirmed_at` timestamp NULL DEFAULT NULL,
  `receiver_confirmed_by_user_id` bigint unsigned DEFAULT NULL,
  `paid_by_user_id` bigint unsigned DEFAULT NULL,
  `payment_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_professional_sessions_appointment_id_unique` (`appointment_id`),
  KEY `clinic_professional_sessions_clinic_id_foreign` (`clinic_id`),
  KEY `clinic_professional_sessions_provider_id_foreign` (`provider_id`),
  KEY `clinic_professional_sessions_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `clinic_professional_sessions_paid_by_user_id_foreign` (`paid_by_user_id`),
  KEY `cps_doctor_clinic_date` (`doctor_id`,`clinic_id`,`service_date`),
  KEY `cps_partnership_status` (`partnership_id`,`status`),
  KEY `clinic_professional_sessions_payer_confirmed_by_user_id_foreign` (`payer_confirmed_by_user_id`),
  CONSTRAINT `clinic_professional_sessions_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_professional_sessions_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_professional_sessions_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_professional_sessions_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_professional_sessions_paid_by_user_id_foreign` FOREIGN KEY (`paid_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_professional_sessions_partnership_id_foreign` FOREIGN KEY (`partnership_id`) REFERENCES `clinic_professional_partnerships` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_professional_sessions_payer_confirmed_by_user_id_foreign` FOREIGN KEY (`payer_confirmed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_professional_sessions_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `medical_network_providers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_service_inventory_requirements`
--

DROP TABLE IF EXISTS `clinic_service_inventory_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_service_inventory_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_id` bigint unsigned NOT NULL,
  `inventory_item_id` bigint unsigned NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_inventory_requirement_unique` (`service_id`,`inventory_item_id`),
  KEY `clinic_service_inventory_requirements_inventory_item_id_foreign` (`inventory_item_id`),
  CONSTRAINT `clinic_service_inventory_requirements_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `clinic_inventory_items` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `clinic_service_inventory_requirements_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `clinic_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_services`
--

DROP TABLE IF EXISTS `clinic_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned DEFAULT NULL,
  `clinic_id` bigint unsigned DEFAULT NULL,
  `code` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty_module` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `default_duration_minutes` smallint unsigned NOT NULL DEFAULT '30',
  `default_price` bigint unsigned NOT NULL DEFAULT '0',
  `doctor_share_basis_points` smallint unsigned NOT NULL DEFAULT '7000',
  `requires_follow_up` tinyint(1) NOT NULL DEFAULT '0',
  `follow_up_after_days` smallint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clinic_services_organization_id_specialty_module_is_active_index` (`organization_id`,`specialty_module`,`is_active`),
  KEY `clinic_services_clinic_id_is_active_index` (`clinic_id`,`is_active`),
  CONSTRAINT `clinic_services_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_services_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinic_staff_payrolls`
--

DROP TABLE IF EXISTS `clinic_staff_payrolls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinic_staff_payrolls` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned DEFAULT NULL,
  `staff_user_id` bigint unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `gross_amount` bigint unsigned NOT NULL,
  `deduction_amount` bigint unsigned NOT NULL DEFAULT '0',
  `net_amount` bigint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `approved_by` bigint unsigned DEFAULT NULL,
  `paid_by` bigint unsigned DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_method` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clinic_staff_payroll_period_unique` (`organization_id`,`staff_user_id`,`period_start`,`period_end`),
  KEY `clinic_staff_payrolls_clinic_id_foreign` (`clinic_id`),
  KEY `clinic_staff_payrolls_approved_by_foreign` (`approved_by`),
  KEY `clinic_staff_payrolls_paid_by_foreign` (`paid_by`),
  KEY `clinic_staff_payrolls_organization_id_period_start_status_index` (`organization_id`,`period_start`,`status`),
  KEY `clinic_staff_payrolls_staff_user_id_period_start_index` (`staff_user_id`,`period_start`),
  CONSTRAINT `clinic_staff_payrolls_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_staff_payrolls_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_staff_payrolls_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clinic_staff_payrolls_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `clinic_staff_payrolls_staff_user_id_foreign` FOREIGN KEY (`staff_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'تهران',
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'IR',
  `working_hours` json DEFAULT NULL,
  `social_links` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contacts_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `contacts_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `doctor_images`
--

DROP TABLE IF EXISTS `doctor_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'clinic',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doctor_images_doctor_id_category_is_active_index` (`doctor_id`,`category`,`is_active`),
  CONSTRAINT `doctor_images_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `doctor_settlements`
--

DROP TABLE IF EXISTS `doctor_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `organization_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `gross_amount` bigint unsigned NOT NULL,
  `clinic_share_amount` bigint unsigned NOT NULL DEFAULT '0',
  `adjustment_amount` bigint unsigned NOT NULL DEFAULT '0',
  `net_amount` bigint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `approved_by` bigint unsigned DEFAULT NULL,
  `paid_by` bigint unsigned DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_method` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_settlement_period_unique` (`organization_id`,`doctor_id`,`period_start`,`period_end`),
  KEY `doctor_settlements_doctor_id_foreign` (`doctor_id`),
  KEY `doctor_settlements_approved_by_foreign` (`approved_by`),
  KEY `doctor_settlements_paid_by_foreign` (`paid_by`),
  CONSTRAINT `doctor_settlements_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctor_settlements_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `doctor_settlements_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_settlements_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `doctor_socials`
--

DROP TABLE IF EXISTS `doctor_socials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_socials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `social_platform_id` bigint unsigned NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_visible` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `social_platform_id` (`social_platform_id`),
  CONSTRAINT `doctor_socials_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_socials_ibfk_2` FOREIGN KEY (`social_platform_id`) REFERENCES `social_platforms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `doctor_user`
--

DROP TABLE IF EXISTS `doctor_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `permissions` json DEFAULT NULL,
  `clinic_ids` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `base_permissions` json DEFAULT NULL,
  `base_clinic_ids` json DEFAULT NULL,
  `managed_by_organization` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctor_user_unique` (`doctor_id`,`user_id`),
  KEY `doctor_user_user_fk` (`user_id`),
  CONSTRAINT `doctor_user_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_user_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `doctor_working_hours`
--

DROP TABLE IF EXISTS `doctor_working_hours`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctor_working_hours` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `duration_minutes` smallint unsigned NOT NULL DEFAULT '5',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `doctor_working_hours_clinic_id_foreign` (`clinic_id`),
  KEY `doctor_working_hours_doctor_id_clinic_id_day_of_week_index` (`doctor_id`,`clinic_id`,`day_of_week`),
  KEY `working_hours_clinic_doctor_fk` (`clinic_id`,`doctor_id`),
  CONSTRAINT `doctor_working_hours_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `working_hours_clinic_doctor_fk` FOREIGN KEY (`clinic_id`, `doctor_id`) REFERENCES `addresses` (`id`, `doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `specialty_id` bigint unsigned DEFAULT NULL,
  `caption` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'دکتر',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `family` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clinic_address` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Medical_system_number` bigint DEFAULT NULL,
  `banner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `specialty_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `years_of_experience` int NOT NULL DEFAULT '0',
  `satisfaction_rate` decimal(4,2) NOT NULL DEFAULT '98.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `registration_status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `site_expires_at` datetime DEFAULT NULL,
  `trial_started_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'doctor',
  PRIMARY KEY (`id`),
  UNIQUE KEY `doctors_slug_unique` (`slug`),
  UNIQUE KEY `doctors_user_id_unique` (`user_id`),
  KEY `doctors_specialty_id_foreign` (`specialty_id`),
  KEY `doctors_approved_by_foreign` (`approved_by`),
  KEY `doctors_city_id_index` (`city_id`),
  CONSTRAINT `doctors_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctors_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctors_specialty_id_foreign` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL,
  CONSTRAINT `doctors_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fees`
--

DROP TABLE IF EXISTS `fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'تعرفه ویزیت',
  `fee_rial` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `deactivated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fees_active_index` (`is_active`),
  KEY `fees_created_by_index` (`created_by`),
  KEY `fees_doctor_id_index` (`doctor_id`),
  CONSTRAINT `fees_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `honor_badges`
--

DROP TABLE IF EXISTS `honor_badges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `honor_badges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '✨',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `honor_badges_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `honor_badges_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `medical_collaboration_contracts`
--

DROP TABLE IF EXISTS `medical_collaboration_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medical_collaboration_contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `provider_id` bigint unsigned NOT NULL,
  `created_by_user_id` bigint unsigned NOT NULL,
  `service_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_fee_rial` bigint unsigned NOT NULL,
  `starts_at` date NOT NULL,
  `ends_at` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proposed',
  `accepted_at` timestamp NULL DEFAULT NULL,
  `terms` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medical_collaboration_contracts_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `med_contract_doctor_status_dates` (`doctor_id`,`status`,`starts_at`,`ends_at`),
  KEY `med_contract_provider_status` (`provider_id`,`status`),
  CONSTRAINT `medical_collaboration_contracts_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `medical_collaboration_contracts_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `medical_collaboration_contracts_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `medical_network_providers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `medical_network_providers`
--

DROP TABLE IF EXISTS `medical_network_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medical_network_providers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by_user_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned DEFAULT NULL,
  `doctor_id` bigint unsigned DEFAULT NULL,
  `provider_type` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(210) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specialties` json DEFAULT NULL,
  `services` json DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `completed_referrals_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medical_network_providers_slug_unique` (`slug`),
  KEY `medical_network_providers_doctor_id_foreign` (`doctor_id`),
  KEY `medical_network_providers_provider_type_status_city_index` (`provider_type`,`status`,`city`),
  KEY `medical_network_providers_organization_id_status_index` (`organization_id`,`status`),
  KEY `medical_network_providers_created_by_user_id_status_index` (`created_by_user_id`,`status`),
  CONSTRAINT `medical_network_providers_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medical_network_providers_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `medical_network_providers_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `medical_referral_events`
--

DROP TABLE IF EXISTS `medical_referral_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medical_referral_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `medical_referral_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `event` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_status` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `medical_referral_events_user_id_foreign` (`user_id`),
  KEY `medical_referral_events_medical_referral_id_created_at_index` (`medical_referral_id`,`created_at`),
  CONSTRAINT `medical_referral_events_medical_referral_id_foreign` FOREIGN KEY (`medical_referral_id`) REFERENCES `medical_referrals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `medical_referral_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `medical_referral_settlements`
--

DROP TABLE IF EXISTS `medical_referral_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medical_referral_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `referral_id` bigint unsigned NOT NULL,
  `amount_rial` bigint unsigned NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `paid_at` timestamp NULL DEFAULT NULL,
  `paid_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medical_referral_settlements_referral_id_unique` (`referral_id`),
  KEY `medical_referral_settlements_paid_by_user_id_foreign` (`paid_by_user_id`),
  KEY `med_ref_settlement_contract_status` (`contract_id`,`status`),
  CONSTRAINT `medical_referral_settlements_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `medical_collaboration_contracts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `medical_referral_settlements_paid_by_user_id_foreign` FOREIGN KEY (`paid_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `medical_referral_settlements_referral_id_foreign` FOREIGN KEY (`referral_id`) REFERENCES `medical_referrals` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `medical_referrals`
--

DROP TABLE IF EXISTS `medical_referrals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medical_referrals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tracking_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `referring_doctor_id` bigint unsigned NOT NULL,
  `target_provider_id` bigint unsigned NOT NULL,
  `created_by_user_id` bigint unsigned NOT NULL,
  `source_clinic_id` bigint unsigned DEFAULT NULL,
  `service_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `reason` varchar(240) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clinical_summary` text COLLATE utf8mb4_unicode_ci,
  `patient_message` text COLLATE utf8mb4_unicode_ci,
  `consent_at` timestamp NOT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `provider_response` text COLLATE utf8mb4_unicode_ci,
  `result_summary` text COLLATE utf8mb4_unicode_ci,
  `patient_instructions` text COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medical_referrals_tracking_code_unique` (`tracking_code`),
  KEY `medical_referrals_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `medical_referrals_referring_doctor_id_status_created_at_index` (`referring_doctor_id`,`status`,`created_at`),
  KEY `medical_referrals_target_provider_id_status_created_at_index` (`target_provider_id`,`status`,`created_at`),
  KEY `medical_referrals_patient_id_created_at_index` (`patient_id`,`created_at`),
  KEY `medical_referrals_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `medical_referrals_source_clinic_id_created_at_index` (`source_clinic_id`,`created_at`),
  CONSTRAINT `medical_referrals_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `medical_referrals_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `medical_referrals_referring_doctor_id_foreign` FOREIGN KEY (`referring_doctor_id`) REFERENCES `doctors` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `medical_referrals_source_clinic_id_foreign` FOREIGN KEY (`source_clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `medical_referrals_target_provider_id_foreign` FOREIGN KEY (`target_provider_id`) REFERENCES `medical_network_providers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `medical_services`
--

DROP TABLE IF EXISTS `medical_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medical_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('general_surgery','cosmetic_surgery','obesity_surgery') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon_html` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `short_description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intro_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intro_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content_heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `benefits_heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `benefits_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `preparation_heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preparation_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recovery_heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recovery_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `faq_json` json DEFAULT NULL,
  `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(320) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `canonical_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schema_json` json DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `medical_services_doctor_slug_unique` (`doctor_id`,`slug`),
  KEY `medical_services_category_index` (`category`),
  KEY `medical_services_active_index` (`is_active`),
  KEY `medical_services_featured_index` (`is_featured`),
  KEY `medical_services_sort_index` (`sort_order`),
  KEY `medical_services_doctor_id_index` (`doctor_id`),
  CONSTRAINT `medical_services_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int NOT NULL DEFAULT '0',
  `guard` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'doctor',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `show_in_sidebar` tinyint(1) NOT NULL DEFAULT '1',
  `permissions` json DEFAULT NULL,
  `roles` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `menus_guard_is_active_order_index` (`guard`,`is_active`,`order`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menus_home1`
--

DROP TABLE IF EXISTS `menus_home1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menus_home1` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `route_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int NOT NULL DEFAULT '0',
  `guard` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'doctor',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `show_in_sidebar` tinyint(1) NOT NULL DEFAULT '1',
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `menus_guard_is_active_order_index` (`guard`,`is_active`,`order`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_access_requests`
--

DROP TABLE IF EXISTS `patient_access_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_access_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `reviewed_by_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `review_note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_access_requests_user_id_patient_id_unique` (`user_id`,`patient_id`),
  KEY `patient_access_requests_patient_id_foreign` (`patient_id`),
  KEY `patient_access_requests_reviewed_by_user_id_foreign` (`reviewed_by_user_id`),
  KEY `patient_access_requests_doctor_id_status_created_at_index` (`doctor_id`,`status`,`created_at`),
  CONSTRAINT `patient_access_requests_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_access_requests_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_access_requests_reviewed_by_user_id_foreign` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_access_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_account_accesses`
--

DROP TABLE IF EXISTS `patient_account_accesses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_account_accesses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `granted_by_user_id` bigint unsigned DEFAULT NULL,
  `access_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'owner',
  `permissions` json DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_account_accesses_patient_id_user_id_unique` (`patient_id`,`user_id`),
  KEY `patient_account_accesses_granted_by_user_id_foreign` (`granted_by_user_id`),
  KEY `patient_account_accesses_user_id_revoked_at_expires_at_index` (`user_id`,`revoked_at`,`expires_at`),
  CONSTRAINT `patient_account_accesses_granted_by_user_id_foreign` FOREIGN KEY (`granted_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_account_accesses_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_account_accesses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_care_pathway_steps`
--

DROP TABLE IF EXISTS `patient_care_pathway_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_care_pathway_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pathway_id` bigint unsigned NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `due_at` datetime DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `patient_reported_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verification_note` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_care_pathway_steps_assigned_to_foreign` (`assigned_to`),
  KEY `patient_care_pathway_steps_verified_by_foreign` (`verified_by`),
  KEY `care_pathway_step_status_due_idx` (`pathway_id`,`status`,`due_at`),
  CONSTRAINT `patient_care_pathway_steps_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_care_pathway_steps_pathway_id_foreign` FOREIGN KEY (`pathway_id`) REFERENCES `patient_care_pathways` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_care_pathway_steps_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_care_pathways`
--

DROP TABLE IF EXISTS `patient_care_pathways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_care_pathways` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `goal` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `starts_at` date DEFAULT NULL,
  `ends_at` date DEFAULT NULL,
  `activated_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_care_pathways_clinic_id_foreign` (`clinic_id`),
  KEY `patient_care_pathways_patient_id_foreign` (`patient_id`),
  KEY `patient_care_pathways_created_by_foreign` (`created_by`),
  KEY `patient_care_pathways_approved_by_foreign` (`approved_by`),
  KEY `care_pathway_scope_status_idx` (`doctor_id`,`clinic_id`,`patient_id`,`status`),
  CONSTRAINT `patient_care_pathways_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_care_pathways_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_care_pathways_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `patient_care_pathways_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_care_pathways_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_clinical_records`
--

DROP TABLE IF EXISTS `patient_clinical_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_clinical_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `kind` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `dosage` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frequency` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `related_record_id` bigint unsigned DEFAULT NULL,
  `related_allergy_record_id` bigint unsigned DEFAULT NULL,
  `reaction_severity` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reaction_date` date DEFAULT NULL,
  `action_taken` text COLLATE utf8mb4_unicode_ci,
  `reaction_outcome` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_clinical_patient_kind_active_index` (`patient_id`,`kind`,`is_active`),
  KEY `patient_clinical_doctor_patient_index` (`doctor_id`,`patient_id`),
  KEY `patient_clinical_records_related_record_id_foreign` (`related_record_id`),
  KEY `patient_clinical_related_record_index` (`patient_id`,`related_record_id`),
  KEY `patient_clinical_records_related_allergy_record_id_foreign` (`related_allergy_record_id`),
  KEY `patient_clinical_allergy_link_index` (`patient_id`,`related_allergy_record_id`),
  CONSTRAINT `patient_clinical_records_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_clinical_records_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_clinical_records_related_allergy_record_id_foreign` FOREIGN KEY (`related_allergy_record_id`) REFERENCES `patient_clinical_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_clinical_records_related_record_id_foreign` FOREIGN KEY (`related_record_id`) REFERENCES `patient_clinical_records` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_collaterals`
--

DROP TABLE IF EXISTS `patient_collaterals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_collaterals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `treatment_plan_id` bigint unsigned NOT NULL,
  `received_by` bigint unsigned DEFAULT NULL,
  `type` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identifier` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `face_value` bigint unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'held',
  `received_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reminder_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `last_reminded_at` datetime DEFAULT NULL,
  `collected_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_collaterals_received_by_foreign` (`received_by`),
  KEY `patient_collaterals_treatment_plan_id_status_index` (`treatment_plan_id`,`status`),
  KEY `collateral_reminder_due_idx` (`status`,`reminder_enabled`,`due_date`),
  CONSTRAINT `patient_collaterals_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_collaterals_treatment_plan_id_foreign` FOREIGN KEY (`treatment_plan_id`) REFERENCES `patient_treatment_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_dental_chart_entries`
--

DROP TABLE IF EXISTS `patient_dental_chart_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_dental_chart_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned DEFAULT NULL,
  `dentition` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'permanent',
  `tooth_number` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surface` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `condition` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` bigint unsigned DEFAULT NULL,
  `recorded_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_dental_chart_entries_doctor_id_foreign` (`doctor_id`),
  KEY `patient_dental_chart_entries_clinic_id_foreign` (`clinic_id`),
  KEY `patient_dental_chart_entries_recorded_by_foreign` (`recorded_by`),
  KEY `dental_chart_patient_tooth_idx` (`patient_id`,`tooth_number`,`recorded_at`),
  CONSTRAINT `patient_dental_chart_entries_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_dental_chart_entries_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_dental_chart_entries_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_dental_chart_entries_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_encounter_prescriptions`
--

DROP TABLE IF EXISTS `patient_encounter_prescriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_encounter_prescriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint unsigned NOT NULL,
  `medication_name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `strength` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dosage` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frequency` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration_days` smallint unsigned DEFAULT NULL,
  `quantity` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `sort_order` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `encounter_prescriptions_order_idx` (`encounter_id`,`sort_order`),
  CONSTRAINT `patient_encounter_prescriptions_encounter_id_foreign` FOREIGN KEY (`encounter_id`) REFERENCES `patient_encounters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_encounters`
--

DROP TABLE IF EXISTS `patient_encounters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_encounters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `appointment_id` bigint unsigned NOT NULL,
  `amends_encounter_id` bigint unsigned DEFAULT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `author_id` bigint unsigned DEFAULT NULL,
  `signed_by` bigint unsigned DEFAULT NULL,
  `note_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'consultation',
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `subjective` text COLLATE utf8mb4_unicode_ci,
  `objective` text COLLATE utf8mb4_unicode_ci,
  `assessment` text COLLATE utf8mb4_unicode_ci,
  `plan` text COLLATE utf8mb4_unicode_ci,
  `diagnosis` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `diagnosis_code` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewed_allergies` tinyint(1) NOT NULL DEFAULT '0',
  `reviewed_medications` tinyint(1) NOT NULL DEFAULT '0',
  `reviewed_vitals` tinyint(1) NOT NULL DEFAULT '0',
  `clinical_snapshot` json DEFAULT NULL,
  `signed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_encounters_patient_id_foreign` (`patient_id`),
  KEY `patient_encounters_amends_encounter_id_foreign` (`amends_encounter_id`),
  KEY `patient_encounters_author_id_foreign` (`author_id`),
  KEY `patient_encounters_signed_by_foreign` (`signed_by`),
  KEY `patient_encounters_doctor_patient_date_idx` (`doctor_id`,`patient_id`,`created_at`),
  KEY `patient_encounters_appointment_status_idx` (`appointment_id`,`status`),
  KEY `patient_encounters_clinic_date_idx` (`clinic_id`,`created_at`),
  CONSTRAINT `patient_encounters_amends_encounter_id_foreign` FOREIGN KEY (`amends_encounter_id`) REFERENCES `patient_encounters` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_encounters_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_encounters_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_encounters_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `patient_encounters_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_encounters_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_encounters_signed_by_foreign` FOREIGN KEY (`signed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_follow_up_tasks`
--

DROP TABLE IF EXISTS `patient_follow_up_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_follow_up_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned DEFAULT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `treatment_plan_id` bigint unsigned DEFAULT NULL,
  `collateral_id` bigint unsigned DEFAULT NULL,
  `template_id` bigint unsigned DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `plan_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `due_at` datetime NOT NULL,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `contact_method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'call',
  `send_sms` tinyint(1) NOT NULL DEFAULT '0',
  `sms_consent_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `outcome` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result_notes` text COLLATE utf8mb4_unicode_ci,
  `completed_at` datetime DEFAULT NULL,
  `sms_sent_at` datetime DEFAULT NULL,
  `sms_attempted_at` datetime DEFAULT NULL,
  `sms_attempts` smallint unsigned NOT NULL DEFAULT '0',
  `last_sms_error` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_follow_up_tasks_clinic_id_foreign` (`clinic_id`),
  KEY `patient_follow_up_tasks_appointment_id_foreign` (`appointment_id`),
  KEY `patient_follow_up_tasks_template_id_foreign` (`template_id`),
  KEY `patient_follow_up_tasks_created_by_foreign` (`created_by`),
  KEY `patient_follow_up_tasks_doctor_id_clinic_id_status_due_at_index` (`doctor_id`,`clinic_id`,`status`,`due_at`),
  KEY `patient_follow_up_tasks_patient_id_due_at_index` (`patient_id`,`due_at`),
  KEY `patient_follow_up_tasks_assigned_to_status_due_at_index` (`assigned_to`,`status`,`due_at`),
  KEY `follow_up_sms_dispatch_index` (`sms_sent_at`,`sms_attempted_at`,`due_at`),
  KEY `followup_treatment_plan_status_idx` (`treatment_plan_id`,`status`),
  KEY `followup_collateral_status_idx` (`collateral_id`,`status`),
  CONSTRAINT `patient_follow_up_tasks_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_follow_up_tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_follow_up_tasks_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_follow_up_tasks_collateral_id_foreign` FOREIGN KEY (`collateral_id`) REFERENCES `patient_collaterals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_follow_up_tasks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_follow_up_tasks_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_follow_up_tasks_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_follow_up_tasks_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `patient_follow_up_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_follow_up_tasks_treatment_plan_id_foreign` FOREIGN KEY (`treatment_plan_id`) REFERENCES `patient_treatment_plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_follow_up_templates`
--

DROP TABLE IF EXISTS `patient_follow_up_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_follow_up_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `description` text COLLATE utf8mb4_unicode_ci,
  `default_days` smallint unsigned NOT NULL DEFAULT '3',
  `contact_method` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'call',
  `send_sms` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_follow_up_templates_doctor_id_is_active_index` (`doctor_id`,`is_active`),
  CONSTRAINT `patient_follow_up_templates_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_health_observations`
--

DROP TABLE IF EXISTS `patient_health_observations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_health_observations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `measurement_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `unit` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observed_at` timestamp NOT NULL,
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_health_obs_patient_type_date_idx` (`patient_id`,`measurement_type`,`observed_at`),
  KEY `patient_health_observations_user_id_observed_at_index` (`user_id`,`observed_at`),
  CONSTRAINT `patient_health_observations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_health_observations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_installment_payments`
--

DROP TABLE IF EXISTS `patient_installment_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_installment_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `installment_id` bigint unsigned NOT NULL,
  `received_by` bigint unsigned DEFAULT NULL,
  `amount` bigint unsigned NOT NULL,
  `method` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` datetime NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_installment_payments_installment_id_foreign` (`installment_id`),
  KEY `patient_installment_payments_received_by_foreign` (`received_by`),
  KEY `patient_installment_payments_paid_at_method_index` (`paid_at`,`method`),
  CONSTRAINT `patient_installment_payments_installment_id_foreign` FOREIGN KEY (`installment_id`) REFERENCES `patient_treatment_installments` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `patient_installment_payments_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_medication_doses`
--

DROP TABLE IF EXISTS `patient_medication_doses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_medication_doses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint unsigned NOT NULL,
  `scheduled_for` datetime NOT NULL,
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recorded_by_user_id` bigint unsigned NOT NULL,
  `recorded_at` datetime NOT NULL,
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_medication_dose_slot_unique` (`plan_id`,`scheduled_for`),
  KEY `patient_medication_doses_recorded_by_user_id_foreign` (`recorded_by_user_id`),
  KEY `patient_medication_doses_schedule_idx` (`plan_id`,`scheduled_for`,`status`),
  CONSTRAINT `patient_medication_doses_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `patient_medication_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_medication_doses_recorded_by_user_id_foreign` FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_medication_plans`
--

DROP TABLE IF EXISTS `patient_medication_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_medication_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `prescription_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reminder_times` json NOT NULL,
  `timezone` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Tehran',
  `status` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_medication_plan_prescription_unique` (`user_id`,`prescription_id`),
  KEY `patient_medication_plans_prescription_id_foreign` (`prescription_id`),
  KEY `patient_medication_plans_owner_status_idx` (`patient_id`,`user_id`,`status`),
  CONSTRAINT `patient_medication_plans_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_medication_plans_prescription_id_foreign` FOREIGN KEY (`prescription_id`) REFERENCES `patient_encounter_prescriptions` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `patient_medication_plans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_treatment_installments`
--

DROP TABLE IF EXISTS `patient_treatment_installments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_treatment_installments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `treatment_plan_id` bigint unsigned NOT NULL,
  `sequence` smallint unsigned NOT NULL,
  `amount` bigint unsigned NOT NULL,
  `paid_amount` bigint unsigned NOT NULL DEFAULT '0',
  `due_date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `last_reminded_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_treatment_installments_treatment_plan_id_sequence_unique` (`treatment_plan_id`,`sequence`),
  KEY `patient_treatment_installments_status_due_date_index` (`status`,`due_date`),
  CONSTRAINT `patient_treatment_installments_treatment_plan_id_foreign` FOREIGN KEY (`treatment_plan_id`) REFERENCES `patient_treatment_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_treatment_items`
--

DROP TABLE IF EXISTS `patient_treatment_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_treatment_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `treatment_plan_id` bigint unsigned NOT NULL,
  `service_id` bigint unsigned DEFAULT NULL,
  `provider_doctor_id` bigint unsigned DEFAULT NULL,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `item_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'procedure',
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tooth_number` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tooth_surface` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sequence` smallint unsigned NOT NULL DEFAULT '1',
  `stage` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `price` bigint unsigned NOT NULL DEFAULT '0',
  `recognized_amount` bigint unsigned NOT NULL DEFAULT '0',
  `paid_applied_amount` bigint unsigned NOT NULL DEFAULT '0',
  `doctor_share_basis_points` smallint unsigned DEFAULT NULL,
  `follow_up_after_days` smallint unsigned DEFAULT NULL,
  `performed_at` datetime DEFAULT NULL,
  `follow_up_due_at` datetime DEFAULT NULL,
  `clinical_notes` text COLLATE utf8mb4_unicode_ci,
  `completed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_treatment_items_service_id_foreign` (`service_id`),
  KEY `patient_treatment_items_appointment_id_foreign` (`appointment_id`),
  KEY `patient_treatment_items_completed_by_foreign` (`completed_by`),
  KEY `treatment_items_plan_stage_idx` (`treatment_plan_id`,`stage`,`sequence`),
  KEY `patient_treatment_items_provider_doctor_id_performed_at_index` (`provider_doctor_id`,`performed_at`),
  CONSTRAINT `patient_treatment_items_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_treatment_items_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_treatment_items_provider_doctor_id_foreign` FOREIGN KEY (`provider_doctor_id`) REFERENCES `doctors` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_treatment_items_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `clinic_services` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_treatment_items_treatment_plan_id_foreign` FOREIGN KEY (`treatment_plan_id`) REFERENCES `patient_treatment_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_treatment_plans`
--

DROP TABLE IF EXISTS `patient_treatment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_treatment_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `organization_id` bigint unsigned DEFAULT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `patient_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialty_module` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'proposed',
  `total_amount` bigint unsigned NOT NULL,
  `discount_amount` bigint unsigned NOT NULL DEFAULT '0',
  `deposit_amount` bigint unsigned NOT NULL DEFAULT '0',
  `installment_count` smallint unsigned NOT NULL DEFAULT '1',
  `clinical_notes` text COLLATE utf8mb4_unicode_ci,
  `financial_terms` text COLLATE utf8mb4_unicode_ci,
  `sms_reminders_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `sms_consent_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `sms_consent_confirmed_at` timestamp NULL DEFAULT NULL,
  `sms_consent_recorded_by` bigint unsigned DEFAULT NULL,
  `follow_up_sms_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `follow_up_sms_consent_confirmed` tinyint(1) NOT NULL DEFAULT '0',
  `follow_up_sms_consent_confirmed_at` timestamp NULL DEFAULT NULL,
  `follow_up_sms_consent_recorded_by` bigint unsigned DEFAULT NULL,
  `started_at` date DEFAULT NULL,
  `completed_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `sms_consent_revoked_at` timestamp NULL DEFAULT NULL,
  `follow_up_sms_consent_revoked_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_treatment_plans_clinic_id_foreign` (`clinic_id`),
  KEY `patient_treatment_plans_created_by_foreign` (`created_by`),
  KEY `ptp_sms_consent_user_fk` (`sms_consent_recorded_by`),
  KEY `ptp_followup_sms_consent_user_fk` (`follow_up_sms_consent_recorded_by`),
  KEY `patient_treatment_plans_doctor_id_clinic_id_status_index` (`doctor_id`,`clinic_id`,`status`),
  KEY `patient_treatment_plans_patient_id_status_index` (`patient_id`,`status`),
  KEY `patient_treatment_plans_organization_id_status_index` (`organization_id`,`status`),
  CONSTRAINT `patient_treatment_plans_clinic_id_foreign` FOREIGN KEY (`clinic_id`) REFERENCES `addresses` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `patient_treatment_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_treatment_plans_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_treatment_plans_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `clinic_organizations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `patient_treatment_plans_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ptp_followup_sms_consent_user_fk` FOREIGN KEY (`follow_up_sms_consent_recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ptp_sms_consent_user_fk` FOREIGN KEY (`sms_consent_recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_vital_signs`
--

DROP TABLE IF EXISTS `patient_vital_signs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patient_vital_signs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `patient_id` bigint unsigned NOT NULL,
  `doctor_id` bigint unsigned NOT NULL,
  `measurement_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(8,2) NOT NULL,
  `unit` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `measured_at` datetime NOT NULL,
  `note` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_vitals_doctor_patient_date_index` (`doctor_id`,`patient_id`,`measured_at`),
  KEY `patient_vitals_type_date_index` (`patient_id`,`measurement_type`,`measured_at`),
  CONSTRAINT `patient_vital_signs_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `patient_vital_signs_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patients_doctor_national_code_index` (`doctor_id`,`national_code`),
  KEY `patients_doctor_phone_index` (`doctor_id`,`phone`),
  CONSTRAINT `patients_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `patient_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` tinyint NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `is_anonymous` tinyint(1) NOT NULL DEFAULT '0',
  `doctor_reply` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `doctor_reply_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_user_id_foreign` (`user_id`),
  KEY `reviews_doctor_id_is_approved_index` (`doctor_id`,`is_approved`),
  KEY `reviews_rating_index` (`rating`),
  KEY `reviews_approved_by_foreign` (`approved_by`),
  KEY `reviews_parent_id_foreign` (`parent_id`),
  CONSTRAINT `reviews_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reviews_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_audit_logs`
--

DROP TABLE IF EXISTS `security_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` bigint unsigned DEFAULT NULL,
  `event` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `security_audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `security_audit_logs_event_created_at_index` (`event`,`created_at`),
  KEY `security_audit_logs_actor_id_created_at_index` (`actor_id`,`created_at`),
  CONSTRAINT `security_audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sms_logs`
--

DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `appointment_id` bigint unsigned DEFAULT NULL,
  `type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `provider` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_message_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deduplication_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sms_logs_deduplication_key_unique` (`deduplication_key`),
  KEY `sms_logs_appointment_id_created_at_index` (`appointment_id`,`created_at`),
  KEY `sms_logs_type_status_index` (`type`,`status`),
  CONSTRAINT `sms_logs_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `social_platforms`
--

DROP TABLE IF EXISTS `social_platforms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_platforms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `base_url` varchar(255) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specialties`
--

DROP TABLE IF EXISTS `specialties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `specialties` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `specialties_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `surgery_types`
--

DROP TABLE IF EXISTS `surgery_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `surgery_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `surgery_types_doctor_title_unique` (`doctor_id`,`title`),
  UNIQUE KEY `surgery_types_id_doctor_unique` (`id`,`doctor_id`),
  CONSTRAINT `surgery_types_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `surgical_procedures`
--

DROP TABLE IF EXISTS `surgical_procedures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `surgical_procedures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `short_description` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_html` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cover_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intro_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intro_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `indications_heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `indications_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `procedure_heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `procedure_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `benefits_heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `benefits_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `preparation_heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preparation_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recovery_heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recovery_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `risks_heading` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `risks_html` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `faq_json` json DEFAULT NULL,
  `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(320) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `canonical_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `og_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `schema_json` json DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `surgical_procedures_doctor_slug_unique` (`doctor_id`,`slug`),
  KEY `surgical_procedures_category_index` (`category`),
  KEY `surgical_procedures_active_index` (`is_active`),
  KEY `surgical_procedures_featured_index` (`is_featured`),
  KEY `surgical_procedures_sort_index` (`sort_order`),
  KEY `surgical_procedures_doctor_id_index` (`doctor_id`),
  CONSTRAINT `surgical_procedures_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `testimonials`
--

DROP TABLE IF EXISTS `testimonials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `testimonials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `service` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` tinyint NOT NULL DEFAULT '5',
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `testimonials_doctor_id_foreign` (`doctor_id`),
  CONSTRAINT `testimonials_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `time_slots`
--

DROP TABLE IF EXISTS `time_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_slots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `clinic_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('available','booked','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `time_slots_id_clinic_doctor_unique` (`id`,`clinic_id`,`doctor_id`),
  KEY `time_slots_clinic_id_foreign` (`clinic_id`),
  KEY `time_slots_doctor_id_clinic_id_date_status_index` (`doctor_id`,`clinic_id`,`date`,`status`),
  KEY `time_slots_clinic_doctor_fk` (`clinic_id`,`doctor_id`),
  CONSTRAINT `time_slots_clinic_doctor_fk` FOREIGN KEY (`clinic_id`, `doctor_id`) REFERENCES `addresses` (`id`, `doctor_id`) ON DELETE CASCADE,
  CONSTRAINT `time_slots_doctor_id_foreign` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2068 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `family` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','doctor','secretary','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_UNIQUE` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `visit_reasons`
--

DROP TABLE IF EXISTS `visit_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visit_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doctor_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `visit_reasons_doctor_title_unique` (`doctor_id`,`title`),
  UNIQUE KEY `visit_reasons_id_doctor_unique` (`id`,`doctor_id`),
  CONSTRAINT `visit_reasons_doctor_fk` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-19 23:03:57
