-- remove high frequency test_entry_* push edit operation entries

SELECT "Removing high frequency test_entry_* push edit operation entries" AS "";

DELETE FROM activity
WHERE operation_id IN (
  SELECT id FROM operation
  WHERE type="push"
  AND name="edit"
  AND subject IN (
  'test_entry_alpha_numeric',
  'test_entry_classification',
  'test_entry_ranked_word',
  'test_entry_confirmation'
  )
);
