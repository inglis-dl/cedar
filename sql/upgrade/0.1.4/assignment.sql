SELECT "Adding new columns to assignment" AS "";

ALTER TABLE assignment
ADD start_datetime DATETIME NOT NULL;

ALTER TABLE assignment
ADD end_datetime DATETIME DEFAULT NULL;
