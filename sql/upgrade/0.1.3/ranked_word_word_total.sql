DROP PROCEDURE IF EXISTS patch_ranked_word_word_total;
DELIMITER //
CREATE PROCEDURE patch_ranked_word_word_total()
  BEGIN

    SELECT "Adding new ranked_word_word_total view" AS ""; 

    SET @test = ( 
      SELECT COUNT(*)
      FROM information_schema.VIEWS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "ranked_word_word_total" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE OR REPLACE VIEW ranked_word_word_total AS ",
        "SELECT w.id AS word_id, COUNT(terw.id) + (COUNT(rws1.id) + COUNT( rws2.id)) DIV 2 AS total, w.dictionary_id AS dictionary_id ",
        "FROM word w ",
        "LEFT JOIN test_entry_ranked_word terw ON terw.word_id=w.id ",
        "LEFT JOIN test AS t1 ON t1.dictionary_id=w.dictionary_id ",
        "LEFT JOIN test AS t2 ON t2.intrusion_dictionary_id=w.dictionary_id ",
        "LEFT JOIN test AS t3 ON t3.variant_dictionary_id=w.dictionary_id ",
        "LEFT JOIN test AS t4 ON t4.mispelled_dictionary_id=w.dictionary_id ",
        "LEFT JOIN ranked_word_set AS rws1 ON rws1.word_en_id=w.id ",
        "LEFT JOIN ranked_word_set AS rws2 ON rws2.word_fr_id=w.id ",
        "LEFT JOIN test_entry_ranked_word AS terw1 ON terw1.ranked_word_set_id=rws1.id ",
        "LEFT JOIN test_entry_ranked_word AS terw2 ON terw2.ranked_word_set_id=rws2.id ",
        "GROUP BY w.id " );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;
     
    END IF; 
  END //
DELIMITER ;

CALL patch_ranked_word_word_total();
DROP PROCEDURE IF EXISTS patch_ranked_word_word_total;       
