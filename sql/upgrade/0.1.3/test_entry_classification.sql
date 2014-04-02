-- removing note column

DROP PROCEDURE IF EXISTS patch_test_entry_classification;
DELIMITER //
CREATE PROCEDURE patch_test_entry_classification()
  BEGIN
    SELECT "Removing word_candidate column from test_entry_classification table" AS ""; 

    SET @test = ( 
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry_classification"
      AND COLUMN_NAME = "word_candidate" );
      
    IF @test = 1 THEN
      ALTER TABLE test_entry_classification 
      CHANGE word_id word_id INT UNSIGNED NULL DEFAULT NULL
      COMMENT 'NULL if not set yet';

      ALTER TABLE test_entry_classification DROP COLUMN word_candidate;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test_entry_classification();
DROP PROCEDURE IF EXISTS patch_test_entry_classification;
