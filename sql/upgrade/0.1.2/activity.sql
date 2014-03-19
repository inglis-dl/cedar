-- remove all activity to assignment_add operations

DELETE FROM activity
WHERE operation_id IN (
  SELECT id
  FROM operation
  WHERE subject = "assignment"
  AND name = "add"
);
