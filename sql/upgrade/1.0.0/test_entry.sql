-- adding audio_status and participant_status columns

DROP PROCEDURE IF EXISTS patch_test_entry;
DELIMITER //
CREATE PROCEDURE patch_test_entry()
  BEGIN
    SELECT "Adding audio and participant status columns in test_entry table" AS ""; 

    SET @test = ( 
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry"
      AND COLUMN_NAME = "participant_status" );

    IF @test = 0 THEN
      
      ALTER TABLE test_entry 
      ADD COLUMN audio_status ENUM('salvageable','unusable','unavailable') NULL DEFAULT NULL;
      ALTER TABLE test_entry 
      ADD COLUMN participant_status ENUM('suspected prompt','prompted','refused') NULL DEFAULT NULL;

      UPDATE test_entry
      SET audio_status = 'unavailable'
      WHERE audio_fault = true;

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test_entry();
DROP PROCEDURE IF EXISTS patch_test_entry;
