DROP PROCEDURE IF EXISTS patch_role_has_operation;
DELIMITER //
CREATE PROCEDURE patch_role_has_operation()
  BEGIN

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

      SELECT "Removing supervisor access to assignment edit" AS "";

      SET @sql = CONCAT(
        "DELETE FROM role_has_operation ",
        "WHERE role_id=( ",
          "SELECT id FROM ", @cenozo, ".role ",
          "WHERE name = 'supervisor' )",
        "AND operation_id IN ( ",
          "SELECT id FROM operation ",
          "WHERE subject='assignment' ",
          "AND name='reassign' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

  END //
DELIMITER ;
CALL patch_role_has_operation();
DROP PROCEDURE IF EXISTS patch_role_has_operation;
