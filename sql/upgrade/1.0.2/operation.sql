SELECT "Adding new operations" AS "";

-- dictionary

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "transfer_word", true,
"View a form for transferring words from a dictionary." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary", "transfer_word", true,
"Transfer words from one dictionary to another." );
