DROP PROCEDURE IF EXISTS patch_ranked_word_set_has_language;
DELIMITER //
CREATE PROCEDURE patch_ranked_word_set_has_language()
  BEGIN
    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "ranked_word_set_has_language"
      AND COLUMN_NAME = "update_timestamp" );

    IF @test = 0 THEN
      SELECT "Adding missing columns to ranked_word_set_has_language table" AS "";

      SET @sql = CONCAT(
        "ALTER TABLE ranked_word_set_has_language ",
        "ADD COLUMN update_timestamp TIMESTAMP NOT NULL ",
        "AFTER language_id" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "ALTER TABLE ranked_word_set_has_language ",
        "ADD COLUMN create_timestamp TIMESTAMP NOT NULL ",
        "AFTER update_timestamp" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE ranked_word_set_has_language ",
        "SET update_timestamp = ( ",
        "SELECT update_timestamp FROM ",
        "ranked_word_set ",
        "WHERE id = ( ",
        "SELECT MAX(id) FROM ranked_word_set ) )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE ranked_word_set_has_language ",
        "SET create_timestamp = ( ",
        "SELECT create_timestamp FROM ",
        "ranked_word_set ",
        "WHERE id = ( ",
        "SELECT MAX(id) FROM ranked_word_set ) )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_ranked_word_set_has_language();
DROP PROCEDURE IF EXISTS patch_ranked_word_set_has_language;
