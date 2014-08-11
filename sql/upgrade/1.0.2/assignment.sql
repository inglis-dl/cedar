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

    IF @test = 1 THEN
      ALTER TABLE assignment
      ADD COLUMN site_id INT UNSIGNED NOT NULL AFTER participant_id;

      ALTER TABLE assignment
      ADD INDEX fk_site_id (site_id ASC);

      SET @sql = CONCAT(
        "CREATE TEMPORARY TABLE tmp AS ",
        "SELECT u.id AS user_id, site_id FROM ", @cenozo, ".user u ",
        "JOIN ", @cenozo, ".access a ON a.user_id=u.id ",
        "WHERE role_id=(",
        "SELECT id FROM ", @cenozo, ".role ",
        "WHERE name='typist') ",
        "AND site_id IN (",
        "SELECT site.id FROM ", @cenozo, ".site ",
        "JOIN ", @cenozo, ".service s ON s.id=site.service_id ",
        "WHERE s.name='cedar') ",
        "GROUP BY u.id" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      UPDATE assignment a JOIN tmp ON tmp.user_id=a.user_id SET a.site_id=tmp.site_id;

      DROP TABLE tmp;

      SET @sql = CONCAT(
        "ALTER TABLE assignment ",
        "ADD CONSTRAINT fk_assignment_site_id ",
        "FOREIGN KEY ( site_id ) ",
        "REFERENCES ", @cenozo, ".site ( id ) ",
        "ON DELETE NO ACTION ON UPDATE NO ACTION" );

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      ALTER TABLE assignment DROP INDEX uq_user_id_participant_id;

      ALTER TABLE assignment
      ADD UNIQUE INDEX uq_user_id_participant_id_site_id
      (user_id ASC, participant_id ASC, site_id ASC);

    END IF;
  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL patch_assignment();
DROP PROCEDURE IF EXISTS patch_assignment;
