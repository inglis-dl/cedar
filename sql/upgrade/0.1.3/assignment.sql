-- add new start and end datetime columns to the assignment table

DROP PROCEDURE IF EXISTS patch_assignment;
DELIMITER //
CREATE PROCEDURE patch_assignment()
  BEGIN
    SELECT "Adding new datetime columns to assignment table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "assignment"
      AND COLUMN_NAME = "end_datetime" );
    IF @test = 0 THEN
      ALTER TABLE assignment
      ADD COLUMN start_datetime DATETIME NOT NULL
      AFTER participant_id;
    END IF;

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "assignment"
      AND COLUMN_NAME = "end_datetime" );
    IF @test = 0 THEN
      ALTER TABLE assignment
      ADD COLUMN end_datetime DATETIME DEFAULT NULL
      AFTER start_datetime;

      UPDATE assignment
      SET start_datetime = create_timestamp + interval 4 hour;

      SET @sql = CONCAT(
        "UPDATE assignment ",
        "INNER JOIN ( ",
          "SELECT assignment_id, max( test_entry.update_timestamp ) AS ut FROM test_entry ",
          "JOIN assignment ON assignment.id = test_entry.assignment_id ",
          "JOIN ", @cenozo, ".participant ON participant.id = assignment.participant_id ",
          "JOIN ", @cenozo, ".cohort  ON cohort.id = participant.cohort_id ",
          "WHERE test_entry.completed = 1 ",
          "AND test_entry.deferred = 0 ",
          "AND test_entry.adjudicate = 0 ",
          "AND cohort.name='tracking' ",
          "GROUP BY assignment_id ",
          "HAVING count(*) = 6 ) AS tmp ",
          "ON assignment.id = tmp.assignment_id ",
          "SET assignment.end_datetime = ut + interval 4 hour" );
       PREPARE statement FROM @sql;
       EXECUTE statement;
       DEALLOCATE PREPARE statement;

      SET @sql = CONCAT(
        "UPDATE assignment ",
        "INNER JOIN ( ",
          "SELECT assignment_id, max( test_entry.update_timestamp ) AS ut FROM test_entry ",
          "JOIN assignment ON assignment.id = test_entry.assignment_id ",
          "JOIN ", @cenozo, ".participant ON participant.id = assignment.participant_id ",
          "JOIN ", @cenozo, ".cohort  ON cohort.id = participant.cohort_id ",
          "WHERE test_entry.completed = 1 ",
          "AND test_entry.deferred = 0 ",
          "AND test_entry.adjudicate = 0 ",
          "AND cohort.name='comprehensive' ",
          "GROUP BY assignment_id ",
          "HAVING count(*) = 9 ) AS tmp ",
          "ON assignment.id = tmp.assignment_id ",
          "SET assignment.end_datetime = ut + interval 4 hour" );
       PREPARE statement FROM @sql;
       EXECUTE statement;
       DEALLOCATE PREPARE statement;
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_assignment();
DROP PROCEDURE IF EXISTS patch_assignment;
