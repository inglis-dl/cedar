-- add the new unique key to the assignment table
-- we need to create a procedure which only alters the table if the
-- unique key is missing

DROP PROCEDURE IF EXISTS patch_assignment;
DELIMITER //
CREATE PROCEDURE patch_assignment()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "assignment"
      AND CONSTRAINT_NAME = "uq_user_id_participant_id" );
    IF @test = 0 THEN
      ALTER TABLE assignment
      ADD UNIQUE INDEX uq_user_id_participant_id
      (user_id ASC, participant_id ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_assignment();
DROP PROCEDURE IF EXISTS patch_assignment;
