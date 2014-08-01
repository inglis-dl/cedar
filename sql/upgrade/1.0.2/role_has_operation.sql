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
        "WHERE subject = 'assignment' AND operation.name != 'new' ",
        "AND role.name IN ( 'administrator', 'supervisor' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
        "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
        "WHERE subject IN ( ",
        "'away_time', 'dictionary', 'dictionary_import', 'productivity', ",
        "'system_message', 'word' ) ",
        "AND role.name IN ( 'administrator', 'supervisor' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
        "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
        "WHERE subject IN ( 'ranked_word_set', 'test' ) ",
        "AND operation.name IN ( 'list', 'view' ) ",
        "AND role.name IN ( 'administrator', 'supervisor' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
        "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
        "WHERE subject = 'note' ",
        "AND operation.name IN ( 'delete', 'edit' ) ",
        "AND role.name IN ( 'administrator', 'supervisor', 'typist' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
        "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
        "WHERE subject = 'user' ",
        "AND operation.name = 'list' ",
        "AND role.name IN ( 'administrator', 'supervisor' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
        "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
        "WHERE subject IN ( 'test_entry', 'test_entry_alpha_numeric', ",
        "'test_entry_classification', 'test_entry_confirmation', 'test_entry_ranked_word' ) ",
        "AND operation.name IN ( 'edit', 'new', 'adjudicate', 'submit', 'list', 'view' ) ",
        "AND role.name IN ( 'administrator', 'supervisor' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "INSERT IGNORE INTO role_has_operation( role_id, operation_id ) ",
        "SELECT role.id, operation.id FROM ", @cenozo, ".role, operation ",
        "WHERE type IN ( 'push', 'widget' ) ",
        "AND subject = 'assignment' ",
        "AND operation.name = 'reassign' ",
        "AND role.name IN ( 'administrator' )" );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

  END //
DELIMITER ;
CALL patch_role_has_operation();
DROP PROCEDURE IF EXISTS patch_role_has_operation;
