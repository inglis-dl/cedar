SELECT "Updating test_entry_total_completed view" AS "";
CREATE  OR REPLACE VIEW test_entry_total_completed AS
SELECT assignment_id, SUM( IF( completed = 'incomplete', 0, 1 ) ) AS completed FROM test_entry
WHERE assignment_id IS NOT NULL
GROUP BY assignment_id;
