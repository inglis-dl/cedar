SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

DROP SCHEMA IF EXISTS `curry` ;
CREATE SCHEMA IF NOT EXISTS `curry` DEFAULT CHARACTER SET utf8 ;
USE `curry` ;

-- -----------------------------------------------------
-- Table `curry`.`setting`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`setting` ;

CREATE  TABLE IF NOT EXISTS `curry`.`setting` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `category` VARCHAR(45) NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `type` ENUM('boolean', 'integer', 'float', 'string') NOT NULL ,
  `value` VARCHAR(45) NOT NULL ,
  `description` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `dk_category` (`category` ASC) ,
  INDEX `dk_name` (`name` ASC) ,
  UNIQUE INDEX `uq_category_name` (`category` ASC, `name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `curry`.`setting_value`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`setting_value` ;

CREATE  TABLE IF NOT EXISTS `curry`.`setting_value` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `setting_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `value` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  UNIQUE INDEX `uq_setting_id_site_id` (`setting_id` ASC, `site_id` ASC) ,
  INDEX `fk_setting_id` (`setting_id` ASC) ,
  CONSTRAINT `fk_setting_value_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `cenozo`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_setting_value_setting_id`
    FOREIGN KEY (`setting_id` )
    REFERENCES `curry`.`setting` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Site-specific setting overriding the default.';


-- -----------------------------------------------------
-- Table `curry`.`operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`operation` ;

CREATE  TABLE IF NOT EXISTS `curry`.`operation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `type` ENUM('push','pull','widget') NOT NULL ,
  `subject` VARCHAR(45) NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `restricted` TINYINT(1) NOT NULL DEFAULT 1 ,
  `description` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_type_subject_name` (`type` ASC, `subject` ASC, `name` ASC) ,
  INDEX `dk_type` (`type` ASC) ,
  INDEX `dk_subject` (`subject` ASC) ,
  INDEX `dk_name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `curry`.`activity`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`activity` ;

CREATE  TABLE IF NOT EXISTS `curry`.`activity` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `site_id` INT UNSIGNED NOT NULL ,
  `role_id` INT UNSIGNED NOT NULL ,
  `operation_id` INT UNSIGNED NOT NULL ,
  `query` VARCHAR(511) NOT NULL ,
  `elapsed` FLOAT NOT NULL DEFAULT 0 COMMENT 'The total time to perform the operation in seconds.' ,
  `error_code` VARCHAR(20) NULL DEFAULT '(incomplete)' COMMENT 'NULL if no error occurred.' ,
  `datetime` DATETIME NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_operation_id` (`operation_id` ASC) ,
  INDEX `dk_datetime` (`datetime` ASC) ,
  CONSTRAINT `fk_activity_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `cenozo`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_role_id`
    FOREIGN KEY (`role_id` )
    REFERENCES `cenozo`.`role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `cenozo`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_activity_operation_id`
    FOREIGN KEY (`operation_id` )
    REFERENCES `curry`.`operation` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `curry`.`role_has_operation`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`role_has_operation` ;

CREATE  TABLE IF NOT EXISTS `curry`.`role_has_operation` (
  `role_id` INT UNSIGNED NOT NULL ,
  `operation_id` INT UNSIGNED NOT NULL ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  PRIMARY KEY (`role_id`, `operation_id`) ,
  INDEX `fk_operation_id` (`operation_id` ASC) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  CONSTRAINT `fk_role_has_operation_role_id`
    FOREIGN KEY (`role_id` )
    REFERENCES `cenozo`.`role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_role_has_operation_operation_id`
    FOREIGN KEY (`operation_id` )
    REFERENCES `curry`.`operation` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `curry`.`system_message`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`system_message` ;

CREATE  TABLE IF NOT EXISTS `curry`.`system_message` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `site_id` INT UNSIGNED NULL DEFAULT NULL ,
  `role_id` INT UNSIGNED NULL DEFAULT NULL ,
  `title` VARCHAR(255) NOT NULL ,
  `note` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_site_id` (`site_id` ASC) ,
  INDEX `fk_role_id` (`role_id` ASC) ,
  CONSTRAINT `fk_system_message_site_id`
    FOREIGN KEY (`site_id` )
    REFERENCES `cenozo`.`site` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_system_message_role_id`
    FOREIGN KEY (`role_id` )
    REFERENCES `cenozo`.`role` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `curry`.`recording`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`recording` ;

CREATE  TABLE IF NOT EXISTS `curry`.`recording` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `file_name` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_file_name` (`file_name` ASC) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  CONSTRAINT `fk_recording_participant1`
    FOREIGN KEY (`participant_id` )
    REFERENCES `cenozo`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `curry`.`dictionary`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`dictionary` ;

CREATE  TABLE IF NOT EXISTS `curry`.`dictionary` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `uq_name` (`name` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `curry`.`word`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`word` ;

CREATE  TABLE IF NOT EXISTS `curry`.`word` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `dictionary_id` INT UNSIGNED NOT NULL ,
  `language` ENUM('en','fr') NOT NULL DEFAULT 'en' ,
  `word` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_dictionary_id` (`dictionary_id` ASC) ,
  UNIQUE INDEX `uq_word` (`word` ASC) ,
  CONSTRAINT `fk_word_dictionary_id`
    FOREIGN KEY (`dictionary_id` )
    REFERENCES `curry`.`dictionary` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `curry`.`test`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`test` ;

CREATE  TABLE IF NOT EXISTS `curry`.`test` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `name` VARCHAR(45) NOT NULL ,
  `dictionary_id` INT UNSIGNED NULL DEFAULT NULL ,
  `intrusion_dictionary_id` INT UNSIGNED NULL DEFAULT NULL ,
  `variant_dictionary_id` INT UNSIGNED NULL DEFAULT NULL ,
  `strict` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = allow non dictionary words' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_dictionary_id` (`dictionary_id` ASC) ,
  INDEX `fk_intrusion_dictionary_id` (`intrusion_dictionary_id` ASC) ,
  INDEX `fk_variant_dictionary_id` (`variant_dictionary_id` ASC) ,
  UNIQUE INDEX `uq_name` (`name` ASC) ,
  CONSTRAINT `fk_test_dictionary_id`
    FOREIGN KEY (`dictionary_id` )
    REFERENCES `curry`.`dictionary` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_intrusion_dictionary_id`
    FOREIGN KEY (`intrusion_dictionary_id` )
    REFERENCES `curry`.`dictionary` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_variant_dictionary_id`
    FOREIGN KEY (`variant_dictionary_id` )
    REFERENCES `curry`.`dictionary` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `curry`.`test_entry`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`test_entry` ;

CREATE  TABLE IF NOT EXISTS `curry`.`test_entry` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `test_id` INT UNSIGNED NOT NULL ,
  `user_id` INT UNSIGNED NOT NULL ,
  `participant_id` INT UNSIGNED NOT NULL ,
  `audio_fault` TINYINT(1) NOT NULL DEFAULT 0 ,
  `completed` TINYINT(1) NOT NULL DEFAULT 0 ,
  `deferred` TINYINT(1) NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_test_id` (`test_id` ASC) ,
  INDEX `fk_user_id` (`user_id` ASC) ,
  INDEX `fk_participant_id` (`participant_id` ASC) ,
  CONSTRAINT `fk_test_entry_test_id`
    FOREIGN KEY (`test_id` )
    REFERENCES `curry`.`test` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `cenozo`.`user` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_participant_id`
    FOREIGN KEY (`participant_id` )
    REFERENCES `cenozo`.`participant` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `curry`.`test_entry_word`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `curry`.`test_entry_word` ;

CREATE  TABLE IF NOT EXISTS `curry`.`test_entry_word` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `update_timestamp` TIMESTAMP NOT NULL ,
  `create_timestamp` TIMESTAMP NOT NULL ,
  `test_entry_id` INT UNSIGNED NOT NULL ,
  `word_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'if NULL word_candidate NOT NULL' ,
  `word_candidate` VARCHAR(45) NULL DEFAULT NULL COMMENT 'if NULL word_id NOT NULL' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_test_entry_id` (`test_entry_id` ASC) ,
  INDEX `fk_word_id` (`word_id` ASC) ,
  CONSTRAINT `fk_test_entry_word_test_entry_id`
    FOREIGN KEY (`test_entry_id` )
    REFERENCES `curry`.`test_entry` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_test_entry_word_word_id`
    FOREIGN KEY (`word_id` )
    REFERENCES `curry`.`word` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

USE `cenozo`;

DELIMITER $$

DELIMITER ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
