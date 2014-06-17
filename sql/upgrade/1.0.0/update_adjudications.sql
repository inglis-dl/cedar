DROP PROCEDURE IF EXISTS update_adjudications;
DELIMITER //
CREATE PROCEDURE update_adjudications()
  BEGIN

    SET @cenozo = (
      SELECT unique_constraint_schema
      FROM information_schema.referential_constraints
      WHERE constraint_schema = DATABASE()
      AND constraint_name = "fk_role_has_operation_role_id" );

    SELECT "Finding missing and resetting adjudications" AS "";

    SET @test = (
      SELECT COUNT(*)
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = "test_entry"
      AND COLUMN_NAME = "participant_status" );

    IF @test = 1 THEN

      -- get all the assignments with test_entrys that are completed, !deferred and not
      -- already identified for adjudication
      SET @sql = CONCAT(
        "CREATE TEMPORARY TABLE IF NOT EXISTS tmp1 ",
        "SELECT a.id AS assignment_id, a.participant_id FROM assignment a ",
        "JOIN ( SELECT a.participant_id AS participant_id FROM assignment a ",
        "JOIN ", @cenozo, ".participant p ON p.id=a.participant_id ",
        "JOIN test_entry t ON t.assignment_id=a.id ",
        "WHERE t.completed=true ",
        "AND t.deferred=false ",
        "AND IFNULL(t.adjudicate,0)=0 ",
        "GROUP BY p.id ",
        "HAVING COUNT(p.id)=12) ",
        "AS tmp ON tmp.participant_id=a.participant_id");

      PREPARE statement FROM @sql;
      EXECUTE statement;
      DEALLOCATE PREPARE statement;

      CREATE TEMPORARY TABLE IF NOT EXISTS tmp2
      SELECT * FROM tmp1 GROUP BY participant_id;

      CREATE TEMPORARY TABLE IF NOT EXISTS tmp3
      SELECT * FROM tmp1 WHERE assignment_id NOT IN
      ( SELECT assignment_id FROM tmp2 );

      CREATE TEMPORARY TABLE IF NOT EXISTS te_an1
      SELECT test_entry_id, participant_id FROM (
        SELECT rank, word_id, participant_id, test_entry_id FROM (
          SELECT t1.rank, t1.word_id, tmp2.participant_id, t1.test_entry_id FROM test_entry_alpha_numeric t1
          JOIN test_entry te1 ON te1.id=t1.test_entry_id
          JOIN tmp2 ON tmp2.assignment_id=te1.assignment_id
          UNION ALL
          SELECT t2.rank, t2.word_id, tmp3.participant_id, t2.test_entry_id FROM test_entry_alpha_numeric t2
          JOIN test_entry te2 ON te2.id=t2.test_entry_id
          JOIN tmp3 ON tmp3.assignment_id=te2.assignment_id
        ) t GROUP BY rank, word_id, participant_id HAVING COUNT(*)=1
      ) t2 GROUP BY test_entry_id;

      UPDATE test_entry
      SET adjudicate=true
      WHERE id IN (SELECT test_entry_id FROM te_an1);

      UPDATE assignment a
      JOIN test_entry te ON te.assignment_id=a.id
      SET a.end_datetime=NULL
      WHERE te.id IN (SELECT test_entry_id FROM te_an1);

      CREATE TEMPORARY TABLE IF NOT EXISTS te_c1
      SELECT test_entry_id, participant_id, test_id FROM (
        SELECT rank, word_id, participant_id, test_id, test_entry_id FROM (
          SELECT t1.rank, t1.word_id, tmp2.participant_id, te1.test_id, t1.test_entry_id FROM test_entry_classification t1
          JOIN test_entry te1 ON te1.id=t1.test_entry_id
          JOIN tmp2 ON tmp2.assignment_id=te1.assignment_id
          UNION ALL
          SELECT t2.rank, t2.word_id, tmp3.participant_id, te2.test_id, t2.test_entry_id FROM test_entry_classification t2
          JOIN test_entry te2 ON te2.id=t2.test_entry_id
          JOIN tmp3 ON tmp3.assignment_id=te2.assignment_id
        ) t GROUP BY rank, word_id, participant_id, test_id HAVING COUNT(*)=1
      ) t2 GROUP BY test_entry_id;

      UPDATE test_entry
      SET adjudicate=true
      WHERE id IN (SELECT test_entry_id FROM te_c1);

      UPDATE assignment a
      JOIN test_entry te ON te.assignment_id=a.id
      SET a.end_datetime=NULL
      WHERE te.id IN (SELECT test_entry_id FROM te_c1);

      CREATE TEMPORARY TABLE IF NOT EXISTS te_c2
      SELECT test_entry_id, participant_id, test_id FROM (
        SELECT confirmation, participant_id, test_id, test_entry_id FROM (
          SELECT t1.confirmation, tmp2.participant_id, te1.test_id, t1.test_entry_id FROM test_entry_confirmation t1
          JOIN test_entry te1 ON te1.id=t1.test_entry_id
          JOIN tmp2 ON tmp2.assignment_id=te1.assignment_id
          UNION ALL
          SELECT t2.confirmation, tmp3.participant_id, te2.test_id, t2.test_entry_id FROM test_entry_confirmation t2
          JOIN test_entry te2 ON te2.id=t2.test_entry_id
          JOIN tmp3 ON tmp3.assignment_id=te2.assignment_id
        ) t GROUP BY confirmation, participant_id, test_id HAVING COUNT(*)=1
      ) t2 GROUP BY test_entry_id;

      UPDATE test_entry
      SET adjudicate=true
      WHERE id IN (SELECT test_entry_id FROM te_c2);

      UPDATE assignment a
      JOIN test_entry te ON te.assignment_id=a.id
      SET a.end_datetime=NULL
      WHERE te.id IN (SELECT test_entry_id FROM te_c2);

      CREATE TEMPORARY TABLE IF NOT EXISTS te_r1
      SELECT test_entry_id, participant_id, test_id FROM (
        SELECT ranked_word_set_id, word_id, selection, participant_id, test_id, test_entry_id FROM (
          SELECT t1.ranked_word_set_id, t1.word_id, t1.selection, tmp2.participant_id, te1.test_id, t1.test_entry_id FROM test_entry_ranked_word t1
          JOIN test_entry te1 ON te1.id=t1.test_entry_id
          JOIN tmp2 ON tmp2.assignment_id=te1.assignment_id
          WHERE t1.selection IS NOT NULL
          UNION ALL
          SELECT t2.ranked_word_set_id, t2.word_id, t2.selection, tmp3.participant_id, te2.test_id, t2.test_entry_id FROM test_entry_ranked_word t2
          JOIN test_entry te2 ON te2.id=t2.test_entry_id
          JOIN tmp3 ON tmp3.assignment_id=te2.assignment_id
          WHERE t2.selection IS NOT NULL
        ) t GROUP BY ranked_word_set_id, word_id, selection, participant_id, test_id HAVING COUNT(*)=1
      ) t2 GROUP BY test_entry_id;

      UPDATE test_entry
      SET adjudicate=true
      WHERE id IN (SELECT test_entry_id FROM te_r1);

      UPDATE assignment a
      JOIN test_entry te ON te.assignment_id=a.id
      SET a.end_datetime=NULL
      WHERE te.id IN (SELECT test_entry_id FROM te_r1);

      CREATE TEMPORARY TABLE IF NOT EXISTS te_r2
      SELECT test_entry_id, participant_id, test_id FROM (
        SELECT word_id, participant_id, test_id, test_entry_id FROM (
          SELECT t1.word_id, tmp2.participant_id, te1.test_id, t1.test_entry_id FROM test_entry_ranked_word t1
          JOIN test_entry te1 ON te1.id=t1.test_entry_id
          JOIN tmp2 ON tmp2.assignment_id=te1.assignment_id
          WHERE t1.ranked_word_set_id IS NULL
          UNION ALL
          SELECT t2.word_id, tmp3.participant_id, te2.test_id, t2.test_entry_id FROM test_entry_ranked_word t2
          JOIN test_entry te2 ON te2.id=t2.test_entry_id
          JOIN tmp3 ON tmp3.assignment_id=te2.assignment_id
          WHERE t2.ranked_word_set_id IS NULL
        ) t GROUP BY word_id, participant_id, test_id HAVING COUNT(*)=1
      ) t2 GROUP BY test_entry_id;

      UPDATE test_entry
      SET adjudicate=true
      WHERE id IN (SELECT test_entry_id FROM te_r2);

      UPDATE assignment a
      JOIN test_entry te ON te.assignment_id=a.id
      SET a.end_datetime=NULL
      WHERE te.id IN (SELECT test_entry_id FROM te_r2);

      CREATE TEMPORARY TABLE IF NOT EXISTS te_s
      SELECT id, participant_id, test_id FROM (
        SELECT audio_status, participant_status, participant_id, test_id, id FROM (
          SELECT t1.audio_status, t1.participant_status, tmp2.participant_id, t1.test_id, t1.id FROM test_entry t1
          JOIN tmp2 ON tmp2.assignment_id=t1.assignment_id
          UNION ALL
          SELECT t2.audio_status, t2.participant_status, tmp3.participant_id, t2.test_id, t2.id FROM test_entry t2
          JOIN tmp3 ON tmp3.assignment_id=t2.assignment_id
        ) t GROUP BY audio_status, participant_status, participant_id, test_id HAVING COUNT(*)=1
      ) t2 GROUP BY id;

      UPDATE test_entry
      SET adjudicate=true
      WHERE id IN (SELECT id FROM te_s);

      UPDATE assignment a
      JOIN test_entry te ON te.assignment_id=a.id
      SET a.end_datetime=NULL
      WHERE te.id IN (SELECT id FROM te_s);

      UPDATE assignment a
      JOIN test_entry_total_adjudicate t ON t.assignment_id=a.id
      SET end_datetime = NULL
      WHERE end_datetime IS NOT NULL
      AND t.adjudicate > 0

      UPDATE assignment a
      JOIN test_entry_total_deferred t ON t.assignment_id=a.id
      SET end_datetime = NULL
      WHERE end_datetime IS NOT NULL
      AND t.deferred > 0

    END IF;

  END //
DELIMITER ;

-- now call the procedure and remove the procedure
CALL update_adjudications();
DROP PROCEDURE IF EXISTS update_adjudications;

