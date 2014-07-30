CREATE OR REPLACE VIEW assignment_total AS
SELECT assignment_id,
SUM( deferred ) AS deferred,
SUM( IFNULL( adjudicate, 0 ) ) AS adjudicate,
SUM( completed ) AS completed
FROM test_entry
WHERE assignment_id IS NOT NULL
GROUP BY assignment_id;
