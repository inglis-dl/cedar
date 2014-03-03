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
-- Table `cedar`.`recording`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cedar`.`recording` ;

CREATE TABLE IF NOT EXISTS `cedar`.`recording` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `update_timestamp` TIMESTAMP NOT NULL,
  `create_timestamp` TIMESTAMP NOT NULL,
  `participant_id` INT UNSIGNED NOT NULL,
  `file_name` VARCHAR(45) NOT NULL,
  `language` ENUM('any','en','fr') NOT NULL DEFAULT 'en',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_file_name` (`file_name` ASC),
  INDEX `fk_participant_id` (`participant_id` ASC),
  CONSTRAINT `fk_recording_participant_id`
    FOREIGN KEY (`participant_id`)
    REFERENCES `cenozo`.`participant` (`id`)
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
  `language` ENUM('en','fr') NOT NULL DEFAULT 'en',
  `word` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_dictionary_id` (`dictionary_id` ASC),
  UNIQUE INDEX `uq_dictionary_id_language_word` (`word` ASC, `dictionary_id` ASC, `language` ASC),
  CONSTRAINT `fk_word_dictionary_id`
    FOREIGN KEY (`dictionary_id`)
    REFERENCES `cedar`.`dictionary` (`id`)
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
  PRIMARY KEY (`id`),
  INDEX `fk_participant_id` (`participant_id` ASC),
  INDEX `fk_user_id` (`user_id` ASC),
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
  `audio_fault` TINYINT(1) NOT NULL DEFAULT 0,
  `completed` TINYINT(1) NOT NULL DEFAULT 0,
  `deferred` TINYINT(1) NOT NULL DEFAULT 0,
  `adjudicate` TINYINT(1) NOT NULL DEFAULT 0,
  `note` TEXT NULL DEFAULT NULL COMMENT 'id required to track adjudicate progenitors',
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
  `word_en_id` INT UNSIGNED NOT NULL,
  `word_fr_id` INT UNSIGNED NOT NULL,
  `rank` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_test_id` (`test_id` ASC),
  INDEX `fk_word_en_id` (`word_en_id` ASC),
  INDEX `fk_word_fr_id` (`word_fr_id` ASC),
  UNIQUE INDEX `uq_test_id_rank` (`test_id` ASC, `rank` ASC),
  CONSTRAINT `fk_ranked_word_set_test_id`
    FOREIGN KEY (`test_id`)
    REFERENCES `cedar`.`test` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ranked_word_set_word_en_id`
    FOREIGN KEY (`word_en_id`)
    REFERENCES `cedar`.`word` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ranked_word_set_word_fr_id`
    FOREIGN KEY (`word_fr_id`)
    REFERENCES `cedar`.`word` (`id`)
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
  `word_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'if NULL word_candidate NOT NULL',
  `word_candidate` VARCHAR(45) NULL DEFAULT NULL,
  `selection` ENUM('yes','no','variant') NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_test_entry_id` (`test_entry_id` ASC),
  INDEX `fk_word_id` (`word_id` ASC),
  CONSTRAINT `fk_test_entry_ranked_word_test_entry_id`
    FOREIGN KEY (`test_entry_id`)
    REFERENCES `cedar`.`test_entry` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_ranked_word_word_id`
    FOREIGN KEY (`word_id`)
    REFERENCES `cedar`.`word` (`id`)
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
  `word_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'if NULL word_candidate NOT NULL',
  `word_candidate` VARCHAR(45) NULL DEFAULT NULL,
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


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
