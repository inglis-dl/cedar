DROP PROCEDURE IF EXISTS patch_test_entry_total_completed;
DELIMITER //
CREATE PROCEDURE patch_test_entry_total_completed()
  BEGIN

    SELECT "Adding new test_entry_total_completed view" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.VIEWS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry_total_completed" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE OR REPLACE VIEW test_entry_total_completed AS ",
        "SELECT assignment_id, SUM( completed ) AS completed FROM test_entry ",
        "WHERE assignment_id IS NOT NULL ",
        "GROUP BY assignment_id " );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END IF;
  END //
DELIMITER ;

CALL patch_test_entry_total_completed();
DROP PROCEDURE IF EXISTS patch_test_entry_total_completed;
