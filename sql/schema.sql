SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

DROP SCHEMA IF EXISTS `cedar` ;
CREATE SCHEMA IF NOT EXISTS `cedar` DEFAULT CHARACTER SET utf8 ;
USE `cedar` ;

-- -----------------------------------------------------
-- Table `cedar`.`setting`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`setting` ;

CREATE TABLE IF NOT EXISTS `cedar`.`setting` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `category` VARCHAR(45) NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  `type` ENUM('boolean', 'integer', 'float', 'string') NOT NULL,
  `value` VARCHAR(45) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `dk_category` (`category` ASC),
  INDEX `dk_name` (`name` ASC),
  UNIQUE INDEX `uq_category_name` (`category` ASC, `name` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`setting_value`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`setting_value` ;

CREATE TABLE IF NOT EXISTS `cedar`.`setting_value` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `setting_id` INT UNSIGNED NOT NULL,
  `site_id` INT UNSIGNED NOT NULL,
  `value` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_site_id` (`site_id` ASC),
  UNIQUE INDEX `uq_setting_id_site_id` (`setting_id` ASC, `site_id` ASC),
  INDEX `fk_setting_id` (`setting_id` ASC),
  CONSTRAINT `fk_setting_value_site_id`
    FOREIGN KEY (`site_id`)
    REFERENCES `cenozo`.`site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_setting_value_setting_id`
    FOREIGN KEY (`setting_id`)
    REFERENCES `cedar`.`setting` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Site-specific setting overriding the default.';


-- -----------------------------------------------------
-- Table `cedar`.`operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`operation` ;

CREATE TABLE IF NOT EXISTS `cedar`.`operation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `type` ENUM('push','pull','widget') NOT NULL,
  `subject` VARCHAR(45) NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  `restricted` TINYINT(1) NOT NULL DEFAULT 1,
  `description` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_type_subject_name` (`type` ASC, `subject` ASC, `name` ASC),
  INDEX `dk_type` (`type` ASC),
  INDEX `dk_subject` (`subject` ASC),
  INDEX `dk_name` (`name` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`activity`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`activity` ;

CREATE TABLE IF NOT EXISTS `cedar`.`activity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `site_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `operation_id` INT UNSIGNED NOT NULL,
  `query` VARCHAR(511) NOT NULL,
  `elapsed` FLOAT NOT NULL DEFAULT 0 COMMENT 'The total time to perform the operation in seconds.',
  `error_code` VARCHAR(20) NULL DEFAULT '(incomplete)' COMMENT 'NULL if no error occurred.',
  `datetime` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_user_id` (`user_id` ASC),
  INDEX `fk_role_id` (`role_id` ASC),
  INDEX `fk_site_id` (`site_id` ASC),
  INDEX `fk_operation_id` (`operation_id` ASC),
  INDEX `dk_datetime` (`datetime` ASC),
  CONSTRAINT `fk_activity_user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `cenozo`.`user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_role_id`
    FOREIGN KEY (`role_id`)
    REFERENCES `cenozo`.`role` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_site_id`
    FOREIGN KEY (`site_id`)
    REFERENCES `cenozo`.`site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_operation_id`
    FOREIGN KEY (`operation_id`)
    REFERENCES `cedar`.`operation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`role_has_operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`role_has_operation` ;

CREATE TABLE IF NOT EXISTS `cedar`.`role_has_operation` (
  `role_id` INT UNSIGNED NOT NULL,
  `operation_id` INT UNSIGNED NOT NULL,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  PRIMARY KEY (`role_id`, `operation_id`),
  INDEX `fk_operation_id` (`operation_id` ASC),
  INDEX `fk_role_id` (`role_id` ASC),
  CONSTRAINT `fk_role_has_operation_role_id`
    FOREIGN KEY (`role_id`)
    REFERENCES `cenozo`.`role` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_role_has_operation_operation_id`
    FOREIGN KEY (`operation_id`)
    REFERENCES `cedar`.`operation` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`system_message`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`system_message` ;

CREATE TABLE IF NOT EXISTS `cedar`.`system_message` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `site_id` INT UNSIGNED NULL DEFAULT NULL,
  `role_id` INT UNSIGNED NULL DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `note` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_site_id` (`site_id` ASC),
  INDEX `fk_role_id` (`role_id` ASC),
  CONSTRAINT `fk_system_message_site_id`
    FOREIGN KEY (`site_id`)
    REFERENCES `cenozo`.`site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_system_message_role_id`
    FOREIGN KEY (`role_id`)
    REFERENCES `cenozo`.`role` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`dictionary`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`dictionary` ;

CREATE TABLE IF NOT EXISTS `cedar`.`dictionary` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_name` (`name` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`word`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`word` ;

CREATE TABLE IF NOT EXISTS `cedar`.`word` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `dictionary_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `word` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_dictionary_id` (`dictionary_id` ASC),
  UNIQUE INDEX `uq_word_dictionary_id_language_id` (`word` ASC, `dictionary_id` ASC, `language_id` ASC),
  INDEX `fk_language_id` (`language_id` ASC),
  CONSTRAINT `fk_word_dictionary_id`
    FOREIGN KEY (`dictionary_id`)
    REFERENCES `cedar`.`dictionary` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_word_language_id`
    FOREIGN KEY (`language_id`)
    REFERENCES `cenozo`.`language` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`test_type`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`test_type` ;

CREATE TABLE IF NOT EXISTS `cedar`.`test_type` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`test`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`test` ;

CREATE TABLE IF NOT EXISTS `cedar`.`test` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `name` VARCHAR(45) NOT NULL,
  `dictionary_id` INT UNSIGNED NULL DEFAULT NULL,
  `intrusion_dictionary_id` INT UNSIGNED NULL DEFAULT NULL,
  `variant_dictionary_id` INT UNSIGNED NULL DEFAULT NULL,
  `mispelled_dictionary_id` INT UNSIGNED NULL DEFAULT NULL,
  `test_type_id` INT UNSIGNED NOT NULL,
  `strict` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = allow non dictionary words',
  `rank_words` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = requires ranked words',
  `rank` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_dictionary_id` (`dictionary_id` ASC),
  INDEX `fk_intrusion_dictionary_id` (`intrusion_dictionary_id` ASC),
  INDEX `fk_variant_dictionary_id` (`variant_dictionary_id` ASC),
  UNIQUE INDEX `uq_name` (`name` ASC),
  UNIQUE INDEX `uq_rank` (`rank` ASC),
  INDEX `fk_test_type_id` (`test_type_id` ASC),
  INDEX `fk_mispelled_dictionary_id` (`mispelled_dictionary_id` ASC),
  CONSTRAINT `fk_test_dictionary_id`
    FOREIGN KEY (`dictionary_id`)
    REFERENCES `cedar`.`dictionary` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_intrusion_dictionary_id`
    FOREIGN KEY (`intrusion_dictionary_id`)
    REFERENCES `cedar`.`dictionary` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_variant_dictionary_id`
    FOREIGN KEY (`variant_dictionary_id`)
    REFERENCES `cedar`.`dictionary` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_test_type_id`
    FOREIGN KEY (`test_type_id`)
    REFERENCES `cedar`.`test_type` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_mispelled_dictionary_id`
    FOREIGN KEY (`mispelled_dictionary_id`)
    REFERENCES `cedar`.`dictionary` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`assignment`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`assignment` ;

CREATE TABLE IF NOT EXISTS `cedar`.`assignment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `participant_id` INT UNSIGNED NOT NULL,
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_participant_id` (`participant_id` ASC),
  INDEX `fk_user_id` (`user_id` ASC),
  UNIQUE INDEX `uq_user_id_participant_id` (`user_id` ASC, `participant_id` ASC),
  CONSTRAINT `fk_assignment_participant_id`
    FOREIGN KEY (`participant_id`)
    REFERENCES `cenozo`.`participant` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_assignment_user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `cenozo`.`user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`test_entry`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`test_entry` ;

CREATE TABLE IF NOT EXISTS `cedar`.`test_entry` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `test_id` INT UNSIGNED NOT NULL,
  `assignment_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL id signifies adjudicate entry',
  `participant_id` INT UNSIGNED NULL DEFAULT NULL,
  `audio_status` ENUM('salvageable','unusable','unavailable') NULL DEFAULT NULL,
  `participant_status` ENUM('suspected prompt','prompted','refused') NULL DEFAULT NULL,
  `audio_fault` TINYINT(1) NOT NULL DEFAULT 0,
  `completed` TINYINT(1) NOT NULL DEFAULT 0,
  `deferred` TINYINT(1) NOT NULL DEFAULT 0,
  `adjudicate` TINYINT(1) NULL DEFAULT NULL COMMENT '0 , 1, or NULL (never set)',
  PRIMARY KEY (`id`),
  INDEX `fk_test_id` (`test_id` ASC),
  INDEX `fk_assignment_id` (`assignment_id` ASC),
  UNIQUE INDEX `uq_test_id_assignment_id` (`test_id` ASC, `assignment_id` ASC),
  INDEX `fk_participant_id` (`participant_id` ASC),
  UNIQUE INDEX `uq_test_id_participant_id` (`test_id` ASC, `participant_id` ASC),
  CONSTRAINT `fk_test_entry_test_id`
    FOREIGN KEY (`test_id`)
    REFERENCES `cedar`.`test` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_assignment_id`
    FOREIGN KEY (`assignment_id`)
    REFERENCES `cedar`.`assignment` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_participant_id`
    FOREIGN KEY (`participant_id`)
    REFERENCES `cenozo`.`participant` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`test_entry_confirmation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`test_entry_confirmation` ;

CREATE TABLE IF NOT EXISTS `cedar`.`test_entry_confirmation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `test_entry_id` INT UNSIGNED NOT NULL,
  `confirmation` TINYINT(1) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_test_entry_id` (`test_entry_id` ASC),
  UNIQUE INDEX `uq_test_entry_id` (`test_entry_id` ASC),
  CONSTRAINT `fk_test_entry_confirmation_test_entry_id`
    FOREIGN KEY (`test_entry_id`)
    REFERENCES `cedar`.`test_entry` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`user_has_cohort`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`user_has_cohort` ;

CREATE TABLE IF NOT EXISTS `cedar`.`user_has_cohort` (
  `user_id` INT UNSIGNED NOT NULL,
  `cohort_id` INT UNSIGNED NOT NULL,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  PRIMARY KEY (`user_id`, `cohort_id`),
  INDEX `fk_cohort_id` (`cohort_id` ASC),
  INDEX `fk_user_id` (`user_id` ASC),
  CONSTRAINT `fk_user_has_cohort_user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `cenozo`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_has_cohort_cohort_id`
    FOREIGN KEY (`cohort_id`)
    REFERENCES `cenozo`.`cohort` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`ranked_word_set`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`ranked_word_set` ;

CREATE TABLE IF NOT EXISTS `cedar`.`ranked_word_set` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `test_id` INT UNSIGNED NOT NULL,
  `rank` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_test_id` (`test_id` ASC),
  UNIQUE INDEX `uq_test_id_rank` (`test_id` ASC, `rank` ASC),
  CONSTRAINT `fk_ranked_word_set_test_id`
    FOREIGN KEY (`test_id`)
    REFERENCES `cedar`.`test` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`dictionary_import`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`dictionary_import` ;

CREATE TABLE IF NOT EXISTS `cedar`.`dictionary_import` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `md5` VARCHAR(45) NOT NULL,
  `data` MEDIUMBLOB NOT NULL,
  `dictionary_id` INT UNSIGNED NULL DEFAULT NULL,
  `serialization` MEDIUMBLOB NULL DEFAULT NULL,
  `processed` TINYINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_md5` (`md5` ASC),
  INDEX `fk_dictionary_id` (`dictionary_id` ASC),
  CONSTRAINT `fk_dictionary_import_dictionary_id`
    FOREIGN KEY (`dictionary_id`)
    REFERENCES `cedar`.`dictionary` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`test_entry_ranked_word`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`test_entry_ranked_word` ;

CREATE TABLE IF NOT EXISTS `cedar`.`test_entry_ranked_word` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `test_entry_id` INT UNSIGNED NOT NULL,
  `ranked_word_set_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'if NULL this is an intrusion',
  `word_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'if NOT NULL then a variant or intrusion',
  `selection` ENUM('yes','no','variant') NULL DEFAULT NULL COMMENT 'if NULL an intrusion or not filled in',
  PRIMARY KEY (`id`),
  INDEX `fk_test_entry_id` (`test_entry_id` ASC),
  INDEX `fk_word_id` (`word_id` ASC),
  INDEX `fk_ranked_word_set_id` (`ranked_word_set_id` ASC),
  CONSTRAINT `fk_test_entry_ranked_word_test_entry_id`
    FOREIGN KEY (`test_entry_id`)
    REFERENCES `cedar`.`test_entry` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_ranked_word_word_id`
    FOREIGN KEY (`word_id`)
    REFERENCES `cedar`.`word` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_ranked_word_ranked_word_set_id`
    FOREIGN KEY (`ranked_word_set_id`)
    REFERENCES `cedar`.`ranked_word_set` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`test_entry_classification`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`test_entry_classification` ;

CREATE TABLE IF NOT EXISTS `cedar`.`test_entry_classification` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `test_entry_id` INT UNSIGNED NOT NULL,
  `word_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL if not set yet',
  `rank` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_test_entry_id` (`test_entry_id` ASC),
  INDEX `fk_word_id` (`word_id` ASC),
  UNIQUE INDEX `uq_test_entry_id_rank` (`test_entry_id` ASC, `rank` ASC),
  CONSTRAINT `fk_test_entry_classification_test_entry_id`
    FOREIGN KEY (`test_entry_id`)
    REFERENCES `cedar`.`test_entry` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_classification_word_id`
    FOREIGN KEY (`word_id`)
    REFERENCES `cedar`.`word` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`test_entry_alpha_numeric`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`test_entry_alpha_numeric` ;

CREATE TABLE IF NOT EXISTS `cedar`.`test_entry_alpha_numeric` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `test_entry_id` INT UNSIGNED NOT NULL,
  `word_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'if NULL word_candidate NOT NULL',
  `rank` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_test_entry_id` (`test_entry_id` ASC),
  INDEX `fk_word_id` (`word_id` ASC),
  UNIQUE INDEX `uq_test_entry_id_rank` (`test_entry_id` ASC, `rank` ASC),
  CONSTRAINT `fk_test_entry_alpha_numeric_test_entry_id`
    FOREIGN KEY (`test_entry_id`)
    REFERENCES `cedar`.`test_entry` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_alpha_numeric_word_id`
    FOREIGN KEY (`word_id`)
    REFERENCES `cedar`.`word` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`test_entry_note`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`test_entry_note` ;

CREATE TABLE IF NOT EXISTS `cedar`.`test_entry_note` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `test_entry_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `sticky` TINYINT(1) NOT NULL DEFAULT 0,
  `datetime` DATETIME NOT NULL,
  `note` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_test_entry_id` (`test_entry_id` ASC),
  INDEX `fk_user_id` (`user_id` ASC),
  INDEX `dk_sticky_datetime` (`sticky` ASC, `datetime` ASC),
  CONSTRAINT `fk_test_entry_note_test_entry_id`
    FOREIGN KEY (`test_entry_id`)
    REFERENCES `cedar`.`test_entry` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_note_user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `cenozo`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`away_time`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`away_time` ;

CREATE TABLE IF NOT EXISTS `cedar`.`away_time` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `site_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `start_datetime` DATETIME NOT NULL,
  `end_datetime` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_user_id` (`user_id` ASC),
  INDEX `fk_site_id` (`site_id` ASC),
  INDEX `fk_role_id` (`role_id` ASC),
  INDEX `dk_start_datetime` (`start_datetime` ASC),
  INDEX `dk_end_datetime` (`end_datetime` ASC),
  CONSTRAINT `fk_away_time_user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `cenozo`.`user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_away_time_site_id`
    FOREIGN KEY (`site_id`)
    REFERENCES `cenozo`.`site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_away_time_role_id`
    FOREIGN KEY (`role_id`)
    REFERENCES `cenozo`.`role` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`user_time`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`user_time` ;

CREATE TABLE IF NOT EXISTS `cedar`.`user_time` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `site_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `total` FLOAT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_user_id` (`user_id` ASC),
  INDEX `fk_site_id` (`site_id` ASC),
  INDEX `fk_role_id` (`role_id` ASC),
  UNIQUE INDEX `uq_user_id_site_id_role_id_date` (`user_id` ASC, `site_id` ASC, `role_id` ASC, `date` ASC),
  INDEX `dk_date` (`date` ASC),
  CONSTRAINT `fk_user_time_user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `cenozo`.`user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_time_site_id`
    FOREIGN KEY (`site_id`)
    REFERENCES `cenozo`.`site` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_user_time_role_id`
    FOREIGN KEY (`role_id`)
    REFERENCES `cenozo`.`role` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `cedar`.`ranked_word_set_has_language`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`ranked_word_set_has_language` ;

CREATE TABLE IF NOT EXISTS `cedar`.`ranked_word_set_has_language` (
  `ranked_word_set_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  `word_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`ranked_word_set_id`, `language_id`),
  INDEX `fk_language_id` (`language_id` ASC),
  INDEX `fk_ranked_word_set_id` (`ranked_word_set_id` ASC),
  INDEX `fk_word_id` (`word_id` ASC),
  CONSTRAINT `fk_ranked_word_set_has_language_ranked_word_set_id`
    FOREIGN KEY (`ranked_word_set_id`)
    REFERENCES `cedar`.`ranked_word_set` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ranked_word_set_has_language_language_id`
    FOREIGN KEY (`language_id`)
    REFERENCES `cenozo`.`language` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ranked_word_set_has_language_word_id`
    FOREIGN KEY (`word_id`)
    REFERENCES `cedar`.`word` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

USE `cedar` ;

-- -----------------------------------------------------
-- Placeholder table for view `cedar`.`sabretooth_recording`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cedar`.`sabretooth_recording` (`interview_id` INT, `assignment_id` INT, `rank` INT, `participant_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cedar`.`assignment_total`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cedar`.`assignment_total` (`assignment_id` INT, `deferred` INT, `adjudicate` INT, `completed` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cedar`.`test_entry_total_completed`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cedar`.`test_entry_total_completed` (`assignment_id` INT, `completed` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cedar`.`test_entry_total_deferred`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cedar`.`test_entry_total_deferred` (`assignment_id` INT, `deferred` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cedar`.`test_entry_total_adjudicate`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cedar`.`test_entry_total_adjudicate` (`assignment_id` INT, `adjudicate` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cedar`.`test_entry_total`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cedar`.`test_entry_total` (`assignment_id` INT, `total` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cedar`.`classification_word_total`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cedar`.`classification_word_total` (`word_id` INT, `total` INT, `dictionary_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cedar`.`alpha_numeric_word_total`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cedar`.`alpha_numeric_word_total` (`word_id` INT, `total` INT, `dictionary_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cedar`.`ranked_word_word_total`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cedar`.`ranked_word_word_total` (`word_id` INT, `total` INT, `dictionary_id` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cedar`.`confirmation_word_total`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cedar`.`confirmation_word_total` (`word_id` INT, `total` INT, `dictionary_id` INT);

-- -----------------------------------------------------
-- View `cedar`.`sabretooth_recording`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `cedar`.`sabretooth_recording` ;
DROP TABLE IF EXISTS `cedar`.`sabretooth_recording`;
USE `cedar`;
CREATE  OR REPLACE VIEW `sabretooth_recording` AS
SELECT r.interview_id, r.assignment_id, r.rank, i.participant_id
FROM sabretooth.recording r
JOIN sabretooth.interview i ON i.id=r.interview_id;

-- -----------------------------------------------------
-- View `cedar`.`assignment_total`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `cedar`.`assignment_total` ;
DROP TABLE IF EXISTS `cedar`.`assignment_total`;
USE `cedar`;
CREATE  OR REPLACE VIEW `assignment_total` AS
SELECT assignment_id,
SUM( deferred ) AS deferred,
SUM( IFNULL( adjudicate, 0 ) ) AS adjudicate,
SUM( completed ) AS completed
FROM test_entry
WHERE assignment_id IS NOT NULL
GROUP BY assignment_id;

-- -----------------------------------------------------
-- View `cedar`.`test_entry_total_completed`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `cedar`.`test_entry_total_completed` ;
DROP TABLE IF EXISTS `cedar`.`test_entry_total_completed`;
USE `cedar`;
CREATE  OR REPLACE VIEW `test_entry_total_completed` AS
SELECT assignment_id, SUM( completed ) AS completed FROM test_entry
WHERE assignment_id IS NOT NULL
GROUP BY assignment_id;

-- -----------------------------------------------------
-- View `cedar`.`test_entry_total_deferred`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `cedar`.`test_entry_total_deferred` ;
DROP TABLE IF EXISTS `cedar`.`test_entry_total_deferred`;
USE `cedar`;
CREATE  OR REPLACE VIEW `test_entry_total_deferred` AS
SELECT assignment_id, SUM( deferred ) AS deferred FROM test_entry
WHERE assignment_id IS NOT NULL
GROUP BY assignment_id
;

-- -----------------------------------------------------
-- View `cedar`.`test_entry_total_adjudicate`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `cedar`.`test_entry_total_adjudicate` ;
DROP TABLE IF EXISTS `cedar`.`test_entry_total_adjudicate`;
USE `cedar`;
CREATE  OR REPLACE VIEW `test_entry_total_adjudicate` AS
SELECT assignment_id, SUM( IFNULL( adjudicate, 0 ) ) AS adjudicate FROM test_entry
WHERE assignment_id IS NOT NULL
GROUP BY assignment_id;

-- -----------------------------------------------------
-- View `cedar`.`test_entry_total`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `cedar`.`test_entry_total` ;
DROP TABLE IF EXISTS `cedar`.`test_entry_total`;
USE `cedar`;
CREATE  OR REPLACE VIEW `test_entry_total` AS
SELECT assignment_id, COUNT(*) AS total FROM test_entry
WHERE assignment_id IS NOT NULL
GROUP BY assignment_id;

-- -----------------------------------------------------
-- View `cedar`.`classification_word_total`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `cedar`.`classification_word_total` ;
DROP TABLE IF EXISTS `cedar`.`classification_word_total`;
USE `cedar`;
CREATE  OR REPLACE VIEW `classification_word_total` AS
SELECT w.id AS word_id,
COUNT(tec.id) AS total,
w.dictionary_id AS dictionary_id
FROM word w
LEFT JOIN test_entry_classification tec ON tec.word_id=w.id
JOIN dictionary d ON d.id=w.dictionary_id
GROUP BY w.id;

-- -----------------------------------------------------
-- View `cedar`.`alpha_numeric_word_total`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `cedar`.`alpha_numeric_word_total` ;
DROP TABLE IF EXISTS `cedar`.`alpha_numeric_word_total`;
USE `cedar`;
CREATE  OR REPLACE VIEW `alpha_numeric_word_total` AS
SELECT w.id AS word_id,
COUNT(tean.id) AS total,
w.dictionary_id AS dictionary_id
FROM word w
LEFT JOIN test_entry_alpha_numeric tean ON tean.word_id=w.id
JOIN dictionary ON d.id=w.dictionary_id
GROUP BY w.id;

-- -----------------------------------------------------
-- View `cedar`.`ranked_word_word_total`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `cedar`.`ranked_word_word_total` ;
DROP TABLE IF EXISTS `cedar`.`ranked_word_word_total`;
USE `cedar`;
CREATE  OR REPLACE VIEW `ranked_word_word_total` AS
SELECT w.id AS word_id,
COUNT(terw.id) AS total,
w.dictionary_id AS dictionary_id
FROM word w
LEFT JOIN test_entry_ranked_word terw ON terw.word_id=w.id
JOIN dictionary d ON d.id=w.dictionary_id
GROUP BY w.id;

-- -----------------------------------------------------
-- View `cedar`.`confirmation_word_total`
-- -----------------------------------------------------
DROP VIEW IF EXISTS `cedar`.`confirmation_word_total` ;
DROP TABLE IF EXISTS `cedar`.`confirmation_word_total`;
USE `cedar`;
CREATE  OR REPLACE VIEW `confirmation_word_total` AS
SELECT w.id AS word_id, COUNT(tec.id) AS total, w.dictionary_id AS dictionary_id FROM word w
JOIN test t ON t.dictionary_id=w.dictionary_id
JOIN test_entry te ON te.test_id=t.id
JOIN test_entry_confirmation tec ON tec.test_entry_id=te.id
GROUP BY w.id;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
