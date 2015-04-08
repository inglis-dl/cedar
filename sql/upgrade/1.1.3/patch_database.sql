-- Patch to upgrade database to version 1.1.3

DROP PROCEDURE IF EXISTS patch;
DELIMITER //
CREATE PROCEDURE patch()
  BEGIN
    DECLARE pre_test INT;
    SET @pre_test=
      ( SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "test_entry"
      AND COLUMN_NAME = "completed"
      AND COLUMN_TYPE = "TINYINT(1)");

    IF @pre_test=1 THEN
      SELECT "Run php script patch_database.php first" AS "";
    ELSE
      SET AUTOCOMMIT=0;

      SOURCE test_entry_total_completed.sql;
      SOURCE assignment_total.sql;
      SOURCE operation.sql;
      SOURCE role_has_operation.sql;

      -- after all SOURCES, update service version
      SOURCE update_version_number.sql;

      COMMIT;
    END IF;
  END //
DELIMITER ;

CALL patch();
DROP PROCEDURE IF EXISTS patch;
