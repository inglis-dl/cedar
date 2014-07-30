-- add mispelled_dictionary_id column

DROP PROCEDURE IF EXISTS patch_test;
DELIMITER //
CREATE PROCEDURE patch_test()
  BEGIN
    SELECT "Adding mispelled_dictionary_id column in test table" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test"
      AND COLUMN_NAME = "mispelled_dictionary_id" );

    IF @test = 0 THEN

      ALTER TABLE test
      ADD COLUMN mispelled_dictionary_id INT UNSIGNED NULL DEFAULT NULL;

      ALTER TABLE test
      ADD INDEX fk_mispelled_dictionary_id ( mispelled_dictionary_id ASC ),
      ADD CONSTRAINT fk_test_mispelled_dictionary_id
      FOREIGN KEY ( mispelled_dictionary_id )
      REFERENCES dictionary ( id )
      ON DELETE NO ACTION ON UPDATE NO ACTION;

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_test();
DROP PROCEDURE IF EXISTS patch_test;
