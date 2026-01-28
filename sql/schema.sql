-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost
-- Üretim Zamanı: 28 Ara 2025, 21:47:51
-- Sunucu sürümü: 10.4.28-MariaDB
-- PHP Sürümü: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `cs306`
--
CREATE DATABASE IF NOT EXISTS `cs306` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cs306`;

DELIMITER $$
--
-- Yordamlar
--
DROP PROCEDURE IF EXISTS `add_full_encounter`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_full_encounter` (IN `p_encounter_id` BIGINT, IN `p_patient_nbr` BIGINT, IN `p_admission_type_id` INT, IN `p_discharge_disposition_id` INT, IN `p_admission_source_id` INT, IN `p_payer_code` VARCHAR(6), IN `p_readmitted` VARCHAR(4), IN `p_diag1` VARCHAR(10), IN `p_diag2` VARCHAR(10), IN `p_diag3` VARCHAR(10))   BEGIN
  -- readmitted validasyonu (extra güvenlik)
  IF p_readmitted NOT IN ('<30','>30','NO') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Invalid readmitted value. Must be <30, >30, or NO.';
  END IF;

  -- Encounter insert (diğer numeric alanlara sabit mock değerler veriyoruz)
  INSERT INTO Encounter (
    encounter_id, patient_nbr, admission_type_id, discharge_disposition_id,
    admission_source_id, payer_code,
    time_in_hospital, medical_specialty,
    num_lab_procedures, num_procedures, num_medications,
    number_outpatient, number_emergency, number_inpatient, number_diagnoses,
    max_glu_serum, A1Cresult, `change`, diabetesMed, readmitted
  )
  VALUES (
    p_encounter_id, p_patient_nbr, p_admission_type_id, p_discharge_disposition_id,
    p_admission_source_id, p_payer_code,
    3, 'InternalMedicine',
    10, 1, 0,
    0, 0, 0, 3,
    'None', 'None', 'No', 'No', p_readmitted
  );

  -- 3 diagnosis insert
  INSERT INTO Encounter_Diagnosis(encounter_id, position, icd9_code)
  VALUES
    (p_encounter_id, 1, p_diag1),
    (p_encounter_id, 2, p_diag2),
    (p_encounter_id, 3, p_diag3);

END$$

DROP PROCEDURE IF EXISTS `count_med_usage`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `count_med_usage` (IN `p_medication` VARCHAR(255), OUT `result` INT)   BEGIN
  SELECT COUNT(*) INTO result
  FROM Encounter_Medication
  WHERE medication_name = p_medication;
END$$

DROP PROCEDURE IF EXISTS `get_high_risk_patients`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_high_risk_patients` ()   BEGIN
  SELECT p.patient_nbr, p.gender, p.age_band, e.readmitted
  FROM Patient p
  JOIN Encounter e ON p.patient_nbr = e.patient_nbr
  WHERE e.readmitted = '<30';
END$$

DROP PROCEDURE IF EXISTS `get_patient_summary`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_patient_summary` (IN `p_patient_nbr` BIGINT)   BEGIN
  SELECT e.encounter_id, e.time_in_hospital, e.readmitted,
         e.num_lab_procedures, e.num_medications
  FROM Encounter e
  WHERE e.patient_nbr = p_patient_nbr;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Admission_Source`
--

DROP TABLE IF EXISTS `Admission_Source`;
CREATE TABLE IF NOT EXISTS `Admission_Source` (
  `admission_source_id` int(11) NOT NULL,
  `description` varchar(120) NOT NULL,
  PRIMARY KEY (`admission_source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Admission_Source`
--

INSERT INTO `Admission_Source` (`admission_source_id`, `description`) VALUES
(1, 'Physician Referral');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Admission_Type`
--

DROP TABLE IF EXISTS `Admission_Type`;
CREATE TABLE IF NOT EXISTS `Admission_Type` (
  `admission_type_id` int(11) NOT NULL,
  `description` varchar(60) NOT NULL,
  PRIMARY KEY (`admission_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Admission_Type`
--

INSERT INTO `Admission_Type` (`admission_type_id`, `description`) VALUES
(1, 'Emergency');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Diagnosis`
--

DROP TABLE IF EXISTS `Diagnosis`;
CREATE TABLE IF NOT EXISTS `Diagnosis` (
  `icd9_code` varchar(10) NOT NULL,
  `description` varchar(200) NOT NULL,
  PRIMARY KEY (`icd9_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Diagnosis`
--

INSERT INTO `Diagnosis` (`icd9_code`, `description`) VALUES
('250.00', 'Diabetes'),
('401.9', 'Hypertension'),
('414.01', 'CAD'),
('486', 'Pneumonia');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Discharge_Disposition`
--

DROP TABLE IF EXISTS `Discharge_Disposition`;
CREATE TABLE IF NOT EXISTS `Discharge_Disposition` (
  `discharge_disposition_id` int(11) NOT NULL,
  `description` varchar(120) NOT NULL,
  PRIMARY KEY (`discharge_disposition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Discharge_Disposition`
--

INSERT INTO `Discharge_Disposition` (`discharge_disposition_id`, `description`) VALUES
(1, 'Discharged');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Encounter`
--

DROP TABLE IF EXISTS `Encounter`;
CREATE TABLE IF NOT EXISTS `Encounter` (
  `encounter_id` bigint(20) NOT NULL,
  `patient_nbr` bigint(20) NOT NULL,
  `admission_type_id` int(11) NOT NULL,
  `discharge_disposition_id` int(11) NOT NULL,
  `admission_source_id` int(11) NOT NULL,
  `payer_code` varchar(6) NOT NULL,
  `time_in_hospital` int(11) NOT NULL,
  `medical_specialty` varchar(80) DEFAULT NULL,
  `num_lab_procedures` int(11) NOT NULL,
  `num_procedures` int(11) NOT NULL,
  `num_medications` int(11) NOT NULL,
  `number_outpatient` int(11) NOT NULL,
  `number_emergency` int(11) NOT NULL,
  `number_inpatient` int(11) NOT NULL,
  `number_diagnoses` int(11) NOT NULL,
  `max_glu_serum` varchar(10) NOT NULL,
  `A1Cresult` varchar(10) NOT NULL,
  `change` varchar(4) NOT NULL,
  `diabetesMed` varchar(3) NOT NULL,
  `readmitted` varchar(4) NOT NULL,
  PRIMARY KEY (`encounter_id`),
  KEY `fk_enc_patient` (`patient_nbr`),
  KEY `fk_enc_admtype` (`admission_type_id`),
  KEY `fk_enc_discharge` (`discharge_disposition_id`),
  KEY `fk_enc_source` (`admission_source_id`),
  KEY `fk_enc_payer` (`payer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Encounter`
--

INSERT INTO `Encounter` (`encounter_id`, `patient_nbr`, `admission_type_id`, `discharge_disposition_id`, `admission_source_id`, `payer_code`, `time_in_hospital`, `medical_specialty`, `num_lab_procedures`, `num_procedures`, `num_medications`, `number_outpatient`, `number_emergency`, `number_inpatient`, `number_diagnoses`, `max_glu_serum`, `A1Cresult`, `change`, `diabetesMed`, `readmitted`) VALUES
(1, 1, 1, 1, 1, 'MC', 3, 'InternalMedicine', 10, 1, 2, 0, 0, 0, 3, 'None', 'None', 'No', 'No', 'no'),
(4, 1001, 1, 1, 1, 'MC', 3, 'InternalMedicine', 10, 1, 0, 0, 0, 0, 3, 'None', 'None', 'No', 'No', '<30'),
(10, 1001, 1, 1, 1, 'MC', 3, 'InternalMedicine', 10, 1, 1, 0, 0, 0, 3, 'None', 'None', 'No', 'No', '<30'),
(14, 1, 1, 1, 1, 'MC', 3, 'InternalMedicine', 10, 1, 0, 0, 0, 0, 3, 'None', 'None', 'No', 'No', 'NO'),
(18, 1001, 1, 1, 1, 'MC', 3, 'InternalMedicine', 10, 1, 0, 0, 0, 0, 3, 'None', 'None', 'No', 'No', 'NO'),
(1002, 1002, 1, 1, 1, 'MC', 5, 'General', 10, 0, 0, 0, 0, 0, 3, 'None', 'None', 'No', 'No', 'NO'),
(2001, 1001, 1, 1, 1, 'MC', 5, 'Internal Medicine', 10, 1, 2, 0, 0, 0, 1, 'None', 'None', 'No', 'Yes', 'no'),
(3001, 1001, 1, 1, 1, 'MC', 5, 'General', 10, 0, 1, 0, 0, 0, 3, 'None', 'None', 'No', 'No', 'NO'),
(3002, 1001, 1, 1, 1, 'MC', 5, 'General', 10, 0, 0, 0, 0, 0, 3, 'None', 'None', 'No', 'No', 'NO'),
(3003, 1001, 1, 1, 1, 'MC', 3, 'InternalMedicine', 10, 1, 0, 0, 0, 0, 3, 'None', 'None', 'No', 'No', '>30');

--
-- Tetikleyiciler `Encounter`
--
DROP TRIGGER IF EXISTS `trg_log_readmission`;
DELIMITER $$
CREATE TRIGGER `trg_log_readmission` AFTER UPDATE ON `Encounter` FOR EACH ROW BEGIN
  IF NOT (OLD.readmitted <=> NEW.readmitted) THEN
    INSERT INTO Readmission_Log(encounter_id, old_value, new_value)
    VALUES (OLD.encounter_id, OLD.readmitted, NEW.readmitted);
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Encounter_Diagnosis`
--

DROP TABLE IF EXISTS `Encounter_Diagnosis`;
CREATE TABLE IF NOT EXISTS `Encounter_Diagnosis` (
  `encounter_id` bigint(20) NOT NULL,
  `position` int(11) NOT NULL,
  `icd9_code` varchar(10) NOT NULL,
  PRIMARY KEY (`encounter_id`,`position`),
  KEY `fk_ed_diag` (`icd9_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Encounter_Diagnosis`
--

INSERT INTO `Encounter_Diagnosis` (`encounter_id`, `position`, `icd9_code`) VALUES
(1, 1, '250.00'),
(4, 1, '250.00'),
(10, 1, '250.00'),
(14, 1, '250.00'),
(18, 1, '250.00'),
(1002, 1, '250.00'),
(1002, 2, '250.00'),
(1002, 3, '250.00'),
(2001, 1, '250.00'),
(2001, 2, '250.00'),
(2001, 3, '250.00'),
(3001, 1, '250.00'),
(3002, 1, '250.00'),
(3003, 1, '250.00'),
(1, 2, '401.9'),
(4, 2, '401.9'),
(10, 2, '401.9'),
(14, 2, '401.9'),
(18, 2, '401.9'),
(3001, 2, '401.9'),
(3002, 2, '401.9'),
(3003, 2, '401.9'),
(1, 3, '414.01'),
(4, 3, '414.01'),
(10, 3, '414.01'),
(14, 3, '414.01'),
(18, 3, '414.01'),
(3001, 3, '414.01'),
(3002, 3, '414.01'),
(3003, 3, '414.01');

--
-- Tetikleyiciler `Encounter_Diagnosis`
--
DROP TRIGGER IF EXISTS `trg_limit_diagnosis`;
DELIMITER $$
CREATE TRIGGER `trg_limit_diagnosis` BEFORE INSERT ON `Encounter_Diagnosis` FOR EACH ROW BEGIN
  DECLARE cnt INT;

  SELECT COUNT(*) INTO cnt
  FROM Encounter_Diagnosis
  WHERE encounter_id = NEW.encounter_id;

  IF cnt >= 3 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cannot insert more than 3 diagnoses per encounter.';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Encounter_Medication`
--

DROP TABLE IF EXISTS `Encounter_Medication`;
CREATE TABLE IF NOT EXISTS `Encounter_Medication` (
  `encounter_id` bigint(20) NOT NULL,
  `medication_name` varchar(40) NOT NULL,
  `value` varchar(10) NOT NULL,
  PRIMARY KEY (`encounter_id`,`medication_name`),
  KEY `fk_em_med` (`medication_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Encounter_Medication`
--

INSERT INTO `Encounter_Medication` (`encounter_id`, `medication_name`, `value`) VALUES
(1, 'insulin', 'Up'),
(1, 'metformin', 'Up'),
(10, 'metformin', 'Up'),
(2001, 'insulin', 'Yes'),
(2001, 'metformin', 'Up'),
(3001, 'insulin', 'Steady');

--
-- Tetikleyiciler `Encounter_Medication`
--
DROP TRIGGER IF EXISTS `trg_inc_med_count`;
DELIMITER $$
CREATE TRIGGER `trg_inc_med_count` AFTER INSERT ON `Encounter_Medication` FOR EACH ROW BEGIN
  UPDATE Encounter
  SET num_medications = num_medications + 1
  WHERE encounter_id = NEW.encounter_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Medication`
--

DROP TABLE IF EXISTS `Medication`;
CREATE TABLE IF NOT EXISTS `Medication` (
  `medication_name` varchar(40) NOT NULL,
  PRIMARY KEY (`medication_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Medication`
--

INSERT INTO `Medication` (`medication_name`) VALUES
('acarbose'),
('glipizide'),
('glyburide'),
('insulin'),
('metformin'),
('miglitol'),
('pioglitazone'),
('rosiglitazone'),
('tolazamide'),
('troglitazone');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Patient`
--

DROP TABLE IF EXISTS `Patient`;
CREATE TABLE IF NOT EXISTS `Patient` (
  `patient_nbr` bigint(20) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `age_band` varchar(20) NOT NULL,
  `race` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`patient_nbr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Patient`
--

INSERT INTO `Patient` (`patient_nbr`, `gender`, `age_band`, `race`) VALUES
(1, 'male', '25', 'Turkish'),
(1001, 'MaLe', '70-80', 'Caucasian'),
(1002, 'Male', '20', 'white');

--
-- Tetikleyiciler `Patient`
--
DROP TRIGGER IF EXISTS `trg_validate_gender`;
DELIMITER $$
CREATE TRIGGER `trg_validate_gender` BEFORE UPDATE ON `Patient` FOR EACH ROW BEGIN
  IF NEW.gender NOT IN ('Male', 'Female', 'Unknown/Invalid') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Invalid gender value!';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Payer`
--

DROP TABLE IF EXISTS `Payer`;
CREATE TABLE IF NOT EXISTS `Payer` (
  `payer_code` varchar(6) NOT NULL,
  `description` varchar(120) NOT NULL,
  PRIMARY KEY (`payer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Payer`
--

INSERT INTO `Payer` (`payer_code`, `description`) VALUES
('MC', 'Medicare');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `Readmission_Log`
--

DROP TABLE IF EXISTS `Readmission_Log`;
CREATE TABLE IF NOT EXISTS `Readmission_Log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `encounter_id` bigint(20) NOT NULL,
  `old_value` varchar(10) DEFAULT NULL,
  `new_value` varchar(10) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `Readmission_Log`
--

INSERT INTO `Readmission_Log` (`log_id`, `encounter_id`, `old_value`, `new_value`, `changed_at`) VALUES
(1, 2001, 'NO', '<30', '2025-12-27 17:49:09'),
(2, 2001, '<30', '>30', '2025-12-27 17:49:21'),
(3, 2001, '>30', '<30', '2025-12-27 18:05:12'),
(4, 2001, '<30', '>30', '2025-12-28 14:05:01'),
(5, 2001, '>30', 'no', '2025-12-28 14:12:46'),
(6, 2001, 'no', '25', '2025-12-28 14:13:06'),
(7, 2001, '25', 'abc', '2025-12-28 14:13:13'),
(8, 2001, 'abc', 'babü', '2025-12-28 14:13:19'),
(9, 2001, 'babü', 'abc', '2025-12-28 14:28:32'),
(10, 1, '<30', 'no', '2025-12-28 16:40:14'),
(11, 2001, 'abc', 'no', '2025-12-28 20:34:20');

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `Encounter`
--
ALTER TABLE `Encounter`
  ADD CONSTRAINT `fk_enc_admtype` FOREIGN KEY (`admission_type_id`) REFERENCES `Admission_Type` (`admission_type_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enc_discharge` FOREIGN KEY (`discharge_disposition_id`) REFERENCES `Discharge_Disposition` (`discharge_disposition_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enc_patient` FOREIGN KEY (`patient_nbr`) REFERENCES `Patient` (`patient_nbr`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enc_payer` FOREIGN KEY (`payer_code`) REFERENCES `Payer` (`payer_code`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_enc_source` FOREIGN KEY (`admission_source_id`) REFERENCES `Admission_Source` (`admission_source_id`) ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `Encounter_Diagnosis`
--
ALTER TABLE `Encounter_Diagnosis`
  ADD CONSTRAINT `fk_ed_diag` FOREIGN KEY (`icd9_code`) REFERENCES `Diagnosis` (`icd9_code`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ed_enc` FOREIGN KEY (`encounter_id`) REFERENCES `Encounter` (`encounter_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `Encounter_Medication`
--
ALTER TABLE `Encounter_Medication`
  ADD CONSTRAINT `fk_em_enc` FOREIGN KEY (`encounter_id`) REFERENCES `Encounter` (`encounter_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_em_med` FOREIGN KEY (`medication_name`) REFERENCES `Medication` (`medication_name`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
