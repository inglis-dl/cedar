SELECT "Removing defunct operations from role" AS "";

DELETE FROM role_has_operation
WHERE operation_id = (
  SELECT id FROM operation
  AND subject LIKE "test_entry%"
  AND name = "delete" );
