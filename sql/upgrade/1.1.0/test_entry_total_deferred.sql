SELECT "Updating test_entry_total_deferred view" AS "";
CREATE  OR REPLACE VIEW test_entry_total_deferred AS
SELECT assignment_id, SUM( IF( deferred IS NULL, 0, IF( deferred = 'resolved', 0, 1 ) ) ) AS deferred FROM test_entry
WHERE assignment_id IS NOT NULL
GROUP BY assignment_id;
