SELECT "Adding new operations" AS "";

-- collection

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "collection", "add", true,
"View a form for creating a new collection." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "collection", "add_participant", true,
"A form to add a participant to a collection." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "collection", "add_user", true,
"A form to add a user to a collection." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "delete", true,
"Removes a collection from the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "delete_participant", true,
"Remove a collection's participant." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "delete_user", true,
"Remove a collection's user." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "edit", true,
"Edits a collection's details." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "collection", "list", true,
"List collections in the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "new", true,
"Add a new collection to the system." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "new_participant", true,
"Add a participant to a collection." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "collection", "new_user", true,
"Add a user to a collection." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "collection", "view", true,
"View a collection's details." );

-- dictionary

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "transfer_word", true,
"View a form for transferring words from a dictionary." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary", "transfer_word", true,
"Transfer words from one dictionary to another." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "assignment", "reassign", true,
"View a form for reassigning an assignment to different users with no language restrictions." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "assignment", "reassign", true,
"Reassign an assignment to a different user with no language restrictions." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry_ranked_word", "delete", true,
"Deletes an intrusion entry for an ranked word test type." );

-- test_entry

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "test_entry", "classify_word", false, "Classifies a word candidate as either
candidate, primary, intrusion or variant." );

UPDATE IGNORE operation SET subject="test_entry" WHERE name="classify_word";
