DROP PROCEDURE IF EXISTS patch_test_entry_has_language;
DELIMITER //
CREATE PROCEDURE patch_test_entry_has_language()
  BEGIN
    -- determine the @cedar and @cenozo database names
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

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry_has_language" );
    IF @test = 0 THEN

      SELECT "Adding new test_entry_has_language table" AS "";

      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS ", @cedar, ".test_entry_has_language ( ",
          "test_entry_id INT UNSIGNED NOT NULL, ",
          "language_id INT UNSIGNED NOT NULL, ",
          "update_timestamp TIMESTAMP NOT NULL, ",
          "create_timestamp TIMESTAMP NOT NULL, ",
          "PRIMARY KEY (test_entry_id, language_id), ",
          "INDEX fk_language_id (language_id ASC), ",
          "INDEX fk_test_entry_id (test_entry_id ASC), ",
          "CONSTRAINT fk_test_entry_has_language_test_entry_id ",
            "FOREIGN KEY (test_entry_id) ",
            "REFERENCES ", @cedar, ".test_entry (id) ",
            "ON DELETE CASCADE ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_test_entry_has_language_language_id ",
            "FOREIGN KEY (language_id) ",
            "REFERENCES ", @cenozo, ".language (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Populating new test_entry_has_language table" AS "";

      SET @sql = CONCAT(
        "SET @default_language_id=( ",
        "SELECT language_id ",
        "FROM ", @cenozo, ".service ",
        "WHERE name = 'cedar' )" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, IFNULL(p.language_id, @default_language_id) AS language_id ",
        "FROM test_entry t ",
        "JOIN test ON test.id = t.test_id ",
        "JOIN test_type tt ON tt.id = test.test_type_id ",
        "JOIN assignment a ON a.id = t.assignment_id ",
        "JOIN ", @cenozo, ".participant p ON p.id = a.participant_id ",
        "WHERE tt.name = 'classification' ",
        "AND (",
          "t.audio_status IN ('unavailable', 'unusable') ",
          "OR t.participant_status IN ('prompted', 'refused') ",
        ")" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, IFNULL(p.language_id, @default_language_id) AS language_id ",
        "FROM test_entry t ",
        "JOIN test ON test.id = t.test_id ",
        "JOIN test_type tt ON tt.id = test.test_type_id ",
        "JOIN ", @cenozo, ".participant p ON p.id = t.participant_id ",
        "WHERE tt.name = 'classification' ",
        "AND (",
          "t.audio_status IN ('unavailable', 'unusable') ",
          "OR t.participant_status IN ('prompted', 'refused') ",
        ")" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT DISTINCT tec.test_entry_id, w.language_id ",
        "FROM test_entry_classification tec ",
        "JOIN word w ON w.id = tec.word_id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, IFNULL(p.language_id, @default_language_id) AS language_id ",
        "FROM test_entry t ",
        "JOIN test ON test.id = t.test_id ",
        "JOIN test_type tt ON tt.id = test.test_type_id ",
        "JOIN assignment a ON a.id = t.assignment_id ",
        "JOIN ", @cenozo, ".participant p ON p.id = a.participant_id ",
        "WHERE tt.name IN ('alpha_numeric', 'confirmation', 'ranked_word') " );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, IFNULL(p.language_id, @default_language_id) AS language_id ",
        "FROM test_entry t ",
        "JOIN test ON test.id = t.test_id ",
        "JOIN test_type tt ON tt.id = test.test_type_id ",
        "JOIN ", @cenozo, ".participant p ON p.id = t.participant_id ",
        "WHERE tt.name IN ('alpha_numeric', 'confirmation', 'ranked_word') " );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SELECT "Run patch_database.php one time only" AS "";

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test_entry_has_language();
DROP PROCEDURE IF EXISTS patch_test_entry_has_language;
