-- removing note column

DROP PROCEDURE IF EXISTS patch_test_entry;
DELIMITER //
CREATE PROCEDURE patch_test_entry()
  BEGIN
    SELECT "Removing defunct note column from test_entry table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry"
      AND COLUMN_NAME = "note" );
    IF @test = 1 THEN
      ALTER TABLE test_entry DROP COLUMN note;
    END IF;

    SELECT "Changing adjudicate column in test_entry table to allow NULL value" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry"
      AND COLUMN_NAME = "adjudicate"
      AND IS_NULLABLE = "NO" );
    IF @test = 1 THEN
      ALTER TABLE test_entry
      CHANGE adjudicate adjudicate TINYINT(1)
      NULL DEFAULT NULL COMMENT '0 , 1, or NULL (never set)';
      -- now we need to update any existing data
      UPDATE test_entry
      JOIN assignment ON assignment_id=assignment.id
      LEFT JOIN assignment AS sibling_assignment
      ON assignment.participant_id=sibling_assignment.participant_id
      AND assignment.user_id != sibling_assignment.user_id
      LEFT JOIN test_entry AS sibling_test_entry
      ON sibling_test_entry.assignment_id=sibling_assignment.id
      AND sibling_test_entry.test_id=test_entry.test_id
      SET test_entry.adjudicate = NULL
      WHERE test_entry.completed = 0
      OR IFNULL( sibling_test_entry.completed, 0 ) = 0;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test_entry();
DROP PROCEDURE IF EXISTS patch_test_entry;
