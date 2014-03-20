-- removing note column

DROP PROCEDURE IF EXISTS patch_qnaire;
DELIMITER //
CREATE PROCEDURE patch_qnaire()
  BEGIN
    SELECT "Removing defunct note column from test_entry table" AS ""; 

    SET @test = ( 
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry"
      AND COLUMN_NAME = "note" );
    IF @test = 1 THEN
      ALTER TABLE qnaire DROP COLUMN note;
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_qnaire();
DROP PROCEDURE IF EXISTS patch_qnaire;
