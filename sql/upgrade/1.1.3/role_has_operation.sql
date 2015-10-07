DROP PROCEDURE IF EXISTS patch_role_has_operation;
DELIMITER //
CREATE PROCEDURE patch_role_has_operation()
  BEGIN

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

      SELECT "Removing typist note deletion permission" AS "";

      SET @sql = CONCAT(
	"DELETE FROM role_has_operation ",
	"WHERE role_id=(",
	"  SELECT id ",
	"  FROM ", @cenozo, ".role "
	"  WHERE name='typist'",
	") ",
	"AND operation_id=(",
	"  SELECT id ",
	"  FROM operation ",
	"  WHERE type='push' ",
	"  AND subject='note' ",
	"  AND name='delete'",
	")" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

  END //
DELIMITER ;
CALL patch_role_has_operation();
DROP PROCEDURE IF EXISTS patch_role_has_operation;
