DROP PROCEDURE IF EXISTS patch_test_entry_note;
DELIMITER //
CREATE PROCEDURE patch_test_entry_note()
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

    SELECT "Adding new test_entry_note table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry_note" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS ", @cedar, ".test_entry_note ( ",
          "id INT UNSIGNED NOT NULL AUTO_INCREMENT, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "test_entry_id INT UNSIGNED NOT NULL, ",
          "user_id INT UNSIGNED NOT NULL, ",
          "datetime DATETIME NOT NULL, ",
          "note TEXT NOT NULL, ",
          "PRIMARY KEY (id), ",
          "INDEX fk_test_entry_id (test_entry_id ASC), ",
          "INDEX fk_user_id (user_id ASC), ",
          "CONSTRAINT fk_test_entry_note_test_entry_id ",
            "FOREIGN KEY (test_entry_id) ",
            "REFERENCES ", @cedar, ".test_entry (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_test_entry_note_user_id ",
            "FOREIGN KEY (user_id) ",
            "REFERENCES ", @cenozo, ".user (id) ",
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
CALL patch_test_entry_note();
DROP PROCEDURE IF EXISTS patch_test_entry_note;
