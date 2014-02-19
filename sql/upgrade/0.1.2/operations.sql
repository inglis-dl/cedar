SELECT "Adding new operations" AS "";

-- notes
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "note", "delete", true, "Removes a note from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "note", "edit", true, "Edits the details of a note." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "note", "list", false, "Displays a list of notes." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "note", "new", false, "Creates a new note." );

-- test
INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "test", "classify_word", false, "Classifies a word candidate as either candidate, 
primary, intrusion or variant." );
