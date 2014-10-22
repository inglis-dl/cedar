SELECT "Updating assignment_total view" AS "";
CREATE  OR REPLACE VIEW assignment_total AS
SELECT assignment_id,
SUM( IF( deferred IS NULL, 0, IF( deferred = 'resolved', 0, 1 ) ) ) AS deferred,
SUM( IFNULL( adjudicate, 0 ) ) AS adjudicate,
SUM( completed ) AS completed
FROM test_entry
WHERE assignment_id IS NOT NULL
GROUP BY assignment_id;
