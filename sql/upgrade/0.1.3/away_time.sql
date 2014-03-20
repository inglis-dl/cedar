DROP PROCEDURE IF EXISTS patch_away_time;
DELIMITER //
CREATE PROCEDURE patch_away_time()
  BEGIN
    -- determine the @cenozo database name
    SET @cedar = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_ranked_word_set_test_id" );

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Adding new away_time table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "away_time" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS ", @cedar, ".away_time ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "site_id INT UNSIGNED NOT NULL, ",
          "role_id INT UNSIGNED NOT NULL, ",
          "start_datetime DATETIME NOT NULL, ",
          "end_datetime DATETIME NULL DEFAULT NULL, ",
          "PRIMARY KEY (id), ",
          "INDEX fk_user_id (user_id ASC), ",
          "INDEX fk_site_id (site_id ASC), ",
          "INDEX fk_role_id (role_id ASC), ",
          "INDEX dk_start_datetime (start_datetime ASC), ",
          "INDEX dk_end_datetime (end_datetime ASC), ",
          "CONSTRAINT fk_away_time_user_id ",
            "FOREIGN KEY (user_id) ",
            "REFERENCES ", @cenozo, ".user (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_away_time_site_id ",
            "FOREIGN KEY (site_id) ",
            "REFERENCES ", @cenozo, ".site (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_away_time_role_id ",
            "FOREIGN KEY (role_id) ",
            "REFERENCES ", @cenozo, ".role (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_away_time();
DROP PROCEDURE IF EXISTS patch_away_time;
