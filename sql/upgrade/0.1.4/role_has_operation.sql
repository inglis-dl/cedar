DROP PROCEDURE IF EXISTS patch_role_has_operation;
DELIMITER //
CREATE PROCEDURE patch_role_has_operation()
  BEGIN

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

      SELECT "Adding new operations to roles" AS "";

      SET @sql = CONCAT(
        "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
        "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
        "WHERE type = 'widget' AND subject = 'productivity' AND operation.name = 'report' ",
        "AND role.name IN ( 'administrator' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
        "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
        "WHERE type = 'pull' AND subject = 'productivity' AND operation.name = 'report' ",
        "AND role.name IN ( 'administrator' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

  END //
DELIMITER ;
CALL patch_role_has_operation();
DROP PROCEDURE IF EXISTS patch_role_has_operation;