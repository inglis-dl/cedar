SELECT "Adding new operations" AS "";

-- test_entry

INSERT IGNORE INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test_entry", "return", true, "Returns a test_entry to a typist
during an adjudication." );
