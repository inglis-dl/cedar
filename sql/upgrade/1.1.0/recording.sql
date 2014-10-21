DROP PROCEDURE IF EXISTS patch_recording;
DELIMITER //
CREATE PROCEDURE patch_recording()
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

    SELECT "Adding new recording table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "recording" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS ", @cedar, ".recording ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "participant_id INT UNSIGNED NOT NULL, ",
          "test_id INT UNSIGNED NOT NULL, ",
          "visit INT NOT NULL, ",
          "PRIMARY KEY (id), ",
          "INDEX fk_participant_id (participant_id ASC), ",
          "INDEX fk_test_id (test_id ASC), ",
          "UNIQUE INDEX uq_visit_participant_id_test_id ",
            "(participant_id ASC, test_id ASC, visit ASC), ",
          "CONSTRAINT fk_recording_participant_id ",
            "FOREIGN KEY (participant_id) ",
            "REFERENCES ", @cenozo, ".participant (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_recording_test_id ",
            "FOREIGN KEY (test_id) ",
            "REFERENCES ", @cedar, ".test (id) ",
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
CALL patch_recording();
DROP PROCEDURE IF EXISTS patch_recording;
