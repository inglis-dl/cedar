SELECT "Removing defunct operations" AS "";

DELETE FROM operation WHERE subject LIKE "test_entry%" AND name = "delete";

SELECT "Adding new operations" AS "";

-- assignment

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "assignment", "report", true, "Download an assignment report." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "report", true, "Set up an assignment report." );

-- away_time

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "away_time", "add", true, "View a form for creating a new away time." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "away_time", "delete", true, "Removes an away time from the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "away_time", "edit", true, "Edits a away time's details." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "away_time", "list", true, "List away times in the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "away_time", "new", true, "Add a new away time to the system." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "away_time", "view", true, "View a away time's details." );

-- productivity

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "productivity", "report", true, "Download a productivity report." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "productivity", "report", true, "Set up a productivity report." );

-- test_entry
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry", "submit", true, "Submits a test_entry as an adjudication." );

-- typist

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "typist", "begin_break", true, "Register the start of a break." );
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "typist", "end_break", true, "Register the end of a break." );
