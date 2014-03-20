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
    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_assignment();
DROP PROCEDURE IF EXISTS patch_assignment;
