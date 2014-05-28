DROP PROCEDURE IF EXISTS patch_sabretooth_recording;
DELIMITER //
CREATE PROCEDURE patch_sabretooth_recording()
  BEGIN

    SET @sabretooth = REPLACE( DATABASE(), 'cedar', 'sabretooth' );

    SELECT "Adding new sabretooth_recording view" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.VIEWS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "sabretooth_recording" );
    IF @test = 0 THEN
      SET @sql = CONCAT(
        "CREATE OR REPLACE VIEW sabretooth_recording AS ",
        "SELECT srecording.interview_id, srecording.assignment_id, srecording.rank, ",
        "sinterview.participant_id ",
        "FROM ", @sabretooth, ".recording AS srecording ",
        "JOIN ", @sabretooth, ".interview AS sinterview ON sinterview.id=srecording.interview_id " );
      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

    END IF;
  END //
DELIMITER ;

CALL patch_sabretooth_recording();
DROP PROCEDURE IF EXISTS patch_sabretooth_recording;
