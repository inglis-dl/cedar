-- adding language_id column

DROP PROCEDURE IF EXISTS patch_word;
DELIMITER //
CREATE PROCEDURE patch_word()
  BEGIN
    SELECT "Adding language_id column in word table" AS "";

    -- determine the @cenozo database name
    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "word"
      AND COLUMN_NAME = "language" );

    IF @test = 0 THEN

      ALTER TABLE word
      ADD COLUMN language_id INT UNSIGNED NOT NULL AFTER dictionary_id;

      ALTER TABLE word
      ADD INDEX fk_language_id ( language_id ASC );

      SET @sql = CONCAT(
        "UPDATE word w ",
        "JOIN ", @cenozo, ".language l ",
        "ON w.language = l.code ",
        "SET w.language_id = l.id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE word ",
        "ADD CONSTRAINT fk_word_language_id ",
        "FOREIGN KEY ( language_id ) ",
        "REFERENCES ", @cenozo, ".language ( id ) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      ALTER TABLE word DROP INDEX uq_dictionary_id_language_word;
      ALTER TABLE word
      ADD UNIQUE INDEX uq_word_dictionary_id_language_id
        (word ASC, dictionary_id ASC, language_id ASC);

      ALTER TABLE word DROP COLUMN language;

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_word();
DROP PROCEDURE IF EXISTS patch_word;
