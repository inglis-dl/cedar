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
      AND COLUMN_NAME = "audio_status"
      AND COLUMN_TYPE = "enum('salvageable','unusable','unavailable')");

    IF @test = 1 THEN

      SELECT "Changing the spelling of an enum in the test_entry table" AS "";

      ALTER TABLE test_entry CHANGE audio_status
      audio_status enum('salvable','unusable','unavailable','salvageable') NULL DEFAULT NULL;

      UPDATE test_entry SET audio_status='salvable' WHERE audio_status='salvageable';

      ALTER TABLE test_entry CHANGE audio_status
      audio_status enum('salvable','unusable','unavailable') NULL DEFAULT NULL;

    END IF;

    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "test_entry"
      AND COLUMN_NAME = "deferred"
      AND DATA_TYPE = "tinyint");

    IF @test = 1 THEN

      ALTER TABLE test_entry ADD COLUMN deferred_temp
      enum('requested','pending','resolved') NULL DEFAULT NULL;

      UPDATE test_entry SET deferred_temp=NULL WHERE deferred=0;

      UPDATE test_entry SET deferred_temp='pending' WHERE deferred=1;

      ALTER TABLE test_entry DROP COLUMN deferred;

      ALTER TABLE test_entry CHANGE deferred_temp
      deferred enum('requested','pending','resolved') NULL DEFAULT NULL;

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test_entry();
DROP PROCEDURE IF EXISTS patch_test_entry;
