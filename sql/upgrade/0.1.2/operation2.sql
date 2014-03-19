SELECT "Removing defunct operations" AS "";

DELETE FROM operation WHERE subject = "assignment" AND name = "add";
