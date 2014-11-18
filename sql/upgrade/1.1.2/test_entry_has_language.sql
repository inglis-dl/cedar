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

    SELECT "Adding new test_entry_has_language table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry_has_language" );
    IF @test = 0 THEN
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
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, tmp.language_id ",
        "FROM ( ",
        "  SELECT DISTINCT a.participant_id, t.test_id, w.language_id ",
        "  FROM test_entry_classification tec ",
        "  JOIN word w ON w.id=tec.word_id ",
        "  JOIN test_entry t ON t.id=tec.test_entry_id ",
        "  JOIN assignment a ON a.id=t.assignment_id ",
        ") AS tmp ",
        "JOIN assignment a ON a.participant_id=tmp.participant_id ",
        "JOIN test_entry t ON t.assignment_id=a.id ",
        "AND t.test_id=tmp.test_id");

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, tmp.language_id ",
        "FROM ( ",
        "  SELECT DISTINCT t.participant_id, t.test_id, w.language_id ",
        "  FROM test_entry_classification tec ",
        "  JOIN word w ON w.id=tec.word_id ",
        "  JOIN test_entry t ON t.id=tec.test_entry_id ",
        "  WHERE t.participant_id IS NOT NULL ",
        ") AS tmp ",
        "JOIN test_entry t ON t.participant_id=tmp.participant_id ",
        "AND t.test_id=tmp.test_id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, tmp.language_id ",
        "FROM ( ",
        "  SELECT DISTINCT a.participant_id, t.test_id, w.language_id ",
        "  FROM test_entry_alpha_numeric tan ",
        "  JOIN word w ON w.id=tan.word_id ",
        "  JOIN test_entry t ON t.id=tan.test_entry_id ",
        "  JOIN assignment a ON a.id=t.assignment_id ",
        ") AS tmp ",
        "JOIN assignment a ON a.participant_id=tmp.participant_id ",
        "JOIN test_entry t ON t.assignment_id=a.id ",
        "AND t.test_id=tmp.test_id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, tmp.language_id ",
        "FROM ( ",
        "  SELECT DISTINCT t.participant_id, t.test_id, w.language_id ",
        "  FROM test_entry_alpha_numeric tan ",
        "  JOIN word w ON w.id=tan.word_id ",
        "  JOIN test_entry t ON t.id=tan.test_entry_id ",
        "  WHERE t.participant_id IS NOT NULL ",
        ") AS tmp ",
        "JOIN test_entry t ON t.participant_id=tmp.participant_id ",
        "AND t.test_id=tmp.test_id ");

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, tmp.language_id ",
        "FROM ( ",
        "  SELECT DISTINCT a.participant_id, t.test_id, IFNULL(p.language_id,s.language_id) AS language_id ",
        "  FROM test_entry_confirmation tec ",
        "  JOIN test_entry t ON t.id=tec.test_entry_id ",
        "  JOIN assignment a ON a.id=t.assignment_id ",
        "  JOIN dean_cenozo.participant p ON p.id=a.participant_id ",
        "  JOIN dean_cenozo.participant_site ps ON ps.participant_id=p.id ",
        "  AND ps.site_id=a.site_id ",
        "  JOIN dean_cenozo.service s ON s.id=ps.service_id ",
        "  WHERE s.name='cedar' ",
        ") AS tmp ",
        "JOIN assignment a ON a.participant_id=tmp.participant_id ",
        "JOIN test_entry t ON t.assignment_id=a.id ",
        "AND t.test_id=tmp.test_id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, tmp.language_id ",
        "FROM ( ",
        "  SELECT DISTINCT t.participant_id, t.test_id, IFNULL(p.language_id,s.language_id) AS language_id ",
        "  FROM test_entry_confirmation tec ",
        "  JOIN test_entry t ON t.id=tec.test_entry_id ",
        "  JOIN dean_cenozo.participant p ON p.id=t.participant_id ",
        "  JOIN dean_cenozo.participant_site ps ON ps.participant_id=p.id ",
        "  JOIN dean_cenozo.service s ON s.id=ps.service_id ",
        "  WHERE s.name='cedar' ",
        ") AS tmp ",
        "JOIN test_entry t ON t.participant_id=tmp.participant_id ",
        "AND t.test_id=tmp.test_id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, tmp.language_id ",
        "FROM ( ",
        "  SELECT DISTINCT a.participant_id, t.test_id, w.language_id ",
        "  FROM test_entry_ranked_word terw ",
        "  JOIN test_entry t ON t.id=terw.test_entry_id ",
        "  JOIN assignment a ON a.id=t.assignment_id ",
        "  JOIN ranked_word_set rws ON rws.id=terw.ranked_word_set_id ",
        "  AND rws.test_id=t.test_id ",
        "  JOIN ranked_word_set_has_language rwshl ON rwshl.ranked_word_set_id=rws.id ",
        "  JOIN word w ON w.id=rwshl.word_id ",
        ") AS tmp ",
        "JOIN assignment a ON a.participant_id=tmp.participant_id ",
        "JOIN test_entry t ON t.assignment_id=a.id ",
        "AND t.test_id=tmp.test_id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO test_entry_has_language ",
        "(test_entry_id, language_id) ",
        "SELECT t.id, tmp.language_id ",
        "FROM ( ",
        "  SELECT DISTINCT t.participant_id, t.test_id, w.language_id ",
        "  FROM test_entry_ranked_word terw ",
        "  JOIN test_entry t ON t.id=terw.test_entry_id ",
        "  JOIN dean_cenozo.participant p ON p.id=t.participant_id ",
        "  JOIN ranked_word_set rws ON rws.id=terw.ranked_word_set_id ",
        "  AND rws.test_id=t.test_id ",
        "  JOIN ranked_word_set_has_language rwshl ON rwshl.ranked_word_set_id=rws.id ",
        "  JOIN word w ON w.id=rwshl.word_id ",
        ") AS tmp ",
        "JOIN test_entry t ON t.participant_id=tmp.participant_id ",
        "AND t.test_id=tmp.test_id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test_entry_has_language();
DROP PROCEDURE IF EXISTS patch_test_entry_has_language;
