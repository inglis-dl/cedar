SELECT "Removing defunct operations from role" AS "";

DELETE FROM role_has_operation
WHERE operation_id IN (
  SELECT id FROM operation
  WHERE subject LIKE "test_entry%"
  AND name = "delete" );
