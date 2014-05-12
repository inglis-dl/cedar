DROP PROCEDURE IF EXISTS patch_confirmation_word_total;
DELIMITER //
CREATE PROCEDURE patch_confirmation_word_total()
  BEGIN

    SELECT "Adding new confirmation_word_total view" AS ""; 

    SET @test = ( 
      SELECT COUNT(*)
      FROM information_schema.VIEWS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "confirmation_word_total" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE OR REPLACE VIEW confirmation_word_total AS ",
        "SELECT w.id AS word_id, COUNT(tec.id) AS total, w.dictionary_id AS dictionary_id FROM word w ",
        "JOIN test t ON t.dictionary_id=w.dictionary_id ",
        "JOIN test_entry te ON te.test_id=t.id ",
        "JOIN test_entry_confirmation tec ON tec.test_entry_id=te.id ",
        "GROUP BY w.id " );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
     
    END IF; 
  END //
DELIMITER ;

CALL patch_confirmation_word_total();
DROP PROCEDURE IF EXISTS patch_confirmation_word_total;       
