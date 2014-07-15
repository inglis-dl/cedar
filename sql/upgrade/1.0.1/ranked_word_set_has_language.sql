DROP PROCEDURE IF EXISTS patch_ranked_word_set_has_language;
DELIMITER //
CREATE PROCEDURE patch_ranked_word_set_has_language()
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

    SELECT "Adding new ranked_word_set_has_language table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "ranked_word_set_has_language" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE TABLE IF NOT EXISTS ", @cedar, ".ranked_word_set_has_language ( ",
          "ranked_word_set_id INT UNSIGNED NOT NULL, ",
          "language_id INT UNSIGNED NOT NULL, ",
          "word_id INT UNSIGNED NOT NULL, ",
          "PRIMARY KEY (ranked_word_set_id, language_id), ",
          "INDEX fk_language_id (language_id ASC), ",
          "INDEX fk_ranked_word_set_id (ranked_word_set_id ASC), ",
          "INDEX fk_word_id (word_id ASC), ",
          "CONSTRAINT fk_ranked_word_set_has_language_ranked_word_set_id ",
            "FOREIGN KEY (ranked_word_set_id) ",
            "REFERENCES ", @cedar, ".ranked_word_set (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_ranked_word_set_has_language_language_id ",
            "FOREIGN KEY (language_id) ",
            "REFERENCES ", @cenozo, ".language (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION, ",
          "CONSTRAINT fk_ranked_word_set_has_language_word_id ",
            "FOREIGN KEY (word_id) ",
            "REFERENCES ", @cedar, ".word (id) ",
            "ON DELETE NO ACTION ",
            "ON UPDATE NO ACTION) ",
        "ENGINE = InnoDB" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO ranked_word_set_has_language ",
        "( ranked_word_set_id, language_id, word_id ) ",
        "SELECT rws.id, l.id, w.id FROM ranked_word_set rws ",
        "JOIN word w ON w.id=rws.word_en_id ",
        "JOIN ", @cenozo, ".language l ON l.id=w.language_id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT INTO ranked_word_set_has_language ",
        "( ranked_word_set_id, language_id, word_id ) ",
        "SELECT rws.id, l.id, w.id FROM ranked_word_set rws ",
        "JOIN word w ON w.id=rws.word_fr_id ",
        "JOIN ", @cenozo, ".language l ON l.id=w.language_id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_ranked_word_set_has_language();
DROP PROCEDURE IF EXISTS patch_ranked_word_set_has_language;
