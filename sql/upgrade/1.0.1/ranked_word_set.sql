-- adding cenozo language table
-- drop defunct word id columns
SELECT "Dropping defunct ranked_word_set word_*_id columns" AS "";

DROP PROCEDURE IF EXISTS patch_ranked_word_set;
DELIMITER //
CREATE PROCEDURE patch_ranked_word_set()
  BEGIN
    DECLARE test INT;
    SET @test = 
      ( SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
        AND TABLE_NAME = "ranked_word_set"
        AND COLUMN_NAME = "word_en_id" );

    IF @test = 1 THEN
       ALTER TABLE ranked_word_set
       DROP FOREIGN KEY fk_ranked_word_set_word_en_id;
       ALTER TABLE ranked_word_set
       DROP FOREIGN KEY fk_ranked_word_set_word_fr_id;
       ALTER TABLE ranked_word_set
       DROP COLUMN word_en_id;
       ALTER TABLE ranked_word_set
       DROP COLUMN word_fr_id;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_ranked_word_set();
DROP PROCEDURE IF EXISTS patch_ranked_word_set;
