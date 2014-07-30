SELECT "Adding new assignment_total view" AS "";

CREATE OR REPLACE VIEW assignment_total AS
SELECT assignment.id AS assignment_id,
 test_entry_total.total AS total,
 test_entry_total_deferred.deferred AS deferred,
 test_entry_total_completed.completed AS completed,
 test_entry_total_adjudicate.adjudicate AS adjudicate
FROM assignment
JOIN test_entry_total ON test_entry_total.assignment_id=assignment.id
JOIN test_entry_total_deferred ON test_entry_total_deferred.assignment_id=assignment.id
JOIN test_entry_total_completed ON test_entry_total_completed.assignment_id=assignment.id
JOIN test_entry_total_adjudicate ON test_entry_total_adjudicate.assignment_id=assignment.id;
