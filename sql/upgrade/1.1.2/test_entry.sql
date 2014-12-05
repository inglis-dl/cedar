DROP PROCEDURE IF EXISTS patch_test_entry;
DELIMITER //
CREATE PROCEDURE patch_test_entry()
  BEGIN

    SELECT "Adding additional participant_status enum codes" AS "";

    ALTER TABLE test_entry MODIFY COLUMN participant_status ENUM('suspected prompt','prompted','prompt middle','prompt end','refused');

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry" 
      AND COLUMN_NAME = "audio_fault" );

    IF @test = 1 THEN

      SELECT "Removing defunct audio_fault column" AS "";

      ALTER TABLE test_entry DROP COLUMN audio_fault;

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test_entry();
DROP PROCEDURE IF EXISTS patch_test_entry;
