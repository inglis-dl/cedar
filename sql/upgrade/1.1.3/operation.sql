-- update operation descriptions

SELECT "Updating operations" AS "";

UPDATE operation SET description="Submit a completed test_entry" WHERE type="push"
AND subject="test_entry" AND name="submit";
