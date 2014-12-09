SELECT "Removing assignment reassign push operation" AS "";

DELETE FROM role_has_operation
WHERE operation_id = (
  SELECT id FROM operation
  WHERE subject='assignment'
  AND type='push'
  AND name='reassign'
);
