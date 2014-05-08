-- adding comments and ranked_word_set_id column

DROP PROCEDURE IF EXISTS patch_test_entry_ranked_word;
DELIMITER //
CREATE PROCEDURE patch_test_entry_ranked_word()
  BEGIN
    SELECT "Adding ranked_word_set_id column in test_entry_ranked_word table" AS ""; 

    SET @test = ( 
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry_ranked_word"
      AND COLUMN_NAME = "ranked_word_set_id" );

    IF @test = 0 THEN
      
      SELECT "Adding comments to columns" AS "";

      ALTER TABLE test_entry_ranked_word 
      CHANGE word_id word_id INT UNSIGNED NULL DEFAULT NULL
      COMMENT 'if NOT NULL then a variant or intrusion';

      ALTER TABLE test_entry_ranked_word 
      CHANGE selection selection ENUM('yes','no','variant') NULL DEFAULT NULL
      COMMENT 'if NULL an intrusion or not filled in';      
     
      ALTER TABLE test_entry_ranked_word 
      ADD COLUMN ranked_word_set_id INT UNSIGNED NULL DEFAULT NULL 
      COMMENT 'if NULL this is an intrusion';

      ALTER TABLE test_entry_ranked_word 
      ADD INDEX fk_ranked_word_set_id ( ranked_word_set_id ASC ),
      ADD CONSTRAINT fk_test_entry_ranked_word_ranked_word_set_id
      FOREIGN KEY ( ranked_word_set_id )
      REFERENCES ranked_word_set ( id )
      ON DELETE NO ACTION ON UPDATE NO ACTION;
     
      -- now determine the ranked_word_set_id using the word_id column

      UPDATE test_entry_ranked_word 
      JOIN test_entry ON test_entry.id=test_entry_ranked_word.test_entry_id
      JOIN test ON test.id=test_entry.test_id
      JOIN ranked_word_set ON ranked_word_set.test_id=test.id
      AND ( 
        ranked_word_set.word_en_id = test_entry_ranked_word.word_id OR
        ranked_word_set.word_fr_id = test_entry_ranked_word.word_id 
      )
      SET test_entry_ranked_word.ranked_word_set_id = ranked_word_set.id
      WHERE word_id IS NOT NULL;

      UPDATE test_entry_ranked_word SET word_id = NULL;

    END IF;

    SELECT "Removing word_candidate column from test_entry_ranked_word table" AS ""; 

    SET @test = ( 
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry_ranked_word"
      AND COLUMN_NAME = "word_candidate" );
      
    IF @test = 1 THEN

      -- before deleting this column we have to assign word_id's for the candidates
      -- case 1) variants
      UPDATE test_entry_ranked_word 
      JOIN test_entry ON test_entry.id=test_entry_ranked_word.test_entry_id
      JOIN word ON test_entry_ranked_word.word_candidate = word.word
      JOIN dictionary ON word.dictionary_id=dictionary.id
      JOIN test ON dictionary.id=test.variant_dictionary_id
      AND test.id = test_entry.test_id
      SET test_entry_ranked_word.word_id = word.id 
      WHERE selection = "variant";
      
      -- case 2) intrusions
      UPDATE test_entry_ranked_word 
      JOIN test_entry ON test_entry.id=test_entry_ranked_word.test_entry_id
      JOIN word ON test_entry_ranked_word.word_candidate = word.word
      JOIN dictionary ON word.dictionary_id=dictionary.id
      JOIN test ON dictionary.id=test.intrusion_dictionary_id
      AND test.id = test_entry.test_id
      SET test_entry_ranked_word.word_id = word.id 
      WHERE selection IS NULL;

      ALTER TABLE test_entry_ranked_word DROP COLUMN word_candidate;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test_entry_ranked_word();
DROP PROCEDURE IF EXISTS patch_test_entry_ranked_word;
