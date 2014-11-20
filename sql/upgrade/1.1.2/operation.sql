-- remove push assignment reassign

SELECT "Removing push assignment reassign and updating widget operations" AS "";

DELETE FROM operation WHERE type="push" AND subject="assignment" AND name="reassign";

UPDATE operation SET description="View a form for reassigning an assignment" WHERE type="widget"
AND subject="assignment" AND name="reassign";
