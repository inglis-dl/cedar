-- add recording_name to the test table

DROP PROCEDURE IF EXISTS patch_test;
DELIMITER //
CREATE PROCEDURE patch_test()
  BEGIN
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test"
      AND COLUMN_NAME = "recording_name" );

    IF @test = 0 THEN
      SELECT "Adding a recording_name column to the test table" AS "";
      ALTER TABLE test ADD COLUMN recording_name VARCHAR(255) NULL DEFAULT NULL;
      ALTER TABLE test ADD UNIQUE INDEX uq_recording_name (recording_name ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test();
DROP PROCEDURE IF EXISTS patch_test;
