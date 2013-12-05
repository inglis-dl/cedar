-- -----------------------------------------------------
-- Operations
-- -----------------------------------------------------
SET AUTOCOMMIT=0;

-- dictionary
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "add", true, "View a form for creating a new dictionary." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary", "delete", true, "Removes a dictionary from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary", "edit", true, "Edits a dictionary's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "list", true, "List dictionarys in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary", "new", true, "Add a new dictionary to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "view", true, "View a dictionary's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "add_word", true, "A form to add a new word to a dictionary." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary", "delete_word", true, "Remove words from a dictionary." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "dictionary", "report", true, "Download a dictionary report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "report", true, "View a form to select a dictionary report." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "dictionary", "import", true, "View a form to import words from a CSV file." );

-- dictionary_import
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary_import", "new", true, "Add a new dictionary import to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary_import", "delete", true, "Removes a dictionay import from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "dictionary_import", "process", true, "Adds unique words to a dictionary from a CSV file." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "pull", "dictionary_import", "process", true, "Processes a words from a CSV file." );

-- ranked_word_set
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ranked_word_set", "add", true, "View a form for creating a new ranked word set." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "ranked_word_set", "delete", true, "Removes a ranked word set from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "ranked_word_set", "edit", true, "Edits a ranked word set's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ranked_word_set", "list", true, "List ranked word sets in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "ranked_word_set", "new", true, "Add a new ranked word set to the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "ranked_word_set", "view", true, "View a ranked word set's details." );

-- test
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test", "edit", true, "Edits a test's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test", "list", true, "List tests in the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test", "view", true, "View a test's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test", "add_dictionary", true, "A form to add a dictionary to a test." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test", "delete_dictionary", true, "Remove a dictionary from a test." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "test", "add_ranked_word_set", true, "A form to create a ranked word set to add to a test." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "test", "delete_ranked_word_set", true, "A form to create a ranked word set to add to a test." );

-- user
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "user", "add_cohort", true, "A form to add a cohort to a user." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "new_cohort", true, "Add a cohort to a user." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "user", "delete_cohort", true, "Remove a user's cohort." );

-- word
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "word", "add", true, "View a form for creating a new word." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "word", "delete", true, "Removes a word from the system." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "word", "edit", true, "Edits a word's details." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "word", "list", true, "Lists a dictionary's words." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "push", "word", "new", true, "Creates a new dictionary word." );
INSERT INTO operation( type, subject, name, restricted, description )
VALUES( "widget", "word", "view", true, "View the details of a dictionary's words." );

COMMIT;
