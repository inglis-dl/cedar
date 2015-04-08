-- rename a mispelled audio_status enum in the test_entry table

DROP PROCEDURE IF EXISTS patch_test_entry;
DELIMITER //
CREATE PROCEDURE patch_test_entry()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "test_entry"
      AND COLUMN_NAME = "completed"
      AND COLUMN_TYPE = "TINYINT(1)");

    IF @test = 1 THEN

      SELECT "Changing completed to an enum in the test_entry table" AS "";

      ALTER TABLE test_entry ADD COLUMN completed_temp
      enum('incomplete','complete','submitted') NOT NULL DEFAULT 'incomplete';

      UPDATE test_entry SET completed_temp='incomplete' WHERE completed=0;

      UPDATE test_entry SET completed_temp='complete' WHERE completed=1;

      UPDATE test_entry SET completed_temp='submitted'
      WHERE completed=1
      AND participant_id IS NOT NULL;

      UPDATE test_entry
      JOIN assignment ON assignment.id=test_entry.assignment_id
      SET completed_temp='submitted'
      WHERE completed=1
      AND assignment.end_datetime IS NOT NULL;

      ALTER TABLE test_entry DROP COLUMN completed;

      ALTER TABLE test_entry CHANGE completed_temp
      completed enum('incomplete','complete','submitted') NOT NULL DEFAULT 'incomplete';

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test_entry();
DROP PROCEDURE IF EXISTS patch_test_entry;
