SELECT "Adding new language related operations" AS "";

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test_entry", "add_language", true,
"View languages to restrict the test_entry to." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry", "delete_language", true,
"Removes this test_entry's language restriction." );

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry", "new_language", true,
"Restricts this test_entry to a particular language." );
