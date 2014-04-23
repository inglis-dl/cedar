-- add the new unique key to the test_entry_alpha_numeric table
-- we need to create a procedure which only alters the table if the
-- unique key is missing

DROP PROCEDURE IF EXISTS patch_test_entry_alpha_numeric;
DELIMITER //
CREATE PROCEDURE patch_test_entry_alpha_numeric()
  BEGIN
    DECLARE test INT;
    SET @test =
      ( SELECT COUNT(*)
      FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = ( SELECT DATABASE() )
      AND TABLE_NAME = "test_entry_alpha_numeric"
      AND CONSTRAINT_NAME = "uq_test_entry_id_rank" );
    IF @test = 0 THEN
      ALTER TABLE test_entry_alpha_numeric
      ADD UNIQUE INDEX uq_test_entry_id_rank
      (test_entry_id ASC, rank ASC);
    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test_entry_alpha_numeric();
DROP PROCEDURE IF EXISTS patch_test_entry_alpha_numeric;
