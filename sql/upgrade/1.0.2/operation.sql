SELECT "Adding new operations" AS "";

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
